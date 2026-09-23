<?php
/**
 * Agenda (§3.1) : événements, réunions CIOFF France / CIOFF Jeunes, journées internationales.
 * Tout adhérent connecté peut proposer un événement ; il est publié après validation.
 */

defined( 'ABSPATH' ) || exit;

function cioff_event_fields() {
	return array(
		'date_debut' => 'Date de début',
		'heure'      => 'Heure',
		'date_fin'   => 'Date de fin (si plusieurs jours)',
		'lieu'       => 'Lieu',
		'lien'       => 'Lien (inscription, visioconférence, site…)',
	);
}

/* ----- Administration ----- */

add_action( 'add_meta_boxes_cioff_evenement', function () {
	add_meta_box( 'cioff-evt', "Date et lieu de l'événement", 'cioff_render_event_metabox', 'cioff_evenement', 'normal', 'high' );
} );

function cioff_render_event_metabox( $post ) {
	wp_nonce_field( 'cioff_save_evt', 'cioff_evt_nonce' );
	$m = array();
	foreach ( array_keys( cioff_event_fields() ) as $k ) {
		$m[ $k ] = get_post_meta( $post->ID, '_cioff_' . $k, true );
	}
	$une = get_post_meta( $post->ID, '_cioff_une', true );
	?>
	<table class="form-table" role="presentation">
		<tr><th><label for="cioff-date-debut">Date de début *</label></th><td><input type="date" id="cioff-date-debut" name="cioff_evt[date_debut]" value="<?php echo esc_attr( $m['date_debut'] ); ?>" required></td></tr>
		<tr><th><label for="cioff-heure">Heure</label></th><td><input type="time" id="cioff-heure" name="cioff_evt[heure]" value="<?php echo esc_attr( $m['heure'] ); ?>"></td></tr>
		<tr><th><label for="cioff-date-fin">Date de fin</label></th><td><input type="date" id="cioff-date-fin" name="cioff_evt[date_fin]" value="<?php echo esc_attr( $m['date_fin'] ); ?>"> <span class="description">Uniquement si l'événement dure plusieurs jours.</span></td></tr>
		<tr><th><label for="cioff-lieu">Lieu</label></th><td><input type="text" class="regular-text" id="cioff-lieu" name="cioff_evt[lieu]" value="<?php echo esc_attr( $m['lieu'] ); ?>" placeholder="Ville, salle… ou « Visioconférence »"></td></tr>
		<tr><th><label for="cioff-lien">Lien</label></th><td><input type="url" class="regular-text" id="cioff-lien" name="cioff_evt[lien]" value="<?php echo esc_attr( $m['lien'] ); ?>" placeholder="https://"></td></tr>
		<tr><th>Mise en avant</th><td><label><input type="checkbox" name="cioff_une" value="1" <?php checked( $une ); ?>> Afficher dans le bandeau « À la une » de la page d'accueil</label></td></tr>
	</table>
	<p class="description">Choisissez la catégorie (Réunion CIOFF France, Réunion CIOFF Jeunes…) dans le panneau de droite.</p>
	<?php
}

function cioff_sanitize_event( array $raw ) {
	$out = array();
	foreach ( array( 'date_debut', 'date_fin' ) as $k ) {
		$d         = sanitize_text_field( $raw[ $k ] ?? '' );
		$out[ $k ] = preg_match( '/^\d{4}-\d{2}-\d{2}$/', $d ) ? $d : '';
	}
	$h            = sanitize_text_field( $raw['heure'] ?? '' );
	$out['heure'] = preg_match( '/^\d{2}:\d{2}$/', $h ) ? $h : '';
	$out['lieu']  = sanitize_text_field( $raw['lieu'] ?? '' );
	$out['lien']  = esc_url_raw( $raw['lien'] ?? '' );
	if ( $out['date_fin'] && $out['date_fin'] < $out['date_debut'] ) {
		$out['date_fin'] = '';
	}
	return $out;
}

