<?php
/**
 * User Roles.
 *
 * @author Aubrey Portwood <aubrey@webdevstudios.com>
 * @since 1.2.0
 *
 * @package  WebDevStudios\SSO
 */

namespace WebDevStudios\SSO;

/**
 * User Roles.
 *
 * @author Aubrey Portwood <aubrey@webdevstudios.com>
 * @since  1.2.0
 */
class Roles {

	/**
	 * Are we running this on the proxy or not?
	 *
	 * @since  1.2.0
	 * @author Aubrey Portwood <aubrey@webdevstudios.com>
	 *
	 * @var boolean
	 */
	private $is_proxy = false;

	/**
	 * Construct.
	 *
	 * @author Aubrey Portwood <aubrey@webdevstudios.com>
	 * @since  1.2.0
	 *
	 * @param boolean $is_proxy Are we running this on the proxy or not?.
	 */
	public function __construct( $is_proxy = false ) {
		$this->is_proxy = $is_proxy;
		$this->hooks();
	}

	/**
	 * Hooks to WordPress actions and filters.
	 *
	 * @author Aubrey Portwood <aubrey@webdevstudios.com>
	 * @since  1.2.0
	 */
	public function hooks() {

		// Only do this on the client.
		if ( ! $this->is_proxy ) {

			// When WDS SSO creates a user, let's modify that user after it's created.
			add_action( 'wds_sso_created_user', array( $this, 'maybe_block_user_if_assign_user_roles' ) );

			// Add settings to change normal operations and assign user roles.
			if ( is_multisite() ) {
				add_action( 'wpmu_options', array( $this, 'wpmu_settings' ) );
				add_action( 'update_wpmu_options', array( $this, 'save_wds_sso_wpmu_assign_user_roles' ) );
			} else {
				add_filter( 'admin_init', array( $this, 'settings' ) );
				add_filter( 'admin_init', array( $this, 'save_wds_sso_assign_user_roles' ) );
			}
			add_action( 'init', array( $this, 'maybe_block_admin' ) );
			add_action( 'admin_init', array( $this, 'maybe_unblock_user' ) );

			// Don't show double title.
			add_filter( 'wds_sso_section_callback_print_title', '__return_false' );

			// Control the initial roles.
			add_filter( 'wds_sso_set_user_role_all_sites_to', array( $this, 'maybe_set_initial_role' ), 10, 3 );
			add_filter( 'wds_sso_created_user', array( $this, 'maybe_revoke_super_admin' ) );

			// Mapping.
			add_filter( 'wds_sso_default_selective_role', array( $this, 'selective_role_mapping' ), 10, 3 );
			add_filter( 'wds_sso_revoke_selective_super_admin', array( $this, 'selective_super_admin_mapping' ), 10, 2 );

			// Notices.
			add_action( 'all_admin_notices', array( $this, 'selective_role_notices' ) );
		}
	}

	/**
	 * When selective roles are present, show this notice.
	 *
	 * @author Aubrey Portwood <aubrey@webdevstudios.com>
	 * @since  1.2.2
	 *
	 * @return void Early bail if we have shown it enough times.
	 */
	public function selective_role_notices() {

		/**
		 * Filter how many times we show this warning.
		 *
		 * @since  1.2.2
		 * @author Aubrey Portwood <aubrey@webdevstudios.com>
		 *
		 * @param int $skip_after How many times, e.g. 3.
		 */
		$skip_after = apply_filters( 'wds_selective_role_notice_count', 5 );
		if ( absint( get_option( 'wds_selective_role_notice', 0 ) ) >= $skip_after ) {

			// We showed this enough times.
			return;
		}

		$user = wp_get_current_user();
		$map  = $this->selective_role_map();

		// If we have rules (other than the above), tell them they need to enable selective roles.
		if ( ! empty( $map ) && $this->is_email_in_auth_domain( $user->user_email ) ) {
			?>
			<div class="notice notice-error">
				<p>
					<?php // Translators: message show when selective roles are enabled. ?>
					<?php echo wp_kses_post( sprintf( __( 'WDS SSO: You have some selective role rules, they won\'t take affect until you <a href="%1$s">assign user roles selectively</a>. All new users who sign in using WDS SSO will be administrators and super admin by default until you enable selective roles.', 'wds-sso' ), app()->settings->get_admin_settings_url() ) ); ?>
				</p>
			</div>
			<?php

			// Increase the number of times we've shown this.
			update_option( 'wds_selective_role_notice', absint( get_option( 'wds_selective_role_notice', 0 ) ) + 1, false );
		}
	}

