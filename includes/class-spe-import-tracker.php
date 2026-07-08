<?php
/**
 * Idempotency tracking for the Woo Custom Emails importer.
 *
 * Tracks which old-plugin data has already been migrated into Smart Product
 * Emails so the importer can be safely re-run (skip unchanged items, update
 * items whose source changed, and detect conflicts with pre-existing native
 * data) without ever creating duplicates.
 *
 * @package SmartProductEmails
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Idempotency tracking for the Woo Custom Emails importer.
 */
class SmartProductEmails_Import_Tracker {

	const STATUSES = array( 'processing', 'onhold', 'completed' );

	/**
	 * Get the target smartproductemails post ID a Woo Custom Emails message
	 * post was already migrated to, if any.
	 *
	 * @param int $wce_post_id Source woocustomemails post ID.
	 * @return int 0 if never imported.
	 */
	public function get_imported_target_post( $wce_post_id ) {
		$target_id = (int) get_post_meta( $wce_post_id, '_spe_imported_to', true );

		// Guard against a stale mapping pointing at a deleted/wrong-type post.
		if ( $target_id && 'smartproductemails' !== get_post_type( $target_id ) ) {
			return 0;
		}

		return $target_id;
	}

	/**
	 * Record that a Woo Custom Emails message post was migrated to a given
	 * smartproductemails post (both directions, for audit + lookup).
	 *
	 * @param int $wce_post_id Source post ID.
	 * @param int $target_post_id Target post ID.
	 */
	public function record_message_mapping( $wce_post_id, $target_post_id ) {
		update_post_meta( $wce_post_id, '_spe_imported_to', $target_post_id );
		update_post_meta( $target_post_id, '_spe_imported_from_wce', $wce_post_id );
	}

	/**
	 * Build the fingerprint hash for a product/status assignment, used to
	 * detect whether the source data has changed since the last import.
	 *
	 * @param string     $schema_tier Which Woo Custom Emails schema tier resolved ('current', 'legacy', 'custom_content').
	 * @param int|string $source_message_id Source message post ID (or empty for Tier C).
	 * @param string     $source_location Source location hook name (may be empty).
	 * @param string     $extra Extra fingerprint input - used for Tier C (custom_content), which has
	 *                          no message ID or location to detect a content edit through, so the
	 *                          scanner passes a hash of the raw content here instead.
	 * @return string
	 */
	public function fingerprint( $schema_tier, $source_message_id, $source_location, $extra = '' ) {
		return md5( $schema_tier . '|' . $source_message_id . '|' . $source_location . '|' . $extra );
	}

	/**
	 * Classify a product/status assignment against tracker state.
	 *
	 * @param WC_Product $product WooCommerce product object.
	 * @param string     $status Status slug: processing|onhold|completed.
	 * @param string     $fingerprint Fingerprint computed from current source data.
	 * @return string One of: new, unchanged, changed, conflict.
	 */
	public function classify( $product, $status, $fingerprint ) {
		$marker = $product->get_meta( '_spe_import_status_' . $status );

		if ( empty( $marker ) ) {
			return $this->has_native_data( $product, $status ) ? 'conflict' : 'new';
		}

		if ( 0 === strpos( $marker, 'error:' ) ) {
			// A previous attempt at this exact source data failed (e.g. a
			// dangling reference). Don't retry it every batch/run forever -
			// only re-attempt once the source data actually changes.
			$failed_fingerprint = substr( $marker, 6 );
			return ( $failed_fingerprint === $fingerprint ) ? 'failed' : 'new';
		}

		return ( $marker === $fingerprint ) ? 'unchanged' : 'changed';
	}

	/**
	 * Whether the product already carries its own (non-imported) Smart
	 * Product Emails assignment for this status.
	 *
	 * Deliberately does NOT check the bare `location_{status}` key by
	 * itself: that exact key name is also what Woo Custom Emails' own
	 * current-schema (Tier A) uses for its OWN location value. Since every
	 * Tier A assignment we're about to import already has that key set (by
	 * the source plugin, not Smart Product Emails), checking it alone would
	 * misclassify nearly every legitimate new import as a false conflict.
	 * A message ID (either shape) is the only reliable signal of genuine
	 * native Smart Product Emails data - PRO's own fallback-read logic
	 * requires one too before it will display anything.
	 *
	 * @param WC_Product $product WooCommerce product object.
	 * @param string     $status Status slug.
	 * @return bool
	 */
	public function has_native_data( $product, $status ) {
		$message_id = $product->get_meta( 'smartproductemails_message_id_' . $status );
		$pro_json   = $product->get_meta( 'spemail_messages_' . $status );

		return ! empty( $message_id ) || ! empty( $pro_json );
	}

	/**
	 * Record the fingerprint marker for a product/status after a successful
	 * write. Must be called last in the engine's per-item write sequence so
	 * an interrupted write is safely retried on the next scan.
	 *
	 * @param WC_Product $product WooCommerce product object.
	 * @param string     $status Status slug.
	 * @param string     $fingerprint Fingerprint to store.
	 */
	public function mark_imported( $product, $status, $fingerprint ) {
		$product->update_meta_data( '_spe_import_status_' . $status, $fingerprint );
	}

	/**
	 * Record that a product/status item failed (e.g. a dangling reference),
	 * so it's excluded from future pending-item batches - within this run
	 * and any future run - until the underlying source data actually
	 * changes. Without this, a permanently-broken item gets swept into
	 * more than one batch as other items succeed and drop out of the
	 * pending list ahead of it, and in a catalog where many broken items
	 * are clustered together the batch loop could never reach zero
	 * remaining at all.
	 *
	 * @param WC_Product $product WooCommerce product object.
	 * @param string     $status Status slug.
	 * @param string     $fingerprint Fingerprint of the (broken) source data that failed.
	 */
	public function mark_failed( $product, $status, $fingerprint ) {
		$product->update_meta_data( '_spe_import_status_' . $status, 'error:' . $fingerprint );
		$product->save();
	}

	/**
	 * Back up a product's pre-existing native assignment for a status before
	 * an overwrite, so it can be manually restored later if needed.
	 *
	 * @param WC_Product $product WooCommerce product object.
	 * @param string     $status Status slug.
	 */
	public function backup_conflict( $product, $status ) {
		$backup = array(
			'message_id'   => $product->get_meta( 'smartproductemails_message_id_' . $status ),
			'location'     => $product->get_meta( 'location_' . $status ),
			'pro_json'     => $product->get_meta( 'spemail_messages_' . $status ),
			'backed_up_at' => current_time( 'mysql' ),
		);

		$product->update_meta_data( '_spe_import_conflict_backup_' . $status, wp_json_encode( $backup ) );
	}

	/**
	 * Remove PRO's multi-message JSON meta for a status before writing the
	 * importer's legacy-shape data. PRO's own fallback-read checks the JSON
	 * key first and ignores the legacy keys entirely while it's present, so
	 * without this an overwrite would be silently shadowed and never
	 * actually display. Only called when actually writing (never for a
	 * skipped conflict).
	 *
	 * @param WC_Product $product WooCommerce product object.
	 * @param string     $status Status slug.
	 */
	public function clear_native_pro_json( $product, $status ) {
		$product->delete_meta_data( 'spemail_messages_' . $status );
	}
}
