<?php
defined( 'ABSPATH' ) || exit;

class PCI_Run {

	const OPT_MAX_CHANGE_PCT = 'pci_max_change_pct';
	const OPT_WRITE_PRICES   = 'pci_write_prices';
	const OPT_HIDE_MODE      = 'pci_hide_mode'; // outofstock | exclude | draft

	public static function max_change_pct() {
		return (float) get_option( self::OPT_MAX_CHANGE_PCT, 25 );
	}

	public static function write_prices() {
		return (bool) get_option( self::OPT_WRITE_PRICES, false );
	}

	public static function hide_mode() {
		$mode = get_option( self::OPT_HIDE_MODE, 'outofstock' );
		return in_array( $mode, array( 'outofstock', 'exclude', 'draft' ), true ) ? $mode : 'outofstock';
	}

	/**
	 * Ingest an uploaded report: parse, classify, stage.
	 *
	 * @return int|WP_Error Run ID.
	 */
	public static function create_from_file( $path, $filename ) {
		global $wpdb;

		$parsed = PCI_Parser::parse_file( $path );
		if ( is_wp_error( $parsed ) ) {
			return $parsed;
		}

		$items   = PCI_Classifier::classify( $parsed['records'], $parsed['orphans'] );
		$profile = PCI_Signals::profile( $parsed['records'] );

		$runs = PCI_Schema::table( 'runs' );
		$ok   = $wpdb->insert(
			$runs,
			array(
				'filename'    => $filename,
				'stored_path' => $path,
				'file_hash'   => sha1_file( $path ),
				'report_date' => $parsed['report_date'],
				'status'      => 'parsed',
				'created_at'  => current_time( 'mysql' ),
				'created_by'  => get_current_user_id(),
			)
		);

		if ( ! $ok ) {
			return new WP_Error( 'pci_db', 'The batch could not be saved. Check that the plugin tables exist.' );
		}

		$run_id = (int) $wpdb->insert_id;
		$table  = PCI_Schema::table( 'items' );

		foreach ( $items as $item ) {
			$wpdb->insert(
				$table,
				array(
					'run_id'        => $run_id,
					'sku'           => substr( (string) $item['sku'], 0, 100 ),
					'vend'          => substr( (string) $item['vend'], 0, 10 ),
					'item_id'       => substr( (string) $item['item_id'], 0, 40 ),
					'description'   => substr( (string) $item['description'], 0, 190 ),
					'dept'          => substr( (string) $item['dept'], 0, 20 ),
					'file_qty'      => (int) $item['file_qty'],
					'file_max'      => (int) $item['file_max'],
					'file_min'      => (int) $item['file_min'],
					'file_price'    => $item['file_price'],
					'file_cost'     => $item['file_cost'],
					'row_count'     => (int) $item['row_count'],
					'action'        => $item['action'],
					'flag_reason'   => substr( (string) $item['flag_reason'], 0, 190 ),
					'product_id'    => $item['product_id'],
					'product_title' => isset( $item['product_title'] ) ? $item['product_title'] : null,
					'cur_qty'       => isset( $item['cur_qty'] ) ? $item['cur_qty'] : null,
					'cur_price'     => isset( $item['cur_price'] ) ? $item['cur_price'] : null,
					'cur_status'    => isset( $item['cur_status'] ) ? $item['cur_status'] : null,
					'cur_manage'    => isset( $item['cur_manage'] ) ? $item['cur_manage'] : null,
					'raw'           => $item['raw'],
				)
			);
		}

		self::refresh_stats( $run_id, $profile );

		return $run_id;
	}

