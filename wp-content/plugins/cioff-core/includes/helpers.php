<?php
/**
 * Définitions communes : les 6 types d'adhérents et petites fonctions utilitaires.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Les 6 types d'adhérents du cahier des charges (§3.4.1 et §3.4.2).
 * « famille » regroupe les types qui partagent les mêmes champs spécifiques.
 */
function cioff_types() {
	return array(
		'festival'           => array(
			'label'       => 'Festival',
			'pluriel'     => 'Festivals',
			'description' => 'Festival de folklore reconnu par le CIOFF, labellisé au niveau international',
			'couleur'     => '#1f3f7a',
			'icone'       => 'drapeau',
			'famille'     => 'festival',
			'role'        => 'cioff_resp_festival',
		),
		'festival-associe'   => array(
			'label'       => 'Festival associé',
			'pluriel'     => 'Festivals associés',
			'description' => "Festival en cours d'affiliation ou reconnu au niveau national uniquement",
			'couleur'     => '#4a90d9',
			'icone'       => 'drapeau',
			'famille'     => 'festival',
			'role'        => 'cioff_resp_festival_associe',
		),
		'groupe-labellise'   => array(
			'label'       => 'Groupe labellisé',
			'pluriel'     => 'Groupes labellisés',
			'description' => 'Groupe de folklore labellisé CIOFF au niveau international',
			'couleur'     => '#c0501a',
			'icone'       => 'danseurs',
			'famille'     => 'groupe',
			'role'        => 'cioff_resp_groupe',
		),
		'groupe-associe'     => array(
			'label'       => 'Groupe associé',
			'pluriel'     => 'Groupes associés',
			'description' => 'Groupe en cours de labellisation ou reconnu au niveau national uniquement',
			'couleur'     => '#f0a04b',
			'icone'       => 'danseurs',
			'famille'     => 'groupe',
			'role'        => 'cioff_resp_groupe_associe',
		),
		'membre-participant' => array(
			'label'       => 'Membre participant',
			'pluriel'     => 'Membres participants',
			'description' => 'Structure ou personne morale participant aux activités du CIOFF France',
			'couleur'     => '#2e8b57',
			'icone'       => 'batiment',
			'famille'     => 'participant',
			'role'        => 'cioff_membre_participant',
		),
		'membre-individuel'  => array(
			'label'       => 'Membre individuel',
			'pluriel'     => 'Membres individuels',
			'description' => 'Personne physique adhérente à titre individuel',
			'couleur'     => '#6b7280',
			'icone'       => 'personne',
			'famille'     => 'individuel',
			'role'        => 'cioff_membre_individuel',
		),
	);
}

function cioff_type( $slug ) {
	$types = cioff_types();
	return $types[ $slug ] ?? null;
}

/** Slug du type d'un adhérent (ou chaîne vide). */
function cioff_get_adherent_type( $post_id ) {
	$terms = get_the_terms( $post_id, 'cioff_type' );
	if ( $terms && ! is_wp_error( $terms ) ) {
		return $terms[0]->slug;
	}
	return '';
}

/** Type d'adhérent correspondant au rôle d'un utilisateur (ou chaîne vide). */
function cioff_type_for_user( $user_id ) {
	$user = get_userdata( $user_id );
	if ( ! $user ) {
		return '';
	}
	foreach ( cioff_types() as $slug => $type ) {
		if ( in_array( $type['role'], (array) $user->roles, true ) ) {
			return $slug;
		}
	}
	return '';
}

/** L'utilisateur peut-il valider/publier les contenus (Administrateur CIOFF) ? */
function cioff_is_validator( $user_id = null ) {
	$user_id = $user_id ?: get_current_user_id();
	return $user_id && user_can( $user_id, 'cioff_valider' );
}

/** L'utilisateur est-il un adhérent connecté (accès intranet) ? */
function cioff_is_member( $user_id = null ) {
	$user_id = $user_id ?: get_current_user_id();
	return $user_id && user_can( $user_id, 'cioff_intranet' );
}

