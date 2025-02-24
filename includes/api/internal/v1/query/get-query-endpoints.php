<?php
/**
 * Handles the REST API route for retrieving W2P queries.
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
			'/queries',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => 'w2pcifw_get_queries',
					'permission_callback' => 'w2pcifw_jwt_token',
				),
			)
		);
	}
);

/**
 * Retrieves a list of W2P queries via the REST API.
 *
 * @param WP_REST_Request $request The REST API request object.
 * @return WP_REST_Response The response object containing the queries or error information.
 */
function w2pcifw_get_queries( $request ) {

	try {
		$queries = W2PCIFW_Query::get_queries(
			true,
			$request->get_params(),
			(int) $request->get_param( 'page' ) ?? 1,
			(int) $request->get_param( 'per_page' ) ?? 10
		);

		return new WP_REST_Response(
			$queries,
			200
		);
	} catch ( \Throwable $e ) {
		w2pcifw_add_error_log( $e->getMessage(), 'w2pcifw_get_queries' );
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
