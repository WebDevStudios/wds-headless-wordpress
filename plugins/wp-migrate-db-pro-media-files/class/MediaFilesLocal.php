<?php

namespace DeliciousBrains\WPMDBMF;

use DeliciousBrains\WPMDB\Common\Error\ErrorLog;
use DeliciousBrains\WPMDB\Common\Filesystem\Filesystem;
use DeliciousBrains\WPMDB\Common\FormData\FormData;
use DeliciousBrains\WPMDB\Common\Http\Helper;
use DeliciousBrains\WPMDB\Common\Http\Http;
use DeliciousBrains\WPMDB\Common\Http\RemotePost;
use DeliciousBrains\WPMDB\Common\MigrationState\MigrationStateManager;
use DeliciousBrains\WPMDB\Common\MigrationState\StateDataContainer;
use DeliciousBrains\WPMDB\Common\Settings\Settings;
use DeliciousBrains\WPMDB\Common\Util\Util;

class MediaFilesLocal extends MediaFilesBase {

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

	public function __construct(
		Filesystem $filesystem,
		MigrationStateManager $migration_state_manager,
		FormData $form_data,
		Http $http,
		Settings $settings,
		Util $util,
		Helper $http_helper,
		RemotePost $remote_post,
		ErrorLog $error_log,
		StateDataContainer $state_data_container
	) {
		parent::__construct(
			$filesystem,
			$migration_state_manager,
			$form_data
		);

		$this->http                 = $http;
		$this->settings             = $settings->get_settings();
		$this->util                 = $util;
		$this->http_helper          = $http_helper;
		$this->remote_post          = $remote_post;
		$this->error_log            = $error_log;
		$this->state_data_container = $state_data_container;
	}

	public function register() {
		// Local AJAX handlers
		add_action( 'wp_ajax_wpmdbmf_prepare_determine_media', array( $this, 'ajax_prepare_determine_media' ) );
		add_action( 'wp_ajax_wpmdbmf_determine_media_to_migrate_recursive', array( $this, 'ajax_determine_media_to_migrate_recursive', ) );
		add_action( 'wp_ajax_wpmdbmf_migrate_media', array( $this, 'ajax_migrate_media' ) );
		add_action( 'wp_ajax_wpmdbmf_remove_files_recursive', array( $this, 'ajax_remove_files_recursive' ) );
	}

	/**
	 * AJAX initial request before determining media to migrate
	 *
	 * @see WPMDBPro_Media_Files_Remote::respond_to_get_remote_media_info
	 *
	 * @param null $existing_state_data
	 *
	 * @return bool|mixed|string|null
	 */
	public function ajax_prepare_determine_media() {
		$this->http->check_ajax_referer( 'prepare-determine-media' );
		$this->util->set_time_limit();

		$key_rules = array(
			'action'             => 'key',
			'migration_state_id' => 'key',
			'nonce'              => 'key',
		);

		$state_data = $this->migration_state_manager->set_post_data( $key_rules );

		$data                    = array();
		$data['action']          = 'wpmdbmf_get_remote_media_info';
		$data['remote_state_id'] = $state_data['remote_state_id'];
		$data['intent']          = $state_data['intent'];
		$data['sig']             = $this->http_helper->create_signature( $data, $state_data['key'] );
		$ajax_url                = trailingslashit( $state_data['url'] ) . 'wp-admin/admin-ajax.php';
		$response                = $this->remote_post->post( $ajax_url, $data, __FUNCTION__ );
		$response                = $this->remote_post->verify_remote_post_response( $response );
		if ( isset( $response['wpmdb_error'] ) ) {
			return $response;
		}

		$return['attachment_batch_limit'] = $this->media_diff_batch_limit;
		$return['remote_uploads_url']     = $response['remote_uploads_url'];
		$return['remote_max_upload_size'] = $response['remote_max_upload_size'];

		// determine the size of the attachments in scope for migration
		if ( 'pull' == $state_data['intent'] ) {
			$return['attachment_count'] = $response['remote_total_attachments'];
			$return['blogs']            = $response['blogs'];
		} else {
			$return['attachment_count'] = $this->get_local_attachments_count();
			$return['blogs']            = serialize( $this->get_blogs() );
		}

		$result = $this->http->end_ajax( json_encode( $return ) );

		return $result;
	}

