<?php
defined( 'ABSPATH' ) || exit;

/**
 * SLS Arts (report Vend code "SS").
 *
 * No login. Confirmed two ways: the legacy Extractor.php never set a cookie
 * jar, credentials or auth header anywhere in 5,145 lines, and the item pages
 * are indexed by public search engines. A dealer login only unlocks *cost*
 * pricing, which we already have in the POS report — so nothing we need here
 * is behind the wall.
 *
 * Two endpoints:
 *
 *   viewitem.asp?slssku=SKU      item detail: title, copy, UPC, MSRP, image
 *   visual_right.asp?txtfind=SKU search: yields level1/level2 category names
 *
 * A verified response for SKU MW200630 contained "UPC-4250397624273", an MSRP
 * of $12.15, and an image at
 * /images/Product Images/Regular Images/MW/200630.jpg — i.e. the first two
 * characters of the SKU are the brand folder and the remainder is the
 * filename, which matches the image naming in the legacy uploads folder.
 */
class PCI_Scraper_SLS implements PCI_Scraper {

	const BASE      = 'https://www.slsarts.com/';
	const ITEM_URL  = 'https://www.slsarts.com/viewitem.asp?slssku=%s';
	const FIND_URL  = 'https://www.slsarts.com/visual_right.asp?txtfind=%s';
	const CACHE_TTL = DAY_IN_SECONDS;

	// Client portal. The public site carries only minimal data; UPCs and fuller
	// descriptions live behind the dealer login.
	const PORTAL_LOGIN = 'https://m.slsarts.com/slsmobile_login.asp';
	const PORTAL_ITEM  = 'https://m.slsarts.com/slsmobile.asp?slssku=%s';

	const OPT_PORTAL_ENABLED = 'pci_sls_portal_enabled';
	const OPT_PORTAL_MAP     = 'pci_sls_portal_map';
	const OPT_PORTAL_ITEMURL = 'pci_sls_portal_itemurl';

	/** @var PCI_Http|null */
	private $http = null;

	private function http() {
		if ( null === $this->http ) {
			$this->http = new PCI_Http( 'SS' );
		}
		return $this->http;
	}

	public static function portal_enabled() {
		return (bool) get_option( self::OPT_PORTAL_ENABLED, false )
			&& PCI_Http::has_credentials( 'SS' );
	}

	/**
	 * Field mapping discovered by the setup screen.
	 *
	 * @return array{action:string,user_field:string,pass_field:string,extra:array}
	 */
	public static function portal_map() {
		$m = get_option( self::OPT_PORTAL_MAP, array() );
		return wp_parse_args( is_array( $m ) ? $m : array(), array(
			'action'     => '',
			'user_field' => '',
			'pass_field' => '',
			'extra'      => array(),
		) );
	}

	public static function portal_item_url( $sku ) {
		$tpl = get_option( self::OPT_PORTAL_ITEMURL, self::PORTAL_ITEM );
		if ( false === strpos( (string) $tpl, '%s' ) ) {
			$tpl = self::PORTAL_ITEM;
		}
		return sprintf( $tpl, rawurlencode( trim( (string) $sku ) ) );
	}

	/**
	 * Authenticate against the portal.
	 *
	 * @param bool $force Ignore any existing session.
	 * @return true|WP_Error
	 */
	public function portal_login( $force = false ) {
		$map = self::portal_map();
		if ( '' === $map['action'] || '' === $map['user_field'] || '' === $map['pass_field'] ) {
			return new WP_Error( 'pci_portal_unmapped', __( 'The portal login form has not been mapped yet. Run Detect on the Portal setup screen.', 'pci' ) );
		}

		$creds = PCI_Http::get_credentials( 'SS' );
		if ( '' === $creds['user'] ) {
			return new WP_Error( 'pci_portal_nocreds', __( 'No portal credentials are saved.', 'pci' ) );
		}

		$http = $this->http();
		if ( $force ) {
			$http->clear_session();
		}

		// Load the login page first: ASP hands out its session cookie there, and
		// posting without it is rejected.
		$http->get( self::PORTAL_LOGIN );

		$fields = is_array( $map['extra'] ) ? $map['extra'] : array();
		$fields[ $map['user_field'] ] = $creds['user'];
		$fields[ $map['pass_field'] ] = $creds['pass'];

		$body = $http->post( $map['action'], $fields );
		if ( is_wp_error( $body ) ) {
			return $body;
		}

		if ( self::looks_logged_out( $body ) ) {
			return new WP_Error(
				'pci_portal_denied',
				__( 'The portal rejected those credentials, or the login form has changed. Re-run Detect and check the username and password.', 'pci' ),
				array( 'excerpt' => self::excerpt( $body ) )
			);
		}

		return true;
	}

