<?php
/**
 * Annuaire des adhérents : carte interactive unique + filtres + liste (§3.4.2 et §3.4.3).
 */

defined( 'ABSPATH' ) || exit;

/** Fond de carte. Modifiable par filtre si besoin (ex. autre fournisseur). */
function cioff_map_tiles() {
	return apply_filters(
		'cioff_map_tiles',
		array(
			'url'         => 'https://tile.openstreetmap.org/{z}/{x}/{y}.png',
			'attribution' => '&copy; <a href="https://www.openstreetmap.org/copyright">Contributeurs OpenStreetMap</a>',
		)
	);
}

function cioff_enqueue_leaflet() {
	wp_enqueue_style( 'leaflet', 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css', array(), '1.9.4' );
	wp_enqueue_style( 'leaflet-markercluster', 'https://cdnjs.cloudflare.com/ajax/libs/leaflet.markercluster/1.5.3/MarkerCluster.min.css', array( 'leaflet' ), '1.5.3' );
	wp_enqueue_script( 'leaflet', 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js', array(), '1.9.4', true );
	wp_enqueue_script( 'leaflet-markercluster', 'https://cdnjs.cloudflare.com/ajax/libs/leaflet.markercluster/1.5.3/leaflet.markercluster.min.js', array( 'leaflet' ), '1.5.3', true );
}

/** Données publiques de tous les adhérents publiés (mises en cache). */
function cioff_annuaire_data() {
	$data = get_transient( 'cioff_annuaire_data' );
	if ( false !== $data ) {
		return $data;
	}
	$data  = array();
	$posts = get_posts(
		array(
			'post_type'      => 'cioff_adherent',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
		)
	);
	foreach ( $posts as $p ) {
		$type = cioff_get_adherent_type( $p->ID );
		if ( ! $type ) {
			continue;
		}
		$dept  = get_post_meta( $p->ID, '_cioff_departement', true );
		$lat   = get_post_meta( $p->ID, '_cioff_lat', true );
		$lng   = get_post_meta( $p->ID, '_cioff_lng', true );
		$texte = wp_strip_all_tags( strip_shortcodes( $p->post_content ) );
		$data[] = array(
			'id'     => $p->ID,
			'nom'    => html_entity_decode( get_the_title( $p ), ENT_QUOTES, 'UTF-8' ),
			'type'   => $type,
			'ville'  => 'membre-individuel' === $type ? '' : (string) get_post_meta( $p->ID, '_cioff_ville', true ),
			'dept'   => $dept,
			'deptNom' => cioff_departement_nom( $dept ),
			'region' => cioff_departement_region( $dept ),
			'lat'    => $lat ? (float) $lat : null,
			'lng'    => $lng ? (float) $lng : null,
			'url'    => get_permalink( $p ),
			'image'  => get_the_post_thumbnail_url( $p, 'medium' ) ?: '',
			'extrait' => wp_trim_words( html_entity_decode( $texte, ENT_QUOTES, 'UTF-8' ), 22, '…' ),
		);
	}
	set_transient( 'cioff_annuaire_data', $data, DAY_IN_SECONDS );
	return $data;
}

// Toute modification de fiche vide le cache : la carte est à jour immédiatement.
foreach ( array( 'save_post_cioff_adherent', 'deleted_post', 'trashed_post', 'untrashed_post' ) as $cioff_hook ) {
	add_action( $cioff_hook, fn() => delete_transient( 'cioff_annuaire_data' ) );
}
add_action( 'updated_post_meta', function ( $meta_id, $post_id ) {
	if ( 'cioff_adherent' === get_post_type( $post_id ) ) {
		delete_transient( 'cioff_annuaire_data' );
	}
}, 10, 2 );
add_action( 'set_object_terms', fn() => delete_transient( 'cioff_annuaire_data' ) );

/**
 * Rendu du bloc « Annuaire des adhérents ».
 *
 * @param array $atts types (liste de slugs séparés par des virgules, vide = tous), hauteur (px).
 */
function cioff_render_annuaire( $atts = array() ) {
	$atts    = wp_parse_args( $atts, array( 'types' => '', 'hauteur' => 520, 'liste' => true ) );
	$types   = cioff_types();
	$visible = array_filter( array_map( 'trim', explode( ',', (string) $atts['types'] ) ) );
	$visible = array_values( array_intersect( $visible, array_keys( $types ) ) ) ?: array_keys( $types );

	cioff_enqueue_front_css();
	cioff_enqueue_leaflet();
	wp_enqueue_script( 'cioff-annuaire', CIOFF_URL . 'assets/js/annuaire.js', array( 'leaflet', 'leaflet-markercluster' ), CIOFF_VERSION, true );

	$js_types = array();
	foreach ( $types as $slug => $t ) {
		$js_types[ $slug ] = array(
			'label'   => $t['label'],
			'pluriel' => $t['pluriel'],
			'couleur' => $t['couleur'],
			'icone'   => cioff_icon_svg( $t['icone'] ),
		);
	}
	$config = array(
		'types'    => $js_types,
		'visibles' => $visible,
		'adherents' => array_values( array_filter( cioff_annuaire_data(), fn( $a ) => in_array( $a['type'], $visible, true ) ) ),
		'tiles'    => cioff_map_tiles(),
	);

	static $instance = 0;
	$instance++;
	$id = 'cioff-annuaire-' . $instance;

	ob_start();
	?>
	<div class="cioff-annuaire" id="<?php echo esc_attr( $id ); ?>" data-config="<?php echo esc_attr( wp_json_encode( $config ) ); ?>">
		<div class="cioff-annuaire__outils">
			<div class="cioff-annuaire__recherche">
				<label for="<?php echo esc_attr( $id ); ?>-q">Rechercher</label>
				<input type="search" id="<?php echo esc_attr( $id ); ?>-q" placeholder="Nom, ville, département ou région" data-q autocomplete="off">
			</div>
			<fieldset class="cioff-annuaire__filtres">
				<legend>Afficher</legend>
				<?php foreach ( $visible as $slug ) : ?>
					<label class="cioff-filtre" style="--cioff-type:<?php echo esc_attr( $types[ $slug ]['couleur'] ); ?>">
						<input type="checkbox" value="<?php echo esc_attr( $slug ); ?>" checked data-filtre>
						<span class="cioff-filtre__pastille"><?php echo cioff_icon_svg( $types[ $slug ]['icone'] ); // phpcs:ignore ?></span>
						<span><?php echo esc_html( $types[ $slug ]['pluriel'] ); ?> <span class="cioff-filtre__nb" data-nb="<?php echo esc_attr( $slug ); ?>"></span></span>
					</label>
				<?php endforeach; ?>
			</fieldset>
		</div>
		<div class="cioff-annuaire__carte" style="height:<?php echo (int) $atts['hauteur']; ?>px" data-carte role="region" aria-label="Carte des adhérents"></div>
		<?php if ( $atts['liste'] ) : ?>
			<div class="cioff-annuaire__liste-entete">
				<p class="cioff-annuaire__resultat" data-resultat aria-live="polite"></p>
				<label>Trier par
					<select data-tri>
						<option value="nom">Nom</option>
						<option value="type">Type</option>
						<option value="region">Région</option>
					</select>
				</label>
			</div>
			<ul class="cioff-annuaire__liste" data-liste></ul>
			<p class="cioff-annuaire__plus"><button type="button" class="wp-element-button" data-plus hidden>Afficher plus</button></p>
		<?php endif; ?>
		<noscript><p>Activez JavaScript pour afficher la carte des adhérents.</p></noscript>
	</div>
	<?php
	return ob_get_clean();
}

/** Petite carte à un seul point (fiche adhérent). */
function cioff_render_mini_map( $post_id ) {
	$lat = get_post_meta( $post_id, '_cioff_lat', true );
	$lng = get_post_meta( $post_id, '_cioff_lng', true );
	if ( ! $lat || ! $lng ) {
		return '';
	}
	cioff_enqueue_leaflet();
	wp_enqueue_script( 'cioff-annuaire', CIOFF_URL . 'assets/js/annuaire.js', array( 'leaflet', 'leaflet-markercluster' ), CIOFF_VERSION, true );
	$type   = cioff_type( cioff_get_adherent_type( $post_id ) );
	$config = array(
		'lat'     => (float) $lat,
		'lng'     => (float) $lng,
		'zoom'    => 'Membre individuel' === ( $type['label'] ?? '' ) ? 8 : 11,
		'couleur' => $type['couleur'] ?? '#1f3f7a',
		'icone'   => cioff_icon_svg( $type['icone'] ?? 'personne' ),
		'tiles'   => cioff_map_tiles(),
	);
	return '<div class="cioff-minicarte" data-minicarte="' . esc_attr( wp_json_encode( $config ) ) . '" role="img" aria-label="Localisation sur la carte"></div>';
}
