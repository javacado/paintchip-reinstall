<?php
defined( 'ABSPATH' ) || exit;

class PCI_Scraper_Registry {

	private static $instance = null;
	private $scrapers = array();

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function register( PCI_Scraper $scraper ) {
		$this->scrapers[ strtoupper( $scraper->vend_code() ) ] = $scraper;
	}

	/** @return PCI_Scraper|null */
	public function for_vend( $vend ) {
		$vend = strtoupper( trim( $vend ) );
		return isset( $this->scrapers[ $vend ] ) ? $this->scrapers[ $vend ] : null;
	}

	public function all() {
		return $this->scrapers;
	}

	public function supported_codes() {
		return array_keys( $this->scrapers );
	}

	/**
	 * Fetch one SKU and cache the result on the staged item row.
	 *
	 * Nothing is created in WooCommerce here. Scraped data is parked on the
	 * item so a human can review title, price and image before a product
	 * exists — the same review-then-commit shape as the stock side.
	 *
	 * @return array|WP_Error
	 */
	public function fetch_for_item( $item_id ) {
		global $wpdb;
		$table = PCI_Schema::table( 'items' );

		$item = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", (int) $item_id ) );
		if ( ! $item ) {
			return new WP_Error( 'pci_no_item', __( 'That staged item no longer exists.', 'pci' ) );
		}

		$scraper = $this->for_vend( $item->vend );
		if ( ! $scraper ) {
			return new WP_Error(
				'pci_no_scraper',
				sprintf( __( 'No adapter is written for supplier %s yet.', 'pci' ), $item->vend )
			);
		}

		$data = $scraper->fetch( $item->sku );
		if ( is_wp_error( $data ) ) {
			return $data;
		}

		$raw = json_decode( (string) $item->raw, true );
		if ( ! is_array( $raw ) ) {
			$raw = array();
		}
		$raw['scraped']    = $data;
		$raw['scraped_at'] = current_time( 'mysql' );

		$wpdb->update( $table, array( 'raw' => wp_json_encode( $raw ) ), array( 'id' => (int) $item_id ) );

		return $data;
	}

	/**
	 * Pull a remote image into the media library.
	 *
	 * @return int|WP_Error Attachment ID.
	 */
	public static function sideload_image( $url, $filename_hint = '' ) {
		if ( empty( $url ) ) {
			return new WP_Error( 'pci_no_image', __( 'No image URL was supplied.', 'pci' ) );
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$tmp = download_url( $url, 30 );
		if ( is_wp_error( $tmp ) ) {
			return $tmp;
		}

		$type = wp_getimagesize( $tmp );
		if ( empty( $type ) ) {
			@unlink( $tmp );
			return new WP_Error( 'pci_not_image', __( 'That URL did not return an image.', 'pci' ) );
		}

		$name = $filename_hint ? sanitize_file_name( $filename_hint ) : basename( wp_parse_url( $url, PHP_URL_PATH ) );
		if ( ! preg_match( '/\.(jpe?g|png|gif|webp)$/i', $name ) ) {
			$name .= '.jpg';
		}

		$attachment_id = media_handle_sideload(
			array( 'name' => $name, 'tmp_name' => $tmp ),
			0
		);

		if ( is_wp_error( $attachment_id ) ) {
			@unlink( $tmp );
			return $attachment_id;
		}

		return (int) $attachment_id;
	}
}
