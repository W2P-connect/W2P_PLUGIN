<?php
/**
 * Handles the REST API route for querying W2P data.
 *
 * @package W2P
 * @since 1.0.0
 */

add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'w2p/v1',
			'/query/(?P<id>[\d]+)/',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => 'w2p_ext_get_query',
					'permission_callback' => function ( $request ) {
						return w2p_check_api_key( $request['api_key'] );
					},
				),
			)
		);
	}
);

/**
 * Handles the GET request for a W2P query.
 *
 * @param WP_REST_Request $request The request object.
 * @return WP_REST_Response The response object.
 */
function w2p_ext_get_query( $request ) {
	try {
		$id    = (int) $request->get_param( 'id' );
		$query = new W2P_Query( $id );

		if ( $query->new_instance ) {
			return new WP_REST_Response(
				array(),
				204
			);
		} else {
			return new WP_REST_Response(
				array(
					'success' => true,
					'data'    => $query->data_for_w2p(),
				),
				200
			);
		}
	} catch ( \Throwable $e ) {
		w2p_add_error_log( $e->getMessage(), 'w2p_ext_get_queries' );
		return new WP_REST_Response(
			array(
				'success'   => false,
				'message'   => $e->getMessage(),
				'traceback' => $e->getTrace(),
				'payload'   => $request->get_params(),
			),
			500
		);
	}
}
