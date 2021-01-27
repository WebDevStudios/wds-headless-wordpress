<?php
/**
 * WP GraphQL settings.
 *
 * @author WebDevStudios
 * @package wds
 * @since 1.0
 */

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
 * Add query to GraphQL to retrieve upcoming featured events.
 */
function wds_add_upcoming_featured_events_query() {

	register_graphql_connection( [
		'fromType'           => 'RootQuery',
		'toType'             => 'Event',
		'fromFieldName'      => 'featuredEvents',
		'connectionTypeName' => 'RootQueryToFeaturedEventsConnection',
		'resolve'            => function( $root, $args, \WPGraphQL\AppContext $context, $info ) {

			global $wpdb;

			$resolver = new \WPGraphQL\Data\Connection\PostObjectConnectionResolver( $root, $args, $context, $info );
			$per_page = $resolver->get_query_amount();

			// Get current ET datetime.
			$datetime = new DateTime();
			$datetime->setTimezone( new DateTimeZone( 'America/New_York' ) );
			$datetime = $datetime->format( 'Ymd' );

			// Get upcoming events sorted by date (ASC).
			$event_ids = $wpdb->get_col( $wpdb->prepare(
				"
				SELECT p.ID, p.post_title, m2.*
				FROM wp_posts AS p
				INNER JOIN wp_postmeta AS m1 ON p.ID = m1.post_id
				INNER JOIN wp_postmeta AS m2 ON p.ID = m2.post_id
				WHERE p.post_type = 'events' AND p.post_status = 'publish'
				AND m1.meta_key = 'featured_event' AND m1.meta_value = '1'
				AND (
					(m2.meta_key = 'start_date' AND m2.meta_value >= %s)
					OR (m2.meta_key = 'end_date' AND m2.meta_value >= %s)
				)
				GROUP BY ID
				ORDER BY m2.meta_value ASC LIMIT %d
				",
				$datetime,
				$datetime,
				$per_page
			) );

			// Return 0 if no posts found.
			$event_ids = count( $event_ids ) ? $event_ids : [0];

			// Update query to only include upcoming event IDs.
			$resolver->set_query_arg( 'post__in', $event_ids );

			return $resolver->get_connection();
		},
	] );
}
// add_action( 'graphql_register_types', 'wds_add_upcoming_featured_events_query' );


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
