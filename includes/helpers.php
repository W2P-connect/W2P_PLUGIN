<?php
/**
 * Helpers for W2P.
 *
 * This file contains helper functions for the W2P plugin.
 * These functions are used for various tasks such as loading files, retrieving parameters, etc.
 *
 * @package W2P
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Charge les fichiers PHP dans un dossier et ses sous-dossiers.
 *
 * Fonction récursive qui charge les fichiers PHP dans un dossier et ses sous-dossiers.
 * Les erreurs sont enregistrées dans le journal d'erreurs W2P.
 *
 * @param string $dossier Chemin du dossier à charger.
 *
 * @throws \Throwable Si une erreur est rencontrée.
 */
function w2pcifw_load_files( $dossier ) {
	try {
		$contenu = scandir( $dossier );
		if ( false === $contenu ) {
			w2pcifw_add_error_log( "Unable to scan directory: $dossier", 'w2pcifw_load_files()' );
			return;
		}

		foreach ( $contenu as $element ) {
			if ( '.' !== $element && '..' !== $element ) {
				$chemin = $dossier . '/' . $element;
				if ( is_dir( $chemin ) ) {
					w2pcifw_load_files( $chemin );
				} elseif ( pathinfo( $chemin, PATHINFO_EXTENSION ) === 'php' ) {
					if ( file_exists( $chemin ) ) {
						include_once $chemin;
					} else {
						w2pcifw_add_error_log( "File not found: $chemin", 'w2pcifw_load_files()' );
					}
				}
			}
		}
	} catch ( \Throwable $e ) {
		w2pcifw_add_error_log( 'Error in w2pcifw_load_files: ' . $e->getMessage(), 'w2pcifw_load_files()' );
		w2pcifw_add_error_log( "Parameters passed: directory = $dossier", 'w2pcifw_load_files()' );
	}
}

/**
 * Retrieves the W2P parameters stored in the database.
 *
 * Retrieves the W2P parameters stored in the database, decrypts the API keys,
 * and returns the parameters as an array or null if an error occurs.
 *
 * @return array|null The W2P parameters or null if an error occurs.
 */
function w2pcifw_get_parameters(): ?array {
	try {
		$w2pcifw_parameters = w2pcifw_maybe_json_decode( get_option( 'w2pcifw_parameters' ) );

		if ( null === $w2pcifw_parameters ) {
			w2pcifw_add_error_log( "Failed to decode JSON for 'w2pcifw_parameters'.", 'w2pcifw_get_parameters()' );
			return null;
		}

		if ( isset( $w2pcifw_parameters['pipedrive']['api_key'] ) ) {
			$w2pcifw_parameters['pipedrive']['api_key'] = w2pcifw_decrypt( $w2pcifw_parameters['pipedrive']['api_key'] );
			if ( false === $w2pcifw_parameters['pipedrive']['api_key'] ) {
				w2pcifw_add_error_log( 'Decryption failed for Pipedrive API key.', 'w2pcifw_get_parameters()' );
			}
		}

		if ( isset( $w2pcifw_parameters['pipedrive']['company_domain'] ) ) {
			$w2pcifw_parameters['pipedrive']['company_domain'] = w2pcifw_decrypt( $w2pcifw_parameters['pipedrive']['company_domain'] );
			if ( false === $w2pcifw_parameters['pipedrive']['company_domain'] ) {
				w2pcifw_add_error_log( 'Decryption failed for Pipedrive company domain.', 'w2pcifw_get_parameters()' );
			}
		}

		if ( isset( $w2pcifw_parameters['w2p']['api_key'] ) ) {
			$w2pcifw_parameters['w2p']['api_key'] = w2pcifw_decrypt( $w2pcifw_parameters['w2p']['api_key'] );
			if ( false === $w2pcifw_parameters['w2p']['api_key'] ) {
				w2pcifw_add_error_log( 'Decryption failed for W2P API key.', 'w2pcifw_get_parameters()' );
			}
		}

		return is_array( $w2pcifw_parameters ) ? $w2pcifw_parameters : null;
	} catch ( \Throwable $e ) {
		w2pcifw_add_error_log( 'Error in w2pcifw_get_parameters: ' . $e->getMessage(), 'w2pcifw_get_parameters()' );
		return null;
	}
}


/**
 * Checks the JWT token for internal API calls.
 *
 * Checks the JWT token sent in the request and returns true if the token is valid,
 * otherwise returns false.
 *
 * @param WP_REST_Request $request The REST request object.
 * @return bool True if the token is valid, otherwise false.
 */
