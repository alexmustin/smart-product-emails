<?php
/**
 * Import from Woo Custom Emails - admin wizard page.
 *
 * A five-step, user-paced wizard (Discovery, Review, Configure, Confirm &
 * Import, Report). Nothing is written to the database until the user
 * reaches step 4 and explicitly clicks "Start Import".
 *
 * @package SmartProductEmails
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Import from Woo Custom Emails - admin wizard page.
 */
class SmartProductEmails_Import_Admin {

	/**
	 * Read-only scan/inventory builder.
	 *
	 * @var SmartProductEmails_Import_Scanner
	 */
	private $scanner;

	/**
	 * Performs the actual writes for a batch.
	 *
	 * @var SmartProductEmails_Import_Engine
	 */
	private $engine;

	/**
	 * Persistent server-side job state.
	 *
	 * @var SmartProductEmails_Import_Job
	 */
	private $job;

	/**
	 * Wires up the wizard's admin menu, assets, and AJAX handlers.
	 */
	public function __construct() {
		$tracker       = new SmartProductEmails_Import_Tracker();
		$this->scanner = new SmartProductEmails_Import_Scanner( $tracker );
		$this->engine  = new SmartProductEmails_Import_Engine( $tracker );
		$this->job     = new SmartProductEmails_Import_Job();

		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		add_action( 'wp_ajax_smartproductemails_import_scan', array( $this, 'ajax_scan' ) );
		add_action( 'wp_ajax_smartproductemails_import_run_batch', array( $this, 'ajax_run_batch' ) );
		add_action( 'wp_ajax_smartproductemails_import_cancel', array( $this, 'ajax_cancel_job' ) );
		add_action( 'wp_ajax_smartproductemails_import_deactivate_source', array( $this, 'ajax_deactivate_source' ) );

		// Free's default: only Processing is reported as "Active" (importable
		// on-hold/completed data still gets written, but reported inactive
		// until PRO is installed and licensed - see SPE_Pro_Import_Order_Statuses).
		add_filter( 'smartproductemails_import_status_is_active', array( $this, 'default_status_is_active' ), 10, 2 );
	}

	/**
	 * Free's default handler for the status-active reporting filter.
	 *
	 * @param bool   $is_active Current value.
	 * @param string $status Status slug.
	 * @return bool
	 */
	public function default_status_is_active( $is_active, $status ) {
		return 'processing' === $status ? true : $is_active;
	}

	/**
	 * Register the Import submenu page.
	 */
	public function add_admin_menu() {
		add_submenu_page(
			'edit.php?post_type=smartproductemails',
			__( 'Import', 'smart-product-emails' ),
			__( 'Import', 'smart-product-emails' ),
			'manage_options',
			'smartproductemails-import',
			array( $this, 'render_page' )
		);
	}

