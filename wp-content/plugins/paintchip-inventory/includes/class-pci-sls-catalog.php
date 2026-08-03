<?php
defined( 'ABSPATH' ) || exit;

/**
 * Builds a local index of the SLS catalog from its listing pages.
 *
 * The per-SKU item page carries almost nothing. Everything useful — UPC, MSRP,
 * dealer net, stock on hand, and a usable image path — is on the category
 * listing (`fright_itemlist.asp`), one row per product. That is also how the
 * original CodeIgniter tool did it, reading the listing table rather than a
 * product page.
 *
 * So: crawl listings once, index every row by SKU, then sourcing becomes a
 * local lookup instead of a request per product. One page yields dozens of
 * products, and the categories arrive with them.
 */
class PCI_SLS_Catalog {

	const BASE = 'https://www.slsarts.com/';

	public static function table() {
		global $wpdb;
		return $wpdb->prefix . 'pci_catalog';
	}

	// ----------------------------------------------------------------- parse

	/**
	 * Pull every product row out of a listing page.
	 *
	 * Row shape, from the live markup:
	 *   td1  thumbnail, with viewitem.asp?slssku=SKU in its onClick
	 *   td2  SKU as link text, plus a hidden osN input holding the same
	 *   td3  DESCRIPTION <br> UPC
	 *   td4  quantity box (hidden osN = SKU)
	 *   td5  QOH as N-nn / V-nn
	 *   td6  DS min   td7  WH min   td8  MSRP   td9  disc   td10  net
	 *
	 * @return array{items:array,category:array,description:string,csv_url:string}
	 */
	public static function parse_listing( $html ) {
		$out = array(
			'items'       => array(),
			'category'    => array(),
			'description' => '',
			'csv_url'     => '',
		);

		if ( ! is_string( $html ) || '' === $html ) {
			return $out;
		}

		// Breadcrumb: hidden levelN inputs are the authoritative category path.
		for ( $i = 1; $i <= 6; $i++ ) {
			if ( preg_match( "/name='level{$i}'\s+value='([^']*)'/i", $html, $m )
				|| preg_match( "/name=\"level{$i}\"\s+value=\"([^\"]*)\"/i", $html, $m ) ) {
				$v = trim( $m[1] );
				if ( '' !== $v ) {
					$out['category'][] = $v;
				}
			}
		}

		// Category blurb, reusable as a description when a product has none.
		if ( preg_match( '#<h3>(.*?)</h3>(.*?)</font>#is', $html, $m ) ) {
			$out['description'] = trim( preg_replace( '/\s+/', ' ',
				wp_strip_all_tags( $m[1] . '. ' . $m[2] ) ) );
		}

		// The CSV export link, which is a far better source than this HTML.
		if ( preg_match( "/getreport\('([^']+)'\)/i", $html, $m ) ) {
			$out['csv_url'] = self::absolutise( html_entity_decode( $m[1], ENT_QUOTES, 'UTF-8' ) );
		}

		// Split on rows, then read each one.
		$rows = preg_split( '/<tr\b[^>]*>/i', $html );

		foreach ( $rows as $row ) {
			if ( false === stripos( $row, 'slssku=' ) ) {
				continue;
			}

			$item = self::parse_row( $row );
			if ( $item ) {
				$item['categories'] = $out['category'];
				if ( '' === $item['description'] && '' !== $out['description'] ) {
					$item['category_copy'] = $out['description'];
				}
				$out['items'][] = $item;
			}
		}

		return $out;
	}

