<?php
/**
 * Custom settings page for saving options for the theme.
 *
 * @author  WebDevStudios
 * @package wds-headless-theme
 * @since   1.0
 */

/*
 * Utility Functions
 * These functions are used to help make working with the options page easier.
 */

/**
 * Determines if the constants are defined and, if so, disabled the option input fields
 * except for the default 404 page.
 *
 * @author WebDevStudios
 * @since  1.0.0
 */
function wds_headless_is_disabled_field() {
	if ( _wds_headless_has_defined_all_constants() ) {
		esc_attr_e( 'disabled="true"', 'wds' );
	}
}

/**
 * Read the incoming constant definition key and, if defined, updates the associated option.
 *
 * @access private
 *
 * @param string $wds_headless_constant The PHP constant to check before saving data from the form.
 * @param string $option_name           The name of the option to serialize the value of the constant.
 *
 * @return bool Returns true if there are user defined constants; false, if not.
 *
 * @author WebDevStudios
 * @since  1.0.0
 */
function _wds_headless_check_wp_config( string $wds_headless_constant, string $option_name ) : bool {
	$wds_headless_constant = strtoupper( $wds_headless_constant );
	$constants             = _wds_headless_get_defined_constants();

	return ( defined( $wds_headless_constant ) && isset( $constants['user'] ) );
}

/**
 * Retrieves an associative array of constants defined in PHP.
 *
 * @access private
 *
 * @return array The associative array of PHP constants.
 *
 * @author WebDevStudios
 * @since  1.0.0
 */
function _wds_headless_get_defined_constants() : array {
	return \get_defined_constants( true );
}

/**
 * Verifies that all of the WDS Headless constants are defined. If all of them are defined,
 * this function will return true; otherwise, it will return false ignoring any that are
 * defined.
 *
 * @access private
 *
 * @return bool True if all options are set in the wp-config.php file.
 *
 * @author WebDevStudios
 * @since  1.0.0
 */
function _wds_headless_has_defined_all_constants() {
	return (
		_wds_headless_check_wp_config( 'HEADLESS_FRONTEND_URL', 'wds-headless-frontend-url' ) &&
		_wds_headless_check_wp_config( 'WORDPRESS_PREVIEW_SECRET', 'wds-headless-preview-secret' ) &&
		_wds_headless_check_wp_config( 'GRAPHQL_JWT_AUTH_SECRET_KEY', 'wds-headless-jwt-auth-key' )
	);
}

/**
 * Updates the options table with the specified options page with the value of the constant
 * set in wp-config.php.
 *
 * @access private
 *
 * @param string $wds_headless_constant The PHP constant to check before saving data from the form.
 * @param string $option_name           The name of the option to serialize the value of the constant.
 *
 * @author WebDevStudios
 * @since  1.0.0
 */
function _wds_headless_update_options( string $wds_headless_constant, string $option_name ) {
	$constants = _wds_headless_get_defined_constants();
	update_option( $option_name, $constants['user'][ strtoupper( $wds_headless_constant ) ] );
}


/*
 * Option Page Settings.
 * These functions use the WordPress API and the utility functions to populate the options
 * and to support user interaction.
 */

add_action( 'after_setup_theme', 'wds_headless_read_wp_config' );
/**
 * This determines if the constants have already been set within the wp-config.php
 * file and, if so, will populate the options accordingly.
 *
 * @return void
 *
 * @author WebDevStudios
 * @since  1.0.0
 */
function wds_headless_read_wp_config() {
	if ( ! _wds_headless_has_defined_all_constants() ) {
		return;
	}

	// The constants and corresponding options names supported by wp-config.php.
	$config_values = [
		'HEADLESS_FRONTEND_URL'       => 'wds-headless-frontend-url',
		'WORDPRESS_PREVIEW_SECRET'    => 'wds-headless-preview-secret',
		'GRAPHQL_JWT_AUTH_SECRET_KEY' => 'wds-headless-jwt-auth-key',
	];

	foreach ( $config_values as $constant => $option_name ) {
		_wds_headless_update_options( $constant, $option_name );
	}
}

add_action( 'admin_menu', 'wds_headless_options_page' );
/**
 * Adds the plugin menu item to the 'Settings' menu in the WordPress dashboard.
 *
 * @author WebDevStudios
 * @since  1.0.0
 */
