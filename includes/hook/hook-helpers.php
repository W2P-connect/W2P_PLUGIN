<?php
/**
 * Hook Helpers
 *
 * This file contains utility functions used by the W2P plugin to interact with hook.
 *
 * @package W2P
 * @since 1.0.0
 */

/**
 * Check if the given value is a logic block value.
 *
 * A logic block value is an array of arrays, where each sub-array has a 'variables' key.
 *
 * @param  array $value The value to check.
 * @return bool        Whether the value is a logic block value.
 */
function w2p_is_logic_block_value( $value ) {
	try {
		if ( ! is_array( $value ) ) {
			return false;
		} else {
			foreach ( $value as $sub_value ) {
				if ( ! is_array( $sub_value ) || ( is_array( $sub_value ) && ! array_key_exists( 'variables', $sub_value ) ) ) {
					return false;
				}
			}
			return true;
		}
	} catch ( Throwable $e ) {
		w2p_add_error_log( 'Error in w2p_is_logic_block_value: ' . $e->getMessage(), 'w2p_is_logic_block_value' );
		w2p_add_error_log( 'Parameter passed: ' . wp_json_encode( $value, JSON_PRETTY_PRINT ), 'w2p_is_logic_block_value' );
		return false;
	}
}

/**
 * Formats logic blocks by retrieving values for each variable.
 *
 * This function processes an array of logic blocks, retrieving and formatting
 * values for each variable within the blocks, using the provided source ID and user ID.
 *
 * @param array $logic_blocks An array of logic blocks to format.
 * @param int   $source_id    The source ID used for value retrieval.
 * @param int   $user_id      The user ID used for value retrieval.
 *
 * @return array An array of formatted logic block values.
 */
function w2p_format_logic_blocks( array $logic_blocks, int $source_id, int $user_id ): array {
	try {
		$formated_logic_blocks = array();

		foreach ( $logic_blocks as $sub_block ) {
			$formated_values = array();
			foreach ( $sub_block['variables'] as $variable ) {
				$value             = w2p_get_variable_value( $variable, $source_id, $user_id );
				$formated_values[] = $value;
			}
			$formated_logic_blocks[] = $formated_values;
		}
		return $formated_logic_blocks;
	} catch ( Throwable $e ) {
		w2p_add_error_log( 'Error in w2p_format_logic_blocks: ' . $e->getMessage(), 'w2p_format_logic_blocks' );
		w2p_add_error_log( 'Parameters passed: ' . wp_json_encode( compact( 'logic_blocks', 'source_id', 'user_id' ), JSON_PRETTY_PRINT ), 'w2p_format_logic_blocks' );
		return array();
	}
}

/**
 * Formats an array of variables by retrieving their values and optionally returns them as a string.
 *
 * This function processes each variable in the provided array, retrieves its value using
 * w2p_get_variable_value, and filters out empty or null values. The result can be returned
 * as an array or a concatenated string based on the $to_array parameter.
 *
 * @param array $variables_array The array of variables to format.
 * @param int   $source_id       The source ID for the variable retrieval.
 * @param int   $user_id         The user ID for the variable retrieval.
 * @param bool  $to_array        Whether to return the result as an array or a string. Defaults to true.
 *
 * @return array|string The formatted values as an array or a string.
 */
function w2p_format_variables( array $variables_array, $source_id, $user_id, $to_array = true ): array|string {
	try {
		$formated_values = array();

		foreach ( $variables_array as $variable ) {
			$value             = w2p_get_variable_value( $variable, $source_id, $user_id );
			$formated_values[] = $value;
		}

		$filtered_values = array_filter(
			$formated_values,
			function ( $value ) {
				return '' !== $value || null !== $value;
			}
		);

		if ( ! $to_array ) {
			$filtered_values = implode( ' ', $filtered_values );
		}
		return $filtered_values;
	} catch ( Throwable $e ) {
		w2p_add_error_log( 'Error in w2p_format_variables: ' . $e->getMessage(), 'w2p_format_variables' );
		w2p_add_error_log( 'Parameters passed: ' . wp_json_encode( compact( 'variables_array', 'source_id', 'user_id', 'to_array' ), JSON_PRETTY_PRINT ), 'w2p_format_variables' );
		return $to_array ? array() : '';
	}
}

/**
 * Retrieves the value of a variable in a given source (user, order, product, or w2p).
 *
 * @param array $variable The variable data containing the source and the value to retrieve.
 * @param int   $source_id The ID of the source to retrieve the value from.
 * @param int   $user_id   The ID of the user to retrieve the value from if the source is user.
 *
 * @return string|null The retrieved value or null if an error occurs.
 */
