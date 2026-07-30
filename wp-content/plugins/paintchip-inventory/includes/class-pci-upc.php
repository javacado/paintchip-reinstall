<?php
defined( 'ABSPATH' ) || exit;

/**
 * Barcode Spider lookup, used only to find a product image when SLS has none
 * or the one it has is too small to be worth showing.
 *
 * The legacy Extractor.php called the same API with the token hardcoded in two
 * places. It lives in an option here instead, so it can be rotated without a
 * code change and doesn't sit in the repository.
 */
class PCI_UPC {

	const OPT_TOKEN = 'pci_barcodespider_token';
	const ENDPOINT  = 'https://api.barcodespider.com/v1/lookup';
	const CACHE_TTL = WEEK_IN_SECONDS;

	public static function token() {
		return trim( (string) get_option( self::OPT_TOKEN, '' ) );
	}

	public static function is_configured() {
		return '' !== self::token();
	}

	/**
	 * @param string $upc
	 * @return array|WP_Error Normalised: title, brand, images[]
	 */
	public static function lookup( $upc ) {
		$upc = preg_replace( '/[^0-9]/', '', (string) $upc );

		if ( '' === $upc ) {
			return new WP_Error( 'pci_upc_empty', __( 'No UPC to look up.', 'pci' ) );
		}
		if ( ! self::is_configured() ) {
			return new WP_Error( 'pci_upc_nokey', __( 'No Barcode Spider token is set. Add one under Settings.', 'pci' ) );
		}

		$key    = 'pci_upc_' . md5( $upc );
		$cached = get_transient( $key );
		if ( false !== $cached ) {
			return is_array( $cached ) ? $cached : new WP_Error( 'pci_upc_miss', __( 'No match for this UPC.', 'pci' ) );
		}

		$url = add_query_arg(
			array( 'token' => self::token(), 'upc' => $upc ),
			self::ENDPOINT
		);

		$res = wp_remote_get( $url, array( 'timeout' => 30 ) );
		if ( is_wp_error( $res ) ) {
			return $res;
		}

		$json = json_decode( wp_remote_retrieve_body( $res ), true );

		if ( empty( $json['item_response']['code'] ) || 200 !== (int) $json['item_response']['code'] ) {
			set_transient( $key, 'miss', self::CACHE_TTL );
			return new WP_Error(
				'pci_upc_miss',
				sprintf( __( 'Barcode Spider has no record for UPC %s.', 'pci' ), $upc )
			);
		}

		$item   = isset( $json['item_attributes'] ) ? $json['item_attributes'] : array();
		$images = array();

		foreach ( array( 'image', 'image2', 'image3' ) as $k ) {
			if ( ! empty( $item[ $k ] ) ) {
				$images[] = $item[ $k ];
			}
		}
		if ( ! empty( $json['Stores'] ) && is_array( $json['Stores'] ) ) {
			foreach ( $json['Stores'] as $store ) {
				if ( ! empty( $store['image'] ) ) {
					$images[] = $store['image'];
				}
			}
		}

		$out = array(
			'upc'    => $upc,
			'title'  => isset( $item['title'] ) ? $item['title'] : '',
			'brand'  => isset( $item['brand'] ) ? $item['brand'] : '',
			'images' => array_values( array_unique( array_filter( $images ) ) ),
		);

		set_transient( $key, $out, self::CACHE_TTL );

		return $out;
	}

	/**
	 * First image from the UPC lookup that is at least $min_width wide.
	 *
	 * @return string|WP_Error
	 */
	public static function find_image( $upc, $min_width = 300 ) {
		$data = self::lookup( $upc );
		if ( is_wp_error( $data ) ) {
			return $data;
		}
		if ( empty( $data['images'] ) ) {
			return new WP_Error( 'pci_upc_noimg', __( 'That UPC matched, but carried no images.', 'pci' ) );
		}

		foreach ( $data['images'] as $img ) {
			$size = PCI_Sourcing::remote_image_size( $img );
			if ( $size && $size['width'] >= $min_width ) {
				return $img;
			}
		}

		return new WP_Error( 'pci_upc_small', __( 'Every image found for that UPC was too small.', 'pci' ) );
	}
}