	/**
	 * Callback used by the recursive AJAX request to determine media to migrate
	 *
	 * @see WPMDBPro_Media_Files_Remote::respond_to_get_remote_attachment_batch
	 * @see WPMDBPro_Media_Files_Remote::respond_to_compare_remote_attachments
	 *
	 * @return bool|null
	 */
	public function ajax_determine_media_to_migrate_recursive() {
		$this->http->check_ajax_referer( 'determine-media-to-migrate-recursive' );
		$this->util->set_time_limit();

		$key_rules = array(
			'action'                 => 'key',
			'migration_state_id'     => 'key',
			'determine_progress'     => 'positive_int',
			'attachment_count'       => 'positive_int',
			'remote_uploads_url'     => 'url',
			'remove_local_media'     => 'positive_int',
			'copy_entire_media'      => 'positive_int',
			'blogs'                  => 'serialized',
			'attachment_batch_limit' => 'positive_int',
			'nonce'                  => 'key',
		);

		$state_data = $this->migration_state_manager->set_post_data( $key_rules );

		if ( ! in_array( $state_data['intent'], array( 'pull', 'push' ) ) ) {
			$error_msg = __( 'Incorrect migration type supplied', 'wp-migrate-db-pro-media-files' ) . ' (#120mf)';
			$return    = array( 'wpmdb_error' => 1, 'body' => $error_msg );
			$this->error_log->log_error( $error_msg );
			$result = $this->http->end_ajax( json_encode( $return ) );

			return $result;
		}

		// get batch of attachments and check if they need migrating
		if ( 'pull' == $state_data['intent'] ) {
			// get the remote batch
			$data                           = array();
			$data['action']                 = 'wpmdbmf_get_remote_attachment_batch';
			$data['remote_state_id']        = $state_data['remote_state_id'];
			$data['intent']                 = $state_data['intent'];
			$data['blogs']                  = stripslashes( $state_data['blogs'] );
			$data['attachment_batch_limit'] = $state_data['attachment_batch_limit'];
			$data['sig']                    = $this->http_helper->create_signature( $data, $state_data['key'] );
			$ajax_url                       = trailingslashit( $state_data['url'] ) . 'wp-admin/admin-ajax.php';
			$response                       = $this->remote_post->post( $ajax_url, $data, __FUNCTION__ );
			$response                       = $this->remote_post->verify_remote_post_response( $response );
			if ( isset( $response['wpmdb_error'] ) ) {
				return $response;
			}

			$response = apply_filters( 'wpmdbmf_get_remote_attachment_batch_response', $response, 'pull', $this );

			if ( '1' == $state_data['copy_entire_media'] ) {
				// skip comparison
				$return = $this->queue_all_attachments( $response['blogs'], $response['remote_attachments'], $state_data['determine_progress'] );
			} else {
				// compare batch against local attachments
				$return = $this->compare_remote_attachments( $response['blogs'], $response['remote_attachments'], $state_data['determine_progress'], 'pull' );
			}
		} else {
			// get the local batch
			$batch = $this->get_local_attachments_batch( $state_data['blogs'], $state_data['attachment_batch_limit'] );

			if ( '1' == $state_data['copy_entire_media'] ) {
				// skip comparison
				$return = $this->queue_all_attachments( $batch['blogs'], $batch['attachments'], $state_data['determine_progress'] );
			} else {
				// send batch to remote to compare against remote attachments
				$data                       = array();
				$data['action']             = 'wpmdbmf_compare_remote_attachments';
				$data['remote_state_id']    = $state_data['remote_state_id'];
				$data['intent']             = $state_data['intent'];
				$data['blogs']              = serialize( $batch['blogs'] );
				$data['determine_progress'] = $state_data['determine_progress'];

				$data['remote_attachments'] = base64_encode( gzencode( serialize( $batch['attachments'] ) ) );
				$data['sig']                = $this->http_helper->create_signature( $data, $state_data['key'] );
				$data['remote_attachments'] = addslashes( $data['remote_attachments'] ); // will be unslashed before sig is checked
				$ajax_url                   = trailingslashit( $state_data['url'] ) . 'wp-admin/admin-ajax.php';
				$response                   = $this->remote_post->post( $ajax_url, $data, __FUNCTION__ );
				$return                     = $this->remote_post->verify_remote_post_response( $response );
				if ( isset( $return['wpmdb_error'] ) ) {
					return $return;
				}
			}
		}

		// persist settings across requests
		$return['copy_entire_media']  = $state_data['copy_entire_media'];
		$return['remove_local_media'] = $state_data['remove_local_media'];
		$return['remote_uploads_url'] = $state_data['remote_uploads_url'];
		$return['attachment_count']   = $state_data['attachment_count'];
		$return['determine_progress'] = isset( $return['determine_progress'] ) ? $return['determine_progress'] : 0;
		$return['blogs']              = serialize( $return['blogs'] );
		$return['total_size']         = array_sum( $return['files_to_migrate'] );
		$return['files_to_migrate']   = isset( $return['files_to_migrate'] ) ? $return['files_to_migrate'] : array();

		$result = $this->http->end_ajax( json_encode( $return ) );

		return $result;
	}

