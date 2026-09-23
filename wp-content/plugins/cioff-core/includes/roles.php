<?php
/**
 * Les 8 profils du cahier des charges (§2.4).
 *
 * - Administrateur CIOFF : gère tout le contenu, valide les fiches et les événements, gère les comptes.
 * - 6 profils « responsable / membre » : ne voient jamais l'administration WordPress ; ils gèrent leur
 *   fiche depuis la page « Mon espace » et accèdent à l'intranet.
 * - Visiteur public : non connecté.
 */

defined( 'ABSPATH' ) || exit;

function cioff_register_roles() {
	$cpt_caps = array();
	foreach ( array( 'adherent', 'evenement' ) as $single ) {
		$plural     = $single . 's';
		$cpt_caps[] = "edit_{$single}";
		$cpt_caps[] = "read_{$single}";
		$cpt_caps[] = "delete_{$single}";
		$cpt_caps[] = "edit_{$plural}";
		$cpt_caps[] = "edit_others_{$plural}";
		$cpt_caps[] = "publish_{$plural}";
		$cpt_caps[] = "read_private_{$plural}";
		$cpt_caps[] = "delete_{$plural}";
		$cpt_caps[] = "delete_private_{$plural}";
		$cpt_caps[] = "delete_published_{$plural}";
		$cpt_caps[] = "delete_others_{$plural}";
		$cpt_caps[] = "edit_private_{$plural}";
		$cpt_caps[] = "edit_published_{$plural}";
	}
	$cioff_caps = array_merge( $cpt_caps, array( 'cioff_valider', 'cioff_intranet', 'cioff_reglages' ) );

	// Administrateur CIOFF = droits d'éditeur + fiches, agenda, validation, réglages CIOFF et comptes.
	remove_role( 'cioff_admin' );
	$editor = get_role( 'editor' );
	$caps   = $editor ? $editor->capabilities : array( 'read' => true );
	foreach ( $cioff_caps as $cap ) {
		$caps[ $cap ] = true;
	}
	foreach ( array( 'list_users', 'create_users', 'edit_users', 'promote_users', 'delete_users', 'edit_theme_options' ) as $cap ) {
		$caps[ $cap ] = true;
	}
	add_role( 'cioff_admin', 'Administrateur CIOFF', $caps );

	// L'administrateur WordPress (technique) garde tous les droits.
	$admin = get_role( 'administrator' );
	if ( $admin ) {
		foreach ( $cioff_caps as $cap ) {
			$admin->add_cap( $cap );
		}
	}

	// Les 6 profils adhérents : lecture + intranet. Fiche et événements se déposent depuis le site.
	foreach ( cioff_types() as $type ) {
		remove_role( $type['role'] );
		$label = 'cioff_membre_individuel' === $type['role'] || 'cioff_membre_participant' === $type['role']
			? $type['label']
			: 'Responsable ' . lcfirst( $type['label'] );
		add_role(
			$type['role'],
			$label,
			array(
				'read'           => true,
				'cioff_intranet' => true,
			)
		);
	}
}

/** Rôles qui correspondent à des adhérents (utile pour les listes et l'intranet). */
function cioff_member_roles() {
	$roles = array();
	foreach ( cioff_types() as $slug => $type ) {
		$roles[ $type['role'] ] = $type['label'];
	}
	return $roles;
}

/** Rôles proposés pour restreindre une page de l'intranet à un sous-groupe. */
function cioff_audience_roles() {
	$roles = array( 'cioff_admin' => 'Administrateurs CIOFF' );
	foreach ( cioff_types() as $type ) {
		$roles[ $type['role'] ] = $type['pluriel'];
	}
	return $roles;
}

/*
 * Les adhérents n'utilisent pas l'administration WordPress : pas de barre d'admin,
 * et redirection vers « Mon espace » s'ils tentent d'y aller.
 */
add_filter( 'show_admin_bar', function ( $show ) {
	if ( is_user_logged_in() && ! current_user_can( 'edit_posts' ) ) {
		return false;
	}
	return $show;
} );

add_action( 'admin_init', function () {
	if ( wp_doing_ajax() || ! is_user_logged_in() || current_user_can( 'edit_posts' ) ) {
		return;
	}
	// admin-post.php (formulaires du site), async-upload.php et le profil restent autorisés.
	global $pagenow;
	if ( in_array( $pagenow, array( 'admin-post.php', 'async-upload.php', 'profile.php' ), true ) ) {
		return;
	}
	wp_safe_redirect( cioff_page_url( 'mon-espace' ) );
	exit;
} );

// Après connexion, un adhérent arrive sur « Mon espace ».
add_filter( 'login_redirect', function ( $redirect_to, $requested, $user ) {
	if ( $user instanceof WP_User && ! user_can( $user, 'edit_posts' ) && user_can( $user, 'cioff_intranet' ) ) {
		if ( ! $requested || str_contains( $requested, 'wp-admin' ) ) {
			return cioff_page_url( 'mon-espace' );
		}
	}
	return $redirect_to;
}, 10, 3 );
