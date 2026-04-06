/**
 * RankReady — AI Summary Gutenberg Block
 * Dynamic block with full style controls. No build step required.
 */
( function () {
	'use strict';

	var el                = wp.element.createElement;
	var Fragment          = wp.element.Fragment;
	var useState          = wp.element.useState;
	var useEffect         = wp.element.useEffect;
	var registerBlockType = wp.blocks.registerBlockType;
	var useBlockProps     = wp.blockEditor.useBlockProps;
	var InspectorControls = wp.blockEditor.InspectorControls;
	var PanelBody         = wp.components.PanelBody;
	var PanelRow          = wp.components.PanelRow;
	var ToggleControl     = wp.components.ToggleControl;
	var TextControl       = wp.components.TextControl;
	var SelectControl     = wp.components.SelectControl;
	var RangeControl      = wp.components.RangeControl;
	var ColorPalette      = wp.components.ColorPalette;
	var BaseControl       = wp.components.BaseControl;
	var Button            = wp.components.Button;
	var Notice            = wp.components.Notice;
	var Spinner           = wp.components.Spinner;
	var useSelect         = wp.data.useSelect;
	var apiFetch          = wp.apiFetch;

	var defaults = ( window.rrBlockData && window.rrBlockData.defaults ) ? window.rrBlockData.defaults : {
		label: 'Key Takeaways', showLabel: true, headingTag: 'h4'
	};

	function decodeSummary( raw ) {
		if ( ! raw ) return { type: 'empty', data: [] };
		try {
			var parsed = JSON.parse( raw );
			if ( parsed && Array.isArray( parsed.bullets ) && parsed.bullets.length ) {
				return { type: 'bullets', data: parsed.bullets };
			}
		} catch ( e ) { /* not JSON */ }
		return { type: 'text', data: raw };
	}

	// Color label helper
	function colorControl( label, value, onChange ) {
		return el( BaseControl, { label: label, __nextHasNoMarginBottom: true },
			el( ColorPalette, {
				value: value || undefined,
				onChange: onChange,
				clearable: true,
			} )
		);
	}

	registerBlockType( 'rankready/ai-summary', {
		title:       'AI Summary',
		icon:        'editor-ul',
		category:    'text',
		description: 'Displays AI-generated key takeaways. Auto-generates on publish/update.',
		keywords:    [ 'summary', 'takeaways', 'ai', 'seo', 'rankready' ],

		attributes: {
			// Content
			label:             { type: 'string',  default: '' },
			showLabel:         { type: 'boolean', default: true },
			headingTag:        { type: 'string',  default: '' },
			// Box
			boxBgColor:        { type: 'string',  default: '' },
			boxBorderColor:    { type: 'string',  default: '' },
			boxBorderWidth:    { type: 'number',  default: 3 },
			boxBorderRadius:   { type: 'number',  default: 0 },
			boxBorderPosition: { type: 'string',  default: 'left' },
			boxPadding:        { type: 'number',  default: 0 },
			// Label
			labelColor:        { type: 'string',  default: '' },
			labelFontSize:     { type: 'number',  default: 0 },
			// Bullets
			bulletColor:       { type: 'string',  default: '' },
			bulletFontSize:    { type: 'number',  default: 0 },
			bulletLineHeight:  { type: 'number',  default: 0 },
			bulletSpacing:     { type: 'number',  default: 0 },
			bulletMarkerColor: { type: 'string',  default: '' },
		},

		edit: function ( props ) {
			var attrs    = props.attributes;
			var setAttrs = props.setAttributes;

			var effectiveLabel    = attrs.label || defaults.label;
			var effectiveShowLabel = attrs.showLabel;
			var effectiveTag      = attrs.headingTag || defaults.headingTag;

			var _raw       = useState( '' );
			var raw        = _raw[0]; var setRaw = _raw[1];
			var _loading   = useState( false );
			var loading    = _loading[0]; var setLoading = _loading[1];
			var _error     = useState( '' );
			var error      = _error[0]; var setError = _error[1];
			var _hasKey    = useState( true );
			var hasKey     = _hasKey[0]; var setHasKey = _hasKey[1];
			var _cooldown  = useState( 0 );
			var cooldown   = _cooldown[0]; var setCooldown = _cooldown[1];

			var postId = useSelect( function ( select ) {
				return select( 'core/editor' ).getCurrentPostId();
			}, [] );

			useEffect( function () {
				if ( ! postId ) return;
				apiFetch( { path: '/rankready/v1/summary/' + postId } )
					.then( function ( data ) {
						setRaw( data.summary || '' );
						setHasKey( data.has_key !== false );
					} )
					.catch( function () {} );
			}, [ postId ] );

			useEffect( function () {
				if ( cooldown <= 0 ) return;
				var t = setTimeout( function () {
					setCooldown( function ( c ) { return c - 1; } );
				}, 1000 );
				return function () { clearTimeout( t ); };
			}, [ cooldown ] );

			var isSaving     = useSelect( function ( s ) { return s( 'core/editor' ).isSavingPost(); }, [] );
			var _wasSaving   = useState( false );
			var wasSaving    = _wasSaving[0]; var setWasSaving = _wasSaving[1];

			useEffect( function () {
				if ( wasSaving && ! isSaving && postId ) {
					setTimeout( function () {
						apiFetch( { path: '/rankready/v1/summary/' + postId } )
							.then( function ( data ) { setRaw( data.summary || '' ); } )
							.catch( function () {} );
					}, 4000 );
				}
				setWasSaving( isSaving );
			}, [ isSaving ] );

			function handleRegenerate() {
				if ( ! postId || loading || cooldown > 0 ) return;
				setLoading( true );
				setError( '' );
				apiFetch( { path: '/rankready/v1/regenerate/' + postId, method: 'POST' } )
					.then( function ( data ) {
						setRaw( data.summary || '' );
						setLoading( false );
						setCooldown( 60 );
					} )
					.catch( function ( err ) {
						var msg = ( err && err.message ) ? err.message : 'Generation failed.';
						if ( err && err.code === 'rr_rate_limited' ) {
							var seconds = parseInt( msg.match( /\d+/ ) );
							if ( seconds ) setCooldown( seconds );
						}
						setError( msg );
						setLoading( false );
					} );
			}

			var summary    = decodeSummary( raw );
			var HeadingTag = effectiveTag || 'h4';
			var regenLabel = loading ? 'Generating...' : cooldown > 0 ? 'Wait ' + cooldown + 's' : 'Regenerate';

			// Build preview styles
			var boxStyle = {};
			if ( attrs.boxBgColor ) boxStyle.backgroundColor = attrs.boxBgColor;
			if ( attrs.boxBorderPosition === 'none' ) {
				boxStyle.border = 'none';
			} else if ( attrs.boxBorderPosition === 'all' ) {
				boxStyle.borderLeft = 'none';
				boxStyle.border = ( attrs.boxBorderWidth || 3 ) + 'px solid ' + ( attrs.boxBorderColor || 'currentColor' );
			} else {
				if ( attrs.boxBorderWidth ) boxStyle.borderLeftWidth = attrs.boxBorderWidth + 'px';
				if ( attrs.boxBorderColor ) boxStyle.borderLeftColor = attrs.boxBorderColor;
			}
			if ( attrs.boxBorderRadius ) boxStyle.borderRadius = attrs.boxBorderRadius + 'px';
			if ( attrs.boxPadding ) boxStyle.padding = attrs.boxPadding + 'px';
			if ( attrs.bulletMarkerColor ) boxStyle['--rr-marker-color'] = attrs.bulletMarkerColor;

			var labelStyle = {};
			if ( attrs.labelColor ) labelStyle.color = attrs.labelColor;
			if ( attrs.labelFontSize ) labelStyle.fontSize = attrs.labelFontSize + 'px';

			var bulletStyle = {};
			if ( attrs.bulletColor ) bulletStyle.color = attrs.bulletColor;
			if ( attrs.bulletFontSize ) bulletStyle.fontSize = attrs.bulletFontSize + 'px';
			if ( attrs.bulletLineHeight ) bulletStyle.lineHeight = attrs.bulletLineHeight;
			if ( attrs.bulletSpacing ) bulletStyle.marginBottom = attrs.bulletSpacing + 'px';

			var blockProps = useBlockProps( { className: 'rr-summary rr-editor-preview', style: boxStyle } );

			return el( Fragment, null,

				// ── Inspector Controls ─────────────────────────────────────────
				el( InspectorControls, null,

					// Panel: Summary Settings
					el( PanelBody, { title: 'Summary Settings', initialOpen: true },
						el( ToggleControl, {
							label: 'Show label',
							checked: effectiveShowLabel,
							onChange: function ( v ) { setAttrs( { showLabel: v } ); },
							__nextHasNoMarginBottom: true,
						} ),

						effectiveShowLabel && el( TextControl, {
							label: 'Label text',
							value: effectiveLabel,
							placeholder: defaults.label,
							onChange: function ( v ) { setAttrs( { label: v } ); },
							help: ! attrs.label ? '(Using global default)' : '',
							__nextHasNoMarginBottom: true,
						} ),

						effectiveShowLabel && el( SelectControl, {
							label: 'Label tag',
							value: effectiveTag,
							options: [
								{ label: 'H2', value: 'h2' }, { label: 'H3', value: 'h3' },
								{ label: 'H4', value: 'h4' }, { label: 'H5', value: 'h5' },
								{ label: 'H6', value: 'h6' }, { label: 'P',  value: 'p'  },
							],
							onChange: function ( v ) { setAttrs( { headingTag: v } ); },
							help: ! attrs.headingTag ? '(Global default: ' + defaults.headingTag + ')' : '',
							__nextHasNoMarginBottom: true,
						} ),

						el( 'div', { style: { marginTop: '12px' } },
							el( Button, {
								variant: 'secondary',
								isBusy: loading,
								disabled: loading || cooldown > 0 || ! postId || ! hasKey,
								onClick: handleRegenerate,
								style: { width: '100%', justifyContent: 'center' },
							}, regenLabel )
						),

						! hasKey && el( 'p', { style: { color: '#d63638', fontSize: '12px', margin: '8px 0 0' } },
							'API key missing \u2014 Settings \u2192 RankReady.'
						),

						el( 'p', { style: { color: '#757575', fontSize: '11px', margin: '8px 0 0', fontStyle: 'italic' } },
							'Summary auto-generates on publish/update.'
						)
					),

					// Panel: Box Style
					el( PanelBody, { title: 'Box Style', initialOpen: false },
						colorControl( 'Background Color', attrs.boxBgColor, function ( v ) { setAttrs( { boxBgColor: v } ); } ),
						colorControl( 'Border Color', attrs.boxBorderColor, function ( v ) { setAttrs( { boxBorderColor: v } ); } ),

						el( SelectControl, {
							label: 'Border Position',
							value: attrs.boxBorderPosition || 'left',
							options: [
								{ label: 'Left only', value: 'left' },
								{ label: 'All sides', value: 'all' },
								{ label: 'None', value: 'none' },
							],
							onChange: function ( v ) { setAttrs( { boxBorderPosition: v } ); },
							__nextHasNoMarginBottom: true,
						} ),

						el( RangeControl, {
							label: 'Border Width',
							value: attrs.boxBorderWidth || 3,
							onChange: function ( v ) { setAttrs( { boxBorderWidth: v } ); },
							min: 0, max: 10, step: 1,
							__nextHasNoMarginBottom: true,
						} ),

						el( RangeControl, {
							label: 'Border Radius',
							value: attrs.boxBorderRadius || 0,
							onChange: function ( v ) { setAttrs( { boxBorderRadius: v } ); },
							min: 0, max: 30, step: 1,
							__nextHasNoMarginBottom: true,
						} ),

						el( RangeControl, {
							label: 'Padding',
							value: attrs.boxPadding || 0,
							onChange: function ( v ) { setAttrs( { boxPadding: v } ); },
							min: 0, max: 60, step: 2,
							help: '0 = use default CSS',
							__nextHasNoMarginBottom: true,
						} )
					),

					// Panel: Label Style
					effectiveShowLabel && el( PanelBody, { title: 'Label Style', initialOpen: false },
						colorControl( 'Label Color', attrs.labelColor, function ( v ) { setAttrs( { labelColor: v } ); } ),

						el( RangeControl, {
							label: 'Font Size (px)',
							value: attrs.labelFontSize || 0,
							onChange: function ( v ) { setAttrs( { labelFontSize: v } ); },
							min: 0, max: 48, step: 1,
							help: '0 = inherit from theme',
							__nextHasNoMarginBottom: true,
						} )
					),

					// Panel: Bullets Style
					el( PanelBody, { title: 'Bullets Style', initialOpen: false },
						colorControl( 'Text Color', attrs.bulletColor, function ( v ) { setAttrs( { bulletColor: v } ); } ),
						colorControl( 'Marker Color', attrs.bulletMarkerColor, function ( v ) { setAttrs( { bulletMarkerColor: v } ); } ),

						el( RangeControl, {
							label: 'Font Size (px)',
							value: attrs.bulletFontSize || 0,
							onChange: function ( v ) { setAttrs( { bulletFontSize: v } ); },
							min: 0, max: 24, step: 1,
							help: '0 = inherit',
							__nextHasNoMarginBottom: true,
						} ),

						el( RangeControl, {
							label: 'Line Height',
							value: attrs.bulletLineHeight || 0,
							onChange: function ( v ) { setAttrs( { bulletLineHeight: v } ); },
							min: 0, max: 3, step: 0.05,
							help: '0 = inherit',
							__nextHasNoMarginBottom: true,
						} ),

						el( RangeControl, {
							label: 'Space Between (px)',
							value: attrs.bulletSpacing || 0,
							onChange: function ( v ) { setAttrs( { bulletSpacing: v } ); },
							min: 0, max: 30, step: 1,
							help: '0 = use default',
							__nextHasNoMarginBottom: true,
						} )
					)
				),

				// ── Editor Preview ────────────────────────────────────────────
				el( 'div', blockProps,

					effectiveShowLabel && el( HeadingTag, { className: 'rr-label', style: labelStyle }, effectiveLabel ),

					error && el( Notice, {
						status: 'error', isDismissible: true,
						onRemove: function () { setError( '' ); },
					}, error ),

					loading
						? el( 'div', { style: { display: 'flex', alignItems: 'center', gap: '8px' } },
							el( Spinner ), el( 'span', null, 'Generating...' )
						)
						: summary.type === 'bullets'
							? el( 'ul', { className: 'rr-bullets' },
								summary.data.map( function ( b, i ) {
									return el( 'li', { className: 'rr-bullet', key: i, style: bulletStyle }, b );
								} )
							)
							: summary.type === 'text'
								? el( 'p', { className: 'rr-text', style: bulletStyle }, summary.data )
								: el( 'p', { style: { opacity: 0.5, fontStyle: 'italic', margin: 0 } },
									! hasKey
										? 'Add API key in Settings \u2192 RankReady.'
										: 'Summary will appear here after you publish this post.'
								)
				)
			);
		},

		save: function () { return null; },
	} );
} )();