	/**
	 * AJAX wrapper for the push/pull migration of media files,
	 *
	 * @return bool|null
	 */
	public function ajax_migrate_media() {
		$this->http->check_ajax_referer( 'migrate-media' );
		$this->util->set_time_limit();

		$key_rules = array(
			'action'             => 'key',
			'migration_state_id' => 'key',
			'file_chunk'         => 'array',
			'nonce'              => 'key',
		);

		$state_data = $this->migration_state_manager->set_post_data( $key_rules );

		if ( 'pull' == $state_data['intent'] ) {
			$result = $this->process_pull_request();
		} else {
			$result = $this->process_push_request();
		}

		return $result;
	}

	/**
	 * Download files from the remote site
	 *
	 * @return bool|null
	 */
	function process_pull_request() {
		$state_data = $this->migration_state_manager->set_post_data();

		$files_to_download  = $state_data['file_chunk'];
		$remote_uploads_url = trailingslashit( $state_data['remote_uploads_url'] );
		$parsed             = Util::parse_url( $state_data['url'] );

		if ( ! empty( $parsed['user'] ) ) {
			$credentials        = sprintf( '%s:%s@', $parsed['user'], $parsed['pass'] );
			$remote_uploads_url = str_replace( '://', '://' . $credentials, $remote_uploads_url );
		}

		$upload_dir = $this->uploads_dir();

		$errors    = array();
		$transfers = array();
		foreach ( $files_to_download as $file_to_download ) {
			$current_transfer = array( 'file' => $file_to_download, 'error' => false );
			$remote_url       = $remote_uploads_url . apply_filters( 'wpmdbmf_file_to_download', $file_to_download, 'pull', $this );
			$temp_file_path   = $this->download_url( $remote_url );

			if ( is_wp_error( $temp_file_path ) ) {
				$download_error            = $temp_file_path->get_error_message();
				$current_transfer['error'] = $download_error;
				$errors[]                  = sprintf( __( 'Could not download file: %1$s - %2$s', 'wp-migrate-db-pro-media-files' ), $remote_url, $download_error );
				$transfers[]               = $current_transfer;
				continue;
			}

			$date     = str_replace( basename( $file_to_download ), '', $file_to_download );
			$new_path = $upload_dir . $date . basename( $file_to_download );
			$folder   = dirname( $new_path );

			// WPMDB_Filesystem::mkdir will return true straight away if the dir exists
			if ( false === $this->filesystem->mkdir( $folder ) ) {
				$error_string              = sprintf( __( 'Error attempting to create required directory: %s', 'wp-migrate-db-pro-media-files' ), $folder ) . ' (#104mf)';
				$errors[]                  = $error_string;
				$current_transfer['error'] = $error_string;

			} elseif ( false === $this->filesystem->move( $temp_file_path, $new_path ) ) {
				$error_string              = sprintf( __( 'Error attempting to move downloaded file. Temp path: %1$s - New Path: %2$s', 'wp-migrate-db-pro-media-files' ), $temp_file_path, $new_path ) . ' (#105mf)';
				$errors[]                  = $error_string;
				$current_transfer['error'] = $error_string;
			}

			$transfers[] = $current_transfer;

			// set default permissions on moved file
			$this->filesystem->chmod( $new_path );
		}

		$return = array( 'success' => 1, 'transfers' => $transfers );

		if ( ! empty( $errors ) ) {
			$return['wpmdb_non_fatal_error'] = 1;

			$return['cli_body'] = $errors;
			$return['body']     = implode( '<br />', $errors ) . '<br />';
			$error_msg          = __( 'Failed attempting to process pull request', 'wp-migrate-db-pro-media-files' ) . ' (#112mf)';
			$this->error_log->log_error( $error_msg, $errors );
		}

		$result = $this->http->end_ajax( json_encode( $return ) );

		return $result;
	}

