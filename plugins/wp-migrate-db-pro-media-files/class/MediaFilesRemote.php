<?php

namespace DeliciousBrains\WPMDBMF;

use DeliciousBrains\WPMDB\Common\Error\ErrorLog;
use DeliciousBrains\WPMDB\Common\Filesystem\Filesystem;
use DeliciousBrains\WPMDB\Common\FormData\FormData;
use DeliciousBrains\WPMDB\Common\Http\Helper;
use DeliciousBrains\WPMDB\Common\Http\Http;
use DeliciousBrains\WPMDB\Common\Http\RemotePost;
use DeliciousBrains\WPMDB\Common\Http\Scramble;
use DeliciousBrains\WPMDB\Common\MigrationState\MigrationStateManager;
use DeliciousBrains\WPMDB\Common\MigrationState\StateDataContainer;
use DeliciousBrains\WPMDB\Common\Properties\Properties;
use DeliciousBrains\WPMDB\Common\Settings\Settings;
use DeliciousBrains\WPMDB\Common\Util\Util;

class MediaFilesRemote extends MediaFilesBase {

	/**
	 * @var Http
	 */
	private $http;
	/**
	 * @var Settings
	 */
	private $settings;
	/**
	 * @var Util
	 */
	private $util;
	/**
	 * @var Helper
	 */
	private $http_helper;
	/**
	 * @var RemotePost
	 */
	private $remote_post;
	/**
	 * @var ErrorLog
	 */
	private $error_log;
	/**
	 * @var StateDataContainer
	 */
	private $state_data_container;
	/**
	 * @var Properties
	 */
	private $properties;
	/**
	 * @var Scramble
	 */
	private $scramble;

	public function __construct(
		Filesystem $filesystem,
		MigrationStateManager $migration_state_manager,
		FormData $form_data,
		Http $http,
		Settings $settings,
		Util $util,
		Helper $http_helper,
		ErrorLog $error_log,
		Properties $properties,
		Scramble $scramble
	) {
		parent::__construct(
			$filesystem,
			$migration_state_manager,
			$form_data
		);

		$this->http        = $http;
		$this->settings    = $settings->get_settings();
		$this->util        = $util;
		$this->http_helper = $http_helper;
		$this->error_log   = $error_log;
		$this->properties  = $properties;
		$this->scramble    = $scramble;
	}

	public function register() {
		// Remote AJAX handlers
		add_action( 'wp_ajax_nopriv_wpmdbmf_get_remote_media_info', array( $this, 'respond_to_get_remote_media_info' ) );
		add_action( 'wp_ajax_nopriv_wpmdbmf_get_remote_attachment_batch', array( $this, 'respond_to_get_remote_attachment_batch' ) );
		add_action( 'wp_ajax_nopriv_wpmdbmf_compare_remote_attachments', array( $this, 'respond_to_compare_remote_attachments' ) );
		add_action( 'wp_ajax_nopriv_wpmdbmf_push_request', array( $this, 'respond_to_push_request' ) );
		add_action( 'wp_ajax_nopriv_wpmdbmf_get_local_media_files_batch', array( $this, 'respond_to_get_local_media_files_batch' ) );
		add_action( 'wp_ajax_nopriv_wpmdbmf_compare_local_media_files', array( $this, 'respond_to_compare_local_media_files' ) );
		add_action( 'wp_ajax_nopriv_wpmdbmf_remove_local_media_files', array( $this, 'respond_to_remove_local_media_files' ) );
	}

	/**
	 * Return information about remote site for use in media migration
	 *
	 * @return bool|null
	 */
	public function respond_to_get_remote_media_info() {
		add_filter( 'wpmdb_before_response', array( $this->scramble, 'scramble' ) );

		$key_rules = array(
			'action'          => 'key',
			'remote_state_id' => 'key',
			'intent'          => 'key',
			'sig'             => 'string',
		);

		$state_data = $this->migration_state_manager->set_post_data( $key_rules, 'remote_state_id' );

		$filtered_post = $this->http_helper->filter_post_elements( $state_data, array(
			'action',
			'remote_state_id',
			'intent',
		) );

		if ( ! $this->http_helper->verify_signature( $filtered_post, $this->settings['key'] ) ) {
			$return = array(
				'wpmdb_error' => 1,
				'body'        => $this->properties->invalid_content_verification_error . ' (#100mf)',
			);
			$this->error_log->log_error( $return['body'], $filtered_post );
			$result = $this->http->end_ajax( serialize( $return ) );

			return $result;
		}

		if ( defined( 'UPLOADBLOGSDIR' ) && get_site_option( 'ms_files_rewriting' ) ) {
			$upload_url = home_url( UPLOADBLOGSDIR );
		} else {
			$upload_dir = wp_upload_dir();
			$upload_url = $upload_dir['baseurl'];

			if ( is_multisite() ) {
				// Remove multisite postfix
				$upload_url = preg_replace( '/\/sites\/(\d)+$/', '', $upload_url );
			}
		}

		if ( ! filter_var( $upload_url, FILTER_VALIDATE_URL ) ) {
			$upload_url = home_url() . $upload_url;
		}

		$return['remote_total_attachments'] = $this->get_local_attachments_count();
		$return['remote_uploads_url']       = apply_filters( 'wpmdbmf_remote_wp_content_url', $upload_url );
		$return['blogs']                    = serialize( $this->get_blogs() );
		$return['remote_max_upload_size']   = $this->util->get_max_upload_size();

		$result = $this->http->end_ajax( serialize( $return ) );

		return $result;
	}

