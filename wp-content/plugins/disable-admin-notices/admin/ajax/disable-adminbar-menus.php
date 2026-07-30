<?php
/**
 * Ajax action to disabled adminbar menus.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Disbaled adminbar items.
 */
function dan_ajax_disabled_adminbar_menus() {
	$menu_ID     = WDN_Plugin::app()->request->post( 'menu_id', null, 'sanitize_key' );
	$enable_menu = WDN_Plugin::app()->request->post( 'enable_menu', "false" ) === "true";

	check_admin_referer( 'enable_adminbar_item_' . $menu_ID );

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( - 1 );
	}

	$items = WDN_Plugin::app()->getPopulateOption( 'hidden_adminbar_items', [] );

	if ( ! $enable_menu ) {
		if ( ! isset( $items[ $menu_ID ] ) ) {
			$items[ $menu_ID ] = true;
		}
	} else {
		if ( isset( $items[ $menu_ID ] ) ) {
			unset( $items[ $menu_ID ] );
		}
	}

	WDN_Plugin::app()->updatePopulateOption( 'hidden_adminbar_items', $items );

	wp_send_json_success( [ 'success_message' => __( 'Settings successfully saved!', 'disable-admin-notices' ) ] );
}

add_action( 'wp_ajax_wdan-disable-adminbar-menus', 'dan_ajax_disabled_adminbar_menus');
