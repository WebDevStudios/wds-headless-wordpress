<?php

namespace DeliciousBrains\WPMDBMF;

use DeliciousBrains\WPMDB\Common\Properties\DynamicProperties;
use DeliciousBrains\WPMDB\Common\Properties\Properties;
use DeliciousBrains\WPMDB\Common\Util\Util;
use DeliciousBrains\WPMDB\Container;
use DeliciousBrains\WPMDB\Pro\Addon\Addon;
use DeliciousBrains\WPMDB\Pro\Addon\AddonAbstract;
use DeliciousBrains\WPMDB\Pro\UI\Template;

/**
 * Class MediaFilesAddon
 *
 * @package DeliciousBrains\WPMDBMF
 */
class MediaFilesAddon extends AddonAbstract {

	/**
	 * An array strings used for translations
	 *
	 * @var array $media_strings
	 */
	protected $media_strings;

	/**
	 * An instance of MediaFilesLocal
	 *
	 * @var MediaFilesLocal
	 */
	public $media_files_local;

	/**
	 * @var Template
	 */
	private $template;
	private $plugin_dir_path;
	private $plugin_folder_name;
	private $plugins_url;

	const MDB_VERSION_REQUIRED = '1.9.6';

	public function __construct(
		Addon $addon,
		Properties $properties,
		Template $template
	) {
		parent::__construct( $addon, $properties );

		$this->plugin_slug    = 'wp-migrate-db-pro-media-files';
		$this->plugin_version = $GLOBALS['wpmdb_meta']['wp-migrate-db-pro-media-files']['version'];

		$this->template           = $template;
		$plugin_file_path         = dirname( __DIR__ ) . '/wp-migrate-db-pro-media-files.php';
		$this->plugin_dir_path    = plugin_dir_path( $plugin_file_path );
		$this->plugin_folder_name = basename( $this->plugin_dir_path );

		// @TODO see if this works
		$this->plugins_url   = trailingslashit( plugins_url( $this->plugin_folder_name ) );
		$this->template_path = $this->plugin_dir_path . 'template/';
	}

	public function register() {
		if ( ! $this->meets_version_requirements( self::MDB_VERSION_REQUIRED ) ) {
			return;
		}

		add_action( 'admin_init', [ $this, 'plugin_name' ] );
		add_action( 'wpmdb_after_advanced_options', array( $this, 'migration_form_controls' ) );
		add_action( 'wpmdb_load_assets', array( $this, 'load_assets' ) );
		add_action( 'wpmdbmf_after_migration_options', array( $this, 'after_migration_options_template' ) );
		add_filter( 'wpmdb_diagnostic_info', array( $this, 'diagnostic_info' ) );
		add_filter( 'wpmdb_establish_remote_connection_data', array( $this, 'establish_remote_connection_data' ) );
		add_filter( 'wpmdb_nonces', array( $this, 'add_nonces' ) );
		add_filter( 'wpmdb_data', array( $this, 'js_variables' ) );
	}

	public function plugin_name() {
		$this->addon_name = $this->addon->get_plugin_name( 'wp-migrate-db-pro-media-files/wp-migrate-db-pro-media-files.php' );
	}

	/**
	 * Adds the media settings to the migration setting page in core
	 */
	function migration_form_controls() {

		$this->template->template( 'migrate', '', [], $this->template_path );
	}

	/**
	 * Get translated strings for javascript and other functions
	 *
	 * @return array Array of translations
	 */
	function get_strings() {
		$strings = array(
			'removing_all_files_pull'      => __( 'Removing all local files before download of remote media', 'wp-migrate-db-pro-media-files' ),
			'removing_all_files_push'      => __( 'Removing all remote files before upload of local media', 'wp-migrate-db-pro-media-files' ),
			'removing_files_pull'          => __( 'Removing local files that are not found on the remote site', 'wp-migrate-db-pro-media-files' ),
			'removing_files_push'          => __( 'Removing remote files that are not found on the local site', 'wp-migrate-db-pro-media-files' ),
			'determining'                  => __( 'Determining media to migrate', 'wp-migrate-db-pro-media-files' ),
			'determining_progress'         => __( 'Determining media to migrate - %1$d of %2$d attachments (%3$d%%)', 'wp-migrate-db-pro-media-files' ),
			'error_determining'            => __( 'Error while attempting to determine which attachments to migrate.', 'wp-migrate-db-pro-media-files' ),
			'migration_failed'             => __( 'Migration failed', 'wp-migrate-db-pro-media-files' ),
			'problem_migrating_media'      => __( 'A problem occurred when migrating the media files.', 'wp-migrate-db-pro-media-files' ),
			'media_attachments'            => __( 'Media Attachments', 'wp-migrate-db-pro-media-files' ),
			'media_files'                  => __( 'Files', 'wp-migrate-db-pro-media-files' ),
			'migrate_media_files_pull'     => __( 'Downloading files', 'wp-migrate-db-pro-media-files' ),
			'migrate_media_files_push'     => __( 'Uploading files', 'wp-migrate-db-pro-media-files' ),
			'migrate_media_files_cli_pull' => __( 'Downloading %d of %d files', 'wp-migrate-db-pro-media-files' ),
			'migrate_media_files_cli_push' => __( 'Uploading %d of %d files', 'wp-migrate-db-pro-media-files' ),
			'files_uploaded'               => __( 'Files Uploaded', 'wp-migrate-db-pro-media-files' ),
			'files_downloaded'             => __( 'Files Downloaded', 'wp-migrate-db-pro-media-files' ),
			'file_too_large'               => __( 'The following file is too large to migrate:', 'wp-migrate-db-pro-media-files' ),
			'please_select_a_subsite'      => __( 'Please select at least one subsite to transfer media files for.', 'wp-migrate-db-pro-media-files' ),
		);

		if ( is_null( $this->media_strings ) ) {
			$this->media_strings = $strings;
		}

		return $this->media_strings;
	}

