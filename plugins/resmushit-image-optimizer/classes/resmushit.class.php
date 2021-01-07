<?php

 /**
   * ReSmushit
   * 
   * 
   * @package    Resmush.it
   * @subpackage Controller
   * @author     Charles Bourgeaux <contact@resmush.it>
   */
Class reSmushit {

	const MAX_FILESIZE = 5242880;
	const MAX_ATTACHMENTS_REQ = 1000;

	/**
	 *
	 * returns the list of supported extensions by the API
	 *
	 * @return array 	List of extensions
	 */
	public static function authorizedExtensions() {
		return array('jpg', 'jpeg', 'gif', 'png', 'bmp', 'tif', 'tiff');
	}


	/**
	 *
	 * Optimize a picture according to a filepath.
	 *
	 * @param  string $file_path the path to the file on the server
	 * @return bool 	TRUE if the resmush operation worked
	 */
	public static function getPictureQualitySetting() {
		if(get_option( 'resmushit_qlty' )) {
			return get_option( 'resmushit_qlty' );
		} else {
			if (!defined('RESMUSHIT_QLTY')) {
			  return RESMUSHIT_DEFAULT_QLTY;
			}
			return RESMUSHIT_QLTY;
		}
	}

	/**
	 *
	 * Optimize a picture according to a filepath.
	 *
	 * @param  string $file_path the path to the file on the server
	 * @return bool 	TRUE if the resmush operation worked
	 */
	public static function optimize($file_path = NULL, $is_original = TRUE) {
		global $wp_version;
		if(!file_exists($file_path) OR !is_file($file_path)) {
			rlog('Error! Picture ' . str_replace(ABSPATH, '/', $file_path) . ' cannot be optimized, file is not found on disk.', 'WARNING');
			return false;
		}
		if(filesize($file_path) > self::MAX_FILESIZE){
			rlog('Error! Picture ' . str_replace(ABSPATH, '/', $file_path) . ' cannot be optimized, file size is above 5MB ('. reSmushitUI::sizeFormat(filesize($file_path)) .')', 'WARNING');
			return false;
		}

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, RESMUSHIT_ENDPOINT);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, RESMUSHIT_TIMEOUT);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_USERAGENT, "Wordpress $wp_version/Resmush.it " . RESMUSHIT_VERSION . ' - ' . get_bloginfo('wpurl') );

		if (!class_exists('CURLFile')) {
			$arg = array('files' => '@' . $file_path);
		} else {
			$cfile = new CURLFile($file_path);
			$arg = array(
			  'files' => $cfile,
			);
		}
		if(get_option( 'resmushit_preserve_exif' ) && get_option( 'resmushit_preserve_exif' ) == 1) {
			$arg['exif'] = 'true';
		}
		
		$arg['qlty'] = self::getPictureQualitySetting();
		curl_setopt($ch, CURLOPT_POSTFIELDS, $arg);

		$data = curl_exec($ch);
		curl_close($ch);

		$json = json_decode($data);
		if($json){
			if (!isset($json->error)) {
				if (ini_get('allow_url_fopen')) {
					$data = file_get_contents($json->dest);
				} else {
					$ch = curl_init();
					curl_setopt($ch, CURLOPT_URL, $json->dest);
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
					$data = curl_exec($ch);
					curl_close($ch);
				}
				if ($data) {
					if($is_original && get_option('resmushit_remove_unsmushed') == 0){
						$originalFile = pathinfo($file_path);
						$newPath = $originalFile['dirname'] . '/' . $originalFile['filename'] . '-unsmushed.' . $originalFile['extension'];
			 			copy($file_path, $newPath);
			 		}
				  	file_put_contents($file_path, $data);
					rlog("Optimized file " . str_replace(ABSPATH, '/', $file_path) . " from " . reSmushitUI::sizeFormat($json->src_size) . " to " . reSmushitUI::sizeFormat($json->dest_size));
				  	return $json;
				}
			} else {
				rlog("Webservice returned the following error while optimizing $file_path : Code #" . $json->error . " - " . $json->error_long, 'ERROR');
			}
		} else {
			rlog("Cannot establish connection with reSmush.it webservice while optimizing $file_path (timeout of " . RESMUSHIT_TIMEOUT . "sec.)", 'ERROR');
		}
		return false;
	}




	/**
	 *
	 * Revert original file and regenerates attachment thumbnails
	 *
	 * @param  int 		$attachment_id 	ID of the attachment to revert
	 * @return none
	 */
	public static function revert($id) {
		global $wp_version;
		global $attachment_id;
		$attachment_id = $id;

		delete_post_meta($attachment_id, 'resmushed_quality');
		delete_post_meta($attachment_id, 'resmushed_cumulated_original_sizes');
		delete_post_meta($attachment_id, 'resmushed_cumulated_optimized_sizes');	

		$basepath = dirname(get_attached_file( $attachment_id )) . '/';
		$fileInfo = pathinfo(get_attached_file( $attachment_id ));

		$originalFile = $basepath . $fileInfo['filename'] . '-unsmushed.' . $fileInfo['extension'];
		rlog('Revert original image for : ' . str_replace(ABSPATH, '/', get_attached_file( $attachment_id )));
	
		if(file_exists($originalFile))
			copy($originalFile, get_attached_file( $attachment_id ));

		//Regenerate thumbnails
		wp_generate_attachment_metadata($attachment_id, get_attached_file( $attachment_id ));
		
		return self::wasSuccessfullyUpdated( $attachment_id );
	}



	/**
	 *
	 * Delete Original file (-unsmushed)
	 *
	 * @param  int 		$attachment_id 	ID of the attachment
	 * @return none
	 */
	public static function deleteOriginalFile($attachment_id) {
		$basepath = dirname(get_attached_file( $attachment_id )) . '/';
		$fileInfo = pathinfo(get_attached_file( $attachment_id ));

		$originalFile = $basepath . $fileInfo['filename'] . '-unsmushed.' . $fileInfo['extension'];
		rlog('Delete original image for : ' . get_attached_file( $attachment_id ));
		if(file_exists($originalFile))
			unlink($originalFile);
	}

	/**
      * 
      * Detect if optimization process was already launched one time
      *
      * @return boolean
      */
	public static function hasAlreadyRunOnce(){
		global $wpdb;
		$query = $wpdb->prepare( 
			"select
				count($wpdb->posts.ID) as count
				from $wpdb->posts
				inner join $wpdb->postmeta on $wpdb->posts.ID = $wpdb->postmeta.post_id and $wpdb->postmeta.meta_key = %s $extraSQL
				limit 1",
				array('resmushed_cumulated_original_sizes')
		);
		return (boolean)$wpdb->get_var($query);
	}
	/**
      * 
      * Return optimization statistics
      *
      * @param int 	$attachment_id (optional)
      * @return array of statistics
      */
	public static function getStatistics($attachment_id = null){
		global $wpdb;
		$output = array();
		$extraSQL = null;
		if($attachment_id)
			$extraSQL = "where $wpdb->postmeta.post_id = ". (int)($attachment_id);

		$query = $wpdb->prepare( 
			"select
				$wpdb->posts.ID as ID, $wpdb->postmeta.meta_value
				from $wpdb->posts
				inner join $wpdb->postmeta on $wpdb->posts.ID = $wpdb->postmeta.post_id and $wpdb->postmeta.meta_key = %s $extraSQL",
				array('resmushed_cumulated_original_sizes')
		);	
		$original_sizes = $wpdb->get_results($query);
		$total_original_size = 0;
		foreach($original_sizes as $s){
			$total_original_size += $s->meta_value;
		}

		$query = $wpdb->prepare( 
			"select
				$wpdb->posts.ID as ID, $wpdb->postmeta.meta_value
				from $wpdb->posts
				inner join $wpdb->postmeta on $wpdb->posts.ID = $wpdb->postmeta.post_id and $wpdb->postmeta.meta_key = %s $extraSQL",
				array('resmushed_cumulated_optimized_sizes')
		);	
		$optimized_sizes = $wpdb->get_results($query);
		$total_optimized_size = 0;
		foreach($optimized_sizes as $s){
			$total_optimized_size += $s->meta_value;
		}


		$output['total_original_size'] 			= $total_original_size;
		$output['total_optimized_size'] 		= $total_optimized_size;
		$output['total_saved_size'] 			= $total_original_size - $total_optimized_size;

		$output['total_original_size_nice'] 	= reSmushitUI::sizeFormat($total_original_size);
		$output['total_optimized_size_nice'] 	= reSmushitUI::sizeFormat($total_optimized_size);
		$output['total_saved_size_nice'] 		= reSmushitUI::sizeFormat($total_original_size - $total_optimized_size);
		if($total_original_size == 0)
			$output['percent_reduction'] 		= 0;
		else
			$output['percent_reduction'] 		= 100*round(($total_original_size - $total_optimized_size)/$total_original_size,4) . ' %';
		//number of thumbnails + original picture
		$output['files_optimized'] 				= sizeof($optimized_sizes);
		$output['files_optimized_with_thumbnails'] = sizeof($optimized_sizes) * (sizeof(get_intermediate_image_sizes()) + 1);

		if(!$attachment_id){
			$output['total_optimizations'] 		= get_option('resmushit_total_optimized');
			$output['total_pictures'] 			= self::getCountAllPictures();
			$output['total_pictures_with_thumbnails'] = self::getCountAllPictures() * (sizeof(get_intermediate_image_sizes()) + 1);
		}
		return $output;
	}



	/**
      * 
      * Get the count of all pictures
      *
      * @param none
      * @return json of unsmushed pictures attachments ID
      */
	public static function getCountAllPictures(){
		global $wpdb;

		$queryAllPictures = $wpdb->prepare( 
			"select
				Count($wpdb->posts.ID) as count
				from $wpdb->posts
				inner join $wpdb->postmeta on $wpdb->posts.ID = $wpdb->postmeta.post_id and $wpdb->postmeta.meta_key = %s
				where $wpdb->posts.post_type = %s
				and $wpdb->posts.post_mime_type like %s
				and ($wpdb->posts.post_mime_type = 'image/jpeg' OR $wpdb->posts.post_mime_type = 'image/gif' OR $wpdb->posts.post_mime_type = 'image/png')",
				array('_wp_attachment_metadata','attachment', 'image%')
			);
		$data = $wpdb->get_results($queryAllPictures);
		if(isset($data[0]))
			$data = $data[0];

		if(!isset($data->count))
			return 0;
		return $data->count;
	}



	/**
      * 
      * Get a list of non optimized pictures
      *
      * @param none
      * @return json of unsmushed pictures attachments ID
      */
	public static function getNonOptimizedPictures($id_only = FALSE){
		global $wpdb;
		$tmp = array();
		$unsmushed_images = array();
		$files_too_big = array();
		$already_optimized_images_array = array();
		$disabled_images_array = array();
		$files_not_found = array();
		$extra_select = "";
		if($id_only == FALSE) {
			$extra_select = ",POSTS.guid as guid, METAATTACH.meta_value as file_meta";
		}

		$queryUnoptimizedPicture = $wpdb->prepare(
			"SELECT ATTACHMENTS.* FROM (
				select 
					POSTS.ID as ID, METAQLTY.meta_value as qlty, METADISABLED.meta_value as disabled
					$extra_select
				from $wpdb->posts as POSTS
				inner join 
					$wpdb->postmeta as METAATTACH on POSTS.ID = METAATTACH.post_id 
					and METAATTACH.meta_key = %s 
				left join 
					$wpdb->postmeta as METAQLTY on POSTS.ID = METAQLTY.post_id 
					and METAQLTY.meta_key = %s
				left join 
					$wpdb->postmeta as METADISABLED on POSTS.ID = METADISABLED.post_id 
					and METADISABLED.meta_key = %s
				where 
					POSTS.post_type = %s
					and (POSTS.post_mime_type = 'image/jpeg' OR POSTS.post_mime_type = 'image/gif' OR POSTS.post_mime_type = 'image/png')
				) as ATTACHMENTS
				WHERE 
					(ATTACHMENTS.qlty != '%s' OR ATTACHMENTS.qlty IS NULL)
					AND ATTACHMENTS.disabled IS NULL
				LIMIT %d",
				array('_wp_attachment_metadata','resmushed_quality','resmushed_disabled','attachment', self::getPictureQualitySetting(), self::MAX_ATTACHMENTS_REQ)
		);
		// Get the images in the attachement table
		$all_images = $wpdb->get_results($queryUnoptimizedPicture);

		foreach($all_images as $image){
			$tmp = array();
			$tmp['ID'] = $image->ID;
			$tmp['attachment_metadata'] = isset($image->file_meta) ? unserialize($image->file_meta) : array();
			
			if( !file_exists(get_attached_file( $image->ID )) ) {
				$files_not_found[] = $tmp;
				continue;
			}
			//If filesize > 5MB, we do not optimize this picture
			if( filesize(get_attached_file( $image->ID )) > self::MAX_FILESIZE ){
				$files_too_big[] = $tmp;
				continue;
			}
			
			$unsmushed_images[] = $tmp;
		}
		return json_encode(array('nonoptimized' => $unsmushed_images, 'filestoobig' => $files_too_big, 'filesnotfound' => $files_not_found));
	}


	/**
      * 
      * Return the number of non optimized pictures
      *
      * @param none
      * @return number of non optimized pictures to the current quality factor
      */
	public static function getCountNonOptimizedPictures(){
		$data = json_decode(self::getNonOptimizedPictures());
		$output = array();
		$output['nonoptimized'] = is_array($data->nonoptimized) ? sizeof($data->nonoptimized) : 0;
		$output['filesnotfound'] = is_array($data->filesnotfound) ? sizeof($data->filesnotfound) : 0;
		$output['filestoobig'] = is_array($data->filestoobig) ? sizeof($data->filestoobig) : 0;
		return $output;
	}


	/**
      * 
      * Record in DB new status for optimization disabled state
      *
      * @param int 		$id 	ID of postID
      * @param string 	$state 	state of disable
      * @return none
      */
	public static function updateDisabledState($id, $state){
		//if we do not want this attachment to be resmushed.
		if($state == "true"){
			update_post_meta($id, 'resmushed_disabled', 'disabled');
			self::revert($id);
			return 'true';
		} else {
			delete_post_meta($id, 'resmushed_disabled');
			return 'false';
		}
	}



	/**
      * 
      * Get Disabled State
      *
      * @param int 		$attachment_id 	Post ID
      * @return boolean true if attachment is disabled
      */
	public static function getDisabledState($attachment_id){
		if(get_post_meta($attachment_id, 'resmushed_disabled'))
			return true;
		return false;
	}



	/**
      * 
      * Get Last Quality Factor attached to a picture
      *
      * @param int 		$attachment_id 	Post ID
      * @return int 	quality setting for this attachment
      */
	public static function getAttachmentQuality($attachment_id){
		$attachmentQuality = get_post_meta($attachment_id, 'resmushed_quality');
		if(isset($attachmentQuality[0]))
			return $attachmentQuality[0];
		return null;
	}



	/**
      * 
      * Check if this Attachment was successfully optimized
      *
      * @param int 		$attachment_id 	Post ID
      * @return string	$status
      */
	public static function wasSuccessfullyUpdated($attachment_id){
		if( self::getDisabledState( $attachment_id ))
			return 'disabled';
		if (!file_exists(get_attached_file( $attachment_id ))) {

			rlog("Error! File " . get_attached_file( $attachment_id ) . " not found on disk.", 'WARNING');
			return 'file_not_found';
		}
		if( filesize(get_attached_file( $attachment_id )) > self::MAX_FILESIZE){
			return 'file_too_big';
		}

		if( self::getPictureQualitySetting() != self::getAttachmentQuality( $attachment_id ))
			return 'failed';
		return 'success';
	}
}
