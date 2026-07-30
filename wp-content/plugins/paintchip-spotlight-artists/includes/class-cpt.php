<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PaintChip_Spotlight_CPT {

	public function __construct() {
		add_action( 'init', array( $this, 'register_post_types' ) );
		add_action( 'save_post_paintchip_exhibition', array( $this, 'maybe_autogenerate_title' ), 20, 3 );
	}

	public function register_post_types() {

		register_post_type( 'paintchip_artist', array(
			'labels'        => array(
				'name'               => 'Artists',
				'singular_name'      => 'Artist',
				'add_new_item'       => 'Add New Artist',
				'edit_item'          => 'Edit Artist',
				'search_items'       => 'Search Artists',
				'not_found'          => 'No artists found',
			),
			'public'        => true,
			'show_in_menu'  => 'paintchip_spotlight',
			'menu_icon'     => 'dashicons-art',
			'supports'      => array( 'title', 'editor', 'thumbnail' ), // editor = bio field
			'has_archive'   => 'artists',
			'rewrite'       => array( 'slug' => 'artist' ),
			'show_in_rest'  => true, // needed for the media/gallery pickers to work smoothly
		) );

		register_post_type( 'paintchip_exhibition', array(
			'labels'        => array(
				'name'               => 'Exhibitions',
				'singular_name'      => 'Exhibition',
				'add_new_item'       => 'Add New Exhibition',
				'edit_item'          => 'Edit Exhibition',
				'search_items'       => 'Search Exhibitions',
				'not_found'          => 'No exhibitions found',
			),
			'public'        => true,
			'show_in_menu'  => 'paintchip_spotlight',
			'menu_icon'     => 'dashicons-calendar-alt',
			'supports'      => array( 'title', 'editor', 'thumbnail' ), // editor = description field
			'has_archive'   => 'spotlight-artists',
			'rewrite'       => array( 'slug' => 'exhibition' ),
			'show_in_rest'  => true,
		) );

		// Top-level admin menu that both CPTs nest under.
		add_action( 'admin_menu', function () {
			add_menu_page(
				'Spotlight Artists',
				'Spotlight Artists',
				'edit_posts',
				'paintchip_spotlight',
				array( $this, 'render_dashboard_redirect' ),
				'dashicons-art',
				25
			);
		} );

		add_filter( 'manage_paintchip_exhibition_posts_columns', array( $this, 'add_status_column' ) );
		add_action( 'manage_paintchip_exhibition_posts_custom_column', array( $this, 'render_status_column' ), 10, 2 );
	}

	public function add_status_column( $columns ) {
		$new = array();
		foreach ( $columns as $key => $label ) {
			$new[ $key ] = $label;
			if ( 'title' === $key ) {
				$new['pc_status'] = 'Status';
			}
		}
		return $new;
	}

	public function render_status_column( $column, $post_id ) {
		if ( 'pc_status' !== $column ) {
			return;
		}
		if ( get_post_meta( $post_id, '_pc_needs_review', true ) ) {
			echo '<span style="color:#b32d2e;font-weight:600;">&#9888; Needs review (backfilled)</span>';
		} else {
			echo '<span style="color:#2271b1;">Ready</span>';
		}
	}

	/**
	 * The top-level menu itself has no real page; send folks to the Exhibitions list.
	 */
	public function render_dashboard_redirect() {
		wp_safe_redirect( admin_url( 'edit.php?post_type=paintchip_exhibition' ) );
		exit;
	}

	/**
	 * If the admin left the title blank, build one from month + attached artists.
	 */
	public function maybe_autogenerate_title( $post_id, $post, $update ) {
		if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
			return;
		}
		if ( ! empty( $post->post_title ) && 'Auto Draft' !== $post->post_title ) {
			return;
		}

		$month   = get_post_meta( $post_id, '_pc_month', true );
		$artists = paintchip_get_exhibition_artists( $post_id );
		$title   = paintchip_generate_exhibition_title( $month, $artists );

		remove_action( 'save_post_paintchip_exhibition', array( $this, 'maybe_autogenerate_title' ), 20 );
		wp_update_post( array(
			'ID'         => $post_id,
			'post_title' => $title,
			'post_name'  => sanitize_title( $title ),
		) );
		add_action( 'save_post_paintchip_exhibition', array( $this, 'maybe_autogenerate_title' ), 20, 3 );
	}
}
