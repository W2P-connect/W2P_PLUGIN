<?php
/**
 * Handles the REST API routes for managing W2P parameters.
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
			'/parameters',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => 'w2pcifw_get_parameters_api',
					'permission_callback' => 'w2pcifw_jwt_token',
				),
				array(
					'methods'             => 'PUT',
					'callback'            => 'w2pcifw_put_parameters',
					'args'                => array(
						'parameters' => array(
							'required' => false,
							'type'     => 'int',
						),
					),
					'permission_callback' => 'w2pcifw_jwt_token',
				),
			)
		);
	}
);

add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'w2p/v1',
			'/restore-parameters',
			array(
				array(
					'methods'             => 'PUT',
					'callback'            => 'w2pcifw_restore_parameters',
					'args'                => array(
						'parameters' => array(
							'required' => false,
							'type'     => 'int',
						),
					),
					'permission_callback' => 'w2pcifw_jwt_token',
				),
			)
		);
	}
);

/**
 * Retrieves W2P parameters via the REST API.
 *
 * @return WP_REST_Response The response object containing the parameters.
 */
function w2pcifw_get_parameters_api() {
	$parameters = w2pcifw_maybe_json_decode( get_option( 'w2pcifw_parameters' ) );
	return new WP_REST_Response( array( 'data' => $parameters ), 200 );
}

/**
 * Restores default W2P parameters via the REST API.
 *
 * @return WP_REST_Response The response object confirming the restoration.
 */
function w2pcifw_restore_parameters() {
	try {
		delete_option( 'w2pcifw_sync_additional_datas' );
		delete_option( 'w2pcifw_sync_last_error' );
		delete_option( 'w2pcifw_sync_progress_users' );
		delete_option( 'w2pcifw_sync_progress_orders' );
		delete_option( 'w2pcifw_start_sync' );
		delete_option( 'w2pcifw_sync_last_heartbeat' );
		delete_option( 'w2pcifw_sync_running' );
		delete_option( 'w2pcifw_last_sync' );

		return new WP_REST_Response(
			array(
				'success' => true,
				'message' => 'Settings restored.',
			),
			200
		);
	} catch ( Throwable $e ) {
		w2pcifw_add_error_log( 'Error while restoring parameters: ' . $e->getMessage(), 'w2pcifw_reset_parameters' );
		return new WP_REST_Response(
			array(
				'message' => 'Error while restoring parameters: ' . $e->getMessage(),
				'success' => false,
			),
			400
		);
	}
}

/**
 * Updates W2P parameters via the REST API.
 *
 * @param WP_REST_Request $request The REST API request object.
 * @return WP_REST_Response The response object confirming the update status.
 */
function w2pcifw_put_parameters( $request ) {
	try {
		$parameters = $request->get_param( 'parameters' );

		if ( isset( $parameters['w2p']['api_key'] ) ) {
			$parameters['w2p']['api_key'] = w2pcifw_encrypt( $parameters['w2p']['api_key'] );
		}

		if ( isset( $parameters['pipedrive']['api_key'] ) ) {
			$parameters['pipedrive']['api_key'] = w2pcifw_encrypt( $parameters['pipedrive']['api_key'] );
		}

		if ( isset( $parameters['pipedrive']['company_domain'] ) ) {
			$parameters['pipedrive']['company_domain'] = w2pcifw_encrypt( $parameters['pipedrive']['company_domain'] );
		}

		if ( isset( $parameters['w2p']['hookList'] ) && is_array( $parameters['w2p']['hookList'] ) ) {
			$parameters['w2p']['hookList'] = array_values(
				array_filter(
					$parameters['w2p']['hookList'],
					function ( $hook ) {
						return true === $hook['enabled'] || ( isset( $hook['fields'] ) && count( $hook['fields'] ) );
					}
				)
			);
		}

		$success = update_option( 'w2pcifw_parameters', $parameters );

		$parameters = w2pcifw_get_parameters();

		return new WP_REST_Response(
			array(
				'data'    => $parameters,
				'request' => $request->get_params(),
				'success' => $success,
				'token'   => wp_generate_auth_cookie( get_current_user_id(), time() + 3600, 'auth' ),
				'message' => $success
					? 'Parameters updated'
					: 'Error while updating parameters',
			),
			$success ? 200 : 400
		);
	} catch ( Throwable $e ) {
		w2pcifw_add_error_log( 'Error while updating parameters: ' . $e->getMessage(), 'w2pcifw_put_parameters' );
		w2pcifw_add_error_log( 'Parameters passed: ' . wp_json_encode( $request->get_params(), JSON_PRETTY_PRINT ), 'w2pcifw_put_parameters' );
		return new WP_REST_Response(
			array(
				'message' => 'Error while updating parameters: ' . $e->getMessage(),
				'success' => false,
			),
			400
		);
	}
}
