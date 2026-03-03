<?php
/**
 * Plugin Name: Cotizacion Online - Muelle Flotante
 * Description: Cotizador online de muelles flotantes con calculo en tiempo real, almacenamiento de cotizaciones y envio de emails automaticos.
 * Version: 1.2.0
 * Author: Muelle Flotante
 * Text Domain: cotizacion-online
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'COT_ONLINE_VERSION', '1.2.0' );
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
        update_option( 'cot_accesorios', cot_online_get_full_accessory_list() );
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

/**
 * Return the tiered pricing table for m².
 * Keys = m² breakpoints, values = price per m² in CLP.
 *
 * @return array
 */
function cot_online_get_precio_m2_tiers() {
    return array(
        10 => 265000,
        15 => 255000,
        20 => 245000,
        25 => 235000,
        30 => 225000,
        35 => 220000,
        40 => 215000,
        45 => 210000,
        50 => 200000,
        55 => 195000,
        60 => 190000,
    );
}

/**
 * Get the price per m² for a given number of square meters.
 * Uses tiered pricing: 10-60 m² have specific prices,
 * 65+ m² (including above 130) = $185,000.
 *
 * @param int $metros Number of square meters.
 * @return int Price per m² in CLP.
 */
function cot_online_get_price_per_m2( $metros ) {
    $metros = intval( $metros );
    if ( $metros >= 65 ) {
        return 185000;
    }
    $tiers = cot_online_get_precio_m2_tiers();
    if ( isset( $tiers[ $metros ] ) ) {
        return $tiers[ $metros ];
    }
    // Fallback: find closest lower tier
    krsort( $tiers );
    foreach ( $tiers as $m2 => $precio ) {
        if ( $metros >= $m2 ) {
            return $precio;
        }
    }
    return 265000; // default for < 10
}

/**
 * Return the full default accessory list with images.
 *
 * @return array
 */
function cot_online_get_full_accessory_list() {
    return array(
        array( 'nombre' => 'Cornamusa de amarre',              'precio' => 25000,  'imagen' => 'cornamusa.jpg',            'activo' => 1 ),
        array( 'nombre' => 'Cornamusa de amarre (negra)',       'precio' => 25000,  'imagen' => 'cornamusa_negra.jpg',      'activo' => 1 ),
        array( 'nombre' => 'Cornamusa de amarre (acero)',       'precio' => 35000,  'imagen' => 'cornamusa_acero.jpg',      'activo' => 1 ),
        array( 'nombre' => 'Cornamusa pequena',                 'precio' => 15000,  'imagen' => 'cornamusa_pequena.jpg',    'activo' => 1 ),
        array( 'nombre' => 'Defensa lateral grande (roja)',     'precio' => 45000,  'imagen' => 'defensa_grande_roja.jpg',  'activo' => 1 ),
        array( 'nombre' => 'Defensa lateral grande (gris)',     'precio' => 45000,  'imagen' => 'defensa_grande_gris.jpg',  'activo' => 1 ),
        array( 'nombre' => 'Defensa lateral pequena (roja)',    'precio' => 25000,  'imagen' => 'defensa_pequena_roja.jpg', 'activo' => 1 ),
        array( 'nombre' => 'Defensa lateral pequena (gris)',    'precio' => 25000,  'imagen' => 'defensa_pequena_gris.jpg', 'activo' => 1 ),
        array( 'nombre' => 'Escala de acceso',                  'precio' => 180000, 'imagen' => 'escala.jpg',               'activo' => 1 ),
        array( 'nombre' => 'Baranda con PPR',                   'precio' => 50000,  'imagen' => 'baranda_ppr.jpg',          'activo' => 1 ),
        array( 'nombre' => 'Baranda con cuerda',                'precio' => 45000,  'imagen' => 'baranda_cuerda.jpg',       'activo' => 1 ),
        array( 'nombre' => 'Guia de pilote',                    'precio' => 120000, 'imagen' => 'guia_pilote.jpg',          'activo' => 1 ),
        array( 'nombre' => 'Soporte de pontoon',                'precio' => 15000,  'imagen' => 'soporte_pontoon.jpg',      'activo' => 1 ),
        array( 'nombre' => 'Bola anticolision',                 'precio' => 40000,  'imagen' => 'bola_anticolision.jpg',    'activo' => 1 ),
        array( 'nombre' => 'Placa de anclaje',                  'precio' => 30000,  'imagen' => 'placa_anclaje.jpg',        'activo' => 1 ),
        array( 'nombre' => 'Winche manual pequeno',             'precio' => 95000,  'imagen' => 'winche_pequeno.jpg',       'activo' => 1 ),
        array( 'nombre' => 'Winche manual grande',              'precio' => 120000, 'imagen' => 'winche_grande.jpg',        'activo' => 1 ),
        array( 'nombre' => 'Conector mushroom',                 'precio' => 5000,   'imagen' => 'mushroom.jpg',             'activo' => 1 ),
        array( 'nombre' => 'Pin de acero corto',                'precio' => 8000,   'imagen' => 'pin_acero.jpg',            'activo' => 1 ),
        array( 'nombre' => 'Pin V de conexion',                 'precio' => 5000,   'imagen' => 'pin_v.jpg',                'activo' => 1 ),
        array( 'nombre' => 'Pin largo (doble capa)',            'precio' => 10000,  'imagen' => 'pin_largo.jpg',            'activo' => 1 ),
        array( 'nombre' => 'Perno de conexion',                 'precio' => 4000,   'imagen' => 'perno_conexion.jpg',       'activo' => 1 ),
        array( 'nombre' => 'Perno largo (doble capa)',          'precio' => 6000,   'imagen' => 'perno_largo.jpg',          'activo' => 1 ),
        array( 'nombre' => 'Arandela doble',                    'precio' => 2000,   'imagen' => 'arandela_doble.jpg',       'activo' => 1 ),
        array( 'nombre' => 'Arandela simple',                   'precio' => 1500,   'imagen' => 'arandela_simple.jpg',      'activo' => 1 ),
        array( 'nombre' => 'Martillo de goma (herramienta)',    'precio' => 8000,   'imagen' => 'martillo.jpg',             'activo' => 1 ),
        array( 'nombre' => 'Llave de armado (herramienta)',     'precio' => 12000,  'imagen' => 'llave.jpg',                'activo' => 1 ),
    );
}

/**
 * Upgrade accessory list for existing installs (v1.1.0).
 * Replaces the old 6-item list with the complete 27-item list.
 */
function cot_online_upgrade_accessories() {
    $db_version = get_option( 'cot_online_db_version', '1.0.0' );
    if ( version_compare( $db_version, '1.2.0', '<' ) ) {
        update_option( 'cot_accesorios', cot_online_get_full_accessory_list() );
        update_option( 'cot_online_db_version', '1.2.0' );
    }
}
add_action( 'plugins_loaded', 'cot_online_upgrade_accessories', 20 );
