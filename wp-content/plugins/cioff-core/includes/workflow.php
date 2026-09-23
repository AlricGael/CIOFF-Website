<?php
/**
 * Circuit de validation (§3.4.6 et §5.2).
 *
 * Une page unique « À valider » regroupe tout ce que les adhérents ont envoyé :
 * nouvelles fiches, modifications de fiches publiées et événements proposés.
 * L'Administrateur CIOFF publie en un clic ou renvoie pour correction avec un commentaire ;
 * l'adhérent est prévenu par e-mail à chaque étape.
 */

defined( 'ABSPATH' ) || exit;

/** Nombre total d'éléments en attente. */
function cioff_pending_count() {
	$count  = (int) wp_count_posts( 'cioff_adherent' )->pending;
	$count += (int) wp_count_posts( 'cioff_evenement' )->pending;
	$count += count( cioff_get_propositions() );
	return $count;
}

/** Fiches publiées qui ont une modification proposée non renvoyée pour correction. */
function cioff_get_propositions() {
	$posts = get_posts(
		array(
			'post_type'      => 'cioff_adherent',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'meta_key'       => '_cioff_proposition', // phpcs:ignore WordPress.DB.SlowDBQuery
		)
	);
	return array_filter( $posts, fn( $p ) => ! get_post_meta( $p->ID, '_cioff_retour', true ) );
}

/* ---------------------------------------------------------------------------
 * Notifications
 * ------------------------------------------------------------------------- */

function cioff_notify_validators_fiche( $post_id, $is_modif ) {
	$post = get_post( $post_id );
	$type = cioff_type( cioff_get_adherent_type( $post_id ) );
	$user = get_userdata( get_current_user_id() );
	cioff_mail(
		cioff_validation_emails(),
		( $is_modif ? 'Modification de fiche à valider : ' : 'Nouvelle fiche à valider : ' ) . $post->post_title,
		sprintf(
			"%s\n\n<strong>%s</strong> (%s), envoyé par %s.\n\n<a href=\"%s\">Ouvrir la page « À valider »</a>",
			$is_modif ? 'Un adhérent a proposé des modifications de sa fiche.' : 'Un adhérent a envoyé une nouvelle fiche.',
			esc_html( $post->post_title ),
			esc_html( $type['label'] ?? '' ),
			esc_html( $user ? $user->display_name . ' <' . $user->user_email . '>' : '' ),
			esc_url( admin_url( 'admin.php?page=cioff-validations' ) )
		)
	);
}

function cioff_notify_author( $post_id, $subject, $message ) {
	$post   = get_post( $post_id );
	$author = $post ? get_userdata( $post->post_author ) : null;
	if ( ! $author || user_can( $author, 'cioff_valider' ) ) {
		return;
	}
	cioff_mail( $author->user_email, $subject, $message );
}

// Publication depuis l'éditeur ou depuis la page « À valider » : on prévient l'adhérent.
add_action( 'transition_post_status', function ( $new, $old, $post ) {
	if ( 'publish' !== $new || 'publish' === $old || ! in_array( $post->post_type, array( 'cioff_adherent', 'cioff_evenement' ), true ) ) {
		return;
	}
	delete_post_meta( $post->ID, '_cioff_retour' );
	$what = 'cioff_adherent' === $post->post_type ? 'Votre fiche' : 'Votre événement';
	cioff_notify_author(
		$post->ID,
		$what . ' est en ligne',
		sprintf( "Bonne nouvelle : %s « %s » a été validé(e) et publié(e) sur le site du CIOFF France.\n\n<a href=\"%s\">Voir en ligne</a>", lcfirst( $what ), esc_html( $post->post_title ), esc_url( get_permalink( $post ) ) )
	);
}, 10, 3 );

/* ---------------------------------------------------------------------------
 * Page « À valider »
 * ------------------------------------------------------------------------- */

