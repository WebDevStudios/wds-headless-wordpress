<?php
/**
 * Custom settings page for saving options for the theme.
 *
 * @author  WebDevStudios
 * @package wds-headless-theme
 * @since   1.0
 */

add_action( 'admin_menu', 'wds_headless_options_page' );
/**
 * Adds the plugin menu item to the 'Settings' menu in the WordPress dashboard.
 *
 * @author WebDevStudios
 * @since  1.0.0
 */
function wds_headless_options_page() {
	add_options_page(
		__( 'WDS Headless Theme Settings', 'wds' ),
		__( 'Theme Settings', 'wds' ),
		'manage_options',
		'wds-headless-options-page',
		'wds_headless_render_options_page'
	);
}

/**
 * Renders the options page for the settings.
 *
 * @author WebDevStudios
 * @since  1.0.0
 */
function wds_headless_render_options_page() {
	require_once 'views/options-view.php';
}

/**
 * Renders the partial for displaying an error if the user is unable to save.
 *
 * @author WebDevStudios
 * @since  1.0.0
 */
function wds_headless_render_error_message() {
	require_once 'views/partials/failure.php';
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
	wds_headless_save_frontend_url();
	wds_headless_save_preview_secret();

	wds_headless_render_options_redirect( $result );
}

/**
 * TODO
 *
 * @author WebDevStudios
 * @since  1.0.0
 */
function wds_headless_save_404_page() {
	if ( null === wp_unslash( filter_input( INPUT_POST, 'wds-headless-404-page' ) ) ) {
		return;
	}

	update_option(
		'wds-headless-404-page',
		sanitize_text_field(
			filter_input( INPUT_POST, 'wds-headless-404-page' )
		)
	);
}

/**
 * TODO
 *
 * @author WebDevStudios
 * @since  1.0.0
 */
function wds_headless_save_frontend_url() {
	if ( null === wp_unslash( filter_input( INPUT_POST, 'wds-headless-frontend-url' ) ) ) {
		return;
	}

	update_option(
		'wds-headless-frontend-url',
		sanitize_text_field(
			filter_var(
				filter_input( INPUT_POST, 'wds-headless-frontend-url' ),
				FILTER_SANITIZE_URL
			)
		)
	);
}

/**
 * TODO
 *
 * @return void
 *
 * @author WebDevStudios
 * @since  1.0.0
 */
function wds_headless_save_preview_secret() {
	if ( null === wp_unslash( filter_input( INPUT_POST, 'wds-headless-preview-secret' ) ) ) {
		return;
	}

	update_option(
		'wds-headless-preview-secret',
		sanitize_text_field(
			filter_var(
				filter_input( INPUT_POST, 'wds-headless-preview-secret' ),
				FILTER_SANITIZE_STRING
			)
		)
	);
}

/**
 * TODO
 *
 * @return void
 *
 * @author WebDevStudios
 * @since  1.0.0
 */
function wds_headless_save_jwt_auth () {
	if ( null === wp_unslash( filter_input( INPUT_POST, 'wds-headless-jwt-auth-key' ) ) ) {
		return;
	}

	update_option(
		'wds-headless-jwt-auth-key',
		sanitize_text_field(
			filter_var(
				filter_input( INPUT_POST, 'wds-headless-jwt-auth-key' ),
				FILTER_SANITIZE_STRING
			)
		)
	);
}

/**
 * Redirects the user back to the administration page will a success or failure message.
 *
 * @param string $result The result of the evaluation of the settings being saved.
 *
 * @return void
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
 * @return void
 *
 * @author WebDevStudios
 * @since  1.0.0
 */
function wds_headless_options_has_valid_nonce() {
	// If the nonce isn't even in the $_POST, then it's invalid.
	if ( null === filter_input( INPUT_POST, 'wds-headless-settings-save-nonce' ) ) {
		return false;
	}

	$field  = wp_unslash( filter_input( INPUT_POST, 'wds-headless-settings-save-nonce' ) );
	$action = 'wds-headless-settings-save';

	return wp_verify_nonce( $field, $action );
}