	/**
	 * Upload files to the remote site
	 *
	 * @see WPMDBPro_Media_Files_Remote::respond_to_push_request
	 *
	 * @return bool|null
	 */
	function process_push_request() {
		$state_data       = $this->migration_state_manager->set_post_data();
		$files_to_migrate = $state_data['file_chunk'];

		$upload_dir = $this->uploads_dir();

		$body = '';

		$file_contents = [];
		foreach ( $files_to_migrate as $file_to_migrate ) {
			$file_contents[] = base64_encode( gzencode( $this->http->file_to_serialized( $upload_dir . $file_to_migrate ) ) );
		}

		$post_args = array(
			'action'          => 'wpmdbmf_push_request',
			'remote_state_id' => $state_data['remote_state_id'],
			'files'           => base64_encode( gzencode( serialize( $files_to_migrate ) ) )
		);

		$post_args['sig']           = $this->http_helper->create_signature( $post_args, $state_data['key'] );
		$post_args['file_contents'] = base64_encode( serialize( $file_contents ) );

		$body .= $this->http->array_to_multipart( $post_args );

		$args['body'] = $body;
		$ajax_url     = trailingslashit( $state_data['url'] ) . 'wp-admin/admin-ajax.php';
		$response     = $this->remote_post->post( $ajax_url, '', __FUNCTION__, $args );
		$response     = $this->remote_post->verify_remote_post_response( $response );
		if ( isset( $response['wpmdb_error'] ) ) {
			return $response;
		}

		$result = $this->http->end_ajax( json_encode( $response ) );

		return $result;
	}

	/**
	 * Queue up all attachments in batch to be migrated
	 *
	 * @param mixed $blogs           Blogs
	 * @param mixed $all_attachments Batch of attachments
	 * @param int   $progress        Progress count
	 *
	 * @return array Data to return to AJAX response
	 */
	function queue_all_attachments( $blogs, $all_attachments, $progress ) {
		if ( ! is_array( $blogs ) ) {
			$blogs = unserialize( stripslashes( $blogs ) );
		}
		if ( ! is_array( $all_attachments ) ) {
			$all_attachments = unserialize( stripslashes( $all_attachments ) );
		}

		$files_to_migrate = array();
		$finish           = time() + $this->media_diff_batch_time;

		foreach ( $all_attachments as $blog_id => $attachments ) {
			foreach ( $attachments as $remote_attachment ) {

				if ( time() >= $finish ) {
					break;
				}

				$this->maybe_queue_attachment( $files_to_migrate, $remote_attachment );

				$blogs[ $blog_id ]['last_post'] = $remote_attachment['ID'];
				$progress ++;
			}
		}

		$return = array(
			'files_to_migrate'   => $files_to_migrate,
			'blogs'              => $blogs,
			'determine_progress' => $progress,
		);

		return $return;
	}