function wds_headless_options_page() {
	add_options_page(
		esc_html__( 'WDS Headless Theme Settings', 'wds' ),
		esc_html__( 'Theme Settings', 'wds' ),
		'manage_options',
		'wds-headless-options-page',
		'_wds_headless_render_options_page'
	);
}

/**
 * Renders the options page for the settings.
 *
 * @access private
 *
 * @author WebDevStudios
 * @since  1.0.0
 */
function _wds_headless_render_options_page() {
	require_once 'views/options-view.php';
}

add_action( 'admin_post', 'wds_headless_save_options' );
/**
 * Validates the incoming nonce value, verifies the current user has
 * permission to save the value from the options page and saves the
 * option to the database.
 *
 * @author WebDevStudios
 * @since  1.0.0
 */
function wds_headless_save_options() {
	// First, validate the nonce and verify the user as permission to save.
	$result = 'true';
	if ( ! ( wds_headless_options_has_valid_nonce() && current_user_can( 'manage_options' ) ) ) {
		$result = 'false';
	}

	wds_headless_save_404_page();

	array_map( function ( $input_name ) {
		wds_headless_save_input( $input_name );
	}, [ 'wds-headless-frontend-url', 'wds-headless-preview-secret', 'wds-headless-jwt-auth-key' ] );

	wds_headless_render_options_redirect( $result );
}

/**
 * Saves the value of the 404 page as defined by the user.
 *
 * @return void Reterns if there is no input.
 *
 * @author WebDevStudios
 * @since  1.0.0
 */
function wds_headless_save_404_page() : void {
	$key = 'wds-headless-404-page';

	if ( null === wp_unslash( filter_input( INPUT_POST, $key ) ) ) {
		return;
	}

	update_option(
		$key,
		sanitize_text_field(
			filter_input( INPUT_POST, $key )
		)
	);
}

/**
 * Saves the value of the specified key as defined by the user.
 *
 * @param string $key The key located in the $_POST array specified by the user.
 *
 * @return void Returns early if there is not input.
 *
 * @author WebDevStudios
 * @since  1.0.0
 */
function wds_headless_save_input( string $key ) : void {
	if ( null === wp_unslash( filter_input( INPUT_POST, $key ) ) ) {
		return;
	}

	if ( empty( wp_unslash( filter_input( INPUT_POST, $key ) ) ) ) {
		delete_option( $key );
	}

	update_option(
		$key,
		sanitize_text_field(
			filter_var(
				filter_input( INPUT_POST, $key ),
				filter_var( filter_input( INPUT_POST, $key ), FILTER_VALIDATE_URL ) ?
					FILTER_SANITIZE_URL : FILTER_SANITIZE_STRING
			)
		)
	);
}

/**
 * Redirects the user back to the administration page will a success or failure message.
 *
 * @param string $result The result of the evaluation of the settings being saved.
 *
 * @author WebDevStudios
 * @since  1.0.0
 */
function wds_headless_render_options_redirect( string $result ) {
	if ( null === filter_input( INPUT_POST, '_wp_http_referer' ) ) {
		$_POST['_wp_http_referer'] = wp_login_url();
	}

	// Sanitize the value of the $_POST collection for the Coding Standards.
	$url = sanitize_text_field(
		wp_unslash( filter_input( INPUT_POST, '_wp_http_referer' ) )
	);

	// Finally, redirect back to the admin page.
	wp_safe_redirect( urldecode( add_query_arg( [ 'success' => $result ], urldecode( $url ) ) ) );
	exit;
}

/**
 * Verifies that the user has permission to save data associated with the settings page.
 *
 * @return bool True if the user has permission to save information; otherwise, false.
 *
 * @author WebDevStudios
 * @since  1.0.0
 */
function wds_headless_options_has_valid_nonce() {
	// If the nonce isn't even in the $_POST array, then it's invalid.
	if ( null === filter_input( INPUT_POST, 'wds-headless-settings-save-nonce' ) ) {
		return false;
	}

	$field  = wp_unslash( filter_input( INPUT_POST, 'wds-headless-settings-save-nonce' ) );
	$action = 'wds-headless-settings-save';

	return wp_verify_nonce( $field, $action );
}
