<?php
defined( 'ABSPATH' ) || exit;

/**
 * Turns "new product" rows into reviewable WooCommerce drafts.
 *
 * Three stages, each one stopping so a human can look:
 *
 *   fetch   - pull supplier data onto the staged row. Creates nothing.
 *   preview - image plus bullet points, so you can see it is on track.
 *   create  - build drafts. Still not public until approved.
 *
 * Nothing here runs on a schedule. Every stage is started by a person.
 */
class PCI_Sourcing {

	const OPT_MIN_IMG_WIDTH = 'pci_min_image_width';
	const OPT_BATCH_SIZE    = 'pci_fetch_batch_size';

	public static function min_image_width() {
		return max( 50, (int) get_option( self::OPT_MIN_IMG_WIDTH, 300 ) );
	}

	public static function batch_size() {
		return max( 1, min( 50, (int) get_option( self::OPT_BATCH_SIZE, 10 ) ) );
	}

	// ------------------------------------------------------------- utilities

	/**
	 * Sentence case: first letter up, the rest down.
	 *
	 * SLS shouts everything. "MOLOTOW ACRYLIC PAINT MARKER 4MM" becomes
	 * "Molotow acrylic paint marker 4mm". Dimensions like 8X10 keep their
	 * separator lowercase, which reads correctly at this scale.
	 */
	public static function sentence_case( $title ) {
		$title = trim( preg_replace( '/\s+/', ' ', (string) $title ) );
		if ( '' === $title ) {
			return '';
		}

		// mbstring is not guaranteed on every host, so fall back to the ASCII
		// functions when it is missing. Titles from the POS are ASCII anyway.
		$mb = function_exists( 'mb_strtoupper' ) && function_exists( 'mb_strtolower' ) && function_exists( 'mb_substr' );

		$upper = $mb ? mb_strtoupper( $title, 'UTF-8' ) : strtoupper( $title );

		// Only fold case if the source is shouting; leave mixed case alone.
		if ( $title === $upper ) {
			$title = $mb ? mb_strtolower( $title, 'UTF-8' ) : strtolower( $title );
		}

		if ( $mb ) {
			$title = mb_strtoupper( mb_substr( $title, 0, 1, 'UTF-8' ), 'UTF-8' ) . mb_substr( $title, 1, null, 'UTF-8' );
		} else {
			$title = ucfirst( $title );
		}

		// Tidy dimension strings: "8 x 10" -> "8x10".
		$title = preg_replace( '/(\d)\s*[xX]\s*(\d)/', '$1x$2', $title );

		return $title;
	}

	/**
	 * Dimensions of a remote image without keeping the file.
	 *
	 * @return array{width:int,height:int}|false
	 */
	public static function remote_image_size( $url ) {
		if ( empty( $url ) ) {
			return false;
		}

		$key    = 'pci_imgsize_' . md5( $url );
		$cached = get_transient( $key );
		if ( false !== $cached ) {
			return is_array( $cached ) ? $cached : false;
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';
		$tmp = download_url( $url, 20 );
		if ( is_wp_error( $tmp ) ) {
			set_transient( $key, 'fail', HOUR_IN_SECONDS );
			return false;
		}

		$size = @getimagesize( $tmp );
		@unlink( $tmp );

		if ( empty( $size[0] ) ) {
			set_transient( $key, 'fail', HOUR_IN_SECONDS );
			return false;
		}

		$out = array( 'width' => (int) $size[0], 'height' => (int) $size[1] );
		set_transient( $key, $out, DAY_IN_SECONDS );

		return $out;
	}

	/**
	 * Decide which image to use.
	 *
	 * Supplier image wins if it is big enough. Otherwise fall back to a UPC
	 * lookup, which is what the old tool did for products whose catalog image
	 * was missing or a postage stamp.
	 *
	 * @return array{url:string,source:string,width:int}|WP_Error
	 */
	public static function resolve_image( $supplier_url, $upc ) {
		$min = self::min_image_width();

		if ( ! empty( $supplier_url ) ) {
			$size = self::remote_image_size( $supplier_url );
			if ( $size && $size['width'] >= $min ) {
				return array( 'url' => $supplier_url, 'source' => 'supplier', 'width' => $size['width'] );
			}
		}

		if ( ! empty( $upc ) && PCI_UPC::is_configured() ) {
			$found = PCI_UPC::find_image( $upc, $min );
			if ( ! is_wp_error( $found ) ) {
				$size = self::remote_image_size( $found );
				return array(
					'url'    => $found,
					'source' => 'upc',
					'width'  => $size ? $size['width'] : 0,
				);
			}
		}

		// Nothing good. Return the small supplier image rather than nothing,
		// flagged so the review screen can say so.
		if ( ! empty( $supplier_url ) ) {
			$size = self::remote_image_size( $supplier_url );
			if ( $size ) {
				return array( 'url' => $supplier_url, 'source' => 'supplier-small', 'width' => $size['width'] );
			}
		}

		return new WP_Error( 'pci_no_image', __( 'No usable image was found from the supplier or the UPC.', 'pci' ) );
	}

	/**
	 * Match supplier category names onto existing product_cat terms.
	 *
	 * The site's category tree was built from the SLS categories, so a direct
	 * name match carries most of it. Slug and punctuation-stripped comparison
	 * pick up the rest. Anything unmatched is reported rather than invented —
	 * this never creates terms.
	 *
	 * @return array{ids:int[],matched:string[],unmatched:string[]}
	 */
	public static function map_categories( array $names ) {
		$ids       = array();
		$matched   = array();
		$unmatched = array();

		$terms = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => false ) );
		if ( is_wp_error( $terms ) ) {
			return array( 'ids' => array(), 'matched' => array(), 'unmatched' => $names );
		}