add_action( 'admin_menu', function () {
	$count = cioff_pending_count();
	$badge = $count ? ' <span class="awaiting-mod"><span class="pending-count">' . $count . '</span></span>' : '';
	add_menu_page( 'À valider', 'À valider' . $badge, 'cioff_valider', 'cioff-validations', 'cioff_render_validations_page', 'dashicons-yes-alt', 4 );
} );

function cioff_render_validations_page() {
	if ( ! current_user_can( 'cioff_valider' ) ) {
		return;
	}
	$fiches      = get_posts( array( 'post_type' => 'cioff_adherent', 'post_status' => 'pending', 'posts_per_page' => -1 ) );
	$evenements  = get_posts( array( 'post_type' => 'cioff_evenement', 'post_status' => 'pending', 'posts_per_page' => -1 ) );
	$propositions = cioff_get_propositions();
	$done        = sanitize_key( $_GET['cioff_done'] ?? '' ); // phpcs:ignore WordPress.Security.NonceVerification
	$done_msg    = array(
		'publie'  => 'Publié. L\'adhérent a été prévenu par e-mail.',
		'renvoye' => 'Renvoyé pour correction. L\'adhérent a reçu votre commentaire par e-mail.',
	);
	?>
	<div class="wrap cioff-validations">
		<h1>À valider</h1>
		<p>Tout ce que les adhérents ont envoyé depuis le site apparaît ici. Rien n'est publié sans votre accord.</p>
		<?php if ( isset( $done_msg[ $done ] ) ) : ?>
			<div class="notice notice-success is-dismissible"><p><?php echo esc_html( $done_msg[ $done ] ); ?></p></div>
		<?php endif; ?>

		<?php if ( ! $fiches && ! $propositions && ! $evenements ) : ?>
			<div class="notice notice-info inline"><p>🎉 Rien à valider pour le moment.</p></div>
		<?php endif; ?>

		<?php if ( $fiches ) : ?>
			<h2>Nouvelles fiches (<?php echo count( $fiches ); ?>)</h2>
			<?php foreach ( $fiches as $p ) : ?>
				<div class="card" style="max-width:none">
					<h3 style="margin-top:0"><?php echo esc_html( $p->post_title ); ?> <?php echo cioff_type_badge( cioff_get_adherent_type( $p->ID ) ); // phpcs:ignore ?></h3>
					<p><?php echo esc_html( cioff_author_line( $p ) ); ?></p>
					<?php cioff_render_fiche_summary( $p->ID ); ?>
					<p><a href="<?php echo esc_url( get_edit_post_link( $p->ID ) ); ?>">Voir et modifier la fiche complète</a> · <a href="<?php echo esc_url( get_preview_post_link( $p ) ); ?>" target="_blank">Aperçu</a></p>
					<?php cioff_render_decision_form( $p->ID, 'fiche' ); ?>
				</div>
			<?php endforeach; ?>
		<?php endif; ?>

		<?php if ( $propositions ) : ?>
			<h2>Modifications de fiches publiées (<?php echo count( $propositions ); ?>)</h2>
			<?php foreach ( $propositions as $p ) : ?>
				<?php $prop = get_post_meta( $p->ID, '_cioff_proposition', true ); ?>
				<div class="card" style="max-width:none">
					<h3 style="margin-top:0"><?php echo esc_html( $p->post_title ); ?> <?php echo cioff_type_badge( cioff_get_adherent_type( $p->ID ) ); // phpcs:ignore ?></h3>
					<p>Proposée le <?php echo esc_html( mysql2date( 'j F Y à H:i', $prop['date'] ) ); ?>. Seuls les champs modifiés sont affichés ; la version actuelle reste en ligne en attendant.</p>
					<?php cioff_render_proposition_diff( $p->ID, $prop ); ?>
					<?php cioff_render_decision_form( $p->ID, 'proposition' ); ?>
				</div>
			<?php endforeach; ?>
		<?php endif; ?>

		<?php if ( $evenements ) : ?>
			<h2>Événements proposés (<?php echo count( $evenements ); ?>)</h2>
			<?php foreach ( $evenements as $p ) : ?>
				<div class="card" style="max-width:none">
					<h3 style="margin-top:0"><?php echo esc_html( $p->post_title ); ?></h3>
					<p><strong><?php echo esc_html( cioff_event_date_label( $p->ID ) ); ?></strong> — <?php echo esc_html( get_post_meta( $p->ID, '_cioff_lieu', true ) ); ?></p>
					<p><?php echo esc_html( cioff_author_line( $p ) ); ?></p>
					<div style="max-width:70ch"><?php echo wp_kses_post( wpautop( $p->post_content ) ); ?></div>
					<p><a href="<?php echo esc_url( get_edit_post_link( $p->ID ) ); ?>">Modifier avant de publier</a></p>
					<?php cioff_render_decision_form( $p->ID, 'evenement' ); ?>
				</div>
			<?php endforeach; ?>
		<?php endif; ?>
	</div>
	<?php
}

