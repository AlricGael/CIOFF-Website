<?php
/**
 * « Mon espace » : l'adhérent crée ou modifie sa fiche depuis le site, sans passer par l'administration.
 *
 * - Nouvelle fiche : créée « en attente de validation ».
 * - Fiche déjà publiée : les changements sont enregistrés comme « modification proposée » ;
 *   la version publiée reste en ligne jusqu'à la validation par un Administrateur CIOFF (§5.1, modèle hybride).
 */

defined( 'ABSPATH' ) || exit;

/** Rendu du bloc / shortcode « Ma fiche adhérent ». */
function cioff_render_ma_fiche() {
	cioff_enqueue_front_css();

	if ( ! is_user_logged_in() ) {
		return cioff_login_box( 'Connectez-vous pour gérer la fiche de votre structure.' );
	}

	$user_id = get_current_user_id();
	$type    = cioff_type_for_user( $user_id );
	$fiche   = cioff_user_fiche( $user_id );

	if ( ! $type && $fiche ) {
		$type = cioff_get_adherent_type( $fiche->ID );
	}
	if ( ! $type ) {
		if ( cioff_is_validator() ) {
			return '<div class="cioff-notice">Vous êtes Administrateur CIOFF : les fiches se gèrent dans <a href="' . esc_url( admin_url( 'edit.php?post_type=cioff_adherent' ) ) . '">l\'administration → Annuaire</a>.</div>';
		}
		return '<div class="cioff-notice">Votre compte n\'est pas encore associé à un type d\'adhérent. Contactez le CIOFF France.</div>';
	}

	wp_enqueue_script( 'cioff-fiche-form', CIOFF_URL . 'assets/js/fiche-form.js', array(), CIOFF_VERSION, true );

	$type_def = cioff_type( $type );
	$fields   = cioff_fields_for_type( $type );

	// Valeurs affichées : la proposition en attente si elle existe, sinon la fiche.
	$proposition = $fiche ? get_post_meta( $fiche->ID, '_cioff_proposition', true ) : null;
	if ( $proposition ) {
		$values      = $proposition['champs'];
		$title       = $proposition['titre'];
		$description = $proposition['description'];
	} elseif ( $fiche ) {
		$values      = cioff_get_field_values( $fiche->ID );
		$title       = $fiche->post_title;
		$description = cioff_blocks_to_text( $fiche->post_content );
	} else {
		$values      = array();
		$title       = '';
		$description = '';
	}

	ob_start();

	// Messages après envoi.
	$msg = sanitize_key( $_GET['cioff_msg'] ?? '' ); // phpcs:ignore WordPress.Security.NonceVerification
	$messages = array(
		'envoye'  => 'Merci ! Votre fiche a été envoyée. Elle sera publiée après validation par le CIOFF France. Vous recevrez un e-mail.',
		'modif'   => 'Merci ! Vos modifications ont été envoyées. La version actuelle reste en ligne jusqu\'à leur validation.',
		'erreur'  => 'Une erreur est survenue. Vérifiez les champs obligatoires et réessayez.',
		'fichier' => 'Certaines images n\'ont pas pu être envoyées (formats acceptés : JPG, PNG, WebP ; 8 Mo maximum). Le reste a bien été enregistré.',
	);
	if ( isset( $messages[ $msg ] ) ) {
		printf( '<div class="cioff-notice cioff-notice--%1$s" role="status">%2$s</div>', 'erreur' === $msg ? 'error' : 'success', esc_html( $messages[ $msg ] ) );
	}

	// Statut actuel de la fiche.
	$retour = $fiche ? get_post_meta( $fiche->ID, '_cioff_retour', true ) : '';
	echo '<div class="cioff-status">';
	echo '<p>Type de fiche : ' . cioff_type_badge( $type ) . '</p>'; // phpcs:ignore WordPress.Security.EscapeOutput
	if ( ! $fiche ) {
		echo '<p>Vous n\'avez pas encore de fiche. Remplissez le formulaire ci-dessous : elle apparaîtra sur la carte après validation.</p>';
	} elseif ( $retour ) {
		echo '<div class="cioff-notice cioff-notice--warning"><strong>Corrections demandées par le CIOFF France :</strong><br>' . nl2br( esc_html( $retour ) ) . '<br>Corrigez le formulaire ci-dessous puis envoyez-le à nouveau.</div>';
	} elseif ( $proposition || 'pending' === $fiche->post_status ) {
		echo '<p><strong>En attente de validation</strong> par le CIOFF France. Vous pouvez encore la modifier.</p>';
	}
	if ( $fiche && 'publish' === $fiche->post_status ) {
		echo '<p>' . ( $proposition ? 'La version actuelle reste en ligne en attendant. ' : '<strong>Votre fiche est en ligne.</strong> ' ) . '<a href="' . esc_url( get_permalink( $fiche ) ) . '">Voir ma fiche publique</a></p>';
	}
	echo '</div>';
	?>
	<form class="cioff-form" method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" data-cioff-type-form data-famille="<?php echo esc_attr( $type_def['famille'] ); ?>">
		<input type="hidden" name="action" value="cioff_ma_fiche">
		<?php wp_nonce_field( 'cioff_ma_fiche', 'cioff_nonce' ); ?>

		<fieldset class="cioff-section">
			<legend>Présentation</legend>
			<div class="cioff-field">
				<label for="cioff-titre"><?php echo 'membre-individuel' === $type ? 'Nom et prénom' : 'Nom de la structure'; ?> <span class="cioff-req">*</span></label>
				<input type="text" id="cioff-titre" name="cioff_titre" value="<?php echo esc_attr( $title ); ?>" required>
			</div>
			<div class="cioff-field">
				<label for="cioff-description"><?php echo 'membre-individuel' === $type ? 'Courte présentation' : 'Présentation'; ?></label>
				<textarea id="cioff-description" name="cioff_description" rows="<?php echo 'membre-individuel' === $type ? 3 : 8; ?>"><?php echo esc_textarea( $description ); ?></textarea>
				<p class="cioff-help">Laissez une ligne vide entre deux paragraphes.</p>
			</div>
			<?php if ( 'membre-individuel' !== $type ) : ?>
				<div class="cioff-field">
					<label for="cioff-photo">Photo principale</label>
					<?php
					$thumb = $proposition ? ( $proposition['photo'] ?? 0 ) : ( $fiche ? get_post_thumbnail_id( $fiche ) : 0 );
					if ( $thumb ) {
						echo '<div class="cioff-media__preview">' . wp_get_attachment_image( $thumb, 'medium' ) . '</div>';
					}
					?>
					<input type="file" id="cioff-photo" name="cioff_fichier_photo" accept="image/jpeg,image/png,image/webp">
					<p class="cioff-help">Format paysage conseillé (au moins 1200 px de large).</p>
				</div>
			<?php endif; ?>
		</fieldset>

		<?php cioff_render_field_sections( $fields, $values, 'front' ); ?>

		<?php if ( 'membre-individuel' === $type ) : ?>
			<p class="cioff-help">Pour votre confidentialité, seul votre département est affiché ; vous êtes placé(e) au centre du département sur la carte. Les coordonnées de contact sont facultatives.</p>
		<?php endif; ?>

		<p><button type="submit" class="wp-element-button cioff-button"><?php echo $fiche ? 'Envoyer mes modifications' : 'Envoyer ma fiche'; ?></button></p>
	</form>
	<?php
	return ob_get_clean();
}

