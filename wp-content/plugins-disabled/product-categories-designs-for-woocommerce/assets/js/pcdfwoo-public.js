/* Define global Variable */
var pcdfwoo_next_arrow = '<span class="slick-next slick-arrow" data-role="none" tabindex="0" role="button"><svg fill="currentColor" viewBox="0 0 96 96" xmlns="http://www.w3.org/2000/svg"><title/><path d="M69.8437,43.3876,33.8422,13.3863a6.0035,6.0035,0,0,0-7.6878,9.223l30.47,25.39-30.47,25.39a6.0035,6.0035,0,0,0,7.6878,9.2231L69.8437,52.6106a6.0091,6.0091,0,0,0,0-9.223Z"/></svg></span>';
var pcdfwoo_prev_arrow = '<span class="slick-prev slick-arrow" data-role="none" tabindex="0" role="button"><svg fill="currentColor" viewBox="0 0 96 96" xmlns="http://www.w3.org/2000/svg"><title/><path d="M39.3756,48.0022l30.47-25.39a6.0035,6.0035,0,0,0-7.6878-9.223L26.1563,43.3906a6.0092,6.0092,0,0,0,0,9.2231L62.1578,82.615a6.0035,6.0035,0,0,0,7.6878-9.2231Z"/></svg></span>';

( function($) {

	'use strict';

	pcdfwoo_product_cat_slider_init();

	/* Elementor Compatibility */
	/***** Elementor Compatibility Start *****/
	if( Pcdfwoo.elementor_preview == 0 ) {

		$(window).on('elementor/frontend/init', function() {

			/* Tweak for Slick Slider */
			$('.pcdfwoo-product-cat-slider').each(function( index ) {

				/* Tweak for Vertical Tab */
				$(this).closest('.elementor-tabs-content-wrapper').addClass('pcdfwoo-elementor-tab-wrap');

				var slider_id = $(this).attr('id');
				$('#'+slider_id).css({'visibility': 'hidden', 'opacity': 0});

				setTimeout(function() {
					if( typeof(slider_id) !== 'undefined' && slider_id != '' ) {
						$('#'+slider_id).slick( 'setPosition' );
						$('#'+slider_id).css({'visibility': 'visible', 'opacity': 1});
					}
				}, 350);
			});
		});
	}

	$(document).on('click', '.elementor-tab-title', function() {

		var ele_control	= $(this).attr('aria-controls');
		var slider_wrap	= $('#'+ele_control).find('.pcdfwoo-product-cat-slider');

		$( slider_wrap ).each(function( index ) {

			var slider_id = $(this).attr('id');
			$('#'+slider_id).css({'visibility': 'hidden', 'opacity': 0});

			/* Tweak for slick slider */
			setTimeout(function() {
				if( typeof(slider_id) !== 'undefined' && slider_id != '' ) {
					$('#'+slider_id).slick( 'setPosition' );
					$('#'+slider_id).css({'visibility': 'visible', 'opacity': 1});
				}
			}, 350);
		});
	});

	/* SiteOrigin Compatibility For Accordion Panel */
	$(document).on('click', '.sow-accordion-panel', function() {

		var ele_control	= $(this).attr('data-anchor');
		var slider_wrap	= $('#accordion-content-'+ele_control).find('.pcdfwoo-product-cat-slider');

		/* Tweak for slick slider */
		$( slider_wrap ).each(function( index ) {

			var slider_id = $(this).attr('id');

			/* Tweak for slick slider */
			if( typeof(slider_id) !== 'undefined' && slider_id != '' ) {
				$('#'+slider_id).slick( 'setPosition' );
			}
		});
	});

	/* SiteOrigin Compatibility for Tab Panel */
	$(document).on('click focus', '.sow-tabs-tab', function() {
		var sel_index	= $(this).index();
		var cls_ele		= $(this).closest('.sow-tabs');
		var tab_cnt		= cls_ele.find('.sow-tabs-panel').eq( sel_index );
		var slider_wrap	= tab_cnt.find('.pcdfwoo-product-cat-slider');

		/* Tweak for slick slider */
		$( slider_wrap ).each(function( index ) {

			var slider_id = $(this).attr('id');
			$('#'+slider_id).css({'visibility': 'hidden', 'opacity': 0});

			setTimeout(function() {
				if( typeof(slider_id) !== 'undefined' && slider_id != '' ) {
					$('#'+slider_id).slick( 'setPosition' );
					$('#'+slider_id).css({'visibility': 'visible', 'opacity': 1});
				}
			}, 300);
		});
	});

	/* Beaver Builder Compatibility for Accordion */
	$(document).on('click', '.fl-accordion-button, .fl-tabs-label', function() {

		var ele_control	= $(this).attr('aria-controls');
		var slider_wrap	= $('#'+ele_control).find('.pcdfwoo-product-cat-slider');

		/* Tweak for filter */
		$( slider_wrap ).each(function( index ) {

			var slider_id = $(this).attr('id');
			$('#'+slider_id).css({'visibility': 'hidden', 'opacity': 0});

			setTimeout(function() {
				if( typeof(slider_id) !== 'undefined' && slider_id != '' ) {
					$('#'+slider_id).slick( 'setPosition' );
					$('#'+slider_id).css({'visibility': 'visible', 'opacity': 1});
				}
			}, 300);
		});
	});

	/* Divi Builder Compatibility for Accordion & Toggle */
	$(document).on('click', '.et_pb_toggle', function() {

		var acc_cont	= $(this).find('.et_pb_toggle_content');
		var slider_wrap	= acc_cont.find('.pcdfwoo-product-cat-slider');

		/* Tweak for slick slider */
		$( slider_wrap ).each(function( index ) {

			var slider_id = $(this).attr('id');

			if( typeof(slider_id) !== 'undefined' && slider_id != '' ) {
				$('#'+slider_id).slick( 'setPosition' );
			}
		});
	});

	/* Divi Builder Compatibility for Tabs */
	$('.et_pb_tabs_controls li a').on( 'click', function() {
		var cls_ele		= $(this).closest('.et_pb_tabs');
		var tab_cls		= $(this).closest('li').attr('class');
		var tab_cont	= cls_ele.find('.et_pb_all_tabs .'+tab_cls);
		var slider_wrap	= tab_cont.find('.pcdfwoo-product-cat-slider');

		setTimeout(function() {

			/* Tweak for slick slider */
			$( slider_wrap ).each(function( index ) {

				var slider_id = $(this).attr('id');

				if( typeof(slider_id) !== 'undefined' && slider_id != '' ) {
					$('#'+slider_id).slick( 'setPosition' );
				}
			});
		}, 550);
	});

	/* Fusion Builder Compatibility for Tabs */
	$(document).on('click', '.fusion-tabs li .tab-link', function() {
		var cls_ele		= $(this).closest('.fusion-tabs');
		var tab_id		= $(this).attr('href');
		var tab_cont	= cls_ele.find(tab_id);
		var slider_wrap	= tab_cont.find('.pcdfwoo-product-cat-slider');

		/* Tweak for slick slider */
		$( slider_wrap ).each(function( index ) {
			var slider_id = $(this).attr('id');
			$('#'+slider_id).css({'visibility': 'hidden', 'opacity': 0});

			setTimeout(function() {
				if( typeof(slider_id) !== 'undefined' && slider_id != '' ) {
					$('#'+slider_id).slick( 'setPosition' );
					$('#'+slider_id).css({'visibility': 'visible', 'opacity': 1});
					$('#'+slider_id).slick( 'setPosition' );
				}
			}, 200);
		});
	});

	/* Fusion Builder Compatibility for Toggles */
	$(document).on('click', '.fusion-accordian .panel-heading a', function() {
		var cls_ele		= $(this).closest('.fusion-accordian');
		var tab_id		= $(this).attr('href');
		var tab_cont	= cls_ele.find(tab_id);
		var slider_wrap	= tab_cont.find('.pcdfwoo-product-cat-slider');

		/* Tweak for slick slider */
		$( slider_wrap ).each(function( index ) {
			var slider_id = $(this).attr('id');
			$('#'+slider_id).css({'visibility': 'hidden', 'opacity': 0});

			setTimeout(function() {
			if( typeof(slider_id) !== 'undefined' && slider_id != '' ) {
				$('#'+slider_id).slick( 'setPosition' );
				$('#'+slider_id).css({'visibility': 'visible', 'opacity': 1});
				$('#'+slider_id).slick( 'setPosition' );
			}
			}, 200);
		});
	});

})(jQuery);

