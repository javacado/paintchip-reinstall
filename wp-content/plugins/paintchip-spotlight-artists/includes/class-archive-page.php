<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles the public "Paint Chip Art Events" listing at the paintchip_exhibition
 * archive (/events/ or whatever slug -- see has_archive in class-cpt.php), plus
 * routing the Exhibition/Artist singular pages and the Artist archive to their
 * plugin templates.
 *
 * Filtering is plain GET-based (works without JS): ?pc_year=2024
 */
class PaintChip_Spotlight_Archive_Page {

	public function __construct() {
		add_filter( 'template_include', array( $this, 'use_plugin_template' ) );
		add_action( 'pre_get_posts', array( $this, 'apply_year_filter' ) );
	}

	public function use_plugin_template( $template ) {
		if ( is_post_type_archive( 'paintchip_exhibition' ) ) {
			$override = PAINTCHIP_SPOTLIGHT_DIR . 'templates/archive-paintchip_exhibition.php';
			if ( file_exists( $override ) ) {
				return $override;
			}
		}
		if ( is_post_type_archive( 'paintchip_artist' ) ) {
			$override = PAINTCHIP_SPOTLIGHT_DIR . 'templates/archive-paintchip_artist.php';
			if ( file_exists( $override ) ) {
				return $override;
			}
		}
		if ( is_singular( 'paintchip_exhibition' ) ) {
			$override = PAINTCHIP_SPOTLIGHT_DIR . 'templates/single-paintchip_exhibition.php';
			if ( file_exists( $override ) ) {
				return $override;
			}
		}
		if ( is_singular( 'paintchip_artist' ) ) {
			$override = PAINTCHIP_SPOTLIGHT_DIR . 'templates/single-paintchip_artist.php';
			if ( file_exists( $override ) ) {
				return $override;
			}
		}
		return $template;
	}

	public function apply_year_filter( $query ) {
		if ( is_admin() || ! $query->is_main_query() || ! is_post_type_archive( 'paintchip_exhibition' ) ) {
			return;
		}

		$query->set( 'meta_key', '_pc_month' );
		$query->set( 'orderby', 'meta_value' );
		$query->set( 'order', 'DESC' );
		$query->set( 'posts_per_page', 24 );

		if ( ! empty( $_GET['pc_year'] ) ) {
			$year = sanitize_text_field( wp_unslash( $_GET['pc_year'] ) );
			$query->set( 'meta_query', array(
				array(
					'key'     => '_pc_month',
					'value'   => $year,
					'compare' => 'LIKE',
				),
			) );
		}
	}
}