		$by_key = array();
		foreach ( $terms as $t ) {
			$by_key[ self::cat_key( $t->name ) ] = $t->term_id;
			$by_key[ self::cat_key( $t->slug ) ] = $t->term_id;
		}

		foreach ( $names as $name ) {
			$k = self::cat_key( $name );
			if ( '' === $k ) {
				continue;
			}
			if ( isset( $by_key[ $k ] ) ) {
				$ids[]     = (int) $by_key[ $k ];
				$matched[] = $name;
			} else {
				$unmatched[] = $name;
			}
		}

		return array(
			'ids'       => array_values( array_unique( $ids ) ),
			'matched'   => $matched,
			'unmatched' => $unmatched,
		);
	}

	private static function cat_key( $s ) {
		return preg_replace( '/[^a-z0-9]/', '', strtolower( (string) $s ) );
	}

	// ----------------------------------------------------------------- fetch

	/** Rows in this run that still need supplier data. */
	public static function pending( $run_id, $limit = 0 ) {
		global $wpdb;
		$t   = PCI_Schema::table( 'items' );
		$sql = $wpdb->prepare(
			"SELECT * FROM {$t}
			 WHERE run_id = %d AND action = %s AND (raw IS NULL OR raw NOT LIKE %s)
			 ORDER BY vend, sku",
			(int) $run_id,
			PCI_Classifier::NEW_P,
			'%"scraped"%'
		);
		if ( $limit > 0 ) {
			$sql .= $wpdb->prepare( ' LIMIT %d', (int) $limit );
		}
		return $wpdb->get_results( $sql );
	}

	public static function counts( $run_id ) {
		global $wpdb;
		$t = PCI_Schema::table( 'items' );

		$total = (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$t} WHERE run_id = %d AND action = %s",
			(int) $run_id, PCI_Classifier::NEW_P
		) );
		$done = (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$t} WHERE run_id = %d AND action = %s AND raw LIKE %s",
			(int) $run_id, PCI_Classifier::NEW_P, '%"scraped"%'
		) );
		$failed = (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$t} WHERE run_id = %d AND action = %s AND raw LIKE %s",
			(int) $run_id, PCI_Classifier::NEW_P, '%scrape_error%'
		) );
		$drafts = (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$t} WHERE run_id = %d AND action = %s",
			(int) $run_id, PCI_Classifier::CREATED
		) );
		$published = (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$t} WHERE run_id = %d AND action = %s",
			(int) $run_id, PCI_Classifier::APPROVED
		) );

		return array(
			'total'        => $total,
			'fetched'      => $done,
			'failed'       => $failed,
			'pending'      => max( 0, $total - $done ),
			'drafts'       => $drafts,
			'published'    => $published,
			'all_new'      => $all_new,
			'out_of_scope' => max( 0, $all_new - $total ),
		);
	}

	/**
	 * Fetch supplier data for up to $limit rows.
	 *
	 * @return array{done:int,failed:int,errors:array}
	 */
	public static function fetch_batch( $run_id, $limit = null ) {
		global $wpdb;

		$limit = $limit ? (int) $limit : self::batch_size();
		$rows  = self::pending( $run_id, $limit );
		$t     = PCI_Schema::table( 'items' );
		$reg   = PCI_Scraper_Registry::instance();

		$done   = 0;
		$failed = 0;
		$errors = array();
		$ids    = array();

		foreach ( $rows as $row ) {
			$ids[] = (int) $row->id;
			$scraper = $reg->for_vend( $row->vend );

			$raw = json_decode( (string) $row->raw, true );
			if ( ! is_array( $raw ) ) {
				$raw = array();
			}

			if ( ! $scraper ) {
				$raw['scraped']       = null;
				$raw['scrape_error']  = sprintf( __( 'No adapter for supplier %s.', 'pci' ), $row->vend );
				$raw['scraped_at']    = current_time( 'mysql' );
				$wpdb->update( $t, array( 'raw' => wp_json_encode( $raw ) ), array( 'id' => (int) $row->id ) );
				$failed++;
				continue;
			}

			$data = $scraper->fetch( $row->sku );

			if ( is_wp_error( $data ) ) {
				$raw['scraped']      = null;
				$raw['scrape_error'] = $data->get_error_message();
				$raw['scraped_at']   = current_time( 'mysql' );
				$failed++;
				$errors[] = $row->sku . ': ' . $data->get_error_message();
			} else {
				$img = self::resolve_image(
					isset( $data['image_url'] ) ? $data['image_url'] : '',
					isset( $data['upc'] ) ? $data['upc'] : ''
				);

				if ( is_wp_error( $img ) ) {
					$data['image_url']    = '';
					$data['image_source'] = 'none';
					$data['image_note']   = $img->get_error_message();
				} else {
					$data['image_url']    = $img['url'];
					$data['image_source'] = $img['source'];
					$data['image_width']  = $img['width'];
				}

				$data['title_clean'] = self::sentence_case( isset( $data['title'] ) ? $data['title'] : '' );

				$cats                = self::map_categories( isset( $data['categories'] ) ? $data['categories'] : array() );
				$data['cat_ids']     = $cats['ids'];
				$data['cat_matched'] = $cats['matched'];
				$data['cat_missing'] = $cats['unmatched'];

				$raw['scraped']    = $data;
				$raw['scraped_at'] = current_time( 'mysql' );
				unset( $raw['scrape_error'] );
				$done++;
			}

			$wpdb->update( $t, array( 'raw' => wp_json_encode( $raw ) ), array( 'id' => (int) $row->id ) );

			// Be a polite guest on an ancient ASP server.
			usleep( 400000 );
		}

		return array( 'done' => $done, 'failed' => $failed, 'errors' => $errors, 'ids' => $ids );
	}

	// ---------------------------------------------------------------- create

	/** Fetched rows that have usable data and no product yet. */
	public static function ready_to_create( $run_id, $limit = 0 ) {
		global $wpdb;
		$t   = PCI_Schema::table( 'items' );
		$sql = $wpdb->prepare(
			"SELECT * FROM {$t}
			 WHERE run_id = %d AND action = %s AND raw LIKE %s AND product_id IS NULL
			 ORDER BY vend, sku",
			(int) $run_id,
			PCI_Classifier::NEW_P,
			'%"scraped":{%'
		);
		if ( $limit > 0 ) {
			$sql .= $wpdb->prepare( ' LIMIT %d', (int) $limit );
		}
		return $wpdb->get_results( $sql );
	}

	/**
	 * Create drafts from fetched rows.
	 *
	 * @return array{created:int,skipped:int,errors:array}
	 */
	public static function create_drafts( $run_id, $limit = 0 ) {
		global $wpdb;

		$rows    = self::ready_to_create( $run_id, $limit );
		$t       = PCI_Schema::table( 'items' );
		$created = 0;
		$skipped = 0;
		$errors  = array();

		foreach ( $rows as $row ) {
			$raw  = json_decode( (string) $row->raw, true );
			$data = isset( $raw['scraped'] ) ? $raw['scraped'] : null;

			if ( ! is_array( $data ) ) {
				$skipped++;
				continue;
			}

			// Never create a second product for an existing SKU.
			if ( function_exists( 'wc_get_product_id_by_sku' ) && wc_get_product_id_by_sku( $row->sku ) ) {
				$skipped++;
				$errors[] = sprintf( __( '%s already exists on the site.', 'pci' ), $row->sku );
				continue;
			}

			$product_id = self::create_one( $row, $data );

			if ( is_wp_error( $product_id ) ) {
				$skipped++;
				$errors[] = $row->sku . ': ' . $product_id->get_error_message();
				continue;
			}

			$wpdb->update(
				$t,
				array( 'product_id' => (int) $product_id, 'action' => PCI_Classifier::CREATED,
				       'product_title' => get_the_title( $product_id ) ),
				array( 'id' => (int) $row->id )
			);

			$created++;
		}

		return array( 'created' => $created, 'skipped' => $skipped, 'errors' => $errors );
	}

	/**
	 * @return int|WP_Error Product ID.
	 */
	public static function create_one( $row, array $data ) {
		$product = new WC_Product_Simple();

		$title = ! empty( $data['title_clean'] ) ? $data['title_clean'] : self::sentence_case( $data['title'] );
		if ( '' === $title ) {
			return new WP_Error( 'pci_no_title', __( 'No title to create a product with.', 'pci' ) );
		}

		$product->set_name( $title );
		$product->set_status( 'draft' );
		$product->set_sku( $row->sku );
		$product->set_catalog_visibility( 'visible' );

		if ( ! empty( $data['description'] ) ) {
			$product->set_description( wp_kses_post( $data['description'] ) );
		}

		// Price comes from the POS report, not the supplier's MSRP: the store's
		// own price is what the catalog should show.
		if ( null !== $row->file_price ) {
			$product->set_regular_price( (string) $row->file_price );
		} elseif ( ! empty( $data['msrp'] ) ) {
			$product->set_regular_price( (string) $data['msrp'] );
		}

		$product->set_manage_stock( true );
		$product->set_stock_quantity( (int) $row->file_qty );
		$product->set_stock_status( (int) $row->file_qty > 0 ? 'instock' : 'outofstock' );

		if ( ! empty( $data['cat_ids'] ) ) {
			$product->set_category_ids( array_map( 'intval', $data['cat_ids'] ) );
		}

		$product_id = $product->save();

		if ( ! $product_id ) {
			return new WP_Error( 'pci_create_failed', __( 'WooCommerce refused to save the product.', 'pci' ) );
		}

		// GTIN plugin field.
		if ( ! empty( $data['upc'] ) ) {
			update_post_meta( $product_id, '_wpm_gtin_code', sanitize_text_field( $data['upc'] ) );
		}

		// Provenance, so the next run never has to guess again.
		update_post_meta( $product_id, '_pci_vend', sanitize_text_field( $row->vend ) );
		update_post_meta( $product_id, '_pci_item_id', sanitize_text_field( $row->item_id ) );
		update_post_meta( $product_id, '_pci_source_url', esc_url_raw( isset( $data['source_url'] ) ? $data['source_url'] : '' ) );
		update_post_meta( $product_id, '_pci_created_run', (int) $row->run_id );

		if ( ! empty( $data['image_url'] ) ) {
			$att = PCI_Scraper_Registry::sideload_image( $data['image_url'], $row->sku );
			if ( ! is_wp_error( $att ) ) {
				set_post_thumbnail( $product_id, $att );
				update_post_meta( $product_id, '_pci_image_source', sanitize_text_field( $data['image_source'] ) );
			}
		}

		return (int) $product_id;
	}

	// ---------------------------------------------------------------- review

	public static function drafts( $run_id, $limit = 100, $offset = 0 ) {
		global $wpdb;
		$t = PCI_Schema::table( 'items' );
		return $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM {$t} WHERE run_id = %d AND action = %s ORDER BY vend, sku LIMIT %d OFFSET %d",
			(int) $run_id, PCI_Classifier::CREATED, (int) $limit, (int) $offset
		) );
	}

	public static function approve( $item_id ) {
		return self::set_review_state( $item_id, 'approve' );
	}

	public static function reject( $item_id ) {
		return self::set_review_state( $item_id, 'reject' );
	}

	private static function set_review_state( $item_id, $mode ) {
		global $wpdb;
		$t    = PCI_Schema::table( 'items' );
		$item = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$t} WHERE id = %d", (int) $item_id ) );

		if ( ! $item || ! $item->product_id ) {
			return new WP_Error( 'pci_no_draft', __( 'That draft no longer exists.', 'pci' ) );
		}

		if ( 'approve' === $mode ) {
			$product = wc_get_product( (int) $item->product_id );
			if ( ! $product ) {
				return new WP_Error( 'pci_no_product', __( 'The product could not be loaded.', 'pci' ) );
			}
			$product->set_status( 'publish' );
			$product->save();
			$wpdb->update( $t, array( 'action' => PCI_Classifier::APPROVED ), array( 'id' => (int) $item_id ) );
		} else {
			wp_trash_post( (int) $item->product_id );
			$wpdb->update( $t, array( 'action' => PCI_Classifier::REJECTED ), array( 'id' => (int) $item_id ) );
		}

		return true;
	}

	/** Publish every remaining draft in one go. */
	public static function approve_all( $run_id ) {
		$n = 0;
		foreach ( self::drafts( $run_id, 1000 ) as $d ) {
			if ( true === self::approve( $d->id ) ) {
				$n++;
			}
		}
		return $n;
	}
}
