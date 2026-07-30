<?php
defined( 'ABSPATH' ) || exit;

/**
 * Turns parsed rows into one decision per SKU.
 *
 * The join key is the report's Alternate column, matched verbatim against
 * WooCommerce _sku. On the reference data that hit 3,129 of 3,381 live
 * products (92.5%) with no normalisation. Item ID and Barcode both matched
 * zero. Fuzzy fallbacks were measured and rescued 8 products out of 252, so
 * they are deliberately absent — the false-positive risk outweighs the gain.
 *
 * Note the SKU prefix is a BRAND code (LQ Liquitex, TB Tombow, RY Royal),
 * never the distributor. Supplier attribution comes from the Vend column only.
 */
class PCI_Classifier {

	const UPDATE = 'update';
	const HIDE   = 'hide';
	const REMOVE = 'remove';
	const NEW_P  = 'new';
	const IGNORE = 'ignore';

	const FLAG_NEGATIVE  = 'flag_negative';
	const FLAG_AMBIGUOUS = 'flag_ambiguous';
	const FLAG_NOKEY     = 'flag_nokey';
	const FLAG_DUPSKU    = 'flag_dupsku';
	const FLAG_REVIEW    = 'flag_review';
	const FLAG_ORPHAN    = 'flag_orphan';

	public static function all_actions() {
		return array(
			self::UPDATE, self::HIDE, self::REMOVE, self::NEW_P, self::IGNORE,
			self::FLAG_NEGATIVE, self::FLAG_AMBIGUOUS, self::FLAG_NOKEY,
			self::FLAG_DUPSKU, self::FLAG_REVIEW, self::FLAG_ORPHAN,
		);
	}

	public static function is_flag( $action ) {
		return 0 === strpos( $action, 'flag_' );
	}

	public static function label( $action ) {
		$labels = array(
			self::UPDATE         => __( 'Update stock', 'pci' ),
			self::HIDE           => __( 'Hide (out of stock)', 'pci' ),
			self::REMOVE         => __( 'Remove from store', 'pci' ),
			self::NEW_P          => __( 'New product to add', 'pci' ),
			self::IGNORE         => __( 'Legacy row, no action', 'pci' ),
			self::FLAG_NEGATIVE  => __( 'Flagged: negative quantity', 'pci' ),
			self::FLAG_AMBIGUOUS => __( 'Flagged: duplicate rows, indistinguishable', 'pci' ),
			self::FLAG_NOKEY     => __( 'Flagged: no SKU in the report', 'pci' ),
			self::FLAG_DUPSKU    => __( 'Flagged: SKU used by more than one product', 'pci' ),
			self::FLAG_REVIEW    => __( 'Flagged: has stock but is not restocked', 'pci' ),
			self::FLAG_ORPHAN    => __( 'Flagged: unpaired line in the report', 'pci' ),
		);
		return isset( $labels[ $action ] ) ? $labels[ $action ] : $action;
	}

	/** sku => array of product IDs. Duplicates are kept so we can flag them. */
	public static function site_sku_map() {
		global $wpdb;

		$rows = $wpdb->get_results(
			"SELECT pm.meta_value AS sku, pm.post_id
			 FROM {$wpdb->postmeta} pm
			 INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
			 WHERE pm.meta_key = '_sku'
			   AND pm.meta_value <> ''
			   AND p.post_type IN ('product','product_variation')
			   AND p.post_status NOT IN ('trash','auto-draft')"
		);

		$map = array();
		foreach ( $rows as $r ) {
			$map[ $r->sku ][] = (int) $r->post_id;
		}
		return $map;
	}