/** Fiche adhérent dont l'utilisateur est responsable (tous statuts sauf corbeille). */
function cioff_user_fiche( $user_id = null ) {
	$user_id = $user_id ?: get_current_user_id();
	if ( ! $user_id ) {
		return null;
	}
	$posts = get_posts(
		array(
			'post_type'      => 'cioff_adherent',
			'author'         => $user_id,
			'post_status'    => array( 'publish', 'pending', 'draft' ),
			'posts_per_page' => 1,
			'orderby'        => 'date',
			'order'          => 'ASC',
		)
	);
	return $posts ? $posts[0] : null;
}

/** Adresses qui reçoivent les notifications de validation. */
function cioff_validation_emails() {
	$raw    = cioff_option( 'emails_validation', get_option( 'admin_email' ) );
	$emails = cioff_parse_emails( $raw );
	return $emails ?: array( get_option( 'admin_email' ) );
}

/** Transforme « a@x.fr, b@y.fr » (virgules, points-virgules ou retours à la ligne) en tableau d'e-mails valides. */
function cioff_parse_emails( $raw ) {
	$parts = preg_split( '/[\s,;]+/', (string) $raw, -1, PREG_SPLIT_NO_EMPTY );
	return array_values( array_filter( array_map( 'sanitize_email', $parts ), 'is_email' ) );
}

/** Envoi d'un e-mail HTML simple aux couleurs du CIOFF. */
function cioff_mail( $to, $subject, $message, $headers = array() ) {
	$headers[] = 'Content-Type: text/html; charset=UTF-8';
	$body      = '<div style="font-family:Arial,sans-serif;font-size:15px;line-height:1.5;color:#1b2333">'
		. '<p style="font-weight:bold;color:#1f3f7a">CIOFF France</p>'
		. wpautop( $message )
		. '<p style="color:#6b7280;font-size:13px">— Message automatique du site ' . esc_html( home_url() ) . '</p></div>';
	return wp_mail( $to, '[CIOFF France] ' . $subject, $body, $headers );
}

/** Icônes SVG des types (marqueurs de carte, badges). */
function cioff_icon_svg( $icone ) {
	$paths = array(
		// Drapeau / scène : festivals.
		'drapeau'  => '<path d="M5 21V4h1v1h11l-2 4 2 4H6v8z"/>',
		// Deux danseurs : groupes.
		'danseurs' => '<circle cx="8" cy="4.5" r="2"/><circle cx="16" cy="4.5" r="2"/><path d="M6 8h4l1.5 4 1.5-4h4l-1 6h-1.5l-.5 7h-2l-.5-5-.5 5h-2l-.5-7H7z"/>',
		// Bâtiment : structures.
		'batiment' => '<path d="M12 3 3 8v2h18V8zM5 11v7h2v-7zm4 0v7h2v-7zm4 0v7h2v-7zm4 0v7h2v-7zM3 19v2h18v-2z"/>',
		// Personne : membres individuels.
		'personne' => '<circle cx="12" cy="7" r="4"/><path d="M4 21c0-4.4 3.6-7 8-7s8 2.6 8 7z"/>',
	);
	return '<svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor" aria-hidden="true">' . ( $paths[ $icone ] ?? $paths['personne'] ) . '</svg>';
}

/** Badge coloré du type d'adhérent. */
function cioff_type_badge( $slug ) {
	$type = cioff_type( $slug );
	if ( ! $type ) {
		return '';
	}
	return sprintf(
		'<span class="cioff-badge" style="--cioff-type:%1$s">%2$s %3$s</span>',
		esc_attr( $type['couleur'] ),
		cioff_icon_svg( $type['icone'] ),
		esc_html( $type['label'] )
	);
}

/** Charge la feuille de style commune du plugin. */
function cioff_enqueue_front_css() {
	wp_enqueue_style( 'cioff-front', CIOFF_URL . 'assets/css/front.css', array(), CIOFF_VERSION );
}
