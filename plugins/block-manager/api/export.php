<?php
/**
 * Custom API route.
 *
 * @since 1.0
 */
add_action( 'rest_api_init', function () {
	$my_namespace = 'gbm';
	$my_endpoint = '/export';
	register_rest_route(
		$my_namespace,
		$my_endpoint,
		array(
			'methods'             => 'GET',
			'callback'            => 'block_manager_export',
			'permission_callback' => function () {
				return Gutenberg_Block_Manager::has_access();
			},
		)
	);
});

/**
 * Export disabled blocks as an erray.
 *
 * @param $request      $_POST
 * @return $response    json
 * @since 1.0
 */
function block_manager_export( WP_REST_Request $request ) {

	if ( is_user_logged_in() && current_user_can( apply_filters( 'block_manager_user_role', 'activate_plugins' ) ) ) {

		$blocks = Gutenberg_Block_Manager::gbm_get_disabled_blocks();

		$response = array(
			'success' => true,
			'blocks'  => json_encode( $blocks ),
		);

		wp_send_json( $response );

	} else {
		$response = array(
			'success' => false,
			'msg'     => __( 'Unatuhorized.', 'block-manager' ),
		);

		wp_send_json( $response );
	}
}
