<?php
/**
 * Custom Post Types.
 *
 * Note: exported from CPTUI.
 *
 * @author WebDevStudios
 * @package wds-headless-theme
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
