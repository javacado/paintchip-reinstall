<?php
/**
 * Plugin generic functions file
 *
 * @package Product Categories Designs for WooCommerce
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Function to unique number value
 * 
 * @since 1.2.5
 */
function pcdfwoo_get_unique() {
	static $unique = 0;
	$unique++;

	// For Elementor & Beaver Builder
	if( ( defined('ELEMENTOR_PLUGIN_BASE') && isset( $_POST['action'] ) && $_POST['action'] == 'elementor_ajax' )
	|| ( class_exists('FLBuilderModel') && ! empty( $_POST['fl_builder_data']['action'] ) )
	|| ( function_exists('vc_is_inline') && vc_is_inline() ) ) {
		$unique = current_time('timestamp') . '-' . rand();
	}

	return $unique;
}

/**
 * Sanitize number value and return fallback value if it is blank
 * 
 * @since 1.1
 */
function pcdfwoo_clean_number( $var, $fallback = null, $type = 'int' ) {

	$var = trim( $var );
	$var = is_numeric( $var ) ? $var : 0;

	if ( $type == 'number' ) {
		$data = intval( $var );
	} else if ( $type == 'abs' ) {
		$data = abs( $var );
	} else if ( $type == 'float' ) {
		$data = (float)$var;
	} else {
		$data = absint( $var );
	}

	return ( empty($data) && isset($fallback) ) ? $fallback : $data;
}

/**
 * Sanitize Multiple HTML class
 * 
 * @since 1.2.6
 */
function pcdfwoo_sanitize_html_classes($classes, $sep = " ") {
	$return = "";

	if( !is_array($classes) ) {
		$classes = explode($sep, $classes);
	}

	if( ! empty( $classes ) ) {
		foreach($classes as $class){
			$return .= sanitize_html_class($class) . " ";
		}
		$return = trim( $return );
	}

	return $return;
}