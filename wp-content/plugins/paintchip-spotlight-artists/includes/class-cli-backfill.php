<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * wp paintchip backfill --from=2021-01 --to=2026-07 [--source=auto|revisions|wayback] [--home-page-id=123] [--dry-run]
 *
 * For each month in the range, targets the 20th of that month and tries to find
 * the version of the homepage that was live then, first from WordPress's own
 * revision history (most reliable, no network needed), then falling back to
 * the Wayback Machine's "availability" API.
 *
 * Every month is logged with what was found and whether it needs manual review --
 * this parser is heuristic and WILL get some historical layout variations wrong.
 * Nothing is meant to be "trust and forget"; check the summary at the end.
 */
class PaintChip_CLI_Backfill {

	/** @var array Rows appended for the end-of-run summary table. */
	protected $log = array();

	public static function register() {
		WP_CLI::add_command( 'paintchip backfill', array( __CLASS__, 'run' ) );
	}

	/**
	 * @param array $args
	 * @param array $assoc_args
	 */
	public static function run( $args, $assoc_args ) {
		$instance = new self();
		$instance->execute( $assoc_args );
	}

	protected function execute( $assoc_args ) {
		$from        = isset( $assoc_args['from'] ) ? $assoc_args['from'] : '2021-01';
		$to          = isset( $assoc_args['to'] ) ? $assoc_args['to'] : date( 'Y-m' );
		$source      = isset( $assoc_args['source'] ) ? $assoc_args['source'] : 'auto';
		$dry_run     = isset( $assoc_args['dry-run'] );
		$home_id     = isset( $assoc_args['home-page-id'] ) ? (int) $assoc_args['home-page-id'] : (int) get_option( 'page_on_front' );

		if ( ! $home_id ) {
			WP_CLI::error( 'Could not determine the home page ID. Pass --home-page-id=123 explicitly (find it in wp-admin > Pages).' );
		}

		WP_CLI::log( sprintf( 'Backfilling %s through %s using source=%s, home page ID %d%s', $from, $to, $source, $home_id, $dry_run ? ' (DRY RUN)' : '' ) );

		foreach ( $this->month_range( $from, $to ) as $month ) {
			WP_CLI::log( "\n--- {$month} ---" );
			$target_date = $month . '-20';

			$html    = null;
			$origin  = null;

			if ( in_array( $source, array( 'auto', 'revisions' ), true ) ) {
				$html = $this->find_revision_html( $home_id, $target_date );
				if ( $html ) {
					$origin = 'revision';
				}
			}

			if ( ! $html && in_array( $source, array( 'auto', 'wayback' ), true ) ) {
				$html = $this->find_wayback_html( $target_date );
				if ( $html ) {
					$origin = 'wayback';
				}
			}

			if ( ! $html ) {
				$this->log( $month, 'NO SOURCE FOUND', '-', 'manual entry needed' );
				continue;
			}

			$matches = $this->stage_spotlight_content( $html );

			if ( empty( $matches ) ) {
				$this->log( $month, $origin, '-', 'no keyword match found -- check manually' );
				continue;
			}

			$preview = wp_trim_words( implode( ' / ', array_map( function ( $m ) {
				return implode( ' | ', $m['headings'] );
			}, $matches ) ), 12 );

			$image_count = array_sum( array_map( function ( $m ) {
				return count( $m['images'] );
			}, $matches ) );

			if ( $dry_run ) {
				$this->log( $month, $origin, $preview, sprintf( 'DRY RUN -- would stage %d block(s), %d image URL(s) found', count( $matches ), $image_count ) );
				continue;
			}

			$result = $this->create_staging_draft( $month, $matches );
			$this->log( $month, $origin, $preview, $result );
		}

		$this->print_summary();
	}

	/**
	 * @return string[] e.g. ["2021-01", "2021-02", ... ]
	 */
	protected function month_range( $from, $to ) {
		$months = array();
		$cursor = new DateTime( $from . '-01' );
		$end    = new DateTime( $to . '-01' );
		while ( $cursor <= $end ) {
			$months[] = $cursor->format( 'Y-m' );
			$cursor->modify( '+1 month' );
		}
		return $months;
	}

