<?php
/**
 * Script Class
 *
 * Handles the script and style functionality of plugin
 *
 * @package Product Categories Designs for WooCommerce
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Pcdfwoo_Script {

	function __construct() {

		// Action to add style in backend
		add_action( 'admin_enqueue_scripts', array( $this, 'pcdfwoo_admin_script_style' ) );

		// Action to add some style and script
		add_action( 'wp_enqueue_scripts', array( $this, 'pcdfwoo_front_style_script' ) );
	}

	/**
	 * Function to add script and style register
	 * 
	 * @since 1.4
	 */
	function pcdfwoo_register_admin_assets() {
		
		// Registring Admin Style
		wp_register_style( 'pcdfwoo-admin-css', PCDFWOO_URL.'assets/css/pcdfwoo-admin.css', array(), PCDFWOO_VERSION );

		// Registring Admin Script
		wp_register_script( 'pcdfwoo-admin-js', PCDFWOO_URL.'assets/js/pcdfwoo-admin.js', array('jquery'), PCDFWOO_VERSION, true );
	}

	/**
	 * Function to add script at admin side
	 * 
	 * @since 1.1
	 */
	function pcdfwoo_admin_script_style( $hook ) {

		$this->pcdfwoo_register_admin_assets();

		if( $hook == 'product_page_pcdfwoo-designs' ) {
			wp_enqueue_script('pcdfwoo-admin-js');
		}
	}

	/**
	 * Function to add style at front side
	 * 
	 * @since 1.0.0
	 */
	function pcdfwoo_front_style_script() {

		global $post;

		// Check elementor preview is there
		$elementor_preview = ( defined('ELEMENTOR_PLUGIN_BASE') && isset( $_GET['elementor-preview'] ) && isset( $post->ID ) && $post->ID == (int) $_GET['elementor-preview'] ) ? 1 : 0;

		/*** Style ***/
		// Registring and enqueing public css
		if( ! wp_style_is( 'wpos-slick-style', 'registered' ) ) {
			wp_register_style( 'wpos-slick-style', PCDFWOO_URL."assets/css/slick.css", array(), PCDFWOO_VERSION );
		}

		// Registring and enqueing public css
		wp_register_style( 'pcdfwoo-public-style', PCDFWOO_URL."assets/css/pcdfwoo-public.css", array(), PCDFWOO_VERSION );

		wp_enqueue_style( 'wpos-slick-style' );
		wp_enqueue_style( 'pcdfwoo-public-style' );


		/*** Script ***/
		// Registring slick slider script
		if( ! wp_script_is( 'wpos-slick-jquery', 'registered' ) ) {
			wp_register_script( 'wpos-slick-jquery', PCDFWOO_URL.'assets/js/slick.min.js', array('jquery'), PCDFWOO_VERSION, true );				
		}

		// Register Elementor script
		wp_register_script( 'pcdfwoo-elementor-js', PCDFWOO_URL.'assets/js/elementor/pcdfwoo-elementor.js', array('jquery'), PCDFWOO_VERSION, true );

		// Register Public script
		wp_register_script( 'pcdfwoo-public-jquery', PCDFWOO_URL.'assets/js/pcdfwoo-public.js', array('jquery'), PCDFWOO_VERSION, true );
		wp_localize_script( 'pcdfwoo-public-jquery', 'Pcdfwoo', array(
															'elementor_preview'	=> $elementor_preview,
															'is_mobile' 		=> ( wp_is_mobile() )					? 1 : 0,
															'is_avada'			=> (class_exists( 'FusionBuilder' ))	? 1 : 0,
														));

		// Enqueue Script for Elementor Preview
		if ( $elementor_preview ) {

			wp_enqueue_script( 'wpos-slick-jquery' );
			wp_enqueue_script( 'pcdfwoo-public-jquery' );
			wp_enqueue_script( 'pcdfwoo-elementor-js' );
		}

		// Enqueue Style & Script for Beaver Builder
		if ( class_exists( 'FLBuilderModel' ) && FLBuilderModel::is_builder_active() ) {

			$this->pcdfwoo_register_admin_assets();
			
			wp_enqueue_script( 'pcdfwoo-admin-js' );
			wp_enqueue_script( 'wpos-slick-jquery' );
			wp_enqueue_script( 'pcdfwoo-public-jquery' );
		}

		// Enqueue Admin Style & Script for Divi Page Builder
		if( function_exists( 'et_core_is_fb_enabled' ) && isset( $_GET['et_fb'] ) && $_GET['et_fb'] == 1 ) {
			$this->pcdfwoo_register_admin_assets();

			wp_enqueue_style( 'pcdfwoo-admin-css');
		}

		// Enqueue Admin Style for Fusion Page Builder
		if( class_exists( 'FusionBuilder' ) && (( isset( $_GET['builder'] ) && $_GET['builder'] == 'true' ) ) ) {
			$this->pcdfwoo_register_admin_assets();

			wp_enqueue_style( 'pcdfwoo-admin-css');
			wp_enqueue_script( 'pcdfwoo-admin-js' );
		}
	}
}

$pcdfwoo_script = new Pcdfwoo_Script();