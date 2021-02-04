<?php
/**
 * Custom Post Types.
 *
 * Note: exported from CPTUI.
 *
 * @author WebDevStudios
 * @package wds
 * @since 1.0
 */

/**
 * Register custom post types.
 *
 * @author WebDevStudios
 * @since 1.0
 */
function wds_register_custom_post_types() {

	/**
	 * Post Type: Team Members.
	 */
	$labels = [
		"name" => esc_html__( "Team Members", "wds" ),
		"singular_name" => esc_html__( "Team", "wds" ),
	];

	$args = [
		"label" => esc_html__( "Team Members", "wds" ),
		"labels" => $labels,
		"description" => "",
		"public" => true,
		"publicly_queryable" => true,
		"show_ui" => true,
		"show_in_rest" => true,
		"rest_base" => "",
		"rest_controller_class" => "WP_REST_Posts_Controller",
		'show_in_graphql'       => true,
		'graphql_single_name'   => 'team',
		'graphql_plural_name'   => 'teams',
		"has_archive" => "team",
		"show_in_menu" => true,
		"show_in_nav_menus" => true,
		"delete_with_user" => false,
		"exclude_from_search" => false,
		"capability_type" => "post",
		"map_meta_cap" => true,
		"hierarchical" => false,
		"rewrite" => [ "slug" => "team", "with_front" => false ],
		"query_var" => true,
		"menu_icon" => "dashicons-groups",
		"supports" => [ "title", "editor", "thumbnail", "excerpt", "custom-fields", "author" ],
	];

	register_post_type( "team", $args );
}
add_action( 'init', 'wds_register_custom_post_types' );

/**
 * Register a field on the Team GraphQL object and populate it with post meta.
 *
 * Provided as an example; not currently needed.
 *
 * @author WebDevStudios
 * @since 1.0
 */
function wds_register_team_profile_data() {
	register_graphql_field( 'Team', 'profileData', [
		'type'        => 'String',
		'description' => esc_html__( 'Extra metadata for team members', 'wds' ),
		'resolve'     => function( $post ) {
			$team_meta_keys = [
				'title',
				'location',
				'linkedin_url',
				'twitter_url',
				'facebook_url',
				'instagram_url',
				'wordpressorg_profile_url',
				'github_url',
				'website_url',
				'easter_egg_url',
			];
			$profile_data   = [];

			foreach ( $team_meta_keys as $key ) {
				$profile_data[ $key ] = get_post_meta( $post->ID, $key, true );
			}
			return wp_json_encode( $profile_data );
		},
	] );
}
//add_action( 'graphql_register_types', 'wds_register_team_profile_data' );