function w2pcifw_jwt_token( $request ) {
	if ( w2pcifw_is_local_environment() ) {
		return true;
	}

	$secret_key = $request->get_param( 'secret_key' );
	if ( ( w2pcifw_get_encryption_key() === $secret_key ) || current_user_can( 'manage_options' ) ) {
		return true;
	}

	return false;
}


/**
 * Checks if the given API key is valid.
 *
 * @param string $key_to_check The API key to check.
 * @return bool True if the API key is valid, otherwise false.
 */
function w2pcifw_check_api_key( $key_to_check ): bool {
	$parameters = w2pcifw_get_parameters();
	return $parameters && isset( $parameters['w2p']['api_key'] )
		? $parameters['w2p']['api_key'] === $key_to_check
		: false;
}

/**
 * Retrieves the W2P API key from the database.
 *
 * @return string|null The W2P API key or null if an error occurs.
 */
function w2pcifw_get_api_key(): ?string {
	$parameters = w2pcifw_get_parameters();
	return $parameters && isset( $parameters['w2p']['api_key'] )
		? $parameters['w2p']['api_key']
		: null;
}


/**
 * Retrieves the W2P API domain from the database.
 *
 * Optionally includes the HTTP or HTTPS schema based on the site protocol.
 *
 * @param bool $schema Whether to include the schema in the returned domain.
 * @return string|null The W2P API domain with or without schema, or null if not available.
 */
function w2pcifw_get_api_domain( $schema = false ): ?string {
	$parameters = w2pcifw_get_parameters();
	if ( $schema ) {
		return $parameters && isset( $parameters['w2p']['domain'] )
			? ( is_ssl()
				? 'https://' . $parameters['w2p']['domain']
				: 'http://' . $parameters['w2p']['domain'] )
			: null;
	} else {
		return $parameters && isset( $parameters['w2p']['domain'] )
			? $parameters['w2p']['domain']
			: null;
	}
}

/**
 * Retrieves the Pipedrive API key from the database.
 *
 * @return string|null The Pipedrive API key or null if an error occurs.
 */
function w2pcifw_get_pipedrive_api_key(): ?string {
	$parameters = w2pcifw_get_parameters();
	return $parameters && isset( $parameters['pipedrive']['api_key'] )
		? $parameters['pipedrive']['api_key']
		: null;
}

/**
 * Retrieves the Pipedrive company domain.
 *
 * Fetches the company domain related to Pipedrive from the stored parameters
 * and returns it with an HTTPS schema. If the domain is not set or available,
 * it returns null.
 *
 * @return string|null The Pipedrive company domain with HTTPS schema, or null if not set.
 */
function w2pcifw_get_pipedrive_domain(): ?string {
	$parameters = w2pcifw_get_parameters();
	return $parameters
		&& isset( $parameters['pipedrive']['company_domain'] )
		&& $parameters['pipedrive']['company_domain']
		? 'https://' . $parameters['pipedrive']['company_domain']
		: null;
}

/**
 * Decodes a given JSON string into an associative array if possible.
 *
 * The function checks if the given data is a string and if it can be decoded.
 * If the decoding is successful, the function returns the decoded associative
 * array. Otherwise, it returns the original data.
 *
 * @param mixed $data The data to be decoded, usually a string.
 *
 * @return mixed The decoded associative array or the original data if decoding failed.
 */
function w2pcifw_maybe_json_decode( $data ) {
	if ( is_string( $data ) ) {
		try {
			$decoded = json_decode( $data, true );
			return ( json_last_error() === JSON_ERROR_NONE ) ? $decoded : $data;
		} catch ( Throwable $e ) {
			w2pcifw_add_error_log( $e->getMessage(), 'w2pcifw_maybe_json_decode' );
			w2pcifw_add_error_log( 'Parameters passed: ' . wp_json_encode( $data, JSON_PRETTY_PRINT ), 'w2pcifw_get_order_value' );
			return $data;
		}
	} else {
		return $data;
	}
}

/**
 * Retrieves distinct user meta keys from the database.
 *
 * This function queries the WordPress usermeta table to fetch all unique meta keys.
 * It logs an error if the query fails and returns the results or null if an exception occurs.
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @return array|null An array of distinct meta keys or null if an error occurs.
 */
