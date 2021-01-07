
/**
 * Bulk Resize admin javascript functions
 */
var bulkCounter = 0;
var bulkTotalimages = 0;
var next_index = 0;
var file_too_big_count = 0;


/**
 * Form Validators
 */
jQuery("#rsmt-options-form").submit(function(){
	jQuery("#resmushit_qlty").removeClass('form-error');
	var qlty = jQuery("#resmushit_qlty").val();
	if(!jQuery.isNumeric(qlty) || qlty > 100 || qlty < 0){
		jQuery("#resmushit_qlty").addClass('form-error');
		return false;
	}
});


jQuery( ".list-accordion h4" ).on('click', function(){
	if(jQuery(this).parent().hasClass('opened')){
		jQuery(".list-accordion ul").slideUp();
		jQuery('.list-accordion').removeClass('opened');

	} else {
		jQuery(".list-accordion ul").slideDown();
		jQuery('.list-accordion').addClass('opened');
	}
});

updateDisabledState();
optimizeSingleAttachment();
removeBackupFiles();


/** 
 * recursive function for resizing images
 */
function resmushit_bulk_process(bulk, item){
	var error_occured = false;	
	jQuery.post(
		ajaxurl, { 
			action: 'resmushit_bulk_process_image', 
			data: bulk[item]
		}, 
		function(response) {
			if(response == 'failed')
				error_occured = true;
			else if(response == 'file_too_big')
				file_too_big_count++;

			if(!flag_removed){
				jQuery('#bulk_resize_target').remove();
				container.append('<div id="smush_results" style="padding: 20px 5px; overflow: auto;" />');
				var results_target = jQuery('#smush_results'); 
				results_target.html('<div class="bulk--back-progressionbar"><div <div class="resmushit--progress--bar"</div></div>');
				flag_removed = true;
			}

			bulkCounter++;
			jQuery('.resmushit--progress--bar').html('<p>'+ Math.round((bulkCounter*100/bulkTotalimages)) +'%</p>');
			jQuery('.resmushit--progress--bar').animate({'width': Math.round((bulkCounter*100/bulkTotalimages))+'%'}, 0);

			if(item < bulk.length - 1)
				resmushit_bulk_process(bulk, item + 1);
			else{
				if(error_occured){
					jQuery('.non-optimized-wrapper h3').text('An error occured when contacting webservice. Please try again later.');
					jQuery('.non-optimized-wrapper > p').remove();
					jQuery('.non-optimized-wrapper > div').remove();
				} else if(file_too_big_count){
					
					var message = file_too_big_count + ' picture cannot be optimized (> 5MB). All others have been optimized';
					if(file_too_big_count > 1)
						var message = file_too_big_count + ' pictures cannot be optimized (> 5MB). All others have been optimized';

					jQuery('.non-optimized-wrapper h3').text(message);
					jQuery('.non-optimized-wrapper > p').remove();
					jQuery('.non-optimized-wrapper > div').remove();
				} else{
					jQuery('.non-optimized-wrapper').addClass('disabled');
					jQuery('.optimized-wrapper').removeClass('disabled');
					updateStatistics();
				}
			}
		}
	);
}


/** 
 * ajax post to return all images that are candidates for resizing
 * @param string the id of the html element into which results will be appended
 */
function resmushit_bulk_resize(container_id) {
	container = jQuery('#'+container_id);
	container.html('<div id="bulk_resize_target">');
	jQuery('#bulk-resize-examine-button').fadeOut(200);
	var target = jQuery('#bulk_resize_target');

	target.html('<div class="loading--bulk"><span class="loader"></span><br />Examining existing attachments. This may take a few moments...</div>');

	target.animate(
		{ height: [100,'swing'] },
		500, 
		function() {		
			jQuery.post(
				ajaxurl, 
				{ action: 'resmushit_bulk_get_images' }, 
				function(response) {
					var images = JSON.parse(response);			
					if (images.nonoptimized.length > 0) {	
						bulkTotalimages = images.nonoptimized.length;
						target.html('<div class="loading--bulk"><span class="loader"></span><br />' + bulkTotalimages + ' attachment(s) found, starting optimization...</div>');
						flag_removed = false;
						//start treating all pictures
						resmushit_bulk_process(images.nonoptimized, 0);
					} else {
						target.html('<div>There are no existing attachments that requires optimization.</div>');
					}
				}
			);
		});
}