	/**
	 * Return a batch of attachments from the remote site
	 *
	 * @return bool|null
	 */
	public function respond_to_get_remote_attachment_batch() {
		add_filter( 'wpmdb_before_response', array( $this->scramble, 'scramble' ) );

		$key_rules = array(
			'action'                 => 'key',
			'remote_state_id'        => 'key',
			'intent'                 => 'key',
			'blogs'                  => 'serialized',
			'attachment_batch_limit' => 'positive_int',
			'sig'                    => 'string',
		);

		$state_data = $this->migration_state_manager->set_post_data( $key_rules, 'remote_state_id' );

		$filtered_post = $this->http_helper->filter_post_elements( $state_data, array(
			'action',
			'remote_state_id',
			'intent',
			'blogs',
			'attachment_batch_limit',
		) );

		$filtered_post['blogs'] = stripslashes( $filtered_post['blogs'] );

		if ( ! $this->http_helper->verify_signature( $filtered_post, $this->settings['key'] ) ) {
			$return = array(
				'wpmdb_error' => 1,
				'body'        => $this->properties->invalid_content_verification_error . ' (#116mf)',
			);
			$this->error_log->log_error( $return['body'], $filtered_post );
			$result = $this->http->end_ajax( serialize( $return ) );

			return $result;
		}
		$batch                        = $this->get_local_attachments_batch( $filtered_post['blogs'], $filtered_post['attachment_batch_limit'] );
		$return['remote_attachments'] = addslashes( serialize( $batch['attachments'] ) );
		$return['blogs']              = addslashes( serialize( $batch['blogs'] ) );

		$result = $this->http->end_ajax( serialize( $return ) );

		return $result;
	}

	/**
	 * Compare posted local files with those on the remote server
	 *
	 * @return bool|null
	 */
	public function respond_to_compare_remote_attachments() {
		add_filter( 'wpmdb_before_response', array( $this->scramble, 'scramble' ) );

		$key_rules = array(
			'action'             => 'key',
			'remote_state_id'    => 'key',
			'intent'             => 'key',
			'blogs'              => 'serialized',
			'determine_progress' => 'positive_int',
			'remote_attachments' => 'string',
			'sig'                => 'string',
		);

		$state_data = $this->migration_state_manager->set_post_data( $key_rules, 'remote_state_id' );

		$filtered_post = $this->http_helper->filter_post_elements( $state_data, array(
			'action',
			'remote_state_id',
			'intent',
			'blogs',
			'determine_progress',
			'remote_attachments',
		) );

		$filtered_post['blogs']              = stripslashes( $filtered_post['blogs'] );
		$filtered_post['remote_attachments'] = stripslashes( $filtered_post['remote_attachments'] );

		if ( ! $this->http_helper->verify_signature( $filtered_post, $this->settings['key'] ) ) {
			$return = array(
				'wpmdb_error' => 1,
				'body'        => $this->properties->invalid_content_verification_error . ' (#118mf)',
			);
			$this->error_log->log_error( $return['body'], $filtered_post );
			$result = $this->http->end_ajax( serialize( $return ) );

			return $result;
		}

		// compare_remote_attachments will unslash these values again
		$filtered_post['blogs']              = addslashes( $filtered_post['blogs'] );
		$filtered_post['remote_attachments'] = addslashes( $filtered_post['remote_attachments'] );

		$return = $this->compare_remote_attachments( $filtered_post['blogs'], $filtered_post['remote_attachments'], $filtered_post['determine_progress'], 'push' );
		$result = $this->http->end_ajax( serialize( $return ) );

		return $result;
	}

