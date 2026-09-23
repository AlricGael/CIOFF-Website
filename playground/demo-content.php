<?php
/**
 * Contenu de démonstration FICTIF pour WordPress Playground.
 * Exécuté une seule fois par blueprint.json après l'activation du thème et de l'extension.
 * Ne pas utiliser sur le vrai site.
 */

defined( 'ABSPATH' ) || exit;

// Permaliens lisibles.
update_option( 'permalink_structure', '/%postname%/' );

// Réglages CIOFF.
update_option(
	'cioff_options',
	array(
		'theme_annuel_titre' => 'Les danses de la mer',
		'theme_annuel_texte' => "Exemple de thème annuel défini par la commission culture.\nIl se modifie dans Réglages CIOFF.",
		'theme_annuel_image' => 0,
		'lien_international' => 'https://www.cioff.org',
		'facebook'           => 'https://www.facebook.com/',
		'instagram'          => 'https://www.instagram.com/',
		'youtube'            => '',
		'emails_validation'  => 'admin@localhost.com',
		'contact_categories' => array(
			array( 'label' => 'Adhésion', 'emails' => 'adhesion@exemple.fr' ),
			array( 'label' => 'Communication', 'emails' => 'communication@exemple.fr' ),
			array( 'label' => 'Festival', 'emails' => 'festivals@exemple.fr' ),
			array( 'label' => 'Groupe', 'emails' => 'groupes@exemple.fr' ),
			array( 'label' => 'Partenariat', 'emails' => 'partenariats@exemple.fr, president@exemple.fr' ),
			array( 'label' => 'Commande de matériel', 'emails' => 'materiel@exemple.fr' ),
			array( 'label' => 'Autre', 'emails' => 'contact@exemple.fr' ),
		),
	)
);

// Comptes de démonstration (mot de passe : demo).
$cioff_demo_users = array(
	'festival' => array( 'Responsable festival (démo)', 'cioff_resp_festival' ),
	'groupe'   => array( 'Responsable groupe (démo)', 'cioff_resp_groupe' ),
	'membre'   => array( 'Membre individuel (démo)', 'cioff_membre_individuel' ),
);
$cioff_user_ids = array();
foreach ( $cioff_demo_users as $login => $u ) {
	$id = username_exists( $login );
	if ( ! $id ) {
		$id = wp_insert_user(
			array(
				'user_login'   => $login,
				'user_pass'    => 'demo',
				'user_email'   => $login . '@exemple.fr',
				'display_name' => $u[0],
				'role'         => $u[1],
			)
		);
	}
	$cioff_user_ids[ $login ] = $id;
}

// Adhérents fictifs : [nom, type, ville, dept, lat, lng].
$cioff_demo = array(
	array( 'Festival des Danses du Monde de Kerlann', 'festival', 'Quimper', '29', 47.996, -4.102 ),
	array( 'Rencontres Folkloriques de la Vallée d’Aure', 'festival', 'Tarbes', '65', 43.233, 0.078 ),
	array( 'Festival International des Arts de la Rue et du Folklore', 'festival', 'Angers', '49', 47.478, -0.563 ),
	array( 'Festival des Cultures d’Ailleurs', 'festival', 'Colmar', '68', 48.079, 7.358 ),
	array( 'Folklores en Fête de Provence', 'festival', 'Aix-en-Provence', '13', 43.529, 5.447 ),
	array( 'Festival Mondial de la Danse Populaire', 'festival', 'Limoges', '87', 45.833, 1.261 ),
	array( 'Festival des Montagnes et des Peuples', 'festival-associe', 'Annecy', '74', 45.899, 6.129 ),
	array( 'Les Journées Folkloriques du Littoral', 'festival-associe', 'La Rochelle', '17', 46.160, -1.151 ),
	array( 'Festival des Terroirs en Danse', 'festival-associe', 'Dijon', '21', 47.322, 5.041 ),
	array( 'Festival Traditions du Nord', 'festival-associe', 'Arras', '62', 50.291, 2.777 ),
	array( 'Ensemble Bro Lann', 'groupe-labellise', 'Vannes', '56', 47.658, -2.760 ),
	array( 'Les Sabots d’Auvergne', 'groupe-labellise', 'Clermont-Ferrand', '63', 45.778, 3.087 ),
	array( 'Ballet Traditionnel Basque Itsasoa', 'groupe-labellise', 'Bayonne', '64', 43.493, -1.475 ),
	array( 'Les Farandoleurs du Rhône', 'groupe-labellise', 'Arles', '13', 43.677, 4.631 ),
	array( 'Ensemble Alsacien Les Cigognes', 'groupe-labellise', 'Strasbourg', '67', 48.573, 7.752 ),
	array( 'Cercle Celtique Avel Mor', 'groupe-associe', 'Brest', '29', 48.390, -4.486 ),
	array( 'Les Bourrées du Limousin', 'groupe-associe', 'Tulle', '19', 45.267, 1.771 ),
	array( 'Ensemble Corse Voce di u Monte', 'groupe-associe', 'Corte', '2B', 42.306, 9.150 ),
	array( 'Groupe Folklorique Normand La Pommeraie', 'groupe-associe', 'Caen', '14', 49.183, -0.370 ),
	array( 'Les Rigaudons Savoyards', 'groupe-associe', 'Chambéry', '73', 45.564, 5.918 ),
	array( 'Maison des Cultures Populaires', 'membre-participant', 'Toulouse', '31', 43.605, 1.444 ),
	array( 'Association Patrimoine Vivant', 'membre-participant', 'Lyon', '69', 45.764, 4.836 ),
	array( 'Conservatoire des Arts Traditionnels', 'membre-participant', 'Paris', '75', 48.857, 2.352 ),
	array( 'Marie D.', 'membre-individuel', '', '35', 48.15, -1.60 ),
	array( 'Jean-Paul R.', 'membre-individuel', '', '86', 46.56, 0.40 ),
	array( 'Karim B.', 'membre-individuel', '', '59', 50.45, 3.20 ),
);

