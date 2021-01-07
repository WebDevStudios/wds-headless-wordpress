<?php

class GBM_Admin {

	/**
	 * Autoload method
	 */
	public function __construct() {

		// Register the submenu.
		add_action( 'admin_menu', array( $this, 'gbm_register_sub_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'gbm_admin_enqueue' ) );

	}

	/**
	 * Enqueue the scripts and styles.
	 *
	 * @param string $hook The current page ID.
	 */
	public function gbm_admin_enqueue( $hook ) {

		if ( 'settings_page_gutenberg-block-manager' !== $hook ) {
			return;
		}

		// Register Block Categories
		$block_categories = array();
		if (function_exists('get_block_categories')){
			$block_categories = get_block_categories(get_post());
		}
		wp_add_inline_script( 'wp-blocks', sprintf( 'wp.blocks.setCategories( %s );', wp_json_encode( $block_categories ) ), 'after');

		do_action('enqueue_block_editor_assets');
		wp_dequeue_script('gutenberg-block-manager');

		$block_registry = WP_Block_Type_Registry::get_instance();
		foreach ( $block_registry->get_all_registered() as $block_name => $block_type ) {
			// Front-end script.
			if ( ! empty( $block_type->editor_script ) ) {
				wp_enqueue_script( $block_type->editor_script );
			}
		}

		// Enqueue Scripts.
		$suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min'; // Use minified libraries if SCRIPT_DEBUG is turned off

		wp_enqueue_style(
			'gutenberg-block-manager-styles',
			plugins_url( 'dist/css/style.css',
			__FILE__ ),
			array(),
			BLOCK_MANAGER_VERSION
		);

		wp_enqueue_script(
			'gutenberg-block-manager-admin',
			plugins_url( 'dist/js/gbm-admin'. $suffix .'.js', __FILE__ ),
			array( 'wp-blocks', 'wp-element', 'wp-data', 'wp-edit-post', 'wp-components', 'wp-block-library' ),
			BLOCK_MANAGER_VERSION,
			true
		);

		// Localize Scripts.
		wp_localize_script(
			'gutenberg-block-manager-admin',
			'gbm_localize',
			array(
				'disabledBlocks' 	=> get_option( BLOCK_MANAGER_OPTION, array() ),
				'root' 				=> esc_url_raw( rest_url() ),
				'nonce' 				=> wp_create_nonce( 'wp_rest' ),
				'enable'  			=> __( 'Enable', 'gutenberg-block-manager' ),
				'disable' 			=> __( 'Disable', 'gutenberg-block-manager' ),
				'enable_all'  		=> __( 'Enable All', 'gutenberg-block-manager' ),
				'disable_all' 		=> __( 'Disable All', 'gutenberg-block-manager' ),
				'toggle_all' 		=> __( 'Toggle All Blocks', 'gutenberg-block-manager' ),
				'toggle' 			=> __( 'Toggle Block Activation', 'gutenberg-block-manager' ),
				'search_label' 	=> __( 'Filter Blocks', 'gutenberg-block-manager' ),
				'submit'		   	=> __( 'Submit', 'gutenberg-block-manager' ),
				'loading'			=> __( 'Loading Blocks', 'gutenberg-block-manager' )
			)
		);

	}

	/**
	 * gbm_register_sub_menu
	 * Register submenu item
	 */
	public function gbm_register_sub_menu() {

		add_submenu_page(
			'options-general.php',
			esc_html__( 'Block Manager', 'gutenberg-block-manager' ),
			esc_html__( 'Block Manager', 'gutenberg-block-manager' ),
			apply_filters( 'gutenberg_block_manager_user_role', 'activate_plugins' ),
			'gutenberg-block-manager',
			array( $this, 'gbm_submenu_page_callback' )
		);

	}

	/**
	 * gbm_submenu_page_callback
	 * The admin page
	 */
	public function gbm_submenu_page_callback() {
		//update_option( BLOCK_MANAGER_OPTION, []);
		?>
		<div class="gbm-page-wrap">
			<div class="gbm-page-wrap--header">
				<h2><?php _e( 'Gutenberg Block Manager', 'gutenberg-block-manager' ); ?> <span><a href="https://connekthq.com" target="_blank"><?php _e( 'by Connekt', 'gutenberg-block-manager' ); ?></a></span></h2>
				<p><?php printf( __( 'Manage the activation status of your %s blocks - disabled blocks will be removed from the block inserter.', 'gutenberg-block-manager' ), '<span class="cnkt-block-totals block-total">--</span>'); ?>

				<button class="button" id="otherPlugins"><span class="dashicons dashicons-admin-plugins"></span> <?php _e( 'Other Plugins', 'gutenberg-block-manager' ); ?></button>
			</div>
			<div id="gbm-container">
				<div id="gbm-other-plugins">
					<?php
			      $plugin_array = array(
			         array(
			            'slug' => 'ajax-load-more'
			         ),
			         array(
			            'slug' => 'easy-query'
			         ),
			         array(
			            'slug' => 'instant-images'
			         ),
			         array(
			            'slug' => 'velocity'
			         )
			      );
			      ?>
			      <section>
				      <h2><?php echo sprintf(__("Other Plugins from %s Connekt %s", 'gutenberg-block-manager'), '<a href="https://connekthq.com" target="_blank">', '</a>');?></h2>
				      <button class="button button-secondary" id="otherPluginsClose">&times; <?php _e( 'Close', 'gutenberg-block-manager' ); ?></button>
				      <div class="cta-wrap">
					      <?php
					      if(class_exists('Connekt_Plugin_Installer')){
					         Connekt_Plugin_Installer::init($plugin_array);
					      }
							?>
				      </div>
			      </section>
				</div>
				<div id="app" class="gbm"></div>
			</div>
		</div>
		<?php
	}
}

new GBM_Admin();
