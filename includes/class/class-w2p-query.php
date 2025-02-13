<?php
/**
 * Handles query operations for W2P.
 *
 * This file defines the W2P_Query class, which provides methods for
 * managing and retrieving field-related data within the W2P plugin.
 *
 * @package W2P
 * @since 1.0.0
 */

/**
 * Class W2P_Query
 *
 * Handles query-related operations in the W2P plugin.
 *
 * @package W2P
 * @since 1.0.0
 */
class W2P_Query {

	use W2P_SetterTrait;
	use W2P_Formater;

	/**
	 * Stores the query data for the W2P plugin.
	 * The data is stored as an associative array with the following keys:
	 *
	 * @var array{
	 *     id: int,
	 *     target_id: int|null,
	 *     source: string,
	 *     source_id: int,
	 *     category: string,
	 *     method: string,
	 *     hook: string,
	 *     payload: array,
	 *     state: string,
	 *     pipedrive_response: array,
	 *     additional_data: array
	 * }
	 */
	private $data = array(
		'id'                 => 0,
		'category'           => '',
		'target_id'          => 0,
		'hook'               => '',
		'method'             => '',
		'payload'            => array(),
		'state'              => '',
		'source_id'          => 0,
		'pipedrive_response' => array(),
		'additional_data'    => array(),
		'source'             => '',
		'user_id'            => 0,
	);

	/**
	 * The name of the database table associated with the object.
	 *
	 * @var string
	 */
	private $db_name;

	/**
	 * Indicates whether the object represents a new instance.
	 *
	 * If true, the object is a new instance and has not been loaded from the database.
	 * If false, the object has been loaded from the database.
	 *
	 * @var bool
	 */
	public $new_instance;

	/**
	 * A list of available states for the object.
	 *
	 * These states represent the various lifecycle stages or statuses
	 * that the object can have during its operation.
	 *
	 * @var array
	 */
	public static $avaible_state = array(
		'CANCELED',
		'INVALID',
		'TODO',
		'SENDED',
		'ERROR',
		'DONE',
	);

	/**
	 * Constructor for the W2P_Query class.
	 *
	 * Initializes the database name with the appropriate table prefix.
	 * If a valid ID is provided, it loads the corresponding object data from the database.
	 *
	 * @param int $id Optional. The ID of the query to load. Default is 0.
	 */
	public function __construct( int $id = 0 ) {
		global $wpdb;
		$this->db_name = $wpdb->prefix . 'w2p_query';
		if ( $id > 0 ) {
			$this->load_object_from_DB( $id );
		}
	}

	/**
	 * Determines if the query data is savable to the database.
	 *
	 * For a query to be savable, it must have a category and source ID.
	 *
	 * @return bool True if the query can be saved, false otherwise.
	 */
	private function is_savable(): bool {
		return $this->data['category']
			&& $this->data['source_id'];
	}

	/**
	 * Retrieves and prepares the query data for processing.
	 *
	 * This method compiles and returns an array containing various details about the query,
	 * including payload data, validation state, additional data (such as the last error),
	 * query state, sendability, target ID, user ID, and method to be used.
	 *
	 * @return array The prepared query data with all associated details.
	 */
	public function get_data(): array {
		$target_id = $this->get_pipedrive_target_id();

		$data                                  = $this->data;
		$data['payload']['data']               = $this->get_payload_data();
		$data['is_valid']                      = $this->is_valid();
		$data['additional_data']               = $this->getter( 'additional_data' );
		$data['additional_data']['last_error'] = $this->get_last_error();
		$data['state']                         = $this->get_state( $data );
		$data['can_be_sent']                   = $this->can_be_sent( $data['state'] );
		$data['target_id']                     = $target_id;
		$data['user_id']                       = $this->get_user_id();
		$data['method']                        = $this->get_method();
		return $data;
	}

	/**
	 * Retrieves the user ID associated with the query.
	 *
	 * If the query's source is a user, the user ID is directly retrieved from the source ID.
	 * If the query's source is an order, the user ID is retrieved using the Pipedrive API.
	 *
	 * @return int|null The user ID or null if not applicable.
	 */
	private function get_user_id(): ?int {
		$user_id = null;

		if ( W2P_HOOK_SOURCES['user'] === $this->data['source'] ) {
			$user_id = (int) $this->data['source_id'];
		} elseif ( W2P_HOOK_SOURCES['order'] === $this->data['source'] ) {
			$user_id = w2p_get_customer_id_from_order_id( $this->data['source_id'] );
		}

		$this->data['user_id'] = $user_id;
		return $user_id;
	}

