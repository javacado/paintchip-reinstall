<?php
/**
 * Settings Page
 *
 * @package Wpos Analytic
 * @since 1.0.0
 */

if ( !defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?>
<style type="text/css">
	.notice, .error, div.fs-notice.updated, div.fs-notice.success, div.fs-notice.promotion{display:none !important;}
</style>

<div class="wrap wpos-anylc-optin">

	<?php if( isset($_GET['error']) && $_GET['error'] == 'wpos_anylc_error' ) { ?>
	<div class="error">
		<p><strong>Sorry, Something happened wrong. Please contact us on <a href="mailto:support@essentialplugin.com">support@essentialplugin.com</a></strong></p>
	</div>
	<?php } ?>

	<form method="POST" action="https://analytics.essentialplugin.com">
		<div class="wpos-anylc-optin-wrap" style="width: 650px; margin: 0 auto; margin-top: 70px;">

			<div>
				<div style="height:50px; text-align: center; background-color: rgba(180,185,190, 0.2);">
					<img style="position: relative; top:-40px;" src="<?php echo esc_url( $analy_product['icon'] ); ?>" alt="Icon" />
				</div>
				<div style="padding: 10px;">
					<div style="margin-top:50px; margin-bottom: 30px; text-align: center; font-weight: 700; font-size: 24px;">Never miss an important update</div>

					<div style="font-size: 20px; font-weight: 500; line-height:25px; margin: 10px 12px; color:#646970;">Opt-in to get email notifications for security & feature updates, and to share some basic WordPress environment info. This will help us make the plugin more compatible with your site and better at doing what you need it to.</div>
				</div>
			</div>

			<?php if( ! empty( $analy_product['promotion'] ) ) { ?>
			<div class="wpos-anylc-promotion-wrap">
				<?php foreach( $analy_product['promotion'] as $promotion_key => $promotion_data ) { ?>
				<div><label><input type="checkbox" value="<?php echo esc_attr( $promotion_key ); ?>" name="promotion[]" checked="checked" /> <?php echo esc_html( $promotion_data['name'] ); ?></label></div>
				<?php } ?>
			</div>
			<?php } ?>

			<div class="wpos-anylc-optin-action wpos-anylc-clearfix" style="background-color: rgba(180,185,190, 0.3);">

				<button type="submit" name="wpos_anylc_optin" class="button button-primary button-large wpos-anylc-allow-btn" value="wpos_anylc_optin">Allow and Continue</button>

				<?php if( is_null( $opt_in ) ) { ?>
				<button type="submit" name="wpos_anylc_action" class="button button-secondary button-large right wpos-anylc-skip-btn" value="skip" style="padding: 0 !important;background: transparent;border: none;">Skip</button>
				<?php }

				if( ! empty( $optin_form_data ) ) {
				 	foreach ($optin_form_data as $data_key => $data_value) {
				 		echo '<input type="hidden" name="'.esc_attr( $data_key ).'" value="'.esc_attr( $data_value ).'" />';
				 	}
				} ?>
			</div>
			<div class="wpos-anylc-optin-permission">
				<a class="wpos-anylc-permission-toggle" href="javascript:void(0);">What permissions are being granted?</a>

				<div class="wpos-anylc-permission-wrap wpos-anylc-hide">
					<div class="wpos-anylc-permission">
						<i class="dashicons dashicons-admin-users"></i>
						<div>
							<span class="wpos-anylc-permission-name">Your Profile Overview</span>
							<span class="wpos-anylc-permission-info">Name and email address</span>
						</div>
					</div>
					<div class="wpos-anylc-permission">
						<i class="dashicons dashicons-admin-settings"></i>
						<div>
							<span class="wpos-anylc-permission-name">Your Site Overview</span>
							<span class="wpos-anylc-permission-info">Site URL, WP version, PHP info & Theme</span>
						</div>
					</div>
					<div class="wpos-anylc-permission">
						<i class="dashicons dashicons-admin-plugins"></i>
						<div>
							<span class="wpos-anylc-permission-name">Current Plugin Events</span>
							<span class="wpos-anylc-permission-info">Activation, Deactivation and Uninstall</span>
						</div>
					</div>
				</div>
			</div>
			<div class="wpos-anylc-terms">
				<a href="https://www.essentialplugin.com/privacy-policy/#free-pluign-info" target="_blank">Privacy Policy</a> - <a href="https://www.essentialplugin.com/term-and-condition/" target="_blank">Terms of Service</a>
			</div>
		</div>
	</form>
</div><!-- end .wrap -->