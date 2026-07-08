<?php
/**
 * Performs the actual writes for a batch of Woo Custom Emails import items.
 *
 * Every product/status item is processed independently: all writes happen
 * before the tracker marker is written last, so an interrupted request
 * (dropped connection, PHP timeout) simply leaves that one item looking
 * un-imported and safe to retry on the next batch - never a duplicate.
 *
 * @package SmartProductEmails
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Performs the writes for a batch of Woo Custom Emails import items.
 */
class SmartProductEmails_Import_Engine {

	/**
	 * Tracker used for mapping/marker bookkeeping.
	 *
	 * @var SmartProductEmails_Import_Tracker
	 */
	private $tracker;

	/**
	 * Constructor.
	 *
	 * @param SmartProductEmails_Import_Tracker $tracker Tracker used for mapping/marker bookkeeping.
	 */
	public function __construct( SmartProductEmails_Import_Tracker $tracker ) {
		$this->tracker = $tracker;
	}

	/**
	 * Migrate the old plugin's global settings. Called once when an import
	 * job starts, not per product/status item.
	 *
	 * The display_classes key has a direct 1:1 equivalent and is merged in
	 * (naturally idempotent - merging a set into itself is a no-op).
	 * show_in_admin_email has no equivalent (admin order emails don't
	 * expose the same content hooks customer emails do) and is
	 * intentionally never written here - the Review/Report steps surface
	 * that as a "Not Imported" label with an explanatory tooltip instead.
	 *
	 * @param array $settings Scanner-extracted settings: display_classes, show_in_admin_email.
	 */
	public function migrate_settings( $settings ) {
		if ( empty( $settings['display_classes'] ) ) {
			return;
		}

		$spe_settings = get_option( 'SmartProductEmails_settings_name' );
		if ( ! is_array( $spe_settings ) ) {
			$spe_settings = array();
		}

		$existing = isset( $spe_settings['display_classes'] ) ? $spe_settings['display_classes'] : '';

		$existing_classes = array_filter( array_map( 'trim', explode( ',', $existing ) ) );
		$new_classes      = array_filter( array_map( 'trim', explode( ',', $settings['display_classes'] ) ) );

		$merged = array_unique( array_merge( $existing_classes, $new_classes ) );

		$spe_settings['display_classes'] = implode( ', ', $merged );

		update_option( 'SmartProductEmails_settings_name', $spe_settings );
	}

	/**
	 * Process a batch of work items (as returned by
	 * SmartProductEmails_Import_Scanner::get_pending_work_items()). One bad
	 * item is logged and skipped rather than aborting the whole batch.
	 *
	 * @param array  $items Work items.
	 * @param string $conflict_strategy 'overwrite' or 'skip'.
	 * @return array {
	 *     @type array $totals Counts: created, updated, skipped, errors, conflict.
	 *     @type array $log    Per-item result rows for the wizard's progress log.
	 * }
	 */
	public function process_batch( $items, $conflict_strategy ) {
		$totals = array(
			'created'  => 0,
			'updated'  => 0,
			'skipped'  => 0,
			'errors'   => 0,
			'conflict' => 0,
		);
		$log    = array();
		$logger = SmartProductEmails_Logger::get_instance();

		foreach ( $items as $item ) {
			try {
				$result = $this->process_item( $item, $conflict_strategy );
			} catch ( Exception $e ) {
				++$totals['errors'];
				$logger->error(
					'Woo Custom Emails import error: ' . $e->getMessage(),
					array(
						'error_type' => 'import',
						'product_id' => $item['product_id'],
					)
				);

				// Mark it failed so this exact broken source data isn't
				// retried in every subsequent batch of this run (or a future
				// run) - only once the underlying reference actually changes.
				$product = wc_get_product( $item['product_id'] );
				if ( $product ) {
					$this->tracker->mark_failed( $product, $item['status'], $item['assignment']['fingerprint'] );
				}

				$log[] = array(
					'product_id'   => $item['product_id'],
					'product_name' => $item['product_name'],
					'status'       => $item['status'],
					'outcome'      => 'errors',
					'message'      => $e->getMessage(),
				);
				continue;
			}

			++$totals[ $result['outcome'] ];
			if ( ! empty( $result['was_conflict'] ) ) {
				++$totals['conflict'];
			}
			$log[] = $result;
		}

		return array(
			'totals' => $totals,
			'log'    => $log,
		);
	}