	/**
	 * Retrieves user data.
	 *
	 * Fetches detailed information about a user based on their ID.
	 *
	 * @return array|null An associative array containing user details such as ID, login,
	 *                    email, nicename, display name, and user meta, or null if the user
	 *                    does not exist.
	 */
	public function get_user_data(): ?array {
		$user_data = null;

		$user_id = $this->get_user_id();

		$user = get_user_by( 'id', $user_id );

		if ( $user ) {
			$user_data = array(
				'ID'            => $user->ID,
				'user_login'    => $user->user_login,
				'user_email'    => $user->user_email,
				'user_nicename' => $user->user_nicename,
				'display_name'  => $user->display_name,
				'user_meta'     => get_user_meta( $user_id ),
			);
		}
		return $user_data;
	}


	/**
	 * Destructor for the class.
	 *
	 * Ensures that if the object is savable, it is saved to the database before destruction.
	 */
	public function __destruct() {
		if ( $this->is_savable() ) {
			$this->save_to_database();
		}
	}

	/**
	 * Retrieves queries from the database based on various filters.
	 *
	 * @param bool   $data     Whether to return the full data of each query or just the query object.
	 * @param array  $filters  Associative array of filters:
	 *                         - 'state'
	 *                         (string|array|null): A single
	 *                         state or an array of states
	 *                         to filter by (uses OR for
	 *                         multiple states). - 'method'
	 *                         (string|null): The HTTP
	 *                         method (POST, PUT). - 'hook'
	 *                         (string|null): The hook name.
	 *                         - 'category' (string|null):
	 *                         The category of the query. -
	 *                         'source_id' (int|null): The
	 *                         source ID to filter by. -
	 *                         'source' (string|null): The
	 *                         source (e.g., product, user,
	 *                         order). - 'target_id'
	 *                         (int|null): The target ID. -
	 *                         'user_id' (int|null): The
	 *                         user ID.
	 *
	 * @param  int    $page     The page number for pagination.
	 * @param  int    $per_page The number of results per page. Set to -1 for no pagination.
	 * @param  string $order    The order of the results. Use 'DESC' for descending (default) or 'ASC' for ascending.
	 * @return array Array containing the filtered query data and pagination info.
	 */
	public static function get_queries(
		bool $data = true,
		array $filters = array(),
		int $page = 1,
		int $per_page = 10,
		string $order = 'DESC',
	): array {
		try {
			global $wpdb;
			$db_name = $wpdb->prefix . 'w2p_query';

			$query_count = "SELECT COUNT(*) FROM $db_name WHERE 1=1";
			$query_data  = "SELECT id FROM $db_name WHERE 1=1";
			$params      = array();

			if ( ! empty( $filters['state'] ) ) {
				if ( is_array( $filters['state'] ) ) {
					$placeholders = implode( ', ', array_fill( 0, count( $filters['state'] ), '%s' ) );
					$query_count .= " AND `state` IN ($placeholders)";
					$query_data  .= " AND `state` IN ($placeholders)";
					$params       = array_merge( $params, $filters['state'] );
				} else {
					$query_count .= ' AND `state` = %s';
					$query_data  .= ' AND `state` = %s';
					$params[]     = $filters['state'];
				}
			}

			foreach ( array( 'method', 'hook', 'category', 'source_id', 'source', 'target_id', 'user_id' ) as $filter ) {
				if ( ! empty( $filters[ $filter ] ) ) {
					$query_count .= " AND `$filter` = %s";
					$query_data  .= " AND `$filter` = %s";
					$params[]     = $filters[ $filter ];
				}
			}

			if ( -1 !== $per_page ) {
				$total_items = $wpdb->get_var( $wpdb->prepare( $query_count, $params ) );
				$offset      = ( $page - 1 ) * $per_page;
				$query_data .= " ORDER BY id $order LIMIT %d, %d";
				$params[]    = $offset;
				$params[]    = $per_page;
			} else {
				$total_items = $wpdb->get_var( $wpdb->prepare( $query_count, $params ) );
				$query_data .= " ORDER BY id $order";
			}

			$query_data  = $wpdb->prepare( $query_data, $params );
			$results     = $wpdb->get_results( $query_data );
			$ids         = wp_list_pluck( $results, 'id' );
			$w2p_queries = array();

			foreach ( $ids as $id ) {
				$data
					? $w2p_queries[] = ( new W2P_Query( $id ) )->get_data()
					: $w2p_queries[] = new W2P_Query( $id );
			}

			$total_pages   = -1 !== $per_page ? ceil( $total_items / $per_page ) : 1;
			$has_next_page = $page < $total_pages;

			return array(
				'data'       => $w2p_queries,
				'pagination' => array(
					'total_items'   => $total_items,
					'total_pages'   => $total_pages,
					'has_next_page' => $has_next_page,
				),
				'error'      => null,
			);
		} catch ( \Throwable $e ) {
			return array(
				'data'       => array(),
				'pagination' => array(
					'total_items'   => 0,
					'total_pages'   => 0,
					'has_next_page' => false,
				),
				'error'      => $e->getMessage(),
			);
		}
	}