	/**
	 * The selective role mapping.
	 *
	 * @author Aubrey Portwood <aubrey@webdevstudios.com>
	 * @since  1.2.0
	 *
	 * @return array
	 */
	public function selective_role_map() {

		/**
		 * Filter the selective role map.
		 *
		 * HOW THIS WORKS
		 * ==============
		 *
		 * This filter makes it easy (programmatically) to assign
		 * user roles per site (and super-admin access) when a user
		 * tries to login using WDS SSO.
		 *
		 * In the future a UI might automatically generate this too.
		 *
		 * E.g.:
		 *
		 *    add_filter( 'wds_sso_selective_role_map', function() {
		 *        return array(
		 *          'aubrey@webdevstudios.com' => array(
		 *              'revoke_super_admin' => true,
		 *              'roles'              => array(
		 *                  '/' => 'author',
		 *              ),
		 *           ),
		 *           'greg@webdevstudios.com' => array(
		 *              'revoke_super_admin' => false,
		 *              'roles'              => array(
		 *                  '/'       => 'administrator',
		 *                  '/en-us/' => 'author',
		 *              ),
		 *           ),
		 *         );
		 *     }
		 *
		 * Follow the below format to map users (by email) with specific
		 * roles on specific sites.
		 *
		 * In the example above, aubrey@webdevstudios.com will get super
		 * admin revoked, and on the site with path / will get an author role
		 * by default. Greg will retain super admin, and on / and /en-us he will
		 * be an author. On all other sites he would be subscriber.
		 *
		 * Note, on single-site installs revoke_super_admin doesn't matter, and
		 * the site's role can be set using the `/` option (site).
		 *
		 * SETTING WILDCARD ROLE ASSIGNMENTS
		 * =================================
		 *
		 *    add_filter( 'wds_sso_selective_role_map', function() {
		 *      return array(
		 *        // For all users (*).
		 *        '*' => array(
		 *          'revoke_super_admin' => false,
		 *          'roles'              => array(
		 *            // For all sites (*) set as administrator.
		 *            '*' => 'administrator',
		 *          ),
		 *        ),
		 *      );
		 *    } );
		 *
		 * In the above example you can say, for all users (*), do not revoke
		 * super admin (false), and for all sites (*) set their role to administrator.
		 *
		 * @since  1.2.0
		 * @author Aubrey Portwood <aubrey@webdevstudios.com>
		 *
		 * @param array $selective_role_map The selective role mappings.
		 */
		return apply_filters( 'wds_sso_selective_role_map', array() );
	}

	/**
	 * Get the selective mapping for a user.
	 *
	 * @author Aubrey Portwood <aubrey@webdevstudios.com>
	 * @since  1.2.0
	 *
	 * @param  string $user_email A user's e-mail.
	 * @return array              The mapping, an empty array if they have none.
	 */
	public function get_selective_role_map_for( $user_email ) {
		$map = $this->selective_role_map();
		return isset( $map[ $user_email ] ) ? $map[ $user_email ] : array();
	}

