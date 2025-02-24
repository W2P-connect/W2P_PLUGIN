<?php
/**
 * W2PCIFW_Formater Trait
 *
 * This file contains the W2PCIFW_Formater trait, which handles
 * formatting and database operations for W2P objects.
 *
 * @package W2P
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Trait W2PCIFW_Formater
 *
 * Provides methods for formatting and database operations for W2P objects.
 *
 * @package W2P
 * @since 1.0.0
 */
trait W2PCIFW_Formater {

	/**
	 * Loads an object from the database by its ID.
	 *
	 * @param int $id The ID of the object to load.
	 */
	private function load_object_from_db( int $id ): void {
		global $wpdb;
		try {
			$table_name = esc_sql( $this->db_name );

			if ( ! empty( $table_name ) ) {
				$query  = $wpdb->prepare( 'SELECT * FROM %i WHERE id = %d', $table_name, $id );
				$object = $wpdb->get_row( $query );
			}

			if ( $object ) {
				foreach ( $object as $key => $value ) {
					if ( array_key_exists( $key, $this->data ) && $value ) {
						if ( is_array( $this->data[ $key ] ) ) {
							$this->data[ $key ] = w2pcifw_json_to_array( $value );
						} else {
							$this->data[ $key ] = is_numeric( $value ) ? (float) $value : $value;
						}
					}
				}
				$this->new_instance = false;
			} else {
				$this->new_instance = true;
			}
		} catch ( \Throwable $e ) {
			w2pcifw_add_error_log( 'Error loading object from DB: ' . $e->getMessage(), 'load_object_from_db' );
		}
	}

	/**
	 * Formats the object data for database storage.
	 *
	 * @return array The formatted data.
	 */
	private function format_object_for_db(): array {
		try {
			$formatted_data = array();
			foreach ( $this->data as $key => $value ) {
				$formatted_data[ $key ] = is_array( $value ) ? w2pcifw_json_encode( $value ) : $value;
			}
			return $formatted_data;
		} catch ( \Throwable $e ) {
			w2pcifw_add_error_log( 'Error formatting object for DB: ' . $e->getMessage(), 'format_object_for_db' );
			return array();
		}
	}

	/**
	 * Saves the object to the database.
	 *
	 * @return int The ID of the saved object, or 0 on failure.
	 */
	public function save_to_database(): int {
		try {
			if ( $this->is_savable() ) {
				global $wpdb;
				$table_name = esc_sql( $this->db_name );

				if ( ! empty( $this->data['id'] ) ) {
					$result = $wpdb->update(
						$table_name,
						$this->format_object_for_db(),
						array( 'id' => $this->data['id'] ),
						array_fill( 0, count( $this->data ), '%s' ),
						array( '%d' )
					);
					return ( false !== $result ) ? $this->data['id'] : 0;
				} else {
					$result = $wpdb->insert(
						$table_name,
						$this->format_object_for_db(),
						array_fill( 0, count( $this->data ), '%s' )
					);
					return ( false !== $result ) ? $wpdb->insert_id : 0;
				}
			}
			return 0;
		} catch ( \Throwable $e ) {
			w2pcifw_add_error_log( 'Error saving to DB: ' . $e->getMessage(), 'save_to_database' );
			return 0;
		}
	}
}