	/**
	 * Move uploaded local site files from tmp to uploads directory
	 *
	 * @return bool|null
	 */
	public function respond_to_push_request() {
		add_filter( 'wpmdb_before_response', array( $this->scramble, 'scramble' ) );

		$key_rules = array(
			'action'          => 'key',
			'remote_state_id' => 'key',
			'files'           => 'string',
			'sig'             => 'string',
		);

		$state_data = $this->migration_state_manager->set_post_data( $key_rules, 'remote_state_id' );

		$filtered_post = $this->http_helper->filter_post_elements( $state_data, array(
			'action',
			'remote_state_id',
			'files',
		) );

		$filtered_post['files'] = stripslashes( $filtered_post['files'] );

		if ( ! $this->http_helper->verify_signature( $filtered_post, $this->settings['key'] ) ) {
			$return = array(
				'wpmdb_error' => 1,
				'body'        => $this->properties->invalid_content_verification_error . ' (#111mf)',
			);
			$this->error_log->log_error( $return['body'], $filtered_post );
			$result = $this->http->end_ajax( serialize( $return ) );

			return $result;
		}

		$file_contents = filter_input( INPUT_POST, 'file_contents', FILTER_SANITIZE_STRING );

		if ( empty( $file_contents ) ) {
			$return = array(
				'wpmdb_error' => 1,
				'body'        => __( 'No media files transferred, the upload appears to have failed', 'wp-migrate-db-pro-media-files' ) . ' (#106mf)',
			);
			$this->error_log->log_error( $return['body'] );
			$result = $this->http->end_ajax( serialize( $return ) );

			return $result;
		}

		$file_contents = unserialize( base64_decode( $file_contents ) );
		$upload_dir    = $this->uploads_dir();

		$file_paths = unserialize( gzdecode( base64_decode( $filtered_post['files'] ) ) );

		list( $errors, $return ) = $this->unpack_files( $file_contents, $upload_dir, $file_paths );

		if ( ! empty( $errors ) ) {
			$return['wpmdb_non_fatal_error'] = 1;

			$return['cli_body'] = $errors;
			$return['body']     = implode( '<br />', $errors ) . '<br />';
			$error_msg          = __( 'Failed attempting to respond to push request', 'wp-migrate-db-pro-media-files' ) . ' (#113mf)';
			$this->error_log->log_error( $error_msg, $errors );
		}

		$result = $this->http->end_ajax( serialize( $return ) );

		return $result;
	}

	/**
	 * AJAX callback for returning a batch of local media files
	 *
	 * @return bool|null
	 */
	public function respond_to_get_local_media_files_batch() {
		add_filter( 'wpmdb_before_response', array( $this->scramble, 'scramble' ) );

		$key_rules = array(
			'action'          => 'key',
			'remote_state_id' => 'key',
			'compare'         => 'positive_int',
			'offset'          => 'string',
			'sig'             => 'string',
		);

		$state_data = $this->migration_state_manager->set_post_data( $key_rules, 'remote_state_id' );

		$filtered_post = $this->http_helper->filter_post_elements( $state_data, array(
			'action',
			'remote_state_id',
			'compare',
			'offset',
		) );

		if ( ! $this->http_helper->verify_signature( $filtered_post, $this->settings['key'] ) ) {
			$return = array(
				'wpmdb_error' => 1,
				'body'        => $this->properties->invalid_content_verification_error . ' (#109mf)',
			);
			$this->error_log->log_error( $return['body'], $filtered_post );
			$result = $this->http->end_ajax( serialize( $return ) );

			return $result;
		}

		$offset = isset( $filtered_post['offset'] ) ? json_decode( $filtered_post['offset'] ) : '0';

		$local_media_files            = array();
		$local_media_attachment_files = array();
		if ( 1 === (int) $filtered_post['compare'] ) {
			$local_media_attachment_files = $this->get_local_media_attachment_files_batch( $offset );
		} else {
			$local_media_files = $this->get_local_media_files_batch( array_pop( $offset ) );
		}

		$return = array(
			'success'                      => 1,
			'local_media_files'            => $local_media_files,
			'local_media_attachment_files' => $local_media_attachment_files,
		);

		$result = $this->http->end_ajax( serialize( $return ) );

		return $result;
	}

