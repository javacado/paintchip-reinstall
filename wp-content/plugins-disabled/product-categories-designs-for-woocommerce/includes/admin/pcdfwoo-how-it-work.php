<?php
/**
 * Pro Designs and Plugins Feed
 *
 * @package Product Categories Designs for WooCommerce
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Register plugin design page in admin menu
 * 
 * @since 1.0.0
 */
function pcdfwoo_register_design_page() {
	add_submenu_page( 'edit.php?post_type='.PCDFWOO_PRODUCT_POST_TYPE, __( 'How it works, our plugins and offers', 'product-categories-designs-for-woocommerce' ), __( 'Category Designs - How It Works', 'product-categories-designs-for-woocommerce' ), 'manage_options', 'pcdfwoo-designs', 'pcdfwoo_designs_page' );
}
add_action('admin_menu', 'pcdfwoo_register_design_page');

/**
 * Function to display plugin design HTML
 * 
 * @since 1.0.0
 */
function pcdfwoo_designs_page() { ?>

<div class="wrap pcdfwoo-wrap">
	<style type="text/css">
		.wpos-pro-box .hndle{background-color:#0073AA; color:#fff;}
		.wpos-pro-box.postbox{background:#dbf0fa none repeat scroll 0 0; border:1px solid #0073aa; color:#191e23;}
		.pcdfwoo-wrap .wpos-button-full{display:block; text-align:center; box-shadow:none; border-radius:0;}
		.pcdfwoo-shortcode-preview{background-color: #e7e7e7; font-weight: bold; padding: 2px 5px; display: inline-block; margin:0 0 2px 0;}
		.postbox-container .wpos-list li:before{font-family: dashicons; content: "\f139"; font-size:20px; color: #0073aa; vertical-align: middle;}
		.upgrade-to-pro{font-size:18px; text-align:center; margin-bottom:15px;}
		.wpos-copy-clipboard{-webkit-touch-callout: all; -webkit-user-select: all; -khtml-user-select: all; -moz-user-select: all; -ms-user-select: all; user-select: all;}
		.wpos-new-feature{ font-size: 10px; margin-left:3px; color: #fff; font-weight: bold; background-color: #03aa29; padding:1px 4px; font-style: normal; }
		.button-orange{background: #ff2700 !important;border-color: #ff2700 !important; font-weight: 600;}
	</style>

	<h2><?php esc_html_e( 'How It Works', 'product-categories-designs-for-woocommerce' ); ?></h2>
	<div id="poststuff">
		<div id="post-body" class="metabox-holder columns-2">

			<!--How it workd HTML -->
			<div id="post-body-content">
				<div class="meta-box-sortables">

					<div class="postbox">
						<div class="postbox-header">
							<h2 class="hndle">
								<span><?php esc_html_e( 'Need Support & Solutions?', 'product-categories-designs-for-woocommerce' ); ?></span>
							</h2>
						</div>
						<div class="inside">
							<table class="form-table">
								<tbody>
									<tr>
										<td>
											<p><?php esc_html_e('Boost design and best solution for your website.', 'product-categories-designs-for-woocommerce'); ?></p> <br/>
											<a class="button button-primary button-orange" href="<?php echo PCDFWOO_SITE_LINK; ?>/pricing/?utm_source=WP&utm_medium=Product-Category&utm_campaign=Check-Designs-Solutions" target="_blank"><?php esc_html_e('Grab Now', 'product-categories-designs-for-woocommerce'); ?></a>
										</td>
									</tr>
								</tbody>
							</table>
						</div><!-- .inside -->
					</div><!-- #general -->

					<div class="postbox">
						<div class="postbox-header">
							<h3 class="hndle">
								<span><?php esc_html_e( 'How It Works - Display and Shortcode', 'product-categories-designs-for-woocommerce' ); ?></span>
							</h3>
						</div>
						<div class="inside">
							<table class="form-table">
								<tbody>
									<tr>
										<th>
											<label><?php esc_html_e('Getting Started', 'product-categories-designs-for-woocommerce'); ?>:</label>
										</th>
										<td>
											<ul>
												<li><?php esc_html_e('This plugin get all the categories from "Products --> Category"', 'product-categories-designs-for-woocommerce'); ?></li>
												<li><?php esc_html_e('Display Product with Categories in Grid View and Slider View.', 'product-categories-designs-for-woocommerce'); ?></li>
											</ul>
										</td>
									</tr>

									<tr>
										<th>
											<label><?php esc_html_e('How Shortcode Works', 'product-categories-designs-for-woocommerce'); ?>:</label>
										</th>
										<td>
											<ul>
												<li><?php esc_html_e('Step-1. Create a page like product categories OR add the shortcode on any page.', 'product-categories-designs-for-woocommerce'); ?></li>
												<li><?php esc_html_e('Step-2. Put below shortcode as per your need.', 'product-categories-designs-for-woocommerce'); ?></li>
											</ul>
										</td>
									</tr>

									<tr>
										<th>
											<label><?php esc_html_e('All Shortcodes', 'product-categories-designs-for-woocommerce'); ?>:</label>
										</th>
										<td>
											<span class="wpos-copy-clipboard pcdfwoo-shortcode-preview">[wpos_product_categories]</span> – <?php esc_html_e('Product categories in grid Shortcode', 'product-categories-designs-for-woocommerce'); ?> <br />
											<span class="wpos-copy-clipboard pcdfwoo-shortcode-preview">[wpos_product_categories_slider]</span> – <?php esc_html_e('Product categories in slider / carousel Shortcode', 'product-categories-designs-for-woocommerce'); ?>
										</td>
									</tr>

									<tr>
										<th>
											<label><?php esc_html_e('Documentation', 'product-categories-designs-for-woocommerce'); ?>:</label>
										</th>
										<td>
											<a class="button button-primary" href="https://docs.essentialplugin.com/product-categories-designs-for-woocommerce/" target="_blank"><?php esc_html_e('Check Documentation', 'product-categories-designs-for-woocommerce'); ?></a>
										</td>
									</tr>
								</tbody>
							</table>
						</div><!-- .inside -->
					</div><!-- #general -->

					<div class="postbox">
						<h3 class="hndle">
							<span><?php esc_html_e( 'Help to improve this plugin!', 'product-categories-designs-for-woocommerce' ); ?></span>
						</h3>
						<div class="inside">
							<p><?php esc_html_e( 'Enjoyed this plugin? You can help by rate this plugin ', 'product-categories-designs-for-woocommerce' ); ?><a href="https://wordpress.org/support/plugin/product-categories-designs-for-woocommerce/reviews/" target="_blank">5 stars!</a></p>
						</div><!-- .inside -->
					</div><!-- #general -->

				</div><!-- .meta-box-sortables -->
			</div><!-- #post-body-content -->

			<!--Upgrad to Pro HTML -->
			<div id="postbox-container-1" class="postbox-container">
				<div class="meta-box-sortables">
					<div class="postbox wpos-pro-box">

						<h3 class="hndle">
							<span><?php esc_html_e( 'Upgrate to Pro', 'product-categories-designs-for-woocommerce' ); ?></span>
						</h3>
						<div class="inside">
							<ul class="wpos-list">
								<li>10 stunning and cool designs for Woocommerce Categories.</li>
								<li>Grid</li>
								<li>Slider</li>
								<li>1 Widgets</li>
								<li>Wp Template Feature Support</li>
								<li>Shortcode Generator </li>
								<li>Visual Composer/ WPBakery Support</li>
								<li>Gutenberg, Elementor, Beaver and SiteOrigin Page Builder Support. <span class="wpos-new-feature">New</span></li>
								<li>Divi Page Builder Native Support. <span class="wpos-new-feature">New</span></li>
								<li>Fusion Page Builder (Avada) native support. <span class="wpos-new-feature">New</span></li>
								<li>Awesome Touch-Swipe Enabled</li>
								<li>Display category title and description.</li>
								<li>Display product count.</li>
								<li>Display specific categories.</li>
								<li>Exclude specific categories.</li>
								<li>Category order and orderby sorting parameter.</li>
								<li>Set Number of Columns you want to show.</li>
								<li>Slider Auto Play on/off</li>
								<li>Navigation show/hide options</li>
								<li>Pagination show/hide options</li>
								<li>Lightweight, Fast & Powerful</li>
								<li>100% Multi language</li>
								<li>Fully responsive</li>
							</ul>
							<div class="upgrade-to-pro">Gain access to <strong>Product Categories Designs for WooCommerce</strong></div>
							<a class="button button-primary wpos-button-full button-orange" href="<?php echo PCDFWOO_SITE_LINK; ?>/pricing/?utm_source=WP&utm_medium=Product-Category&utm_campaign=Check-Designs-Solutions" target="_blank"><?php esc_html_e('Grab Now', 'product-categories-designs-for-woocommerce'); ?></a>
						</div><!-- .inside -->
					</div><!-- #general -->
				</div><!-- .meta-box-sortables -->
			</div><!-- #post-container-1 -->
		</div><!-- #post-body -->
	</div><!-- #poststuff -->
</div><!-- end .pcdfwoo-wrap -->

<?php }