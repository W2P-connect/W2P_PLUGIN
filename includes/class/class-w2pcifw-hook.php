<?php
/**
 * Handles hook operations for W2P.
 *
 * This file defines the W2PCIFW_Hook class, which provides methods for
 * managing and retrieving field-related data within the W2P plugin.
 *
 * @package W2P
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class W2PCIFW_Hook
 *
 * Handles hook-related operations in the W2P plugin.
 *
 * @package W2P
 * @since 1.0.0
 */
class W2PCIFW_Hook {

	/**
	 * Hook parameters from configuration.
	 *
	 * @var array
	 */
	private $hook_from_parameters;

	/**
	 * Source ID for the hook.
	 *
	 * @var int
	 */
	private $source_id;

	/**
	 * Constructor for W2PCIFW_Hook.
	 *
	 * Initializes the hook with the provided parameters and source ID. Throws an exception
	 * if the source ID is invalid or if the source is not defined in the allowed hook sources.
	 *
	 * @param array $hook_from_parameters Parameters for the hook configuration.
	 * @param int   $source_id            The source ID for the hook.
	 *
	 * @throws \InvalidArgumentThrowable If the source ID is invalid or the source is not recognized.
	 */
	public function __construct( array $hook_from_parameters, int $source_id ) {
		try {
			if ( $source_id < 0 ) {
				throw new \InvalidArgumentThrowable( "Invalid source_id for W2PCIFW_HOOK: $source_id" );
			}
			if ( ! isset( W2PCIFW_HOOK_SOURCES[ $hook_from_parameters['source'] ] ) ) {
				throw new \InvalidArgumentThrowable( "Invalid source for W2PCIFW_HOOK: {$hook_from_parameters['source']}" );
			}

			$this->hook_from_parameters = $hook_from_parameters;
			$this->source_id            = $source_id;
		} catch ( \Throwable $e ) {
			w2pcifw_add_error_log( 'Error in W2PCIFW_Hook constructor: ' . $e->getMessage(), 'W2PCIFW_Hook->__construct()' );
		}
	}

	/**
	 * Retrieves a formatted hook structure.
	 *
	 * @return array Formatted hook data.
	 */
	public function w2pcifw_get_formated_hook(): array {
		try {
			$formated_hook = array(
				'fields' => array(),
			);
			foreach ( $this->hook_from_parameters['fields'] as $field ) {
				$formated_field = $this->w2pcifw_format_hook_field( $field );
				if ( $formated_field ) {
					$formated_hook['fields'][] = $formated_field;
				}
			}

			$formated_hook['products']  = $this->get_order_products();
			$formated_hook['category']  = $this->hook_from_parameters['category'];
			$formated_hook['key']       = $this->hook_from_parameters['key'];
			$formated_hook['label']     = $this->hook_from_parameters['label'];
			$formated_hook['source']    = $this->hook_from_parameters['source'];
			$formated_hook['source_id'] = $this->source_id;
			return $formated_hook;
		} catch ( \Throwable $e ) {
			w2pcifw_add_error_log( 'Error in w2pcifw_get_formated_hook: ' . $e->getMessage(), 'W2PCIFW_Hook->w2pcifw_get_formated_hook()' );
			return array();
		}
	}

	/**
	 * Retrieves the last query with the same category, source, source_id and products than the current hook.
	 * If the last query has the same fields than the current hook, return the query object, otherwise return null.
	 *
	 * @return W2PCIFW_Query|null
	 */
	public function get_same_previous_query(): null|W2PCIFW_Query {

		$formated_hook = $this->w2pcifw_get_formated_hook();

		$last_query_query = W2PCIFW_Query::get_queries(
			false,
			array(
				'category'  => $formated_hook['category'],
				'source'    => $formated_hook['source'],
				'source_id' => $formated_hook['source_id'],
				'state'     => array( 'DONE', 'SENDED' ),
			),
			1,
			1
		)['data'];

		if ( isset( $last_query_query[0] ) ) {
			$last_quey_obj      = $last_query_query[0];
			$last_query_data    = $last_quey_obj->get_data();
			$last_query_payload = $last_query_data['payload'];

			unset( $last_query_payload['data'] );
			if (
				isset( $last_query_payload['fields'] ) &&
				w2pcifw_compare_arrays( $last_query_payload['fields'], $formated_hook['fields'] ) &&
				w2pcifw_compare_arrays( $last_query_payload['products'], $formated_hook['products'] )
			) {
				return $last_quey_obj;
			}
		}
		return null;
	}

	/**
	 * Retrieves products from an order if applicable.
	 *
	 * @return array|null Order products or null if not applicable.
	 */
	private function get_order_products(): ?array {
		try {
			if (
				'order' !== $this->hook_from_parameters['source'] ||
				'deal' !== $this->hook_from_parameters['category'] ||
				! $this->source_id
			) {
				return null;
			}

			$products        = array();
			$parameters      = w2pcifw_get_parameters();
			$deal_parameters = $parameters['w2p']['deal'];

			if ( isset( $deal_parameters['sendProducts'] ) && $deal_parameters['sendProducts'] ) {
				$order = wc_get_order( $this->source_id );

				if ( $order ) {
					foreach ( $order->get_items() as $item ) {
						$products[] = $this->format_product( $item, $deal_parameters );
					}
				}
			}

			return $products;
		} catch ( \Throwable $e ) {
			w2pcifw_add_error_log( "Error in get_order_products for order ID: {$this->source_id} - " . $e->getMessage(), 'W2PCIFW_Hook->get_order_products()' );
			return null;
		}
	}