	/**
	 *  AJAX recursive request to remove all media files if skipping comparison in batches
	 *
	 * @return bool|null
	 *
	 * @param null $existing_state_data
	 */
	public function ajax_remove_files_recursive() {
		$this->http->check_ajax_referer( 'remove-files-recursive' );
		$this->util->set_time_limit();

		$key_rules = array(
			'action'             => 'key',
			'migration_state_id' => 'key',
			'compare'            => 'positive_int',
			'offset'             => 'string',
			'nonce'              => 'key',
		);

		$state_data = $this->migration_state_manager->set_post_data( $key_rules );

		$state_data['offset'] = (array) json_decode( $state_data['offset'] );

		if ( 'pull' === $state_data['intent'] ) {
			// send batch of files to be compared on the remote
			// receive batch of files to be deleted
			$return = $this->remove_local_files_recursive( $state_data['url'], $state_data['key'], $state_data['compare'], $state_data['offset'] );
		} else {
			// request a batch from the remote
			// compare received batch of files with local filesystem
			// send files to be deleted to the remote for deletion
			$return = $this->remove_remote_files_recursive( $state_data['url'], $state_data['key'], $state_data['compare'], $state_data['offset'] );
		}

		// persist the comparison flag across recursive requests
		$return['compare'] = $state_data['compare'];

		$result = $this->http->end_ajax( json_encode( $return ) );

		return $result;
	}

	/**
	 * Remove local media files in batches that can be called recursively
	 *
	 * Used in pull requests
	 *
	 * @see WPMDBPro_Media_Files_Remote::respond_to_compare_local_media_files
	 *
	 * @param string $remote_url          The remote site URL
	 * @param string $remote_key          The remote site key
	 * @param int    $compare_with_remote 1 = Will compare files existence on remote, 0 = no comparison
	 * @param array  $offset              Offset (blog id, post id) of last file in batch
	 * @param null   $existing_state_data
	 *
	 * @return array
	 */
	function remove_local_files_recursive( $remote_url, $remote_key, $compare_with_remote, $offset = null ) {
		$state_data = $this->migration_state_manager->set_post_data();

		if ( 1 === ( int ) $compare_with_remote ) {
			$local_media_files = $this->get_local_media_attachment_files_batch( $offset );

			if ( empty( $local_media_files['files'] ) ) {
				return array( 'offset' => '', 'remove_files' => 0 );
			}

			// send batch of files to be compared on the remote
			$data                    = array();
			$data['action']          = 'wpmdbmf_compare_local_media_files';
			$data['remote_state_id'] = $state_data['remote_state_id'];
			$data['files']           = serialize( $local_media_files['files'] );
			$data['sig']             = $this->http_helper->create_signature( $data, $remote_key );
			$data['files']           = addslashes( $data['files'] ); // will be unslashed before sig is checked
			$ajax_url                = trailingslashit( $remote_url ) . 'wp-admin/admin-ajax.php';
			$response                = $this->remote_post->post( $ajax_url, $data, __FUNCTION__ );
			$response                = $this->remote_post->verify_remote_post_response( $response );
			if ( isset( $response['wpmdb_error'] ) ) {
				return $response;
			}

			// files that don't exist returned as new batch to delete
			$files_to_remove = isset( $response['files_to_remove'] ) ? $response['files_to_remove'] : array();

			$return_offset = array( $local_media_files['last_blog_id'], $local_media_files['last_attachment_id'] );
		} else {
			$local_media_files = $this->get_local_media_files_batch( array_pop( $offset ) );

			if ( ! $local_media_files ) {
				return array( 'offset' => '', 'remove_files' => 0 );
			}

			$files_to_remove = $local_media_files;

			$return_offset = end( $local_media_files );
		}

		$errors = $this->remove_local_media_files( $files_to_remove );

		$return = array(
			'offset'       => $return_offset,
			'remove_files' => 1,
		);

		if ( ! empty( $errors ) ) {
			$return['wpmdb_non_fatal_error'] = 1;

			$return['cli_body'] = $errors;
			$return['body']     = implode( '<br />', $errors ) . '<br />';
			$error_msg          = __( 'There were errors when removing local media files', 'wp-migrate-db-pro-media-files' ) . ' (#123mf)';
			$this->error_log->log_error( $error_msg, $errors );
		}

		return $return;
	}

	/**
	 * Remove remote media files in batches that can be called recursively
	 *
	 * Used in push requests
	 *
	 * @see WPMDBPro_Media_Files_Remote::respond_to_get_local_media_files_batch
	 * @see WPMDBPro_Media_Files_Remote::respond_to_remove_local_media_files
	 *
	 * @param string $remote_url          The remote site URL
	 * @param string $remote_key          The remote site key
	 * @param int    $compare_with_remote 1 = Will compare files existence on remote, 0 = no comparison
	 * @param array  $offset              Offset (blog_id, post_id) of last file in previous batch to start this batch from
	 * @param null   $existing_state_data
	 *
	 * @return array
	 */

