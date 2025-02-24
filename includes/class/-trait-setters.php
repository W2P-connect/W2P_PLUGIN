<?php
/**
 * W2P Setter Trait
 *
 * This trait provides methods for setting and retrieving object data. It includes functionality
 * to set individual values, retrieve values, and handle bulk updates. The trait also integrates
 * error handling and logging for operations that may fail during execution.
 *
 * @package W2P
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Trait W2PCIFW_SetterTrait
 *
 * Provides methods for setting and getting object data.
 *
 * @package W2P
 * @since 1.0.0
 */

trait W2PCIFW_SetterTrait {

	/**
	 * Sets a value in the object's data array.
	 *
	 * @param string $key   The key to set in the data array.
	 * @param mixed  $value The value to assign to the key.
	 */
	public function setter( string $key, $value ): void {
		try {
			if ( array_key_exists( $key, $this->data ) ) {
				$this->data[ $key ] = $value;
			}
		} catch ( \Throwable $e ) {
			w2pcifw_add_error_log( "Error in setter for key: $key, value: " . wp_json_encode( $value ) . ' - ' . $e->getMessage(), 'setter' );
		}
	}

	/**
	 * Gets a value from the object's data array.
	 *
	 * @param string $key The key to retrieve from the data array.
	 * @return mixed|null The value of the key, or null if it doesn't exist.
	 */
	public function getter( string $key ) {
		try {
			return array_key_exists( $key, $this->data ) ? $this->data[ $key ] : null;
		} catch ( \Throwable $e ) {
			w2pcifw_add_error_log( "Error in getter for key: $key - " . $e->getMessage(), 'getter' );
			return null;
		}
	}

	/**
	 * Sets multiple values in the object's data array from an associative array.
	 *
	 * @param array $params The associative array of key-value pairs to set.
	 */
	public function set_from_array( array $params ): void {
		try {
			foreach ( $params as $key => $value ) {
				if ( array_key_exists( $key, $this->data ) ) {
					$this->setter( $key, $value );
				}
			}
		} catch ( \Throwable $e ) {
			w2pcifw_add_error_log( 'Error in set_from_array with params: ' . wp_json_encode( $params ) . ' - ' . $e->getMessage(), 'set_from_array' );
		}
	}

	/**
	 * Gets the ID of the object, saving it to the database if necessary.
	 *
	 * @return int The ID of the object, or 0 if it cannot be determined.
	 */
	public function get_id(): int {
		try {
			if ( ! empty( $this->data['id'] ) ) {
				return $this->data['id'];
			} elseif ( $this->is_savable() ) {
				return $this->save_to_database();
			} else {
				return 0;
			}
		} catch ( \Throwable $e ) {
			w2pcifw_add_error_log( 'Error in get_id - ' . $e->getMessage(), 'get_id' );
			return 0;
		}
	}
}
