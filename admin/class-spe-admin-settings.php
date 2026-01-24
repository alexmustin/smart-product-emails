<?php
/**
 * Adds the Admin Settings for the plugin.
 *
 * @package SmartProductEmails
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Smart_Product_Emails_For_WooCommerce_Admin_Settings is a class for adding the Admin Settings for the plugin.
 */
class Smart_Product_Emails_For_WooCommerce_Admin_Settings {

	/**
	 * Tracks the plugin settings.
	 *
	 * @var object $smartproductemails_settings_options Object to track the settings for the plugin.
	 */
	private $smartproductemails_settings_options;

	/**
	 * Setup the plugin settings object.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( &$this, 'spe_settings_add_plugin_page' ) );
		add_action( 'admin_init', array( &$this, 'spe_settings_page_init' ) );
		add_action( 'admin_enqueue_scripts', array( &$this, 'spe_enqueue_separator_admin_scripts' ) );
		add_action( 'admin_notices', array( &$this, 'spe_woocommerce_missing_notice' ) );
	}

	/**
	 * Adds the WP Color Picker scripts to the page.
	 */
	public function spe_enqueue_separator_admin_scripts($hook) {
		// Only load on SPE settings page
		if (strpos($hook, 'smartproductemails') === false) {
			return;
		}

		// Enqueue WordPress color picker
		wp_enqueue_style('wp-color-picker');
		wp_enqueue_script('wp-color-picker');
	}

	/**
	 * Display admin notice if WooCommerce is not active.
	 */
	public function spe_woocommerce_missing_notice() {
		// Only show on SPE settings page
		$screen = get_current_screen();
		if ( ! $screen || strpos( $screen->id, 'smartproductemails_page_smartproductemails-settings' ) === false ) {
			return;
		}

		// Check if WooCommerce is active
		if ( ! $this->is_woocommerce_active() ) {
			$woo_plugin_url = esc_url( 'https://wordpress.org/plugins/woocommerce/' );
			?>
			<div class="notice notice-error">
				<p>
					<strong><?php esc_html_e( 'WooCommerce Required', 'smart-product-emails' ); ?></strong>
				</p>
				<p>
					<?php
					printf(
						/* translators: %1$s: opening link tag, %2$s: closing link tag */
						esc_html__( 'Smart Product Emails requires %1$sWooCommerce%2$s to be installed and activated. Please install and activate WooCommerce to use this plugin.', 'smart-product-emails' ),
						'<a href="' . esc_url( $woo_plugin_url ) . '" target="_blank">',
						'</a>'
					);
					?>
				</p>
			</div>
			<?php
		}
	}

	/**
	 * Check if WooCommerce is active.
	 *
	 * @return bool True if WooCommerce is active, false otherwise.
	 */
	private function is_woocommerce_active() {
		// Check if WooCommerce class exists (most reliable method)
		if ( class_exists( 'WooCommerce' ) ) {
			return true;
		}

		// Fallback: Check if WooCommerce plugin is in active plugins list (direct check to avoid false positive from Plugin Check)
		$active_plugins = get_option( 'active_plugins', array() );
		return in_array( 'woocommerce/woocommerce.php', $active_plugins, true );
	}

	/**
	 * Adds the plugin settings page to the menu.
	 */
	public function spe_settings_add_plugin_page() {
		add_submenu_page(
			'edit.php?post_type=smartproductemails', // parent slug.
			__( 'SPE Settings', 'smart-product-emails' ), // page title.
			__( 'SPE Settings', 'smart-product-emails' ), // menu title.
			'manage_options', // capability.
			'smartproductemails-settings', // menu slug.
			array( $this, 'spe_settings_create_admin_page' ) // callback function.
		);
	}

	/**
	 * Adds the plugin Admin settings page to the menu.
	 */
	public function spe_settings_create_admin_page() {
		$this->smartproductemails_settings_options = get_option( 'SmartProductEmails_settings_name' );
		?>

		<div class="wrap">
			<h2><?php esc_html_e( 'Smart Product Emails Settings', 'smart-product-emails' ); ?></h2>

			<hr>

			<p class="howto"><?php echo esc_html__( 'Settings for the Smart Product Emails plugin.', 'smart-product-emails' ); ?></p>

			<?php settings_errors(); ?>

			<?php
			// Nonce check for tab parameter (only if tab is set in URL)
			$nonce_verified = false;
			if (isset($_GET['_wpnonce']) && wp_verify_nonce(sanitize_key($_GET['_wpnonce']), 'smartproductemails_admin_nonce')) {
				$nonce_verified = true;
			}

			if (isset($_GET['tab']) && $nonce_verified) {
				$active_tab = sanitize_text_field(wp_unslash($_GET['tab']));
			} else {
				$active_tab = 'display_settings';
			}

			// Page parameter is set by WordPress admin menu, so just sanitize it
			if ( isset( $_GET['page'] ) ) {
				$page = sanitize_text_field( wp_unslash( $_GET['page'] ) );
			} else {
				$page = 'smartproductemails-settings';
			}

			// Update the tab links to include nonces:
			$nonce_url = wp_create_nonce('smartproductemails_admin_nonce');
			?>
			<h2 class="nav-tab-wrapper">
				<a href="?post_type=smartproductemails&page=<?php echo esc_attr( $page ); ?>&tab=display_settings&_wpnonce=<?php echo esc_attr($nonce_url); ?>" class="nav-tab <?php echo 'display_settings' === $active_tab ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Display Settings', 'smart-product-emails' ); ?></a>
				<a href="?post_type=smartproductemails&page=<?php echo esc_attr( $page ); ?>&tab=placeholders&_wpnonce=<?php echo esc_attr($nonce_url); ?>#placeholders" class="nav-tab <?php echo 'placeholders' === $active_tab ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Dynamic Tags', 'smart-product-emails' ); ?></a>
			</h2>

			<?php if ( 'display_settings' === $active_tab ) : ?>
			<form method="post" action="options.php">
				<?php
				settings_fields( 'smartproductemails_settings_option_group' );
				do_settings_sections( 'smartproductemails-settings-admin' );
				submit_button();
				?>
			</form>
			<?php elseif ( 'placeholders' === $active_tab ) : ?>
				<?php $this->render_placeholders_documentation(); ?>
			<?php endif; ?>
		</div>
		<?php

		$this->spe_settings_scriptsandstyles();
	}

