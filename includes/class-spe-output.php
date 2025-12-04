<?php
/**
 * Handles output of the messages inside the WC emails.
 *
 * @package SmartProductEmails
 */

/**
 * Smart_Product_Emails_Output is a class used to output the custom email message.
 */
class Smart_Product_Emails_Output {

	// Define vars.

	/**
	 * Assigns the current version of this plugin.
	 *
	 * @since 1.0.0
	 * @var string $version The current version of this plugin.
	 */
	protected $version;

	/**
	 * Assigns the text domain of this plugin.
	 *
	 * @since 1.0.0
	 * @var string $textdomain The text domain of this plugin.
	 */
	protected $textdomain;

	/**
	 * Assigns an array of messages which have already been added to the email.
	 *
	 * @since 1.0.0
	 * @var array $shown_messages An array of messages which have already been added to the email.
	 */
	public $shown_messages;

	/**
	 * Class constructor.
	 */
	public function __construct() {

		$this->version = SPE_PLUGIN_VERSION;

		$shown_messages = array();

		/**
		 * Hook: spe_register_order_status_actions_before_processing
		 *
		 * Allows PRO version to register order status actions before Processing
		 * (e.g., On-Hold status actions)
		 */
		do_action('spe_register_order_status_actions_before_processing', $this);

		// PROCESSING STATUS.
		add_action( 'woocommerce_order_status_cancelled_to_processing_notification', array( $this, 'status_action_processing' ), 10 );
		add_action( 'woocommerce_order_status_cancelled_to_processing_notification', array( $this, 'smart_product_emails_insert_content' ), 10, 2 );
		add_action( 'woocommerce_order_status_failed_to_processing_notification', array( $this, 'status_action_processing' ), 10 );
		add_action( 'woocommerce_order_status_failed_to_processing_notification', array( $this, 'smart_product_emails_insert_content' ), 10, 2 );
		add_action( 'woocommerce_order_status_on-hold_to_processing_notification', array( $this, 'status_action_processing' ), 10 );
		add_action( 'woocommerce_order_status_on-hold_to_processing_notification', array( $this, 'smart_product_emails_insert_content' ), 10, 2 );
		add_action( 'woocommerce_order_status_pending_to_processing_notification', array( $this, 'status_action_processing' ), 10 );
		add_action( 'woocommerce_order_status_pending_to_processing_notification', array( $this, 'smart_product_emails_insert_content' ), 10, 2 );

		/**
		 * Hook: spe_register_order_status_actions_after_processing
		 *
		 * Allows PRO version to register order status actions after Processing
		 * (e.g., Completed status actions)
		 */
		do_action('spe_register_order_status_actions_after_processing', $this);

		/*
		// FAILED STATUS
		// woocommerce_order_status_pending_to_failed_notification
		// woocommerce_order_status_on-hold_to_failed_notification

		// REFUNDED
		// woocommerce_order_fully_refunded_notification
		// woocommerce_order_partially_refunded_notification

		// CANCELLED
		// woocommerce_order_status_processing_to_cancelled_notification
		// woocommerce_order_status_on-hold_to_cancelled_notification
		*/

	}

	/**
	 * Adds a flag for when the current status is set to 'processing'.
	 */
	public function status_action_processing() {
		global $this_order_status_action;
		$this_order_status_action = 'woocommerce_order_status_processing';
	}

