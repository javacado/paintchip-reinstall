<?php
/**
 * Plugin Name: WP Change Default From Email
 * Plugin URI: 
 * Text Domain: WCDFE_translation
 * Domain Path: /languages
 * Description: A simple and easy way to change the from email address and from email name that appear on emails sent from WordPress.
 * Version: 1.1.3
 * Author: Subodh Ghulaxe
 * Author URI: http://www.subodhghulaxe.com
 */

//avoid direct calls to this file where wp core files not present
if (!function_exists ('add_action')) {
	header('Status: 403 Forbidden');
	header('HTTP/1.1 403 Forbidden');
	exit();
}

if ( !class_exists( 'WCDFE' ) ) {

	/**
	 * WP Change Default From Email class
	 *
	 * @package WP Change Default From Email
	 * @since 1.0.0
	 */
	class WCDFE {

		/**
		 * Instance of WCDFE class
		 *
		 * @since 1.0.0
		 * @access private
		 * @var object
		 */
		private static $instance = false;

		/**
		 * Plugin settings
		 *
		 * @since 1.0.0
		 * @access public
		 * @var array
		 */
		public $wcdfe_settings = array();

		/**
		 * Return unique instance of this class
		 *
		 * @since 1.0.0
		 * @return object
		 */
		public static function get_instance() {
		    if ( ! self::$instance ) {
		      self::$instance = new self();
		    }
		    return self::$instance;
		}
		
		function __construct() {
			$this->constants();
			$this->text_domain();
			add_action( 'init', array(&$this, 'init' ));
			$this->wcdfe_settings = get_option('wcdfe_settings');
		}
		
		/**
		 * Define plugin constants
		 *
		 * @since 1.0.0
		 */
		public function constants() {
			defined("WCDFE_PLUGIN_NAME") || define( 'WCDFE_PLUGIN_NAME', 'WP Change Default From Email' );
			defined("WCDFE_BASEDIR") || define( 'WCDFE_BASEDIR', dirname( plugin_basename(__FILE__) ) );
			defined("WCDFE_TEXTDOMAIN") || define( 'WCDFE_TEXTDOMAIN', 'WCDFE_translation' );
			defined("WCDFE_CSS_URL") || define( 'WCDFE_CSS_URL', plugins_url('assets/css/',__FILE__) );
		}
		
		/**
		 * Load plugin text domain
		 *
		 * @since 1.0.0
		 */
		public function text_domain() {
			load_plugin_textdomain( WCDFE_TEXTDOMAIN, false, WCDFE_BASEDIR . '/languages' );
		}

		/**
		 * Runs after WordPress has finished loading but before any headers are sent.
		 *
		 * @since 1.0.0
		 */
		public function init() {
			// Add settings link in plugin listing page.
			add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), array( &$this, 'add_action_links' ) );

			// Add donate link in plugin listing page.
			add_filter( 'plugin_row_meta', array( &$this, 'donate_link' ), 10, 2 );

			// Change the "from email address" used in an email sent using the wp_mail function.
			add_filter('wp_mail_from', array( &$this,'change_mail_from' ));

			// Change the "from name" used in an email sent using the wp_mail function.
			add_filter('wp_mail_from_name', array( &$this,'change_mail_from_name' ));
		}

		/**
		 * Return email that will be use to update default email using 'wp_mail_from' filter
		 * 
		 * @since 1.0.0
		 * @param  string $from_email
		 * @return string (email)
		 */
		function change_mail_from($from_email) { 
			if ( isset($this->wcdfe_settings['enable']) && isset($this->wcdfe_settings['from_email']) && is_email($this->wcdfe_settings['from_email'])) {
			    return $this->wcdfe_settings['from_email'];	
			} else {
			    return $from_email;	
			}
		}

		/**
		 * Return name that will be use to update default from name using 'wp_mail_from_name' filter
		 * 
		 * @since 1.0.0
		 * @param  string $from_name
		 * @return string
		 */
		function change_mail_from_name($from_name) {
		    if ( isset($this->wcdfe_settings['enable']) && isset($this->wcdfe_settings['from_name']) && !empty($this->wcdfe_settings['from_name'])) {
			 	return $this->wcdfe_settings['from_name'];
			} else {
				return $from_name;
			}
		}

		/**
		 * Add settings link to plugin action links in /wp-admin/plugins.php
		 * 
		 * @since 1.0.0
		 * @param  array $links
		 * @return array
		 */
		public function add_action_links ( $links ) {
			$mylinks = array(
				'<a href="' . admin_url( 'options-general.php?page=wcdfe_settings' ) . '">'.__( 'Settings', WCDFE_TEXTDOMAIN ).'</a>',
			);

			return array_merge( $mylinks, $links );
		}

		/**
		 * Add donate link to plugin description in /wp-admin/plugins.php
		 * 
		 * @since 1.0.0
		 * @param  array $plugin_meta
		 * @param  string $plugin_file
		 * @return array
		 */
		public function donate_link( $plugin_meta, $plugin_file ) {
			if ( plugin_basename( __FILE__ ) == $plugin_file )
				$plugin_meta[] = sprintf(
					'&hearts; <a href="%s" target="_blank">%s</a>',
					'https://www.patreon.com/subodhghulaxe',
					__( 'Donate', WCDFE_TEXTDOMAIN )
			);
			
			return $plugin_meta;
		}


	} // end class WCDFE
	
	add_action( 'plugins_loaded', array( 'WCDFE', 'get_instance' ) );

	include_once('admin/WCDFE_Admin.php');

} // end class_exists