function w2p_get_variable_value( array $variable, ?int $source_id, ?int $user_id ) {
	try {
		if ( isset( $variable['isFreeField'] ) && $variable['isFreeField'] ) {
			return $variable['value'];
		} else {
			$value = null;
			if ( W2P_HOOK_SOURCES['user'] === $variable['source'] ) {
				if ( $user_id ) {
					$user = new W2P_User( $user_id );
					if ( $user ) {
						$value = $user->get( $variable['value'] );
					}
				}
			} elseif ( W2P_HOOK_SOURCES['order'] === $variable['source'] ) {
				$order = wc_get_order( $source_id );
				if ( $order ) {
					$value = w2p_get_order_value( $order, $variable['value'] );
				} else {
					w2p_add_error_log( "Order ID #$source_id is not valid while trying to wc_get_order source, searching value for " . $variable['value'], 'w2p_get_variable_value' );
				}
			} elseif ( W2P_HOOK_SOURCES['product'] === $variable['source'] ) {
				$product = wc_get_product( $source_id );
				if ( $product ) {
					$value = w2p_get_product_value( $product, $variable['value'] );
				} else {
					w2p_add_error_log( "Product ID #$source_id is not valid while trying to wc_get_product, searching value for " . $variable['value'], 'w2p_get_variable_value' );
				}
			} elseif ( 'w2p' === $variable['source'] ) {
				$value = w2p_get_w2p_value( $variable['value'] );
			}
			return $value;
		}
	} catch ( Throwable $e ) {
		w2p_add_error_log( 'Error in w2p_get_variable_value: ' . $e->getMessage(), 'w2p_get_variable_value' );
		w2p_add_error_log( 'Parameters passed: ' . wp_json_encode( compact( 'variable', 'source_id', 'user_id' ), JSON_PRETTY_PRINT ), 'w2p_get_variable_value' );
		return null;
	}
}

/**
 * Retrieves a value from the internal W2P fields.
 *
 * @param string $value The value to retrieve.
 *
 * @return string|null The retrieved value or null if an error occurs.
 * @throws Exception If the value is not recognized.
 */
function w2p_get_w2p_value( $value ) {
	try {
		switch ( $value ) {
			case 'w2p_current_time':
				return current_time( 'mysql' );
			case 'w2p_current_date':
				return current_time( 'Y-m-d' );
			case 'w2p_website_domain':
				return wp_parse_url( home_url(), PHP_URL_HOST );
			case 'w2p_site_title':
				return get_bloginfo( 'name' );
			default:
				throw new Exception( "Unknown meta key: $value" );
		}
	} catch ( Throwable $e ) {
		w2p_add_error_log( 'Error in w2p_get_w2p_value: ' . $e->getMessage(), 'w2p_get_w2p_value' );
		w2p_add_error_log( 'Parameters passed: ' . wp_json_encode( compact( 'value' ), JSON_PRETTY_PRINT ), 'w2p_get_w2p_value' );
		return null;
	}
}

/**
 * Retrieves a value from a product object.
 *
 * @param WC_Product $product The product object.
 * @param string     $value   The value to retrieve.
 *
 * @return string|null The retrieved value or null if an error occurs.
 */
