<?php
/**
 * Types de contenus : fiches adhérents (annuaire) et événements (agenda).
 */

defined( 'ABSPATH' ) || exit;

add_action( 'init', 'cioff_register_post_types' );

function cioff_register_post_types() {
	register_post_type(
		'cioff_adherent',
		array(
			'labels'          => array(
				'name'               => 'Adhérents',
				'singular_name'      => 'Fiche adhérent',
				'menu_name'          => 'Annuaire',
				'all_items'          => 'Toutes les fiches',
				'add_new'            => 'Ajouter une fiche',
				'add_new_item'       => 'Ajouter une fiche adhérent',
				'edit_item'          => 'Modifier la fiche',
				'new_item'           => 'Nouvelle fiche',
				'view_item'          => 'Voir la fiche',
				'search_items'       => 'Rechercher un adhérent',
				'not_found'          => 'Aucune fiche trouvée',
				'not_found_in_trash' => 'Aucune fiche dans la corbeille',
			),
			'public'          => true,
			'has_archive'     => false, // La page « Annuaire » (carte) sert d'archive.
			'rewrite'         => array( 'slug' => 'annuaire', 'with_front' => false ),
			'menu_icon'       => 'dashicons-location-alt',
			'menu_position'   => 5,
			'supports'        => array( 'title', 'editor', 'thumbnail', 'author', 'revisions' ),
			'show_in_rest'    => true,
			'capability_type' => array( 'adherent', 'adherents' ),
			'map_meta_cap'    => true,
		)
	);

	register_taxonomy(
		'cioff_type',
		'cioff_adherent',
		array(
			'labels'            => array(
				'name'          => "Types d'adhérent",
				'singular_name' => "Type d'adhérent",
			),
			'public'            => true,
			'hierarchical'      => true,
			'show_admin_column' => true,
			'show_in_rest'      => true,
			'meta_box_cb'       => false, // Choisi dans la boîte « Informations de la fiche ».
			'rewrite'           => array( 'slug' => 'type-adherent' ),
			'capabilities'      => array(
				'manage_terms' => 'manage_options', // Les 6 types sont fixes.
				'edit_terms'   => 'manage_options',
				'delete_terms' => 'manage_options',
				'assign_terms' => 'edit_adherents',
			),
		)
	);

	register_post_type(
		'cioff_evenement',
		array(
			'labels'          => array(
				'name'          => 'Agenda',
				'singular_name' => 'Événement',
				'menu_name'     => 'Agenda',
				'all_items'     => 'Tous les événements',
				'add_new'       => 'Ajouter un événement',
				'add_new_item'  => 'Ajouter un événement',
				'edit_item'     => "Modifier l'événement",
				'view_item'     => "Voir l'événement",
				'search_items'  => 'Rechercher un événement',
				'not_found'     => 'Aucun événement',
			),
			'public'          => true,
			'has_archive'     => false,
			'rewrite'         => array( 'slug' => 'agenda', 'with_front' => false ),
			'menu_icon'       => 'dashicons-calendar-alt',
			'menu_position'   => 6,
			'supports'        => array( 'title', 'editor', 'thumbnail', 'author' ),
			'show_in_rest'    => true,
			'capability_type' => array( 'evenement', 'evenements' ),
			'map_meta_cap'    => true,
		)
	);

	register_taxonomy(
		'cioff_evt_cat',
		'cioff_evenement',
		array(
			'labels'            => array(
				'name'          => "Catégories d'événement",
				'singular_name' => "Catégorie d'événement",
				'add_new_item'  => 'Ajouter une catégorie',
			),
			'public'            => true,
			'hierarchical'      => true,
			'show_admin_column' => true,
			'show_in_rest'      => true,
			'rewrite'           => array( 'slug' => 'agenda-categorie' ),
			'capabilities'      => array(
				'manage_terms' => 'edit_others_evenements',
				'edit_terms'   => 'edit_others_evenements',
				'delete_terms' => 'edit_others_evenements',
				'assign_terms' => 'edit_evenements',
			),
		)
	);
}

function cioff_insert_default_terms() {
	foreach ( cioff_types() as $slug => $type ) {
		if ( ! term_exists( $slug, 'cioff_type' ) ) {
			wp_insert_term( $type['label'], 'cioff_type', array( 'slug' => $slug, 'description' => $type['description'] ) );
		}
	}
	$categories = array(
		'reunion-cioff-france' => 'Réunion CIOFF France',
		'reunion-cioff-jeunes' => 'Réunion CIOFF Jeunes',
		'evenement-adherent'   => 'Événement adhérent',
		'journee-internationale' => 'Journée internationale',
	);
	foreach ( $categories as $slug => $name ) {
		if ( ! term_exists( $slug, 'cioff_evt_cat' ) ) {
			wp_insert_term( $name, 'cioff_evt_cat', array( 'slug' => $slug ) );
		}
	}
}
