<?php
/**
 * Other WordPress related settings.
 *
 * @author WebDevStudios
 * @package wds-headless-theme
 * @since 1.0
 */

/**
 * Wrap WYSIWYG embed in a div wrapper for responsive
 *
 * @author WebDevStudios
 * @since 1.0
 * @param string $html HTML string.
 * @param string $url  Current URL.
 * @param string $attr Embed attributes.
 * @param string $id   Post ID.
 * @return string
 */
function wds_embed_wrapper( $html, $url, $attr, $id ) {
	return '<div class="iframe-wrapper">' . $html . '</div>';
}
add_filter( 'embed_oembed_html', 'wds_embed_wrapper', 10, 4 );

/**
 * Create custom block category.
 *
 * @author WebDevStudios
 * @since 1.0
 * @param array  $categories Current block categories.
 * @param object $post       WP Post object.
 * @return array $categories
 */
function wds_block_category( $categories, $post ) {
	return array_merge(
		$categories,
		[
			[
				'slug'  => 'client',
				'title' => esc_html__( 'Client Name', 'wds' ),
			],
		]
	);
}
add_filter( 'block_categories', 'wds_block_category', 10, 2 );

/**
 * Customize the preview button in the WordPress admin to point to the headless client.
 *
 * @author WebDevStudios
 * @since 1.0
 * @param  string  $link WordPress preview link.
 * @param  WP_Post $post Current post object.
 * @return string        The headless WordPress preview link.
 */
function wds_set_headless_preview_link( string $link, WP_Post $post ) {
	if ( ! defined( 'HEADLESS_FRONTEND_URL' ) ) {
		return $link;
	}

	$base_url = HEADLESS_FRONTEND_URL;
	$slug     = strlen( $post->post_name ) > 0 ? $post->post_name : sanitize_title( $post->post_title );

	// Get GraphQL single name.
	$post_type = get_post_type_object( $post->post_type )->graphql_single_name ?? $post->post_type;

	// Preview link will have format: <domain>/api/preview?name=<slug>&id=<post-id>&post_type=<postType>&token=<preview-token>.
	return add_query_arg(
		[
			'name'      => $slug,
			'id'        => $post->ID,
			'post_type' => $post_type,
			'token'     => defined( 'PREVIEW_SECRET_TOKEN' ) ? PREVIEW_SECRET_TOKEN : '',
		],
		"{$base_url}api/preview"
	);
}
add_filter( 'preview_post_link', 'wds_set_headless_preview_link', 10, 2 );

/**
 * Customize WP home URL to point to frontend.
 *
 * @author WebDevStudios
 * @since 1.0
 * @param  string $url    Complete home URL, including path.
 * @param  string $path   Path relative to home URL.
 * @param  string $scheme Context for home URL.
 * @return string         Frontend home URL.
 */
function wds_set_headless_home_url( string $url, string $path, $scheme = null ) {
	if ( ! defined( 'HEADLESS_FRONTEND_URL' ) ) {
		return $url;
	}

	// Don't redirect REST requests.
	if ( 'rest' === $scheme ) {
		return $url;
	}

	// Don't redirect unless in WP admin.
	if ( ! is_admin() ) {
		return $url;
	}

	$base_url = HEADLESS_FRONTEND_URL;

	if ( ! $path ) {
		return $base_url;
	}

	// Remove excess slash from beginning of path.
	$path = ltrim( $path, '/' );

	return "{$base_url}{$path}";
}
add_filter( 'home_url', 'wds_set_headless_home_url', 10, 3 );

/**
 * Customize the REST preview link to point to the headless client.
 *
 * @author WebDevStudios
 * @since 1.0
 * @param  WP_REST_Response $response Response object.
 * @param  WP_Post          $post     Current post object.
 * @return WP_REST_Response           Response object.
 */
function wds_set_headless_rest_preview_link( WP_REST_Response $response, WP_Post $post ) {
	if ( 'draft' === $post->post_status ) {

		// Manually call preview filter for draft posts.
		$response->data['link'] = get_preview_post_link( $post );
	} elseif ( 'publish' === $post->post_status ) {

		// Override view link for published posts.
		if ( ! defined( 'HEADLESS_FRONTEND_URL' ) ) {
			return $response;
		}

		$base_url = HEADLESS_FRONTEND_URL;

		// Handle special-case pages.
		$homepage_id   = intval( get_field( 'homepage', 'option' ) );
		$error_page_id = get_field( 'error_404_page', 'option' );

		if ( $post->ID === $homepage_id ) {

			// Return root FE URL for homepage.
			$response->data['link'] = $base_url;
		} elseif ( $post->ID === $error_page_id ) {

			// Return 404 URL for error page.
			$response->data['link'] = "{$base_url}/404";
		} else {

			// Remove excess slash from end of base URL.
			$base_url = rtrim( $base_url, '/' );

			// Return URL based on post name.
			$response->data['link'] = "{$base_url}/{$post->post_name}";
		}
	}

	return $response;
}
add_filter( 'rest_prepare_page', 'wds_set_headless_rest_preview_link', 10, 2 );
