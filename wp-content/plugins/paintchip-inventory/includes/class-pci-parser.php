<?php
defined( 'ABSPATH' ) || exit;

/**
 * Parses the POS "General Inventory Full Master List" report.
 *
 * The report is FIXED-WIDTH, not delimited. Every record spans two lines:
 * a master line (vendor, item id, description, min/max, deleted, active)
 * followed by a detail line (product, alternate, barcode, cost, price, qty).
 *
 * The offsets below were derived empirically: for each line type, every
 * character column that is blank across all 9,808 records in the reference
 * report marks a field boundary. They then cross-checked against the report's
 * own header row.
 *
 * Do NOT go back to splitting on runs of whitespace. The field count changes
 * with whichever optional columns happen to be populated (Manufacturer,
 * Location, Dept), which silently dropped 20% of records and read Min where
 * it meant Max.
 */
class PCI_Parser {

	/** offset => length, master (first) line. */
	const MASTER = array(
		'vend'    => array( 0, 3 ),
		'item_id' => array( 9, 12 ),
		'desc'    => array( 27, 30 ),
		'dept'    => array( 57, 4 ),
		'mfr'     => array( 66, 31 ),
		'loc'     => array( 97, 15 ),
		'min'     => array( 112, 8 ),
		'max'     => array( 120, 10 ),
		'deleted' => array( 130, 13 ),
		'active'  => array( 143, 17 ),
	);

	/** offset => length, detail (second) line. */
	const DETAIL = array(
		'product'    => array( 0, 11 ),
		'alternate'  => array( 11, 16 ),
		'barcode'    => array( 27, 20 ),
		'cost'       => array( 47, 14 ),
		'price'      => array( 61, 11 ),
		'unit'       => array( 72, 6 ),
		'tax'        => array( 78, 10 ),
		'supplier'   => array( 88, 12 ),
		'supplier2'  => array( 100, 18 ),
		'qco'        => array( 118, 8 ),
		'qo'         => array( 126, 7 ),
		'note'       => array( 133, 8 ),
		'multiplier' => array( 141, 15 ),
		'fixed'      => array( 156, 14 ),
	);

	/** Page furniture and column headers, none of which are data. */
	private static function is_noise( $line ) {
		if ( '' === trim( $line ) ) {
			return true;
		}
		$patterns = array(
			'/^\s*The Paint Chip\s*$/i',
			'/^Inventory:/i',
			'/Page\s+\d+\s+of/i',
			'/^\s*\d{1,2}:\d{2}\s*[AP]M\s*$/i',
			'/^Vend\s+Item ID/i',
			'/^Product\s+Alternate/i',
		);
		foreach ( $patterns as $p ) {
			if ( preg_match( $p, $line ) ) {
				return true;
			}
		}
		return false;
	}

	private static function cut( $line, $spec ) {
		$out = array();
		foreach ( $spec as $key => $pos ) {
			$out[ $key ] = trim( substr( $line, $pos[0], $pos[1] ) );
		}
		return $out;
	}

	/** A detail line always carries currency; a master line never does. */
	private static function is_detail( $line ) {
		return false !== strpos( $line, '$' );
	}

	public static function to_number( $raw ) {
		$clean = preg_replace( '/[^0-9.\-]/', '', (string) $raw );
		if ( '' === $clean || '-' === $clean || '.' === $clean ) {
			return null;
		}
		return (float) $clean;
	}

	/**
	 * @param string $path Absolute path to the uploaded report.
	 * @return array{records:array,orphans:array,report_date:string}|WP_Error
	 */
	public static function parse_file( $path ) {
		if ( ! is_readable( $path ) ) {
			return new WP_Error( 'pci_unreadable', 'That file could not be read. Re-upload it and try again.' );
		}

		$raw = file_get_contents( $path );
		if ( false === $raw || '' === $raw ) {
			return new WP_Error( 'pci_empty', 'That file is empty.' );
		}

		// Normalise CRLF and lone-CR to LF before splitting.
		$raw   = str_replace( array( "\r\n", "\r" ), "\n", $raw );
		$lines = explode( "\n", $raw );

		$report_date = '';
		foreach ( $lines as $line ) {
			if ( preg_match( '/^Inventory:.*?([0-9]{1,2}-[A-Za-z]{3}-[0-9]{2,4})\s*$/', $line, $m ) ) {
				$report_date = $m[1];
				break;
			}
		}

		$data = array();
		foreach ( $lines as $line ) {
			if ( ! self::is_noise( $line ) ) {
				$data[] = $line;
			}
		}

		$records = array();
		$orphans = array();
		$count   = count( $data );

		for ( $i = 0; $i < $count; $i++ ) {
			$line = $data[ $i ];

			// A detail line with no master before it is an orphan.
			if ( self::is_detail( $line ) ) {
				$orphans[] = array(
					'reason' => 'Detail line with no matching master line',
					'raw'    => $line,
				);
				continue;
			}

			if ( $i + 1 >= $count || ! self::is_detail( $data[ $i + 1 ] ) ) {
				$orphans[] = array(
					'reason' => 'Master line with no matching detail line',
					'raw'    => $line,
				);
				continue;
			}

			$master = self::cut( $line, self::MASTER );
			$detail = self::cut( $data[ $i + 1 ], self::DETAIL );

			$records[] = array_merge(
				$master,
				$detail,
				array(
					'raw_master' => $line,
					'raw_detail' => $data[ $i + 1 ],
				)
			);

			$i++; // consume the detail line
		}

		if ( empty( $records ) ) {
			return new WP_Error(
				'pci_no_records',
				'No inventory records were found. Check that this is the fixed-width "General Inventory Full Master List" text export rather than a CSV or spreadsheet.'
			);
		}

		return array(
			'records'     => $records,
			'orphans'     => $orphans,
			'report_date' => $report_date,
		);
	}
}
