<?php
/**
 * Admin Class
 *
 * Handles the Admin side functionality of plugin
 *
 * @package WP Slick Slider and Image Carousel
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
} ?>

<div id="wpsisac_welcome_tabs" class="wpsisac-vtab-cnt wpsisac_welcome_tabs wpsisac-clearfix">
	
	<!-- <div class="wpsisac-deal-offer-wrap">
		<h3 style="font-weight: bold; font-size: 30px; color:#ffef00; text-align:center; margin: 15px 0 5px 0;">Why Invest Time On Free Version?</h3>

		<h3 style="font-size: 18px; text-align:center; margin:0; color:#fff;">Explore WP Blog and Widgets Pro with Essential Bundle Free for 5 Days.</h3>			

		<div class="wpsisac-deal-free-offer">
			<a href="<?php //echo esc_url( WPSISAC_PLUGIN_BUNDLE_LINK ); ?>" target="_blank" class="wpsisac-sf-free-btn"><span class="dashicons dashicons-cart"></span> Try Pro For 5 Days Free</a>
		</div>
	</div> -->

	<!-- <div class="wpsisac-black-friday-banner-wrp">
			<a href="<?php // echo esc_url( WPSISAC_PLUGIN_BUNDLE_LINK ); ?>" target="_blank"><img style="width: 100%;" src="<?php // echo esc_url( WPSISAC_URL ); ?>assets/images/black-friday-banner.png" alt="black-friday-banner" /></a>
	</div> -->

	<div class="wpsisac-black-friday-banner-wrp" style="background:#e1ecc8;padding: 20px 20px 40px; border-radius:5px; text-align:center;margin-bottom: 40px;">
		<h2 style="font-size:30px; margin-bottom:10px;"><span style="color:#0055fb;">WP Slick Slider and Image Carousel</span> is included in <span style="color:#0055fb;">Essential Plugin Bundle</span> </h2> 
		<h4 style="font-size: 18px;margin-top: 0px;color: #ff5d52;margin-bottom: 24px;">Now get Designs, Optimization, Security, Backup, Migration Solutions @ one stop. </h4>

		<div class="wpsisac-black-friday-feature">

			<div class="wpsisac-inner-deal-class" style="width:40%;">
				<div class="wpsisac-inner-Bonus-class">Bonus</div>
				<div class="wpsisac-image-logo" style="font-weight: bold;font-size: 26px;color: #222;"><img style="width: 34px; height:34px;vertical-align: middle;margin-right: 5px;" class="wpsisac-img-logo" src="<?php echo esc_url( WPSISAC_URL ); ?>assets/images/essential-logo-small.png" alt="essential-logo" /><span class="wpsisac-esstial-name" style="color:#0055fb;">Essential </span>Plugin</div>
				<div class="wpsisac-sub-heading" style="font-size: 16px;text-align: left;font-weight: bold;color: #222;margin-bottom: 10px;">Includes All premium plugins at no extra cost.</div>
				<a class="wpsisac-sf-btn" href="<?php echo esc_url( WPSISAC_PLUGIN_BUNDLE_LINK ); ?>" target="_blank">Grab The Deal</a>
			</div>

			<div class="wpsisac-main-list-class" style="width:60%;">
				<div class="wpsisac-inner-list-class">
					<div class="wpsisac-list-img-class"><img src="<?php echo esc_url( WPSISAC_URL ); ?>assets/images/logo-image/img-slider.png" alt="essential-logo" /> Image Slider</li></div>

					<div class="wpsisac-list-img-class"><img src="<?php echo esc_url( WPSISAC_URL ); ?>assets/images/logo-image/advertising.png" alt="essential-logo" /> Publication</li></div>

					<div class="wpsisac-list-img-class"><img src="<?php echo esc_url( WPSISAC_URL ); ?>assets/images/logo-image/marketing.png" alt="essential-logo" /> Marketing</li></div>

					<div class="wpsisac-list-img-class"><img src="<?php echo esc_url( WPSISAC_URL ); ?>assets/images/logo-image/photo-album.png" alt="essential-logo" /> Photo album</li></div>

					<div class="wpsisac-list-img-class"><img src="<?php echo esc_url( WPSISAC_URL ); ?>assets/images/logo-image/showcase.png" alt="essential-logo" /> Showcase</li></div>

					<div class="wpsisac-list-img-class"><img src="<?php echo esc_url( WPSISAC_URL ); ?>assets/images/logo-image/shopping-bag.png" alt="essential-logo" /> WooCommerce</li></div>

					<div class="wpsisac-list-img-class"><img src="<?php echo esc_url( WPSISAC_URL ); ?>assets/images/logo-image/performance.png" alt="essential-logo" /> Performance</li></div>

					<div class="wpsisac-list-img-class"><img src="<?php echo esc_url( WPSISAC_URL ); ?>assets/images/logo-image/security.png" alt="essential-logo" /> Security</li></div>

					<div class="wpsisac-list-img-class"><img src="<?php echo esc_url( WPSISAC_URL ); ?>assets/images/logo-image/forms.png" alt="essential-logo" /> Pro Forms</li></div>

					<div class="wpsisac-list-img-class"><img src="<?php echo esc_url( WPSISAC_URL ); ?>assets/images/logo-image/seo.png" alt="essential-logo" /> SEO</li></div>

					<div class="wpsisac-list-img-class"><img src="<?php echo esc_url( WPSISAC_URL ); ?>assets/images/logo-image/backup.png" alt="essential-logo" /> Backups</li></div>

					<div class="wpsisac-list-img-class"><img src="<?php echo esc_url( WPSISAC_URL ); ?>assets/images/logo-image/White-labeling.png" alt="essential-logo" /> Migration</li></div>
				</div>
			</div>
		</div>
		<div class="wpsisac-main-feature-item">
			<div class="wpsisac-inner-feature-item">
				<div class="wpsisac-list-feature-item">
					<img src="<?php echo esc_url( WPSISAC_URL ); ?>assets/images/logo-image/layers.png" alt="layer" />
					<h5>Site management</h5>
					<p>Manage, update, secure & optimize unlimited sites.</p>
				</div>
				<div class="wpsisac-list-feature-item">
					<img src="<?php echo esc_url( WPSISAC_URL ); ?>assets/images/logo-image/risk.png" alt="backup" />
					<h5>Backup storage</h5>
					<p>Secure sites with auto backups and easy restore.</p>
				</div>
				<div class="wpsisac-list-feature-item">
					<img src="<?php echo esc_url( WPSISAC_URL ); ?>assets/images/logo-image/support.png" alt="support" />
					<h5>Support</h5>
					<p>Get answers on everything WordPress at anytime.</p>
				</div>
			</div>
		</div>
		<a class="wpsisac-sf-btn" href="<?php echo esc_url( WPSISAC_PLUGIN_BUNDLE_LINK ); ?>" target="_blank">Grab The Deal</a>
	</div>

	<!-- Start - Welcome Box -->
	<div class="wpsisac-sf-welcome-wrap" style="padding: 30px;border-radius: 10px;border: 1px solid #e5ecf6;">
		<div class="wpsisac-sf-welcome-inr wpsisac-sf-center">
			<h1 class="wpsisac-sf-heading" style="font-size: 25px; margin: 20px 0;">Showcase your <span class="wpsisac-sf-blue">images</span> associated with your business with slick slider</h1>
			<h5 class="wpsisac-sf-content" style="font-size: 20px; margin: 20px 0;">Experience <span class="wpsisac-sf-blue">5 Layouts</span>, <span class="wpsisac-sf-blue">90+ stunning designs</span>. Build and display responsive slick image sliders/carousels to  increase website engagement.</h5>
			<h5 class="wpsisac-sf-content" style="font-size: 18px; margin: 20px 0;"><span class="wpsisac-sf-blue">20,000+ </span>websites are using <span class="wpsisac-sf-blue">Slick Slider</span>.</h5>
		</div>
		<div style=" text-transform: uppercase; text-align:center;">
			<a href="<?php echo esc_url( $wpsisac_add_link ); ?>" class="wpsisac-sf-btn">Launch Slick Slider With Free Features</a>
		</div>
	</div>
	<!-- End - Welcome Box -->
	
</div>