	public static function get( $run_id ) {
		global $wpdb;
		$runs = PCI_Schema::table( 'runs' );
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$runs} WHERE id = %d", (int) $run_id ) );
	}

	public static function recent( $limit = 25 ) {
		global $wpdb;
		$runs = PCI_Schema::table( 'runs' );
		return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$runs} ORDER BY id DESC LIMIT %d", (int) $limit ) );
	}

	public static function counts( $run_id ) {
		global $wpdb;
		$items = PCI_Schema::table( 'items' );
		$rows  = $wpdb->get_results(
			$wpdb->prepare( "SELECT action, COUNT(*) AS n FROM {$items} WHERE run_id = %d GROUP BY action", (int) $run_id )
		);

		$out = array_fill_keys( PCI_Classifier::all_actions(), 0 );
		foreach ( $rows as $r ) {
			$out[ $r->action ] = (int) $r->n;
		}
		return $out;
	}

	public static function items( $run_id, $action, $limit = 500, $offset = 0 ) {
		global $wpdb;
		$items = PCI_Schema::table( 'items' );

		if ( is_array( $action ) ) {
			$in = implode( ',', array_fill( 0, count( $action ), '%s' ) );
			$sql = $wpdb->prepare(
				"SELECT * FROM {$items} WHERE run_id = %d AND action IN ({$in}) ORDER BY vend, sku LIMIT %d OFFSET %d",
				array_merge( array( (int) $run_id ), $action, array( (int) $limit, (int) $offset ) )
			);
		} else {
			$sql = $wpdb->prepare(
				"SELECT * FROM {$items} WHERE run_id = %d AND action = %s ORDER BY vend, sku LIMIT %d OFFSET %d",
				(int) $run_id,
				$action,
				(int) $limit,
				(int) $offset
			);
		}

		return $wpdb->get_results( $sql );
	}

	/** Published, purchasable product count — the denominator for the safety check. */
	public static function live_product_count() {
		global $wpdb;
		return (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'product' AND post_status = 'publish'"
		);
	}

	public static function refresh_stats( $run_id, $profile = null ) {
		global $wpdb;
		$counts = self::counts( $run_id );

		// Preserve an existing fingerprint when recomputing counts, since the
		// parsed records are long gone by then.
		if ( null === $profile ) {
			$existing = self::stats( $run_id );
			$profile  = isset( $existing['profile'] ) ? $existing['profile'] : array();
		}
		$live   = self::live_product_count();
		$risky  = $counts[ PCI_Classifier::HIDE ] + $counts[ PCI_Classifier::REMOVE ];

		$stats = array(
			'counts'          => $counts,
			'live_products'   => $live,
			'risky'           => $risky,
			'risky_pct'       => $live > 0 ? round( $risky / $live * 100, 1 ) : 0,
			'threshold_pct'   => self::max_change_pct(),
			'profile'         => $profile,
			'computed_at'     => current_time( 'mysql' ),
		);

		$wpdb->update(
			PCI_Schema::table( 'runs' ),
			array( 'stats' => wp_json_encode( $stats ) ),
			array( 'id' => (int) $run_id )
		);

		return $stats;
	}

	public static function stats( $run_id ) {
		$run = self::get( $run_id );
		if ( ! $run ) {
			return array();
		}
		$stats = json_decode( (string) $run->stats, true );
		return is_array( $stats ) ? $stats : self::refresh_stats( $run_id );
	}

	/**
	 * Does this batch exceed the safety threshold?
	 *
	 * @return array{blocked:bool,message:string}
	 */
	public static function safety_check( $run_id ) {
		$stats = self::stats( $run_id );
		$pct   = isset( $stats['risky_pct'] ) ? (float) $stats['risky_pct'] : 0;
		$max   = self::max_change_pct();

		if ( $pct > $max ) {
			return array(
				'blocked' => true,
				'message' => sprintf(
					/* translators: 1: percentage, 2: count, 3: threshold */
					__( 'This batch would hide or remove %2$d products — %1$s%% of the %4$d published products in the store. The safety limit is %3$s%%. Review the lists below, then tick the override box if this is genuinely correct.', 'pci' ),
					$pct,
					isset( $stats['risky'] ) ? $stats['risky'] : 0,
					$max,
					isset( $stats['live_products'] ) ? $stats['live_products'] : 0
				),
			);
		}

		return array( 'blocked' => false, 'message' => '' );
	}

	public static function set_status( $run_id, $status, array $extra = array() ) {
		global $wpdb;
		$wpdb->update(
			PCI_Schema::table( 'runs' ),
			array_merge( array( 'status' => $status ), $extra ),
			array( 'id' => (int) $run_id )
		);
	}
}
