<?php
/**
 * Blocks Initializer
 * 
 * @package WP Slick Slider and Image Carousel
 * @since 2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Blocks Initializer
 * Registers the block using the metadata loaded from the `block.json` file.
 * Behind the scenes, it registers also all assets so they can be enqueued
 * through the block editor in the corresponding context.
 *
 * @see https://developer.wordpress.org/reference/functions/register_block_type/
 */
function wpsisac_register_guten_block() {

	$blocks = array(
						'slick-slider' => array(
											'callback' => 'wpsisac_get_slick_slider',
											'script_handle'	=> 'wpsisac-slick-slider-editor-script',
										),
						'slick-carousel-slider' => array(
											'callback' => 'wpsisac_get_carousel_slider',
											'script_handle'	=> 'wpsisac-slick-carousel-slider-editor-script',
										)
					);

	foreach ($blocks as $block_key => $block_data) {

		register_block_type( __DIR__ . "/build/{$block_key}", array(
																'render_callback' => $block_data['callback'],
															));

		wp_set_script_translations( $block_data['script_handle'], 'wp-slick-slider-and-image-carousel', WPSISAC_DIR . '/languages' );
	}
}
add_action( 'init', 'wpsisac_register_guten_block' );

/**
 * Adds a custom variable to the JS to allow a user in the block editor
 * to preview sensitive data.
 *
 * @since 1.0
 * @return void
 */
function wpsisac_block_editor_assets() {

	wp_localize_script( 'wp-block-editor', 'Wpsisac_free_Block', array(
																'pro_demo_link' => 'https://demo.essentialplugin.com/prodemo/pro-wp-slick-slider-and-carousel-demo/',
																'free_demo_link' => 'https://demo.essentialplugin.com/slick-slider-demo/',
																'pro_link' => WPSISAC_PLUGIN_LINK_UNLOCK,
															));
}
add_action( 'enqueue_block_editor_assets', 'wpsisac_block_editor_assets' );

/**
 * Adds an extra category to the block inserter
 *
 *  @since 1.0
 */
function wpsisac_add_block_category( $categories ) {

	$guten_cats = wp_list_pluck( $categories, 'slug' );

	if( ! empty( $guten_cats ) && ! in_array( 'essp_guten_block', $guten_cats ) ) {

		$categories[] = array(
							'slug'	=> 'essp_guten_block',
							'title'	=> __('Essential Plugin Blocks', 'wp-slick-slider-and-image-carousel'),
							'icon'	=> null,
						);
	}

	return $categories;
}
add_filter( 'block_categories_all', 'wpsisac_add_block_category' );