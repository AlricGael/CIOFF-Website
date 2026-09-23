/**
 * Annuaire des adhérents : carte Leaflet, filtres par type, recherche, liste triée.
 * Aussi : mini-carte des fiches adhérents.
 */
( function () {
	'use strict';

	var PAR_PAGE = 24;

	function normaliser( s ) {
		return ( s || '' ).toString().toLowerCase().normalize( 'NFD' ).replace( /[̀-ͯ]/g, '' );
	}

	function echapper( s ) {
		var d = document.createElement( 'div' );
		d.textContent = s == null ? '' : s;
		return d.innerHTML;
	}

	function icone( couleur, svg ) {
		return L.divIcon( {
			className: 'cioff-marqueur',
			html: '<span class="cioff-marqueur__pin" style="--cioff-type:' + couleur + '">' + svg + '</span>',
			iconSize: [ 30, 40 ],
			iconAnchor: [ 15, 40 ],
			popupAnchor: [ 0, -36 ],
		} );
	}

	function fondDeCarte( map, tiles ) {
		L.tileLayer( tiles.url, { attribution: tiles.attribution, maxZoom: 18, subdomains: 'abcd' } ).addTo( map );
	}

	function lieu( a ) {
		return [ a.ville, a.deptNom ? a.deptNom + ' (' + a.dept + ')' : '' ].filter( Boolean ).join( ' · ' );
	}

	function badge( type ) {
		return '<span class="cioff-badge" style="--cioff-type:' + type.couleur + '">' + type.icone + ' ' + echapper( type.label ) + '</span>';
	}

	function initAnnuaire( root ) {
		var config = JSON.parse( root.getAttribute( 'data-config' ) );
		var types = config.types;
		var adherents = config.adherents;
		var el = {
			carte: root.querySelector( '[data-carte]' ),
			q: root.querySelector( '[data-q]' ),
			filtres: root.querySelectorAll( '[data-filtre]' ),
			liste: root.querySelector( '[data-liste]' ),
			resultat: root.querySelector( '[data-resultat]' ),
			tri: root.querySelector( '[data-tri]' ),
			plus: root.querySelector( '[data-plus]' ),
		};
		var affiches = PAR_PAGE;

		// Texte de recherche pré-calculé.
		adherents.forEach( function ( a ) {
			a._recherche = normaliser( [ a.nom, a.ville, a.deptNom, a.dept, a.region, types[ a.type ].label ].join( ' ' ) );
		} );

		// Nombre par type à côté des cases.
		config.visibles.forEach( function ( t ) {
			var n = adherents.filter( function ( a ) { return a.type === t; } ).length;
			var span = root.querySelector( '[data-nb="' + t + '"]' );
			if ( span ) {
				span.textContent = '(' + n + ')';
			}
		} );

		// Carte.
		var map = L.map( el.carte, { scrollWheelZoom: false } ).setView( [ 46.6, 2.4 ], 6 );
		fondDeCarte( map, config.tiles );
		map.on( 'focus', function () { map.scrollWheelZoom.enable(); } );
		map.on( 'blur', function () { map.scrollWheelZoom.disable(); } );

		var groupe = L.markerClusterGroup( { showCoverageOnHover: false, maxClusterRadius: 40 } );
		map.addLayer( groupe );

		adherents.forEach( function ( a ) {
			if ( a.lat === null || a.lng === null ) {
				return;
			}
			var t = types[ a.type ];
			a._marqueur = L.marker( [ a.lat, a.lng ], { icon: icone( t.couleur, t.icone ), title: a.nom, alt: a.nom } );
			a._marqueur.bindPopup(
				'<div class="cioff-popup">' +
					( a.image ? '<img src="' + echapper( a.image ) + '" alt="" loading="lazy">' : '' ) +
					badge( t ) +
					'<h3>' + echapper( a.nom ) + '</h3>' +
					( lieu( a ) ? '<p class="cioff-popup__lieu">' + echapper( lieu( a ) ) + '</p>' : '' ) +
					( a.extrait ? '<p>' + echapper( a.extrait ) + '</p>' : '' ) +
					'<p><a href="' + echapper( a.url ) + '">Voir la fiche complète →</a></p>' +
				'</div>',
				{ maxWidth: 280 }
			);
		} );

		function selection() {
			var actifs = [];
			el.filtres.forEach( function ( c ) {
				if ( c.checked ) {
					actifs.push( c.value );
				}
			} );
			var q = normaliser( el.q ? el.q.value.trim() : '' );
			return adherents.filter( function ( a ) {
				return actifs.indexOf( a.type ) !== -1 && ( ! q || a._recherche.indexOf( q ) !== -1 );
			} );
		}

		function trier( liste ) {
			var cle = el.tri ? el.tri.value : 'nom';
			var ordreTypes = Object.keys( types );
			return liste.slice().sort( function ( a, b ) {
				var r = 0;
				if ( cle === 'type' ) {
					r = ordreTypes.indexOf( a.type ) - ordreTypes.indexOf( b.type );
				} else if ( cle === 'region' ) {
					r = ( a.region || '~' ).localeCompare( b.region || '~', 'fr' );
				}
				return r || a.nom.localeCompare( b.nom, 'fr', { sensitivity: 'base' } );
			} );
		}

		function majListe( liste ) {
			if ( ! el.liste ) {
				return;
			}
			var tries = trier( liste );
			el.resultat.textContent = liste.length + ( liste.length > 1 ? ' adhérents' : ' adhérent' );
			el.liste.innerHTML = tries.slice( 0, affiches ).map( function ( a ) {
				var t = types[ a.type ];
				return '<li class="cioff-carte-adherent">' +
					'<a href="' + echapper( a.url ) + '">' +
					( a.image ? '<img src="' + echapper( a.image ) + '" alt="" loading="lazy">' : '<span class="cioff-carte-adherent__vide" style="--cioff-type:' + t.couleur + '">' + t.icone + '</span>' ) +
					'<span class="cioff-carte-adherent__texte">' + badge( t ) +
					'<strong>' + echapper( a.nom ) + '</strong>' +
					'<span>' + echapper( lieu( a ) ) + '</span></span></a></li>';
			} ).join( '' ) || '<li class="cioff-annuaire__aucun">Aucun adhérent ne correspond à votre recherche.</li>';
			el.plus.hidden = tries.length <= affiches;
		}

		function majCarte( liste, zoom ) {
			groupe.clearLayers();
			var marqueurs = liste.map( function ( a ) { return a._marqueur; } ).filter( Boolean );
			groupe.addLayers( marqueurs );
			if ( zoom && marqueurs.length ) {
				// Zoom automatique sur la sélection.
				map.fitBounds( L.featureGroup( marqueurs ).getBounds(), { padding: [ 40, 40 ], maxZoom: 11 } );
			}
		}

		function maj( zoom ) {
			var liste = selection();
			majCarte( liste, zoom );
			majListe( liste );
		}

		var minuteur;
		if ( el.q ) {
			el.q.addEventListener( 'input', function () {
				clearTimeout( minuteur );
				affiches = PAR_PAGE;
				minuteur = setTimeout( function () { maj( true ); }, 250 );
			} );
		}
		el.filtres.forEach( function ( c ) {
			c.addEventListener( 'change', function () {
				affiches = PAR_PAGE;
				maj( true );
			} );
		} );
		if ( el.tri ) {
			el.tri.addEventListener( 'change', function () { maj( false ); } );
		}
		if ( el.plus ) {
			el.plus.addEventListener( 'click', function () {
				affiches += PAR_PAGE;
				majListe( selection() );
			} );
		}

		maj( false );
		// Recalcule la taille si la carte était masquée au chargement (onglets, etc.).
		setTimeout( function () { map.invalidateSize(); }, 200 );
	}

	function initMiniCarte( el ) {
		var c = JSON.parse( el.getAttribute( 'data-minicarte' ) );
		var map = L.map( el, { scrollWheelZoom: false, dragging: ! L.Browser.mobile, zoomControl: true } ).setView( [ c.lat, c.lng ], c.zoom );
		fondDeCarte( map, c.tiles );
		L.marker( [ c.lat, c.lng ], { icon: icone( c.couleur, c.icone ), keyboard: false } ).addTo( map );
	}

	function init() {
		if ( typeof L === 'undefined' ) {
			return;
		}
		document.querySelectorAll( '.cioff-annuaire[data-config]' ).forEach( initAnnuaire );
		document.querySelectorAll( '[data-minicarte]' ).forEach( initMiniCarte );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();
