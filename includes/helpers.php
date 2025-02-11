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
function w2p_load_files( $dossier ) {
	try {
		$contenu = scandir( $dossier );
		if ( false === $contenu ) {
			w2p_add_error_log( "Unable to scan directory: $dossier", 'w2p_load_files()' );
			return;
		}

		foreach ( $contenu as $element ) {
			if ( '.' !== $element && '..' !== $element ) {
				$chemin = $dossier . '/' . $element;
				if ( is_dir( $chemin ) ) {
					w2p_load_files( $chemin );
				} elseif ( pathinfo( $chemin, PATHINFO_EXTENSION ) === 'php' ) {
					if ( file_exists( $chemin ) ) {
						include_once $chemin;
					} else {
						w2p_add_error_log( "File not found: $chemin", 'w2p_load_files()' );
					}
				}
			}
		}
	} catch ( \Throwable $e ) {
		w2p_add_error_log( 'Error in w2p_load_files: ' . $e->getMessage(), 'w2p_load_files()' );
		w2p_add_error_log( "Parameters passed: directory = $dossier", 'w2p_load_files()' );
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
function w2p_get_parameters(): ?array {
	try {
		$w2p_parameters = w2p_maybe_json_decode( get_option( 'w2p_parameters' ) );

		if ( null === $w2p_parameters ) {
			w2p_add_error_log( "Failed to decode JSON for 'w2p_parameters'.", 'w2p_get_parameters()' );
			return null;
		}

		if ( isset( $w2p_parameters['pipedrive']['api_key'] ) ) {
			$w2p_parameters['pipedrive']['api_key'] = w2p_decrypt( $w2p_parameters['pipedrive']['api_key'] );
			if ( false === $w2p_parameters['pipedrive']['api_key'] ) {
				w2p_add_error_log( 'Decryption failed for Pipedrive API key.', 'w2p_get_parameters()' );
			}
		}

		if ( isset( $w2p_parameters['pipedrive']['company_domain'] ) ) {
			$w2p_parameters['pipedrive']['company_domain'] = w2p_decrypt( $w2p_parameters['pipedrive']['company_domain'] );
			if ( false === $w2p_parameters['pipedrive']['company_domain'] ) {
				w2p_add_error_log( 'Decryption failed for Pipedrive company domain.', 'w2p_get_parameters()' );
			}
		}

		if ( isset( $w2p_parameters['w2p']['api_key'] ) ) {
			$w2p_parameters['w2p']['api_key'] = w2p_decrypt( $w2p_parameters['w2p']['api_key'] );
			if ( false === $w2p_parameters['w2p']['api_key'] ) {
				w2p_add_error_log( 'Decryption failed for W2P API key.', 'w2p_get_parameters()' );
			}
		}

		return is_array( $w2p_parameters ) ? $w2p_parameters : null;
	} catch ( \Throwable $e ) {
		w2p_add_error_log( 'Error in w2p_get_parameters: ' . $e->getMessage(), 'w2p_get_parameters()' );
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
function w2p_jwt_token( $request ) {
	if ( w2p_is_local_environment() ) {
		return true;
	}

	$secret_key = $request->get_param( 'secret_key' );
	if ( ( defined( 'W2P_ENCRYPTION_KEY' ) && W2P_ENCRYPTION_KEY === $secret_key ) || current_user_can( 'manage_options' ) ) {
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
function w2p_check_api_key( $key_to_check ): bool {
	$parameters = w2p_get_parameters();
	return $parameters && isset( $parameters['w2p']['api_key'] )
		? $parameters['w2p']['api_key'] === $key_to_check
		: false;
}

/**
 * Retrieves the W2P API key from the database.
 *
 * @return string|null The W2P API key or null if an error occurs.
 */
function w2p_get_api_key(): ?string {
	$parameters = w2p_get_parameters();
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
function w2p_get_api_domain( $schema = false ): ?string {
	$parameters = w2p_get_parameters();
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
function w2p_get_pipedrive_api_key(): ?string {
	$parameters = w2p_get_parameters();
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
function w2p_get_pipedrive_domain(): ?string {
	$parameters = w2p_get_parameters();
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
function w2p_maybe_json_decode( $data ) {
	if ( is_string( $data ) ) {
		try {
			$decoded = json_decode( $data, true );
			return ( json_last_error() === JSON_ERROR_NONE ) ? $decoded : $data;
		} catch ( Throwable $e ) {
			w2p_add_error_log( $e->getMessage(), 'w2p_maybe_json_decode' );
			w2p_add_error_log( 'Parameters passed: ' . wp_json_encode( $data, JSON_PRETTY_PRINT ), 'w2p_get_order_value' );
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
function w2p_get_users_metakey() {
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
			w2p_add_error_log( 'Query failed: ' . $wpdb->last_error, 'w2p_get_users_metakey()' );
		}
		return $results;
	} catch ( \Throwable $e ) {
		w2p_add_error_log( 'Error in w2p_get_users_metakey: ' . $e->getMessage(), 'w2p_get_users_metakey()' );
		return null;
	}
}

/**
 * Logs an error message to a file.
 *
 * The function creates a log entry with the current date and time in ISO 8601 format.
 * It appends the given message and optional function name to the log entry.
 * The log entries are stored in a file named 'error_log.log' in a directory named 'w2p_logs'
 * in the WordPress uploads directory.
 *
 * @param string $message The error message to log. Default is 'No message'.
 * @param string $func    The name of the function where the error occurred. Default is an empty string.
 */
function w2p_add_error_log( string $message = 'No message', string $func = '' ) {
	$upload_dir = wp_upload_dir();
	$log_dir    = $upload_dir['basedir'] . '/w2p_logs/';
	$log_file   = $log_dir . 'error_log.log';

	if ( ! file_exists( $log_dir ) ) {
		mkdir( $log_dir, 0755, true );
	}

	$log_entry = gmdate( 'Y-m-d\TH:i:s\Z' );
	if ( $func ) {
		$log_entry .= " [$func] -";
	}
	$log_entry .= " $message\n";

	file_put_contents( $log_file, $log_entry, FILE_APPEND | LOCK_EX );
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
function w2p_json_to_array( string $json ) {
	try {
		$decoded_value = w2p_maybe_json_decode( $json, true );
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
		w2p_add_error_log( 'Error: ' . $e->getMessage(), 'w2p_json_to_array' );
		w2p_add_error_log( 'Parameters passed: ' . wp_json_encode( $json, JSON_PRETTY_PRINT ), 'w2p_json_to_array' );
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
function w2p_json_encode( array $arr ): string {
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
		w2p_add_error_log( 'Error: ' . $e->getMessage(), 'w2p_json_encode' );
		w2p_add_error_log( 'Parameters passed: ' . wp_json_encode( $arr, JSON_PRETTY_PRINT ), 'w2p_json_encode' );
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
function w2p_get_meta_key( string $category, string $suffix ) {
	return "w2p_{$category}_{$suffix}";
}

/**
 * Checks if a W2P synchronization is currently running.
 *
 * The function returns true if a synchronization is running and false otherwise.
 * It also checks if the last heartbeat was older than 4 hours, and if so, stops the synchronization.
 *
 * @return bool True if a synchronization is running, false otherwise.
 */
function w2p_is_sync_running() {
	$is_sync_running = get_option( 'w2p_sync_running', false );
	$last_heartbeat  = get_option( 'w2p_sync_last_heartbeat', null );

	if ( $is_sync_running && ( ! $last_heartbeat || time() - $last_heartbeat > 60 * 60 * 4 ) ) {
		update_option( 'w2p_sync_running', false );
		wp_clear_scheduled_hook( 'w2p_cron_check_sync' );

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
function w2p_http_request( string $url, string $method, array $data = array() ) {
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
		w2p_add_error_log( 'HTTP request failed: ' . $e->getMessage(), 'w2p_http_request' );
		w2p_add_error_log( "URL: $url", 'w2p_http_request' );
		w2p_add_error_log( "Method: $method", 'w2p_http_request' );

		return array(
			'success'     => false,
			'error'       => $e->getMessage(),
			'status_code' => 500,
			'data'        => null,
		);
	}
}

/**
 * Generates a cryptographically secure encryption key.
 * This key is 256 bits long (32 bytes) and is suitable for use with AES-256 or similar encryption algorithms.
 *
 * @return string The generated encryption key in hexadecimal format.
 */
function w2p_generate_encryption_key() {
	if ( function_exists( 'random_bytes' ) ) {
		return bin2hex( random_bytes( 32 ) ); // Preferred method for PHP 7+.
	} elseif ( function_exists( 'openssl_random_pseudo_bytes' ) ) {
		$crypto_strong = false;
		$key           = openssl_random_pseudo_bytes( 32, $crypto_strong );

		if ( ! $crypto_strong ) {
			wp_die( 'The encryption key could not be generated securely.' );
		}

		return bin2hex( $key );
	} else {
		wp_die( 'No secure random number generator is available.' );
	}
}

/**
 * Encrypts data using AES-256-CBC encryption.
 *
 * If the W2P_ENCRYPTION_KEY constant is not defined, it will be generated and written to wp-config.php.
 * If the key is not 32 bytes long (256 bits), an exception will be thrown.
 *
 * The function returns the encrypted data as a base64-encoded string, prefixed with the 16-byte IV.
 *
 * @param string $data The data to encrypt.
 * @return string The encrypted data.
 * @throws Exception If there is an error encrypting the data.
 */
function w2p_encrypt( $data ) {
	try {
		if ( ! defined( 'W2P_ENCRYPTION_KEY' ) ) {
			w2p_secret_key_init();
			if ( ! defined( 'W2P_ENCRYPTION_KEY' ) ) {
				throw new Exception( 'Encryption key not defined.' );
			}
		}

		// Conversion de la clé hexadécimale en binaire.
		$key = hex2bin( W2P_ENCRYPTION_KEY );
		if ( strlen( $key ) !== 32 ) {
			throw new Exception( 'Invalid encryption key length. Key must be 32 bytes for AES-256-CBC.' );
		}

		$iv             = openssl_random_pseudo_bytes( 16 ); // Génère un IV de 16 octets.
		$encrypted_data = openssl_encrypt( $data, 'aes-256-cbc', $key, 0, $iv );

		if ( false === $encrypted_data ) {
			throw new Exception( 'Encryption failed.' );
		}

		return base64_encode( $iv . $encrypted_data ); // Combine IV et données chiffrées.
	} catch ( Throwable $e ) {
		w2p_add_error_log( 'Encryption error: ' . $e->getMessage(), 'w2p_encrypt' );
		return $data;
	}
}

/**
 * Decrypts data using AES-256-CBC encryption.
 *
 * This function is the inverse of w2p_encrypt(). It takes the encrypted data (as a base64-encoded string,
 * prefixed with the 16-byte IV) and returns the decrypted data.
 *
 * If the W2P_ENCRYPTION_KEY constant is not defined, it will be generated and written to wp-config.php.
 * If the key is not 32 bytes long (256 bits), an exception will be thrown.
 *
 * @param string $encrypted_data The encrypted data to decrypt.
 * @return string The decrypted data.
 * @throws Exception If there is an error decrypting the data.
 */
function w2p_decrypt( $encrypted_data ) {
	try {
		if ( ! defined( 'W2P_ENCRYPTION_KEY' ) ) {
			w2p_secret_key_init();
			if ( ! defined( 'W2P_ENCRYPTION_KEY' ) ) {
				throw new Exception( 'Encryption key not defined.' );
			}
		}

		$key = hex2bin( W2P_ENCRYPTION_KEY );
		if ( strlen( $key ) !== 32 ) {
			throw new Exception( 'Invalid encryption key length. Key must be 32 bytes for AES-256-CBC.' );
		}

		$encrypted_data = base64_decode( $encrypted_data );

		if ( strlen( $encrypted_data ) < 16 ) {
			throw new Exception( 'Encrypted data is too short to contain a valid IV.' );
		}

		$iv             = substr( $encrypted_data, 0, 16 ); // Extract IV.
		$encrypted_data = substr( $encrypted_data, 16 ); // Extract encrypted data.

		$decrypted_data = openssl_decrypt( $encrypted_data, 'aes-256-cbc', $key, 0, $iv );

		if ( false === $decrypted_data ) {
			throw new Exception( 'Decryption failed.' );
		}

		return $decrypted_data;
	} catch ( Throwable $e ) {
		w2p_add_error_log( 'Decryption error: ' . $e->getMessage(), 'w2p_decrypt' );
		return $encrypted_data;
	}
}

/**
 * Checks if WooCommerce is active.
 *
 * @return bool True if WooCommerce is active, false otherwise.
 */
function w2p_is_woocomerce_active() {
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
function w2p_is_local_environment() {
	$server_name = isset( $_SERVER['SERVER_NAME'] ) ? sanitize_text_field( wp_unslash( $_SERVER['SERVER_NAME'] ) ) : '';
	return strpos( $server_name, '.local' ) !== false;
}

/**
 * Compare two arrays, ignoring their order.
 *
 * The function first checks that the input is an array, then uses wp_unslash to clean the values,
 * and finally sorts the keys before comparing the arrays.
 *
 * @param array $array1 The first array to compare.
 * @param array $array2 The second array to compare.
 *
 * @return bool True if the arrays are equal, false otherwise.
 */
function w2p_compare_arrays( $array1, $array2 ) {
	if ( ! is_array( $array1 ) || ! is_array( $array2 ) ) {
		return false;
	}

	// Nettoyage des valeurs avec wp_unslash (si besoin, pour les données issues de formulaires)
	$array1 = wp_unslash( $array1 );
	$array2 = wp_unslash( $array2 );

	// Trier les clés pour comparer sans tenir compte de l'ordre
	ksort( $array1 );
	ksort( $array2 );

	return $array1 === $array2;
}
