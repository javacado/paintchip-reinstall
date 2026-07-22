<?php
/**
 * Admin Class
 *
 * Handles the Admin side functionality of plugin
 *
 * @package Product Categories Designs for WooCommerce
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class PcdfWoo_Admin {

	function __construct() {

		// Action to register plugin settings
		add_action ( 'admin_init', array( $this, 'pcdfwoo_admin_processes' ) );
	}

	/**
	 * Function register setings
	 *
	 * @since 1.3
	 */
	function pcdfwoo_admin_processes() {

		// If plugin notice is dismissed
		if( isset($_GET['message']) && 'pcdfwoo-plugin-notice' == $_GET['message'] ) {
			set_transient( 'pcdfwoo_install_notice', true, 604800 );
		}
	}
}

$pcdfwoo_admin = new PcdfWoo_Admin();