/* Function to initialize Product Slider */
function pcdfwoo_product_cat_slider_init() {
	jQuery( '.pcdfwoo-product-cat-slider' ).each(function( index ) {

		if( jQuery(this).hasClass('slick-initialized') ) {
			return;
		}

		/* Flex Condition */
		if( Pcdfwoo.is_avada == 1 ) {
			jQuery(this).closest('.fusion-flex-container').addClass('pcdfwoo-fusion-flex');
		}

		var slider_id   = jQuery(this).attr('id');
		var slider_conf = JSON.parse( jQuery(this).attr('data-conf') );

		jQuery('#'+slider_id).slick({
			dots			: slider_conf.dots,
			infinite		: slider_conf.loop,
			arrows			: slider_conf.arrows,
			autoplay		: slider_conf.autoplay,
			nextArrow 		: pcdfwoo_next_arrow,
			prevArrow 		: pcdfwoo_prev_arrow,
			speed			: parseInt( slider_conf.speed ),
			autoplaySpeed	: parseInt( slider_conf.autoplay_interval ),
			slidesToShow	: parseInt( slider_conf.slidestoshow ),
			slidesToScroll	: parseInt( slider_conf.slidestoscroll ),
			mobileFirst		: ( Pcdfwoo.is_mobile == 1 )	? true : false,
			centerPadding 	: 0,
			responsive		: [{
								breakpoint	: 1100,
								settings	: {
												slidesToShow	: parseInt( slider_conf.slidestoshow ),
												slidesToScroll	: parseInt( slider_conf.slidestoscroll ),
											}
							},{
								breakpoint	: 811,
								settings	: {
												slidesToShow	: ( parseInt( slider_conf.slidestoshow ) > 3 ) ? 3 : parseInt( slider_conf.slidestoshow ),
												slidesToScroll	: ( parseInt( slider_conf.slidestoscroll ) > 3 ) ? 3 : parseInt( slider_conf.slidestoscroll ),
											}
							},{
								breakpoint	: 479,
								settings	: {
												slidesToShow	: ( parseInt( slider_conf.slidestoshow ) > 2 ) ? 2 : parseInt( slider_conf.slidestoshow ),
												slidesToScroll	: ( parseInt( slider_conf.slidestoscroll ) > 2 ) ? 2 : parseInt( slider_conf.slidestoscroll ),
											}
							},{
								breakpoint	: 220,
								settings	: {
												slidesToShow	: 1,
												slidesToScroll	: 1,
												dots			: false,
											}
							}]
		});
	});
}