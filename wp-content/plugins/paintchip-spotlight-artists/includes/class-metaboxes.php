<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PaintChip_Spotlight_MetaBoxes {

	public function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'register_meta_boxes' ) );
		add_action( 'save_post_paintchip_artist', array( $this, 'save_artist' ) );
		add_action( 'save_post_paintchip_exhibition', array( $this, 'save_exhibition' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
	}

	public function enqueue( $hook ) {
		global $post_type;
		if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
			return;
		}
		if ( ! in_array( $post_type, array( 'paintchip_artist', 'paintchip_exhibition' ), true ) ) {
			return;
		}
		wp_enqueue_media();
		wp_enqueue_style( 'paintchip-admin', PAINTCHIP_SPOTLIGHT_URL . 'assets/css/admin.css', array(), paintchip_asset_version( 'assets/css/admin.css' ) );

		if ( 'paintchip_artist' === $post_type ) {
			wp_enqueue_script( 'paintchip-artist-gallery', PAINTCHIP_SPOTLIGHT_URL . 'assets/js/admin-artist-gallery.js', array( 'jquery' ), paintchip_asset_version( 'assets/js/admin-artist-gallery.js' ), true );
		}

		if ( 'paintchip_exhibition' === $post_type ) {
			wp_enqueue_script( 'paintchip-exhibition-admin', PAINTCHIP_SPOTLIGHT_URL . 'assets/js/admin-exhibition.js', array( 'jquery' ), paintchip_asset_version( 'assets/js/admin-exhibition.js' ), true );

			$existing_artists = get_posts( array(
				'post_type'      => 'paintchip_artist',
				'posts_per_page' => -1,
				'post_status'    => array( 'publish', 'draft', 'pending' ),
				'orderby'        => 'title',
				'order'          => 'ASC',
				'fields'         => 'ids',
			) );
			$artist_list = array();
			foreach ( $existing_artists as $artist_id ) {
				$artist_list[] = array( 'id' => $artist_id, 'name' => get_the_title( $artist_id ) );
			}

			wp_localize_script( 'paintchip-exhibition-admin', 'PaintChipAdmin', array(
				'ajaxUrl'         => admin_url( 'admin-ajax.php' ),
				'nonce'           => wp_create_nonce( 'paintchip_search_artists' ),
				'existingArtists' => $artist_list,
			) );
		}
	}

	public function register_meta_boxes() {
		add_meta_box( 'paintchip_artist_details', 'Artist Details', array( $this, 'render_artist_metabox' ), 'paintchip_artist', 'normal', 'high' );
		add_meta_box( 'paintchip_artist_gallery', 'Images of Work', array( $this, 'render_artist_gallery_metabox' ), 'paintchip_artist', 'normal', 'default' );

		add_meta_box( 'paintchip_exhibition_details', 'Exhibition Details', array( $this, 'render_exhibition_metabox' ), 'paintchip_exhibition', 'normal', 'high' );
		add_meta_box( 'paintchip_exhibition_new_artists', 'Artists for This Exhibition', array( $this, 'render_exhibition_new_artists_metabox' ), 'paintchip_exhibition', 'normal', 'default' );
		add_meta_box( 'paintchip_exhibition_artists', 'Link an Existing Artist', array( $this, 'render_exhibition_artists_metabox' ), 'paintchip_exhibition', 'side', 'default' );
		add_meta_box( 'paintchip_exhibition_image', 'Exhibition Image', array( $this, 'render_exhibition_image_metabox' ), 'paintchip_exhibition', 'side', 'default' );

		// The editor box handles "bio" (artist) and "description" (exhibition) already,
		// but relabel it so it's clear in the admin UI.
		add_filter( 'gettext', array( $this, 'relabel_editor_box' ), 10, 2 );
	}

	public function relabel_editor_box( $translation, $text ) {
		global $post_type;
		if ( 'Content' === $text && 'paintchip_artist' === $post_type ) {
			return 'Bio';
		}
		if ( 'Content' === $text && 'paintchip_exhibition' === $post_type ) {
			return 'Description';
		}
		return $translation;
	}

	/* ---------------------------------------------------------------- */
	/* Artist                                                            */
	/* ---------------------------------------------------------------- */

	public function render_artist_metabox( $post ) {
		wp_nonce_field( 'paintchip_save_artist', 'paintchip_artist_nonce' );
		$mediums    = get_post_meta( $post->ID, '_pc_mediums', true );
		$website    = get_post_meta( $post->ID, '_pc_website', true );
		$instagram  = get_post_meta( $post->ID, '_pc_instagram', true );
		$facebook   = get_post_meta( $post->ID, '_pc_facebook', true );
		?>
		<p>
			<label for="pc_mediums"><strong>Mediums</strong> <span style="font-weight:normal;">(comma separated, e.g. "Oil, Acrylic, Watercolor")</span></label><br>
			<input type="text" id="pc_mediums" name="pc_mediums" class="widefat" value="<?php echo esc_attr( $mediums ); ?>">
		</p>
		<p>
			<label for="pc_website"><strong>Website</strong></label><br>
			<input type="url" id="pc_website" name="pc_website" class="widefat" value="<?php echo esc_attr( $website ); ?>" placeholder="https://">
		</p>
		<p>
			<label for="pc_instagram"><strong>Instagram Handle</strong></label><br>
			<input type="text" id="pc_instagram" name="pc_instagram" class="widefat" value="<?php echo esc_attr( $instagram ); ?>" placeholder="@handle">
		</p>
		<p>
			<label for="pc_facebook"><strong>Facebook URL</strong></label><br>
			<input type="url" id="pc_facebook" name="pc_facebook" class="widefat" value="<?php echo esc_attr( $facebook ); ?>" placeholder="https://facebook.com/...">
		</p>
		<p class="description">Use the <strong>Featured Image</strong> box for the artist's portrait/headshot, and the <strong>Bio</strong> editor above for their biography.</p>
		<?php
	}

	public function render_artist_gallery_metabox( $post ) {
		$ids = paintchip_get_artist_gallery_ids( $post->ID );
		?>
		<div id="pc-gallery-wrap" data-ids="<?php echo esc_attr( implode( ',', $ids ) ); ?>">
			<ul id="pc-gallery-list" class="pc-gallery-list">
				<?php foreach ( $ids as $id ) : ?>
					<li data-id="<?php echo esc_attr( $id ); ?>">
						<?php echo wp_get_attachment_image( $id, 'thumbnail' ); ?>
						<a href="#" class="pc-gallery-remove">&times;</a>
					</li>
				<?php endforeach; ?>
			</ul>
			<input type="hidden" name="pc_gallery_ids" id="pc_gallery_ids" value="<?php echo esc_attr( implode( ',', $ids ) ); ?>">
			<button type="button" class="button" id="pc-gallery-add">Add Images</button>
		</div>
		<?php
	}

	public function save_artist( $post_id ) {
		if ( ! isset( $_POST['paintchip_artist_nonce'] ) || ! wp_verify_nonce( $_POST['paintchip_artist_nonce'], 'paintchip_save_artist' ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$fields = array(
			'_pc_mediums'   => isset( $_POST['pc_mediums'] ) ? sanitize_text_field( wp_unslash( $_POST['pc_mediums'] ) ) : '',
			'_pc_website'   => isset( $_POST['pc_website'] ) ? esc_url_raw( wp_unslash( $_POST['pc_website'] ) ) : '',
			'_pc_instagram' => isset( $_POST['pc_instagram'] ) ? sanitize_text_field( wp_unslash( $_POST['pc_instagram'] ) ) : '',
			'_pc_facebook'  => isset( $_POST['pc_facebook'] ) ? esc_url_raw( wp_unslash( $_POST['pc_facebook'] ) ) : '',
		);
		foreach ( $fields as $key => $value ) {
			update_post_meta( $post_id, $key, $value );
		}

		if ( isset( $_POST['pc_gallery_ids'] ) ) {
			$ids = array_filter( array_map( 'intval', explode( ',', sanitize_text_field( wp_unslash( $_POST['pc_gallery_ids'] ) ) ) ) );
			update_post_meta( $post_id, '_pc_gallery_ids', implode( ',', $ids ) );
		}
	}

	/* ---------------------------------------------------------------- */
	/* Exhibition                                                        */
	/* ---------------------------------------------------------------- */

	public function render_exhibition_metabox( $post ) {
		wp_nonce_field( 'paintchip_save_exhibition', 'paintchip_exhibition_nonce' );
		$month           = get_post_meta( $post->ID, '_pc_month', true );
		$second_friday   = get_post_meta( $post->ID, '_pc_second_friday', true );
		$event_date      = get_post_meta( $post->ID, '_pc_event_date', true );
		$event_time      = get_post_meta( $post->ID, '_pc_event_time', true );
		if ( '' === $event_time ) {
			$event_time = '6-8:30pm';
		}
		if ( '' === $second_friday ) {
			$second_friday = 'yes';
		}
		$computed_date = paintchip_compute_second_friday( $month );
		?>
		<p>
			<label for="pc_month"><strong>Exhibition Month</strong></label><br>
			<input type="month" id="pc_month" name="pc_month" value="<?php echo esc_attr( $month ); ?>">
		</p>
		<p>
			<strong>2nd Friday ArtAbout?</strong><br>
			<label><input type="radio" name="pc_second_friday" value="yes" id="pc_second_friday_yes" <?php checked( $second_friday, 'yes' ); ?>> Yes</label>
			&nbsp;&nbsp;
			<label><input type="radio" name="pc_second_friday" value="no" id="pc_second_friday_no" <?php checked( $second_friday, 'no' ); ?>> No</label>
		</p>
		<p>
			<label for="pc_event_date"><strong>Event date</strong></label><br>
			<input type="date" id="pc_event_date" name="pc_event_date" value="<?php echo esc_attr( $event_date ? $event_date : $computed_date ); ?>" <?php echo 'yes' === $second_friday ? 'readonly' : ''; ?>>
			<span class="description" id="pc_second_friday_note" style="<?php echo 'yes' === $second_friday ? '' : 'display:none;'; ?>">auto-set to the 2nd Friday of the exhibition month</span>
		</p>
		<p>
			<label for="pc_event_time"><strong>Event time</strong> <span style="font-weight:normal;">(e.g. "5-6 PM" or "6-8:30pm")</span></label><br>
			<input type="text" id="pc_event_time" name="pc_event_time" value="<?php echo esc_attr( $event_time ); ?>">
		</p>
		<p class="description">Use the <strong>Description</strong> editor above for the show's write-up.</p>
		<?php
		$raw_scrape = get_post_meta( $post->ID, '_pc_raw_scrape', true );
		$staged_ids = get_post_meta( $post->ID, '_pc_staged_image_ids', true );
		$is_published = 'publish' === get_post_status( $post->ID );

		if ( ! $is_published && $staged_ids ) :
			$ids = array_filter( array_map( 'intval', explode( ',', $staged_ids ) ) );
			?>
			<hr>
			<p><strong>Staged Images</strong> (found during backfill). Click a thumbnail to set it as the Exhibition Image, or copy a URL below into an artist's image field in the box below.</p>
			<ul class="pc-staged-images" style="display:flex;flex-wrap:wrap;gap:12px;list-style:none;margin:0;padding:0;">
				<?php foreach ( $ids as $id ) :
					$url = wp_get_attachment_url( $id );
					?>
					<li style="max-width:120px;">
						<a href="#" class="pc-staged-image-select" data-id="<?php echo esc_attr( $id ); ?>">
							<?php echo wp_get_attachment_image( $id, 'thumbnail', false, array( 'style' => 'width:100px;height:100px;object-fit:cover;border:2px solid transparent;display:block;' ) ); ?>
						</a>
						<input type="text" readonly value="<?php echo esc_attr( $url ); ?>" onclick="this.select();" style="width:100px;font-size:10px;padding:2px;margin-top:2px;">
					</li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
		<?php if ( ! $is_published && $raw_scrape ) : ?>
			<hr>
			<p><strong>&#9888; Backfilled from an old page scrape.</strong> This box is reference only -- it's not saved anywhere else and won't display on the front end. Read it, fill in the real fields above and the Artists box below, then remove the "[NEEDS REVIEW]" prefix from the title when done.</p>
			<textarea readonly rows="10" class="widefat" style="font-family:monospace;font-size:12px;"><?php echo esc_textarea( $raw_scrape ); ?></textarea>
			<?php
		endif;
	}

	public function render_exhibition_artists_metabox( $post ) {
		$artists = paintchip_get_exhibition_artists( $post->ID );
		?>
		<div id="pc-artist-picker">
			<p class="description">Search for an artist who's already in the database (from a past show) to add them to this one too.</p>
			<input type="text" id="pc-artist-search" class="widefat" placeholder="Search artists by name&hellip;">
			<ul id="pc-artist-results" class="pc-artist-results"></ul>
			<ul id="pc-artist-selected" class="pc-artist-selected">
				<?php foreach ( $artists as $artist ) : ?>
					<li data-id="<?php echo esc_attr( $artist->ID ); ?>">
						<?php echo esc_html( $artist->post_title ); ?>
						<a href="#" class="pc-artist-remove">&times;</a>
					</li>
				<?php endforeach; ?>
			</ul>
			<input type="hidden" name="pc_artist_ids" id="pc_artist_ids" value="<?php echo esc_attr( implode( ',', wp_list_pluck( $artists, 'ID' ) ) ); ?>">
		</div>
		<?php
	}

	/**
	 * Repeatable "brand new artist" form: name + mediums/website/instagram/
	 * facebook/bio + a repeatable list of image URL+title rows. Submitting
	 * the Exhibition creates one Artist post per filled-in row and tags the
	 * images entered here as belonging to THIS exhibition specifically.
	 */
	public function render_exhibition_new_artists_metabox( $post ) {
		$staged_raw = get_post_meta( $post->ID, '_pc_staged_image_ids', true );
		$staged_ids = $staged_raw ? array_filter( array_map( 'intval', explode( ',', $staged_raw ) ) ) : array();

		// Images already tagged to any artist for this exhibition should also
		// be selectable/visible here, even if they didn't come from a backfill
		// (e.g. uploaded directly in a previous edit) -- union both sources.
		$artist_image_map = paintchip_get_exhibition_artist_images( $post->ID );
		$tagged_ids       = array();
		foreach ( $artist_image_map as $ids ) {
			$tagged_ids = array_merge( $tagged_ids, (array) $ids );
		}
		$available_ids = array_values( array_unique( array_merge( $staged_ids, $tagged_ids ) ) );

		$existing_artists = paintchip_get_exhibition_artists( $post->ID );
		?>
		<p class="description">Artists already linked to this exhibition are pre-filled below, with their currently-assigned images checked -- edit a title field to change that image's caption. Add brand-new artists with "+ Add another artist"; each becomes its own Artist record when you save.</p>
		<p>
			<button type="button" class="button" id="pc-upload-exhibition-image">Upload an image for this exhibition</button>
			<span class="description">Uploads once, then shows up as a checkbox option below for whichever artist(s) it belongs to.</span>
		</p>
		<div id="pc-new-artists" data-next-index="<?php echo esc_attr( max( 1, count( $existing_artists ) ) ); ?>">
			<?php if ( $existing_artists ) : ?>
				<?php foreach ( $existing_artists as $i => $artist ) : ?>
					<div class="pc-new-artist" data-index="<?php echo esc_attr( $i ); ?>">
						<?php echo $this->new_artist_row_html( $i, $available_ids, isset( $artist_image_map[ $artist->ID ] ) ? $artist_image_map[ $artist->ID ] : array(), $artist ); ?>
					</div>
				<?php endforeach; ?>
			<?php else : ?>
				<div class="pc-new-artist" data-index="0">
					<?php echo $this->new_artist_row_html( 0, $available_ids ); ?>
				</div>
			<?php endif; ?>
		</div>
		<p><button type="button" class="button button-secondary" id="pc-add-artist">+ Add another artist</button></p>
		<template id="pc-new-artist-template"><?php echo $this->new_artist_row_html( '__INDEX__', $available_ids ); ?></template>
		<?php
	}

	/**
	 * Markup for one artist row in the repeater. $index is either an integer
	 * (server-rendered) or the literal string "__INDEX__" (client-side template,
	 * swapped out by JS when a new row is added).
	 *
	 * @param int|string   $index
	 * @param int[]        $available_ids   All image IDs offered as checkbox options.
	 * @param int[]        $checked_ids     Which of those should render pre-checked for this row.
	 * @param WP_Post|null $existing_artist If set, pre-fills this row as an already-linked
	 *                                      existing artist (name locked, meta fields hidden).
	 */
	protected function new_artist_row_html( $index, $available_ids = array(), $checked_ids = array(), $existing_artist = null ) {
		ob_start();
		$name_value    = $existing_artist ? $existing_artist->post_title : '';
		$existing_id   = $existing_artist ? $existing_artist->ID : '';
		$name_attrs    = $existing_artist ? 'readonly' : '';
		$meta_hidden   = $existing_artist ? 'style="display:none;"' : '';
		?>
		<div class="pc-new-artist-fields" style="border:1px solid #dcdcde;padding:12px;margin-bottom:12px;background:#fff;">
			<p>
				<button type="button" class="button-link pc-remove-artist" style="color:#b32d2e;float:right;">Remove this artist</button>
				<label><strong>Artist Name</strong></label><br>
				<input type="text" class="widefat pc-artist-name" name="pc_new_artists[<?php echo esc_attr( $index ); ?>][name]" placeholder="e.g. Jessica Bergler" value="<?php echo esc_attr( $name_value ); ?>" <?php echo $name_attrs; ?>>
				<input type="hidden" class="pc-existing-id" name="pc_new_artists[<?php echo esc_attr( $index ); ?>][existing_id]" value="<?php echo esc_attr( $existing_id ); ?>">
			</p>
			<p>
				<label><strong>Or pick an artist who's shown before</strong></label><br>
				<select class="widefat pc-existing-artist-select" data-preselect="<?php echo esc_attr( $existing_id ); ?>">
					<option value="">-- New artist --</option>
				</select>
			</p>
			<div class="pc-artist-meta-fields" <?php echo $meta_hidden; ?>>
				<p>
					<label><strong>Mediums</strong> <span style="font-weight:normal;">(comma separated)</span></label><br>
					<input type="text" class="widefat" name="pc_new_artists[<?php echo esc_attr( $index ); ?>][mediums]" placeholder="Oil, Acrylic, Watercolor">
				</p>
				<p style="display:flex;gap:10px;">
					<span style="flex:1;">
						<label><strong>Website</strong></label><br>
						<input type="url" class="widefat" name="pc_new_artists[<?php echo esc_attr( $index ); ?>][website]" placeholder="https://">
					</span>
					<span style="flex:1;">
						<label><strong>Instagram</strong></label><br>
						<input type="text" class="widefat" name="pc_new_artists[<?php echo esc_attr( $index ); ?>][instagram]" placeholder="@handle">
					</span>
					<span style="flex:1;">
						<label><strong>Facebook URL</strong></label><br>
						<input type="url" class="widefat" name="pc_new_artists[<?php echo esc_attr( $index ); ?>][facebook]" placeholder="https://facebook.com/...">
					</span>
				</p>
				<p>
					<label><strong>Bio</strong></label><br>
					<textarea class="widefat" rows="3" name="pc_new_artists[<?php echo esc_attr( $index ); ?>][bio]"></textarea>
				</p>
			</div>

			<div class="pc-staged-images-section" <?php echo empty( $available_ids ) ? 'style="display:none;"' : ''; ?>>
				<p><strong>Images -- check the ones that belong to this artist, edit the text field to change a caption</strong></p>
				<ul class="pc-staged-checklist" style="display:flex;flex-wrap:wrap;gap:10px;list-style:none;margin:0 0 12px;padding:0;">
					<?php foreach ( $available_ids as $image_id ) :
						$existing_title = get_the_title( $image_id );
						if ( '' === trim( $existing_title ) ) {
							$existing_title = 'Untitled';
						}
						$is_checked = in_array( (int) $image_id, array_map( 'intval', $checked_ids ), true );
						?>
						<li style="width:100px;text-align:center;">
							<label>
								<?php echo wp_get_attachment_image( $image_id, 'thumbnail', false, array( 'style' => 'width:100px;height:100px;object-fit:cover;display:block;' ) ); ?>
								<input type="checkbox" name="pc_new_artists[<?php echo esc_attr( $index ); ?>][staged_image_ids][]" value="<?php echo esc_attr( $image_id ); ?>" <?php checked( $is_checked ); ?>>
							</label>
							<input type="text" name="pc_new_artists[<?php echo esc_attr( $index ); ?>][staged_titles][<?php echo esc_attr( $image_id ); ?>]" value="<?php echo esc_attr( $existing_title ); ?>" style="width:100px;font-size:11px;padding:2px;">
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
			<div class="pc-staged-images-anchor"></div>

			<p><strong>Additional images (paste a URL)</strong></p>
			<?php // NOTE: this "additional images" section always stays, regardless of new-vs-existing-artist mode. ?>
			<div class="pc-artist-images">
				<div class="pc-artist-image-row" style="display:flex;gap:8px;margin-bottom:6px;">
					<input type="text" class="pc-image-url" name="pc_new_artists[<?php echo esc_attr( $index ); ?>][images][0][url]" placeholder="Image URL" style="flex:2;">
					<input type="text" name="pc_new_artists[<?php echo esc_attr( $index ); ?>][images][0][title]" value="Untitled" style="flex:1;">
					<button type="button" class="button-link pc-remove-image" style="color:#b32d2e;">&times;</button>
				</div>
			</div>
			<p><button type="button" class="button pc-add-image">+ Add another image for this artist</button></p>
		</div>
		<?php
		return ob_get_clean();
	}

	public function render_exhibition_image_metabox( $post ) {
		$image_id  = get_post_meta( $post->ID, '_pc_image_id', true );
		$use_art   = get_post_meta( $post->ID, '_pc_use_artist_artwork', true );
		$art_map   = paintchip_get_exhibition_artist_images( $post->ID );
		?>
		<div id="pc-exhibition-image-wrap" data-id="<?php echo esc_attr( $image_id ); ?>">
			<p>
				<label><input type="radio" name="pc_image_mode" value="specific" <?php checked( ! $use_art ); ?>> Choose a specific image</label><br>
				<label><input type="radio" name="pc_image_mode" value="artist" <?php checked( (bool) $use_art ); ?>> Use artwork from the artist(s) in this show</label>
			</p>
			<div id="pc-specific-image-fields" style="<?php echo $use_art ? 'display:none;' : ''; ?>">
				<div id="pc-exhibition-image-preview">
					<?php if ( $image_id ) : ?>
						<?php echo wp_get_attachment_image( $image_id, 'medium' ); ?>
					<?php endif; ?>
				</div>
				<input type="hidden" name="pc_image_id" id="pc_image_id" value="<?php echo esc_attr( $image_id ); ?>">
				<button type="button" class="button" id="pc-exhibition-image-select">Choose Image</button>
				<button type="button" class="button" id="pc-exhibition-image-remove" <?php echo $image_id ? '' : 'style="display:none;"'; ?>>Remove</button>
			</div>
			<div id="pc-artist-image-note" style="<?php echo $use_art ? '' : 'display:none;'; ?>">
				<?php if ( $art_map ) : ?>
					<p class="description">Will use the first image entered for the attached artist(s) in the box below (not their full historical gallery).</p>
					<?php foreach ( $art_map as $artist_id => $ids ) :
						if ( empty( $ids ) ) { continue; }
						?>
						<?php echo wp_get_attachment_image( $ids[0], 'thumbnail' ); ?>
					<?php endforeach; ?>
				<?php else : ?>
					<p class="description">No per-show artist images yet -- add some in the "Artists for This Exhibition" box below and save, then this will populate.</p>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}

	public function save_exhibition( $post_id ) {
		if ( ! isset( $_POST['paintchip_exhibition_nonce'] ) || ! wp_verify_nonce( $_POST['paintchip_exhibition_nonce'], 'paintchip_save_exhibition' ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		if ( isset( $_POST['pc_month'] ) ) {
			update_post_meta( $post_id, '_pc_month', sanitize_text_field( wp_unslash( $_POST['pc_month'] ) ) );
		}
		$second_friday = isset( $_POST['pc_second_friday'] ) && 'no' === $_POST['pc_second_friday'] ? 'no' : 'yes';
		update_post_meta( $post_id, '_pc_second_friday', $second_friday );

		if ( 'yes' === $second_friday ) {
			// Always derive from the month -- ignore whatever the (readonly) field posted.
			$computed = paintchip_compute_second_friday( sanitize_text_field( wp_unslash( $_POST['pc_month'] ?? '' ) ) );
			if ( $computed ) {
				update_post_meta( $post_id, '_pc_event_date', $computed );
			}
		} elseif ( isset( $_POST['pc_event_date'] ) ) {
			update_post_meta( $post_id, '_pc_event_date', sanitize_text_field( wp_unslash( $_POST['pc_event_date'] ) ) );
		}
		if ( isset( $_POST['pc_event_time'] ) ) {
			update_post_meta( $post_id, '_pc_event_time', sanitize_text_field( wp_unslash( $_POST['pc_event_time'] ) ) );
		}

		// Legacy composite string (kept in sync for the front-end block's sentence-building).
		$date_for_text = get_post_meta( $post_id, '_pc_event_date', true );
		$time_for_text = get_post_meta( $post_id, '_pc_event_time', true );
		if ( $date_for_text ) {
			$composite = date_i18n( 'F j', strtotime( $date_for_text ) );
			if ( $time_for_text ) {
				$composite .= ' from ' . $time_for_text;
			}
			update_post_meta( $post_id, '_pc_event_date_text', $composite );
		}

		$image_mode = isset( $_POST['pc_image_mode'] ) ? $_POST['pc_image_mode'] : 'specific';
		update_post_meta( $post_id, '_pc_use_artist_artwork', 'artist' === $image_mode ? 1 : 0 );
		if ( isset( $_POST['pc_image_id'] ) ) {
			update_post_meta( $post_id, '_pc_image_id', 'artist' === $image_mode ? 0 : intval( $_POST['pc_image_id'] ) );
		}

		// Existing-artist picker (sidebar search).
		$linked_existing_ids = array();
		if ( isset( $_POST['pc_artist_ids'] ) ) {
			$linked_existing_ids = array_filter( array_map( 'intval', explode( ',', sanitize_text_field( wp_unslash( $_POST['pc_artist_ids'] ) ) ) ) );
		}

		// Brand-new artist repeater rows -> create Artist posts + resolve their images.
		list( $new_artist_ids, $exhibition_artist_images ) = $this->process_new_artist_rows( $post_id );

		$all_artist_ids = array_values( array_unique( array_merge( $linked_existing_ids, $new_artist_ids ) ) );
		update_post_meta( $post_id, '_pc_artist_ids', $all_artist_ids );

		if ( $exhibition_artist_images ) {
			// Each artist row processed this save is authoritative for that
			// artist's image set on THIS exhibition -- replace, don't union,
			// so unchecking an image actually removes the association instead
			// of it silently sticking around forever.
			$existing_map = paintchip_get_exhibition_artist_images( $post_id );
			foreach ( $exhibition_artist_images as $artist_id => $ids ) {
				$existing_map[ $artist_id ] = array_values( array_unique( array_map( 'intval', $ids ) ) );
			}
			update_post_meta( $post_id, '_pc_exhibition_artist_images', wp_json_encode( $existing_map ) );
		}

		// Once someone has edited the title away from the staged placeholder,
		// consider it reviewed and stop flagging it in the admin list.
		$title = isset( $_POST['post_title'] ) ? wp_unslash( $_POST['post_title'] ) : '';
		if ( get_post_meta( $post_id, '_pc_needs_review', true ) && 0 !== strpos( $title, '[NEEDS REVIEW]' ) ) {
			delete_post_meta( $post_id, '_pc_needs_review' );
		}

		// Publishing the exhibition also publishes any artists attached to it
		// that are still drafts (typically ones just created via the repeater
		// above), so you don't have to go publish each one separately.
		if ( 'publish' === get_post_status( $post_id ) ) {
			foreach ( $all_artist_ids as $artist_id ) {
				if ( 'publish' !== get_post_status( $artist_id ) ) {
					wp_update_post( array( 'ID' => $artist_id, 'post_status' => 'publish' ) );
				}
			}
		}
	}

	/**
	 * Process $_POST['pc_new_artists'][n] rows: create an Artist post per row
	 * that has a name, resolve its image URLs to attachments, and build the
	 * per-exhibition image map so "use artwork from artist" only ever surfaces
	 * images entered for THIS show.
	 *
	 * @param int $exhibition_id
	 * @return array [ int[] $artist_ids, array<int,int[]> $exhibition_artist_images ]
	 */
	protected function process_new_artist_rows( $exhibition_id ) {
		if ( empty( $_POST['pc_new_artists'] ) || ! is_array( $_POST['pc_new_artists'] ) ) {
			return array( array(), array() );
		}

		$artist_ids                = array();
		$exhibition_artist_images  = array();

		foreach ( wp_unslash( $_POST['pc_new_artists'] ) as $row ) {
			$name        = isset( $row['name'] ) ? sanitize_text_field( $row['name'] ) : '';
			$existing_id = isset( $row['existing_id'] ) ? (int) $row['existing_id'] : 0;

			// An explicit dropdown pick always wins over name text, and avoids
			// creating a duplicate if two artists happen to share a name.
			if ( $existing_id && get_post( $existing_id ) ) {
				$artist_id = $existing_id;
			} else {
				if ( '' === $name ) {
					continue; // skip blank rows (e.g. an "add another" that was never filled in)
				}
				// Reuse an existing artist with an exact name match rather than duplicating.
				$existing  = get_page_by_title( $name, OBJECT, 'paintchip_artist' );
				$artist_id = $existing ? $existing->ID : 0;

				$postarr = array(
					'post_type'   => 'paintchip_artist',
					'post_title'  => $name,
					'post_status' => 'draft',
				);
				if ( ! empty( $row['bio'] ) ) {
					$postarr['post_content'] = wpautop( sanitize_textarea_field( $row['bio'] ) );
				}

				if ( $artist_id ) {
					$postarr['ID'] = $artist_id;
					wp_update_post( $postarr );
				} else {
					$artist_id = wp_insert_post( $postarr );
				}
			}
			if ( ! $artist_id || is_wp_error( $artist_id ) ) {
				continue;
			}

			if ( ! empty( $row['mediums'] ) && '' !== trim( $row['mediums'] ) ) {
				update_post_meta( $artist_id, '_pc_mediums', sanitize_text_field( $row['mediums'] ) );
			}
			if ( ! empty( $row['website'] ) && '' !== trim( $row['website'] ) ) {
				update_post_meta( $artist_id, '_pc_website', esc_url_raw( $row['website'] ) );
			}
			if ( ! empty( $row['instagram'] ) && '' !== trim( $row['instagram'] ) ) {
				update_post_meta( $artist_id, '_pc_instagram', sanitize_text_field( $row['instagram'] ) );
			}
			if ( ! empty( $row['facebook'] ) && '' !== trim( $row['facebook'] ) ) {
				update_post_meta( $artist_id, '_pc_facebook', esc_url_raw( $row['facebook'] ) );
			}

			// Checked staged images (from the backfill) -- already attachments,
			// so no resolution needed; just apply the title the admin set.
			$resolved_ids = array();
			if ( ! empty( $row['staged_image_ids'] ) && is_array( $row['staged_image_ids'] ) ) {
				$staged_titles = isset( $row['staged_titles'] ) && is_array( $row['staged_titles'] ) ? $row['staged_titles'] : array();
				foreach ( $row['staged_image_ids'] as $staged_id ) {
					$staged_id = (int) $staged_id;
					if ( ! $staged_id ) {
						continue;
					}
					$title = isset( $staged_titles[ $staged_id ] ) ? sanitize_text_field( $staged_titles[ $staged_id ] ) : '';
					wp_update_post( array( 'ID' => $staged_id, 'post_title' => '' !== trim( $title ) ? $title : 'Untitled' ) );
					$resolved_ids[] = $staged_id;
				}
			}

			// Resolve each pasted image URL to an attachment, tag it for THIS
			// exhibition+artist, and append it to the artist's overall gallery too.
			if ( ! empty( $row['images'] ) && is_array( $row['images'] ) ) {
				foreach ( $row['images'] as $image_row ) {
					$url = isset( $image_row['url'] ) ? esc_url_raw( trim( $image_row['url'] ) ) : '';
					if ( '' === $url ) {
						continue;
					}
					$title         = isset( $image_row['title'] ) ? sanitize_text_field( $image_row['title'] ) : '';
					$attachment_id = paintchip_resolve_image_url_to_id( $url, $exhibition_id, $title );
					if ( is_wp_error( $attachment_id ) ) {
						continue;
					}
					$resolved_ids[] = (int) $attachment_id;
				}
			}

			if ( $resolved_ids ) {
				$existing_gallery = paintchip_get_artist_gallery_ids( $artist_id );
				$merged_gallery   = array_values( array_unique( array_merge( $existing_gallery, $resolved_ids ) ) );
				update_post_meta( $artist_id, '_pc_gallery_ids', implode( ',', $merged_gallery ) );

				if ( ! has_post_thumbnail( $artist_id ) ) {
					set_post_thumbnail( $artist_id, $resolved_ids[0] );
				}
			}
			// Always record this artist's image set for this exhibition, even if
			// empty -- otherwise unchecking every box would leave the old
			// (now-stale) association in place instead of clearing it.
			$exhibition_artist_images[ $artist_id ] = $resolved_ids;

			$artist_ids[] = $artist_id;
		}

		return array( $artist_ids, $exhibition_artist_images );
	}
}
