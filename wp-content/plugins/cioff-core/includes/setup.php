<?php
/**
 * Création des pages de l'arborescence (§3) à l'activation, avec un contenu de départ
 * que les rédacteurs n'ont plus qu'à compléter dans l'éditeur.
 * Les pages existantes ne sont jamais écrasées.
 */

defined( 'ABSPATH' ) || exit;

function cioff_page_id( $key ) {
	$pages = get_option( 'cioff_pages', array() );
	$id    = (int) ( $pages[ $key ] ?? 0 );
	return $id && 'trash' !== get_post_status( $id ) && get_post_status( $id ) ? $id : 0;
}

function cioff_page_url( $key ) {
	$id = cioff_page_id( $key );
	return $id ? get_permalink( $id ) : home_url( '/' );
}

/** Pages créées : clé => [titre, slug, parent]. */
function cioff_default_pages() {
	return array(
		'accueil'            => array( 'Accueil', 'accueil', '' ),
		'cioff'              => array( 'Le CIOFF', 'le-cioff', '' ),
		'jeunes'             => array( 'CIOFF Jeunes', 'cioff-jeunes', '' ),
		'annuaire'           => array( 'Annuaire des adhérents', 'annuaire-des-adherents', '' ),
		'agenda'             => array( 'Agenda', 'agenda-des-evenements', '' ),
		'outils'             => array( 'Boîte à outils', 'boite-a-outils', '' ),
		'intranet'           => array( 'Intranet', 'intranet', '' ),
		'membres'            => array( 'Annuaire des membres', 'annuaire-des-membres', 'intranet' ),
		'mon-espace'         => array( 'Mon espace', 'mon-espace', '' ),
		'proposer-evenement' => array( 'Proposer un événement', 'proposer-un-evenement', '' ),
		'contact'            => array( 'Contact', 'contact', '' ),
		'mentions'           => array( 'Mentions légales', 'mentions-legales', '' ),
	);
}

function cioff_create_default_pages() {
	$pages = get_option( 'cioff_pages', array() );

	// 1) Création des pages manquantes (vides).
	foreach ( cioff_default_pages() as $key => $def ) {
		if ( cioff_page_id( $key ) ) {
			continue;
		}
		$existing = $def[2] ? null : get_page_by_path( $def[1] );
		if ( $existing ) {
			$pages[ $key ] = $existing->ID;
			continue;
		}
		$pages[ $key ] = wp_insert_post(
			array(
				'post_type'   => 'page',
				'post_status' => 'publish',
				'post_title'  => $def[0],
				'post_name'   => $def[1],
				'post_parent' => $def[2] ? (int) ( $pages[ $def[2] ] ?? 0 ) : 0,
				'post_content' => '',
				'meta_input'  => array( '_cioff_contenu_initial' => 1 ),
			)
		);
		update_option( 'cioff_pages', $pages );
	}
	update_option( 'cioff_pages', $pages );

	// 2) Contenu de départ (uniquement pour les pages créées par ce plugin et encore vides).
	foreach ( array_keys( cioff_default_pages() ) as $key ) {
		$id = cioff_page_id( $key );
		if ( $id && get_post_meta( $id, '_cioff_contenu_initial', true ) && '' === get_post_field( 'post_content', $id ) ) {
			wp_update_post( array( 'ID' => $id, 'post_content' => cioff_default_page_content( $key ) ) );
		}
	}

	// 3) Page d'accueil statique.
	if ( 'page' !== get_option( 'show_on_front' ) && cioff_page_id( 'accueil' ) ) {
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', cioff_page_id( 'accueil' ) );
	}

	// 4) Menu principal (modifiable dans Apparence → Éditeur → Navigation).
	if ( ! get_option( 'cioff_navigation_id' ) || ! get_post( get_option( 'cioff_navigation_id' ) ) ) {
		$links = '';
		foreach ( array( 'cioff', 'jeunes', 'annuaire', 'agenda', 'outils', 'intranet', 'contact' ) as $key ) {
			$id     = cioff_page_id( $key );
			$links .= sprintf(
				'<!-- wp:navigation-link {"label":"%1$s","type":"page","id":%2$d,"url":"%3$s","kind":"post-type"} /-->',
				esc_attr( get_the_title( $id ) ),
				$id,
				esc_url( get_permalink( $id ) )
			);
		}
		$nav_id = wp_insert_post(
			array(
				'post_type'    => 'wp_navigation',
				'post_status'  => 'publish',
				'post_title'   => 'Menu principal',
				'post_content' => $links,
			)
		);
		update_option( 'cioff_navigation_id', $nav_id );
	}

	// 5) Premier événement d'exemple pour que l'agenda ne soit pas vide.
	if ( ! get_posts( array( 'post_type' => 'cioff_evenement', 'post_status' => 'any', 'posts_per_page' => 1 ) ) ) {
		$evt = wp_insert_post(
			array(
				'post_type'    => 'cioff_evenement',
				'post_status'  => 'draft',
				'post_title'   => 'Exemple : Assemblée générale du CIOFF France',
				'post_content' => cioff_text_to_blocks( "Événement d'exemple à modifier ou supprimer.\n\nOrdre du jour, lieu et modalités d'inscription." ),
			)
		);
		cioff_save_event_meta( $evt, array( 'date_debut' => wp_date( 'Y-m-d', strtotime( '+1 month' ) ), 'date_fin' => '', 'heure' => '10:00', 'lieu' => 'À préciser', 'lien' => '' ) );
		wp_set_object_terms( $evt, 'reunion-cioff-france', 'cioff_evt_cat' );
	}
}