function w2pcifw_get_users_metakey() {
	global $wpdb;
	$table_usermeta = $wpdb->prefix . 'usermeta';

	try {
		$query   = $wpdb->prepare(
			'SELECT DISTINCT meta_key
            FROM %i',
			$table_usermeta
		);
		$results = $wpdb->get_results( $query );
		if ( null === $results ) {
			w2pcifw_add_error_log( 'Query failed: ' . $wpdb->last_error, 'w2pcifw_get_users_metakey()' );
		}
		return $results;
	} catch ( \Throwable $e ) {
		w2pcifw_add_error_log( 'Error in w2pcifw_get_users_metakey: ' . $e->getMessage(), 'w2pcifw_get_users_metakey()' );
		return null;
	}
}


/**
 * Logs an error message to a file.
 *
 * The function creates a log entry with the current date and time in ISO 8601 format.
 * It appends the given message and optional function name to the log entry.
 * The log entries are stored in a file named 'error_log.log' in a directory named 'w2pcifw_logs'
 * in the WordPress uploads directory.
 *
 * @param string $message The error message to log. Default is 'No message'.
 * @param string $func    The name of the function where the error occurred. Default is an empty string.
 */
function w2pcifw_add_error_log( string $message = 'No message', string $func = '' ) {
	global $wp_filesystem;

	if ( ! function_exists( 'WP_Filesystem' ) ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';
	}

	if ( ! WP_Filesystem() ) {
		return;
	}

	$upload_dir = wp_upload_dir();
	$log_dir    = $upload_dir['basedir'] . '/w2pcifw_logs/';
	$log_file   = $log_dir . 'error_log.log';

	if ( ! $wp_filesystem->exists( $log_dir ) ) {
		$wp_filesystem->mkdir( $log_dir, 0755 );
	}

	if ( ! $wp_filesystem->exists( $log_file ) ) {
		$wp_filesystem->put_contents( $log_file, '', FS_CHMOD_FILE );
	}

	if ( ! $wp_filesystem->is_writable( $log_file ) ) {
		return;
	}

	$existing_logs = $wp_filesystem->get_contents( $log_file );
	if ( false === $existing_logs ) {
		$existing_logs = '';
	}

	$log_entry = gmdate( 'Y-m-d\TH:i:s\Z' );
	if ( $func ) {
		$log_entry .= " [$func] -";
	}
	$log_entry .= " $message\n";

	$wp_filesystem->put_contents( $log_file, $existing_logs . $log_entry, FS_CHMOD_FILE );
}


/**
 * Converts a JSON string to an associative array.
 *
 * The function takes a JSON string, decodes it and returns the result as an associative array.
 * If the decoding fails or the input string is not a valid JSON, the function logs an error
 * and returns an empty array.
 *
 * @param string $json A JSON string to be converted.
 *
 * @return array An associative array or an empty array if an error occurs.
 */
function w2pcifw_json_to_array( string $json ) {
	try {
		$decoded_value = w2pcifw_maybe_json_decode( $json, true );
		if ( is_array( $decoded_value ) ) {
			return array_map(
				function ( $item ) {
					return is_numeric( $item ) ? floatval( $item ) : $item;
				},
				$decoded_value
			);
		} else {
			return array();
		}
	} catch ( \Throwable $e ) {
		w2pcifw_add_error_log( 'Error: ' . $e->getMessage(), 'w2pcifw_json_to_array' );
		w2pcifw_add_error_log( 'Parameters passed: ' . wp_json_encode( $json, JSON_PRETTY_PRINT ), 'w2pcifw_json_to_array' );
		return array();
	}
}

/**
 * Converts an associative array to a JSON string.
 *
 * The function takes an associative array, formats its values (if numeric, converts to string)
 * and returns the result as a JSON string.
 * If the encoding fails or the input array is not a valid associative array, the function logs an error
 * and returns an empty array in JSON format.
 *
 * @param array $arr An associative array to be converted.
 *
 * @return string A JSON string or an empty array in JSON format if an error occurs.
 */
function w2pcifw_json_encode( array $arr ): string {
	try {
		$formated_array = array_map(
			function ( $value ) {
				return is_numeric( $value ) ? strval( $value ) : $value;
			},
			$arr
		);

		$formated_string = wp_json_encode( $formated_array );

		return $formated_string ?? '[]';
	} catch ( \Throwable $e ) {
		w2pcifw_add_error_log( 'Error: ' . $e->getMessage(), 'w2pcifw_json_encode' );
		w2pcifw_add_error_log( 'Parameters passed: ' . wp_json_encode( $arr, JSON_PRETTY_PRINT ), 'w2pcifw_json_encode' );
		return '[]';
	}
}