	/**
	 * Look through the home page's revision history for the version closest to
	 * (but not after) the target date.
	 *
	 * @param int    $home_id
	 * @param string $target_date Y-m-d
	 * @return string|null Raw post_content HTML, or null if no usable revision found.
	 */
	protected function find_revision_html( $home_id, $target_date ) {
		global $wpdb;

		$row = $wpdb->get_row( $wpdb->prepare(
			"SELECT post_content, post_date FROM {$wpdb->posts}
			 WHERE post_parent = %d AND post_type = 'revision' AND post_date <= %s
			 ORDER BY post_date DESC LIMIT 1",
			$home_id,
			$target_date . ' 23:59:59'
		) );

		if ( ! $row ) {
			return null;
		}

		// Sanity check: don't use a revision more than ~40 days before the target --
		// that likely means revisions were pruned and we've walked too far back.
		$days_gap = ( strtotime( $target_date ) - strtotime( $row->post_date ) ) / DAY_IN_SECONDS;
		if ( $days_gap > 40 ) {
			return null;
		}

		return $row->post_content;
	}

	/**
	 * Ask the Wayback Machine's availability API for the snapshot closest to the
	 * target date, then fetch its HTML.
	 *
	 * @param string $target_date Y-m-d
	 * @return string|null
	 */
	protected function find_wayback_html( $target_date ) {
		$timestamp = str_replace( '-', '', $target_date ); // YYYYMMDD

		$response = wp_remote_get( add_query_arg( array(
			'url'       => 'thepaint-chip.com',
			'timestamp' => $timestamp,
		), 'https://archive.org/wayback/available' ), array( 'timeout' => 20 ) );

		if ( is_wp_error( $response ) ) {
			WP_CLI::warning( 'Wayback availability request failed: ' . $response->get_error_message() );
			return null;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( empty( $body['archived_snapshots']['closest']['url'] ) ) {
			return null;
		}

		$snapshot_url = $body['archived_snapshots']['closest']['url'];

		// Force the "id_" flag so we get the raw HTML without Wayback's toolbar/JS injected.
		$snapshot_url = preg_replace( '#(/web/\d+)#', '$1id_', $snapshot_url );

		$page = wp_remote_get( $snapshot_url, array( 'timeout' => 30 ) );
		if ( is_wp_error( $page ) ) {
			WP_CLI::warning( 'Wayback snapshot fetch failed: ' . $page->get_error_message() );
			return null;
		}

		return wp_remote_retrieve_body( $page );
	}

	/** Keyword phrases (lowercase) that mark a column as relevant content. */
	protected $keywords = array( '2nd friday artabout', 'spotlight artist', 'featured artist' );

	/**
	 * Loosely scan a homepage HTML document for any wp-block-column whose text
	 * contains one of our anchor keyword phrases, regardless of heading level,
	 * heading order, or where the artist name/title sits within it. Deliberately
	 * does NOT try to decide which heading is "the title" vs "the artist name" --
	 * your homepage's layout has changed enough over the years that guessing
	 * wrong silently is worse than staging everything for a human to sort out.
	 *
	 * @param string $html
	 * @return array[] List of matched blocks: ['headings' => [...], 'paragraphs' => [...], 'images' => [['url'=>,'caption'=>], ...], 'raw_html' => '...']
	 */
	protected function stage_spotlight_content( $html ) {
		if ( empty( $html ) ) {
			return array();
		}

		libxml_use_internal_errors( true );
		$dom = new DOMDocument();
		$dom->loadHTML( '<?xml encoding="utf-8" ?>' . $html );
		libxml_clear_errors();
		$xpath = new DOMXPath( $dom );

		// Find candidate "column-like" containers: anything with a class containing
		// wp-block-column, or -- if the theme/editor didn't use columns for a given
		// era -- fall back to the whole <body> as one big candidate.
		$columns = $xpath->query( "//*[contains(concat(' ', normalize-space(@class), ' '), ' wp-block-column ')]" );
		if ( 0 === $columns->length ) {
			$columns = $xpath->query( '//body' );
		}

		$matches      = array();
		$seen_ancestor = array();

		foreach ( $columns as $column ) {
			$text_lower = strtolower( $column->textContent );

			$is_match = false;
			foreach ( $this->keywords as $keyword ) {
				if ( false !== strpos( $text_lower, $keyword ) ) {
					$is_match = true;
					break;
				}
			}
			if ( ! $is_match ) {
				continue;
			}

			// Skip if this column is nested inside a column we already captured
			// (prevents double-counting when columns wrap columns).
			$is_nested = false;
			foreach ( $seen_ancestor as $ancestor ) {
				if ( $ancestor === $column || $this->node_contains( $ancestor, $column ) ) {
					$is_nested = true;
					break;
				}
			}
			if ( $is_nested ) {
				continue;
			}
			$seen_ancestor[] = $column;

			$headings     = array();
			$paragraphs   = array();
			$images       = array();
			$seen_figures = array();

			// Walk block-level nodes in document order so we can stop cleanly once
			// we hit the "Browse...Inventory Online" boilerplate that lives in the
			// same column as the real content on this site, rather than grabbing it
			// as one big undifferentiated bag.
			foreach ( $xpath->query( './/h1 | .//h2 | .//h3 | .//h4 | .//p | .//img', $column ) as $node ) {
				if ( in_array( $node->nodeName, array( 'h1', 'h2', 'h3', 'h4' ), true ) ) {
					$text  = trim( preg_replace( '/\s+/', ' ', $node->textContent ) );
					$lower = strtolower( $text );
					if ( false !== strpos( $lower, 'browse' ) && false !== strpos( $lower, 'inventory' ) ) {
						break; // reached the recurring shop-promo boilerplate -- stop here
					}
					if ( '' !== $text ) {
						$headings[] = $text;
					}
				} elseif ( 'p' === $node->nodeName ) {
					$text = trim( preg_replace( '/\s+/', ' ', $node->textContent ) );
					if ( '' !== $text ) {
						$paragraphs[] = $text;
					}
				} elseif ( 'img' === $node->nodeName ) {
					// Multiple <img> tags for the same picture (different sizes) often
					// share one <figure> ancestor -- only take the first per figure.
					$figure  = $this->closest_ancestor( $node, 'figure' );
					$caption = '';
					if ( $figure ) {
						$figure_key = spl_object_id( $figure );
						if ( isset( $seen_figures[ $figure_key ] ) ) {
							continue;
						}
						$seen_figures[ $figure_key ] = true;

						// Gutenberg's image caption block: <figcaption class="wp-element-caption">...
						$caption_nodes = $xpath->query( ".//figcaption[contains(concat(' ', normalize-space(@class), ' '), ' wp-element-caption ')]", $figure );
						if ( 0 === $caption_nodes->length ) {
							$caption_nodes = $xpath->query( './/figcaption', $figure ); // fallback: any figcaption at all
						}
						if ( $caption_nodes->length ) {
							$caption = trim( preg_replace( '/\s+/', ' ', $caption_nodes->item( 0 )->textContent ) );
						}
					}
					$src = $node->getAttribute( 'src' );
					if ( $src ) {
						$images[] = array( 'url' => $src, 'caption' => $caption );
					}
				}
			}

			$raw_html = $dom->saveHTML( $column );

			$matches[] = array(
				'headings'   => $headings,
				'paragraphs' => $paragraphs,
				'images'     => $images,
				'raw_html'   => $raw_html,
			);
		}

		return $matches;
	}

	/**
	 * @param DOMNode $ancestor
	 * @param DOMNode $node
	 * @return bool True if $node is a descendant of $ancestor.
	 */
	protected function node_contains( $ancestor, $node ) {
		$parent = $node->parentNode;
		while ( $parent ) {
			if ( $parent === $ancestor ) {
				return true;
			}
			$parent = $parent->parentNode;
		}
		return false;
	}

	/**
	 * @param DOMNode $node
	 * @param string  $tag_name
	 * @return DOMNode|null Nearest ancestor with this tag name, or null.
	 */
	protected function closest_ancestor( $node, $tag_name ) {
		$parent = $node->parentNode;
		while ( $parent ) {
			if ( XML_ELEMENT_NODE === $parent->nodeType && $tag_name === $parent->nodeName ) {
				return $parent;
			}
			$parent = $parent->parentNode;
		}
		return null;
	}

	/**
	 * Create (or update) a DRAFT Exhibition for this month containing everything
	 * that was found, staged for a human to read and turn into real fields.
	 * Never touches a month that's already PUBLISHED -- only drafts/none.
	 *
	 * @param string  $month
	 * @param array[] $matches Output of stage_spotlight_content().
	 * @return string Short status message for the log.
	 */
	protected function create_staging_draft( $month, $matches ) {
		$existing = get_posts( array(
			'post_type'      => 'paintchip_exhibition',
			'meta_key'       => '_pc_month',
			'meta_value'     => $month,
			'posts_per_page' => 1,
			'post_status'    => 'any',
		) );

		if ( $existing && 'publish' === $existing[0]->post_status ) {
			return 'skipped -- exhibition #' . $existing[0]->ID . ' already published for this month';
		}

		// Build a human-readable preview from all matched blocks (there's
		// occasionally more than one hit per page, e.g. an ArtAbout blurb AND a
		// separate spotlight column).
		$body_parts = array();
		$all_images = array(); // url => caption, deduped
		foreach ( $matches as $i => $match ) {
			$block_text  = "### Staged block " . ( $i + 1 ) . "\n";
			$block_text .= "Headings found: " . implode( ' | ', $match['headings'] ) . "\n\n";
			$block_text .= implode( "\n\n", $match['paragraphs'] );
			$body_parts[] = $block_text;
			foreach ( $match['images'] as $image ) {
				if ( ! isset( $all_images[ $image['url'] ] ) || '' === $all_images[ $image['url'] ] ) {
					$all_images[ $image['url'] ] = $image['caption'];
				}
			}
		}

		$postarr = array(
			'post_type'    => 'paintchip_exhibition',
			'post_title'   => '[NEEDS REVIEW] ' . paintchip_format_month_label( $month ),
			'post_content' => wpautop( implode( "\n\n---\n\n", $body_parts ) ),
			'post_status'  => 'draft',
		);

		if ( $existing ) {
			$postarr['ID'] = $existing[0]->ID;
			$exhibition_id = wp_update_post( $postarr );
		} else {
			$exhibition_id = wp_insert_post( $postarr );
		}

		if ( ! $exhibition_id || is_wp_error( $exhibition_id ) ) {
			return 'FAILED to save staging draft';
		}

		update_post_meta( $exhibition_id, '_pc_month', $month );
		update_post_meta( $exhibition_id, '_pc_needs_review', 1 );
		update_post_meta( $exhibition_id, '_pc_raw_scrape', implode( "\n\n<!-- next block -->\n\n", wp_list_pluck( $matches, 'raw_html' ) ) );

		// Resolve every image found to an attachment ID, using its detected
		// caption (e.g. from a Gutenberg "wp-element-caption" figcaption) as
		// the title -- falling back to "Untitled" if none was found. Prefers
		// an existing Media Library attachment over re-downloading (fast, no
		// network call, and avoids the same self-referential-request problem
		// we hit with Wayback timeouts).
		$staged_ids = array();
		foreach ( $all_images as $url => $caption ) {
			$attachment_id = paintchip_resolve_image_url_to_id( $url, $exhibition_id, $caption );
			if ( is_wp_error( $attachment_id ) ) {
				WP_CLI::warning( sprintf( '  image failed for %s: %s (%s)', $month, $url, $attachment_id->get_error_message() ) );
				continue;
			}
			$staged_ids[] = $attachment_id;
		}

		$staged_ids = array_values( array_unique( $staged_ids ) );
		if ( $staged_ids ) {
			update_post_meta( $exhibition_id, '_pc_staged_image_ids', implode( ',', $staged_ids ) );
		}

		if ( ! empty( $all_images ) && empty( $staged_ids ) ) {
			return sprintf( 'staged as DRAFT (exhibition #%d, %d image URL(s) found but ALL failed -- see warnings above) -- open in wp-admin', $exhibition_id, count( $all_images ) );
		}
		if ( empty( $all_images ) ) {
			return sprintf( 'staged as DRAFT (exhibition #%d, no images found) -- open in wp-admin to assign artist/title/month', $exhibition_id );
		}

		return sprintf( 'staged as DRAFT (exhibition #%d, %d/%d image(s) resolved -- see "Staged Images" in the editor)', $exhibition_id, count( $staged_ids ), count( $all_images ) );
	}

	protected function log( $month, $source, $names, $status ) {
		$this->log[] = compact( 'month', 'source', 'names', 'status' );
		WP_CLI::log( "  source: {$source} | names: {$names} | {$status}" );
	}

	protected function print_summary() {
		WP_CLI::log( "\n=== Backfill Summary ===" );
		if ( class_exists( 'WP_CLI\\Utils' ) ) {
			WP_CLI\Utils\format_items( 'table', $this->log, array( 'month', 'source', 'names', 'status' ) );
		}
		WP_CLI::success( 'Backfill pass complete. Everything was saved as a DRAFT -- review, add bios/socials/mediums, and publish.' );
	}
}

PaintChip_CLI_Backfill::register();
