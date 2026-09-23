<?php
/**
 * Blocs « CIOFF France » disponibles dans l'éditeur (bouton + → catégorie CIOFF France).
 * Chaque bloc existe aussi en shortcode, par exemple [cioff_annuaire].
 */

defined( 'ABSPATH' ) || exit;

add_filter( 'block_categories_all', function ( $categories ) {
	array_unshift( $categories, array( 'slug' => 'cioff', 'title' => 'CIOFF France' ) );
	return $categories;
} );

function cioff_blocks() {
	return array(
		'annuaire'            => array(
			'title'       => 'Annuaire des adhérents (carte)',
			'description' => 'Carte interactive avec filtres par type, recherche et liste des adhérents.',
			'icon'        => 'location-alt',
			'attributes'  => array(
				'types'   => array( 'type' => 'array', 'default' => array() ),
				'hauteur' => array( 'type' => 'number', 'default' => 520 ),
				'liste'   => array( 'type' => 'boolean', 'default' => true ),
			),
			'render'      => fn( $a ) => cioff_render_annuaire(
				array(
					'types'   => implode( ',', (array) $a['types'] ),
					'hauteur' => $a['hauteur'],
					'liste'   => $a['liste'],
				)
			),
		),
		'fiche-adherent'      => array(
			'title'       => 'Détails de la fiche adhérent',
			'description' => 'Affiche les informations de la fiche (à utiliser dans le modèle « Fiche adhérent »).',
			'icon'        => 'id-alt',
			'attributes'  => array(),
			'uses'        => array( 'postId' ),
			'render'      => fn( $a, $c, $block ) => cioff_render_fiche( $block->context['postId'] ?? get_the_ID() ),
		),
		'agenda'              => array(
			'title'       => 'Agenda : prochains événements',
			'description' => 'Liste des prochains événements, éventuellement filtrée par catégorie.',
			'icon'        => 'calendar-alt',
			'attributes'  => array(
				'nombre'    => array( 'type' => 'number', 'default' => 5 ),
				'categorie' => array( 'type' => 'string', 'default' => '' ),
				'une'       => array( 'type' => 'boolean', 'default' => false ),
				'vide'      => array( 'type' => 'string', 'default' => 'Aucun événement à venir pour le moment.' ),
			),
			'render'      => fn( $a ) => cioff_render_agenda( $a ),
		),
		'evenement-details'   => array(
			'title'       => "Détails de l'événement",
			'description' => 'Date, lieu et lien de l\'événement (modèle « Événement »).',
			'icon'        => 'clock',
			'attributes'  => array(),
			'uses'        => array( 'postId' ),
			'render'      => fn( $a, $c, $block ) => cioff_render_event_details( $block->context['postId'] ?? get_the_ID() ),
		),
		'theme-annuel'        => array(
			'title'       => 'Thème annuel',
			'description' => 'Affiche le thème annuel défini dans Réglages CIOFF.',
			'icon'        => 'star-filled',
			'attributes'  => array(),
			'render'      => 'cioff_render_theme_annuel',
		),
		'bouton-international' => array(
			'title'       => 'Bouton CIOFF International',
			'description' => 'Lien vers le site du CIOFF International (adresse réglée dans Réglages CIOFF).',
			'icon'        => 'admin-site-alt3',
			'attributes'  => array( 'texte' => array( 'type' => 'string', 'default' => 'CIOFF International' ) ),
			'render'      => fn( $a ) => sprintf(
				'<a class="cioff-intl wp-element-button" href="%1$s" target="_blank" rel="noopener">%2$s <span aria-hidden="true">↗</span></a>',
				esc_url( cioff_option( 'lien_international' ) ),
				esc_html( $a['texte'] )
			),
		),
		'reseaux'             => array(
			'title'       => 'Réseaux sociaux du CIOFF France',
			'description' => 'Liens vers Facebook, Instagram et YouTube (adresses réglées dans Réglages CIOFF).',
			'icon'        => 'share',
			'attributes'  => array(),
			'render'      => 'cioff_render_reseaux',
		),
		'connexion'           => array(
			'title'       => 'Lien Espace adhérent',
			'description' => 'Lien « Espace adhérent » ou « Mon espace / Déconnexion » selon la connexion.',
			'icon'        => 'admin-users',
			'attributes'  => array(),
			'render'      => 'cioff_render_connexion',
		),
		'ma-fiche'            => array(
			'title'       => 'Formulaire « Ma fiche adhérent »',
			'description' => 'Permet à l\'adhérent connecté de créer ou modifier sa fiche.',
			'icon'        => 'edit',
			'attributes'  => array(),
			'render'      => 'cioff_render_ma_fiche',
			'placeholder' => true,
		),
		'proposer-evenement'  => array(
			'title'       => 'Formulaire « Proposer un événement »',
			'description' => 'Permet à un adhérent connecté de proposer un événement pour l\'agenda.',
			'icon'        => 'calendar',
			'attributes'  => array(),
			'render'      => 'cioff_render_proposer_evenement',
			'placeholder' => true,
		),
		'contact'             => array(
			'title'       => 'Formulaire de contact',
			'description' => 'Formulaire avec catégories ; destinataires réglés dans Réglages CIOFF.',
			'icon'        => 'email',
			'attributes'  => array( 'categorie' => array( 'type' => 'string', 'default' => '' ) ),
			'render'      => fn( $a ) => cioff_render_contact_form( $a ),
			'placeholder' => true,
		),
		'liste-membres'       => array(
			'title'       => 'Liste des membres (intranet)',
			'description' => 'Noms, structures et e-mails des adhérents. Visible uniquement par les adhérents connectés.',
			'icon'        => 'groups',
			'attributes'  => array(),
			'render'      => 'cioff_render_liste_membres',
			'placeholder' => true,
		),
	);
}

