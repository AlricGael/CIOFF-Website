<?php
/**
 * Affichage public d'une fiche adhérent (bloc « Détails de la fiche adhérent »).
 */

defined( 'ABSPATH' ) || exit;

function cioff_render_fiche( $post_id ) {
	$post = get_post( $post_id );
	if ( ! $post || 'cioff_adherent' !== $post->post_type ) {
		return '';
	}
	cioff_enqueue_front_css();

	$type   = cioff_get_adherent_type( $post_id );
	$fields = cioff_fields_for_type( $type );
	$v      = cioff_get_field_values( $post_id );
	$dept   = $v['departement'] ?? '';
	$lieu   = implode( ' · ', array_filter( array( 'membre-individuel' === $type ? '' : ( $v['ville'] ?? '' ), cioff_departement_label( $dept ), cioff_departement_region( $dept ) ) ) );

	ob_start();
	?>
	<div class="cioff-fiche">
		<p class="cioff-fiche__meta"><?php echo cioff_type_badge( $type ); // phpcs:ignore ?> <?php if ( $lieu ) : ?><span class="cioff-fiche__lieu"><?php echo esc_html( $lieu ); ?></span><?php endif; ?></p>

		<div class="cioff-fiche__grille">
			<div class="cioff-fiche__principal">
				<?php
				// Présentation (contenu de l'éditeur).
				if ( trim( $post->post_content ) ) {
					echo '<div class="cioff-fiche__description">' . apply_filters( 'the_content', $post->post_content ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput
				}

				// Champs texte spécifiques (festival / groupe).
				$blocs = array( 'dates', 'programmation', 'categorie', 'repertoire', 'tournees', 'historique' );
				foreach ( $blocs as $key ) {
					if ( empty( $fields[ $key ] ) || empty( $v[ $key ] ) ) {
						continue;
					}
					$value = 'select' === $fields[ $key ]['type'] ? ( $fields[ $key ]['options'][ $v[ $key ] ] ?? '' ) : $v[ $key ];
					printf( '<section class="cioff-fiche__bloc"><h2>%s</h2>%s</section>', esc_html( $fields[ $key ]['label'] ), wp_kses_post( wpautop( esc_html( $value ) ) ) );
				}

				if ( ! empty( $fields['lien_benevole'] ) && ! empty( $v['lien_benevole'] ) ) {
					printf( '<p><a class="wp-element-button" href="%s" target="_blank" rel="noopener">Devenir bénévole</a></p>', esc_url( $v['lien_benevole'] ) );
				}

				// Galerie.
				if ( ! empty( $fields['galerie'] ) && ! empty( $v['galerie'] ) ) {
					echo '<section class="cioff-fiche__bloc"><h2>Photos</h2><div class="cioff-galerie">';
					foreach ( $v['galerie'] as $att ) {
						$full = wp_get_attachment_image_url( $att, 'large' );
						if ( $full ) {
							printf( '<a href="%s" class="cioff-galerie__item">%s</a>', esc_url( $full ), wp_get_attachment_image( $att, 'medium', false, array( 'loading' => 'lazy' ) ) );
						}
					}
					echo '</div></section>';
				}

				// Vidéos.
				if ( ! empty( $fields['videos'] ) && ! empty( $v['videos'] ) ) {
					$embeds = '';
					foreach ( preg_split( '/\s+/', $v['videos'], -1, PREG_SPLIT_NO_EMPTY ) as $url ) {
						$html    = wp_oembed_get( esc_url_raw( $url ) );
						$embeds .= $html ? '<div class="cioff-video">' . $html . '</div>' : '<p><a href="' . esc_url( $url ) . '" target="_blank" rel="noopener">' . esc_html( $url ) . '</a></p>';
					}
					echo '<section class="cioff-fiche__bloc"><h2>Vidéos</h2>' . $embeds . '</section>'; // phpcs:ignore WordPress.Security.EscapeOutput
				}
				?>
			</div>

			<aside class="cioff-fiche__cote">
				<?php
				if ( ! empty( $fields['affiche'] ) && ! empty( $v['affiche'] ) ) {
					$full = wp_get_attachment_image_url( $v['affiche'], 'full' );
					printf( '<figure class="cioff-fiche__affiche"><a href="%s">%s</a><figcaption>Affiche officielle</figcaption></figure>', esc_url( $full ), wp_get_attachment_image( $v['affiche'], 'large' ) );
				}

				$contacts = array();
				if ( ! empty( $v['email'] ) ) {
					$contacts[] = '<a href="mailto:' . esc_attr( antispambot( $v['email'] ) ) . '">' . esc_html( antispambot( $v['email'] ) ) . '</a>';
				}
				if ( ! empty( $v['telephone'] ) ) {
					$contacts[] = '<a href="tel:' . esc_attr( preg_replace( '/[^0-9+]/', '', $v['telephone'] ) ) . '">' . esc_html( $v['telephone'] ) . '</a>';
				}
				if ( ! empty( $fields['site_web'] ) && ! empty( $v['site_web'] ) ) {
					$contacts[] = '<a href="' . esc_url( $v['site_web'] ) . '" target="_blank" rel="noopener">' . esc_html( preg_replace( '#^https?://(www\.)?#', '', untrailingslashit( $v['site_web'] ) ) ) . '</a>';
				}
				$reseaux = array();
				foreach ( array( 'facebook' => 'Facebook', 'instagram' => 'Instagram', 'youtube' => 'YouTube' ) as $key => $label ) {
					if ( ! empty( $fields[ $key ] ) && ! empty( $v[ $key ] ) ) {
						$reseaux[] = '<a href="' . esc_url( $v[ $key ] ) . '" target="_blank" rel="noopener">' . esc_html( $label ) . '</a>';
					}
				}
				if ( $contacts || $reseaux ) {
					echo '<div class="cioff-fiche__contact"><h2>Contact</h2>';
					if ( $contacts ) {
						echo '<ul><li>' . implode( '</li><li>', $contacts ) . '</li></ul>'; // phpcs:ignore WordPress.Security.EscapeOutput
					}
					if ( $reseaux ) {
						echo '<p class="cioff-fiche__reseaux">' . implode( ' · ', $reseaux ) . '</p>'; // phpcs:ignore WordPress.Security.EscapeOutput
					}
					echo '</div>';
				}

				echo cioff_render_mini_map( $post_id ); // phpcs:ignore WordPress.Security.EscapeOutput
				echo cioff_share_buttons( get_permalink( $post_id ), get_the_title( $post_id ) ); // phpcs:ignore WordPress.Security.EscapeOutput
				?>
			</aside>
		</div>
		<p class="cioff-fiche__retour"><a href="<?php echo esc_url( cioff_page_url( 'annuaire' ) ); ?>">← Retour à l'annuaire</a></p>
	</div>
	<?php
	return ob_get_clean();
}

/** Boutons de partage (§4.3), sans module tiers ni traceur. */
function cioff_share_buttons( $url, $title ) {
	$u = rawurlencode( $url );
	$t = rawurlencode( html_entity_decode( $title, ENT_QUOTES, 'UTF-8' ) );
	$links = array(
		'Facebook' => "https://www.facebook.com/sharer/sharer.php?u={$u}",
		'WhatsApp' => "https://wa.me/?text={$t}%20{$u}",
		'LinkedIn' => "https://www.linkedin.com/sharing/share-offsite/?url={$u}",
		'X'        => "https://twitter.com/intent/tweet?url={$u}&text={$t}",
		'E-mail'   => "mailto:?subject={$t}&body={$u}",
	);
	$html = '<div class="cioff-partage"><h2>Partager</h2><ul>';
	foreach ( $links as $label => $href ) {
		$html .= sprintf( '<li><a href="%1$s" target="_blank" rel="noopener">%2$s</a></li>', esc_url( $href ), esc_html( $label ) );
	}
	return $html . '</ul></div>';
}
