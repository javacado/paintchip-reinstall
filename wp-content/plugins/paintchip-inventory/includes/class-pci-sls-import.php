<?php
defined( 'ABSPATH' ) || exit;

/**
 * Imports the SLS "Download Detail" export.
 *
 * That export is the dealer feed we spent a long time trying to reconstruct by
 * scraping: SKU, description, three UPC fields, MSRP and dealer pricing, the
 * full LEVEL1..LEVEL5 category path, brand, per-warehouse stock, weights and
 * dimensions. It is downloaded from the portal by hand, in a browser with a
 * working session, which sidesteps rate limiting entirely.
 *
 * XLSX is read directly rather than through a library: the format is a zip of
 * XML, and only two members matter — the shared string table and the sheet.
 */
class PCI_SLS_Import {

	/** Column header => internal field. Unlisted columns are ignored. */
	const MAP = array(
		'SLS_SKU'    => 'sku',
		'CUST_SKU'   => 'cust_sku',
		'DESCRIPTIO' => 'title',
		'SHORT_DESC' => 'short_title',
		'VENDOR_NAM' => 'brand',
		'MSRP'       => 'msrp',
		'REG_PRICE'  => 'net',
		'REG_DISCOU' => 'disc',
		'UPC1'       => 'upc',
		'UPC2'       => 'upc2',
		'UPC3'       => 'upc3',
		'LEVEL1'     => 'level1',
		'LEVEL2'     => 'level2',
		'LEVEL3'     => 'level3',
		'LEVEL4'     => 'level4',
		'LEVEL5'     => 'level5',
		'QOH_NO'     => 'qoh_no',
		'QOH_VEGAS'  => 'qoh_vegas',
		'WEIGHT_EA'  => 'weight',
		'HEIGHT_EA'  => 'height',
		'WIDTH_EA'   => 'width',
		'DEPTH_EA'   => 'depth',
		'COUNTRY'    => 'country',
		'PROP65'     => 'prop65',
		'DROPSHIP'   => 'dropship',
		'MIN_ORD_QT' => 'min_order',
	);

	/**
	 * Read an XLSX into rows of cell values.
	 *
	 * @return array|WP_Error List of rows, each a list of strings.
	 */
	public static function read_xlsx( $path ) {
		if ( ! class_exists( 'ZipArchive' ) ) {
			return new WP_Error( 'pci_nozip', __( 'PHP has no ZipArchive support, so .xlsx cannot be read. Save the file as CSV and upload that instead.', 'pci' ) );
		}

		$zip = new ZipArchive();
		if ( true !== $zip->open( $path ) ) {
			return new WP_Error( 'pci_badzip', __( 'That file could not be opened as a spreadsheet.', 'pci' ) );
		}

		// Shared strings: most cell text lives here, referenced by index.
		$shared = array();
		$ss     = $zip->getFromName( 'xl/sharedStrings.xml' );
		if ( false !== $ss ) {
			$xml = @simplexml_load_string( $ss );
			if ( $xml ) {
				foreach ( $xml->si as $si ) {
					// A string may be split across runs; concatenate them.
					$text = '';
					if ( isset( $si->t ) ) {
						$text = (string) $si->t;
					}
					if ( isset( $si->r ) ) {
						foreach ( $si->r as $r ) {
							$text .= (string) $r->t;
						}
					}
					$shared[] = $text;
				}
			}
		}

		$sheet = $zip->getFromName( 'xl/worksheets/sheet1.xml' );
		$zip->close();

		if ( false === $sheet ) {
			return new WP_Error( 'pci_nosheet', __( 'No worksheet was found in that file.', 'pci' ) );
		}

		$xml = @simplexml_load_string( $sheet );
		if ( ! $xml || ! isset( $xml->sheetData ) ) {
			return new WP_Error( 'pci_badsheet', __( 'The worksheet could not be read.', 'pci' ) );
		}

		$rows = array();

		foreach ( $xml->sheetData->row as $row ) {
			$cells = array();
			foreach ( $row->c as $c ) {
				$ref  = (string) $c['r'];
				$type = (string) $c['t'];
				$col  = self::col_index( $ref );

				$value = '';
				if ( 'inlineStr' === $type && isset( $c->is->t ) ) {
					$value = (string) $c->is->t;
				} elseif ( isset( $c->v ) ) {
					$raw = (string) $c->v;
					if ( 's' === $type ) {
						$value = isset( $shared[ (int) $raw ] ) ? $shared[ (int) $raw ] : '';
					} else {
						$value = $raw;
					}
				}

				// Blank cells are skipped in the XML, so index explicitly.
				$cells[ $col ] = trim( $value );
			}

			if ( empty( $cells ) ) {
				continue;
			}

			$max  = max( array_keys( $cells ) );
			$flat = array();
			for ( $i = 0; $i <= $max; $i++ ) {
				$flat[] = isset( $cells[ $i ] ) ? $cells[ $i ] : '';
			}

			$rows[] = $flat;
		}

		return $rows;
	}

	/** "BC12" => 54 */
	private static function col_index( $ref ) {
		if ( ! preg_match( '/^([A-Z]+)/', strtoupper( $ref ), $m ) ) {
			return 0;
		}
		$letters = $m[1];
		$n       = 0;
		for ( $i = 0; $i < strlen( $letters ); $i++ ) {
			$n = $n * 26 + ( ord( $letters[ $i ] ) - 64 );
		}
		return $n - 1;
	}