	/**
	 * Process a single product/status work item.
	 *
	 * @param array  $item Work item: product_id, product_name, status, assignment.
	 * @param string $conflict_strategy 'overwrite' or 'skip'.
	 * @return array Result row for the progress log.
	 * @throws Exception On unrecoverable per-item failure (caught by process_batch()).
	 */
	private function process_item( $item, $conflict_strategy ) {
		$product_id = $item['product_id'];
		$status     = $item['status'];
		$assignment = $item['assignment'];

		$product = wc_get_product( $product_id );
		if ( ! $product ) {
			throw new Exception( sprintf( 'Product #%d no longer exists.', $product_id ) ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- internal exception message, never echoed directly; caught by process_batch(), stored via the logger, and HTML-escaped client-side before display in spe-import.js.
		}

		$was_conflict = ( 'conflict' === $assignment['classification'] );

		if ( $was_conflict && 'skip' === $conflict_strategy ) {
			return array(
				'product_id'   => $product_id,
				'product_name' => $item['product_name'],
				'status'       => $status,
				'outcome'      => 'skipped',
				'was_conflict' => true,
				'message'      => __( 'Skipped: already has its own Smart Product Emails configuration.', 'smart-product-emails' ),
			);
		}

		if ( $was_conflict ) {
			$this->tracker->backup_conflict( $product, $status );
		}

		if ( 'custom_content' === $assignment['schema_tier'] ) {
			$target_post_id = $this->sync_legacy_content_post( $product, $assignment );
		} else {
			if ( empty( $assignment['source_message_id'] ) || ! empty( $assignment['dangling'] ) ) {
				throw new Exception( sprintf( 'Invalid or missing message reference for product #%d (%s status).', $product_id, $status ) ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- internal exception message, never echoed directly; see note above.
			}
			$target_post_id = $this->sync_message_post( (int) $assignment['source_message_id'] );
		}

		if ( ! $target_post_id ) {
			throw new Exception( sprintf( 'Failed to create/update message post for product #%d (%s status).', $product_id, $status ) ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- internal exception message, never echoed directly; see note above.
		}

		$location = isset( $assignment['source_location'] ) ? $assignment['source_location'] : '';

		// PRO's fallback-read checks its own multi-message JSON meta first
		// and ignores the legacy keys entirely while it's present - clear it
		// so the write below actually takes effect instead of being shadowed.
		$this->tracker->clear_native_pro_json( $product, $status );

		// Dual-write: Free's own Processing output reads the prefixed key,
		// while PRO's fallback read (for every status, including Processing
		// once PRO takes over) reads the bare key. Writing both keeps this
		// correct regardless of current or future PRO/license state.
		$product->update_meta_data( 'smartproductemails_message_id_' . $status, $target_post_id );
		if ( 'processing' === $status ) {
			$product->update_meta_data( 'smartproductemails_location_processing', $location );
		}
		$product->update_meta_data( 'location_' . $status, $location );

		// Marker write is always last: an interrupted request leaves this
		// item looking un-imported and safe to retry, never duplicated.
		$this->tracker->mark_imported( $product, $status, $assignment['fingerprint'] );
		$product->save();

		$outcome = ( 'changed' === $assignment['classification'] ) ? 'updated' : 'created';

		return array(
			'product_id'   => $product_id,
			'product_name' => $item['product_name'],
			'status'       => $status,
			'outcome'      => $outcome,
			'was_conflict' => $was_conflict,
			'message'      => empty( $location )
				? __( 'Imported, but no display location was set on the original assignment - choose one on the product\'s Smart Product Emails tab.', 'smart-product-emails' )
				: '',
		);
	}

	/**
	 * Create or update (if already imported once) the smartproductemails
	 * post that mirrors a Woo Custom Emails message post.
	 *
	 * @param int $source_message_id Source woocustomemails post ID.
	 * @return int New/updated target post ID, or 0 on failure.
	 */
	private function sync_message_post( $source_message_id ) {
		$source = get_post( $source_message_id );
		if ( ! $source ) {
			return 0;
		}

		$existing_target = $this->tracker->get_imported_target_post( $source_message_id );
		$post_status     = ( 'publish' === $source->post_status ) ? 'publish' : 'draft';

		if ( $existing_target ) {
			$updated = wp_update_post(
				array(
					'ID'           => $existing_target,
					'post_title'   => $source->post_title,
					'post_content' => $source->post_content,
					'post_status'  => $post_status,
				),
				true
			);

			return is_wp_error( $updated ) ? 0 : $existing_target;
		}

		$new_id = wp_insert_post(
			array(
				'post_type'    => 'smartproductemails',
				'post_title'   => $source->post_title,
				'post_content' => $source->post_content,
				'post_status'  => $post_status,
			),
			true
		);

		if ( is_wp_error( $new_id ) || ! $new_id ) {
			return 0;
		}

		$this->tracker->record_message_mapping( $source_message_id, $new_id );

		return $new_id;
	}

	/**
	 * Create or update the standalone message post generated for Tier C
	 * (oldest v1.x custom_content field, which has no linked message post
	 * to key a mapping off of - tracked instead via a per-product marker).
	 *
	 * @param WC_Product $product WooCommerce product object.
	 * @param array      $assignment Resolved assignment including source_custom_content.
	 * @return int New/updated target post ID, or 0 on failure.
	 */
	private function sync_legacy_content_post( $product, $assignment ) {
		$existing = (int) $product->get_meta( '_spe_imported_legacy_content_post' );

		$title = sprintf(
			/* translators: %s: product name */
			__( 'Imported: %s (legacy content)', 'smart-product-emails' ),
			$product->get_name()
		);

		if ( $existing && 'smartproductemails' === get_post_type( $existing ) ) {
			$updated = wp_update_post(
				array(
					'ID'           => $existing,
					'post_title'   => $title,
					'post_content' => $assignment['source_custom_content'],
					'post_status'  => 'publish',
				),
				true
			);

			return is_wp_error( $updated ) ? 0 : $existing;
		}

		$new_id = wp_insert_post(
			array(
				'post_type'    => 'smartproductemails',
				'post_title'   => $title,
				'post_content' => $assignment['source_custom_content'],
				'post_status'  => 'publish',
			),
			true
		);

		if ( is_wp_error( $new_id ) || ! $new_id ) {
			return 0;
		}

		$product->update_meta_data( '_spe_imported_legacy_content_post', $new_id );

		return $new_id;
	}
}