	function remove_remote_files_recursive( $remote_url, $remote_key, $compare_with_remote, $offset = null ) {
		$state_data = $this->migration_state_manager->set_post_data();

		// request a batch from the remote
		$data                    = array();
		$data['action']          = 'wpmdbmf_get_local_media_files_batch';
		$data['remote_state_id'] = $state_data['remote_state_id'];
		$data['compare']         = $compare_with_remote;
		$data['offset']          = json_encode( $offset );
		$data['sig']             = $this->http_helper->create_signature( $data, $remote_key );
		$ajax_url                = trailingslashit( $remote_url ) . 'wp-admin/admin-ajax.php';
		$response                = $this->remote_post->post( $ajax_url, $data, __FUNCTION__ );
		$response                = $this->remote_post->verify_remote_post_response( $response );

		if ( isset( $response['wpmdb_error'] ) ) {
			return $response;
		}

		if ( 1 === ( int ) $compare_with_remote ) {
			$remote_media_files = $response['local_media_attachment_files'];

			if ( empty( $remote_media_files['files'] ) ) {
				return array( 'offset' => '', 'remove_files' => 0 );
			}

			// compare received batch of files with local filesystem
			$files_to_remove = $this->get_files_not_on_local( $remote_media_files['files'], 'push' );
			$return_offset   = array( $remote_media_files['last_blog_id'], $remote_media_files['last_attachment_id'] );
		} else {
			$remote_media_files = $response['local_media_files'];

			if ( ! $remote_media_files ) {
				return array( 'offset' => '', 'remove_files' => 0 );
			}

			$files_to_remove = $remote_media_files;
			$return_offset   = end( $remote_media_files );
		}

		// send files not found on local to the remote for deletion
		$data                    = array();
		$data['action']          = 'wpmdbmf_remove_local_media_files';
		$data['remote_state_id'] = $state_data['remote_state_id'];
		$data['files_to_remove'] = serialize( $files_to_remove );
		$data['sig']             = $this->http_helper->create_signature( $data, $remote_key );
		$data['files_to_remove'] = addslashes( $data['files_to_remove'] ); // will be unslashed before sig is checked
		$ajax_url                = trailingslashit( $remote_url ) . 'wp-admin/admin-ajax.php';
		$response                = $this->remote_post->post( $ajax_url, $data, __FUNCTION__ );
		$response                = $this->remote_post->verify_remote_post_response( $response );
		if ( isset( $response['wpmdb_error'] ) ) {
			return $response;
		}

		$response['offset']       = $return_offset;
		$response['remove_files'] = 1;

		return $response;
	}

	/**
	 * Download a remote media file
	 *
	 * @param string $url     File to download
	 * @param int    $timeout Timeout limit
	 *
	 * @return array|string|WP_Error
	 */
	function download_url( $url, $timeout = 300 ) {
		// WARNING: The file is not automatically deleted, The script must unlink() the file.
		if ( ! $url ) {
			return new \WP_Error( 'http_no_url', __( 'Invalid URL Provided.' ) );
		}

		$tmpfname = wp_tempnam( $url );
		if ( ! $tmpfname ) {
			return new \WP_Error( 'http_no_file', __( 'Could not create Temporary file.' ) );
		}

		$sslverify = ( 1 == $this->settings['verify_ssl'] ) ? true : false;
		$args      = array(
			'timeout'            => $timeout,
			'stream'             => true,
			'filename'           => $tmpfname,
			'reject_unsafe_urls' => false,
			'sslverify'          => $sslverify,
		);

		$response = wp_remote_get( $url, $args );

		if ( is_wp_error( $response ) ) {
			$this->filesystem->unlink( $tmpfname );

			return $response;
		}

		if ( 200 != wp_remote_retrieve_response_code( $response ) ) {
			$this->filesystem->unlink( $tmpfname );

			return new \WP_Error( 'http_404', trim( wp_remote_retrieve_response_message( $response ) ) );
		}

		return $tmpfname;
	}

}
