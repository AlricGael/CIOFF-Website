/* Données de la maquette. Les adhérents sont les vrais (assets/adherents.js) ; les événements et comptes sont fictifs. */
window.CIOFF = ( function () {
	var icones = {
		drapeau: '<svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor" aria-hidden="true"><path d="M5 21V4h1v1h11l-2 4 2 4H6v8z"/></svg>',
		danseurs: '<svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor" aria-hidden="true"><circle cx="8" cy="4.5" r="2"/><circle cx="16" cy="4.5" r="2"/><path d="M6 8h4l1.5 4 1.5-4h4l-1 6h-1.5l-.5 7h-2l-.5-5-.5 5h-2l-.5-7H7z"/></svg>',
		batiment: '<svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor" aria-hidden="true"><path d="M12 3 3 8v2h18V8zM5 11v7h2v-7zm4 0v7h2v-7zm4 0v7h2v-7zm4 0v7h2v-7zM3 19v2h18v-2z"/></svg>',
		personne: '<svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor" aria-hidden="true"><circle cx="12" cy="7" r="4"/><path d="M4 21c0-4.4 3.6-7 8-7s8 2.6 8 7z"/></svg>',
	};

	var types = {
		'festival': { label: 'Festival', pluriel: 'Festivals', couleur: '#1f3f7a', icone: icones.drapeau },
		'festival-associe': { label: 'Festival associé', pluriel: 'Festivals associés', couleur: '#4a90d9', icone: icones.drapeau },
		'groupe-labellise': { label: 'Groupe labellisé', pluriel: 'Groupes labellisés', couleur: '#c0501a', icone: icones.danseurs },
		'groupe-associe': { label: 'Groupe associé', pluriel: 'Groupes associés', couleur: '#f0a04b', icone: icones.danseurs },
		'membre-participant': { label: 'Membre participant', pluriel: 'Membres participants', couleur: '#2e8b57', icone: icones.batiment },
		'membre-individuel': { label: 'Membre individuel', pluriel: 'Membres individuels', couleur: '#6b7280', icone: icones.personne },
	};

	// Adhérents réels repris de cioff-france.org (fichier généré : assets/adherents.js).
	var adherents = window.CIOFF_ADHERENTS || [];

	return {
		types: types,
		adherents: adherents,
		tiles: {
			url: 'https://tile.openstreetmap.org/{z}/{x}/{y}.png',
			attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">Contributeurs OpenStreetMap</a>',
		},
		badge: function ( slug ) {
			var t = types[ slug ];
			return '<span class="cioff-badge" style="--cioff-type:' + t.couleur + '">' + t.icone + ' ' + t.label + '</span>';
		},
	};
} )();