	/**
	 * Set role based on role mapping.
	 *
	 * @author Aubrey Portwood <aubrey@webdevstudios.com>
	 * @since  1.2.0
	 *
	 * @param  string         $role    The default role from filter.
	 * @param  mixed          $site_id On what site (ID), pass false for single-site.
	 * @param  WP_User|string $user    The User, or an email of a potential user.
	 * @return string                  The role, either the default (subscriber) or the role in the mapping.
	 */
	public function selective_role_mapping( $role, $site_id, $user ) {
		$map = $this->selective_role_map();

		// On single-site.
		if ( ! is_multisite() && false === $site_id ) {
			if ( is_a( $user, 'WP_User' ) ) {

				// We got a user object for some reason...
				$user = $user->user_email;
			}

			if ( ! $this->is_email_in_auth_domain( $user ) ) {

				// Don't block the user.
				update_option( "wds_sso_needs_role_{$user}_dont_block", true, false );

				// The user has to be in an allowed domain.
				return $role;
			}

			// Try and get the mapping for this user.
			$user_map = $this->get_selective_role_map_for( $user );

			// We have some mappings for the user.
			if ( ! empty( $user_map ) ) {
				if ( isset( $user_map['roles']['/'] ) ) {

					// Don't block the user.
					update_option( "wds_sso_needs_role_{$user}_dont_block", true, false );

					// We have something specific for this user for the main site.
					return $user_map['roles']['/'];

				} elseif ( isset( $user_map['roles']['*'] ) ) {

					// Don't block the user.
					update_option( "wds_sso_needs_role_{$user}_dont_block", true, false );

					// We have a wildcard for the user set.
					return $user_map['roles']['*'];
				}

			// No mappings for the user.
			} else {
				if ( isset( $map['*']['roles']['/'] ) ) {

					// Don't block the user.
					update_option( "wds_sso_needs_role_{$user}_dont_block", true, false );

					// They have a wildcard for all users for / specifically.
					return $map['*']['roles']['/'];
				} elseif ( isset( $map['*']['roles']['*'] ) ) {

					// Don't block the user.
					update_option( "wds_sso_needs_role_{$user}_dont_block", true, false );

					// They have a wildcard set for all sites, use that.
					return $map['*']['roles']['*'];
				}
			}

			// Block the user and just use the normal role.
			return $role;
		} // End single site, start multi-site stuff:

		if ( ! is_a( $user, 'WP_User' ) ) {
			return $role;
		}

		// Multi-site, get the map for a specific user.
		$site = get_site( $site_id );
		if ( ! is_a( $site, 'WP_Site' ) ) {
			return $role;
		}

		// We have a mapping for this site specifically...
		if ( isset( $map[ $user->user_email ]['roles'][ $site->path ] ) ) {

			// We found a mapped role for this user, let's make sure they aren't blocked to use it.
			delete_user_meta( $user->ID, 'wds_sso_needs_role' );

			// Use the specific role.
			return $map[ $user->user_email ]['roles'][ $site->path ];

		// We don't have something specific, but there's a wildcard present in the list for this user.
		} elseif ( isset( $map[ $user->user_email ]['roles']['*'] ) ) {
			/*
			 * HOW TO USE WILDCARDS (Multisite)
			 * ================================
			 *
			 * E.g.:
			 *
			 *    add_filter( 'wds_sso_selective_role_map', function() {
			 *      return array(
			 *        'aubrey@webdevstudios.com' => array(
			 *          'revoke_super_admin' => true,
			 *          'roles'              => array(
			 *            '/a/' => 'author',
			 *            '/b/' => 'editor',
			 *            '*'   => 'subscriber',
			 *          ),
			 *        ),
			 *      );
			 *    } );
			 *
			 * In the above example, aubrey@webdevstudios.com will
			 * have super admin revoked. On site /a/ he will be an author,
			 * on site /b/ he will be an editor, and on all other sites (*)
			 * he will be a subscriber.
			 *
			 * But in this example:
			 *
			 *    add_filter( 'wds_sso_selective_role_map', function() {
			 *      return array(
			 *        'aubrey@webdevstudios.com' => array(
			 *          'revoke_super_admin' => true,
			 *          'roles'              => array(
			 *            '/a/' => 'author',
			 *            '/b/' => 'editor',
			 *          ),
			 *        ),
			 *        '*'=> array(
			 *          'roles' => array(
			 *            '*' => 'contributor',
			 *          ),
			 *        ),
			 *      );
			 *    } );
			 *
			 * On site /a/ he will be an author, on site /b/ he will be an
			 * editor, on all other sites he will be a contributor (*) => (*).
			 * If you DO NOT supply a fallback (*) in the specific user's roles,
			 * any global for all sites, e.g. `'*'=> array`, will be used as the fallback.
			 *
			 * For example:
			 *
			 *    add_filter( 'wds_sso_selective_role_map', function() {
			 *      return array(
			 *        'aubrey@webdevstudios.com' => array(
			 *          'revoke_super_admin' => true,
			 *          'roles'              => array(
			 *            '/a/' => 'author',
			 *            '/b/' => 'editor',
			 *          ),
			 *        ),
			 *      );
			 *    } );
			 *
			 * In this case, aubrey will be assigned roles for /a/ and /b/, but for all other
			 * sites he will get the default role (subscriber).
			 */

			// We found a mapped role for this user, let's make sure they aren't blocked to use it.
			delete_user_meta( $user->ID, 'wds_sso_needs_role' );

			// Use the wildcard....
			return $map[ $user->user_email ]['roles']['*'];

		// We have something specific for all users for this specific site...
		} elseif ( isset( $map['*']['roles'][ $site->path ] ) ) {

			// We found a mapped role for this user, let's make sure they aren't blocked to use it.
			delete_user_meta( $user->ID, 'wds_sso_needs_role' );

			// Use the wildcard for all users and this specific site.
			return $map['*']['roles'][ $site->path ];

		// We have a wildcard for all users and all sites...
		} elseif ( isset( $map['*']['roles']['*'] ) ) {

			// We found a mapped role for this user, let's make sure they aren't blocked to use it.
			delete_user_meta( $user->ID, 'wds_sso_needs_role' );

			// Use that as the fallback.
			return $map['*']['roles']['*'];
		}

		return $role;
	}

