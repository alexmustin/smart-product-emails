<?php
/**
 * Read-only scanner for leftover Woo Custom Emails data.
 *
 * Detects whether the predecessor plugin's data still exists in this site's
 * database (it does not need to be active) and builds a full inventory of
 * what can be imported into Smart Product Emails, without writing anything.
 *
 * @package SmartProductEmails
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Read-only scanner for leftover Woo Custom Emails data.
 */
class SmartProductEmails_Import_Scanner {

	const STATUSES = array( 'processing', 'onhold', 'completed' );

	/**
	 * All Woo Custom Emails product-meta keys the importer reads, across
	 * every schema tier the old plugin ever used.
	 *
	 * @var string[]
	 */
	const PRODUCT_META_KEYS = array(
		'wcemessage_id_onhold',
		'wcemessage_id_processing',
		'wcemessage_id_completed',
		'location_onhold',
		'location_processing',
		'location_completed',
		'wcemessage_id',
		'order_status',
		'location',
		'custom_content',
	);

	/**
	 * Tracker used to classify products/statuses.
	 *
	 * @var SmartProductEmails_Import_Tracker
	 */
	private $tracker;

	/**
	 * Constructor.
	 *
	 * @param SmartProductEmails_Import_Tracker $tracker Tracker used to classify products/statuses.
	 */
	public function __construct( SmartProductEmails_Import_Tracker $tracker ) {
		$this->tracker = $tracker;
	}

