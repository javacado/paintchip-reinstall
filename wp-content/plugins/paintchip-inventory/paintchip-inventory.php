<?php
/**
 * Plugin Name: Paint Chip Inventory Sync
 * Description: Imports the POS "General Inventory Full Master List" report, previews every change before it lands, applies stock through WooCommerce CRUD, and can roll the whole batch back.
 * Version:     1.1.1
 * Requires PHP: 7.4
 * Author:      The Paint Chip
 * Text Domain: pci
 */

defined( 'ABSPATH' ) || exit;

define( 'PCI_VERSION', '1.1.1' );
define( 'PCI_FILE', __FILE__ );
define( 'PCI_DIR', plugin_dir_path( __FILE__ ) );
define( 'PCI_URL', plugin_dir_url( __FILE__ ) );

/** Capability required for every screen and action in this plugin. */
define( 'PCI_CAP', 'manage_woocommerce' );

require_once PCI_DIR . 'includes/class-pci-schema.php';
require_once PCI_DIR . 'includes/class-pci-parser.php';
require_once PCI_DIR . 'includes/class-pci-suppliers.php';
require_once PCI_DIR . 'includes/class-pci-signals.php';
require_once PCI_DIR . 'includes/class-pci-classifier.php';
require_once PCI_DIR . 'includes/class-pci-run.php';
require_once PCI_DIR . 'includes/class-pci-applier.php';
require_once PCI_DIR . 'includes/class-pci-upc.php';
require_once PCI_DIR . 'includes/class-pci-sourcing.php';
require_once PCI_DIR . 'includes/scrapers/interface-pci-scraper.php';
require_once PCI_DIR . 'includes/scrapers/class-pci-scraper-registry.php';
require_once PCI_DIR . 'includes/scrapers/class-pci-scraper-sls.php';
require_once PCI_DIR . 'includes/class-pci-admin.php';

register_activation_hook( __FILE__, array( 'PCI_Schema', 'install' ) );

add_action( 'plugins_loaded', 'pci_boot' );
function pci_boot() {
	if ( ! function_exists( 'wc_get_product' ) ) {
		add_action( 'admin_notices', function () {
			echo '<div class="notice notice-error"><p>' .
				esc_html__( 'Paint Chip Inventory Sync needs WooCommerce active. Activate WooCommerce, then reload this page.', 'pci' ) .
				'</p></div>';
		} );
		return;
	}

	PCI_Schema::maybe_upgrade();
	PCI_Scraper_Registry::instance()->register( new PCI_Scraper_SLS() );

	if ( is_admin() ) {
		PCI_Admin::instance()->hooks();
	}
}

/**
 * Uploaded report files live outside the web root where possible, so a
 * guessable URL can't hand the client's full cost book to a stranger.
 */
function pci_storage_dir() {
	$dir  = wp_get_upload_dir();
	$path = trailingslashit( $dir['basedir'] ) . 'pci-inventory';

	if ( ! file_exists( $path ) ) {
		wp_mkdir_p( $path );
		// Deny direct access under Apache; harmless elsewhere.
		@file_put_contents( $path . '/.htaccess', "Require all denied\n" );
		@file_put_contents( $path . '/index.php', "<?php // Silence is golden." );
	}

	return $path;
}