	/**
	 * Creates a new W2P query with the specified parameters and inserts it into the database.
	 *
	 * @param string $category The category of the query.
	 * @param string $source The source of the query.
	 * @param int    $source_id The source ID associated with the query.
	 * @param string $hook The hook associated with the query.
	 * @param array  $payload Additional data for the query (optional).
	 *
	 * @return W2P_Query|bool The created W2P_Query object on success, or false on failure.
	 */
	public static function create_query(
		string $category,
		string $source,
		int $source_id,
		string $hook,
		array $payload = array(),
	): W2P_Query|bool {
		$w2p_query = new W2P_Query();
		$w2p_query->setter( 'category', $category );
		$w2p_query->setter( 'source', $source );
		$w2p_query->setter( 'source_id', $source_id );
		$w2p_query->setter( 'hook', $hook );
		$w2p_query->setter( 'payload', $payload );
		$w2p_query->setter( 'state', 'TODO' );

		$w2p_query->update_additionnal_data( 'created_at', gmdate( 'Y-m-d\TH:i:s\Z' ) );

		global $wpdb;
		$wpdb->insert( $w2p_query->db_name, $w2p_query->format_object_for_DB() );
		if ( $wpdb->last_error ) {
			return false;
		} else {
			$id = $wpdb->insert_id;
			$w2p_query->setter( 'id', $id );
			$w2p_query->cancel_previous_query();
			return $w2p_query;
		}
	}

	/**
	 * Updates an additional data field for the query.
	 *
	 * This method updates the value of a specified key in the 'additional_data'
	 * attribute of the query object. If the key does not exist, it will be added.
	 *
	 * @param string $key   The key to update or add in the additional data.
	 * @param mixed  $value The value to set for the specified key.
	 * @return void
	 */
	public function update_additionnal_data( $key, $value ): void {
		$additional_data         = $this->getter( 'additional_data' );
		$additional_data[ $key ] = $value;
		$this->setter( 'additional_data', $additional_data );
	}

	/**
	 * Determines if a query can be sent based on its current state.
	 *
	 * Checks the provided state and returns true if the state is not 'INVALID',
	 * 'CANCELED', or 'SENDED', indicating that the query is eligible to be sent.
	 *
	 * @param string $state The current state of the query.
	 * @return bool True if the query can be sent, false otherwise.
	 */
	private function can_be_sent( $state ) {
		return 'INVALID' !== $state && 'CANCELED' !== $state && 'SENDED' !== $state;
	}

	/**
	 * Cancels the current query and saves it to the database.
	 *
	 * @return bool True if the query was successfully canceled, false otherwise.
	 */
	public function cancel() {
		$this->data['state'] = 'CANCELED';
		$this->save_to_database();
		return true;
	}
	/**
	 * Formats the current object for sending to W2P.
	 *
	 * Returns an array with the following structure:
	 * - query: The current object's data.
	 * - user_data: Additional user data.
	 * - pipedrive_parameters: An array with the Pipedrive domain and API key.
	 * - w2p_parameters: An array with parameters for the W2P plugin.
	 *
	 * @return array The formatted data.
	 */
	public function data_for_w2p() {
		$parameters = w2p_get_parameters();
		return array(
			'query'                => $this->get_data(),
			'user_data'            => $this->get_user_data(),
			'pipedrive_parameters' => array(
				'domain'  => w2p_get_pipedrive_domain(),
				'api_key' => w2p_get_pipedrive_api_key(),
			),
			'w2p_parameters'       => $parameters['w2p'],
		);
	}

	/**
	 * Increments the error count for the current object and handles cancellation if a threshold is reached.
	 *
	 * This function retrieves the current error count from the `additional_data` field, increments it by one,
	 * and updates the `additional_data` field. If the total error count exceeds or equals 5, the query is
	 * cancelled, and a traceback message is added to provide context.
	 *
	 * @return void
	 */
	private function increment_error() {

		$current_additional_data = $this->getter( 'additional_data' );

		$total_error = isset( $current_additional_data['total_error'] )
			? (int) $current_additional_data['total_error']
			: 0;

		$this->update_additionnal_data( 'total_error', (int) ( $total_error + 1 ) );

		if ( (int) ( $total_error + 1 ) >= 5 ) {
			$this->add_traceback(
				'Checking query',
				false,
				'Your request encountered too many errors and needs to be cancelled. You may want to check your settings'
			);
			$this->cancel();
		}
	}

