<?php
/**
 * Initializes the W2P_Order class for WooCommerce orders.
 *
 * @package W2P
 * @since 1.0.0
 */

/**
 * Initializes the W2P_Order class if WooCommerce is active.
 */
function w2p_initialize_order_class() {
	if ( w2p_is_woocomerce_active() ) {

		/**
		 * Class W2P_Order
		 *
		 * Extends WooCommerce WC_Order to add custom functionality.
		 *
		 * @package W2P
		 * @since 1.0.0
		 */
		class W2P_Order extends WC_Order {

			/**
			 * Indicates if the order is valid.
			 *
			 * @var bool
			 */
			public $is_order = false;

			/**
			 * Constructor for the W2P_Order class.
			 *
			 * @param int $order_id The ID of the WooCommerce order.
			 */
			public function __construct( $order_id = 0 ) {
				try {
					$order = wc_get_order( $order_id );
					if ( $order ) {
						parent::__construct( $order_id );
						$this->is_order = true;
					} else {
						$this->is_order = false;
					}
				} catch ( \Throwable $e ) {
					w2p_add_error_log( 'Error in W2P_Order constructor: ' . $e->getMessage(), 'W2P_Order->__construct()' );
					w2p_add_error_log( "Parameters passed: order_id = $order_id", 'W2P_Order->__construct()' );
				}
			}

			/**
			 * Retrieves detailed data about the order.
			 *
			 * @return array The order data.
			 */
			public function get_data() {
				try {
					$data = parent::get_data();

					$queries = W2P_Query::get_queries(
						true,
						array(
							'category'  => W2P_CATEGORY['deal'],
							'source_id' => $data['id'],
						),
						1,
						-1
					)['data'];

					$data['queries']         = $queries;
					$data['products']        = $this->get_products();
					$data['state']           = $this->get_state( $queries );
					$data['deal_id']         = isset( $queries[0] ) ? $queries[0]['target_id'] : null;
					$currency                = get_option( 'woocommerce_currency' );
					$data['currency_symbol'] = get_woocommerce_currency_symbol( $currency );

					$customer_id      = $data['customer_id'];
					$data['customer'] = null;

					if ( $customer_id ) {
						$customer = get_userdata( $customer_id );
						if ( $customer ) {
							$data['customer'] = array(
								'ID'         => $customer->ID,
								'user_login' => $customer->user_login,
								'user_email' => $customer->user_email,
								'first_name' => get_user_meta( $customer->ID, 'first_name', true ),
								'last_name'  => get_user_meta( $customer->ID, 'last_name', true ),
							);
						}
					}
					return $data;
				} catch ( \Throwable $e ) {
					w2p_add_error_log( 'Error in get_data: ' . $e->getMessage(), 'W2P_Order->get_data()' );
					w2p_add_error_log( 'Parameters passed: ' . wp_json_encode( $this, true ), 'W2P_Order->get_data()' );
					return array();
				}
			}

			/**
			 * Determines the state of the order based on associated queries.
			 *
			 * @param array $queries The queries associated with the order.
			 * @return string|null The state of the order.
			 */
			private function get_state( array $queries ) {
				try {
					if ( 0 === count( $queries ) ) {
						return 'NOT READY';
					}

					$last_query = $queries[0];
					if ( $last_query ) {
						if ( 'DONE' === $last_query['state'] ) {
							return 'SYNCED';
						} elseif ( 'ERROR' === $last_query['state'] || 'TODO' === $last_query['state'] ) {
							return 'NOT SYNCED';
						}
						return $last_query['state'];
					}
				} catch ( \Throwable $e ) {
					w2p_add_error_log( 'Error in get_state: ' . $e->getMessage(), 'W2P_Order->get_state()' );
					w2p_add_error_log( 'Parameters passed: ' . wp_json_encode( $queries, true ), 'W2P_Order->get_state()' );
					return null;
				}
			}

			/**
			 * Retrieves the products in the order.
			 *
			 * @return array The product details.
			 */
			private function get_products() {
				try {
					$products = array();
					foreach ( parent::get_items() as $item_id => $item ) {
						$data                 = array();
						$data['product_id']   = $item->get_product_id();
						$data['variation_id'] = $item->get_variation_id();
						$data['product_name'] = $item->get_name();
						$data['quantity']     = $item->get_quantity();
						$data['subtotal']     = $item->get_subtotal();
						$data['total']        = $item->get_total();
						$data['tax']          = $item->get_subtotal_tax();
						$data['tax_class']    = $item->get_tax_class();
						$data['tax_status']   = $item->get_tax_status();
						$data['item_type']    = $item->get_type(); // e.g. "line_item", "fee".
						$products[]           = $data;
					}
					return $products;
				} catch ( \Throwable $e ) {
					w2p_add_error_log( 'Error in get_products: ' . $e->getMessage(), 'W2P_Order->get_products()' );
					w2p_add_error_log( 'Parameters passed: ' . wp_json_encode( $this, true ), 'W2P_Order->get_products()' );
					return array();
				}
			}

			/**
			 * Retrieves WooCommerce orders with pagination.
			 *
			 * @param int|null $order_id The order ID to retrieve (optional).
			 * @param int      $page The page number for pagination.
			 * @param int      $per_page The number of orders per page.
			 * @return array The orders data with pagination.
			 */
			public static function get_orders( ?int $order_id = null, int $page = 1, int $per_page = 10 ) {
				try {
					$orders_data = array();

					if ( null !== $order_id ) {
						$order = wc_get_order( $order_id );

						if ( $order ) {
							$orders        = array( $order );
							$total_items   = 1;
							$total_pages   = 1;
							$has_next_page = false;
						} else {
							return array(
								'success'    => false,
								'message'    => 'Order not found.',
								'data'       => array(),
								'pagination' => array(
									'total_items'   => 0,
									'total_pages'   => 0,
									'has_next_page' => false,
								),
							);
						}
					} else {
						$args = array(
							'limit' => $per_page,
							'paged' => $page,
						);

						$orders = wc_get_orders( $args );

						$total_items   = count( wc_get_orders( array( 'return' => 'ids' ) ) );
						$total_pages   = ceil( $total_items / $per_page );
						$has_next_page = $page < $total_pages;
					}

					foreach ( $orders as $order ) {
						$w2p_order     = new W2P_Order( $order->get_id() );
						$orders_data[] = $w2p_order->get_data();
					}

					return array(
						'success'    => true,
						'data'       => $orders_data,
						'pagination' => array(
							'total_items'   => $total_items,
							'total_pages'   => $total_pages,
							'has_next_page' => $has_next_page,
						),
					);
				} catch ( \Throwable $e ) {
					w2p_add_error_log( 'Error in get_orders: ' . $e->getMessage(), 'W2P_Order::get_orders()' );
					w2p_add_error_log( "Parameters passed: order_id = $order_id, page = $page, per_page = $per_page", 'W2P_Order::get_orders()' );
					return array(
						'success' => false,
						'message' => 'An error occurred while retrieving orders.',
						'data'    => array(),
					);
				}
			}
		}
	}
}

add_action( 'woocommerce_loaded', 'w2p_initialize_order_class' );
