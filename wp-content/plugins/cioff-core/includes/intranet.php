<?php
/**
 * Intranet (§3.6) : pages réservées aux adhérents connectés.
 *
 * Fonctionnement très simple pour les rédacteurs :
 * - la page « Intranet » et toutes ses sous-pages sont automatiquement réservées aux adhérents ;
 * - n'importe quelle autre page peut être réservée en cochant « Réservée aux adhérents » ;
 * - on peut limiter une page à certains profils (ex. uniquement les festivals).
 */

defined( 'ABSPATH' ) || exit;

add_action( 'add_meta_boxes_page', function () {
	add_meta_box( 'cioff-acces', 'Accès (intranet)', 'cioff_render_access_metabox', 'page', 'side', 'high' );
} );

function cioff_render_access_metabox( $post ) {
	wp_nonce_field( 'cioff_save_access', 'cioff_access_nonce' );
	$restricted = get_post_meta( $post->ID, '_cioff_reserve', true );
	$audience   = (array) get_post_meta( $post->ID, '_cioff_audience', true );
	$inherited  = cioff_restriction_source( $post->ID );
	if ( $inherited && $inherited !== $post->ID ) {
		printf( '<p>🔒 Cette page est réservée aux adhérents car elle se trouve sous « %s ».</p>', esc_html( get_the_title( $inherited ) ) );
	}
	?>
	<p><label><input type="checkbox" name="cioff_reserve" value="1" <?php checked( $restricted ); ?>> Réservée aux adhérents connectés</label></p>
	<p><strong>Limiter à certains profils</strong> (facultatif) :</p>
	<?php foreach ( cioff_audience_roles() as $role => $label ) : ?>
		<label style="display:block"><input type="checkbox" name="cioff_audience[]" value="<?php echo esc_attr( $role ); ?>" <?php checked( in_array( $role, $audience, true ) ); ?>> <?php echo esc_html( $label ); ?></label>
	<?php endforeach; ?>
	<p class="description">Aucune case cochée = tous les adhérents.</p>
	<?php
}