	/**
	 * Sends the query to the remote server and handles the response.
	 *
	 * This method validates the query, sends it to the W2P server, and processes
	 * the response. It includes error handling, updates tracebacks, and manages
	 * additional data related to the query state. If `direct_to_pipedrive` is true,
	 * it processes the response directly from Pipedrive.
	 *
	 * @param bool $direct_to_pipedrive Whether to send the query directly to Pipedrive (default: false).
	 * @return array An array containing the result of the query, including success status, message,
	 *               response data, and additional tracebacks or errors.
	 */
	public function send( bool $direct_to_pipedrive = false ): array {

		$this->resset_traceback();
		$this->update_additionnal_data( 'sended_at', null );

		$state    = $this->get_state( $this->data );
		$is_valid = $this->is_valid();

		if ( ! $this->get_id() || ! $this->can_be_sent( $state ) || ! $is_valid ) {
			$this->add_traceback(
				'Sending query from your server',
				false,
				'The query is not valid',
				array(
					'get_id'      => $this->get_id(),
					'state'       => $state,
					'can_be_sent' => $this->can_be_sent( $state ),
					'is_valid'    => $is_valid,
				)
			);
			$this->increment_error();
			$this->save_to_database();
			return array(
				'success' => false,
				'data'    => null,
				'message' => 'This query is not valid.',
			);
		}

		$this->add_traceback(
			'Sending query from your server',
			true,
			'The query is ready to be sent'
		);

		$response = w2p_http_request(
			W2P_DISTANT_REST_URL . '/query',
			'POST',
			array(
				'user_query_id'       => $this->get_id(),
				'direct_to_pipedrive' => $direct_to_pipedrive,
				'api_key'             => w2p_get_api_key(),
				'domain'              => w2p_get_api_domain( true ),
				'user_query'          => $this->data_for_w2p(),
			)
		);

		$this->update_additionnal_data( 'sended_at', gmdate( 'Y-m-d\TH:i:s\Z' ) );

		if ( 201 !== $response['status_code'] && 200 !== $response['status_code'] ) {
			$message = isset( $response['data']['message'] )
				? $response['data']['message']
				: ( 404 === $response['status_code'] || 503 === $response['status_code']
					? 'Servers are down for maintenance. Apologies for the inconvenience'
					: ( 404 === $response['status_code']
						? 'Request timed out, please try again later.'
						: ( isset( $response['error'] )
							? $message = $response['error']
							: 'Unknown error' ) ) );

			$this->add_traceback(
				'Processing the request on our servers',
				false,
				$message,
			);
			$this->get_data();
			$this->increment_error();
			$this->save_to_database();
			return array(
				'message'             => $message,
				'data'                => null,
				'post_query_response' => $response,
				'success'             => false,
			);
		}

		if ( $direct_to_pipedrive ) {

			$pipedrive_response = isset( $response['data']['data']['pipedrive_response'] )
				? $response['data']['data']['pipedrive_response']
				: null;

			$traceback = isset( $response['data']['data']['Traceback'] )
				? $response['data']['data']['Traceback']
				: null;

			if ( isset( $response['data']['data']['method'] ) ) {
				$this->setter( 'method', $response['data']['data']['method'] );
			}

			if ( $traceback && is_array( $traceback ) ) {
				foreach ( $traceback as $event ) {
					if ( isset( $event['step'] ) && isset( $event['success'] ) ) {
						$date = isset( $event['createdAt'] ) ? gmdate( 'Y-m-d\TH:i:s\Z', strtotime( $event['createdAt'] ) ) : null;

						$this->add_traceback(
							$event['step'],
							$event['success'],
							isset( $event['value'] ) ? $event['value'] : '', // Oui c'est value et pas message, erreur de ma part flemme de changer.
							isset( $event['data'] ) ? $event['data'] : '',
							false,
							$date,
						);
					}
				}
			}

			if ( isset( $pipedrive_response['id'] ) ) {
				$this->setter( 'target_id', $pipedrive_response['id'] );
				$this->update_source_target_id( $pipedrive_response['id'] );
				$this->cancel_previous_query();
			} else {
				$this->increment_error();
				$this->save_to_database();
			}

			$this->update_additionnal_data( 'responded_at', gmdate( 'Y-m-d\TH:i:s\Z' ) );
			$this->setter( 'pipedrive_response', $pipedrive_response );
		}

		$this->get_data();
		$this->save_to_database();

		return array(
			'success'            => $response['data']["success"],
			'message'            => $response['data']["message"] ?? 'Query sended',
			'data'               => $response['data'],
			'pipedrive_response' => isset( $pipedrive_response ) ? $pipedrive_response : null,
			'traceback'          => isset( $traceback ) ? $traceback : null,
			'target_id'          => $this->get_pipedrive_target_id(),
		);
	}

