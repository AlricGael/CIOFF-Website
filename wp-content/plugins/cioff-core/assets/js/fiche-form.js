/**
 * Formulaire de fiche adhérent : n'affiche que les champs du type choisi
 * et gère le choix d'images dans la médiathèque (administration).
 */
( function () {
	function familleCourante( form ) {
		var select = form.querySelector( '[data-cioff-type-select]' );
		if ( select ) {
			var opt = select.options[ select.selectedIndex ];
			return opt ? opt.getAttribute( 'data-famille' ) || '' : '';
		}
		return form.getAttribute( 'data-famille' ) || '';
	}

	function appliquer( form ) {
		var famille = familleCourante( form );
		form.querySelectorAll( '[data-familles]' ).forEach( function ( el ) {
			var visible = famille && el.getAttribute( 'data-familles' ).split( ' ' ).indexOf( famille ) !== -1;
			el.hidden = ! visible;
			// Un champ masqué n'est ni envoyé ni exigé.
			if ( el.classList.contains( 'cioff-field' ) ) {
				el.querySelectorAll( 'input, select, textarea, button' ).forEach( function ( i ) {
					i.disabled = ! visible;
				} );
			}
		} );
	}

	document.querySelectorAll( '[data-cioff-type-form]' ).forEach( function ( form ) {
		appliquer( form );
		var select = form.querySelector( '[data-cioff-type-select]' );
		if ( select ) {
			select.addEventListener( 'change', function () {
				appliquer( form );
			} );
		}
	} );

	// Médiathèque WordPress (uniquement dans l'administration).
	if ( ! window.wp || ! window.wp.media ) {
		return;
	}
	document.querySelectorAll( '.cioff-media' ).forEach( function ( box ) {
		var multiple = box.getAttribute( 'data-multiple' ) === '1';
		var input = box.querySelector( 'input[type=hidden]' );
		var preview = box.querySelector( '.cioff-media__preview' );
		var frame;

		box.querySelector( '.cioff-media__choose' ).addEventListener( 'click', function () {
			if ( ! frame ) {
				frame = wp.media( {
					title: multiple ? 'Choisir les photos' : 'Choisir une image',
					button: { text: 'Utiliser' },
					multiple: multiple ? 'add' : false,
					library: { type: 'image' },
				} );
				frame.on( 'open', function () {
					var selection = frame.state().get( 'selection' );
					input.value.split( ',' ).filter( Boolean ).forEach( function ( id ) {
						var a = wp.media.attachment( id );
						a.fetch();
						selection.add( a );
					} );
				} );
				frame.on( 'select', function () {
					var items = frame.state().get( 'selection' ).toJSON();
					input.value = items.map( function ( a ) { return a.id; } ).join( ',' );
					preview.innerHTML = '';
					items.forEach( function ( a ) {
						var img = document.createElement( 'img' );
						var size = a.sizes && ( a.sizes.thumbnail || a.sizes.medium );
						img.src = size ? size.url : a.url;
						img.alt = '';
						preview.appendChild( img );
					} );
				} );
			}
			frame.open();
		} );

		box.querySelector( '.cioff-media__clear' ).addEventListener( 'click', function () {
			input.value = '';
			preview.innerHTML = '';
		} );
	} );
} )();
