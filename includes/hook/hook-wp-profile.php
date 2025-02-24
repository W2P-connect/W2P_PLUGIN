<?php
/**
 * Adds and saves custom Pipedrive-related user meta fields in WordPress user profiles.
 *
 * This file contains functions to display and manage custom fields for associating
 * users with Pipedrive Person and Organization IDs. These fields are visible
 * and editable on user profile pages.
 *
 * @package W2P
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Displays custom Pipedrive fields (Person ID and Organization ID) on the user profile page.
 *
 * These fields allow administrators to view and update the Pipedrive Person and Organization IDs
 * associated with a user. The values are automatically populated during the first synchronization with Pipedrive.
 *
 * @param WP_User $user The current user object.
 */
function w2pcifw_pipedrive_custom_user_profile_fields( $user ) {
	$w2pcifw_person_id       = get_user_meta( $user->ID, w2pcifw_get_meta_key( W2PCIFW_CATEGORY['person'], 'id' ), true );
	$w2pcifw_organization_id = get_user_meta( $user->ID, w2pcifw_get_meta_key( W2PCIFW_CATEGORY['organization'], 'id' ), true );

	// Old support for 'W2P' prefix.
	if ( ! $w2pcifw_person_id ) {
		$w2pcifw_person_id = get_user_meta( $user->ID, 'W2PCIFW_person_id', true );
	}

	if ( ! $w2pcifw_organization_id ) {
		$w2pcifw_organization_id = get_user_meta( $user->ID, 'W2PCIFW_organization_id', true );
	}

	?>
	<h3>Pipedrive Information</h3>
	<p>These values will be automatically added during the first synchronization with Pipedrive. If there's an error in associating the person or organization ID, you can modify them here.</p>

	<table class="form-table">
		<tr>
			<th><label for="w2pcifw_person_id">Pipedrive Person ID</label></th>
			<td>
				<input type="number" name="w2pcifw_person_id" id="w2pcifw_person_id" value="<?php echo esc_attr( $w2pcifw_person_id ); ?>" class="regular-text" />
			</td>
		</tr>
		<tr>
			<th><label for="w2pcifw_organization_id">Pipedrive Organization ID</label></th>
			<td>
				<input type="number" name="w2pcifw_organization_id" id="w2pcifw_organization_id" value="<?php echo esc_attr( $w2pcifw_organization_id ); ?>" class="regular-text" />
			</td>
		</tr>
	</table>
	<?php
	wp_nonce_field( 'w2pcifw_save_pipedrive_fields', 'w2pcifw_pipedrive_nonce' );
}
add_action( 'show_user_profile', 'w2pcifw_pipedrive_custom_user_profile_fields' );
add_action( 'edit_user_profile', 'w2pcifw_pipedrive_custom_user_profile_fields' );

/**
 * Saves the custom Pipedrive fields (Person ID and Organization ID) from the user profile page.
 *
 * Updates the metadata for the user with the new values entered in the profile fields.
 * Only users with permission to edit profiles can save changes.
 *
 * @param int $user_id The ID of the user being updated.
 * @return void|false Returns false if the current user lacks permission to edit the user.
 */
function save_w2pcifw_pipedrive_custom_user_profile_fields( $user_id ) {
	if ( ! current_user_can( 'edit_user', $user_id ) ) {
		return false;
	}

	if ( ! isset( $_POST['w2pcifw_pipedrive_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['w2pcifw_pipedrive_nonce'] ) ), 'w2pcifw_save_pipedrive_fields' ) ) {
		return false;
	}

	if ( isset( $_POST['w2pcifw_person_id'] ) ) {
		update_user_meta( $user_id, w2pcifw_get_meta_key( W2PCIFW_CATEGORY['person'], 'id' ), intval( $_POST['w2pcifw_person_id'] ) );
	}

	if ( isset( $_POST['w2pcifw_organization_id'] ) ) {
		update_user_meta( $user_id, w2pcifw_get_meta_key( W2PCIFW_CATEGORY['organization'], 'id' ), intval( $_POST['w2pcifw_organization_id'] ) );
	}
}

add_action( 'personal_options_update', 'save_w2pcifw_pipedrive_custom_user_profile_fields' );
add_action( 'edit_user_profile_update', 'save_w2pcifw_pipedrive_custom_user_profile_fields' );
