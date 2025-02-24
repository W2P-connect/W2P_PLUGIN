<?php
/**
 * Handles the REST API route for app localizer.
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
			'/applocalizer/',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => 'w2pcifw_get_applocalizer',
					'permission_callback' => function () {
						return w2pcifw_is_local_environment();
					},
				),
			)
		);
	}
);

/**
 * Callback for the applocalizer REST API route.
 *
 * @param WP_REST_Request $request The request object.
 * @return WP_REST_Response The response object.
 */
function w2pcifw_get_applocalizer( $request ) {
	try {
		$app_localizer = array(
			'token'                    => wp_generate_auth_cookie( 1, time() + 3600, 'auth' ),
			'w2pcifw_client_rest_url'  => get_rest_url() . 'w2p/v1',
			'users_meta_key'           => w2pcifw_get_users_metakey(),
			'w2pcifw_distant_rest_url' => W2PCIFW_DISTANT_REST_URL,
			'build_url'                => '',
			'CONSTANTES'               => array(
				'W2PCIFW_META_KEYS'       => W2PCIFW_META_KEYS,
				'W2PCIFW_REQUIRED_FIELDS' => W2PCIFW_REQUIRED_FIELDS,
				'W2PCIFW_HOOK_LIST'       => W2PCIFW_HOOK_LIST,
				'W2PCIFW_HOOK_SOURCES'    => array_keys( W2PCIFW_HOOK_SOURCES ),
				'W2PCIFW_AVAIBLE_STATES'  => W2PCIFW_query::$avaible_state,
			),
			'remote'                   => isset( $_SERVER['SERVER_NAME'] ) ? sanitize_text_field( wp_unslash( $_SERVER['SERVER_NAME'] ) ) : '',
		);

		$parameters = get_option( 'w2pcifw_parameters' );
		if ( $parameters ) {
			$app_localizer['parameters'] = w2pcifw_get_parameters();
		}

		$parsed_url = wp_parse_url( get_site_url() );
		if ( $parsed_url && isset( $parsed_url['host'] ) ) {
			$app_localizer['parameters']['w2p']['domain'] = $parsed_url['host'];
		}

		return new WP_REST_Response(
			array(
				'success' => true,
				'data'    => $app_localizer,
			),
			200
		);
	} catch ( \Throwable $e ) {
		return new WP_REST_Response(
			array(
				'success'   => false,
				'message'   => $e->getMessage(),
				'traceback' => $e->__toString(),
				'payload'   => $request->get_params(),
			),
			500
		);
	}
}