function cioff_author_line( $post ) {
	$author = get_userdata( $post->post_author );
	return sprintf(
		'Envoyé par %s le %s',
		$author ? $author->display_name . ' (' . $author->user_email . ')' : 'inconnu',
		get_the_modified_date( 'j F Y', $post )
	);
}

function cioff_render_decision_form( $post_id, $kind ) {
	?>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="cioff-decision">
		<input type="hidden" name="action" value="cioff_decision">
		<input type="hidden" name="post_id" value="<?php echo (int) $post_id; ?>">
		<input type="hidden" name="kind" value="<?php echo esc_attr( $kind ); ?>">
		<?php wp_nonce_field( 'cioff_decision_' . $post_id ); ?>
		<p>
			<label for="cioff-retour-<?php echo (int) $post_id; ?>">Commentaire pour l'adhérent (obligatoire en cas de renvoi) :</label><br>
			<textarea id="cioff-retour-<?php echo (int) $post_id; ?>" name="retour" rows="2" class="large-text" placeholder="Ex. : merci d'ajouter une photo et les dates 2028."></textarea>
		</p>
		<p>
			<button type="submit" name="decision" value="publier" class="button button-primary">✔ Valider et publier</button>
			<button type="submit" name="decision" value="renvoyer" class="button">↩ Renvoyer pour correction</button>
		</p>
	</form>
	<?php
}

/** Résumé des champs remplis d'une fiche (pour la validation). */
function cioff_render_fiche_summary( $post_id ) {
	$values = cioff_get_field_values( $post_id );
	$fields = cioff_fields_for_type( cioff_get_adherent_type( $post_id ) );
	echo '<table class="widefat striped" style="max-width:900px"><tbody>';
	$post = get_post( $post_id );
	printf( '<tr><th style="width:220px">Présentation</th><td>%s</td></tr>', wp_kses_post( wp_trim_words( $post->post_content, 60 ) ) );
	foreach ( $fields as $key => $field ) {
		$display = cioff_admin_value( $field, $values[ $key ] ?? '' );
		if ( '' !== $display ) {
			printf( '<tr><th>%s</th><td>%s</td></tr>', esc_html( $field['label'] ), $display ); // phpcs:ignore WordPress.Security.EscapeOutput
		}
	}
	echo '</tbody></table>';
}

