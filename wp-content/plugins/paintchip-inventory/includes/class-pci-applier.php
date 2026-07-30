<?php
defined( 'ABSPATH' ) || exit;

/**
 * Writes changes and records how to undo them.
 *
 * Everything goes through wc_get_product() / $product->save(), never a raw
 * UPDATE on wp_postmeta. That matters concretely: the previous tool wrote
 * _stock directly and never recalculated _stock_status, which is why the
 * reference store had 366 products at zero stock still advertised as in stock,
 * and why wp_wc_product_meta_lookup drifted out of sync with the meta.
 *
 * It also forces stock management on. Roughly 38% of the reference catalog had
 * _manage_stock = 'no' and a NULL _stock, and WooCommerce ignores a quantity
 * entirely on those products — the import "succeeded" and changed nothing.
 */
class PCI_Applier {

	/** Fields captured before every write, and restored on rollback. */
	private static function snapshot( WC_Product $product ) {
		return array(
			'manage_stock'       => $product->get_manage_stock(),
			'stock_quantity'     => $product->get_stock_quantity(),
			'stock_status'       => $product->get_stock_status(),
			'backorders'         => $product->get_backorders(),
			'regular_price'      => $product->get_regular_price(),
			'sale_price'         => $product->get_sale_price(),
			'price'              => $product->get_price(),
			'catalog_visibility' => $product->get_catalog_visibility(),
			'post_status'        => get_post_status( $product->get_id() ),
		);
	}

	/** Actions that actually write to a product. */
	public static function writable_actions() {
		return array( PCI_Classifier::UPDATE, PCI_Classifier::HIDE, PCI_Classifier::REMOVE );
	}

	/**
	 * Items still to write for this run.
	 *
	 * Resumability lives here: an item is "done" once it has a journal row for
	 * this run, so a chunk that dies partway can simply be continued. Without
	 * this a re-run would write every product a second time and — far worse —
	 * overwrite the before-snapshot with already-applied values, quietly
	 * destroying the ability to roll back.
	 */
	public static function pending_items( $run_id, $limit = 0 ) {
		global $wpdb;
		$i = PCI_Schema::table( 'items' );
		$j = PCI_Schema::table( 'journal' );

		$actions = self::writable_actions();
		$in      = implode( ',', array_fill( 0, count( $actions ), '%s' ) );

		$args = array_merge( array( (int) $run_id, (int) $run_id ), $actions );
		$sql  = "SELECT i.* FROM {$i} i
		         LEFT JOIN {$j} j ON j.run_id = %d AND j.product_id = i.product_id
		         WHERE i.run_id = %d AND i.action IN ({$in})
		           AND i.product_id IS NOT NULL AND j.id IS NULL
		         ORDER BY i.id ASC";

		if ( $limit > 0 ) {
			$sql   .= ' LIMIT %d';
			$args[] = (int) $limit;
		}

		return $wpdb->get_results( $wpdb->prepare( $sql, $args ) );
	}

