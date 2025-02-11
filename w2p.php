<?php
/**
 * Plugin Name: W2P: Pipedrive CRM Integration for WooCommerce
 * Plugin URI: https://woocommerce-to-pipedrive.com/
 * Description: Permet de syncroniser unidirectionnelement vos informations CRM de Woocommerce à Pipedrive
 * Author: Tristan Dieny
 * Version: 1.0.0
 * Author URI: https://woocommerce-to-pipedrive.com/
 * Requires PHP: 8.0
 * WC requires at least: 4.0.0
 * Copyright: (c) 2024 W2P SASU. (contact@woocommerce2pipedrive.com)
 *
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 * @package		W2P
 * @author      W2P
 * @Category    Plugin
 * @license     http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Add a main menu link in the WordPress admin interface.
 *
 * This function is only meant to be used by WordPress itself,
 * and should not be called directly.
 *
 * @since 1.0.0
 *
 * @return void
 */
function w2p_add_main_menu_link() {
	add_menu_page(
		'Woocommerce to Pipedrive',
		'W2P',
		'manage_options',
		'w2p-settings',
		'w2p_show_front_app',
		'data:image/svg+xml;base64,' . base64_encode( '<svg xmlns="http://www.w3.org/2000/svg" fill="black" viewBox="0 0 576 512"><path d="M96 0C78.3 0 64 14.3 64 32l0 96 64 0 0-96c0-17.7-14.3-32-32-32zM288 0c-17.7 0-32 14.3-32 32l0 96 64 0 0-96c0-17.7-14.3-32-32-32zM32 160c-17.7 0-32 14.3-32 32s14.3 32 32 32l0 32c0 77.4 55 142 128 156.8l0 67.2c0 17.7 14.3 32 32 32s32-14.3 32-32l0-67.2c12.3-2.5 24.1-6.4 35.1-11.5c-2.1-10.8-3.1-21.9-3.1-33.3c0-80.3 53.8-148 127.3-169.2c.5-2.2 .7-4.5 .7-6.8c0-17.7-14.3-32-32-32L32 160zM432 512a144 144 0 1 0 0-288 144 144 0 1 0 0 288zm47.9-225c4.3 3.7 5.4 9.9 2.6 14.9L452.4 356l35.6 0c5.2 0 9.8 3.3 11.4 8.2s-.1 10.3-4.2 13.4l-96 72c-4.5 3.4-10.8 3.2-15.1-.6s-5.4-9.9-2.6-14.9L411.6 380 376 380c-5.2 0-9.8-3.3-11.4-8.2s.1-10.3 4.2-13.4l96-72c4.5-3.4 10.8-3.2 15.1 .6z"/></svg>' ),
	);
}

add_action( 'admin_menu', 'w2p_add_main_menu_link' );

/**
 * Add a settings link in the plugin action links.
 *
 * @param array $links Les liens du plugin.
 * @return array Les liens du plugin avec le lien vers la page de settings en premier.
 */
function w2p_add_settings_link( $links ) {
	$settings_link = '<a href="' . admin_url( 'admin.php?page=w2p-settings&menu=settings&submenu=general' ) . '">Settings</a>';
	array_unshift( $links, $settings_link );
	return $links;
}

add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'w2p_add_settings_link' );


/**
 * Renders the React app in the WordPress admin interface.
 *
 * This function is called by WordPress when the plugin's main menu link is clicked.
 *
 * @since 1.0.0
 *
 * @return void
 */