/** Formulaire de connexion réutilisé (Mon espace, intranet). */
function cioff_login_box( $intro ) {
	cioff_enqueue_front_css();
	$html  = '<div class="cioff-login"><p>' . esc_html( $intro ) . '</p>';
	$html .= wp_login_form(
		array(
			'echo'           => false,
			'redirect'       => ( is_ssl() ? 'https://' : 'http://' ) . sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ?? '' ) ) . sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ?? '' ) ),
			'label_username' => 'Identifiant ou e-mail',
			'label_password' => 'Mot de passe',
			'label_remember' => 'Rester connecté',
			'label_log_in'   => 'Se connecter',
		)
	);
	$html .= '<p><a href="' . esc_url( wp_lostpassword_url() ) . '">Mot de passe oublié ?</a></p>';
	$html .= '<p class="cioff-help">Vous êtes adhérent et n\'avez pas d\'identifiant ? <a href="' . esc_url( cioff_page_url( 'contact' ) ) . '">Contactez le CIOFF France</a>.</p></div>';
	return $html;
}

/** Traitement de l'envoi du formulaire « Mon espace ». */
add_action( 'admin_post_cioff_ma_fiche', 'cioff_handle_ma_fiche' );

function cioff_handle_ma_fiche() {
	$back = wp_get_referer() ?: cioff_page_url( 'mon-espace' );
	$back = remove_query_arg( 'cioff_msg', $back );

	if ( ! isset( $_POST['cioff_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['cioff_nonce'] ), 'cioff_ma_fiche' ) || ! is_user_logged_in() ) {
		wp_safe_redirect( add_query_arg( 'cioff_msg', 'erreur', $back ) );
		exit;
	}

	$user_id = get_current_user_id();
	$fiche   = cioff_user_fiche( $user_id );
	$type    = cioff_type_for_user( $user_id ) ?: ( $fiche ? cioff_get_adherent_type( $fiche->ID ) : '' );
	$title   = sanitize_text_field( wp_unslash( $_POST['cioff_titre'] ?? '' ) );
	if ( ! $type || '' === $title ) {
		wp_safe_redirect( add_query_arg( 'cioff_msg', 'erreur', $back ) );
		exit;
	}

	$fields      = cioff_fields_for_type( $type );
	$raw         = wp_unslash( $_POST['cioff'] ?? array() ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	$description = sanitize_textarea_field( wp_unslash( $_POST['cioff_description'] ?? '' ) );
	$proposition = $fiche ? get_post_meta( $fiche->ID, '_cioff_proposition', true ) : null;
	$current     = $proposition ? $proposition['champs'] : ( $fiche ? cioff_get_field_values( $fiche->ID ) : array() );
	$upload_err  = false;

	$values = array();
	foreach ( $fields as $key => $field ) {
		if ( 'image' === $field['type'] ) {
			$value = absint( $current[ $key ] ?? 0 );
			if ( ! empty( $_POST['cioff_retirer'][ $key ] ) ) {
				$value = 0;
			}
			$new = cioff_handle_upload( 'cioff_fichier_' . $key, $fiche ? $fiche->ID : 0 );
			if ( is_wp_error( $new ) ) {
				$upload_err = true;
			} elseif ( $new ) {
				$value = $new;
			}
			$values[ $key ] = $value;
		} elseif ( 'gallery' === $field['type'] ) {
			$ids    = array_map( 'absint', (array) ( $current[ $key ] ?? array() ) );
			$remove = array_map( 'absint', (array) ( $_POST['cioff_retirer_galerie'] ?? array() ) );
			$ids    = array_values( array_diff( $ids, $remove ) );
			$new    = cioff_handle_multi_upload( 'cioff_fichier_' . $key, $fiche ? $fiche->ID : 0, $upload_err );
			$values[ $key ] = array_slice( array_merge( $ids, $new ), 0, 30 );
		} else {
			$values[ $key ] = cioff_sanitize_field( $field, $raw[ $key ] ?? '' );
		}
	}

	$photo = $proposition ? absint( $proposition['photo'] ?? 0 ) : ( $fiche ? (int) get_post_thumbnail_id( $fiche ) : 0 );
	$new   = cioff_handle_upload( 'cioff_fichier_photo', $fiche ? $fiche->ID : 0 );
	if ( is_wp_error( $new ) ) {
		$upload_err = true;
	} elseif ( $new ) {
		$photo = $new;
	}

	if ( $fiche && 'publish' === $fiche->post_status ) {
		// Fiche en ligne : on stocke la proposition, la version publique ne change pas.
		update_post_meta(
			$fiche->ID,
			'_cioff_proposition',
			wp_slash( array(
				'titre'       => $title,
				'description' => $description,
				'photo'       => $photo,
				'champs'      => $values,
				'date'        => current_time( 'mysql' ),
				'auteur'      => $user_id,
			) )
		);
		delete_post_meta( $fiche->ID, '_cioff_retour' );
		cioff_notify_validators_fiche( $fiche->ID, true );
		$msg = 'modif';
	} else {
		// Nouvelle fiche ou fiche jamais publiée : on écrit directement, en attente de validation.
		$postarr = array(
			'post_type'    => 'cioff_adherent',
			'post_title'   => wp_slash( $title ),
			'post_content' => wp_slash( cioff_text_to_blocks( $description ) ),
			'post_status'  => 'pending',
			'post_author'  => $user_id,
		);
		if ( $fiche ) {
			$postarr['ID'] = $fiche->ID;
			$post_id       = wp_update_post( $postarr, true );
		} else {
			$post_id = wp_insert_post( $postarr, true );
		}
		if ( is_wp_error( $post_id ) ) {
			wp_safe_redirect( add_query_arg( 'cioff_msg', 'erreur', $back ) );
			exit;
		}
		wp_set_object_terms( $post_id, $type, 'cioff_type' );
		cioff_save_field_values( $post_id, $values );
		if ( $photo ) {
			set_post_thumbnail( $post_id, $photo );
		} else {
			delete_post_thumbnail( $post_id );
		}
		delete_post_meta( $post_id, '_cioff_retour' );
		cioff_notify_validators_fiche( $post_id, false );
		$msg = 'envoye';
	}

	wp_safe_redirect( add_query_arg( 'cioff_msg', $upload_err ? 'fichier' : $msg, $back ) );
	exit;
}

/**
 * Envoie une image du formulaire dans la médiathèque.
 * Renvoie l'ID de la pièce jointe, 0 si aucun fichier, ou WP_Error.
 */
function cioff_handle_upload( $input, $parent_id = 0 ) {
	if ( empty( $_FILES[ $input ]['name'] ) || ! empty( $_FILES[ $input ]['error'] ) && UPLOAD_ERR_NO_FILE === $_FILES[ $input ]['error'] ) {
		return 0;
	}
	return cioff_media_from_file( $_FILES[ $input ], $parent_id ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
}

/** Même chose pour un champ à fichiers multiples ; renvoie la liste des IDs créés. */
function cioff_handle_multi_upload( $input, $parent_id, &$error ) {
	$ids = array();
	if ( empty( $_FILES[ $input ]['name'] ) || ! is_array( $_FILES[ $input ]['name'] ) ) {
		return $ids;
	}
	$files = $_FILES[ $input ]; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	foreach ( array_keys( $files['name'] ) as $i ) {
		if ( UPLOAD_ERR_NO_FILE === $files['error'][ $i ] ) {
			continue;
		}
		$id = cioff_media_from_file(
			array(
				'name'     => $files['name'][ $i ],
				'type'     => $files['type'][ $i ],
				'tmp_name' => $files['tmp_name'][ $i ],
				'error'    => $files['error'][ $i ],
				'size'     => $files['size'][ $i ],
			),
			$parent_id
		);
		if ( is_wp_error( $id ) ) {
			$error = true;
		} else {
			$ids[] = $id;
		}
	}
	return $ids;
}

function cioff_media_from_file( array $file, $parent_id ) {
	if ( UPLOAD_ERR_OK !== $file['error'] ) {
		return new WP_Error( 'upload', 'Envoi impossible' );
	}
	if ( $file['size'] > 8 * MB_IN_BYTES ) {
		return new WP_Error( 'taille', 'Fichier trop lourd' );
	}
	$check = wp_check_filetype_and_ext( $file['tmp_name'], $file['name'] );
	if ( ! in_array( $check['type'], array( 'image/jpeg', 'image/png', 'image/webp' ), true ) ) {
		return new WP_Error( 'format', 'Format non accepté' );
	}
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';

	$key           = 'cioff_upload_tmp';
	$_FILES[ $key ] = $file;
	$id            = media_handle_upload( $key, $parent_id, array(), array( 'test_form' => false ) );
	unset( $_FILES[ $key ] );
	return $id;
}
