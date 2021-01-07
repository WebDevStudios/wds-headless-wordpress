<?php
/**
 * WDS Roles.
 *
 * @since 1.0.0
 * @package  WebDevStudios\SSO\AddonWDS
 */

namespace WebDevStudios\SSO\AddonWDS;

/**
 * WDS User Roles.
 *
 * @since  1.0.0
 */
class Roles {

	/**
	 * Constructor.
	 *
	 * @author Justin Foell <justin.foell@webdevstudios.com>
	 * @since  1.0.0
	 */
	public function __construct() {
		$this->hooks();
	}

	/**
	 * Hooks to WordPress actions and filters.
	 *
	 * @author Justin Foell <justin.foell@webdevstudios.com>
	 * @since  1.0.0
	 */
	public function hooks() {
		$this->seletive_role_map_hooks();

		add_filter( 'wds_sso_user_can_manage', array( $this, 'is_user_wds_email' ), 10, 2 );
		add_filter( 'wds_sso_replacement_author_info', array( $this, 'deactivation_author' ) );
		add_filter( 'wds_selective_role_notice_count', array( $this, 'wds_selective_role_notice_count' ) );
	}

	/**
	 * Don't show selective role notice if it's just brad and lisa.
	 *
	 * @author Aubrey Portwood <aubrey@webdevstudios.com>
	 * @since  1.0.0
	 *
	 * @param  int $count How many times to show notice.
	 * @return bool
	 */
	public function wds_selective_role_notice_count( $count = 5 ) {
		$map = \WebDevStudios\SSO\app()->roles->selective_role_map();

		$brad = in_array( 'brad@webdevstudios.com', array_keys( $map ), true );
		$lisa = in_array( 'lisa@webdevstudios.com', array_keys( $map ), true );

		$just_brad_and_lisa = $brad && $lisa && count( $map ) === 2;

		return $just_brad_and_lisa ? 0 : $count; // Show 0 if just brad & lisa.
	}

	/**
	 * Make sure that selective roles is turned on automatically.
	 *
	 * @author Aubrey Portwood <aubrey@webdevstudios.com>
	 * @since  2.0.0
	 */
	private function seletive_role_map_hooks() {
		add_action( 'wds_sso_selective_role_map', array( $this, 'hard_coded_mappings' ), 30 );
	}

	/**
	 * When deactivating this plugin, make content attributed to 'wds_author'.
	 *
	 * @param array $user_args Attribution user args sent to wp_insert_user().
	 * @return array
	 * @author Justin Foell <justin.foell@webdevstudios.com>
	 * @since  1.0.0
	 */
	public function deactivation_author( $user_args ) {
		$user_args['user_login'] = 'wds_author';
		return $user_args;
	}

	/**
	 * Called by 'wds_sso_user_can_manage' filter, used to limit SSO settings
	 * to only users with WDS emails.
	 *
	 * @param bool    $can_manage Can this user manage roles? Default: true.
	 * @param WP_User $user       User to check.
	 * @return bool               True if this user can manage roles, false otherwise.
	 * @author Justin Foell <justin.foell@webdevstudios.com>
	 * @since  1.0.0
	 */
	public function is_user_wds_email( $can_manage, $user ) {
		if ( strstr( $user->user_email, '@webdevstudios.com' ) ) {

			// Only WebDev people can manage WDS SSO settings, this prevents clients from changing the settings.
			return true;
		}
		return false;
	}

	/**
	 * Default mapping.
	 *
	 * @author Aubrey Portwood <aubrey@webdevstudios.com>
	 * @since  1.0.0
	 *
	 * @param  array $map  The current mappings.
	 * @return array       The mappings, but with a couple people with hard-coded access.
	 */
	public function hard_coded_mappings( $map ) {
		return array_merge( $map, array(
			'brad@webdevstudios.com' => $this->all_access_args(),
			'lisa@webdevstudios.com' => $this->all_access_args(),
		) );
	}

	/**
	 * Arguments that give all access (mappings).
	 *
	 * @author Aubrey Portwood <aubrey@webdevstudios.com>
	 * @since  1.0.0
	 *
	 * @param  array $args Anything you want to override.
	 * @return array       All access arguments.
	 */
	private function all_access_args( $args = array() ) {
		return array_merge( array(
			'revoke_super_admin' => false,
			'roles'              => array(
				'*' => 'administrator',
			),
		), $args );
	}

}
