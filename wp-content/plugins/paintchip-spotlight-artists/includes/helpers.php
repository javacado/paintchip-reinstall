<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Version an enqueued asset by its file's modification time, so every time
 * a file is updated on the server (e.g. via scp + unzip) browsers/proxies/
 * CDNs are forced to fetch the new version instead of serving a stale cached
 * copy under the same "?ver=1.0.0" URL.
 *
 * @param string $relative_path e.g. "assets/js/admin-exhibition.js"
 * @return string
 */
function paintchip_asset_version( $relative_path ) {
	$file = PAINTCHIP_SPOTLIGHT_DIR . ltrim( $relative_path, '/' );
	return file_exists( $file ) ? (string) filemtime( $file ) : PAINTCHIP_SPOTLIGHT_VERSION;
}

/**
 * Get artist post objects attached to an exhibition, in the order they were selected.
 *
 * @param int $exhibition_id
 * @return WP_Post[]
 */
function paintchip_get_exhibition_artists( $exhibition_id ) {
	$ids = get_post_meta( $exhibition_id, '_pc_artist_ids', true );
	if ( empty( $ids ) || ! is_array( $ids ) ) {
		return array();
	}
	$artists = array();
	foreach ( $ids as $id ) {
		$post = get_post( $id );
		if ( $post && 'paintchip_artist' === $post->post_type ) {
			$artists[] = $post;
		}
	}
	return $artists;
}

/**
 * Human readable "Jessica Bergler" or "Jessica Bergler and Sam Lee" or "Jessica Bergler, Sam Lee, and Alex Kim".
 *
 * @param WP_Post[] $artists
 * @return string
 */
function paintchip_format_artist_names( $artists ) {
	$names = wp_list_pluck( $artists, 'post_title' );
	$count = count( $names );

	if ( 0 === $count ) {
		return '';
	}
	if ( 1 === $count ) {
		return $names[0];
	}
	if ( 2 === $count ) {
		return $names[0] . ' and ' . $names[1];
	}
	$last = array_pop( $names );
	return implode( ', ', $names ) . ', and ' . $last;
}

/**
 * Build the exhibition image. Priority:
 *  1. An explicit image chosen directly on the exhibition (_pc_image_id)
 *  2. If "use artwork from artist(s)" is enabled, the first image from the
 *     _pc_exhibition_artist_images map -- which only ever contains the images
 *     entered/tagged for THIS specific exhibition, never an artist's full
 *     historical gallery from other past shows.
 *  3. (Legacy fallback, for exhibitions created before this map existed) the
 *     first attached artist's featured image or gallery.
 *
 * @param int $exhibition_id
 * @return int|false Attachment ID or false if nothing available.
 */
function paintchip_get_exhibition_image_id( $exhibition_id ) {
	$image_id = get_post_meta( $exhibition_id, '_pc_image_id', true );
	if ( $image_id ) {
		return (int) $image_id;
	}

	if ( get_post_meta( $exhibition_id, '_pc_use_artist_artwork', true ) ) {
		$map = paintchip_get_exhibition_artist_images( $exhibition_id );
		foreach ( $map as $artist_id => $image_ids ) {
			if ( ! empty( $image_ids ) ) {
				return (int) $image_ids[0];
			}
		}
	}

	$artists = paintchip_get_exhibition_artists( $exhibition_id );
	foreach ( $artists as $artist ) {
		if ( has_post_thumbnail( $artist->ID ) ) {
			return (int) get_post_thumbnail_id( $artist->ID );
		}
		$gallery = paintchip_get_artist_gallery_ids( $artist->ID );
		if ( ! empty( $gallery ) ) {
			return (int) $gallery[0];
		}
	}

	return false;
}

/**
 * The images specifically tagged for this exhibition, per artist -- e.g. an
 * artist who has shown work in five past exhibitions will still only surface
 * the handful of images actually entered for THIS show.
 *
 * @param int $exhibition_id
 * @return array<int, int[]> artist_id => [attachment_id, ...]
 */
