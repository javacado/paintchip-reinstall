<?php
/**
 * Plugin Name: Product Categories Designs for WooCommerce
 * Plugin URI: https://www.essentialplugin.com/wordpress-plugin/product-categories-designs-woocommerce/
 * Description: Display WooCommerce product categories designs with grid and silder view. Also work with Gutenberg shortcode block.
 * Author: Essential Plugin
 * Author URI: http://www.essentialplugin.com/
 * Text Domain: product-categories-designs-for-woocommerce
 * Domain Path: /languages/
 * Requires Plugins: woocommerce
 * WC tested up to: 9.8.5
 * Version: 1.5.2
 * WC requires at least: 3.0
 *
 * @package Product Categories Designs for WooCommerce
 * @author Essential Plugin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if( ! defined( 'PCDFWOO_VERSION' ) ) {
	define( 'PCDFWOO_VERSION', '1.5.2' ); // Version of plugin
}
if( ! defined( 'PCDFWOO_NAME' ) ) {
	define( 'PCDFWOO_NAME', 'Product Categories Designs for WooCommerce' ); // Name of plugin
}
if( ! defined( 'PCDFWOO_DIR' ) ) {
	define( 'PCDFWOO_DIR', dirname( __FILE__ ) ); // Plugin dir
}
if( ! defined( 'PCDFWOO_URL' ) ) {
	define( 'PCDFWOO_URL', plugin_dir_url( __FILE__ ) ); // Plugin url
}
if( ! defined( 'PCDFWOO_PRODUCT_POST_TYPE' ) ) {
	define( 'PCDFWOO_PRODUCT_POST_TYPE', 'product' ); // Plugin category name
}
if( ! defined( 'PCDFWOO_PLUGIN_LINK' ) ) {
	define( 'PCDFWOO_PLUGIN_LINK', 'https://www.essentialplugin.com/wordpress-plugin/product-categories-designs-woocommerce/?utm_source=WP&utm_medium=Product-Category&utm_campaign=Features-PRO' ); // Plugin Category
}
if( ! defined( 'PCDFWOO_SITE_LINK' ) ) {
	define( 'PCDFWOO_SITE_LINK', 'https://www.essentialplugin.com' ); // Plugin link
}

function pcdfwoo_load_textdomain() {

	global $wp_version;

	// Set filter for plugin's languages directory
	$wp_pcdfwoo_lang_dir = dirname( plugin_basename( __FILE__ ) ) . '/languages/';
	$wp_pcdfwoo_lang_dir = apply_filters( 'wp_pcdfwoo_languages_directory', $wp_pcdfwoo_lang_dir );

	// Traditional WordPress plugin locale filter.
	$get_locale = get_locale();

	if ( $wp_version >= 4.7 ) {
		$get_locale = get_user_locale();
	}

	// Traditional WordPress plugin locale filter
	$locale = apply_filters( 'plugin_locale',  $get_locale, 'product-categories-designs-for-woocommerce' );
	$mofile = sprintf( '%1$s-%2$s.mo', 'product-categories-designs-for-woocommerce', $locale );

	// Setup paths to current locale file
	$mofile_global  = WP_LANG_DIR . '/plugins/' . basename( PCDFWOO_DIR ) . '/' . $mofile;

	if ( file_exists( $mofile_global ) ) { // Look in global /wp-content/languages/plugin-name folder
		load_textdomain( 'product-categories-designs-for-woocommerce', $mofile_global );
	} else { // Load the default language files
		load_plugin_textdomain( 'product-categories-designs-for-woocommerce', false, $wp_pcdfwoo_lang_dir );
	}
}

/**
 * Action for declare compatibility HPOS custom order.
 * 
 * @since 1.5
 */
add_action('before_woocommerce_init', function(){
    if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
    }
});

/**
 * Activation Hook
 * Register plugin activation hook.
 * 
 * @since 1.1
 */
register_activation_hook( __FILE__, 'pcdfwoo_install' );

/**
 * Plugin Setup On Activation
 * Does the initial setup, set default values for the plugin options.
 * 
 * @since 1.1
 */