function cioff_save_event_meta( $post_id, array $values ) {
	foreach ( $values as $k => $v ) {
		if ( '' === $v ) {
			delete_post_meta( $post_id, '_cioff_' . $k );
		} else {
			update_post_meta( $post_id, '_cioff_' . $k, $v );
		}
	}
	// Date de tri : fin si présente (l'événement reste « à venir » jusqu'à sa fin).
	update_post_meta( $post_id, '_cioff_date_tri', $values['date_fin'] ?: $values['date_debut'] );
}

add_action( 'save_post_cioff_evenement', function ( $post_id ) {
	if ( ! isset( $_POST['cioff_evt_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['cioff_evt_nonce'] ), 'cioff_save_evt' ) ) {
		return;
	}
	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	cioff_save_event_meta( $post_id, cioff_sanitize_event( wp_unslash( $_POST['cioff_evt'] ?? array() ) ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	if ( ! empty( $_POST['cioff_une'] ) ) {
		update_post_meta( $post_id, '_cioff_une', 1 );
	} else {
		delete_post_meta( $post_id, '_cioff_une' );
	}
} );

add_filter( 'manage_cioff_evenement_posts_columns', function ( $cols ) {
	$cols = array_slice( $cols, 0, 2, true ) + array( 'cioff_quand' => 'Date', 'cioff_ou' => 'Lieu' ) + array_slice( $cols, 2, null, true );
	unset( $cols['date'] );
	return $cols;
} );

add_action( 'manage_cioff_evenement_posts_custom_column', function ( $col, $post_id ) {
	if ( 'cioff_quand' === $col ) {
		echo esc_html( cioff_event_date_label( $post_id ) );
	} elseif ( 'cioff_ou' === $col ) {
		echo esc_html( get_post_meta( $post_id, '_cioff_lieu', true ) );
	}
}, 10, 2 );

/* ----- Affichage ----- */

/** « Samedi 12 juillet 2027 à 14h00 » ou « Du 12 au 18 juillet 2027 ». */
function cioff_event_date_label( $post_id ) {
	$debut = get_post_meta( $post_id, '_cioff_date_debut', true );
	$fin   = get_post_meta( $post_id, '_cioff_date_fin', true );
	$heure = get_post_meta( $post_id, '_cioff_heure', true );
	if ( ! $debut ) {
		return '';
	}
	$d = strtotime( $debut );
	if ( $fin && $fin !== $debut ) {
		$f = strtotime( $fin );
		if ( wp_date( 'Y-m', $d, new DateTimeZone( 'UTC' ) ) === wp_date( 'Y-m', $f, new DateTimeZone( 'UTC' ) ) ) {
			return 'Du ' . wp_date( 'j', $d, new DateTimeZone( 'UTC' ) ) . ' au ' . wp_date( 'j F Y', $f, new DateTimeZone( 'UTC' ) );
		}
		return 'Du ' . wp_date( 'j F', $d, new DateTimeZone( 'UTC' ) ) . ' au ' . wp_date( 'j F Y', $f, new DateTimeZone( 'UTC' ) );
	}
	$label = ucfirst( wp_date( 'l j F Y', $d, new DateTimeZone( 'UTC' ) ) );
	if ( $heure ) {
		$label .= ' à ' . str_replace( ':', 'h', $heure );
	}
	return $label;
}

/**
 * Liste des prochains événements.
 *
 * @param array $atts nombre, categorie (slug(s) séparés par des virgules), une (1 = seulement « à la une »), style (liste|cartes).
 */
function cioff_render_agenda( $atts = array() ) {
	$atts = wp_parse_args( $atts, array( 'nombre' => 5, 'categorie' => '', 'une' => false, 'style' => 'liste', 'vide' => 'Aucun événement à venir pour le moment.' ) );
	cioff_enqueue_front_css();

	$args = array(
		'post_type'      => 'cioff_evenement',
		'post_status'    => 'publish',
		'posts_per_page' => max( 1, (int) $atts['nombre'] ),
		'meta_key'       => '_cioff_date_tri', // phpcs:ignore WordPress.DB.SlowDBQuery
		'orderby'        => 'meta_value',
		'order'          => 'ASC',
		'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery
			array(
				'key'     => '_cioff_date_tri',
				'value'   => current_time( 'Y-m-d' ),
				'compare' => '>=',
				'type'    => 'DATE',
			),
		),
	);
	if ( $atts['categorie'] ) {
		$args['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery
			array(
				'taxonomy' => 'cioff_evt_cat',
				'field'    => 'slug',
				'terms'    => array_map( 'trim', explode( ',', $atts['categorie'] ) ),
			),
		);
	}
	if ( $atts['une'] ) {
		$args['meta_query'][] = array( 'key' => '_cioff_une', 'value' => '1' );
	}
	$events = get_posts( $args );

	if ( ! $events ) {
		return $atts['vide'] ? '<p class="cioff-agenda__vide">' . esc_html( $atts['vide'] ) . '</p>' : '';
	}

	$html = '<ul class="cioff-agenda cioff-agenda--' . esc_attr( $atts['style'] ) . '">';
	foreach ( $events as $e ) {
		$debut = strtotime( get_post_meta( $e->ID, '_cioff_date_debut', true ) );
		$lieu  = get_post_meta( $e->ID, '_cioff_lieu', true );
		$cats  = get_the_terms( $e->ID, 'cioff_evt_cat' );
		$html .= '<li class="cioff-agenda__item">';
		$html .= '<div class="cioff-agenda__date" aria-hidden="true"><span>' . esc_html( wp_date( 'j', $debut, new DateTimeZone( 'UTC' ) ) ) . '</span>' . esc_html( wp_date( 'M', $debut, new DateTimeZone( 'UTC' ) ) ) . '</div>';
		$html .= '<div class="cioff-agenda__texte">';
		if ( $cats && ! is_wp_error( $cats ) ) {
			$html .= '<p class="cioff-agenda__cat">' . esc_html( $cats[0]->name ) . '</p>';
		}
		$html .= '<h3 class="cioff-agenda__titre"><a href="' . esc_url( get_permalink( $e ) ) . '">' . esc_html( get_the_title( $e ) ) . '</a></h3>';
		$html .= '<p class="cioff-agenda__quand">' . esc_html( cioff_event_date_label( $e->ID ) ) . ( $lieu ? ' — ' . esc_html( $lieu ) : '' ) . '</p>';
		$html .= '</div></li>';
	}
	return $html . '</ul>';
}

/** Détails d'un événement (bloc utilisé dans le modèle « Événement »). */
function cioff_render_event_details( $post_id ) {
	cioff_enqueue_front_css();
	$lieu = get_post_meta( $post_id, '_cioff_lieu', true );
	$lien = get_post_meta( $post_id, '_cioff_lien', true );
	$html = '<div class="cioff-evt-details"><p class="cioff-evt-details__quand">📅 ' . esc_html( cioff_event_date_label( $post_id ) ) . '</p>';
	if ( $lieu ) {
		$html .= '<p>📍 ' . esc_html( $lieu ) . '</p>';
	}
	if ( $lien ) {
		$html .= '<p><a class="wp-element-button" href="' . esc_url( $lien ) . '" target="_blank" rel="noopener">En savoir plus / s\'inscrire</a></p>';
	}
	return $html . '</div>';
}

/* ----- Proposition d'un événement par un adhérent ----- */

function cioff_render_proposer_evenement() {
	cioff_enqueue_front_css();
	if ( ! is_user_logged_in() ) {
		return cioff_login_box( 'Connectez-vous pour proposer un événement à l\'agenda.' );
	}
	$msg = sanitize_key( $_GET['cioff_msg'] ?? '' ); // phpcs:ignore WordPress.Security.NonceVerification
	ob_start();
	if ( 'evt_envoye' === $msg ) {
		echo '<div class="cioff-notice cioff-notice--success" role="status">Merci ! Votre événement a été envoyé. Il apparaîtra dans l\'agenda après validation par le CIOFF France.</div>';
	} elseif ( 'erreur' === $msg ) {
		echo '<div class="cioff-notice cioff-notice--error" role="alert">Merci de renseigner au moins le titre et la date de début.</div>';
	}
	?>
	<form class="cioff-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="cioff_proposer_evenement">
		<?php wp_nonce_field( 'cioff_proposer_evenement', 'cioff_nonce' ); ?>
		<div class="cioff-field"><label for="cioff-evt-titre">Titre de l'événement <span class="cioff-req">*</span></label><input type="text" id="cioff-evt-titre" name="titre" required></div>
		<div class="cioff-field-row">
			<div class="cioff-field"><label for="cioff-evt-debut">Date de début <span class="cioff-req">*</span></label><input type="date" id="cioff-evt-debut" name="cioff_evt[date_debut]" required></div>
			<div class="cioff-field"><label for="cioff-evt-heure">Heure</label><input type="time" id="cioff-evt-heure" name="cioff_evt[heure]"></div>
			<div class="cioff-field"><label for="cioff-evt-fin">Date de fin</label><input type="date" id="cioff-evt-fin" name="cioff_evt[date_fin]"></div>
		</div>
		<div class="cioff-field"><label for="cioff-evt-lieu">Lieu</label><input type="text" id="cioff-evt-lieu" name="cioff_evt[lieu]" placeholder="Ville, salle…"></div>
		<div class="cioff-field"><label for="cioff-evt-lien">Lien (facultatif)</label><input type="url" id="cioff-evt-lien" name="cioff_evt[lien]" placeholder="https://"></div>
		<div class="cioff-field"><label for="cioff-evt-desc">Description</label><textarea id="cioff-evt-desc" name="description" rows="6"></textarea></div>
		<p><button type="submit" class="wp-element-button cioff-button">Proposer l'événement</button></p>
	</form>
	<?php
	return ob_get_clean();
}

add_action( 'admin_post_cioff_proposer_evenement', function () {
	$back = remove_query_arg( 'cioff_msg', wp_get_referer() ?: home_url() );
	if ( ! isset( $_POST['cioff_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['cioff_nonce'] ), 'cioff_proposer_evenement' ) || ! cioff_is_member() ) {
		wp_safe_redirect( add_query_arg( 'cioff_msg', 'erreur', $back ) );
		exit;
	}
	$titre  = sanitize_text_field( wp_unslash( $_POST['titre'] ?? '' ) );
	$values = cioff_sanitize_event( wp_unslash( $_POST['cioff_evt'] ?? array() ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	if ( ! $titre || ! $values['date_debut'] ) {
		wp_safe_redirect( add_query_arg( 'cioff_msg', 'erreur', $back ) );
		exit;
	}
	$post_id = wp_insert_post(
		array(
			'post_type'    => 'cioff_evenement',
			'post_title'   => wp_slash( $titre ),
			'post_content' => wp_slash( cioff_text_to_blocks( sanitize_textarea_field( wp_unslash( $_POST['description'] ?? '' ) ) ) ),
			'post_status'  => 'pending',
			'post_author'  => get_current_user_id(),
		),
		true
	);
	if ( is_wp_error( $post_id ) ) {
		wp_safe_redirect( add_query_arg( 'cioff_msg', 'erreur', $back ) );
		exit;
	}
	cioff_save_event_meta( $post_id, $values );
	wp_set_object_terms( $post_id, 'evenement-adherent', 'cioff_evt_cat' );

	$user = wp_get_current_user();
	cioff_mail(
		cioff_validation_emails(),
		'Événement à valider : ' . $titre,
		sprintf(
			"%s a proposé un événement pour l'agenda :\n\n<strong>%s</strong> — %s\n\n<a href=\"%s\">Ouvrir la page « À valider »</a>",
			esc_html( $user->display_name ),
			esc_html( $titre ),
			esc_html( cioff_event_date_label( $post_id ) ),
			esc_url( admin_url( 'admin.php?page=cioff-validations' ) )
		)
	);
	wp_safe_redirect( add_query_arg( 'cioff_msg', 'evt_envoye', $back ) );
	exit;
} );