	/**
	 * Enqueue the wizard's script/style, only on its own admin page.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_scripts( $hook ) {
		if ( 'smartproductemails_page_smartproductemails-import' !== $hook ) {
			return;
		}

		wp_enqueue_style(
			'smartproductemails-import',
			SMARTPRODUCTEMAILS_PLUGIN_URL . 'admin/css/spe-import.css',
			array(),
			SMARTPRODUCTEMAILS_PLUGIN_VERSION
		);

		wp_enqueue_script(
			'smartproductemails-import',
			SMARTPRODUCTEMAILS_PLUGIN_URL . 'admin/js/spe-import.js',
			array( 'jquery' ),
			SMARTPRODUCTEMAILS_PLUGIN_VERSION,
			true
		);

		wp_localize_script(
			'smartproductemails-import',
			'smartproductemailsImport',
			array(
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'smartproductemails_import_nonce' ),
				'urls'    => array(
					'proUpgrade' => 'https://smartproductemails.com/pro/',
					'proLicense' => admin_url( 'edit.php?post_type=smartproductemails&page=spe-pro-license' ),
				),
				'i18n'    => array(
					'scanFailed'            => __( 'Something went wrong while scanning for Woo Custom Emails data. Please reload the page and try again.', 'smart-product-emails' ),
					'batchFailed'           => __( 'Something went wrong during the import. You can safely resume - nothing already imported was lost.', 'smart-product-emails' ),
					'confirmCancel'         => __( 'Stop the import after the current batch finishes? You can resume later without losing progress.', 'smart-product-emails' ),
					'confirmDeactivate'     => __( 'Deactivate Woo Custom Emails now? Its data will not be deleted.', 'smart-product-emails' ),
					'adminEmailTooltip'     => __( "Admin order emails don't expose the same content hooks as customer emails, so this message could never reliably display there.", 'smart-product-emails' ),
					'displayClassesTooltip' => __( 'These control which additional WooCommerce product types (like Bookings) show the Smart Product Emails tab - the same idea as the Custom Statuses page, but for product types instead of order statuses. There is no settings screen for this yet, so the value is merged in automatically.', 'smart-product-emails' ),
				),
			)
		);
	}

	/**
	 * Render the wizard page shell. All step content is populated by JS
	 * from the scan/batch AJAX responses.
	 */
	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'smart-product-emails' ) );
		}
		?>
		<div class="wrap smartproductemails-import-wrap">
			<h1><?php esc_html_e( 'Import from Woo Custom Emails', 'smart-product-emails' ); ?></h1>
			<p class="description">
				<?php esc_html_e( 'Bring your Custom Emails and settings from Woo Custom Emails into Smart Product Emails. The old plugin\'s data is never modified - this only reads from it.', 'smart-product-emails' ); ?>
			</p>

			<div class="spe-import-steps">
				<span class="spe-import-step-indicator" data-step="1"><?php esc_html_e( '1. Discovery', 'smart-product-emails' ); ?></span>
				<span class="spe-import-step-indicator" data-step="2"><?php esc_html_e( '2. Review', 'smart-product-emails' ); ?></span>
				<span class="spe-import-step-indicator" data-step="3"><?php esc_html_e( '3. Configure', 'smart-product-emails' ); ?></span>
				<span class="spe-import-step-indicator" data-step="4"><?php esc_html_e( '4. Import', 'smart-product-emails' ); ?></span>
				<span class="spe-import-step-indicator" data-step="5"><?php esc_html_e( '5. Report', 'smart-product-emails' ); ?></span>
			</div>

			<div id="spe-import-step-1" class="spe-import-step">
				<p class="spe-import-loading"><?php esc_html_e( 'Scanning for Woo Custom Emails data…', 'smart-product-emails' ); ?></p>
			</div>

			<div id="spe-import-step-2" class="spe-import-step" style="display:none;"></div>
			<div id="spe-import-step-3" class="spe-import-step" style="display:none;"></div>
			<div id="spe-import-step-4" class="spe-import-step" style="display:none;"></div>
			<div id="spe-import-step-5" class="spe-import-step" style="display:none;"></div>
		</div>
		<?php
	}

	/**
	 * AJAX: run the read-only scan and return inventory counts + wizard state.
	 */
	public function ajax_scan() {
		check_ajax_referer( 'smartproductemails_import_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'smart-product-emails' ) ) );
		}

		if ( ! $this->scanner->source_data_exists() ) {
			wp_send_json_success(
				array(
					'exists' => false,
					'job'    => $this->job->get(),
				)
			);
		}

		$inventory = $this->scanner->build_inventory();

		wp_send_json_success(
			array(
				'exists'          => true,
				'counts'          => $inventory['counts'],
				'settings'        => $inventory['settings'],
				'conflicts'       => $this->scanner->describe_conflicts( $inventory ),
				'status_active'   => $this->get_status_active_map(),
				'pro'             => $this->get_pro_state(),
				'source_active'   => $this->is_source_plugin_active(),
				'job'             => $this->job->get(),
				'admin_email_tip' => __( "Admin order emails don't expose the same content hooks as customer emails, so this message could never reliably display there.", 'smart-product-emails' ),
			)
		);
	}

	/**
	 * AJAX: process the next batch of pending work items. Because the
	 * scanner recomputes pending items live on every call (already-completed
	 * items disappear once marked), this doubles as the resume mechanism -
	 * there is no stored cursor to get out of sync.
	 */
	public function ajax_run_batch() {
		check_ajax_referer( 'smartproductemails_import_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'smart-product-emails' ) ) );
		}

		$job = $this->job->get();

		if ( ! $job || 'running' !== $job['status'] ) {
			$conflict_strategy = isset( $_POST['conflict_strategy'] ) && 'skip' === $_POST['conflict_strategy'] ? 'skip' : 'overwrite';

			$inventory = $this->scanner->build_inventory();
			$pending   = $this->scanner->get_pending_work_items( $inventory, $conflict_strategy );

			$this->engine->migrate_settings( $inventory['settings'] );
			$job = $this->job->start( $conflict_strategy, count( $pending ) );
		}

		$inventory = $this->scanner->build_inventory();
		$pending   = $this->scanner->get_pending_work_items( $inventory, $job['conflict_strategy'] );

		if ( empty( $pending ) ) {
			$job = $this->job->complete();
			wp_send_json_success(
				array(
					'done' => true,
					'job'  => $job,
					'log'  => array(),
				)
			);
		}

		$batch_size = apply_filters( 'smartproductemails_import_batch_size', 20 );
		$batch      = array_slice( $pending, 0, $batch_size );

		$result = $this->engine->process_batch( $batch, $job['conflict_strategy'] );
		$job    = $this->job->record_batch( $result['totals'], count( $batch ) );

		$remaining = count( $pending ) - count( $batch );

		if ( $remaining <= 0 ) {
			$job = $this->job->complete();
		}

		wp_send_json_success(
			array(
				'done' => $remaining <= 0,
				'job'  => $job,
				'log'  => $result['log'],
			)
		);
	}

	/**
	 * AJAX: cancel the running job after the current in-flight batch (the
	 * JS controller stops calling run_batch; this just flips job status so
	 * a page reload shows "cancelled" rather than "abandoned").
	 */
	public function ajax_cancel_job() {
		check_ajax_referer( 'smartproductemails_import_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'smart-product-emails' ) ) );
		}

		wp_send_json_success( array( 'job' => $this->job->cancel() ) );
	}

	/**
	 * AJAX: deactivate (never delete) the Woo Custom Emails plugin.
	 */
	public function ajax_deactivate_source() {
		check_ajax_referer( 'smartproductemails_import_nonce', 'nonce' );

		if ( ! current_user_can( 'activate_plugins' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'smart-product-emails' ) ) );
		}

		$plugin_file = $this->get_source_plugin_basename();

		if ( $plugin_file ) {
			deactivate_plugins( $plugin_file );
		}

		wp_send_json_success( array( 'deactivated' => true ) );
	}

	/**
	 * Build the per-status "is this active for reporting" map.
	 *
	 * @return array Status slug => whether the report should badge it "Active".
	 */
	private function get_status_active_map() {
		$map = array();
		foreach ( array( 'processing', 'onhold', 'completed' ) as $status ) {
			$map[ $status ] = apply_filters( 'smartproductemails_import_status_is_active', false, $status );
		}
		return $map;
	}

	/**
	 * Get the current PRO install/license state.
	 *
	 * @return array {
	 *     @type bool $installed Whether the PRO plugin is installed/loaded.
	 *     @type bool $licensed Whether PRO's license is active.
	 * }
	 */
	private function get_pro_state() {
		return array(
			'installed' => function_exists( 'spe_pro_is_installed' ) && spe_pro_is_installed(),
			'licensed'  => function_exists( 'spe_pro_is_license_active' ) && spe_pro_is_license_active(),
		);
	}

	/**
	 * Find the Woo Custom Emails plugin's basename among installed plugins.
	 *
	 * @return string|false Plugin basename (e.g. 'woocustomemails/woo-custom-emails-per-product.php') or false if not found.
	 */
	private function get_source_plugin_basename() {
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		foreach ( array_keys( get_plugins() ) as $plugin_file ) {
			if ( 0 === strpos( $plugin_file, 'woocustomemails/' ) ) {
				return $plugin_file;
			}
		}

		return false;
	}

	/**
	 * Check whether the Woo Custom Emails plugin is currently active.
	 *
	 * @return bool Whether the Woo Custom Emails plugin is currently active.
	 */
	private function is_source_plugin_active() {
		$plugin_file = $this->get_source_plugin_basename();

		if ( ! $plugin_file ) {
			return false;
		}

		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		return is_plugin_active( $plugin_file );
	}
}
