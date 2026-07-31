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
		add_action( 'wp_ajax_pci_fetch_batch', array( $this, 'ajax_fetch_batch' ) );
		add_action( 'wp_ajax_pci_apply_chunk', array( $this, 'ajax_apply_chunk' ) );
		add_action( 'admin_post_pci_create_drafts', array( $this, 'handle_create_drafts' ) );
		add_action( 'admin_post_pci_review', array( $this, 'handle_review' ) );
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
			.pci-cards { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 16px; margin-top: 12px; }
			.pci-card { display: flex; gap: 12px; border: 1px solid #dcdcde; background: #fff; padding: 12px; border-radius: 3px; }
			.pci-card-img { flex: 0 0 96px; }
			.pci-card-img img { width: 96px; height: 96px; object-fit: contain; background: #f6f7f7; }
			.pci-noimg { width: 96px; height: 96px; background: #f6f7f7; color: #8c8f94; display: flex; align-items: center; justify-content: center; font-size: 11px; }
			.pci-card-body { flex: 1 1 auto; min-width: 0; }
			.pci-card-body ul { margin: 6px 0 0; font-size: 12px; line-height: 1.7; }
			.pci-card-body li { margin: 0; }
			.pci-card-actions { margin: 10px 0 0; display: flex; gap: 6px; }
			.pci-card-failed { border-left: 4px solid #d63638; background: #fcf9f9; }
			.pci-card.pci-new { animation: pci-flash 1.2s ease-out; }
			@keyframes pci-flash { from { background: #f0f6fc; } to { background: #fff; } }
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
		} elseif ( 'review' === $view && $run_id ) {
			$this->screen_review( $run_id );
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
							<?php if ( in_array( $run->status, array( 'applied', 'applying' ), true ) ) : ?>
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
			case 'applying':
				$p = PCI_Applier::progress( $run->id );
				return sprintf( __( 'Part applied — %1$d of %2$d', 'pci' ), $p['done'], $p['total'] );
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

		<?php if ( in_array( $run->status, array( 'parsed', 'applying' ), true ) ) : ?>
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

		if ( in_array( $run->status, array( 'parsed', 'applying' ), true ) ) {
			$this->apply_form( $run, $check );
			if ( 'applying' === $run->status ) {
				$this->rollback_form( $run );
			}
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
		global $wpdb;
		$items_t = PCI_Schema::table( 'items' );
		$items   = PCI_Run::items( $run_id, PCI_Classifier::UPDATE, 1000 );

		// Price movement across the whole run, not just the first 1,000 shown.
		$where = $wpdb->prepare(
			"run_id = %d AND action = %s AND file_price IS NOT NULL
			 AND cur_price IS NOT NULL AND cur_price <> ''
			 AND ABS(file_price - cur_price) > 0.005",
			(int) $run_id,
			PCI_Classifier::UPDATE
		);

		$p = $wpdb->get_row(
			"SELECT COUNT(*) AS n,
			        SUM(CASE WHEN file_price > cur_price THEN 1 ELSE 0 END) AS up,
			        SUM(CASE WHEN file_price < cur_price THEN 1 ELSE 0 END) AS down,
			        SUM(CASE WHEN ABS(file_price - cur_price) > 5 THEN 1 ELSE 0 END) AS big
			 FROM {$items_t} WHERE {$where}"
		);

		$movers = $wpdb->get_results(
			"SELECT sku, product_title, cur_price, file_price,
			        (file_price - cur_price) AS delta
			 FROM {$items_t} WHERE {$where}
			 ORDER BY ABS(file_price - cur_price) DESC LIMIT 10"
		);

		$writing = PCI_Run::write_prices();
		?>
		<div class="pci-section">
			<h2><?php printf( esc_html__( 'Stock changes (%d)', 'pci' ), (int) $counts[ PCI_Classifier::UPDATE ] ); ?></h2>

			<?php if ( $p && (int) $p->n > 0 ) : ?>
				<div class="<?php echo $writing ? 'pci-note' : 'pci-note'; ?>">
					<p>
						<strong><?php printf( esc_html__( '%d products have a different price in the report', 'pci' ), (int) $p->n ); ?></strong>
						— <?php printf( esc_html__( '%1$d up, %2$d down, %3$d moving by more than $5.', 'pci' ), (int) $p->up, (int) $p->down, (int) $p->big ); ?>
						<br>
						<?php if ( $writing ) : ?>
							<span style="color:#d63638;"><?php esc_html_e( 'Prices WILL be written to the regular price. Sale prices are left alone and WooCommerce recalculates the effective price.', 'pci' ); ?></span>
						<?php else : ?>
							<?php esc_html_e( 'Prices will NOT be written — this is shown for review only. Turn it on under Settings if you want the report to set prices.', 'pci' ); ?>
						<?php endif; ?>
					</p>
					<?php if ( $movers ) : ?>
						<details class="pci-list">
							<summary><?php esc_html_e( 'Ten biggest price movements', 'pci' ); ?></summary>
							<table class="widefat striped" style="max-width:800px;">
								<thead><tr>
									<th><?php esc_html_e( 'Product', 'pci' ); ?></th>
									<th><?php esc_html_e( 'SKU', 'pci' ); ?></th>
									<th><?php esc_html_e( 'Now', 'pci' ); ?></th>
									<th><?php esc_html_e( 'Report', 'pci' ); ?></th>
									<th><?php esc_html_e( 'Change', 'pci' ); ?></th>
								</tr></thead>
								<tbody>
								<?php foreach ( $movers as $m ) : ?>
									<tr>
										<td><?php echo esc_html( $m->product_title ); ?></td>
										<td><code><?php echo esc_html( $m->sku ); ?></code></td>
										<td><?php echo esc_html( $m->cur_price ); ?></td>
										<td><?php echo esc_html( $m->file_price ); ?></td>
										<td style="color:<?php echo ( (float) $m->delta ) > 0 ? '#d63638' : '#00a32a'; ?>;font-weight:600;">
											<?php echo esc_html( sprintf( '%+.2f', (float) $m->delta ) ); ?>
										</td>
									</tr>
								<?php endforeach; ?>
								</tbody>
							</table>
						</details>
					<?php endif; ?>
				</div>
			<?php endif; ?>

			<details class="pci-list">
				<summary><?php esc_html_e( 'Show the first 1,000 stock changes', 'pci' ); ?></summary>
				<div class="pci-scroll">
				<table class="widefat striped">
					<thead><tr>
						<th><?php esc_html_e( 'Product', 'pci' ); ?></th>
						<th><?php esc_html_e( 'SKU', 'pci' ); ?></th>
						<th><?php esc_html_e( 'Stock now', 'pci' ); ?></th>
						<th><?php esc_html_e( 'Stock after', 'pci' ); ?></th>
						<th><?php esc_html_e( 'Price now', 'pci' ); ?></th>
						<th><?php esc_html_e( 'Price in report', 'pci' ); ?></th>
						<th><?php esc_html_e( 'Managed?', 'pci' ); ?></th>
						<th><?php esc_html_e( 'Note', 'pci' ); ?></th>
					</tr></thead>
					<tbody>
					<?php foreach ( $items as $it ) :
						$qty_changed = ( (string) $it->cur_qty !== (string) $it->file_qty );
						$has_prices  = ( null !== $it->file_price && '' !== (string) $it->cur_price );
						$delta       = $has_prices ? ( (float) $it->file_price - (float) $it->cur_price ) : 0;
						$price_moved = $has_prices && abs( $delta ) > 0.005;
						?>
						<tr>
							<td><?php echo esc_html( $it->product_title ); ?></td>
							<td><code><?php echo esc_html( $it->sku ); ?></code></td>
							<td><?php echo esc_html( null === $it->cur_qty ? '—' : $it->cur_qty ); ?></td>
							<td<?php echo $qty_changed ? ' style="font-weight:600;"' : ''; ?>><?php echo (int) $it->file_qty; ?></td>
							<td><?php echo esc_html( '' === (string) $it->cur_price ? '—' : $it->cur_price ); ?></td>
							<td<?php echo $price_moved ? ' style="font-weight:600;color:' . ( $delta > 0 ? '#d63638' : '#00a32a' ) . ';"' : ''; ?>>
								<?php
								echo esc_html( null === $it->file_price ? '—' : $it->file_price );
								if ( $price_moved ) {
									echo ' <small>(' . esc_html( sprintf( '%+.2f', $delta ) ) . ')</small>';
								}
								?>
							</td>
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
		$p        = PCI_Applier::progress( $run->id );
		$resuming = ( $p['done'] > 0 );
		?>
		<div class="pci-section" style="border-top:1px solid #c3c4c7;padding-top:1.5em;">
			<h2><?php echo $resuming ? esc_html__( 'Resume this batch', 'pci' ) : esc_html__( 'Approve this batch', 'pci' ); ?></h2>

			<?php if ( $resuming ) : ?>
				<div class="pci-note">
					<p><strong><?php printf( esc_html__( '%1$d of %2$d products already written.', 'pci' ), (int) $p['done'], (int) $p['total'] ); ?></strong>
					<?php esc_html_e( 'A previous attempt stopped early. Continuing picks up where it left off — products already written are skipped, not redone.', 'pci' ); ?></p>
				</div>
			<?php endif; ?>

			<?php if ( $check['blocked'] ) : ?>
				<p><label>
					<input type="checkbox" id="pci-override" value="1">
					<?php esc_html_e( 'I have read the lists above and this batch is correct. Apply it despite the safety limit.', 'pci' ); ?>
				</label></p>
			<?php endif; ?>

			<p>
				<button class="button button-primary button-large" id="pci-apply"
					data-run="<?php echo (int) $run->id; ?>"
					data-blocked="<?php echo $check['blocked'] ? '1' : '0'; ?>">
					<?php echo $resuming ? esc_html__( 'Continue applying', 'pci' ) : esc_html__( 'Apply this batch', 'pci' ); ?>
				</button>
			</p>

			<div id="pci-apply-progress" style="display:none;max-width:640px;">
				<div style="background:#f0f0f1;border-radius:3px;height:22px;overflow:hidden;">
					<div id="pci-apply-bar" style="background:#2271b1;height:100%;width:0;transition:width .2s;"></div>
				</div>
				<p id="pci-apply-status" class="pci-muted" style="margin-top:6px;"></p>
			</div>

			<p class="pci-muted">
				<?php esc_html_e( 'Applied in chunks from this page, so it cannot time out. Safe to interrupt — closing the tab stops it cleanly and you can continue later. A before-snapshot is saved for every product touched.', 'pci' ); ?>
			</p>
		</div>

		<script>
		(function () {
			var btn = document.getElementById('pci-apply');
			if (!btn) return;
			var nonce = <?php echo wp_json_encode( wp_create_nonce( 'pci_apply_chunk' ) ); ?>;
			var wrap = document.getElementById('pci-apply-progress');
			var bar = document.getElementById('pci-apply-bar');
			var status = document.getElementById('pci-apply-status');
			var stop = false;

			function chunk(runId, override) {
				var body = new FormData();
				body.append('action', 'pci_apply_chunk');
				body.append('run', runId);
				body.append('override', override ? '1' : '0');
				body.append('_ajax_nonce', nonce);

				return fetch(ajaxurl, { method: 'POST', body: body, credentials: 'same-origin' })
					.then(function (r) { return r.json(); })
					.then(function (res) {
						if (!res.success) { status.textContent = res.data || 'Failed'; btn.disabled = false; return; }
						var d = res.data;
						var pct = d.total ? Math.round(d.done / d.total * 100) : 100;
						bar.style.width = pct + '%';
						status.textContent = d.done + ' / ' + d.total + ' written (' + pct + '%)'
							+ (d.errors && d.errors.length ? ' — ' + d.errors[0] : '');
						if (d.finished) {
							status.textContent = 'Finished. ' + d.done + ' products written. Reloading…';
							setTimeout(function () { location.reload(); }, 900);
							return;
						}
						if (d.pending > 0 && !stop) { return chunk(runId, override); }
					})
					.catch(function () {
						status.textContent = <?php echo wp_json_encode( __( 'Request failed. Press the button again to continue from where it stopped.', 'pci' ) ); ?>;
						btn.disabled = false;
					});
			}

			btn.addEventListener('click', function (e) {
				e.preventDefault();
				var ov = document.getElementById('pci-override');
				if (this.dataset.blocked === '1' && (!ov || !ov.checked)) {
					alert(<?php echo wp_json_encode( __( 'Tick the override box first.', 'pci' ) ); ?>);
					return;
				}
				if (!confirm(<?php echo wp_json_encode( __( 'Apply this batch now? You can roll it back afterwards.', 'pci' ) ); ?>)) return;
				btn.disabled = true;
				wrap.style.display = 'block';
				chunk(this.dataset.run, ov && ov.checked);
			});

			window.addEventListener('beforeunload', function () { stop = true; });
		})();
		</script>
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
		$c    = PCI_Sourcing::counts( $run_id );
		$rows = $this->fetched_rows( $run_id, 200 );
		?>
		<h1><?php printf( esc_html__( 'Sourcing — batch #%d', 'pci' ), (int) $run_id ); ?></h1>
		<p><a href="<?php echo esc_url( $this->url( array( 'view' => 'preview', 'run' => $run_id ) ) ); ?>">&larr; <?php esc_html_e( 'Back to the batch', 'pci' ); ?></a></p>

		<div class="pci-note">
			<p><strong><?php esc_html_e( 'Nothing here runs on its own.', 'pci' ); ?></strong>
			<?php esc_html_e( 'Fetching pulls supplier data onto the staged rows and creates nothing. You then look at the previews, create drafts, and publish the ones you want.', 'pci' ); ?></p>
		</div>

		<table class="pci-ledger">
			<tbody>
				<tr><td><?php esc_html_e( 'New products in this batch', 'pci' ); ?></td><td class="num"><?php echo (int) $c['total']; ?></td></tr>
				<tr><td><?php esc_html_e( 'Supplier data fetched', 'pci' ); ?></td><td class="num pci-stat-fetched"><?php echo (int) $c['fetched']; ?></td></tr>
				<tr><td><?php esc_html_e( 'Fetches that failed', 'pci' ); ?></td><td class="num"><?php echo (int) $c['failed']; ?></td></tr>
				<tr><td><?php esc_html_e( 'Still to fetch', 'pci' ); ?></td><td class="num pci-stat-pending"><?php echo (int) $c['pending']; ?></td></tr>
				<tr><td><?php esc_html_e( 'Drafts created', 'pci' ); ?></td><td class="num"><?php echo (int) $c['drafts']; ?></td></tr>
				<tr><td><?php esc_html_e( 'Published', 'pci' ); ?></td><td class="num"><?php echo (int) $c['published']; ?></td></tr>
			</tbody>
		</table>

		<?php if ( ! PCI_UPC::is_configured() ) : ?>
			<div class="pci-note">
				<p><?php esc_html_e( 'No Barcode Spider token is set, so products whose supplier image is missing or too small will be created without one. Add a token under Settings to enable the UPC image fallback.', 'pci' ); ?></p>
			</div>
		<?php endif; ?>

		<p>
			<button class="button button-primary" id="pci-fetch-batch" data-run="<?php echo (int) $run_id; ?>" <?php disabled( 0, (int) $c['pending'] ); ?>>
				<?php printf( esc_html__( 'Fetch next %d', 'pci' ), PCI_Sourcing::batch_size() ); ?>
			</button>
			<button class="button" id="pci-fetch-all" data-run="<?php echo (int) $run_id; ?>" <?php disabled( 0, (int) $c['pending'] ); ?>>
				<?php esc_html_e( 'Keep fetching until done', 'pci' ); ?>
			</button>
			<span id="pci-fetch-status" class="pci-muted"></span>
		</p>

		<div class="pci-section" id="pci-results" <?php echo empty( $rows ) ? 'style="display:none;"' : ''; ?>>
			<h2><?php esc_html_e( 'What will be created', 'pci' ); ?></h2>
			<p class="pci-muted"><?php esc_html_e( 'Newest first, appearing as each batch comes back. Red edge means the fetch failed and the reason is on the card.', 'pci' ); ?></p>
			<div class="pci-cards" id="pci-cards">
				<?php foreach ( $rows as $r ) : $this->sourcing_card( $r ); ?><?php endforeach; ?>
			</div>
		</div>

		<?php if ( true ) : ?>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-top:1.5em;">
				<?php wp_nonce_field( 'pci_create_drafts_' . $run_id ); ?>
				<input type="hidden" name="action" value="pci_create_drafts">
				<input type="hidden" name="run" value="<?php echo (int) $run_id; ?>">
				<p>
					<label><?php esc_html_e( 'How many drafts to create now:', 'pci' ); ?>
						<input type="number" name="limit" value="10" min="1" max="500" class="small-text">
					</label>
					<button class="button button-primary"><?php esc_html_e( 'Create drafts', 'pci' ); ?></button>
					<span class="pci-muted"><?php esc_html_e( 'Drafts are not visible on the site until you publish them.', 'pci' ); ?></span>
				</p>
			</form>
		<?php endif; ?>

		<?php if ( (int) $c['drafts'] > 0 ) : ?>
			<p style="margin-top:1.5em;">
				<a class="button button-secondary button-large" href="<?php echo esc_url( $this->url( array( 'view' => 'review', 'run' => $run_id ) ) ); ?>">
					<?php printf( esc_html__( 'Review %d drafts', 'pci' ), (int) $c['drafts'] ); ?>
				</a>
			</p>
		<?php endif; ?>

		<script>
		(function () {
			var nonce = <?php echo wp_json_encode( wp_create_nonce( 'pci_fetch' ) ); ?>;
			var status = document.getElementById('pci-fetch-status');
			var grid = document.getElementById('pci-cards');
			var wrap = document.getElementById('pci-results');
			var stop = false;

			function render(cards) {
				if (!grid || !cards || !cards.length) return;
				if (wrap) wrap.style.display = 'block';
				cards.forEach(function (html) {
					var d = document.createElement('div');
					d.innerHTML = html.trim();
					var card = d.firstChild;
					if (!card) return;
					card.classList.add('pci-new');
					grid.insertBefore(card, grid.firstChild);
				});
			}

			function run(runId, keepGoing) {
				var body = new FormData();
				body.append('action', 'pci_fetch_batch');
				body.append('run', runId);
				body.append('_ajax_nonce', nonce);
				status.textContent = <?php echo wp_json_encode( __( 'Fetching…', 'pci' ) ); ?>;

				return fetch(ajaxurl, { method: 'POST', body: body, credentials: 'same-origin' })
					.then(function (r) { return r.json(); })
					.then(function (res) {
						if (!res.success) { status.textContent = res.data || 'Failed'; return; }
						var d = res.data;
						render(d.cards);
						status.textContent = d.fetched + ' / ' + d.total + ' fetched'
							+ (d.failed ? ', ' + d.failed + ' failed' : '')
							+ ', ' + d.pending + ' to go';
						document.querySelectorAll('.pci-stat-fetched').forEach(function (el) { el.textContent = d.fetched; });
						document.querySelectorAll('.pci-stat-pending').forEach(function (el) { el.textContent = d.pending; });
						if (d.pending === 0) {
							status.textContent += ' — done.';
							return;
						}
						if (keepGoing && !stop) { return run(runId, true); }
					})
					.catch(function () {
						status.textContent = <?php echo wp_json_encode( __( 'Request failed. Press the button again to continue.', 'pci' ) ); ?>;
					});
			}

			var one = document.getElementById('pci-fetch-batch');
			var all = document.getElementById('pci-fetch-all');
			if (one) one.addEventListener('click', function (e) { e.preventDefault(); stop = false; run(this.dataset.run, false); });
			if (all) all.addEventListener('click', function (e) { e.preventDefault(); stop = false; run(this.dataset.run, true); });
			window.addEventListener('beforeunload', function () { stop = true; });
		})();
		</script>
		<?php
	}

	/** Rows with supplier data attached, newest first. */
	private function fetched_rows( $run_id, $limit = 200, $action = null ) {
		global $wpdb;
		$t = PCI_Schema::table( 'items' );
		return $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM {$t} WHERE run_id = %d AND action = %s
			   AND (raw LIKE %s OR raw LIKE %s)
			 ORDER BY id DESC LIMIT %d",
			(int) $run_id,
			$action ? $action : PCI_Classifier::NEW_P,
			'%"scraped":{%',
			'%scrape_error%',
			(int) $limit
		) );
	}

	/** One preview card: image plus the bullet points that matter. */
	private function sourcing_card( $r, $show_actions = false ) {
		$raw  = json_decode( (string) $r->raw, true );
		$d    = isset( $raw['scraped'] ) ? $raw['scraped'] : array();
		$err  = isset( $raw['scrape_error'] ) ? $raw['scrape_error'] : '';
		$img  = isset( $d['image_url'] ) ? $d['image_url'] : '';
		$src  = isset( $d['image_source'] ) ? $d['image_source'] : '';
		?>
		<div class="pci-card<?php echo $err ? ' pci-card-failed' : ''; ?>">
			<div class="pci-card-img">
				<?php if ( $img ) : ?>
					<img src="<?php echo esc_url( $img ); ?>" alt="" loading="lazy">
				<?php else : ?>
					<div class="pci-noimg"><?php esc_html_e( 'no image', 'pci' ); ?></div>
				<?php endif; ?>
			</div>
			<div class="pci-card-body">
				<strong><?php echo esc_html( isset( $d['title_clean'] ) ? $d['title_clean'] : ( $r->description ? $r->description : $r->sku ) ); ?></strong>
				<ul>
					<li><?php esc_html_e( 'SKU', 'pci' ); ?>: <code><?php echo esc_html( $r->sku ); ?></code> · <?php echo esc_html( $r->vend ); ?></li>
					<li><?php esc_html_e( 'Price', 'pci' ); ?>:
						<?php echo null === $r->file_price ? '—' : esc_html( '$' . $r->file_price ); ?>
						<span class="pci-muted">(<?php esc_html_e( 'from the POS report', 'pci' ); ?><?php if ( ! empty( $d['msrp'] ) ) : ?>; <?php esc_html_e( 'SLS MSRP', 'pci' ); ?> $<?php echo esc_html( $d['msrp'] ); endif; ?>)</span>
					</li>
					<li><?php esc_html_e( 'Quantity', 'pci' ); ?>: <?php echo (int) $r->file_qty; ?></li>
					<li><?php esc_html_e( 'UPC', 'pci' ); ?>: <?php echo ! empty( $d['upc'] ) ? '<code>' . esc_html( $d['upc'] ) . '</code>' : '<span class="pci-muted">' . esc_html__( 'none', 'pci' ) . '</span>'; ?></li>
					<li><?php esc_html_e( 'Category', 'pci' ); ?>:
						<?php
						if ( ! empty( $d['cat_matched'] ) ) {
							echo esc_html( implode( ' › ', $d['cat_matched'] ) );
						} else {
							echo '<span style="color:#d63638;">' . esc_html__( 'no match — will be uncategorised', 'pci' ) . '</span>';
						}
						if ( ! empty( $d['cat_missing'] ) ) {
							echo ' <span class="pci-muted">(' . esc_html__( 'unmatched:', 'pci' ) . ' ' . esc_html( implode( ', ', $d['cat_missing'] ) ) . ')</span>';
						}
						?>
					</li>
					<li><?php esc_html_e( 'Image', 'pci' ); ?>:
						<?php
						if ( 'supplier' === $src ) {
							esc_html_e( 'from SLS', 'pci' );
						} elseif ( 'upc' === $src ) {
							echo '<span style="color:#2271b1;">' . esc_html__( 'found via UPC lookup', 'pci' ) . '</span>';
						} elseif ( 'supplier-small' === $src ) {
							echo '<span style="color:#dba617;">' . esc_html__( 'SLS image is small', 'pci' ) . '</span>';
						} else {
							echo '<span style="color:#d63638;">' . esc_html__( 'none found', 'pci' ) . '</span>';
						}
						if ( ! empty( $d['image_width'] ) ) {
							echo ' <span class="pci-muted">' . (int) $d['image_width'] . 'px</span>';
						}
						?>
					</li>
					<?php if ( $err ) : ?>
						<li style="color:#d63638;"><?php echo esc_html( $err ); ?></li>
					<?php endif; ?>
				</ul>

				<?php if ( $show_actions && $r->product_id ) : ?>
					<p class="pci-card-actions">
						<a class="button button-primary" href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'action' => 'pci_review', 'mode' => 'approve', 'item' => $r->id, 'run' => $r->run_id ), admin_url( 'admin-post.php' ) ), 'pci_review_' . $r->id ) ); ?>"><?php esc_html_e( 'Publish', 'pci' ); ?></a>
						<a class="button" href="<?php echo esc_url( get_edit_post_link( $r->product_id ) ); ?>" target="_blank"><?php esc_html_e( 'Edit', 'pci' ); ?></a>
						<a class="button" style="color:#d63638;" href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'action' => 'pci_review', 'mode' => 'reject', 'item' => $r->id, 'run' => $r->run_id ), admin_url( 'admin-post.php' ) ), 'pci_review_' . $r->id ) ); ?>"><?php esc_html_e( 'Reject', 'pci' ); ?></a>
					</p>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}

	// ---------------------------------------------------------- review screen

	private function screen_review( $run_id ) {
		$drafts = PCI_Sourcing::drafts( $run_id, 200 );
		$c      = PCI_Sourcing::counts( $run_id );
		?>
		<h1><?php printf( esc_html__( 'Review drafts — batch #%d', 'pci' ), (int) $run_id ); ?></h1>
		<p><a href="<?php echo esc_url( $this->url( array( 'view' => 'scrape', 'run' => $run_id ) ) ); ?>">&larr; <?php esc_html_e( 'Back to sourcing', 'pci' ); ?></a></p>

		<p>
			<?php printf( esc_html__( '%1$d awaiting review · %2$d already published.', 'pci' ), (int) $c['drafts'], (int) $c['published'] ); ?>
		</p>

		<?php if ( empty( $drafts ) ) : ?>
			<p class="pci-muted"><?php esc_html_e( 'Nothing waiting. Create some drafts from the sourcing screen.', 'pci' ); ?></p>
		<?php else : ?>
			<p>
				<a class="button button-primary" href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'action' => 'pci_review', 'mode' => 'approve_all', 'run' => $run_id ), admin_url( 'admin-post.php' ) ), 'pci_review_all_' . $run_id ) ); ?>"
				   onclick="return confirm('<?php echo esc_js( __( 'Publish every remaining draft in this batch?', 'pci' ) ); ?>');">
					<?php printf( esc_html__( 'Publish all %d', 'pci' ), (int) $c['drafts'] ); ?>
				</a>
				<span class="pci-muted"><?php esc_html_e( 'Or publish and reject them one at a time below.', 'pci' ); ?></span>
			</p>
			<div class="pci-cards">
				<?php foreach ( $drafts as $d ) : $this->sourcing_card( $d, true ); ?><?php endforeach; ?>
			</div>
		<?php endif; ?>
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
					<th scope="row"><?php esc_html_e( 'Barcode Spider token', 'pci' ); ?></th>
					<td>
						<input type="text" name="upc_token" value="<?php echo esc_attr( PCI_UPC::token() ); ?>" class="regular-text" autocomplete="off">
						<p class="description"><?php esc_html_e( 'Used only to find a product image when the supplier has none or theirs is too small. Leave blank to skip the UPC fallback.', 'pci' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Minimum image width', 'pci' ); ?></th>
					<td>
						<input type="number" name="min_img" min="50" max="2000" step="10" value="<?php echo esc_attr( PCI_Sourcing::min_image_width() ); ?>" class="small-text"> px
						<p class="description"><?php esc_html_e( 'Supplier images narrower than this trigger the UPC lookup.', 'pci' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Fetch batch size', 'pci' ); ?></th>
					<td>
						<input type="number" name="batch" min="1" max="50" value="<?php echo esc_attr( PCI_Sourcing::batch_size() ); ?>" class="small-text">
						<p class="description"><?php esc_html_e( 'How many products to fetch per request. Small keeps each request quick and the supplier happy.', 'pci' ); ?></p>
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
		update_option( PCI_UPC::OPT_TOKEN, isset( $_POST['upc_token'] ) ? sanitize_text_field( wp_unslash( $_POST['upc_token'] ) ) : '' );
		update_option( PCI_Sourcing::OPT_MIN_IMG_WIDTH, isset( $_POST['min_img'] ) ? max( 50, (int) $_POST['min_img'] ) : 300 );
		update_option( PCI_Sourcing::OPT_BATCH_SIZE, isset( $_POST['batch'] ) ? max( 1, min( 50, (int) $_POST['batch'] ) ) : 10 );

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

	public function ajax_apply_chunk() {
		check_ajax_referer( 'pci_apply_chunk' );
		if ( ! current_user_can( PCI_CAP ) ) {
			wp_send_json_error( __( 'You do not have permission to apply batches.', 'pci' ) );
		}

		@set_time_limit( 120 );

		$run_id   = isset( $_POST['run'] ) ? (int) $_POST['run'] : 0;
		$override = ! empty( $_POST['override'] );
		$result   = PCI_Applier::apply_chunk( $run_id, 100, $override );

		wp_send_json_success( $result );
	}

	public function ajax_fetch_batch() {
		check_ajax_referer( 'pci_fetch' );
		if ( ! current_user_can( PCI_CAP ) ) {
			wp_send_json_error( __( 'You do not have permission to fetch supplier data.', 'pci' ) );
		}

		@set_time_limit( 120 );

		$run_id = isset( $_POST['run'] ) ? (int) $_POST['run'] : 0;
		$result = PCI_Sourcing::fetch_batch( $run_id );
		$counts = PCI_Sourcing::counts( $run_id );

		// Render the rows just processed so they can be shown immediately,
		// reusing the same card markup as the page rather than rebuilding it
		// in JavaScript.
		$cards = array();
		if ( ! empty( $result['ids'] ) ) {
			global $wpdb;
			$t   = PCI_Schema::table( 'items' );
			$in  = implode( ',', array_map( 'intval', $result['ids'] ) );
			$rows = $wpdb->get_results( "SELECT * FROM {$t} WHERE id IN ({$in}) ORDER BY FIELD(id,{$in})" );
			foreach ( $rows as $row ) {
				ob_start();
				$this->sourcing_card( $row );
				$cards[] = ob_get_clean();
			}
		}

		wp_send_json_success( array_merge( $counts, array(
			'batch_done'   => $result['done'],
			'batch_failed' => $result['failed'],
			'cards'        => $cards,
		) ) );
	}

	public function handle_create_drafts() {
		$run_id = isset( $_POST['run'] ) ? (int) $_POST['run'] : 0;
		check_admin_referer( 'pci_create_drafts_' . $run_id );
		if ( ! current_user_can( PCI_CAP ) ) {
			wp_die( esc_html__( 'You do not have permission to create products.', 'pci' ) );
		}

		$limit  = isset( $_POST['limit'] ) ? max( 1, min( 500, (int) $_POST['limit'] ) ) : 10;
		$result = PCI_Sourcing::create_drafts( $run_id, $limit );

		$msg = sprintf(
			/* translators: 1: created, 2: skipped */
			__( '%1$d drafts created, %2$d skipped. Nothing is visible on the site until you publish it.', 'pci' ),
			$result['created'],
			$result['skipped']
		);
		if ( ! empty( $result['errors'] ) ) {
			$msg .= ' ' . $result['errors'][0];
		}

		$this->redirect( array( 'view' => 'review', 'run' => $run_id ), $msg );
	}

	public function handle_review() {
		$run_id = isset( $_GET['run'] ) ? (int) $_GET['run'] : 0;
		$mode   = isset( $_GET['mode'] ) ? sanitize_key( $_GET['mode'] ) : '';

		if ( ! current_user_can( PCI_CAP ) ) {
			wp_die( esc_html__( 'You do not have permission to review products.', 'pci' ) );
		}

		if ( 'approve_all' === $mode ) {
			check_admin_referer( 'pci_review_all_' . $run_id );
			$n = PCI_Sourcing::approve_all( $run_id );
			$this->redirect( array( 'view' => 'review', 'run' => $run_id ), sprintf( __( '%d products published.', 'pci' ), $n ) );
		}

		$item_id = isset( $_GET['item'] ) ? (int) $_GET['item'] : 0;
		check_admin_referer( 'pci_review_' . $item_id );

		$res = ( 'approve' === $mode ) ? PCI_Sourcing::approve( $item_id ) : PCI_Sourcing::reject( $item_id );

		if ( is_wp_error( $res ) ) {
			$this->redirect( array( 'view' => 'review', 'run' => $run_id ), $res->get_error_message(), 'error' );
		}

		$this->redirect(
			array( 'view' => 'review', 'run' => $run_id ),
			'approve' === $mode ? __( 'Published.', 'pci' ) : __( 'Rejected and moved to Trash.', 'pci' )
		);
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
