<?php

/*
*  rest_api_init
*  Custom /resize route
*
*  @since 1.0
*/

add_action( 'rest_api_init', function () {
   $my_namespace = 'gbm';
   $my_endpoint = '/toggle';
   register_rest_route( $my_namespace, $my_endpoint,
      array(
         'methods' => 'POST',
			'callback' => 'block_manager_toggle',
			'permission_callback' => function () {
				return Gutenberg_Block_Manager::has_access();
			},
      )
   );
});




/*
*  block_manager_toggle
*  Enable/Disable gutenberg blocks
*
*  @param $request      $_POST
*  @return $response    json
*  @since 1.0
*/

function block_manager_toggle( WP_REST_Request $request ) {

	if (is_user_logged_in() && current_user_can( apply_filters( 'block_manager_user_role', 'activate_plugins' ) )){

		error_reporting(E_ALL|E_STRICT);

		// Get JSON Data
      $body = json_decode($request->get_body(), true); // Get contents of request body
      $data = json_decode($body['data']); // Get contents of data

      if($body && $data){

	      $block = ($data && $data->block) ? $data->block : ''; // block name
	      $type = ($data && $data->type) ? $data->type : 'enable'; // enable/disable
	      $blocks = (array)get_option(BLOCK_MANAGER_OPTION, array()); // all currently disabled blocks

	      // Disable
	      if($block && $type === 'disable'){
				if ( ! in_array( $block, $blocks, true ) ) {
					$blocks[] = $block;
				}
				update_option( BLOCK_MANAGER_OPTION, $blocks );
				$response = array(
		   		'success' => true,
		   		'msg' => __('Block Disabled', 'block-manager'),
		   		'disabled_blocks' => count(get_option( BLOCK_MANAGER_OPTION ))
				);
	      }

	      // Enable
	      if($block && $type === 'enable'){
				$new_blocks = array();
				if ( in_array( $block, $blocks, true ) ) {
					$new_blocks = array_diff( $blocks, array( $block ) );
				}
				update_option( BLOCK_MANAGER_OPTION, $new_blocks );
				$response = array(
		   		'success' => true,
		   		'msg' => __('Block enabled', 'block-manager'),
		   		'disabled_blocks' => count(get_option( BLOCK_MANAGER_OPTION ))
				);
	      }

	   } else {
		   $response = array(
	   		'success' => false,
	   		'msg' => __('Error accessing API data.', 'block-manager'),
		   	'disabled_blocks' => count(get_option( BLOCK_MANAGER_OPTION ))
			);
	   }

      wp_send_json($response); // Send response as JSON

   }
}
