<?php
/**
 * Formulaire de contact avec routage par catégorie (§3.7).
 * Les catégories et adresses se règlent dans « Réglages CIOFF ».
 * Chaque message est aussi archivé dans l'administration (menu « Messages »),
 * au cas où un e-mail ne serait pas arrivé.
 */

defined( 'ABSPATH' ) || exit;

add_action( 'init', function () {
	register_post_type(
		'cioff_message',
		array(
			'labels'          => array(
				'name'          => 'Messages reçus',
				'singular_name' => 'Message',
				'menu_name'     => 'Messages',
				'all_items'     => 'Messages reçus',
				'edit_item'     => 'Message',
				'search_items'  => 'Rechercher un message',
				'not_found'     => 'Aucun message',
			),
			'public'          => false,
			'show_ui'         => true,
			'show_in_menu'    => true,
			'menu_icon'       => 'dashicons-email-alt',
			'menu_position'   => 7,
			'supports'        => array( 'title', 'editor' ),
			'capability_type' => array( 'adherent', 'adherents' ), // Mêmes droits que l'annuaire.
			'map_meta_cap'    => true,
			'capabilities'    => array( 'create_posts' => 'do_not_allow' ),
		)
	);
} );

add_filter( 'manage_cioff_message_posts_columns', function ( $cols ) {
	return array(
		'cb'           => $cols['cb'],
		'title'        => 'Objet',
		'cioff_cat'    => 'Catégorie',
		'cioff_from'   => 'Expéditeur',
		'date'         => 'Date',
	);
} );
add_action( 'manage_cioff_message_posts_custom_column', function ( $col, $post_id ) {
	if ( 'cioff_cat' === $col ) {
		echo esc_html( get_post_meta( $post_id, '_cioff_categorie', true ) );
	} elseif ( 'cioff_from' === $col ) {
		echo esc_html( get_post_meta( $post_id, '_cioff_nom', true ) . ' <' . get_post_meta( $post_id, '_cioff_email', true ) . '>' );
	}
}, 10, 2 );

/** Jeton anti-robot (pas de nonce : compatible avec le cache de page). */
function cioff_contact_token( $time ) {
	return wp_hash( 'cioff_contact|' . $time );
}