function w2p_get_product_value( $product, $value ) {
	try {
		switch ( $value ) {
			case 'id':
				return $product->get_id();
			case 'name':
				return $product->get_name();
			case 'attribute_summary':
				if ( $product->is_type( 'variation' ) ) {
					return $product->get_attribute_summary();
				} else {
					return '';
				}
			case 'slug':
				return $product->get_slug();
			case 'short_description':
				return $product->get_short_description();
			case 'price':
				return $product->get_price();
			case 'regular_price':
				return $product->get_regular_price();
			case 'sale_price':
				return $product->get_sale_price();
			case 'stock_quantity':
				return $product->get_stock_quantity();
			case 'sku':
				return $product->get_sku();
			case 'shipping_class':
				return $product->get_shipping_class();
			case 'weight':
				return $product->get_weight();
			case 'length':
				return $product->get_length();
			case 'width':
				return $product->get_width();
			case 'height':
				return $product->get_height();
			case 'tax_class':
				return $product->get_tax_class();
			case 'categories':
				return implode( ', ', wp_get_post_terms( $product->get_id(), 'product_cat', array( 'fields' => 'names' ) ) );
			case 'tags':
				return implode( ', ', wp_get_post_terms( $product->get_id(), 'product_tag', array( 'fields' => 'names' ) ) );
			case 'attributes':
				$attributes = $product->get_attributes();
				return implode(
					', ',
					array_map(
						function ( $key, $value ) {
							return "$key: $value";
						},
						array_keys( $attributes ),
						$attributes
					)
				);

			case 'default_attributes':
				$attributes = $product->get_default_attributes();
				return implode(
					', ',
					array_map(
						function ( $key, $value ) {
							return "$key: $value";
						},
						array_keys( $attributes ),
						$attributes
					)
				);

			default:
				return $product->get_meta( $value );
		}
	} catch ( Throwable $e ) {
		w2p_add_error_log( 'Error in w2p_get_product_value: ' . $e->getMessage(), 'w2p_get_product_value' );
		w2p_add_error_log( 'Parameters passed: ' . wp_json_encode( compact( 'product', 'value' ), JSON_PRETTY_PRINT ), 'w2p_get_product_value' );
		return null;
	}
}

/**
 * Retrieves a value from an order.
 *
 * @param \WC_Order $order The order to retrieve the value from.
 * @param string    $value The value to retrieve.
 *
 * @return mixed The retrieved value.
 */
function w2p_get_order_value( $order, $value ) {
	try {
		switch ( $value ) {
			case 'id':
				return $order->get_id();
			case 'billing_first_name':
				return $order->get_billing_first_name();
			case 'billing_last_name':
				return $order->get_billing_last_name();
			case 'billing_company':
				return $order->get_billing_company();
			case 'billing_email':
				return $order->get_billing_email();
			case 'billing_phone':
				return $order->get_billing_phone();
			case 'billing_address_1':
				return $order->get_billing_address_1();
			case 'billing_address_2':
				return $order->get_billing_address_2();
			case 'billing_city':
				return $order->get_billing_city();
			case 'billing_postcode':
				return $order->get_billing_postcode();
			case 'billing_country':
				return $order->get_billing_country();
			case 'billing_state':
				return $order->get_billing_state();
			case 'shipping_first_name':
				return $order->get_shipping_first_name();
			case 'shipping_last_name':
				return $order->get_shipping_last_name();
			case 'shipping_company':
				return $order->get_shipping_company();
			case 'shipping_address_1':
				return $order->get_shipping_address_1();
			case 'shipping_address_2':
				return $order->get_shipping_address_2();
			case 'shipping_city':
				return $order->get_shipping_city();
			case 'shipping_postcode':
				return $order->get_shipping_postcode();
			case 'shipping_country':
				return $order->get_shipping_country();
			case 'shipping_state':
				return $order->get_shipping_state();
			case '_order_total':
				return $order->get_total();
			case '_order_total_excl_tax':
				return $order->get_total() - $order->get_total_tax();
			case '_order_tax':
				return $order->get_total_tax();
			case '_order_shipping':
				return $order->get_shipping_total();
			case '_order_discount':
				return $order->get_total_discount();
			case '_payment_method':
				return $order->get_payment_method();
			case '_order_currency':
				return $order->get_currency();
			case '_order_status':
				return $order->get_status();
			case '_shipping_method':
				return implode( ', ', $order->get_shipping_methods() );
			default:
				return $order->get_meta( $value );
		}
	} catch ( Throwable $e ) {
		w2p_add_error_log( 'Error in w2p_get_order_value: ' . $e->getMessage(), 'w2p_get_order_value' );
		w2p_add_error_log( 'Parameters passed: ' . wp_json_encode( compact( 'order', 'value' ), JSON_PRETTY_PRINT ), 'w2p_get_order_value' );
		return null;
	}
}

/**
 * Handles custom hooks.
 *
 * @param array $hook Hook configuration.
 * @param mixed $args Hook arguments.
 *
 * @return void
 */