	/**
	 * Set super admin based on selective mapping.
	 *
	 * @author Aubrey Portwood <aubrey@webdevstudios.com>
	 * @since  1.2.0
	 *
	 * @param  boolean $revoke  Are we revoking it now?.
	 * @param  int     $user_id For this User (ID).
	 * @return boolean          If we are revoking it or not.
	 */
	public function selective_super_admin_mapping( $revoke, $user_id ) {
		$map = $this->selective_role_map();

		// We have a blanked mapping for all sites.
		if ( isset( $map['*']['revoke_super_admin'] ) ) {
			return $map['*']['revoke_super_admin'];
		}

		$user     = get_userdata( $user_id );
		$user_map = $this->get_selective_role_map_for( $user->user_email );

		if ( isset( $user_map['revoke_super_admin'] ) ) {

			// This specific users has a setting.
			return $user_map['revoke_super_admin'];
		}

		if ( isset( $map['*']['revoke_super_admin'] ) ) {

			// We have a wildcard set, use that setting.
			return $map['*']['revoke_super_admin'];
		}

		return $revoke;
	}

	/**
	 * When selecting specific roles, always set SSO users to subscriber.
	 *
	 * @author Aubrey Portwood <aubrey@webdevstudios.com>
	 * @since  1.2.0
	 *
	 * @param  string  $role The role they want to set it to (usually administrator).
	 * @param  int     $site_id The Site ID.
	 * @param  WP_User $user The User.
	 * @return string       'subscriber' if they are assigning roles.
	 */
	public function maybe_set_initial_role( $role, $site_id, $user ) {

		/**
		 * Filter the default selective role.
		 *
		 * @since  1.2.0
		 * @author Aubrey Portwood <aubrey@webdevstudios.com>
		 *
		 * @param string $selective_role Default Role, 'subscriber' (yes, subscriber is correct).
		 * @param int     $site_id       The Site ID we're adding them to.
		 * @param WP_User $user          The User we're adding to the site.
		 */
		$selective_role = apply_filters( 'wds_sso_default_selective_role', 'subscriber', $site_id, $user );

		// If they are selecting roles, make sure the user is a subscriber.
		return $this->get_wds_sso_assign_user_roles() ? $selective_role : $role;
	}

	/**
	 * When selecting specific roles, always revoke super admin.
	 *
	 * @author Aubrey Portwood <aubrey@webdevstudios.com>
	 * @since  1.2.0
	 *
	 * @param  int $user_id The new SSO User's ID.
	 */
	public function maybe_revoke_super_admin( $user_id ) {
		if ( is_multisite() && $this->get_wds_sso_assign_user_roles() ) {

			/**
			 * Filter whether or not we revoke someone's role when doing selective roles.
			 *
			 * @since  1.2.0
			 * @author Aubrey Portwood <aubrey@webdevstudios.com>
			 *
			 * @param boolean $revoke  Set to true to revoke super admin when using selective roles.
			 * @param int     $user_id The User we'll be revoking super admin from.
			 */
			$revoke = apply_filters( 'wds_sso_revoke_selective_super_admin', true, $user_id );
			if ( $revoke ) {

				// If they are selecting roles, make sure our SSO user does not have super admin.
				revoke_super_admin( $user_id );
			}
		}
	}

