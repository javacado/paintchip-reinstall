<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
get_header();

$artists = get_posts( array(
	'post_type'      => 'paintchip_artist',
	'post_status'    => 'publish',
	'posts_per_page' => -1,
	'orderby'        => 'title',
	'order'          => 'ASC',
) );
?>

<div class="paintchip-artist-archive wp-block-group paintchip-content-width">

	<h1>Artists</h1>

	<?php if ( $artists ) : ?>
		<ul class="paintchip-artist-index" style="columns:2;column-gap:2rem;list-style:none;margin:0;padding:0;">
			<?php foreach ( $artists as $artist ) : ?>
				<li style="break-inside:avoid;margin-bottom:0.5rem;">
					<a href="<?php echo esc_url( get_permalink( $artist->ID ) ); ?>"><?php echo esc_html( $artist->post_title ); ?></a>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php else : ?>
		<p>No artists found yet.</p>
	<?php endif; ?>

</div>

<?php
get_footer();