function w2p_handle_custom_hook( $hook, $args ) {
	try {
		$source_id = null;
		$user_id   = get_current_user_id();
		$hook_key  = $hook['key'];

		switch ( $hook_key ) {
			case 'user_register':
			case 'profile_update':
				$source_id = is_object( $args ) ? $args->ID : $args;
				break;

			case 'wp_login':
				$user = get_user_by( 'login', $args );
				if ( $user ) {
					$source_id = $user->ID;
				}
				break;

			case 'woocommerce_new_order':
				$source_id = $args;
				$user_id   = w2p_get_customer_id_from_order_id( $source_id );
				break;

			case 'woocommerce_add_to_cart':
			case 'woocommerce_after_cart_item_quantity_update':
			case 'woocommerce_remove_cart_item':
				$source_id = $args;
				$user_id   = get_current_user_id();
				break;

			default:
				if ( str_starts_with( $hook_key, 'woocommerce_order_status' )
				|| 'woocommerce_update_order' === $hook_key
				) {
					$source_id = $args;
					$user_id   = w2p_get_customer_id_from_order_id( $source_id );
				} else {
					w2p_add_error_log( "/!\ Hook $hook_key ($hook[category] from $hook[source]) not recognized /!\ ", 'w2p_handle_custom_hook' );
				}
				break;
		}

		if ( $source_id && is_int( $source_id ) ) {
			$hook_obj      = new W2P_Hook( $hook, $source_id );
			$formated_hook = $hook_obj->w2p_get_formated_hook();

			$transient_key = 'w2p_hook_' . md5( $formated_hook['category'] . '_' . $formated_hook['source'] . '_' . $formated_hook['source_id'] . '_' . $formated_hook['label'] );
			$previous_data = get_transient( $transient_key );

			if ( $hook_obj->get_same_previous_query() ) {
				w2p_add_error_log( "$hook_key : Nothing to update for the source id $source_id of the category $hook[category] (last query were the same)", 'w2p_register_user_defined_hooks' );
				return;
			}

			$query = W2P_Query::create_query(
				$formated_hook['category'],
				$formated_hook['source'],
				$formated_hook['source_id'],
				$formated_hook['label'],
				$formated_hook
			);

			w2p_add_error_log( "Hook $hook_key (category: $hook[category]) triggered for source: $source_id (source: $formated_hook[source]), user: $user_id", 'w2p_handle_custom_hook' );
		} else {
			$stringified_args = wp_json_encode( $args, JSON_PRETTY_PRINT );
			w2p_add_error_log( "/!\ Unable to retrieve ID for hook $hook_key ($hook[category]) triggered for source: $source_id ($hook[source]) /!\ : \n$stringified_args", 'w2p_handle_custom_hook' );
		}
	} catch ( Throwable $e ) {
		w2p_add_error_log( 'Error in w2p_handle_custom_hook: ' . $e->getMessage(), 'w2p_handle_custom_hook' );
		w2p_add_error_log( 'Parameters passed: ' . wp_json_encode( compact( 'hook', 'args' ), JSON_PRETTY_PRINT ), 'w2p_handle_custom_hook' );
	}
}

/**
 * Finds a reference hook from W2P_HOOK_LIST given a key.
 *
 * @param string $key The key of the hook to find.
 *
 * @return array|null The hook if found, null otherwise.
 */
function w2p_find_reference_hook( $key ) {
	foreach ( W2P_HOOK_LIST as $hook ) {
		if ( $hook['key'] === $key ) {
			return $hook;
		}
	}
	return null;
}

/**
 * Retrieves a W2P_Hook object based on the provided key, category, and source ID.
 *
 * @param string $key The key of the hook to retrieve.
 * @param string $category The category of the hook.
 * @param int    $source_id The source ID associated with the hook.
 *
 * @return W2P_Hook|null The W2P_Hook object if found and enabled, null otherwise.
 */
function w2p_get_hook( string $key, string $category, int $source_id ) {
	$parameters = w2p_get_parameters();
	$hooks      = isset( $parameters['w2p']['hookList'] )
		? $parameters['w2p']['hookList']
		: array();

	if ( $source_id && in_array( $category, W2P_CATEGORY, true ) ) {
		foreach ( $hooks as $hook ) {
			if ( isset( $hook['enabled'] )
				&& true === $hook['enabled']
				&& $key === $hook['key']
				&& $category === $hook['category']
			) {
				return new W2P_Hook( $hook, $source_id );
			}
		}
	}

	return null;
}

/**
 * Registers all user-defined hooks based on the `w2p` parameter.
 *
 * Iterates over the `hookList` parameter and registers each hook with the
 * corresponding action using the `add_action` function. The priority of the
 * hook is determined by the `W2P_HOOK_PRIORITY` constant and the category of
 * the hook.
 *
 * @since 1.0.0
 */
