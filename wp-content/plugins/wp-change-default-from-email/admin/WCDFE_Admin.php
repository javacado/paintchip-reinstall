<?php

//avoid direct calls to this file where wp core files not present
if (!function_exists ('add_action')) {
	header('Status: 403 Forbidden');
	header('HTTP/1.1 403 Forbidden');
	exit();
}

if ( !class_exists( 'WCDFE_Admin' ) ) {

	/**
	 * WP Change Default From Email Admin class
	 *
	 * @package WP Change Default From Email
	 * @since 1.0.0
	 */
	class WCDFE_Admin {

		/**
		 * Instance of WCDFE_Admin class
		 *
		 * @since 1.0.0
		 * @access private
		 * @var object
		 */
		private static $instance = false;

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
		
		public function __construct() {
			add_action( 'admin_enqueue_scripts', array( &$this,'admin_scripts' ) );
			add_action( 'admin_menu', array( &$this, 'register_admin_menu' ) );
			add_action( 'wp_ajax_wcdfe_save_settings', array( &$this,'save_settings' ) );
		}
		
		/**
		 * Include style in WordPress admin
		 * 
		 * @since 1.0.0
		 */
		function admin_scripts() {
			wp_enqueue_style('wcdfe-admin-style', WCDFE_CSS_URL.'admin.css');
		}

		/**
		 * Add submenu in WordPress admin settings menu
		 * 
		 * @since 1.0.0
		 */
		public function register_admin_menu() {
			add_options_page( WCDFE_PLUGIN_NAME.' Settings', __( 'Change From Email', WCDFE_TEXTDOMAIN ), 'manage_options', 'wcdfe_settings', array( &$this, 'settings' ) );
		}

		public function settings() {
			include_once('pages/settings.php');
		}

		/**
		 * Save plugin settings
		 * 
		 * @since 1.0.0
		 * @return string (json)
		 */
		public function save_settings() {
			$response 	= array();
			$error 		= "";

			// Check for request security
			check_ajax_referer( 'wcdfe-save-settings', 'security' );

			$wcdfe_settings = isset($_POST['wcdfe_settings']) ? $_POST['wcdfe_settings'] : array();

			if( isset($wcdfe_settings['from_name']) && empty($wcdfe_settings['from_name']) ) {
				$error = __( '<b>From Name</b> must not be empty.', WCDFE_TEXTDOMAIN );
			}

			// Validate email
			if( isset($wcdfe_settings['from_email']) && !is_email($wcdfe_settings['from_email'])){
				$error = __( '<b>From Email</b> must be a valid email address.', WCDFE_TEXTDOMAIN );
			}

			if(empty($error)) {
				// Save setting in WordPress options
				$result = update_option('wcdfe_settings',$wcdfe_settings);
				if($result){
					$response['status'] = 'success';
					$response['message'] = __( 'Settings updated successfully.', WCDFE_TEXTDOMAIN );
				} else {
					$response['status'] = 'error';
					$response['message'] = __( 'No settings were updated.', WCDFE_TEXTDOMAIN );
				}
			} else {
				$response['status'] = 'error';
				$response['message'] = $error;
			}

			echo json_encode($response);
			exit();
		}


	} // end class WCDFE_Admin

	add_action( 'plugins_loaded', array( 'WCDFE_Admin', 'get_instance' ) );

} // end class_exists