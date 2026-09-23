<?php
/**
 * Reprise des adhérents de l'ancien site (data/adherents.json).
 * Un bouton dans « Réglages CIOFF » crée les fiches manquantes ; une fiche existante (même nom) n'est jamais modifiée.
 */

defined( 'ABSPATH' ) || exit;

/** Liste des adhérents à reprendre. */
function cioff_import_source() {
	$file = CIOFF_DIR . 'data/adherents.json';
	if ( ! is_readable( $file ) ) {
		return array();
	}
	$data = json_decode( (string) file_get_contents( $file ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	return is_array( $data['adherents'] ?? null ) ? $data['adherents'] : array();
}

/**
 * Crée les fiches manquantes.
 *
 * @return array{crees:int, existantes:int}
 */
function cioff_import_adherents() {
	$crees      = 0;
	$existantes = 0;
	foreach ( cioff_import_source() as $a ) {
		if ( empty( $a['nom'] ) || ! cioff_type( $a['type'] ?? '' ) ) {
			continue;
		}
		$exists = get_posts(
			array(
				'post_type'      => 'cioff_adherent',
				'post_status'    => array( 'publish', 'pending', 'draft', 'private' ),
				'title'          => $a['nom'],
				'posts_per_page' => 1,
				'fields'         => 'ids',
			)
		);
		if ( $exists ) {
			$existantes++;
			continue;
		}
		$post_id = wp_insert_post(
			array(
				'post_type'    => 'cioff_adherent',
				'post_status'  => 'publish',
				'post_title'   => wp_slash( $a['nom'] ),
				'post_content' => wp_slash( cioff_text_to_blocks( $a['description'] ?? '' ) ),
				'post_author'  => get_current_user_id(),
			),
			true
		);
		if ( is_wp_error( $post_id ) ) {
			continue;
		}
		wp_set_object_terms( $post_id, $a['type'], 'cioff_type' );

		$meta = array(
			'_cioff_ville'       => $a['ville'] ?? '',
			'_cioff_departement' => $a['dept'] ?? '',
			'_cioff_dates'       => $a['dates'] ?? '',
			'_cioff_site_web'    => $a['site'] ?? '',
			'_cioff_email'       => $a['email'] ?? '',
			'_cioff_facebook'    => $a['facebook'] ?? '',
			'_cioff_instagram'   => $a['instagram'] ?? '',
			'_cioff_youtube'     => $a['youtube'] ?? '',
			'_cioff_import'      => 1,
		);
		if ( isset( $a['lat'], $a['lng'] ) && is_numeric( $a['lat'] ) && is_numeric( $a['lng'] ) ) {
			$meta['_cioff_lat']      = (float) $a['lat'];
			$meta['_cioff_lng']      = (float) $a['lng'];
			$meta['_cioff_geo_hash'] = 'import';
		}
		foreach ( $meta as $key => $value ) {
			if ( '' !== $value ) {
				update_post_meta( $post_id, $key, wp_slash( $value ) );
			}
		}
		$crees++;
	}
	delete_transient( 'cioff_annuaire_data' );
	return array( 'crees' => $crees, 'existantes' => $existantes );
}

/** Encadré affiché en bas de la page « Réglages CIOFF ». */
function cioff_render_import_box() {
	$total = count( cioff_import_source() );
	$done  = sanitize_key( $_GET['cioff_import'] ?? '' ); // phpcs:ignore WordPress.Security.NonceVerification
	?>
	<hr>
	<h2>Reprise des adhérents de l'ancien site</h2>
	<?php if ( 'ok' === $done ) : ?>
		<div class="notice notice-success inline"><p>
			<?php
			printf(
				'%d fiche(s) créée(s), %d déjà présente(s).',
				absint( $_GET['crees'] ?? 0 ), // phpcs:ignore WordPress.Security.NonceVerification
				absint( $_GET['existantes'] ?? 0 ) // phpcs:ignore WordPress.Security.NonceVerification
			);
			?>
			<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=cioff_adherent' ) ); ?>">Voir l'annuaire</a>
		</p></div>
	<?php endif; ?>
	<p>Crée les <?php echo (int) $total; ?> fiches (festivals, festivals associés, membres participants, groupes labellisés) publiées sur cioff-france.org, avec leur description, leurs dates, leur site web et leur position sur la carte. Les fiches qui existent déjà (même nom) ne sont pas modifiées : vous pouvez cliquer sans risque.</p>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="cioff_import">
		<?php wp_nonce_field( 'cioff_import' ); ?>
		<?php submit_button( 'Importer les adhérents', 'secondary', 'submit', false ); ?>
	</form>
	<?php
}

add_action( 'admin_post_cioff_import', function () {
	check_admin_referer( 'cioff_import' );
	if ( ! current_user_can( 'cioff_reglages' ) || ! current_user_can( 'edit_others_adherents' ) ) {
		wp_die( 'Action non autorisée.' );
	}
	$result = cioff_import_adherents();
	wp_safe_redirect(
		add_query_arg(
			array(
				'page'         => 'cioff-reglages',
				'cioff_import' => 'ok',
				'crees'        => $result['crees'],
				'existantes'   => $result['existantes'],
			),
			admin_url( 'admin.php' )
		)
	);
	exit;
} );
