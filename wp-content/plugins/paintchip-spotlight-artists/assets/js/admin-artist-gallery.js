jQuery( function ( $ ) {
	var frame;
	var $wrap = $( '#pc-gallery-wrap' );
	var $list = $( '#pc-gallery-list' );
	var $input = $( '#pc_gallery_ids' );

	function currentIds() {
		return $input.val() ? $input.val().split( ',' ).filter( Boolean ) : [];
	}

	function render( ids ) {
		$input.val( ids.join( ',' ) );
	}

	$( document ).on( 'click', '#pc-gallery-add', function ( e ) {
		e.preventDefault();

		if ( frame ) {
			frame.open();
			return;
		}

		frame = wp.media( {
			title: 'Select Images of Work',
			button: { text: 'Add to Gallery' },
			multiple: true,
		} );

		frame.on( 'select', function () {
			var selection = frame.state().get( 'selection' );
			var ids = currentIds();

			selection.each( function ( attachment ) {
				attachment = attachment.toJSON();
				if ( ids.indexOf( String( attachment.id ) ) === -1 ) {
					ids.push( String( attachment.id ) );
					var thumb = attachment.sizes && attachment.sizes.thumbnail ? attachment.sizes.thumbnail.url : attachment.url;
					$list.append(
						$( '<li>' )
							.attr( 'data-id', attachment.id )
							.append( $( '<img>' ).attr( 'src', thumb ) )
							.append( $( '<a href="#" class="pc-gallery-remove">&times;</a>' ) )
					);
				}
			} );

			render( ids );
		} );

		frame.open();
	} );

	$( document ).on( 'click', '.pc-gallery-remove', function ( e ) {
		e.preventDefault();
		var $li = $( this ).closest( 'li' );
		var id = String( $li.data( 'id' ) );
		var ids = currentIds().filter( function ( existing ) {
			return existing !== id;
		} );
		render( ids );
		$li.remove();
	} );
} );
