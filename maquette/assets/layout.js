/* Maquette : en-tête, pied de page et annuaire (reproduit le HTML généré par WordPress). */
( function () {
	var page = document.body.getAttribute( 'data-page' );
	var nav = [
		[ 'accueil', 'index.html', 'Accueil' ],
		[ 'cioff', '#', 'Le CIOFF' ],
		[ 'jeunes', '#', 'CIOFF Jeunes' ],
		[ 'annuaire', 'annuaire.html', 'Annuaire' ],
		[ 'agenda', '#', 'Agenda' ],
		[ 'outils', '#', 'Boîte à outils' ],
		[ 'mon-espace', 'mon-espace.html', 'Intranet' ],
		[ 'contact', '#', 'Contact' ],
	];

	var bandeau = '<div class="maquette-bandeau">Maquette de présentation – les adhérents, événements et images sont <strong>fictifs</strong>. ' +
		'<a href="a-valider.html">Voir l’écran de validation (administration)</a></div>';

	var entete = document.querySelector( '[data-entete]' );
	if ( entete ) {
		entete.outerHTML = bandeau +
			'<header class="cioff-entete">' +
			'<div class="large cioff-entete__haut"><a href="https://www.cioff.org" target="_blank" rel="noopener">CIOFF International ↗</a><a href="mon-espace.html">Espace adhérent</a></div>' +
			'<div class="large cioff-entete__principal">' +
			'<a class="logo" href="index.html"><span class="logo__rond" aria-hidden="true"></span>CIOFF France</a>' +
			'<nav class="nav" aria-label="Menu principal"><button class="nav__bouton" type="button" aria-expanded="false">☰ Menu</button><ul>' +
			nav.slice( 1 ).map( function ( n ) {
				return '<li><a href="' + n[1] + '"' + ( n[0] === page ? ' aria-current="page"' : '' ) + '>' + n[2] + '</a></li>';
			} ).join( '' ) +
			'</ul></nav></div></header>';
		var bouton = document.querySelector( '.nav__bouton' );
		bouton.addEventListener( 'click', function () {
			var ouvert = bouton.parentNode.classList.toggle( 'ouvert' );
			bouton.setAttribute( 'aria-expanded', ouvert );
		} );
	}

	var pied = document.querySelector( '[data-pied]' );
	if ( pied ) {
		pied.outerHTML = '<footer class="cioff-pied"><div class="large"><div class="cioff-pied__colonnes">' +
			'<div><p class="logo" style="color:#fff"><span class="logo__rond" aria-hidden="true"></span>CIOFF France</p>' +
			'<p>Conseil International des Organisations de Festivals de Folklore et d’Arts Traditionnels – section France. Partenaire officiel de l’UNESCO.</p>' +
			'<ul class="cioff-reseaux"><li><a href="#" aria-label="Facebook">f</a></li><li><a href="#" aria-label="Instagram">◎</a></li><li><a href="#" aria-label="YouTube">▶</a></li></ul></div>' +
			'<div><h3>Le site</h3><ul>' + nav.map( function ( n ) { return '<li><a href="' + n[1] + '">' + n[2] + '</a></li>'; } ).join( '' ) + '</ul></div>' +
			'<div><h3>Adhérents</h3><ul><li><a href="mon-espace.html">Espace adhérent</a></li><li><a href="#">Nous contacter</a></li><li><a href="#">Mentions légales</a></li></ul></div>' +
			'</div><p class="cioff-pied__bas">© CIOFF France – maquette</p></div></footer>';
	}

	// Annuaire : même HTML que cioff_render_annuaire() en PHP.
	var mount = document.querySelector( '[data-annuaire]' );
	if ( mount ) {
		var C = window.CIOFF;
		var hauteur = mount.getAttribute( 'data-hauteur' ) || 520;
		var avecListe = mount.getAttribute( 'data-liste' ) !== '0';
		var visibles = Object.keys( C.types );
		var config = { types: C.types, visibles: visibles, adherents: C.adherents, tiles: C.tiles };
		mount.outerHTML =
			'<div class="cioff-annuaire" data-config=\'' + JSON.stringify( config ).replace( /'/g, '&#39;' ) + '\'>' +
			'<div class="cioff-annuaire__outils">' +
			'<div class="cioff-annuaire__recherche"><label for="q">Rechercher</label><input type="search" id="q" placeholder="Nom, ville, département ou région" data-q autocomplete="off"></div>' +
			'<fieldset class="cioff-annuaire__filtres"><legend>Afficher</legend>' +
			visibles.map( function ( slug ) {
				var t = C.types[ slug ];
				return '<label class="cioff-filtre" style="--cioff-type:' + t.couleur + '"><input type="checkbox" value="' + slug + '" checked data-filtre>' +
					'<span class="cioff-filtre__pastille">' + t.icone + '</span><span>' + t.pluriel + ' <span class="cioff-filtre__nb" data-nb="' + slug + '"></span></span></label>';
			} ).join( '' ) +
			'</fieldset></div>' +
			'<div class="cioff-annuaire__carte" style="height:' + hauteur + 'px" data-carte role="region" aria-label="Carte des adhérents"></div>' +
			( avecListe ?
				'<div class="cioff-annuaire__liste-entete"><p class="cioff-annuaire__resultat" data-resultat aria-live="polite"></p>' +
				'<label>Trier par <select data-tri><option value="nom">Nom</option><option value="type">Type</option><option value="region">Région</option></select></label></div>' +
				'<ul class="cioff-annuaire__liste" data-liste></ul>' +
				'<p class="cioff-annuaire__plus"><button type="button" class="wp-element-button" data-plus hidden>Afficher plus</button></p>'
				: '' ) +
			'</div>';
	}
} )();