	/** @return array{total:int,done:int,pending:int} */
	public static function progress( $run_id ) {
		global $wpdb;
		$i = PCI_Schema::table( 'items' );
		$j = PCI_Schema::table( 'journal' );

		$actions = self::writable_actions();
		$in      = implode( ',', array_fill( 0, count( $actions ), '%s' ) );

		$total = (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$i} WHERE run_id = %d AND action IN ({$in}) AND product_id IS NOT NULL",
			array_merge( array( (int) $run_id ), $actions )
		) );

		$done = (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$j} WHERE run_id = %d",
			(int) $run_id
		) );

		return array(
			'total'   => $total,
			'done'    => $done,
			'pending' => max( 0, $total - $done ),
		);
	}

	/**
	 * Write one chunk. Safe to call repeatedly; safe to interrupt.
	 *
	 * @return array{applied:int,skipped:int,errors:array,total:int,done:int,pending:int,finished:bool}
	 */
	public static function apply_chunk( $run_id, $limit = 100, $override_threshold = false ) {
		global $wpdb;

		$run = PCI_Run::get( $run_id );
		if ( ! $run ) {
			return self::chunk_error( $run_id, __( 'That batch no longer exists.', 'pci' ) );
		}

		if ( ! in_array( $run->status, array( 'parsed', 'applying' ), true ) ) {
			return self::chunk_error( $run_id, sprintf(
				__( 'This batch is marked "%s", so it cannot be applied.', 'pci' ),
				$run->status
			) );
		}

		// Threshold is only checked before the first write.
		$progress = self::progress( $run_id );
		if ( 0 === $progress['done'] ) {
			$check = PCI_Run::safety_check( $run_id );
			if ( $check['blocked'] && ! $override_threshold ) {
				return self::chunk_error( $run_id, $check['message'] );
			}
			PCI_Run::set_status( $run_id, 'applying' );
		}

		$items   = self::pending_items( $run_id, $limit );
		$journal = PCI_Schema::table( 'journal' );
		$applied = 0;
		$skipped = 0;
		$errors  = array();

		foreach ( $items as $item ) {
			$product = wc_get_product( (int) $item->product_id );
			if ( ! $product ) {
				$skipped++;
				$errors[] = sprintf( __( 'SKU %1$s: product %2$d could not be loaded.', 'pci' ), $item->sku, $item->product_id );
				continue;
			}

			$before = self::snapshot( $product );

			try {
				$after = self::apply_one( $product, $item );
			} catch ( Exception $e ) {
				$skipped++;
				$errors[] = sprintf( __( 'SKU %1$s: %2$s', 'pci' ), $item->sku, $e->getMessage() );
				continue;
			}

			$wpdb->insert( $journal, array(
				'run_id'     => (int) $run_id,
				'product_id' => (int) $item->product_id,
				'sku'        => $item->sku,
				'action'     => $item->action,
				'snapshot'   => wp_json_encode( $before ),
				'applied'    => wp_json_encode( $after ),
				'created_at' => current_time( 'mysql' ),
			) );

			$applied++;
		}

		$progress = self::progress( $run_id );
		$finished = ( 0 === $progress['pending'] );

		if ( $finished ) {
			PCI_Run::set_status( $run_id, 'applied', array(
				'applied_at' => current_time( 'mysql' ),
				'applied_by' => get_current_user_id(),
			) );
			if ( function_exists( 'wc_delete_product_transients' ) ) {
				wc_delete_product_transients();
			}
		}

		return array(
			'applied'  => $applied,
			'skipped'  => $skipped,
			'errors'   => $errors,
			'total'    => $progress['total'],
			'done'     => $progress['done'],
			'pending'  => $progress['pending'],
			'finished' => $finished,
		);
	}

	private static function chunk_error( $run_id, $message ) {
		$p = self::progress( $run_id );
		return array(
			'applied'  => 0,
			'skipped'  => 0,
			'errors'   => array( $message ),
			'total'    => $p['total'],
			'done'     => $p['done'],
			'pending'  => $p['pending'],
			'finished' => false,
		);
	}

	/**
	 * Apply everything in one request.
	 *
	 * Kept for small batches and CLI use. The admin screen drives apply_chunk()
	 * from the browser instead, because a full run here is thousands of
	 * product saves and will outlive any sane max_execution_time.
	 *
	 * @return array{applied:int,skipped:int,errors:array}
	 */
	public static function apply_run( $run_id, $override_threshold = false ) {
		global $wpdb;

		$run = PCI_Run::get( $run_id );
		if ( ! $run ) {
			return array( 'applied' => 0, 'skipped' => 0, 'errors' => array( __( 'That batch no longer exists.', 'pci' ) ) );
		}

		if ( 'parsed' !== $run->status ) {
			return array(
				'applied' => 0,
				'skipped' => 0,
				'errors'  => array( sprintf( __( 'This batch is already marked "%s", so it cannot be applied again.', 'pci' ), $run->status ) ),
			);
		}

		$check = PCI_Run::safety_check( $run_id );
		if ( $check['blocked'] && ! $override_threshold ) {
			return array( 'applied' => 0, 'skipped' => 0, 'errors' => array( $check['message'] ) );
		}

		$applied = 0;
		$skipped = 0;
		$errors  = array();

		do {
			$res      = self::apply_chunk( $run_id, 200, $override_threshold );
			$applied += $res['applied'];
			$skipped += $res['skipped'];
			$errors   = array_merge( $errors, $res['errors'] );

			if ( ! empty( $res['errors'] ) && 0 === $res['applied'] ) {
				break;
			}
		} while ( ! $res['finished'] && $res['pending'] > 0 );

		return array( 'applied' => $applied, 'skipped' => $skipped, 'errors' => $errors );
	}

	private static function apply_one( WC_Product $product, $item ) {
		$action = $item->action;
		$qty    = (int) $item->file_qty;

		if ( PCI_Classifier::REMOVE === $action ) {
			// Trash, never force-delete. Recoverable by design, and rollback
			// restores it from here.
			wp_trash_post( $product->get_id() );
			return array( 'trashed' => true );
		}

		$product->set_manage_stock( true );

		if ( PCI_Classifier::HIDE === $action ) {
			$product->set_stock_quantity( 0 );
			$product->set_stock_status( 'outofstock' );

			$mode = PCI_Run::hide_mode();
			if ( 'exclude' === $mode ) {
				$vis = $product->get_catalog_visibility();
				$product->set_catalog_visibility( 'search' === $vis ? 'hidden' : 'hidden' );
			}

			$product->save();

			if ( 'draft' === $mode ) {
				wp_update_post( array( 'ID' => $product->get_id(), 'post_status' => 'draft' ) );
			}

			return array( 'stock_quantity' => 0, 'stock_status' => 'outofstock', 'hide_mode' => $mode );
		}

		// Update.
		$product->set_stock_quantity( $qty );
		$product->set_stock_status( $qty > 0 ? 'instock' : 'outofstock' );

		$after = array(
			'stock_quantity' => $qty,
			'stock_status'   => $qty > 0 ? 'instock' : 'outofstock',
		);

		// Prices are off by default. Writing _price directly is what wipes an
		// active sale price, so only the regular price is ever touched and
		// WooCommerce recalculates the effective price itself.
		if ( PCI_Run::write_prices() && null !== $item->file_price ) {
			$product->set_regular_price( (string) $item->file_price );
			$after['regular_price'] = (string) $item->file_price;
		}

		$product->save();

		return $after;
	}

	/**
	 * Undo an applied run.
	 *
	 * Replays the journal newest-first, restoring each snapshot through CRUD.
	 * Safe to run twice: rows already restored are skipped.
	 *
	 * @return array{restored:int,skipped:int,errors:array}
	 */
	public static function rollback_run( $run_id ) {
		global $wpdb;

		$run = PCI_Run::get( $run_id );
		if ( ! $run ) {
			return array( 'restored' => 0, 'skipped' => 0, 'errors' => array( __( 'That batch no longer exists.', 'pci' ) ) );
		}

		// 'applying' is a run that died partway. Its journal holds exactly the
		// products that were written, so rolling it back is both valid and
		// exactly what you want after a timeout.
		if ( ! in_array( $run->status, array( 'applied', 'applying' ), true ) ) {
			return array(
				'restored' => 0,
				'skipped'  => 0,
				'errors'   => array( sprintf( __( 'Only an applied or part-applied batch can be rolled back. This one is "%s".', 'pci' ), $run->status ) ),
			);
		}

		$journal = PCI_Schema::table( 'journal' );
		$rows    = $wpdb->get_results(
			$wpdb->prepare( "SELECT * FROM {$journal} WHERE run_id = %d AND restored = 0 ORDER BY id DESC", (int) $run_id )
		);

		$restored = 0;
		$skipped  = 0;
		$errors   = array();

		foreach ( $rows as $row ) {
			$snap = json_decode( (string) $row->snapshot, true );
			if ( ! is_array( $snap ) ) {
				$skipped++;
				continue;
			}

			// A trashed product has to come back before CRUD can load it.
			if ( 'trash' === get_post_status( $row->product_id ) ) {
				wp_untrash_post( $row->product_id );
				if ( ! empty( $snap['post_status'] ) ) {
					wp_update_post( array( 'ID' => (int) $row->product_id, 'post_status' => $snap['post_status'] ) );
				}
			}

			$product = wc_get_product( (int) $row->product_id );
			if ( ! $product ) {
				$skipped++;
				$errors[] = sprintf( __( 'SKU %1$s: product %2$d could not be restored.', 'pci' ), $row->sku, $row->product_id );
				continue;
			}

			try {
				$product->set_manage_stock( ! empty( $snap['manage_stock'] ) );
				$product->set_stock_quantity( array_key_exists( 'stock_quantity', $snap ) ? $snap['stock_quantity'] : null );

				if ( ! empty( $snap['stock_status'] ) ) {
					$product->set_stock_status( $snap['stock_status'] );
				}
				if ( ! empty( $snap['backorders'] ) ) {
					$product->set_backorders( $snap['backorders'] );
				}
				if ( ! empty( $snap['catalog_visibility'] ) ) {
					$product->set_catalog_visibility( $snap['catalog_visibility'] );
				}

				// Only restore prices if this run actually changed them.
				$applied = json_decode( (string) $row->applied, true );
				if ( is_array( $applied ) && array_key_exists( 'regular_price', $applied ) ) {
					$product->set_regular_price( (string) $snap['regular_price'] );
					$product->set_sale_price( (string) $snap['sale_price'] );
				}

				$product->save();

				if ( ! empty( $snap['post_status'] ) && get_post_status( $row->product_id ) !== $snap['post_status'] ) {
					wp_update_post( array( 'ID' => (int) $row->product_id, 'post_status' => $snap['post_status'] ) );
				}
			} catch ( Exception $e ) {
				$skipped++;
				$errors[] = sprintf( __( 'SKU %1$s: %2$s', 'pci' ), $row->sku, $e->getMessage() );
				continue;
			}

			$wpdb->update( $journal, array( 'restored' => 1 ), array( 'id' => (int) $row->id ) );
			$restored++;
		}

		PCI_Run::set_status(
			$run_id,
			'rolled_back',
			array(
				'rolled_back_at' => current_time( 'mysql' ),
				'rolled_back_by' => get_current_user_id(),
			)
		);

		if ( function_exists( 'wc_delete_product_transients' ) ) {
			wc_delete_product_transients();
		}

		return array( 'restored' => $restored, 'skipped' => $skipped, 'errors' => $errors );
	}
}
