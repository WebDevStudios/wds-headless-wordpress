<?php
/**
 * WP Search with Algolia settings.
 *
 * @author WebDevStudios
 * @package wds
 * @since 1.0
 */

/**
 * Filter the searchable post types for Algolia.
 *
 * @author WebDevStudios
 * @since 1.0
 * @param  array $post_types Searchable post types.
 * @return array             Filtered searchable post types.
 */
function wds_algolia_ignore_post_types( $post_types ) {

	// Ignore these post types.
	unset( $post_types['acf-field_group'] );
	unset( $post_types['custom_css'] );
	unset( $post_types['customize_changeset'] );
	unset( $post_types['import_users'] );
	unset( $post_types['oembed_cache'] );
	unset( $post_types['submission'] );
	unset( $post_types['user_request'] );
	unset( $post_types['wgg_preview'] );
	unset( $post_types['wp_stream_alerts'] );

	return $post_types;
}
add_filter( 'algolia_searchable_post_types', 'wds_algolia_ignore_post_types' );

/**
 * Don't index pages when the searchable option is unchecked.
 *
 * @author WebDevStudios
 * @since 1.0
 * @param bool    $should_index  Items that should be indexed.
 * @param WP_Post $post          The post object.
 * @return bool
 */
function wds_algolia_filter_pages( $should_index, WP_Post $post ) {

	// If a page has been marked not searchable
	// by some other means, then don't index page.
	if ( false === $should_index ) {
		return false;
	}

	// Check if a page is searchable.
	$searchable = get_field( 'searchable', $post->ID );

	// If not, then don't index page.
	if ( 0 === $searchable ) {
		return false;
	}

	// If all else fails, index page.
	return true;
}
add_filter( 'algolia_should_index_searchable_post', 'wds_algolia_filter_pages', 10, 2 );
add_filter( 'algolia_should_index_post', 'wds_algolia_filter_pages', 10, 2 );

/**
 * Check in custom fields and push them to Algolia.
 *
 * @author WebDevStudios
 * @since 1.0
 * @param array   $attributes  Original set of attributes from Algolia.
 * @param WP_Post $post        The post object.
 * @return array
 */
function wds_algolia_custom_fields( array $attributes, WP_Post $post ) {

	// List all post types with custom post meta.
	$post_types = [
		'team',
	];

	// List all eligible meta fields.
	$fields = [
		'title',
		'location'
	];

	// Check if post type is eligible.
	if ( in_array( $post->post_type, $post_types, true ) ) {

		// Loop over each field...
		foreach ( $fields as $field ) {

			// Get field data.
			$data = get_field( $field, $post->ID );

			/**
			 * Due to Algolia size restrictions (10kb), we cannot index
			 * every field. Only index if a field is a boolean or has
			 * content.
			 */
			if ( is_bool( $data ) || ! empty( $data )  ) {

				// Append _meta to each field.
				$attributes[ $field . '_meta' ] = $data;
			}
		}
	}

	return $attributes;
}
add_filter( 'algolia_post_shared_attributes', 'wds_algolia_custom_fields', 10, 2 );
add_filter( 'algolia_searchable_post_shared_attributes', 'wds_algolia_custom_fields', 10, 2 );

/**
 * Send certain image sizes to Algolia.
 *
 * @author WebDevStudios
 * @since 1.0
 * @return array The list of known image sizes.
 */
function wds_algolia_set_image_sizes() {
	return [
		'nineteentwenty',
		'thumbnail',
	];
}
add_filter( 'algolia_post_images_sizes', 'wds_algolia_set_image_sizes', 10, 2 );
