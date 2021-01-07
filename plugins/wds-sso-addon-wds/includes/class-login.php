<?php
/**
 * Login
 *
 * @since 1.0.0
 * @package  WebDevStudios\SSO\AddonWDS
 */

namespace WebDevStudios\SSO\AddonWDS;

/**
 * Login
 *
 * @since  1.0.0
 */
class Login {

	/**
	 * Construct
	 *
	 * @author Aubrey Portwood <aubrey@webdevstudios.com>
	 * @since  1.0.0
	 */
	public function __construct() {
		$this->hooks();
	}

	/**
	 * Hooks
	 *
	 * @author Aubrey Portwood <aubrey@webdevstudios.com>
	 * @since  1.0.0
	 */
	public function hooks() {
		add_filter( 'wds_sso_login_button_message', [ $this, 'login_button_message' ] );
		add_action( 'login_enqueue_scripts', array( $this, 'scripts' ) );
	}

	/**
	 * Scripts
	 *
	 * @author Aubrey Portwood <aubrey@webdevstudios.com>
	 * @since  1.0.0
	 *
	 * @return void
	 */
	public function scripts() {
		if ( ! isset( $GLOBALS['pagenow'] ) ? 'wp-login.php' === $GLOBALS['pagenow'] : false ) {
			return;
		}

		global $is_IE, $is_edge; // @codingStandardsIgnoreStart: We want to use these.

		if ( ! $is_edge && ! $is_IE ) {

			// Colorize the button on non-ie browsers, and only on wp-login.php.
			wp_enqueue_style( 'wds-sso-login-colorize', app()->url( 'assets/css/wds-sso-login-colorize.css' ), array( 'login', 'wds-sso-login' ) );
		}
	}

	/**
	 * Set login button message.
	 *
	 * @author Aubrey Portwood <aubrey@webdevstudios.com>
	 * @since  1.0.0
	 *
	 * @param  string $message The current message.
	 * @return string          Our message.
	 */
	public function login_button_message( $message = '' ) {
		return 'WebDevStudios Login';
	}
}
