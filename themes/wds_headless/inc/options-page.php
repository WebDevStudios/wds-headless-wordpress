<?php
/**
 * Custom settings page for saving options for the theme.
 *
 * @author  WebDevStudios
 * @package wds-headless-theme
 * @since   1.0
 */

add_action( 'admin_menu', 'wds_headless_options_menu' );
/**
 * Adds the plugin menu item to the 'Tools' menu in the WordPress dashboard.
 *
 * @since     1.0.0
 */
function wds_headless_options_menu() {
	add_submenu_page(
		'tools.php',
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
 * @since 1.0
 */
function wds_headless_render_options_page() {
	include_once 'views/options-view.php';
}

add_action( 'load-tools_page_wds-headless-options-page', 'wds_headless_save_options' );
/**
 * Validates the user's permission to save settings and updates them accordingly.
 *
 * @author WebDevStudios
 * @since 1.0
 */
function wds_headless_save_options() {
	$action = 'wds-headless-options-save';
	$nonce  = 'wds-headless-options-save-nonce';

	// TODO: Verify user can save.
}
