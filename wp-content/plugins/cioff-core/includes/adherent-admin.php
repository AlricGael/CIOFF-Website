<?php
/**
 * Fiches adhérents dans l'administration (utilisée par les Administrateurs CIOFF).
 */

defined( 'ABSPATH' ) || exit;

add_action( 'add_meta_boxes_cioff_adherent', function () {
	add_meta_box( 'cioff-fiche', 'Informations de la fiche', 'cioff_render_fiche_metabox', 'cioff_adherent', 'normal', 'high' );
	add_meta_box( 'cioff-position', 'Position sur la carte', 'cioff_render_position_metabox', 'cioff_adherent', 'side' );
} );

/** Rendu d'un champ (administration ou formulaire public). */
function cioff_render_field( $key, $field, $value, $context = 'admin' ) {
	$id       = 'cioff-f-' . $key;
	$name     = 'cioff[' . $key . ']';
	$required = ! empty( $field['requis'] ) ? ' required' : '';
	$familles = implode( ' ', $field['familles'] );

	echo '<div class="cioff-field cioff-field--' . esc_attr( $field['type'] ) . '" data-familles="' . esc_attr( $familles ) . '">';
	echo '<label for="' . esc_attr( $id ) . '">' . esc_html( $field['label'] ) . ( $required ? ' <span class="cioff-req" aria-hidden="true">*</span>' : '' ) . '</label>';

	switch ( $field['type'] ) {
		case 'textarea':
			printf(
				'<textarea id="%1$s" name="%2$s" rows="%3$d">%4$s</textarea>',
				esc_attr( $id ),
				esc_attr( $name ),
				(int) ( $field['lignes'] ?? 4 ),
				esc_textarea( $value )
			);
			break;

		case 'select':
			printf( '<select id="%1$s" name="%2$s"><option value="">— Choisir —</option>', esc_attr( $id ), esc_attr( $name ) );
			foreach ( $field['options'] as $opt => $label ) {
				printf( '<option value="%1$s"%2$s>%3$s</option>', esc_attr( $opt ), selected( $value, $opt, false ), esc_html( $label ) );
			}
			echo '</select>';
			break;

		case 'departement':
			printf( '<select id="%1$s" name="%2$s"%3$s><option value="">— Choisir le département —</option>', esc_attr( $id ), esc_attr( $name ), $required ); // phpcs:ignore WordPress.Security.EscapeOutput
			foreach ( cioff_departements() as $code => $d ) {
				printf( '<option value="%1$s"%2$s>%3$s – %4$s</option>', esc_attr( $code ), selected( $value, (string) $code, false ), esc_html( $code ), esc_html( $d[0] ) );
			}
			echo '</select>';
			break;

		case 'image':
			if ( 'admin' === $context ) {
				$url = $value ? wp_get_attachment_image_url( $value, 'medium' ) : '';
				echo '<div class="cioff-media" data-multiple="0">';
				echo '<div class="cioff-media__preview">' . ( $url ? '<img src="' . esc_url( $url ) . '" alt="">' : '' ) . '</div>';
				printf( '<input type="hidden" id="%1$s" name="%2$s" value="%3$s">', esc_attr( $id ), esc_attr( $name ), esc_attr( $value ) );
				echo '<button type="button" class="button cioff-media__choose">Choisir une image</button> <button type="button" class="button-link-delete cioff-media__clear">Retirer</button></div>';
			} else {
				if ( $value ) {
					echo '<div class="cioff-media__preview">' . wp_get_attachment_image( $value, 'medium' ) . '</div>';
					printf( '<label class="cioff-check"><input type="checkbox" name="cioff_retirer[%s]" value="1"> Retirer cette image</label>', esc_attr( $key ) );
				}
				printf( '<input type="file" id="%1$s" name="cioff_fichier_%2$s" accept="image/jpeg,image/png,image/webp">', esc_attr( $id ), esc_attr( $key ) );
			}
			break;

		case 'gallery':
			$ids = array_filter( array_map( 'absint', (array) $value ) );
			if ( 'admin' === $context ) {
				echo '<div class="cioff-media" data-multiple="1"><div class="cioff-media__preview">';
				foreach ( $ids as $att ) {
					echo wp_get_attachment_image( $att, 'thumbnail' );
				}
				echo '</div>';
				printf( '<input type="hidden" id="%1$s" name="%2$s" value="%3$s">', esc_attr( $id ), esc_attr( $name ), esc_attr( implode( ',', $ids ) ) );
				echo '<button type="button" class="button cioff-media__choose">Choisir les photos</button> <button type="button" class="button-link-delete cioff-media__clear">Tout retirer</button></div>';
			} else {
				if ( $ids ) {
					echo '<div class="cioff-gallery-edit">';
					foreach ( $ids as $att ) {
						echo '<label class="cioff-gallery-edit__item">' . wp_get_attachment_image( $att, 'thumbnail' );
						printf( '<span><input type="checkbox" name="cioff_retirer_galerie[]" value="%d"> Retirer</span></label>', (int) $att );
					}
					echo '</div>';
				}
				printf( '<input type="file" id="%1$s" name="cioff_fichier_%2$s[]" accept="image/jpeg,image/png,image/webp" multiple>', esc_attr( $id ), esc_attr( $key ) );
			}
			break;

		default:
			printf(
				'<input type="%1$s" id="%2$s" name="%3$s" value="%4$s"%5$s>',
				esc_attr( $field['type'] ),
				esc_attr( $id ),
				esc_attr( $name ),
				esc_attr( $value ),
				$required // phpcs:ignore WordPress.Security.EscapeOutput
			);
	}

	if ( ! empty( $field['aide'] ) ) {
		echo '<p class="cioff-help description">' . esc_html( $field['aide'] ) . '</p>';
	}
	echo '</div>';
}

