<?php
/**
 * Main constructor file.
 *
 * @package SmartProductEmails
 */

/**
 * The Smart_Product_Emails class handles the structure of the plugin.
 */
class Smart_Product_Emails {

	/**
	 * Loader var.
	 *
	 * @var object $loader Object to track what to load.
	 */
	protected $loader;

	/**
	 * Slug.
	 *
	 * @var string $plugin_slug The slug of this plugin.
	 */
	protected $plugin_slug;

	/**
	 * Plugin version.
	 *
	 * @var string $version The version of this plugin.
	 */
	protected $version;

	/**
	 * The Class constructor.
	 */
	public function __construct() {

		$this->plugin_slug = 'smart_product_emails_domain';
		$this->version     = SPE_PLUGIN_VERSION;

		$this->smart_product_emails_load_dependencies();
		$this->smart_product_emails_define_admin_hooks();

	}

	/**
	 * Loads the required files.
	 *
	 * @return void
	 */
	private function smart_product_emails_load_dependencies() {

		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-spe-product-data-admin.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-spe-loader.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-spe-hooks.php';
		$this->loader = new Smart_Product_Emails_Loader();

	}

	/**
	 * Setup the Admin Hooks
	 *
	 * @return void
	 */
	private function smart_product_emails_define_admin_hooks() {
		$spe_product_data_admin = new SPE_Product_Data_Admin( $this->get_version() );

		$this->loader->add_action( 'admin_head-post.php', $spe_product_data_admin, 'spe_custom_admin_style' );
		$this->loader->add_action( 'admin_head-post-new.php', $spe_product_data_admin, 'spe_custom_admin_style' );
		$this->loader->add_action( 'admin_enqueue_scripts', $spe_product_data_admin, 'spe_enqueue_custom_admin_style' );
		$this->loader->add_action( 'woocommerce_product_data_tabs', $spe_product_data_admin, 'add_smart_product_emails_tab' );
		$this->loader->add_action( 'woocommerce_product_data_panels', $spe_product_data_admin, 'add_smart_product_emails_tab_fields' );
		$this->loader->add_action( 'woocommerce_process_product_meta', $spe_product_data_admin, 'save_smart_product_emails_tab_fields' );

		// Add AJAX Fetch JS to footer.
		$this->loader->add_action( 'admin_footer', $spe_product_data_admin, 'ajax_spe_fetch_script' );

		// Add AJAX Fetch Function.
		$this->loader->add_action( 'wp_ajax_spe_data_fetch', $spe_product_data_admin, 'spe_data_fetch' );
		$this->loader->add_action( 'wp_ajax_nopriv_spe_data_fetch', $spe_product_data_admin, 'spe_data_fetch' );

	}

	/**
	 * Get the plugin version.
	 *
	 * @return string $version The plugin version.
	 */
	public function get_version() {
		return $this->version;
	}

	/**
	 * Run everything.
	 *
	 * @return void
	 */
	public function run() {
		$this->loader->run();

		// Initialize extensibility hooks and notify extensions that Free version is loaded
		SPE_Hooks::init();
	}

}
