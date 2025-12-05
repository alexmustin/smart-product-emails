<?php
/**
 * Creates the Custom Post Type required for the plugin.
 *
 * @package SmartProductEmails
 */

/**
 * Smart_Product_Emails_CPT is a class for creating the 'smartproductemails' Custom Post Type.
 */
class Smart_Product_Emails_CPT {

	/**
	 * Constructor - Set up hooks
	 */
	public function __construct() {
		add_action( 'admin_notices', array( $this, 'spe_woocommerce_missing_notice' ) );
	}

	/**
	 * Display admin notice if WooCommerce is not active.
	 */
	public function spe_woocommerce_missing_notice() {
		// Only show on SPE Message CPT screens (list, create, edit) - not settings page
		$screen = get_current_screen();
		if ( ! $screen || $screen->post_type !== 'smartproductemails' || ! in_array( $screen->base, array( 'post', 'edit' ), true ) ) {
			return;
		}

		// Check if WooCommerce is active
		if ( ! $this->is_woocommerce_active() ) {
			$woo_plugin_url = esc_url( 'https://wordpress.org/plugins/woocommerce/' );
			?>
			<div class="notice notice-error">
				<p>
					<strong><?php esc_html_e( 'WooCommerce Required', 'smart_product_emails_domain' ); ?></strong>
				</p>
				<p>
					<?php
					printf(
						/* translators: %1$s: opening link tag, %2$s: closing link tag */
						esc_html__( 'Smart Product Emails requires %1$sWooCommerce%2$s to be installed and activated. Please install and activate WooCommerce to use this plugin.', 'smart_product_emails_domain' ),
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

		// Fallback: Check if WooCommerce plugin is in active plugins list
		return in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ), true );
	}

	/**
	 * Creates a new custom post type.
	 *
	 * @access public
	 * @uses register_post_type()
	 */
	public function new_cpt_speemails() {

		$cap_type = 'post';
		$plural   = 'Smart Product Emails';
		$single   = 'Smart Product Email';
		$cpt_name = 'smartproductemails';

		$opts['can_export']           = true;
		$opts['capability_type']      = $cap_type;
		$opts['description']          = '';
		$opts['exclude_from_search']  = true;
		$opts['has_archive']          = true;
		$opts['hierarchical']         = false;
		$opts['map_meta_cap']         = true;
		$opts['menu_icon']            = 'dashicons-email-alt';
		$opts['menu_position']        = 50;
		$opts['public']               = false;
		$opts['publicly_querable']    = false;
		$opts['register_meta_box_cb'] = '';
		$opts['rewrite']              = false;
		$opts['show_in_admin_bar']    = true;
		$opts['show_in_menu']         = true;
		$opts['show_in_nav_menu']     = true;
		$opts['show_ui']              = true;
		$opts['supports']             = array( 'title', 'editor', 'revisions' );
		$opts['taxonomies']           = array();

		$opts['capabilities']['delete_others_posts']    = "delete_others_{$cap_type}s";
		$opts['capabilities']['delete_post']            = "delete_{$cap_type}";
		$opts['capabilities']['delete_posts']           = "delete_{$cap_type}s";
		$opts['capabilities']['delete_private_posts']   = "delete_private_{$cap_type}s";
		$opts['capabilities']['delete_published_posts'] = "delete_published_{$cap_type}s";
		$opts['capabilities']['edit_others_posts']      = "edit_others_{$cap_type}s";
		$opts['capabilities']['edit_post']              = "edit_{$cap_type}";
		$opts['capabilities']['edit_posts']             = "edit_{$cap_type}s";
		$opts['capabilities']['edit_private_posts']     = "edit_private_{$cap_type}s";
		$opts['capabilities']['edit_published_posts']   = "edit_published_{$cap_type}s";
		$opts['capabilities']['publish_posts']          = "publish_{$cap_type}s";
		$opts['capabilities']['read_post']              = "read_{$cap_type}";
		$opts['capabilities']['read_private_posts']     = "read_private_{$cap_type}s";

		$opts['labels']['add_new']            = 'Add New SPE Message';
		$opts['labels']['add_new_item']       = 'Add New SPE Message';
		$opts['labels']['all_items']          = 'SPE Messages';
		$opts['labels']['edit_item']          = 'Edit SPE Message';
		$opts['labels']['menu_name']          = 'SPE Messages';
		$opts['labels']['name']               = 'SPE Messages';
		$opts['labels']['name_admin_bar']     = 'SPE Message';
		$opts['labels']['new_item']           = 'New SPE Message';
		$opts['labels']['not_found']          = 'No SPE Messages Found';
		$opts['labels']['not_found_in_trash'] = 'No SPE Messages Found in Trash';
		$opts['labels']['parent_item_colon']  = 'Parent SPE Messages:';
		$opts['labels']['search_items']       = 'Search SPE Messages';
		$opts['labels']['singular_name']      = 'SPE Message';
		$opts['labels']['view_item']          = 'View SPE Message';

		$opts = apply_filters( 'smartproductemails-cpt-options', $opts );

		register_post_type( strtolower( $cpt_name ), $opts );

	}

}

// Initialize the Class.
add_action(
	'plugins_loaded',
	function() {
		$spe_cpt = new Smart_Product_Emails_CPT();
		$spe_cpt->new_cpt_speemails();
	}
);
