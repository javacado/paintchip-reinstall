<?php
/**
 * Plugin Name: TCP Update Inventory Menu
 * Description: Adds an "Update Inventory" link under the WooCommerce Products menu in wp-admin.
 * Version: 1.0.0
 * Author: The Paint Chip
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Add "Update Inventory" submenu under Products.
 */
function tcp_add_update_inventory_menu() {
	add_submenu_page(
		'edit.php?post_type=product',
		'Update Inventory',
		'Update Inventory',
		'manage_woocommerce',
		'tcp-update-inventory',
		'tcp_update_inventory_menu_page'
	);
}
add_action( 'admin_menu', 'tcp_add_update_inventory_menu', 99 );

/**
 * Redirect the submenu page to /helper.
 */
function tcp_update_inventory_menu_page() {
	wp_safe_redirect( home_url( '/helper' ) );
	exit;
}