function w2p_show_front_app() {
	$app_global_data = array(
		'nonce'                => wp_create_nonce( 'wp_rest' ),
		'w2p_client_rest_url'  => get_rest_url() . 'w2p/v1',
		'users_meta_key'       => w2p_get_users_metakey(),
		'w2p_distant_rest_url' => W2P_DISTANT_REST_URL,
		'build_url'            => plugins_url( '/admin/build', __FILE__ ),
		'CONSTANTES'           => array(
			'W2P_META_KEYS'       => W2P_META_KEYS,
			'W2P_REQUIRED_FIELDS' => W2P_REQUIRED_FIELDS,
			'W2P_HOOK_LIST'       => W2P_HOOK_LIST,
			'W2P_HOOK_SOURCES'    => array_keys( W2P_HOOK_SOURCES ),
			'W2P_AVAIBLE_STATES'  => W2P_Query::$avaible_state,
		),
	);

	$parameters = get_option( 'w2p_parameters' );
	if ( $parameters ) {
		$app_global_data['parameters'] = w2p_get_parameters();
	}

	$parsed_url = wp_parse_url( get_site_url() );
	if ( $parsed_url && isset( $parsed_url['host'] ) ) {
		$app_global_data['parameters']['w2p']['domain'] = $parsed_url['host'];
	}

	?>

	<div id="w2p-app"></div>
	<div id='w2p-background'></div>

	<?php
	// Enqueue JS files.
	$js_files = glob( plugin_dir_path( __FILE__ ) . 'admin/build/static/js/main.*.js' );
	if ( ! empty( $js_files ) ) {
		$js_file_path = $js_files[0];
		$version      = file_exists( $js_file_path ) ? filemtime( $js_file_path ) : time(); // Use file modification time or fallback to current time.
		wp_enqueue_script(
			'w2p-app',
			plugins_url( 'admin/build/static/js/' . basename( $js_file_path ), __FILE__ ),
			array( 'wp-element' ),
			$version,
			true
		);
	} else {
		w2p_add_error_log(
			'React JS file not found in ' . wp_json_encode( plugin_dir_path( __FILE__ ) . 'admin/build/static/js/main.*.js', JSON_PRETTY_PRINT ),
			'w2p_show_front_app'
		);
	}

	wp_localize_script( 'w2p-app', 'appGlobalData', $app_global_data );

	// Enqueue CSS files.
	$css_files = glob( plugin_dir_path( __FILE__ ) . 'admin/build/static/css/main.*.css' );
	if ( ! empty( $css_files ) ) {
		$css_file_path = $css_files[0];
		$version       = file_exists( $css_file_path ) ? filemtime( $css_file_path ) : time(); // Use file modification time or fallback to current time.
		wp_enqueue_style(
			'w2p-app-css',
			plugins_url( 'admin/build/static/css/' . basename( $css_file_path ), __FILE__ ),
			array(),
			$version
		);
	} else {
		w2p_add_error_log(
			'React CSS file not found in ' . wp_json_encode( plugin_dir_path( __FILE__ ) . 'admin/build/static/css/main.*.css', JSON_PRETTY_PRINT ),
			'w2p_show_front_app'
		);
	}
}

/*****************************    CRON    *********************************/

/**
 * Add a custom cron schedule to WordPress.
 *
 * This custom schedule is used by some tasks in the plugin.
 *
 * @param array $schedules The array of cron schedules.
 *
 * @return array The modified array of cron schedules.
 */
function w2p_custom_cron_schedules( $schedules ) {
	$schedules['w2p_five_minutes'] = array(
		'interval' => 5 * 60, // 5 minutes
		'display'  => __( 'Every Five Minutes' ),
	);
	return $schedules;
}
add_filter( 'cron_schedules', 'w2p_custom_cron_schedules' );

add_filter(
	'cron_schedules',
	function ( $schedules ) {
		$schedules['one_minute'] = array(
			'interval' => 60, // 60 secondes
			'display'  => __( 'Every Minute' ),
		);
		return $schedules;
	}
);

/**
 * Schedule the cron event on plugin activation.
 *
 * Schedules the w2p_send_queries cron event to run every 5 minutes.
 *
 * @since 1.0.0
 */
function w2p_plugin_activate_cron() {
	if ( ! wp_next_scheduled( 'w2p_send_queries' ) ) {
		w2p_add_error_log( 'Scheduling cron w2p_send_queries', 'w2p_plugin_activate_cron' );
		wp_schedule_event( time(), 'w2p_five_minutes', 'w2p_send_queries' );
	}
}
register_activation_hook( __FILE__, 'w2p_plugin_activate_cron' );

/**
 * Unschedules the w2p_send_queries cron event.
 *
 * Checks if the w2p_send_queries cron event is scheduled, logs the cancellation,
 * and unschedules it from the cron schedule.
 *
 * @since 1.0.0
 */
function w2p_plugin_desactivate_cron() {
	$timestamp = wp_next_scheduled( 'w2p_send_queries' );
	if ( $timestamp ) {
		w2p_add_error_log( 'Canceling cron w2p_send_queries', 'w2p_plugin_desactivate_cron' );
		wp_unschedule_event( $timestamp, 'w2p_send_queries' );
	}
}
register_deactivation_hook( __FILE__, 'w2p_plugin_desactivate_cron' );

/**
 * Sends pending queries to Pipedrive.
 *
 * This function is triggered by a cron job to process and send queries
 * categorized under 'organization', 'person', and 'deal' to Pipedrive.
 * It skips execution if a synchronization is running or if necessary API
 * keys are missing. For each query, it logs whether the send was successful
 * or resulted in an error, and maintains a count of successes, errors, and
 * total processed queries.
 *
 * @since 1.0.0
 */
