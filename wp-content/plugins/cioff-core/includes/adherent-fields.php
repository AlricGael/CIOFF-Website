<?php
/**
 * Champs des fiches adhérents (§3.4.4 et §3.4.5).
 *
 * Une seule définition sert à la fois au formulaire de l'administration, au formulaire « Mon espace »
 * et à l'affichage public de la fiche. Pour ajouter un champ, il suffit de l'ajouter ici.
 *
 * « familles » : festival, groupe, participant, individuel (voir cioff_types()).
 */

defined( 'ABSPATH' ) || exit;

function cioff_fields() {
	$tous       = array( 'festival', 'groupe', 'participant', 'individuel' );
	$sauf_indiv = array( 'festival', 'groupe', 'participant' );

	return array(
		// Localisation.
		'ville'          => array( 'label' => 'Ville', 'type' => 'text', 'familles' => $sauf_indiv, 'section' => 'Localisation', 'requis' => true ),
		'adresse'        => array( 'label' => 'Adresse (facultatif)', 'type' => 'text', 'familles' => $sauf_indiv, 'section' => 'Localisation', 'aide' => 'Permet de placer le point plus précisément sur la carte. Elle n\'est pas affichée sur la fiche.', 'prive' => true ),
		'departement'    => array( 'label' => 'Département', 'type' => 'departement', 'familles' => $tous, 'section' => 'Localisation', 'requis' => true ),

		// Contact.
		'email'          => array( 'label' => 'E-mail de contact', 'type' => 'email', 'familles' => $tous, 'section' => 'Contact' ),
		'telephone'      => array( 'label' => 'Téléphone', 'type' => 'tel', 'familles' => $tous, 'section' => 'Contact' ),
		'site_web'       => array( 'label' => 'Site web', 'type' => 'url', 'familles' => $sauf_indiv, 'section' => 'Contact' ),
		'facebook'       => array( 'label' => 'Facebook', 'type' => 'url', 'familles' => $sauf_indiv, 'section' => 'Réseaux sociaux' ),
		'instagram'      => array( 'label' => 'Instagram', 'type' => 'url', 'familles' => $sauf_indiv, 'section' => 'Réseaux sociaux' ),
		'youtube'        => array( 'label' => 'YouTube', 'type' => 'url', 'familles' => $sauf_indiv, 'section' => 'Réseaux sociaux' ),

		// Festival et festival associé.
		'affiche'        => array( 'label' => 'Affiche officielle', 'type' => 'image', 'familles' => array( 'festival' ), 'section' => 'Le festival' ),
		'dates'          => array( 'label' => 'Dates des prochaines éditions', 'type' => 'textarea', 'familles' => array( 'festival' ), 'section' => 'Le festival', 'aide' => 'Une édition par ligne, sur au moins 3 ans. Ex. : 2027 : du 12 au 18 juillet', 'lignes' => 3 ),
		'programmation'  => array( 'label' => 'Programmation et groupes invités', 'type' => 'textarea', 'familles' => array( 'festival' ), 'section' => 'Le festival', 'lignes' => 5 ),
		'lien_benevole'  => array( 'label' => "Lien d'inscription bénévole", 'type' => 'url', 'familles' => array( 'festival' ), 'section' => 'Le festival' ),

		// Groupe labellisé et groupe associé.
		'categorie'      => array(
			'label'    => 'Catégorie',
			'type'     => 'select',
			'familles' => array( 'groupe' ),
			'section'  => 'Le groupe',
			'options'  => array(
				'adultes'           => 'Adultes',
				'enfants'           => 'Enfants',
				'intergenerationnel' => 'Intergénérationnel',
			),
		),
		'repertoire'     => array( 'label' => 'Spécificité et répertoire dansé', 'type' => 'textarea', 'familles' => array( 'groupe' ), 'section' => 'Le groupe', 'lignes' => 4 ),
		'tournees'       => array( 'label' => "Tournées à l'étranger", 'type' => 'textarea', 'familles' => array( 'groupe' ), 'section' => 'Le groupe', 'lignes' => 4 ),
		'historique'     => array( 'label' => 'Historique', 'type' => 'textarea', 'familles' => array( 'groupe' ), 'section' => 'Le groupe', 'lignes' => 4 ),

		// Médias (tous sauf membre individuel).
		'galerie'        => array( 'label' => 'Galerie photos', 'type' => 'gallery', 'familles' => $sauf_indiv, 'section' => 'Photos et vidéos' ),
		'videos'         => array( 'label' => 'Vidéos (liens YouTube, Vimeo…)', 'type' => 'textarea', 'familles' => $sauf_indiv, 'section' => 'Photos et vidéos', 'aide' => 'Un lien par ligne.', 'lignes' => 3 ),
	);
}

/** Champs applicables à un type d'adhérent donné. */
function cioff_fields_for_type( $type_slug ) {
	$type = cioff_type( $type_slug );
	if ( ! $type ) {
		return array();
	}
	return array_filter( cioff_fields(), fn( $f ) => in_array( $type['famille'], $f['familles'], true ) );
}

/** Valeurs des champs d'une fiche. */
function cioff_get_field_values( $post_id ) {
	$values = array();
	foreach ( cioff_fields() as $key => $field ) {
		$value = get_post_meta( $post_id, '_cioff_' . $key, true );
		if ( 'gallery' === $field['type'] ) {
			$value = array_filter( array_map( 'absint', (array) $value ) );
		}
		$values[ $key ] = $value;
	}
	return $values;
}

