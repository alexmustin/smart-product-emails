<?php
/**
 * Uninstall Smart Product Emails for WooCommerce
 *
 * This file runs when the plugin is uninstalled (deleted) from WordPress.
 * It removes all plugin data from the database to ensure clean uninstallation.
 *
 * @package SmartProductEmails
 */

// Exit if accessed directly or if uninstall not called from WordPress.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Clean up plugin data on uninstall
 */
function spe_uninstall_cleanup() {
	global $wpdb;

	// 1. Delete all SPE Message posts (custom post type: smartproductemails)
	$spe_posts = get_posts(
		array(
			'post_type'      => 'smartproductemails',
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'fields'         => 'ids',
		)
	);

	foreach ( $spe_posts as $post_id ) {
		// Force delete the post (bypass trash)
		wp_delete_post( $post_id, true );
	}

	// 2. Delete plugin settings option
	delete_option( 'SmartProductEmails_settings_name' );

	// 3. Delete all product meta data created by the plugin
	// Current meta keys used by the plugin
	$meta_keys = array(
		'spemail_id_processing',
		'location_processing',
		// Legacy meta keys from older versions (for backward compatibility cleanup)
		'order_status',
		'location',
		'custom_content',
		'spemail_id',
	);

	foreach ( $meta_keys as $meta_key ) {
		// Delete from post meta table (legacy storage)
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->postmeta} WHERE meta_key = %s",
				$meta_key
			)
		);

		// Delete from HPOS meta table if WooCommerce HPOS is enabled
		// Check if the table exists (WooCommerce HPOS)
		$hpos_table = $wpdb->prefix . 'wc_orders_meta';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$table_exists = $wpdb->get_var(
			$wpdb->prepare(
				'SHOW TABLES LIKE %s',
				$hpos_table
			)
		);

		if ( $table_exists === $hpos_table ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->query(
				$wpdb->prepare(
					"DELETE FROM {$hpos_table} WHERE meta_key = %s",
					$meta_key
				)
			);
		}
	}

	// 4. Clear any cached data
	wp_cache_flush();
}

// Run the cleanup
spe_uninstall_cleanup();
