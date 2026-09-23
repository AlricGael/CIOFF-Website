<?php
/**
 * Page « Réglages CIOFF » : tout ce qu'un administrateur non technicien doit pouvoir changer
 * sans toucher au code (thème annuel, liens, réseaux sociaux, e-mails de validation, routage du contact).
 */

defined( 'ABSPATH' ) || exit;

function cioff_default_options() {
	return array(
		'theme_annuel_titre'  => '',
		'theme_annuel_texte'  => '',
		'theme_annuel_image'  => 0,
		'lien_international'  => 'https://www.cioff.org',
		'facebook'            => '',
		'instagram'           => '',
		'youtube'             => '',
		'emails_validation'   => get_option( 'admin_email' ),
		'contact_categories'  => array(
			array( 'label' => 'Adhésion', 'emails' => '' ),
			array( 'label' => 'Communication', 'emails' => '' ),
			array( 'label' => 'Festival', 'emails' => '' ),
			array( 'label' => 'Groupe', 'emails' => '' ),
			array( 'label' => 'Label CIOFF', 'emails' => 'label@cioff-france.org' ),
			array( 'label' => 'CIOFF Jeunes', 'emails' => 'contact@cioffjeune.fr' ),
			array( 'label' => 'Partenariat', 'emails' => '' ),
			array( 'label' => 'Commande de matériel', 'emails' => '' ),
			array( 'label' => 'Autre', 'emails' => '' ),
		),
	);
}

function cioff_option( $key, $fallback = '' ) {
	$options  = get_option( 'cioff_options', array() );
	$defaults = cioff_default_options();
	if ( isset( $options[ $key ] ) && '' !== $options[ $key ] ) {
		return $options[ $key ];
	}
	return $defaults[ $key ] ?? $fallback;
}

add_action( 'admin_menu', function () {
	add_menu_page(
		'Réglages CIOFF',
		'Réglages CIOFF',
		'cioff_reglages',
		'cioff-reglages',
		'cioff_render_settings_page',
		'dashicons-admin-settings',
		59
	);
} );

add_action( 'admin_init', function () {
	register_setting(
		'cioff_options',
		'cioff_options',
		array( 'sanitize_callback' => 'cioff_sanitize_options' )
	);
} );

// Les Administrateurs CIOFF (sans « manage_options ») peuvent enregistrer cette page.
add_filter( 'option_page_capability_cioff_options', fn() => 'cioff_reglages' );

function cioff_sanitize_options( $input ) {
	$out = array(
		'theme_annuel_titre' => sanitize_text_field( $input['theme_annuel_titre'] ?? '' ),
		'theme_annuel_texte' => wp_kses_post( $input['theme_annuel_texte'] ?? '' ),
		'theme_annuel_image' => absint( $input['theme_annuel_image'] ?? 0 ),
		'lien_international' => esc_url_raw( $input['lien_international'] ?? '' ),
		'facebook'           => esc_url_raw( $input['facebook'] ?? '' ),
		'instagram'          => esc_url_raw( $input['instagram'] ?? '' ),
		'youtube'            => esc_url_raw( $input['youtube'] ?? '' ),
		'emails_validation'  => implode( ', ', cioff_parse_emails( $input['emails_validation'] ?? '' ) ),
		'contact_categories' => array(),
	);

	$labels = (array) ( $input['contact_categories']['label'] ?? array() );
	$emails = (array) ( $input['contact_categories']['emails'] ?? array() );
	foreach ( $labels as $i => $label ) {
		$label = sanitize_text_field( $label );
		if ( '' === $label ) {
			continue;
		}
		$out['contact_categories'][] = array(
			'label'  => $label,
			'emails' => implode( ', ', cioff_parse_emails( $emails[ $i ] ?? '' ) ),
		);
	}
	add_settings_error( 'cioff_options', 'cioff_saved', 'Réglages enregistrés.', 'success' );
	return $out;
}

add_action( 'admin_enqueue_scripts', function ( $hook ) {
	if ( 'toplevel_page_cioff-reglages' === $hook ) {
		wp_enqueue_media();
	}
} );