function paintchip_get_exhibition_artist_images( $exhibition_id ) {
	$raw = get_post_meta( $exhibition_id, '_pc_exhibition_artist_images', true );
	if ( empty( $raw ) ) {
		return array();
	}
	$map = json_decode( $raw, true );
	return is_array( $map ) ? $map : array();
}

/**
 * The 2nd Friday of a given month -- always computable, never manually entered.
 *
 * @param string $month_ym e.g. "2026-07"
 * @return string|null Y-m-d, or null if $month_ym is invalid.
 */
function paintchip_compute_second_friday( $month_ym ) {
	if ( ! $month_ym ) {
		return null;
	}
	$first_of_month = strtotime( $month_ym . '-01' );
	if ( ! $first_of_month ) {
		return null;
	}
	$first_friday = strtotime( 'first friday of ' . date( 'F Y', $first_of_month ), $first_of_month );
	$second_friday = strtotime( '+7 days', $first_friday );
	return date( 'Y-m-d', $second_friday );
}

/**
 * Resolve an image URL to a Media Library attachment ID, preferring an
 * existing attachment (fast, no network) and falling back to downloading it.
 * Shared by the CLI backfill and the manual "paste a URL" artist image rows.
 * Always sets the attachment's title explicitly (defaulting to "Untitled")
 * rather than relying on media_sideload_image's internal description handling.
 *
 * @param string $url
 * @param int    $parent_post_id Post to attach a newly-downloaded image to.
 * @param string $title          Title for the image; defaults to "Untitled".
 * @return int|WP_Error
 */
function paintchip_resolve_image_url_to_id( $url, $parent_post_id = 0, $title = '' ) {
	$title = '' !== trim( (string) $title ) ? trim( $title ) : 'Untitled';

	$existing_id = attachment_url_to_postid( $url );
	if ( $existing_id ) {
		wp_update_post( array( 'ID' => $existing_id, 'post_title' => $title ) );
		return $existing_id;
	}

	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';

	$attachment_id = media_sideload_image( $url, $parent_post_id, $title, 'id' );
	if ( ! is_wp_error( $attachment_id ) ) {
		wp_update_post( array( 'ID' => $attachment_id, 'post_title' => $title ) );
	}
	return $attachment_id;
}

/**
 * Wrap the first occurrence of each artist's name in already-rendered HTML
 * with a link to their Artist post. Best-effort (plain string/regex matching,
 * not a full DOM parse) -- fine for the simple prose this runs against, but
 * skips a name if it looks like it's already inside a link.
 *
 * @param string    $html
 * @param WP_Post[] $artists
 * @return string
 */
function paintchip_autolink_artist_names_in_html( $html, $artists ) {
	foreach ( $artists as $artist ) {
		$name = trim( $artist->post_title );
		if ( '' === $name ) {
			continue;
		}
		$permalink = get_permalink( $artist->ID );
		if ( ! $permalink ) {
			continue;
		}
		// Skip if this exact name already appears inside an <a>...</a> in the content.
		if ( preg_match( '/<a[^>]*>[^<]*' . preg_quote( $name, '/' ) . '[^<]*<\/a>/i', $html ) ) {
			continue;
		}
		$pattern = '/\b' . preg_quote( $name, '/' ) . '\b/';
		$link    = '<a href="' . esc_url( $permalink ) . '">' . esc_html( $name ) . '</a>';
		$html    = preg_replace( $pattern, $link, $html, 1 );
	}
	return $html;
}

/**
 * Render a gallery of the images submitted for this exhibition, captioned
 * "Artist Name, Image Title" -- pulled from the per-exhibition artist image
 * map, so it only ever shows what was actually entered for THIS show.
 *
 * @param int $exhibition_id
 * @return string HTML, or '' if there's nothing to show.
 */
