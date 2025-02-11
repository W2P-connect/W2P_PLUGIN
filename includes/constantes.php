<?php
/**
 * W2P Plugin constants.
 *
 * All constants are defined here.
 *
 * @package W2P
 * @since 1.0.0
 */

if ( ! defined( 'W2P_DISTANT_REST_URL' ) ) {
	if(w2p_is_local_environment()){
		// define( 'W2P_DISTANT_REST_URL', 'https://woocommerce-to-pipedrive.com/api/v1' );
		define( 'W2P_DISTANT_REST_URL', 'http://localhost:3000/api/v1' );
		
	} else {
		define( 'W2P_DISTANT_REST_URL', 'https://woocommerce-to-pipedrive.com/api/v1' );
	}
}

if ( ! defined( 'W2P_VARIABLE_SOURCES' ) ) {
	define(
		'W2P_VARIABLE_SOURCES',
		array(
			'user'    => 'user',
			'order'   => 'order',
			'product' => 'product',
			'w2p'     => 'w2p',
		)
	);
}

if ( ! defined( 'W2P_HOOK_SOURCES' ) ) {
	define(
		'W2P_HOOK_SOURCES',
		array(
			'user'    => 'user',
			'order'   => 'order',
			'product' => 'product',
		)
	);
}

if ( ! defined( 'W2P_META_KEYS' ) ) {
	define(
		'W2P_META_KEYS',
		array(
			array(
				'label'         => 'Woocomerce (order)',
				'description'   => null,
				// 'toolTip' => "Please note, Woocommerce meta keys are generally only completed by the customer.
				// when the first order is placed. They are therefore often empty when the user registers.",.
				'subcategories' => array(
					array(
						'label'    => 'order',
						'metaKeys' => array(
							array(
								'label'       => 'id',
								'value'       => 'id',
								'source'      => 'order',
								'description' => 'Order Id.',
								'exemple'     => '6452',
							),
							array(
								'label'       => 'Order total',
								'value'       => '_order_total',
								'source'      => 'order',
								'description' => 'Total amount of the order including taxes.',
								'exemple'     => '100.00',
							),
							array(
								'label'       => 'Order total (no tax)',
								'recommanded' => true,
								'source'      => 'order',
								'value'       => '_order_total_excl_tax',
								'description' => 'Total amount of the order excluding taxes.',
								'exemple'     => '84.00',
							),
							array(
								'label'       => 'Order total tax',
								'value'       => '_order_tax',
								'source'      => 'order',
								'description' => 'Total tax amount for the order.',
								'exemple'     => '16.00',
							),
							array(
								'label'       => 'Order shipping amount',
								'value'       => '_order_shipping',
								'source'      => 'order',
								'description' => 'Total shipping cost for the order.',
								'exemple'     => '10.00',
							),
							array(
								'label'       => 'Order discount',
								'value'       => '_order_discount',
								'source'      => 'order',
								'description' => 'Total discount applied to the order.',
								'exemple'     => '5.00',
							),
							array(
								'label'       => 'Payment method',
								'value'       => '_payment_method',
								'source'      => 'order',
								'description' => 'Payment method used for the order.',
								'exemple'     => 'paypal',
							),
							array(
								'label'       => 'Order currency',
								'value'       => '_order_currency',
								'source'      => 'order',
								'description' => 'Currency used for the order.',
								'exemple'     => 'USD',
							),
							array(
								'label'       => 'Order status',
								'value'       => '_order_status',
								'source'      => 'order',
								'recommanded' => true,
								'description' => 'Status of the order.',
								'exemple'     => 'completed',
							),
							array(
								'label'       => 'Shipping method',
								'value'       => '_shipping_method',
								'source'      => 'order',
								'description' => 'Shipping method used for the order.',
								'exemple'     => 'flat_rate',
							),
							array(
								'label'       => 'Customer note',
								'value'       => '_customer_note',
								'source'      => 'order',
								'description' => 'Note provided by the customer during checkout.',
								'exemple'     => 'Please leave the package at the front door.',
							),
						),
					),
				),
				'allowedSource' => array( W2P_HOOK_SOURCES['order'] ),
			),
			array(
				'label'         => 'Woocomerce (product)',
				'description'   => null,
				// 'toolTip' => "Please note, Woocommerce meta keys are generally only completed by the customer
				// when the first order is placed. They are therefore often empty when the user registers.",
				'subcategories' => array(
					array(
						'label'    => 'product',
						'metaKeys' => array(
							array(
								'label'       => 'id',
								'value'       => 'id',
								'source'      => 'product',
								'description' => 'Product ID.',
								'exemple'     => '123',
							),
							array(
								'label'       => 'name',
								'value'       => 'name',
								'source'      => 'product',
								'recommanded' => true,
								'description' => 'Product name.',
								'exemple'     => 'T-Shirt',
							),
							array(
								'label'       => 'attribute_summary',
								'recommanded' => true,
								'value'       => 'attribute_summary',
								'source'      => 'product',
								'description' => 'Summary of attributes for variations of variable products.',
								'exemple'     => 'Size: S, M, L - Color: Red, Blue, Green',
							),
							array(
								'label'       => 'slug',
								'value'       => 'slug',
								'source'      => 'product',
								'description' => 'Product slug (permalink).',
								'exemple'     => 't-shirt',
							),
							array(
								'label'       => 'short_description',
								'value'       => 'short_description',
								'source'      => 'product',
								'description' => 'Short description of the product.',
								'exemple'     => 'Stylish t-shirt.',
							),
							array(
								'label'       => 'sku',
								'value'       => 'sku',
								'source'      => 'product',
								'description' => 'Product SKU.',
								'exemple'     => 'TSHIRT-001',
							),
							array(
								'label'       => 'price',
								'value'       => 'price',
								'source'      => 'product',
								'description' => 'Current price of the product.',
								'exemple'     => '25.00',
							),
							array(
								'label'       => 'regular_price',
								'value'       => 'regular_price',
								'source'      => 'product',
								'description' => 'Regular price of the product.',
								'exemple'     => '30.00',
							),
							array(
								'label'       => 'sale_price',
								'value'       => 'sale_price',
								'source'      => 'product',
								'description' => 'Sale price of the product.',
								'exemple'     => '25.00',
							),
							array(
								'label'       => 'tax_class',
								'value'       => 'tax_class',
								'source'      => 'product',
								'description' => 'Tax class of the product.',
								'exemple'     => 'standard',
							),
							array(
								'label'       => 'stock_quantity',
								'value'       => 'stock_quantity',
								'source'      => 'product',
								'description' => 'Quantity in stock.',
								'exemple'     => '100',
							),
							array(
								'label'       => 'weight',
								'value'       => 'weight',
								'source'      => 'product',
								'description' => 'Weight of the product.',
								'exemple'     => '0.5',
							),
							array(
								'label'       => 'length',
								'value'       => 'length',
								'source'      => 'product',
								'description' => 'Length of the product.',
								'exemple'     => '30',
							),
							array(
								'label'       => 'width',
								'value'       => 'width',
								'source'      => 'product',
								'description' => 'Width of the product.',
								'exemple'     => '20',
							),
							array(
								'label'       => 'height',
								'value'       => 'height',
								'source'      => 'product',
								'description' => 'Height of the product.',
								'exemple'     => '1',
							),
							array(
								'label'       => 'shipping_class',
								'value'       => 'shipping_class',
								'source'      => 'product',
								'description' => 'Shipping class of the product.',
								'exemple'     => 'standard',
							),
							// [
							// 'label' => 'reviews_allowed',
							// 'value' => 'reviews_allowed',
							// 'description' => 'Indicates if reviews are allowed.',
							// 'exemple' => 'true'
							// ],
							// [
							// 'label' => 'average_rating',
							// 'value' => 'average_rating',
							// 'description' => 'Average rating of the product.',
							// 'exemple' => '4.5'
							// ],
							// [
							// 'label' => 'rating_count',
							// 'value' => 'rating_count',
							// 'description' => 'Number of ratings for the product.',
							// 'exemple' => '50'
							// ],
							// [
							// 'label' => 'related_ids',
							// 'value' => 'related_ids',
							// 'description' => 'IDs of related products.',
							// 'exemple' => '[124, 125]'
							// ],
							// [
							// 'label' => 'upsell_ids',
							// 'value' => 'upsell_ids',
							// 'description' => 'IDs of upsell products.',
							// 'exemple' => '[126, 127]'
							// ],
							// [
							// 'label' => 'cross_sell_ids',
							// 'value' => 'cross_sell_ids',
							// 'description' => 'IDs of cross-sell products.',
							// 'exemple' => '[128, 129]'
							// ],
							// [
							// 'label' => 'parent_id',
							// 'value' => 'parent_id',
							// 'description' => 'ID of the parent product (if applicable).',
							// 'exemple' => '120'
							// ],
							array(
								'label'       => 'categories',
								'value'       => 'categories',
								'source'      => 'product',
								'description' => 'Categories of the product.',
								'exemple'     => '\'Clothing\', \'T-Shirts\'',
							),
							array(
								'label'       => 'tags',
								'value'       => 'tags',
								'source'      => 'product',
								'description' => 'Tags of the product.',
								'exemple'     => '\'Summer\', \'Cotton\'',
							),
							array(
								'label'       => 'attributes',
								'value'       => 'attributes',
								'source'      => 'product',
								'description' => 'Attributes of the product.',
								'exemple'     => "'Color': 'Blue', 'Size': 'Large'",
							),
							array(
								'label'       => 'default_attributes',
								'value'       => 'default_attributes',
								'source'      => 'product',
								'description' => 'Default attributes for variable products.',
								'exemple'     => "'Color' => 'Red', 'Size' => 'Medium'",
							),
							// [
							// 'label' => 'menu_order',
							// 'value' => 'menu_order',
							// 'description' => 'Menu order for the product.',
							// 'exemple' => '1'
							// ],
							// [
							// 'label' => 'virtual',
							// 'value' => 'virtual',
							// 'description' => 'Indicates if the product is virtual.',
							// 'exemple' => 'false'
							// ],
							// [
							// 'label' => 'downloadable',
							// 'value' => 'downloadable',
							// 'description' => 'Indicates if the product is downloadable.',
							// 'exemple' => 'false'
							// ],
							// [
							// 'label' => 'downloads',
							// 'value' => 'downloads',
							// 'description' => 'Downloadable files for the product.',
							// 'exemple' => '[\'file1.zip\', \'file2.pdf\']'
							// ],
						),
					),
				),
				'allowedSource' => array( W2P_HOOK_SOURCES['product'] ),
			),
			array(
				'label'         => 'Woocommerce (customer)',
				'description'   => null,
				'toolTip'       => 'Please note, WooCommerce meta keys are generally only completed by the customer 
                when the first order is placed. They are therefore often empty when the user registers.',
				'subcategories' => array(
					array(
						'label'    => 'Identity',
						'metaKeys' => array(
							array(
								'label'       => 'Billing first_name',
								'value'       => 'billing_first_name',
								'source'      => 'user',
								'description' => "Customer's billing first name.",
								'exemple'     => 'John',
							),
							array(
								'label'       => 'Billing last name',
								'value'       => 'billing_last_name',
								'source'      => 'user',
								'description' => "Customer's billing last name.",
								'exemple'     => 'Doe',
							),
							array(
								'label'       => 'Billing company',
								'value'       => 'billing_company',
								'source'      => 'user',
								'description' => "Customer's billing company name.",
								'exemple'     => 'ABC Corp',
							),
							array(
								'label'       => 'Shipping first name',
								'value'       => 'shipping_first_name',
								'source'      => 'user',
								'description' => "Customer's shipping first name.",
								'exemple'     => 'John',
							),
							array(
								'label'       => 'Shipping last name',
								'value'       => 'shipping_last_name',
								'source'      => 'user',
								'description' => "Customer's shipping last name.",
								'exemple'     => 'Doe',
							),
							array(
								'label'       => 'Shipping company',
								'value'       => 'shipping_company',
								'source'      => 'user ',
								'description' => "Customer's shipping company name.",
								'exemple'     => 'XYZ Ltd',
							),
						),
					),
					array(
						'label'    => 'Contact',
						'metaKeys' => array(
							array(
								'label'       => 'Billing email',
								'value'       => 'billing_email',
								'source'      => 'user',
								'description' => "Customer's billing email address.",
								'exemple'     => 'john.doe@example.com',
							),
							array(
								'label'       => 'Billing phone',
								'value'       => 'billing_phone',
								'source'      => 'user',
								'description' => "Customer's billing phone number.",
								'exemple'     => '123-456-7890',
							),
						),
					),

					array(
						'label'    => 'Billing address',
						'metaKeys' => array(
							array(
								'label'       => 'Billing address 1',
								'value'       => 'billing_address_1',
								'source'      => 'user',
								'description' => "First line of the customer's billing address.",
								'exemple'     => '123 Main Street',
							),
							array(
								'label'       => 'Billing address 2',
								'value'       => 'billing_address_2',
								'source'      => 'user',
								'description' => "Second line of the customer's billing address.",
								'exemple'     => 'Apt 4B',
							),
							array(
								'label'       => 'Billing city',
								'value'       => 'billing_city',
								'source'      => 'user',
								'description' => "Customer's billing city.",
								'exemple'     => 'Cityville',
							),
							array(
								'label'       => 'Billing postcode',
								'value'       => 'billing_postcode',
								'source'      => 'user',
								'description' => "Customer's billing postal code.",
								'exemple'     => '12345',
							),
							array(
								'label'       => 'Billing country',
								'value'       => 'billing_country',
								'source'      => 'user',
								'description' => "Customer's billing country.",
								'exemple'     => 'US',
							),
							array(
								'label'       => 'Billing state',
								'value'       => 'billing_state',
								'source'      => 'user',
								'description' => "Customer's billing state or region.",
								'exemple'     => 'CA',
							),
						),
					),
					array(
						'label'    => 'Shipping address',
						'metaKeys' => array(
							array(
								'label'       => 'Shipping address 1',
								'value'       => 'shipping_address_1',
								'source'      => 'user',
								'description' => "First line of the customer's shipping address.",
								'exemple'     => '456 Shipping Lane',
							),
							array(
								'label'       => 'Shipping address 2',
								'value'       => 'shipping_address_2',
								'source'      => 'user',
								'description' => "Second line of the customer's shipping address.",
								'exemple'     => 'Suite 8',
							),
							array(
								'label'       => 'Shipping city',
								'value'       => 'shipping_city',
								'source'      => 'user',
								'description' => "Customer's shipping city.",
								'exemple'     => 'Shippingtown',
							),
							array(
								'label'       => 'Shipping postcode',
								'value'       => 'shipping_postcode',
								'source'      => 'user',
								'description' => "Customer's shipping postal code.",
								'exemple'     => '54321',
							),
							array(
								'label'       => 'Shipping country',
								'value'       => 'shipping_country',
								'source'      => 'user',
								'description' => "Customer's shipping country.",
								'exemple'     => 'CA',
							),
							array(
								'label'       => 'Shipping state',
								'value'       => 'shipping_state',
								'source'      => 'user',
								'description' => "Customer's shipping state or region.",
								'exemple'     => 'ON',
							),
						),
					),
				),
				'allowedSource' => array( W2P_HOOK_SOURCES['user'], W2P_HOOK_SOURCES['order'] ),
			),
			array(
				'label'         => 'Wordpress',
				'description'   => null,
				'toolTip'       => null,
				'subcategories' => array(
					array(
						'label'    => 'Identity',
						'metaKeys' => array(
							array(
								'label'       => 'First name',
								'value'       => 'first_name',
								'source'      => 'user',
								'description' => 'User\'s first name.',
								'exemple'     => 'John',
							),
							array(
								'label'       => 'Last name',
								'value'       => 'last_name',
								'source'      => 'user',
								'description' => 'User\'s last name.',
								'exemple'     => 'Doe',
							),
							array(
								'label'       => 'Nickname',
								'value'       => 'nickname',
								'source'      => 'user',
								'description' => 'User\'s display name.',
								'exemple'     => 'john_doe',
							),
							array(
								'label'       => 'user email',
								'value'       => 'user_email',
								'source'      => 'user',
								'description' => 'User\'s email address.',
								'exemple'     => 'john.doe@exemple.com',
							),
						),
					),
					array(
						'label'    => 'Account',
						'metaKeys' => array(
							array(
								'label'       => 'User login',
								'value'       => 'user_login',
								'source'      => 'user',
								'description' => 'User\'s login username.',
								'exemple'     => 'john_doe',
							),
							array(
								'label'       => 'User id',
								'value'       => 'ID',
								'source'      => 'user',
								'description' => 'Unique identifier for each user in WordPress.',
								'exemple'     => '123',
							),
							array(
								'label'       => 'Wordpress capabilities',
								'value'       => 'wp_capabilities',
								'source'      => 'user',
								'description' => 'Stores user roles and capabilities for access permissions.',
								'exemple'     => 'a:1:{s:8:"customer";b:1;}',
							),
							array(
								'label'       => 'Description',
								'value'       => 'description',
								'source'      => 'user',
								'description' => 'Optional description of the user.',
								'exemple'     => 'A WordPress enthusiast.',
							),
							array(
								'label'       => 'User_url',
								'value'       => 'user_url',
								'source'      => 'user',
								'description' => 'User\'s website URL.',
								'exemple'     => 'https://www.exemple.com',
							),
							array(
								'label'       => 'User status',
								'value'       => 'user_status',
								'source'      => 'user',
								'description' => 'User\'s status in the system (e.g., 0 for active, 1 for inactive).',
								'exemple'     => '0',
							),
							// [
							// 'label' => 'session_tokens',
							// 'value' => 'session_tokens',
							// 'description' => 'Stores session information, including tokens for maintaining login.'
							// 'exemple' => '{"ABCD1234": {"expiration": "1644576000"'
							// },
						),
					),
				),
				'allowedSource' => array( W2P_HOOK_SOURCES['user'], W2P_HOOK_SOURCES['order'] ),
			),
			array(
				'label'         => 'Environment Data',
				'description'   => null,
				'toolTip'       => null,
				'subcategories' => array(
					array(
						'label'    => '',
						'metaKeys' => array(
							array(
								'label'       => 'Current time',
								'value'       => 'w2p_current_time',
								'source'      => 'w2p',
								'description' => 'Current time when the query is sent to pipedrive',
								'recommanded' => false,
								'exemple'     => '2024-09-13 15:30:45',
							),
							array(
								'label'       => 'Current date',
								'value'       => 'w2p_current_date',
								'source'      => 'w2p',
								'description' => 'Current date when the query is sent to pipedrive',
								'recommanded' => false,
								'exemple'     => '2024-09-13',
							),
							array(
								'label'       => 'Website domain',
								'value'       => 'w2p_website_domain',
								'source'      => 'w2p',
								'description' => 'Current Website domain',
								'recommanded' => false,
								'exemple'     => 'mywebsite.com',
							),
							array(
								'label'       => 'Site title',
								'value'       => 'w2p_site_title',
								'source'      => 'w2p',
								'description' => 'Title of the current website',
								'recommanded' => false,
								'exemple'     => 'My Awesome Website',
							),
						),
					),
				),
				'allowedSource' => array_values( W2P_HOOK_SOURCES ),
			),
		)
	);
}

