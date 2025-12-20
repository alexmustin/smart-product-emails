<?php
/**
 * Logger Class for Smart Product Emails
 *
 * Provides centralized error logging functionality with different log levels.
 * Stores logs in custom database table for easy viewing and filtering.
 *
 * @package SmartProductEmails
 */

class SPE_Logger {

	/**
	 * Singleton instance
	 *
	 * @var SPE_Logger
	 */
	private static $instance = null;

	/**
	 * Database table name
	 *
	 * @var string
	 */
	private $table_name;

	/**
	 * Private constructor to prevent direct instantiation
	 */
	private function __construct() {
		global $wpdb;
		$this->table_name = $wpdb->prefix . 'spe_error_logs';
	}

	/**
	 * Get singleton instance
	 *
	 * @return SPE_Logger
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Log an error
	 *
	 * @param string $message Error message
	 * @param array  $context Additional context data
	 */
	public function error( $message, $context = array() ) {
		$this->log( 'error', $message, $context );
	}

	/**
	 * Log a warning
	 *
	 * @param string $message Warning message
	 * @param array  $context Additional context data
	 */
	public function warning( $message, $context = array() ) {
		$this->log( 'warning', $message, $context );
	}

	/**
	 * Log info
	 *
	 * @param string $message Info message
	 * @param array  $context Additional context data
	 */
	public function info( $message, $context = array() ) {
		$this->log( 'info', $message, $context );
	}

