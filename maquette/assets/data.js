/* Données FICTIVES pour la maquette. Sur le vrai site, elles viennent des fiches WordPress. */
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

	// [nom, type, ville, dept, deptNom, région, lat, lng]
	var brut = [
		[ 'Festival des Danses du Monde de Kerlann', 'festival', 'Quimper', '29', 'Finistère', 'Bretagne', 47.996, -4.102 ],
		[ 'Rencontres Folkloriques de la Vallée d’Aure', 'festival', 'Tarbes', '65', 'Hautes-Pyrénées', 'Occitanie', 43.233, 0.078 ],
		[ 'Festival International des Arts de la Rue et du Folklore', 'festival', 'Angers', '49', 'Maine-et-Loire', 'Pays de la Loire', 47.478, -0.563 ],
		[ 'Festival des Cultures d’Ailleurs', 'festival', 'Colmar', '68', 'Haut-Rhin', 'Grand Est', 48.079, 7.358 ],
		[ 'Folklores en Fête de Provence', 'festival', 'Aix-en-Provence', '13', 'Bouches-du-Rhône', "Provence-Alpes-Côte d'Azur", 43.529, 5.447 ],
		[ 'Festival du Bocage et des Traditions', 'festival', 'Cholet', '49', 'Maine-et-Loire', 'Pays de la Loire', 47.060, -0.879 ],
		[ 'Festival Mondial de la Danse Populaire', 'festival', 'Limoges', '87', 'Haute-Vienne', 'Nouvelle-Aquitaine', 45.833, 1.261 ],
		[ 'Festival des Montagnes et des Peuples', 'festival-associe', 'Annecy', '74', 'Haute-Savoie', 'Auvergne-Rhône-Alpes', 45.899, 6.129 ],
		[ 'Les Journées Folkloriques du Littoral', 'festival-associe', 'La Rochelle', '17', 'Charente-Maritime', 'Nouvelle-Aquitaine', 46.160, -1.151 ],
		[ 'Festival des Terroirs en Danse', 'festival-associe', 'Dijon', '21', "Côte-d'Or", 'Bourgogne-Franche-Comté', 47.322, 5.041 ],
		[ 'Festival Traditions du Nord', 'festival-associe', 'Arras', '62', 'Pas-de-Calais', 'Hauts-de-France', 50.291, 2.777 ],
		[ 'Ensemble Bro Lann', 'groupe-labellise', 'Vannes', '56', 'Morbihan', 'Bretagne', 47.658, -2.760 ],
		[ 'Les Sabots d’Auvergne', 'groupe-labellise', 'Clermont-Ferrand', '63', 'Puy-de-Dôme', 'Auvergne-Rhône-Alpes', 45.778, 3.087 ],
		[ 'Ballet Traditionnel Basque Itsasoa', 'groupe-labellise', 'Bayonne', '64', 'Pyrénées-Atlantiques', 'Nouvelle-Aquitaine', 43.493, -1.475 ],
		[ 'Les Farandoleurs du Rhône', 'groupe-labellise', 'Arles', '13', 'Bouches-du-Rhône', "Provence-Alpes-Côte d'Azur", 43.677, 4.631 ],
		[ 'Ensemble Alsacien Les Cigognes', 'groupe-labellise', 'Strasbourg', '67', 'Bas-Rhin', 'Grand Est', 48.573, 7.752 ],
		[ 'Les Danseurs du Pays de Retz', 'groupe-labellise', 'Pornic', '44', 'Loire-Atlantique', 'Pays de la Loire', 47.115, -2.103 ],
		[ 'Cercle Celtique Avel Mor', 'groupe-associe', 'Brest', '29', 'Finistère', 'Bretagne', 48.390, -4.486 ],
		[ 'Les Bourrées du Limousin', 'groupe-associe', 'Tulle', '19', 'Corrèze', 'Nouvelle-Aquitaine', 45.267, 1.771 ],
		[ 'Ensemble Corse Voce di u Monte', 'groupe-associe', 'Corte', '2B', 'Haute-Corse', 'Corse', 42.306, 9.150 ],
		[ 'Les Enfants de la Gavotte', 'groupe-associe', 'Lorient', '56', 'Morbihan', 'Bretagne', 47.748, -3.370 ],
		[ 'Groupe Folklorique Normand La Pommeraie', 'groupe-associe', 'Caen', '14', 'Calvados', 'Normandie', 49.183, -0.370 ],
		[ 'Les Rigaudons Savoyards', 'groupe-associe', 'Chambéry', '73', 'Savoie', 'Auvergne-Rhône-Alpes', 45.564, 5.918 ],
		[ 'Maison des Cultures Populaires', 'membre-participant', 'Toulouse', '31', 'Haute-Garonne', 'Occitanie', 43.605, 1.444 ],
		[ 'Association Patrimoine Vivant', 'membre-participant', 'Lyon', '69', 'Rhône', 'Auvergne-Rhône-Alpes', 45.764, 4.836 ],
		[ 'Conservatoire des Arts Traditionnels', 'membre-participant', 'Paris', '75', 'Paris', 'Île-de-France', 48.857, 2.352 ],
		[ 'Office Culturel du Pays Basque', 'membre-participant', 'Biarritz', '64', 'Pyrénées-Atlantiques', 'Nouvelle-Aquitaine', 43.483, -1.559 ],
		[ 'Marie D.', 'membre-individuel', '', '35', 'Ille-et-Vilaine', 'Bretagne', 48.15, -1.60 ],
		[ 'Jean-Paul R.', 'membre-individuel', '', '86', 'Vienne', 'Nouvelle-Aquitaine', 46.56, 0.40 ],
		[ 'Sophie L.', 'membre-individuel', '', '38', 'Isère', 'Auvergne-Rhône-Alpes', 45.25, 5.60 ],
		[ 'Karim B.', 'membre-individuel', '', '59', 'Nord', 'Hauts-de-France', 50.45, 3.20 ],
	];

	var extraits = {
		'festival': 'Chaque été, une semaine de spectacles, de défilés et de rencontres avec des groupes venus des cinq continents.',
		'festival-associe': 'Un festival à taille humaine qui fait découvrir les traditions d’ici et d’ailleurs.',
		'groupe-labellise': 'Danses, chants et costumes traditionnels présentés en France et à l’étranger.',
		'groupe-associe': 'Un groupe passionné qui fait vivre le répertoire de sa région.',
		'membre-participant': 'Structure partenaire des activités du CIOFF France.',
		'membre-individuel': 'Membre adhérent à titre individuel.',
	};

	var adherents = brut.map( function ( r, i ) {
		return {
			id: i + 1, nom: r[0], type: r[1], ville: r[2], dept: r[3], deptNom: r[4], region: r[5], lat: r[6], lng: r[7],
			url: 'fiche.html', image: '', extrait: extraits[ r[1] ],
		};
	} );

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
