<?php
/**
 * Page/post editor functionality.
 *
 * @author WebDevStudios
 * @package WDS_Headless
 * @since 1.0.0
 */

namespace WDS_Headless;

/**
 * Customize settings for the page/post editor.
 *
 * @author WebDevStudios
 * @since 1.0.0
 */
function customize_editor() {
  // Add support for post thumbnails.
	add_theme_support( 'post-thumbnails' );

  // Add excerpts to pages.
	add_post_type_support( 'page', 'excerpt' );

  // Reset available font size presets to only "normal" (16px).
	add_theme_support(
		'editor-font-sizes',
		[
			[
				'name' => 'Normal',
				'size' => 16,
				'slug' => 'normal',
			],
		]
	);
}
add_action( 'after_setup_theme', __NAMESPACE__ . '\customize_editor' );
