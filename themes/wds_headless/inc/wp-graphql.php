<?php
/**
 * WP GraphQL settings.
 *
 * @see https://wordpress.org/plugins/wp-graphql/
 * @author WebDevStudios
 * @package wds-headless-theme
 * @since 1.0
 */

if ( class_exists( 'WPGraphQL' ) ) {

	/**
	 * Retrieve relational post data by IDs.
	 *
	 * @author WebDevStudios
	 * @since 1.0
	 * @param  array $post_ids Array of post IDs.
	 * @return array           Array of post data.
	 */
	function wds_get_relational_posts( array $post_ids ) {
		if ( ! count( $post_ids ) ) {
			return [];
		}

		return array_map( function( $post_id ) {

			// Return as-is if already array of post data.
			if ( is_array( $post_id ) ) {
				return $post_id;
			}

			$post = get_post( $post_id );

			// Return post ID as array if error encountered retrieving post object.
			if ( ! $post || ! $post instanceof WP_Post ) {
				return [ 'id' => $post_id ];
			}

			$post_type = get_post_type_object( $post->post_type );

			return [
				'id'         => $post_id,
				'type'       => $post_type->graphql_single_name,
				'pluralType' => $post_type->graphql_plural_name,
			];
		}, $post_ids );
	}

	/**
	 * Add query to GraphQL to retrieve homepage settings.
	 *
	 * @author WebDevStudios
	 * @since 1.0
	 */
	function wds_add_homepage_settings_query() {

		register_graphql_object_type( 'HomepageSettings', [
			'description' => esc_html__( 'Front and posts archive page data', 'wds' ),
			'fields'      => [
				'frontPage' => [ 'type' => 'Page' ],
				'postsPage' => [ 'type' => 'Page' ],
			],
		] );

		register_graphql_field( 'RootQuery', 'homepageSettings', [
			'type'        => 'HomepageSettings',
			'description' => esc_html__( 'Returns front and posts archive page data', 'wds' ),
			'resolve'     => function( $source, array $args, \WPGraphQL\AppContext $context ) {
				global $wpdb;

				// Get homepage settings.
				$settings = $wpdb->get_row(
					"
					SELECT
						(select option_value from {$wpdb->prefix}options where option_name = 'page_for_posts') as 'page_for_posts',
						(select option_value from {$wpdb->prefix}options where option_name = 'page_on_front') as 'page_on_front'
					",
					ARRAY_A
				);

				// Format settings data.
				$settings_data = [];

				foreach ( $settings as $key => $value ) {
					// Get page data.
					$page_data = ! empty( $value ?? 0 ) ? $context->get_loader( 'post' )->load_deferred( intval( $value ) ) : null;

					switch ( $key ) {
						case 'page_for_posts':
							$settings_data['postsPage'] = $page_data;
							break;

						case 'page_on_front':
							$settings_data['frontPage'] = $page_data;
							break;
					}
				}

				return $settings_data;
			},
		] );
	}
	add_action( 'graphql_register_types', 'wds_add_homepage_settings_query' );

	/**
	 * Allow access to additional fields via non-authed GraphQL request.
	 *
	 * @author WebDevStudios
	 * @since 1.0
	 * @param  array  $fields     The fields to allow when the data is designated as restricted to the current user.
	 * @param  string $model_name Name of the model the filter is currently being executed in.
	 * @return array                   Allowed fields.
	 */
	function wds_graphql_allowed_fields( array $fields, string $model_name ) {
		if ( 'PostTypeObject' !== $model_name ) {
			return $fields;
		}

		// Add label fields.
		$fields[] = 'label';
		$fields[] = 'labels';

		return $fields;
	}
	add_filter( 'graphql_allowed_fields_on_restricted_type', 'wds_graphql_allowed_fields', 10, 6 );

	/**
	 * Include users without published posts in SQL query.
	 *
	 * @author WebDevStudios
	 * @since 1.0
	 * @param array                      $query_args          The query args to be used with the executable query to get data.
	 * @param AbstractConnectionResolver $connection_resolver Instance of the connection resolver.
	 * @return array
	 */
	function wds_public_unpublished_users( array $query_args, \WPGraphQL\Data\Connection\AbstractConnectionResolver $connection_resolver ) {// phpcs:ignore
		if ( $connection_resolver instanceof \WPGraphQL\Data\Connection\UserConnectionResolver ) {
			unset( $query_args['has_published_posts'] );
		}

		return $query_args;
	}
	add_filter( 'graphql_connection_query_args', 'wds_public_unpublished_users', 10, 2 );

	/**
	 * Make all Users public including in non-authenticated WPGraphQL requests.
	 *
	 * @author WebDevStudios
	 * @since 1.0
	 * @param string $visibility The current visibility of a user.
	 * @param string $model_name The model name of the user model.
	 * @return string
	 */
	function wds_public_users( string $visibility, string $model_name ) {
		if ( 'UserObject' === $model_name ) {
			$visibility = 'public';
		}

		return $visibility;
	}
	add_filter( 'graphql_object_visibility', 'wds_public_users', 10, 2 );

	/**
	 * Add archive SEO field to CPT archive queries in GraphQL.
	 *
	 * @author WebDevStudios
	 * @since 1.0
	 * @return void
	 */
	function wds_register_archive_seo() {
		register_graphql_object_type( 'ArchiveSeo', [
			'description' => esc_html__( 'Archive SEO data', 'wds' ),
			'fields'      => [
				'title'              => [ 'type' => 'String' ],
				'metaDesc'           => [ 'type' => 'String' ],
				'metaRobotsNoindex'  => [ 'type' => 'String' ],
				'metaRobotsNofollow' => [ 'type' => 'String' ],
				'canonical'          => [ 'type' => 'String' ],
			],
		] );

		// Get post types that support archives (will not include "post" PT).
		$post_types = get_post_types( [
			'has_archive' => true,
		], 'objects' );

		// Bail if we don't have an array of post types.
		if ( empty( $post_types ) || ! is_array( $post_types ) ) {
			return;
		}

		// Register GraphQL field on each post type's plural/archive connection.
		foreach ( $post_types as $post_type => $post_type_object ) {
			if ( ! $post_type_object->show_in_graphql || ! $post_type_object->graphql_single_name ) {
				break;
			}

			$pt_singular = ucfirst( $post_type_object->graphql_single_name );

			register_graphql_field(
				"RootQueryTo{$pt_singular}Connection",
				'archiveSeo',
				[
					'type'        => 'ArchiveSeo',
					'description' => sprintf(
						/* translators: the post type label. */
						__( 'The Yoast SEO data of the %s post type archive', 'wds' ),
						$post_type_object->label
					),
					'resolve'     => function () use ( $post_type, $post_type_object ) {
						// Surface info: https://developer.yoast.com/blog/yoast-seo-14-0-using-yoast-seo-surfaces/.
						/* We would ideally use this surface to determine meta title and desc, but the surface is not pulling the correct meta for those fields (as of Yoast 15.9.2, it seems to be pulling some default archive meta title and a blank desc). */
						$archive_seo = YoastSEO()->meta->for_post_type_archive( $post_type );

						// Retrieve Yoast SEO options for archive title and desc instead.
						$wpseo_options = WPSEO_Options::get_instance();
						$title         = $wpseo_options->get( "title-ptarchive-{$post_type}" );
						$description   = $wpseo_options->get( "metadesc-ptarchive-{$post_type}", $archive_seo->description );

						/* Manually replace title vars that won't get caught in next step (e.g., pt_single, pt_plural) -- these appear to be "advanced" vars that require a single post to be passed, rather than a post type object. */
						$title = str_ireplace( '%%pt_single%%', $post_type_object->labels->singular_name, $title );
						$title = str_ireplace( '%%pt_plural%%', $post_type_object->labels->name, $title );

						// Replace standard title vars with post type object.
						$title = wpseo_replace_vars( $title, $post_type_object );

						return [
							'title'              => wp_gql_seo_format_string( $title ?? $archive_seo->title ),
							'metaDesc'           => wp_gql_seo_format_string( $description ),
							'metaRobotsNoindex'  => $archive_seo->robots['index'],
							'metaRobotsNofollow' => $archive_seo->robots['follow'],
							'canonical'          => $archive_seo->canonical,
						];
					},
				]
			);
		}
	}
	add_action( 'graphql_register_types', 'wds_register_archive_seo' );

	/**
	 * Add file upload field to GF FieldValuesInput.
	 *
	 * @author WebDevStudios
	 * @since 1.0
	 * @param array $fields The array of fields for the FieldValuesInput object.
	 * @return array        The filtered array of fields for the FieldValuesInput object.
	 */
	function wds_add_file_upload_field( $fields ) {
		$fields['fileUploadValues'] = [
			'type'        => 'Upload',
			'description' => esc_html__( 'The form field values for FileUpload fields.', 'wds' ),
		];

		return $fields;
	}
	add_filter( 'graphql_fieldValuesInput_fields', 'wds_add_file_upload_field' );
}

