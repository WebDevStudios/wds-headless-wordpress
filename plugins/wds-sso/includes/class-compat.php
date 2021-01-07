<?php
/**
 * Compatibility.
 *
 * @since    1.2.4
 * @package  WebDevStudios\SSO
 */

namespace WebDevStudios\SSO;

/**
 * Compatibility.
 *
 * When constructing methods, please use @see and plugin file reference
 * on each one so we know what plugin it interacts with.
 *
 * @since  1.2.4
 */
class Compat {

	/**
	 * Constructor.
	 *
	 * Please add methods to handle different plugins.
	 *
	 * @author Aubrey Portwood <aubrey@webdevstudios.com>
	 * @since  1.2.4
	 */
	public function __construct() {
		$this->plugins();
		$this->caching();
	}

	/**
	 * Plugin issues.
	 *
	 * @author Aubrey Portwood <aubrey@webdevstudios.com>
	 * @since  1.2.4
	 */
	public function plugins() {

		// registered-users-only/registered-users-only.php.
		$this->registered_users_only();
	}

	/**
	 * Caching issues.
	 *
	 * @author Aubrey Portwood <aubrey@webdevstudios.com>
	 * @since  1.2.4
	 */
	public function caching() {

		// Make sure wp-login.php doesn't get cached.
		add_action( 'init', array( $this, 'nocache_wp_login' ) );

		// Fix WPE redirects.
		add_action( 'wp_redirect', array( $this, 'fix_wpe_redirects' ), 10, 2 );
	}

	/**
	 * Compatibility for Registered Users Only Plugin.
	 *
	 * @author Aubrey Portwood <aubrey@webdevstudios.com>
	 * @since  1.2.4
	 *
	 * @see https://wordpress.org/plugins/registered-users-only/ The plugin we need to add compatibility for.
	 * @see registered-users-only/registered-users-only.php      The plugin file.
	 */
	public function registered_users_only() {
		add_action( 'init', array( $this, 'registered_users_only_fix_redirect_to_https' ) );
	}

	/**
	 * Intercept redirects from wp-login.php to the homepage and ensure we land on a non-cached page.
	 *
	 * The reason this exists is because if forced-login plugin's re-direct from the homepage
	 * to wp-login.php that redirect gets cached by WPE, and so re-directing back to the homepage
	 * just ends up re-directing back to wp-login.php, because on WPE, that redirect is cached.
	 *
	 * This ensures that any redirect on WPE goes to a cached version of it by adding a query
	 * parameter.
	 *
	 * @author Aubrey Portwood <aubrey@webdevstudios.com>
	 * @since  1.2.4
	 *
	 * @param  string $location The URL.
	 * @param  string $status   The status.
	 * @return string           The location (or maybe the location with ?_wpe= and a random string).
	 */
	public function fix_wpe_redirects( $location, $status ) {
		if ( ! isset( $_SERVER['HTTP_REFERER'] ) ) {
			return $location;
		}

		if ( wp_doing_ajax() ) {
			return $location;
		}

		if ( wp_doing_cron() ) {
			return $location;
		}

		// These referers will force a _wpe= query parameter.
		$referers = array(
			(bool) stristr( $_SERVER['HTTP_REFERER'], 'accounts.youtube.com' ),
			(bool) stristr( $_SERVER['HTTP_REFERER'], 'wp-login.php' ),
		);

		if ( ! in_array( true, $referers, true ) ) {

			// We aren't being redirected from a proper referer.
			return $location;
		}

		// Places we should not be adding ?_wpe= to.
		$reject_destinations = array(
			(bool) stristr( $location, '/wp-admin' ),
			(bool) stristr( $location, 'wp-cron.php' ),
			(bool) stristr( $location, 'wp-login.php' ),
			(bool) stristr( $location, '.php' ),
			(bool) stristr( $location, 'wp-content' ),
		);

		if ( in_array( true, $reject_destinations, true ) ) {

			// We are trying to go to a destination that doesn't need ?_wpe.
			return $location;
		}

		// Is this WPE?
		$wpe = defined( 'IS_WPE' ) ? true : function_exists( 'is_wpe' ) && is_wpe();

		// wp-login.php is trying to re-direct somewhere.
		if ( $wpe ) {

			// Add a query parameter so the resulting page is not a cached one.
			return add_query_arg( '_wpe', crc32( time() ), $location );
		}

		return $location;
	}

	/**
	 * Tell the wp-login.php page not to cache itself in-browser.
	 *
	 * @author Aubrey Portwood <aubrey@webdevstudios.com>
	 * @since  1.2.4
	 *
	 * @return void Early bails if not the wp-login.php screen.
	 */
	public function nocache_wp_login() {
		if ( is_admin() ) {

			// The admin, not login screen.
			return;
		}

		$request = isset( $_SERVER['REQUEST_URI'] ) ? $_SERVER['REQUEST_URI'] : '';
		if ( ! stristr( $request, 'wp-login.php' ) ) {

			// We are not on wp-login.php.
			return;
		}

		// Set these headers which should tell the browser to not cache the wp-login.php page.
		header( 'Expires: on, 01 Jan 1970 00:00:00 GMT' );
		header( 'Last-Modified: ' . gmdate( 'D, d M Y H:i:s' ) . ' GMT' );
		header( 'Cache-Control: no-store, no-cache, must-revalidate' );
		header( 'Cache-Control: post-check=0, pre-check=0', false );
		header( 'Pragma: no-cache' );
	}

	/**
	 * Ensure that our redirect_to is always https://.
	 *
	 * @author Aubrey Portwood <aubrey@webdevstudios.com>
	 * @since  1.2.4
	 *
	 * @return void Early bail if there's nothing to fix.
	 *
	 * @see registered-users-only/registered-users-only.php The plugin that does this.
	 *
	 * Note, this may not be specific to just this plugin, other plugin's may do this too.
	 */
	public function registered_users_only_fix_redirect_to_https() {
		if ( is_admin() ) {

			// The admin, not login screen.
			return;
		}

		if ( app()->shared->http_enabled() ) {
			return; // Use HTTP.
		}

		$request = isset( $_SERVER['REQUEST_URI'] ) ? $_SERVER['REQUEST_URI'] : '';
		if ( ! stristr( $request, 'wp-login.php' ) ) {

			// We are not on wp-login.php.
			return;
		}

		// Did we fix this already?
		$fixed = isset( $_GET['redirect_to_https_forced'] ) ? true : false; // @codingStandardsIgnoreLine: GET access okay here.
		if ( $fixed ) {

			// We already fixed it.
			return;
		}

		if ( ! stristr( $request, 'redirect_to=' ) ) {

			// wp-login.php is not redirecting to anything.
			return;
		}

		/*
		 * The issue in most cases is that redirect_to is being set to a non https
		 * location, which will always fail SSO.
		 */
		$replacements = array(
			'redirect_to=http://'       => 'redirect_to=https://',
			'redirect_to=http%3A%2F%2F' => 'redirect_to=https%3A%2F%2F',
		);

		foreach ( $replacements as $replace => $with ) {
			if ( stristr( $request, $replace ) ) {
				$request = str_replace( $replace, $with, $request );
			}
		}

		wp_redirect( add_query_arg( 'redirect_to_https_forced', '1', $request ) ); // @codingStandardsIgnoreLine: Legacy.
		exit;
	}
}
