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

	/**
	 * Read one product row.
	 *
	 * Cells are located by their content, not by their attributes. The markup
	 * varies between categories — single vs double quoted attributes, different
	 * column widths, "Drop Ship Only" where a stock figure normally sits — so
	 * anchoring on `width='29%'` silently produced rows with a SKU and nothing
	 * else. Splitting into cells and identifying them by what they contain is
	 * far more durable.
	 *
	 * @return array|null
	 */
	private static function parse_row( $row ) {
		$sku = '';
		if ( preg_match( '/name=[\'"]os\d+[\'"]\s+value=[\'"]([^\'"]+)[\'"]/i', $row, $m ) ) {
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

		// Split the row into cells.
		$cells = array();
		if ( preg_match_all( '#<td\b[^>]*>(.*?)(?=<td\b|</tr>|$)#is', $row, $cm ) ) {
			$cells = $cm[1];
		}

		$money = array();

		foreach ( $cells as $cell ) {
			$text = trim( preg_replace( '/[ \t]+/', ' ',
				html_entity_decode( wp_strip_all_tags(
					preg_replace( '#<br\s*/?>#i', "\n", $cell )
				), ENT_QUOTES, 'UTF-8' ) ) );

			if ( '' === $text ) {
				continue;
			}

			// Description cell: free text on one line, a long digit run on the
			// next. That pairing is unique to the Description/UPC column.
			if ( '' === $item['title'] && preg_match( '/^(.*\S.*)\n\s*(\d{8,14})\b/s', $text, $d ) ) {
				$title = trim( preg_replace( '/\s+/', ' ', $d[1] ) );
				if ( strtoupper( $title ) !== strtoupper( $sku ) && strlen( $title ) > 3 ) {
					$item['title'] = $title;
					$item['upc']   = $d[2];
					continue;
				}
			}

			// Stock: either N-24 / V-15, or wording like "Drop Ship Only".
			if ( preg_match_all( '/\b([NVD])-(\d+)\b/', $text, $q, PREG_SET_ORDER ) ) {
				$total = 0;
				$parts = array();
				foreach ( $q as $hit ) {
					$total  += (int) $hit[2];
					$parts[] = strtoupper( $hit[1] ) . '-' . (int) $hit[2];
				}
				$item['qoh']        = $total;
				$item['qoh_detail'] = implode( ' ', $parts );
				continue;
			}
			if ( '' === $item['qoh_detail'] && preg_match( '/drop\s*ship/i', $text ) ) {
				$item['qoh_detail'] = 'Drop Ship Only';
				continue;
			}

			// Money and plain numbers, in document order.
			if ( preg_match( '/^\$?\s*([0-9]+(?:\.[0-9]{2})?)$/', $text, $n ) ) {
				$money[] = array( 'value' => $n[1], 'currency' => ( false !== strpos( $text, '$' ) ) );
			}
		}

		// Fall back to any long digit run if the description cell was unusual.
		if ( '' === $item['upc'] && preg_match( '/\b(\d{11,14})\b/', wp_strip_all_tags( $row ), $u ) ) {
			$item['upc'] = $u[1];
		}

		// The last three numeric cells are MSRP, discount and net. Anchoring on
		// the currency-marked ones keeps the DS/WH minimums out of it.
		$cur = array_values( array_filter( $money, function ( $x ) { return $x['currency']; } ) );
		if ( count( $cur ) >= 2 ) {
			$item['msrp'] = $cur[0]['value'];
			$item['net']  = $cur[ count( $cur ) - 1 ]['value'];
			foreach ( $money as $i => $x ) {
				if ( ! $x['currency'] && $i > 0 && (float) $x['value'] > 0 && (float) $x['value'] <= 100 ) {
					$item['disc'] = $x['value'];
				}
			}
		} elseif ( count( $money ) >= 3 ) {
			$n            = count( $money );
			$item['msrp'] = $money[ $n - 3 ]['value'];
			$item['disc'] = $money[ $n - 2 ]['value'];
			$item['net']  = $money[ $n - 1 ]['value'];
		}

		if ( '' === $item['title'] ) {
			$item['title'] = $sku;
		}

		// Image. The page's own onimgError walks thumbnails -> Small -> Regular
		// -> Large, so the large variant is derivable from the thumbnail path.
		if ( preg_match( '/<img[^>]+src=[\'"]([^\'"]*Product Images[^\'"]*)[\'"]/i', $row, $m ) ) {
			$thumb             = html_entity_decode( $m[1], ENT_QUOTES, 'UTF-8' );
			$item['thumb_url'] = self::absolutise( $thumb );
			$item['image_url'] = self::absolutise( str_ireplace(
				array( '/thumbnails/', '/Thumbnails/' ),
				'/Large Images/',
				$thumb
			) );
		}

		return $item;
	}

	// ---------------------------------------------------------------- search

	/**
	 * Look one SKU up directly.
	 *
	 * The listing page takes a txtfind parameter, so a single product can be
	 * fetched in listing context — which is the only context that carries the
	 * UPC and the category path. Far cheaper than crawling the whole tree when
	 * only a few hundred SKUs are wanted.
	 */

	/**
	 * Fetch and index a single SKU via search.
	 *
	 * @return array{ok:bool,message:string,item:array|null}
	 */
	/**
	 * Record that a SKU has been searched for.
	 *
	 * Without this, a SKU that SLS cannot find never enters the catalog, so it
	 * reappears in the next batch of "missing" SKUs and the lookup loop retries
	 * the same handful forever. The attempt log is what makes progress
	 * monotonic.
	 */
	private static function mark_searched( $sku, $found, $note = '' ) {
		global $wpdb;
		$t   = self::crawl_table();
		$url = self::search_url( $sku );

		$wpdb->query( $wpdb->prepare(
			"INSERT INTO {$t} (url, url_hash, kind, label, depth, status, message, created_at, crawled_at)
			 VALUES (%s, %s, 'search', %s, 0, %s, %s, %s, %s)
			 ON DUPLICATE KEY UPDATE status = VALUES(status), message = VALUES(message), crawled_at = VALUES(crawled_at)",
			substr( $url, 0, 500 ),
			sha1( 'search:' . $sku ),
			substr( $sku, 0, 250 ),
			$found ? 'done' : 'failed',
			substr( $note, 0, 250 ),
			current_time( 'mysql' ),
			current_time( 'mysql' )
		) );
	}

	/** Has this SKU already been looked up, whatever the outcome? */
	public static function was_searched( $sku ) {
		global $wpdb;
		return (bool) $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM " . self::crawl_table() . " WHERE kind='search' AND label = %s",
			trim( (string) $sku )
		) );
	}

	/** How many SKUs were searched for and genuinely not found at SLS. */
	public static function not_found_count() {
		global $wpdb;
		return (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM " . self::crawl_table() . " WHERE kind='search' AND status='failed'"
		);
	}

	public static function not_found_skus( $limit = 200 ) {
		global $wpdb;
		return $wpdb->get_col( $wpdb->prepare(
			"SELECT label FROM " . self::crawl_table() . "
			 WHERE kind='search' AND status='failed' ORDER BY label LIMIT %d",
			(int) $limit
		) );
	}

	/** Allow a retry of everything that was not found, e.g. after a fix. */
	public static function clear_not_found() {
		global $wpdb;
		return (int) $wpdb->query(
			"DELETE FROM " . self::crawl_table() . " WHERE kind='search' AND status='failed'"
		);
	}

	/**
	 * Step one of a search: the narrowed category tree.
	 *
	 * The search box posts to fright.asp, not to the listing. Findimg carries
	 * the submit button's own value — sending it empty is what made every
	 * earlier attempt fall through to an unfiltered catalog dump.
	 */
	public static function search_tree_url( $sku ) {
		return self::BASE . 'fright.asp?' . http_build_query( array(
			'txtfind'   => trim( (string) $sku ),
			'brand'     => '',
			'Findimg'   => 'Search Items',
			'findclick' => 'Y',
			'newsearch' => 'Y',
		) );
	}

	/**
	 * Step two: the listing for a category, filtered to the searched SKU.
	 *
	 * The link taken from the search tree may already carry findtype/txtfind,
	 * so those are stripped before being added back. Duplicated parameters are
	 * harmless on some pages and confusing on others.
	 */
	public static function listing_url_for( $listing_url, $sku ) {
		$parts = explode( '?', $listing_url, 2 );
		$base  = $parts[0];
		$query = isset( $parts[1] ) ? $parts[1] : '';

		$keep = array();
		foreach ( explode( '&', $query ) as $pair ) {
			if ( '' === $pair ) {
				continue;
			}
			$name = strtolower( explode( '=', $pair, 2 )[0] );
			if ( 'findtype' === $name || 'txtfind' === $name ) {
				continue;
			}
			$keep[] = $pair;
		}

		$keep[] = 'findtype=';
		$keep[] = 'txtfind=' . rawurlencode( trim( (string) $sku ) );

		return $base . '?' . implode( '&', $keep );
	}

	/** Kept for the debug panel and older callers. */
	public static function search_url( $sku ) {
		return self::search_tree_url( $sku );
	}

	private static function get_page( $url ) {
		if ( PCI_Scraper_SLS::portal_enabled() ) {
			$scraper = new PCI_Scraper_SLS();
			return $scraper->portal_get( $url );
		}
		$http = new PCI_Http( 'SS' );
		return $http->get( $url );
	}

	/**
	 * Search for a SKU and return its listing page.
	 *
	 * Hop one narrows the category tree to wherever the SKU lives; hop two
	 * loads that category's listing with the SKU filter applied. The category
	 * path falls out of hop one for free, which is exactly what product
	 * creation needs anyway.
	 *
	 * @return array{html:string,listing_url:string,path:array}|WP_Error
	 */
	public static function fetch_search( $sku, array $extra = array() ) {
		$tree = self::get_page( self::search_tree_url( $sku ) );
		if ( is_wp_error( $tree ) ) {
			return $tree;
		}

		$links    = self::parse_links( $tree );
		$listings = array();
		foreach ( $links as $url => $meta ) {
			if ( 'listing' === $meta['kind'] ) {
				$listings[ $url ] = $meta['label'];
			}
		}

		if ( empty( $listings ) ) {
			return new WP_Error(
				'pci_no_category',
				__( 'The search did not narrow to any category — SLS most likely does not carry that code.', 'pci' ),
				array( 'tree_bytes' => strlen( $tree ) )
			);
		}

		self::pause();

		// The deepest link is the most specific category.
		$best = '';
		foreach ( array_keys( $listings ) as $url ) {
			if ( substr_count( $url, 'level' ) >= substr_count( $best, 'level' ) ) {
				$best = $url;
			}
		}

		$listing_url = self::listing_url_for( $best, $sku );
		$html        = self::get_page( $listing_url );

		if ( is_wp_error( $html ) ) {
			return $html;
		}

		return array(
			'html'        => $html,
			'listing_url' => $listing_url,
			'candidates'  => array_keys( $listings ),
		);
	}

	/**
	 * One search, with everything needed to see what happened.
	 *
	 * @return array
	 */
	public static function debug_search( $sku, array $extra = array() ) {
		$out = array(
			'sku'        => $sku,
			'frame_url'  => self::search_tree_url( $sku ),
			'search_url' => '',
			'portal'     => PCI_Scraper_SLS::portal_enabled(),
		);

		$res = self::fetch_search( $sku, $extra );

		if ( is_wp_error( $res ) ) {
			$out['error'] = $res->get_error_message();
			return $out;
		}

		$html               = $res['html'];
		$out['search_url']  = $res['listing_url'];

		$parsed = self::parse_listing( $html );

		$skus = array();
		foreach ( $parsed['items'] as $i ) {
			$skus[] = $i['sku'];
		}

		$out['bytes']       = strlen( $html );
		$out['rows']        = count( $parsed['items'] );
		$out['category']    = implode( ' > ', $parsed['category'] );
		$out['first_skus']  = array_slice( $skus, 0, 20 );
		$out['contains']    = ( false !== stripos( $html, $sku ) );
		$out['logged_out']  = PCI_Scraper_SLS::looks_logged_out( $html );
		$out['excerpt']     = PCI_Scraper_SLS::excerpt( $html, 1200 );

		return $out;
	}

	// ----------------------------------------------------- full-catalog page

	const OPT_PAGE_CURSOR = 'pci_sls_page_cursor';
	const OPT_PAGE_DONE   = 'pci_sls_page_done';

	/**
	 * Page through the entire catalog.
	 *
	 * The listing endpoint with no category filter returns the whole catalog in
	 * alphabetical blocks of 500, and the form carries a hidden `vlastsku`
	 * field — the last SKU on the page. Posting that back asks for the block
	 * after it. Sixty-odd requests covers everything, which is far cheaper than
	 * either walking the category tree or searching SKU by SKU.
	 *
	 * @return array
	 */
	/**
	 * Two-letter brand prefixes, used to page the catalog.
	 *
	 * The endpoint caps a result set at 500 and offers no working cursor, so
	 * the catalog is walked in slices instead. SLS SKUs begin with a two-letter
	 * brand code, and a prefix search returns just that brand — comfortably
	 * under the cap for almost all of them.
	 */
	public static function prefixes() {
		$out = array();
		foreach ( range( 'A', 'Z' ) as $a ) {
			foreach ( range( 'A', 'Z' ) as $b ) {
				$out[] = $a . $b;
			}
		}
		return $out;
	}

	public static function page_catalog( $batches = 1 ) {
		$done_list = get_option( self::OPT_PAGE_CURSOR, array() );
		if ( ! is_array( $done_list ) ) {
			$done_list = array();
		}

		$all       = self::prefixes();
		$remaining = array_values( array_diff( $all, $done_list ) );
		$log       = array();
		$done      = false;

		if ( empty( $remaining ) ) {
			update_option( self::OPT_PAGE_DONE, 1, false );
			return array(
				'log'      => array(),
				'cursor'   => sprintf( __( 'all %d prefixes done', 'pci' ), count( $all ) ),
				'done'     => true,
				'catalog'  => self::stats(),
				'coverage' => self::coverage(),
			);
		}

		$fails = 0;

		for ( $b = 0; $b < $batches && ! empty( $remaining ); $b++ ) {
			$prefix = array_shift( $remaining );
			$res    = self::fetch_search( $prefix );
			$html   = is_wp_error( $res ) ? $res : $res['html'];

			if ( is_wp_error( $html ) ) {
				$log[] = array( 'ok' => false, 'label' => $prefix, 'message' => $html->get_error_message() );
				if ( ++$fails >= 3 ) {
					break;
				}
				continue;
			}

			$fails  = 0;
			$parsed = self::parse_listing( $html );
			$n      = count( $parsed['items'] );

			// Keep only rows that really start with this prefix: an unfiltered
			// response would otherwise dump the whole catalog under it.
			$mine = array();
			foreach ( $parsed['items'] as $item ) {
				if ( 0 === stripos( $item['sku'], $prefix ) ) {
					$mine[] = $item;
				}
			}

			$done_list[] = $prefix;
			update_option( self::OPT_PAGE_CURSOR, $done_list, false );

			if ( empty( $mine ) ) {
				$log[] = array(
					'ok'      => false,
					'label'   => $prefix,
					'message' => $n
						? sprintf( __( 'no %1$s products (%2$d unrelated rows ignored)', 'pci' ), $prefix, $n )
						: __( 'nothing returned', 'pci' ),
				);
			} else {
				$res   = self::index_items( $mine );
				$log[] = array(
					'ok'      => true,
					'label'   => $prefix,
					'message' => sprintf(
						__( '%1$d products (%2$d new, %3$d updated)%4$s', 'pci' ),
						count( $mine ),
						$res['added'],
						$res['updated'],
						count( $mine ) >= 495 ? __( ' — at the 500 cap, may be truncated', 'pci' ) : ''
					),
				);
			}

			self::pause();
		}

		if ( empty( $remaining ) ) {
			$done = true;
			update_option( self::OPT_PAGE_DONE, 1, false );
		}

		return array(
			'log'      => $log,
			'cursor'   => sprintf( __( '%1$d of %2$d prefixes', 'pci' ), count( $done_list ), count( $all ) ),
			'done'     => $done,
			'catalog'  => self::stats(),
			'coverage' => self::coverage(),
		);
	}

	public static function reset_paging() {
		delete_option( self::OPT_PAGE_CURSOR );
		delete_option( self::OPT_PAGE_DONE );
	}

	public static function paging_state() {
		return array(
			'cursor' => (string) get_option( self::OPT_PAGE_CURSOR, '' ),
			'done'   => (bool) get_option( self::OPT_PAGE_DONE, false ),
		);
	}

	public static function find_sku( $sku ) {
		$sku = trim( (string) $sku );
		if ( '' === $sku ) {
			return array( 'ok' => false, 'message' => __( 'No SKU given.', 'pci' ), 'item' => null, 'transport' => false );
		}

		$res = self::fetch_search( $sku );

		if ( ! is_wp_error( $res ) ) {
			$html = $res['html'];
		} else {
			$html = $res;
		}

		if ( is_wp_error( $html ) ) {
			$code = $html->get_error_code();

			// "The search found no category" is a definitive answer from SLS,
			// not a failure to reach it. Recording it is what stops the SKU
			// coming back round on the next batch forever.
			if ( 'pci_no_category' === $code || 'pci_not_found' === $code ) {
				self::mark_searched( $sku, false, 'search matched no category' );
				return array(
					'ok'        => false,
					'message'   => $html->get_error_message(),
					'item'      => null,
					'transport' => false,
				);
			}

			// Anything else is a genuine transport problem: leave it
			// unrecorded so it can be retried, but flag it so the caller can
			// stop rather than hammer an unreachable server.
			return array(
				'ok'        => false,
				'message'   => $html->get_error_message(),
				'item'      => null,
				'transport' => true,
			);
		}

		$parsed = self::parse_listing( $html );
		$n      = count( $parsed['items'] );

		// A search with no hits returns a capped default listing rather than an
		// empty page, so a large result set means "not stocked", not "many
		// matches".
		if ( $n > 25 ) {
			self::mark_searched( $sku, false, sprintf( 'no match — default listing of %d returned', $n ) );
			return array(
				'ok'      => false,
				'message' => __( 'SLS has no product matching that code.', 'pci' ),
				'item'    => null,
				'candidates' => array(),
			);
		}

		if ( 0 === $n ) {
			// An expired session returns a login page, which parses as zero
			// products. Marking that "not stocked" would quietly condemn good
			// products, so the session is verified before accepting the result.
			if ( PCI_Scraper_SLS::portal_enabled() && PCI_Scraper_SLS::looks_logged_out( $html ) ) {
				return array(
					'ok'         => false,
					'message'    => __( 'The portal session had expired — not recorded, will retry.', 'pci' ),
					'item'       => null,
					'candidates' => array(),
					'transport'  => true,
				);
			}

			self::mark_searched( $sku, false, 'no results' );
			return array( 'ok' => false, 'message' => __( 'SLS returned no product for that code.', 'pci' ), 'item' => null, 'candidates' => array() );
		}

		// Everything returned is a real product, so index it regardless.
		self::index_items( $parsed['items'] );

		// Exact match wins.
		foreach ( $parsed['items'] as $item ) {
			if ( 0 === strcasecmp( $item['sku'], $sku ) ) {
				self::mark_searched( $sku, true, 'exact' );
				return array(
					'ok'      => true,
					'message' => sprintf( __( 'Found: %s', 'pci' ), $item['title'] ),
					'item'    => $item,
					'candidates' => array(),
				);
			}
		}

		// The report often carries a shortened form of the supplier code —
		// MTEX014P for MTEX014PM9010 — so a SKU that is a prefix of exactly one
		// catalog entry is safe to accept. Several prefix hits are ambiguous
		// and are left for a person to resolve.
		$prefix = array();
		foreach ( $parsed['items'] as $item ) {
			if ( 0 === stripos( $item['sku'], $sku ) ) {
				$prefix[] = $item;
			}
		}

		if ( 1 === count( $prefix ) ) {
			self::record_alias( $sku, $prefix[0]['sku'] );
			self::mark_searched( $sku, true, 'prefix: ' . $prefix[0]['sku'] );
			return array(
				'ok'      => true,
				'message' => sprintf( __( 'Matched %1$s to %2$s — %3$s', 'pci' ), $sku, $prefix[0]['sku'], $prefix[0]['title'] ),
				'item'    => $prefix[0],
				'candidates' => array(),
			);
		}

		if ( count( $prefix ) > 1 ) {
			$codes = array();
			foreach ( $prefix as $p ) {
				$codes[] = $p['sku'];
			}
			self::mark_searched( $sku, false, 'ambiguous: ' . implode( ' ', $codes ) );
			return array(
				'ok'      => false,
				'message' => sprintf( __( '%1$d products start with %2$s: %3$s — needs a decision.', 'pci' ), count( $prefix ), $sku, implode( ', ', $codes ) ),
				'item'    => null,
				'candidates' => $codes,
			);
		}

		$codes = array();
		foreach ( $parsed['items'] as $p ) {
			$codes[] = $p['sku'];
		}
		self::mark_searched( $sku, false, 'related only: ' . implode( ' ', array_slice( $codes, 0, 6 ) ) );

		return array(
			'ok'      => false,
			'message' => sprintf( __( '%1$d related products indexed but none begins with %2$s: %3$s', 'pci' ), $n, $sku, implode( ', ', array_slice( $codes, 0, 6 ) ) ),
			'item'    => null,
			'candidates' => $codes,
		);
	}

	/**
	 * Remember that a report code maps to a longer supplier code.
	 *
	 * Stored as a second catalog row under the report's own SKU, so the plain
	 * join used everywhere else keeps working without special cases.
	 */
	public static function record_alias( $report_sku, $supplier_sku ) {
		global $wpdb;
		$t   = self::table();
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$t} WHERE sku = %s", $supplier_sku ), ARRAY_A );

		if ( ! $row ) {
			return false;
		}

		$raw = json_decode( (string) $row['raw'], true );
		if ( ! is_array( $raw ) ) {
			$raw = array();
		}
		$raw['alias_of']     = $supplier_sku;
		$raw['report_sku']   = $report_sku;

		unset( $row['id'] );
		$row['sku']        = $report_sku;
		$row['raw']        = wp_json_encode( $raw );
		$row['updated_at'] = current_time( 'mysql' );

		$exists = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$t} WHERE sku = %s", $report_sku ) );
		if ( $exists ) {
			$wpdb->update( $t, $row, array( 'sku' => $report_sku ) );
		} else {
			$wpdb->insert( $t, $row );
		}

		return true;
	}

	/**
	 * Work through the SKUs we still need, one search each.
	 *
	 * @return array
	 */
	public static function find_missing( $limit = 5 ) {
		$log     = array();
		$stopped = false;

		if ( self::circuit_open() ) {
			return array(
				'log'       => array( array( 'ok' => false, 'label' => __( 'Paused', 'pci' ),
					'message' => __( 'SLS stopped responding. Nothing further will be tried until you reset this.', 'pci' ) ) ),
				'catalog'   => self::stats(),
				'coverage'  => self::coverage(),
				'queue'     => self::queue_stats(),
				'not_found' => self::not_found_count(),
				'stopped'   => true,
			);
		}

		foreach ( self::missing_skus( $limit ) as $sku ) {
			$res = self::find_sku( $sku );

			// Safety net. Every branch of find_sku() should either record the
			// attempt or flag a transport failure, but if one ever forgets,
			// the SKU would return in the next batch forever. Record it here
			// so a missed case costs one wasted lookup, not an endless loop.
			if ( empty( $res['transport'] ) && ! self::was_searched( $sku ) ) {
				self::mark_searched( $sku, ! empty( $res['ok'] ), 'recorded by the loop guard' );
			}

			// Tell a transport failure apart from a real answer. Only the
			// former should ever stop the run.
			// find_sku() flags a transport failure as 'transport'. Reading the
			// wrong key here meant every result looked like a clean answer, so
			// the streak never advanced and a stale latch could never clear.
			$transport = ( ! $res['ok'] && ! empty( $res['transport'] ) );
			self::note_transport( $transport );

			$log[] = array( 'ok' => $res['ok'], 'label' => $sku, 'message' => $res['message'] );

			if ( $transport && self::circuit_open() ) {
				$log[]   = array(
					'ok'      => false,
					'label'   => __( 'Stopped', 'pci' ),
					'message' => sprintf(
						__( '%d requests in a row failed to reach SLS. Stopping so a temporary block does not become a permanent one. Try again later.', 'pci' ),
						self::FAIL_LIMIT
					),
				);
				$stopped = true;
				break;
			}

			self::pause();
		}

		return array(
			'log'       => $log,
			'catalog'   => self::stats(),
			'coverage'  => self::coverage(),
			'queue'     => self::queue_stats(),
			'not_found' => self::not_found_count(),
			'stopped'   => $stopped,
		);
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

	/**
	 * Every catalog link on a page, whether directory or listing.
	 *
	 * The tree renders as JavaScript expanders, but the underlying anchors are
	 * present in the HTML, so following links is enough — no need to model the
	 * tree or know its depth in advance.
	 *
	 * @return array url => array{label:string,kind:string}
	 */
	public static function parse_links( $html ) {
		$links = array();

		// The category tree is not markup. It is a JavaScript array:
		//
		//   var tmenuItems = [ "...", "fright.asp?level1=X&level2=Y", "tm/tm12.asp" ];
		//
		// which the expander widget renders client-side. Anchors alone find
		// nothing, so read the array directly — this is how the original tool
		// walked the tree.
		if ( preg_match( '/var\s+tmenuItems\s*=\s*\[(.*?)\];/is', $html, $tm ) ) {
			if ( preg_match_all( '/"([^"]*)"/', $tm[1], $entries ) ) {
				// The array holds more than label/URL pairs — frame targets and
				// icon paths are in there too — so pick out the URL-shaped
				// entries and take the label from the nearest plain text before
				// each one.
				$skip = array( '_self', '_blank', '_parent', '_top', '_new' );

				$label = '';
				foreach ( $entries[1] as $entry ) {
					$entry = trim( $entry );
					if ( '' === $entry ) {
						continue;
					}

					$lower = strtolower( $entry );

					if ( in_array( $lower, $skip, true ) ) {
						continue;
					}

					$is_tree = ( 0 === stripos( $entry, 'tm/' ) ) || ( '.js' === substr( $lower, -3 ) );
					$is_page = ( false !== stripos( $entry, 'fright' ) && false !== stripos( $entry, '.asp' ) );

					if ( ! $is_tree && ! $is_page ) {
						// Icons and images are not labels either.
						if ( ! preg_match( '/\.(gif|jpe?g|png|ico|css)$/i', $lower ) ) {
							$label = wp_strip_all_tags( $entry );
						}
						continue;
					}

					$url = self::absolutise( str_replace( ' ', '%20', html_entity_decode( $entry, ENT_QUOTES, 'UTF-8' ) ) );

					if ( $is_tree ) {
						$kind = 'tree';
					} elseif ( false !== stripos( $url, 'fright_itemlist.asp' ) ) {
						$kind = 'listing';
					} else {
						$kind = 'directory';
					}

					$links[ $url ] = array( 'label' => $label, 'kind' => $kind );
					$label         = '';
				}
			}
		}

		if ( preg_match_all( '#<a[^>]+href=[\'"]([^\'"]*fright[^\'"]*\.asp[^\'"]*)[\'"][^>]*>(.*?)</a>#is', $html, $m, PREG_SET_ORDER ) ) {
			foreach ( $m as $hit ) {
				$href = html_entity_decode( $hit[1], ENT_QUOTES, 'UTF-8' );

				// Breadcrumb links point back up the tree; skip them.
				if ( false !== stripos( $href, 'javascript:' ) ) {
					continue;
				}

				$url  = self::absolutise( str_replace( ' ', '%20', $href ) );
				$kind = ( false !== stripos( $url, 'fright_itemlist.asp' ) ) ? 'listing' : 'directory';

				$links[ $url ] = array(
					'label' => trim( wp_strip_all_tags( $hit[2] ) ),
					'kind'  => $kind,
				);
			}
		}

		return $links;
	}

	/** Kept for compatibility; listings only. */
	public static function parse_directory( $html ) {
		$out = array();
		foreach ( self::parse_links( $html ) as $url => $meta ) {
			if ( 'listing' === $meta['kind'] ) {
				$out[ $url ] = $meta['label'];
			}
		}
		return $out;
	}

	// ------------------------------------------------------------- the queue

	/** Top-level categories, as listed by the portal's own browse page. */
	public static function top_level_categories() {
		return array(
			'**** NEW ITEMS ****',
			'AIRBRUSH SUPPLIES',
			'ART ACCESSORIES',
			'ASSORTMENTS AND DISPLAYS',
			'BASIC CRAFT SUPPLIES',
			'BOOKS',
			'BRUSHES AND BRUSH CARE',
			'CANVAS AND SURFACES',
			'CHILDRENS CRAFTS',
			'CLAYS AND ACCESSORIES',
			'DRAWING SUPPLIES',
			'PAINTS, MEDIUMS AND FINISHES',
			'PAPER AND PADS',
			'PENS AND MARKERS',
			'PRINTMAKING',
			'TAPES AND ADHESIVES',
			'W/H BARGAIN BIN',
			'DROP-SHIP ONLY PRODUCTS',
		);
	}

	const OPT_DELAY_MS   = 'pci_sls_delay_ms';
	const OPT_FAIL_STREAK = 'pci_sls_fail_streak';

	/** Pause between requests. Deliberately generous: this is someone else's
	 *  ageing IIS box and being blocked costs far more than being slow. */
	public static function delay_ms() {
		return max( 250, min( 10000, (int) get_option( self::OPT_DELAY_MS, 2500 ) ) );
	}

	private static function pause() {
		usleep( self::delay_ms() * 1000 );
	}

	public static function fail_streak() {
		return (int) get_option( self::OPT_FAIL_STREAK, 0 );
	}

	private static function note_transport( $failed ) {
		if ( $failed ) {
			update_option( self::OPT_FAIL_STREAK, self::fail_streak() + 1, false );
		} else {
			update_option( self::OPT_FAIL_STREAK, 0, false );
		}
	}

	public static function clear_fail_streak() {
		update_option( self::OPT_FAIL_STREAK, 0, false );
	}

	/** Consecutive transport failures that stop a run. */
	const FAIL_LIMIT = 3;

	public static function circuit_open() {
		return self::fail_streak() >= self::FAIL_LIMIT;
	}

	/** Is SLS answering at all? */
	public static function connectivity_check() {
		$res = wp_remote_get( self::BASE, array( 'timeout' => 20, 'user-agent' => 'ThePaintChip-InventorySync/1.6' ) );
		if ( is_wp_error( $res ) ) {
			return array( 'ok' => false, 'message' => $res->get_error_message() );
		}
		$code = (int) wp_remote_retrieve_response_code( $res );
		return array(
			'ok'      => ( $code > 0 && $code < 500 ),
			'message' => sprintf( __( 'HTTP %d from slsarts.com', 'pci' ), $code ),
		);
	}

	public static function crawl_table() {
		global $wpdb;
		return $wpdb->prefix . 'pci_crawl';
	}

	/**
	 * Add a URL to the frontier.
	 *
	 * The unique hash makes this idempotent, so re-discovering a link that has
	 * already been crawled is a no-op rather than an endless loop.
	 */
	public static function enqueue( $url, $kind = 'directory', $label = '', $depth = 0 ) {
		global $wpdb;
		$url = trim( (string) $url );
		if ( '' === $url ) {
			return false;
		}

		return (bool) $wpdb->query( $wpdb->prepare(
			"INSERT IGNORE INTO " . self::crawl_table() . "
			 (url, url_hash, kind, label, depth, status, created_at)
			 VALUES (%s, %s, %s, %s, %d, 'pending', %s)",
			substr( $url, 0, 500 ),
			sha1( $url ),
			$kind,
			substr( $label, 0, 250 ),
			(int) $depth,
			current_time( 'mysql' )
		) );
	}

	/** Seed the frontier with the top-level category directories. */
	public static function seed() {
		$n = 0;
		foreach ( self::top_level_categories() as $cat ) {
			$url = self::BASE . 'fright.asp?level1=' . rawurlencode( $cat );
			if ( self::enqueue( $url, 'directory', $cat, 0 ) ) {
				$n++;
			}
		}
		return $n;
	}

	public static function queue_stats() {
		global $wpdb;
		$t = self::crawl_table();
		return array(
			'pending' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$t} WHERE status='pending'" ),
			'done'    => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$t} WHERE status='done'" ),
			'failed'  => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$t} WHERE status='failed'" ),
			'total'   => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$t}" ),
		);
	}

	/** Put failed pages back on the queue, e.g. after fixing a request header. */
	public static function retry_failed() {
		global $wpdb;
		return (int) $wpdb->query(
			"UPDATE " . self::crawl_table() . " SET status='pending', message='' WHERE status='failed'"
		);
	}

	/**
	 * Clear the page-crawl frontier only.
	 *
	 * Search attempts share this table but are deliberately spared: they are a
	 * record of what SLS does not stock, not a work queue, and wiping them
	 * means every dead SKU gets looked up again from scratch.
	 */
	public static function reset_queue() {
		global $wpdb;
		return (int) $wpdb->query(
			"DELETE FROM " . self::crawl_table() . " WHERE kind <> 'search'"
		);
	}

	/**
	 * Crawl the next few pages in the frontier.
	 *
	 * Listings get their products indexed; directories contribute their links.
	 * Either way any new catalog link found is queued, so the spider walks the
	 * whole tree from the seeds without needing to know its shape.
	 *
	 * @return array
	 */
	public static function crawl_step( $limit = 3, $max_depth = 8 ) {
		global $wpdb;
		$t = self::crawl_table();

		$rows = $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM {$t} WHERE status='pending'
			 ORDER BY FIELD(kind,'listing','directory','tree'), depth ASC, id ASC LIMIT %d",
			(int) $limit
		) );

		$log = array();

		foreach ( $rows as $row ) {
			$wpdb->update( $t, array( 'status' => 'working' ), array( 'id' => (int) $row->id ) );

			$scraper = new PCI_Scraper_SLS();
			$html    = PCI_Scraper_SLS::portal_enabled()
				? $scraper->portal_get( $row->url )
				: ( new PCI_Http( 'SS' ) )->get( $row->url );

			if ( is_wp_error( $html ) ) {
				// Back to pending, not failed: an unreachable server is not a
				// bad URL and should be retried once it recovers.
				$wpdb->update( $t, array(
					'status'     => 'pending',
					'message'    => substr( $html->get_error_message(), 0, 250 ),
					'crawled_at' => current_time( 'mysql' ),
				), array( 'id' => (int) $row->id ) );

				self::note_transport( true );
				$log[] = array( 'ok' => false, 'label' => $row->label ? $row->label : $row->url, 'message' => $html->get_error_message() );

				if ( self::circuit_open() ) {
					$log[] = array( 'ok' => false, 'label' => __( 'Stopped', 'pci' ),
						'message' => __( 'SLS stopped responding. Pausing so a temporary block does not become permanent.', 'pci' ) );
					break;
				}
				continue;
			}

			self::note_transport( false );

			$parsed = self::parse_listing( $html );
			$found  = count( $parsed['items'] );
			$added  = 0;

			if ( $found ) {
				$res   = self::index_items( $parsed['items'] );
				$added = $res['added'];
			}

			// Follow every catalog link on the page.
			$queued = 0;
			if ( (int) $row->depth < $max_depth ) {
				foreach ( self::parse_links( $html ) as $url => $meta ) {
					if ( self::enqueue( $url, $meta['kind'], $meta['label'], (int) $row->depth + 1 ) ) {
						$queued++;
					}
				}
			}

			$label = $parsed['category'] ? implode( ' > ', $parsed['category'] ) : ( $row->label ? $row->label : $row->url );

			// A page that yields neither products nor links is a parsing
			// failure, not an empty branch. Keep enough of it to diagnose.
			$diagnostic = '';
			if ( 0 === $found && 0 === $queued ) {
				$has_tm  = ( false !== stripos( $html, 'tmenuItems' ) );
				$has_row = ( false !== stripos( $html, 'slssku' ) );
				$diagnostic = sprintf(
					'nothing found — %d bytes, tmenuItems:%s slssku:%s',
					strlen( $html ),
					$has_tm ? 'yes' : 'no',
					$has_row ? 'yes' : 'no'
				);
				update_option( 'pci_sls_last_empty', array(
					'url'     => $row->url,
					'bytes'   => strlen( $html ),
					'excerpt' => PCI_Scraper_SLS::excerpt( $html, 1500 ),
					'when'    => current_time( 'mysql' ),
				), false );
			}

			$wpdb->update( $t, array(
				'status'      => 'done',
				'items_found' => $found,
				'message'     => $diagnostic ? $diagnostic : sprintf( '%d products, %d links queued', $found, $queued ),
				'crawled_at'  => current_time( 'mysql' ),
			), array( 'id' => (int) $row->id ) );

			$log[] = array(
				'ok'      => true,
				'label'   => $label,
				'message' => $found
					? sprintf( __( '%1$d products (%2$d new), %3$d links queued', 'pci' ), $found, $added, $queued )
					: ( $diagnostic ? $diagnostic : sprintf( __( 'directory — %d links queued', 'pci' ), $queued ) ),
			);

			self::pause();
		}

		return array(
			'log'      => $log,
			'queue'    => self::queue_stats(),
			'catalog'  => self::stats(),
			'coverage' => self::coverage(),
			'stopped'  => self::circuit_open(),
		);
	}

	/**
	 * How much of what we actually need is now indexed.
	 *
	 * This is the number that matters: not how big the catalog index is, but
	 * how many of the SKUs waiting to be sourced can now be resolved locally.
	 */
	public static function coverage( $run_id = 0 ) {
		global $wpdb;
		$items = PCI_Schema::table( 'items' );
		$cat   = self::table();

		$where = "i.action = %s AND i.vend = 'SS'";
		$args  = array( PCI_Classifier::NEW_P );

		if ( $run_id ) {
			$where .= ' AND i.run_id = %d';
			$args[] = (int) $run_id;
		}

		// DISTINCT matters: the same SKU appears once per upload run, so a plain
		// COUNT(*) multiplies both sides by the number of runs.
		$needed = (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(DISTINCT i.sku) FROM {$items} i WHERE {$where} AND i.sku <> ''", $args
		) );

		$have = (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(DISTINCT i.sku) FROM {$items} i
			 INNER JOIN {$cat} c ON c.sku = i.sku
			 WHERE {$where} AND i.sku <> ''", $args
		) );

		return array(
			'needed'  => $needed,
			'have'    => $have,
			'missing' => max( 0, $needed - $have ),
			'pct'     => $needed > 0 ? round( $have / $needed * 100, 1 ) : 0,
		);
	}

	/**
	 * Side-by-side sample of what is wanted against what has been indexed.
	 *
	 * If coverage stays low while the index grows, the two sides are not
	 * speaking the same language, and seeing real examples of each is the
	 * fastest way to work out the transformation.
	 */
	public static function format_diagnostic( $n = 12 ) {
		global $wpdb;
		$items = PCI_Schema::table( 'items' );
		$cat   = self::table();

		$wanted = $wpdb->get_col( $wpdb->prepare(
			"SELECT DISTINCT sku FROM {$items}
			 WHERE action = %s AND vend = 'SS' AND sku <> '' ORDER BY sku LIMIT %d",
			PCI_Classifier::NEW_P, (int) $n
		) );

		$indexed = $wpdb->get_col( $wpdb->prepare(
			"SELECT sku FROM {$cat} ORDER BY id DESC LIMIT %d", (int) $n
		) );

		$matched = $wpdb->get_col( $wpdb->prepare(
			"SELECT DISTINCT i.sku FROM {$items} i
			 INNER JOIN {$cat} c ON c.sku = i.sku
			 WHERE i.action = %s AND i.vend = 'SS' LIMIT %d",
			PCI_Classifier::NEW_P, (int) $n
		) );

		// Case- and punctuation-insensitive match, to reveal a near miss.
		$loose = (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(DISTINCT i.sku) FROM {$items} i
			 INNER JOIN {$cat} c
			   ON UPPER(REPLACE(REPLACE(c.sku,'-',''),' ','')) = UPPER(REPLACE(REPLACE(i.sku,'-',''),' ',''))
			 WHERE i.action = %s AND i.vend = 'SS' AND i.sku <> ''",
			PCI_Classifier::NEW_P
		) );

		$junk = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$cat} WHERE upc = '' AND title = sku" );

		return array(
			'wanted'      => $wanted,
			'indexed'     => $indexed,
			'matched'     => $matched,
			'loose_match' => $loose,
			'junk_rows'   => $junk,
		);
	}

	/** Remove index rows that carry nothing beyond a SKU. */
	public static function purge_junk() {
		global $wpdb;
		return (int) $wpdb->query(
			"DELETE FROM " . self::table() . " WHERE upc = '' AND title = sku"
		);
	}

	/** SKUs still unresolved, for a targeted follow-up. */
	public static function missing_skus( $limit = 200 ) {
		global $wpdb;
		$items = PCI_Schema::table( 'items' );
		$cat   = self::table();

		$crawl = self::crawl_table();

		// Exclude SKUs already searched for, or the loop retries the same
		// failures indefinitely and never advances.
		return $wpdb->get_col( $wpdb->prepare(
			"SELECT DISTINCT i.sku FROM {$items} i
			 LEFT JOIN {$cat} c ON c.sku = i.sku
			 LEFT JOIN {$crawl} s ON s.kind = 'search' AND s.label = i.sku
			 WHERE i.action = %s AND i.vend = 'SS' AND c.id IS NULL
			   AND s.id IS NULL AND i.sku <> ''
			 ORDER BY i.sku LIMIT %d",
			PCI_Classifier::NEW_P,
			(int) $limit
		) );
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
