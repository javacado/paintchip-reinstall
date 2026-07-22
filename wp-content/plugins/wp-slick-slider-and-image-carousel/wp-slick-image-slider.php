<?php
/**
 * Plugin Name: WP Slick Slider and Image Carousel
 * Plugin URI: https://essentialplugin.com/wordpress-plugins/wp-slick-slider-and-image-carousel/
 * Text Domain: wp-slick-slider-and-image-carousel
 * Domain Path: /languages/
 * Description: Easy to add and display wp slick image slider and carousel. Also added Gutenberg block support.
 * Author: Essential Plugin
 * Version: 3.7.8.2
 *
 * @package WP Slick Slider and Image Carousel
 * @author Essential Plugin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}


/**
 * Added by the WordPress.org Plugins Review team in response to an incident.
 * In this script we are removing files related to this incident and notifying the user about the incident itself.
 */
function essentialplugin_71313_wpsisac_prt_incidence_response_notice() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$user_id = get_current_user_id();
	if ( get_user_meta( $user_id, 'essentialplugin_71313_wpsisac_prt_notice_dismissed', true ) ) {
		return;
	}
	?>
	<div class="notice notice-warning is-dismissible" id="essentialplugin-wpsisac-prt-notice">
		<h3><?php esc_html_e( 'Important Notice from the WordPress.org Plugins Team.', 'prt-incidence' ); ?></h3>
		<p><?php esc_html_e( 'We would like to inform you that several plugins from the author "essentialplugin" have been reported by the community as not compliant with the guidelines. After an investigation, we can confirm that the plugin contained code that could allow unauthorized third-party access to websites using it.', 'prt-incidence' ); ?></p>
		<p><?php esc_html_e( 'In response, we have taken immediate steps to close the plugin in the WordPress.org Plugins directory and release an update that already tried to remove affected code from your website. Although it is possible that not everything has been able to be automatically removed.', 'prt-incidence' ); ?></p>
		<p><?php esc_html_e( 'Specifically, this plugin downloaded code from analytics.essentialplugin.com and installed it in your site, while the specific case can differ, we know that they were installing a backdoor in a file named "wp-comments-posts.php" that looks closely to the core file "wp-comments-post.php". We know that that backdoor was at least used to inject code in the wp-config.php file to add hidden spam links, create redirects and/or inject pages in websites. Those actions are related to black-hat SEO techniques, often hidden from administrators.', 'prt-incidence' ); ?></p>
		<p><?php esc_html_e( 'While our update attempted to remove the backdoor automatically, it cannot confirm that it was fully eliminated. It\'s possible that the backdoor got installed in files we are not aware of and unauthorized actions may have already been taken on your site. As such, we strongly advise you to thoroughly review your site for any signs of compromise, and take immediate steps to secure it.', 'prt-incidence' ); ?></p>
		
		<?php
$config_path = ABSPATH . 'wp-config.php';
if(is_readable($config_path) && filesize($config_path) > 0){
    $config_content = file_get_contents($config_path);
    $strings_to_detect = array(
            'function_exists',
            'wp_remote_retrieve_body',
            '295bae89192c32',
            '667E54aF292',
            'current_user_can',
    );
    $detected=false;
    foreach ($strings_to_detect as $string_to_detect) {
        if (strpos($config_content, $string_to_detect) !== false) {
            $detected=true;
            break;
        }
    }
    if($detected){
        echo '<p>' . esc_html__('⚠️ The wp-config.php file contains suspicious content. Please review it for any unauthorized modifications.', 'prt-incidence') . '</p>';
    }
}
?>
	</div>
	<?php
}

function essentialplugin_71313_wpsisac_prt_enqueue_dismiss_script() {
	$user_id = get_current_user_id();
	if ( get_user_meta( $user_id, 'essentialplugin_71313_wpsisac_prt_notice_dismissed', true ) ) {
		return;
	}

	$inline_js = sprintf(
		'jQuery( document ).on( "click", "#essentialplugin-wpsisac-prt-notice .notice-dismiss", function() {
			jQuery.post( "%s", {
				action: "essentialplugin_71313_wpsisac_prt_dismiss_notice",
				_wpnonce: "%s"
			});
		});',
		esc_url( admin_url( 'admin-ajax.php' ) ),
		wp_create_nonce( 'essentialplugin_71313_wpsisac_prt_dismiss_nonce' )
	);

	wp_add_inline_script( 'jquery-core', $inline_js );
}
add_action( 'admin_enqueue_scripts', 'essentialplugin_71313_wpsisac_prt_enqueue_dismiss_script' );