	/**
	 * Cancel previous queries with the same category, source ID, target ID and hook.
	 *
	 * @since 1.0.0
	 */
	public function cancel_previous_query() {
		$queries = self::get_queries(
			false,
			array(
				'state'     => array( 'TODO', 'ERROR' ),
				'category'  => $this->data['category'],
				'source_id' => $this->data['source_id'],
				'target_id' => $this->data['target_id'],
				'hook'      => $this->data['hook'],
			),
			1,
			-1
		)['data'];

		foreach ( $queries as $query ) {
			// On annule évidement pas les requêtes plus récentes.
			if ( $query->get_id() < $this->get_id() ) {
				$query->add_traceback(
					'Request Cancellation',
					false,
					'Your request has been canceled because a more recent request has already been created or sent to Pipedrive.'
				);
				$query->cancel();
			}
		}
	}

	/**
	 * Retrieves and formats the payload data.
	 *
	 * This method processes the payload data from the object, applies any field-specific
	 * conditions and logic blocks, and combines it with default payload data. It ensures
	 * that the values meet the required conditions before being added to the final payload.
	 *
	 * @return array The formatted payload data, including fields, their values, and associated metadata.
	 */
	public function get_payload_data(): array {
		$formatted_payload = (array) $this->data['payload'];

		$data = $this->get_default_payload_data();

		if ( isset( $formatted_payload['fields'] ) && is_array( $formatted_payload['fields'] ) ) {
			foreach ( $formatted_payload['fields'] as $field ) {
				if ( $field ) {

					$field_obj       = new W2P_Field( $field );
					$pipedrive_field = $field_obj->get_field($this->data["category"]);
					$data_key        = $pipedrive_field['key'];
					$values          = $field['values'];

					if ( $values && count( $values ) ) {
						$value_to_add = null;

						// Si la condition est désactivée, garder la première valeur.
						if ( ! $field['condition'] || ( isset( $field['condition']['logicBlock']['enabled'] ) && ! $field['condition']['logicBlock']['enabled'] ) ) {
							$value_to_add = $values[0];
						} elseif (
							isset( $field['condition']['logicBlock']['fieldNumber'] )
							&& 'ALL' === $field['condition']['logicBlock']['fieldNumber']
						) {

							foreach ( $values as $value_set ) {

								$filtered_values = array_filter(
									$value_set,
									function ( $value ) {
										return '' !== $value;
									}
								);

								if ( count( $filtered_values ) === count( $value_set ) ) {
									$value_to_add = $value_set;
									break;
								}
							}
						} elseif (
							isset( $field['condition']['logicBlock']['fieldNumber'] )
							&& '1' === $field['condition']['logicBlock']['fieldNumber']
						) {
							foreach ( $values as $value_set ) {
								$filtered_values = array_filter(
									$value_set,
									function ( $value ) {
										return '' !== $value;
									}
								);

								if ( count( $filtered_values ) >= 1 ) {
									$value_to_add = $value_set;
									break;
								}
							}
						}

						if ( null !== $value_to_add ) {
							if ( is_array( $value_to_add ) ) {
								$filtered_values = array_filter(
									$value_to_add,
									function ( $value ) {
										return '' !== $value;
									}
								);
								$value_to_add    = implode( ' ', $filtered_values );
							}

							if ( null !== $value_to_add && '' !== trim( $value_to_add ) && '' !== $data_key ) {
								$found = false;
								foreach ( $data as &$item ) {
									if ( strtolower( $data_key ) === $item['key'] ) {
										$item  = array(
											'key'          => strtolower( $data_key ),
											'name'         => $pipedrive_field['name'],
											'value'        => $value_to_add,
											'condition'    => isset( $field['condition'] ) ? $field['condition'] : null,
											'pipedriveFieldId' => isset( $field['pipedriveFieldId'] ) ? $field['pipedriveFieldId'] : null,
											'isLogicBlock' => $field['isLogicBlock'],
										);
										$found = true;
										break;
									}
								}
								if ( ! $found ) {
									$data[] = array(
										'key'              => strtolower( $data_key ),
										'name'             => $pipedrive_field['name'],
										'value'            => $value_to_add,
										'condition'        => isset( $field['condition'] ) ? $field['condition'] : null,
										'pipedriveFieldId' => isset( $field['pipedriveFieldId'] ) ? $field['pipedriveFieldId'] : null,
										'isLogicBlock'     => $field['isLogicBlock'],
									);
								}
							}
						}
					}
				}
			}
		}

		return $data;
	}

