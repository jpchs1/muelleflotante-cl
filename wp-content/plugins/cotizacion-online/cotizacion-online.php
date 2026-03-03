<?php
/**
 * Plugin Name: Cotizacion Online - Muelle Flotante
 * Description: Cotizador online de muelles flotantes con calculo en tiempo real, almacenamiento de cotizaciones y envio de emails automaticos.
 * Version: 1.0.0
 * Author: Muelle Flotante
 * Text Domain: cotizacion-online
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'COT_ONLINE_VERSION', '1.0.0' );
define( 'COT_ONLINE_PATH', plugin_dir_path( __FILE__ ) );
define( 'COT_ONLINE_URL', plugin_dir_url( __FILE__ ) );

// Include files
require_once COT_ONLINE_PATH . 'includes/class-cot-cpt.php';
require_once COT_ONLINE_PATH . 'includes/class-cot-admin.php';
require_once COT_ONLINE_PATH . 'includes/class-cot-shortcode.php';
require_once COT_ONLINE_PATH . 'includes/class-cot-ajax.php';
require_once COT_ONLINE_PATH . 'includes/class-cot-email.php';

/**
 * Plugin activation hook
 */
function cot_online_activate() {
    // Register CPT first
    COT_CPT::register_post_type();

    // Flush rewrite rules
    flush_rewrite_rules();

    // Create the cotizacion page if it doesn't exist
    $page = get_page_by_path( 'cotizaciononline' );
    if ( ! $page ) {
        wp_insert_post( array(
            'post_title'   => 'Cotizacion Online',
            'post_name'    => 'cotizaciononline',
            'post_content' => '[cotizacion_online]',
            'post_status'  => 'publish',
            'post_type'    => 'page',
            'post_author'  => 1,
        ) );
    }

    // Set default options if not set
    if ( false === get_option( 'cot_precio_m2' ) ) {
        update_option( 'cot_precio_m2', 290000 );
    }
    if ( false === get_option( 'cot_costo_santiago' ) ) {
        update_option( 'cot_costo_santiago', 45000 );
    }
    if ( false === get_option( 'cot_costo_santiago_tipo' ) ) {
        update_option( 'cot_costo_santiago_tipo', 'por_m2' );
    }
    if ( false === get_option( 'cot_accesorios' ) ) {
        $default_accesorios = array(
            array(
                'nombre' => 'Cornamusa de amarre',
                'precio' => 25000,
                'activo' => 1,
            ),
            array(
                'nombre' => 'Defensa lateral',
                'precio' => 35000,
                'activo' => 1,
            ),
            array(
                'nombre' => 'Escala de acceso',
                'precio' => 180000,
                'activo' => 1,
            ),
            array(
                'nombre' => 'Soporte para kayak',
                'precio' => 95000,
                'activo' => 1,
            ),
            array(
                'nombre' => 'Iluminacion LED sumergible',
                'precio' => 65000,
                'activo' => 1,
            ),
            array(
                'nombre' => 'Anclaje de fondo',
                'precio' => 120000,
                'activo' => 1,
            ),
        );
        update_option( 'cot_accesorios', $default_accesorios );
    }
    if ( false === get_option( 'cot_admin_email' ) ) {
        update_option( 'cot_admin_email', 'info@muelleflotante.cl' );
    }
}
register_activation_hook( __FILE__, 'cot_online_activate' );

/**
 * Plugin deactivation hook
 */
function cot_online_deactivate() {
    flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'cot_online_deactivate' );

/**
 * Initialize plugin classes
 */
function cot_online_init() {
    new COT_CPT();
    new COT_Admin();
    new COT_Shortcode();
    new COT_Ajax();
}
add_action( 'plugins_loaded', 'cot_online_init' );
