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
	 * Creates a new custom post type.
	 *
	 * @access public
	 * @uses register_post_type()
	 */
	public static function new_cpt_speemails() {

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
