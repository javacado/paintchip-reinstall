<?php
/**
 * Plugin Name:       Paint Chip Spotlight Artists
 * Plugin URI:        https://thepaint-chip.com
 * Description:       Manages Artist and Exhibition ("Spotlight") records for The Paint Chip's monthly 2nd Friday ArtAbout, with a homepage block, a searchable Spotlight Artists archive page, and a one-time historical backfill tool.
 * Version:           1.0.0
 * Author:            The Paint Chip
 * Text Domain:       paintchip-spotlight
 * Requires PHP:      7.4
 * Requires at least: 5.9
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'PAINTCHIP_SPOTLIGHT_VERSION', '1.0.0' );
define( 'PAINTCHIP_SPOTLIGHT_DIR', plugin_dir_path( __FILE__ ) );
define( 'PAINTCHIP_SPOTLIGHT_URL', plugin_dir_url( __FILE__ ) );

require_once PAINTCHIP_SPOTLIGHT_DIR . 'includes/helpers.php';
require_once PAINTCHIP_SPOTLIGHT_DIR . 'includes/class-cpt.php';
require_once PAINTCHIP_SPOTLIGHT_DIR . 'includes/class-metaboxes.php';
require_once PAINTCHIP_SPOTLIGHT_DIR . 'includes/class-ajax.php';
require_once PAINTCHIP_SPOTLIGHT_DIR . 'includes/class-block.php';
require_once PAINTCHIP_SPOTLIGHT_DIR . 'includes/class-archive-page.php';

// WP-CLI backfill command only needs to load when WP-CLI is actually running.
if ( defined( 'WP_CLI' ) && WP_CLI ) {
	require_once PAINTCHIP_SPOTLIGHT_DIR . 'includes/class-cli-backfill.php';
}

/**
 * Bootstrap all the pieces. Each class wires up its own hooks in its constructor.
 */
function paintchip_spotlight_init() {
	new PaintChip_Spotlight_CPT();
	new PaintChip_Spotlight_MetaBoxes();
	new PaintChip_Spotlight_Ajax();
	new PaintChip_Spotlight_Block();
	new PaintChip_Spotlight_Archive_Page();
}
add_action( 'plugins_loaded', 'paintchip_spotlight_init' );

add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_style( 'paintchip-spotlight-front', PAINTCHIP_SPOTLIGHT_URL . 'assets/css/front.css', array(), paintchip_asset_version( 'assets/css/front.css' ) );
	wp_enqueue_script( 'paintchip-spotlight-lightbox', PAINTCHIP_SPOTLIGHT_URL . 'assets/js/front-lightbox.js', array(), paintchip_asset_version( 'assets/js/front-lightbox.js' ), true );
} );


/**
 * Flush rewrite rules on activation/deactivation so the CPT permalinks work immediately.
 */
function paintchip_spotlight_activate() {
	// Registering here too so the rewrite rules exist before the flush.
	require_once PAINTCHIP_SPOTLIGHT_DIR . 'includes/class-cpt.php';
	$cpt = new PaintChip_Spotlight_CPT();
	$cpt->register_post_types();
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'paintchip_spotlight_activate' );

function paintchip_spotlight_deactivate() {
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'paintchip_spotlight_deactivate' );