	/**
	 * Block access to WP Admin with a appropriate message for SSO users that haven't been assigned a role.
	 *
	 * @author Aubrey Portwood <aubrey@webdevstudios.com>
	 * @since  1.2.0
	 *
	 * @return void Early bails if we don't need to block them.
	 */
	public function maybe_block_admin() {
		if ( ! is_user_logged_in() ) {

			// No user......no blocky.
			return;
		}

		if ( ! stristr( $_SERVER['REQUEST_URI'], '/wp-admin' ) ) {

			// The user isn't trying to get into wp-admin, don't block them.
			return;
		}

		// Get the user.
		$user = wp_get_current_user();

		// Is the option override set?, see self::selective_role_mapping(), if it's not set it to the meta.
		$dontblock = get_option( "wds_sso_needs_role_{$user->user_email}_dont_block" );
		if ( $dontblock ) {

			// Let's delete this option (cleanup).
			delete_option( "wds_sso_needs_role_{$user->user_email}_dont_block" );

			// Also delete this one, it doesn't apply anymore.
			delete_user_meta( $user->ID, 'wds_sso_needs_role' );

			// Something set the option override, we respect this first.
			return;
		}

		// Is the user meta set to block them?
		$user_meta_blocked = get_user_meta( $user->ID, 'wds_sso_needs_role', false );

		// If the user was made blocked by WDS SSO...
		if ( $user_meta_blocked ) {
			$admin_email = get_option( 'admin_email' );

			// Translators: Show them a more appropriate message about not having WP Admin access.
			wp_die( wp_kses_post( sprintf( __( 'This site requires that an administrator assign your user a role.<br /> %s, has been notified!', 'wds-sso' ), sprintf( '<a href="mailto:%s">%s</a>', $admin_email, $admin_email ) ), esc_html__( 'WDS SSO Role Assignment Required', 'wds-sso' ) ) );
			exit;
		}
	}

	/**
	 * Settings.
	 *
	 * @author Aubrey Portwood <aubrey@webdevstudios.com>
	 * @since  1.2.0
	 *
	 * @return void Early bail if the user cannot manage this option.
	 */
	public function settings() {
		if ( ! $this->is_admin() ) {
			return;
		}

		app()->settings->add_settings_section();
		add_settings_field( 'wds_sso_assign_user_roles', __( 'Role Management', 'wds-sso' ), array( $this, 'template' ), 'general', 'wds_sso_field_section', array() );
	}

