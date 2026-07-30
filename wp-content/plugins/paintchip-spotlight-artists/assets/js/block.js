( function ( blocks, element, blockEditor, components, serverSideRender, i18n ) {
	var el = element.createElement;
	var __ = i18n.__;
	var InspectorControls = blockEditor.InspectorControls;
	var PanelBody = components.PanelBody;
	var ToggleControl = components.ToggleControl;
	var TextControl = components.TextControl;
	var ServerSideRender = serverSideRender;

	blocks.registerBlockType( 'paintchip/current-spotlight', {
		edit: function ( props ) {
			var attributes = props.attributes;
			var setAttributes = props.setAttributes;
			var useCurrent = 'current' === attributes.mode;

			return el(
				'div',
				{ className: props.className },
				el(
					InspectorControls,
					{},
					el(
						PanelBody,
						{ title: __( 'Spotlight Settings', 'paintchip-spotlight' ) },
						el( ToggleControl, {
							label: __( "Automatically show this month's exhibition", 'paintchip-spotlight' ),
							checked: useCurrent,
							onChange: function ( value ) {
								setAttributes( { mode: value ? 'current' : 'manual' } );
								if ( value ) {
									setAttributes( { exhibitionId: 0 } );
								}
							},
						} ),
						! useCurrent &&
							el( TextControl, {
								label: __( 'Exhibition Post ID', 'paintchip-spotlight' ),
								help: __( 'Find this in the Exhibitions list in wp-admin.', 'paintchip-spotlight' ),
								type: 'number',
								value: attributes.exhibitionId || '',
								onChange: function ( value ) {
									setAttributes( { exhibitionId: parseInt( value, 10 ) || 0 } );
								},
							} )
					)
				),
				el( ServerSideRender, {
					block: 'paintchip/current-spotlight',
					attributes: attributes,
				} )
			);
		},
		save: function () {
			return null; // dynamic block, rendered entirely by PHP
		},
	} );
} )(
	window.wp.blocks,
	window.wp.element,
	window.wp.blockEditor,
	window.wp.components,
	window.wp.serverSideRender,
	window.wp.i18n
);