/** 
 * ajax post to update statistics
 */
function updateStatistics() {
	jQuery.post(
		ajaxurl, { 
			action: 'resmushit_update_statistics'
		}, 
		function(response) {
			statistics = JSON.parse(response);	
			jQuery('#rsmt-statistics-space-saved').text(statistics.total_saved_size_formatted);
			jQuery('#rsmt-statistics-files-optimized').text(statistics.files_optimized);
			jQuery('#rsmt-statistics-percent-reduction').text(statistics.percent_reduction);
			jQuery('#rsmt-statistics-total-optimizations').text(statistics.total_optimizations);
		}
	);
}


/** 
 * ajax post to disabled status (or remove)
 */
function updateDisabledState() {
	jQuery(document).delegate(".rsmt-trigger--disabled-checkbox","change",function(e){
	    e.preventDefault();
		var current = this;
		jQuery(current).addClass('rsmt-disable-loader');
		jQuery(current).prop('disabled', true);
		var disabledState = jQuery(current).is(':checked');
		var postID = jQuery(current).attr('data-attachment-id');

		jQuery.post(
			ajaxurl, { 
				action: 'resmushit_update_disabled_state',
				data: {id: postID, disabled: disabledState}
			}, 
			function(response) {
				jQuery(current).removeClass('rsmt-disable-loader');
				jQuery(current).prop('disabled', false);

				if(jQuery(current).parent().hasClass('field')){
					var selector = jQuery(current).parent().parent().next('tr').find('td.field');
				} else {
					var selector = jQuery(current).parent().next('td');
				}

				if(disabledState == true){
					selector.empty().append('-');
				} else {
					selector.empty().append('<input type="button" value="Optimize" class="rsmt-trigger--optimize-attachment button media-button  select-mode-toggle-button" name="resmushit" data-attachment-id="' + postID + '" class="button wp-smush-send" />');
				}	
				optimizeSingleAttachment();			
			}
		);
	});
}



/** 
 * ajax to Optimize a single picture
 */
function optimizeSingleAttachment() {
	jQuery(document).delegate(".rsmt-trigger--optimize-attachment","mouseup",function(e){
	    e.preventDefault();
		var current = this;
		jQuery(current).val('Optimizing...');
		jQuery(current).prop('disabled', true);
		var disabledState = jQuery(current).is(':checked');
		var postID = jQuery(current).attr('data-attachment-id');
		jQuery.post(
			ajaxurl, { 
				action: 'resmushit_optimize_single_attachment',
				data: {id: postID}
			}, 
			function(response) {
				var statistics = jQuery.parseJSON(response);
				jQuery(current).parent().empty().append('Reduced by ' + statistics.total_saved_size_nice + ' (' + statistics.percent_reduction + ' saved)');
			}
		);
	});
}


/** 
 * ajax to Optimize a single picture
 */
function removeBackupFiles() {
	jQuery(document).delegate(".rsmt-trigger--remove-backup-files","mouseup",function(e){
		if ( confirm( "You're about to delete your image backup files. Are you sure to perform this operation ?" ) ) {
   
		    e.preventDefault();
			var current = this;
			jQuery(current).val('Removing backups...');
			jQuery(current).prop('disabled', true);
			jQuery.post(
				ajaxurl, { 
					action: 'resmushit_remove_backup_files'
				}, 
				function(response) {
					var data = jQuery.parseJSON(response);
					jQuery(current).val(data.success + ' backup files successfully removed');
					setTimeout(function(){ jQuery(current).parent().parent().slideUp() }, 3000);
				}
			);
		}
	});
}