function w2p_register_user_defined_hooks() {
	$parameters = w2p_get_parameters();
	$hooks      = isset( $parameters['w2p']['hookList'] ) ? $parameters['w2p']['hookList'] : array();

	foreach ( $hooks as $hook ) {
		try {
			if ( isset( $hook['enabled'] ) && $hook['enabled'] ) {
				$hook_key  = $hook['key'];
				$category  = $hook['category'];
				$reference = w2p_find_reference_hook( $hook['key'] );

				if ( null !== $reference ) {
					$hook = array_merge( $hook, $reference );

					$priority = W2P_HOOK_PRIORITY[ $category ];

					if ( 'woocommerce_cart_updated' === $hook_key ) {
						add_action(
							'woocommerce_add_to_cart',
							function () use ( $hook ) {
								$order_id = w2p_get_current_checkout_order_id();
								$order_id &&
									w2p_handle_cart_updated(
										array_merge(
											$hook,
											array(
												'label'  => 'Product added to cart',
												'key'    => 'woocommerce_add_to_cart',
												'source' => 'order',
											)
										),
										$order_id
									);
							},
							$priority,
						);
						add_action(
							'woocommerce_after_cart_item_quantity_update',
							function () use ( $hook ) {
								$order_id = w2p_get_current_checkout_order_id();
								$order_id &&
									w2p_handle_cart_updated(
										array_merge(
											$hook,
											array(
												'label'  => 'Product quantity updated from cart',
												'key'    => 'woocommerce_after_cart_item_quantity_update',
												'source' => 'order',
											)
										),
										$order_id,
									);
							},
							$priority,
						);
						add_action(
							'woocommerce_remove_cart_item',
							function () use ( $hook ) {
								$order_id = w2p_get_current_checkout_order_id();
								$order_id &&
									w2p_handle_cart_updated(
										array_merge(
											$hook,
											array(
												'label'  => 'Product removed from cart',
												'key'    => 'woocommerce_remove_cart_item',
												'source' => 'order',
											)
										),
										$order_id
									);
							},
							$priority,
						);
					} else {
						add_action(
							$hook_key,
							function ( $entity_id ) use ( $hook, $hook_key ) {
								if ( 'woocommerce_update_order' === $hook_key ) {
									if ( get_current_user_id() ) {
										$disabled = get_transient( "disable_hook_update_order_$entity_id" );
										if ( $disabled ) {
											set_transient( "fired_woocommerce_update_ order_from_card_$entity_id", false );
										} else {
											w2p_handle_custom_hook( $hook, $entity_id );
										}
									}
								} else {
									w2p_handle_custom_hook( $hook, $entity_id );
								}
							},
							$priority,
							1
						);
						if ( isset( $hook['linked_hooks_key'] ) ) {
							foreach ( $hook['linked_hooks_key'] as $linked_hook_key ) {
								add_action(
									$linked_hook_key,
									function ( $entity_id ) use ( $hook, $linked_hook_key ) {
										w2p_handle_custom_hook( $hook, $entity_id );
									},
									$priority,
									1
								);
							}
						}
					}
				}
			}
		} catch ( Throwable $e ) {
			w2p_add_error_log( 'Error in registering hook: ' . $e->getMessage(), 'w2p_register_user_defined_hooks' );
		}
	}
}

add_action( 'w2p_check_last_woocommerce_update_order', 'w2p_check_last_woocommerce_update_order_handler', 10, 3 );

/**
 * Handles the last update order hook for WooCommerce.
 *
 * This function checks if the last fired iteration for updating a WooCommerce
 * order matches the current iteration. If it does, it triggers a custom hook
 * for the order and resets the transient storing the last iteration.
 *
 * @param array $hook      The hook configuration.
 * @param int   $order_id  The ID of the WooCommerce order.
 * @param int   $iteration The current iteration number.
 *
 * @return void
 */
function w2p_check_last_woocommerce_update_order_handler( $hook, $order_id, $iteration ) {
	try {
		$last_iteration = get_transient( "last_iteration_fired_woocommerce_update_order_from_card_$order_id" );
		if ( $last_iteration === $iteration ) {
			w2p_handle_custom_hook(
				$hook,
				$order_id,
			);
			set_transient( "last_iteration_fired_woocommerce_update_order_from_card_$order_id", 0, 10 );
		}
	} catch ( Throwable $e ) {
		w2p_add_error_log( 'Error handling cart update ' . $e->getMessage(), 'w2p_check_last_woocommerce_update_order_handler' );
	}
}