function paintchip_render_exhibition_gallery( $exhibition_id ) {
	$map = paintchip_get_exhibition_artist_images( $exhibition_id );
	if ( empty( $map ) ) {
		return '';
	}

	$items = array();
	foreach ( $map as $artist_id => $image_ids ) {
		$artist      = get_post( $artist_id );
		$artist_name = $artist ? $artist->post_title : '';
		foreach ( (array) $image_ids as $image_id ) {
			$items[] = array( 'id' => (int) $image_id, 'artist_name' => $artist_name );
		}
	}
	if ( empty( $items ) ) {
		return '';
	}

	ob_start();
	?>
	<div class="paintchip-exhibition-gallery">
		<?php foreach ( $items as $item ) :
			$title = trim( get_the_title( $item['id'] ) );
			if ( '' === $title || 'Untitled' === $title ) {
				$caption = '';
			} else {
				$caption = $item['artist_name'] ? $item['artist_name'] . ', ' . $title : $title;
			}
			$full_url = wp_get_attachment_image_url( $item['id'], 'full' );
			?>
			<figure class="paintchip-gallery-item">
				<a href="<?php echo esc_url( $full_url ); ?>" class="paintchip-lightbox-trigger" data-caption="<?php echo esc_attr( $caption ); ?>">
					<?php echo wp_get_attachment_image( $item['id'], 'medium' ); ?>
				</a>
				<?php if ( $caption ) : ?>
					<figcaption><?php echo esc_html( $caption ); ?></figcaption>
				<?php endif; ?>
			</figure>
		<?php endforeach; ?>
	</div>
	<?php
	return ob_get_clean();
}

/**
 * Gallery attachment IDs for an artist ("images of work").
 *
 * @param int $artist_id
 * @return int[]
 */
function paintchip_get_artist_gallery_ids( $artist_id ) {
	$raw = get_post_meta( $artist_id, '_pc_gallery_ids', true );
	if ( empty( $raw ) ) {
		return array();
	}
	$ids = array_filter( array_map( 'intval', explode( ',', $raw ) ) );
	return array_values( $ids );
}

/**
 * Auto-generate an exhibition title from month + artist names, e.g. "July Spotlight Artist: Jessica Bergler".
 *
 * @param string    $month_ym e.g. "2026-07"
 * @param WP_Post[] $artists
 * @return string
 */
function paintchip_generate_exhibition_title( $month_ym, $artists ) {
	$label = 'Spotlight Artist';
	if ( count( $artists ) > 1 ) {
		$label = 'Spotlight Artists';
	}
	$month_name = '';
	if ( $month_ym ) {
		$timestamp = strtotime( $month_ym . '-01' );
		if ( $timestamp ) {
			$month_name = date_i18n( 'F', $timestamp );
		}
	}
	$names = paintchip_format_artist_names( $artists );
	$title = trim( $month_name . "'s " . $label );
	if ( $names ) {
		$title .= ': ' . $names;
	}
	return $title ? $title : 'Untitled Spotlight';
}

/**
 * Month select helper: value like "2026-07" -> "July 2026".
 *
 * @param string $month_ym
 * @return string
 */
function paintchip_format_month_label( $month_ym ) {
	if ( ! $month_ym ) {
		return '';
	}
	$timestamp = strtotime( $month_ym . '-01' );
	return $timestamp ? date_i18n( 'F Y', $timestamp ) : $month_ym;
}

/**
 * All unique image attachment IDs associated with an exhibition -- its
 * explicit image (if set) plus every image tagged to its artist(s) for THIS
 * show. Used to decide whether to show one large image or a full gallery:
 * it's very common for the "event image" and the sole artist image to be
 * the same file, and that should count as one image, not two.
 *
 * @param int $exhibition_id
 * @return int[]
 */