	/**
	 * Retrieve a specific translated string
	 *
	 * @param string $key Array key
	 *
	 * @return string Translation
	 */
	function get_string( $key ) {
		$strings = $this->get_strings();

		return ( isset( $strings[ $key ] ) ) ? $strings[ $key ] : '';
	}

	/**
	 * Load media related assets in core plugin
	 */
	function load_assets() {

		$version    = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? time() : $this->plugin_version;
		$ver_string = '-' . str_replace( '.', '', $this->plugin_version );

		$src = $this->plugins_url . 'asset/build/css/styles.css';
		wp_enqueue_style( 'wp-migrate-db-pro-media-files-styles', $src, array( 'wp-migrate-db-pro-styles' ), $version );

		$src = $this->plugins_url . "asset/build/js/bundle{$ver_string}.js";
		wp_enqueue_script( 'wp-migrate-db-pro-media-files-script', $src, array(
			'jquery',
			'wp-migrate-db-pro-script',
		), $version, true );

		wp_localize_script( 'wp-migrate-db-pro-media-files-script', 'wpmdbmf_strings', $this->get_strings() );
	}

	/**
	 * Check the remote site has the media addon setup
	 *
	 * @param array $data Connection data
	 *
	 * @return array Updated connection data
	 */
	function establish_remote_connection_data( $data ) {
		$data['media_files_available'] = '1';
		$data['media_files_version']   = $this->plugin_version;
		if ( function_exists( 'ini_get' ) ) {
			$max_file_uploads = ini_get( 'max_file_uploads' );
		}
		$max_file_uploads                     = ( empty( $max_file_uploads ) ) ? 20 : $max_file_uploads;
		$data['media_files_max_file_uploads'] = apply_filters( 'wpmdbmf_max_file_uploads', $max_file_uploads );

		return $data;
	}

	/**
	 * Add media related javascript variables to the page
	 *
	 * @param array $data
	 *
	 * @return array
	 */
	function js_variables( $data ) {
		$data['media_files_version'] = $this->plugin_version;

		return $data;
	}

	/**
	 * Adds extra information to the core plugin's diagnostic info
	 */
	function diagnostic_info( $diagnostic_info ) {
		// store the count of local attachments in a transient
		// so not to impact performance with sites with large media libraries
		if ( false === ( $attachment_count = get_transient( 'wpmdb_local_attachment_count' ) ) ) {
			$media_files_local = Container::getInstance()->get( 'media_files_addon_base' );
			$attachment_count  = $media_files_local->get_local_attachments_count();
			set_transient( 'wpmdb_local_attachment_count', $attachment_count, 2 * HOUR_IN_SECONDS );
		}
		$diagnostic_info['media-files'] = array(
			'Media Files'           => number_format( $attachment_count ),
			'Number of Image Sizes' => number_format( count( get_intermediate_image_sizes() ) ),
		);

		return $diagnostic_info;
	}

	/**
	 * Media addon nonces for core javascript variables
	 *
	 * @param array $nonces Array of nonces
	 *
	 * @return array Updated array of nonces
	 */
	function add_nonces( $nonces ) {
		$nonces['migrate_media']                        = Util::create_nonce( 'migrate-media' );
		$nonces['remove_files_recursive']               = Util::create_nonce( 'remove-files-recursive' );
		$nonces['prepare_determine_media']              = Util::create_nonce( 'prepare-determine-media' );
		$nonces['determine_media_to_migrate_recursive'] = Util::create_nonce( 'determine-media-to-migrate-recursive' );

		return $nonces;
	}

	/**
	 * Handler for "wpmdbmf_after_migration_options" action to append subsite select UI.
	 */
	public function after_migration_options_template() {
		if ( is_multisite() ) {
			$this->template->template( 'select-subsites', '', [], $this->template_path );
		}
	}
}