add_action( 'init', function () {
	wp_register_script(
		'cioff-blocks',
		CIOFF_URL . 'assets/js/blocks.js',
		array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-server-side-render', 'wp-data' ),
		CIOFF_VERSION,
		true
	);
	foreach ( cioff_blocks() as $slug => $b ) {
		register_block_type(
			'cioff/' . $slug,
			array(
				'api_version'     => 3,
				'title'           => $b['title'],
				'description'     => $b['description'],
				'category'        => 'cioff',
				'icon'            => $b['icon'],
				'attributes'      => $b['attributes'],
				'uses_context'    => $b['uses'] ?? array(),
				'supports'        => array( 'html' => false, 'align' => array( 'wide', 'full' ) ),
				'editor_script'   => 'cioff-blocks',
				'render_callback' => function ( $attributes, $content, $block ) use ( $b ) {
					$html = call_user_func( $b['render'], $attributes, $content, $block );
					return $html ? '<div ' . get_block_wrapper_attributes() . '>' . $html . '</div>' : '';
				},
			)
		);
	}

	// Shortcodes équivalents.
	add_shortcode( 'cioff_annuaire', fn( $a ) => cioff_render_annuaire( (array) $a ) );
	add_shortcode( 'cioff_agenda', fn( $a ) => cioff_render_agenda( (array) $a ) );
	add_shortcode( 'cioff_ma_fiche', 'cioff_render_ma_fiche' );
	add_shortcode( 'cioff_proposer_evenement', 'cioff_render_proposer_evenement' );
	add_shortcode( 'cioff_contact', fn( $a ) => cioff_render_contact_form( (array) $a ) );
	add_shortcode( 'cioff_liste_membres', 'cioff_render_liste_membres' );
	add_shortcode( 'cioff_theme_annuel', 'cioff_render_theme_annuel' );
}, 30 );

// Données utiles aux réglages des blocs, uniquement dans l'éditeur.
add_action( 'enqueue_block_editor_assets', function () {
	$js = array();
	foreach ( cioff_blocks() as $slug => $b ) {
		$js[ $slug ] = array( 'placeholder' => ! empty( $b['placeholder'] ) );
	}
	$types = array();
	foreach ( cioff_types() as $slug => $t ) {
		$types[] = array( 'value' => $slug, 'label' => $t['pluriel'] );
	}
	$cats = array( array( 'value' => '', 'label' => 'Toutes les catégories' ) );
	foreach ( get_terms( array( 'taxonomy' => 'cioff_evt_cat', 'hide_empty' => false ) ) ?: array() as $term ) {
		if ( ! is_wp_error( $term ) && isset( $term->slug ) ) {
			$cats[] = array( 'value' => $term->slug, 'label' => $term->name );
		}
	}
	wp_add_inline_script( 'cioff-blocks', 'window.cioffBlocks = ' . wp_json_encode( array( 'blocks' => $js, 'types' => $types, 'categories' => $cats ) ) . ';', 'before' );
} );

function cioff_render_theme_annuel() {
	$titre = cioff_option( 'theme_annuel_titre' );
	$texte = cioff_option( 'theme_annuel_texte' );
	$image = (int) cioff_option( 'theme_annuel_image', 0 );
	cioff_enqueue_front_css();
	if ( ! $titre ) {
		return current_user_can( 'cioff_reglages' )
			? '<div class="cioff-notice">Le thème annuel n\'est pas encore renseigné : <a href="' . esc_url( admin_url( 'admin.php?page=cioff-reglages' ) ) . '">Réglages CIOFF</a>. (Ce message n\'est visible que par les administrateurs.)</div>'
			: '';
	}
	return sprintf(
		'<section class="cioff-theme-annuel">%1$s<div class="cioff-theme-annuel__texte"><p class="cioff-theme-annuel__surtitre">Thème de l\'année %2$s</p><h2>%3$s</h2>%4$s</div></section>',
		$image ? '<div class="cioff-theme-annuel__image">' . wp_get_attachment_image( $image, 'large' ) . '</div>' : '',
		esc_html( wp_date( 'Y' ) ),
		esc_html( $titre ),
		wp_kses_post( wpautop( $texte ) )
	);
}

