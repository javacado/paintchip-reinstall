<?php
if(!function_exists('add_action')){
	echo 'You are not allowed to access this page directly.';
	exit;
}

function dsxmlrpc_action_links($links) {
    $plugin_shortcuts = array(
        '<a style="color:green;" rel="noopener" href="https://wordpress.org/support/plugin/disable-xml-rpc-api/reviews/#new-post" target="_blank">' . __('Rate Plugin', 'dsxmlrpc') . '</a>',
        '<a style="color:#f44336;" rel="noopener" href="https://bit.ly/324BrbO" target="_blank">' . __('More Protection', 'dsxmlrpc') . '</a>',

    );
    return array_merge($links, $plugin_shortcuts);
}

function dsxmlrpc_admin_notice_wpsg() {
	if (   ! PAnD::is_admin_notice_active( 'dsxmlrpc-wpsg-notice-10' )  ) {
		return;
	}
	
	?>
	<div data-dismissible="dsxmlrpc-wpsg-notice-10" id="dsxmlrpc-wpsg-notice" class="notice notice-warning is-dismissible">
	<img src="<?=DSXMLRPC_URL?>/admin/logo-icon.png" style="float:left; margin:10px 20px 10px 10px" width="100">
	<h2>You can improve your website security by using WP Security Guard!</h2>
	<div class="dsxmlrpc-wpsg-notice-innner">
		<p>Brand new <strong>lightweight</strong> security plugin is ready now you can buy it with special discount offer. Use <strong style="color:green;">"xmlrpc20"</strong> promo code to get 20% off in your purchase. </p>
		<a class="button button-primary dsxmlrpc_button" target="_blank"  href="https://neatma.com/wpsg-plugin/" >More Info</a>
	</div>
	</div>
	<style>
	div#dsxmlrpc-wpsg-notice {
    background-image: url('<?=DSXMLRPC_URL?>/admin/xmlrpc20.png');
    background-repeat: no-repeat;
    background-position: 95%;
    background-size: contain;
	height: 140px;
	}
	.dsxmlrpc_button {
    margin: 3px 0 15px 15px !important;
    transition: 500ms;
	}
	</style>
	<?php
}

function dsxmlrpc_admin_notice_review() {

	
	if (isset($_POST['dsxmlrpc-notice-forever'])){
		update_option('dsxmlrpc-notice-forever','forever',false);
	}
	if (   ! PAnD::is_admin_notice_active( 'dsxmlrpc-notice-15' ) ||  get_option('dsxmlrpc-notice-forever')  ) {
		return;
	}

	?>
	<div data-dismissible="dsxmlrpc-notice-15" id="dsxmlrpc-notice" class="updated notice notice-success is-dismissible">
	<h2>Your website is protected from XML-RPC Brute-force and DDOS attacks!</h2>
	<div class="dsxmlrpc-notice-innner">

		<p>You can help us make this plugin better by reviewing and giving it 5 stars</p>
		<div class="dsxmlrpc-rate">
		<fieldset class="dsxmlrpc-ratings rating-stars"><label for="rating_1"><input class="hidden dsxmlrpc-hidden" id="rating_1" type="radio" name="rating" value="1"><span class="dashicons dashicons-star-empty dashicons-star-filled" style="color:#ffb900 !important;" title="Poor"></span><span class="screen-reader-text">Poor</span></label><label for="rating_2"><input class="hidden dsxmlrpc-hidden" id="rating_2" type="radio" name="rating" value="2"><span class="dashicons dashicons-star-empty dashicons-star-filled" style="color:#ffb900 !important;" title="Works"></span><span class="screen-reader-text">Works</span></label><label for="rating_3"><input class="hidden dsxmlrpc-hidden" id="rating_3" type="radio" name="rating" value="3"><span class="dashicons dashicons-star-empty dashicons-star-filled" style="color:#ffb900 !important;" title="Good"></span><span class="screen-reader-text">Good</span></label><label for="rating_4"><input class="hidden dsxmlrpc-hidden" id="rating_4" type="radio" name="rating" value="4"><span class="dashicons dashicons-star-empty dashicons-star-filled" style="color:#ffb900 !important;" title="Great"></span><span class="screen-reader-text">Great</span></label><label for="rating_5"><input class="hidden dsxmlrpc-hidden" id="rating_5" type="radio" name="rating" checked="checked" value="5"><span class="dashicons dashicons-star-empty dashicons-star-filled" style="color:#ffb900 !important;" title="Fantastic!"></span><span class="screen-reader-text">Fantastic!</span></label></fieldset><input type="hidden" name="rating" id="rating" value="5">
		</div>
		<form action="" method="post" >
		<input  class="button button-primary dsxmlrpc_button" type="submit" name="dsxmlrpc-notice-forever" value="Already Rated" />
		</form>

	</div>
	
			<script>

		jQuery( document ).ready( function( $ ) {
				var ratings = $( '.rating-stars' );
				var selectedClass = 'dashicons-star-filled';

				function dxtoggleStyles( currentInput ) {
					var thisInput = $( currentInput );
					var index = parseInt( thisInput.val() );

					stars.removeClass( selectedClass );
					stars.slice( 0, index ).addClass( selectedClass );
				}

				// If the ratings exist on the page
				if ( ratings.length !== 0 ) {
					var inputs = ratings.find( 'input[type="radio"]' );
					var labels = ratings.find( 'label' );
					var stars = inputs.next();

					inputs.on( 'change', function( event ) {
						dxtoggleStyles( event.target )
					} );
					inputs.on( 'click', function( event ) {
						 window.open("https://wordpress.org/support/plugin/disable-xml-rpc-api/reviews/#new-post");
					} );
					labels.hover( function( event ) {
						$curInput = $( event.currentTarget ).find( 'input' );
						dxtoggleStyles( $curInput );
					}, function () {
						$currentSelected = ratings.find( 'input[type="radio"]:checked' );
						dxtoggleStyles( $currentSelected )
					} );
				}
			});
		</script>
	</div>
	<style>
	a.dsxmlrpc-ratings span:hover {
    color: #FF9800 !important;
    }
	@media screen and (min-width: 782px) {
	.dsxmlrpc-notice-innner {
	display: flex;
	}}
	.dsxmlrpc-hidden {
    height: 0;
    width: 0;
    overflow: hidden;
    overflow-x: hidden;
    overflow-y: hidden;
    position: absolute;
    background: none;
    left: -999em;
	}
	.dsxmlrpc-rate {
    top: 5px;
    padding-left: 10px;
    position: relative;
	}
	.dsxmlrpc_button {
    margin: 3px 0 15px 15px !important;
    transition: 500ms;
	}
	</style>
	<?php
}

add_action( 'admin_notices', 'dsxmlrpc_admin_notice_review' );
add_action( 'admin_notices', 'dsxmlrpc_admin_notice_wpsg' );