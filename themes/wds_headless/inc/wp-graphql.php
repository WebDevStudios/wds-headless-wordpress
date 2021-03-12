<?php
/**
 * WP GraphQL settings.
 *
 * @see https://wordpress.org/plugins/wp-graphql/
 * @author WebDevStudios
 * @package wds-headless-theme
 * @since 1.0
 */

if ( class_exists( 'WPGraphQL' ) ) {

	/**
	 * Retrieve relational post data by IDs.
	 *
	 * @author WebDevStudios
	 * @since 1.0
	 * @param  array $post_ids Array of post IDs.
	 * @return array           Array of post data.
	 */
	function wds_get_relational_posts( array $post_ids ) {
		if ( ! count( $post_ids ) ) {
			return [];
		}

		return array_map( function( $post_id ) {

			// Return as-is if already array of post data.
			if ( is_array( $post_id ) ) {
				return $post_id;
			}

			$post = get_post( $post_id );

			// Return post ID as array if error encountered retrieving post object.
			if ( ! $post || ! $post instanceof WP_Post ) {
				return [ 'id' => $post_id ];
			}

			$post_type = get_post_type_object( $post->post_type );

			return [
				'id'         => $post_id,
				'type'       => $post_type->graphql_single_name,
				'pluralType' => $post_type->graphql_plural_name,
			];
		}, $post_ids );
	}

	/**
	 * Add query to GraphQL to retrieve homepage settings.
	 */
	function wds_add_homepage_settings_query() {

		register_graphql_object_type( 'HomepageSettings', [
			'description' => esc_html__( 'Front and posts archive page data', 'wds' ),
			'fields'      => [
				'frontPage' => [ 'type' => 'Page' ],
				'postsPage' => [ 'type' => 'Page' ],
			],
		] );

		register_graphql_field( 'RootQuery', 'homepageSettings', [
			'type'        => 'HomepageSettings',
			'description' => esc_html__( 'Returns front and posts archive page data', 'wds' ),
			'resolve'     => function( $source, array $args, \WPGraphQL\AppContext $context ) {
				global $wpdb;

				// Get homepage settings.
				$settings = $wpdb->get_row(
					"
					SELECT
						(select option_value from {$wpdb->prefix}options where option_name = 'page_for_posts') as 'page_for_posts',
						(select option_value from {$wpdb->prefix}options where option_name = 'page_on_front') as 'page_on_front'
					",
					ARRAY_A
				);

				// Format settings data.
				$settings_data = [];

				foreach ( $settings as $key => $value ) {
					// Get page data.
					$page_data = ! empty( $value ?? 0 ) ? $context->get_loader( 'post' )->load_deferred( intval( $value ) ) : null;

					switch ( $key ) {
						case 'page_for_posts':
							$settings_data['postsPage'] = $page_data;
							break;

						case 'page_on_front':
							$settings_data['frontPage'] = $page_data;
							break;
					}
				}

				return $settings_data;
			},
		] );
	}
	add_action( 'graphql_register_types', 'wds_add_homepage_settings_query' );

	/**
	 * Allow access to additional fields via non-authed GraphQL request.
	 *
	 * @author WebDevStudios
	 * @since 1.0
	 * @param  array  $fields     The fields to allow when the data is designated as restricted to the current user.
	 * @param  string $model_name Name of the model the filter is currently being executed in.
	 * @return array                   Allowed fields.
	 */
	function wds_graphql_allowed_fields( array $fields, string $model_name ) {
		if ( 'PostTypeObject' !== $model_name ) {
			return $fields;
		}

		// Add label fields.
		$fields[] = 'label';
		$fields[] = 'labels';

		return $fields;
	}
	add_filter( 'graphql_allowed_fields_on_restricted_type', 'wds_graphql_allowed_fields', 10, 6 );

	/**
	 * Include users without published posts in SQL query.
	 *
	 * @author WebDevStudios
	 * @since 1.0
	 * @param array                      $query_args          The query args to be used with the executable query to get data.
	 * @param AbstractConnectionResolver $connection_resolver Instance of the connection resolver
	 * @return void
	 */
	function wds_public_unpublished_users( array $query_args, \WPGraphQL\Data\Connection\AbstractConnectionResolver $connection_resolver ) {
		if ( $connection_resolver instanceof \WPGraphQL\Data\Connection\UserConnectionResolver ) {
			unset( $query_args['has_published_posts'] );
		}

		return $query_args;
	}
	add_filter( 'graphql_connection_query_args', 'wds_public_unpublished_users', 10, 2 );

	/**
	 * Make all Users public including in non-authenticated WPGraphQL requests.
	 *
	 * @param string  $visibility
	 * @param string  $model_name
	 * @param mixed   $data
	 * @param integer $owner
	 * @param WP_User $current_user
	 * @return void
	 */
	function wds_public_users( string $visibility, string $model_name ) {
		if ( 'UserObject' === $model_name ) {
			$visibility = 'public';
		}

		return $visibility;
	}
	add_filter( 'graphql_object_visibility', 'wds_public_users', 10, 2 );
}