if ( ! defined( 'W2P_QUERY_CATEGORY_TYPE' ) ) {
	define(
		'W2P_QUERY_CATEGORY_TYPE',
		array(
			'person'       => 'user',
			'organization' => 'user',
			'deal'         => 'order',
		)
	);
}

// KEEP THIS ORDER!!
if ( ! defined( 'W2P_CATEGORY' ) ) {
	define(
		'W2P_CATEGORY',
		array(
			'organization' => 'organization',
			'person'       => 'person',
			'deal'         => 'deal',
		)
	);
}


if ( ! defined( 'W2P_REQUIRED_FIELDS' ) ) {
	define(
		'W2P_REQUIRED_FIELDS',
		array(
			'deal'         => array( 'title' ),
			'person'       => array( 'name' ),
			'organization' => array( 'name' ),
		)
	);
}

if ( ! defined( 'W2P_ORDER_STATUS_HOOK' ) ) {
	define(
		'W2P_ORDER_STATUS_HOOK',
		array(
			'on-hold'        => 'woocommerce_order_status_on-hold',
			'pending'        => 'woocommerce_order_status_pending',
			'processing'     => 'woocommerce_order_status_processing',
			'completed'      => 'woocommerce_order_status_completed',
			'refunded'       => 'woocommerce_order_status_refunded',
			'cancelled'      => 'woocommerce_order_status_cancelled',
			'failed'         => 'woocommerce_order_status_failed',
			'checkout-draft' => 'woocommerce_cart_updated',
		)
	);
}

