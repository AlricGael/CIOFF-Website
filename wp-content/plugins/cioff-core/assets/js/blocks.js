/**
 * Blocs CIOFF dans l'éditeur. Les titres, icônes et attributs viennent de PHP (includes/blocks.php) ;
 * ce fichier ne décrit que l'aperçu et les réglages du panneau de droite.
 */
( function ( wp, data ) {
	var el = wp.element.createElement;
	var InspectorControls = wp.blockEditor.InspectorControls;
	var useBlockProps = wp.blockEditor.useBlockProps;
	var C = wp.components;
	var ServerSideRender = wp.serverSideRender;

	// Réglages spécifiques par bloc.
	var reglages = {
		annuaire: function ( props ) {
			var a = props.attributes;
			return [
				el( 'p', { key: 'aide' }, 'Types affichés (aucun coché = tous) :' ),
				data.types.map( function ( t ) {
					return el( C.CheckboxControl, {
						key: t.value,
						label: t.label,
						checked: a.types.indexOf( t.value ) !== -1,
						onChange: function ( on ) {
							var next = a.types.filter( function ( v ) { return v !== t.value; } );
							if ( on ) {
								next.push( t.value );
							}
							props.setAttributes( { types: next } );
						},
					} );
				} ),
				el( C.RangeControl, { key: 'h', label: 'Hauteur de la carte (px)', min: 300, max: 900, step: 20, value: a.hauteur, onChange: function ( v ) { props.setAttributes( { hauteur: v } ); } } ),
				el( C.ToggleControl, { key: 'l', label: 'Afficher la liste sous la carte', checked: a.liste, onChange: function ( v ) { props.setAttributes( { liste: v } ); } } ),
			];
		},
		agenda: function ( props ) {
			var a = props.attributes;
			return [
				el( C.RangeControl, { key: 'n', label: "Nombre d'événements", min: 1, max: 50, value: a.nombre, onChange: function ( v ) { props.setAttributes( { nombre: v } ); } } ),
				el( C.SelectControl, { key: 'c', label: 'Catégorie', value: a.categorie, options: data.categories, onChange: function ( v ) { props.setAttributes( { categorie: v } ); } } ),
				el( C.ToggleControl, { key: 'u', label: 'Seulement les événements « À la une »', checked: a.une, onChange: function ( v ) { props.setAttributes( { une: v } ); } } ),
				el( C.TextControl, { key: 'v', label: "Texte si aucun événement (vide = ne rien afficher)", value: a.vide, onChange: function ( v ) { props.setAttributes( { vide: v } ); } } ),
			];
		},
		'bouton-international': function ( props ) {
			return el( C.TextControl, { label: 'Texte du bouton', value: props.attributes.texte, onChange: function ( v ) { props.setAttributes( { texte: v } ); } } );
		},
		contact: function ( props ) {
			return el( C.TextControl, {
				label: 'Catégorie présélectionnée (facultatif)',
				help: 'Nom exact d\'une catégorie définie dans Réglages CIOFF, par ex. « Commande de matériel ».',
				value: props.attributes.categorie,
				onChange: function ( v ) { props.setAttributes( { categorie: v } ); },
			} );
		},
	};

	Object.keys( data.blocks ).forEach( function ( slug ) {
		var name = 'cioff/' + slug;
		var type = wp.blocks.getBlockType( name );
		if ( type && type.edit && type.edit.cioff ) {
			return;
		}

		var Edit = function ( props ) {
			var blockProps = useBlockProps();
			var bt = wp.blocks.getBlockType( name );
			var apercu;

			if ( slug === 'annuaire' ) {
				apercu = el( C.Placeholder, { icon: 'location-alt', label: bt.title, instructions: 'La carte interactive et la liste des adhérents s\'afficheront ici sur le site. Réglages dans le panneau de droite.' } );
			} else if ( data.blocks[ slug ].placeholder ) {
				apercu = el( C.Placeholder, { icon: bt.icon && bt.icon.src ? bt.icon.src : 'admin-generic', label: bt.title, instructions: bt.description + ' (Aperçu visible sur le site.)' } );
			} else {
				apercu = el( C.Disabled, null, el( ServerSideRender, { block: name, attributes: props.attributes, urlQueryArgs: { post_id: props.context && props.context.postId } } ) );
			}

			return el(
				'div',
				blockProps,
				reglages[ slug ] ? el( InspectorControls, null, el( C.PanelBody, { title: 'Réglages' }, reglages[ slug ]( props ) ) ) : null,
				apercu
			);
		};
		Edit.cioff = true;

		wp.blocks.registerBlockType( name, {
			edit: Edit,
			save: function () { return null; },
		} );
	} );
} )( window.wp, window.cioffBlocks || { blocks: {}, types: [], categories: [] } );
