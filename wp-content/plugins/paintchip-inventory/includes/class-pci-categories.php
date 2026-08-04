<?php
defined( 'ABSPATH' ) || exit;

/**
 * Maps SLS category paths onto WooCommerce product categories.
 *
 * The site's tree was built from SLS's, but the two have drifted and the depths
 * do not correspond. SLS puts the brand at level 3 in one branch and level 4 in
 * another; "CHILDRENS CRAFTS > DRAWING SUPPLIES > PENS / MARKERS > CRAYOLA"
 * belongs under Pens and Markers on the site, not Childrens Crafts. No rule
 * gets that right, so each distinct path is decided once by a person and
 * remembered.
 *
 * Suggestions do the obvious cases; the rest is a short list to work through.
 */
class PCI_Categories {

	const OPT_MAP = 'pci_category_map';

	/** @return array path => term_id (0 meaning "deliberately unmapped") */
	public static function map() {
		$m = get_option( self::OPT_MAP, array() );
		return is_array( $m ) ? $m : array();
	}

	public static function save_map( array $map ) {
		$clean = array();
		foreach ( $map as $path => $term_id ) {
			$path = trim( (string) $path );
			if ( '' !== $path ) {
				$clean[ $path ] = (int) $term_id;
			}
		}
		update_option( self::OPT_MAP, $clean, false );
	}

	public static function set( $path, $term_id ) {
		$map          = self::map();
		$map[ $path ] = (int) $term_id;
		self::save_map( $map );
	}

	/**
	 * Term IDs for an SLS path.
	 *
	 * Falls back to progressively shorter paths, so mapping a level-2 path
	 * covers every brand and product line beneath it without listing each.
	 *
	 * @return int[]
	 */
	public static function terms_for( array $levels ) {
		$map = self::map();

		for ( $n = count( $levels ); $n >= 1; $n-- ) {
			$path = implode( ' > ', array_slice( $levels, 0, $n ) );
			if ( isset( $map[ $path ] ) && $map[ $path ] > 0 ) {
				return self::with_ancestors( (int) $map[ $path ] );
			}
		}

		return array();
	}

	/** A term plus its ancestors, so the product appears at every level. */
	private static function with_ancestors( $term_id ) {
		$ids  = array( $term_id );
		$term = get_term( $term_id, 'product_cat' );

		$guard = 0;
		while ( $term && ! is_wp_error( $term ) && $term->parent && $guard++ < 10 ) {
			$ids[] = (int) $term->parent;
			$term  = get_term( $term->parent, 'product_cat' );
		}

		return array_values( array_unique( $ids ) );
	}