	/**
	 * Retrieves the default payload data based on the object's category.
	 *
	 * This method constructs an array of default payload data, including fields
	 * like `name`, `org_id`, `title`, `person_id`, and others based on the object's
	 * category (`person` or `deal`). It integrates parameters and metadata to
	 * create a structured payload with default values and conditions.
	 *
	 * @return array The default payload data, formatted for inclusion in the final payload.
	 */
	private function get_default_payload_data(): array {
		$data = array();

		$parameters = w2p_get_parameters();

		if ( 'person' === $this->data['category'] ) {
			if ( $parameters['w2p']['person']['defaultEmailAsName'] ) {
				$user_data = $this->get_user_data();
				if ( $user_data ) {
					$data[] = array(
						'key'              => 'name',
						'name'             => 'Name',
						'value'            => $user_data['user_email'],
						'condition'        => array(
							'logicBlock'      => array(
								'enabled'     => false,
								'fieldNumber' => '1',
							),
							'SkipOnExist'     => true,
							'findInPipedrive' => true,
						),
						'isLogicBlock'     => false,
						'pipedriveFieldId' => 0,
					);
				}
			}

			$user_id  = $this->get_user_id();
			$meta_key = w2p_get_meta_key( W2P_CATEGORY['organization'], 'id' );
			$org_id   = get_user_meta( $user_id, $meta_key, true );
			if( ! $org_id ) {
				$org_id = get_user_meta($user_id, "W2P_organization_id", true);
			}

			if ( $org_id ) {
				$data[] = array(
					'key'              => 'org_id',
					'name'             => 'Organization id',
					'value'            => $org_id,
					'condition'        => array(
						'logicBlock'      => array(
							'enabled'     => false,
							'fieldNumber' => '1',
						),
						'SkipOnExist'     => false,
						'findInPipedrive' => false,
					),
					'isLogicBlock'     => false,
					'pipedriveFieldId' => 0,
				);
			}
		} elseif ( 'deal' === $this->data['category'] ) {
			if (
				isset( $parameters['w2p']['deal']['defaultOrderName']['variables'] )
				&& is_array( $parameters['w2p']['deal']['defaultOrderName']['variables'] )
			) {
				$variables = $parameters['w2p']['deal']['defaultOrderName']['variables'];
				$user_id   = $this->get_user_id();

				$value_to_add = w2p_format_variables( $variables, $this->data['source_id'], $user_id, false );

				$data[] = array(
					'key'              => 'title',
					'name'             => 'Title',
					'value'            => $value_to_add,
					'condition'        => array(
						'logicBlock'      => array(
							'enabled'     => false,
							'fieldNumber' => '1',
						),
						'SkipOnExist'     => true,
						'findInPipedrive' => true,
					),
					'isLogicBlock'     => true,
					'pipedriveFieldId' => 0,
				);

				$user_data = $this->get_user_data();
				if ( $user_data ) {

					$meta_key = w2p_get_meta_key( W2P_CATEGORY['organization'], 'id' );

					$org_id = isset( $user_data['user_meta'][ $meta_key ][0] )
						? $user_data['user_meta'][ $meta_key ][0]
						: null;

					if( ! $org_id ) {
						$org_id = get_user_meta($user_id, "W2P_organization_id", true);
					}

					if ( $org_id ) {
						$data[] = array(
							'key'              => 'org_id',
							'name'             => 'Organization id',
							'value'            => $org_id,
							'condition'        => array(
								'logicBlock'      => array(
									'enabled'     => false,
									'fieldNumber' => '1',
								),
								'SkipOnExist'     => false,
								'findInPipedrive' => false,
							),
							'isLogicBlock'     => false,
							'pipedriveFieldId' => 0,
						);
					}

					$meta_key = w2p_get_meta_key( W2P_CATEGORY['person'], 'id' );

					$person_id = isset( $user_data['user_meta'][ $meta_key ][0] )
						? $user_data['user_meta'][ $meta_key ][0]
						: null;

					if(! $person_id ) {
						$person_id = get_user_meta($user_id, "W2P_person_id", true);
					}

					if ( $person_id ) {
						$data[] = array(
							'key'              => 'person_id',
							'name'             => 'Person id',
							'value'            => $person_id,
							'condition'        => array(
								'logicBlock'      => array(
									'enabled'     => false,
									'fieldNumber' => '1',
								),
								'SkipOnExist'     => false,
								'findInPipedrive' => false,
							),
							'isLogicBlock'     => false,
							'pipedriveFieldId' => 0,
						);
					}
				}
			}
		}

		return $data;
	}

