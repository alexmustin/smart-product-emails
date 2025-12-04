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
	 * @var object $spe_settings_options Object to track the settings for the plugin.
	 */
	private $spe_settings_options;

	/**
	 * Setup the plugin settings object.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( &$this, 'spe_settings_add_plugin_page' ) );
		add_action( 'admin_init', array( &$this, 'spe_settings_page_init' ) );
		add_action( 'admin_enqueue_scripts', array( &$this, 'spe_enqueue_separator_admin_scripts' ) );
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
	 * Adds the plugin settings page to the menu.
	 */
	public function spe_settings_add_plugin_page() {
		add_submenu_page(
			'edit.php?post_type=smartproductemails', // parent slug.
			__( 'SPE Settings', 'smart_product_emails_domain' ), // page title.
			__( 'SPE Settings', 'smart_product_emails_domain' ), // menu title.
			'manage_options', // capability.
			'spe-settings', // menu slug.
			array( $this, 'spe_settings_create_admin_page' ) // callback function.
		);
	}

	/**
	 * Adds the plugin Admin settings page to the menu.
	 */
	public function spe_settings_create_admin_page() {
		$this->spe_settings_options = get_option( 'SmartProductEmails_settings_name' );
		?>

		<div class="wrap">
			<h2>Smart Product Emails Settings</h2>

			<hr>

			<p class="howto"><?php echo esc_html( 'Settings for the Smart Product Emails plugin.', 'smart_product_emails_domain' ); ?></p>

			<?php settings_errors(); ?>

			<?php
			// if ( isset( $_GET['tab'] ) && wp_verify_nonce( sanitize_key( $_GET['tab'] ) ) ) {
			// 	$active_tab = sanitize_text_field( wp_unslash( $_GET['tab'] ) );
			// } else {
			// 	$active_tab = 'display_settings';
			// }

			// Nonce check.
			$nonce_verified = false;
			if (isset($_GET['_wpnonce']) && wp_verify_nonce(sanitize_key($_GET['_wpnonce']), 'spe_admin_nonce')) {
				$nonce_verified = true;
			}

			if (isset($_GET['tab']) && $nonce_verified) {
				$active_tab = sanitize_text_field(wp_unslash($_GET['tab']));
			} else {
				$active_tab = 'display_settings';
			}
			?>

			<?php
			if ( isset( $_GET['page'] ) && wp_verify_nonce( sanitize_key( $_GET['page'], 'spe_admin_nonce' ) ) ) {
				$page = sanitize_text_field( wp_unslash( $_GET['page'] ) );
			} else {
				$page = 'spe-settings';
			}

			// Update the tab links to include nonces:
			$nonce_url = wp_create_nonce('spe_admin_nonce');
			?>
			<h2 class="nav-tab-wrapper">
				<a href="?post_type=smartproductemails&page=<?php echo esc_attr( $page ); ?>&tab=display_settings&_wpnonce=<?php echo esc_attr($nonce_url); ?>" class="nav-tab <?php echo 'display_settings' === $active_tab ? 'nav-tab-active' : ''; ?>">Display Settings</a>
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

		// add_settings_section(
		// 	'spe_settings_display_settings_section', // id.
		// 	__('Display Settings', 'smart_product_emails_domain'), // title.
		// 	array( $this, 'spe_settings_section_info' ), // callback.
		// 	'spe-settings-admin' // page.
		// );

		add_settings_section(
			'spe_settings_content_separator_section', // id.
			__('Content Separator', 'smart_product_emails_domain'), // title.
			array( $this, 'spe_settings_section_info' ), // callback.
			'spe-settings-admin' // page.
		);

		// Add settings field: Separator Styles
		add_settings_field(
			'spe_content_separator_style_field', // Field ID
			__('Separator Style', 'smart_product_emails_domain'), // Field title
			array( $this, 'spe_settings_content_separator_style'), // Callback function
			'spe-settings-admin', // Page slug
			'spe_settings_content_separator_section' // Section ID
		);

		// Add settings field: Separator Color
		add_settings_field(
			'spe_content_separator_color_field', // Field ID
			__('Separator Color', 'smart_product_emails_domain'), // Field title
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
			__('Line Thickness', 'smart_product_emails_domain'), // Field title
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
			__('Spacing', 'smart_product_emails_domain'), // Field title
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
			__('Custom Separator HTML', 'smart_product_emails_domain'), // Field title
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
			__('Live Preview', 'smart_product_emails_domain'), // Field title
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
		
		// if ( isset( $input['show_in_admin_email'] ) ) {
		// 	$sanitary_values['show_in_admin_email'] = $input['show_in_admin_email'];
		// }

		// if ( isset( $input['display_classes'] ) ) {
		// 	$sanitary_values['display_classes'] = sanitize_text_field( $input['display_classes'] );
		// }

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
		esc_html_e('Customize how your custom email content is visually separated from the rest of the order email.', 'smart_product_emails_domain');
	}

	// /**
	//  * Adds a checkbox field for the setting: Show messages in Admin emails.
	//  */
	// public function show_in_admin_email_callback() {
	// 	printf(
	// 		'<input type="checkbox" name="SmartProductEmails_settings_name[show_in_admin_email]" id="show_in_admin_email" value="show_in_admin_email" %s> <label for="show_in_admin_email">' . __( 'Show the Smart Product Email content inside Admin order notification emails.', 'smart_product_emails_domain' ) . '</label>',
	// 		( isset( $this->spe_settings_options['show_in_admin_email'] ) && 'show_in_admin_email' === $this->spe_settings_options['show_in_admin_email'] ) ? 'checked' : ''
	// 	);
	// }

	// /**
	//  * Adds a textarea field for the setting: Display classes.
	//  */
	// public function display_classes_callback() {
	// 	printf(
	// 		'<textarea class="large-text" rows="5" name="SmartProductEmails_settings_name[display_classes]" id="display_classes">%s</textarea>',
	// 		isset( $this->spe_settings_options['display_classes'] ) ? esc_attr( $this->spe_settings_options['display_classes'] ) : ''
	// 	);
	// 	// Description.
	// 	echo '<div class="description" style="margin-top: 20px;">' . esc_html__( 'By default, WooCommerce only shows the "Smart Product Emails" tab for the standard "', 'smart_product_emails_domain' ) . '<b>product</b>' . esc_html__( '" post type.', 'smart_product_emails_domain' );
	// 	echo '<br>' . esc_html__( 'Use this option to force other post types to show the Smart Product Emails tab. This should be a comma-separated list.', 'smart_product_emails_domain' );
	// 	echo '</div>';

	// 	// Code example.
	// 	echo '<div class="description" style="margin-top: 20px; margin-left: 30px;"><b>Example:</b><pre>show_if_booking, show_if_grouped</pre></div>';

	// 	echo '<hr />';

	// 	// Instructions to find CSS Class.
	// 	echo '<div class="description" style="margin-top: 20px;"><h3>' . esc_html__( 'How to Find the CSS Class for a Custom Product', 'smart_product_emails_domain' ) . '</h3>' . esc_html__( 'To find the class for your product type, do the following:', 'smart_product_emails_domain' );
	// 	echo '<ol>';
	// 	echo '<li>' . esc_html__( 'Go to ', 'smart_product_emails_domain' ) . '<b>' . esc_html__( 'Products', 'smart_product_emails_domain' ) . '</b>' . esc_html__( ', and Edit a custom product', 'smart_product_emails_domain' ) . '</li>';
	// 	echo '<li>' . esc_html__( 'Scroll down to the ', 'smart_product_emails_domain' ) . '<b>' . esc_html__( 'Product Data', 'smart_product_emails_domain' ) . '</b>' . esc_html__( ' table', 'smart_product_emails_domain' ) . '</li>';
	// 	echo '<li><b>' . esc_html__( 'Inspect the code', 'smart_product_emails_domain' ) . '</b>' . esc_html__( ' for a tab item, like ', 'smart_product_emails_domain' ) . '<b>' . esc_html__( 'Inventory', 'smart_product_emails_domain' ) . '</b>' . esc_html__( ' (or any menu item which appears only for your product type)', 'smart_product_emails_domain' ) . '</li>';
	// 	echo '<li>' . esc_html__( 'Find the custom CSS class for your product type which is assigned to that tab menu item. These classes usually start with ', 'smart_product_emails_domain' ) . '<b>' . esc_html__( 'show_if_', 'smart_product_emails_domain' ) . '</b>' . esc_html__( ' -- ex: "show_if_booking"', 'smart_product_emails_domain' ) . '</li>';
	// 	echo '<li>' . esc_html__( 'Copy and paste that CSS class into the field above', 'smart_product_emails_domain' ) . '</li>';
	// 	echo '<li>' . esc_html__( 'Save your settings', 'smart_product_emails_domain' ) . '</li>';
	// 	echo '</ol>';
	// 	echo '</div>';

	// 	// Note text.
	// 	echo '<div style="margin-top: 20px; padding: 20px; border: 1px solid rgba(0,0,0,0.15); border-radius: 4px; background: rgba(0,0,0,0.025);"><b style="color: #ff0000;">' . esc_html( 'Important: This feature is still experimental and may not work in all cases.' ) . '</b><br>' . esc_html( 'Some WooCommerce extensions send emails in their own way, separate from the standard WooCommerce email system that this plugin uses.' ) . '</div>';
	// }

	/**
	 * Adds setting field: Separator Style
	 */
	public function spe_settings_content_separator_style() {

		// Separator settings with defaults
        $content_separator = isset( $this->spe_settings_options['content_separator'] ) ? $this->spe_settings_options['content_separator'] : 'none';

		?>
		<select name="SmartProductEmails_settings_name[content_separator]" id="spe_content_separator" class="regular-text">

			<option value="none" <?php selected($content_separator, 'none'); ?>>
				<?php esc_html_e('None - No separator', 'smart_product_emails_domain'); ?>
			</option>
			<option value="line" <?php selected($content_separator, 'line'); ?>>
				<?php esc_html_e('Solid Line', 'smart_product_emails_domain'); ?>
			</option>
			<option value="dots" <?php selected($content_separator, 'dots'); ?>>
				<?php esc_html_e('Dotted Line', 'smart_product_emails_domain'); ?>
			</option>
			<option value="dashes" <?php selected($content_separator, 'dashes'); ?>>
				<?php esc_html_e('Dashed Line', 'smart_product_emails_domain'); ?>
			</option>
			<option value="double" <?php selected($content_separator, 'double'); ?>>
				<?php esc_html_e('Double Line', 'smart_product_emails_domain'); ?>
			</option>
			<option value="space" <?php selected($content_separator, 'space'); ?>>
				<?php esc_html_e('Extra Space Only', 'smart_product_emails_domain'); ?>
			</option>
			<option value="custom" <?php selected($content_separator, 'custom'); ?>>
				<?php esc_html_e('Custom HTML', 'smart_product_emails_domain'); ?>
			</option>

		</select>

		<?php
		echo '<p>';
		esc_html_e('Choose a visual style to separate your custom content from order details.', 'smart_product_emails_domain');
		echo '</p>';

	}

	/**
	 * Adds setting field: Separator Color
	 */
	public function spe_settings_content_separator_color() {
		$separator_color = isset( $this->spe_settings_options['separator_color'] ) ? $this->spe_settings_options['separator_color'] : '#dddddd';

		?>
		<!-- Separator Color (for line styles) -->
		<input type="text" 
			name="SmartProductEmails_settings_name[separator_color]" 
			id="spe_separator_color" 
			value="<?php echo esc_attr($separator_color); ?>" 
			class="spe-color-picker" 
			data-default-color="#dddddd" />

			<p class="description">
				<?php esc_html_e('Choose the color for the separator line.', 'smart_product_emails_domain'); ?>
			</p>
		<?php
	}

	/**
	 * Adds setting field: Separator Thickness
	 */
	public function spe_settings_content_separator_thickness() {
		$separator_thickness = isset( $this->spe_settings_options['separator_thickness'] ) ? $this->spe_settings_options['separator_thickness'] : '1';
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
			<?php esc_html_e('Adjust the thickness of the separator line (1-5 pixels).', 'smart_product_emails_domain'); ?>
		</p>
		<?php
	}

	/**
	 * Adds setting field: Separator Spacing
	 */
	public function spe_settings_content_separator_spacing() {
		$separator_spacing = isset( $this->spe_settings_options['separator_spacing'] ) ? $this->spe_settings_options['separator_spacing'] : '20';
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
			<?php esc_html_e('Space above and below the separator (10-50 pixels).', 'smart_product_emails_domain'); ?>
		</p>
		<?php
	}

	/**
	 * Adds setting field: Separator Custom HTML
	 */
	public function spe_settings_content_separator_customhtml() {
		$custom_separator_html = isset( $this->spe_settings_options['separator_customhtml'] ) ? $this->spe_settings_options['separator_customhtml'] : '';
		?>
		<textarea name="SmartProductEmails_settings_name[separator_customhtml]" 
			id="spe_separator_customhtml"
			rows="5" 
			class="large-text code"
			placeholder='<div style="border-top: 2px solid #ff9800; margin: 20px 0;"></div>'><?php echo nl2br( esc_html($custom_separator_html)); ?></textarea>
		<p class="description">
			<?php esc_html_e('Enter custom HTML for your separator. Must use inline CSS styles for email compatibility.', 'smart_product_emails_domain'); ?>
		</p>
		<details style="margin-top: 10px;">
			<summary style="cursor: pointer; color: #2271b1;"><?php esc_html_e('Show Examples', 'smart_product_emails_domain'); ?></summary>
			<div style="margin-top: 10px; padding: 10px; background: #f9f9f9; border-left: 3px solid #2271b1;">
				<p><strong><?php esc_html_e('Brand-colored line:', 'smart_product_emails_domain'); ?></strong></p>
				<code>&lt;hr style="border: none; border-top: 3px solid #ff6900; margin: 20px 0;" /&gt;</code>
				
				<p style="margin-top: 15px;"><strong><?php esc_html_e('Decorative stars:', 'smart_product_emails_domain'); ?></strong></p>
				<code>&lt;div style="text-align: center; color: #999; margin: 20px 0;"&gt;★ ★ ★&lt;/div&gt;</code>
				
				<p style="margin-top: 15px;"><strong><?php esc_html_e('Gradient line:', 'smart_product_emails_domain'); ?></strong></p>
				<code>&lt;div style="height: 2px; background: linear-gradient(to right, transparent, #2271b1, transparent); margin: 20px 0;"&gt;&lt;/div&gt;</code>
			</div>
		</details>
		<?php
	}

	public function spe_settings_content_separator_livepreview() {
		?>
		<div id="spe_separator_preview" style="padding: 20px; background: #f9f9f9; border: 1px solid #ddd; border-radius: 4px;">
			<p style="margin: 0 0 10px 0; color: #666; font-size: 13px;">
				<?php esc_html_e('Order content appears here...', 'smart_product_emails_domain'); ?>
			</p>
			
			<div id="spe_separator_preview_top"></div>
			
			<div style="background: #fffbcc; border-left: 4px solid #ff9800; padding: 15px; margin: 10px 0;">
				<strong style="display: block; margin-bottom: 5px; color: #e65100;">
					<?php esc_html_e('Your Custom Email Content', 'smart_product_emails_domain'); ?>
				</strong>
				<p style="margin: 0; color: #666; font-size: 13px;">
					<?php esc_html_e('This is where your custom product-specific content will appear in the customer\'s order email.', 'smart_product_emails_domain'); ?>
				</p>
			</div>
			
			<div id="spe_separator_preview_bottom"></div>
			
			<p style="margin: 10px 0 0 0; color: #666; font-size: 13px;">
				<?php esc_html_e('More order content continues below...', 'smart_product_emails_domain'); ?>
			</p>
		</div>
		<p class="description" style="margin-top: 10px;">
			<?php esc_html_e('This preview shows how the separator will appear in actual customer emails.', 'smart_product_emails_domain'); ?>
		</p>
		<?php
	}

	/**
	 * Adds jQuery and CSS.
	 */
	public function spe_settings_scriptsandstyles() {

		?>
		<script>
        jQuery(document).ready(function($) {
            
            // Initialize WordPress color picker
            if ($.fn.wpColorPicker) {
                $('.spe-color-picker').wpColorPicker({
                    change: function() {
                        updateSeparatorPreview();
                    },
                    clear: function() {
                        updateSeparatorPreview();
                    }
                });
            }
            
            // Update thickness value display
            $('#spe_separator_thickness').on('input', function() {
                $('#spe_thickness_value').text($(this).val() + 'px');
                updateSeparatorPreview();
            });
            
            // Update spacing value display
            $('#spe_separator_spacing').on('input', function() {
                $('#spe_spacing_value').text($(this).val() + 'px');
                updateSeparatorPreview();
            });
            
            // Update preview when separator type changes
            $('#spe_content_separator').on('change', function() {
                var separatorType = $(this).val();
                
                // Show/hide relevant options
                if (separatorType === 'line' || separatorType === 'dots' || separatorType === 'dashes' || separatorType === 'double') {
                    $('.spe_separator_color_row').show();
                    $('.spe_separator_thickness_row').show();
                } else {
                    $('.spe_separator_color_row').hide();
                    $('.spe_separator_thickness_row').hide();
                }

				if (separatorType === 'line' || separatorType === 'dots' || separatorType === 'dashes' || separatorType === 'double' || separatorType === 'space') {
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
            $('#spe_separator_customhtml').on('input', function() {
                if ($('#spe_content_separator').val() === 'custom') {
                    updateSeparatorPreview();
                }
            });
            
            /**
             * Update the live preview based on current settings
             */
            function updateSeparatorPreview() {
                var separatorType = $('#spe_content_separator').val();
                var color = $('#spe_separator_color').val() || '#dddddd';
                var thickness = $('#spe_separator_thickness').val() || '1';
                var spacing = $('#spe_separator_spacing').val() || '20';
                var customHTML = $('#spe_separator_customhtml').val();
                
                var html = '';
                
                switch(separatorType) {
                    case 'none':
                        html = '';
                        break;
                        
                    case 'line':
                        html = '<hr style="border: none; border-top: ' + thickness + 'px solid ' + color + '; margin: ' + spacing + 'px 0;" />';
                        break;
                        
                    case 'dots':
                        html = '<hr style="border: none; border-top: ' + thickness + 'px dotted ' + color + '; margin: ' + spacing + 'px 0;" />';
                        break;
                        
                    case 'dashes':
                        html = '<hr style="border: none; border-top: ' + thickness + 'px dashed ' + color + '; margin: ' + spacing + 'px 0;" />';
                        break;
                        
                    case 'double':
                        html = '<hr style="border: none; border-top: ' + thickness + 'px double ' + color + '; margin: ' + spacing + 'px 0;" />';
                        break;
                        
                    case 'space':
                        html = '<div style="height: ' + spacing + 'px;"></div>';
                        break;
                        
                    case 'custom':
                        html = customHTML || '<div style="border-top: 2px solid #ff9800; margin: 20px 0;"></div>';
                        break;
                }

				// Show/hide relevant options
                if (separatorType === 'line' || separatorType === 'dots' || separatorType === 'dashes' || separatorType === 'double') {
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
	$spe_settings = new Smart_Product_Emails_For_WooCommerce_Admin_Settings();
}

/*
 * Retrieve values with the following functions:
 *
 * $spe_settings_options = get_option( 'SmartProductEmails_settings_name' ); // Array of All Options
 * $show_in_admin_email = $spe_settings_options['show_in_admin_email']; // Include in Admin Emails
 * $display_classes = $spe_settings_options['display_classes']; // Display for Other Product Types
 */
