<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PaintChip_Spotlight_Block {

	public function __construct() {
		add_action( 'init', array( $this, 'register_block' ) );
		add_shortcode( 'paintchip_spotlight', array( $this, 'render_shortcode' ) );
	}

	public function register_block() {
		wp_register_script(
			'paintchip-spotlight-block-editor',
			PAINTCHIP_SPOTLIGHT_URL . 'assets/js/block.js',
			array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-server-side-render', 'wp-i18n' ),
			paintchip_asset_version( 'assets/js/block.js' ),
			true
		);

		register_block_type_from_metadata( PAINTCHIP_SPOTLIGHT_DIR . 'block.json', array(
			'render_callback' => 'paintchip_spotlight_render_block',
		) );
	}

	/**
	 * [paintchip_spotlight] -- shows the current month's exhibition, same as
	 * the block. [paintchip_spotlight id="123"] pins it to a specific one.
	 * A plain shortcode is trivial to remove: just delete the text.
	 */
	public function render_shortcode( $atts ) {
		$atts = shortcode_atts( array( 'id' => 0 ), $atts, 'paintchip_spotlight' );
		return paintchip_spotlight_render_block( array( 'exhibitionId' => (int) $atts['id'] ) );
	}
}

/**
 * Find the exhibition to show: an explicitly picked one, or whichever exhibition's
 * _pc_month matches the current calendar month, falling back to the most recent one.
 *
 * @return WP_Post|null
 */
function paintchip_spotlight_get_display_exhibition( $exhibition_id = 0 ) {
	if ( $exhibition_id ) {
		$post = get_post( $exhibition_id );
		return ( $post && 'paintchip_exhibition' === $post->post_type ) ? $post : null;
	}

	$current_month = date_i18n( 'Y-m' );

	$query = new WP_Query( array(
		'post_type'      => 'paintchip_exhibition',
		'posts_per_page' => 1,
		'post_status'    => 'publish',
		'meta_key'       => '_pc_month',
		'meta_value'     => $current_month,
	) );

	if ( $query->have_posts() ) {
		return $query->posts[0];
	}

	// Fallback: the most recent exhibition at or before the current month --
	// i.e. "use the previous month's" rather than just whatever's newest overall
	// (which could theoretically be a future-dated one).
	$query = new WP_Query( array(
		'post_type'      => 'paintchip_exhibition',
		'posts_per_page' => 1,
		'post_status'    => 'publish',
		'meta_key'       => '_pc_month',
		'orderby'        => 'meta_value',
		'order'          => 'DESC',
		'meta_query'     => array(
			array(
				'key'     => '_pc_month',
				'value'   => $current_month,
				'compare' => '<=',
				'type'    => 'CHAR',
			),
		),
	) );

	return $query->have_posts() ? $query->posts[0] : null;
}

/**
 * Render callback for the paintchip/current-spotlight block.
 *
 * @param array $attributes
 * @return string
 */
function paintchip_spotlight_render_block( $attributes ) {
	$exhibition_id = isset( $attributes['exhibitionId'] ) ? (int) $attributes['exhibitionId'] : 0;
	$exhibition    = paintchip_spotlight_get_display_exhibition( $exhibition_id );

	if ( ! $exhibition ) {
		return is_user_logged_in() && current_user_can( 'edit_posts' )
			? '<p><em>No Spotlight exhibition found for this month yet. Create one under Spotlight Artists &rarr; Exhibitions.</em></p>'
			: '';
	}

	$artists = paintchip_get_exhibition_artists( $exhibition->ID );

	$content_html = apply_filters( 'the_content', $exhibition->post_content );
	$content_html = paintchip_autolink_artist_names_in_html( $content_html, $artists );

	$unique_image_ids = paintchip_get_exhibition_unique_image_ids( $exhibition->ID );
	$show_single_hero = count( $unique_image_ids ) <= 1;
	$hero_id          = $show_single_hero ? paintchip_get_exhibition_image_id( $exhibition->ID ) : 0;

	$archive_link = get_post_type_archive_link( 'paintchip_exhibition' );

	ob_start();
	?>
	<div class="paintchip-spotlight-block">
		<p class="paintchip-spotlight-series-link">
			<strong><a href="<?php echo esc_url( $archive_link ); ?>">The Paint Chip Spotlight Artist Series</a></strong>
		</p>

		<h2 class="wp-block-heading paintchip-spotlight-title"><?php echo esc_html( get_the_title( $exhibition->ID ) ); ?></h2>

		<div class="paintchip-spotlight-description">
			<?php echo wp_kses_post( $content_html ); ?>
		</div>

		<?php if ( $show_single_hero && $hero_id ) :
			$caption  = paintchip_get_exhibition_image_caption( $exhibition->ID, $hero_id );
			$full_url = wp_get_attachment_image_url( $hero_id, 'full' );
			?>
			<figure class="wp-block-image size-full paintchip-spotlight-image">
				<a href="<?php echo esc_url( $full_url ); ?>" class="paintchip-lightbox-trigger" data-caption="<?php echo esc_attr( $caption ); ?>">
					<?php echo wp_get_attachment_image( $hero_id, 'large', false, array( 'style' => 'width:100%;height:auto;' ) ); ?>
				</a>
				<?php if ( $caption ) : ?>
					<figcaption style="font-size:0.9em;color:#555;margin-top:0.4em;"><?php echo esc_html( $caption ); ?></figcaption>
				<?php endif; ?>
			</figure>
		<?php elseif ( ! $show_single_hero ) : ?>
			<?php echo paintchip_render_exhibition_gallery( $exhibition->ID ); // phpcs:ignore -- already escaped in the helper ?>
		<?php endif; ?>
	</div>
	<hr class="wp-block-separator has-css-opacity">
	<?php
	return ob_get_clean();
}
