<?php
/**
 * Extensibility Hooks for Smart Product Emails
 *
 * Provides action and filter hooks that allow the Pro version
 * (or other extensions) to add functionality to the Free version.
 *
 * @package SmartProductEmails
 */

/**
 * SPE_Hooks class manages all extensibility points
 */
class SPE_Hooks {

	/**
	 * Initialize hooks
	 */
	public static function init() {
		// Allow extensions to initialize after Free version loads
		do_action('spe_free_loaded');
	}

	/**
	 * Product admin tab - allow extensions to add buttons/controls
	 *
	 * @param int $product_id The product ID
	 * @param string $status The order status (onhold, processing, completed)
	 * @param int $message_id The Smart Product Email message ID
	 */
	public static function product_admin_controls($product_id, $status, $message_id) {
		do_action('spe_product_admin_controls', $product_id, $status, $message_id);
	}

	/**
	 * Product admin tab - allow extensions to add extra fields
	 *
	 * @param int $product_id The product ID
	 */
	public static function product_admin_extra_fields($product_id) {
		do_action('spe_product_admin_extra_fields', $product_id);
	}

	/**
	 * Email message content - allow extensions to modify content
	 *
	 * @param string $content The email content
	 * @param int $message_id The message ID
	 * @param string $status The order status
	 * @return string Modified content
	 */
	public static function filter_message_content($content, $message_id, $status) {
		return apply_filters('spe_filter_message_content', $content, $message_id, $status);
	}

	/**
	 * Settings page - allow extensions to add settings sections
	 */
	public static function settings_page_sections() {
		do_action('spe_settings_page_sections');
	}

	/**
	 * Admin enqueue scripts - allow extensions to add their scripts
	 *
	 * @param string $hook The current admin page hook
	 */
	public static function admin_enqueue_scripts($hook) {
		do_action('spe_admin_enqueue_scripts', $hook);
	}

	/**
	 * Email helper placeholders - allow extensions to add more placeholders
	 *
	 * @param array $placeholders Array of placeholder => value pairs
	 * @param object $order WooCommerce order object (if available)
	 * @return array Modified placeholders array
	 */
	public static function filter_placeholders($placeholders, $order = null) {
		return apply_filters('spe_filter_placeholders', $placeholders, $order);
	}

	/**
	 * Check if Pro version is active
	 *
	 * @return bool True if Pro is active
	 */
	public static function is_pro_active() {
		return defined('SPE_PRO_VERSION');
	}

	/**
	 * Get Pro version number
	 *
	 * @return string|false Pro version or false if not active
	 */
	public static function get_pro_version() {
		return defined('SPE_PRO_VERSION') ? SPE_PRO_VERSION : false;
	}

}
