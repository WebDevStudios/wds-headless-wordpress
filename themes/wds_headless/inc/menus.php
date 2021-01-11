<?php
/**
 * WP Nav Menus.
 *
 * @author WebDevStudios
 * @package wds
 * @since 1.0
 */

/**
 * Register the theme nav menus.
 *
 * @author WebDevStudios
 * @since 1.0
 */
function wds_register_my_menus() {
	register_nav_menus(
		array(
			'primary-menu' => __( 'Header Menu' ),
			'mobile-menu'  => __( 'Mobile Menu' ),
			'footer-menu'  => __( 'Footer Menu' ),
		)
	);
}
add_action( 'init', 'wds_register_my_menus' );
