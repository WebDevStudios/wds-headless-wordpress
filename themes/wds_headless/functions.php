<?php
/**
 * Theme functionality.
 *
 * @author WebDevStudios
 * @package wds
 * @since 1.0
 */

require_once 'inc/custom-post-types.php';
require_once 'inc/custom-taxonomies.php';
require_once 'inc/wpgraphql.php';
require_once 'inc/acf.php';
require_once 'inc/blocks.php';
require_once 'inc/algolia.php';
require_once 'inc/admin.php';

/**
 * Sets up theme defaults .
 *
 * @author WebDevStudios
 * @since 1.0
 */
function wds_theme_setup() {

	// Add support for post thumbnails.
	add_theme_support( 'post-thumbnails' );
	add_image_size( 'nineteen-twenty', 1920, 540, true );

	// Add support for editor styles.
	add_theme_support( 'editor-styles' );

	// Enqueue editor styles.
	add_editor_style( 'style.css' );

	// Add excerpts to pages.
	add_post_type_support( 'page', 'excerpt' );

	// Add ACF Options Page.
	if ( function_exists( 'acf_add_options_page' ) ) {
		acf_add_options_page([
			'page_title'      => 'Additional Settings',
			'menu_title'      => 'Additional Settings',
			'menu_slug'       => 'theme-general-settings',
			'capability'      => 'edit_posts',
			'redirect'        => false,
			'show_in_graphql' => true,
		]);
	}
}
add_action( 'after_setup_theme', 'wds_theme_setup' );
