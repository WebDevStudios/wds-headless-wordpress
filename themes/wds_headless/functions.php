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
require_once 'inc/menus.php';

/**
 * Sets up theme defaults.
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

/**
 * Sets up default disabled bocks.
 *
 * @see https://wordpress.org/plugins/block-manager/
 * @author WebDevStudios
 * @since 1.0
 * @return array
 */
function wds_disabled_block() {
	return [ 'core/text-columns', 'core/nextpage', 'core/more', 'variation;core/embed;facebook', 'variation;core/embed;instagram', 'variation;core/embed;wordpress', 'variation;core/embed;soundcloud', 'variation;core/embed;spotify', 'variation;core/embed;flickr', 'variation;core/embed;animoto', 'variation;core/embed;cloudup', 'variation;core/embed;collegehumor', 'variation;core/embed;crowdsignal', 'variation;core/embed;dailymotion', 'variation;core/embed;imgur', 'variation;core/embed;issuu', 'variation;core/embed;kickstarter', 'variation;core/embed;meetup-com', 'variation;core/embed;mixcloud', 'variation;core/embed;reddit', 'variation;core/embed;reverbnation', 'variation;core/embed;screencast', 'variation;core/embed;scribd', 'variation;core/embed;slideshare', 'variation;core/embed;smugmug', 'variation;core/embed;speaker-deck', 'variation;core/embed;tiktok', 'variation;core/embed;ted', 'variation;core/embed;tumblr', 'variation;core/embed;videopress', 'variation;core/embed;wordpress-tv', 'variation;core/embed;amazon-kindle', 'core/audio', 'core/cover', 'core/file', 'core/gallery', 'core/video', 'core/freeform', 'core/subhead', 'core/preformatted', 'core/verse', 'core/archives', 'core/calendar', 'core/categories', 'core/html', 'core/latest-comments', 'core/latest-posts', 'core/rss', 'core/search', 'core/social-link', 'core/social-links', 'core/tag-cloud', 'core/shortcode' ];
};

add_filter( 'gbm_disabled_blocks', 'wds_disabled_block' );
