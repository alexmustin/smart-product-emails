<?php
/**
 * Error Log Admin Page for Smart Product Emails
 *
 * Provides admin interface for viewing, filtering, and managing error logs.
 *
 * @package SmartProductEmails
 */

class SPE_Error_Log_Admin {

	/**
	 * Logger instance
	 *
	 * @var SPE_Logger
	 */
	private $logger;

	/**
	 * Constructor - Initialize hooks
	 */
	public function __construct() {
		$this->logger = SPE_Logger::get_instance();
		$this->init_hooks();
	}

	/**
	 * Initialize hooks
	 */
	private function init_hooks() {
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'wp_ajax_spe_clear_logs', array( $this, 'ajax_clear_logs' ) );
		add_action( 'wp_ajax_spe_export_logs', array( $this, 'ajax_export_logs' ) );
	}

	/**
	 * Add admin menu page
	 */
	public function add_admin_menu() {
		add_submenu_page(
			'edit.php?post_type=smartproductemails',
			__( 'Error Log', 'smart-product-emails' ),
			__( 'Error Log', 'smart-product-emails' ),
			'manage_options',
			'spe-error-log',
			array( $this, 'render_admin_page' )
		);
	}

	/**
	 * Enqueue admin scripts and styles
	 *
	 * @param string $hook Current admin page hook
	 */
	public function enqueue_scripts( $hook ) {
		if ( 'smartproductemails_page_spe-error-log' !== $hook ) {
			return;
		}

		wp_enqueue_style(
			'spe-error-log',
			SPE_PLUGIN_URL . 'admin/css/spe-error-log.css',
			array(),
			SPE_PLUGIN_VERSION
		);

		wp_enqueue_script(
			'spe-error-log',
			SPE_PLUGIN_URL . 'admin/js/spe-error-log.js',
			array( 'jquery' ),
			SPE_PLUGIN_VERSION,
			true
		);

		wp_localize_script(
			'spe-error-log',
			'speErrorLog',
			array(
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'spe_error_log_nonce' ),
			)
		);
	}

	/**
	 * Render admin page
	 */
	public function render_admin_page() {
		// Check user capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'smart-product-emails' ) );
		}

		// Get filters from request
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonces not required for GET-based filtering per WordPress documentation (https://developer.wordpress.org/apis/security/nonces/#when-to-use-nonces). This is read-only display filtering with no state changes, and users may bookmark filtered URLs.
		$filters = array(
			'log_level'  => isset( $_GET['log_level'] ) ? sanitize_text_field( wp_unslash( $_GET['log_level'] ) ) : '',
			'error_type' => isset( $_GET['error_type'] ) ? sanitize_text_field( wp_unslash( $_GET['error_type'] ) ) : '',
			'search'     => isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '',
		);

		// Get current page
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonces not required for GET-based pagination per WordPress documentation. Read-only display with no state changes.
		$current_page = isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1;

		// Get logs
		$logs = $this->logger->get_logs( array(
			'per_page' => 20,
			'page'     => $current_page,
			'filters'  => $filters,
		) );

		$total_logs = $this->logger->get_log_count( $filters );
		$total_pages = ceil( $total_logs / 20 );

		?>
		<div class="wrap spe-error-log-wrap">
			<h1><?php esc_html_e( 'Smart Product Emails - Error Log', 'smart-product-emails' ); ?></h1>

			<!-- Filters -->
			<div class="spe-log-filters">
				<form method="get" action="">
					<input type="hidden" name="post_type" value="smartproductemails">
					<input type="hidden" name="page" value="spe-error-log">

					<!-- Log Level Filter -->
					<select name="log_level" id="spe-log-level-filter">
						<option value=""><?php esc_html_e( 'All Levels', 'smart-product-emails' ); ?></option>
						<option value="error" <?php selected( $filters['log_level'], 'error' ); ?>><?php esc_html_e( 'Error', 'smart-product-emails' ); ?></option>
						<option value="warning" <?php selected( $filters['log_level'], 'warning' ); ?>><?php esc_html_e( 'Warning', 'smart-product-emails' ); ?></option>
						<option value="info" <?php selected( $filters['log_level'], 'info' ); ?>><?php esc_html_e( 'Info', 'smart-product-emails' ); ?></option>
						<option value="debug" <?php selected( $filters['log_level'], 'debug' ); ?>><?php esc_html_e( 'Debug', 'smart-product-emails' ); ?></option>
					</select>

					<!-- Error Type Filter -->
					<select name="error_type" id="spe-error-type-filter">
						<option value=""><?php esc_html_e( 'All Types', 'smart-product-emails' ); ?></option>
						<option value="php_error" <?php selected( $filters['error_type'], 'php_error' ); ?>><?php esc_html_e( 'PHP Error', 'smart-product-emails' ); ?></option>
						<option value="ajax_error" <?php selected( $filters['error_type'], 'ajax_error' ); ?>><?php esc_html_e( 'AJAX Error', 'smart-product-emails' ); ?></option>
						<option value="email_error" <?php selected( $filters['error_type'], 'email_error' ); ?>><?php esc_html_e( 'Email Error', 'smart-product-emails' ); ?></option>
						<option value="user_action" <?php selected( $filters['error_type'], 'user_action' ); ?>><?php esc_html_e( 'User Action', 'smart-product-emails' ); ?></option>
					</select>

					<!-- Search -->
					<input type="search" name="s" value="<?php echo esc_attr( $filters['search'] ); ?>" placeholder="<?php esc_attr_e( 'Search logs...', 'smart-product-emails' ); ?>">

					<button type="submit" class="button"><?php esc_html_e( 'Filter', 'smart-product-emails' ); ?></button>
					<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=smartproductemails&page=spe-error-log' ) ); ?>" class="button"><?php esc_html_e( 'Reset', 'smart-product-emails' ); ?></a>
				</form>

				<!-- Actions -->
				<div class="spe-log-actions">
					<button type="button" class="button" id="spe-export-logs"><?php esc_html_e( 'Export CSV', 'smart-product-emails' ); ?></button>
					<button type="button" class="button button-secondary" id="spe-clear-logs"><?php esc_html_e( 'Clear All Logs', 'smart-product-emails' ); ?></button>
				</div>
			</div>

			<!-- Logs Table -->
			<div class="spe-logs-table-wrapper">
			<table class="wp-list-table widefat fixed striped spe-logs-table">
				<thead>
					<tr>
						<th class="spe-log-level"><?php esc_html_e( 'Level', 'smart-product-emails' ); ?></th>
						<th class="spe-log-message"><?php esc_html_e( 'Message', 'smart-product-emails' ); ?></th>
						<th class="spe-log-type"><?php esc_html_e( 'Type', 'smart-product-emails' ); ?></th>
						<th class="spe-log-location"><?php esc_html_e( 'Location', 'smart-product-emails' ); ?></th>
						<th class="spe-log-date"><?php esc_html_e( 'Date', 'smart-product-emails' ); ?></th>
						<th class="spe-log-actions"><?php esc_html_e( 'Actions', 'smart-product-emails' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $logs ) ) : ?>
						<tr>
							<td colspan="6" class="spe-no-logs"><?php esc_html_e( 'No logs found.', 'smart-product-emails' ); ?></td>
						</tr>
					<?php else : ?>
						<?php foreach ( $logs as $log ) : ?>
							<tr class="spe-log-row spe-log-<?php echo esc_attr( $log->log_level ); ?>" data-log-id="<?php echo esc_attr( $log->id ); ?>">
								<td class="spe-log-level">
									<span class="spe-badge spe-badge-<?php echo esc_attr( $log->log_level ); ?>">
										<?php echo esc_html( ucfirst( $log->log_level ) ); ?>
									</span>
								</td>
								<td class="spe-log-message">
									<div class="spe-message-preview">
										<?php echo esc_html( wp_trim_words( $log->message, 20 ) ); ?>
									</div>
									<div class="spe-message-full" style="display: none;">
										<?php echo esc_html( $log->message ); ?>
									</div>
									<?php if ( $log->stack_trace ) : ?>
										<div class="spe-stack-trace" style="display: none;">
											<pre><?php echo esc_html( $log->stack_trace ); ?></pre>
										</div>
									<?php endif; ?>
									<?php if ( $log->context ) : ?>
										<div class="spe-context" style="display: none;">
											<pre><?php echo esc_html( $log->context ); ?></pre>
										</div>
									<?php endif; ?>
								</td>
								<td class="spe-log-type">
									<?php echo $log->error_type ? esc_html( $log->error_type ) : '—'; ?>
								</td>
								<td class="spe-log-location">
									<?php if ( $log->file ) : ?>
										<code><?php echo esc_html( basename( $log->file ) ); ?>:<?php echo esc_html( $log->line ); ?></code>
									<?php else : ?>
										—
									<?php endif; ?>
								</td>
								<td class="spe-log-date">
									<?php echo esc_html( mysql2date( 'Y-m-d H:i:s', $log->created_at ) ); ?>
								</td>
								<td class="spe-log-actions">
									<button type="button" class="button button-small spe-view-details" data-log-id="<?php echo esc_attr( $log->id ); ?>">
										<?php esc_html_e( 'View Details', 'smart-product-emails' ); ?>
									</button>
								</td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>
			</div><!-- .spe-logs-table-wrapper -->

			<!-- Pagination -->
			<?php if ( $total_pages > 1 ) : ?>
				<div class="tablenav bottom">
					<div class="tablenav-pages">
						<?php
						// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- paginate_links() returns safe HTML
						echo wp_kses_post( paginate_links( array(
							'base'      => add_query_arg( 'paged', '%#%' ),
							'format'    => '',
							'prev_text' => __( '&laquo;', 'smart-product-emails' ),
							'next_text' => __( '&raquo;', 'smart-product-emails' ),
							'total'     => $total_pages,
							'current'   => $current_page,
						) ) );
						?>
					</div>
				</div>
			<?php endif; ?>
		</div>

		<!-- Details Modal -->
		<div id="spe-log-details-modal" class="spe-modal" style="display: none;">
			<div class="spe-modal-content">
				<span class="spe-modal-close">&times;</span>
				<div id="spe-log-details-content"></div>
			</div>
		</div>
		<?php
	}

	/**
	 * AJAX: Clear all logs
	 */
	public function ajax_clear_logs() {
		check_ajax_referer( 'spe_error_log_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'smart-product-emails' ) ) );
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'spe_error_logs';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is constructed from known constant
		$wpdb->query( "TRUNCATE TABLE `{$table_name}`" );

		wp_send_json_success( array( 'message' => __( 'All logs cleared successfully.', 'smart-product-emails' ) ) );
	}

	/**
	 * AJAX: Export logs as CSV
	 */
	public function ajax_export_logs() {
		check_ajax_referer( 'spe_error_log_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Unauthorized', 'smart-product-emails' ) );
		}

		// Get all logs (no pagination)
		$logs = $this->logger->get_logs( array(
			'per_page' => 999999,
			'page'     => 1,
		) );

		// Set headers for CSV download
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- CSV headers, safe hardcoded values
		header( 'Content-Type: text/csv; charset=utf-8' );
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- CSV filename with sanitized date
		header( 'Content-Disposition: attachment; filename=spe-error-log-' . gmdate( 'Y-m-d-His' ) . '.csv' );

		// Open output stream
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- Streaming CSV output
		$output = fopen( 'php://output', 'w' );

		// Write CSV header
		fputcsv( $output, array(
			'ID',
			'Level',
			'Type',
			'Message',
			'File',
			'Line',
			'User ID',
			'Product ID',
			'Order ID',
			'Date',
		) );

		// Write data rows
		foreach ( $logs as $log ) {
			fputcsv( $output, array(
				$log->id,
				$log->log_level,
				$log->error_type,
				$log->message,
				$log->file,
				$log->line,
				$log->user_id,
				$log->product_id,
				$log->order_id,
				$log->created_at,
			) );
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- Streaming CSV output
		fclose( $output );
		exit;
	}

}