	/**
	 * Group parsed rows by SKU and resolve one action each.
	 *
	 * @param array $records From PCI_Parser::parse_file().
	 * @param array $orphans From PCI_Parser::parse_file().
	 * @return array List of staged item arrays, ready for insert.
	 */
	public static function classify( array $records, array $orphans = array() ) {
		$site   = self::site_sku_map();
		$groups = array();
		$nokey  = array();

		foreach ( $records as $r ) {
			$sku = trim( $r['alternate'] );
			if ( '' === $sku ) {
				$nokey[] = $r;
				continue;
			}
			$groups[ $sku ][] = $r;
		}

		$items = array();

		foreach ( $groups as $sku => $rows ) {
			$item = self::resolve( $sku, $rows, $site );

			/**
			 * Override a resolved action without editing this class.
			 *
			 * The rules here are a hypothesis about how the POS records
			 * "discontinued" versus "temporarily out". When that convention
			 * turns out to be different, hook this rather than forking the
			 * classifier. Every parsed column is available in $rows, including
			 * the ones no rule currently reads (Deleted, Active, Dept, Note,
			 * QCo, Multiplier, Fixed).
			 *
			 * @param array  $item Resolved staged item.
			 * @param string $sku  The Alternate value used as the join key.
			 * @param array  $rows All report rows sharing this SKU.
			 */
			$items[] = apply_filters( 'pci_resolved_item', $item, $sku, $rows );
		}

		// Rows the report gave us no key for. RT, LF and BL are the usual
		// offenders. Nothing can be done with these but show them.
		foreach ( $nokey as $r ) {
			// Explicit assignment, not the `+` union operator: `+` keeps the
			// LEFT operand on key collision, which would silently discard the
			// action set here and leave these rows unclassified.
			$item                = self::base_item( $r, '' );
			$item['action']      = self::FLAG_NOKEY;
			$item['row_count']   = 1;
			$item['flag_reason'] = sprintf(
				/* translators: %s: supplier code */
				__( 'The Alternate column is blank, so there is no way to match this %s row to a product.', 'pci' ),
				$r['vend']
			);
			$items[] = $item;
		}

		foreach ( $orphans as $o ) {
			$items[] = array(
				'sku'         => '',
				'vend'        => '',
				'item_id'     => '',
				'description' => '',
				'dept'        => '',
				'file_qty'    => 0,
				'file_max'    => 0,
				'file_min'    => 0,
				'file_price'  => null,
				'file_cost'   => null,
				'row_count'   => 1,
				'action'      => self::FLAG_ORPHAN,
				'flag_reason' => $o['reason'],
				'product_id'  => null,
				'raw'         => wp_json_encode( array( 'raw' => $o['raw'] ) ),
			);
		}

		return $items;
	}

	private static function base_item( $r, $sku ) {
		return array(
			'sku'         => $sku,
			'vend'        => $r['vend'],
			'item_id'     => $r['item_id'],
			'description' => $r['desc'],
			'dept'        => $r['dept'],
			'file_qty'    => 0,
			'file_max'    => 0,
			'file_min'    => 0,
			'file_price'  => PCI_Parser::to_number( $r['price'] ),
			'file_cost'   => PCI_Parser::to_number( $r['cost'] ),
			'product_id'  => null,
			// Initialised because attach_product() may set it to FLAG_DUPSKU and
			// resolve() tests it straight afterwards.
			'action'      => '',
			'flag_reason' => '',
			'raw'         => wp_json_encode( $r ),
		);
	}

