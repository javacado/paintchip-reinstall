<?php
/**
 * Shortcode
 * 
 * @package Product Categories Designs for WooCommerce 
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

function pcdfwoo_product_categories_slider( $atts, $content = null ) {

	// SiteOrigin Page Builder Gutenberg Block Tweak - Do not Display Preview
	if( isset( $_POST['action'] ) && ( $_POST['action'] == 'so_panels_layout_block_preview' || $_POST['action'] == 'so_panels_builder_content_json' ) ) {
		return '[wpos_product_categories_slider]';
	}

	// Divi Frontend Builder - Do not Display Preview
	if( function_exists( 'et_core_is_fb_enabled' ) && isset( $_POST['is_fb_preview'] ) && isset( $_POST['shortcode'] ) ) {
		return '<div class="pcdfwoo-builder-shrt-prev">
					<div class="pcdfwoo-builder-shrt-title"><span>'.esc_html__('Product Categories Slider - Shortcode', 'product-categories-designs-for-woocommerce').'</span></div>
					wpos_product_categories_slider
				</div>';
	}

	// Fusion Builder Live Editor - Do not Display Preview
	if( class_exists( 'FusionBuilder' ) && (( isset( $_GET['builder'] ) && $_GET['builder'] == 'true' ) || ( isset( $_POST['action'] ) && $_POST['action'] == 'get_shortcode_render' )) ) {
		return '<div class="pcdfwoo-builder-shrt-prev">
					<div class="pcdfwoo-builder-shrt-title"><span>'.esc_html__('Product Categories Grid - Shortcode', 'product-categories-designs-for-woocommerce').'</span></div>
					wpos_product_categories_slider
				</div>';
	}

	$atts = extract( shortcode_atts(array(
		'orderby'			=> 'name',
		'order'				=> 'ASC',
		'design'			=> 'design-1',
		'ids'				=> array(),
		'loop' 				=> 'true',
		'dots'				=> 'true',
		'arrows'			=> 'true',
		'autoplay'			=> 'true',
		'autoplay_interval' => 3000,
		'speed'				=> 600,
		'height'			=> 300,
		'number'			=> 0,
		'hide_empty'		=> 1,
		'parent'			=> '',
		'slidestoshow'		=> 3,
		'slidestoscroll' 	=> 1,
		'extra_class'		=> '',
		'className'			=> '',
		'align'				=> '',
		'dev_param_1'		=> '',
		'dev_param_2'		=> '',
	), $atts, 'wpos_product_categories_slider') );

	$number				= pcdfwoo_clean_number( $number, 0 );
	$slidestoshow		= pcdfwoo_clean_number( $slidestoshow, 3 );
	$slidestoscroll		= pcdfwoo_clean_number( $slidestoscroll, 1 );
	$autoplay_interval 	= pcdfwoo_clean_number( $autoplay_interval, 3000 );
	$speed 				= pcdfwoo_clean_number( $speed, 300 );
	$height 			= pcdfwoo_clean_number( $height, 300 );
	$height_css			= ! empty( $height ) ? "height:{$height}px;" : '';
	$design				= ( $design == 'design-2' ) 					? $design 					: 'design-1';
	$ids 				= ! empty( $ids )								? explode( ',', $ids )		: array();
	$order				= ( strtolower( $order ) == 'asc' )				? 'ASC'						: 'DESC';
	$orderby			= ! empty( $orderby )							? $orderby					: 'name';
	$parent				= ( isset( $parent ) )							? $parent					: '';
	$hide_empty 		= ( $hide_empty == true || $hide_empty == 1 )	? 1 						: 0;
	$loop 				= ( $loop == 'false' ) 							? false 					: true;
	$dots 				= ( $dots == 'false' ) 							? false 					: true;
	$arrows 			= ( $arrows == 'false' ) 						? false 					: true;
	$autoplay 			= ( $autoplay == 'false' ) 						? false 					: true;
	$align				= ! empty( $align )								? 'align'.$align			: '';
	$extra_class		= $extra_class .' '. $align .' '. $className;
	$extra_class		= pcdfwoo_sanitize_html_classes( $extra_class );

	// Slider configuration
	$slider_conf = compact('slidestoshow', 'slidestoscroll', 'loop', 'dots', 'arrows', 'autoplay', 'autoplay_interval', 'speed');

	// If needwd
	wp_enqueue_script( 'wpos-slick-jquery' );
	wp_enqueue_script( 'pcdfwoo-public-jquery' );

	// Some Variable
	$unique	= pcdfwoo_get_unique();

	// get terms and workaround WP bug with parents/pad counts
	$args = array(
		'number'		=> $number,
		'orderby'		=> $orderby,
		'order'			=> $order,
		'hide_empty'	=> $hide_empty,
		'include'		=> $ids,
		'parent'		=> $parent,
		'pad_counts'	=> true,
	);

	// Get Terms
	$product_categories = get_terms( 'product_cat', $args );

	ob_start();

	if ( $product_categories ) { ?>
		<div class="pcdfwoo-product-cat-wrp pcdfwoo_woocommerce_slider pcdfwoo-clearfix <?php echo esc_attr( $design.' '.$extra_class ); ?>">
			<div class="pcdfwoo-product-cat pcdfwoo-product-cat-slider" id="pcdfwoo-<?php echo esc_attr( $unique ); ?>" data-conf="<?php echo htmlspecialchars( json_encode( $slider_conf ) ); ?>">

				<?php foreach ( $product_categories as $category ) {

					$cat_thumb_id	= get_term_meta( $category->term_id, 'thumbnail_id', true );
					$cat_thumb_url	= wp_get_attachment_image_src( $cat_thumb_id, 'medium_large' );
					$term_link		= get_term_link( $category, 'product_cat' );
					$cat_thumb_link = ! empty( $cat_thumb_url[0] ) ? $cat_thumb_url[0] : wc_placeholder_img_src();
				?>

				<div class="pcdfwoo-product-slider">
					<div class="pcdfwoo-product-cat_inner" style="<?php echo esc_attr( $height_css ); ?>">
						<a href="<?php echo esc_url( $term_link ); ?>">
						<?php if( ! empty( $cat_thumb_link ) ) { ?>
							<img src="<?php echo esc_url( $cat_thumb_link ); ?>" alt="<?php echo esc_attr( $category->name ); ?>" />
						<?php } ?>
							<div class="pcdfwoo_title"><?php echo wp_kses_post( $category->name ); ?> <span class="pcdfwoo_count"><?php echo esc_html( $category->count ); ?> </span></div>
						</a>
					</div>
				</div>
				<?php } ?>
			</div>
		</div>
	<?php }

	$content .= ob_get_clean();
	return $content;
}
add_shortcode( 'wpos_product_categories_slider', 'pcdfwoo_product_categories_slider' );