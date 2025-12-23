<?php
/**
 * Adds the Admin Settings for the plugin.
 *
 * @package SmartProductEmails
 */

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
		if ( ! $screen || strpos( $screen->id, 'smartproductemails_page_spe-settings' ) === false ) {
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
			'spe-settings', // menu slug.
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

			<p class="howto"><?php echo esc_html( 'Settings for the Smart Product Emails plugin.', 'smart-product-emails' ); ?></p>

			<?php settings_errors(); ?>

			<?php
			// Nonce check for tab parameter (only if tab is set in URL)
			$nonce_verified = false;
			if (isset($_GET['_wpnonce']) && wp_verify_nonce(sanitize_key($_GET['_wpnonce']), 'spe_admin_nonce')) {
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
				$page = 'spe-settings';
			}

			// Update the tab links to include nonces:
			$nonce_url = wp_create_nonce('spe_admin_nonce');
			?>
			<h2 class="nav-tab-wrapper">
				<a href="?post_type=smartproductemails&page=<?php echo esc_attr( $page ); ?>&tab=display_settings&_wpnonce=<?php echo esc_attr($nonce_url); ?>" class="nav-tab <?php echo 'display_settings' === $active_tab ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Display Settings', 'smart-product-emails' ); ?></a>
			</h2>

			<form method="post" action="options.php">
				<?php
				if ( 'display_settings' === $active_tab ) {
					settings_fields( 'spe_settings_option_group' );
					do_settings_sections( 'spe-settings-admin' );
					submit_button();
				}
				?>
			</form>
		</div>
		<?php

		$this->spe_settings_scriptsandstyles();
	}

	/**
	 * Adds the 'Display Settings' section to the plugin settings page.
	 */
	public function spe_settings_page_init() {
		register_setting(
			'spe_settings_option_group', // option_group.
			'SmartProductEmails_settings_name', // option_name.
			array( $this, 'spe_settings_sanitize' ) // sanitize_callback.
		);

		add_settings_section(
			'spe_settings_content_separator_section', // id.
			__('Content Separator', 'smart-product-emails'), // title.
			array( $this, 'spe_settings_section_info' ), // callback.
			'spe-settings-admin' // page.
		);

		// Add settings field: Separator Styles
		add_settings_field(
			'spe_content_separator_style_field', // Field ID
			__('Separator Style', 'smart-product-emails'), // Field title
			array( $this, 'spe_settings_content_separator_style'), // Callback function
			'spe-settings-admin', // Page slug
			'spe_settings_content_separator_section' // Section ID
		);

		// Add settings field: Separator Color
		add_settings_field(
			'spe_content_separator_color_field', // Field ID
			__('Separator Color', 'smart-product-emails'), // Field title
			array( $this, 'spe_settings_content_separator_color'), // Callback function
			'spe-settings-admin', // Page slug
			'spe_settings_content_separator_section', // Section ID
			array(
				'label_for' => 'spe_content_separator_color_field', // Associates the label with the input field
				'class'     => 'spe_separator_color_row', // CSS class to be added to the <tr> element
			)
		);

		// Add settings field: Separator Thickness
		add_settings_field(
			'spe_content_separator_thickness_field', // Field ID
			__('Line Thickness', 'smart-product-emails'), // Field title
			array( $this, 'spe_settings_content_separator_thickness'), // Callback function
			'spe-settings-admin', // Page slug
			'spe_settings_content_separator_section', // Section ID
			array(
				'label_for' => 'spe_content_separator_thickness_field', // Associates the label with the input field
				'class'     => 'spe_separator_thickness_row', // CSS class to be added to the <tr> element
			)
		);

		// Add settings field: Separator Spacing
		add_settings_field(
			'spe_content_separator_spacing_field', // Field ID
			__('Spacing', 'smart-product-emails'), // Field title
			array( $this, 'spe_settings_content_separator_spacing'), // Callback function
			'spe-settings-admin', // Page slug
			'spe_settings_content_separator_section', // Section ID
			array(
				'label_for' => 'spe_content_separator_spacing_field', // Associates the label with the input field
				'class'     => 'spe_separator_spacing_row', // CSS class to be added to the <tr> element
			)
		);

		// Add settings field: Custom HTML
		add_settings_field(
			'spe_content_separator_customhtml_field', // Field ID
			__('Custom Separator HTML', 'smart-product-emails'), // Field title
			array( $this, 'spe_settings_content_separator_customhtml'), // Callback function
			'spe-settings-admin', // Page slug
			'spe_settings_content_separator_section', // Section ID
			array(
				'label_for' => 'spe_content_separator_customhtml_field', // Associates the label with the input field
				'class'     => 'spe_separator_customhtml_row', // CSS class to be added to the <tr> element
			)
		);

		// Add settings field: Live Preview
		add_settings_field(
			'spe_content_separator_livepreview', // Field ID
			__('Live Preview', 'smart-product-emails'), // Field title
			array( $this, 'spe_settings_content_separator_livepreview'), // Callback function
			'spe-settings-admin', // Page slug
			'spe_settings_content_separator_section' // Section ID
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