function w2p_send_queries() {
	if ( ! w2p_is_sync_running() && w2p_get_api_key() && w2p_get_pipedrive_api_key() ) {
		$query_states = array(
			'success' => 0,
			'error'   => 0,
			'total'   => 0,
		);
		foreach ( array_keys( W2P_CATEGORY ) as $category ) { // toujours organization en premier pour l'envoyer à person.
			$queries = W2P_Query::get_queries(
				false,
				array(
					'state'    => array( 'TODO', 'ERROR' ),
					'category' => $category,
				),
				1,
				-1,
				'ASC'
			)['data'];

			foreach ( $queries as $query ) {
				$send_info = $query->send( true );
				if ( $send_info['success'] ) {
					$query_states['success'] += 1;
				} else {
					$query_states['error'] += 1;
				}
				$query_states['total'] += 1;
			}
		}
		if ( $query_states['total'] ) {
			w2p_add_error_log( 'Cron executed for ' . $query_states['total'] . " queries ! - success:  $query_states[success] | error: $query_states[error]", 'w2p_send_queries' );
		}
	}
}
add_action( 'w2p_send_queries', 'w2p_send_queries' );

require_once plugin_dir_path( __FILE__ ) . 'includes/helpers.php';
w2p_load_files( plugin_dir_path( __FILE__ ) . 'includes' );

/**
 * Initialize the plugin by creating the database table for query results.
 *
 * Creates the w2p_query table with the following columns:
 * - id: auto-incrementing ID
 * - category: category of the query (e.g. organization, person, etc.)
 * - target_id: ID of the target resource (e.g. organization ID, person ID, etc.)
 * - hook: hook that triggered the query (e.g. create, update, etc.)
 * - method: HTTP method used for the query (e.g. POST, PUT, etc.)
 * - payload: JSON payload sent with the query
 * - state: state of the query (e.g. TODO, ERROR, etc.)
 * - source_id: ID of the source resource (e.g. order ID, user ID, etc.)
 * - pipedrive_response: response from Pipedrive
 * - additional_data: additional data sent with the query
 * - source: source of the query (e.g. WooCommerce, WordPress, etc.)
 * - user_id: ID of the user who triggered the query
 *
 * @since 1.0.0
 */
function w2p_init_plugin() {
	global $wpdb;
	require_once ABSPATH . '/wp-admin/includes/upgrade.php';

	/**Table resultats analyses */
	$table_name      = $wpdb->prefix . 'w2p_query';
	$charset_collate = $wpdb->get_charset_collate();
	$sql             = "CREATE TABLE $table_name (
            `id` int(10) NOT NULL AUTO_INCREMENT,
            `category` varchar(255) NOT NULL,
            `target_id` int(10) NOT NULL,
            `hook` varchar(255) NOT NULL,
            `method` varchar(255) NOT NULL,
            `payload` TEXT DEFAULT NULL,
            `state` varchar(255) NOT NULL,
            `source_id` int(10) NOT NULL,
            `pipedrive_response` TEXT DEFAULT NULL,
            `additional_data` TEXT DEFAULT NULL,
            `source` varchar(255) NOT NULL,
            `user_id` int(10) NOT NULL,
            PRIMARY KEY (`id`)
        ) $charset_collate;";
	dbDelta( $sql );
}

register_activation_hook( __FILE__, 'w2p_init_plugin' );

/**
 * Generate an encryption key and write it to wp-config.php if it does not exist.
 *
 * The key is generated using w2p_generate_encryption_key() and is written just above the
 * "That's all, stop editing!" line in wp-config.php.
 *
 * @return void
 */
function w2p_secret_key_init() {
	if ( defined( 'W2P_ENCRYPTION_KEY' ) ) {
		return;
	}

	$encryption_key = w2p_generate_encryption_key();
	$config_file    = ABSPATH . 'wp-config.php';

	global $wp_filesystem;
	if ( ! function_exists( 'WP_Filesystem' ) ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';
	}
	WP_Filesystem();

	if ( $wp_filesystem->exists( $config_file ) && $wp_filesystem->is_writable( $config_file ) ) {
		$config_content = $wp_filesystem->get_contents( $config_file );
		if ( strpos( $config_content, 'W2P_ENCRYPTION_KEY' ) === false ) {
			$key_definition = "\ndefine('W2P_ENCRYPTION_KEY', '$encryption_key');\n";
			$marker         = "/* That's all, stop editing!";
			$marker_pos     = strpos( $config_content, $marker );

			if ( false !== $marker_pos ) {
				$new_content = substr_replace( $config_content, $key_definition, $marker_pos, 0 );
			} else {
				$new_content = $config_content . $key_definition;
			}
			if ( ! $wp_filesystem->put_contents( $config_file, $new_content ) ) {
				w2p_add_error_log( 'Failed to write the encryption key to wp-config.php.' );
			}
		}
	} else {
		w2p_add_error_log( 'Unable to write to wp-config.php for encryption key setup.' );
		return;
	}
}

register_activation_hook( __FILE__, 'w2p_secret_key_init' );
