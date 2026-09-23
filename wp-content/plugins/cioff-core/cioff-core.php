<?php
/**
 * Plugin Name:       CIOFF France – Fonctions du site
 * Description:       Annuaire des adhérents (6 types) avec carte interactive, fiches avec validation, agenda, intranet, formulaire de contact avec routage e-mail, rôles CIOFF.
 * Version:           1.0.0
 * Requires at least: 6.5
 * Requires PHP:      8.0
 * Author:            CIOFF France – Commission Communication
 * Text Domain:       cioff
 * License:           GPL-2.0-or-later
 */

defined( 'ABSPATH' ) || exit;

define( 'CIOFF_VERSION', '1.0.0' );
define( 'CIOFF_FILE', __FILE__ );
define( 'CIOFF_DIR', plugin_dir_path( __FILE__ ) );
define( 'CIOFF_URL', plugin_dir_url( __FILE__ ) );

require_once CIOFF_DIR . 'includes/helpers.php';
require_once CIOFF_DIR . 'includes/departements.php';
require_once CIOFF_DIR . 'includes/post-types.php';
require_once CIOFF_DIR . 'includes/roles.php';
require_once CIOFF_DIR . 'includes/settings.php';
require_once CIOFF_DIR . 'includes/adherent-fields.php';
require_once CIOFF_DIR . 'includes/adherent-admin.php';
require_once CIOFF_DIR . 'includes/adherent-front.php';
require_once CIOFF_DIR . 'includes/workflow.php';
require_once CIOFF_DIR . 'includes/map.php';
require_once CIOFF_DIR . 'includes/fiche-display.php';
require_once CIOFF_DIR . 'includes/events.php';
require_once CIOFF_DIR . 'includes/intranet.php';
require_once CIOFF_DIR . 'includes/contact.php';
require_once CIOFF_DIR . 'includes/blocks.php';
require_once CIOFF_DIR . 'includes/import.php';
require_once CIOFF_DIR . 'includes/setup.php';

register_activation_hook( __FILE__, 'cioff_activate' );
register_deactivation_hook( __FILE__, 'flush_rewrite_rules' );

function cioff_activate() {
	cioff_register_post_types();
	cioff_register_roles();
	cioff_insert_default_terms();
	cioff_create_default_pages();
	flush_rewrite_rules();
}

// Les rôles et capacités sont versionnés : on les remet à jour si le plugin change.
add_action( 'init', function () {
	if ( get_option( 'cioff_roles_version' ) !== CIOFF_VERSION ) {
		cioff_register_roles();
		update_option( 'cioff_roles_version', CIOFF_VERSION );
	}
}, 20 );
