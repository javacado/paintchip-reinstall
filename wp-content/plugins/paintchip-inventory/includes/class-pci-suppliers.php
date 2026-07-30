<?php
defined( 'ABSPATH' ) || exit;

/**
 * Per-supplier policy.
 *
 * This exists because the report's own Min/Max columns cannot be trusted as a
 * statement of intent. MacPherson's (MA) is the worked example: the business
 * cancelled that contract, yet 1,980 of its 2,194 rows still carry Max > 0 and
 * 1,857 still carry a reorder point, because nobody zeroed the POS out. Reading
 * the data alone, MA looks like the second most active supplier in the store.
 *
 * So intent lives here, set by a human, and it overrides the file:
 *
 *   active       - normal handling
 *   discontinued - never propose new products; existing stock still updates so
 *                  the shelf count stays honest while it sells through
 *   ignore       - skip entirely; the file's rows for this supplier are noise
 */
class PCI_Suppliers {

	const OPT = 'pci_supplier_policy';

	const ACTIVE       = 'active';
	const DISCONTINUED = 'discontinued';
	const IGNORE       = 'ignore';

	/** Supplier codes seen in the reference report, with a starting policy. */
	public static function defaults() {
		return array(
			'MA' => self::DISCONTINUED, // contract cancelled — confirmed by the business
		);
	}

	public static function policy_map() {
		$saved = get_option( self::OPT, null );
		if ( ! is_array( $saved ) ) {
			$saved = self::defaults();
			update_option( self::OPT, $saved );
		}
		return $saved;
	}

	public static function policy_for( $vend ) {
		$map  = self::policy_map();
		$vend = strtoupper( trim( $vend ) );
		return isset( $map[ $vend ] ) ? $map[ $vend ] : self::ACTIVE;
	}

	public static function save_policy( array $map ) {
		$clean = array();
		$valid = array( self::ACTIVE, self::DISCONTINUED, self::IGNORE );
		foreach ( $map as $code => $policy ) {
			$code = strtoupper( preg_replace( '/[^A-Za-z0-9]/', '', (string) $code ) );
			if ( '' !== $code && in_array( $policy, $valid, true ) ) {
				$clean[ $code ] = $policy;
			}
		}
		update_option( self::OPT, $clean );
	}

	public static function label( $policy ) {
		switch ( $policy ) {
			case self::DISCONTINUED:
				return __( 'Discontinued — update stock, never add new', 'pci' );
			case self::IGNORE:
				return __( 'Ignore — skip every row', 'pci' );
			default:
				return __( 'Active', 'pci' );
		}
	}

	/** Distinct supplier codes present in a parsed run, with row counts. */
	public static function codes_in_run( $run_id ) {
		global $wpdb;
		$items = PCI_Schema::table( 'items' );
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT vend, COUNT(*) AS skus, SUM(file_qty) AS units,
				        SUM(CASE WHEN action = 'new' THEN 1 ELSE 0 END) AS new_skus,
				        SUM(CASE WHEN product_id IS NOT NULL THEN 1 ELSE 0 END) AS on_site
				 FROM {$items} WHERE run_id = %d
				 GROUP BY vend ORDER BY skus DESC",
				$run_id
			)
		);
	}
}