/** Nettoie une valeur selon le type du champ. */
function cioff_sanitize_field( $field, $value ) {
	switch ( $field['type'] ) {
		case 'email':
			return sanitize_email( $value );
		case 'url':
			return esc_url_raw( trim( (string) $value ) );
		case 'textarea':
			return sanitize_textarea_field( $value );
		case 'image':
			return absint( $value );
		case 'gallery':
			$ids = is_array( $value ) ? $value : explode( ',', (string) $value );
			return array_values( array_filter( array_map( 'absint', $ids ) ) );
		case 'departement':
			$value = strtoupper( sanitize_text_field( $value ) );
			return array_key_exists( $value, cioff_departements() ) ? $value : '';
		case 'select':
			return array_key_exists( $value, $field['options'] ) ? $value : '';
		default:
			return sanitize_text_field( $value );
	}
}

/**
 * Enregistre un ensemble de valeurs (déjà nettoyées) sur une fiche,
 * puis met à jour la position sur la carte si la localisation a changé.
 */
function cioff_save_field_values( $post_id, array $values ) {
	foreach ( cioff_fields() as $key => $field ) {
		if ( ! array_key_exists( $key, $values ) ) {
			continue;
		}
		$value = $values[ $key ];
		if ( '' === $value || array() === $value || 0 === $value ) {
			delete_post_meta( $post_id, '_cioff_' . $key );
		} else {
			update_post_meta( $post_id, '_cioff_' . $key, wp_slash( $value ) ); // update_post_meta() retire les antislashs.
		}
	}
	cioff_maybe_geocode( $post_id );
}

/** Convertit un texte saisi dans un champ simple en paragraphes de l'éditeur de blocs. */
function cioff_text_to_blocks( $text ) {
	$paragraphs = preg_split( '/\n\s*\n/', trim( str_replace( "\r", '', (string) $text ) ) );
	$out        = '';
	foreach ( array_filter( $paragraphs ) as $p ) {
		$out .= "<!-- wp:paragraph -->\n<p>" . nl2br( esc_html( trim( $p ) ), false ) . "</p>\n<!-- /wp:paragraph -->\n\n";
	}
	return $out;
}

/** Inverse de cioff_text_to_blocks() pour pré-remplir le formulaire « Mon espace ». */
function cioff_blocks_to_text( $content ) {
	$content = preg_replace( '/<!--.*?-->/s', '', (string) $content );
	$content = preg_replace( '#<br\s*/?>#i', "\n", $content );
	$content = preg_replace( '#</p>\s*#i', "\n\n", $content );
	return trim( html_entity_decode( wp_strip_all_tags( $content ), ENT_QUOTES, 'UTF-8' ) );
}

/*
 * Géolocalisation automatique à partir de l'adresse / ville / département (OpenStreetMap Nominatim).
 * Un membre individuel est placé au centre de son département (confidentialité).
 * L'administrateur peut toujours corriger la position à la main.
 */
function cioff_maybe_geocode( $post_id, $force = false ) {
	if ( get_post_meta( $post_id, '_cioff_position_manuelle', true ) ) {
		return;
	}
	$type  = cioff_get_adherent_type( $post_id );
	$dept  = get_post_meta( $post_id, '_cioff_departement', true );
	$query = '';
	if ( 'membre-individuel' === $type ) {
		$query = $dept ? cioff_departement_nom( $dept ) . ', France' : '';
	} else {
		$parts = array_filter(
			array(
				get_post_meta( $post_id, '_cioff_adresse', true ),
				get_post_meta( $post_id, '_cioff_ville', true ),
				cioff_departement_nom( $dept ),
				'France',
			)
		);
		$query = count( $parts ) > 1 ? implode( ', ', $parts ) : '';
	}
	if ( ! $query ) {
		return;
	}
	$hash = md5( $query );
	if ( ! $force && get_post_meta( $post_id, '_cioff_geo_hash', true ) === $hash ) {
		return;
	}

	$response = wp_remote_get(
		add_query_arg(
			array(
				'format' => 'json',
				'limit'  => 1,
				'q'      => $query,
			),
			'https://nominatim.openstreetmap.org/search'
		),
		array(
			'timeout' => 8,
			'headers' => array(
				'User-Agent'      => 'CIOFF-France-Site/' . CIOFF_VERSION . ' (' . home_url() . ')',
				'Accept-Language' => 'fr',
			),
		)
	);
	if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
		return;
	}
	$data = json_decode( wp_remote_retrieve_body( $response ), true );
	if ( empty( $data[0]['lat'] ) ) {
		return;
	}
	$lat = (float) $data[0]['lat'];
	$lng = (float) $data[0]['lon'];
	if ( 'membre-individuel' === $type ) {
		// Léger décalage stable pour que plusieurs membres d'un même département restent cliquables.
		$lat += ( ( $post_id * 37 ) % 100 - 50 ) / 1000;
		$lng += ( ( $post_id * 53 ) % 100 - 50 ) / 1000;
	}
	update_post_meta( $post_id, '_cioff_lat', round( $lat, 6 ) );
	update_post_meta( $post_id, '_cioff_lng', round( $lng, 6 ) );
	update_post_meta( $post_id, '_cioff_geo_hash', $hash );
}
