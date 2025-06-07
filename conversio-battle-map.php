<?php
/**
 * Plugin Name: Conversio Battle Map
 * Description: Plugin que muestra el mapa gamificado del m\xC3\xA9todo Conversio para cada usuario v\xC3\xADa token.
 * Version: 0.1.0
 * Author: Tu Nombre
 * GitHub Plugin URI: https://github.com/tuusuario/conversio-battle-map
 * GitHub Branch: main
 */

defined( 'ABSPATH' ) || exit;

define( 'CBM_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );

require_once CBM_PLUGIN_PATH . 'includes/register-cpt.php';
require_once CBM_PLUGIN_PATH . 'includes/class-user-map.php';
require_once CBM_PLUGIN_PATH . 'includes/class-rest-endpoints.php';
require_once CBM_PLUGIN_PATH . 'includes/helpers-map.php';
require_once CBM_PLUGIN_PATH . 'includes/token-auth.php';

// Plugin constants
define( 'CBM_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'CBM_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Custom rewrite rule and tag for direct map access.
add_action( 'init', function() {
    add_rewrite_rule( '^battle-map/map/?$', 'index.php?battle_map_page=1', 'top' );
    add_rewrite_tag( '%battle_map_page%', '1' );
} );

/**
 * Activate plugin: register CPT and flush rewrite rules.
 */
function cbm_activate() {
    register_battle_map_cpt();
    flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'cbm_activate' );

/**
 * Deactivate plugin: flush rewrite rules.
 */
function cbm_deactivate() {
    flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'cbm_deactivate' );

/**
 * Enqueue frontend assets when shortcode is present.
 */
function cbm_enqueue_assets() {
    wp_enqueue_style( 'cbm-style', CBM_PLUGIN_URL . 'assets/style.css', [], '0.1.0' );
    wp_enqueue_script( 'cbm-alpine', CBM_PLUGIN_URL . 'assets/alpine.min.js', [], '0.1.0', true );
}
add_action( 'wp_enqueue_scripts', 'cbm_enqueue_assets' );

/**
 * Render the map template via shortcode.
 *
 * @return string HTML output of the map container.
 */
function cbm_render_map() {
    ob_start();
    include CBM_PLUGIN_DIR . 'templates/map-template.php';
    return ob_get_clean();
}
add_shortcode( 'battle_map', 'cbm_render_map' );

// Rewrite rule and template loader for direct map access.

add_filter( 'template_include', function ( $template ) {
    if ( get_query_var( 'battle_map_page' ) == 1 ) {
        return plugin_dir_path( __FILE__ ) . 'templates/map-template.php';
    }
    return $template;
} );

// Initialize plugin
add_action( 'plugins_loaded', function() {
    new CBM_User_Map();
    new CBM_Rest_Endpoints();
} );
