<?php
/**
 * Shortcode for the quotation form
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class COT_Shortcode {

    public function __construct() {
        add_shortcode( 'cotizacion_online', array( $this, 'render_shortcode' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
    }

    /**
     * Enqueue frontend assets only on the cotizacion page
     */
    public function enqueue_assets() {
        if ( ! is_singular( 'page' ) ) {
            return;
        }

        global $post;
        if ( ! $post || ! has_shortcode( $post->post_content, 'cotizacion_online' ) ) {
            return;
        }

        wp_enqueue_style(
            'cot-online-css',
            COT_ONLINE_URL . 'assets/css/cotizacion.css',
            array(),
            COT_ONLINE_VERSION
        );

        wp_enqueue_script(
            'cot-online-js',
            COT_ONLINE_URL . 'assets/js/cotizacion.js',
            array( 'jquery' ),
            COT_ONLINE_VERSION,
            true
        );

        // Pass pricing data to JS
        $accesorios = get_option( 'cot_accesorios', array() );
        $accesorios_activos = array();
        if ( is_array( $accesorios ) ) {
            foreach ( $accesorios as $index => $acc ) {
                if ( ! empty( $acc['activo'] ) ) {
                    $accesorios_activos[] = array(
                        'id'     => $index,
                        'nombre' => $acc['nombre'],
                        'precio' => intval( $acc['precio'] ),
                    );
                }
            }
        }

        wp_localize_script( 'cot-online-js', 'cotData', array(
            'ajaxurl'            => admin_url( 'admin-ajax.php' ),
            'nonce'              => wp_create_nonce( 'cot_online_nonce' ),
            'precio_m2'          => 290000,
            'accesorios'         => $accesorios_activos,
        ) );
    }

    /**
     * Render the shortcode
     */
    public function render_shortcode( $atts ) {
        ob_start();
        include COT_ONLINE_PATH . 'templates/form-cotizacion.php';
        return ob_get_clean();
    }
}
