<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
get_header();

while ( have_posts() ) :
	the_post();
	$artist_id = get_the_ID();
	$mediums   = get_post_meta( $artist_id, '_pc_mediums', true );
	$website   = get_post_meta( $artist_id, '_pc_website', true );
	$instagram = get_post_meta( $artist_id, '_pc_instagram', true );
	$facebook  = get_post_meta( $artist_id, '_pc_facebook', true );
	$gallery   = paintchip_get_artist_gallery_ids( $artist_id );

	// A gallery image is almost always also the featured image (we auto-set it
	// that way on save), so showing both would just duplicate one picture.
	// Show one large image only when there's nothing (or just one) to choose
	// from; otherwise skip the standalone image and show the full gallery.
	$show_single_hero = count( $gallery ) <= 1;
	$hero_id          = 0;
	if ( $show_single_hero ) {
		if ( ! empty( $gallery ) ) {
			$hero_id = $gallery[0];
		} elseif ( has_post_thumbnail() ) {
			$hero_id = get_post_thumbnail_id();
		}
	}

	$exhibitions  = paintchip_get_exhibitions_for_artist( $artist_id );
	$has_meta_info = $mediums || $website || $instagram || $facebook;
	?>
	<article class="paintchip-single-artist wp-block-group paintchip-content-width">

		<h1><?php the_title(); ?></h1>

		<?php if ( $has_meta_info ) : ?>
			<div class="paintchip-artist-meta-info">
				<?php if ( $mediums ) : ?>
					<p class="paintchip-artist-mediums" style="color:#666;"><?php echo esc_html( $mediums ); ?></p>
				<?php endif; ?>

				<p class="paintchip-artist-links">
					<?php if ( $website ) : ?>
						<a href="<?php echo esc_url( $website ); ?>" target="_blank" rel="noopener">Website</a>
					<?php endif; ?>
					<?php if ( $instagram ) : ?>
						&nbsp;&middot;&nbsp;<a href="https://instagram.com/<?php echo esc_attr( ltrim( $instagram, '@' ) ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $instagram ); ?></a>
					<?php endif; ?>
					<?php if ( $facebook ) : ?>
						&nbsp;&middot;&nbsp;<a href="<?php echo esc_url( $facebook ); ?>" target="_blank" rel="noopener">Facebook</a>
					<?php endif; ?>
				</p>
			</div>
		<?php endif; ?>

		<div class="paintchip-artist-bio">
			<?php the_content(); ?>
		</div>

		<?php if ( $show_single_hero && $hero_id ) :
			$hero_title = get_the_title( $hero_id );
			$full_url   = wp_get_attachment_image_url( $hero_id, 'full' );
			?>
			<figure class="paintchip-artist-hero" style="margin:1.5rem 0;">
				<a href="<?php echo esc_url( $full_url ); ?>" class="paintchip-lightbox-trigger" data-caption="<?php echo esc_attr( $hero_title ); ?>">
					<?php echo wp_get_attachment_image( $hero_id, 'large', false, array( 'style' => 'width:100%;height:auto;border-radius:4px;' ) ); ?>
				</a>
			</figure>
		<?php elseif ( ! $show_single_hero && $gallery ) : ?>
			<div class="paintchip-artist-gallery-2col">
				<?php foreach ( $gallery as $image_id ) :
					$title    = get_the_title( $image_id );
					$full_url = wp_get_attachment_image_url( $image_id, 'full' );
					?>
					<figure style="margin:0;">
						<a href="<?php echo esc_url( $full_url ); ?>" class="paintchip-lightbox-trigger" data-caption="<?php echo esc_attr( $title ); ?>">
							<?php echo wp_get_attachment_image( $image_id, 'medium', false, array( 'style' => 'width:100%;height:auto;display:block;' ) ); ?>
						</a>
						<?php if ( $title && 'Untitled' !== $title ) : ?>
							<figcaption style="font-size:0.85em;color:#555;margin-top:4px;"><?php echo esc_html( $title ); ?></figcaption>
						<?php endif; ?>
					</figure>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

		<?php if ( $exhibitions ) : ?>
			<hr>
			<h3>Events at The Paint Chip featuring <?php the_title(); ?></h3>
			<ul class="paintchip-artist-events">
				<?php foreach ( $exhibitions as $exhibition ) : ?>
					<li>
						<a href="<?php echo esc_url( get_permalink( $exhibition->ID ) ); ?>"><?php echo esc_html( get_the_title( $exhibition->ID ) ); ?></a>
						<span style="color:#666;"> &mdash; <?php echo esc_html( paintchip_format_month_label( get_post_meta( $exhibition->ID, '_pc_month', true ) ) ); ?></span>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>

		<p><a href="<?php echo esc_url( get_post_type_archive_link( 'paintchip_exhibition' ) ); ?>">&larr; Back to Art Events</a></p>

	</article>
	<?php
endwhile;

get_footer();
