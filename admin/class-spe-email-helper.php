<?php
class SPE_Email_Helper {

    /**
	 * Get WooCommerce email color settings
	 */
	public static function get_woocommerce_email_colors() {
		// Get WooCommerce email settings
		$base_color = get_option('woocommerce_email_base_color', '#96588a');
		$background_color = get_option('woocommerce_email_background_color', '#f7f7f7');
		$body_background_color = get_option('woocommerce_email_body_background_color', '#ffffff');
		$body_text_color = get_option('woocommerce_email_text_color', '#3c3c3c');

		return array(
			'base' => $base_color,
			'background' => $background_color,
			'body_bg' => $body_background_color,
			'body_text' => $body_text_color
		);
	}

    /**
	 * Get contrast color (white or black) for a given background color
	 */
	public static function get_contrast_color($hex_color) {
		// Remove # if present
		$hex_color = ltrim($hex_color, '#');

		// Convert to RGB
		$r = hexdec(substr($hex_color, 0, 2));
		$g = hexdec(substr($hex_color, 2, 2));
		$b = hexdec(substr($hex_color, 4, 2));

		// Calculate relative luminance
		$luminance = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;

		// Return white for dark backgrounds, black for light backgrounds
		return $luminance > 0.5 ? '#000000' : '#ffffff';
	}

    /**
	 * Get WooCommerce email template settings for specific status
	 */
	public static function get_woocommerce_email_settings($status) {
		// Map status to WooCommerce email option names
		$email_option_map = array(
			'onhold' => 'customer_on_hold_order',
			'processing' => 'customer_processing_order',
			'completed' => 'customer_completed_order'
		);

		$email_type = isset($email_option_map[$status]) ? $email_option_map[$status] : 'customer_processing_order';

		// Get email-specific settings
		$heading = get_option('woocommerce_' . $email_type . '_settings');
		$subject = isset($heading['subject']) ? $heading['subject'] : '';
		$heading_text = isset($heading['heading']) ? $heading['heading'] : '';
		$additional_content = isset($heading['additional_content']) ? $heading['additional_content'] : '';

		// Fallback to direct option names if settings array doesn't exist
		if (empty($heading_text)) {
			$heading_text = get_option('woocommerce_' . $email_type . '_heading', '');
		}
		if (empty($additional_content)) {
			$additional_content = get_option('woocommerce_' . $email_type . '_additional_content', '');
		}

		// Default headings if none set
		$default_headings = array(
			'onhold' => __('Thank you for your order', 'woocommerce'),
			'processing' => __('Thank you for your order', 'woocommerce'),
			'completed' => __('Your order is complete', 'woocommerce')
		);

		if (empty($heading_text)) {
			$heading_text = isset($default_headings[$status]) ? $default_headings[$status] : __('Thank you for your order', 'woocommerce');
		}

		return array(
			'subject' => $subject,
			'heading' => $heading_text,
			'additional_content' => $additional_content
		);
	}

    /**
	 * Get separator HTML based on settings
	 * 
	 * @return string HTML for separator
	 */
	public static function get_separator_html() {
		$settings = get_option( 'SmartProductEmails_settings_name', array() );

		$separator_type = isset($settings['content_separator']) ? $settings['content_separator'] : 'none';
		$color = isset($settings['separator_color']) ? $settings['separator_color'] : '#dddddd';
		$thickness = isset($settings['separator_thickness']) ? $settings['separator_thickness'] : '1';
		$spacing = isset($settings['separator_spacing']) ? $settings['separator_spacing'] : '20';
		$custom_html = isset($settings['separator_customhtml']) ? $settings['separator_customhtml'] : '';
		
		switch ($separator_type) {
			case 'line':
				return '<hr style="border: none; border-top: ' . esc_attr($thickness) . 'px solid ' . esc_attr($color) . '; margin: ' . esc_attr($spacing) . 'px 0;" />';
				
			case 'dots':
				return '<hr style="border: none; border-top: ' . esc_attr($thickness) . 'px dotted ' . esc_attr($color) . '; margin: ' . esc_attr($spacing) . 'px 0;" />';
				
			case 'dashes':
				return '<hr style="border: none; border-top: ' . esc_attr($thickness) . 'px dashed ' . esc_attr($color) . '; margin: ' . esc_attr($spacing) . 'px 0;" />';
				
			case 'double':
				return '<hr style="border: none; border-top: ' . esc_attr($thickness) . 'px double ' . esc_attr($color) . '; margin: ' . esc_attr($spacing) . 'px 0;" />';
				
			case 'space':
				return '<div style="height: ' . esc_attr($spacing) . 'px;"></div>';
				
			case 'custom':
				return nl2br($custom_html);
				
			case 'none':
			default:
				return '';
		}
	}

    /**
	 * Get blog name for placeholders.
	 *
	 * @return string
	 */
	public static function get_blogname() {
		return wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
	}

	/**
	 * Get the store physical address for placeholders.
	 */
	public static function get_store_address(){

		$shop_address = '';

		// The main address pieces:
		$store_address     = get_option( 'woocommerce_store_address' );
		$store_address_2   = get_option( 'woocommerce_store_address_2' );
		$store_city        = get_option( 'woocommerce_store_city' );
		$store_postcode    = get_option( 'woocommerce_store_postcode' );

		// The country/state
		$store_raw_country = get_option( 'woocommerce_default_country' );

		// Split the country/state
		$split_country = explode( ":", $store_raw_country );

		// Country and state separated:
		$store_country = $split_country[0];
		$store_state   = $split_country[1];

		$shop_address .= $store_address . "<br />";
		$shop_address .= ( $store_address_2 ) ? $store_address_2 . "<br />" : '';
		$shop_address .= $store_city . ', ' . $store_state . ' ' . $store_postcode . "<br />";
		$shop_address .= $store_country;

		return $shop_address;
	}

	/**
	 * Get from email address for placeholders.
	 *
	 * @return string
	 */
	public static function get_from_address() {
		return sanitize_email( get_option( 'woocommerce_email_from_address' ) );
	}

	/**
	 * Replace placeholders text in email preview.
	 *
	 */
	public static function replace_placeholders($text) {
		$domain = wp_parse_url( home_url(), PHP_URL_HOST );
        // $domain = "DOMAIN";

        $replaced_text = str_replace(
			array(
				'{site_title}',
				'{site_address}',
				'{site_url}',
				'{store_email}',
				'{order_date}',
				'{order_number}'
				// {store_address} -> self::get_store_address()
			),
			array(
				self::get_blogname(),
				$domain,
				$domain,
				self::get_from_address(),
				current_time( get_option( 'date_format' ) ),
				'12345'
			),
			$text
        );

		return $replaced_text;
	}

}