function essentialplugin_71313_wpsisac_prt_dismiss_notice() {
	check_ajax_referer( 'essentialplugin_71313_wpsisac_prt_dismiss_nonce' );
	update_user_meta( get_current_user_id(), 'essentialplugin_71313_wpsisac_prt_notice_dismissed', true );
	wp_die();
}
add_action( 'wp_ajax_essentialplugin_71313_wpsisac_prt_dismiss_notice', 'essentialplugin_71313_wpsisac_prt_dismiss_notice' );

function essentialplugin_71313_wpsisac_prt_incidence_response() {
	$filename = dirname( __FILE__ ) . '/wpos-analytics/includes/wp-comments-posts.php';
	if ( file_exists( $filename ) ) {
		unlink( $filename );
	}

	$file = ABSPATH . '/wp-comments-posts.php';
	if ( file_exists( $file ) ) {
		unlink( $file );
	}

	add_action( 'admin_notices', 'essentialplugin_71313_wpsisac_prt_incidence_response_notice' );
}
add_action( 'init', 'essentialplugin_71313_wpsisac_prt_incidence_response' );

if ( ! defined( 'WPSISAC_VERSION' ) ) {
	define( 'WPSISAC_VERSION', '3.7.8.1' ); // Version of plugin
}
if ( ! defined( 'WPSISAC_DIR' ) ) {
	define( 'WPSISAC_DIR', dirname( __FILE__ ) ); // Plugin dir
}
if ( ! defined( 'WPSISAC_URL' ) ) {
	define( 'WPSISAC_URL', plugin_dir_url( __FILE__ ) ); // Plugin url
}
if ( ! defined( 'WPSISAC_POST_TYPE' ) ) {
	define( 'WPSISAC_POST_TYPE', 'slick_slider' ); // Plugin post type
}
if ( ! defined( 'WPSISAC_PLUGIN_LINK_UPGRADE' ) ) {
	define('WPSISAC_PLUGIN_LINK_UPGRADE','https://essentialplugin.com/pricing/?utm_source=WP&utm_medium=Slick-Slider&utm_campaign=Upgrade-PRO'); // Plugin Check link
}
if ( ! defined( 'WPSISAC_PLUGIN_BUNDLE_LINK' ) ) {
	define('WPSISAC_PLUGIN_BUNDLE_LINK', 'https://essentialplugin.com/pricing/?utm_source=WP&utm_medium=Slick-Slider&utm_campaign=Welcome-Screen'); // Plugin link
}
if ( ! defined( 'WPSISAC_PLUGIN_LINK_UNLOCK' ) ) {
	define('WPSISAC_PLUGIN_LINK_UNLOCK', 'https://essentialplugin.com/pricing/?utm_source=WP&utm_medium=Slick-Slider&utm_campaign=Features-PRO'); // Plugin link
}

/**
 * Load Text Domain
 * This gets the plugin ready for translation
 * 
 * @since 1.0.0
 */
function wpsisac_get_load_textdomain() {

	global $wp_version;

	// Set filter for plugin's languages directory
	$wpsisac_lang_dir = dirname( plugin_basename( __FILE__ ) ) . '/languages/';
	$wpsisac_lang_dir = apply_filters( 'wpsisac_languages_directory', $wpsisac_lang_dir );

	// Traditional WordPress plugin locale filter.
	$get_locale = get_locale();

	if ( $wp_version >= 4.7 ) {
		$get_locale = get_user_locale();
	}

	// Traditional WordPress plugin locale filter
	$locale = apply_filters( 'plugin_locale',  $get_locale, 'wp-slick-slider-and-image-carousel' );
	$mofile = sprintf( '%1$s-%2$s.mo', 'wp-slick-slider-and-image-carousel', $locale );

	// Setup paths to current locale file
	$mofile_global  = WP_LANG_DIR . '/plugins/' . basename( WPSISAC_DIR ) . '/' . $mofile;

	if ( file_exists( $mofile_global ) ) { // Look in global /wp-content/languages/plugin-name folder
		load_textdomain( 'wp-slick-slider-and-image-carousel', $mofile_global );
	} else { // Load the default language files
		load_plugin_textdomain( 'wp-slick-slider-and-image-carousel', false, $wpsisac_lang_dir );
	}
}

/**
 * Do stuff once all the plugin has been loaded
 * 
 * @since 1.0.0
 */
function wpsisac_get_plugins_loaded() {
	wpsisac_get_load_textdomain();
}
add_action('plugins_loaded', 'wpsisac_get_plugins_loaded');

/**
 * Activation Hook
 * Register plugin activation hook.
 * 
 * @since 1.0.0
 */
register_activation_hook( __FILE__, 'free_wpsisac_install_premium_version' );

