<?php
/**
 * Block Manager admin class.
 *
 * @author ConnektMedia
 * @since 1.0
 */
class GBM_Admin {

	/**
	 * Construct method.
	 *
	 * @author ConnektMedia
	 * @since 1.0
	 */
	public function __construct() {

		// Register the submenu.
		add_action( 'admin_menu', array( $this, 'gbm_register_sub_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'gbm_admin_enqueue' ) );

	}

	/**
	 * Enqueue the scripts and styles.
	 *
	 * @author ConnektMedia
	 * @since 1.0
	 * @param string $hook The current page ID.
	 * @return null
	 */
	public function gbm_admin_enqueue( $hook ) {

		if ( 'settings_page_gutenberg-block-manager' !== $hook ) {
			return;
		}

		// Register Block Categories.
		$block_categories = array();
		if ( function_exists( 'get_block_categories' ) ) {
			$block_categories = get_block_categories( get_post() );
		}
		wp_add_inline_script( 'wp-blocks', sprintf( 'wp.blocks.setCategories( %s );', wp_json_encode( $block_categories ) ), 'after' );

		do_action( 'enqueue_block_editor_assets' );
		wp_dequeue_script( 'gutenberg-block-manager' );

		$block_registry = WP_Block_Type_Registry::get_instance();
		foreach ( $block_registry->get_all_registered() as $block_name => $block_type ) {
			// Front-end script.
			if ( ! empty( $block_type->editor_script ) ) {
				wp_enqueue_script( $block_type->editor_script );
			}
		}

		// Enqueue Scripts.
		$suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min'; // Use minified libraries if SCRIPT_DEBUG is turned off.

		wp_enqueue_style(
			'gutenberg-block-manager-styles',
			plugins_url( 'dist/css/style.css',
			__FILE__ ),
			array(),
			BLOCK_MANAGER_VERSION
		);

		wp_enqueue_script(
			'gutenberg-block-manager-admin',
			plugins_url( 'dist/js/gbm-admin' . $suffix . '.js', __FILE__ ),
			array( 'wp-blocks', 'wp-element', 'wp-data', 'wp-edit-post', 'wp-components', 'wp-block-library' ),
			BLOCK_MANAGER_VERSION,
			true
		);

		// Localize Scripts.
		wp_localize_script(
			'gutenberg-block-manager-admin',
			'gbm_localize',
			array(
				'disabledBlocks' => Gutenberg_Block_Manager::gbm_get_disabled_blocks(),
				'filteredBlocks' => Gutenberg_Block_Manager::gbm_get_filtered_blocks(),
				'root'           => esc_url_raw( rest_url() ),
				'nonce'          => wp_create_nonce( 'wp_rest' ),
				'enable'         => __( 'Enable', 'gutenberg-block-manager' ),
				'disable'        => __( 'Disable', 'gutenberg-block-manager' ),
				'enable_all'     => __( 'Enable All', 'gutenberg-block-manager' ),
				'disable_all'    => __( 'Disable All', 'gutenberg-block-manager' ),
				'toggle_all'     => __( 'Toggle All Blocks', 'gutenberg-block-manager' ),
				'toggle'         => __( 'Toggle Block Activation', 'gutenberg-block-manager' ),
				'search_label'   => __( 'Filter Blocks', 'gutenberg-block-manager' ),
				'submit'         => __( 'Submit', 'gutenberg-block-manager' ),
				'loading'        => __( 'Loading Blocks', 'gutenberg-block-manager' ),
				'loading_export' => __( 'Getting export data...', 'gutenberg-block-manager' ),
				'copy'           => __( 'Copy Code', 'gutenberg-block-manager' ),
				'copied'         => __( 'Copied', 'gutenberg-block-manager' ),
				'close'          => __( 'Close', 'gutenberg-block-manager' ),
				'grid'           => __( 'Grid', 'gutenberg-block-manager' ),
				'list'           => __( 'List', 'gutenberg-block-manager' ),
				'export'         => __( 'Export', 'gutenberg-block-manager' ),
				'export_intro'   => __( 'Add the the following code to your functions.php to remove blocks at the theme level.', 'gutenberg-block-manager' ),
				'filtered_alert' => __( 'This block has been globally disabled via the `gbm_disabled_blocks` filter and cannot be activated.', 'gutenberg-block-manager' ),
			)
		);

	}

	/**
	 * Register submenu item.
	 *
	 * @author ConnektMedia
	 * @since 1.0
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
	 * The Block Manager admin page.
	 *
	 * @author ConnektMedia
	 * @since 1.0
	 */
	public function gbm_submenu_page_callback() {
		?>
		<div class="gbm-page-wrap">
			<div class="gbm-page-wrap--header">
				<h2><?php esc_html_e( 'Gutenberg Block Manager', 'gutenberg-block-manager' ); ?> <span><a href="https://connekthq.com" target="_blank"><?php esc_html_e( 'by Connekt', 'gutenberg-block-manager' ); ?></a></span></h2>
				<p><?php printf( __( 'Manage the activation status of your %s blocks - disabled blocks will be removed from the block inserter.', 'gutenberg-block-manager' ), '<span class="cnkt-block-totals block-total">--</span>'); ?>
				<button class="button" id="otherPlugins"><span class="dashicons dashicons-admin-plugins"></span> <?php esc_html_e( 'Other Plugins', 'gutenberg-block-manager' ); ?></button>
			</div>
			<div id="gbm-container">
				<div id="gbm-other-plugins">
					<?php
					$plugin_array = array(
						array(
							'slug' => 'ajax-load-more',
						),
						array(
							'slug' => 'easy-query',
						),
						array(
							'slug' => 'instant-images',
						),
						array(
							'slug' => 'velocity',
						),
					);
					?>
					<section>
						<h2><?php echo sprintf( __("Other Plugins from %s Connekt %s", 'gutenberg-block-manager' ), '<a href="https://connekthq.com" target="_blank">', '</a>' ); ?></h2>
						<button class="button button-secondary" id="otherPluginsClose">&times; <?php esc_html_e( 'Close', 'gutenberg-block-manager' ); ?></button>
						<div class="cta-wrap">
							<?php
							if ( class_exists( 'Connekt_Plugin_Installer' ) ) {
								Connekt_Plugin_Installer::init( $plugin_array );
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