	/** @return array|null */
	private static function parse_row( $row ) {
		$sku = '';
		if ( preg_match( "/name='os\d+'\s+value='([^']+)'/i", $row, $m ) ) {
			$sku = trim( $m[1] );
		} elseif ( preg_match( '/slssku=([A-Za-z0-9\-\.]+)/i', $row, $m ) ) {
			$sku = trim( $m[1] );
		}

		if ( '' === $sku ) {
			return null;
		}

		$item = array(
			'sku'         => $sku,
			'title'       => '',
			'upc'         => '',
			'description' => '',
			'msrp'        => '',
			'net'         => '',
			'disc'        => '',
			'qoh'         => 0,
			'qoh_detail'  => '',
			'image_url'   => '',
			'thumb_url'   => '',
		);

		// Description cell: text, <br>, then the UPC.
		if ( preg_match( "#<td[^>]*width='29%'[^>]*>\s*<font[^>]*>(.*?)</font#is", $row, $m ) ) {
			$cell = $m[1];
			$bits = preg_split( '#<br\s*/?>#i', $cell, 2 );
			$item['title'] = trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( $bits[0] ) ) );
			if ( isset( $bits[1] ) && preg_match( '/([0-9]{8,14})/', wp_strip_all_tags( $bits[1] ), $u ) ) {
				$item['upc'] = $u[1];
			}
		}

		// Fall back to the SKU link text if the description cell moved.
        if ( '' === $item['title'] && preg_match( '#>\s*' . preg_quote( $sku, '#' ) . '\s*<#i', $row ) ) {
			$item['title'] = $sku;
		}

		// Stock on hand, split across warehouses: N = New Orleans, V = Vegas.
		if ( preg_match_all( '/<span>([NVD])-(\d+)<\/span>/i', $row, $q, PREG_SET_ORDER ) ) {
			$total = 0;
			$parts = array();
			foreach ( $q as $hit ) {
				$total  += (int) $hit[2];
				$parts[] = strtoupper( $hit[1] ) . '-' . (int) $hit[2];
			}
			$item['qoh']        = $total;
			$item['qoh_detail'] = implode( ' ', $parts );
		}

		// Money columns, in document order: MSRP, discount, net.
		if ( preg_match_all( "/<td[^>]*align='right'[^>]*>\s*<font[^>]*>\s*\\\$?([0-9]+(?:\.[0-9]{2})?)/i", $row, $money ) ) {
			$vals = $money[1];
			$n    = count( $vals );
			if ( $n >= 3 ) {
				$item['msrp'] = $vals[ $n - 3 ];
				$item['disc'] = $vals[ $n - 2 ];
				$item['net']  = $vals[ $n - 1 ];
			}
		}

		// Image. The page's own onimgError walks thumbnails -> Small -> Regular
		// -> Large, so the large variant is derivable from the thumbnail path.
		if ( preg_match( '/<img[^>]+src="([^"]*Product Images[^"]*)"/i', $row, $m ) ) {
			$thumb              = html_entity_decode( $m[1], ENT_QUOTES, 'UTF-8' );
			$item['thumb_url']  = self::absolutise( $thumb );
			$item['image_url']  = self::absolutise( str_ireplace(
				array( '/thumbnails/', '/Thumbnails/' ),
				'/Large Images/',
				$thumb
			) );
		}

		return $item;
	}

	public static function absolutise( $path ) {
		$path = str_replace( '\\', '/', trim( (string) $path ) );
		$path = preg_replace( '#^\./#', '', $path );

		if ( preg_match( '#^https?://#i', $path ) ) {
			$url = $path;
		} else {
			$url = self::BASE . ltrim( $path, '/' );
		}

		$parts = wp_parse_url( $url );
		if ( ! empty( $parts['path'] ) ) {
			$enc = implode( '/', array_map( 'rawurlencode', explode( '/', $parts['path'] ) ) );
			$url = $parts['scheme'] . '://' . $parts['host'] . $enc;
			if ( ! empty( $parts['query'] ) ) {
				$url .= '?' . $parts['query'];
			}
		}

		return $url;
	}

	/** Sub-category links on a directory page. */
	public static function parse_directory( $html ) {
		$links = array();
		if ( preg_match_all( '#<a[^>]+href=[\'"]([^\'"]*fright_itemlist\.asp[^\'"]*)[\'"][^>]*>(.*?)</a>#is', $html, $m, PREG_SET_ORDER ) ) {
			foreach ( $m as $hit ) {
				$url = self::absolutise( html_entity_decode( str_replace( ' ', '%20', $hit[1] ), ENT_QUOTES, 'UTF-8' ) );
				$links[ $url ] = trim( wp_strip_all_tags( $hit[2] ) );
			}
		}
		return $links;
	}

	// ----------------------------------------------------------------- index

	/** @return array{added:int,updated:int} */
	public static function index_items( array $items ) {
		global $wpdb;
		$t       = self::table();
		$added   = 0;
		$updated = 0;

		foreach ( $items as $item ) {
			if ( empty( $item['sku'] ) ) {
				continue;
			}

			$exists = (int) $wpdb->get_var( $wpdb->prepare(
				"SELECT COUNT(*) FROM {$t} WHERE sku = %s", $item['sku']
			) );

			$data = array(
				'sku'        => substr( $item['sku'], 0, 100 ),
				'vend'       => 'SS',
				'title'      => substr( (string) $item['title'], 0, 250 ),
				'upc'        => substr( (string) $item['upc'], 0, 20 ),
				'msrp'       => '' !== $item['msrp'] ? $item['msrp'] : null,
				'net'        => '' !== $item['net'] ? $item['net'] : null,
				'qoh'        => (int) $item['qoh'],
				'image_url'  => substr( (string) $item['image_url'], 0, 500 ),
				'categories' => wp_json_encode( $item['categories'] ),
				'raw'        => wp_json_encode( $item ),
				'updated_at' => current_time( 'mysql' ),
			);

			if ( $exists ) {
				$wpdb->update( $t, $data, array( 'sku' => $item['sku'] ) );
				$updated++;
			} else {
				$wpdb->insert( $t, $data );
				$added++;
			}
		}

		return array( 'added' => $added, 'updated' => $updated );
	}

	/** @return object|null */
	public static function lookup( $sku ) {
		global $wpdb;
		$t = self::table();
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$t} WHERE sku = %s", trim( (string) $sku ) ) );
	}

	public static function stats() {
		global $wpdb;
		$t = self::table();
		return array(
			'total'     => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$t}" ),
			'with_upc'  => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$t} WHERE upc <> ''" ),
			'with_img'  => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$t} WHERE image_url <> ''" ),
			'updated'   => $wpdb->get_var( "SELECT MAX(updated_at) FROM {$t}" ),
		);
	}

	/**
	 * Fetch one listing URL and index what it holds.
	 *
	 * @return array{ok:bool,message:string,found:int,added:int,updated:int,category:string}
	 */
	public static function crawl_url( $url ) {
		$scraper = new PCI_Scraper_SLS();
		$html    = PCI_Scraper_SLS::portal_enabled() ? $scraper->portal_get( $url ) : ( new PCI_Http( 'SS' ) )->get( $url );

		if ( is_wp_error( $html ) ) {
			return array( 'ok' => false, 'message' => $html->get_error_message(), 'found' => 0, 'added' => 0, 'updated' => 0, 'category' => '' );
		}

		$parsed = self::parse_listing( $html );

		if ( empty( $parsed['items'] ) ) {
			return array(
				'ok'      => false,
				'message' => __( 'That page loaded but held no product rows. Check it is an item listing rather than a category directory.', 'pci' ),
				'found'   => 0, 'added' => 0, 'updated' => 0,
				'category' => implode( ' > ', $parsed['category'] ),
			);
		}

		$res = self::index_items( $parsed['items'] );

		return array(
			'ok'       => true,
			'message'  => sprintf( __( '%1$d products found: %2$d new, %3$d updated.', 'pci' ), count( $parsed['items'] ), $res['added'], $res['updated'] ),
			'found'    => count( $parsed['items'] ),
			'added'    => $res['added'],
			'updated'  => $res['updated'],
			'category' => implode( ' > ', $parsed['category'] ),
			'csv_url'  => $parsed['csv_url'],
		);
	}
}
