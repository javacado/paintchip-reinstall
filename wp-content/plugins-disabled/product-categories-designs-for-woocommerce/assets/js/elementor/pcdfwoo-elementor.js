( function($) {

	'use strict';

	jQuery(window).on('elementor/frontend/init', function() {

		/* Shortcode Element */
		elementorFrontend.hooks.addAction( 'frontend/element_ready/shortcode.default', function() {
			pcdfwoo_product_cat_slider_init();
		});

		/* Text Editor Element */
		elementorFrontend.hooks.addAction( 'frontend/element_ready/text-editor.default', function() {
			pcdfwoo_product_cat_slider_init();
		});

		/* Tabs Element */
		elementorFrontend.hooks.addAction( 'frontend/element_ready/tabs.default', function( $scope ) {

			if( $scope.find('.pcdfwoo-product-cat-slider').length >= 1 ) {
				$scope.find('.elementor-tabs-content-wrapper').addClass('pcdfwoo-elementor-tab-wrap');
			} else {
				$scope.find('.elementor-tabs-content-wrapper').removeClass('pcdfwoo-elementor-tab-wrap');
			}

			$('.pcdfwoo-product-cat-slider').each(function( index ) {

				var slider_id = $(this).attr('id');
				$('#'+slider_id).css({'visibility': 'hidden', 'opacity': 0});

				pcdfwoo_product_cat_slider_init();

				setTimeout(function() {

					/* Tweak for slick slider */
					if( typeof(slider_id) !== 'undefined' && slider_id != '' ) {
						$('#'+slider_id).slick( 'setPosition' );
						$('#'+slider_id).css({'visibility': 'visible', 'opacity': 1});
					}
				}, 300);
			});
		});

		/* Accordion Element */
		elementorFrontend.hooks.addAction( 'frontend/element_ready/accordion.default', function() {

			$('.pcdfwoo-product-cat-slider').each(function( index ) {

				var slider_id = $(this).attr('id');
				$('#'+slider_id).css({'visibility': 'hidden', 'opacity': 0});

				pcdfwoo_product_cat_slider_init();

				setTimeout(function() {

					/* Tweak for slick slider */
					if( typeof(slider_id) !== 'undefined' && slider_id != '' ) {
						$('#'+slider_id).slick( 'setPosition' );
						$('#'+slider_id).css({'visibility': 'visible', 'opacity': 1});
					}
				}, 300);
			});
		});

		/* Toggle Element */
		elementorFrontend.hooks.addAction( 'frontend/element_ready/toggle.default', function() {

			$('.pcdfwoo-product-cat-slider').each(function( index ) {

				var slider_id = $(this).attr('id');
				$('#'+slider_id).css({'visibility': 'hidden', 'opacity': 0});

				pcdfwoo_product_cat_slider_init();

				setTimeout(function() {

					/* Tweak for slick slider */
					if( typeof(slider_id) !== 'undefined' && slider_id != '' ) {
						$('#'+slider_id).slick( 'setPosition' );
						$('#'+slider_id).css({'visibility': 'visible', 'opacity': 1});
					}
				}, 300);
			});
		});
	});
})(jQuery);