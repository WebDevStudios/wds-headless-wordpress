<?php
/**
 * ACF Blocks settings.
 *
 * @author WebDevStudios
 * @package wds
 * @since 1.0
 */

/**
 * Register custom ACF blocks.
 *
 * @author WebDevStudios
 * @since 1.0
 */
function wds_acf_blocks_init() {

	$supports = [
		'align'  => 'none',
		'anchor' => false,
		'mode'   => false,
	];

	// Accordions.
	acf_register_block_type(
		[
			'name'            => 'accordions',
			'title'           => esc_html__( 'Accordions', 'wds' ),
			'description'     => esc_html__( 'A component for adding expand and collapse sections.', 'wds' ),
			'render_callback' => '',
			'category'        => 'wds-content',
			'icon'            => 'sort',
			'keywords'        => [ 'accordion', 'accordions', 'wds' ],
			'mode'            => 'edit',
			'enqueue_assets'  => '',
			'align'           => 'wide',
			'supports'        => $supports,
		]
	);

	// Algolia.
	acf_register_block_type(
		[
			'name'            => 'algolia',
			'title'           => esc_html__( 'Algolia', 'wds' ),
			'description'     => esc_html__( 'A grid of content cards and filters powered by Algolia', 'wds' ),
			'render_callback' => '',
			'category'        => 'wds-content',
			'icon'            => 'grid-view',
			'keywords'        => [ 'algolia', 'content', 'grid', 'featured', 'wds' ],
			'mode'            => 'edit',
			'enqueue_assets'  => '',
			'align'           => 'wide',
			'supports'        => $supports,
		]
	);

	// Netflix.
	acf_register_block_type(
		[
			'name'            => 'netflix',
			'title'           => esc_html__( 'Netflix Carousel', 'wds' ),
			'description'     => esc_html__( 'A Netflix style slider for selecting related content.', 'wds' ),
			'render_callback' => '',
			'category'        => 'wds-content',
			'icon'            => 'images-alt2',
			'keywords'        => [ 'slider', 'netflix', 'carousel', 'wds' ],
			'mode'            => 'edit',
			'enqueue_assets'  => '',
			'align'           => 'wide',
			'supports'        => $supports,
		]
	);

	// Media Text.
	acf_register_block_type(
		[
			'name'            => 'acf-media-text',
			'title'           => esc_html__( 'ACF Media Text', 'wds' ),
			'description'     => esc_html__( 'A block to display media and text in a 50/50 layout.', 'wds' ),
			'render_callback' => '',
			'category'        => 'wds-content',
			'icon'            => 'images-alt2',
			'keywords'        => [ 'media', 'text', 'button', 'wds' ],
			'mode'            => 'edit',
			'enqueue_assets'  => '',
			'align'           => 'wide',
			'supports'        => $supports,
		]
	);
}
add_action( 'acf/init', 'wds_acf_blocks_init' );
