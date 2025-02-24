<?php
/**
 * Handles the REST API route for updating W2P queries.
 *
 * @package W2P
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'w2p/v1',
			'/query/(?P<id>[\d]+)/',
			array(
				array(
					'methods'             => 'PUT',
					'callback'            => 'w2pcifw_ext_put_query',
					'args'                => array(
						'pipedrive_response' => array(
							'required' => false,
							'type'     => 'string',
							'default'  => null,
						),
						'traceback'          => array(
							'required' => false,
							'type'     => 'array',
							'default'  => array(),
						),
					),
					'permission_callback' => function ( $request ) {
						return w2pcifw_check_api_key( $request['api_key'] );
					},
				),
			)
		);
	}
);

/**
 * Handles the PUT request for updating a W2P query.
 *
 * @param WP_REST_Request $request The request object.
 * @return WP_REST_Response The response object.
 */
function w2pcifw_ext_put_query( $request ) {
	try {
		$id    = (int) $request->get_param( 'id' );
		$query = new W2PCIFW_Query( (int) $id );

		if ( $query->new_instance ) {
			return new WP_REST_Response(
				array(),
				204
			);
		} else {

			$params             = $request->get_params();
			$pipedrive_response = null;
			if ( isset( $params['pipedrive_response'] ) && is_string( $params['pipedrive_response'] ) ) {
				$params['pipedrive_response'] = w2pcifw_maybe_json_decode( $params['pipedrive_response'], true );
			}

			$traceback          = $params['traceback'];
			$pipedrive_response = $params['pipedrive_response'];
			$cancel             = $params['cancel'];

			if ( $cancel ) {
				$query->cancel();
				$query->add_traceback(
					'Request Cancellation',
					false,
					'Your query has been canceled due to too many errors on our servers.'
				);
				return new WP_REST_Response(
					array(
						'success' => true,
						'message' => 'Query canceled',
						'data'    => $query->get_data(),
						'params'  => $params,
					),
					200
				);
			}

			if ( $traceback && is_array( $traceback ) ) {
				foreach ( $traceback as $event ) {
					if ( isset( $event['step'] ) && isset( $event['success'] ) ) {
						$date = isset( $event['createdAt'] ) ? gmdate( 'Y-m-d\TH:i:s\Z', strtotime( $event['createdAt'] ) ) : null;

						$query->add_traceback(
							$event['step'],
							$event['success'],
							isset( $event['value'] ) ? $event['value'] : '',
							isset( $event['data'] ) ? $event['data'] : '',
							false,
							$date,
						);
					}
				}
			}

			if ( $pipedrive_response ) {
				$query->setter( 'pipedrive_response', array( 'id' => $pipedrive_response['id'] ) );
				if ( isset( $pipedrive_response['id'] ) ) {
					$query->setter( 'target_id', $pipedrive_response['id'] );
					$query->update_source_target_id( $pipedrive_response['id'] );
					$query->cancel_previous_query();
				}
			}

			return new WP_REST_Response(
				array(
					'success' => true,
					'message' => 'Query updated',
					'data'    => $query->get_data(),
					'params'  => $params,
				),
				200
			);
		}
	} catch ( \Throwable $e ) {
		w2pcifw_add_error_log( $e->getMessage(), 'w2pcifw_ext_put_query' );
		return new WP_REST_Response(
			array(
				'success' => false,
				'message' => $e->getMessage(),
				'params'  => $params,
			),
			500
		);
	}
}
