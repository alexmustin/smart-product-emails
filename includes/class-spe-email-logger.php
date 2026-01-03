<?php
/**
 * Email Logger for Smart Product Emails
 *
 * Captures email sending errors and successful email sends.
 *
 * @package SmartProductEmails
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SmartProductEmails_Email_Logger {

	/**
	 * Logger instance
	 *
	 * @var SmartProductEmails_Logger
	 */
	private $logger;

	/**
	 * Constructor - Initialize hooks
	 */
	public function __construct() {
		$this->logger = SmartProductEmails_Logger::get_instance();
		$this->init_hooks();
	}

	/**
	 * Initialize WordPress hooks
	 */
	private function init_hooks() {
		// Hook into wp_mail_failed
		add_action( 'wp_mail_failed', array( $this, 'log_email_failure' ), 10, 1 );

		// Hook into WooCommerce email sent action
		add_action( 'woocommerce_email_sent', array( $this, 'log_email_sent' ), 10, 3 );
	}

	/**
	 * Log email failures
	 *
	 * @param WP_Error $wp_error WP_Error object from wp_mail_failed
	 */
	public function log_email_failure( $wp_error ) {
		if ( ! is_wp_error( $wp_error ) ) {
			return;
		}

		$context = array(
			'error_type' => 'email_error',
			'error_code' => $wp_error->get_error_code(),
			'error_data' => wp_json_encode( $wp_error->get_error_data() ),
		);

		$this->logger->error( $wp_error->get_error_message(), $context );
	}

	/**
	 * Log successful email sends (info level)
	 *
	 * @param bool   $return       Whether email was sent successfully
	 * @param string $email_id     Email ID
	 * @param object $email_object WooCommerce email object
	 */
	public function log_email_sent( $return, $email_id, $email_object ) {
		// Only log if email was sent successfully
		if ( ! $return ) {
			return;
		}

		// Only log SPE-related emails (check if product has SPE messages)
		if ( ! $this->is_spe_email( $email_object ) ) {
			return;
		}

		$to = '';
		$subject = '';

		// Extract recipient and subject from email object
		if ( isset( $email_object->recipient ) ) {
			$to = $email_object->recipient;
		}
		if ( isset( $email_object->get_subject ) && is_callable( array( $email_object, 'get_subject' ) ) ) {
			$subject = $email_object->get_subject();
		}

		$context = array(
			'error_type' => 'email_sent',
			'email_id'   => $email_id,
			'to'         => $to,
			'subject'    => $subject,
		);

		$this->logger->info( 'Email sent successfully: ' . $email_id, $context );
	}

	/**
	 * Check if email is SPE-related
	 *
	 * @param object $email_object WooCommerce email object
	 * @return bool True if email contains SPE messages
	 */
	private function is_spe_email( $email_object ) {
		// For now, log all WooCommerce emails
		// In the future, we can check if the order contains products with SPE messages
		// This would require checking the order object for products that have spemail_id meta

		// Simple check: if this is a WooCommerce email, log it
		return isset( $email_object->id );
	}
}
