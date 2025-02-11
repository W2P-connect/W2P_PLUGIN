<?php
/**
 * Handles the REST API route for running W2P synchronization.
 *
 * @package W2P
 * @since 1.0.0
 */

add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'w2p/v1',
			'/run-sync',
			array(
				'methods'             => 'POST',
				'callback'            => 'w2p_run_sync',
				'permission_callback' => function () {
					return get_option( 'w2p_start_sync', false );
				},
			)
		);
	}
);

/**
 * Update an additional data value in the w2p_sync_additional_datas option.
 *
 * @param string $key   The key of the additional data.
 * @param mixed  $value The new value of the additional data.
 */
function w2p_update_additional_data( string $key, $value ) {
	$w2p_sync_additional_datas         = get_option( 'w2p_sync_additional_datas', W2P_EMPTY_SYNC_ADDITIONAL_DATA );
	$w2p_sync_additional_datas[ $key ] = $value;
	update_option( 'w2p_sync_additional_datas', $w2p_sync_additional_datas );
}

/**
 * Increments an additional data value in the w2p_sync_additional_datas option.
 *
 * This function retrieves the 'w2p_sync_additional_datas' option and increments
 * the value associated with the specified key by one. The updated data is then
 * saved back to the option.
 *
 * @param string $key The key of the additional data to be incremented.
 * @return void
 */
function w2p_incremente_additional_data( string $key ) {
	$w2p_sync_additional_datas         = get_option( 'w2p_sync_additional_datas', W2P_EMPTY_SYNC_ADDITIONAL_DATA );
	$w2p_sync_additional_datas[ $key ] = (int) $w2p_sync_additional_datas[ $key ] + 1;
	update_option( 'w2p_sync_additional_datas', $w2p_sync_additional_datas );
}

/**
 * Runs the W2P synchronization process.
 *
 * This function is called by the "start-sync" REST API endpoint and is responsible for starting the
 * synchronization process. If the "re-sync" parameter is set to true, the sync process will be
 * restarted from the beginning. If the "retry" parameter is set to true, the sync process will be
 * restarted from the last known position.
 *
 * @param WP_REST_Request $request The REST API request object.
 *
 * @return array The response object containing synchronization progress and related data.
 */
function w2p_run_sync( WP_REST_Request $request ) {
	$resync = $request->get_param( 're-sync' );
	$retry  = $request->get_param( 'retry' );
	update_option( 'w2p_start_sync', false );
	return w2p_sync_function( $resync, $retry );
}

/**
 * Executes the W2P synchronization process for users and orders.
 *
 * This function handles the synchronization of users and orders from WooCommerce
 * to Pipedrive. It supports options for resyncing from the start or retrying from
 * the last known position. The function updates synchronization progress and
 * maintains a heartbeat to track active synchronizations. It logs errors and
 * manages synchronization states.
 *
 * @param bool $resync Indicates if the synchronization should start from the beginning.
 * @param bool $retry  Indicates if the synchronization should retry from the last known position.
 *
 * @return WP_REST_Response The response containing synchronization result and status.
 */
