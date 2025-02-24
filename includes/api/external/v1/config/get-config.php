<?php
/**
 * Handles the REST API route for getting W2P config for support.
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
			'/config',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => 'w2pcifw_ext_get_config',
					'permission_callback' => function ( $request ) {
						return w2pcifw_check_api_key( $request['api_key'] );
					},
				),
			)
		);
	}
);

/**
 * Retrieves the W2P configuration settings for support via a REST API endpoint.
 *
 * This function handles the GET request to fetch global configuration data
 * needed for the W2P plugin. It includes URLs, user meta keys, distant REST URLs,
 * and various defined constants. The function also attempts to retrieve any
 * additional parameters stored in options and appends the site's domain to the
 * configuration data if available. In case of an error during execution, it logs
 * the error and returns a response with detailed error information.
 *
 * @param WP_REST_Request $request The incoming REST API request.
 * @return WP_REST_Response The REST API response containing configuration data
 *                          or error details if an exception occurs.
 */
function w2pcifw_ext_get_config( $request ) {
	try {

		$app_global_data = array(
			'w2pcifw_client_rest_url'  => get_rest_url() . 'w2p/v1',
			'users_meta_key'           => w2pcifw_get_users_metakey(),
			'w2pcifw_distant_rest_url' => W2PCIFW_DISTANT_REST_URL,
			'build_url'                => plugins_url( '/admin/build', __FILE__ ),
			'CONSTANTES'               => array(
				'W2PCIFW_META_KEYS'       => W2PCIFW_META_KEYS,
				'W2PCIFW_REQUIRED_FIELDS' => W2PCIFW_REQUIRED_FIELDS,
				'W2PCIFW_HOOK_LIST'       => W2PCIFW_HOOK_LIST,
				'W2PCIFW_HOOK_SOURCES'    => array_keys( W2PCIFW_HOOK_SOURCES ),
				'W2PCIFW_AVAIBLE_STATES'  => W2PCIFW_Query::$avaible_state,
			),
		);

		$parameters = get_option( 'w2pcifw_parameters' );
		if ( $parameters ) {
			$app_global_data['parameters'] = w2pcifw_get_parameters();
		}

		$parsed_url = wp_parse_url( get_site_url() );
		if ( $parsed_url && isset( $parsed_url['host'] ) ) {
			$app_global_data['parameters']['w2p']['domain'] = $parsed_url['host'];
		}

		return new WP_REST_Response(
			array(
				'success' => true,
				'data'    => $app_global_data,
			),
			200
		);
	} catch ( \Throwable $e ) {
		w2pcifw_add_error_log( $e->getMessage(), 'w2pcifw_ext_get_queries' );
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
