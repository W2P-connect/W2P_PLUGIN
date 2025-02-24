<?php
/**
 * Handles the REST API route for sending WooCommerce orders to our server.
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
			'/order/(?P<id>[\d]+)/send',
			array(
				array(
					'methods'             => 'PUT',
					'callback'            => 'w2pcifw_send_order',
					'args'                => array(
						'direct_to_pipedrive' => array(
							'type'     => 'boolean',
							'required' => false,
							'default'  => true,
						),
					),
					'permission_callback' => 'w2pcifw_jwt_token',
				),
			)
		);
	}
);

/**
 * Sends a WooCommerce order to our server.
 *
 * @param WP_REST_Request $request The REST API request object.
 * @return WP_REST_Response The response object containing the result of the operation.
 */
function w2pcifw_send_order( WP_REST_Request $request ) {

	try {
		$id    = (int) $request->get_param( 'id' );
		$order = new W2PCIFW_order( $id );

		if ( ! $order->is_order ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => 'This is not a valid order id',
				),
				400
			);
		}

		$create_query  = (bool) $request->get_param( 'create-query' );
		$order_data    = $order->get_data();
		$order_queries = $order_data['queries'];

		if ( $create_query ) {
			// Sync organization first.
			$user            = new W2PCIFW_User( $order_data['customer_id'] );
			$skip_next_query = false;

			$hook_obj_orga = w2pcifw_get_hook( 'profile_update', W2PCIFW_CATEGORY['organization'], $user->ID );
			if ( $hook_obj_orga ) {
				$formated_hook = $hook_obj_orga->w2pcifw_get_formated_hook();

				if ( $hook_obj_orga->get_same_previous_query() ) {
					$skip_next_query = true;
				}

				if ( ! $skip_next_query ) {
					$query_obj = W2PCIFW_Query::create_query(
						$formated_hook['category'],
						$formated_hook['source'],
						$formated_hook['source_id'],
						"Manual order sync ($formated_hook[label])",
						$formated_hook
					);
					$query_obj->send( true );
				}
				$skip_next_query = false;
			}

			// Then person first.
			$hook_obj_person = w2pcifw_get_hook( 'profile_update', W2PCIFW_CATEGORY['person'], $user->ID );
			if ( $hook_obj_person ) {
				$formated_hook = $hook_obj_person->w2pcifw_get_formated_hook();

				if ( $hook_obj_person->get_same_previous_query() ) {
					$skip_next_query = true;
				}

				if ( ! $skip_next_query ) {
					$query_obj  = W2PCIFW_Query::create_query(
						$formated_hook['category'],
						$formated_hook['source'],
						$formated_hook['source_id'],
						"Manual order sync ($formated_hook[label])",
						$formated_hook
					);
					$send_infos = $query_obj->send( true );
				}
				$skip_next_query = false;
			}

			// Order.
			$status = $order->get_status(); // From WooCommerce.
			$key    = W2PCIFW_ORDER_STATUS_HOOK[ $status ];
			if ( $key ) {
				$hook_obj = w2pcifw_get_hook( $key ?? '', W2PCIFW_CATEGORY['deal'], $id );
			}
			if ( $hook_obj ) {
				$formated_hook = $hook_obj->w2pcifw_get_formated_hook();

				$query_obj = W2PCIFW_Query::create_query(
					$formated_hook['category'],
					$formated_hook['source'],
					$formated_hook['source_id'],
					"Manual order sync ($formated_hook[label])",
					$formated_hook
				);

				$send_info = $query_obj->send( true );

				return new WP_REST_Response(
					array(
						'success'   => $send_info['success'],
						'send_info' => $send_info,
						'data'      => $order->get_data(),
						'message'   => $send_info['message'],
					),
					200
				);
			} else {
				return new WP_REST_Response(
					array(
						'success' => false,
						'data'    => $order->get_data(),
						'message' => "You need to set the status 'Order $status' in deals settings.",
					),
					200
				);
			}
		} elseif ( $order_queries && count( $order_queries ) ) {

				$query_to_sent = null;
			foreach ( $order_queries as $order_query ) {
				if ( 'INVALID' !== $order_query['state'] ) {
					$query_to_sent = $order_query;
					break;
				}
			}

			if ( $query_to_sent ) {
				$query_obj = new W2PCIFW_Query( $query_to_sent['id'] );
				$send_info = $query_obj->send( true );

				return new WP_REST_Response(
					array(
						'success'   => $send_info['success'],
						'send_info' => $send_info,
						'data'      => $order->get_data(),
						'message'   => $send_info['message'],
					),
					200
				);
			} else {
				return new WP_REST_Response(
					array(
						'success' => true,
						'data'    => $order->get_data(),
						'message' => 'No valid request to send for this order',
					),
					200
				);
			}
		} else {
			return new WP_REST_Response(
				array(
					'success' => true,
					'data'    => $order->get_data(),
					'message' => 'No request to send for this order',
					'payload' => $request->get_params(),
				),
				200
			);
		}
	} catch ( \Throwable $e ) {
		w2pcifw_add_error_log( $e->getMessage(), 'w2pcifw_send_order' );
		return new WP_REST_Response(
			array(
				'success' => false,
				'data'    => $order->get_data(),
				'message' => 'An error occured during sending the order on your website',
				'payload' => $request->get_params(),
				'context' => 'Catch triggered during w2pcifw_send_order',
			),
			500
		);
	}
}