	/**
	 * Formats an order item into a product array for deal payload.
	 *
	 * @param WC_Order_Item $item Order item to format.
	 * @param array         $deal_parameters Parameters of the deal.
	 *
	 * @return array|null Formated product array or null if an error occurred.
	 */
	private function format_product( $item, $deal_parameters ): array {
		try {
			$product_name = $item->get_name();
			$quantity     = ( (float) $item->get_quantity() )
				? (float) $item->get_quantity()
				: 1;

			$regular_price = (float) $item->get_subtotal() / $quantity;
			$sale_price    = (float) $item->get_total() / $quantity;
			$source_id     = $item->get_variation_id() ? $item->get_variation_id() : $item->get_product_id();

			if ( $source_id ) {
				$product_comment = isset( $deal_parameters['productsComment']['variables'] )
					? w2pcifw_format_variables(
						$deal_parameters['productsComment']['variables'],
						$source_id,
						$this->get_user_id(),
						false
					)
					: '';
			} else {
				$product_comment = '';
			}

			// Classe de taxe et calcul du taux de TVA.
			$tax_class = $item->get_meta( '_tax_class', true );
			$tax_rates = WC_Tax::get_rates( $tax_class );
			$tax_rate  = ! empty( $tax_rates ) ? reset( $tax_rates )['rate'] : 0;

			// Calcul du pourcentage de réduction.
			$discount_percentage = 0;
			if ( $regular_price > 0 && $sale_price > 0 ) {
				$discount_percentage = ( ( $regular_price - $sale_price ) / $regular_price ) * 100;
			}

			$currency = get_option( 'woocommerce_currency' );
			return array(
				'name'            => $product_name,
				'comments'        => $product_comment,
				'quantity'        => $quantity,
				'tax'             => $tax_rate,
				'discount'        => $discount_percentage,
				'discount_type'   => 'percentage',
				'tax_method'      => $deal_parameters['amountsAre'],
				'currency'        => $currency,
				'currency_symbol' => get_woocommerce_currency_symbol( $currency ),
				'item_price'      => 'inclusive' === $deal_parameters['amountsAre']
					? round( $regular_price * ( 1 + $tax_rate / 100 ), 2 )
					: $regular_price,
				'prices'          => array(
					'regular_price' => $regular_price,
					'sale_price'    => $sale_price,
				),
			);
		} catch ( \Throwable $e ) {
			w2pcifw_add_error_log( 'Error in format_product: ' . $e->getMessage(), 'W2PCIFW_Hook->format_product()' );
			return array();
		}
	}

	/**
	 * Formats a hook field.
	 *
	 * @param array $field The field to format.
	 *
	 * @return array|null Formatted field data or null if invalid.
	 */
	private function w2pcifw_format_hook_field( array $field ): ?array {
		try {
			if ( $field['enabled'] ) {
				return array(
					'pipedriveFieldId' => $field['pipedriveFieldId'],
					'condition'        => $field['condition'],
					'values'           => w2pcifw_is_logic_block_value( $field['value'] )
						? w2pcifw_format_logic_blocks( $field['value'], $this->source_id, $this->get_user_id() )
						: array( $field['value'] ),
				);
			}
			return null;
		} catch ( \Throwable $e ) {
			w2pcifw_add_error_log( 'Error in w2pcifw_format_hook_field: ' . $e->getMessage(), 'W2PCIFW_Hook->w2pcifw_format_hook_field()' );
			return null;
		}
	}

	/**
	 * Retrieves the user ID based on the hook's source.
	 *
	 * @return int|null User ID or null if not applicable.
	 */
	private function get_user_id(): ?int {
		try {
			$source = $this->hook_from_parameters['source'];
			if ( W2PCIFW_HOOK_SOURCES['user'] === $source ) {
				return $this->source_id;
			} elseif ( W2PCIFW_HOOK_SOURCES['order'] === $source ) {
				return w2pcifw_get_customer_id_from_order_id( $this->source_id );
			}
			return null;
		} catch ( \Throwable $e ) {
			w2pcifw_add_error_log( 'Error in get_user_id: ' . $e->getMessage(), 'W2PCIFW_Hook->get_user_id()' );
			return null;
		}
	}

	/**
	 * Retrieves the Pipedrive target ID based on the hook's category and source.
	 *
	 * This method determines the target ID for a Pipedrive entity (person, organization, or deal)
	 * based on the category and source provided in the hook parameters. It fetches the target ID
	 * either from user metadata or from an order's meta field.
	 *
	 * @return int|null The target ID if found, or null if not available.
	 *
	 * @throws \Throwable Logs any exception that occurs during the process.
	 */
	public function get_pipedrive_target_id() {
		try {
			$meta_key  = w2pcifw_get_meta_key( $this->hook_from_parameters['category'], 'id' );
			$target_id = null;

			if (
				W2PCIFW_CATEGORY['person'] === $this->hook_from_parameters['category']
				|| W2PCIFW_CATEGORY['organization'] === $this->hook_from_parameters['category']
			) {
				$user      = new W2PCIFW_User( $this->get_user_id() );
				$target_id = $user->get( $meta_key, 'id' );
			} elseif (
				W2PCIFW_CATEGORY['deal'] === $this->hook_from_parameters['category']
				&& 'order' === $this->hook_from_parameters['source']
			) {
				$order = wc_get_order( $this->source_id );
				if ( $order ) {
					$target_id = $order->get_meta( $meta_key, 'id' );
				}
			}

			return $target_id;
		} catch ( \Throwable $e ) {
			w2pcifw_add_error_log( 'Error in get_pipedrive_target_id: ' . $e->getMessage(), 'W2PCIFW_Hook->get_pipedrive_target_id()' );
			return null;
		}
	}
}
