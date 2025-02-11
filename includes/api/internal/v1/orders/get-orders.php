<?php
/**
 * Handles the REST API route for retrieving WooCommerce orders.
 *
 * @package W2P
 * @since 1.0.0
 */

add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'w2p/v1',
			'/orders',
			array(
				'methods'             => 'GET',
				'callback'            => 'w2p_get_orders',
				'permission_callback' => 'w2p_jwt_token',
			)
		);
	}
);

/**
 * Retrieves WooCommerce orders based on the provided parameters.
 *
 * @param WP_REST_Request $request The REST API request object.
 * @return WP_REST_Response The response object containing the orders or error information.
 */
function w2p_get_orders( WP_REST_Request $request ) {
	try {

		$order_id = (int) $request->get_param( 'orderId' );
		$page     = (int) $request->get_param( 'page' );
		$per_page = (int) $request->get_param( 'per_page' );

		if ( w2p_is_woocomerce_active() ) {
			$orders = W2P_Order::get_orders(
				$order_id ? $order_id : null,
				$page ? $page : null,
				$per_page ? $per_page : null,
			);

			return new WP_REST_Response(
				$orders,
				200
			);
		} else {
			return new WP_REST_Response(
				array(
					'success' => false,
					'data'    => array(),
					'message' => 'Woocomerce is not active on your website',
				),
				200
			);
		}
	} catch ( \Throwable $e ) {
		w2p_add_error_log( $e->getMessage(), 'w2p_get_orders' );
		return new WP_REST_Response(
			array(
				'success'   => false,
				'error'     => $e->getMessage(),
				'traceback' => $e->__toString(),
				'payload'   => $request->get_params(),
			),
			500
		);
	}
}
