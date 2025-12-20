<?php
/**
 * Plugin Name: Smart Product Emails
 * Plugin URI: https://smartproductemails.com/
 * Description: Transform WooCommerce emails into a powerful customer communication platform with dynamic content, segmentation, A/B testing, and analytics.
 * Version: 0.6.0
 * Author: Alex Mustin
 * Author URI: https://alexmustin.com
 * Text Domain: smart_product_emails_domain
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * WC requires at least: 10.2.1
 * WC tested up to: 10.3.6
 *
 * @package smart_product_emails_domain
 */

// Exit if not WordPress.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Declare HPOS compatibility.
add_action('before_woocommerce_init', function() {
    if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
    }
});

// Define Globals.
define( 'SPE_PLUGIN_VERSION', '0.6.0' );
define( 'SPE_PLUGIN_FILE', __FILE__ );
define( 'SPE_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'SPE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Add a check for WooCommerce on plugin activation.
register_activation_hook( __FILE__, 'smart_product_emails_activate' );
register_deactivation_hook( __FILE__, 'smart_product_emails_deactivate' );

/**
 * Activation hook - checks for WooCommerce and creates error log database table
 */
function smart_product_emails_activate() {
	// Check for WooCommerce
	if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ), true ) ) {
		$woo_plugin_url = esc_url( 'https://wordpress.org/plugins/woocommerce/' );

		$text_string  = '';
		$text_string .= sprintf( '%1$s%2$s%3$s', '<h2>', esc_html__( 'Oops!', 'smart_product_emails_domain' ), '</h2>' );
		$text_string .= sprintf( '%1$s%2$sWooCommerce%3$s%4$s%5$s', '<p>', '<a href="' . $woo_plugin_url . '" target="_blank">', '</a>', esc_html__( ' is required for this plugin.', 'smart_product_emails_domain' ), '</p>' );
		$text_string .= sprintf( '%1$s%2$s%3$s', '<p>', esc_html__( 'Please install and activate WooCommerce and try again.', 'smart_product_emails_domain' ), '</p>' );
		wp_die( $text_string ); // phpcs:ignore
	}

	// Create error log database table
	global $wpdb;
	$table_name      = $wpdb->prefix . 'spe_error_logs';
	$charset_collate = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE {$table_name} (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		log_level varchar(20) NOT NULL DEFAULT 'info',
		message text NOT NULL,
		context longtext DEFAULT NULL,
		user_id bigint(20) unsigned DEFAULT NULL,
		product_id bigint(20) unsigned DEFAULT NULL,
		order_id bigint(20) unsigned DEFAULT NULL,
		error_type varchar(100) DEFAULT NULL,
		file varchar(255) DEFAULT NULL,
		line int(11) DEFAULT NULL,
		stack_trace longtext DEFAULT NULL,
		request_url varchar(255) DEFAULT NULL,
		user_agent varchar(255) DEFAULT NULL,
		ip_address varchar(45) DEFAULT NULL,
		created_at datetime NOT NULL,
		PRIMARY KEY (id),
		KEY log_level (log_level),
		KEY user_id (user_id),
		KEY product_id (product_id),
		KEY order_id (order_id),
		KEY error_type (error_type),
		KEY created_at (created_at)
	) {$charset_collate};";

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	dbDelta( $sql );

	// Schedule log cleanup event (daily)
	if ( ! wp_next_scheduled( 'spe_cleanup_old_logs' ) ) {
		wp_schedule_event( time(), 'daily', 'spe_cleanup_old_logs' );
	}
}

/**
 * Deactivation hook - clears scheduled events
 */
function smart_product_emails_deactivate() {
	// Clear scheduled cron event for log cleanup
	wp_clear_scheduled_hook( 'spe_cleanup_old_logs' );
}

/**
 * Cleanup old log entries (runs daily via wp-cron)
 */
function spe_cleanup_logs_callback() {
	if ( class_exists( 'SPE_Logger' ) ) {
		$logger = SPE_Logger::get_instance();
		$logger->cleanup_old_logs( 30 ); // Keep last 30 days
	}
}
add_action( 'spe_cleanup_old_logs', 'spe_cleanup_logs_callback' );

// Include required files.
require_once plugin_dir_path( __FILE__ ) . 'includes/class-smart-product-emails.php';
require_once plugin_dir_path( __FILE__ ) . 'admin/class-spe-cpt.php';
require_once plugin_dir_path( __FILE__ ) . 'admin/class-spe-column-display.php';
require_once plugin_dir_path( __FILE__ ) . 'admin/class-spe-admin-settings.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-spe-output.php';

// Load Error Log classes
require_once plugin_dir_path( __FILE__ ) . 'includes/class-spe-logger.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-spe-error-handler.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-spe-email-logger.php';
require_once plugin_dir_path( __FILE__ ) . 'admin/class-spe-error-log-admin.php';

// Initialize error handlers
new SPE_Error_Handler();
new SPE_Email_Logger();
new SPE_Error_Log_Admin();

/**
 * Runs main plugin functions.
 */
function run_smart_product_emails() {
	// Create a new object.
	$smart_product_emails = new Smart_Product_Emails();

	// Do the 'run' function inside our object.
	$smart_product_emails->run();

	// Create a new output object.
	new smart_product_emails_Output();
}

// Go!
run_smart_product_emails();
