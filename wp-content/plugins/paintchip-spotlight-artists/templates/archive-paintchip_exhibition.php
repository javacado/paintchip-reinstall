<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
get_header();

$selected_year = isset( $_GET['pc_year'] ) ? sanitize_text_field( wp_unslash( $_GET['pc_year'] ) ) : '';
$years         = paintchip_get_exhibition_years();

$all_artists = get_posts( array(
	'post_type'      => 'paintchip_artist',
	'post_status'    => 'publish',
	'posts_per_page' => -1,
	'orderby'        => 'title',
	'order'          => 'ASC',
) );

$page_title = 'The Paint Chip Spotlight Artist Series';
if ( $selected_year ) {
	$page_title .= ' - ' . $selected_year;
}
?>

<div class="paintchip-spotlight-archive wp-block-group paintchip-content-width">

	<h1><?php echo esc_html( $page_title ); ?></h1>
	<p><?php esc_html_e( 'Browse past and current featured artists from our monthly 2nd Friday ArtAbout exhibitions.', 'paintchip-spotlight' ); ?></p>

	<div class="pc-archive-controls" style="display:flex;gap:2rem;flex-wrap:wrap;margin-bottom:2rem;">
		<form method="get" style="margin:0;flex:1 1 calc(50% - 1rem);min-width:200px;">
			<label for="pc-year-select"><strong>Exhibitions by year</strong></label><br>
			<select name="pc_year" id="pc-year-select" onchange="this.form.submit()" style="width:100%;">
				<option value="">All years</option>
				<?php foreach ( $years as $year ) : ?>
					<option value="<?php echo esc_attr( $year ); ?>" <?php selected( $selected_year, $year ); ?>><?php echo esc_html( $year ); ?></option>
				<?php endforeach; ?>
			</select>
		</form>

		<div style="flex:1 1 calc(50% - 1rem);min-width:200px;">
			<label for="pc-artist-jump"><strong>Participating Artists</strong></label><br>
			<select id="pc-artist-jump" onchange="if (this.value) { window.location.href = this.value; }" style="width:100%;">
				<option value="">Jump to an artist&hellip;</option>
				<?php foreach ( $all_artists as $artist ) : ?>
					<option value="<?php echo esc_url( get_permalink( $artist->ID ) ); ?>"><?php echo esc_html( $artist->post_title ); ?></option>
				<?php endforeach; ?>
			</select>
		</div>
	</div>

	<?php if ( have_posts() ) : ?>
		<div class="pc-exhibition-grid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:1.5rem;">
			<?php while ( have_posts() ) : the_post();
				$exhibition_id   = get_the_ID();
				$artists         = paintchip_get_exhibition_artists( $exhibition_id );
				$month           = get_post_meta( $exhibition_id, '_pc_month', true );
				$image_id        = paintchip_get_exhibition_image_id( $exhibition_id );
				?>
				<article class="pc-exhibition-card">
					<a href="<?php the_permalink(); ?>">
						<?php if ( $image_id ) : ?>
							<?php echo wp_get_attachment_image( $image_id, 'medium', false, array( 'style' => 'width:100%;height:auto;aspect-ratio:1/1;object-fit:cover;' ) ); ?>
						<?php endif; ?>
						<h3 style="margin-bottom:0;"><?php the_title(); ?></h3>
					</a>
					<p style="margin-top:.25rem;color:#666;">
						<?php echo esc_html( paintchip_format_month_label( $month ) ); ?>
						<?php if ( $artists ) : ?>
							&middot; <?php echo esc_html( paintchip_format_artist_names( $artists ) ); ?>
						<?php endif; ?>
					</p>
				</article>
			<?php endwhile; ?>
		</div>

		<div class="pc-pagination" style="margin-top:2rem;">
			<?php the_posts_pagination(); ?>
		</div>

	<?php else : ?>
		<p><?php esc_html_e( 'No exhibitions found.', 'paintchip-spotlight' ); ?></p>
	<?php endif; ?>

</div>

<?php
get_footer();
