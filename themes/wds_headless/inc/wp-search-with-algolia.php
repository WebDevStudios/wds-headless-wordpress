<?php
/**
 * WP Search with Algolia settings.
 *
 * @see https://wordpress.org/plugins/wp-search-with-algolia/
 * @author WebDevStudios
 * @package wds-headless-theme
 * @since 1.0
 */

 if ( defined( 'ALGOLIA_VERSION' ) ) {

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

		return $post_types;
	}
	add_filter( 'algolia_searchable_post_types', 'wds_algolia_ignore_post_types' );

	/**
	 * Push custom fields to Algolia.
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

 }
