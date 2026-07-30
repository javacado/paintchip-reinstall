<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
get_header();

while ( have_posts() ) :
	the_post();
	$exhibition_id = get_the_ID();
	$artists       = paintchip_get_exhibition_artists( $exhibition_id );
	$month         = get_post_meta( $exhibition_id, '_pc_month', true );
	$event_date    = get_post_meta( $exhibition_id, '_pc_event_date', true );
	$event_time    = get_post_meta( $exhibition_id, '_pc_event_time', true );
	$second_friday = get_post_meta( $exhibition_id, '_pc_second_friday', true );

	$unique_image_ids = paintchip_get_exhibition_unique_image_ids( $exhibition_id );
	$show_single_hero = count( $unique_image_ids ) <= 1;
	$hero_id          = $show_single_hero ? paintchip_get_exhibition_image_id( $exhibition_id ) : 0;

	$content_html = apply_filters( 'the_content', get_the_content() );
	$content_html = paintchip_autolink_artist_names_in_html( $content_html, $artists );
	?>
	<article class="paintchip-single-exhibition wp-block-group paintchip-content-width">

		<h1><?php the_title(); ?></h1>

		<div class="paintchip-exhibition-description">
			<?php echo wp_kses_post( $content_html ); ?>
		</div>

		<p class="paintchip-exhibition-meta" style="color:#666;">
			<?php echo esc_html( paintchip_format_month_label( $month ) ); ?>
			<?php if ( 'no' !== $second_friday && $event_date ) : ?>
				&middot; 2nd Friday ArtAbout, <?php echo esc_html( date_i18n( 'F j', strtotime( $event_date ) ) ); ?><?php echo $event_time ? ' from ' . esc_html( $event_time ) : ''; ?>
			<?php endif; ?>
		</p>

		<?php if ( $artists ) : ?>
			<p class="paintchip-exhibition-artists">
				<strong>Featuring:</strong>
				<?php
				$links = array();
				foreach ( $artists as $artist ) {
					$links[] = '<a href="' . esc_url( get_permalink( $artist->ID ) ) . '">' . esc_html( $artist->post_title ) . '</a>';
				}
				echo wp_kses_post( implode( ', ', $links ) );
				?>
			</p>
		<?php endif; ?>

		<?php if ( $show_single_hero && $hero_id ) :
			$caption  = paintchip_get_exhibition_image_caption( $exhibition_id, $hero_id );
			$full_url = wp_get_attachment_image_url( $hero_id, 'full' );
			?>
			<figure class="paintchip-exhibition-hero">
				<a href="<?php echo esc_url( $full_url ); ?>" class="paintchip-lightbox-trigger" data-caption="<?php echo esc_attr( $caption ); ?>">
					<?php echo wp_get_attachment_image( $hero_id, 'large', false, array( 'style' => 'width:100%;height:auto;' ) ); ?>
				</a>
				<?php if ( $caption ) : ?>
					<figcaption style="font-size:0.9em;color:#555;margin-top:0.4em;"><?php echo esc_html( $caption ); ?></figcaption>
				<?php endif; ?>
			</figure>
		<?php elseif ( ! $show_single_hero ) : ?>
			<?php echo paintchip_render_exhibition_gallery( $exhibition_id ); // phpcs:ignore -- already escaped in the helper ?>
		<?php endif; ?>

		<hr>
		<hr>
		<p><a href="<?php echo esc_url( get_post_type_archive_link( 'paintchip_exhibition' ) ); ?>">&larr; Back to Paint Chip Art Events</a></p>

	</article>
	<?php
endwhile;

get_footer();