/* ---------------------------------------------------------------------------
 * Petits constructeurs de blocs pour écrire le contenu de départ lisiblement.
 * ------------------------------------------------------------------------- */

function cioff_b_h( $text, $level = 2 ) {
	$attrs = 2 === $level ? '' : ' {"level":' . $level . '}';
	return "<!-- wp:heading{$attrs} -->\n<h{$level} class=\"wp-block-heading\">" . esc_html( $text ) . "</h{$level}>\n<!-- /wp:heading -->\n\n";
}

function cioff_b_p( $html, $class = '' ) {
	$attrs = $class ? ' {"className":"' . $class . '"}' : '';
	$cls   = $class ? ' class="' . $class . '"' : '';
	return "<!-- wp:paragraph{$attrs} -->\n<p{$cls}>" . $html . "</p>\n<!-- /wp:paragraph -->\n\n";
}

function cioff_b_list( array $items ) {
	$out = "<!-- wp:list -->\n<ul class=\"wp-block-list\">";
	foreach ( $items as $item ) {
		$out .= "<!-- wp:list-item -->\n<li>" . $item . "</li>\n<!-- /wp:list-item -->";
	}
	return $out . "</ul>\n<!-- /wp:list -->\n\n";
}

function cioff_b_button( $label, $url, $outline = false ) {
	$attrs = $outline ? ' {"className":"is-style-outline"}' : '';
	$cls   = $outline ? ' is-style-outline' : '';
	return "<!-- wp:button{$attrs} -->\n<div class=\"wp-block-button{$cls}\"><a class=\"wp-block-button__link wp-element-button\" href=\"" . esc_url( $url ) . '">' . esc_html( $label ) . "</a></div>\n<!-- /wp:button -->";
}

function cioff_b_buttons( ...$buttons ) {
	return "<!-- wp:buttons -->\n<div class=\"wp-block-buttons\">" . implode( "\n\n", $buttons ) . "</div>\n<!-- /wp:buttons -->\n\n";
}

/** Bande de section pleine largeur (couleurs du thème). */
function cioff_b_section( $inner, $background = '', $class = '' ) {
	$attrs = array( 'align' => 'full', 'layout' => array( 'type' => 'constrained' ) );
	$cls   = array( 'wp-block-group', 'alignfull' );
	if ( $class ) {
		$attrs['className'] = $class;
		$cls[]              = $class;
	}
	if ( $background ) {
		$attrs['backgroundColor'] = $background;
		$cls[]                    = 'has-' . $background . '-background-color';
		$cls[]                    = 'has-background';
	}
	return '<!-- wp:group ' . wp_json_encode( $attrs, JSON_UNESCAPED_SLASHES ) . " -->\n<div class=\"" . implode( ' ', $cls ) . "\">" . $inner . "</div>\n<!-- /wp:group -->\n\n";
}