/** Affiche les champs regroupés par section. */
function cioff_render_field_sections( array $fields, array $values, $context ) {
	$sections = array();
	foreach ( $fields as $key => $field ) {
		$sections[ $field['section'] ][ $key ] = $field;
	}
	foreach ( $sections as $title => $section_fields ) {
		$familles = array_unique( array_merge( ...array_values( array_map( fn( $f ) => $f['familles'], $section_fields ) ) ) );
		echo '<fieldset class="cioff-section" data-familles="' . esc_attr( implode( ' ', $familles ) ) . '"><legend>' . esc_html( $title ) . '</legend>';
		foreach ( $section_fields as $key => $field ) {
			cioff_render_field( $key, $field, $values[ $key ] ?? '', $context );
		}
		echo '</fieldset>';
	}
}

function cioff_render_fiche_metabox( $post ) {
	wp_nonce_field( 'cioff_save_fiche', 'cioff_fiche_nonce' );
	$type   = cioff_get_adherent_type( $post->ID );
	$values = cioff_get_field_values( $post->ID );
	?>
	<div class="cioff-form cioff-form--admin" data-cioff-type-form>
		<div class="cioff-field">
			<label for="cioff-type">Type d'adhérent <span class="cioff-req">*</span></label>
			<select id="cioff-type" name="cioff_type" data-cioff-type-select required>
				<option value="">— Choisir le type —</option>
				<?php foreach ( cioff_types() as $slug => $t ) : ?>
					<option value="<?php echo esc_attr( $slug ); ?>" data-famille="<?php echo esc_attr( $t['famille'] ); ?>" <?php selected( $type, $slug ); ?>><?php echo esc_html( $t['label'] ); ?></option>
				<?php endforeach; ?>
			</select>
			<p class="description">La description se rédige dans l'éditeur ci-dessus ; la photo principale dans « Image mise en avant ». Le responsable de la fiche se choisit dans « Auteur ».</p>
		</div>
		<?php cioff_render_field_sections( cioff_fields(), $values, 'admin' ); ?>
	</div>
	<?php
}

function cioff_render_position_metabox( $post ) {
	$lat    = get_post_meta( $post->ID, '_cioff_lat', true );
	$lng    = get_post_meta( $post->ID, '_cioff_lng', true );
	$manual = get_post_meta( $post->ID, '_cioff_position_manuelle', true );
	?>
	<p class="description">La position est calculée automatiquement à partir de la ville et du département. Pour la corriger, cochez la case et saisissez les coordonnées (clic droit sur Google Maps → coordonnées).</p>
	<p><label><input type="checkbox" name="cioff_position_manuelle" value="1" <?php checked( $manual ); ?>> Position saisie à la main</label></p>
	<p><label>Latitude<br><input type="text" name="cioff_lat" value="<?php echo esc_attr( $lat ); ?>" class="widefat"></label></p>
	<p><label>Longitude<br><input type="text" name="cioff_lng" value="<?php echo esc_attr( $lng ); ?>" class="widefat"></label></p>
	<?php if ( $lat && $lng ) : ?>
		<p><a href="<?php echo esc_url( sprintf( 'https://www.openstreetmap.org/?mlat=%1$s&mlon=%2$s#map=12/%1$s/%2$s', $lat, $lng ) ); ?>" target="_blank" rel="noopener">Vérifier sur la carte ↗</a></p>
	<?php else : ?>
		<p><em>Pas encore de position : elle sera calculée à l'enregistrement.</em></p>
	<?php endif; ?>
	<?php
}

