<?php
/**
 * WDS Specific Settings.
 *
 * @since 1.0.0
 * @package  WebDevStudios\SSO\AddonWDS
 */

namespace WebDevStudios\SSO\AddonWDS;

/**
 * WDS Settings Class.
 *
 * @since 1.0.0
 */
class Settings {

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
		add_filter( 'wds_sso_auth_domains', array( $this, 'auth_domains' ) );
		add_action( 'wds_sso_show_admin_notices', array( $this, 'show_admin_notice' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_client_scripts' ) );
		add_action( 'wp_ajax_wds_send_sso_key', array( $this, 'ajax_save_sso_key' ) );
	}

	/**
	 * Filter domains that are allowed to authenticate.
	 *
	 * @param array $domains Filtered domain list to be overwritten.
	 * @return array Array with only one entry: WDS.
	 * @author Justin Foell <justin.foell@webdevstudios.com>
	 * @since  1.0.0
	 */
	public function auth_domains( $domains ) {
		return array( 'webdevstudios.com' );
	}

	/**
	 * Enqueue scripts & styles for settings display.
	 *
	 * @author Pavel Korotenko
	 * @since  1.0.0
	 */
	public function enqueue_client_scripts() {

		// Set the WDS SSO constant.
		wp_enqueue_script( 'wds-sso-key', app()->url( 'assets/js/wds-sso-key.js' ), array( 'jquery' ), time(), false );
		wp_localize_script( 'wds-sso-key', 'wdsSSOKey', array(
			'adminUrl' => admin_url( 'admin-ajax.php' ),
			'prompt'   => __( "Please paste the key here and we'll encrypt it and save it in the DB. Make sure you have it correct, if you paste it wrong you will have to set the constant; don't worry we'll check it for you first.", 'wds-sso-addon-wds' ),
			'nonce'    => wp_create_nonce( 'wds_send_sso_key' ),
		) );
	}

	/**
	 * Save the WDS SSO KEY to the DB.
	 *
	 * @author Aubrey Portwood <aubrey@webdevstudios.com>
	 * @since  1.0.0
	 */
	public function ajax_save_sso_key() {
		check_admin_referer( 'wds_send_sso_key', 'nonce' );

		// They key they sent.
		$key = isset( $_REQUEST['key'] ) ? $_REQUEST['key'] : '';

		if ( ! is_string( $key ) ) {
			wp_send_json_error( array(
				'error' => __( 'Send a string please.', 'wds-sso-addon-wds' ),
			) );
		}

		if ( empty( $key ) ) {
			wp_send_json_error( array(
				'error' => __( "You need to supply some value, we didn't save anything.", 'wds-sso-addon-wds' ),
			) );
		}

		// Maybe they copied and pasted too much?
		if ( strstr( $key, 'define(' ) ) {
			wp_send_json_error( array(
				'error' => __( 'We found some code, please make sure you copy just the key. Please try again.', 'wds-sso-addon-wds' ),
			) );
		}

		// Check for a piece (yes hard-coded) of the key, it should have these pieces.
		$missing_partial = in_array( false, array(
			(bool) strstr( $key, 'j=}G' ),
			(bool) strstr( $key, 'W?HB' ),
			(bool) strstr( $key, '{%j=' ),
		), true );

		if ( $missing_partial || 64 !== strlen( $key ) ) {
			wp_send_json_error( array(
				'error' => __( "This isn't the right key, please try again.", 'wds-sso-addon-wds' ),
			) );
		}

		// Save the key to the DB.
		update_option( 'wds_sso_key', wp_strip_all_tags( $key ), false );
		update_site_option( 'wds_sso_key', wp_strip_all_tags( $key ) );

		// We did it!
		wp_send_json_success( array() );
	}

	/**
	 * Show an admin notice, possibly overriding upstream notices.
	 *
	 * @param boolean                    $display_notice Whether or not to display upstream notices.
	 * @param WebDevStudios\SSO\Settings $settings SSO Settings object.
	 * @return boolean Whether or not to display upstream notices.
	 * @author Aubrey Portwood <aubrey@webdevstudios.com>
	 * @since  1.0.0
	 */
	public function show_admin_notice( $display_notice, $settings ) {

		if ( ! empty( $settings->get_wds_sso_key() ) ) {
			return $display_notice;
		}

		// Client message...
		$message = sprintf(
			// translators: You have activated WDS SSO, but do not have the WDS_SSO constant set in wp-config.php.
			__( '%1$s is active, please set the %2$s %3$s in %4$s | Or, %5$s to paste the key, and we\'ll save it to the DB.', 'wds-sso-addon-wds' ),
			'<strong>WDS SSO</strong>',
			'<code>WDS_SSO_KEY</code>',
			sprintf( '<a href="https://github.com/WebDevStudios/wds-sso/search?q=WDS_SSO_KEY&type=Wikis" target="_blank">%s</a>', __( 'constant', 'wds-sso-addon-wds' ) ),
			'<code>wp-config.php</code>',
			sprintf( '<a href="%s">click here</a>', ! is_multisite() ? admin_url( 'options-general.php' ) : network_admin_url( 'settings.php' ) )
		);

		// Show our custom message.
		echo '<div class="notice notice-error"><p>' . wp_kses_post( $message ) . '</p></div>';
		return false;
	}
}
