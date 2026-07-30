<?php
/**
 * Plugin Name: Disable Admin Notices Individually
 * Plugin URI: https://clearfy.pro/disable-admin-notices
 * Description: Disable admin notices plugin gives you the option to hide updates warnings and inline notices in the admin panel.
 * Author: Themeisle
 * Version: 1.4.7
 * Text Domain: disable-admin-notices
 * Domain Path: /languages/
 * Author URI: https://themeisle.com
 * Framework Version: FACTORY_480_VERSION
 * WordPress Available: yes
 * Requires License: no
 */

/**
 * Developers who contributions in the development plugin:
 *
 * Alexander Kovalev
 * ---------------------------------------------------------------------------------
 * Full plugin development.
 *
 * Email:         alex.kovalevv@gmail.com
 * Personal card: https://alexkovalevv.github.io
 * Personal repo: https://github.com/alexkovalevv
 * ---------------------------------------------------------------------------------
 *
 * Artem Prihodko
 * ---------------------------------------------------------------------------------
 * Updates and fixes
 *
 * Email:  webtemyk@yandex.ru
 * GitHub: https://github.com/temyk
 * ---------------------------------------------------------------------------------
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * -----------------------------------------------------------------------------
 * CHECK REQUIREMENTS
 * Check compatibility with php and wp version of the user's site. As well as checking
 * compatibility with other plugins from Webcraftic.
 * -----------------------------------------------------------------------------
 */

require_once( dirname( __FILE__ ) . '/libs/factory/core/includes/class-factory-requirements.php' );

// @formatter:off
$wdan_plugin_info = [
	'prefix'               => 'wbcr_dan_',
	'plugin_name'          => 'wbcr_dan',
	'plugin_title'         => 'Disable admin notices',

	// PLUGIN SUPPORT
	'support_details'      => [
		'url'       => 'https://clearfy.pro/',
		'pages_map' => [
			'support' => 'support', // {site}/support
			'docs'    => 'docs',     // {site}/docs,
			'pricing' => 'disable-admin-notices'
		]
	],

	// PLUGIN SUBSCRIBE FORM
	'subscribe_widget'     => true,
	'subscribe_settings'   => [ 'group_id' => '105407140' ],

	// FRAMEWORK MODULES
	'load_factory_modules' => [
		[ 'libs/factory/bootstrap', 'factory_bootstrap_482', 'admin' ],
		[ 'libs/factory/forms', 'factory_forms_480', 'admin' ],
		[ 'libs/factory/pages', 'factory_pages_480', 'admin' ],
		[ 'libs/factory/templates', 'factory_templates_134', 'all' ],
		//array('libs/factory/logger', 'factory_logger_149', 'all')
	]
];

$wdan_compatibility = new Wbcr_Factory480_Requirements( __FILE__, array_merge( $wdan_plugin_info, [
	'plugin_already_activate'          => defined( 'WDN_PLUGIN_ACTIVE' ),
	'required_php_version'             => '7.0',
	'required_wp_version'              => '4.8.0',
	'required_clearfy_check_component' => false
] ) );



/**
 * If the plugin is compatible, then it will continue its work, otherwise it will be stopped,
 * and the user will throw a warning.
 */
if ( ! $wdan_compatibility->check() ) {
	return;
}

/**
 * -----------------------------------------------------------------------------
 * CONSTANTS
 * Install frequently used constants and constants for debugging, which will be
 * removed after compiling the plugin.
 * -----------------------------------------------------------------------------
 */

// This plugin is activated
define( 'WDN_PLUGIN_ACTIVE', true );
define( 'WDN_PLUGIN_VERSION', $wdan_compatibility->get_plugin_version() );
define( 'WDN_PLUGIN_DIR', dirname( __FILE__ ) );
define( 'WDN_PLUGIN_BASE', plugin_basename( __FILE__ ) );
define( 'WDN_PLUGIN_URL', plugins_url( '', __FILE__ ) );
define( 'WDN_PLUGIN_FILE', __FILE__ );
define( 'WDN_PRODUCT_SLUG', basename( dirname( __FILE__ ) ) );


/**
 * -----------------------------------------------------------------------------
 * PLUGIN INIT
 * -----------------------------------------------------------------------------
 */

require_once( WDN_PLUGIN_DIR . '/libs/factory/core/boot.php' );
require_once( WDN_PLUGIN_DIR . '/includes/functions.php' );
require_once( WDN_PLUGIN_DIR . '/includes/class-plugin.php' );

/**
 * Deactivate PRO plugin.
 *
 * @param WDN_Plugin $disable_admin_notice_obj
 */
function deactivate_pro_plugin( $disable_admin_notice_obj ) {
	$pro_slug = 'disable-admin-notices-premium-premium/disable-admin-notices-premium.php';

	// If plugin isn't active, we stop immediately.
	if ( ! is_plugin_active( $pro_slug ) ) {
		return;
	}

	// Delete premium option
	$disable_admin_notice_obj->deletePopulateOption( 'premium_package' );

	// Remove pro plugin action. 
	remove_action( 'plugins_loaded', 'wdan_premium_load', 20 );

	// As register_deactivation_hook called, deactivate the pro plugin silently.
	deactivate_plugins( $pro_slug, true );
}

try {
	require_once WDN_PLUGIN_DIR . '/vendor/autoload.php';
	$disable_admin_notice = new WDN_Plugin( __FILE__, array_merge( $wdan_plugin_info, [
		'plugin_version'     => WDN_PLUGIN_VERSION,
		'plugin_text_domain' => $wdan_compatibility->get_text_domain(),
	] ) );

	deactivate_pro_plugin( $disable_admin_notice );
} catch ( Exception $e ) {
	// Plugin wasn't initialized due to an error
	define( 'WDN_PLUGIN_THROW_ERROR', true );

	$wdan_plugin_error_func = function () use ( $e ) {
		$error = sprintf( "The %s plugin has stopped. <b>Error:</b> %s Code: %s", 'Disable Admin Notices', $e->getMessage(), $e->getCode() );
		echo '<div class="notice notice-error"><p>' . $error . '</p></div>';
	};

	add_action( 'admin_notices', $wdan_plugin_error_func );
	add_action( 'network_admin_notices', $wdan_plugin_error_func );
}
// @formatter:on