	/**
	 * Adds the 'Display Settings' section to the plugin settings page.
	 */
	public function spe_settings_page_init() {
		register_setting(
			'smartproductemails_settings_option_group', // option_group.
			'SmartProductEmails_settings_name', // option_name.
			array( $this, 'spe_settings_sanitize' ) // sanitize_callback.
		);

		add_settings_section(
			'smartproductemails_settings_content_separator_section', // id.
			__('Content Separator', 'smart-product-emails'), // title.
			array( $this, 'spe_settings_section_info' ), // callback.
			'smartproductemails-settings-admin' // page.
		);

		// Add settings field: Separator Styles
		add_settings_field(
			'smartproductemails_content_separator_style_field', // Field ID
			__('Separator Style', 'smart-product-emails'), // Field title
			array( $this, 'spe_settings_content_separator_style'), // Callback function
			'smartproductemails-settings-admin', // Page slug
			'smartproductemails_settings_content_separator_section' // Section ID
		);

		// Add settings field: Separator Color
		add_settings_field(
			'smartproductemails_content_separator_color_field', // Field ID
			__('Separator Color', 'smart-product-emails'), // Field title
			array( $this, 'spe_settings_content_separator_color'), // Callback function
			'smartproductemails-settings-admin', // Page slug
			'smartproductemails_settings_content_separator_section', // Section ID
			array(
				'label_for' => 'smartproductemails_content_separator_color_field', // Associates the label with the input field
				'class'     => 'spe_separator_color_row', // CSS class to be added to the <tr> element
			)
		);

		// Add settings field: Separator Thickness
		add_settings_field(
			'smartproductemails_content_separator_thickness_field', // Field ID
			__('Line Thickness', 'smart-product-emails'), // Field title
			array( $this, 'spe_settings_content_separator_thickness'), // Callback function
			'smartproductemails-settings-admin', // Page slug
			'smartproductemails_settings_content_separator_section', // Section ID
			array(
				'label_for' => 'smartproductemails_content_separator_thickness_field', // Associates the label with the input field
				'class'     => 'spe_separator_thickness_row', // CSS class to be added to the <tr> element
			)
		);

		// Add settings field: Separator Spacing
		add_settings_field(
			'smartproductemails_content_separator_spacing_field', // Field ID
			__('Spacing', 'smart-product-emails'), // Field title
			array( $this, 'spe_settings_content_separator_spacing'), // Callback function
			'smartproductemails-settings-admin', // Page slug
			'smartproductemails_settings_content_separator_section', // Section ID
			array(
				'label_for' => 'smartproductemails_content_separator_spacing_field', // Associates the label with the input field
				'class'     => 'spe_separator_spacing_row', // CSS class to be added to the <tr> element
			)
		);

		// Add settings field: Custom HTML
		add_settings_field(
			'smartproductemails_content_separator_customhtml_field', // Field ID
			__('Custom Separator HTML', 'smart-product-emails'), // Field title
			array( $this, 'spe_settings_content_separator_customhtml'), // Callback function
			'smartproductemails-settings-admin', // Page slug
			'smartproductemails_settings_content_separator_section', // Section ID
			array(
				'label_for' => 'smartproductemails_content_separator_customhtml_field', // Associates the label with the input field
				'class'     => 'spe_separator_customhtml_row', // CSS class to be added to the <tr> element
			)
		);

		// Add settings field: Live Preview
		add_settings_field(
			'smartproductemails_content_separator_livepreview', // Field ID
			__('Live Preview', 'smart-product-emails'), // Field title
			array( $this, 'spe_settings_content_separator_livepreview'), // Callback function
			'smartproductemails-settings-admin', // Page slug
			'smartproductemails_settings_content_separator_section' // Section ID
		);
	}

	/**
	 * Sanitizes field inputs.
	 *
	 * @param string $input The field data to be sanitized.
	 */
	public function spe_settings_sanitize( $input ) {
		$sanitary_values = array();

		// Separator style.
		if ( isset( $input['content_separator'] ) ) {
			$sanitary_values['content_separator'] = sanitize_text_field( $input['content_separator'] );
		}

		// Separator color.
		if ( isset( $input['separator_color'] ) ) {
			$sanitary_values['separator_color'] = sanitize_text_field( $input['separator_color'] );
		}

		// Separator thickness.
		if ( isset( $input['separator_thickness'] ) ) {
			$sanitary_values['separator_thickness'] = sanitize_text_field( $input['separator_thickness'] );
		}

		// Separator spacing.
		if ( isset( $input['separator_spacing'] ) ) {
			$sanitary_values['separator_spacing'] = sanitize_text_field( $input['separator_spacing'] );
		}

		// Separator custom HTML.
		if ( isset( $input['separator_customhtml'] ) ) {
			$sanitary_values['separator_customhtml'] = wp_kses_post( $input['separator_customhtml'] );
		}

		return $sanitary_values;
	}

	/**
	 * Displays a line of description text.
	 */
	public function spe_settings_section_info() {
		esc_html_e('Customize how your custom email content is visually separated from the rest of the order email.', 'smart-product-emails');
	}