	/**
	 * Cheap existence check: is there any Woo Custom Emails data on this
	 * site at all? Used to decide whether to show the wizard or an empty
	 * state.
	 *
	 * @return bool
	 */
	public function source_data_exists() {
		$has_posts = ! empty(
			get_posts(
				array(
					'post_type'      => 'woocustomemails',
					'post_status'    => 'any',
					'posts_per_page' => 1,
					'fields'         => 'ids',
				)
			)
		);

		if ( $has_posts ) {
			return true;
		}

		global $wpdb;
		$placeholders = implode( ', ', array_fill( 0, count( self::PRODUCT_META_KEYS ), '%s' ) );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared -- prepared below via $wpdb->prepare(), placeholders built from a fixed-count constant array.
		$has_meta = (bool) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT 1 FROM {$wpdb->postmeta} WHERE meta_key IN ({$placeholders}) LIMIT 1", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- table name constant; placeholders string is a fixed-count run of "%s" built from a constant array, correctly filled by the args below.
				self::PRODUCT_META_KEYS
			)
		);

		if ( $has_meta ) {
			return true;
		}

		return false !== get_option( 'woocustomemails_settings_name' );
	}

	/**
	 * Build the full, read-only inventory of importable data.
	 *
	 * @return array {
	 *     @type array $messages Woo Custom Emails message posts, keyed by post ID.
	 *     @type array $products Per-product resolved status assignments, keyed by product ID.
	 *     @type array $settings Extracted woocustomemails_settings_name values.
	 *     @type array $counts   Rollup counts for the wizard's Review step.
	 * }
	 */
	public function build_inventory() {
		$messages      = $this->load_messages();
		$products_meta = $this->load_product_meta();
		$settings      = $this->load_settings();

		$classification_keys = array( 'new', 'unchanged', 'changed', 'conflict', 'failed' );

		$products = array();
		$counts   = array(
			'messages_found'           => count( $messages ),
			'products_affected'        => 0,
			'by_status'                => array_fill_keys( self::STATUSES, 0 ),
			'by_classification'        => array_fill_keys( $classification_keys, 0 ),
			// Same breakdown as by_classification, but split per status - this
			// is what the Review step's table actually needs to fill in the
			// New/Unchanged/Changed/Conflicts columns per row (the aggregate
			// by_classification total alone can't tell you which column a
			// given status's count belongs in).
			'by_status_classification' => array(
				'processing' => array_fill_keys( $classification_keys, 0 ),
				'onhold'     => array_fill_keys( $classification_keys, 0 ),
				'completed'  => array_fill_keys( $classification_keys, 0 ),
			),
			'dangling'                 => 0,
		);

		foreach ( $products_meta as $product_id => $meta ) {
			$product = wc_get_product( $product_id );
			if ( ! $product ) {
				continue;
			}

			$resolved = $this->resolve_schema_tier( $meta );
			if ( empty( $resolved ) ) {
				continue;
			}

			$product_entry = array(
				'product_id'   => $product_id,
				'product_name' => $product->get_name(),
				'statuses'     => array(),
			);

			foreach ( $resolved as $status => $assignment ) {
				$dangling          = false;
				$source_message_id = $assignment['source_message_id'];

				if ( ! empty( $source_message_id ) ) {
					if ( ! isset( $messages[ $source_message_id ] ) ) {
						$dangling = true;
					} elseif ( 'publish' !== $messages[ $source_message_id ]['status'] ) {
						$dangling = true;
					}
				}

				// Tier C (custom_content) has no message ID/location to detect
				// a content edit through, so fold a content hash into the
				// fingerprint instead - otherwise editing the raw legacy
				// content would never register as "changed" on a re-scan.
				$extra = ( isset( $assignment['source_custom_content'] ) ) ? md5( $assignment['source_custom_content'] ) : '';

				$fingerprint = $this->tracker->fingerprint(
					$assignment['schema_tier'],
					$source_message_id,
					$assignment['source_location'],
					$extra
				);

				$classification = $this->tracker->classify( $product, $status, $fingerprint );

				$product_entry['statuses'][ $status ] = array_merge(
					$assignment,
					array(
						'dangling'       => $dangling,
						'fingerprint'    => $fingerprint,
						'classification' => $classification,
					)
				);

				++$counts['by_status'][ $status ];
				++$counts['by_classification'][ $classification ];
				++$counts['by_status_classification'][ $status ][ $classification ];
				if ( $dangling ) {
					++$counts['dangling'];
				}
			}

			if ( ! empty( $product_entry['statuses'] ) ) {
				$products[ $product_id ] = $product_entry;
				++$counts['products_affected'];
			}
		}

		return array(
			'messages' => $messages,
			'products' => $products,
			'settings' => $settings,
			'counts'   => $counts,
		);
	}

	/**
	 * Build a human-readable summary of each conflicting product/status, so
	 * the Review step can explain exactly what's conflicting - what's
	 * already natively assigned vs. what Woo Custom Emails would bring in -
	 * before the user picks Overwrite or Skip in the next step. Capped at
	 * $limit rows so the scan response stays bounded on large catalogs with
	 * many conflicts.
	 *
	 * @param array $inventory Result of build_inventory().
	 * @param int   $limit Maximum rows to return in detail.
	 * @return array {
	 *     @type array $items Conflict rows: product_id, product_name, status, incoming_title, existing_summary.
	 *     @type int   $total Total conflict count (may exceed count($items) if capped).
	 * }
	 */
	public function describe_conflicts( $inventory, $limit = 25 ) {
		$items = array();
		$total = 0;

		foreach ( $inventory['products'] as $product_id => $entry ) {
			foreach ( $entry['statuses'] as $status => $assignment ) {
				if ( 'conflict' !== $assignment['classification'] ) {
					continue;
				}

				++$total;
				if ( count( $items ) >= $limit ) {
					continue;
				}

				if ( 'custom_content' === $assignment['schema_tier'] ) {
					$incoming_title = __( 'legacy content (no linked message post)', 'smart-product-emails' );
				} elseif ( isset( $inventory['messages'][ $assignment['source_message_id'] ] ) ) {
					$incoming_title = $inventory['messages'][ $assignment['source_message_id'] ]['title'];
				} else {
					$incoming_title = __( '(missing message)', 'smart-product-emails' );
				}

				$items[] = array(
					'product_id'       => $product_id,
					'product_name'     => $entry['product_name'],
					'status'           => $status,
					'incoming_title'   => $incoming_title,
					'existing_summary' => $this->describe_native_data( $product_id, $status ),
				);
			}
		}

		return array(
			'items' => $items,
			'total' => $total,
		);
	}

	/**
	 * Describe what a product's existing native Smart Product Emails
	 * assignment for a status actually is, for the conflict summary above.
	 *
	 * @param int    $product_id Product ID.
	 * @param string $status Status slug.
	 * @return string
	 */
	private function describe_native_data( $product_id, $status ) {
		$product = wc_get_product( $product_id );
		if ( ! $product ) {
			return __( '(unknown)', 'smart-product-emails' );
		}

		$message_id = (int) $product->get_meta( 'smartproductemails_message_id_' . $status );
		if ( $message_id ) {
			$title = get_the_title( $message_id );
			return $title ? $title : __( '(deleted message)', 'smart-product-emails' );
		}

		$pro_json = $product->get_meta( 'spemail_messages_' . $status );
		if ( ! empty( $pro_json ) ) {
			$messages = json_decode( $pro_json, true );
			$count    = is_array( $messages ) ? count( $messages ) : 0;
			return sprintf(
				/* translators: %d: number of messages already configured via Smart Product Emails PRO */
				_n( '%d PRO message already configured', '%d PRO messages already configured', $count, 'smart-product-emails' ),
				$count
			);
		}

		return __( '(unknown)', 'smart-product-emails' );
	}

	/**
	 * Return only the product/status entries still needing work (new,
	 * changed, or conflict per the chosen strategy), ordered by product ID
	 * for a stable, resumable sequence. Used by the batch engine so "resume"
	 * is simply "re-scan and keep going" (see Import_Job).
	 *
	 * @param array  $inventory Result of build_inventory().
	 * @param string $conflict_strategy 'overwrite' or 'skip'.
	 * @return array List of work items: array( product_id, status, assignment ).
	 */
	public function get_pending_work_items( $inventory, $conflict_strategy ) {
		$items = array();

		$product_ids = array_keys( $inventory['products'] );
		sort( $product_ids, SORT_NUMERIC );

		foreach ( $product_ids as $product_id ) {
			$entry = $inventory['products'][ $product_id ];

			foreach ( $entry['statuses'] as $status => $assignment ) {
				if ( in_array( $assignment['classification'], array( 'unchanged', 'failed' ), true ) ) {
					continue;
				}

				if ( 'conflict' === $assignment['classification'] && 'skip' === $conflict_strategy ) {
					continue;
				}

				$items[] = array(
					'product_id'   => $product_id,
					'product_name' => $entry['product_name'],
					'status'       => $status,
					'assignment'   => $assignment,
				);
			}
		}

		return $items;
	}

	/**
	 * Load all Woo Custom Emails message posts.
	 *
	 * @return array Keyed by post ID.
	 */
	private function load_messages() {
		$posts = get_posts(
			array(
				'post_type'      => 'woocustomemails',
				'post_status'    => 'any',
				'posts_per_page' => -1,
			)
		);

		$messages = array();
		foreach ( $posts as $post ) {
			$messages[ $post->ID ] = array(
				'title'       => $post->post_title,
				'content'     => $post->post_content,
				'status'      => $post->post_status,
				'imported_to' => $this->tracker->get_imported_target_post( $post->ID ),
			);
		}

		return $messages;
	}

	/**
	 * Batch-load all relevant Woo Custom Emails product/variation meta in a
	 * single query, grouped by post ID.
	 *
	 * @return array Keyed by product/variation ID => array( meta_key => meta_value ).
	 */
	private function load_product_meta() {
		global $wpdb;

		$placeholders = implode( ', ', array_fill( 0, count( self::PRODUCT_META_KEYS ), '%s' ) );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- one-off read-only scan, not a repeated/hot-path query.
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT pm.post_id, pm.meta_key, pm.meta_value FROM {$wpdb->postmeta} pm INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id WHERE pm.meta_key IN ({$placeholders}) AND p.post_type IN ('product', 'product_variation')", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- table names are constants; placeholders string is a fixed-count run of "%s" built from a constant array, correctly filled by the args below.
				self::PRODUCT_META_KEYS
			)
		);

		$products = array();
		foreach ( $rows as $row ) {
			$products[ (int) $row->post_id ][ $row->meta_key ] = $row->meta_value;
		}

		return $products;
	}

	/**
	 * Read the old plugin's global settings option.
	 *
	 * @return array {
	 *     @type string $display_classes Comma-separated extra product-type CSS classes.
	 *     @type bool   $show_in_admin_email Whether the old "show in admin emails" checkbox was set.
	 * }
	 */
	private function load_settings() {
		$options = get_option( 'woocustomemails_settings_name' );
		if ( ! is_array( $options ) ) {
			$options = array();
		}

		return array(
			'display_classes'     => isset( $options['display_classes'] ) ? $options['display_classes'] : '',
			'show_in_admin_email' => isset( $options['show_in_admin_email'] ) && 'show_in_admin_email' === $options['show_in_admin_email'],
		);
	}

	/**
	 * Resolve which Woo Custom Emails schema tier applies to a product and
	 * return the per-status assignments it implies, mirroring the exact
	 * precedence the old plugin itself uses when reading its own data
	 * (current schema fully suppresses the legacy trio; the legacy trio
	 * fully suppresses the oldest custom_content field).
	 *
	 * @param array $meta Meta key => value pairs for one product.
	 * @return array Status slug => assignment array. Empty if nothing resolves.
	 */
	private function resolve_schema_tier( $meta ) {
		$has_tier_a = false;
		foreach ( self::STATUSES as $status ) {
			if ( ! empty( $meta[ 'wcemessage_id_' . $status ] ) ) {
				$has_tier_a = true;
				break;
			}
		}

		if ( $has_tier_a ) {
			$assignments = array();
			foreach ( self::STATUSES as $status ) {
				$message_id = isset( $meta[ 'wcemessage_id_' . $status ] ) ? (int) $meta[ 'wcemessage_id_' . $status ] : 0;
				if ( empty( $message_id ) ) {
					continue;
				}

				$assignments[ $status ] = array(
					'schema_tier'       => 'current',
					'source_message_id' => $message_id,
					'source_location'   => isset( $meta[ 'location_' . $status ] ) ? $meta[ 'location_' . $status ] : '',
				);
			}

			return $assignments;
		}

		// Tier B: legacy trio (pre-2.2.0), single status only.
		if ( ! empty( $meta['wcemessage_id'] ) && ! empty( $meta['order_status'] ) ) {
			$status = $this->status_from_hook_name( $meta['order_status'] );

			if ( $status ) {
				return array(
					$status => array(
						'schema_tier'       => 'legacy',
						'source_message_id' => (int) $meta['wcemessage_id'],
						'source_location'   => isset( $meta['location'] ) ? $meta['location'] : '',
					),
				);
			}
		}

		// Tier C: oldest v1.x field, no linked message post, implicit "completed".
		if ( ! empty( $meta['custom_content'] ) ) {
			return array(
				'completed' => array(
					'schema_tier'           => 'custom_content',
					'source_message_id'     => 0,
					'source_location'       => '',
					'source_custom_content' => $meta['custom_content'],
				),
			);
		}

		return array();
	}

	/**
	 * Map a Woo Custom Emails legacy 'order_status' hook-name value to a
	 * status slug.
	 *
	 * @param string $hook Full hook name, e.g. 'woocommerce_order_status_processing'.
	 * @return string Status slug, or '' if unrecognized.
	 */
	private function status_from_hook_name( $hook ) {
		$map = array(
			'woocommerce_order_status_on-hold'    => 'onhold',
			'woocommerce_order_status_processing' => 'processing',
			'woocommerce_order_status_completed'  => 'completed',
		);

		return isset( $map[ $hook ] ) ? $map[ $hook ] : '';
	}
}