/** Tableau « avant / après » d'une modification proposée. */
function cioff_render_proposition_diff( $post_id, $prop ) {
	$post    = get_post( $post_id );
	$current = cioff_get_field_values( $post_id );
	$rows    = array();

	if ( $prop['titre'] !== $post->post_title ) {
		$rows[] = array( 'Nom', esc_html( $post->post_title ), esc_html( $prop['titre'] ) );
	}
	$old_desc = cioff_blocks_to_text( $post->post_content );
	if ( trim( $prop['description'] ) !== $old_desc ) {
		$rows[] = array( 'Présentation', nl2br( esc_html( $old_desc ) ), nl2br( esc_html( $prop['description'] ) ) );
	}
	if ( (int) $prop['photo'] !== (int) get_post_thumbnail_id( $post_id ) ) {
		$rows[] = array( 'Photo principale', wp_get_attachment_image( get_post_thumbnail_id( $post_id ), 'thumbnail' ), wp_get_attachment_image( $prop['photo'], 'thumbnail' ) );
	}
	foreach ( cioff_fields() as $key => $field ) {
		if ( ! array_key_exists( $key, $prop['champs'] ) ) {
			continue;
		}
		$old = $current[ $key ] ?? '';
		$new = $prop['champs'][ $key ];
		if ( $old != $new ) { // phpcs:ignore Universal.Operators.StrictComparisons -- '' et 0 / [] équivalents.
			$rows[] = array( $field['label'], cioff_admin_value( $field, $old ), cioff_admin_value( $field, $new ) );
		}
	}

	if ( ! $rows ) {
		echo '<p><em>Aucun changement par rapport à la version en ligne.</em></p>';
		return;
	}
	echo '<table class="widefat striped" style="max-width:1100px"><thead><tr><th style="width:200px">Champ</th><th>Version en ligne</th><th>Modification proposée</th></tr></thead><tbody>';
	foreach ( $rows as $r ) {
		printf( '<tr><th>%1$s</th><td>%2$s</td><td style="background:#f0f7ee">%3$s</td></tr>', esc_html( $r[0] ), $r[1] ?: '<em>(vide)</em>', $r[2] ?: '<em>(vide)</em>' ); // phpcs:ignore WordPress.Security.EscapeOutput
	}
	echo '</tbody></table>';
}

/** Valeur lisible (HTML échappé) d'un champ pour l'administration. */
function cioff_admin_value( $field, $value ) {
	if ( '' === $value || array() === $value || 0 === $value || null === $value ) {
		return '';
	}
	switch ( $field['type'] ) {
		case 'image':
			return wp_get_attachment_image( $value, 'thumbnail' );
		case 'gallery':
			return implode( ' ', array_map( fn( $id ) => wp_get_attachment_image( $id, array( 60, 60 ) ), (array) $value ) );
		case 'departement':
			return esc_html( cioff_departement_label( $value ) );
		case 'select':
			return esc_html( $field['options'][ $value ] ?? $value );
		case 'url':
			return '<a href="' . esc_url( $value ) . '" target="_blank" rel="noopener">' . esc_html( $value ) . '</a>';
		default:
			return nl2br( esc_html( $value ) );
	}
}

/* ---------------------------------------------------------------------------
 * Traitement des décisions
 * ------------------------------------------------------------------------- */

add_action( 'admin_post_cioff_decision', function () {
	$post_id = absint( $_POST['post_id'] ?? 0 );
	check_admin_referer( 'cioff_decision_' . $post_id );
	if ( ! current_user_can( 'cioff_valider' ) || ! current_user_can( 'edit_post', $post_id ) ) {
		wp_die( 'Action non autorisée.' );
	}

	$kind     = sanitize_key( $_POST['kind'] ?? '' );
	$decision = sanitize_key( $_POST['decision'] ?? '' );
	$retour   = sanitize_textarea_field( wp_unslash( $_POST['retour'] ?? '' ) );
	$post     = get_post( $post_id );
	$back     = admin_url( 'admin.php?page=cioff-validations' );

	if ( 'renvoyer' === $decision ) {
		if ( '' === $retour ) {
			wp_die( 'Merci d\'écrire un commentaire expliquant les corrections attendues. <a href="javascript:history.back()">Retour</a>' );
		}
		update_post_meta( $post_id, '_cioff_retour', $retour );
		if ( 'proposition' !== $kind ) {
			wp_update_post( array( 'ID' => $post_id, 'post_status' => 'draft' ) );
		}
		$what = 'cioff_evenement' === $post->post_type ? 'votre événement' : 'votre fiche';
		cioff_notify_author(
			$post_id,
			'Corrections demandées : ' . $post->post_title,
			sprintf(
				"Le CIOFF France a relu %s « %s » et vous demande quelques corrections avant publication :\n\n<em>%s</em>\n\n<a href=\"%s\">Faire les corrections</a>",
				$what,
				esc_html( $post->post_title ),
				nl2br( esc_html( $retour ) ),
				esc_url( cioff_page_url( 'cioff_evenement' === $post->post_type ? 'proposer-evenement' : 'mon-espace' ) )
			)
		);
		wp_safe_redirect( add_query_arg( 'cioff_done', 'renvoye', $back ) );
		exit;
	}

	if ( 'publier' === $decision ) {
		if ( 'proposition' === $kind ) {
			cioff_apply_proposition( $post_id );
			cioff_notify_author(
				$post_id,
				'Modifications validées : ' . get_the_title( $post_id ),
				sprintf( "Vos modifications ont été validées et sont maintenant en ligne.\n\n<a href=\"%s\">Voir votre fiche</a>", esc_url( get_permalink( $post_id ) ) )
			);
		} else {
			// La notification part via transition_post_status.
			wp_update_post( array( 'ID' => $post_id, 'post_status' => 'publish' ) );
		}
		delete_post_meta( $post_id, '_cioff_retour' );
		wp_safe_redirect( add_query_arg( 'cioff_done', 'publie', $back ) );
		exit;
	}

	wp_safe_redirect( $back );
	exit;
} );

