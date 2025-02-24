<?php
/**
 * Custom user class extending WP_User for additional functionalities.
 *
 * @package W2P
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class W2PCIFW_User
 *
 * Handles custom user-related operations.
 *
 * @package W2P
 */
class W2PCIFW_User extends WP_User {

	/**
	 * Constructor for the W2PCIFW_User class.
	 *
	 * @param int $id The user ID.
	 */
	public function __construct( int $id = 0 ) {
		try {
			parent::__construct( $id );
		} catch ( \Throwable $e ) {
			w2pcifw_add_error_log( 'Error in W2PCIFW_User constructor: ' . $e->getMessage(), 'W2PCIFW_User->__construct()' );
			w2pcifw_add_error_log( "Parameters passed: id = $id", 'W2PCIFW_User->__construct()' );
		}
	}

	/**
	 * Updates a meta key for the user.
	 *
	 * @param string $meta_key The meta key to update.
	 * @param mixed  $value    The value to update.
	 */
	public function update_meta_key( string $meta_key, $value ) {
		try {
			update_user_meta( $this->ID, $meta_key, $value );
		} catch ( \Throwable $e ) {
			w2pcifw_add_error_log( 'Error in update_meta_key: ' . $e->getMessage(), 'W2PCIFW_User->update_meta_key()' );
			w2pcifw_add_error_log( "Parameters passed: meta_key = $meta_key, value = " . wp_json_encode( $value, JSON_PRETTY_PRINT ), 'W2PCIFW_User->update_meta_key()' );
		}
	}

	/**
	 * Retrieves the last name of the user.
	 *
	 * @return string The user's last name.
	 */
	public function get_lastName(): string {
		try {
			return $this->last_name;
		} catch ( \Throwable $e ) {
			w2pcifw_add_error_log( 'Error in get_lastName: ' . $e->getMessage(), 'W2PCIFW_User->get_lastName()' );
			w2pcifw_add_error_log( 'Parameters passed: ' . wp_json_encode( $this, JSON_PRETTY_PRINT ), 'W2PCIFW_User->get_lastName()' );
			return ''; // Default value in case of error.
		}
	}

	/**
	 * Retrieves the first name of the user.
	 *
	 * @return string The user's first name.
	 */
	public function get_firstName(): string {
		try {
			return $this->first_name;
		} catch ( \Throwable $e ) {
			w2pcifw_add_error_log( 'Error in get_firstName: ' . $e->getMessage(), 'W2PCIFW_User->get_firstName()' );
			w2pcifw_add_error_log( 'Parameters passed: ' . wp_json_encode( $this, JSON_PRETTY_PRINT ), 'W2PCIFW_User->get_firstName()' );
			return ''; // Default value in case of error.
		}
	}

	/**
	 * Retrieves the company name of the user.
	 *
	 * @return string The user's company name.
	 */
	public function get_company(): string {
		try {
			$billing_company = $this->get( 'billing_company' );
			return $billing_company;
		} catch ( \Throwable $e ) {
			w2pcifw_add_error_log( 'Error in get_company: ' . $e->getMessage(), 'W2PCIFW_User->get_company()' );
			w2pcifw_add_error_log( 'Parameters passed: ' . wp_json_encode( $this, JSON_PRETTY_PRINT ), 'W2PCIFW_User->get_company()' );
			return ''; // Default value in case of error.
		}
	}

	/**
	 * Checks if the user is a new user.
	 *
	 * @return bool True if the user is new, false otherwise.
	 */
	public function is_new_user(): bool {
		try {
			if ( 0 !== $this->get( 'w2pcifw_new_user' ) ) {
				return false;
			}

			$user_registered_date     = $this->user_registered;
			$user_registered_datetime = new DateTime( $user_registered_date );
			$current_date             = new DateTime();
			$interval                 = $current_date->diff( $user_registered_datetime );

			if ( 0 === $interval->days && 0 === $interval->h && $interval->i <= 1 ) {
				return true;
			} else {
				$this->update_meta_key( 'w2pcifw_new_user', 0 );
				return false;
			}
		} catch ( \Throwable $e ) {
			w2pcifw_add_error_log( 'Error in is_new_user: ' . $e->getMessage(), 'W2PCIFW_User->is_new_user()' );
			w2pcifw_add_error_log( 'Parameters passed: ' . wp_json_encode( $this, JSON_PRETTY_PRINT ), 'W2PCIFW_User->is_new_user()' );
			return false; // Default value in case of error.
		}
	}

	/**
	 * Retrieves queries related to the person.
	 *
	 * @return array The queries data.
	 */
	public function get_person_queries(): array {
		return W2PCIFW_Query::get_queries(
			true,
			array(
				'category'  => W2PCIFW_CATEGORY['person'],
				'source_id' => $this->ID,
			),
			1,
			-1
		)['data'];
	}

	/**
	 * Retrieves queries related to the organization.
	 *
	 * @return array The queries data.
	 */
	public function get_organization_queries(): array {
		return W2PCIFW_Query::get_queries(
			true,
			array(
				'category'  => W2PCIFW_CATEGORY['organization'],
				'source_id' => $this->ID,
				'source'    => W2PCIFW_HOOK_SOURCES['user'],
			),
			1,
			-1
		)['data'];
	}
}
