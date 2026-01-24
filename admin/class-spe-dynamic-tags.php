<?php
/**
 * Dynamic Tags Button and Modal for SPE Message Editor
 *
 * Adds a "Dynamic Tags" button above the content editor that opens a modal
 * listing all available placeholders organized by category.
 *
 * @package SmartProductEmails
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * SmartProductEmails_Dynamic_Tags handles the Dynamic Tags button and modal.
 */
class SmartProductEmails_Dynamic_Tags {

	/**
	 * Constructor - Initialize hooks
	 */
	public function __construct() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'edit_form_after_title', array( $this, 'render_dynamic_tags_button' ) );
		add_action( 'admin_footer', array( $this, 'render_dynamic_tags_modal' ) );
	}

	/**
	 * Check if we're on the SPE Message editor screen
	 *
	 * @return bool
	 */
	private function is_spe_message_screen() {
		$screen = get_current_screen();
		return $screen && 'smartproductemails' === $screen->post_type && 'post' === $screen->base;
	}

	/**
	 * Enqueue admin scripts and styles
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_assets( $hook ) {
		// Only load on post.php and post-new.php
		if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
			return;
		}

		// Check if we're on SPE Message post type
		$screen = get_current_screen();
		if ( ! $screen || 'smartproductemails' !== $screen->post_type ) {
			return;
		}

		wp_enqueue_style(
			'spe-dynamic-tags',
			SMARTPRODUCTEMAILS_PLUGIN_URL . 'admin/css/spe-dynamic-tags.css',
			array(),
			SMARTPRODUCTEMAILS_PLUGIN_VERSION
		);

		wp_enqueue_script(
			'spe-dynamic-tags',
			SMARTPRODUCTEMAILS_PLUGIN_URL . 'admin/js/spe-dynamic-tags.js',
			array( 'jquery' ),
			SMARTPRODUCTEMAILS_PLUGIN_VERSION,
			true
		);

		wp_localize_script(
			'spe-dynamic-tags',
			'speDynamicTags',
			array(
				'i18n' => array(
					'insertTag'    => __( 'Click a tag to insert it at your cursor position', 'smart-product-emails' ),
					'copied'       => __( 'Tag inserted!', 'smart-product-emails' ),
					'searchPlaceholder' => __( 'Search tags...', 'smart-product-emails' ),
				),
			)
		);
	}

	/**
	 * Render the Dynamic Tags button above the editor
	 *
	 * @param WP_Post $post The post object.
	 */
	public function render_dynamic_tags_button( $post ) {
		if ( 'smartproductemails' !== $post->post_type ) {
			return;
		}

		?>
		<div class="spe-editor-buttons">
			<button type="button" id="spe-dynamic-tags-btn" class="button spe-editor-btn">
				<span class="dashicons dashicons-tag"></span>
				<?php esc_html_e( 'Dynamic Tags', 'smart-product-emails' ); ?>
			</button>
			<?php
			/**
			 * Hook for PRO version to add additional buttons (e.g., Content Library)
			 *
			 * @param WP_Post $post The post object.
			 */
			do_action( 'spe_editor_buttons_after_dynamic_tags', $post );
			?>
		</div>
		<?php
	}

	/**
	 * Render the Dynamic Tags modal
	 */
	public function render_dynamic_tags_modal() {
		if ( ! $this->is_spe_message_screen() ) {
			return;
		}

		$tags = $this->get_dynamic_tags();
		?>
		<div id="spe-dynamic-tags-modal" class="spe-modal" style="display: none;">
			<div class="spe-modal-overlay"></div>
			<div class="spe-modal-container">
				<div class="spe-modal-header">
					<h2><?php esc_html_e( 'Dynamic Tags', 'smart-product-emails' ); ?></h2>
					<p class="spe-modal-subtitle"><?php esc_html_e( 'Click a tag to insert it at your cursor position in the editor.', 'smart-product-emails' ); ?></p>
					<button type="button" class="spe-modal-close" aria-label="<?php esc_attr_e( 'Close', 'smart-product-emails' ); ?>">
						<span class="dashicons dashicons-no-alt"></span>
					</button>
				</div>

				<div class="spe-modal-search">
					<input type="text" id="spe-tag-search" placeholder="<?php esc_attr_e( 'Search tags...', 'smart-product-emails' ); ?>" />
					<span class="dashicons dashicons-search"></span>
				</div>

				<div class="spe-modal-body">
					<?php foreach ( $tags as $category_slug => $category ) : ?>
						<div class="spe-tag-category" data-category="<?php echo esc_attr( $category_slug ); ?>">
							<h3 class="spe-tag-category-title">
								<span class="dashicons <?php echo esc_attr( $category['icon'] ); ?>"></span>
								<?php echo esc_html( $category['title'] ); ?>
								<span class="spe-tag-count"><?php echo count( $category['tags'] ); ?></span>
							</h3>
							<div class="spe-tag-list">
								<?php foreach ( $category['tags'] as $tag => $info ) : ?>
									<div class="spe-tag-item" data-tag="<?php echo esc_attr( $tag ); ?>">
										<div class="spe-tag-main">
											<code class="spe-tag-code"><?php echo esc_html( $tag ); ?></code>
											<span class="spe-tag-description"><?php echo esc_html( $info['description'] ); ?></span>
										</div>
										<div class="spe-tag-example">
											<span class="spe-tag-example-label"><?php esc_html_e( 'Example:', 'smart-product-emails' ); ?></span>
											<span class="spe-tag-example-value"><?php echo esc_html( $info['example'] ); ?></span>
										</div>
									</div>
								<?php endforeach; ?>
							</div>
						</div>
					<?php endforeach; ?>

					<div class="spe-no-results" style="display: none;">
						<span class="dashicons dashicons-search"></span>
						<p><?php esc_html_e( 'No tags found matching your search.', 'smart-product-emails' ); ?></p>
					</div>
				</div>

				<div class="spe-modal-footer">
					<?php
					$docs_url = add_query_arg(
						array(
							'post_type' => 'smartproductemails',
							'page'      => 'smartproductemails-settings',
							'tab'       => 'placeholders',
							'_wpnonce'  => wp_create_nonce( 'smartproductemails_admin_nonce' ),
						),
						admin_url( 'edit.php' )
					) . '#placeholders';
					?>
					<a href="<?php echo esc_url( $docs_url ); ?>" target="_blank" class="spe-docs-link">
						<span class="dashicons dashicons-external"></span>
						<?php esc_html_e( 'View Full Documentation', 'smart-product-emails' ); ?>
					</a>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Get all available dynamic tags organized by category
	 *
	 * @return array
	 */
	private function get_dynamic_tags() {
		$tags = array(
			'site' => array(
				'title' => __( 'Site & Store', 'smart-product-emails' ),
				'icon'  => 'dashicons-store',
				'tags'  => array(
					'{site_title}'   => array(
						'description' => __( 'Your website/store name', 'smart-product-emails' ),
						'example'     => 'My Awesome Store',
					),
					'{site_address}' => array(
						'description' => __( 'Your website domain', 'smart-product-emails' ),
						'example'     => 'mystore.com',
					),
					'{site_url}'     => array(
						'description' => __( 'Full website URL', 'smart-product-emails' ),
						'example'     => 'https://mystore.com',
					),
					'{store_email}'  => array(
						'description' => __( 'Store contact email', 'smart-product-emails' ),
						'example'     => 'hello@mystore.com',
					),
				),
			),
			'order' => array(
				'title' => __( 'Order Information', 'smart-product-emails' ),
				'icon'  => 'dashicons-clipboard',
				'tags'  => array(
					'{order_number}'   => array(
						'description' => __( 'Order number/ID', 'smart-product-emails' ),
						'example'     => '12345',
					),
					'{order_id}'       => array(
						'description' => __( 'Order ID (same as order_number)', 'smart-product-emails' ),
						'example'     => '12345',
					),
					'{order_date}'     => array(
						'description' => __( 'Date order was placed', 'smart-product-emails' ),
						'example'     => 'December 4, 2025',
					),
					'{order_time}'     => array(
						'description' => __( 'Time order was placed', 'smart-product-emails' ),
						'example'     => '2:30 PM',
					),
					'{order_status}'   => array(
						'description' => __( 'Current order status', 'smart-product-emails' ),
						'example'     => 'Processing',
					),
					'{payment_method}' => array(
						'description' => __( 'How customer paid', 'smart-product-emails' ),
						'example'     => 'Credit Card',
					),
				),
			),
			'customer' => array(
				'title' => __( 'Customer Information', 'smart-product-emails' ),
				'icon'  => 'dashicons-admin-users',
				'tags'  => array(
					'{customer_first_name}' => array(
						'description' => __( 'Customer\'s first name', 'smart-product-emails' ),
						'example'     => 'John',
					),
					'{customer_last_name}'  => array(
						'description' => __( 'Customer\'s last name', 'smart-product-emails' ),
						'example'     => 'Doe',
					),
					'{customer_name}'       => array(
						'description' => __( 'Full name', 'smart-product-emails' ),
						'example'     => 'John Doe',
					),
					'{customer_email}'      => array(
						'description' => __( 'Customer\'s email address', 'smart-product-emails' ),
						'example'     => 'john@example.com',
					),
					'{customer_phone}'      => array(
						'description' => __( 'Customer\'s phone number', 'smart-product-emails' ),
						'example'     => '(555) 123-4567',
					),
				),
			),
			'billing' => array(
				'title' => __( 'Billing Address', 'smart-product-emails' ),
				'icon'  => 'dashicons-location',
				'tags'  => array(
					'{billing_address}'  => array(
						'description' => __( 'Complete formatted billing address', 'smart-product-emails' ),
						'example'     => '123 Main St, New York, NY 10001',
					),
					'{billing_city}'     => array(
						'description' => __( 'Billing city', 'smart-product-emails' ),
						'example'     => 'New York',
					),
					'{billing_state}'    => array(
						'description' => __( 'Billing state/province', 'smart-product-emails' ),
						'example'     => 'NY',
					),
					'{billing_postcode}' => array(
						'description' => __( 'Billing ZIP/postal code', 'smart-product-emails' ),
						'example'     => '10001',
					),
					'{billing_country}'  => array(
						'description' => __( 'Billing country name', 'smart-product-emails' ),
						'example'     => 'United States',
					),
				),
			),
			'shipping' => array(
				'title' => __( 'Shipping Address', 'smart-product-emails' ),
				'icon'  => 'dashicons-car',
				'tags'  => array(
					'{shipping_address}'  => array(
						'description' => __( 'Complete formatted shipping address', 'smart-product-emails' ),
						'example'     => '456 Oak Ave, Los Angeles, CA 90001',
					),
					'{shipping_city}'     => array(
						'description' => __( 'Shipping city', 'smart-product-emails' ),
						'example'     => 'Los Angeles',
					),
					'{shipping_state}'    => array(
						'description' => __( 'Shipping state/province', 'smart-product-emails' ),
						'example'     => 'CA',
					),
					'{shipping_postcode}' => array(
						'description' => __( 'Shipping ZIP/postal code', 'smart-product-emails' ),
						'example'     => '90001',
					),
					'{shipping_country}'  => array(
						'description' => __( 'Shipping country name', 'smart-product-emails' ),
						'example'     => 'United States',
					),
				),
			),
			'totals' => array(
				'title' => __( 'Order Totals', 'smart-product-emails' ),
				'icon'  => 'dashicons-money-alt',
				'tags'  => array(
					'{order_subtotal}' => array(
						'description' => __( 'Order subtotal (before tax/shipping)', 'smart-product-emails' ),
						'example'     => '$85.00',
					),
					'{order_total}'    => array(
						'description' => __( 'Final order total', 'smart-product-emails' ),
						'example'     => '$99.99',
					),
					'{order_tax}'      => array(
						'description' => __( 'Total tax amount', 'smart-product-emails' ),
						'example'     => '$7.50',
					),
					'{order_shipping}' => array(
						'description' => __( 'Shipping cost', 'smart-product-emails' ),
						'example'     => '$7.49',
					),
					'{order_discount}' => array(
						'description' => __( 'Total discount amount', 'smart-product-emails' ),
						'example'     => '$10.00',
					),
				),
			),
			'product' => array(
				'title' => __( 'Product Information', 'smart-product-emails' ),
				'icon'  => 'dashicons-products',
				'tags'  => array(
					'{product_id}'                => array(
						'description' => __( 'Product ID number', 'smart-product-emails' ),
						'example'     => '1234',
					),
					'{product_name}'              => array(
						'description' => __( 'Product name/title', 'smart-product-emails' ),
						'example'     => 'Premium Wireless Headphones',
					),
					'{product_sku}'               => array(
						'description' => __( 'Product SKU code', 'smart-product-emails' ),
						'example'     => 'WH-2000',
					),
					'{product_url}'               => array(
						'description' => __( 'Product page URL', 'smart-product-emails' ),
						'example'     => 'https://mystore.com/product/headphones',
					),
					'{product_price}'             => array(
						'description' => __( 'Current product price', 'smart-product-emails' ),
						'example'     => '$79.99',
					),
					'{product_regular_price}'     => array(
						'description' => __( 'Regular (non-sale) price', 'smart-product-emails' ),
						'example'     => '$99.99',
					),
					'{product_sale_price}'        => array(
						'description' => __( 'Sale price (if on sale)', 'smart-product-emails' ),
						'example'     => '$79.99',
					),
					'{product_short_description}' => array(
						'description' => __( 'Product short description', 'smart-product-emails' ),
						'example'     => 'Premium sound quality...',
					),
					'{product_description}'       => array(
						'description' => __( 'Full product description', 'smart-product-emails' ),
						'example'     => 'Experience crystal-clear audio...',
					),
					'{product_categories}'        => array(
						'description' => __( 'Product categories (comma-separated)', 'smart-product-emails' ),
						'example'     => 'Electronics, Audio, Headphones',
					),
					'{product_tags}'              => array(
						'description' => __( 'Product tags (comma-separated)', 'smart-product-emails' ),
						'example'     => 'wireless, bluetooth, premium',
					),
				),
			),
		);

		/**
		 * Filter to allow PRO version or extensions to add custom tag categories
		 *
		 * @param array $tags Array of tag categories and their tags.
		 */
		return apply_filters( 'spe_dynamic_tags_list', $tags );
	}
}

// Initialize the class.
new SmartProductEmails_Dynamic_Tags();
