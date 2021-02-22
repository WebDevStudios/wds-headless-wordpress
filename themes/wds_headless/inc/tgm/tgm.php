<?php
/**
 * Theme functionality.
 *
 * @author WebDevStudios
 * @package wds-headless-theme
 * @since 1.0
 */

require_once 'class-tgm-plugin-activation.php';

 /**
  * List of required plugins.
  *
  * @see http://tgmpluginactivation.com/configuration/
  * @author WebDevStudios
  * @since 1.0
  */
function wds_register_required_plugins() {

	$plugins = [

		// Plugins only available on Github.
		[
			'name'     => 'WPGraphQL for Advanced Custom Fields',
			'slug'     => 'wp-graphql-acf',
			'source'   => 'https://github.com/wp-graphql/wp-graphql-acf/archive/develop.zip',
			'required' => true,
		],
		[
			'name'     => 'WPGraphQL for Custom Post Type UI',
			'slug'     => 'wp-graphql-custom-post-type-ui',
			'source'   => 'https://github.com/wp-graphql/wp-graphql-custom-post-type-ui/archive/master.zip',
		],
		[
			'name'     => 'WPGraphQL Gutenberg',
			'slug'     => 'wp-graphql-gutenberg',
			'source'   => 'https://github.com/pristas-peter/wp-graphql-gutenberg/archive/develop.zip',
			'required' => true,
		],
		[
			'name'     => 'WPGraphQL Tax Query',
			'slug'     => 'wp-graphql-tax-query',
			'source'   => 'https://github.com/wp-graphql/wp-graphql-tax-query/archive/develop.zip',
			'required' => true,
		],

		// Plugins from the WordPress Plugin Repository.
		[
			'name'        => 'Advanced Custom Fields',
			'slug'        => 'advanced-custom-fields',
			'is_callable' => 'acf',
			'required'    => true,
		],
		[
			'name'      => 'Custom Post Type UI',
			'slug'      => 'custom-post-type-ui',
		],
		[
			'name'      => 'Gutenberg',
			'slug'      => 'gutenberg',
		],
		[
			'name'      => 'Gutenberg Block Manager',
			'slug'      => 'block-manager',
			'required'  => true,
		],
		[
			'name'        => 'WordPress SEO by Yoast',
			'slug'        => 'wordpress-seo',
			'is_callable' => 'wpseo_init',
			'required'    => true,
		],
		[
			'name'      => 'WPGraphQL',
			'slug'      => 'wp-graphql',
			'required'  => true,
		],
		[
			'name'      => 'WPGraphQL Yoast SEO Addon',
			'slug'      => 'add-wpgraphql-seo',
			'required'  => true,
		],
		[
			'name'      => 'WP Search with Algolia',
			'slug'      => 'wp-search-with-algolia',
			'required'  => true,
		],
	];

	// Configuration settings.
	$config = [
		'id'           => 'wds',                   // Unique ID for hashing notices for multiple instances of TGMPA.
		'default_path' => '',                      // Default absolute path to bundled plugins.
		'menu'         => 'tgmpa-install-plugins', // Menu slug.
		'parent_slug'  => 'themes.php',            // Parent menu slug.
		'capability'   => 'edit_theme_options',    // Capability needed to view plugin install page, should be a capability associated with the parent menu used.
		'has_notices'  => true,                    // Show admin notices or not.
		'dismissable'  => true,                    // If false, a user cannot dismiss the nag message.
		'dismiss_msg'  => '',                      // If 'dismissable' is false, this message will be output at top of nag.
		'is_automatic' => true,                    // Automatically activate plugins after installation or not.
		'message'      => '',                      // Message to output right before the plugins table.
	];

	tgmpa( $plugins, $config );
}
add_action( 'tgmpa_register', 'wds_register_required_plugins' );