	/** Flat list of product_cat terms with an indented label. */
	public static function term_choices() {
		$terms = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => false ) );
		if ( is_wp_error( $terms ) ) {
			return array();
		}

		$by_parent = array();
		foreach ( $terms as $t ) {
			$by_parent[ (int) $t->parent ][] = $t;
		}

		$out = array();

		$walk = function ( $parent, $depth ) use ( &$walk, $by_parent, &$out ) {
			if ( empty( $by_parent[ $parent ] ) ) {
				return;
			}
			$kids = $by_parent[ $parent ];
			usort( $kids, function ( $a, $b ) { return strcasecmp( $a->name, $b->name ); } );
			foreach ( $kids as $t ) {
				$out[ (int) $t->term_id ] = str_repeat( '— ', $depth ) . $t->name;
				$walk( (int) $t->term_id, $depth + 1 );
			}
		};

		$walk( 0, 0 );

		return $out;
	}

	private static function norm( $s ) {
		$s = strtolower( (string) $s );
		$s = str_replace( array( '&', '/', '-' ), ' ', $s );
		$s = preg_replace( '/\b(and|the|of|for)\b/', ' ', $s );
		return trim( preg_replace( '/[^a-z0-9]+/', ' ', $s ) );
	}

	/**
	 * Best-guess term for an SLS path.
	 *
	 * Deeper SLS levels are more specific, so they are tried first — but a
	 * brand name at level 4 should not win over a real category at level 2,
	 * which is why an exact name match is required before a fuzzy one.
	 *
	 * @return array{term_id:int,confidence:string,why:string}
	 */
	public static function suggest( array $levels ) {
		$choices = self::term_choices();
		if ( empty( $choices ) ) {
			return array( 'term_id' => 0, 'confidence' => 'none', 'why' => '' );
		}

		$by_norm = array();
		foreach ( $choices as $id => $label ) {
			$by_norm[ self::norm( str_replace( '— ', '', $label ) ) ][] = $id;
		}

		// Exact name match, deepest level first.
		for ( $i = count( $levels ) - 1; $i >= 0; $i-- ) {
			$k = self::norm( $levels[ $i ] );
			if ( '' !== $k && isset( $by_norm[ $k ] ) ) {
				return array(
					'term_id'    => (int) $by_norm[ $k ][0],
					'confidence' => 'exact',
					'why'        => sprintf( __( 'name matches level %d', 'pci' ), $i + 1 ),
				);
			}
		}

		// Otherwise the closest name, requiring a decent similarity.
		$best   = 0;
		$score  = 0;
		$whyLvl = 0;

		for ( $i = count( $levels ) - 1; $i >= 0; $i-- ) {
			$k = self::norm( $levels[ $i ] );
			if ( '' === $k ) {
				continue;
			}
			foreach ( $by_norm as $name => $ids ) {
				similar_text( $k, $name, $pct );
				if ( $pct > $score ) {
					$score  = $pct;
					$best   = (int) $ids[0];
					$whyLvl = $i + 1;
				}
			}
		}

		if ( $score >= 80 ) {
			return array(
				'term_id'    => $best,
				'confidence' => 'close',
				'why'        => sprintf( __( '%1$d%% similar to level %2$d', 'pci' ), round( $score ), $whyLvl ),
			);
		}

		return array( 'term_id' => 0, 'confidence' => 'none', 'why' => '' );
	}

	/**
	 * Distinct SLS paths in the catalog index, with usage counts.
	 *
	 * @param bool $needed_only Only paths belonging to products still to source.
	 */
	public static function paths( $needed_only = true, $limit = 500 ) {
		global $wpdb;
		$cat   = PCI_SLS_Catalog::table();
		$items = PCI_Schema::table( 'items' );

		if ( $needed_only ) {
			$rows = $wpdb->get_results( $wpdb->prepare(
				"SELECT c.categories, COUNT(*) AS n
				 FROM {$cat} c
				 INNER JOIN {$items} i ON i.sku = c.sku AND i.action = %s
				 WHERE c.categories <> '' AND c.categories <> '[]'
				 GROUP BY c.categories ORDER BY n DESC LIMIT %d",
				PCI_Classifier::NEW_P,
				(int) $limit
			) );
		} else {
			$rows = $wpdb->get_results( $wpdb->prepare(
				"SELECT categories, COUNT(*) AS n FROM {$cat}
				 WHERE categories <> '' AND categories <> '[]'
				 GROUP BY categories ORDER BY n DESC LIMIT %d",
				(int) $limit
			) );
		}

		$out = array();

		foreach ( $rows as $r ) {
			$levels = json_decode( (string) $r->categories, true );
			if ( ! is_array( $levels ) || empty( $levels ) ) {
				continue;
			}
			$path = implode( ' > ', $levels );
			if ( isset( $out[ $path ] ) ) {
				$out[ $path ]['count'] += (int) $r->n;
				continue;
			}
			$out[ $path ] = array(
				'levels' => $levels,
				'count'  => (int) $r->n,
			);
		}

		return $out;
	}

	/** Fill in every unmapped path with its suggestion. @return int */
	public static function apply_suggestions( $needed_only = true ) {
		$map = self::map();
		$n   = 0;

		foreach ( self::paths( $needed_only ) as $path => $info ) {
			if ( isset( $map[ $path ] ) ) {
				continue;
			}
			$s = self::suggest( $info['levels'] );
			if ( $s['term_id'] > 0 && 'exact' === $s['confidence'] ) {
				$map[ $path ] = $s['term_id'];
				$n++;
			}
		}

		self::save_map( $map );

		return $n;
	}

	public static function stats( $needed_only = true ) {
		$paths  = self::paths( $needed_only );
		$map    = self::map();
		$mapped = 0;

		foreach ( $paths as $path => $info ) {
			if ( isset( $map[ $path ] ) && $map[ $path ] > 0 ) {
				$mapped++;
			}
		}

		return array(
			'total'    => count( $paths ),
			'mapped'   => $mapped,
			'unmapped' => count( $paths ) - $mapped,
		);
	}
}
