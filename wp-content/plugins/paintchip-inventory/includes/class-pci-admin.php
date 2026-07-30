<?php
defined( 'ABSPATH' ) || exit;

class PCI_Admin {

	private static $instance = null;
	const SLUG = 'pci-inventory';

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function hooks() {
		add_action( 'admin_menu', array( $this, 'menu' ) );
		add_action( 'admin_post_pci_upload', array( $this, 'handle_upload' ) );
		add_action( 'admin_post_pci_apply', array( $this, 'handle_apply' ) );
		add_action( 'admin_post_pci_rollback', array( $this, 'handle_rollback' ) );
		add_action( 'admin_post_pci_settings', array( $this, 'handle_settings' ) );
		add_action( 'admin_post_pci_export', array( $this, 'handle_export' ) );
		add_action( 'wp_ajax_pci_scrape', array( $this, 'ajax_scrape' ) );
		add_action( 'admin_head', array( $this, 'styles' ) );
	}

	public function menu() {
		add_menu_page(
			__( 'Inventory Sync', 'pci' ),
			__( 'Inventory Sync', 'pci' ),
			PCI_CAP,
			self::SLUG,
			array( $this, 'route' ),
			'dashicons-clipboard',
			56
		);

		add_submenu_page( self::SLUG, __( 'Batches', 'pci' ), __( 'Batches', 'pci' ), PCI_CAP, self::SLUG, array( $this, 'route' ) );
		add_submenu_page( self::SLUG, __( 'Suppliers', 'pci' ), __( 'Suppliers', 'pci' ), PCI_CAP, self::SLUG . '-suppliers', array( $this, 'route_suppliers' ) );
		add_submenu_page( self::SLUG, __( 'Settings', 'pci' ), __( 'Settings', 'pci' ), PCI_CAP, self::SLUG . '-settings', array( $this, 'route_settings' ) );
	}

