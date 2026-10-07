/**
 * Editor UI for the TrekPilot blocks (no build step: plain ES5 against the wp.* globals).
 * Each block is server-rendered, so the editor shows the real output via ServerSideRender.
 */
( function ( wp ) {
	'use strict';

	var el = wp.element.createElement;
	var Fragment = wp.element.Fragment;
	var __ = wp.i18n.__;
	var registerBlockType = wp.blocks.registerBlockType;
	var InspectorControls = wp.blockEditor.InspectorControls;
	var useBlockProps = wp.blockEditor.useBlockProps;
	var PanelBody = wp.components.PanelBody;
	var SelectControl = wp.components.SelectControl;
	var RangeControl = wp.components.RangeControl;
	var ToggleControl = wp.components.ToggleControl;
	var CheckboxControl = wp.components.CheckboxControl;
	var Placeholder = wp.components.Placeholder;
	var Spinner = wp.components.Spinner;
	var ServerSideRender = wp.serverSideRender;
	var useSelect = wp.data.useSelect;

	/** All treks as { value, label } plus a loading flag. */
	function useTreks() {
		return useSelect( function ( select ) {
			var store = select( 'core' );
			var query = { per_page: -1, orderby: 'title', order: 'asc', status: 'publish,draft,private' };
			var records = store.getEntityRecords( 'postType', 'trekpilot_trek', query );
			return {
				loading: records === null || records === undefined,
				treks: ( records || [] ).map( function ( post ) {
					var title = post.title && ( post.title.raw || post.title.rendered );
					return { value: post.id, label: title || ( '#' + post.id ) };
				} )
			};
		}, [] );
	}

	function useCurrentPostId() {
		return useSelect( function ( select ) {
			var editor = select( 'core/editor' );
			return editor && editor.getCurrentPostId ? editor.getCurrentPostId() : 0;
		}, [] );
	}

	/** Preview wrapper: the output is live markup, so make it inert in the editor. */
	function Preview( props ) {
		return el( 'div', { className: 'trekpilot-block-preview' },
			el( ServerSideRender, {
				block: props.block,
				attributes: props.attributes,
				urlQueryArgs: { post_id: props.postId || 0 },
				LoadingResponsePlaceholder: function () {
					return el( Placeholder, {}, el( Spinner ) );
				}
			} )
		);
	}

	/** Shared edit component for the blocks that target a single trek. */
	function singleTrekEdit( blockName ) {
		return function ( props ) {
			var data = useTreks();
			var postId = useCurrentPostId();
			var blockProps = useBlockProps();
			var options = [ { value: 0, label: __( 'Current Post / Trek Page', 'trekpilot' ) } ].concat( data.treks );

			return el( Fragment, {},
				el( InspectorControls, {},
					el( PanelBody, { title: __( 'Configuration', 'trekpilot' ), initialOpen: true },
						el( SelectControl, {
							label: __( 'Select Trek', 'trekpilot' ),
							value: props.attributes.trekId,
							options: options,
							onChange: function ( value ) {
								props.setAttributes( { trekId: parseInt( value, 10 ) || 0 } );
							}
						} )
					)
				),
				el( 'div', blockProps,
					el( Preview, { block: blockName, attributes: props.attributes, postId: postId } )
				)
			);
		};
	}

	registerBlockType( 'trekpilot/trek-details', {
		edit: singleTrekEdit( 'trekpilot/trek-details' ),
		save: function () { return null; }
	} );

	registerBlockType( 'trekpilot/trek-booking', {
		edit: singleTrekEdit( 'trekpilot/trek-booking' ),
		save: function () { return null; }
	} );

	registerBlockType( 'trekpilot/trek-archive', {
		edit: function ( props ) {
			var a = props.attributes;
			var set = props.setAttributes;
			var data = useTreks();
			var postId = useCurrentPostId();
			var blockProps = useBlockProps();
			var manual = a.source === 'manual';

			function toggleTrek( id, checked ) {
				var current = ( a.selectedTreks || [] ).slice();
				var index = current.indexOf( id );
				if ( checked && index === -1 ) {
					current.push( id );
				} else if ( ! checked && index !== -1 ) {
					current.splice( index, 1 );
				}
				set( { selectedTreks: current } );
			}

			return el( Fragment, {},
				el( InspectorControls, {},
					el( PanelBody, { title: __( 'Query', 'trekpilot' ), initialOpen: true },
						el( SelectControl, {
							label: __( 'Source', 'trekpilot' ),
							value: a.source,
							options: [
								{ value: 'all', label: __( 'All', 'trekpilot' ) },
								{ value: 'manual', label: __( 'Manual Selection', 'trekpilot' ) }
							],
							onChange: function ( value ) { set( { source: value } ); }
						} ),
						manual && ( data.loading
							? el( Spinner )
							: data.treks.map( function ( trek ) {
								return el( CheckboxControl, {
									key: trek.value,
									label: trek.label,
									checked: ( a.selectedTreks || [] ).indexOf( trek.value ) !== -1,
									onChange: function ( checked ) { toggleTrek( trek.value, checked ); }
								} );
							} ) ),
						! manual && el( SelectControl, {
							label: __( 'Order By', 'trekpilot' ),
							value: a.orderby,
							options: [
								{ value: 'date', label: __( 'Publish Date', 'trekpilot' ) },
								{ value: 'title', label: __( 'Title', 'trekpilot' ) },
								{ value: 'menu_order', label: __( 'Menu Order', 'trekpilot' ) },
								{ value: 'rand', label: __( 'Random', 'trekpilot' ) }
							],
							onChange: function ( value ) { set( { orderby: value } ); }
						} ),
						! manual && el( SelectControl, {
							label: __( 'Order', 'trekpilot' ),
							value: a.order,
							options: [
								{ value: 'DESC', label: __( 'Descending', 'trekpilot' ) },
								{ value: 'ASC', label: __( 'Ascending', 'trekpilot' ) }
							],
							onChange: function ( value ) { set( { order: value } ); }
						} )
					),
					el( PanelBody, { title: __( 'Layout', 'trekpilot' ), initialOpen: true },
						! manual && el( RangeControl, {
							label: __( 'Treks Per Page', 'trekpilot' ),
							value: a.postsPerPage,
							min: 1,
							max: 100,
							onChange: function ( value ) { set( { postsPerPage: value || 9 } ); }
						} ),
						el( RangeControl, {
							label: __( 'Columns', 'trekpilot' ),
							value: a.columns,
							min: 1,
							max: 6,
							onChange: function ( value ) { set( { columns: value || 3 } ); }
						} ),
						el( ToggleControl, {
							label: __( 'Show Excerpt', 'trekpilot' ),
							checked: !! a.showExcerpt,
							onChange: function ( value ) { set( { showExcerpt: value } ); }
						} ),
						el( ToggleControl, {
							label: __( 'Show Price', 'trekpilot' ),
							checked: !! a.showPrice,
							onChange: function ( value ) { set( { showPrice: value } ); }
						} )
					),
					! manual && el( PanelBody, { title: __( 'Pagination', 'trekpilot' ), initialOpen: false },
						el( ToggleControl, {
							label: __( 'Show Pagination', 'trekpilot' ),
							checked: !! a.showPagination,
							onChange: function ( value ) { set( { showPagination: value } ); }
						} )
					)
				),
				el( 'div', blockProps,
					el( Preview, { block: 'trekpilot/trek-archive', attributes: a, postId: postId } )
				)
			);
		},
		save: function () { return null; }
	} );
} )( window.wp );