function cioff_render_settings_page() {
	if ( ! current_user_can( 'cioff_reglages' ) ) {
		return;
	}
	$o          = wp_parse_args( get_option( 'cioff_options', array() ), cioff_default_options() );
	$categories = $o['contact_categories'] ?: cioff_default_options()['contact_categories'];
	$image_url  = $o['theme_annuel_image'] ? wp_get_attachment_image_url( $o['theme_annuel_image'], 'medium' ) : '';
	?>
	<div class="wrap">
		<h1>Réglages CIOFF France</h1>
		<p>Ces réglages s'appliquent partout sur le site. Pensez à cliquer sur <strong>Enregistrer</strong> en bas de la page.</p>
		<?php settings_errors( 'cioff_options' ); ?>
		<form method="post" action="options.php">
			<?php settings_fields( 'cioff_options' ); ?>

			<h2>Thème annuel</h2>
			<p class="description">Défini par la commission culture, il s'affiche sur la page d'accueil.</p>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="cioff-ta-titre">Titre du thème</label></th>
					<td><input type="text" class="regular-text" id="cioff-ta-titre" name="cioff_options[theme_annuel_titre]" value="<?php echo esc_attr( $o['theme_annuel_titre'] ); ?>" placeholder="Ex. : Les danses de la mer"></td>
				</tr>
				<tr>
					<th scope="row"><label for="cioff-ta-texte">Présentation</label></th>
					<td><textarea class="large-text" rows="4" id="cioff-ta-texte" name="cioff_options[theme_annuel_texte]"><?php echo esc_textarea( $o['theme_annuel_texte'] ); ?></textarea></td>
				</tr>
				<tr>
					<th scope="row">Image</th>
					<td>
						<div class="cioff-image-field">
							<img src="<?php echo esc_url( $image_url ); ?>" alt="" style="max-width:240px;height:auto;display:<?php echo $image_url ? 'block' : 'none'; ?>;margin-bottom:8px">
							<input type="hidden" name="cioff_options[theme_annuel_image]" value="<?php echo esc_attr( $o['theme_annuel_image'] ); ?>">
							<button type="button" class="button cioff-image-choose">Choisir une image</button>
							<button type="button" class="button-link-delete cioff-image-remove" style="margin-left:8px">Retirer</button>
						</div>
					</td>
				</tr>
			</table>

			<h2>Liens et réseaux sociaux</h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="cioff-intl">Site du CIOFF International</label></th>
					<td><input type="url" class="regular-text" id="cioff-intl" name="cioff_options[lien_international]" value="<?php echo esc_attr( $o['lien_international'] ); ?>"></td>
				</tr>
				<?php foreach ( array( 'facebook' => 'Page Facebook', 'instagram' => 'Compte Instagram', 'youtube' => 'Chaîne YouTube' ) as $key => $label ) : ?>
					<tr>
						<th scope="row"><label for="cioff-<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></label></th>
						<td><input type="url" class="regular-text" id="cioff-<?php echo esc_attr( $key ); ?>" name="cioff_options[<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( $o[ $key ] ); ?>" placeholder="https://"></td>
					</tr>
				<?php endforeach; ?>
			</table>

			<h2>Validation des contenus</h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="cioff-validation">E-mails prévenus</label></th>
					<td>
						<input type="text" class="large-text" id="cioff-validation" name="cioff_options[emails_validation]" value="<?php echo esc_attr( $o['emails_validation'] ); ?>">
						<p class="description">Reçoivent une notification quand un adhérent envoie une fiche, une modification ou un événement à valider. Séparez plusieurs adresses par des virgules.</p>
					</td>
				</tr>
			</table>

			<h2>Formulaire de contact : catégories et destinataires</h2>
			<p class="description">Chaque catégorie de la liste déroulante envoie le message aux adresses indiquées (plusieurs adresses possibles, séparées par des virgules, par exemple pour mettre le président en copie). Sans adresse, le message part vers les e-mails de validation ci-dessus.</p>
			<table class="widefat striped" id="cioff-contact-rows" style="max-width:900px;margin-top:12px">
				<thead><tr><th style="width:30%">Catégorie</th><th>Destinataires</th><th style="width:90px"></th></tr></thead>
				<tbody>
					<?php foreach ( $categories as $row ) : ?>
						<tr>
							<td><input type="text" class="widefat" name="cioff_options[contact_categories][label][]" value="<?php echo esc_attr( $row['label'] ); ?>"></td>
							<td><input type="text" class="widefat" name="cioff_options[contact_categories][emails][]" value="<?php echo esc_attr( $row['emails'] ); ?>" placeholder="contact@cioff-france.org, president@cioff-france.org"></td>
							<td><button type="button" class="button-link-delete cioff-row-remove">Supprimer</button></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<p><button type="button" class="button" id="cioff-row-add">+ Ajouter une catégorie</button></p>

			<?php submit_button( 'Enregistrer' ); ?>
		</form>
		<?php cioff_render_import_box(); ?>
	</div>
	<script>
	( function () {
		var tbody = document.querySelector( '#cioff-contact-rows tbody' );
		document.getElementById( 'cioff-row-add' ).addEventListener( 'click', function () {
			var row = tbody.rows[0].cloneNode( true );
			row.querySelectorAll( 'input' ).forEach( function ( i ) { i.value = ''; } );
			tbody.appendChild( row );
			row.querySelector( 'input' ).focus();
		} );
		tbody.addEventListener( 'click', function ( e ) {
			if ( e.target.classList.contains( 'cioff-row-remove' ) && tbody.rows.length > 1 ) {
				e.target.closest( 'tr' ).remove();
			}
		} );
		document.querySelectorAll( '.cioff-image-field' ).forEach( function ( field ) {
			var img = field.querySelector( 'img' ), input = field.querySelector( 'input' ), frame;
			field.querySelector( '.cioff-image-choose' ).addEventListener( 'click', function () {
				frame = frame || wp.media( { title: 'Choisir une image', multiple: false, library: { type: 'image' } } );
				frame.off( 'select' ).on( 'select', function () {
					var a = frame.state().get( 'selection' ).first().toJSON();
					input.value = a.id;
					img.src = ( a.sizes && a.sizes.medium ? a.sizes.medium.url : a.url );
					img.style.display = 'block';
				} );
				frame.open();
			} );
			field.querySelector( '.cioff-image-remove' ).addEventListener( 'click', function () {
				input.value = '';
				img.style.display = 'none';
			} );
		} );
	} )();
	</script>
	<?php
}
