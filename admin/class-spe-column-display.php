<?php
/**
 * Adds custom columns to the SPE Messages page.
 *
 * @package SmartProductEmails
 */

/**
 * Smart_Product_Emails_Column_Display is a class for displaying extra table columns on the SPE Messages page.
 */
class Smart_Product_Emails_Column_Display {

	/**
	 * Adds new columns to the page.
	 */
	public function __construct() {
		add_filter( 'manage_smartproductemails_posts_columns', array( &$this, 'set_custom_edit_smartproductemails_columns' ) );
		add_action( 'manage_smartproductemails_posts_custom_column', array( &$this, 'custom_smartproductemails_column' ), 10, 2 );
	}

	/**
	 * Adds an "ID" column to the SPE Messages list.
	 *
	 * @param object $columns The column object to be modified.
	 */
	public function set_custom_edit_smartproductemails_columns( $columns ) {

		// Remove the Title and Date columns now, add them back later.
		unset( $columns['title'] );
		unset( $columns['date'] );

		// Add new "ID" column.
		$columns['messageid'] = __( 'ID', 'smart_product_emails_domain' );

		// Add the Title and Date columns back in.
		$columns['title'] = __( 'Title', 'smart_product_emails_domain' );
		$columns['date']  = __( 'Date', 'smart_product_emails_domain' );

		return $columns;
	}

	/**
	 * Displays the "ID" column data.
	 *
	 * @param object $column The column object to be modified.
	 * @param string $post_id The ID of the post to be output.
	 */
	public function custom_smartproductemails_column( $column, $post_id ) {
		if ( 'messageid' === $column ) {
			$id = $post_id;
			echo esc_attr( $id );
		}
	}

}

// Initialize the Class.
add_action(
	'plugins_loaded',
	function() {
		new Smart_Product_Emails_Column_Display();
	}
);
