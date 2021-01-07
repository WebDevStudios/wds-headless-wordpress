<?php
/**
 * Plugin Name: WDS SSO Addon
 * Description: WDS Configuration for WDS SSO.
 * Version:     1.0.1
 * Author:      WebDevStudios
 * Author URI:  http://webdevstudios.com
 * Text Domain: wds-sso-addon-wds
 * Network:     False
 * License:     GPLv2
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 *
 * @since       1.0.0
 * @package     WebDevStudios\SSO\AddonWDS
 *
 * Built with https://github.com/aubreypwd/wp-plugin-boilerplate
 */

// Our namespace.
namespace WebDevStudios\SSO\AddonWDS;

// WDS Specific Defines.
if ( ! defined( 'WDS_SSO_PROXY_URL' ) ) {
	define( 'WDS_SSO_PROXY_URL', 'https://sso.webdevstudios.com/wp-login.php?wds-sso=true' );
}

// Require the App class.
require_once dirname( __FILE__ ) . '/includes/class-app.php';

/**
 * Create/Get the App.
 *
 * @author Aubrey Portwood
 * @since  1.0.0
 *
 * @return App The App.
 */
function app() {
	static $app = null;

	if ( null === $app ) {

		// Create the app and go!
		$app = new App( __FILE__ );

		// Attach our other classes.
		$app->attach();

		// Run any hooks.
		$app->hooks();
	}

	return $app;
}

// Wait until WordPress is ready, then go!
add_action( 'plugins_loaded', 'WebDevStudios\SSO\AddonWDS\app' );

// When we deactivate this plugin...
register_deactivation_hook( __FILE__, array( app(), 'deactivate_plugin' ) );
