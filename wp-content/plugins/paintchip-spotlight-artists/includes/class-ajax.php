<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PaintChip_Spotlight_Ajax {

	public function __construct() {
		add_action( 'wp_ajax_paintchip_search_artists', array( $this, 'search_artists' ) );
	}

	public function search_artists() {
		check_ajax_referer( 'paintchip_search_artists', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( 'forbidden', 403 );
		}

		$term = isset( $_GET['term'] ) ? sanitize_text_field( wp_unslash( $_GET['term'] ) ) : '';

		$query = new WP_Query( array(
			'post_type'      => 'paintchip_artist',
			's'              => $term,
			'posts_per_page' => 20,
			'post_status'    => 'publish',
			'orderby'        => 'title',
			'order'          => 'ASC',
		) );

		$results = array();
		foreach ( $query->posts as $post ) {
			$results[] = array(
				'id'        => $post->ID,
				'name'      => $post->post_title,
				'thumbnail' => get_the_post_thumbnail_url( $post->ID, 'thumbnail' ),
			);
		}

		wp_send_json_success( $results );
	}
}