/**
 * Generates a meta key based on the given category and suffix.
 *
 * @param string $category The meta key category.
 * @param string $suffix    The meta key suffix.
 *
 * @return string The generated meta key.
 */
function w2pcifw_get_meta_key( string $category, string $suffix ) {
	return "w2pcifw_{$category}_{$suffix}";
}

/**
 * Checks if a W2P synchronization is currently running.
 *
 * The function returns true if a synchronization is running and false otherwise.
 * It also checks if the last heartbeat was older than 4 hours, and if so, stops the synchronization.
 *
 * @return bool True if a synchronization is running, false otherwise.
 */
function w2pcifw_is_sync_running() {
	$is_sync_running = get_option( 'w2pcifw_sync_running', false );
	$last_heartbeat  = get_option( 'w2pcifw_sync_last_heartbeat', null );

	if ( $is_sync_running && ( ! $last_heartbeat || time() - $last_heartbeat > 60 * 60 * 4 ) ) {
		update_option( 'w2pcifw_sync_running', false );
		wp_clear_scheduled_hook( 'w2pcifw_cron_check_sync' );

		$is_sync_running = false;
	}

	return $is_sync_running;
}

/**
 * Makes an HTTP request using WordPress HTTP API.
 *
 * @param string $url    The request URL.
 * @param string $method The HTTP method (GET, POST, PUT, PATCH, DELETE).
 * @param array  $data   The data to send with the request (for POST, PUT, PATCH).
 * @return array Structured response with success, data, raw response, status code, and error.
 */
function w2pcifw_http_request( string $url, string $method, array $data = array() ) {
	try {
		$args = array(
			'method'    => strtoupper( $method ),
			'timeout'   => 15,
			'headers'   => array(
				'Content-Type' => 'application/json',
			),
			'sslverify' => true, // Should be true in production for security.
		);

		// Add body data for applicable HTTP methods.
		if ( in_array( $method, array( 'POST', 'PUT', 'PATCH' ), true ) ) {
			$args['body'] = wp_json_encode( $data );
		}

		// Perform the request.
		$response = wp_remote_request( $url, $args );

		// Check for errors in the response.
		if ( is_wp_error( $response ) ) {
			return array(
				'success'     => false,
				'error'       => $response->get_error_message(),
				'status_code' => $response->get_error_code(),
				'data'        => null,
			);
		}

		// Parse the response.
		$status_code = wp_remote_retrieve_response_code( $response );
		$body        = wp_remote_retrieve_body( $response );

		return array(
			'success'     => true,
			'data'        => json_decode( $body, true ),
			'raw'         => $body,
			'status_code' => $status_code,
		);
	} catch ( Throwable $e ) {
		// Log errors and return a structured response.
		w2pcifw_add_error_log( 'HTTP request failed: ' . $e->getMessage(), 'w2pcifw_http_request' );
		w2pcifw_add_error_log( "URL: $url", 'w2pcifw_http_request' );
		w2pcifw_add_error_log( "Method: $method", 'w2pcifw_http_request' );

		return array(
			'success'     => false,
			'error'       => $e->getMessage(),
			'status_code' => 500,
			'data'        => null,
		);
	}
}

/**
 * Checks if WooCommerce is active.
 *
 * @return bool True if WooCommerce is active, false otherwise.
 */
function w2pcifw_is_woocomerce_active() {
	return class_exists( 'WC_Order' );
}

/**
 * Checks if the current environment is local.
 *
 * This function checks if the current host name contains the string '.local'.
 * This is a common convention used for local development environments.
 *
 * @return bool True if the environment is local, false otherwise.
 */
function w2pcifw_is_local_environment() {
	$server_name = isset( $_SERVER['SERVER_NAME'] ) ? sanitize_text_field( wp_unslash( $_SERVER['SERVER_NAME'] ) ) : '';
	return strpos( $server_name, '.local' ) !== false;
}

/**
 * Compares two arrays.
 *
 * @param array $array1 The first array to compare.
 * @param array $array2 The second array to compare.
 *
 * @return bool True if the arrays are equal, false otherwise.
 */
function w2pcifw_compare_arrays( $array1, $array2 ) {
	return $array1 == $array2;
}