	/**
	 * Validates the current object for processing.
	 *
	 * This method checks whether the object has the required data to process the
	 * request. Specifically, for POST requests, it ensures the payload contains
	 * the necessary fields defined in `W2P_REQUIRED_FIELDS` for the object's
	 * category. If validation fails, an appropriate traceback message is added.
	 *
	 * @return bool Returns `true` if the object is valid, otherwise `false`.
	 */
	public function is_valid() {
		if ( $this->get_method() === 'POST' ) {
			$data = $this->get_payload_data();

			if ( ! count( $data ) ) {
				$this->add_traceback( 'Processing data', false, 'No data available for this request.' );
				return false;
			}

			$searched_key = W2P_REQUIRED_FIELDS[ $this->data['category'] ];

			foreach ( $searched_key as $search_key ) {
				$found_item = array_filter(
					$data,
					function ( $item ) use ( $search_key ) {
						return isset( $item['key'] ) && $item['key'] === $search_key;
					}
				);

				if ( empty( $found_item ) ) {
					$this->add_traceback(
						'Processing data',
						false,
						"You need at least a $search_key"
							. ' to create this ' . $this->data['category'] . '.'
					);
					return false;
				}
			}
		}
		return true;
	}

	/**
	 * Retrieves the HTTP method to be used for the request.
	 *
	 * This method determines the HTTP method (`POST` or `PUT`) based on the presence
	 * of a Pipedrive target ID. If a target ID exists, it returns `PUT`, otherwise `POST`.
	 * The determined method is stored in the object's data array to avoid recalculating it.
	 *
	 * @return string The HTTP method (`POST` or `PUT`) to be used for the request.
	 */
	private function get_method(): string {

		if ( $this->data['method'] ) {
			return $this->data['method'];
		}

		$target_id            = $this->get_pipedrive_target_id();
		$method               = $target_id ? 'PUT' : 'POST';
		$this->data['method'] = $method;
		return $method;
	}

	/**
	 * Retrieves the state of the query.
	 *
	 * The state can be any of the following:
	 *  - TODO: The query has not been sent yet.
	 *  - SENDED: The query has been sent and is waiting for a response.
	 *  - DONE: The query has been successfully processed.
	 *  - ERROR: The query has failed.
	 *  - INVALID: The query is invalid and cannot be processed.
	 *  - CANCELED: The query has been canceled.
	 *
	 * The state is determined by the following rules:
	 *  - If the query has been canceled, the state is CANCELED.
	 *  - If the query is invalid, the state is INVALID.
	 *  - If the query has failed, the state is ERROR.
	 *  - If the query has been successfully processed, the state is DONE.
	 *  - If the query has been sent and is waiting for a response, the state is SENDED.
	 *  - Otherwise, the state is TODO.
	 *
	 * @param array $data The query data.
	 * @return string The state of the query.
	 */
	public function get_state( $data ): string {
		if ( 'CANCELED' === $this->data['state'] ) {
			return $this->data['state'];
		}

		if ( ( isset( $data['is_valid'] ) && ! $data['is_valid'] ) || ! $this->is_valid() ) {
			$this->data['state'] = 'INVALID';
			return 'INVALID';
		}

		if ( $this->get_last_error() ) {
			$this->data['state'] = 'ERROR';
			return 'ERROR';
		}

		$pipedrive_response = $this->data['pipedrive_response'];
		if ( count( $pipedrive_response ) ) {
			if ( isset( $pipedrive_response['id'] ) && $pipedrive_response['id'] ) {
				$this->data['state'] = 'DONE';
				return 'DONE';
			}
		}

		if ( isset( $data['additional_data']['sended_at'] ) && $data['additional_data']['sended_at'] ) {
			$this->data['state'] = 'SENDED';
			return 'SENDED';
		}
		$this->data['state'] = 'TODO';
		return 'TODO';
	}

	/**
	 * Update the target ID for the source in WordPress.
	 *
	 * Updates the user meta or the order meta depending on the category and source.
	 *
	 * @param int $target_id The target ID to update.
	 */
	public function update_source_target_id( $target_id ) {
		$source_id = $this->getter( 'source_id' );
		$user_id   = $this->get_user_id();
		$meta_key  = w2p_get_meta_key( W2P_CATEGORY[ $this->data['category'] ], 'id' );

		$parameters = w2p_get_parameters();

		switch ( $this->getter( 'category' ) ) {
			case W2P_CATEGORY['person']:
				// Bien que la source puissent être une commande ou un produit, c'est bien l'utilisateur que l'on souhaite mettre à jour vu que la catégory est une person ou une organsation.
				update_user_meta( $user_id, $meta_key, $target_id );

				if (
					$parameters['w2p']['person']['linkToOrga']
					&& isset( $this->data['pipedrive_response']['org_id']['value'] )
				) {
					$meta_key = w2p_get_meta_key( W2P_CATEGORY['organization'], 'id' );
					update_user_meta( $user_id, $meta_key, $this->data['pipedrive_response']['org_id']['value'] );
				}
				break;

			case W2P_CATEGORY['organization']:
				update_user_meta( $user_id, $meta_key, $target_id );
				break;

			case W2P_CATEGORY['deal']:
				// Il n'y à que des hook de source 'order' pour la catégorie deal.
				// Source_id est donc forcément une commande.
				update_post_meta( $source_id, $meta_key, $target_id );
				break;
		}
	}

