jQuery( function ( $ ) {

	/* ---------------- Artist search/picker ---------------- */

	var $search = $( '#pc-artist-search' );
	var $results = $( '#pc-artist-results' );
	var $selected = $( '#pc-artist-selected' );
	var $idsInput = $( '#pc_artist_ids' );
	var searchTimer;

	function selectedIds() {
		return $idsInput.val() ? $idsInput.val().split( ',' ).filter( Boolean ) : [];
	}

	function saveSelectedIds( ids ) {
		$idsInput.val( ids.join( ',' ) );
	}

	function addArtist( id, name ) {
		var ids = selectedIds();
		id = String( id );
		if ( ids.indexOf( id ) !== -1 ) {
			return;
		}
		ids.push( id );
		saveSelectedIds( ids );
		$selected.append(
			$( '<li>' ).attr( 'data-id', id ).text( name ).append( ' <a href="#" class="pc-artist-remove">&times;</a>' )
		);
	}

	$search.on( 'input', function () {
		var term = $( this ).val();
		clearTimeout( searchTimer );

		if ( term.length < 2 ) {
			$results.empty();
			return;
		}

		searchTimer = setTimeout( function () {
			$.getJSON( PaintChipAdmin.ajaxUrl, {
				action: 'paintchip_search_artists',
				nonce: PaintChipAdmin.nonce,
				term: term,
			} ).done( function ( response ) {
				$results.empty();
				if ( ! response.success || ! response.data.length ) {
					$results.append( '<li class="pc-no-results">No artists found</li>' );
					return;
				}
				response.data.forEach( function ( artist ) {
					var $li = $( '<li class="pc-artist-result">' )
						.text( artist.name )
						.attr( 'data-id', artist.id )
						.attr( 'data-name', artist.name );
					$results.append( $li );
				} );
			} );
		}, 300 );
	} );

	$( document ).on( 'click', '.pc-artist-result', function () {
		addArtist( $( this ).data( 'id' ), $( this ).data( 'name' ) );
		$results.empty();
		$search.val( '' );
	} );

	$( document ).on( 'click', '.pc-artist-remove', function ( e ) {
		e.preventDefault();
		var $li = $( this ).closest( 'li' );
		var id = String( $li.data( 'id' ) );
		saveSelectedIds( selectedIds().filter( function ( existing ) {
			return existing !== id;
		} ) );
		$li.remove();
	} );

	/* ---------------- Exhibition image picker ---------------- */

	var frame;
	$( '#pc-exhibition-image-select' ).on( 'click', function ( e ) {
		e.preventDefault();
		if ( frame ) {
			frame.open();
			return;
		}
		frame = wp.media( { title: 'Select Exhibition Image', multiple: false } );
		frame.on( 'select', function () {
			var attachment = frame.state().get( 'selection' ).first().toJSON();
			$( '#pc_image_id' ).val( attachment.id );
			var preview = attachment.sizes && attachment.sizes.medium ? attachment.sizes.medium.url : attachment.url;
			$( '#pc-exhibition-image-preview' ).html( '<img src="' + preview + '" style="max-width:100%;height:auto;">' );
			$( '#pc-exhibition-image-remove' ).show();
		} );
		frame.open();
	} );

	$( '#pc-exhibition-image-remove' ).on( 'click', function ( e ) {
		e.preventDefault();
		$( '#pc_image_id' ).val( '' );
		$( '#pc-exhibition-image-preview' ).empty();
		$( this ).hide();
	} );

	/* ---------------- Staged image quick-select ---------------- */

	$( document ).on( 'click', '.pc-staged-image-select', function ( e ) {
		e.preventDefault();
		var id = $( this ).data( 'id' );

		$( '.pc-staged-image-select img' ).css( 'border-color', 'transparent' );
		$( this ).find( 'img' ).css( 'border-color', '#2271b1' );

		// Selecting a staged image implies "use a specific image".
		$( 'input[name="pc_image_mode"][value="specific"]' ).prop( 'checked', true ).trigger( 'change' );

		$( '#pc_image_id' ).val( id );

		var thumbSrc = $( this ).find( 'img' ).attr( 'src' );
		// Use the thumbnail as an immediate low-res preview; full editor reload will show the real size.
		$( '#pc-exhibition-image-preview' ).html( '<img src="' + thumbSrc + '" style="max-width:100%;height:auto;">' );
		$( '#pc-exhibition-image-remove' ).show();
	} );

	/* ---------------- Image mode toggle ---------------- */

	$( 'input[name="pc_image_mode"]' ).on( 'change', function () {
		var useArtist = 'artist' === $( 'input[name="pc_image_mode"]:checked' ).val();
		$( '#pc-specific-image-fields' ).toggle( ! useArtist );
		$( '#pc-artist-image-note' ).toggle( useArtist );
	} );

	/* ---------------- 2nd Friday auto-computation ---------------- */

	function secondFridayOf( monthValue ) {
		// monthValue is "YYYY-MM" from a <input type="month">
		if ( ! monthValue ) {
			return '';
		}
		var parts = monthValue.split( '-' );
		var year = parseInt( parts[ 0 ], 10 );
		var month = parseInt( parts[ 1 ], 10 ) - 1;
		var firstOfMonth = new Date( year, month, 1 );
		var dayOfWeek = firstOfMonth.getDay(); // 0=Sun ... 6=Sat, Friday=5
		var offsetToFirstFriday = ( 5 - dayOfWeek + 7 ) % 7;
		var secondFridayDate = new Date( year, month, 1 + offsetToFirstFriday + 7 );
		var mm = String( secondFridayDate.getMonth() + 1 ).padStart( 2, '0' );
		var dd = String( secondFridayDate.getDate() ).padStart( 2, '0' );
		return secondFridayDate.getFullYear() + '-' + mm + '-' + dd;
	}

	function updateEventDateField() {
		var isSecondFriday = $( '#pc_second_friday_yes' ).is( ':checked' );
		var $dateField = $( '#pc_event_date' );

		$dateField.prop( 'readonly', isSecondFriday );
		$( '#pc_second_friday_note' ).toggle( isSecondFriday );

		if ( isSecondFriday ) {
			var computed = secondFridayOf( $( '#pc_month' ).val() );
			if ( computed ) {
				$dateField.val( computed );
			}
		}
	}

	$( '#pc_month, #pc_second_friday_yes, #pc_second_friday_no' ).on( 'change input', updateEventDateField );

	/* ---------------- New Artists repeater ---------------- */

	var $wrap = $( '#pc-new-artists' );
	var templateHtml = document.getElementById( 'pc-new-artist-template' )
		? document.getElementById( 'pc-new-artist-template' ).innerHTML
		: '';
	var uploadedImages = []; // images added via "Upload an image for this exhibition" this session

	function appendImageToRow( $row, img ) {
		var index = $row.data( 'index' );
		var $section = $row.find( '.pc-staged-images-section' );
		var $list = $section.find( '.pc-staged-checklist' );

		$section.show();

		var $li = $( '<li style="width:100px;text-align:center;"></li>' );
		var $label = $( '<label></label>' );
		var $thumb = $( '<img>' ).attr( 'src', img.url ).css( { width: '100px', height: '100px', 'object-fit': 'cover', display: 'block' } );
		var $checkbox = $( '<input type="checkbox">' )
			.attr( 'name', 'pc_new_artists[' + index + '][staged_image_ids][]' )
			.val( img.id );
		$label.append( $thumb ).append( $checkbox );

		var $titleInput = $( '<input type="text">' )
			.attr( 'name', 'pc_new_artists[' + index + '][staged_titles][' + img.id + ']' )
			.val( img.title )
			.css( { width: '100px', 'font-size': '11px', padding: '2px' } );

		$li.append( $label ).append( $titleInput );
		$list.append( $li );
	}

	$( '#pc-upload-exhibition-image' ).on( 'click', function ( e ) {
		e.preventDefault();
		var frame = wp.media( { title: 'Upload an image for this exhibition', multiple: false } );
		frame.on( 'select', function () {
			var attachment = frame.state().get( 'selection' ).first().toJSON();
			var defaultTitle = attachment.title || 'Untitled';
			var caption = window.prompt( 'Caption for this image (you can also edit it per-artist below):', defaultTitle );
			if ( null === caption ) {
				return; // user cancelled the prompt
			}
			caption = caption.trim() || 'Untitled';

			var thumbUrl = attachment.sizes && attachment.sizes.thumbnail ? attachment.sizes.thumbnail.url : attachment.url;
			var img = { id: attachment.id, url: thumbUrl, title: caption };
			uploadedImages.push( img );

			$( '.pc-new-artist' ).each( function () {
				appendImageToRow( $( this ), img );
			} );
		} );
		frame.open();
	} );

	$( '#pc-add-artist' ).on( 'click', function () {
		var index = parseInt( $wrap.attr( 'data-next-index' ), 10 ) || 1;
		var html = templateHtml.split( '__INDEX__' ).join( index );
		var $newRow = $( '<div class="pc-new-artist" data-index="' + index + '"></div>' ).html( html );
		$wrap.append( $newRow );
		$wrap.attr( 'data-next-index', index + 1 );
		initExistingArtistSelects( $newRow );

		// Carry over any images uploaded earlier in this session so newer
		// artist rows can be checked for them too.
		uploadedImages.forEach( function ( img ) {
			appendImageToRow( $newRow, img );
		} );
	} );

	$( document ).on( 'click', '.pc-remove-artist', function ( e ) {
		e.preventDefault();
		if ( $( '.pc-new-artist' ).length <= 1 ) {
			// Keep at least one row present; just clear it instead of removing entirely.
			var $fields = $( this ).closest( '.pc-new-artist-fields' );
			$fields.find( 'input[type="text"], input[type="url"], textarea' ).val( '' );
			return;
		}
		$( this ).closest( '.pc-new-artist' ).remove();
	} );

	$( document ).on( 'click', '.pc-add-image', function () {
		var $imagesWrap = $( this ).closest( 'p' ).prev( '.pc-artist-images' );
		var $rows = $imagesWrap.find( '.pc-artist-image-row' );
		var $lastRow = $rows.last();
		var $newRow = $lastRow.clone();

		// Bump the image index within this artist's [images][n] fields and clear values.
		var newImageIndex = $rows.length;
		$newRow.find( 'input' ).each( function () {
			var name = $( this ).attr( 'name' );
			if ( name ) {
				name = name.replace( /\[images\]\[\d+\]/, '[images][' + newImageIndex + ']' );
				$( this ).attr( 'name', name );
			}
			$( this ).val( '' );
		} );

		$imagesWrap.append( $newRow );
	} );

	$( document ).on( 'click', '.pc-remove-image', function () {
		var $imagesWrap = $( this ).closest( '.pc-artist-images' );
		if ( $imagesWrap.find( '.pc-artist-image-row' ).length <= 1 ) {
			$( this ).closest( '.pc-artist-image-row' ).find( 'input' ).val( '' );
			return;
		}
		$( this ).closest( '.pc-artist-image-row' ).remove();
	} );

	// Initialize state on page load.
	updateEventDateField();
	$( 'input[name="pc_image_mode"]:checked' ).trigger( 'change' );

	/* ---------------- Existing-artist dropdown per row ---------------- */

	var existingArtists = ( window.PaintChipAdmin && PaintChipAdmin.existingArtists ) || [];

	function populateExistingArtistSelect( $select ) {
		existingArtists.forEach( function ( artist ) {
			$select.append( $( '<option></option>' ).val( artist.id ).text( artist.name ) );
		} );
	}

	function initExistingArtistSelects( $scope ) {
		$scope.find( '.pc-existing-artist-select' ).each( function () {
			var $select = $( this );
			if ( ! $select.data( 'populated' ) ) {
				populateExistingArtistSelect( $select );
				$select.data( 'populated', true );

				var preselect = $select.data( 'preselect' );
				if ( preselect ) {
					$select.val( String( preselect ) );
					$select.trigger( 'change' );
				}
			}
		} );
	}

	initExistingArtistSelects( $( document ) );

	$( document ).on( 'change', '.pc-existing-artist-select', function () {
		var $row = $( this ).closest( '.pc-new-artist-fields' );
		var $nameField = $row.find( '.pc-artist-name' );
		var $existingIdField = $row.find( '.pc-existing-id' );
		var $metaFields = $row.find( '.pc-artist-meta-fields' );
		var selectedId = $( this ).val();

		if ( selectedId ) {
			$nameField.val( $( this ).find( 'option:selected' ).text() ).prop( 'readonly', true );
			$existingIdField.val( selectedId );
			$metaFields.hide();
		} else {
			$nameField.val( '' ).prop( 'readonly', false );
			$existingIdField.val( '' );
			$metaFields.show();
		}
	} );
} );
