<?php
/**
 * Handles field operations for W2P.
 *
 * This file defines the W2PCIFW_Field class, which provides methods for
 * managing and retrieving field-related data within the W2P plugin.
 *
 * @package W2P
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class W2PCIFW_Field
 *
 * Handles operations related to fields in W2P.
 *
 * @package W2P
 * @since 1.0.0
 */
class W2PCIFW_Field {


	/**
	 * The field data.
	 *
	 * @var array
	 */
	private $field = array();

	/**
	 * Constructor for the W2PCIFW_Field class.
	 *
	 * @param array $field The field data.
	 */
	public function __construct( array $field ) {
		$this->field = $field;
	}


	/**
	 * Retrieves the Pipedrive field data based on the category.
	 *
	 * Attempts to find the Pipedrive field data from the stored parameters
	 * based on the provided category and the 'pipedriveFieldId' associated
	 * with the current field.
	 *
	 * @param string $category The category of the Pipedrive field to retrieve.
	 * @return array|null The Pipedrive field data, or null if not found or an error occurs.
	 */
	public function get_field( string $category ): ?array {
		try {
			$field = null;
			if ( isset( $this->field['pipedriveFieldId'] ) && is_int( $this->field['pipedriveFieldId'] ) ) {
				$parameters       = w2pcifw_get_parameters();
				$pipedrive_fields = $parameters['pipedrive']['fields'];

				foreach ( $pipedrive_fields as $p_field ) {
					if ( isset( $p_field['id'] ) && $p_field['id'] === $this->field['pipedriveFieldId'] && $p_field['category'] === $category ) {
						$field = $p_field;
						break;
					}
				}
			}
			return $field;
		} catch ( \Throwable $e ) {
			w2pcifw_add_error_log( 'Error in get_field for pipedriveFieldId: ' . $this->field['pipedriveFieldId'] . ' - ' . $e->getMessage(), 'get_field' );
			return null;
		}
	}

	/**
	 * Retrieves the value of a specific key from the field data.
	 *
	 * @param string|null $key The key to retrieve, or null to get the entire field data.
	 * @return mixed The value of the specified key, the entire field data, or null if the key does not exist.
	 */
	public function get_data( ?string $key = null ) {
		try {
			return $key
				? ( isset( $this->field[ $key ] ) ? $this->field[ $key ] : null )
				: $this->field;
		} catch ( \Throwable $e ) {
			w2pcifw_add_error_log( "Error in get_data with key: $key - " . $e->getMessage(), 'get_data' );
			return null;
		}
	}
}