/**
 * Handle custom file upload.
 *
 * This mimics WP Core upload functionality but allows for uploading file to a custom directory rather than the standard WP uploads dir.
 * @see https://developer.wordpress.org/reference/functions/_wp_handle_upload/
 *
 * @author WebDevStudios
 * @since 1.0
 * @param array $file   File data to upload.
 * @param array $target Target upload directory; WP uploads dir will be used if none provided.
 * @return array        Uploaded file data.
 */
function wds_handle_file_upload( array $file, array $target = null ) {
	// Default to uploads dir if alternative not provided.
	$target = $target ?? wp_upload_dir();

	// Check if filetype & ext are valid.
	$wp_filetype     = wp_check_filetype_and_ext( $file['tmp_name'], $file['name'] );
	$ext             = empty( $wp_filetype['ext'] ) ? '' : $wp_filetype['ext'];
	$type            = empty( $wp_filetype['type'] ) ? '' : $wp_filetype['type'];
	$proper_filename = empty( $wp_filetype['proper_filename'] ) ? '' : $wp_filetype['proper_filename'];

	// Check to see if wp_check_filetype_and_ext() determined the filename was incorrect.
	if ( $proper_filename ) {
		$file['name'] = $proper_filename;
	}

	// Return error if file type not allowed.
	if ( ( ! $type || ! $ext ) && ! current_user_can( 'unfiltered_upload' ) ) {
		return call_user_func_array( 'wp_handle_upload_error', array( &$file, esc_html__( 'Sorry, this file type is not permitted for security reasons.', 'wds' ) ) );
	}

	$type = ! $type ? $file['type'] : $type;


	$filename = wp_unique_filename( $target['path'], $file['name'] );

	// Move the file to the GF uploads dir.
	$new_file = $target['path'] . "/{$filename}";

	// Use copy and unlink because rename breaks streams.
	$move_new_file = @copy( $file['tmp_name'], $new_file );
	unlink( $file['tmp_name'] );

	if ( ! $move_new_file ) {
		$field['value'] = '';

		return $field;
	}


	// Set correct file permissions.
	$stat  = stat( dirname( $new_file ) );
	$perms = $stat['mode'] & 0000666;
	chmod( $new_file, $perms );

	// Compute the URL.
	$url = $target['url'] . "/{$filename}";

	$upload = [
		'file' => $new_file,
		'url'  => $url,
		'type' => $type,
	];

	return $upload;
}