	/**
	 * Role settings for multisite.
	 *
	 * @return void
	 * @author Justin Foell <justin.foell@webdevstudios.com>
	 * @since  2.0.0
	 */
	public function wpmu_settings() {
		if ( ! $this->is_admin() ) {
			return;
		}

		?>
		<table class="form-table">
			<tbody>
				<tr>
					<th scope="row"><label for="wds_sso_assign_user_roles"><?php esc_html_e( 'Role Management', 'wds-sso' ); ?></label></th>
					<td><?php $this->template(); ?></td>
				</tr>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Settings template.
	 *
	 * @author Aubrey Portwood <aubrey@webdevstudios.com>
	 * @since  1.2.0
	 */
	public function template() {
		?>
			<label for="wds_sso_assign_user_roles"><input type="checkbox" name="wds_sso_assign_user_roles" <?php checked( $this->get_wds_sso_assign_user_roles() ); ?> /> <?php esc_html_e( 'Assign User Roles Selectively', 'wds-sso' ); ?></label>
			<p class="description"><?php echo wp_kses_post( __( 'With this option disabled, all WDS SSO users will be automatically granted super admin and administrative user roles on all sites.<br><small>Enable to assign WDS SSO user roles selectively.<br>You will receive an email anytime a WebDev user tries to sign in using WDS SSO and you will have to grant them a role to allow them access to the admin.<br>When enabled users will not have super admin rights, and will be given a Subscriber role until one is set. They will also be locked out of the admin until you respond.</small>', 'wds-sso' ) ); ?></p>
			<?php wp_nonce_field( 'wds_sso_assign_user_roles', '_wds_sso_assign_user_roles_nonce' ); ?>
		<?php
	}

	/**
	 * Save the Assign Users Role setting.
	 *
	 * @author Aubrey Portwood <aubrey@webdevstudios.com>
	 * @since  1.2.0
	 *
	 * @return void Early bail if we're not saving.
	 */
	public function save_wds_sso_assign_user_roles() {
		// @codingStandardsIgnoreLine: REQUEST access okay here, we do a nonce check below.
		if ( ! isset( $_REQUEST['option_page'] ) || 'general' !== $_REQUEST['option_page'] ) {
			return;
		}

		// @codingStandardsIgnoreLine: REQUEST access okay here, we do a nonce check below.
		if ( ! isset( $_REQUEST['action'] ) || 'update' !== $_REQUEST['action'] ) {
			return;
		}

		if ( ! $this->is_admin() ) {
			return;
		}

		check_admin_referer( 'wds_sso_assign_user_roles', '_wds_sso_assign_user_roles_nonce' );

		$this->update_role_settings_from_post();
	}

	/**
	 * Save role settings on multi-site.
	 *
	 * @return void Early bail if we're not saving.
	 * @author Justin Foell <justin.foell@webdevstudios.com>
	 * @since  2.0.0
	 */
	public function save_wds_sso_wpmu_assign_user_roles() {
		if ( ! $this->is_admin() ) {
			return;
		}

		check_admin_referer( 'siteoptions' );

		$this->update_role_settings_from_post();
	}

	/**
	 * Update role settings from $_POST from single or multi-site settings.
	 *
	 * @author Justin Foell <justin.foell@webdevstudios.com>
	 * @since  2.0.0
	 */
	private function update_role_settings_from_post() {
		if ( isset( $_POST['wds_sso_assign_user_roles'] ) ) { // phpcs:ignore -- Nonce already checked.

			// Enable.
			if ( is_multisite() ) {

				// On multisite, save site option.
				update_site_option( 'wds_sso_assign_user_roles', isset( $_POST['wds_sso_assign_user_roles'] ) ? true : false ); // phpcs:ignore -- Nonce already checked.
			} else {
				update_option( 'wds_sso_assign_user_roles', isset( $_POST['wds_sso_assign_user_roles'] ) ? true : false ); // phpcs:ignore -- Nonce already checked.
			}
		} else {

			// Disable.
			if ( is_multisite() ) {

				// On multisite, delete site option.
				delete_site_option( 'wds_sso_assign_user_roles' );
			} else {
				delete_option( 'wds_sso_assign_user_roles' );
			}
		}
	}

	/**
	 * Get the Assign Users Roles setting.
	 *
	 * @author Aubrey Portwood <aubrey@webdevstudios.com>
	 * @since  1.2.0
	 *
	 * @return mixed Value of get_option().
	 */
	public function get_wds_sso_assign_user_roles() {
		return is_multisite()

			// On multisite, always use site option.
			? get_site_option( 'wds_sso_assign_user_roles', false )

			// Fallback to single-site option.
			: get_option( 'wds_sso_assign_user_roles', false );
	}

	/**
	 * When WDS SSO creates a SSO user.
	 *
	 * @author Aubrey Portwood <aubrey@webdevstudios.com>
	 * @since  1.2.0
	 *
	 * @param  int $user_id The user's ID.
	 * @return void         Early bail if it's not a valid user ID.
	 */
	public function maybe_block_user_if_assign_user_roles( $user_id ) {
		if ( ! is_numeric( $user_id ) ) {
			return;
		}

		if ( ! $this->get_wds_sso_assign_user_roles() ) {

			// For this site, the option to assign user roles is not enabled, allow the user to keep their default role.
			return;
		}

		// Get that newly created user.
		$user = get_userdata( $user_id );
		if ( ! is_a( $user, 'WP_User' ) ) {

			// This is not a WP User for some reason, bail!
			return;
		}

		// This user has a role, but we need to block them until the role is confirmed.
		if ( $this->block_user( $user_id ) ) {

			// Email the admin a link to un-block them.
			$this->unblock_email( $user_id, $user );
		}
	}

	/**
	 * Mail the admin about a blocked user.
	 *
	 * @author Aubrey Portwood <aubrey@webdevstudios.com>
	 * @since  1.2.0
	 *
	 * @param int     $user_id The user's ID.
	 * @param WP_User $user    The WP_User object.
	 * @return boolean         True if the mail was sent, false if not.
	 */
	public function unblock_email( $user_id, $user ) {
		if ( ! is_numeric( $user_id ) || ! is_a( $user, 'WP_User' ) ) {
			return false;
		}

		// Does the mapping say this user will get a role?
		$role = $this->selective_role_mapping( 'none', is_multisite() ? get_current_blog_id() : false, $user );
		if ( 'none' !== $role ) {

			// The user will be assigned a role based on mapping, no need for the email.
			return;
		}

		// Send an email for the admin to set/confirm a role (the confirm= tells WP to remove the blockage).
		$edit_user_url    = admin_url( "user-edit.php?user_id={$user_id}&wds_sso_needs_role_confirm=yes#user_login" );
		$edit_user_url_mu = network_admin_url( "user-edit.php?user_id={$user_id}&wds_sso_needs_role_confirm=yes#user_login" );
		$site             = get_bloginfo( 'name' );
		$message          = str_replace( "\t", '', "
			Because you've requested that all new WDS SSO users be assigned roles selectively,
			you've been sent this email because {$user->display_name} needs a role on site '{$site}'.

			To set the user's role on site '{$site}', please use the below link:
			{$edit_user_url}

			Please note that the above link will help you assign this user a role
			on site '{$site}', you may need to edit their role on other sites in the
			network via individual site dashboards.
		" );

		if ( is_multisite() ) {

			// Multi-site users would have been granted super-admin privileges.
			$message .= str_replace( "\t", '', "
				To grant SUPER ADMIN for {$user->display_name}, please use the link below:
				{$edit_user_url_mu}
			" );
		}

		// Email the admin about the user needing a role.
		return wp_mail( get_option( 'admin_email' ), "(WDS SSO) {$user->display_name} has requested a role on site {$site}.", $message );
	}

	/**
	 * Maybe unblock a blocked user.
	 *
	 * @author Aubrey Portwood <aubrey@webdevstudios.com>
	 * @since  1.2.0
	 *
	 * @return void Early bail if they aren't blocked.
	 */
	public function maybe_unblock_user() {

		// If we're on the edit screen and we're confirming the user...
		if ( is_admin() && $this->is_admin() && stristr( $_SERVER['REQUEST_URI'], 'wds_sso_needs_role_confirm=' ) ) {

			// The user's ID. @codingStandardsIgnoreLine: GET access okay here.
			$user_id = isset( $_GET['user_id'] ) ? absint( $_GET['user_id'] ) : 0;

			// Is the user blocked (the secret is actually stored in the meta BTW)?
			$blocked = get_user_meta( $user_id, 'wds_sso_needs_role', true );
			if ( ! $blocked ) {

				// The user has a role, bail.
				return;
			}

			$secret = md5( "wds_sso_needs_role_{$user_id}" );
			if ( (string) $secret === (string) $blocked ) {

				// If the admin followed the link, and the user was role-less, then let them keep their role.
				delete_user_meta( $user_id, 'wds_sso_needs_role' );
			}
		}
	}

	/**
	 * Block a user from accessing WP Admin.
	 *
	 * @author Aubrey Portwood <aubrey@webdevstudios.com>
	 * @since  1.2.0
	 *
	 * @param  int $user_id The User's ID.
	 * @return boolean      True if we blocked them, false if not.
	 */
	public function block_user( $user_id ) {
		if ( ! is_numeric( $user_id ) ) {
			return false;
		}

		// Generate a secret to compare for later (vs using a nonce).
		$secret = md5( "wds_sso_needs_role_{$user_id}" );

		// Tell the User screen that the user needs to be assigned a role and save it as the secret.
		update_user_meta( $user_id, 'wds_sso_needs_role', $secret );
		return true;
	}

	/**
	 * Whether this user is part of the authorized domain list.
	 *
	 * @param string $email Email address of a user.
	 * @return boolean
	 * @author Justin Foell <justin.foell@webdevstudios.com>
	 * @since  2.0.0
	 */
	public function is_email_in_auth_domain( $email ) {
		if ( ! is_email( $email ) ) {
			return false;
		}

		$parts = explode( '@', $email, 2 );

		if ( ! isset( $parts[1] ) ) {
			return false;
		}

		if ( in_array( $parts[1], app()->settings->get_auth_domains(), true ) ) {
			return true;
		}
		return false;
	}

	/**
	 * Whether the current user can perform SSO admin tasks.
	 *
	 * @return boolean
	 * @author Justin Foell <justin.foell@webdevstudios.com>
	 * @since  2.0.0
	 */
	public function is_admin() {
		if ( ! ( is_multisite() ? is_super_admin() : current_user_can( 'manage_options' ) ) ) {
			return false;
		}

		// Filter to limit certain users from changing the setting.
		if ( ! apply_filters( 'wds_sso_user_can_manage', true, wp_get_current_user() ) ) {
			return false;
		}
		return true;

	}
}
