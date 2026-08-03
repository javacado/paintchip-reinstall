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
	public static function search_url( $sku ) {
		return self::BASE . 'fright_itemlist.asp?findtype=&txtfind=' . rawurlencode( trim( (string) $sku ) );
	}

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

	public static function find_sku( $sku ) {
		$sku = trim( (string) $sku );
		if ( '' === $sku ) {
			return array( 'ok' => false, 'message' => __( 'No SKU given.', 'pci' ), 'item' => null );
		}

		$url     = self::search_url( $sku );
		$scraper = new PCI_Scraper_SLS();
		$html    = PCI_Scraper_SLS::portal_enabled() ? $scraper->portal_get( $url ) : ( new PCI_Http( 'SS' ) )->get( $url );

		if ( is_wp_error( $html ) ) {
			// A transport failure is not the same as "not stocked", so this is
			// left unrecorded and will be retried.
			return array( 'ok' => false, 'message' => $html->get_error_message(), 'item' => null );
		}

		$parsed = self::parse_listing( $html );

		foreach ( $parsed['items'] as $item ) {
			if ( 0 === strcasecmp( $item['sku'], $sku ) ) {
				self::index_items( array( $item ) );
				self::mark_searched( $sku, true );
				return array(
					'ok'      => true,
					'message' => sprintf( __( 'Found: %s', 'pci' ), $item['title'] ),
					'item'    => $item,
				);
			}
		}

		// A search can legitimately return near matches; index them anyway.
		if ( ! empty( $parsed['items'] ) ) {
			self::index_items( $parsed['items'] );
			self::mark_searched( $sku, false, sprintf( '%d near matches, no exact', count( $parsed['items'] ) ) );
			return array(
				'ok'      => false,
				'message' => sprintf( __( '%d products returned but none matched exactly — indexed anyway.', 'pci' ), count( $parsed['items'] ) ),
				'item'    => null,
			);
		}

		self::mark_searched( $sku, false, 'no results' );

		return array( 'ok' => false, 'message' => __( 'SLS returned no product for that SKU.', 'pci' ), 'item' => null );
	}

	/**
	 * Work through the SKUs we still need, one search each.
	 *
	 * @return array
	 */
	public static function find_missing( $limit = 5 ) {
		$skus = self::missing_skus( $limit );
		$log  = array();

		foreach ( $skus as $sku ) {
			$res   = self::find_sku( $sku );
			$log[] = array( 'ok' => $res['ok'], 'label' => $sku, 'message' => $res['message'] );
			usleep( 400000 );
		}

		return array(
			'log'       => $log,
			'catalog'   => self::stats(),
			'coverage'  => self::coverage(),
			'queue'     => self::queue_stats(),
			'not_found' => self::not_found_count(),
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
				$wpdb->update( $t, array(
					'status'     => 'failed',
					'message'    => substr( $html->get_error_message(), 0, 250 ),
					'crawled_at' => current_time( 'mysql' ),
				), array( 'id' => (int) $row->id ) );

				$log[] = array( 'ok' => false, 'label' => $row->label ? $row->label : $row->url, 'message' => $html->get_error_message() );
				continue;
			}

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

			// Ancient IIS box; do not hammer it.
			usleep( 500000 );
		}

		return array(
			'log'      => $log,
			'queue'    => self::queue_stats(),
			'catalog'  => self::stats(),
			'coverage' => self::coverage(),
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

		$needed = (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$items} i WHERE {$where}", $args
		) );

		$have = (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$items} i
			 INNER JOIN {$cat} c ON c.sku = i.sku
			 WHERE {$where}", $args
		) );

		return array(
			'needed'  => $needed,
			'have'    => $have,
			'missing' => max( 0, $needed - $have ),
			'pct'     => $needed > 0 ? round( $have / $needed * 100, 1 ) : 0,
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