	/** Heuristic: does this response look like a login wall rather than content? */
	public static function looks_logged_out( $html ) {
		if ( ! is_string( $html ) || '' === $html ) {
			return true;
		}
		$t = strtolower( wp_strip_all_tags( $html ) );
		if ( false !== strpos( $t, 'invalid' ) && false !== strpos( $t, 'password' ) ) {
			return true;
		}
		if ( preg_match( '/type\s*=\s*["\']password["\']/i', $html ) ) {
			return true;
		}
		return false;
	}

	public static function excerpt( $html, $len = 600 ) {
		$t = trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( (string) $html ) ) );
		return function_exists( 'mb_substr' ) ? mb_substr( $t, 0, $len ) : substr( $t, 0, $len );
	}

	/**
	 * Fetch a portal page, logging in once if the session has lapsed.
	 *
	 * @return string|WP_Error
	 */
	public function portal_get( $url ) {
		$http = $this->http();

		if ( ! $http->has_session() ) {
			$ok = $this->portal_login();
			if ( is_wp_error( $ok ) ) {
				return $ok;
			}
		}

		$body = $http->get( $url );
		if ( is_wp_error( $body ) ) {
			return $body;
		}

		if ( self::looks_logged_out( $body ) ) {
			$ok = $this->portal_login( true );
			if ( is_wp_error( $ok ) ) {
				return $ok;
			}
			$body = $http->get( $url );
			if ( is_wp_error( $body ) ) {
				return $body;
			}
			if ( self::looks_logged_out( $body ) ) {
				return new WP_Error( 'pci_portal_session', __( 'Logged in, but the portal still returned a login page for that item.', 'pci' ), array( 'url' => $url ) );
			}
		}

		return $body;
	}

	/**
	 * Pull the extra fields the portal exposes and merge them over the public
	 * data. The public site stays the base because it reliably yields a title
	 * and image; the portal fills in UPC and description.
	 */
	private function enrich_from_portal( $sku, array $out ) {
		$url  = self::portal_item_url( $sku );
		$body = $this->portal_get( $url );

		if ( is_wp_error( $body ) ) {
			$out['portal_error'] = $body->get_error_message();
			$out['portal_url']   = $url;
			return $out;
		}

		$out['portal_url'] = $url;
		$text = trim( preg_replace( '/[ \t]+/', ' ', html_entity_decode( wp_strip_all_tags(
			preg_replace( '#<(script|style)\b.*?</\1>#is', ' ',
				preg_replace( '#<br\s*/?>|</t[dhr]>|</p>|</div>#i', "\n", $body ) )
		), ENT_QUOTES, 'UTF-8' ) ) );

		if ( empty( $out['upc'] ) && preg_match( '/\b(?:UPC|GTIN|EAN)[^0-9]{0,12}([0-9]{8,14})\b/i', $text, $m ) ) {
			$out['upc'] = $m[1];
		}
		if ( empty( $out['upc'] ) && preg_match( '/\b([0-9]{12,13})\b/', $text, $m ) ) {
			$out['upc'] = $m[1];
		}
		if ( empty( $out['msrp'] ) && preg_match( '/(?:MSRP|Retail|List)[^0-9$]{0,12}\$?\s*([0-9]+(?:\.[0-9]{2})?)/i', $text, $m ) ) {
			$out['msrp'] = $m[1];
		}
		if ( empty( $out['title'] ) && preg_match( '/' . preg_quote( $sku, '/' ) . '\s+(.{3,160}?)\s*(?:\n|$)/is', $text, $m ) ) {
			$out['title'] = $this->tidy_title( $m[1] );
		}

		$img = $this->extract_image( $body, $sku );
		if ( $img && ( empty( $out['image_url'] ) || false !== stripos( (string) $out['image_url'], 'thumbnail' ) ) ) {
			$out['image_url'] = $img;
		}

		$out['portal_bytes'] = strlen( $body );
		$out['portal_used']  = true;

		return $out;
	}

	public function vend_code() {
		return 'SS';
	}

	public function name() {
		return 'SLS Arts';
	}

	public function is_available() {
		$res = wp_remote_head( self::BASE, array( 'timeout' => 10 ) );
		return ! is_wp_error( $res ) && wp_remote_retrieve_response_code( $res ) < 500;
	}

	private function get( $url ) {
		$key    = 'pci_sls_' . md5( $url );
		$cached = get_transient( $key );
		if ( false !== $cached ) {
			return $cached;
		}

		$res = wp_remote_get(
			$url,
			array(
				'timeout'     => 30,
				'redirection' => 5,
				'user-agent'  => 'ThePaintChip-InventorySync/1.0 (+https://thepaint-chip.com)',
				'headers'     => array( 'Accept' => 'text/html' ),
			)
		);

		if ( is_wp_error( $res ) ) {
			return $res;
		}

		$code = wp_remote_retrieve_response_code( $res );
		if ( 200 !== (int) $code ) {
			return new WP_Error( 'pci_http', sprintf( __( 'SLS returned HTTP %d.', 'pci' ), $code ) );
		}

		$body = wp_remote_retrieve_body( $res );
		set_transient( $key, $body, self::CACHE_TTL );

		return $body;
	}

	/** The item-detail URL this adapter will request for a SKU. */
	public static function item_url( $sku ) {
		return sprintf( self::ITEM_URL, rawurlencode( trim( (string) $sku ) ) );
	}

	/** The catalog search URL, useful when the item URL comes back empty. */
	public static function search_url( $sku ) {
		return sprintf( self::FIND_URL, rawurlencode( trim( (string) $sku ) ) );
	}

	/** Build the predictable image URL for a SKU, e.g. MW200630 -> MW/200630.jpg */
	public static function guess_image_url( $sku ) {
		$sku = strtoupper( preg_replace( '/[^A-Za-z0-9]/', '', (string) $sku ) );
		if ( strlen( $sku ) < 3 ) {
			return '';
		}
		$brand = substr( $sku, 0, 2 );
		$rest  = substr( $sku, 2 );
		return self::BASE . 'images/Product Images/Regular Images/' . $brand . '/' . $rest . '.jpg';
	}

	public function fetch( $sku ) {
		$sku = trim( (string) $sku );
		if ( '' === $sku ) {
			return new WP_Error( 'pci_no_sku', __( 'No SKU was supplied.', 'pci' ) );
		}


		$url    = self::item_url( $sku );
		$search = self::search_url( $sku );
		$ctx    = array( 'url' => $url, 'search_url' => $search );

		$html = $this->get( $url );
		if ( is_wp_error( $html ) ) {
			return new WP_Error( $html->get_error_code(), $html->get_error_message(), $ctx );
		}

		$text = $this->to_text( $html );

		// The page renders even for unknown SKUs, so confirm ours came back.
		if ( false === stripos( $text, $sku ) ) {
			// The public catalog does not list everything. If the portal is
			// configured, it is worth asking there before giving up.
			if ( self::portal_enabled() ) {
				$only = $this->enrich_from_portal( $sku, array(
					'sku' => $sku, 'title' => '', 'description' => '', 'upc' => '',
					'msrp' => '', 'image_url' => '', 'categories' => array(),
					'source_url' => $url, 'search_url' => $search,
				) );
				if ( ! empty( $only['title'] ) ) {
					$only['title_source'] = 'portal';
					return $only;
				}
			}

			return new WP_Error(
				'pci_not_found',
				sprintf( __( 'SLS returned a page for %s but it did not contain that SKU.', 'pci' ), $sku ),
				$ctx
			);
		}

		$out = array(
			'sku'         => $sku,
			'title'       => '',
			'description' => '',
			'upc'         => '',
			'msrp'        => '',
			'image_url'   => '',
			'categories'  => array(),
			'source_url'  => $url,
			'search_url'  => $search,
			'bytes'       => strlen( $html ),
		);

		// "SKU: MW200630   MOLOTOW ACRYLIC PAINT MARKER 4MM POP DISPLAY"
		if ( preg_match( '/SKU:\s*' . preg_quote( $sku, '/' ) . '\s+(.{3,200}?)\s*(?:\r|\n|<|$)/is', $text, $m ) ) {
			$out['title'] = $this->tidy_title( $m[1] );
		}

		if ( preg_match( '/UPC-\s*([0-9]{8,14})/i', $text, $m ) ) {
			$out['upc'] = $m[1];
		}

		if ( preg_match( '/\$\s*([0-9]+(?:\.[0-9]{2})?)/', $text, $m ) ) {
			$out['msrp'] = $m[1];
		}

		$out['image_url']  = $this->extract_image( $html, $sku );
		$out['categories'] = $this->fetch_categories( $sku );

		if ( self::portal_enabled() ) {
			$out = $this->enrich_from_portal( $sku, $out );
		}

		// A page that yields no title is a parse failure, not a product.
		if ( '' === $out['title'] ) {
			return new WP_Error(
				'pci_parse',
				sprintf( __( 'The SLS page for %s loaded but no title could be read. The page layout may have changed — check the adapter selectors.', 'pci' ), $sku ),
				$ctx
			);
		}

		return $out;
	}

	private function to_text( $html ) {
		$html = preg_replace( '#<(script|style)\b.*?</\1>#is', ' ', $html );
		$html = preg_replace( '#<br\s*/?>|</t[dhr]>|</p>|</div>#i', "\n", $html );
		$text = wp_strip_all_tags( $html );
		$text = html_entity_decode( $text, ENT_QUOTES, 'UTF-8' );
		return preg_replace( '/[ \t]+/', ' ', $text );
	}

	private function tidy_title( $raw ) {
		$raw = trim( preg_replace( '/\s+/', ' ', $raw ) );
		// The catalog shouts; the storefront should not.
		if ( $raw === strtoupper( $raw ) ) {
			$raw = ucwords( strtolower( $raw ) );
		}
		return $raw;
	}

	/**
	 * Pull the main product image. SLS emits Windows-style backslashes and a
	 * leading "./" in its src attributes, so those get normalised.
	 */
	private function extract_image( $html, $sku ) {
		if ( preg_match_all( '/<img[^>]+src\s*=\s*["\']([^"\']+)["\']/i', $html, $m ) ) {
			foreach ( $m[1] as $src ) {
				if ( false === stripos( $src, 'Product Images' ) ) {
					continue;
				}
				// Prefer the full-size image over a thumbnail.
				if ( false !== stripos( $src, 'thumbnail' ) ) {
					continue;
				}
				return $this->absolutise( $src );
			}
			// Fall back to a thumbnail if that is all there is.
			foreach ( $m[1] as $src ) {
				if ( false !== stripos( $src, 'Product Images' ) ) {
					return $this->absolutise( $src );
				}
			}
		}

		return self::guess_image_url( $sku );
	}

	private function absolutise( $src ) {
		$src = str_replace( '\\', '/', $src );
		$src = preg_replace( '#^\./#', '', $src );
		$src = ltrim( $src, '/' );

		if ( preg_match( '#^https?://#i', $src ) ) {
			$url = $src;
		} else {
			$url = self::BASE . $src;
		}

		// Encode spaces in the path without mangling the scheme or slashes.
		$parts = wp_parse_url( $url );
		if ( ! empty( $parts['path'] ) ) {
			$path = implode( '/', array_map( 'rawurlencode', explode( '/', $parts['path'] ) ) );
			$url  = $parts['scheme'] . '://' . $parts['host'] . $path;
		}

		return $url;
	}

	/** visual_right.asp exposes the catalog breadcrumb as level1/level2 params. */
	private function fetch_categories( $sku ) {
		$html = $this->get( sprintf( self::FIND_URL, rawurlencode( $sku ) ) );
		if ( is_wp_error( $html ) ) {
			return array();
		}

		$cats = array();
		if ( preg_match( '/level1=([^&"\']+)/i', $html, $m ) ) {
			$cats[] = $this->tidy_cat( urldecode( $m[1] ) );
		}
		if ( preg_match( '/level2=([^&"\']+)/i', $html, $m ) ) {
			$cats[] = $this->tidy_cat( urldecode( $m[1] ) );
		}

		return array_values( array_filter( array_unique( $cats ) ) );
	}

	private function tidy_cat( $name ) {
		$name = trim( preg_replace( '/\s+/', ' ', $name ) );
		if ( '' === $name ) {
			return '';
		}
		$name = ucwords( strtolower( $name ) );
		// Carried over from the legacy mapping.
		if ( 'Drawing Supplies' === $name ) {
			$name = 'Pencils And Drawing Supplies';
		}
		return $name;
	}
}