	/** Read a CSV export, as an alternative to XLSX. */
	public static function read_csv( $path ) {
		$rows = array();
		$fh   = fopen( $path, 'r' );
		if ( ! $fh ) {
			return new WP_Error( 'pci_badcsv', __( 'That file could not be opened.', 'pci' ) );
		}
		while ( false !== ( $row = fgetcsv( $fh, 0, ',' ) ) ) {
			$rows[] = array_map( 'trim', $row );
		}
		fclose( $fh );
		return $rows;
	}

	/**
	 * Import one export file into the catalog index.
	 *
	 * @return array{ok:bool,message:string,added:int,updated:int,rows:int,categories:array}
	 */
	public static function import_file( $path, $filename = '' ) {
		$ext  = strtolower( pathinfo( $filename ? $filename : $path, PATHINFO_EXTENSION ) );
		$rows = ( 'csv' === $ext || 'txt' === $ext ) ? self::read_csv( $path ) : self::read_xlsx( $path );

		if ( is_wp_error( $rows ) ) {
			return array( 'ok' => false, 'message' => $rows->get_error_message(), 'added' => 0, 'updated' => 0, 'rows' => 0, 'categories' => array() );
		}

		if ( count( $rows ) < 2 ) {
			return array( 'ok' => false, 'message' => __( 'That file holds no data rows.', 'pci' ), 'added' => 0, 'updated' => 0, 'rows' => 0, 'categories' => array() );
		}

		// Header row: map column position to our field names.
		$header = array_shift( $rows );
		$cols   = array();
		foreach ( $header as $i => $name ) {
			$name = strtoupper( trim( (string) $name ) );
			if ( isset( self::MAP[ $name ] ) ) {
				$cols[ self::MAP[ $name ] ] = $i;
			}
		}

		if ( ! isset( $cols['sku'] ) ) {
			return array(
				'ok'      => false,
				'message' => __( 'No SLS_SKU column was found. Is this the Download Detail export?', 'pci' ),
				'added'   => 0, 'updated' => 0, 'rows' => 0, 'categories' => array(),
			);
		}

		$items = array();
		$paths = array();

		foreach ( $rows as $row ) {
			$get = function ( $field ) use ( $row, $cols ) {
				return isset( $cols[ $field ], $row[ $cols[ $field ] ] ) ? trim( (string) $row[ $cols[ $field ] ] ) : '';
			};

			// Excel stores money as a float, so 9.59 arrives as
			// 9.5899999999999999. Round anything numeric back to two places.
			$money = function ( $field ) use ( $get ) {
				$v = $get( $field );
				return ( '' === $v || ! is_numeric( $v ) ) ? $v : (string) round( (float) $v, 2 );
			};

			$sku = $get( 'sku' );
			if ( '' === $sku ) {
				continue;
			}

			$levels = array();
			foreach ( array( 'level1', 'level2', 'level3', 'level4', 'level5' ) as $l ) {
				$v = $get( $l );
				if ( '' !== $v ) {
					$levels[] = $v;
				}
			}
			if ( $levels ) {
				$paths[ implode( ' > ', $levels ) ] = true;
			}

			$qoh = (int) $get( 'qoh_no' ) + (int) $get( 'qoh_vegas' );

			$title = $get( 'title' );
			if ( '' === $title ) {
				$title = $get( 'short_title' );
			}

			$items[] = array(
				'sku'         => $sku,
				'title'       => $title,
				'upc'         => preg_replace( '/[^0-9]/', '', $get( 'upc' ) ),
				'description' => '',
				'msrp'        => $money( 'msrp' ),
				'net'         => $money( 'net' ),
				'disc'        => $money( 'disc' ),
				'qoh'         => $qoh,
				'qoh_detail'  => sprintf( 'N-%d V-%d', (int) $get( 'qoh_no' ), (int) $get( 'qoh_vegas' ) ),
				'image_url'   => PCI_Scraper_SLS::guess_image_url( $sku ),
				'thumb_url'   => '',
				'categories'  => $levels,
				'brand'       => $get( 'brand' ),
				'weight'      => $money( 'weight' ),
				'dimensions'  => array( $money( 'height' ), $money( 'width' ), $money( 'depth' ) ),
				'country'     => $get( 'country' ),
				'prop65'      => $get( 'prop65' ),
				'dropship'    => $get( 'dropship' ),
				'min_order'   => $get( 'min_order' ),
				'source'      => 'export',
			);
		}

		if ( empty( $items ) ) {
			return array( 'ok' => false, 'message' => __( 'No usable rows were found.', 'pci' ), 'added' => 0, 'updated' => 0, 'rows' => 0, 'categories' => array() );
		}

		$res = PCI_SLS_Catalog::index_items( $items );

		return array(
			'ok'         => true,
			'message'    => sprintf(
				__( '%1$d products imported — %2$d new, %3$d updated.', 'pci' ),
				count( $items ),
				$res['added'],
				$res['updated']
			),
			'added'      => $res['added'],
			'updated'    => $res['updated'],
			'rows'       => count( $items ),
			'categories' => array_keys( $paths ),
		);
	}
}