if ( ! defined( 'W2P_EMPTY_SYNC_ADDITIONAL_DATA' ) ) {
	define(
		'W2P_EMPTY_SYNC_ADDITIONAL_DATA',
		array(
			'total_users'           => 0,
			'current_user'          => 0,
			'total_orders'          => 0,
			'current_order'         => 0,
			'current_user_index'    => 0,
			'current_order_index'   => 0,
			'total_person_errors'   => 0,
			'total_person_uptodate' => 0,
			'total_person_done'     => 0,
			'total_order_errors'    => 0,
			'total_order_uptodate'  => 0,
			'total_order_done'      => 0,
		)
	);
}


if ( ! defined( 'W2P_HOOK_LIST' ) ) {
	define(
		'W2P_HOOK_LIST',
		array(
			array(
				'label'       => 'User login',
				'key'         => 'wp_login',
				'description' => 'Fired after a user logs in.',
				'disabledFor' => array( 'deal' ),
				'source'      => 'user',
			),
			array(
				'label'       => 'User Registration',
				'key'         => 'user_register',
				'description' => 'Triggered after a new user registration.',
				'disabledFor' => array( 'deal' ),
				'source'      => 'user',
			),
			array(
				'label'            => 'User updated',
				'key'              => 'profile_update',
				'description'      => 'Fired after a user is updated.',
				'disabledFor'      => array( 'deal' ),
				'linked_hooks_key' => array( 'woocommerce_checkout_update_user_meta' ),
				'source'           => 'user',
			),
			array(
				'label'       => 'Cart updated',
				'key'         => 'woocommerce_cart_updated',
				'description' => 'Fired when a product is added, removed or updated to the shopping cart.',
				'disabledFor' => array(),
				'source'      => 'order',
			),
			array(
				'label'       => 'New Order',
				'key'         => 'woocommerce_new_order',
				'description' => 'Fired when a new order is created.',
				'disabledFor' => array(),
				'source'      => 'order',
			),
			array(
				'label'       => 'Order updated',
				'key'         => 'woocommerce_update_order',
				'description' => 'Fired when an order is updated.',
				'disabledFor' => array(),
				'source'      => 'order',
			),
			array(
				'label'       => 'Order on hold',
				'key'         => 'woocommerce_order_status_on-hold',
				'description' => 'Fired when an order is placed on hold.',
				'disabledFor' => array(),
				'source'      => 'order',
			),
			array(
				'label'       => 'Order pending',
				'key'         => 'woocommerce_order_status_pending',
				'description' => 'Fired when an order is awaiting payment (pending).',
				'disabledFor' => array(),
				'source'      => 'order',
			),
			array(
				'label'       => 'Order processing',
				'key'         => 'woocommerce_order_status_processing',
				'description' => 'Fired when an order is being processed.',
				'disabledFor' => array(),
				'source'      => 'order',
			),
			array(
				'label'       => 'Order completed',
				'key'         => 'woocommerce_order_status_completed',
				'description' => 'Fired when an order is successfully completed.',
				'disabledFor' => array(),
				'source'      => 'order',
			),
			array(
				'label'       => 'Order refunded',
				'key'         => 'woocommerce_order_status_refunded',
				'description' => 'Fired when an order is refunded.',
				'disabledFor' => array(),
				'source'      => 'order',
			),
			array(
				'label'       => 'Order canceled',
				'key'         => 'woocommerce_order_status_cancelled',
				'description' => 'Fired when an order is canceled.',
				'disabledFor' => array(),
				'source'      => 'order',
			),
			array(
				'label'       => 'Order failed',
				'key'         => 'woocommerce_order_status_failed',
				'description' => 'Fired when an order fails.',
				'disabledFor' => array(),
				'source'      => 'order',
			),
		)
	);
}

if ( ! defined( 'W2P_HOOK_PRIORITY' ) ) {
	define(
		'W2P_HOOK_PRIORITY',
		array(
			'organization' => 100,
			'person'       => 105,
			'deal'         => 110,
		)
	);
}