function cioff_render_reseaux() {
	$icons = array(
		'facebook'  => array( 'Facebook', '<path d="M13.5 22v-8h2.7l.4-3.2h-3.1V8.8c0-.9.3-1.5 1.6-1.5h1.7V4.4c-.3 0-1.3-.1-2.4-.1-2.4 0-4 1.5-4 4.1v2.4H7.7V14h2.7v8z"/>' ),
		'instagram' => array( 'Instagram', '<path d="M12 7.3A4.7 4.7 0 1 0 12 16.7 4.7 4.7 0 0 0 12 7.3zm0 7.7a3 3 0 1 1 0-6 3 3 0 0 1 0 6zm6-7.9a1.1 1.1 0 1 1-2.2 0 1.1 1.1 0 0 1 2.2 0zM21.9 8.2c-.1-1.6-.4-3-1.6-4.2S17.6 2.3 16 2.2C14.3 2.1 9.7 2.1 8 2.2 6.4 2.3 5 2.6 3.8 3.8S2.3 6.5 2.2 8.1c-.1 1.7-.1 6.2 0 7.9.1 1.6.4 3 1.6 4.2s2.6 1.5 4.2 1.6c1.7.1 6.2.1 7.9 0 1.6-.1 3-.4 4.2-1.6s1.5-2.6 1.6-4.2c.1-1.7.1-6.2.2-7.8zM19.8 18a3.1 3.1 0 0 1-1.8 1.8c-1.2.5-4.1.4-5.9.4s-4.7.1-5.9-.4A3.1 3.1 0 0 1 4.4 18c-.5-1.2-.4-4.1-.4-5.9s-.1-4.7.4-5.9A3.1 3.1 0 0 1 6.2 4.4c1.2-.5 4.1-.4 5.9-.4s4.7-.1 5.9.4a3.1 3.1 0 0 1 1.8 1.8c.5 1.2.4 4.1.4 5.9s.1 4.7-.4 5.9z"/>' ),
		'youtube'   => array( 'YouTube', '<path d="M21.6 7.2a2.5 2.5 0 0 0-1.8-1.8C18.2 5 12 5 12 5s-6.2 0-7.8.4A2.5 2.5 0 0 0 2.4 7.2 26 26 0 0 0 2 12a26 26 0 0 0 .4 4.8 2.5 2.5 0 0 0 1.8 1.8C5.8 19 12 19 12 19s6.2 0 7.8-.4a2.5 2.5 0 0 0 1.8-1.8A26 26 0 0 0 22 12a26 26 0 0 0-.4-4.8zM10 15V9l5.2 3z"/>' ),
	);
	$out = '';
	foreach ( $icons as $key => $icon ) {
		$url = cioff_option( $key );
		if ( $url ) {
			$out .= sprintf(
				'<li><a href="%1$s" target="_blank" rel="noopener" aria-label="%2$s"><svg viewBox="0 0 24 24" width="22" height="22" fill="currentColor" aria-hidden="true">%3$s</svg></a></li>',
				esc_url( $url ),
				esc_attr( $icon[0] . ' du CIOFF France' ),
				$icon[1]
			);
		}
	}
	if ( ! $out ) {
		return current_user_can( 'cioff_reglages' ) ? '<p class="cioff-reseaux-vide"><a href="' . esc_url( admin_url( 'admin.php?page=cioff-reglages' ) ) . '">Renseigner les réseaux sociaux</a></p>' : '';
	}
	return '<ul class="cioff-reseaux">' . $out . '</ul>';
}

function cioff_render_connexion() {
	if ( ! is_user_logged_in() ) {
		return '<a class="cioff-connexion" href="' . esc_url( cioff_page_url( 'mon-espace' ) ) . '">Espace adhérent</a>';
	}
	$links = array();
	if ( current_user_can( 'edit_posts' ) ) {
		$links[] = '<a href="' . esc_url( admin_url() ) . '">Administration</a>';
	}
	$links[] = '<a href="' . esc_url( cioff_page_url( 'mon-espace' ) ) . '">Mon espace</a>';
	$links[] = '<a href="' . esc_url( wp_logout_url( home_url() ) ) . '">Déconnexion</a>';
	return '<span class="cioff-connexion">' . implode( ' · ', $links ) . '</span>';
}