	private static function resolve( $sku, array $rows, array $site ) {
		$first = $rows[0];
		$item  = self::base_item( $first, $sku );
		$item['row_count'] = count( $rows );

		$policy = PCI_Suppliers::policy_for( $first['vend'] );
		if ( PCI_Suppliers::IGNORE === $policy ) {
			$item['action']      = self::IGNORE;
			$item['flag_reason'] = __( 'Supplier is set to Ignore.', 'pci' );
			return $item;
		}

		$qtys = array();
		$maxs = array();
		foreach ( $rows as $r ) {
			$qtys[] = PCI_Parser::to_number( $r['qo'] );
			$maxs[] = PCI_Parser::to_number( $r['max'] );
			$mn     = PCI_Parser::to_number( $r['min'] );
			if ( null !== $mn ) {
				$item['file_min'] = max( $item['file_min'], (int) $mn );
			}
		}

		// Anything unreadable in the numeric columns stops here.
		foreach ( array_merge( $qtys, $maxs ) as $n ) {
			if ( null === $n ) {
				$item['action']      = self::FLAG_REVIEW;
				$item['flag_reason'] = __( 'A quantity or Max value could not be read as a number.', 'pci' );
				return $item;
			}
		}

		$qty = (int) array_sum( $qtys );
		$max = (int) max( $maxs );
		$item['file_qty'] = $qty;
		$item['file_max'] = $max;

		foreach ( $qtys as $q ) {
			if ( $q < 0 ) {
				$item['action']      = self::FLAG_NEGATIVE;
				$item['flag_reason'] = sprintf(
					/* translators: %s: negative quantity */
					__( 'The report shows a negative quantity (%s), which usually means the POS oversold this line.', 'pci' ),
					implode( ', ', $qtys )
				);
				self::attach_product( $item, $sku, $site );
				return $item;
			}
		}

		// Several rows sharing one SKU. If nothing distinguishes them, the
		// report has collapsed separate products into one key (the Daniel
		// Smith watercolours are 71 rows deep) and no parser can undo that.
		if ( count( $rows ) > 1 ) {
			$sigs = array();
			foreach ( $rows as $r ) {
				$sigs[ implode( '|', array( $r['vend'], $r['item_id'], $r['desc'], $r['barcode'], $r['cost'], $r['price'] ) ) ] = true;
			}
			if ( 1 === count( $sigs ) ) {
				$item['action']      = self::FLAG_AMBIGUOUS;
				$item['flag_reason'] = sprintf(
					/* translators: %d: number of identical rows */
					__( '%d rows share this SKU with identical description, barcode and price. Only the quantities differ, so they cannot be told apart.', 'pci' ),
					count( $rows )
				);
				self::attach_product( $item, $sku, $site );
				return $item;
			}
			$item['flag_reason'] = sprintf(
				/* translators: %d: number of rows */
				__( '%d rows share this SKU at different prices or sizes. Quantities were added together.', 'pci' ),
				count( $rows )
			);
		}

		$on_site = self::attach_product( $item, $sku, $site );

		if ( self::FLAG_DUPSKU === $item['action'] ) {
			return $item;
		}

		if ( $on_site ) {
			if ( $max > 0 && $qty > 0 ) {
				$item['action'] = self::UPDATE;
			} elseif ( $max > 0 && 0 === $qty ) {
				$item['action'] = self::HIDE;
			} elseif ( 0 === $max && 0 === $qty ) {
				$item['action'] = self::REMOVE;
			} else {
				$item['action']      = self::FLAG_REVIEW;
				$item['flag_reason'] = __( 'Stock is on hand but Max is 0, so the POS is not restocking it. Decide whether this line is being run down or was set up wrong.', 'pci' );
			}
			return $item;
		}

		// Not on the site. Only in-stock, actively-restocked lines are worth
		// sourcing; everything else is legacy noise that should never become
		// a product.
		if ( $max > 0 && $qty > 0 ) {
			if ( PCI_Suppliers::DISCONTINUED === $policy ) {
				$item['action']      = self::IGNORE;
				$item['flag_reason'] = sprintf(
					/* translators: %s: supplier code */
					__( 'In stock, but supplier %s is marked discontinued, so it will not be added.', 'pci' ),
					$first['vend']
				);
			} else {
				$item['action'] = self::NEW_P;
			}
		} else {
			$item['action']      = self::IGNORE;
			$item['flag_reason'] = __( 'Not on the website, no stock and not restocked. Legacy row.', 'pci' );
		}

		return $item;
	}

	/**
	 * Attach the matching product, or flag a duplicate-SKU collision.
	 *
	 * @return bool True when exactly one product matched.
	 */
	private static function attach_product( array &$item, $sku, array $site ) {
		if ( ! isset( $site[ $sku ] ) ) {
			return false;
		}

		$ids = $site[ $sku ];

		if ( count( $ids ) > 1 ) {
			$item['action']      = self::FLAG_DUPSKU;
			$item['product_id']  = null;
			$item['flag_reason'] = sprintf(
				/* translators: 1: number of products, 2: comma-separated IDs */
				__( '%1$d products share the SKU "%2$s". Give each one a unique SKU before this batch can touch them.', 'pci' ),
				count( $ids ),
				implode( ', ', $ids )
			);
			return false;
		}

		$product_id = (int) $ids[0];
		$item['product_id'] = $product_id;

		$product = wc_get_product( $product_id );
		if ( $product ) {
			$item['product_title'] = $product->get_name();
			$stock                 = $product->get_stock_quantity();
			$item['cur_qty']       = ( null === $stock ) ? 'NULL' : (string) $stock;
			$item['cur_price']     = (string) $product->get_regular_price();
			$item['cur_status']    = $product->get_stock_status();
			$item['cur_manage']    = $product->get_manage_stock() ? 'yes' : 'no';
		} else {
			$item['product_title'] = sprintf( __( '(post %d could not be loaded as a product)', 'pci' ), $product_id );
		}

		return true;
	}
}