	/**
	 * Retrieves the last error message from the query traceback.
	 *
	 * This method examines the `traceback` from `additional_data` in reverse order.
	 * It returns the message of the first trace that indicates a failure, or `null`
	 * if no such trace is found.
	 *
	 * @return string|null The last error message or `null` if no error exists.
	 */
	public function get_last_error(): ?string {
		$last_error      = null;
		$additional_data = $this->getter( 'additional_data' );

		$traceback = isset( $additional_data['traceback'] )
			? $additional_data['traceback']
			: array();

		foreach ( array_reverse( $traceback ) as $trace ) {
			if ( isset( $trace['success'] ) && false === $trace['success'] ) {
				$last_error = $trace['message'];
				break;
			}
		}

		return $last_error;
	}

	/**
	 * Retrieves the Pipedrive target ID from user meta or from an order's meta field.
	 *
	 * This method determines the target ID for a Pipedrive entity (person, organization, or deal)
	 * based on the category and source provided in the hook parameters. It fetches the target ID
	 * either from user metadata or from an order's meta field.
	 *
	 * @return int|null The target ID if found, or null if not available.
	 */
	public function get_pipedrive_target_id(): ?int {
		if ( $this->data['target_id'] ) {
			return $this->data['target_id'];
		}

		$target_id = null;
		$meta_key  = w2p_get_meta_key( $this->data['category'], 'id' );

		if ( ! (int) $this->data['source_id'] ) {
			$this->data['target_id'] = null;
			return $target_id;
		}

		if ( ( W2P_CATEGORY['person'] === $this->data['category']
				|| W2P_CATEGORY['organization'] === $this->data['category'] )
			&& (int) $this->get_user_id()
		) {
			$user = new W2P_User( (int) $this->get_user_id() );
			if ( $user ) {
				$target_id = $user->get( $meta_key, 'id' );
			}
		} elseif (
			W2P_CATEGORY['deal'] === $this->data['category']
			&& 'order' === $this->data['source']
		) {
			$target_id = get_post_meta( $this->data['source_id'], $meta_key, 'id' );
		}

		$this->data['target_id'] = (int) $target_id ? (int) $target_id : null;
		return (int) $target_id ? (int) $target_id : null;
	}

	/**
	 * Resets the traceback data for the current query.
	 *
	 * This function updates the 'traceback' field in the additional data
	 * to an empty array, effectively clearing any previous tracebacks.
	 *
	 * @return void
	 */
	public function resset_traceback() {
		$this->update_additionnal_data( 'traceback', array() );
	}

	/**
	 * Adds a new traceback entry to the query's traceback data.
	 *
	 * Tracebacks are used to store a history of events that occurred during the execution of the query.
	 * This function adds a new entry to the traceback data, which can be used to debug the query.
	 *
	 * @param string $step The step that was executed.
	 * @param bool   $success Whether the step was successful or not.
	 * @param string $message A message to be associated with the traceback entry. Defaults to an empty string.
	 * @param mixed  $additional_data Additional data to be associated with the traceback entry. Defaults to null.
	 * @param bool   $internal Whether the traceback entry is an internal step or not. Defaults to true.
	 * @param string $date The date of the traceback entry. Defaults to the current date and time.
	 *
	 * @return void
	 */
	public function add_traceback( string $step, bool $success, string $message = '', $additional_data = null, $internal = true, $date = null ) {
		$current_additional_data = $this->getter( 'additional_data' );

		$traceback = isset( $current_additional_data['traceback'] )
			? $current_additional_data['traceback']
			: array();

		$found = false;

		foreach ( $traceback as &$existing_traceback ) {
			if ( $existing_traceback['step'] === $step ) {
				$existing_traceback = array(
					'date'            => $date ?? gmdate( 'Y-m-d\TH:i:s\Z' ),
					'step'            => $step,
					'success'         => $success,
					'message'         => $message,
					'additional_data' => $additional_data,
					'internal'        => $internal,
				);
				$found              = true;
				break;
			}
		}

		if ( ! $found ) {
			$traceback[] = array(
				'date'            => $date ?? gmdate( 'Y-m-d\TH:i:s\Z' ),
				'step'            => $step,
				'success'         => $success,
				'message'         => $message,
				'additional_data' => $additional_data,
				'internal'        => $internal,
			);
		}
		$this->update_additionnal_data( 'traceback', $traceback );
	}
}
