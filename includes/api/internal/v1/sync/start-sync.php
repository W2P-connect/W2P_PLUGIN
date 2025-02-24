<?php
/**
 * Handles the REST API route for starting W2P synchronization.
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
			'/start-sync',
			array(
				'methods'             => 'GET',
				'callback'            => 'w2pcifw_start_sync',
				'args'                => array(
					're-sync' => array(
						'type'     => 'boolean',
						'default'  => false,
						'required' => false,
					),
					'retry'   => array(
						'type'     => 'boolean',
						'default'  => false,
						'required' => false,
					),
				),
				'permission_callback' => 'w2pcifw_jwt_token',
			)
		);
	}
);

add_action(
	'w2pcifw_cron_check_sync',
	function () {
		$is_sync_running = get_option( 'w2pcifw_sync_running', false );
		$last_heartbeat  = get_option( 'w2pcifw_sync_last_heartbeat', null );

		if ( $is_sync_running && ( ! $last_heartbeat || time() - $last_heartbeat > 60 ) ) {
			w2pcifw_add_error_log(
				'cron job launched: Condition du if du cron déclenché: la syncronisation est running mais pourtant pas de heart_beat' .
				wp_json_encode(
					array(
						'is_sync_running'              => $is_sync_running,
						'last_heartbeat'               => $last_heartbeat,
						'time() - last_heartbeat > 60' => time() - $last_heartbeat > 60,
					),
					JSON_PRETTY_PRINT
				),
				'w2pcifw_cron_check_sync'
			);
			w2pcifw_sync_function( false, true );
		}
	}
);

/**
 * Resets synchronization-related options to their default values.
 *
 * @return void
 */
function w2pcifw_reset_sync_options() {
	update_option( 'w2pcifw_sync_additional_datas', W2PCIFW_EMPTY_SYNC_ADDITIONAL_DATA );
	update_option( 'w2pcifw_sync_last_error', '' );
	update_option( 'w2pcifw_sync_progress_users', 0 );
	update_option( 'w2pcifw_sync_progress_orders', 0 );
}

/**
 * Starts the W2P synchronization process via the REST API.
 *
 * @param WP_REST_Request $request The REST API request object.
 * @return WP_REST_Response The response object indicating synchronization status.
 */
function w2pcifw_start_sync( WP_REST_Request $request ) {
	try {
		$retry = $request->get_param( 'retry' );

		if ( ! w2pcifw_is_sync_running() || $retry ) {
			w2pcifw_add_error_log( 'starting sync ', 'w2pcifw_start_sync' );

			if ( ! $retry ) {
				w2pcifw_reset_sync_options();
			}

			update_option( 'w2pcifw_start_sync', true );

			$sync_url = rest_url( 'w2p/v1/run-sync' );
			$args     = array(
				'blocking' => false,
				'method'   => 'POST',
				'body'     => wp_json_encode( $request->get_params() ),
				'headers'  => array(
					'Content-Type' => 'application/json',
				),
			);

			wp_remote_post( $sync_url, $args );

			if ( ! wp_next_scheduled( 'w2pcifw_cron_check_sync' ) ) {
				wp_schedule_event( time(), 'one_minute', 'w2pcifw_cron_check_sync' );
			}

			return new WP_REST_Response(
				array(
					'success' => true,
					'message' => 'Synchronization started in the background.',
				),
				200
			);
		} else {
			return new WP_REST_Response(
				array(
					'message' => 'Synchronization already in progress.',
					'running' => w2pcifw_is_sync_running(),
				),
				200
			);
		}
	} catch ( \Throwable $e ) {
		// Handle the error.
		return new WP_REST_Response(
			array(
				'success' => false,
				'error'   => $e->getMessage(),
			),
			500
		);
	}
}