/**
 * Handles cart updates by triggering a custom hook and scheduling a check for the last fired iteration.
 *
 * This function is used to handle cart updates triggered by the following actions:
 * - woocommerce_add_to_cart
 * - woocommerce_after_cart_item_quantity_update
 * - woocommerce_remove_cart_item
 *
 * It sets a transient to disable the hook update_order and stores the current iteration number.
 * Then it schedules a single event to check if the last fired iteration matches the current iteration.
 * If it does, it triggers a custom hook for the order and resets the transient storing the last iteration.
 *
 * @param array $hook      The hook configuration.
 * @param int   $order_id  The ID of the WooCommerce order.
 *
 * @return void
 */
function w2p_handle_cart_updated( $hook, $order_id ) {
	try {
		set_transient( "disable_hook_update_order_$order_id", true, 60 );
		$iteration = did_action( 'woocommerce_update_order' );
		set_transient( "last_iteration_fired_woocommerce_update_order_from_card_$order_id", $iteration, 10 );

		w2p_create_or_update_order_from_api(); // forcing update order at the end.
		add_action(
			'wp_footer',
			function () {
				w2p_create_or_update_order_from_api(); // forcing update order at the end.
			}
		);

		if ( ! wp_next_scheduled( 'w2p_check_last_woocommerce_update_order', array( $hook, $order_id, $iteration ) ) ) {
			wp_schedule_single_event(
				time() + 2,
				'w2p_check_last_woocommerce_update_order',
				array( $hook, $order_id, $iteration )
			);
		}

		$next_event = wp_get_scheduled_event( 'w2p_check_last_woocommerce_update_order', array( $hook, $order_id, $iteration ) );
		if ( ! $next_event ) {
			w2p_add_error_log( 'No scheduled event for w2p_check_last_woocommerce_update_order', 'w2p_handle_cart_updated' );
		}
	} catch ( Throwable $e ) {
		w2p_add_error_log( 'Error in handling cart update: ' . $e->getMessage(), 'w2p_handle_cart_updated' );
		w2p_add_error_log( 'Parameters passed: ' . wp_json_encode( compact( 'hook', 'order_id' ), JSON_PRETTY_PRINT ), 'w2p_handle_cart_updated' );
	}
}


/**
 * Creates or updates a WooCommerce order from the API response.
 *
 * This function sends a GET request to the WooCommerce store checkout endpoint
 * and attempts to retrieve the order ID from the response. If successful, it
 * returns the order ID; otherwise, it logs an error and returns null.
 *
 * @return int|null The WooCommerce order ID if successful, or null if an error occurs.
 */
function w2p_create_or_update_order_from_api() {
	try {
		$order_id = null;

		$request = new WP_REST_Request( 'GET', '/wc/store/v1/checkout' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$response = rest_do_request( $request );

		if ( is_wp_error( $response ) ) {
			w2p_add_error_log( 'Error API : ' . $response->get_error_message(), 'w2p_create_order_from_api' );
			return null;
		}

		if ( method_exists( $response, 'get_data' ) ) {
			$data = $response->get_data();

			if ( ! empty( $data ) && isset( $data['order_id'] ) ) {
				$order_id = $data['order_id'];
			} else {
				w2p_add_error_log( 'No data in response or order_id missing.', 'w2p_create_order_from_api' );
			}
		} else {
			w2p_add_error_log( 'Response is invalid or does not contain get_data().', 'w2p_create_order_from_api' );
		}
		return $order_id;
	} catch ( Throwable $e ) {
		w2p_add_error_log( 'Erreur lors de la création de la commande : ' . $e->getMessage(), 'woocommerce_api_debug' );
		return null;
	}
}

/**
 * Retrieves the current checkout order ID for the logged-in user.
 *
 * This function first checks if there is a logged-in user. If so, it attempts to
 * retrieve the order ID from the WooCommerce session. If not found, it creates
 * or updates an order via the API. Logs an error and returns null if any exception
 * occurs during the process.
 *
 * @return int|null The order ID if available, or null if not found or an error occurs.
 */
function w2p_get_current_checkout_order_id() {
	try {
		if ( get_current_user_id() ) {
			$order_id = WC()->session->get( 'store_api_draft_order' );
			if ( ! $order_id ) {
				$order_id = w2p_create_or_update_order_from_api();
			}
			return $order_id;
		} else {
			return null;
		}
	} catch ( Throwable $e ) {
		w2p_add_error_log( 'Error while getting order_id: ' . $e->getMessage(), 'w2p_manage_cart_and_order' );
		return null;
	}
}

w2p_register_user_defined_hooks();
