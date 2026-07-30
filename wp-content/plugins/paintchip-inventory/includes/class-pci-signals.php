<?php
defined( 'ABSPATH' ) || exit;

/**
 * Report fingerprinting.
 *
 * The classifier's rules are a hypothesis about how the client's POS records
 * "gone for good" versus "temporarily out". That hypothesis was inferred from
 * one report. It can go stale silently: if the convention changes, rule-based
 * classification keeps producing confident answers from a wrong premise.
 *
 * So every run also gets profiled. For each column we record what values
 * actually appear and how often, then diff that against the previous run. A
 * column that has been constant for months and suddenly starts varying is the
 * single best signal that the convention moved — and it shows up here before
 * anyone approves anything.
 *
 * Columns known to carry no signal in the 13-Jul-25 baseline:
 *
 *   Deleted     No   on 9,808 / 9,808
 *   Active      Yes  on 9,808 / 9,808
 *   QCo         0    on 9,804 / 9,808
 *   Multiplier  0    on 9,808 / 9,808
 *   Fixed       0    on 9,808 / 9,808
 *   Tax         Yes  on 9,806 / 9,808
 *
 * Those six are the most likely places a new convention would appear, which is
 * exactly why they are surfaced even though nothing reads them yet.
 */
class PCI_Signals {

	/** Columns worth profiling, and whether to enumerate every value. */
	const COLUMNS = array(
		'deleted'    => true,
		'active'     => true,
		'dept'       => true,
		'unit'       => true,
		'tax'        => true,
		'qco'        => true,
		'multiplier' => true,
		'fixed'      => true,
		'mfr'        => true,
		'loc'        => false,
		'note'       => false,
		'supplier2'  => false,
		'barcode'    => false,
		'alternate'  => false,
		'min'        => false,
		'max'        => false,
	);

	/** Values beyond this and we report shape rather than a value list. */
	const ENUM_LIMIT = 25;

	/**
	 * Build a fingerprint of one parsed report.
	 *
	 * @param array $records From PCI_Parser::parse_file().
	 * @return array column => profile
	 */
	public static function profile( array $records ) {
		$total   = count( $records );
		$profile = array();

		foreach ( self::COLUMNS as $col => $enumerate ) {
			$counts    = array();
			$populated = 0;

			foreach ( $records as $r ) {
				$v = isset( $r[ $col ] ) ? trim( (string) $r[ $col ] ) : '';
				if ( '' !== $v ) {
					$populated++;
				}
				if ( ! isset( $counts[ $v ] ) ) {
					$counts[ $v ] = 0;
				}
				$counts[ $v ]++;
			}

			arsort( $counts );
			$distinct = count( $counts );

			$entry = array(
				'total'     => $total,
				'populated' => $populated,
				'distinct'  => $distinct,
				'constant'  => ( 1 === $distinct ),
			);

			// A column is treated as carrying no signal when one value covers
			// essentially everything. 99.5% is deliberately strict: QCo at
			// 9,804/9,808 is 99.96% and still counts as no signal, but a column
			// that drifts to 99% is worth a look.
			$top             = key( $counts );
			$top_n           = current( $counts );
			$entry['top']    = $top;
			$entry['top_n']  = $top_n;
			$entry['top_pct'] = $total > 0 ? round( $top_n / $total * 100, 2 ) : 0;
			$entry['no_signal'] = ( $entry['top_pct'] >= 99.5 );

			if ( $enumerate && $distinct <= self::ENUM_LIMIT ) {
				$entry['values']     = $counts;
				$entry['enumerated'] = true;
			} else {
				// Only the top few, so the value list is not a complete census
				// and must not be diffed for "new" values.
				$entry['values']     = array_slice( $counts, 0, 8, true );
				$entry['truncated']  = ( $distinct > 8 );
				$entry['enumerated'] = false;
			}

			$profile[ $col ] = $entry;
		}

		return $profile;
	}

	/**
	 * Diff two fingerprints and describe what moved.
	 *
	 * @return array List of change descriptions, most significant first.
	 */
	public static function compare( array $prev, array $cur ) {
		$changes = array();

		foreach ( $cur as $col => $now ) {
			if ( ! isset( $prev[ $col ] ) ) {
				continue;
			}
			$was = $prev[ $col ];

			// The headline case: a column that carried no signal now varies.
			if ( ! empty( $was['no_signal'] ) && empty( $now['no_signal'] ) ) {
				$changes[] = array(
					'severity' => 'high',
					'column'   => $col,
					'message'  => sprintf(
						/* translators: 1: column, 2: previous value, 3: previous pct, 4: current pct */
						__( '%1$s used to be effectively constant ("%2$s" on %3$s%% of rows) and now varies — only %4$s%% share the top value. This is the strongest sign the convention changed.', 'pci' ),
						strtoupper( $col ),
						$was['top'],
						$was['top_pct'],
						$now['top_pct']
					),
				);
				continue;
			}

			// Values appearing for the first time. Only meaningful where both
			// profiles hold a complete census — the top-8 slice kept for
			// high-cardinality columns like Barcode churns every month and
			// would emit noise on every run.
			if ( empty( $was['enumerated'] ) || empty( $now['enumerated'] ) ) {
				continue;
			}

			$new_values = array_diff( array_keys( $now['values'] ), array_keys( $was['values'] ) );
			$new_values = array_filter( $new_values, function ( $v ) { return '' !== $v; } );
			if ( ! empty( $new_values ) ) {
				$changes[] = array(
					'severity' => empty( $was['no_signal'] ) ? 'low' : 'high',
					'column'   => $col,
					'message'  => sprintf(
						/* translators: 1: column, 2: comma-separated values */
						__( '%1$s has values not seen in the previous report: %2$s', 'pci' ),
						strtoupper( $col ),
						implode( ', ', array_map( function ( $v ) { return '"' . $v . '"'; }, array_slice( $new_values, 0, 8 ) ) )
					),
				);
			}

			// A big swing in how often a column is filled in at all.
			$was_fill = $was['total'] > 0 ? $was['populated'] / $was['total'] : 0;
			$now_fill = $now['total'] > 0 ? $now['populated'] / $now['total'] : 0;
			if ( abs( $now_fill - $was_fill ) > 0.15 ) {
				$changes[] = array(
					'severity' => 'medium',
					'column'   => $col,
					'message'  => sprintf(
						/* translators: 1: column, 2: previous pct, 3: current pct */
						__( '%1$s is filled in on %3$s%% of rows, up from %2$s%%.', 'pci' ),
						strtoupper( $col ),
						round( $was_fill * 100 ),
						round( $now_fill * 100 )
					),
				);
			}
		}

		$rank = array( 'high' => 0, 'medium' => 1, 'low' => 2 );
		usort( $changes, function ( $a, $b ) use ( $rank ) {
			return $rank[ $a['severity'] ] <=> $rank[ $b['severity'] ];
		} );

		return $changes;
	}

