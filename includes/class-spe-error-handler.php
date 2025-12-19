<?php
/**
 * Error Handler for Smart Product Emails
 *
 * Registers PHP error and exception handlers to capture errors from plugin files.
 *
 * @package SmartProductEmails
 */

class SPE_Error_Handler {

	/**
	 * Logger instance
	 *
	 * @var SPE_Logger
	 */
	private $logger;

	/**
	 * Constructor - Initialize and register handlers
	 */
	public function __construct() {
		$this->logger = SPE_Logger::get_instance();
		$this->register_handlers();
	}

	/**
	 * Register error and exception handlers
	 */
	private function register_handlers() {
		// Register error handler for plugin files only
		set_error_handler( array( $this, 'handle_error' ) );

		// Register exception handler
		set_exception_handler( array( $this, 'handle_exception' ) );
	}

	/**
	 * Handle PHP errors
	 *
	 * @param int    $errno   Error number
	 * @param string $errstr  Error message
	 * @param string $errfile File where error occurred
	 * @param int    $errline Line where error occurred
	 * @return bool False to allow WordPress default error handling
	 */
	public function handle_error( $errno, $errstr, $errfile, $errline ) {
		// Only handle errors from our plugin
		if ( ! $this->is_plugin_file( $errfile ) ) {
			return false; // Let WordPress handle it
		}

		// Map PHP error types to log levels
		$error_types = array(
			E_ERROR             => 'error',
			E_WARNING           => 'warning',
			E_PARSE             => 'error',
			E_NOTICE            => 'warning',
			E_CORE_ERROR        => 'error',
			E_CORE_WARNING      => 'warning',
			E_COMPILE_ERROR     => 'error',
			E_COMPILE_WARNING   => 'warning',
			E_USER_ERROR        => 'error',
			E_USER_WARNING      => 'warning',
			E_USER_NOTICE       => 'info',
			E_STRICT            => 'info',
			E_RECOVERABLE_ERROR => 'error',
			E_DEPRECATED        => 'warning',
			E_USER_DEPRECATED   => 'warning',
		);

		$log_level = isset( $error_types[ $errno ] ) ? $error_types[ $errno ] : 'error';

		$context = array(
			'error_type' => 'php_error',
			'file'       => $errfile,
			'line'       => $errline,
			'errno'      => $errno,
		);

		// Log based on level
		if ( 'error' === $log_level ) {
			$this->logger->error( $errstr, $context );
		} elseif ( 'warning' === $log_level ) {
			$this->logger->warning( $errstr, $context );
		} else {
			$this->logger->info( $errstr, $context );
		}

		// Don't prevent WordPress default error handler
		return false;
	}

	/**
	 * Handle uncaught exceptions
	 *
	 * @param Exception $exception The uncaught exception
	 */
	public function handle_exception( $exception ) {
		// Only handle exceptions from our plugin
		if ( ! $this->is_plugin_file( $exception->getFile() ) ) {
			return;
		}

		$context = array(
			'error_type'  => 'php_exception',
			'file'        => $exception->getFile(),
			'line'        => $exception->getLine(),
			'stack_trace' => $exception->getTraceAsString(),
		);

		$this->logger->error( $exception->getMessage(), $context );
	}

	/**
	 * Check if file belongs to our plugin
	 *
	 * @param string $file File path to check
	 * @return bool True if file is part of SPE plugin
	 */
	private function is_plugin_file( $file ) {
		// Check if file path contains our plugin directory
		$plugin_dir = defined( 'SPE_PLUGIN_DIR' ) ? SPE_PLUGIN_DIR : '';
		$pro_plugin_dir = defined( 'SPE_PRO_PLUGIN_DIR' ) ? SPE_PRO_PLUGIN_DIR : '';

		return ( ! empty( $plugin_dir ) && strpos( $file, $plugin_dir ) !== false ) ||
		       ( ! empty( $pro_plugin_dir ) && strpos( $file, $pro_plugin_dir ) !== false );
	}
}