	/**
	 * Adds setting field: Separator Style
	 */
	public function spe_settings_content_separator_style() {

		// Separator settings with defaults
        $content_separator = isset( $this->smartproductemails_settings_options['content_separator'] ) ? $this->smartproductemails_settings_options['content_separator'] : 'none';

		?>
		<select name="SmartProductEmails_settings_name[content_separator]" id="spe_content_separator" class="regular-text">

			<option value="none" <?php selected($content_separator, 'none'); ?>>
				<?php esc_html_e('None - No separator', 'smart-product-emails'); ?>
			</option>
			<option value="line" <?php selected($content_separator, 'line'); ?>>
				<?php esc_html_e('Solid Line', 'smart-product-emails'); ?>
			</option>
			<option value="dots" <?php selected($content_separator, 'dots'); ?>>
				<?php esc_html_e('Dotted Line', 'smart-product-emails'); ?>
			</option>
			<option value="dashes" <?php selected($content_separator, 'dashes'); ?>>
				<?php esc_html_e('Dashed Line', 'smart-product-emails'); ?>
			</option>
			<option value="double" <?php selected($content_separator, 'double'); ?>>
				<?php esc_html_e('Double Line', 'smart-product-emails'); ?>
			</option>
			<option value="space" <?php selected($content_separator, 'space'); ?>>
				<?php esc_html_e('Extra Space Only', 'smart-product-emails'); ?>
			</option>
			<option value="custom" <?php selected($content_separator, 'custom'); ?>>
				<?php esc_html_e('Custom HTML', 'smart-product-emails'); ?>
			</option>

		</select>

		<?php
		echo '<p>';
		esc_html_e('Choose a visual style to separate your custom content from order details.', 'smart-product-emails');
		echo '</p>';

	}

	/**
	 * Adds setting field: Separator Color
	 */
	public function spe_settings_content_separator_color() {
		$separator_color = isset( $this->smartproductemails_settings_options['separator_color'] ) ? $this->smartproductemails_settings_options['separator_color'] : '#dddddd';

		?>
		<!-- Separator Color (for line styles) -->
		<input type="text"
			name="SmartProductEmails_settings_name[separator_color]"
			id="spe_separator_color"
			value="<?php echo esc_attr($separator_color); ?>"
			class="spe-color-picker"
			data-default-color="#dddddd" />

			<p class="description">
				<?php esc_html_e('Choose the color for the separator line.', 'smart-product-emails'); ?>
			</p>
		<?php
	}

	/**
	 * Adds setting field: Separator Thickness
	 */
	public function spe_settings_content_separator_thickness() {
		$separator_thickness = isset( $this->smartproductemails_settings_options['separator_thickness'] ) ? $this->smartproductemails_settings_options['separator_thickness'] : '1';
		?>
		<input type="range"
			name="SmartProductEmails_settings_name[separator_thickness]"
			id="spe_separator_thickness"
			min="1"
			max="5"
			step="1"
			value="<?php echo esc_attr($separator_thickness); ?>"
			style="width: 200px;" />
		<span id="spe_thickness_value"><?php echo esc_html($separator_thickness); ?>px</span>
		<p class="description">
			<?php esc_html_e('Adjust the thickness of the separator line (1-5 pixels).', 'smart-product-emails'); ?>
		</p>
		<?php
	}

	/**
	 * Adds setting field: Separator Spacing
	 */
	public function spe_settings_content_separator_spacing() {
		$separator_spacing = isset( $this->smartproductemails_settings_options['separator_spacing'] ) ? $this->smartproductemails_settings_options['separator_spacing'] : '20';
		?>
		<input type="range"
			name="SmartProductEmails_settings_name[separator_spacing]"
			id="spe_separator_spacing"
			min="10"
			max="50"
			step="5"
			value="<?php echo esc_attr($separator_spacing); ?>"
			style="width: 200px;" />
		<span id="spe_spacing_value"><?php echo esc_html($separator_spacing); ?>px</span>
		<p class="description">
			<?php esc_html_e('Space above and below the separator (10-50 pixels).', 'smart-product-emails'); ?>
		</p>
		<?php
	}

	/**
	 * Adds setting field: Separator Custom HTML
	 */
	public function spe_settings_content_separator_customhtml() {
		$custom_separator_html = isset( $this->smartproductemails_settings_options['separator_customhtml'] ) ? $this->smartproductemails_settings_options['separator_customhtml'] : '';
		?>
		<textarea name="SmartProductEmails_settings_name[separator_customhtml]"
			id="spe_separator_customhtml"
			rows="5"
			class="large-text code"
			placeholder='<div style="border-top: 2px solid #ff9800; margin: 20px 0;"></div>'><?php echo nl2br( esc_html($custom_separator_html)); ?></textarea>
		<p class="description">
			<?php esc_html_e('Enter custom HTML for your separator. Must use inline CSS styles for email compatibility. Shortcodes are not supported.', 'smart-product-emails'); ?>
		</p>
		<details style="margin-top: 10px;">
			<summary style="cursor: pointer; color: #2271b1;"><?php esc_html_e('Show Examples', 'smart-product-emails'); ?></summary>
			<div style="margin-top: 10px; padding: 10px; background: #f9f9f9; border-left: 3px solid #2271b1;">
				<p><strong><?php esc_html_e('Brand-colored line:', 'smart-product-emails'); ?></strong></p>
				<code>&lt;hr style="border: none; border-top: 3px solid #ff6900; margin: 20px 0;" /&gt;</code>

				<p style="margin-top: 15px;"><strong><?php esc_html_e('Decorative stars:', 'smart-product-emails'); ?></strong></p>
				<code>&lt;div style="text-align: center; color: #999; margin: 20px 0;"&gt;★ ★ ★&lt;/div&gt;</code>

				<p style="margin-top: 15px;"><strong><?php esc_html_e('Gradient line:', 'smart-product-emails'); ?></strong></p>
				<code>&lt;div style="height: 2px; background: linear-gradient(to right, #0c88d9, #9459dc); margin: 20px 0;"&gt;&lt;/div&gt;</code>
			</div>
		</details>
		<?php
	}

