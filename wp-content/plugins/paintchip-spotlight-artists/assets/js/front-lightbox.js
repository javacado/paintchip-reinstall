document.addEventListener( 'DOMContentLoaded', function () {
	var currentOverlay = null;

	function closeLightbox() {
		if ( currentOverlay ) {
			currentOverlay.remove();
			currentOverlay = null;
			document.removeEventListener( 'keydown', onKeydown );
		}
	}

	function onKeydown( e ) {
		if ( 'Escape' === e.key ) {
			closeLightbox();
		}
	}

	function openLightbox( src, caption ) {
		closeLightbox();

		var overlay = document.createElement( 'div' );
		overlay.className = 'paintchip-lightbox-overlay';

		var inner = document.createElement( 'div' );
		inner.className = 'paintchip-lightbox-inner';

		var img = document.createElement( 'img' );
		img.src = src;
		img.alt = caption || '';
		inner.appendChild( img );

		if ( caption ) {
			var captionEl = document.createElement( 'p' );
			captionEl.className = 'paintchip-lightbox-caption';
			captionEl.textContent = caption; // textContent, not innerHTML -- no markup injection risk
			inner.appendChild( captionEl );
		}

		overlay.appendChild( inner );
		overlay.addEventListener( 'click', closeLightbox ); // clicking anywhere, including the image, closes it
		document.body.appendChild( overlay );

		currentOverlay = overlay;
		document.addEventListener( 'keydown', onKeydown );
	}

	document.addEventListener( 'click', function ( e ) {
		var trigger = e.target.closest ? e.target.closest( '.paintchip-lightbox-trigger' ) : null;
		if ( trigger ) {
			e.preventDefault();
			openLightbox( trigger.getAttribute( 'href' ), trigger.getAttribute( 'data-caption' ) );
		}
	} );
} );