$cioff_textes = array(
	'festival'           => "Chaque été, le festival accueille une quinzaine de groupes venus des cinq continents pour une semaine de spectacles, de défilés et d'ateliers.\n\nPlus de 400 bénévoles hébergent et accompagnent les artistes.",
	'festival-associe'   => 'Un festival à taille humaine qui fait découvrir les traditions d’ici et d’ailleurs.',
	'groupe-labellise'   => "Danses, chants et costumes traditionnels présentés en France et à l’étranger.\n\nLe groupe compte une quarantaine de danseurs et musiciens.",
	'groupe-associe'     => 'Un groupe passionné qui fait vivre le répertoire de sa région.',
	'membre-participant' => 'Structure partenaire des activités du CIOFF France.',
	'membre-individuel'  => 'Membre adhérent à titre individuel.',
);

if ( ! get_posts( array( 'post_type' => 'cioff_adherent', 'posts_per_page' => 1, 'post_status' => 'any' ) ) ) {
	foreach ( $cioff_demo as $i => $a ) {
		list( $nom, $type, $ville, $dept, $lat, $lng ) = $a;
		$author = get_current_user_id();
		if ( 'Festival des Danses du Monde de Kerlann' === $nom ) {
			$author = $cioff_user_ids['festival'];
		} elseif ( 'Les Sabots d’Auvergne' === $nom ) {
			$author = $cioff_user_ids['groupe'];
		} elseif ( 'Marie D.' === $nom ) {
			$author = $cioff_user_ids['membre'];
		}
		$id = wp_insert_post(
			array(
				'post_type'    => 'cioff_adherent',
				'post_status'  => 'publish',
				'post_title'   => $nom,
				'post_content' => cioff_text_to_blocks( $cioff_textes[ $type ] ),
				'post_author'  => $author,
			)
		);
		wp_set_object_terms( $id, $type, 'cioff_type' );
		$meta = array(
			'_cioff_departement' => $dept,
			'_cioff_email'       => 'contact@exemple.fr',
			'_cioff_lat'         => $lat,
			'_cioff_lng'         => $lng,
			'_cioff_geo_hash'    => 'demo',
		);
		if ( $ville ) {
			$meta['_cioff_ville']     = $ville;
			$meta['_cioff_site_web']  = 'https://www.exemple.fr';
			$meta['_cioff_telephone'] = '02 00 00 00 00';
		}
		if ( str_starts_with( $type, 'festival' ) ) {
			$meta['_cioff_dates']         = "2027 : du 12 au 18 juillet\n2028 : du 10 au 16 juillet\n2029 : du 9 au 15 juillet";
			$meta['_cioff_programmation'] = 'Mexique, Sénégal, Géorgie, Corée du Sud, Pérou, Estonie…';
			$meta['_cioff_lien_benevole'] = 'https://www.exemple.fr/benevoles';
		}
		if ( str_starts_with( $type, 'groupe' ) ) {
			$meta['_cioff_categorie']  = 'adultes';
			$meta['_cioff_repertoire'] = 'Danses et chants traditionnels de la région.';
			$meta['_cioff_tournees']   = 'Mexique (2019), Pologne (2022), Corée du Sud (2025).';
		}
		foreach ( $meta as $k => $v ) {
			update_post_meta( $id, $k, $v );
		}
	}

	// Une nouvelle fiche en attente et une modification proposée, pour la page « À valider ».
	$pending = wp_insert_post(
		array(
			'post_type'    => 'cioff_adherent',
			'post_status'  => 'pending',
			'post_title'   => 'Les Enfants de la Gavotte',
			'post_content' => cioff_text_to_blocks( 'Groupe d’enfants de 6 à 16 ans qui fait vivre les danses du pays vannetais.' ),
			'post_author'  => $cioff_user_ids['groupe'],
		)
	);
	wp_set_object_terms( $pending, 'groupe-associe', 'cioff_type' );
	foreach ( array( '_cioff_ville' => 'Lorient', '_cioff_departement' => '56', '_cioff_categorie' => 'enfants', '_cioff_lat' => 47.748, '_cioff_lng' => -3.370, '_cioff_geo_hash' => 'demo' ) as $k => $v ) {
		update_post_meta( $pending, $k, $v );
	}

	$sabots = get_posts( array( 'post_type' => 'cioff_adherent', 'title' => 'Les Sabots d’Auvergne', 'posts_per_page' => 1 ) );
	if ( $sabots ) {
		$champs                = cioff_get_field_values( $sabots[0]->ID );
		$champs['categorie']   = 'intergenerationnel';
		$champs['tournees']    = 'Mexique (2019), Pologne (2022), Corée du Sud (2025), Japon (2026).';
		update_post_meta(
			$sabots[0]->ID,
			'_cioff_proposition',
			wp_slash(
				array(
					'titre'       => $sabots[0]->post_title,
					'description' => cioff_blocks_to_text( $sabots[0]->post_content ),
					'photo'       => 0,
					'champs'      => $champs,
					'date'        => current_time( 'mysql' ),
					'auteur'      => $cioff_user_ids['groupe'],
				)
			)
		);
	}
	delete_transient( 'cioff_annuaire_data' );
}