/** Applique une modification proposée à la fiche publiée. */
function cioff_apply_proposition( $post_id ) {
	$prop = get_post_meta( $post_id, '_cioff_proposition', true );
	if ( ! $prop ) {
		return;
	}
	wp_update_post(
		array(
			'ID'           => $post_id,
			'post_title'   => wp_slash( $prop['titre'] ),
			'post_content' => wp_slash( cioff_text_to_blocks( $prop['description'] ) ),
		)
	);
	if ( $prop['photo'] ) {
		set_post_thumbnail( $post_id, $prop['photo'] );
	} else {
		delete_post_thumbnail( $post_id );
	}
	cioff_save_field_values( $post_id, $prop['champs'] );
	delete_post_meta( $post_id, '_cioff_proposition' );
}

// Rappel dans l'éditeur d'une fiche qui a une modification en attente.
add_action( 'add_meta_boxes_cioff_adherent', function ( $post ) {
	if ( get_post_meta( $post->ID, '_cioff_proposition', true ) ) {
		add_meta_box(
			'cioff-proposition',
			'⚠ Modification proposée par l\'adhérent',
			function () {
				echo '<p>L\'adhérent a proposé des modifications de cette fiche. <a href="' . esc_url( admin_url( 'admin.php?page=cioff-validations' ) ) . '">Les examiner sur la page « À valider »</a>.</p>';
			},
			'cioff_adherent',
			'side',
			'high'
		);
	}
}, 5 );

// Encadré sur le tableau de bord.
add_action( 'wp_dashboard_setup', function () {
	if ( ! current_user_can( 'cioff_valider' ) ) {
		return;
	}
	wp_add_dashboard_widget( 'cioff_dashboard', 'CIOFF France', function () {
		$count = cioff_pending_count();
		echo '<p style="font-size:15px">' . ( $count
			? sprintf( '<strong>%d élément(s)</strong> en attente de validation. <a class="button button-primary" href="%s">Voir</a>', (int) $count, esc_url( admin_url( 'admin.php?page=cioff-validations' ) ) )
			: '✔ Rien à valider.' ) . '</p>';
		echo '<ul>';
		echo '<li><a href="' . esc_url( admin_url( 'edit.php?post_type=cioff_adherent' ) ) . '">Gérer l\'annuaire des adhérents</a></li>';
		echo '<li><a href="' . esc_url( admin_url( 'edit.php?post_type=cioff_evenement' ) ) . '">Gérer l\'agenda</a></li>';
		echo '<li><a href="' . esc_url( admin_url( 'user-new.php' ) ) . '">Créer un compte adhérent</a></li>';
		echo '<li><a href="' . esc_url( admin_url( 'admin.php?page=cioff-reglages' ) ) . '">Réglages CIOFF (thème annuel, contact…)</a></li>';
		echo '</ul>';
	} );
} );