function cioff_b_columns( ...$columns ) {
	$out = "<!-- wp:columns -->\n<div class=\"wp-block-columns\">";
	foreach ( $columns as $col ) {
		$out .= "<!-- wp:column -->\n<div class=\"wp-block-column\">" . $col . "</div>\n<!-- /wp:column -->";
	}
	return $out . "</div>\n<!-- /wp:columns -->\n\n";
}

function cioff_b_block( $name, array $attrs = array() ) {
	return '<!-- wp:cioff/' . $name . ( $attrs ? ' ' . wp_json_encode( $attrs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) : '' ) . " /-->\n\n";
}

function cioff_b_separator() {
	return "<!-- wp:separator -->\n<hr class=\"wp-block-separator has-alpha-channel-opacity\"/>\n<!-- /wp:separator -->\n\n";
}

function cioff_a_completer( $text ) {
	return cioff_b_p( '<em>[À compléter] ' . esc_html( $text ) . '</em>', 'cioff-a-completer' );
}

function cioff_default_page_content( $key ) {
	$url = fn( $k ) => cioff_page_url( $k );

	switch ( $key ) {
		case 'accueil':
			return cioff_b_section(
				cioff_b_p( 'Conseil International des Organisations de Festivals de Folklore et d\'Arts Traditionnels', 'cioff-surtitre' )
				. "<!-- wp:heading {\"level\":1} -->\n<h1 class=\"wp-block-heading\">Faire vivre les cultures traditionnelles du monde entier</h1>\n<!-- /wp:heading -->\n\n"
				. cioff_b_p( 'Le CIOFF France fédère des festivals et des groupes de folklore dans toute la France, en partenariat avec l\'UNESCO pour la sauvegarde du patrimoine culturel immatériel.' )
				. cioff_b_buttons( cioff_b_button( 'Trouver un festival ou un groupe', $url( 'annuaire' ) ), cioff_b_button( 'Découvrir le CIOFF', $url( 'cioff' ), true ) ),
				'bleu-nuit',
				'cioff-hero'
			)
			. cioff_b_section(
				cioff_b_h( 'À la une' )
				. cioff_b_block( 'agenda', array( 'nombre' => 3, 'une' => true, 'vide' => '' ) )
				. cioff_b_block( 'theme-annuel' ),
				'',
				'cioff-bande'
			)
			. cioff_b_section(
				cioff_b_columns(
					cioff_b_h( 'Prochaines réunions CIOFF France' ) . cioff_b_block( 'agenda', array( 'nombre' => 3, 'categorie' => 'reunion-cioff-france' ) ),
					cioff_b_h( 'Prochaines réunions CIOFF Jeunes' ) . cioff_b_block( 'agenda', array( 'nombre' => 3, 'categorie' => 'reunion-cioff-jeunes' ) )
				),
				'sable',
				'cioff-bande'
			)
			. cioff_b_section(
				cioff_b_h( 'Agenda des adhérents' )
				. cioff_b_block( 'agenda', array( 'nombre' => 6 ) )
				. cioff_b_buttons( cioff_b_button( 'Tout l\'agenda', $url( 'agenda' ), true ), cioff_b_button( 'Proposer un événement', $url( 'proposer-evenement' ), true ) ),
				'',
				'cioff-bande'
			)
			. cioff_b_section(
				cioff_b_h( 'Suivez-nous' )
				. cioff_b_p( 'Retrouvez l\'actualité des festivals et des groupes sur nos réseaux sociaux.' )
				. cioff_a_completer( 'Installer l\'extension gratuite « Smash Balloon Social Photo Feed », puis remplacer ce paragraphe par son bloc « Instagram Feed ».' ),
				'sable',
				'cioff-bande'
			)
			. cioff_b_section(
				cioff_b_columns(
					cioff_b_h( 'Un réseau international' ) . cioff_b_p( 'Le CIOFF France est la section nationale du CIOFF International, présent dans une centaine de pays et partenaire officiel de l\'UNESCO.' ),
					cioff_b_block( 'bouton-international', array( 'texte' => 'Visiter le site du CIOFF International' ) )
				),
				'',
				'cioff-bande'
			);

		case 'cioff':
			return cioff_b_p( 'Le CIOFF France (Conseil International des Organisations de Festivals de Folklore et d\'Arts Traditionnels) est l\'organisation nationale représentant la France au sein du CIOFF International, partenaire officiel de l\'UNESCO. Il fédère des festivals et groupes de folklore reconnus ou associés, et s\'appuie sur des bénévoles et membres actifs dans toute la France.', 'is-style-lead' )
				. cioff_b_h( 'Le CIOFF national et international' )
				. cioff_a_completer( 'Présentation du CIOFF France et du CIOFF International : histoire, chiffres clés, pays membres.' )
				. cioff_b_block( 'bouton-international' )
				. cioff_b_h( 'Missions et valeurs' )
				. cioff_a_completer( 'Missions et valeurs du CIOFF.' )
				. cioff_b_h( 'Fonctionnement de l\'organisation' )
				. cioff_a_completer( 'Assemblée générale, conseil d\'administration, bureau, adhésion…' )
				. cioff_b_h( 'Organigramme et commissions' )
				. cioff_a_completer( 'Insérer l\'organigramme (bloc Image) et la liste des commissions : communication, culture, festivals, groupes, jeunes…' )
				. cioff_b_h( 'Le CIOFF et l\'UNESCO' )
				. cioff_b_p( 'Le CIOFF est partenaire officiel de l\'UNESCO et contribue à la mise en œuvre de la <strong>Convention de 2003 pour la sauvegarde du patrimoine culturel immatériel</strong>.' )
				. cioff_a_completer( 'Actions menées par le CIOFF dans le cadre du partenariat UNESCO.' );

		case 'jeunes':
			return cioff_b_p( 'La branche jeune du CIOFF France rassemble les jeunes danseurs, musiciens et bénévoles des festivals et des groupes.', 'is-style-lead' )
				. cioff_b_h( 'Nos activités' )
				. cioff_a_completer( 'Présentation de la branche jeune et de ses activités.' )
				. cioff_b_h( 'Prochaines réunions CIOFF Jeunes' )
				. cioff_b_block( 'agenda', array( 'nombre' => 5, 'categorie' => 'reunion-cioff-jeunes' ) )
				. cioff_b_h( 'Témoignages' )
				. cioff_a_completer( 'Témoignages et retours d\'expérience (bloc Citation conseillé).' )
				. cioff_b_h( 'Rejoindre CIOFF Jeunes' )
				. cioff_a_completer( 'Conditions, contact et démarches pour rejoindre la branche jeune.' )
				. cioff_b_buttons( cioff_b_button( 'Nous contacter', $url( 'contact' ) ) );

		case 'annuaire':
			return cioff_b_p( 'Festivals, groupes et membres du CIOFF France partout en France. Cochez les types d\'adhérents à afficher, recherchez par nom, ville ou département, puis cliquez sur un point pour voir sa fiche.' )
				. cioff_b_block( 'annuaire', array( 'align' => 'wide' ) );

		case 'agenda':
			return cioff_b_p( 'Réunions du CIOFF France et du CIOFF Jeunes, festivals et événements des adhérents.' )
				. cioff_b_block( 'agenda', array( 'nombre' => 50 ) )
				. cioff_b_p( 'Vous êtes adhérent ? <a href="' . esc_url( $url( 'proposer-evenement' ) ) . '">Proposez votre événement</a> : il sera publié après validation.' );

		case 'outils':
			return cioff_b_p( 'Ressources à télécharger pour communiquer aux couleurs du CIOFF. Certaines ressources internes se trouvent dans l\'<a href="' . esc_url( $url( 'intranet' ) ) . '">intranet</a>.' )
				. cioff_b_h( 'Logos officiels et charte graphique' )
				. cioff_a_completer( 'Ajouter les logos avec le bloc « Fichier » (glisser-déposer le fichier dans l\'éditeur).' )
				. cioff_b_h( 'Documents de communication' )
				. cioff_a_completer( 'Plaquettes, dossiers de presse…' )
				. cioff_b_h( 'Affiches et visuels types' )
				. cioff_a_completer( 'Modèles d\'affiches et de visuels.' )
				. cioff_b_h( 'Guides et procédures' )
				. cioff_a_completer( 'Guides pratiques et procédures.' );

		case 'intranet':
			return cioff_b_p( 'Bienvenue dans l\'espace réservé aux adhérents du CIOFF France.', 'is-style-lead' )
				. cioff_b_columns(
					cioff_b_h( 'Membres', 3 ) . cioff_b_p( '<a href="' . esc_url( $url( 'membres' ) ) . '">Annuaire et adresses e-mail des membres</a>' ),
					cioff_b_h( 'Ma fiche', 3 ) . cioff_b_p( '<a href="' . esc_url( $url( 'mon-espace' ) ) . '">Mettre à jour la fiche de ma structure</a>' ),
					cioff_b_h( 'Agenda', 3 ) . cioff_b_p( '<a href="' . esc_url( $url( 'proposer-evenement' ) ) . '">Proposer un événement</a>' )
				)
				. cioff_b_h( 'Comptes-rendus de réunions' )
				. cioff_a_completer( 'Ajouter les comptes-rendus avec le bloc « Fichier ».' )
				. cioff_b_h( 'Documents officiels' )
				. cioff_a_completer( 'Contrats, dossier assurances…' )
				. cioff_b_h( 'Logos et ressources à usage interne' )
				. cioff_a_completer( 'Ressources internes.' )
				. cioff_b_h( 'Commande de matériel CIOFF' )
				. cioff_b_p( 'Drapeaux, ecocups… Utilisez le <a href="' . esc_url( $url( 'contact' ) ) . '">formulaire de contact</a> en choisissant « Commande de matériel ».' )
				. cioff_b_h( 'Procédures de reconnaissance' )
				. cioff_b_list(
					array(
						'<a href="#">[À compléter] Devenir festival reconnu internationalement</a>',
						'<a href="#">[À compléter] Obtenir le label CIOFF pour un groupe</a>',
					)
				);

		case 'membres':
			return cioff_b_block( 'liste-membres' );

		case 'mon-espace':
			return cioff_b_p( 'Gérez ici la fiche de votre structure dans l\'annuaire. Chaque envoi est relu par le CIOFF France avant publication.' )
				. cioff_b_block( 'ma-fiche' );

		case 'proposer-evenement':
			return cioff_b_p( 'Proposez un événement pour l\'agenda du site. Il sera publié après validation par le CIOFF France.' )
				. cioff_b_block( 'proposer-evenement' );

		case 'contact':
			return cioff_b_columns(
				cioff_b_h( 'Écrivez-nous' ) . cioff_b_block( 'contact' ),
				cioff_b_h( 'Vos interlocuteurs' ) . cioff_a_completer( 'Coordonnées des différents interlocuteurs : présidence, secrétariat, commissions…' )
			);

		case 'mentions':
			return cioff_b_h( 'Éditeur du site' )
				. cioff_a_completer( 'CIOFF France – adresse du siège, n° RNA / SIRET, directeur de la publication.' )
				. cioff_b_h( 'Hébergement' )
				. cioff_b_p( 'OVH SAS – 2 rue Kellermann, 59100 Roubaix, France.' )
				. cioff_b_h( 'Données personnelles' )
				. cioff_b_p( 'Voir la politique de confidentialité. Les données envoyées par le formulaire de contact servent uniquement à répondre aux demandes.' );
	}
	return '';
}
