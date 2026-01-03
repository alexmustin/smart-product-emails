<?php
/**
 * Uninstall Smart Product Emails for WooCommerce
 *
 * This file runs when the plugin is uninstalled (deleted) from WordPress.
 * It removes all plugin data from the database to ensure clean uninstallation.
 *
 * Note: Direct database queries are required for uninstall because:
 * 1. Removing custom table data (no WordPress API available)
 * 2. Cleaning HPOS meta table (WooCommerce custom table, requires direct queries)
 * 3. Bulk deletion operations (more efficient than iterating through WordPress APIs)
 * 4. Uninstall runs once and doesn't need caching
 *
 * All queries use $wpdb->prepare() for security where user input is involved.
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
function smartproductemails_uninstall_cleanup() {
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
		'smartproductemails_message_id_processing',
		'smartproductemails_location_processing',
		// Legacy meta keys from older versions (for backward compatibility cleanup)
		'spemail_id_processing',
		'location_processing',
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
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is constructed from known constant
			$wpdb->query(
				$wpdb->prepare(
					"DELETE FROM {$hpos_table} WHERE meta_key = %s",
					$meta_key
				)
			);
		}
	}

	// 4. Drop the error log table
	$error_log_table = $wpdb->prefix . 'spe_error_logs';
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is constructed from known constant
	$wpdb->query( "DROP TABLE IF EXISTS {$error_log_table}" );

	// 5. Clear scheduled cron event
	wp_clear_scheduled_hook( 'smartproductemails_cleanup_old_logs' );

	// 6. Clear any cached data
	wp_cache_flush();
}

// Run the cleanup
smartproductemails_uninstall_cleanup();