function w2p_sync_function( $resync = false, $retry = false ) {
	try {
		update_option( 'w2p_sync_last_heartbeat', time() );

		if ( ! w2p_is_sync_running() || $retry ) {

			update_option( 'w2p_sync_running', true );

			$w2p_sync_additional_datas = get_option( 'w2p_sync_additional_datas', W2P_EMPTY_SYNC_ADDITIONAL_DATA );
			$init_user_index           = $w2p_sync_additional_datas['current_user_index'];
			$init_order_index          = $w2p_sync_additional_datas['current_order_index'];

			w2p_add_error_log(
				'Démarage de la fonction w2p_run_sync avec les params suivant : '
					. wp_json_encode(
						array(
							'resync' => $resync,
							'retry'  => $retry,
						),
						JSON_PRETTY_PRINT
					)
					. wp_json_encode( $w2p_sync_additional_datas, JSON_PRETTY_PRINT ),
				'w2p_sync_function'
			);

			/***************** Users */

			$users = get_users(
				array(
					'orderby' => 'registered',
					'order'   => 'ASC',
				)
			);

			$total_users = count( $users );

			$orders = wc_get_orders(
				array(
					'limit'   => -1,
					'orderby' => 'date',
					'order'   => 'ASC',
				)
			);

			$total_orders = count( $orders );

			w2p_update_additional_data( 'total_users', $total_users );
			w2p_update_additional_data( 'total_orders', $total_orders );

			foreach ( $users as $index => $user ) {
				if ( $index < $init_user_index || ! w2p_is_sync_running() ) {
					continue;
				}
				update_option( 'w2p_sync_last_heartbeat', time() );
				$user            = new W2P_User( $user->ID );
				$skip_next_query = false;

				$hook_obj_orga = w2p_get_hook( 'profile_update', W2P_CATEGORY['organization'], $user->ID );
				if ( $hook_obj_orga ) {
					$formated_hook        = $hook_obj_orga->w2p_get_formated_hook();
					$organization_queries = $user->get_organization_queries();

					if ( isset( $organization_queries[0] ) ) {
						$query_payload = $organization_queries[0]['payload'];
						unset( $query_payload['data'] );

						if ( w2p_compare_arrays( $query_payload, $formated_hook ) ) {
							$skip_next_query = true;
							if ( 'DONE' !== $organization_queries[0]['state'] ) {
								$query = new W2P_Query( $organization_queries[0]['id'] );
								$query->send( true );
							}
						}
					}

					if ( ! $skip_next_query ) {
						$query_obj = W2P_Query::create_query(
							$formated_hook['category'],
							$formated_hook['source'],
							$formated_hook['source_id'],
							"Manual ($formated_hook[label])",
							$formated_hook
						);
						$query_obj->send( true );
					}
					$skip_next_query = false;
				}

				$hook_obj_person = w2p_get_hook( 'profile_update', W2P_CATEGORY['person'], $user->ID );
				if ( $hook_obj_person ) {

					$formated_hook  = $hook_obj_person->w2p_get_formated_hook();
					$person_queries = $user->get_person_queries();

					if ( isset( $person_queries[0] ) ) {
						$query_payload = $person_queries[0]['payload'];
						unset( $query_payload['data'] );

						if ( w2p_compare_arrays( $query_payload, $formated_hook ) ) {
							$skip_next_query = true;
							w2p_incremente_additional_data( 'total_person_uptodate' );
							if ( 'DONE' !== $person_queries[0]['state'] ) {
								$query = new W2P_Query( $person_queries[0]['id'] );
								$query->send( true );
							}
						}
					}

					if ( ! $skip_next_query ) {
						$query_obj  = W2P_Query::create_query(
							$formated_hook['category'],
							$formated_hook['source'],
							$formated_hook['source_id'],
							"Manual ($formated_hook[label])",
							$formated_hook
						);
						$send_infos = $query_obj->send( true );

						if ( $send_infos['success'] ) {
							w2p_incremente_additional_data( 'total_person_done' );
						} else {
							w2p_incremente_additional_data( 'total_person_errors' );
						}
					}
					$skip_next_query = false;
				} else {
					update_option( 'w2p_sync_running', false );
					w2p_add_error_log( "You need to set the status 'User updated' in persons settings", 'w2p_run_sync' );
					update_option( 'w2p_sync_last_error', "You need to enable the 'User updated' status in the person's settings." );

					return new WP_REST_Response(
						array(
							'success' => false,
							'data'    => $user->ID,
							'message' => "You need to set the status 'User updated' in persons settings.",
						),
						200
					);
				}

				$progress = intval( ( $index + 1 ) / $total_users * 100 );
				update_option( 'w2p_sync_progress_users', $progress );

				w2p_update_additional_data( 'current_user', $user->ID );
				w2p_update_additional_data( 'current_user_index', $index + 1 );
			}

			/***************** Orders */

			foreach ( $orders as $index => $order ) {
				if ( $index < $init_order_index || ! w2p_is_sync_running() ) {
					continue;
				}
				update_option( 'w2p_sync_last_heartbeat', time() );

				$skip_next_query = false;
				$order_id        = $order->get_id();
				$order           = new W2P_order( $order_id );

				$status = $order->get_status(); // From woocommerce.
				$key    = W2P_ORDER_STATUS_HOOK[ $status ];

				$hook_obj = w2p_get_hook( $key, W2P_CATEGORY['deal'], $order_id );

				if ( $hook_obj ) {
					$formated_payload = $hook_obj->w2p_get_formated_hook();

					$order_data    = $order->get_data();
					$order_queries = $order_data['queries'];

					if ( isset( $order_queries[0] ) ) {
						$query_payload = $order_queries[0]['payload'];
						unset( $query_payload['data'] );

						if ( w2p_compare_arrays( $query_payload, $formated_payload ) ) {
							$skip_next_query = true;
							if ( 'DONE' !== $order_queries[0]['state'] ) {
								$query = new W2P_Query( $order_queries[0]['id'] );
								$query->send( true );
							}
						}
					}

					if ( ! $skip_next_query ) {
						$query_obj = W2P_Query::create_query(
							$formated_payload['category'],
							$formated_payload['source'],
							$formated_payload['source_id'],
							"Manual ($formated_payload[label])",
							$formated_payload
						);

						$send_infos = $query_obj->send( true );

						if ( $send_infos['success'] ) {
							w2p_incremente_additional_data( 'total_order_done' );
						} else {
							w2p_incremente_additional_data( 'total_order_errors' );
						}
					} else {
						w2p_incremente_additional_data( 'total_order_uptodate' );
					}
				}

				$progress = intval( ( $index + 1 ) / $total_orders * 100 );
				update_option( 'w2p_sync_progress_orders', $progress );

				w2p_update_additional_data( 'current_order', $order_id );
				w2p_update_additional_data( 'current_order_index', $index + 1 );
			}

			update_option( 'w2p_sync_running', false );
			update_option( 'w2p_last_sync', gmdate( 'Y-m-d\TH:i:s\Z' ) );

			wp_clear_scheduled_hook( 'w2p_cron_check_sync' );

			return new WP_REST_Response(
				array(
					'success' => true,
					'message' => 'Data synced',
				),
				200
			);
		} else {

			$w2p_sync_progress_users   = get_option( 'w2p_sync_progress_users', 0 );
			$w2p_sync_progress_orders  = get_option( 'w2p_sync_progress_orders', 0 );
			$w2p_sync_additional_datas = get_option( 'w2p_sync_additional_datas', array() );

			return new WP_REST_Response(
				array(
					'message'                   => 'synchronization already in progress',
					'running'                   => w2p_is_sync_running(),
					'sync_progress_users'       => $w2p_sync_progress_users,
					'sync_progress_orders'      => $w2p_sync_progress_orders,
					'w2p_sync_additional_datas' => $w2p_sync_additional_datas,
				),
				200
			);
		}
	} catch ( \Throwable $e ) {
		update_option( 'w2p_sync_running', false );
		wp_clear_scheduled_hook( 'w2p_cron_check_sync' );
		w2p_add_error_log( 'ERROR : ' . $e->__toString(), 'w2p_run_sync' );
		update_option( 'w2p_sync_last_error', 'An error occurred during the synchronization. Please try again later. If the issue persists, you may want to contact support for assistance.' );

		return new WP_REST_Response(
			array(
				'success'   => false,
				'error'     => $e->getMessage(),
				'traceback' => $e->__toString(),
			),
			500
		);
	}
}