	/**
	 * Log debug info
	 * Only logs if WP_DEBUG is enabled
	 *
	 * @param string $message Debug message
	 * @param array  $context Additional context data
	 */
	public function debug( $message, $context = array() ) {
		// Only log debug if WP_DEBUG is enabled
		if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
			return;
		}
		$this->log( 'debug', $message, $context );
	}

	/**
	 * Core logging method
	 *
	 * @param string $level   Log level (error, warning, info, debug)
	 * @param string $message Message to log
	 * @param array  $context Additional context data
	 */
	private function log( $level, $message, $context = array() ) {
		global $wpdb;

		// Extract context data
		$user_id     = isset( $context['user_id'] ) ? $context['user_id'] : get_current_user_id();
		$product_id  = isset( $context['product_id'] ) ? $context['product_id'] : null;
		$order_id    = isset( $context['order_id'] ) ? $context['order_id'] : null;
		$error_type  = isset( $context['error_type'] ) ? $context['error_type'] : null;
		$file        = isset( $context['file'] ) ? $context['file'] : null;
		$line        = isset( $context['line'] ) ? $context['line'] : null;
		$stack_trace = isset( $context['stack_trace'] ) ? $context['stack_trace'] : null;

		// Get request data
		$request_url = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( $_SERVER['REQUEST_URI'] ) : null;
		$user_agent  = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( $_SERVER['HTTP_USER_AGENT'] ) : null;
		$ip_address  = $this->get_client_ip();

		// Prepare context JSON (remove already-extracted fields)
		$context_data = $context;
		unset( $context_data['user_id'], $context_data['product_id'], $context_data['order_id'] );
		unset( $context_data['error_type'], $context_data['file'], $context_data['line'], $context_data['stack_trace'] );

		$context_json = ! empty( $context_data ) ? wp_json_encode( $context_data ) : null;

		// Insert log entry
		$wpdb->insert(
			$this->table_name,
			array(
				'log_level'    => $level,
				'message'      => $message,
				'context'      => $context_json,
				'user_id'      => $user_id,
				'product_id'   => $product_id,
				'order_id'     => $order_id,
				'error_type'   => $error_type,
				'file'         => $file,
				'line'         => $line,
				'stack_trace'  => $stack_trace,
				'request_url'  => $request_url,
				'user_agent'   => $user_agent,
				'ip_address'   => $ip_address,
				'created_at'   => current_time( 'mysql' ),
			),
			array(
				'%s', '%s', '%s', '%d', '%d', '%d', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s'
			)
		);

		// Also log to PHP error_log if WP_DEBUG is enabled
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( sprintf( '[SPE %s] %s', strtoupper( $level ), $message ) );
		}
	}

	/**
	 * Get client IP address
	 *
	 * @return string Client IP address
	 */
	private function get_client_ip() {
		$ip = '';

		// Try different sources for IP address and validate
		if ( isset( $_SERVER['HTTP_CLIENT_IP'] ) ) {
			$ip = filter_var( wp_unslash( $_SERVER['HTTP_CLIENT_IP'] ), FILTER_VALIDATE_IP );
		} elseif ( isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			// X-Forwarded-For can contain multiple IPs, get the first one
			$forwarded = wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] );
			$ip_list = explode( ',', $forwarded );
			$ip = filter_var( trim( $ip_list[0] ), FILTER_VALIDATE_IP );
		} elseif ( isset( $_SERVER['REMOTE_ADDR'] ) ) {
			$ip = filter_var( wp_unslash( $_SERVER['REMOTE_ADDR'] ), FILTER_VALIDATE_IP );
		}

		// Return validated IP or empty string
		return $ip ? $ip : '';
	}

	/**
	 * Clear logs older than X days
	 *
	 * @param int $days Number of days to keep logs (default: 30)
	 */
	public function cleanup_old_logs( $days = 30 ) {
		global $wpdb;

		$wpdb->query( $wpdb->prepare(
			"DELETE FROM {$this->table_name} WHERE created_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
			$days
		) );
	}

	/**
	 * Get total log count
	 *
	 * @param array $filters Filter criteria
	 * @return int Total log count
	 */
	public function get_log_count( $filters = array() ) {
		global $wpdb;

		$where = $this->build_where_clause( $filters );

		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$this->table_name} {$where}" );
	}

	/**
	 * Get logs with pagination and filtering
	 *
	 * @param array $args Query arguments
	 * @return array Log entries
	 */
	public function get_logs( $args = array() ) {
		global $wpdb;

		$defaults = array(
			'per_page' => 20,
			'page'     => 1,
			'orderby'  => 'created_at',
			'order'    => 'DESC',
			'filters'  => array(),
		);

		$args = wp_parse_args( $args, $defaults );

		$where  = $this->build_where_clause( $args['filters'] );
		$orderby = sanitize_sql_orderby( $args['orderby'] . ' ' . $args['order'] );
		$limit  = absint( $args['per_page'] );
		$offset = ( absint( $args['page'] ) - 1 ) * $limit;

		$query = "SELECT * FROM {$this->table_name} {$where} ORDER BY {$orderby} LIMIT {$limit} OFFSET {$offset}";

		return $wpdb->get_results( $query );
	}

	/**
	 * Build WHERE clause from filters
	 *
	 * @param array $filters Filter criteria
	 * @return string WHERE clause
	 */
	private function build_where_clause( $filters ) {
		global $wpdb;

		$where_clauses = array();

		if ( ! empty( $filters['log_level'] ) ) {
			$where_clauses[] = $wpdb->prepare( 'log_level = %s', $filters['log_level'] );
		}

		if ( ! empty( $filters['error_type'] ) ) {
			$where_clauses[] = $wpdb->prepare( 'error_type = %s', $filters['error_type'] );
		}

		if ( ! empty( $filters['user_id'] ) ) {
			$where_clauses[] = $wpdb->prepare( 'user_id = %d', $filters['user_id'] );
		}

		if ( ! empty( $filters['product_id'] ) ) {
			$where_clauses[] = $wpdb->prepare( 'product_id = %d', $filters['product_id'] );
		}

		if ( ! empty( $filters['order_id'] ) ) {
			$where_clauses[] = $wpdb->prepare( 'order_id = %d', $filters['order_id'] );
		}

		if ( ! empty( $filters['search'] ) ) {
			$search = '%' . $wpdb->esc_like( $filters['search'] ) . '%';
			$where_clauses[] = $wpdb->prepare( 'message LIKE %s', $search );
		}

		if ( ! empty( $filters['date_from'] ) ) {
			$where_clauses[] = $wpdb->prepare( 'created_at >= %s', $filters['date_from'] );
		}

		if ( ! empty( $filters['date_to'] ) ) {
			$where_clauses[] = $wpdb->prepare( 'created_at <= %s', $filters['date_to'] );
		}

		if ( ! empty( $where_clauses ) ) {
			return 'WHERE ' . implode( ' AND ', $where_clauses );
		}

		return '';
	}
}