/**
 * Determine appropriate GF form-specific uploads dir config and ensure folders are initiated as needed.
 *
 * @author WebDevStudios
 * @since 1.0
 * @param int $form_id GF form ID.
 * @return array       GF uploads dir config.
 */
function wds_gravity_forms_upload_dir( int $form_id ) {
	// Determine YYYY/MM values.
	$time = current_time( 'mysql' );
	$y    = substr( $time, 0, 4 );
	$m    = substr( $time, 5, 2 );

	$date_dir = DIRECTORY_SEPARATOR . $y . DIRECTORY_SEPARATOR . $m;

	// Determine upload directory.
	$target_path = GFFormsModel::get_upload_path( $form_id ) . $date_dir;
	$target_url = GFFormsModel::get_upload_url( $form_id ) . $date_dir;

	// Create upload directory if doesn't exist.
	if ( ! is_dir( $target_path ) ) {
		if ( ! wp_mkdir_p( $target_path ) ) {
			GFCommon::log_debug( "GFAsyncUpload::upload(): Couldn't create the upload folder: " . $target_path );
			GFAsyncUpload::die_error( 500, __( 'Failed to upload file.', 'wds' ) );
		}
	}

	// Add index.html files to upload directory subfolders.
	if ( ! file_exists( GFFormsModel::get_upload_root() . '/index.html' ) ) {
		GFForms::add_security_files();
	} else if ( ! file_exists( GFFormsModel::get_upload_path( $form_id ) . '/index.html' ) ) {
		GFCommon::recursive_add_index_file( GFFormsModel::get_upload_path( $form_id ) );
	} else if ( ! file_exists( GFFormsModel::get_upload_path( $form_id ) . "/$y/index.html" ) ) {
		GFCommon::recursive_add_index_file( GFFormsModel::get_upload_path( $form_id ) . "/$y" );
	} else {
		GFCommon::recursive_add_index_file( GFFormsModel::get_upload_path( $form_id ) . "/$y/$m" );
	}

	return [
		'path'    => $target_path,
		'url'     => $target_url,
		'subdir'  => $date_dir,
		'basedir' => untrailingslashit( GFFormsModel::get_upload_root() ),
		'baseurl' => untrailingslashit( GFFormsModel::get_upload_url_root() ),
	];
}
