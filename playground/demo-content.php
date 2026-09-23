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

// Adhérents réels de l'ancien site (data/adherents.json de l'extension).
if ( ! get_posts( array( 'post_type' => 'cioff_adherent', 'posts_per_page' => 1, 'post_status' => 'any' ) ) ) {
	cioff_import_adherents();

	// Les comptes de démonstration deviennent responsables de deux fiches réelles.
	$cioff_liens = array(
		'festival' => 'Confolens – Festival Danses et musiques du monde',
		'groupe'   => 'Bleuniadur',
	);
	foreach ( $cioff_liens as $login => $titre ) {
		$p = get_posts( array( 'post_type' => 'cioff_adherent', 'title' => $titre, 'posts_per_page' => 1 ) );
		if ( $p ) {
			wp_update_post( array( 'ID' => $p[0]->ID, 'post_author' => $cioff_user_ids[ $login ] ) );
		}
	}

	// Un membre individuel d'exemple (fictif).
	$indiv = wp_insert_post(
		array(
			'post_type'    => 'cioff_adherent',
			'post_status'  => 'publish',
			'post_title'   => 'Membre individuel (exemple)',
			'post_content' => cioff_text_to_blocks( 'Fiche fictive de démonstration : seul le département est affiché.' ),
			'post_author'  => $cioff_user_ids['membre'],
		)
	);
	wp_set_object_terms( $indiv, 'membre-individuel', 'cioff_type' );
	foreach ( array( '_cioff_departement' => '35', '_cioff_lat' => 48.15, '_cioff_lng' => -1.62, '_cioff_geo_hash' => 'demo' ) as $k => $v ) {
		update_post_meta( $indiv, $k, $v );
	}

	// Une nouvelle fiche en attente (fictive) et une modification proposée, pour la page « À valider ».
	$pending = wp_insert_post(
		array(
			'post_type'    => 'cioff_adherent',
			'post_status'  => 'pending',
			'post_title'   => 'Groupe d’exemple – en attente de validation',
			'post_content' => cioff_text_to_blocks( 'Fiche fictive de démonstration envoyée par un adhérent.' ),
			'post_author'  => $cioff_user_ids['groupe'],
		)
	);
	wp_set_object_terms( $pending, 'groupe-associe', 'cioff_type' );
	foreach ( array( '_cioff_ville' => 'Lorient', '_cioff_departement' => '56', '_cioff_categorie' => 'enfants', '_cioff_lat' => 47.748, '_cioff_lng' => -3.370, '_cioff_geo_hash' => 'demo' ) as $k => $v ) {
		update_post_meta( $pending, $k, $v );
	}

	$bleu = get_posts( array( 'post_type' => 'cioff_adherent', 'title' => 'Bleuniadur', 'posts_per_page' => 1 ) );
	if ( $bleu ) {
		$champs              = cioff_get_field_values( $bleu[0]->ID );
		$champs['categorie'] = 'adultes';
		$champs['tournees']  = 'États-Unis, Italie, République tchèque, Pologne, Belgique, Hongrie, Roumanie, Autriche (exemple de modification).';
		update_post_meta(
			$bleu[0]->ID,
			'_cioff_proposition',
			wp_slash(
				array(
					'titre'       => $bleu[0]->post_title,
					'description' => cioff_blocks_to_text( $bleu[0]->post_content ),
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
		array( 'Stage de danses du monde (exemple)', '+30 days', '', '09:30', 'Angers', 'evenement-adherent', false, 'publish' ),
		array( 'Journée internationale (exemple)', '+75 days', '', '', 'Partout en France', 'journee-internationale', false, 'publish' ),
		array( 'Soirée anniversaire d’un groupe (exemple)', '+25 days', '', '20:00', 'Clermont-Ferrand', 'evenement-adherent', false, 'pending' ),
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
