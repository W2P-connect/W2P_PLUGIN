<?php
/**
 * Encryption utility functions for W2PCIFW.
 *
 * This file provides secure encryption and decryption functions,
 * ensuring the storage of sensitive data is handled securely.
 *
 * @package W2PCIFW
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Retrieves or generates the encryption key securely.
 *
 * @return string The encryption key in binary format.
 */
function w2pcifw_get_encryption_key() {
	$stored_key = get_option( 'w2pcifw_encryption_key' );

	if ( ! $stored_key ) {
		$raw_key     = w2pcifw_generate_encryption_key();
		$encoded_key = w2pcifw_encrypt_key( $raw_key );
		update_option( 'w2pcifw_encryption_key', $encoded_key );
		return hex2bin( $raw_key );
	}

	return hex2bin( w2pcifw_decrypt_key( $stored_key ) );
}

/**
 * Encrypts the stored encryption key using AUTH_KEY or a fallback.
 *
 * @param string $key The encryption key to encrypt.
 * @return string The encrypted key in base64 format.
 */
function w2pcifw_encrypt_key( $key ) {
	return base64_encode( openssl_encrypt( $key, 'aes-256-cbc', w2pcifw_get_auth_key(), 0, substr( w2pcifw_get_auth_key(), 0, 16 ) ) );
}

/**
 * Decrypts the stored encryption key.
 *
 * @param string $stored_key The encrypted key stored in the database.
 * @return string The decrypted encryption key.
 */
function w2pcifw_decrypt_key( $stored_key ) {
	return openssl_decrypt( base64_decode( $stored_key ), 'aes-256-cbc', w2pcifw_get_auth_key(), 0, substr( w2pcifw_get_auth_key(), 0, 16 ) );
}

/**
 * Generates a cryptographically secure encryption key.
 *
 * @return string The generated encryption key in hexadecimal format.
 * @throws Exception If no secure random number generator is available.
 */
function w2pcifw_generate_encryption_key() {
	if ( function_exists( 'random_bytes' ) ) {
		return bin2hex( random_bytes( 32 ) );
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
 * Retrieves a secure authentication key.
 *
 * @return string The authentication key used for encryption.
 */
function w2pcifw_get_auth_key() {
	if ( defined( 'AUTH_KEY' ) && AUTH_KEY !== '' ) {
		return AUTH_KEY;
	}

	if ( defined( 'DB_NAME' ) && defined( 'DB_PASSWORD' ) ) {
		return hash( 'sha256', DB_NAME . DB_PASSWORD );
	}

	$fallback_key = get_option( 'w2pcifw_fallback_auth_key' );
	if ( ! $fallback_key ) {
		$fallback_key = bin2hex( random_bytes( 32 ) );
		update_option( 'w2pcifw_fallback_auth_key', $fallback_key );
	}

	return $fallback_key;
}

/**
 * Encrypts data using AES-256-CBC encryption.
 *
 * @param string $data The data to encrypt.
 * @return string The encrypted data in base64 format.
 * @throws Exception If encryption fails.
 */
function w2pcifw_encrypt( $data ) {
	try {
		$key = w2pcifw_get_encryption_key();

		if ( strlen( $key ) !== 32 ) {
			throw new Exception( 'Invalid encryption key length. Key must be 32 bytes for AES-256-CBC.' );
		}

		$iv             = openssl_random_pseudo_bytes( 16 );
		$encrypted_data = openssl_encrypt( $data, 'aes-256-cbc', $key, 0, $iv );

		if ( false === $encrypted_data ) {
			throw new Exception( 'Encryption failed.' );
		}

		return base64_encode( $iv . $encrypted_data );
	} catch ( Throwable $e ) {
		w2pcifw_add_error_log( 'Encryption error: ' . $e->getMessage(), 'w2pcifw_encrypt' );
		return $data;
	}
}

/**
 * Decrypts data using AES-256-CBC encryption.
 *
 * @param string $encrypted_data The encrypted data in base64 format.
 * @return string The decrypted data.
 * @throws Exception If decryption fails.
 */
function w2pcifw_decrypt( $encrypted_data ) {
	try {
		$key = w2pcifw_get_encryption_key();

		if ( strlen( $key ) !== 32 ) {
			throw new Exception( 'Invalid encryption key length. Key must be 32 bytes for AES-256-CBC.' );
		}

		$encrypted_data = base64_decode( $encrypted_data );

		if ( strlen( $encrypted_data ) < 16 ) {
			throw new Exception( 'Encrypted data is too short to contain a valid IV.' );
		}

		$iv             = substr( $encrypted_data, 0, 16 );
		$encrypted_data = substr( $encrypted_data, 16 );

		$decrypted_data = openssl_decrypt( $encrypted_data, 'aes-256-cbc', $key, 0, $iv );

		if ( false === $decrypted_data ) {
			throw new Exception( 'Decryption failed.' );
		}

		return $decrypted_data;
	} catch ( Throwable $e ) {
		w2pcifw_add_error_log( 'Decryption error: ' . $e->getMessage(), 'w2pcifw_decrypt' );
		return $encrypted_data;
	}
}