// Agenda.
if ( ! get_posts( array( 'post_type' => 'cioff_evenement', 'post_status' => 'publish', 'posts_per_page' => 1 ) ) ) {
	$cioff_evts = array(
		array( 'Assemblée générale du CIOFF France', '+40 days', '', '10:00', 'Lieu à préciser', 'reunion-cioff-france', true, 'publish' ),
		array( 'Réunion des festivals', '+95 days', '', '14:00', 'Visioconférence', 'reunion-cioff-france', false, 'publish' ),
		array( 'Week-end CIOFF Jeunes', '+60 days', '+61 days', '', 'Lyon', 'reunion-cioff-jeunes', false, 'publish' ),
		array( 'Stage de danses du monde', '+30 days', '', '09:30', 'Angers', 'evenement-adherent', false, 'publish' ),
		array( 'Journée mondiale du patrimoine immatériel', '+75 days', '', '', 'Partout en France', 'journee-internationale', false, 'publish' ),
		array( 'Soirée des 60 ans des Sabots d’Auvergne', '+25 days', '', '20:00', 'Clermont-Ferrand', 'evenement-adherent', false, 'pending' ),
	);
	foreach ( $cioff_evts as $e ) {
		$id = wp_insert_post(
			array(
				'post_type'    => 'cioff_evenement',
				'post_status'  => $e[7],
				'post_title'   => $e[0],
				'post_content' => cioff_text_to_blocks( 'Événement fictif de démonstration.' ),
				'post_author'  => 'pending' === $e[7] ? $cioff_user_ids['groupe'] : get_current_user_id(),
			)
		);
		cioff_save_event_meta(
			$id,
			array(
				'date_debut' => wp_date( 'Y-m-d', strtotime( $e[1] ) ),
				'date_fin'   => $e[2] ? wp_date( 'Y-m-d', strtotime( $e[2] ) ) : '',
				'heure'      => $e[3],
				'lieu'       => $e[4],
				'lien'       => '',
			)
		);
		wp_set_object_terms( $id, $e[5], 'cioff_evt_cat' );
		if ( $e[6] ) {
			update_post_meta( $id, '_cioff_une', 1 );
		}
	}
}

flush_rewrite_rules();
