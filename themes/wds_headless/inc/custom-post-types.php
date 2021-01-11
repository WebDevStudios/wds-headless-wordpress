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
	 * Post Type: Careers.
	 */
	$labels = [
		"name" => esc_html__( "Careers", "wds" ),
		"singular_name" => esc_html__( "Career", "wds" ),
	];

	$args = [
		"label" => esc_html__( "Careers", "wds" ),
		"labels" => $labels,
		"description" => "",
		"public" => true,
		"publicly_queryable" => true,
		"show_ui" => true,
		"show_in_rest" => true,
		"rest_base" => "",
		"rest_controller_class" => "WP_REST_Posts_Controller",
		'show_in_graphql'       => true,
		'graphql_single_name'   => 'career',
		'graphql_plural_name'   => 'careers',
		"has_archive" => false,
		"show_in_menu" => true,
		"show_in_nav_menus" => true,
		"delete_with_user" => false,
		"exclude_from_search" => false,
		"capability_type" => "post",
		"map_meta_cap" => true,
		"hierarchical" => false,
		"rewrite" => [ "slug" => "careers", "with_front" => false ],
		"query_var" => true,
		"menu_icon" => "dashicons-tide",
		"supports" => [ "title", "editor", "thumbnail", "excerpt", "revisions", "author" ],
	];

	register_post_type( "careers", $args );

	/**
	 * Post Type: Events.
	 */
	$labels = [
		"name" => esc_html__( "Events", "wds" ),
		"singular_name" => esc_html__( "Event", "wds" ),
	];

	$args = [
		"label" => esc_html__( "Events", "wds" ),
		"labels" => $labels,
		"description" => "",
		"public" => true,
		"publicly_queryable" => true,
		"show_ui" => true,
		"show_in_rest" => true,
		"rest_base" => "",
		"rest_controller_class" => "WP_REST_Posts_Controller",
		'show_in_graphql'       => true,
		'graphql_single_name'   => 'event',
		'graphql_plural_name'   => 'events',
		"has_archive" => false,
		"show_in_menu" => true,
		"show_in_nav_menus" => true,
		"delete_with_user" => false,
		"exclude_from_search" => false,
		"capability_type" => "post",
		"map_meta_cap" => true,
		"hierarchical" => false,
		"rewrite" => [ "slug" => "events", "with_front" => false ],
		"query_var" => true,
		"menu_icon" => "dashicons-nametag",
		"supports" => [ "title", "editor", "thumbnail", "excerpt", "custom-fields", "author" ],
	];

	register_post_type( "events", $args );

	/**
	 * Post Type: Portfolio.
	 */
	$labels = [
		"name" => esc_html__( "Portfolio", "wds" ),
		"singular_name" => esc_html__( "Portfolios", "wds" ),
	];

	$args = [
		"label" => esc_html__( "Portfolio", "wds" ),
		"labels" => $labels,
		"description" => "",
		"public" => true,
		"publicly_queryable" => true,
		"show_ui" => true,
		"show_in_rest" => true,
		"rest_base" => "",
		"rest_controller_class" => "WP_REST_Posts_Controller",
		'show_in_graphql'       => true,
		'graphql_single_name'   => 'portfolio',
		'graphql_plural_name'   => 'portfolios',
		"has_archive" => false,
		"show_in_menu" => true,
		"show_in_nav_menus" => true,
		"delete_with_user" => false,
		"exclude_from_search" => false,
		"capability_type" => "post",
		"map_meta_cap" => true,
		"hierarchical" => false,
		"rewrite" => [ "slug" => "portfolio", "with_front" => false ],
		"query_var" => true,
		"menu_icon" => 'dashicons-buddicons-activity',
		"supports" => [ "title", "editor", "thumbnail", "excerpt", "custom-fields", "author" ],
	];

	register_post_type( "portfolio", $args );

	/**
	 * Post Type: Services.
	 */
	$labels = [
		"name" => esc_html__( "Services", "wds" ),
		"singular_name" => esc_html__( "Service", "wds" ),
	];

	$args = [
		"label" => esc_html__( "Services", "wds" ),
		"labels" => $labels,
		"description" => "",
		"public" => true,
		"publicly_queryable" => true,
		"show_ui" => true,
		"show_in_rest" => true,
		"rest_base" => "",
		"rest_controller_class" => "WP_REST_Posts_Controller",
		'show_in_graphql'       => true,
		'graphql_single_name'   => 'service',
		'graphql_plural_name'   => 'services',
		"has_archive" => false,
		"show_in_menu" => true,
		"show_in_nav_menus" => true,
		"delete_with_user" => false,
		"exclude_from_search" => false,
		"capability_type" => "post",
		"map_meta_cap" => true,
		"hierarchical" => false,
		"rewrite" => [ "slug" => "service", "with_front" => false ],
		"query_var" => true,
		"menu_icon" => 'dashicons-admin-tools',
		"supports" => [ "title", "editor", "thumbnail", "excerpt", "custom-fields", "author" ],
	];

	register_post_type( "service", $args );

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

	/**
	 * Post Type: Testimonials.
	 */
	$labels = [
		"name" => esc_html__( "Testimonials", "wds" ),
		"singular_name" => esc_html__( "Testimonial", "wds" ),
	];

	$args = [
		"label" => esc_html__( "Testimonials", "wds" ),
		"labels" => $labels,
		"description" => "",
		"public" => true,
		"publicly_queryable" => true,
		"show_ui" => true,
		"show_in_rest" => true,
		"rest_base" => "",
		"rest_controller_class" => "WP_REST_Posts_Controller",
		'show_in_graphql'       => true,
		'graphql_single_name'   => 'testimonial',
		'graphql_plural_name'   => 'testimonials',
		"has_archive" => false,
		"show_in_menu" => true,
		"show_in_nav_menus" => true,
		"delete_with_user" => false,
		"exclude_from_search" => false,
		"capability_type" => "post",
		"map_meta_cap" => true,
		"hierarchical" => false,
		"rewrite" => [ "slug" => "testimonial", "with_front" => false ],
		"query_var" => true,
		"menu_icon" => 'dashicons-megaphone',
		"supports" => [ "title", "editor", "thumbnail", "excerpt", "custom-fields", "author" ],
	];

	register_post_type( "testimonial", $args );
}
add_action( 'init', 'wds_register_custom_post_types' );