add_action( 'save_post_page', function ( $post_id ) {
	if ( ! isset( $_POST['cioff_access_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['cioff_access_nonce'] ), 'cioff_save_access' ) ) {
		return;
	}
	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	if ( ! empty( $_POST['cioff_reserve'] ) ) {
		update_post_meta( $post_id, '_cioff_reserve', 1 );
	} else {
		delete_post_meta( $post_id, '_cioff_reserve' );
	}
	$roles    = array_keys( cioff_audience_roles() );
	$audience = array_values( array_intersect( array_map( 'sanitize_key', (array) wp_unslash( $_POST['cioff_audience'] ?? array() ) ), $roles ) );
	if ( $audience ) {
		update_post_meta( $post_id, '_cioff_audience', $audience );
	} else {
		delete_post_meta( $post_id, '_cioff_audience' );
	}
} );

/** ID de la page (elle-même ou un parent) qui impose la restriction, ou 0. */
function cioff_restriction_source( $post_id ) {
	$intranet = (int) cioff_page_id( 'intranet' );
	$ids      = array_merge( array( (int) $post_id ), array_map( 'intval', get_post_ancestors( $post_id ) ) );
	foreach ( $ids as $id ) {
		if ( ( $intranet && $id === $intranet ) || get_post_meta( $id, '_cioff_reserve', true ) ) {
			return $id;
		}
	}
	return 0;
}

/** L'utilisateur courant peut-il voir cette page ? */
function cioff_can_view( $post_id, $user_id = null ) {
	if ( 'page' !== get_post_type( $post_id ) ) {
		return true;
	}
	$source = cioff_restriction_source( $post_id );
	if ( ! $source ) {
		return true;
	}
	$user_id = $user_id ?? get_current_user_id();
	if ( ! cioff_is_member( $user_id ) ) {
		return false;
	}
	if ( cioff_is_validator( $user_id ) || user_can( $user_id, 'edit_pages' ) ) {
		return true;
	}
	// Restriction par profil : la plus précise (page elle-même puis parents) s'applique.
	foreach ( array_merge( array( (int) $post_id ), get_post_ancestors( $post_id ) ) as $id ) {
		$audience = (array) get_post_meta( $id, '_cioff_audience', true );
		$audience = array_filter( $audience );
		if ( $audience ) {
			$user = get_userdata( $user_id );
			return (bool) array_intersect( $audience, (array) $user->roles );
		}
	}
	return true;
}

// Remplace le contenu par un formulaire de connexion / un message.
add_filter( 'the_content', function ( $content ) {
	$post_id = get_the_ID();
	if ( ! $post_id || cioff_can_view( $post_id ) ) {
		return $content;
	}
	if ( ! is_user_logged_in() ) {
		return cioff_login_box( 'Cet espace est réservé aux adhérents du CIOFF France. Connectez-vous pour y accéder.' );
	}
	return '<div class="cioff-notice">Cette page est réservée à certains profils d\'adhérents. Si vous pensez devoir y accéder, contactez le CIOFF France.</div>';
}, 5 );

add_filter( 'get_the_excerpt', function ( $excerpt, $post ) {
	return cioff_can_view( $post->ID ) ? $excerpt : '';
}, 10, 2 );

// Pas de contenu réservé via l'API REST.
add_filter( 'rest_prepare_page', function ( $response, $post ) {
	if ( ! cioff_can_view( $post->ID ) ) {
		$data                         = $response->get_data();
		$data['content']['rendered'] = '';
		$data['excerpt']['rendered'] = '';
		$response->set_data( $data );
	}
	return $response;
}, 10, 2 );

// Les pages réservées n'apparaissent pas dans la recherche pour les visiteurs.
add_action( 'pre_get_posts', function ( $query ) {
	if ( is_admin() || ! $query->is_main_query() || ! $query->is_search() || cioff_is_member() ) {
		return;
	}
	$ids      = get_posts( array( 'post_type' => 'page', 'posts_per_page' => -1, 'fields' => 'ids', 'meta_key' => '_cioff_reserve' ) ); // phpcs:ignore WordPress.DB.SlowDBQuery
	$intranet = cioff_page_id( 'intranet' );
	if ( $intranet ) {
		$ids[] = $intranet;
		$ids   = array_merge( $ids, wp_list_pluck( (array) get_pages( array( 'child_of' => $intranet ) ), 'ID' ) );
	}
	if ( $ids ) {
		$query->set( 'post__not_in', array_unique( array_map( 'intval', $ids ) ) );
	}
} );

// Pas d'indexation par les moteurs de recherche.
add_filter( 'wp_robots', function ( $robots ) {
	if ( is_singular( 'page' ) && cioff_restriction_source( get_queried_object_id() ) ) {
		$robots['noindex'] = true;
	}
	return $robots;
} );

/* ----- Annuaire interne des membres (liste des e-mails) ----- */

function cioff_render_liste_membres() {
	cioff_enqueue_front_css();
	if ( ! cioff_is_member() ) {
		return is_user_logged_in() ? '' : cioff_login_box( 'Liste réservée aux adhérents.' );
	}
	$users = get_users(
		array(
			'role__in' => array_merge( array( 'cioff_admin' ), array_keys( cioff_member_roles() ) ),
			'orderby'  => 'display_name',
		)
	);
	$roles = cioff_member_roles() + array( 'cioff_admin' => 'Administrateur CIOFF' );

	ob_start();
	?>
	<div class="cioff-membres">
		<p><label>Filtrer : <input type="search" class="cioff-membres__filtre" placeholder="Nom, structure, profil…" oninput="var q=this.value.toLowerCase();this.closest('.cioff-membres').querySelectorAll('tbody tr').forEach(function(r){r.hidden=q&&r.textContent.toLowerCase().indexOf(q)<0;});"></label></p>
		<div class="cioff-table-scroll">
			<table class="cioff-table">
				<thead><tr><th>Nom</th><th>Structure</th><th>Profil</th><th>E-mail</th><th>Téléphone</th></tr></thead>
				<tbody>
				<?php foreach ( $users as $u ) : ?>
					<?php
					$fiche = cioff_user_fiche( $u->ID );
					$role  = '';
					foreach ( $u->roles as $r ) {
						if ( isset( $roles[ $r ] ) ) {
							$role = $roles[ $r ];
							break;
						}
					}
					$tel = $fiche ? get_post_meta( $fiche->ID, '_cioff_telephone', true ) : '';
					?>
					<tr>
						<td><?php echo esc_html( $u->display_name ); ?></td>
						<td><?php echo $fiche && 'publish' === $fiche->post_status ? '<a href="' . esc_url( get_permalink( $fiche ) ) . '">' . esc_html( $fiche->post_title ) . '</a>' : esc_html( $fiche->post_title ?? '' ); ?></td>
						<td><?php echo esc_html( $role ); ?></td>
						<td><a href="mailto:<?php echo esc_attr( $u->user_email ); ?>"><?php echo esc_html( $u->user_email ); ?></a></td>
						<td><?php echo esc_html( $tel ); ?></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<p class="cioff-help">Ces informations sont confidentielles et réservées aux échanges entre adhérents du CIOFF France.</p>
	</div>
	<?php
	return ob_get_clean();
}
