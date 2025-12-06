<?php
/**
 * Plugin Name: Smart Product Emails
 * Plugin URI: https://smartproductemails.com/
 * Description: Transform WooCommerce emails into a powerful customer communication platform with dynamic content, segmentation, A/B testing, and analytics.
 * Version: 0.4.8.1
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
define( 'SPE_PLUGIN_VERSION', '0.4.8.1' );
define( 'SPE_PLUGIN_FILE', __FILE__ );
define( 'SPE_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'SPE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Add a check for WooCommerce on plugin activation.
register_activation_hook( __FILE__, 'smart_product_emails_activate_check_for_woo' );

/**
 * Checks for WooCommerce on plugin activation.
 */
function smart_product_emails_activate_check_for_woo() {
	if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ), true ) ) {
		$woo_plugin_url = esc_url( 'https://wordpress.org/plugins/woocommerce/' );

		$text_string  = '';
		$text_string .= sprintf( '%1$s%2$s%3$s', '<h2>', esc_html__( 'Oops!', 'smart_product_emails_domain' ), '</h2>' );
		$text_string .= sprintf( '%1$s%2$sWooCommerce%3$s%4$s%5$s', '<p>', '<a href="' . $woo_plugin_url . '" target="_blank">', '</a>', esc_html__( ' is required for this plugin.', 'smart_product_emails_domain' ), '</p>' );
		$text_string .= sprintf( '%1$s%2$s%3$s', '<p>', esc_html__( 'Please install and activate WooCommerce and try again.', 'smart_product_emails_domain' ), '</p>' );
		wp_die( $text_string ); // phpcs:ignore
	}
}

// Include required files.
require_once plugin_dir_path( __FILE__ ) . 'includes/class-smart-product-emails.php';
require_once plugin_dir_path( __FILE__ ) . 'admin/class-spe-cpt.php';
require_once plugin_dir_path( __FILE__ ) . 'admin/class-spe-column-display.php';
require_once plugin_dir_path( __FILE__ ) . 'admin/class-spe-admin-settings.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-spe-output.php';

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