function cioff_render_contact_form( $atts = array() ) {
	$atts       = wp_parse_args( $atts, array( 'categorie' => '' ) );
	$categories = wp_list_pluck( (array) cioff_option( 'contact_categories' ), 'label' );
	cioff_enqueue_front_css();

	$msg = sanitize_key( $_GET['cioff_contact'] ?? '' ); // phpcs:ignore WordPress.Security.NonceVerification
	ob_start();
	if ( 'ok' === $msg ) {
		echo '<div class="cioff-notice cioff-notice--success" role="status">Merci, votre message a bien été envoyé. Nous vous répondrons dès que possible.</div>';
	} elseif ( 'erreur' === $msg ) {
		echo '<div class="cioff-notice cioff-notice--error" role="alert">Le message n\'a pas pu être envoyé. Vérifiez que tous les champs obligatoires sont remplis, puis réessayez.</div>';
	}
	$time = time();
	$user = wp_get_current_user();
	?>
	<form class="cioff-form cioff-contact" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="cioff_contact">
		<input type="hidden" name="t" value="<?php echo (int) $time; ?>">
		<input type="hidden" name="k" value="<?php echo esc_attr( cioff_contact_token( $time ) ); ?>">
		<div class="cioff-hp" aria-hidden="true"><label>Ne pas remplir <input type="text" name="site_web_hp" tabindex="-1" autocomplete="off"></label></div>
		<div class="cioff-field-row">
			<div class="cioff-field"><label for="cioff-c-nom">Nom <span class="cioff-req">*</span></label><input type="text" id="cioff-c-nom" name="nom" required autocomplete="name" value="<?php echo esc_attr( $user->display_name ?? '' ); ?>"></div>
			<div class="cioff-field"><label for="cioff-c-email">E-mail <span class="cioff-req">*</span></label><input type="email" id="cioff-c-email" name="email" required autocomplete="email" value="<?php echo esc_attr( $user->user_email ?? '' ); ?>"></div>
		</div>
		<div class="cioff-field">
			<label for="cioff-c-cat">Votre demande concerne <span class="cioff-req">*</span></label>
			<select id="cioff-c-cat" name="categorie" required>
				<option value="">— Choisir —</option>
				<?php foreach ( $categories as $i => $label ) : ?>
					<option value="<?php echo (int) $i; ?>" <?php selected( $atts['categorie'], $label ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
		</div>
		<div class="cioff-field"><label for="cioff-c-sujet">Objet <span class="cioff-req">*</span></label><input type="text" id="cioff-c-sujet" name="sujet" required></div>
		<div class="cioff-field"><label for="cioff-c-msg">Message <span class="cioff-req">*</span></label><textarea id="cioff-c-msg" name="message" rows="7" required></textarea></div>
		<div class="cioff-field"><label class="cioff-check"><input type="checkbox" name="rgpd" value="1" required> J'accepte que mes données soient utilisées par le CIOFF France pour répondre à ma demande. Elles ne sont ni cédées ni utilisées à d'autres fins. <span class="cioff-req">*</span></label></div>
		<p><button type="submit" class="wp-element-button cioff-button">Envoyer</button></p>
	</form>
	<?php
	return ob_get_clean();
}

add_action( 'admin_post_cioff_contact', 'cioff_handle_contact' );
add_action( 'admin_post_nopriv_cioff_contact', 'cioff_handle_contact' );

function cioff_handle_contact() {
	$back = remove_query_arg( 'cioff_contact', wp_get_referer() ?: cioff_page_url( 'contact' ) );
	$fail = function () use ( $back ) {
		wp_safe_redirect( add_query_arg( 'cioff_contact', 'erreur', $back ) . '#cioff-c-nom' );
		exit;
	};

	// Protections anti-spam : champ piège, délai minimum, jeton signé, limite par adresse IP.
	$time = absint( $_POST['t'] ?? 0 );
	$key  = sanitize_text_field( wp_unslash( $_POST['k'] ?? '' ) );
	if ( ! empty( $_POST['site_web_hp'] ) || ! hash_equals( cioff_contact_token( $time ), $key ) || time() - $time < 3 || time() - $time > DAY_IN_SECONDS ) {
		$fail();
	}
	$ip_key = 'cioff_contact_' . md5( sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ?? '' ) ) );
	$hits   = (int) get_transient( $ip_key );
	if ( $hits >= 5 ) {
		$fail();
	}
	set_transient( $ip_key, $hits + 1, 10 * MINUTE_IN_SECONDS );

	$nom     = sanitize_text_field( wp_unslash( $_POST['nom'] ?? '' ) );
	$email   = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
	$sujet   = sanitize_text_field( wp_unslash( $_POST['sujet'] ?? '' ) );
	$message = sanitize_textarea_field( wp_unslash( $_POST['message'] ?? '' ) );
	$cats    = (array) cioff_option( 'contact_categories' );
	$index   = isset( $_POST['categorie'] ) && '' !== $_POST['categorie'] ? absint( $_POST['categorie'] ) : -1;

	if ( ! $nom || ! is_email( $email ) || ! $sujet || ! $message || empty( $_POST['rgpd'] ) || ! isset( $cats[ $index ] ) ) {
		$fail();
	}

	$categorie = $cats[ $index ]['label'];
	$to        = cioff_parse_emails( $cats[ $index ]['emails'] ) ?: cioff_validation_emails();

	$saved = wp_insert_post(
		array(
			'post_type'    => 'cioff_message',
			'post_status'  => 'private',
			'post_title'   => '[' . $categorie . '] ' . $sujet,
			'post_content' => $message,
			'meta_input'   => array(
				'_cioff_nom'       => $nom,
				'_cioff_email'     => $email,
				'_cioff_categorie' => $categorie,
				'_cioff_destinataires' => implode( ', ', $to ),
			),
		)
	);

	$sent = cioff_mail(
		$to,
		'[' . $categorie . '] ' . $sujet,
		sprintf(
			"<strong>Nouveau message depuis le formulaire de contact</strong>\n\nCatégorie : %s\nDe : %s &lt;%s&gt;\n\n%s\n\n<em>Répondez directement à cet e-mail pour écrire à l'expéditeur.</em>",
			esc_html( $categorie ),
			esc_html( $nom ),
			esc_html( $email ),
			nl2br( esc_html( $message ) )
		),
		array( 'Reply-To: ' . $nom . ' <' . $email . '>' )
	);

	wp_safe_redirect( add_query_arg( 'cioff_contact', ( $sent || $saved ) ? 'ok' : 'erreur', $back ) . '#cioff-c-nom' );
	exit;
}