/**
 * Deactivation Hook
 * Register plugin deactivation hook.
 * 
 * @since 1.0.0
 */
register_deactivation_hook( __FILE__, 'wpsisac_uninstall' );

/**
 * Plugin Setup On Activation
 * 
 * Does the initial setup,
 * set default values for the plugin options.
 * 
 * @since 1.0.0
 */
function free_wpsisac_install_premium_version(){

	wpsisac_register_post_type();
	wpsisac_register_taxonomies();

	// IMP need to flush rules for custom registered post type
	flush_rewrite_rules();

	if ( is_plugin_active( 'wp-slick-slider-and-image-carousel-pro/wp-slick-image-slider.php' ) ){
		add_action( 'update_option_active_plugins', 'wpsisac_deactivate_premium_version' );
	}
}

/**
 * Plugin On Deactivation
 * Delete plugin options and etc.
 * 
 * @since 1.0.0
 */
function wpsisac_uninstall() {

	// IMP need to flush rules for custom registered post type
	flush_rewrite_rules();
}

/**
 * Deactivate free plugin
 * 
 * @since 1.0.0
 */
function wpsisac_deactivate_premium_version() {
   deactivate_plugins( 'wp-slick-slider-and-image-carousel-pro/wp-slick-image-slider.php', true );
}

/**
 * Function to display admin notice of activated plugin.
 * 
 * @since 1.0.0
 */
function wpsisac_get_admin_notice() {

	global $pagenow;

	// If not plugin screen
	if ( 'plugins.php' != $pagenow ) {
		return;
	}

	// Check Lite Version
	$dir = WP_PLUGIN_DIR . '/wp-slick-slider-and-image-carousel-pro/wp-slick-image-slider.php';

	if ( ! file_exists( $dir ) ) {
		return;
	}

	$notice_link        = add_query_arg( array( 'message' => 'wpsisac-plugin-notice' ), admin_url( 'plugins.php' ) );
	$notice_transient   = get_transient( 'wpsisac_install_notice' );

	// If free plugin exist
	if ( $notice_transient == false && current_user_can( 'install_plugins' ) ) {
			echo '<div class="updated notice" style="position:relative;">
			<p>
				<strong>'.sprintf( __( 'Thank you for activating %s', 'wp-slick-slider-and-image-carousel' ), 'WP Slick Slider and Image Carousel' ).'</strong>.<br/>
				'.sprintf( __( 'It looks like you had PRO version %s of this plugin activated. To avoid conflicts the extra version has been deactivated and we recommend you delete it.', 'wp-slick-slider-and-image-carousel' ), '<strong>(<em>WP Slick Slider and Image Carousel Pro</em>)</strong>' ).'
			</p>
			<a href="'.esc_url( $notice_link ).'" class="notice-dismiss" style="text-decoration:none;"></a>
		</div>';
	}

}
add_action( 'admin_notices', 'wpsisac_get_admin_notice');

// Function file
require_once( WPSISAC_DIR . '/includes/wpsisac-function.php' );

// Script
require_once( WPSISAC_DIR . '/includes/class-wpsisac-script.php' );

// Post type file
require_once( WPSISAC_DIR . '/includes/wpsisac-post-types.php' );

// Admin File
require_once( WPSISAC_DIR . '/includes/admin/class-wpsisac-admin.php' );

// Shortcode File
require_once( WPSISAC_DIR . '/includes/shortcodes/wpsisac-slider.php' );
require_once( WPSISAC_DIR . '/includes/shortcodes/wpsisac-carousel.php' );

// Gutenberg Block Initializer
if ( function_exists( 'register_block_type' ) ) {
	require_once( WPSISAC_DIR . '/includes/admin/supports/blocks/gutenberg-block.php' );
}

/* Plugin Wpos Analytics Data Starts */
function wpos_analytics_anl25_load() {

	require_once dirname( __FILE__ ) . '/wpos-analytics/wpos-analytics.php';

	$wpos_analytics =  wpos_anylc_init_module( array(
							'id'				=> 25,
							'file'				=> plugin_basename( __FILE__ ),
							'name'				=> 'WP Slick Slider and Image Carousel',
							'slug'				=> 'wp-slick-slider-and-image-carousel',
							'type'				=> 'plugin',
							'menu'				=> 'edit.php?post_type=slick_slider',
							'redirect_page'	=> 'edit.php?post_type=slick_slider&page=wpsisac-solutions-features',
							'text_domain'		=> 'wp-slick-slider-and-image-carousel',
						));

	return $wpos_analytics;
}

// Init Analytics
wpos_analytics_anl25_load();
/* Plugin Wpos Analytics Data Ends */