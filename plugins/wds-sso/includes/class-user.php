<?php
/**
 * User-related code.
 *
 * @author Pavel Korotenko
 * @since 1.0.0
 *
 * @package  WebDevStudios\SSO
 */

namespace WebDevStudios\SSO;

/**
 * User class.
 *
 * @author Pavel Korotenko
 * @since  1.0.0
 */
class User {

	/**
	 * Construct.
	 *
	 * @author Pavel Korotenko
	 * @since  1.0.0
	 */
	public function __construct() {
		$this->hooks();
	}

	/**
	 * Hooks to WordPress actions and filters.
	 *
	 * @author Pavel Korotenko
	 * @since  1.0.0
	 */
	public function hooks() {
		add_action( 'current_screen', array( $this, 'add_user_password_restrictions' ) );
		add_action( 'admin_head', array( $this, 'hide_user_email' ), 99 );
		add_action( 'user_profile_update_errors', array( $this, 'prevent_email_change' ), 10, 3 );
		add_action( 'wp_login', array( $this, 'ensure_sso_user_access_all_sites' ), 10, 2 );
		add_action( 'init', array( $this, 'reset_password_on_logout' ) );
		add_action( 'password_reset', array( $this, 'dont_allow_sso_password_resets' ), 10, 2 );
	}

