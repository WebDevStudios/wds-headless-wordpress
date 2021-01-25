<?php
/*
Plugin Name: Block Manager
Plugin URI: https://connekthq.com/plugins/block-manager/
Description: Globally manage the active state of each Gutenberg block.
Text Domain: block-manager
Author: Darren Cooney
Author URI: https://connekthq.com
Version: 1.1
License: GPL
Copyright: Darren Cooney & Connekt Media
*/


// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'BLOCK_MANAGER_VERSION', '1.1' );
define( 'BLOCK_MANAGER_RELEASE', 'January 19, 2021' );
define( 'BLOCK_MANAGER_DIR_PATH', plugin_dir_path( __FILE__ ) );
define( 'BLOCK_MANAGER_OPTION', 'gbm_disabled_blocks' );

/**
 * Block Manager Class.
 *
 * @since 1.0
 */
class Gutenberg_Block_Manager {

	/**
	 * Block Manager Instance variable.
	 *
	 * @var $instance
	 * @since 1.0
	 */
	private static $instance = null;


	/**
	 * Define the Block Manager Instance
	 *
	 * @author ConnektMedia
	 * @since 1.0
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new Gutenberg_Block_Manager();
		}
		return self::$instance;
	}


	/**
	 * Initialize plugin.
	 *
	 * @author ConnektMedia
	 * @since 1.0
	 */
	private function __construct() {

		add_action( 'enqueue_block_editor_assets', array( $this, 'gbm_enqueue' ) );
		load_plugin_textdomain( 'gutenberg-block-manager', false, dirname( plugin_basename( __FILE__ ) ) . '/lang' );
		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( &$this, 'gbm_action_links' ) );
		require_once BLOCK_MANAGER_DIR_PATH . 'class-admin.php';
		require_once 'api/toggle.php';
		require_once 'api/bulk_process.php';
		require_once 'api/export.php';
		include_once 'vendor/connekt-plugin-installer/class-connekt-plugin-installer.php';

	}

	/**
	 * Enqueue the scripts.
	 *
	 * @author ConnektMedia
	 * @since 1.0
	 */
	public function gbm_enqueue() {
		$script = 'dist/js/gbm.js';
		wp_enqueue_script(
			'gutenberg-block-manager',
			plugins_url( $script, __FILE__ ),
			array( 'wp-edit-post' ),
			BLOCK_MANAGER_VERSION, false
		);
		wp_localize_script(
			'gutenberg-block-manager',
			'gutenberg_block_manager',
			$this->gbm_get_disabled_blocks()
		);
	}

	/**
	 * Get all disabled blocks.
	 *
	 * @author ConnektMedia
	 * @since 1.0
	 * @return array
	 */
	public static function gbm_get_disabled_blocks() {
		$blocks_manual = (array) get_option( BLOCK_MANAGER_OPTION, array() ); // Get manually disabled blocks.
		$blocks_filter = apply_filters( 'gbm_disabled_blocks', [] ); // Get filtered disabled blocks.
		$blocks_array  = array_merge( $blocks_manual, $blocks_filter ); // Merge arrays.
		$blocks        = array_unique( $blocks_array ); // Remove Duplicates.

		return $blocks ? $blocks : [];
	}

	/**
	 * Get all filtered blocks.
	 *
	 * @author ConnektMedia
	 * @since 1.1
	 * @return array
	 */
	public static function gbm_get_filtered_blocks() {
		$blocks = apply_filters( 'gbm_disabled_blocks', [] ); // Get filtered disabled blocks.
		return $blocks ? $blocks : [];
	}

	/**
	 * Add plugin action links to WP plugin screen
	 *
	 * @author ConnektMedia
	 * @since 1.0
	 * @param array $links The action links.
	 * @return array
	 */
	public static function gbm_action_links( $links ) {
		$settings = '<a href="' . get_admin_url( null, 'options-general.php?page=gutenberg-block-manager' ) . '">' . __( 'Manage Blocks', 'gutenberg-block-manager' ).'</a>';
		array_unshift( $links, $settings );
		return $links;
	}

	/**
	 * Confirm user has access to Block Manager.
	 *
	 * @author ConnektMedia
	 * @since 1.1
	 * @return Boolean
	 */
	public static function has_access() {
		$access = false;
		if ( is_user_logged_in() && current_user_can( apply_filters( 'gutenberg_block_manager_user_role', 'activate_plugins' ) ) ) {
			$access = true;
		}
		return $access;
	}

}

/**
 * The main function for Gutenberg_Block_Manager_Init
 *
 * @author ConnektMedia
 * @since 1.0
 */
function block_manager_init() {
	include_once ABSPATH . 'wp-admin/includes/plugin.php';
	if ( is_plugin_active( 'gutenberg/gutenberg.php' ) || version_compare( get_bloginfo( 'version' ), '4.9.9', '>' ) ) {
		Gutenberg_Block_Manager::instance();
	}
}
add_action( 'plugins_loaded', 'block_manager_init', 100 );