	/**
	 * AJAX callback to compare a posted batch of files with those on local site
	 *
	 * @return bool|null
	 */
	public function respond_to_compare_local_media_files() {
		add_filter( 'wpmdb_before_response', array( $this->scramble, 'scramble' ) );

		$key_rules = array(
			'action'          => 'key',
			'remote_state_id' => 'key',
			'files'           => 'serialized',
			'sig'             => 'string',
		);

		$state_data = $this->migration_state_manager->set_post_data( $key_rules, 'remote_state_id' );

		$filtered_post = $this->http_helper->filter_post_elements( $state_data, array(
			'action',
			'remote_state_id',
			'files',
		) );

		$filtered_post['files'] = stripslashes( $filtered_post['files'] );

		if ( ! $this->http_helper->verify_signature( $filtered_post, $this->settings['key'] ) ) {
			$return = array(
				'wpmdb_error' => 1,
				'body'        => $this->properties->invalid_content_verification_error . ' (#117mf)',
			);
			$this->error_log->log_error( $return['body'], $filtered_post );
			$result = $this->http->end_ajax( serialize( $return ) );

			return $result;
		}

		// compare files to those on the local filesystem
		$files_to_remove = $this->get_files_not_on_local( $filtered_post['files'], 'pull' );

		$return = array(
			'success'         => 1,
			'files_to_remove' => $files_to_remove,
		);

		$result = $this->http->end_ajax( serialize( $return ) );

		return $result;
	}

	/**
	 * AJAX callback to remove files for the local filesystem
	 *
	 * @return bool|null
	 */
	public function respond_to_remove_local_media_files() {
		add_filter( 'wpmdb_before_response', array( $this->scramble, 'scramble' ) );

		$key_rules = array(
			'action'          => 'key',
			'remote_state_id' => 'key',
			'files_to_remove' => 'serialized',
			'sig'             => 'string',
		);

		$state_data = $this->migration_state_manager->set_post_data( $key_rules, 'remote_state_id' );

		$filtered_post = $this->http_helper->filter_post_elements( $state_data, array(
			'action',
			'remote_state_id',
			'files_to_remove',
		) );

		$filtered_post['files_to_remove'] = stripslashes( $filtered_post['files_to_remove'] );

		if ( ! $this->http_helper->verify_signature( $filtered_post, $this->settings['key'] ) ) {
			$return = array(
				'wpmdb_error' => 1,
				'body'        => $this->properties->invalid_content_verification_error . ' (#119mf)',
			);
			$this->error_log->log_error( $return['body'], $filtered_post );
			$result = $this->http->end_ajax( serialize( $return ) );

			return $result;
		}

		$errors = $this->remove_local_media_files( $filtered_post['files_to_remove'] );

		$return['success'] = 1;

		if ( ! empty( $errors ) ) {
			$return['wpmdb_non_fatal_error'] = 1;

			$return['cli_body'] = $errors;
			$return['body']     = implode( '<br />', $errors ) . '<br />';
			$error_msg          = __( 'There were errors when removing local media files from the remote site', 'wp-migrate-db-pro-media-files' ) . ' (#121mf)';
			$this->error_log->log_error( $error_msg, $errors );
		}

		$result = $this->http->end_ajax( serialize( $return ) );

		return $result;
	}

	/**
	 * @param        $file_contents
	 * @param string $upload_dir
	 * @param        $file_paths
	 *
	 * @return array
	 */
	protected function unpack_files( $file_contents, $upload_dir, $file_paths ) {
		$i         = 0;
		$errors    = array();
		$transfers = array();

		foreach ( $file_contents as $file ) {

			$file = unserialize( gzdecode( base64_decode( $file ) ) );

			$destination      = $upload_dir . apply_filters( 'wpmdbmf_destination_file_path', $file_paths[ $i ], 'push', $this );
			$folder           = \dirname( $destination );
			$current_transfer = array( 'file' => $file_paths[ $i ], 'error' => false );

			if ( false === $this->filesystem->file_exists( $folder ) && false === $this->filesystem->mkdir( $folder ) ) {
				$error_string              = sprintf( __( 'Error attempting to create required directory: %s', 'wp-migrate-db-pro-media-files' ), $folder ) . ' (#108mf)';
				$errors[]                  = $error_string;
				$current_transfer['error'] = $error_string;
				++ $i;
				$transfers[] = $current_transfer;
				continue;
			}

			if ( false === file_put_contents( $destination, $file['contents'] ) ) {
				$error_string              = sprintf( __( 'A problem occurred when attempting to move the temp file contents into place.', 'wp-migrate-db-pro-media-files' ), $destination ) . ' (#107mf)';
				$errors[]                  = $error_string;
				$current_transfer['error'] = $error_string;
			}

			$transfers[] = $current_transfer;
			++ $i;
		}

		$return = array( 'success' => 1, 'transfers' => $transfers );

		return array( $errors, $return );
	}

}
