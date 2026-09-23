<?php
/**
 * Thème CIOFF France (thème en blocs).
 * La mise en page se modifie dans Apparence → Éditeur ; les fonctions (annuaire, agenda…)
 * sont dans l'extension « CIOFF France – Fonctions du site ».
 */

defined( 'ABSPATH' ) || exit;

add_action( 'after_setup_theme', function () {
	add_theme_support( 'editor-styles' );
	add_editor_style( 'style.css' );
	add_theme_support( 'responsive-embeds' );
} );

add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_style( 'cioff-theme', get_stylesheet_uri(), array(), wp_get_theme()->get( 'Version' ) );
} );

// Styles de blocs proposés aux rédacteurs (menu « Styles » d'un bloc).
add_action( 'init', function () {
	register_block_style( 'core/paragraph', array( 'name' => 'lead', 'label' => 'Chapô (texte d\'introduction)' ) );
} );

// Catégorie de compositions (patterns) du thème.
add_action( 'init', function () {
	register_block_pattern_category( 'cioff', array( 'label' => 'CIOFF France' ) );
} );

/** URL du logo CIOFF livré avec le thème (assets/logo-cioff.svg, .png ou .jpg), ou chaîne vide. */
function cioff_theme_logo_url() {
	foreach ( array( 'svg', 'png', 'jpg', 'webp' ) as $ext ) {
		if ( file_exists( get_theme_file_path( "assets/logo-cioff.$ext" ) ) ) {
			return get_theme_file_uri( "assets/logo-cioff.$ext" );
		}
	}
	return '';
}

/*
 * Tant qu'aucun logo n'est choisi dans l'éditeur (Apparence → Éditeur → En-tête → Logo),
 * le bloc « Logo du site » affiche le logo officiel du CIOFF livré avec le thème.
 */
add_filter( 'render_block_core/site-logo', function ( $content, $block ) {
	if ( '' !== trim( $content ) || ! cioff_theme_logo_url() ) {
		return $content;
	}
	$width = (int) ( $block['attrs']['width'] ?? 120 );
	return sprintf(
		'<div class="wp-block-site-logo"><a href="%1$s" class="custom-logo-link" rel="home"><img class="custom-logo" src="%2$s" alt="%3$s" width="%4$d" style="width:%4$dpx;height:auto"></a></div>',
		esc_url( home_url( '/' ) ),
		esc_url( cioff_theme_logo_url() ),
		esc_attr__( 'CIOFF – accueil', 'cioff' ),
		$width
	);
}, 10, 2 );

// Icône d'onglet par défaut : le logo CIOFF (remplaçable dans Réglages → Général → Icône du site).
add_action( 'wp_head', function () {
	if ( ! has_site_icon() && cioff_theme_logo_url() ) {
		echo '<link rel="icon" href="' . esc_url( cioff_theme_logo_url() ) . '">' . "\n";
	}
} );

// Avertissement si l'extension indispensable n'est pas active.
add_action( 'admin_notices', function () {
	if ( ! function_exists( 'cioff_types' ) && current_user_can( 'activate_plugins' ) ) {
		echo '<div class="notice notice-warning"><p>Le thème CIOFF France a besoin de l\'extension <strong>CIOFF France – Fonctions du site</strong>. Activez-la dans <a href="' . esc_url( admin_url( 'plugins.php' ) ) . '">Extensions</a>.</p></div>';
	}
} );
