<?php
/**
 * Yoast SEO settings.
 *
 * @see https://wordpress.org/plugins/wordpress-seo/
 * @author WebDevStudios
 * @package wds-headless-theme
 * @since 1.0
 */

if ( defined( 'WPSEO_VERSION' ) ) {

	/**
	 * Replace Site URL JAMStack URL as needed.
	 *
	 * @author WebDevStudios
	 * @since  1.0
	 * @param  array $breadcrumbs Yoast SEO breadcrumbs.
	 * @return array              Filtered breadcrumbs.
	 */
	function wds_breadcrumb_links( array $breadcrumbs ) {
		if ( ! defined( 'HEADLESS_FRONTEND_URL' ) ) {
			return $breadcrumbs;
		}

		$base_url = rtrim( HEADLESS_FRONTEND_URL, '/' );

		// Override domain in breadcrumbs.
		return array_map(
			function( $breadcrumb ) use ( $base_url ) {
				$parsed_url        = wp_parse_url( $breadcrumb['url'] );
				$path              = $parsed_url['path'] ?? '';
				$breadcrumb['url'] = "{$base_url}{$path}";

				return $breadcrumb;
			},
			$breadcrumbs
		);
	}
	add_filter( 'wpseo_breadcrumb_links', 'wds_breadcrumb_links' );
}
