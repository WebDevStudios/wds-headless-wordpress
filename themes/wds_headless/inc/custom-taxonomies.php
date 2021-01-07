<?php
/**
 * Custom Taxonomies.
 *
 * Note: exported from CPTUI.
 *
 * @author WebDevStudios
 * @package wds
 * @since 1.0
 */

/**
 * Register custom taxonomies.
 *
 * @author WebDevStudios
 * @since 1.0
 */
function wds_register_taxonomies() {

	/**
	 * Taxonomy: Clients.
	 */
	$labels = [
		'name'          => esc_html__( 'Clients', 'wds' ),
		'singular_name' => esc_html__( 'Client', 'wds' ),
	];

	$args = [
		'label'                 => esc_html__( 'Clients', 'wds' ),
		'labels'                => $labels,
		'public'                => true,
		'publicly_queryable'    => true,
		'hierarchical'          => false,
		'show_ui'               => true,
		'show_in_menu'          => true,
		'show_in_nav_menus'     => true,
		'query_var'             => true,
		'rewrite'               => [
			'slug'       => 'clients',
			'with_front' => true,
		],
		'show_admin_column'     => false,
		'show_in_rest'          => true,
		'rest_base'             => 'clients',
		'rest_controller_class' => 'WP_REST_Terms_Controller',
		'show_in_graphql'       => true,
		'graphql_single_name'   => 'client',
		'graphql_plural_name'   => 'clients',
		'show_in_quick_edit'    => false,
	];

	register_taxonomy( 'role', [ 'portfolio', 'testimonial' ], $args );
}
add_action( 'init', 'wds_register_taxonomies', 0 );