	/**
	 * Don't allow SSO Users to reset their password.
	 *
	 * @author Aubrey Portwood <aubrey@webdevstudios.com>
	 * @since  1.2.2
	 *
	 * @param  WP_User $user     The User.
	 * @param  string  $password The password.
	 * @return void
	 */
	public function dont_allow_sso_password_resets( $user, $password ) {
		if ( ! $this->is_sso_user( $user->ID ) ) {
			return;
		}

		// Let's reset the password, maybe this is one of those angry fellas.
		$this->reset_password( $user->ID );

		// Stringify the user info and site.
		$userinfo = print_r( $user, true ); // @codingStandardsIgnoreLine: print_r intentional here.
		$site     = get_bloginfo( 'name' );

		// translators: Message.
		$message_html = __( '
			You are being sent this email because an SSO user attempted to reset their
			password on %1$s at %2$s. Below is a dump of the user\'s information.

			%3$s
		', 'wds-sso' );

		// translators: Here we're telling on the user...
		wp_mail( get_option( 'admin_email' ), sprintf( __( 'SSO User attempted to reset password on %1$s', 'wds-sso' ), $site ), sprintf( str_replace( "\t", '', $message_html ), $site, home_url(), $userinfo ) );

		$login_html = sprintf( '<a href="wp-login.php">%1$s</a>', __( 'login', 'wds-sso' ) );

		// translators: And stop the reset password process.
		wp_die( wp_kses_post( sprintf( __( 'Sorry, but you can\'t reset your password because you are an SSO user. You must %1$s using the SSO Login button.', 'wds-sso' ), $login_html ) ), esc_html__( "Cheat'n Huh?", 'wds-sso' ) );
	}

	/**
	 * Reset password on logout.
	 *
	 * We reset the users password every time they login and logout,
	 * this helps us prevent future logins when we remove a user via
	 * Google Apps.
	 *
	 * @author Aubrey Portwood <aubrey@webdevstudios.com>
	 * @since  1.2.0
	 *
	 * @return void Early bail if not an SSO User.
	 */
	public function reset_password_on_logout() {
		if ( ! $this->is_sso_user( get_current_user_id() ) ) {

			// Only happens for SSO users.
			return;
		}

		if ( isset( $_SERVER['REQUEST_URI'] ) && strstr( $_SERVER['REQUEST_URI'], 'wp-login.php?action=logout' ) ) {
			$this->reset_password();
		}
	}

	/**
	 * Reset user's password.
	 *
	 * @author Aubrey Portwood <aubrey@webdevstudios.com>
	 * @since  1.2.0
	 *
	 * @param  int $user_id The User's ID.
	 * @return void         Early bail if not an SSO User.
	 */
	public function reset_password( $user_id = 0 ) {
		if ( 0 === $user_id ) {
			$user_id = get_current_user_id();
		}

		if ( ! $this->is_sso_user( $user_id ) ) {

			// Only happens for SSO users.
			return;
		}

		// Reset the password.
		wp_set_password( wp_generate_password( wp_rand( 50, 100 ) ), $user_id );
	}

	/**
	 * Add SSO users to all sites in network.
	 *
	 * @author Aubrey Portwood
	 * @since  1.1.0
	 *
	 * @param string  $user_login The users' login username.
	 * @param WP_User $user       The WP_User object.
	 *
	 * @return void Early bail if we don't need to do this.
	 */
	public function ensure_sso_user_access_all_sites( $user_login, $user ) {
		if ( ! is_multisite() ) {

			// Never needed for single site.
			return;
		}

		if ( ! $this->is_sso_user( $user->ID ) ) {

			// Only happens for SSO users.
			return;
		}

		$sites = get_sites( array(
			'fields' => 'ids',

			/**
			 * Filter the number of sites we can add an SSO user to.
			 *
			 * @author Aubrey Portwood
			 * @since  1.1.0
			 *
			 * @var int The number that of sites we'll try and associate with the user.
			 */
			'number' => apply_filters( 'wds_sso_get_sites_limit', 500 ),
		) );

		foreach ( $sites as $site_id ) {

			/**
			 * Filter what role is assigned to all new WDS SSO users on all sites.
			 *
			 * @since 1.2.0
			 * @author Aubrey Portwood <aubrey@webdevstudios.com>
			 *
			 * @param string $role The role.
			 */
			$role = apply_filters( 'wds_sso_set_user_role_all_sites_to', 'administrator', $site_id, $user );

			// Add the user to all the sites on the network.
			add_user_to_blog( $site_id, $user->ID, $role );
		}
	}

	/**
	 * Start the user's session.
	 *
	 * Adds a transient that signifies the user can continue to be logged in
	 * for a certain amount of time from now.
	 *
	 * @author Aubrey Portwood
	 * @since  1.0.0
	 *
	 * @param  int $user_id The user's ID, default to the current user's.
	 */
	public function start_session( $user_id = false ) {

		// What is the user ID for the session?
		$user_id = is_int( $user_id )
			? $user_id // The user ID if they passed one.
			: get_current_user_id(); // The current logged in user if none.

		// Save session.
		set_transient( $this->get_session_key( $user_id ), $user_id, app()->auth->get_session_time() );
	}

	/**
	 * Get the user's session key.
	 *
	 * @author Aubrey Portwood
	 * @since  1.0.0
	 *
	 * @param  int $user_id The User ID, default to the current user.
	 * @return string       The user's session transient key.
	 */
	public function get_session_key( $user_id = 0 ) {
		if ( 0 === $user_id ) {
			$user_id = get_current_user_id();
		}

		return "wds_sso_session_for_user_{$user_id}";
	}

	/**
	 * Add user profile restrictions.
	 *
	 * @author Pavel Korotenko
	 * @author Justin Foell
	 * @since  1.0.0
	 *
	 * @param WP_Screen $screen WP-Admin screen, restriction only takes place on user edit screen.
	 * @return void If not SSO user.
	 */
	public function add_user_password_restrictions( $screen ) {
		if ( 'user-edit' === (string) $screen->id || 'profile' === (string) $screen->id ) {
			$is_sso_user = $this->is_sso_user();

			// Bail if not SSO user.
			if ( ! $is_sso_user ) {
				return;
			}

			if ( $this->editing_other_user() ) {

				// Bail, we're editing another user, so allow password fields.
				return;
			}

			// Hide password change/reset fields.
			add_filter( 'show_password_fields', '__return_false' );
		}
	}

	/**
	 * Prevent email update on saving process for all SSO users.
	 *
	 * @author Pavel Korotenko
	 * @since  1.0.0
	 *
	 * @param \WP_Error $errors Any list of errors from the process.
	 * @param boolean   $update Rather this is an updated request or not.
	 * @param Object    $user   The user object, for some reason isn't an instance of \WP_User.
	 *
	 * @see    \edit_user() For the reason behind not using WP_User.
	 *
	 * @return void If not SSO user.
	 */
	public function prevent_email_change( $errors, $update, $user ) {

		// Bail if not SSO user.
		if ( ! $this->is_sso_user( $user->ID ) ) {
			return;
		}

		if ( $this->editing_other_user() ) {

			// Bail, we're editing another user, allow the change.
			return;
		}

		// Get old user data.
		$old_user_data = get_user_by( 'id', $user->ID );

		// If new email is different, restore to the old value.
		if ( $user->user_email !== $old_user_data->user_email ) {
			$user->user_email = $old_user_data->user_email;
		}
	}

	/**
	 * Are we editing another user's profile?
	 *
	 * @author Aubrey Portwood
	 * @since  1.1.0
	 *
	 * @return boolean True if we are, false if we're editing our own.
	 */
	public function editing_other_user() {

		// Get the screen.
		$screen = get_current_screen();

		if ( 'user-edit' === $screen->id ) {

			// The screen->id will be 'profile' if we're editing ourself.
			return true;
		}

		// We might be editing ourselves.
		return false;
	}

	/**
	 * Hide email field from profile for all SSO users.
	 *
	 * @author Pavel Korotenko
	 * @since  1.0.0
	 *
	 * @return void If not SSO user.
	 */
	public function hide_user_email() {

		$is_sso_user = $this->is_sso_user();

		// Bail if not SSO user.
		if ( ! $is_sso_user ) {
			return;
		}

		if ( $this->editing_other_user() ) {

			// Bail, we're editing another user, allow the change.
			return;
		}

		?>
		<style type="text/css">
			.user-email-wrap {
				display: none;
			}
		</style>
		<?php
	}

	/**
	 * Create SSO user.
	 *
	 * @author Pavel Korotenko
	 * @since  1.0.0
	 *
	 * @param array   $user_info User info data.
	 * @param string  $role User role, defaults to false.
	 * @param boolean $is_sso_user Boolean for adding _wds_sso linked user.
	 *
	 * @return boolean|int User id or false if user already exists.
	 */
	public function create_user( $user_info, $role = false, $is_sso_user = true ) {

		// Default arguments.
		$defaults = array(
			'user_email' => '',
			'user_login' => '',
			'first_name' => '',
			'last_name'  => '',
			'locale'     => '',
			'user_pass'  => wp_generate_password( wp_rand( 50, 100 ) ),
			'role'       => $role,
		);

		// Parse incoming $user_info into an array and merge it with $defaults.
		$user_args = wp_parse_args( $user_info, $defaults );

		// Reset user email to a different key WP uses.
		if ( empty( $user_args['user_email'] ) && ! empty( $user_args['email'] ) ) {
			$user_args['user_email'] = $user_args['email'];
			unset( $user_args['email'] );
		}

		// Bail if there's already registered user with this email.
		if ( email_exists( $user_args['user_email'] ) ) {
			return false;
		}

		// We don't have settings for that domain, so we should bail here.
		if ( ! app()->roles->is_email_in_auth_domain( $user_args['user_email'] ) ) {
			return false;
		}

		/**
		 * This is documented in self::ensure_sso_user_access_all_sites.
		 *
		 * We do send different data to the filter here though, instead we send
		 * false for $site_id which tells the resulting function that we are not
		 * working with multi-site, and we send the user's potential email to
		 * to figure out the intended role via any mapping.
		 *
		 * @see    Roles::selective_role_mapping().
		 *
		 * @since  1.2.0
		 * @author Aubrey Portwood <aubrey@webdevstudios.com>
		 */
		$user_args['role'] = apply_filters( 'wds_sso_set_user_role_all_sites_to', 'administrator', false, isset( $user_args['user_email'] ) ? $user_args['user_email'] : '' );

		// Set user login to his email if none set.
		if ( empty( $user_args['user_login'] ) && ! empty( $user_args['user_email'] ) ) {
			$user_args['user_login'] = $user_args['user_email'];
		}

		// Add a new user, return its id.
		$user_id = wp_insert_user( $user_args );
		if ( $is_sso_user ) {
			if ( is_multisite() ) {
				if ( ! app()->roles->get_wds_sso_assign_user_roles() ) {

					// On multi-site, grant super-admin by default (only if they aren't assigning roles).
					grant_super_admin( $user_id );
				}

				// Always trust mapping to maybe revoke super admin.
				app()->roles->maybe_revoke_super_admin( $user_id );
			}

			// Save SSO meta key for new user and grant super admin if multisite.
			$this->set_sso_user( $user_id );
		}

		/**
		 * After a user is created, normally, by WDS SSO.
		 *
		 * @author Aubrey Portwood
		 * @since  1.2.0
		 */
		do_action( 'wds_sso_created_user', $user_id );

		return $user_id;
	}

	/**
	 * Check if SSO user.
	 *
	 * @param int $user_id User ID.
	 * @author Pavel Korotenko
	 * @since  1.0.0
	 *
	 * @return boolean True if SSO user.
	 */
	public function is_sso_user( $user_id = 0 ) {

		// Get current user id if no id was provided.
		if ( empty( $user_id ) ) {
			$user_id = get_current_user_id();
		}

		// Check email address against auth domain first.
		$user = get_user_by( 'ID', $user_id );

		if ( ! empty( $user->user_email ) && ! app()->roles->is_email_in_auth_domain( $user->user_email ) ) {
			return false;
		}

		$meta = get_user_meta( $user_id, '_wds_sso', true );
		return ! empty( $meta );
	}

	/**
	 * Marks this user ID as an SSO user.
	 *
	 * @author Justin Foell
	 * @since  1.0.0
	 *
	 * @param int $user_id User ID of SSO user.
	 */
	public function set_sso_user( $user_id ) {

		// Save SSO meta key for new user.
		if ( ! empty( $user_id ) && is_int( $user_id ) ) {
			update_user_meta( $user_id, '_wds_sso', true );
		}
	}

}
