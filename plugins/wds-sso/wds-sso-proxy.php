<?php
/**
 * Proxy
 *
 * @since  2.0.0
 * @package  WebDevStudios\SSO
 */

namespace WebDevStudios\SSO\Proxy;

use \WebDevStudios\SSO\App;
use \Exception;

// Require the App class.
require_once dirname( __FILE__ ) . '/includes/class-app.php';

/**
 * Helper function to access the application instance for the Proxy Plugin.
 *
 * @author Aubrey Portwood, Justin Foell
 * @since  1.0.0
 *
 * @return App|null App if success, null if exception caught (error will be logged).
 */
function app() {
	static $app;

	if ( ! $app instanceof App ) {
		if ( ! class_exists( '\WebDevStudios\SSO\App' ) ) {
			return null;
		}

		// Start the app.
		try {
			$app = new App( __FILE__, true );
		} catch ( Exception $e ) {

			// Catch any errors and log them if debugging is enabled.
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( $e->getMessage() ); // @codingStandardsIgnoreLine Conditionally debug.
			}

			// Return null so no further action can take place.
			return null;
		}
	}

	return $app;
}
app(); // Initialize the app.
