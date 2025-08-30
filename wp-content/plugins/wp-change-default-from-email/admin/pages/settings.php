<?php $wcdfe_settings = get_option( 'wcdfe_settings' ); ?>
<div class="wrap about-wrap">
	<h1><?php echo __( WCDFE_PLUGIN_NAME, WCDFE_TEXTDOMAIN ); ?></h1>
	<div class="about-text"><?php echo __( 'Manage WP Change Default From Email settings here.', WCDFE_TEXTDOMAIN ); ?></div>

	<h2 class="nav-tab-wrapper">
		<a class="nav-tab" data-tab="wcdfe-settings" href="#wcdfe-settings" id="wcdfe-settings-tab"><?php echo __( 'Settings', WCDFE_TEXTDOMAIN ); ?></a>
		<a class="nav-tab" data-tab="wcdfe-help" href="#wcdfe-help" id="wcdfe-help-tab"><?php echo __( 'Help', WCDFE_TEXTDOMAIN ); ?></a>
	</h2>
	<div id="wcdfe-settings" class="wcdfe-tabs">
		<form id="wcdfe-settings-form" method="post">
			<input type="hidden" name="action" value="wcdfe_save_settings">
			<input type="hidden" name="security" value="<?php echo wp_create_nonce( "wcdfe-save-settings" ); ?>">
			<div>
				<!-- BEGIN ADDONS LISTING -->
				<div class="wcdfe-row">
					
					<div class="wcdfe-col-12">
						<span class="wcdfe-addon-label"><?php echo __( 'Enable/Disable', WCDFE_TEXTDOMAIN ); ?></span>
						<span class="wcdfe-addon-switch">
							<div class="wcdfe-onoffswitch">
							    <input type="checkbox" name="wcdfe_settings[enable]" class="wcdfe-onoffswitch-checkbox" id="wcdfe_enable" value="1"  <?php if(isset($wcdfe_settings['enable']) && $wcdfe_settings['enable'] == 1) echo 'checked="checked"'; ?>>
							    <label class="wcdfe-onoffswitch-label" for="wcdfe_enable">
							        <span class="wcdfe-onoffswitch-inner"></span>
							        <span class="wcdfe-onoffswitch-switch"></span>
							    </label>
							</div>
						</span>
					</div>

					<div class="wcdfe-col-12">
						<span class="wcdfe-addon-label"><?php echo __( 'From Name', WCDFE_TEXTDOMAIN ); ?></span>
						<span class="wcdfe-addon-switch">
							<input type="text" name="wcdfe_settings[from_name]" size="100" value="<?php if(isset($wcdfe_settings['from_name'])) echo $wcdfe_settings['from_name']; ?>" placeholder="<?php echo get_bloginfo('name');?>">
						</span>
					</div>

					<div class="wcdfe-col-12">
						<span class="wcdfe-addon-label"><?php echo __( 'From Email', WCDFE_TEXTDOMAIN ); ?></span>
						<span class="wcdfe-addon-switch">
							<input type="text" name="wcdfe_settings[from_email]" size="100" value="<?php if(isset($wcdfe_settings['from_email'])) echo $wcdfe_settings['from_email']; ?>" placeholder="<?php echo get_bloginfo('admin_email');?>">
							<small><?php echo __( 'To avoid your email being marked as spam, it is highly recommended that your "from" domain match your website.', WCDFE_TEXTDOMAIN ); ?></small>
						</span>
					</div>

				</div>
				<!-- END ADDONS LISTING -->
			</div>
		</form>
		
		<div class="wcdfe-save-settings-container">
			<input type="submit" value="<?php echo __( 'Save Settings', WCDFE_TEXTDOMAIN ); ?>" class="button button-large button-primary" id="wcdfe-save-settings" name="save_settings">
			<div id="wcdfe-error-message"></div>
		</div>

	</div>
	<div id="wcdfe-help" class="wcdfe-tabs">
		<div class="changelog feature-list">
			<div class="feature-section col two-col">
				<div>
					<h4><?php echo __( 'If mails are going in spam.', WCDFE_TEXTDOMAIN ); ?></h4>
					<p><?php echo __( "To avoid your email being marked as spam, it is highly recommended that your domain name in 'From Email' must match with your website, i.e. if your website is <code>www.example.com</code> then your email must hosted on <code>@example.com</code>.", WCDFE_TEXTDOMAIN ); ?></p>
				</div>
				<div class="last-feature">
					<h4><?php echo __( 'Do you offer support?', WCDFE_TEXTDOMAIN ); ?></h4>
					<p><?php echo __( 'You can contact me using', WCDFE_TEXTDOMAIN ); ?> <code><a href="https://forms.gle/TTdwYUsJpvu8No186" onclick="window.open('https://forms.gle/TTdwYUsJpvu8No186', '', 'toolbar=0,scrollbars=0,location=0,statusbar=0,menubar=0,resizable=0'); return false;" title="Contact me">Contact Form</a></code></p>
				</div>
			</div>

			<hr>
		</div>
	</div>
</div>

<script>
jQuery(document).ready(function(e) {
	jQuery('.wcdfe-tabs').hide();
	if (typeof(localStorage) != 'undefined' ) {
        activetab = localStorage.getItem("wcdfeAddonsActivetab");
    }
    if (activetab != '' && jQuery(activetab).length ) {
        jQuery(activetab).fadeIn();
    } else {
        jQuery('.wcdfe-tabs:first').fadeIn();
    }

    if (activetab != '' && jQuery(activetab + '-tab').length ) {
        jQuery(activetab + '-tab').addClass('nav-tab-active');
    } else {
        jQuery('.nav-tab-wrapper a:first').addClass('nav-tab-active');
    }
	jQuery('.nav-tab-wrapper a').click(function(e) {
	    jQuery('.nav-tab-wrapper a').removeClass('nav-tab-active');
	    jQuery(this).addClass('nav-tab-active').blur();
	    var clicked_group = jQuery(this).attr('href');
	    if (typeof(localStorage) != 'undefined' ) {
	        localStorage.setItem("wcdfeAddonsActivetab", jQuery(this).attr('href'));
	    }
	    jQuery('.wcdfe-tabs').hide();
	    jQuery(clicked_group).fadeIn();
	    e.preventDefault();
	});

	jQuery("#wcdfe-save-settings").on('click', function(e) {
		e.preventDefault();
		var data = jQuery("#wcdfe-settings-form").serialize();
		jQuery.ajax({
			url: ajaxurl,
			dataType: 'json',
			type: 'post',
			data: data,
			success: function(response){
				if(response.status == "success"){
					jQuery("#wcdfe-error-message").html('<div class="updated"><p>'+response.message+'</p></div>');
				} else if(response.status == "error") {
					jQuery("#wcdfe-error-message").html('<div class="error"><p>'+response.message+'</p></div>');
				} else {
					jQuery("#wcdfe-error-message").html('<div class="error"><p><?php echo __( "No settings were saved.", WCDFE_TEXTDOMAIN ); ?></p></div>');
				}
			}
		});
	});
});
</script>