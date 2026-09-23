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
		'label'              => array( 'Le label CIOFF', 'label-cioff', 'cioff' ),
		'jeunes'             => array( 'CIOFF Jeunes', 'cioff-jeunes', '' ),
		'engager'            => array( "S'engager", 's-engager', '' ),
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
		foreach ( array( 'cioff', 'jeunes', 'annuaire', 'agenda', 'engager', 'outils', 'intranet', 'contact' ) as $key ) {
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

/** Colonnes avec une classe (chiffres clés, cartes…). */
function cioff_b_columns_class( $class, array $columns ) {
	$out = '<!-- wp:columns {"align":"wide","className":"' . $class . '"} -->' . "\n<div class=\"wp-block-columns alignwide " . $class . '">';
	foreach ( $columns as $col ) {
		$out .= "<!-- wp:column -->\n<div class=\"wp-block-column\">" . $col . "</div>\n<!-- /wp:column -->";
	}
	return $out . "</div>\n<!-- /wp:columns -->\n\n";
}

/** Carte : groupe encadré avec titre, texte et bouton facultatif. */
function cioff_b_carte( $titre, $texte, $bouton = '', $url = '', $couleur = 'bleu' ) {
	$inner = cioff_b_h( $titre, 3 ) . cioff_b_p( $texte ) . ( $bouton ? cioff_b_buttons( cioff_b_button( $bouton, $url, true ) ) : '' );
	$class = 'cioff-carte cioff-carte--' . $couleur;
	return '<!-- wp:group {"className":"' . $class . '","layout":{"type":"constrained"}} -->' . "\n<div class=\"wp-block-group " . $class . '">' . $inner . "</div>\n<!-- /wp:group -->\n\n";
}

/** Chiffre clé. */
function cioff_b_chiffre( $nombre, $texte ) {
	return cioff_b_p( '<strong>' . esc_html( $nombre ) . '</strong> ' . esc_html( $texte ), 'cioff-chiffre' );
}

/** Liste numérotée. */
function cioff_b_ol( array $items ) {
	$out = "<!-- wp:list {\"ordered\":true} -->\n<ol class=\"wp-block-list\">";
	foreach ( $items as $item ) {
		$out .= "<!-- wp:list-item -->\n<li>" . $item . "</li>\n<!-- /wp:list-item -->";
	}
	return $out . "</ol>\n<!-- /wp:list -->\n\n";
}

function cioff_b_quote( $text ) {
	return "<!-- wp:quote -->\n<blockquote class=\"wp-block-quote\"><!-- wp:paragraph -->\n<p>" . esc_html( $text ) . "</p>\n<!-- /wp:paragraph --></blockquote>\n<!-- /wp:quote -->\n\n";
}

/** Contenu de départ de chaque page (textes repris de l'ancien site cioff-france.org). */
function cioff_default_page_content( $key ) {
	$url = fn( $k ) => cioff_page_url( $k );

	switch ( $key ) {
		case 'accueil':
			return cioff_b_section(
				cioff_b_p( 'Conseil International des Organisations de Festivals de Folklore et d\'Arts Traditionnels', 'cioff-surtitre' )
				. "<!-- wp:heading {\"level\":1} -->\n<h1 class=\"wp-block-heading\">Le monde est de toutes les couleurs</h1>\n<!-- /wp:heading -->\n\n"
				. cioff_b_p( 'Il s\'épanouit dans une harmonie multicolore et fraternelle sur nos scènes, dans nos rues, mais surtout dans nos cœurs. Le CIOFF France rassemble une trentaine de festivals, des groupes labellisés, des associations et des institutions qui œuvrent pour les cultures populaires et la paix.' )
				. cioff_b_buttons( cioff_b_button( 'Trouver un festival ou un groupe', $url( 'annuaire' ) ), cioff_b_button( 'Découvrir le CIOFF', $url( 'cioff' ), true ) ),
				'bleu-nuit',
				'cioff-hero'
			)
			. cioff_b_section(
				cioff_b_columns_class(
					'cioff-chiffres',
					array(
						cioff_b_chiffre( '29', 'festivals en France' ),
						cioff_b_chiffre( '21', 'groupes labellisés CIOFF' ),
						cioff_b_chiffre( '103', 'pays membres du CIOFF' ),
						cioff_b_chiffre( '1970', 'création du CIOFF à Confolens' ),
					)
				),
				'',
				'cioff-bande cioff-bande--serree'
			)
			. cioff_b_section(
				cioff_b_h( 'À la une' )
				. cioff_b_block( 'agenda', array( 'nombre' => 3, 'une' => true, 'vide' => '' ) )
				. cioff_b_block( 'theme-annuel' ),
				'',
				'cioff-bande'
			)
			. cioff_b_section(
				cioff_b_h( 'Trouvez un festival près de chez vous' )
				. cioff_b_p( 'Festivals, groupes labellisés et membres du CIOFF France partout en France. Cliquez sur un point pour découvrir sa fiche.' )
				. cioff_b_block( 'annuaire', array( 'liste' => false, 'hauteur' => 480, 'align' => 'wide' ) )
				. cioff_b_buttons( cioff_b_button( 'Ouvrir l\'annuaire complet', $url( 'annuaire' ) ) ),
				'sable',
				'cioff-bande'
			)
			. cioff_b_section(
				cioff_b_columns(
					cioff_b_h( 'Prochaines réunions CIOFF France' ) . cioff_b_block( 'agenda', array( 'nombre' => 3, 'categorie' => 'reunion-cioff-france' ) ),
					cioff_b_h( 'Prochaines réunions CIOFF Jeunes' ) . cioff_b_block( 'agenda', array( 'nombre' => 3, 'categorie' => 'reunion-cioff-jeunes' ) )
				)
				. cioff_b_buttons( cioff_b_button( 'Tout l\'agenda', $url( 'agenda' ), true ), cioff_b_button( 'Proposer un événement', $url( 'proposer-evenement' ), true ) ),
				'',
				'cioff-bande'
			)
			. cioff_b_section(
				cioff_b_h( 'Actualités' )
				. "<!-- wp:latest-posts {\"postsToShow\":3,\"displayPostDate\":true,\"postLayout\":\"grid\",\"columns\":3,\"displayFeaturedImage\":true,\"featuredImageSizeSlug\":\"medium\",\"align\":\"wide\"} /-->\n\n",
				'',
				'cioff-bande'
			)
			. cioff_b_section(
				cioff_b_h( 'Semeurs de paix : engagez-vous' )
				. cioff_b_columns_class(
					'cioff-cartes',
					array(
						cioff_b_carte( 'Devenir bénévole', 'Des milliers de bénévoles font vivre nos festivals chaque été : accueil des groupes, accompagnement, familles d\'accueil…', 'Trouver un festival', $url( 'engager' ), 'terre' ),
						cioff_b_carte( 'Rejoindre CIOFF Jeunes', 'Vous avez entre 15 et 28 ans et vous êtes investi(e) dans un festival ou un groupe ? La branche jeune vous attend.', 'CIOFF Jeunes', $url( 'jeunes' ), 'vert' ),
						cioff_b_carte( 'Faire labelliser son groupe', 'Votre ensemble veut représenter la France dans les festivals CIOFF du monde entier ? Découvrez la procédure.', 'Le label CIOFF', $url( 'label' ), 'ocre' ),
						cioff_b_carte( 'Devenir partenaire', 'Collectivités, entreprises, institutions : soutenez la diversité culturelle et la culture de la paix.', 'Nous contacter', $url( 'contact' ), 'bleu' ),
					)
				),
				'sable',
				'cioff-bande'
			)
			. cioff_b_section(
				cioff_b_columns(
					cioff_b_h( 'Suivez-nous' ) . cioff_b_p( 'Retrouvez l\'actualité des festivals et des groupes sur nos réseaux sociaux.' ) . cioff_b_block( 'reseaux' )
					. cioff_a_completer( 'Installer l\'extension gratuite « Smash Balloon Social Photo Feed », puis ajouter ici son bloc « Instagram Feed ».' ),
					cioff_b_h( 'Un réseau mondial' ) . cioff_b_p( 'Le CIOFF France est la section nationale du CIOFF International : 103 pays, plus d\'un million de personnes, partenaire officiel de l\'UNESCO.' ) . cioff_b_block( 'bouton-international', array( 'texte' => 'Visiter le site du CIOFF International' ) )
				),
				'',
				'cioff-bande'
			)
			. cioff_b_section(
				cioff_b_p( 'Avec le soutien de', 'cioff-surtitre' )
				. cioff_a_completer( 'Ajouter les logos des partenaires (UNESCO, CIOFF International, ministère, collectivités…) avec le bloc « Galerie ».' ),
				'',
				'cioff-bande cioff-partenaires'
			);

		case 'cioff':
			return cioff_b_p( 'Cinquante ans après sa création, le CIOFF® France rassemble les organisateurs de plus d\'une trentaine de festivals, des groupes labellisés, des associations et des institutions œuvrant pour la promotion et la diffusion des cultures populaires. Sa section « Jeunes » mobilise la jeunesse autour de l\'échange des cultures pour la paix.', 'is-style-lead' )
				. cioff_b_p( 'Les centaines de milliers de spectateurs passionnés qui vivent intensément chaque année nos festivals d\'été sont la récompense de ceux sans qui toutes ces merveilleuses fêtes n\'auraient jamais existé : les milliers de bénévoles qui œuvrent avec bonheur et compétence dans chacune de nos organisations.' )
				. cioff_b_h( 'Le CIOFF International' )
				. cioff_b_p( 'Le 8 août 1970, le CIOFF® a été créé par Henri Coursaget, à Confolens, en France. Deux objectifs sont poursuivis :' )
				. cioff_b_list(
					array(
						'créer des liens de plus en plus forts entre les festivals d\'Europe et permettre la venue de groupes plus lointains, avec des possibilités de tournées ;',
						'par ces échanges de plus en plus nombreux, créer une fraternité toujours plus grande et servir la cause de la paix.',
					)
				)
				. cioff_b_columns_class(
					'cioff-chiffres',
					array(
						cioff_b_chiffre( '103', 'pays membres' ),
						cioff_b_chiffre( '1 million', 'de personnes associées' ),
						cioff_b_chiffre( '30 000', 'groupes et associations' ),
						cioff_b_chiffre( '320+', 'festivals internationaux' ),
					)
				)
				. cioff_b_p( 'Le CIOFF® promeut aussi la diversité culturelle à travers plus de 1 500 expositions d\'art et d\'artisanat traditionnels, plus de 5 000 ateliers de danse, de musique, de chant et d\'artisanat, et les Folkloriades mondiales.' )
				. cioff_b_block( 'bouton-international' )
				. cioff_b_h( 'Missions et valeurs' )
				. cioff_b_list(
					array(
						'Promouvoir le patrimoine culturel immatériel en coopération avec l\'UNESCO, à travers la danse, la musique, le chant, les jeux, l\'artisanat traditionnel, les costumes et la cuisine.',
						'Préserver l\'identité culturelle à travers le monde.',
						'Cultiver le patrimoine culturel par l\'éducation des enfants et des jeunes.',
						'Servir la cause de la paix et de la non-violence à travers une coopération culturelle internationale.',
					)
				)
				. cioff_b_h( 'Une dynamique pour la culture de la paix', 3 )
				. cioff_b_p( 'Aujourd\'hui ensemble, artistes, festivaliers, organisateurs et bénévoles sont les auteurs de la réussite du CIOFF® et de ses festivals, de leur engagement pour la paix par la rencontre des cultures.' )
				. cioff_b_quote( '« L\'autre » n\'est pas une curiosité, c\'est un frère, un ami venu avec des richesses qu\'il est heureux de partager. Il émerveille et il est émerveillé. Avec le CIOFF® France, soyez des semeurs de paix, de générosité et d\'espoir.' )
				. cioff_b_h( 'Le CIOFF et l\'UNESCO' )
				. cioff_b_p( 'Le CIOFF® est partenaire officiel de l\'UNESCO. Ses membres encouragent toutes les activités liées au <strong>patrimoine culturel immatériel</strong> (PCI), dans l\'esprit de la <strong>Convention de 2003 pour la sauvegarde du patrimoine culturel immatériel</strong>.' )
				. cioff_a_completer( 'Actions menées dans le cadre du partenariat UNESCO (ex. : soirée à l\'UNESCO du 3 juillet 2015).' )
				. cioff_b_h( 'Les actions culturelles' )
				. cioff_b_p( 'Lors des festivals d\'adultes et d\'enfants, les membres du CIOFF® favorisent la promotion, la sauvegarde et la mise en valeur des traditions culturelles et artistiques des peuples du monde : faire connaître la diversité des cultures, mieux comprendre leurs différences et favoriser l\'ouverture d\'esprit des jeunes. Parmi ces actions :' )
				. cioff_b_list(
					array(
						'des expositions (costumes, instruments de musique, jeux en bois, métiers et objets traditionnels…) ;',
						'des programmes d\'éducation au PCI pour tous les publics (adultes, enfants, personnes âgées ou isolées, personnes en situation de handicap) ;',
						'des conférences et ciné-débats en lien avec les pays invités ;',
						'des ateliers : stages de danse, de musique, de langue, d\'artisanat ;',
						'des forums et des projets communs entre personnes de pays différents.',
					)
				)
				. cioff_b_h( 'Fonctionnement et organigramme' )
				. cioff_b_p( 'Le CIOFF® France est administré par un conseil d\'administration où siègent notamment deux représentants de CIOFF Jeunes. Des commissions (culture, communication, label, jeunes…) préparent les décisions et animent le réseau.' )
				. cioff_a_completer( 'Insérer l\'organigramme (bloc Image) et la composition du bureau et des commissions.' )
				. cioff_b_buttons( cioff_b_button( 'Le label CIOFF', $url( 'label' ) ), cioff_b_button( 'CIOFF Jeunes', $url( 'jeunes' ), true ) );

		case 'label':
			return cioff_b_p( 'Toutes les sections nationales du CIOFF® dans le monde sont constituées de festivals et de groupes folkloriques. Le CIOFF® France sélectionne et labellise des groupes pour présenter la culture traditionnelle française dans les festivals à l\'étranger.', 'is-style-lead' )
				. cioff_b_p( 'Le label n\'est pas une fédération de plus : il distingue des groupes en cohérence avec les classifications du CIOFF® et les accompagne pour maintenir la qualité artistique et les valeurs d\'ouverture et de tolérance qu\'ils portent dans le monde. Une vingtaine de groupes sont aujourd\'hui labellisés.' )
				. cioff_b_h( 'La procédure' )
				. cioff_b_ol(
					array(
						'<strong>Candidature</strong> : le groupe envoie un dossier comprenant la fiche de présentation, l\'engagement à l\'éthique du CIOFF® et des documents de présentation (photos et vidéos obligatoires).',
						'<strong>Visite</strong> : si la candidature est recevable, deux membres de la commission rendent visite au groupe.',
						'<strong>Festival</strong> : le groupe participe à un festival CIOFF® français ; les visiteurs et le festival hôte rédigent un rapport.',
						'<strong>Décision</strong> : la commission et le conseil d\'administration statuent sur la labellisation.',
					)
				)
				. cioff_a_completer( 'Ajouter la « Fiche présentation ensemble CIOFF » à télécharger (bloc Fichier).' )
				. cioff_b_p( 'Renseignements : <a href="mailto:label@cioff-france.org">label@cioff-france.org</a>' )
				. cioff_b_buttons( cioff_b_button( 'Voir les groupes labellisés', $url( 'annuaire' ) ), cioff_b_button( 'Nous contacter', $url( 'contact' ), true ) );

		case 'jeunes':
			return cioff_b_p( 'Le CIOFF® Jeune France est une commission du CIOFF® France. Créée en 2001, elle regroupe des jeunes de 15 à 28 ans investis dans les festivals et les groupes labellisés du réseau, pour apporter un regard neuf sur l\'organisation tout en formant la relève.', 'is-style-lead' )
				. cioff_b_h( 'Nos missions' )
				. cioff_b_list(
					array(
						'Faciliter l\'intégration des jeunes bénévoles au sein de leur association.',
						'Mettre en commun et échanger les expériences de chacun.',
						'Monter des projets communs autour des arts et traditions populaires : une exposition sur les jeux traditionnels circule dans les festivals du réseau, et un nouveau projet est consacré aux métiers traditionnels.',
					)
				)
				. cioff_b_h( 'Prochaines réunions CIOFF Jeunes' )
				. cioff_b_block( 'agenda', array( 'nombre' => 5, 'categorie' => 'reunion-cioff-jeunes' ) )
				. cioff_b_h( 'Organisation' )
				. cioff_b_p( 'CIOFF Jeunes est piloté par un comité de coordination de cinq membres : un président élu pour 4 ans et quatre responsables de commission élus pour 2 ans. Depuis 2017, deux représentants des jeunes siègent au conseil d\'administration du CIOFF® France.' )
				. cioff_b_columns_class(
					'cioff-cartes',
					array(
						cioff_b_carte( 'Communication', 'Partager l\'actualité du réseau, des groupes, des festivals, du CIOFF international et de l\'UNESCO ; animer les réseaux sociaux.', '', '', 'bleu' ),
						cioff_b_carte( 'Bénévolat', 'Faire le lien entre les bénévoles du réseau : carte CIOFF Jeunes, banque des accompagnateurs et des bénévoles, guide des guides, lexiques.', '', '', 'terre' ),
						cioff_b_carte( 'Action commune', 'Développer des projets communs à tout le réseau, groupes comme festivals, et outiller les jeunes qui veulent créer une commission.', '', '', 'vert' ),
						cioff_b_carte( 'Culture', 'Relayer auprès des jeunes le groupe de travail culture du CIOFF® France et valoriser les apports culturels des festivals et des groupes.', '', '', 'ocre' ),
					)
				)
				. cioff_b_h( 'Témoignages' )
				. cioff_a_completer( 'Témoignages et retours d\'expérience (utiliser la composition « Témoignage »).' )
				. cioff_b_h( 'Rejoindre CIOFF Jeunes' )
				. cioff_b_p( 'Vous avez entre 15 et 28 ans et vous êtes bénévole dans un festival ou membre d\'un groupe du réseau ? Écrivez-nous : <a href="mailto:contact@cioffjeune.fr">contact@cioffjeune.fr</a>' )
				. cioff_b_buttons( cioff_b_button( 'Nous contacter', $url( 'contact' ) ) );

		case 'engager':
			return cioff_b_p( 'Artistes, festivaliers, organisateurs et bénévoles : ensemble, nous sommes les auteurs de la réussite du CIOFF® et de ses festivals. Voici comment nous rejoindre.', 'is-style-lead' )
				. cioff_b_h( 'Devenir bénévole dans un festival' )
				. cioff_b_p( 'Accueil et accompagnement des groupes, familles d\'accueil, logistique, billetterie, traduction… Chaque festival a besoin de vous. Choisissez un festival sur la carte et contactez-le depuis sa fiche.' )
				. cioff_b_block( 'annuaire', array( 'types' => array( 'festival', 'festival-associe' ), 'hauteur' => 420, 'align' => 'wide' ) )
				. cioff_b_h( 'Rejoindre CIOFF Jeunes' )
				. cioff_b_p( 'La branche jeune rassemble les 15-28 ans du réseau.' )
				. cioff_b_buttons( cioff_b_button( 'Découvrir CIOFF Jeunes', $url( 'jeunes' ), true ) )
				. cioff_b_h( 'Faire labelliser son groupe' )
				. cioff_b_p( 'Représentez la culture traditionnelle française dans les festivals CIOFF du monde entier.' )
				. cioff_b_buttons( cioff_b_button( 'Le label CIOFF', $url( 'label' ), true ) )
				. cioff_b_h( 'Adhérer au CIOFF France' )
				. cioff_b_p( 'Festivals, groupes, structures (membres participants) et personnes (membres individuels) peuvent adhérer au CIOFF® France.' )
				. cioff_a_completer( 'Conditions et montant des cotisations.' )
				. cioff_b_buttons( cioff_b_button( 'Demander une adhésion', $url( 'contact' ) ) )
				. cioff_b_h( 'Devenir partenaire' )
				. cioff_b_p( 'Collectivités, entreprises, institutions : soutenez la diversité culturelle et la culture de la paix.' )
				. cioff_b_buttons( cioff_b_button( 'Nous contacter', $url( 'contact' ), true ) );

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
				. cioff_a_completer( 'Plaquettes, communiqués de presse, revue de presse, cartes des festivals…' )
				. cioff_b_h( 'Affiches et visuels types' )
				. cioff_a_completer( 'Modèles d\'affiches et de visuels, photothèque.' )
				. cioff_b_h( 'Guides et procédures' )
				. cioff_b_list( array( '<a href="' . esc_url( $url( 'label' ) ) . '">Procédure de labellisation des groupes</a>' ) )
				. cioff_a_completer( 'Guides pratiques (guide des guides, lexiques…).' );

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
						'<a href="' . esc_url( $url( 'label' ) ) . '">Obtenir le label CIOFF pour un groupe</a>',
						'[À compléter] Devenir festival reconnu internationalement',
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
				cioff_b_h( 'Vos interlocuteurs' )
				. cioff_b_list(
					array(
						'Label CIOFF : <a href="mailto:label@cioff-france.org">label@cioff-france.org</a>',
						'CIOFF Jeunes : <a href="mailto:contact@cioffjeune.fr">contact@cioffjeune.fr</a>',
					)
				)
				. cioff_a_completer( 'Présidence, secrétariat, commissions (utiliser la composition « Interlocuteurs »).' )
			);

		case 'mentions':
			return cioff_b_h( 'Éditeur du site' )
				. cioff_a_completer( 'CIOFF France – adresse du siège, n° RNA / SIRET, directeur de la publication.' )
				. cioff_b_h( 'Hébergement' )
				. cioff_b_p( 'OVH SAS – 2 rue Kellermann, 59100 Roubaix, France.' )
				. cioff_b_h( 'Données personnelles' )
				. cioff_b_p( 'Les données envoyées par le formulaire de contact servent uniquement à répondre aux demandes. Les fiches de l\'annuaire sont publiées avec l\'accord des adhérents, qui peuvent les modifier à tout moment depuis leur espace.' );
	}
	return '';
}