	/**
	 * Renders the Dynamic Tags / Placeholders documentation page
	 */
	public function render_placeholders_documentation() {
		?>
		<div id="placeholders" class="spe-placeholders-docs">
			<div class="spe-docs-intro">
				<h2><?php esc_html_e( 'Dynamic Tags Reference', 'smart-product-emails' ); ?></h2>
				<p><?php esc_html_e( 'Use dynamic tags in your SPE Messages to personalize emails with real customer and order data. Simply type the tag (including curly braces) anywhere in your message content.', 'smart-product-emails' ); ?></p>
				<div class="spe-docs-tip">
					<span class="dashicons dashicons-lightbulb"></span>
					<p><?php esc_html_e( 'Tip: Use the "Dynamic Tags" button above the editor when creating SPE Messages to quickly insert tags at your cursor position.', 'smart-product-emails' ); ?></p>
				</div>
			</div>

			<!-- Site & Store -->
			<div class="spe-docs-category">
				<h3>
					<span class="dashicons dashicons-store"></span>
					<?php esc_html_e( 'Site & Store', 'smart-product-emails' ); ?>
				</h3>
				<table class="widefat spe-docs-table">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Tag', 'smart-product-emails' ); ?></th>
							<th><?php esc_html_e( 'Description', 'smart-product-emails' ); ?></th>
							<th><?php esc_html_e( 'Example Output', 'smart-product-emails' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td><code>{site_title}</code></td>
							<td><?php esc_html_e( 'Your website/store name', 'smart-product-emails' ); ?></td>
							<td><em>My Awesome Store</em></td>
						</tr>
						<tr>
							<td><code>{site_address}</code></td>
							<td><?php esc_html_e( 'Your website domain', 'smart-product-emails' ); ?></td>
							<td><em>mystore.com</em></td>
						</tr>
						<tr>
							<td><code>{site_url}</code></td>
							<td><?php esc_html_e( 'Full website URL', 'smart-product-emails' ); ?></td>
							<td><em>https://mystore.com</em></td>
						</tr>
						<tr>
							<td><code>{store_email}</code></td>
							<td><?php esc_html_e( 'Store contact email', 'smart-product-emails' ); ?></td>
							<td><em>hello@mystore.com</em></td>
						</tr>
					</tbody>
				</table>
			</div>

			<!-- Order Information -->
			<div class="spe-docs-category">
				<h3>
					<span class="dashicons dashicons-clipboard"></span>
					<?php esc_html_e( 'Order Information', 'smart-product-emails' ); ?>
				</h3>
				<table class="widefat spe-docs-table">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Tag', 'smart-product-emails' ); ?></th>
							<th><?php esc_html_e( 'Description', 'smart-product-emails' ); ?></th>
							<th><?php esc_html_e( 'Example Output', 'smart-product-emails' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td><code>{order_number}</code></td>
							<td><?php esc_html_e( 'Order number/ID', 'smart-product-emails' ); ?></td>
							<td><em>12345</em></td>
						</tr>
						<tr>
							<td><code>{order_id}</code></td>
							<td><?php esc_html_e( 'Order ID (same as order_number)', 'smart-product-emails' ); ?></td>
							<td><em>12345</em></td>
						</tr>
						<tr>
							<td><code>{order_date}</code></td>
							<td><?php esc_html_e( 'Date order was placed', 'smart-product-emails' ); ?></td>
							<td><em>December 4, 2025</em></td>
						</tr>
						<tr>
							<td><code>{order_time}</code></td>
							<td><?php esc_html_e( 'Time order was placed', 'smart-product-emails' ); ?></td>
							<td><em>2:30 PM</em></td>
						</tr>
						<tr>
							<td><code>{order_status}</code></td>
							<td><?php esc_html_e( 'Current order status', 'smart-product-emails' ); ?></td>
							<td><em>Processing</em></td>
						</tr>
						<tr>
							<td><code>{payment_method}</code></td>
							<td><?php esc_html_e( 'How customer paid', 'smart-product-emails' ); ?></td>
							<td><em>Credit Card</em></td>
						</tr>
					</tbody>
				</table>
			</div>

			<!-- Customer Information -->
			<div class="spe-docs-category">
				<h3>
					<span class="dashicons dashicons-admin-users"></span>
					<?php esc_html_e( 'Customer Information', 'smart-product-emails' ); ?>
				</h3>
				<table class="widefat spe-docs-table">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Tag', 'smart-product-emails' ); ?></th>
							<th><?php esc_html_e( 'Description', 'smart-product-emails' ); ?></th>
							<th><?php esc_html_e( 'Example Output', 'smart-product-emails' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td><code>{customer_first_name}</code></td>
							<td><?php esc_html_e( 'Customer\'s first name', 'smart-product-emails' ); ?></td>
							<td><em>John</em></td>
						</tr>
						<tr>
							<td><code>{customer_last_name}</code></td>
							<td><?php esc_html_e( 'Customer\'s last name', 'smart-product-emails' ); ?></td>
							<td><em>Doe</em></td>
						</tr>
						<tr>
							<td><code>{customer_name}</code></td>
							<td><?php esc_html_e( 'Full name', 'smart-product-emails' ); ?></td>
							<td><em>John Doe</em></td>
						</tr>
						<tr>
							<td><code>{customer_email}</code></td>
							<td><?php esc_html_e( 'Customer\'s email address', 'smart-product-emails' ); ?></td>
							<td><em>john@example.com</em></td>
						</tr>
						<tr>
							<td><code>{customer_phone}</code></td>
							<td><?php esc_html_e( 'Customer\'s phone number', 'smart-product-emails' ); ?></td>
							<td><em>(555) 123-4567</em></td>
						</tr>
					</tbody>
				</table>
			</div>

			<!-- Billing Address -->
			<div class="spe-docs-category">
				<h3>
					<span class="dashicons dashicons-location"></span>
					<?php esc_html_e( 'Billing Address', 'smart-product-emails' ); ?>
				</h3>
				<table class="widefat spe-docs-table">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Tag', 'smart-product-emails' ); ?></th>
							<th><?php esc_html_e( 'Description', 'smart-product-emails' ); ?></th>
							<th><?php esc_html_e( 'Example Output', 'smart-product-emails' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td><code>{billing_address}</code></td>
							<td><?php esc_html_e( 'Complete formatted billing address', 'smart-product-emails' ); ?></td>
							<td><em>123 Main St, New York, NY 10001</em></td>
						</tr>
						<tr>
							<td><code>{billing_city}</code></td>
							<td><?php esc_html_e( 'Billing city', 'smart-product-emails' ); ?></td>
							<td><em>New York</em></td>
						</tr>
						<tr>
							<td><code>{billing_state}</code></td>
							<td><?php esc_html_e( 'Billing state/province', 'smart-product-emails' ); ?></td>
							<td><em>NY</em></td>
						</tr>
						<tr>
							<td><code>{billing_postcode}</code></td>
							<td><?php esc_html_e( 'Billing ZIP/postal code', 'smart-product-emails' ); ?></td>
							<td><em>10001</em></td>
						</tr>
						<tr>
							<td><code>{billing_country}</code></td>
							<td><?php esc_html_e( 'Billing country name', 'smart-product-emails' ); ?></td>
							<td><em>United States</em></td>
						</tr>
					</tbody>
				</table>
			</div>

			<!-- Shipping Address -->
			<div class="spe-docs-category">
				<h3>
					<span class="dashicons dashicons-car"></span>
					<?php esc_html_e( 'Shipping Address', 'smart-product-emails' ); ?>
				</h3>
				<table class="widefat spe-docs-table">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Tag', 'smart-product-emails' ); ?></th>
							<th><?php esc_html_e( 'Description', 'smart-product-emails' ); ?></th>
							<th><?php esc_html_e( 'Example Output', 'smart-product-emails' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td><code>{shipping_address}</code></td>
							<td><?php esc_html_e( 'Complete formatted shipping address', 'smart-product-emails' ); ?></td>
							<td><em>456 Oak Ave, Los Angeles, CA 90001</em></td>
						</tr>
						<tr>
							<td><code>{shipping_city}</code></td>
							<td><?php esc_html_e( 'Shipping city', 'smart-product-emails' ); ?></td>
							<td><em>Los Angeles</em></td>
						</tr>
						<tr>
							<td><code>{shipping_state}</code></td>
							<td><?php esc_html_e( 'Shipping state/province', 'smart-product-emails' ); ?></td>
							<td><em>CA</em></td>
						</tr>
						<tr>
							<td><code>{shipping_postcode}</code></td>
							<td><?php esc_html_e( 'Shipping ZIP/postal code', 'smart-product-emails' ); ?></td>
							<td><em>90001</em></td>
						</tr>
						<tr>
							<td><code>{shipping_country}</code></td>
							<td><?php esc_html_e( 'Shipping country name', 'smart-product-emails' ); ?></td>
							<td><em>United States</em></td>
						</tr>
					</tbody>
				</table>
			</div>

			<!-- Order Totals -->
			<div class="spe-docs-category">
				<h3>
					<span class="dashicons dashicons-money-alt"></span>
					<?php esc_html_e( 'Order Totals', 'smart-product-emails' ); ?>
				</h3>
				<p class="spe-docs-note"><?php esc_html_e( 'All monetary values are automatically formatted with the order\'s currency symbol.', 'smart-product-emails' ); ?></p>
				<table class="widefat spe-docs-table">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Tag', 'smart-product-emails' ); ?></th>
							<th><?php esc_html_e( 'Description', 'smart-product-emails' ); ?></th>
							<th><?php esc_html_e( 'Example Output', 'smart-product-emails' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td><code>{order_subtotal}</code></td>
							<td><?php esc_html_e( 'Order subtotal (before tax/shipping)', 'smart-product-emails' ); ?></td>
							<td><em>$85.00</em></td>
						</tr>
						<tr>
							<td><code>{order_total}</code></td>
							<td><?php esc_html_e( 'Final order total', 'smart-product-emails' ); ?></td>
							<td><em>$99.99</em></td>
						</tr>
						<tr>
							<td><code>{order_tax}</code></td>
							<td><?php esc_html_e( 'Total tax amount', 'smart-product-emails' ); ?></td>
							<td><em>$7.50</em></td>
						</tr>
						<tr>
							<td><code>{order_shipping}</code></td>
							<td><?php esc_html_e( 'Shipping cost', 'smart-product-emails' ); ?></td>
							<td><em>$7.49</em></td>
						</tr>
						<tr>
							<td><code>{order_discount}</code></td>
							<td><?php esc_html_e( 'Total discount amount', 'smart-product-emails' ); ?></td>
							<td><em>$10.00</em></td>
						</tr>
					</tbody>
				</table>
			</div>

			<!-- Product Information -->
			<div class="spe-docs-category">
				<h3>
					<span class="dashicons dashicons-products"></span>
					<?php esc_html_e( 'Product Information', 'smart-product-emails' ); ?>
				</h3>
				<p class="spe-docs-note"><?php esc_html_e( 'Product tags display information about the specific product that triggered the SPE Message. Price values are automatically formatted with currency.', 'smart-product-emails' ); ?></p>
				<table class="widefat spe-docs-table">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Tag', 'smart-product-emails' ); ?></th>
							<th><?php esc_html_e( 'Description', 'smart-product-emails' ); ?></th>
							<th><?php esc_html_e( 'Example Output', 'smart-product-emails' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td><code>{product_id}</code></td>
							<td><?php esc_html_e( 'Product ID number', 'smart-product-emails' ); ?></td>
							<td><em>1234</em></td>
						</tr>
						<tr>
							<td><code>{product_name}</code></td>
							<td><?php esc_html_e( 'Product name/title', 'smart-product-emails' ); ?></td>
							<td><em>Premium Wireless Headphones</em></td>
						</tr>
						<tr>
							<td><code>{product_sku}</code></td>
							<td><?php esc_html_e( 'Product SKU code', 'smart-product-emails' ); ?></td>
							<td><em>WH-2000</em></td>
						</tr>
						<tr>
							<td><code>{product_url}</code></td>
							<td><?php esc_html_e( 'Product page URL', 'smart-product-emails' ); ?></td>
							<td><em>https://mystore.com/product/headphones</em></td>
						</tr>
						<tr>
							<td><code>{product_price}</code></td>
							<td><?php esc_html_e( 'Current product price', 'smart-product-emails' ); ?></td>
							<td><em>$79.99</em></td>
						</tr>
						<tr>
							<td><code>{product_regular_price}</code></td>
							<td><?php esc_html_e( 'Regular (non-sale) price', 'smart-product-emails' ); ?></td>
							<td><em>$99.99</em></td>
						</tr>
						<tr>
							<td><code>{product_sale_price}</code></td>
							<td><?php esc_html_e( 'Sale price (if on sale)', 'smart-product-emails' ); ?></td>
							<td><em>$79.99</em></td>
						</tr>
						<tr>
							<td><code>{product_short_description}</code></td>
							<td><?php esc_html_e( 'Product short description', 'smart-product-emails' ); ?></td>
							<td><em>Premium sound quality...</em></td>
						</tr>
						<tr>
							<td><code>{product_description}</code></td>
							<td><?php esc_html_e( 'Full product description', 'smart-product-emails' ); ?></td>
							<td><em>Experience crystal-clear audio...</em></td>
						</tr>
						<tr>
							<td><code>{product_categories}</code></td>
							<td><?php esc_html_e( 'Product categories (comma-separated)', 'smart-product-emails' ); ?></td>
							<td><em>Electronics, Audio, Headphones</em></td>
						</tr>
						<tr>
							<td><code>{product_tags}</code></td>
							<td><?php esc_html_e( 'Product tags (comma-separated)', 'smart-product-emails' ); ?></td>
							<td><em>wireless, bluetooth, premium</em></td>
						</tr>
					</tbody>
				</table>
			</div>

			<!-- Usage Examples -->
			<div class="spe-docs-category spe-docs-examples">
				<h3>
					<span class="dashicons dashicons-editor-code"></span>
					<?php esc_html_e( 'Usage Examples', 'smart-product-emails' ); ?>
				</h3>

				<div class="spe-example-box">
					<h4><?php esc_html_e( 'Personalized Greeting', 'smart-product-emails' ); ?></h4>
					<div class="spe-example-code">
						<code>Hi {customer_first_name},

Thank you for your order #{order_number}!
Your order total of {order_total} will be processed shortly.

Best regards,
{site_title}</code>
					</div>
				</div>

				<div class="spe-example-box">
					<h4><?php esc_html_e( 'Product-Specific Message', 'smart-product-emails' ); ?></h4>
					<div class="spe-example-code">
						<code>Thank you for purchasing {product_name}!

Here are some helpful resources to get started:
- Setup Guide: https://example.com/setup
- Video Tutorial: https://example.com/video

Need help? Reply to this email or contact us at {store_email}</code>
					</div>
				</div>

				<div class="spe-example-box">
					<h4><?php esc_html_e( 'Shipping Information', 'smart-product-emails' ); ?></h4>
					<div class="spe-example-code">
						<code>Your order will be shipped to:

{shipping_address}

Expected delivery: 3-5 business days after processing.</code>
					</div>
				</div>
			</div>

			<!-- Tips & Best Practices -->
			<div class="spe-docs-category spe-docs-tips">
				<h3>
					<span class="dashicons dashicons-info"></span>
					<?php esc_html_e( 'Tips & Best Practices', 'smart-product-emails' ); ?>
				</h3>
				<div class="spe-tips-grid">
					<div class="spe-tip-do">
						<h4><?php esc_html_e( 'Do', 'smart-product-emails' ); ?></h4>
						<ul>
							<li><?php esc_html_e( 'Use customer first names for a personal touch', 'smart-product-emails' ); ?></li>
							<li><?php esc_html_e( 'Include order numbers for easy reference', 'smart-product-emails' ); ?></li>
							<li><?php esc_html_e( 'Test with real orders before going live', 'smart-product-emails' ); ?></li>
							<li><?php esc_html_e( 'Combine multiple tags for rich, dynamic content', 'smart-product-emails' ); ?></li>
						</ul>
					</div>
					<div class="spe-tip-dont">
						<h4><?php esc_html_e( 'Don\'t', 'smart-product-emails' ); ?></h4>
						<ul>
							<li><?php esc_html_e( 'Rely solely on tags - add context around them', 'smart-product-emails' ); ?></li>
							<li><?php esc_html_e( 'Assume all data will always be present (some fields may be empty)', 'smart-product-emails' ); ?></li>
							<li><?php esc_html_e( 'Use tags in product descriptions (they only work in SPE Messages)', 'smart-product-emails' ); ?></li>
						</ul>
					</div>
				</div>
			</div>

		</div>

		<style>
		.spe-placeholders-docs {
			max-width: 900px;
			margin-top: 20px;
		}

		.spe-docs-intro {
			background: #fff;
			padding: 20px;
			border: 1px solid #c3c4c7;
			border-radius: 4px;
			margin-bottom: 20px;
		}

		.spe-docs-intro h2 {
			margin-top: 0;
			color: #1d2327;
		}

		.spe-docs-tip {
			display: flex;
			align-items: flex-start;
			gap: 10px;
			background: #f0f6fc;
			border-left: 4px solid #2271b1;
			padding: 12px 16px;
			margin-top: 15px;
			border-radius: 0 4px 4px 0;
		}

		.spe-docs-tip .dashicons {
			color: #2271b1;
			margin-top: 2px;
		}

		.spe-docs-tip p {
			margin: 0;
			color: #1d2327;
		}

		.spe-docs-category {
			background: #fff;
			border: 1px solid #c3c4c7;
			border-radius: 4px;
			margin-bottom: 20px;
			overflow: hidden;
		}

		.spe-docs-category h3 {
			display: flex;
			align-items: center;
			gap: 8px;
			margin: 0;
			padding: 15px 20px;
			background: #f6f7f7;
			border-bottom: 1px solid #c3c4c7;
			font-size: 14px;
		}

		.spe-docs-category h3 .dashicons {
			color: #9459DC;
		}

		.spe-docs-note {
			margin: 0;
			padding: 10px 20px;
			background: #fffbeb;
			border-bottom: 1px solid #f0e6c8;
			font-size: 13px;
			color: #6b5900;
		}

		.spe-docs-table {
			border: none;
			border-radius: 0;
			box-shadow: none;
		}

		.spe-docs-table thead th {
			background: #f9f9f9;
			font-weight: 600;
			padding: 12px 20px;
		}

		.spe-docs-table tbody td {
			padding: 10px 20px;
			vertical-align: middle;
		}

		.spe-docs-table code {
			background: #f0e8fa;
			color: #9459DC;
			padding: 4px 8px;
			border-radius: 3px;
			font-size: 12px;
			font-weight: 500;
		}

		.spe-docs-table em {
			color: #646970;
			font-style: normal;
		}

		/* Examples Section */
		.spe-docs-examples {
			padding: 20px;
		}

		.spe-docs-examples h3 {
			border-bottom: none;
			padding: 0 0 15px 0;
			background: transparent;
		}

		.spe-example-box {
			background: #f9f9f9;
			border: 1px solid #e0e0e0;
			border-radius: 4px;
			margin-bottom: 15px;
			overflow: hidden;
		}

		.spe-example-box:last-child {
			margin-bottom: 0;
		}

		.spe-example-box h4 {
			margin: 0;
			padding: 10px 15px;
			background: #f0f0f1;
			border-bottom: 1px solid #e0e0e0;
			font-size: 13px;
			font-weight: 600;
		}

		.spe-example-code {
			padding: 15px;
		}

		.spe-example-code code {
			display: block;
			background: #fff;
			border: 1px solid #e0e0e0;
			padding: 15px;
			border-radius: 4px;
			font-size: 12px;
			line-height: 1.6;
			white-space: pre-wrap;
			color: #1d2327;
		}

		/* Tips Section */
		.spe-docs-tips {
			padding: 20px;
		}

		.spe-docs-tips h3 {
			border-bottom: none;
			padding: 0 0 15px 0;
			background: transparent;
		}

		.spe-tips-grid {
			display: grid;
			grid-template-columns: 1fr 1fr;
			gap: 20px;
		}

		.spe-tip-do,
		.spe-tip-dont {
			padding: 15px;
			border-radius: 4px;
		}

		.spe-tip-do {
			background: #edfaef;
			border: 1px solid #46b450;
		}

		.spe-tip-do h4 {
			color: #1e7e34;
			margin: 0 0 10px 0;
		}

		.spe-tip-dont {
			background: #fef1f1;
			border: 1px solid #dc3232;
		}

		.spe-tip-dont h4 {
			color: #a00;
			margin: 0 0 10px 0;
		}

		.spe-tip-do ul,
		.spe-tip-dont ul {
			margin: 0;
			padding-left: 20px;
		}

		.spe-tip-do li,
		.spe-tip-dont li {
			margin-bottom: 6px;
			font-size: 13px;
		}

		@media screen and (max-width: 782px) {
			.spe-tips-grid {
				grid-template-columns: 1fr;
			}
		}
		</style>
		<?php
	}

	public function spe_settings_content_separator_livepreview() {
		?>
		<div id="spe_separator_preview" style="padding: 20px; background: #f9f9f9; border: 1px solid #ddd; border-radius: 4px;">
			<p style="margin: 0 0 10px 0; color: #666; font-size: 13px;">
				<?php esc_html_e('Order content appears here...', 'smart-product-emails'); ?>
			</p>

			<div id="spe_separator_preview_top"></div>

			<div style="background: #fffbcc; border-left: 4px solid #ff9800; padding: 15px; margin: 10px 0;">
				<strong style="display: block; margin-bottom: 5px; color: #e65100;">
					<?php esc_html_e('Your Custom Email Content', 'smart-product-emails'); ?>
				</strong>
				<p style="margin: 0; color: #666; font-size: 13px;">
					<?php esc_html_e('This is where your custom product-specific content will appear in the customer\'s order email.', 'smart-product-emails'); ?>
				</p>
			</div>

			<div id="spe_separator_preview_bottom"></div>

			<p style="margin: 10px 0 0 0; color: #666; font-size: 13px;">
				<?php esc_html_e('More order content continues below...', 'smart-product-emails'); ?>
			</p>
		</div>
		<p class="description" style="margin-top: 10px;">
			<?php esc_html_e('This preview shows how the separator will appear in actual customer emails.', 'smart-product-emails'); ?>
		</p>
		<?php
	}

	/**
	 * Adds jQuery and CSS.
	 */
	public function spe_settings_scriptsandstyles() {

		?>
		<script>
        jQuery(document).ready(function ($) {
        	// Initialize WordPress color picker
        	if ($.fn.wpColorPicker) {
        		$('.spe-color-picker').wpColorPicker({
        			change() {
        				updateSeparatorPreview();
        			},
        			clear() {
        				updateSeparatorPreview();
        			},
        		});
        	}

        	// Update thickness value display
        	$('#spe_separator_thickness').on('input', function () {
        		$('#spe_thickness_value').text($(this).val() + 'px');
        		updateSeparatorPreview();
        	});

        	// Update spacing value display
        	$('#spe_separator_spacing').on('input', function () {
        		$('#spe_spacing_value').text($(this).val() + 'px');
        		updateSeparatorPreview();
        	});

        	// Update preview when separator type changes
        	$('#spe_content_separator').on('change', function () {
        		const separatorType = $(this).val();

        		// Show/hide relevant options
        		if (
        			separatorType === 'line' ||
        			separatorType === 'dots' ||
        			separatorType === 'dashes' ||
        			separatorType === 'double'
        		) {
        			$('.spe_separator_color_row').show();
        			$('.spe_separator_thickness_row').show();
        		} else {
        			$('.spe_separator_color_row').hide();
        			$('.spe_separator_thickness_row').hide();
        		}

        		if (
        			separatorType === 'line' ||
        			separatorType === 'dots' ||
        			separatorType === 'dashes' ||
        			separatorType === 'double' ||
        			separatorType === 'space'
        		) {
        			$('.spe_separator_spacing_row').show();
        		}

        		if (separatorType === 'custom') {
        			$('.spe_separator_customhtml_row').show();
        			$('.spe_separator_spacing_row').hide();
        		} else {
        			$('.spe_separator_customhtml_row').hide();
        		}

        		if (separatorType === 'none') {
        			$('.spe_separator_spacing_row').hide();
        		}

        		updateSeparatorPreview();
        	});

        	// Update preview when custom HTML changes
        	$('#spe_separator_customhtml').on('input', function () {
        		if ($('#spe_content_separator').val() === 'custom') {
        			updateSeparatorPreview();
        		}
        	});

        	/**
        	 * Update the live preview based on current settings
        	 */
        	function updateSeparatorPreview() {
        		const separatorType = $('#spe_content_separator').val();
        		const color = $('#spe_separator_color').val() || '#dddddd';
        		const thickness = $('#spe_separator_thickness').val() || '1';
        		const spacing = $('#spe_separator_spacing').val() || '20';
        		const customHTML = $('#spe_separator_customhtml').val();

        		let html = '';

        		switch (separatorType) {
        			case 'none':
        				html = '';
        				break;

        			case 'line':
        				html =
        					'<hr style="border: none; border-top: ' +
        					thickness +
        					'px solid ' +
        					color +
        					'; margin: ' +
        					spacing +
        					'px 0;" />';
        				break;

        			case 'dots':
        				html =
        					'<hr style="border: none; border-top: ' +
        					thickness +
        					'px dotted ' +
        					color +
        					'; margin: ' +
        					spacing +
        					'px 0;" />';
        				break;

        			case 'dashes':
        				html =
        					'<hr style="border: none; border-top: ' +
        					thickness +
        					'px dashed ' +
        					color +
        					'; margin: ' +
        					spacing +
        					'px 0;" />';
        				break;

        			case 'double':
        				html =
        					'<hr style="border: none; border-top: ' +
        					thickness +
        					'px double ' +
        					color +
        					'; margin: ' +
        					spacing +
        					'px 0;" />';
        				break;

        			case 'space':
        				html = '<div style="height: ' + spacing + 'px;"></div>';
        				break;

        			case 'custom':
        				html =
        					customHTML ||
        					'<div style="border-top: 2px solid #ff9800; margin: 20px 0;"></div>';
        				break;
        		}

        		// Show/hide relevant options
        		if (
        			separatorType === 'line' ||
        			separatorType === 'dots' ||
        			separatorType === 'dashes' ||
        			separatorType === 'double'
        		) {
        			$('.spe_separator_color_row').show();
        			$('.spe_separator_thickness_row').show();
        		} else {
        			$('.spe_separator_color_row').hide();
        			$('.spe_separator_thickness_row').hide();
        		}

        		if (separatorType === 'custom') {
        			$('.spe_separator_spacing_row').hide();
        			$('.spe_separator_customhtml_row').show();
        		} else {
        			$('.spe_separator_customhtml_row').hide();
        		}

        		if (separatorType === 'none') {
        			$('.spe_separator_spacing_row').hide();
        		}

        		$('#spe_separator_preview_top').html(html);
        		$('#spe_separator_preview_bottom').html(html);
        	}

        	// Initial preview update
        	updateSeparatorPreview();
        });
        </script>

        <style>
        #spe_separator_preview {
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        #spe_separator_preview hr {
            height: 0;
        }

        .spe-color-picker {
            max-width: 100px;
        }

        input[type="range"] {
            vertical-align: middle;
        }

        #spe_thickness_value,
        #spe_spacing_value {
            display: inline-block;
            min-width: 40px;
            font-weight: bold;
            color: #2271b1;
        }

        details summary {
            font-size: 13px;
        }

        details code {
            display: block;
            padding: 8px;
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 3px;
            font-size: 12px;
            overflow-x: auto;
        }
        </style>
		<?php

	}

}

if ( is_admin() ) {
	$smartproductemails_settings = new Smart_Product_Emails_For_WooCommerce_Admin_Settings();
}