	/**
	 * How individual SKUs moved between two runs.
	 *
	 * This is the other half of the detector. Two conventions for "gone for
	 * good" look identical in a single report but completely different across
	 * two:
	 *
	 *   - the row stays and Max drops to 0  -> "still_listed_max_zeroed"
	 *   - the row is removed from the export -> "dropped_from_report"
	 *
	 * If dropped_from_report is large and still_listed_max_zeroed is near zero,
	 * the POS deletes rows rather than flagging them, and the Max=0 rule is
	 * looking for a signal that no longer exists.
	 *
	 * @return array|null Null when there is no previous run to compare.
	 */
	public static function transitions( $run_id, $prev_run_id ) {
		global $wpdb;

		if ( ! $prev_run_id ) {
			return null;
		}

		$t = PCI_Schema::table( 'items' );

		$sql = $wpdb->prepare(
			"SELECT
				SUM(CASE WHEN p.sku IS NULL THEN 1 ELSE 0 END) AS appeared,
				SUM(CASE WHEN p.sku IS NOT NULL AND p.file_max > 0 AND c.file_max = 0 THEN 1 ELSE 0 END) AS still_listed_max_zeroed,
				SUM(CASE WHEN p.sku IS NOT NULL AND p.file_qty > 0 AND c.file_qty = 0 THEN 1 ELSE 0 END) AS went_to_zero_qty,
				SUM(CASE WHEN p.sku IS NOT NULL AND p.file_qty = 0 AND c.file_qty > 0 THEN 1 ELSE 0 END) AS restocked
			 FROM {$t} c
			 LEFT JOIN {$t} p ON p.run_id = %d AND p.sku = c.sku AND p.sku <> ''
			 WHERE c.run_id = %d AND c.sku <> ''",
			(int) $prev_run_id,
			(int) $run_id
		);

		$row = $wpdb->get_row( $sql, ARRAY_A );
		if ( ! $row ) {
			return null;
		}

		$dropped = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$t} p
				 WHERE p.run_id = %d AND p.sku <> ''
				   AND NOT EXISTS (
					   SELECT 1 FROM {$t} c WHERE c.run_id = %d AND c.sku = p.sku
				   )",
				(int) $prev_run_id,
				(int) $run_id
			)
		);

		$row['dropped_from_report'] = $dropped;
		$row['prev_run_id']         = (int) $prev_run_id;

		return array_map( 'intval', $row );
	}

	/** The most recent run before this one, applied or not. */
	public static function previous_run_id( $run_id ) {
		global $wpdb;
		$runs = PCI_Schema::table( 'runs' );
		return (int) $wpdb->get_var(
			$wpdb->prepare( "SELECT id FROM {$runs} WHERE id < %d ORDER BY id DESC LIMIT 1", (int) $run_id )
		);
	}

	public static function label( $col ) {
		$labels = array(
			'deleted'    => __( 'Deleted flag', 'pci' ),
			'active'     => __( 'Active flag', 'pci' ),
			'dept'       => __( 'Dept', 'pci' ),
			'unit'       => __( 'Unit', 'pci' ),
			'tax'        => __( 'Tax', 'pci' ),
			'qco'        => __( 'QCo', 'pci' ),
			'multiplier' => __( 'Multiplier', 'pci' ),
			'fixed'      => __( 'Fixed', 'pci' ),
			'mfr'        => __( 'Manufacturer', 'pci' ),
			'loc'        => __( 'Location', 'pci' ),
			'note'       => __( 'Note', 'pci' ),
			'supplier2'  => __( 'Second supplier', 'pci' ),
			'barcode'    => __( 'Barcode', 'pci' ),
			'alternate'  => __( 'Alternate (SKU key)', 'pci' ),
			'min'        => __( 'Min', 'pci' ),
			'max'        => __( 'Max', 'pci' ),
		);
		return isset( $labels[ $col ] ) ? $labels[ $col ] : $col;
	}
}
