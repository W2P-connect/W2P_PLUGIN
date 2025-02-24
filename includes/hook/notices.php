<?php
/**
 * Displays a notification in the WordPress admin area if guest checkout is enabled.
 *
 * This notification appears on the W2P settings page and warns administrators
 * about potential issues with guest checkout and data synchronization with Pipedrive.
 *
 * @package W2P
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'admin_notices', 'w2pcifw_guest_checkout_notification' );

/**
 * Displays a warning notification about guest checkout settings.
 *
 * Checks if the "Guest Checkout" option is enabled in WooCommerce settings and
 * shows a notification on the W2P settings page if applicable.
 *
 * @return void
 */
function w2pcifw_guest_checkout_notification() {
	global $pagenow;

	// Check if guest checkout is enabled.
	$guest_checkout_enabled = 'yes' === get_option( 'woocommerce_enable_guest_checkout' );

	// Verify current page is W2P settings.
	$is_w2pcifw_settings = false;

	if ( isset( $_GET['page'] ) ) {
		// Sanitize and verify the input.
		$page = sanitize_text_field( wp_unslash( $_GET['page'] ) );

		if ( 'w2p-settings' === $page ) {
			// Ensure the request includes a valid nonce.
			if ( isset( $_GET['_wpnonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'w2pcifw_settings_page' ) ) {
				$is_w2pcifw_settings = 'admin.php' === $pagenow;
			}
		}
	}

	// Display notice if conditions are met.
	if ( $guest_checkout_enabled && $is_w2pcifw_settings ) {
		?>
		<div class="notice notice-warning is-dismissible">
			<h2 style="font-weight:500; font-size:large">Guest Checkout Enabled on Your Online Store</h2>
			<p style="font-size:medium">
				You have enabled the "Guest Checkout" option on your online store. This allows customers to place orders without creating an account, which may result in anonymous orders being recorded in Pipedrive. Consider disabling guest checkout to ensure customer data is properly synced.
			</p>
		</div>
		<?php
	}
}
