<?php
/**
 * WooCommerce Helpers
 *
 * This file contains utility functions used by the W2P plugin to interact with WooCommerce.
 * It provides methods for handling customer data, processing orders, and other related tasks.
 *
 * @package W2P
 * @since 1.0.0
 */

/**
 * Retrieves the customer ID associated with a given WooCommerce order ID.
 *
 * This function checks if the order is a regular order or a refund and returns
 * the appropriate customer ID. If the order is a refund, it retrieves the customer
 * ID from the parent/original order. Returns null if the order type is not recognized
 * or if any error occurs during the process.
 *
 * @param int $order_id The WooCommerce order ID.
 * @return int|null The customer ID associated with the order, or null if not found.
 */
function w2p_get_customer_id_from_order_id( int $order_id ): ?int {
	try {
		$order = wc_get_order( $order_id );

		if ( $order instanceof \Automattic\WooCommerce\Admin\Overrides\OrderRefund ) {
			// Si c’est un remboursement, récupère la commande originale.
			$parent_order = wc_get_order( $order->get_parent_id() );
			$customer_id  = $parent_order ? $parent_order->get_customer_id() : null;
		} elseif ( $order instanceof \WC_Order ) {
			// Si c’est une commande, récupère directement l'ID du client.
			$customer_id = $order->get_customer_id();
		} else {
			return null;  // Pas de type reconnu.
		}

		return $customer_id && $customer_id > 0
			? $customer_id
			: null;
	} catch ( Throwable $e ) {
		w2p_add_error_log( 'Error retrieving customer ID from order: ' . $e->getMessage(), 'w2p_get_customer_id_from_order_id' );
		w2p_add_error_log( 'Parameters passed: ' . wp_json_encode( compact( 'order_id' ), JSON_PRETTY_PRINT ), 'w2p_get_customer_id_from_order_id' );
		return null;
	}
}
