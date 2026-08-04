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
	 * Check the token against a UPC that is certainly in their database.
	 *
	 * @return array{ok:bool,message:string}
	 */
	public static function test_token() {
		if ( ! self::is_configured() ) {
			return array( 'ok' => false, 'message' => __( 'No token is set.', 'pci' ) );
		}

		// Coca-Cola 12oz can — about as well-known as a barcode gets.
		delete_transient( 'pci_upc_' . md5( '049000006344' ) );
		$res = self::lookup( '049000006344' );

		if ( is_wp_error( $res ) ) {
			return array(
				'ok'      => false,
				'message' => $res->get_error_message() . ' '
					. ( 'pci_upc_auth' === $res->get_error_code()
						? __( 'That points at the token rather than the data.', 'pci' )
						: __( 'The token appears to work, but this well-known barcode was not found — which is odd.', 'pci' ) ),
			);
		}

		return array(
			'ok'      => true,
			'message' => sprintf( __( 'Token works: "%s" returned.', 'pci' ), $res['title'] ),
		);
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

		$code = isset( $json['item_response']['code'] ) ? (int) $json['item_response']['code'] : 0;
		$note = isset( $json['item_response']['message'] ) ? (string) $json['item_response']['message'] : '';

		if ( 200 !== $code ) {
			// Distinguish a genuine miss from a rejected key. Reporting an
			// auth failure as "no record" hides an expired token behind what
			// looks like ordinary absence, and every lookup then fails
			// silently.
			$auth = in_array( $code, array( 101, 102, 103, 104, 105, 401, 403 ), true )
				|| preg_match( '/token|auth|key|denied|limit|quota/i', $note );

			if ( $auth ) {
				return new WP_Error(
					'pci_upc_auth',
					sprintf(
						__( 'Barcode Spider rejected the request (code %1$d%2$s). The token is probably expired or out of quota.', 'pci' ),
						$code,
						$note ? ': ' . $note : ''
					)
				);
			}

			set_transient( $key, 'miss', self::CACHE_TTL );

			return new WP_Error(
				'pci_upc_miss',
				sprintf(
					__( 'Barcode Spider has no record for UPC %1$s (code %2$d%3$s).', 'pci' ),
					$upc,
					$code,
					$note ? ': ' . $note : ''
				)
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
