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

	/**
	 * Apply every actionable item in a run.
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

		$actions = array( PCI_Classifier::UPDATE, PCI_Classifier::HIDE, PCI_Classifier::REMOVE );
		$applied = 0;
		$skipped = 0;
		$errors  = array();
		$offset  = 0;
		$batch   = 200;
		$journal = PCI_Schema::table( 'journal' );

		while ( true ) {
			$items = PCI_Run::items( $run_id, $actions, $batch, $offset );
			if ( empty( $items ) ) {
				break;
			}

			foreach ( $items as $item ) {
				if ( empty( $item->product_id ) ) {
					$skipped++;
					continue;
				}

				$product = wc_get_product( (int) $item->product_id );
				if ( ! $product ) {
					$skipped++;
					$errors[] = sprintf( __( 'SKU %s: product %d could not be loaded.', 'pci' ), $item->sku, $item->product_id );
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

				$wpdb->insert(
					$journal,
					array(
						'run_id'     => (int) $run_id,
						'product_id' => (int) $item->product_id,
						'sku'        => $item->sku,
						'action'     => $item->action,
						'snapshot'   => wp_json_encode( $before ),
						'applied'    => wp_json_encode( $after ),
						'created_at' => current_time( 'mysql' ),
					)
				);

				$applied++;
			}

			$offset += $batch;
		}

		PCI_Run::set_status(
			$run_id,
			'applied',
			array(
				'applied_at' => current_time( 'mysql' ),
				'applied_by' => get_current_user_id(),
			)
		);

		if ( function_exists( 'wc_delete_product_transients' ) ) {
			wc_delete_product_transients();
		}

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

		if ( 'applied' !== $run->status ) {
			return array(
				'restored' => 0,
				'skipped'  => 0,
				'errors'   => array( sprintf( __( 'Only an applied batch can be rolled back. This one is "%s".', 'pci' ), $run->status ) ),
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
