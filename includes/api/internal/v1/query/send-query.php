<?php
/**
 * Handles the REST API route for sending a specific W2P query.
 *
 * @package W2P
 * @since 1.0.0
 */

add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'w2p/v1',
			'/query/(?P<id>[\d]+)/send',
			array(
				array(
					'methods'             => 'PUT',
					'callback'            => 'w2p_send_query',
					'args'                => array(
						'direct_to_pipedrive' => array(
							'type'     => 'boolean',
							'required' => false,
							'default'  => true,
						),
						'secret_key'          => array(
							'type'     => 'string',
							'required' => false,
						),
					),
					'permission_callback' => 'w2p_jwt_token',
				),
			)
		);
	}
);

/**
 * Sends a specific W2P query via the REST API.
 *
 * @param WP_REST_Request $request The REST API request object.
 * @return WP_REST_Response The response object containing the result of the operation.
 */
function w2p_send_query( WP_REST_Request $request ) {
	try {
		$id    = (int) $request->get_param( 'id' );
		$query = new W2P_Query( $id );

		if ( $query->new_instance ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => 'There is no query for this id',
				),
				400
			);
		}

		$direct_to_pipedrive = $request['direct_to_pipedrive'];
		$send_info           = $query->send( (bool) $direct_to_pipedrive );

		if ( $send_info['success'] ) {
			return new WP_REST_Response(
				array(
					'success'   => true,
					'data'      => $query->get_data(),
					'send_info' => $send_info,
					'message'   => $send_info['message'],
				),
				200
			);
		} else {
			$query->setter( 'state', 'ERROR' );
			$query->update_additionnal_data( 'last_error', $send_info['message'] );
			return new WP_REST_Response(
				array(
					'send_info' => $send_info,
					'data'      => $query->get_data(),
					'context'   => "send wasn't a success",
				),
				400
			);
		}
	} catch ( \Throwable $e ) {
		w2p_add_error_log( $e->getMessage(), 'w2p_send_query' );
		return new WP_REST_Response(
			array(
				'success' => false,
				'data'    => $query->get_data(),
				'message' => 'An error occured during sending the query on your website',
				'payload' => $request->get_params(),
				'context' => 'Catch triggered during w2p_send_query',
			),
			500
		);
	}
}