function paintchip_get_exhibition_unique_image_ids( $exhibition_id ) {
	$ids = array();

	$explicit_id = (int) get_post_meta( $exhibition_id, '_pc_image_id', true );
	if ( $explicit_id ) {
		$ids[] = $explicit_id;
	}

	foreach ( paintchip_get_exhibition_artist_images( $exhibition_id ) as $image_ids ) {
		foreach ( (array) $image_ids as $image_id ) {
			$ids[] = (int) $image_id;
		}
	}

	return array_values( array_unique( $ids ) );
}

/**
 * Caption for a single image in the context of an exhibition: "Artist, Title"
 * if the image is tagged to one of the exhibition's artists, otherwise just
 * the image's own title (may be blank).
 *
 * @param int $exhibition_id
 * @param int $image_id
 * @return string
 */
function paintchip_get_exhibition_image_caption( $exhibition_id, $image_id ) {
	$title = trim( get_the_title( $image_id ) );
	if ( '' === $title || 'Untitled' === $title ) {
		return ''; // no real caption entered -- don't show the artist name either
	}

	$map = paintchip_get_exhibition_artist_images( $exhibition_id );
	foreach ( $map as $artist_id => $image_ids ) {
		if ( in_array( (int) $image_id, array_map( 'intval', (array) $image_ids ), true ) ) {
			$artist = get_post( $artist_id );
			return $artist ? $artist->post_title . ', ' . $title : $title;
		}
	}
	return $title;
}

/**
 * All published Exhibitions that feature a given artist, most recent first.
 * Done as a PHP-side filter over each exhibition's _pc_artist_ids (rather
 * than a meta_query LIKE against the serialized array, which would risk
 * false-positive substring matches on IDs) -- fine at this site's scale.
 *
 * @param int $artist_id
 * @return WP_Post[]
 */
function paintchip_get_exhibitions_for_artist( $artist_id ) {
	$all = get_posts( array(
		'post_type'      => 'paintchip_exhibition',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'meta_key'       => '_pc_month',
		'orderby'        => 'meta_value',
		'order'          => 'DESC',
	) );

	$matches = array();
	foreach ( $all as $exhibition ) {
		$ids = get_post_meta( $exhibition->ID, '_pc_artist_ids', true );
		if ( is_array( $ids ) && in_array( (int) $artist_id, array_map( 'intval', $ids ), true ) ) {
			$matches[] = $exhibition;
		}
	}
	return $matches;
}

/**
 * Render a simple breadcrumb trail.
 *
 * @param array $items Each item: ['label' => string, 'url' => string|null]. A
 *                      null/empty url renders as plain (unlinked) text --
 *                      use that for the current page, last in the list.
 * @return string
 */
function paintchip_render_breadcrumb( $items ) {
	$parts = array();
	foreach ( $items as $item ) {
		if ( ! empty( $item['url'] ) ) {
			$parts[] = '<a href="' . esc_url( $item['url'] ) . '">' . esc_html( $item['label'] ) . '</a>';
		} else {
			$parts[] = '<span>' . esc_html( $item['label'] ) . '</span>';
		}
	}
	return '<nav class="paintchip-breadcrumb" aria-label="Breadcrumb">' . implode( ' <span class="paintchip-breadcrumb-sep">/</span> ', $parts ) . '</nav>';
}

/**
 * Distinct exhibition years, newest first, derived from each Exhibition's
 * _pc_month meta ("YYYY-MM").
 *
 * @return string[] e.g. ["2026", "2025", "2024"]
 */
function paintchip_get_exhibition_years() {
	global $wpdb;
	$months = $wpdb->get_col( $wpdb->prepare(
		"SELECT DISTINCT pm.meta_value FROM {$wpdb->postmeta} pm
		 INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
		 WHERE pm.meta_key = %s AND p.post_type = 'paintchip_exhibition' AND p.post_status = 'publish'",
		'_pc_month'
	) );

	$years = array();
	foreach ( $months as $month ) {
		if ( preg_match( '/^(\d{4})-/', $month, $matches ) ) {
			$years[ $matches[1] ] = true;
		}
	}
	krsort( $years );
	return array_keys( $years );
}