	/**
	 * Insert the custom content into the email template at the chosen location.
	 *
	 * @param array  $shown_messages An array of which messages have already been added to the email.
	 * @param object $order An object containing all the order info.
	 */
	public function smart_product_emails_insert_content( $order, $shown_messages ) {

		global $woocommerce;

		if ( ! is_array( $shown_messages ) ) {
			$shown_messages = array();
		}

		global $this_order_status_action;

		/**
		 * Get separator HTML based on settings
		 * 
		 * @return string HTML for separator
		 */
		function get_separator_html() {
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

		// Function to output the smart product email.
		if ( ! function_exists( 'smart_product_emails_output_message' ) ) {

			/**
			 * Inserts the custom content into the email template at the chosen location.
			 *
			 * @param object  $order An object containing all the Order information.
			 * @param boolean $sent_to_admin A boolean value if this email is sent to admin or not.
			 * @param array   $shown_messages An array of which messages have already been added to the email.
			 */
			function smart_product_emails_output_message( $order, $sent_to_admin, $email, $shown_messages ) {

				if ( ! is_array( $shown_messages ) ) {
					$shown_messages = array();
				}

				global $this_order_status_action;

				// Get items in this order using HPOS-compatible method
    			$items = $order->get_items();

				// Loop through all items in this order.
				foreach ( $items as $item ) {

					// Use HPOS-compatible methods for getting product ID
					$product_id = $item->get_product_id();
					$variation_id = $item->get_variation_id();

					// Check variation first, then parent product
        			$this_product_id = $variation_id ? $variation_id : $product_id;

					// Get the product object
					$product = wc_get_product($this_product_id);
					if (!$product) {
						continue;
					}

					// Legacy support - still check old meta structure
					$orderstatus_meta = (string) get_post_meta($this_product_id, 'order_status', true);
					$spemail_id = get_post_meta($this_product_id, 'spemail_id', true);
					$templatelocation_meta = get_post_meta($this_product_id, 'location', true);

					// Set the current email template location.
					$this_email_template_location = (string) current_action();

					/**
					 * Hook: spe_output_message_before_processing
					 *
					 * Allows PRO version to output messages for additional order statuses before "Processing"
					 * (e.g., On-Hold status)
					 *
					 * @param WC_Product $product The product object
					 * @param WC_Order $order The order object
					 * @param array $shown_messages Array of already shown message IDs
					 * @param string $this_email_template_location Current email template location
					 */
					do_action('spe_output_message_before_processing', $product, $order, $sent_to_admin, $shown_messages, $this_email_template_location);

					// PROCESSING Status.
					// --------------------------------

					// Use WooCommerce CRUD methods for meta data
					$spemail_id_processing = (int) $product->get_meta('spemail_id_processing');
					$spemail_location_processing = $product->get_meta('location_processing');

					// Begin logic for adding message content
					if ( 'woocommerce_order_status_processing' === $this_order_status_action && !empty( $spemail_id_processing ) ) {

						// If there is an email assigned for 'Processing' status and this message is not already shown,
						// AND if the message location set for the 'Processing' message is the current email template location...
						// AND if this email is NOT being sent to admin...
						if ( !in_array( $spemail_id_processing, $shown_messages, true ) &&
                			$spemail_location_processing === $this_email_template_location && !$sent_to_admin ) {

							// Show the message.

							// Define output var.
							$output = '';

							// Get separator content.
							$separator = get_separator_html();

							// Output custom separator.
							$output .= $separator;

							// Output the_content of the saved SPE Message ID.
							$output .= wp_kses_post( nl2br( get_post_field( 'post_content', $spemail_id_processing ) ) );

							// Output custom separator.
							$output .= $separator;

							// Output everything.
							echo wp_kses_post($output);

							// Update 'shown_emails' var.
							$shown_messages[] = $spemail_id_processing;
						}
					}

					/**
					 * Hook: spe_output_message_after_processing
					 *
					 * Allows PRO version to output messages for additional order statuses after "Processing"
					 * (e.g., Completed status)
					 *
					 * @param WC_Product $product The product object
					 * @param WC_Order $order The order object
					 * @param array $shown_messages Array of already shown message IDs
					 * @param string $this_email_template_location Current email template location
					 */
					do_action('spe_output_message_after_processing', $product, $order, $sent_to_admin, $shown_messages, $this_email_template_location);
					
				}

			}
		}



		// Wrapper function for email header hook (different parameters)
		function smart_product_emails_header_wrapper( $email_heading, $email ) {
			if ( is_a( $email, 'WC_Email' ) && isset( $email->object ) && is_a( $email->object, 'WC_Order' ) ) {
				$order = $email->object;
				$sent_to_admin = method_exists( $email, 'is_customer_email' ) ? ! $email->is_customer_email() : false;
				$shown_messages = array();
				smart_product_emails_output_message( $order, $sent_to_admin, $email, $shown_messages );
			}
		}

		// Wrapper function for email footer hook (different parameters)
		function smart_product_emails_footer_wrapper( $email ) {
			if ( is_a( $email, 'WC_Email' ) && isset( $email->object ) && is_a( $email->object, 'WC_Order' ) ) {
				$order = $email->object;
				$sent_to_admin = method_exists( $email, 'is_customer_email' ) ? ! $email->is_customer_email() : false;
				$shown_messages = array();
				smart_product_emails_output_message( $order, $sent_to_admin, $email, $shown_messages );
			}
		}

		// Add an action for each email template location to insert our smart product email.
		add_action( 'woocommerce_email_header', 'smart_product_emails_header_wrapper', 30, 2 ); // Email Template Location: Email Header (priority 30 = after header is rendered)
		add_action( 'woocommerce_email_before_order_table', 'smart_product_emails_output_message', 10, 4 ); // Email Template Location: Before Order Table
		add_action( 'woocommerce_email_after_order_table', 'smart_product_emails_output_message', 10, 4 ); // Email Template Location: After Order Table
		add_action( 'woocommerce_email_order_meta', 'smart_product_emails_output_message', 10, 4 ); // Email Template Location: After Order Meta
		add_action( 'woocommerce_email_customer_details', 'smart_product_emails_output_message', 10, 4 ); // Email Template Location: After Customer Details
		add_action( 'woocommerce_email_footer', 'smart_product_emails_footer_wrapper', 5, 1 ); // Email Template Location: Email Footer (priority 5 = before footer is rendered)

	}

}
