<?php
/**
 * Disable admin notices core class
 *
 * @author        Alexander Kovalev <alex.kovalevv@gmail.com>
 *                Github: https://github.com/alexkovalevv
 * @copyright (c) 2018 Webraftic Ltd
 * @version       1.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WDN_Plugin extends Wbcr_Factory480_Plugin {

	/**
	 * @var Wbcr_Factory480_Plugin
	 */
	private static $app;
	private $plugin_data;


	/**
	 * @param string $plugin_path
	 * @param array $data
	 *
	 * @throws Exception
	 */
	public function __construct( $plugin_path, $data ) {
		parent::__construct( $plugin_path, $data );

		self::$app         = $this;
		$this->plugin_data = $data;

		$this->global_scripts();

		if ( is_admin() ) {
			$this->admin_scripts();
		}

		// Wordpress 6.7 fix
		add_action( 'init', function () {
			if ( is_admin() ) {
				$this->register_pages();
			}
		} );

		add_filter( 'themeisle_sdk_products', [ __CLASS__, 'register_sdk' ] );

		add_filter( 'themeisle_sdk_ran_promos', '__return_true' );

		add_action( 'admin_enqueue_scripts', [ $this, 'mark_internal_page' ] );
		add_filter( 'themeisle_sdk_blackfriday_data', [ $this, 'add_black_friday_data' ] );
	}
	/**
	 * Register product into SDK.
	 *
	 * @param array $products All products.
	 *
	 * @return array Registered product.
	 */
	public static function register_sdk( $products ) {
		$products[] = WDN_PLUGIN_FILE;

		return $products;
	}
	/**
	 * @return Wbcr_Factory480_Plugin
	 */
	public static function app() {
		return self::$app;
	}

	private function register_pages() {
		//self::app()->registerPage( 'WDN_Log_Page', WDN_PLUGIN_DIR . '/admin/pages/class-pages-log.php' );
		self::app()->registerPage( 'WDN_Settings_Page', WDN_PLUGIN_DIR . '/admin/pages/class-pages-settings.php' );
		self::app()->registerPage( 'WDAN_Notices', WDN_PLUGIN_DIR . '/admin/pages/class-pages-notices.php' );
		self::app()->registerPage( 'WDAN_Block_Ad_Redirects', WDN_PLUGIN_DIR . '/admin/pages/class-pages-edit-redirects.php' );
		self::app()->registerPage( 'WDAN_Edit_Admin_Bar', WDN_PLUGIN_DIR . '/admin/pages/class-pages-edit-admin-bar.php' );
	}

	private function admin_scripts() {
		require( WDN_PLUGIN_DIR . '/admin/options.php' );
		require( WDN_PLUGIN_DIR . '/admin/class-page-basic.php' );

		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			require_once( WDN_PLUGIN_DIR . '/admin/ajax/hide-notice.php' );
			require_once( WDN_PLUGIN_DIR . '/admin/ajax/restore-notice.php' );
			require_once( WDN_PLUGIN_DIR . '/admin/ajax/disable-adminbar-menus.php' );
		}

		require_once( WDN_PLUGIN_DIR . '/admin/boot.php' );
		require_once( WDN_PLUGIN_DIR . '/admin/pages/class-pages-edit-admin-bar.php' );
		require_once( WDN_PLUGIN_DIR . '/admin/pages/class-pages-edit-redirects.php' );
		require_once( WDN_PLUGIN_DIR . '/admin/pages/class-pages-notices.php' );

		/*add_action( 'plugins_loaded', function () {
			$this->register_pages();
		}, 30 );*/
	}

	private function global_scripts() {
		require_once( WDN_PLUGIN_DIR . '/includes/classes/class-configurate-notices.php' );
		new WDN_ConfigHideNotices( self::$app );

		add_action( 'wp_before_admin_bar_render', [ $this, 'remove_from_admin_bar' ], 999 );
	}

	public function remove_from_admin_bar() {
		global $wp_admin_bar;

		if ( empty( $wp_admin_bar ) ) {
			return;
		}

		$hidden_items = $this->getPopulateOption( 'hidden_adminbar_items', [] );

		if ( is_admin() ) {
			$nodes = [];
			foreach ( $wp_admin_bar->get_nodes() as $node ) {
				if ( false === $node->parent && ! empty( $node->title ) ) {
					if ( "updates" === $node->id ) {
						$node->title = "Updates";
					}
					if ( "comments" === $node->id ) {
						$node->title = "Comments";
					}
					$nodes[ $node->id ] = strip_tags( $node->title );
				}
			}
			$this->updatePopulateOption( 'adminbar_items', $nodes );
		}

		foreach ( (array) $hidden_items as $item_ID => $bool ) {
			$wp_admin_bar->remove_menu( $item_ID );
		}
	}

	public function add_black_friday_data( $configs ) {
		$config = $configs['default'];

		if ( defined( 'NEVE_VERSION' ) ) {
			return $configs;
		}

		// translators: 1. Number of free licenses, 2. The price of the product.
		$config['message'] = sprintf( __( 'You\'re using Disable Admin Notices, and the team behind it is celebrating Black Friday by giving away %1$s licences of Neve Pro. A premium WordPress theme worth %2$s, packed with starter sites, a header builder, and WooCommerce layouts. Claim yours before they run out.', 'disable-admin-notices' ), 100, '$69' );
		$config['cta_label'] = __( 'Get Neve Pro free', 'disable-admin-notices' );
		$config['plugin_meta_message'] = __( 'Black Friday Sale - Get Neve Pro free', 'disable-admin-notices' );
		$config['sale_url'] = add_query_arg(
			array(
				'utm_term' => 'free',
			),
			tsdk_translate_link( tsdk_utmify( 'https://themeisle.link/neve-claim-bf', 'bfcm', 'disable-notices' ) )
		);

		$configs[ WDN_PRODUCT_SLUG ] = $config;

		return $configs;
	}

	public function mark_internal_page( $hook ) {
		if ( strpos( $hook, 'wdan_settings' ) !== false ) {
			do_action( 'themeisle_internal_page', WDN_PRODUCT_SLUG, 'settings' );
		} elseif ( strpos( $hook, 'wdan-notices' ) !== false ) {
			do_action( 'themeisle_internal_page', WDN_PRODUCT_SLUG, 'notices' );
		} elseif ( strpos( $hook, 'wdanp-edit-redirects' ) !== false ) {
			do_action( 'themeisle_internal_page', WDN_PRODUCT_SLUG, 'edit-redirects' );
		} elseif ( strpos( $hook, 'wdanp-edit-admin-bar' ) !== false ) {
			do_action( 'themeisle_internal_page', WDN_PRODUCT_SLUG, 'edit-admin-bar' );
		}
	}
}