	public function styles() {
		$screen = get_current_screen();
		if ( ! $screen || false === strpos( (string) $screen->id, self::SLUG ) ) {
			return;
		}
		?>
		<style>
			.pci-ledger { border-collapse: collapse; margin: 1em 0; max-width: 900px; }
			.pci-ledger th, .pci-ledger td { padding: 8px 14px; border-bottom: 1px solid #dcdcde; text-align: left; }
			.pci-ledger th { background: #f6f7f7; font-weight: 600; }
			.pci-ledger td.num { text-align: right; font-variant-numeric: tabular-nums; font-weight: 600; }
			.pci-ledger tr.act-update  td:first-child { border-left: 4px solid #00a32a; }
			.pci-ledger tr.act-hide    td:first-child { border-left: 4px solid #dba617; }
			.pci-ledger tr.act-remove  td:first-child { border-left: 4px solid #d63638; }
			.pci-ledger tr.act-new     td:first-child { border-left: 4px solid #2271b1; }
			.pci-ledger tr.act-flag    td:first-child { border-left: 4px solid #8c8f94; }
			.pci-ledger tr.act-ignore  td:first-child { border-left: 4px solid #f0f0f1; }
			.pci-danger { border-left: 4px solid #d63638; background: #fcf0f1; padding: 12px 16px; margin: 16px 0; }
			.pci-note { background: #f6f7f7; border-left: 4px solid #72aee6; padding: 12px 16px; margin: 16px 0; }
			.pci-section { margin-top: 2.5em; }
			.pci-muted { color: #646970; }
			details.pci-list { margin: 10px 0; }
			details.pci-list > summary { cursor: pointer; font-weight: 600; padding: 6px 0; }
			.pci-scroll { max-height: 420px; overflow-y: auto; border: 1px solid #dcdcde; }
		</style>
		<?php
	}

	private function url( $args = array() ) {
		return add_query_arg( array_merge( array( 'page' => self::SLUG ), $args ), admin_url( 'admin.php' ) );
	}

	private function notices() {
		if ( isset( $_GET['pci_msg'] ) ) {
			$class = ( isset( $_GET['pci_type'] ) && 'error' === $_GET['pci_type'] ) ? 'notice-error' : 'notice-success';
			echo '<div class="notice ' . esc_attr( $class ) . ' is-dismissible"><p>' .
				esc_html( wp_unslash( $_GET['pci_msg'] ) ) . '</p></div>';
		}
	}

	private function redirect( $args, $msg, $type = 'success' ) {
		wp_safe_redirect( $this->url( array_merge( $args, array( 'pci_msg' => $msg, 'pci_type' => $type ) ) ) );
		exit;
	}

	// ---------------------------------------------------------------- routing

	public function route() {
		if ( ! current_user_can( PCI_CAP ) ) {
			wp_die( esc_html__( 'You do not have permission to manage inventory batches.', 'pci' ) );
		}

		$view   = isset( $_GET['view'] ) ? sanitize_key( $_GET['view'] ) : 'list';
		$run_id = isset( $_GET['run'] ) ? (int) $_GET['run'] : 0;

		echo '<div class="wrap">';
		$this->notices();

		if ( 'preview' === $view && $run_id ) {
			$this->screen_preview( $run_id );
		} elseif ( 'scrape' === $view && $run_id ) {
			$this->screen_scrape( $run_id );
		} else {
			$this->screen_list();
		}

		echo '</div>';
	}

	// ------------------------------------------------------------ list screen

	private function screen_list() {
		$runs = PCI_Run::recent( 25 );
		?>
		<h1><?php esc_html_e( 'Inventory Sync', 'pci' ); ?></h1>

		<div class="pci-note">
			<p><strong><?php esc_html_e( 'Nothing is written to the store until you approve it.', 'pci' ); ?></strong>
			<?php esc_html_e( 'Upload the POS report, read the summary of what would change, then apply. Every applied batch can be rolled back.', 'pci' ); ?></p>
		</div>

		<h2><?php esc_html_e( 'Upload a report', 'pci' ); ?></h2>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data">
			<?php wp_nonce_field( 'pci_upload' ); ?>
			<input type="hidden" name="action" value="pci_upload">
			<p>
				<input type="file" name="pci_file" accept=".txt,.text,.prn,.rpt" required>
				<button type="submit" class="button button-primary"><?php esc_html_e( 'Upload and analyse', 'pci' ); ?></button>
			</p>
			<p class="pci-muted"><?php esc_html_e( 'Expects the fixed-width "General Inventory Full Master List" text export.', 'pci' ); ?></p>
		</form>

		<div class="pci-section">
		<h2><?php esc_html_e( 'Batches', 'pci' ); ?></h2>
		<?php if ( empty( $runs ) ) : ?>
			<p><?php esc_html_e( 'No batches yet. Upload a report above to get started.', 'pci' ); ?></p>
		<?php else : ?>
			<table class="widefat striped">
				<thead><tr>
					<th><?php esc_html_e( 'Batch', 'pci' ); ?></th>
					<th><?php esc_html_e( 'Report date', 'pci' ); ?></th>
					<th><?php esc_html_e( 'Uploaded', 'pci' ); ?></th>
					<th><?php esc_html_e( 'Status', 'pci' ); ?></th>
					<th><?php esc_html_e( 'Update', 'pci' ); ?></th>
					<th><?php esc_html_e( 'Hide', 'pci' ); ?></th>
					<th><?php esc_html_e( 'Remove', 'pci' ); ?></th>
					<th><?php esc_html_e( 'New', 'pci' ); ?></th>
					<th></th>
				</tr></thead>
				<tbody>
				<?php foreach ( $runs as $run ) :
					$stats  = json_decode( (string) $run->stats, true );
					$counts = isset( $stats['counts'] ) ? $stats['counts'] : array();
					?>
					<tr>
						<td><strong>#<?php echo (int) $run->id; ?></strong><br>
							<span class="pci-muted"><?php echo esc_html( $run->filename ); ?></span></td>
						<td><?php echo esc_html( $run->report_date ? $run->report_date : '—' ); ?></td>
						<td><?php echo esc_html( $run->created_at ); ?></td>
						<td><?php echo esc_html( $this->status_label( $run ) ); ?></td>
						<td><?php echo isset( $counts['update'] ) ? (int) $counts['update'] : 0; ?></td>
						<td><?php echo isset( $counts['hide'] ) ? (int) $counts['hide'] : 0; ?></td>
						<td><?php echo isset( $counts['remove'] ) ? (int) $counts['remove'] : 0; ?></td>
						<td><?php echo isset( $counts['new'] ) ? (int) $counts['new'] : 0; ?></td>
						<td>
							<a class="button" href="<?php echo esc_url( $this->url( array( 'view' => 'preview', 'run' => $run->id ) ) ); ?>">
								<?php echo 'parsed' === $run->status ? esc_html__( 'Review', 'pci' ) : esc_html__( 'View', 'pci' ); ?>
							</a>
							<?php if ( 'applied' === $run->status ) : ?>
								<a class="button" href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'action' => 'pci_rollback', 'run' => $run->id ), admin_url( 'admin-post.php' ) ), 'pci_rollback_' . $run->id ) ); ?>"
								   onclick="return confirm('<?php echo esc_js( __( 'Roll this batch back? Every product it touched returns to the values it had before.', 'pci' ) ); ?>');">
									<?php esc_html_e( 'Roll back', 'pci' ); ?>
								</a>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>
		</div>
		<?php
	}

	private function status_label( $run ) {
		switch ( $run->status ) {
			case 'applied':
				return sprintf( __( 'Applied %s', 'pci' ), $run->applied_at );
			case 'rolled_back':
				return sprintf( __( 'Rolled back %s', 'pci' ), $run->rolled_back_at );
			default:
				return __( 'Awaiting review', 'pci' );
		}
	}

	// --------------------------------------------------------- preview screen

	private function screen_preview( $run_id ) {
		$run = PCI_Run::get( $run_id );
		if ( ! $run ) {
			echo '<h1>' . esc_html__( 'Batch not found', 'pci' ) . '</h1>';
			return;
		}

		$counts = PCI_Run::counts( $run_id );
		$stats  = PCI_Run::stats( $run_id );
		$check  = PCI_Run::safety_check( $run_id );
		$live   = isset( $stats['live_products'] ) ? (int) $stats['live_products'] : 0;
		?>
		<h1><?php printf( esc_html__( 'Batch #%d', 'pci' ), (int) $run->id ); ?>
			<span class="pci-muted" style="font-size:13px;font-weight:400;">
				<?php echo esc_html( $run->filename ); ?>
				<?php if ( $run->report_date ) : ?> · <?php printf( esc_html__( 'report dated %s', 'pci' ), esc_html( $run->report_date ) ); ?><?php endif; ?>
			</span>
		</h1>

		<p><a href="<?php echo esc_url( $this->url() ); ?>">&larr; <?php esc_html_e( 'All batches', 'pci' ); ?></a></p>

		<?php if ( 'parsed' === $run->status ) : ?>
			<h2><?php esc_html_e( 'What will happen', 'pci' ); ?></h2>
		<?php else : ?>
			<h2><?php esc_html_e( 'What happened', 'pci' ); ?></h2>
			<p class="pci-muted"><?php echo esc_html( $this->status_label( $run ) ); ?></p>
		<?php endif; ?>

		<table class="pci-ledger">
			<thead><tr>
				<th><?php esc_html_e( 'Action', 'pci' ); ?></th>
				<th style="text-align:right;"><?php esc_html_e( 'SKUs', 'pci' ); ?></th>
				<th><?php esc_html_e( 'Effect', 'pci' ); ?></th>
			</tr></thead>
			<tbody>
				<?php
				$rows = array(
					array( PCI_Classifier::UPDATE, 'act-update', __( 'Stock quantity set to the report figure. Stock management is switched on where it was off.', 'pci' ) ),
					array( PCI_Classifier::HIDE, 'act-hide', $this->hide_effect_text() ),
					array( PCI_Classifier::REMOVE, 'act-remove', __( 'Moved to Trash. Recoverable from Products → Trash, and by rolling this batch back.', 'pci' ) ),
					array( PCI_Classifier::NEW_P, 'act-new', __( 'Nothing yet. Needs supplier data and an image before a product can exist.', 'pci' ) ),
				);
				foreach ( $rows as $r ) :
					?>
					<tr class="<?php echo esc_attr( $r[1] ); ?>">
						<td><strong><?php echo esc_html( PCI_Classifier::label( $r[0] ) ); ?></strong></td>
						<td class="num"><?php echo (int) $counts[ $r[0] ]; ?></td>
						<td class="pci-muted"><?php echo esc_html( $r[2] ); ?></td>
					</tr>
				<?php endforeach; ?>

				<?php
				$flag_total = 0;
				foreach ( $counts as $action => $n ) {
					if ( PCI_Classifier::is_flag( $action ) ) {
						$flag_total += $n;
					}
				}
				?>
				<tr class="act-flag">
					<td><strong><?php esc_html_e( 'Flagged for a human', 'pci' ); ?></strong></td>
					<td class="num"><?php echo (int) $flag_total; ?></td>
					<td class="pci-muted"><?php esc_html_e( 'Nothing is written. Listed in full below.', 'pci' ); ?></td>
				</tr>
				<tr class="act-ignore">
					<td><?php esc_html_e( 'Legacy rows, skipped', 'pci' ); ?></td>
					<td class="num"><?php echo (int) $counts[ PCI_Classifier::IGNORE ]; ?></td>
					<td class="pci-muted"><?php esc_html_e( 'In the report, not on the website, no stock. Left alone.', 'pci' ); ?></td>
				</tr>
			</tbody>
		</table>

		<p class="pci-muted">
			<?php
			printf(
				esc_html__( 'The store currently has %1$d published products. This batch touches %2$d of them.', 'pci' ),
				$live,
				(int) $counts[ PCI_Classifier::UPDATE ] + (int) $counts[ PCI_Classifier::HIDE ] + (int) $counts[ PCI_Classifier::REMOVE ]
			);
			?>
		</p>

		<?php if ( $check['blocked'] ) : ?>
			<div class="pci-danger">
				<p><strong><?php esc_html_e( 'Safety limit exceeded', 'pci' ); ?></strong></p>
				<p><?php echo esc_html( $check['message'] ); ?></p>
			</div>
		<?php endif; ?>

		<?php
		$this->fingerprint_section( $run_id );
		$this->removals_section( $run_id, $counts );
		$this->flags_section( $run_id, $counts );
		$this->updates_section( $run_id, $counts );
		$this->new_section( $run_id, $counts );

		if ( 'parsed' === $run->status ) {
			$this->apply_form( $run, $check );
		} elseif ( 'applied' === $run->status ) {
			$this->rollback_form( $run );
		}
	}

	private function hide_effect_text() {
		switch ( PCI_Run::hide_mode() ) {
			case 'exclude':
				return __( 'Stock set to 0, marked out of stock, and hidden from the catalog and search.', 'pci' );
			case 'draft':
				return __( 'Stock set to 0, marked out of stock, and switched to Draft so it leaves the storefront.', 'pci' );
			default:
				return __( 'Stock set to 0 and marked out of stock. Still visible in the catalog.', 'pci' );
		}
	}

	/**
	 * Evidence, not conclusions.
	 *
	 * The action counts above are the output of a rule set. This section shows
	 * the raw column behaviour those rules were inferred from, so a change in
	 * the client's convention is visible before anyone approves a batch.
	 */
	private function fingerprint_section( $run_id ) {
		$stats   = PCI_Run::stats( $run_id );
		$profile = isset( $stats['profile'] ) ? $stats['profile'] : array();

		if ( empty( $profile ) ) {
			return;
		}

		$prev_id     = PCI_Signals::previous_run_id( $run_id );
		$changes     = array();
		$transitions = null;

		if ( $prev_id ) {
			$prev_stats = PCI_Run::stats( $prev_id );
			if ( ! empty( $prev_stats['profile'] ) ) {
				$changes = PCI_Signals::compare( $prev_stats['profile'], $profile );
			}
			$transitions = PCI_Signals::transitions( $run_id, $prev_id );
		}
		?>
		<div class="pci-section">
			<h2><?php esc_html_e( 'What the report itself says', 'pci' ); ?></h2>
			<p>
				<?php esc_html_e( 'The actions above come from a rule about Max and quantity. That rule is an inference, not something the report states. Below is what each column actually contains, so you can check the inference still holds.', 'pci' ); ?>
			</p>

			<?php if ( ! empty( $changes ) ) : ?>
				<?php foreach ( $changes as $c ) : ?>
					<div class="<?php echo 'high' === $c['severity'] ? 'pci-danger' : 'pci-note'; ?>">
						<p>
							<strong>
								<?php
								echo 'high' === $c['severity']
									? esc_html__( 'Possible convention change', 'pci' )
									: esc_html__( 'Column changed', 'pci' );
								?>
							</strong><br>
							<?php echo esc_html( $c['message'] ); ?>
						</p>
					</div>
				<?php endforeach; ?>
			<?php elseif ( $prev_id ) : ?>
				<p class="pci-muted">
					<?php printf( esc_html__( 'No column behaviour changed since batch #%d.', 'pci' ), (int) $prev_id ); ?>
				</p>
			<?php endif; ?>

			<?php if ( $transitions ) : ?>
				<h3><?php printf( esc_html__( 'How SKUs moved since batch #%d', 'pci' ), (int) $prev_id ); ?></h3>
				<table class="pci-ledger">
					<tbody>
						<tr>
							<td><?php esc_html_e( 'Still listed, but Max dropped to 0', 'pci' ); ?></td>
							<td class="num"><?php echo (int) $transitions['still_listed_max_zeroed']; ?></td>
							<td class="pci-muted"><?php esc_html_e( 'Consistent with flagging a line as discontinued in place.', 'pci' ); ?></td>
						</tr>
						<tr>
							<td><?php esc_html_e( 'Removed from the report entirely', 'pci' ); ?></td>
							<td class="num"><?php echo (int) $transitions['dropped_from_report']; ?></td>
							<td class="pci-muted"><?php esc_html_e( 'Consistent with deleting the record instead of flagging it.', 'pci' ); ?></td>
						</tr>
						<tr>
							<td><?php esc_html_e( 'New SKUs in this report', 'pci' ); ?></td>
							<td class="num"><?php echo (int) $transitions['appeared']; ?></td>
							<td></td>
						</tr>
						<tr>
							<td><?php esc_html_e( 'Sold down to zero', 'pci' ); ?></td>
							<td class="num"><?php echo (int) $transitions['went_to_zero_qty']; ?></td>
							<td></td>
						</tr>
						<tr>
							<td><?php esc_html_e( 'Restocked from zero', 'pci' ); ?></td>
							<td class="num"><?php echo (int) $transitions['restocked']; ?></td>
							<td></td>
						</tr>
					</tbody>
				</table>
				<?php
				$zeroed  = (int) $transitions['still_listed_max_zeroed'];
				$dropped = (int) $transitions['dropped_from_report'];
				if ( 0 === $zeroed && $dropped > 20 ) :
					?>
					<div class="pci-danger">
						<p><strong><?php esc_html_e( 'The Max rule may be looking for a signal that is not there', 'pci' ); ?></strong><br>
						<?php
						printf(
							esc_html__( 'No SKU had its Max zeroed, but %d disappeared from the export. That pattern means the POS deletes discontinued records rather than flagging them, so "Max = 0 and quantity = 0" will almost never fire. Those %d SKUs are listed as untouched, not as removals.', 'pci' ),
							$dropped,
							$dropped
						);
						?>
						</p>
					</div>
				<?php endif; ?>
			<?php endif; ?>

			<details class="pci-list">
				<summary><?php esc_html_e( 'Column-by-column detail', 'pci' ); ?></summary>
				<table class="widefat striped" style="max-width:1000px;">
					<thead><tr>
						<th><?php esc_html_e( 'Column', 'pci' ); ?></th>
						<th><?php esc_html_e( 'Filled in', 'pci' ); ?></th>
						<th><?php esc_html_e( 'Distinct', 'pci' ); ?></th>
						<th><?php esc_html_e( 'Carries signal?', 'pci' ); ?></th>
						<th><?php esc_html_e( 'Values', 'pci' ); ?></th>
					</tr></thead>
					<tbody>
					<?php foreach ( $profile as $col => $p ) : ?>
						<tr>
							<td><strong><?php echo esc_html( PCI_Signals::label( $col ) ); ?></strong></td>
							<td><?php echo (int) $p['populated']; ?> / <?php echo (int) $p['total']; ?></td>
							<td><?php echo (int) $p['distinct']; ?></td>
							<td>
								<?php if ( ! empty( $p['no_signal'] ) ) : ?>
									<span class="pci-muted"><?php esc_html_e( 'no — effectively constant', 'pci' ); ?></span>
								<?php else : ?>
									<span style="color:#00a32a;"><?php esc_html_e( 'yes', 'pci' ); ?></span>
								<?php endif; ?>
							</td>
							<td class="pci-muted">
								<?php
								$bits = array();
								foreach ( $p['values'] as $v => $n ) {
									$bits[] = ( '' === $v ? '(blank)' : $v ) . ' × ' . $n;
								}
								echo esc_html( implode( ' · ', $bits ) );
								echo ! empty( $p['truncated'] ) ? ' …' : '';
								?>
							</td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
				<p class="pci-muted">
					<?php esc_html_e( 'Columns marked "no signal" are the likeliest place a new convention would appear. Nothing currently reads Deleted, Active, Dept, Note, QCo, Multiplier or Fixed — if one of them starts varying, that is probably the new identifier.', 'pci' ); ?>
				</p>
			</details>
		</div>
		<?php
	}

	/** Removals are listed in full, with titles — never behind a toggle. */
	private function removals_section( $run_id, $counts ) {
		$items = PCI_Run::items( $run_id, PCI_Classifier::REMOVE, 1000 );
		?>
		<div class="pci-section">
			<h2><?php printf( esc_html__( 'Products to remove (%d)', 'pci' ), count( $items ) ); ?></h2>
			<?php if ( empty( $items ) ) : ?>
				<p class="pci-muted"><?php esc_html_e( 'None. No product in the report is both out of stock and no longer restocked.', 'pci' ); ?></p>
			<?php else : ?>
				<p><?php esc_html_e( 'These are on the website, but the report shows no stock and no reorder ceiling — the POS has stopped carrying them. Read the titles before approving.', 'pci' ); ?></p>
				<table class="widefat striped" style="max-width:1100px;">
					<thead><tr>
						<th><?php esc_html_e( 'Product title', 'pci' ); ?></th>
						<th><?php esc_html_e( 'SKU', 'pci' ); ?></th>
						<th><?php esc_html_e( 'Supplier', 'pci' ); ?></th>
						<th><?php esc_html_e( 'Report description', 'pci' ); ?></th>
						<th><?php esc_html_e( 'Stock now', 'pci' ); ?></th>
						<th></th>
					</tr></thead>
					<tbody>
					<?php foreach ( $items as $it ) : ?>
						<tr>
							<td><strong><?php echo esc_html( $it->product_title ? $it->product_title : __( '(untitled)', 'pci' ) ); ?></strong></td>
							<td><code><?php echo esc_html( $it->sku ); ?></code></td>
							<td><?php echo esc_html( $it->vend ); ?></td>
							<td class="pci-muted"><?php echo esc_html( $it->description ); ?></td>
							<td><?php echo esc_html( null === $it->cur_qty ? '—' : $it->cur_qty ); ?></td>
							<td>
								<?php if ( $it->product_id ) : ?>
									<a href="<?php echo esc_url( get_edit_post_link( $it->product_id ) ); ?>" target="_blank"><?php esc_html_e( 'Open', 'pci' ); ?></a>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>
		<?php
	}

	private function flags_section( $run_id, $counts ) {
		$flag_actions = array();
		foreach ( $counts as $action => $n ) {
			if ( PCI_Classifier::is_flag( $action ) && $n > 0 ) {
				$flag_actions[] = $action;
			}
		}
		?>
		<div class="pci-section">
			<h2><?php esc_html_e( 'Flagged rows', 'pci' ); ?></h2>
			<?php if ( empty( $flag_actions ) ) : ?>
				<p class="pci-muted"><?php esc_html_e( 'Nothing flagged in this report.', 'pci' ); ?></p>
			<?php else : ?>
				<p>
					<?php esc_html_e( 'These could not be resolved either way. Nothing is written for them.', 'pci' ); ?>
					<a class="button" href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'action' => 'pci_export', 'run' => $run_id ), admin_url( 'admin-post.php' ) ), 'pci_export_' . $run_id ) ); ?>">
						<?php esc_html_e( 'Download all flagged rows (CSV)', 'pci' ); ?>
					</a>
				</p>
				<?php foreach ( $flag_actions as $action ) :
					$items = PCI_Run::items( $run_id, $action, 1000 );
					?>
					<details class="pci-list">
						<summary><?php echo esc_html( PCI_Classifier::label( $action ) ); ?> (<?php echo count( $items ); ?>)</summary>
						<div class="pci-scroll">
						<table class="widefat striped">
							<thead><tr>
								<th><?php esc_html_e( 'SKU', 'pci' ); ?></th>
								<th><?php esc_html_e( 'Supplier', 'pci' ); ?></th>
								<th><?php esc_html_e( 'Description', 'pci' ); ?></th>
								<th><?php esc_html_e( 'Qty', 'pci' ); ?></th>
								<th><?php esc_html_e( 'Why', 'pci' ); ?></th>
							</tr></thead>
							<tbody>
							<?php foreach ( $items as $it ) : ?>
								<tr>
									<td><code><?php echo esc_html( $it->sku ? $it->sku : '—' ); ?></code></td>
									<td><?php echo esc_html( $it->vend ); ?></td>
									<td><?php echo esc_html( $it->description ); ?></td>
									<td><?php echo (int) $it->file_qty; ?></td>
									<td class="pci-muted"><?php echo esc_html( $it->flag_reason ); ?></td>
								</tr>
							<?php endforeach; ?>
							</tbody>
						</table>
						</div>
					</details>
				<?php endforeach; ?>
			<?php endif; ?>
		</div>
		<?php
	}

	private function updates_section( $run_id, $counts ) {
		$items = PCI_Run::items( $run_id, PCI_Classifier::UPDATE, 1000 );
		?>
		<div class="pci-section">
			<h2><?php printf( esc_html__( 'Stock changes (%d)', 'pci' ), (int) $counts[ PCI_Classifier::UPDATE ] ); ?></h2>
			<details class="pci-list">
				<summary><?php esc_html_e( 'Show the first 1,000 stock changes', 'pci' ); ?></summary>
				<div class="pci-scroll">
				<table class="widefat striped">
					<thead><tr>
						<th><?php esc_html_e( 'Product', 'pci' ); ?></th>
						<th><?php esc_html_e( 'SKU', 'pci' ); ?></th>
						<th><?php esc_html_e( 'Stock now', 'pci' ); ?></th>
						<th><?php esc_html_e( 'Stock after', 'pci' ); ?></th>
						<th><?php esc_html_e( 'Managed?', 'pci' ); ?></th>
						<th><?php esc_html_e( 'Note', 'pci' ); ?></th>
					</tr></thead>
					<tbody>
					<?php foreach ( $items as $it ) :
						$changed = ( (string) $it->cur_qty !== (string) $it->file_qty );
						?>
						<tr>
							<td><?php echo esc_html( $it->product_title ); ?></td>
							<td><code><?php echo esc_html( $it->sku ); ?></code></td>
							<td><?php echo esc_html( null === $it->cur_qty ? '—' : $it->cur_qty ); ?></td>
							<td<?php echo $changed ? ' style="font-weight:600;"' : ''; ?>><?php echo (int) $it->file_qty; ?></td>
							<td><?php echo 'no' === $it->cur_manage ? '<span style="color:#d63638;">' . esc_html__( 'will switch on', 'pci' ) . '</span>' : esc_html__( 'yes', 'pci' ); ?></td>
							<td class="pci-muted"><?php echo esc_html( $it->flag_reason ); ?></td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
				</div>
			</details>
		</div>
		<?php
	}

	private function new_section( $run_id, $counts ) {
		global $wpdb;
		$items_t = PCI_Schema::table( 'items' );
		$by_vend = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT vend, COUNT(*) AS n, SUM(file_qty) AS units FROM {$items_t}
				 WHERE run_id = %d AND action = %s GROUP BY vend ORDER BY n DESC",
				(int) $run_id,
				PCI_Classifier::NEW_P
			)
		);
		$registry  = PCI_Scraper_Registry::instance();
		?>
		<div class="pci-section">
			<h2><?php printf( esc_html__( 'New products to add (%d)', 'pci' ), (int) $counts[ PCI_Classifier::NEW_P ] ); ?></h2>
			<p><?php esc_html_e( 'In stock and actively restocked, but not on the website. Each needs a title, description, UPC, price and image before it can be created.', 'pci' ); ?></p>

			<?php if ( ! empty( $by_vend ) ) : ?>
				<table class="pci-ledger">
					<thead><tr>
						<th><?php esc_html_e( 'Supplier', 'pci' ); ?></th>
						<th style="text-align:right;"><?php esc_html_e( 'SKUs', 'pci' ); ?></th>
						<th style="text-align:right;"><?php esc_html_e( 'Units', 'pci' ); ?></th>
						<th><?php esc_html_e( 'Sourcing', 'pci' ); ?></th>
					</tr></thead>
					<tbody>
					<?php foreach ( $by_vend as $v ) :
						$scraper = $registry->for_vend( $v->vend );
						?>
						<tr class="act-new">
							<td><strong><?php echo esc_html( $v->vend ); ?></strong>
								<?php if ( $scraper ) : ?><span class="pci-muted"> · <?php echo esc_html( $scraper->name() ); ?></span><?php endif; ?>
							</td>
							<td class="num"><?php echo (int) $v->n; ?></td>
							<td class="num"><?php echo (int) $v->units; ?></td>
							<td>
								<?php if ( $scraper ) : ?>
									<span style="color:#00a32a;">&#10003; <?php esc_html_e( 'Adapter ready', 'pci' ); ?></span>
								<?php else : ?>
									<span class="pci-muted"><?php esc_html_e( 'No adapter yet — needs a catalog source', 'pci' ); ?></span>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>

				<p>
					<a class="button button-secondary" href="<?php echo esc_url( $this->url( array( 'view' => 'scrape', 'run' => $run_id ) ) ); ?>">
						<?php esc_html_e( 'Open the sourcing queue', 'pci' ); ?>
					</a>
				</p>
			<?php else : ?>
				<p class="pci-muted"><?php esc_html_e( 'Nothing new to source in this report.', 'pci' ); ?></p>
			<?php endif; ?>
		</div>
		<?php
	}

	private function apply_form( $run, $check ) {
		?>
		<div class="pci-section" style="border-top:1px solid #c3c4c7;padding-top:1.5em;">
			<h2><?php esc_html_e( 'Approve this batch', 'pci' ); ?></h2>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( 'pci_apply_' . $run->id ); ?>
				<input type="hidden" name="action" value="pci_apply">
				<input type="hidden" name="run" value="<?php echo (int) $run->id; ?>">

				<?php if ( $check['blocked'] ) : ?>
					<p><label>
						<input type="checkbox" name="override" value="1" required>
						<?php esc_html_e( 'I have read the lists above and this batch is correct. Apply it despite the safety limit.', 'pci' ); ?>
					</label></p>
				<?php endif; ?>

				<p>
					<button type="submit" class="button button-primary button-large"
						onclick="return confirm('<?php echo esc_js( __( 'Apply this batch now? You can roll it back afterwards from the Batches screen.', 'pci' ) ); ?>');">
						<?php esc_html_e( 'Apply this batch', 'pci' ); ?>
					</button>
				</p>
				<p class="pci-muted"><?php esc_html_e( 'A before-snapshot is saved for every product touched, so this batch can be undone in one click.', 'pci' ); ?></p>
			</form>
		</div>
		<?php
	}

	private function rollback_form( $run ) {
		?>
		<div class="pci-section" style="border-top:1px solid #c3c4c7;padding-top:1.5em;">
			<h2><?php esc_html_e( 'Roll this batch back', 'pci' ); ?></h2>
			<p><?php printf( esc_html__( 'Applied %s. Every product this batch touched can be returned to the stock, status and price it had beforehand.', 'pci' ), esc_html( $run->applied_at ) ); ?></p>
			<p>
				<a class="button button-secondary button-large"
				   href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'action' => 'pci_rollback', 'run' => $run->id ), admin_url( 'admin-post.php' ) ), 'pci_rollback_' . $run->id ) ); ?>"
				   onclick="return confirm('<?php echo esc_js( __( 'Roll this batch back now?', 'pci' ) ); ?>');">
					<?php esc_html_e( 'Roll back this batch', 'pci' ); ?>
				</a>
			</p>
		</div>
		<?php
	}

	// ---------------------------------------------------------- scrape screen

	private function screen_scrape( $run_id ) {
		$items = PCI_Run::items( $run_id, PCI_Classifier::NEW_P, 500 );
		$reg   = PCI_Scraper_Registry::instance();
		?>
		<h1><?php printf( esc_html__( 'Sourcing queue — batch #%d', 'pci' ), (int) $run_id ); ?></h1>
		<p><a href="<?php echo esc_url( $this->url( array( 'view' => 'preview', 'run' => $run_id ) ) ); ?>">&larr; <?php esc_html_e( 'Back to the batch', 'pci' ); ?></a></p>

		<div class="pci-note">
			<p><?php esc_html_e( 'Fetching supplier data does not create products. It parks the title, description, UPC, price and image on the row so you can check them first.', 'pci' ); ?></p>
		</div>

		<table class="widefat striped">
			<thead><tr>
				<th><?php esc_html_e( 'SKU', 'pci' ); ?></th>
				<th><?php esc_html_e( 'Supplier', 'pci' ); ?></th>
				<th><?php esc_html_e( 'Report description', 'pci' ); ?></th>
				<th><?php esc_html_e( 'Qty', 'pci' ); ?></th>
				<th><?php esc_html_e( 'Cost', 'pci' ); ?></th>
				<th><?php esc_html_e( 'Supplier data', 'pci' ); ?></th>
			</tr></thead>
			<tbody>
			<?php foreach ( $items as $it ) :
				$raw     = json_decode( (string) $it->raw, true );
				$scraped = ( is_array( $raw ) && isset( $raw['scraped'] ) ) ? $raw['scraped'] : null;
				$scraper = $reg->for_vend( $it->vend );
				?>
				<tr id="pci-row-<?php echo (int) $it->id; ?>">
					<td><code><?php echo esc_html( $it->sku ); ?></code></td>
					<td><?php echo esc_html( $it->vend ); ?></td>
					<td class="pci-muted"><?php echo esc_html( $it->description ); ?></td>
					<td><?php echo (int) $it->file_qty; ?></td>
					<td><?php echo null === $it->file_cost ? '—' : esc_html( wc_price( $it->file_cost ) ); ?></td>
					<td>
						<?php if ( $scraped ) : ?>
							<strong><?php echo esc_html( $scraped['title'] ); ?></strong><br>
							<span class="pci-muted">
								<?php if ( ! empty( $scraped['upc'] ) ) : ?>UPC <?php echo esc_html( $scraped['upc'] ); ?> · <?php endif; ?>
								<?php if ( ! empty( $scraped['msrp'] ) ) : ?>MSRP $<?php echo esc_html( $scraped['msrp'] ); ?><?php endif; ?>
							</span>
							<?php if ( ! empty( $scraped['image_url'] ) ) : ?>
								<br><a href="<?php echo esc_url( $scraped['image_url'] ); ?>" target="_blank"><?php esc_html_e( 'image', 'pci' ); ?></a>
							<?php endif; ?>
						<?php elseif ( $scraper ) : ?>
							<button class="button pci-fetch" data-item="<?php echo (int) $it->id; ?>"><?php esc_html_e( 'Fetch', 'pci' ); ?></button>
							<span class="pci-result pci-muted"></span>
						<?php else : ?>
							<span class="pci-muted"><?php printf( esc_html__( 'No adapter for %s', 'pci' ), esc_html( $it->vend ) ); ?></span>
						<?php endif; ?>
					</td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>

		<script>
		(function () {
			var nonce = <?php echo wp_json_encode( wp_create_nonce( 'pci_scrape' ) ); ?>;
			document.addEventListener('click', function (e) {
				var btn = e.target.closest('.pci-fetch');
				if (!btn) return;
				e.preventDefault();
				var out = btn.parentNode.querySelector('.pci-result');
				btn.disabled = true;
				out.textContent = <?php echo wp_json_encode( __( 'Fetching…', 'pci' ) ); ?>;

				var body = new FormData();
				body.append('action', 'pci_scrape');
				body.append('item', btn.dataset.item);
				body.append('_ajax_nonce', nonce);

				fetch(ajaxurl, { method: 'POST', body: body, credentials: 'same-origin' })
					.then(function (r) { return r.json(); })
					.then(function (res) {
						if (res.success) {
							var d = res.data;
							out.innerHTML = '<strong>' + d.title + '</strong>';
						} else {
							out.textContent = res.data || 'Failed';
							btn.disabled = false;
						}
					})
					.catch(function () {
						out.textContent = <?php echo wp_json_encode( __( 'Request failed.', 'pci' ) ); ?>;
						btn.disabled = false;
					});
			});
		})();
		</script>
		<?php
	}

	// ------------------------------------------------------- suppliers screen

	public function route_suppliers() {
		if ( ! current_user_can( PCI_CAP ) ) {
			wp_die( esc_html__( 'You do not have permission to view this page.', 'pci' ) );
		}

		$run_id = isset( $_GET['run'] ) ? (int) $_GET['run'] : 0;
		if ( ! $run_id ) {
			$recent = PCI_Run::recent( 1 );
			$run_id = $recent ? (int) $recent[0]->id : 0;
		}

		$codes = $run_id ? PCI_Suppliers::codes_in_run( $run_id ) : array();
		$reg   = PCI_Scraper_Registry::instance();
		$map   = PCI_Suppliers::policy_map();

		echo '<div class="wrap">';
		$this->notices();
		?>
		<h1><?php esc_html_e( 'Suppliers', 'pci' ); ?></h1>

		<div class="pci-note">
			<p><?php esc_html_e( 'The report cannot tell you which supplier relationships are still live. Its Min and Max columns keep showing reorder intent long after a contract ends, so set that here — this page overrides the file.', 'pci' ); ?></p>
		</div>

		<?php if ( empty( $codes ) ) : ?>
			<p><?php esc_html_e( 'Upload a report first and the supplier codes it contains will be listed here.', 'pci' ); ?></p>
		<?php else : ?>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( 'pci_settings' ); ?>
				<input type="hidden" name="action" value="pci_settings">
				<input type="hidden" name="which" value="suppliers">
				<table class="widefat striped" style="max-width:1000px;">
					<thead><tr>
						<th><?php esc_html_e( 'Code', 'pci' ); ?></th>
						<th><?php esc_html_e( 'SKUs in report', 'pci' ); ?></th>
						<th><?php esc_html_e( 'On website', 'pci' ); ?></th>
						<th><?php esc_html_e( 'Units', 'pci' ); ?></th>
						<th><?php esc_html_e( 'Scraper', 'pci' ); ?></th>
						<th><?php esc_html_e( 'Policy', 'pci' ); ?></th>
					</tr></thead>
					<tbody>
					<?php foreach ( $codes as $c ) :
						$policy  = isset( $map[ $c->vend ] ) ? $map[ $c->vend ] : PCI_Suppliers::ACTIVE;
						$scraper = $reg->for_vend( $c->vend );
						?>
						<tr>
							<td><strong><?php echo esc_html( $c->vend ); ?></strong></td>
							<td><?php echo (int) $c->skus; ?></td>
							<td><?php echo (int) $c->on_site; ?></td>
							<td><?php echo (int) $c->units; ?></td>
							<td><?php echo $scraper ? esc_html( $scraper->name() ) : '<span class="pci-muted">—</span>'; ?></td>
							<td>
								<select name="policy[<?php echo esc_attr( $c->vend ); ?>]">
									<?php foreach ( array( PCI_Suppliers::ACTIVE, PCI_Suppliers::DISCONTINUED, PCI_Suppliers::IGNORE ) as $opt ) : ?>
										<option value="<?php echo esc_attr( $opt ); ?>" <?php selected( $policy, $opt ); ?>>
											<?php echo esc_html( PCI_Suppliers::label( $opt ) ); ?>
										</option>
									<?php endforeach; ?>
								</select>
							</td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
				<p><button class="button button-primary"><?php esc_html_e( 'Save supplier policies', 'pci' ); ?></button></p>
				<p class="pci-muted"><?php esc_html_e( 'Changing a policy affects the next report you upload. Re-upload to re-classify an existing batch.', 'pci' ); ?></p>
			</form>
		<?php endif; ?>
		<?php
		echo '</div>';
	}

	// -------------------------------------------------------- settings screen

	public function route_settings() {
		if ( ! current_user_can( PCI_CAP ) ) {
			wp_die( esc_html__( 'You do not have permission to view this page.', 'pci' ) );
		}

		echo '<div class="wrap">';
		$this->notices();
		?>
		<h1><?php esc_html_e( 'Inventory Sync settings', 'pci' ); ?></h1>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php wp_nonce_field( 'pci_settings' ); ?>
			<input type="hidden" name="action" value="pci_settings">
			<input type="hidden" name="which" value="general">

			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'What "hide" does', 'pci' ); ?></th>
					<td>
						<?php $mode = PCI_Run::hide_mode(); ?>
						<label><input type="radio" name="hide_mode" value="outofstock" <?php checked( $mode, 'outofstock' ); ?>>
							<?php esc_html_e( 'Mark out of stock only — stays in the catalog', 'pci' ); ?></label><br>
						<label><input type="radio" name="hide_mode" value="exclude" <?php checked( $mode, 'exclude' ); ?>>
							<?php esc_html_e( 'Mark out of stock and hide from catalog and search', 'pci' ); ?></label><br>
						<label><input type="radio" name="hide_mode" value="draft" <?php checked( $mode, 'draft' ); ?>>
							<?php esc_html_e( 'Mark out of stock and switch to Draft', 'pci' ); ?></label>
						<p class="description"><?php esc_html_e( 'Out of stock alone leaves a product visible, and products set to allow backorders stay purchasable. Pick one of the lower two options if the aim is to take it off the storefront.', 'pci' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Prices', 'pci' ); ?></th>
					<td>
						<label><input type="checkbox" name="write_prices" value="1" <?php checked( PCI_Run::write_prices() ); ?>>
							<?php esc_html_e( 'Write the report price to the regular price', 'pci' ); ?></label>
						<p class="description"><?php esc_html_e( 'Off by default. Sale prices are never touched, and the effective price is left to WooCommerce to recalculate.', 'pci' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Safety limit', 'pci' ); ?></th>
					<td>
						<input type="number" name="max_change_pct" min="1" max="100" step="1" value="<?php echo esc_attr( PCI_Run::max_change_pct() ); ?>" class="small-text"> %
						<p class="description"><?php esc_html_e( 'A batch that would hide or remove more than this share of published products has to be confirmed with an override before it can be applied.', 'pci' ); ?></p>
					</td>
				</tr>
			</table>
			<p><button class="button button-primary"><?php esc_html_e( 'Save settings', 'pci' ); ?></button></p>
		</form>
		<?php
		echo '</div>';
	}

	// -------------------------------------------------------------- handlers

	public function handle_upload() {
		check_admin_referer( 'pci_upload' );
		if ( ! current_user_can( PCI_CAP ) ) {
			wp_die( esc_html__( 'You do not have permission to upload reports.', 'pci' ) );
		}

		if ( empty( $_FILES['pci_file']['tmp_name'] ) || ! is_uploaded_file( $_FILES['pci_file']['tmp_name'] ) ) {
			$this->redirect( array(), __( 'No file arrived. Choose a file and try again.', 'pci' ), 'error' );
		}

		$name = sanitize_file_name( $_FILES['pci_file']['name'] );
		$dest = trailingslashit( pci_storage_dir() ) . gmdate( 'Ymd-His' ) . '-' . $name;

		if ( ! move_uploaded_file( $_FILES['pci_file']['tmp_name'], $dest ) ) {
			$this->redirect( array(), __( 'The file could not be saved. Check that the uploads directory is writable.', 'pci' ), 'error' );
		}

		$run_id = PCI_Run::create_from_file( $dest, $name );

		if ( is_wp_error( $run_id ) ) {
			$this->redirect( array(), $run_id->get_error_message(), 'error' );
		}

		$this->redirect(
			array( 'view' => 'preview', 'run' => $run_id ),
			__( 'Report analysed. Nothing has changed yet — review the summary below.', 'pci' )
		);
	}

	public function handle_apply() {
		$run_id = isset( $_POST['run'] ) ? (int) $_POST['run'] : 0;
		check_admin_referer( 'pci_apply_' . $run_id );
		if ( ! current_user_can( PCI_CAP ) ) {
			wp_die( esc_html__( 'You do not have permission to apply batches.', 'pci' ) );
		}

		$override = ! empty( $_POST['override'] );
		$result   = PCI_Applier::apply_run( $run_id, $override );

		if ( ! empty( $result['errors'] ) && 0 === $result['applied'] ) {
			$this->redirect( array( 'view' => 'preview', 'run' => $run_id ), $result['errors'][0], 'error' );
		}

		$msg = sprintf(
			/* translators: 1: applied count, 2: skipped count */
			__( 'Applied. %1$d products updated, %2$d skipped. Roll back from this screen if anything looks wrong.', 'pci' ),
			$result['applied'],
			$result['skipped']
		);

		$this->redirect( array( 'view' => 'preview', 'run' => $run_id ), $msg );
	}

	public function handle_rollback() {
		$run_id = isset( $_GET['run'] ) ? (int) $_GET['run'] : 0;
		check_admin_referer( 'pci_rollback_' . $run_id );
		if ( ! current_user_can( PCI_CAP ) ) {
			wp_die( esc_html__( 'You do not have permission to roll back batches.', 'pci' ) );
		}

		$result = PCI_Applier::rollback_run( $run_id );

		if ( ! empty( $result['errors'] ) && 0 === $result['restored'] ) {
			$this->redirect( array( 'view' => 'preview', 'run' => $run_id ), $result['errors'][0], 'error' );
		}

		$msg = sprintf(
			/* translators: 1: restored count, 2: skipped count */
			__( 'Rolled back. %1$d products restored, %2$d skipped.', 'pci' ),
			$result['restored'],
			$result['skipped']
		);

		$this->redirect( array( 'view' => 'preview', 'run' => $run_id ), $msg );
	}

	public function handle_settings() {
		check_admin_referer( 'pci_settings' );
		if ( ! current_user_can( PCI_CAP ) ) {
			wp_die( esc_html__( 'You do not have permission to change these settings.', 'pci' ) );
		}

		$which = isset( $_POST['which'] ) ? sanitize_key( $_POST['which'] ) : 'general';

		if ( 'suppliers' === $which ) {
			$policy = isset( $_POST['policy'] ) && is_array( $_POST['policy'] ) ? wp_unslash( $_POST['policy'] ) : array();
			PCI_Suppliers::save_policy( $policy );
			wp_safe_redirect( add_query_arg(
				array( 'page' => self::SLUG . '-suppliers', 'pci_msg' => __( 'Supplier policies saved.', 'pci' ) ),
				admin_url( 'admin.php' )
			) );
			exit;
		}

		update_option( PCI_Run::OPT_HIDE_MODE, isset( $_POST['hide_mode'] ) ? sanitize_key( $_POST['hide_mode'] ) : 'outofstock' );
		update_option( PCI_Run::OPT_WRITE_PRICES, ! empty( $_POST['write_prices'] ) ? 1 : 0 );
		update_option( PCI_Run::OPT_MAX_CHANGE_PCT, max( 1, min( 100, (int) $_POST['max_change_pct'] ) ) );

		wp_safe_redirect( add_query_arg(
			array( 'page' => self::SLUG . '-settings', 'pci_msg' => __( 'Settings saved.', 'pci' ) ),
			admin_url( 'admin.php' )
		) );
		exit;
	}

	/** CSV of every flagged row, so nothing gets lost. */
	public function handle_export() {
		$run_id = isset( $_GET['run'] ) ? (int) $_GET['run'] : 0;
		check_admin_referer( 'pci_export_' . $run_id );
		if ( ! current_user_can( PCI_CAP ) ) {
			wp_die( esc_html__( 'You do not have permission to export.', 'pci' ) );
		}

		global $wpdb;
		$items = PCI_Schema::table( 'items' );
		$rows  = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$items} WHERE run_id = %d AND action LIKE 'flag_%%' ORDER BY action, vend, sku",
				$run_id
			)
		);

		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=pci-flagged-batch-' . $run_id . '.csv' );

		$out = fopen( 'php://output', 'w' );
		fputcsv( $out, array( 'action', 'sku', 'vend', 'item_id', 'description', 'dept', 'qty', 'max', 'min', 'price', 'cost', 'rows', 'product_id', 'product_title', 'reason' ) );

		foreach ( $rows as $r ) {
			fputcsv( $out, array(
				$r->action, $r->sku, $r->vend, $r->item_id, $r->description, $r->dept,
				$r->file_qty, $r->file_max, $r->file_min, $r->file_price, $r->file_cost,
				$r->row_count, $r->product_id, $r->product_title, $r->flag_reason,
			) );
		}

		fclose( $out );
		exit;
	}

	public function ajax_scrape() {
		check_ajax_referer( 'pci_scrape' );
		if ( ! current_user_can( PCI_CAP ) ) {
			wp_send_json_error( __( 'You do not have permission to fetch supplier data.', 'pci' ) );
		}

		$item_id = isset( $_POST['item'] ) ? (int) $_POST['item'] : 0;
		$data    = PCI_Scraper_Registry::instance()->fetch_for_item( $item_id );

		if ( is_wp_error( $data ) ) {
			wp_send_json_error( $data->get_error_message() );
		}

		wp_send_json_success( array(
			'title' => esc_html( $data['title'] ),
			'upc'   => esc_html( $data['upc'] ),
			'msrp'  => esc_html( $data['msrp'] ),
			'image' => esc_url( $data['image_url'] ),
		) );
	}
}