function pcdfwoo_install() {

	/**
	 * Load plugin necessary files as this is add-on
	 */
	pcdfwoo_load_plugin();

	// Deactivate free version
	if( is_plugin_active( 'product-categories-designs-for-woo-pro/product-categories-designs-for-woo-pro.php' ) ) {
		add_action( 'update_option_active_plugins', 'pcdfwoo_deactivate_free_version' );
	}
}

/**
 * Deactivate premium plugin
 * 
 * @since 1.1
 */
function pcdfwoo_deactivate_free_version() {
	deactivate_plugins( 'product-categories-designs-for-woo-pro/product-categories-designs-for-woo-pro.php', true );
}

/**
 * Admin notices to activate WooCommerce plugin
 * 
 * @since 1.0
 */
function pcdfwoo_admin_notices() {

	if ( ! class_exists('WooCommerce') ) {

		echo '<div class="error notice is-dismissible">
				<p><strong>Woo Product Slider and Carousel with Category</strong> '.esc_html__(' recommends the following plugin to use.', 'product-categories-designs-for-woocommerce').'</p>
				<p><strong><a href="https://wordpress.org/plugins/woocommerce/" target="_blank">WooCommerce</a></strong></p>
			 </div>';
	}
}
add_action( 'admin_notices', 'pcdfwoo_admin_notices' );

/**
 * Function to display admin notice of activated plugin.
 * 
 * @since 1.1
 */
function pcdfwoo_plugin_exist_notice() {

	global $pagenow;

	// If not plugin screen
	if( 'plugins.php' != $pagenow ) {
		return;
	}

	// Check Lite Version
	$dir = WP_PLUGIN_DIR . '/product-categories-designs-for-woo-pro/product-categories-designs-for-woo-pro.php';

	if( ! file_exists( $dir ) ) {
		return;
	}

	$notice_link		= add_query_arg( array('message' => 'pcdfwoo-plugin-notice'), admin_url('plugins.php') );
	$notice_transient	= get_transient( 'pcdfwoo_install_notice' );

	// If PRO plugin is active and free plugin exist
	if( $notice_transient == false && current_user_can( 'install_plugins' ) ) {

		echo '<div class="updated notice" style="position:relative;">
					<p>
						<strong>'.sprintf( __( 'Thank you for activating %s', 'product-categories-designs-for-woocommerce' ), 'Product Categories Designs for WooCommerce').'</strong>.<br/>
						'.sprintf( __('It looks like you had PRO version %s of this plugin activated. To avoid conflicts the extra version has been deactivated and we recommend you delete it.', 'product-categories-designs-for-woocommerce'), '<strong>(<em>Product Categories Designs Pro for WooCommerce</em>)</strong>' ).'
					</p>
					<a href="'.esc_url( $notice_link ).'" class="notice-dismiss" style="text-decoration:none;"></a>
				</div>';
	}
}

/**
 * Load the plugin after the main plugin is loaded.
 * 
 * @since 1.0.0
 */
function pcdfwoo_load_plugin() {

	pcdfwoo_load_textdomain();

	// Check main plugin is active or not
	if( class_exists('WooCommerce') ) {

		// Action to display notice
		add_action( 'admin_notices', 'pcdfwoo_plugin_exist_notice' );

		// Function File
		require_once( PCDFWOO_DIR . '/includes/pcdfwoo-functions.php' );

		// Admin Class
		require_once( PCDFWOO_DIR . '/includes/admin/class-pcdfwoo-admin.php' );

		// Script Class
		require_once( PCDFWOO_DIR . '/includes/class-pcdfwoo-script.php' );

		// Including shortcode files
		require_once( PCDFWOO_DIR . '/includes/shortcode/pcdfwoo-shortcode.php' );
		require_once( PCDFWOO_DIR . '/includes/shortcode/pcdfwoo-slider-shortcode.php' );

		// Gutenberg Block Initializer
		if ( function_exists( 'register_block_type' ) ) {
			require_once( PCDFWOO_DIR . '/includes/admin/supports/gutenberg-block.php' );
		}

		// How it work file, Load admin files
		if ( is_admin() || ( defined( 'WP_CLI' ) && WP_CLI ) ) {
			require_once( PCDFWOO_DIR . '/includes/admin/pcdfwoo-how-it-work.php' );
		}
	}
}

// Action to load plugin after the main plugin is loaded
add_action( 'plugins_loaded', 'pcdfwoo_load_plugin', 15 );