add_action( 'save_post_cioff_adherent', function ( $post_id ) {
	if ( ! isset( $_POST['cioff_fiche_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['cioff_fiche_nonce'] ), 'cioff_save_fiche' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$type = sanitize_key( wp_unslash( $_POST['cioff_type'] ?? '' ) );
	if ( cioff_type( $type ) ) {
		wp_set_object_terms( $post_id, $type, 'cioff_type' );
	}

	$raw    = wp_unslash( $_POST['cioff'] ?? array() ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	$values = array();
	foreach ( cioff_fields() as $key => $field ) {
		if ( array_key_exists( $key, $raw ) ) {
			$values[ $key ] = cioff_sanitize_field( $field, $raw[ $key ] );
		}
	}

	// Position manuelle.
	if ( ! empty( $_POST['cioff_position_manuelle'] ) ) {
		update_post_meta( $post_id, '_cioff_position_manuelle', 1 );
		$lat = (float) str_replace( ',', '.', sanitize_text_field( wp_unslash( $_POST['cioff_lat'] ?? '' ) ) );
		$lng = (float) str_replace( ',', '.', sanitize_text_field( wp_unslash( $_POST['cioff_lng'] ?? '' ) ) );
		if ( $lat && $lng ) {
			update_post_meta( $post_id, '_cioff_lat', $lat );
			update_post_meta( $post_id, '_cioff_lng', $lng );
		}
	} else {
		delete_post_meta( $post_id, '_cioff_position_manuelle' );
	}

	cioff_save_field_values( $post_id, $values );
}, 10 );

add_action( 'admin_enqueue_scripts', function ( $hook ) {
	if ( 'toplevel_page_cioff-validations' === $hook || 'edit.php' === $hook ) {
		wp_enqueue_style( 'cioff-admin', CIOFF_URL . 'assets/css/admin.css', array(), CIOFF_VERSION );
	}
	$screen = get_current_screen();
	if ( ! $screen || 'cioff_adherent' !== $screen->post_type || ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
		return;
	}
	wp_enqueue_media();
	wp_enqueue_style( 'cioff-admin', CIOFF_URL . 'assets/css/admin.css', array(), CIOFF_VERSION );
	wp_enqueue_script( 'cioff-fiche-form', CIOFF_URL . 'assets/js/fiche-form.js', array(), CIOFF_VERSION, true );
} );

// Colonnes de la liste des fiches.
add_filter( 'manage_cioff_adherent_posts_columns', function ( $cols ) {
	$new = array();
	foreach ( $cols as $key => $label ) {
		$new[ $key ] = $label;
		if ( 'title' === $key ) {
			$new['cioff_lieu']   = 'Localisation';
			$new['cioff_carte']  = 'Carte';
			$new['cioff_modif']  = 'Modification proposée';
		}
	}
	return $new;
} );

add_action( 'manage_cioff_adherent_posts_custom_column', function ( $col, $post_id ) {
	switch ( $col ) {
		case 'cioff_lieu':
			$ville = get_post_meta( $post_id, '_cioff_ville', true );
			$dept  = get_post_meta( $post_id, '_cioff_departement', true );
			echo esc_html( trim( $ville . ( $dept ? ' – ' . cioff_departement_label( $dept ) : '' ), ' –' ) );
			break;
		case 'cioff_carte':
			echo get_post_meta( $post_id, '_cioff_lat', true ) ? '✅' : '<span title="Position introuvable : vérifiez la ville ou saisissez-la à la main">⚠️</span>';
			break;
		case 'cioff_modif':
			if ( get_post_meta( $post_id, '_cioff_proposition', true ) ) {
				printf( '<a href="%s"><strong>À valider</strong></a>', esc_url( get_edit_post_link( $post_id ) . '#cioff-proposition' ) );
			} else {
				echo '—';
			}
			break;
	}
}, 10, 2 );

// Filtre par type au-dessus de la liste.
add_action( 'restrict_manage_posts', function ( $post_type ) {
	if ( 'cioff_adherent' !== $post_type ) {
		return;
	}
	$current = sanitize_key( $_GET['cioff_type'] ?? '' ); // phpcs:ignore WordPress.Security.NonceVerification
	echo '<select name="cioff_type"><option value="">Tous les types</option>';
	foreach ( cioff_types() as $slug => $type ) {
		printf( '<option value="%1$s"%2$s>%3$s</option>', esc_attr( $slug ), selected( $current, $slug, false ), esc_html( $type['pluriel'] ) );
	}
	echo '</select>';
} );
