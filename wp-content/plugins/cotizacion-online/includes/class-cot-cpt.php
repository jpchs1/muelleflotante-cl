<?php
/**
 * Custom Post Type: Cotizaciones
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class COT_CPT {

    public function __construct() {
        add_action( 'init', array( __CLASS__, 'register_post_type' ) );
        add_filter( 'manage_cotizacion_posts_columns', array( $this, 'set_columns' ) );
        add_action( 'manage_cotizacion_posts_custom_column', array( $this, 'render_columns' ), 10, 2 );
        add_filter( 'manage_edit-cotizacion_sortable_columns', array( $this, 'sortable_columns' ) );
    }

    /**
     * Register the Cotizaciones CPT
     */
    public static function register_post_type() {
        $labels = array(
            'name'               => 'Cotizaciones',
            'singular_name'      => 'Cotizacion',
            'menu_name'          => 'Cotizaciones',
            'all_items'          => 'Todas las Cotizaciones',
            'view_item'          => 'Ver Cotizacion',
            'search_items'       => 'Buscar Cotizaciones',
            'not_found'          => 'No se encontraron cotizaciones',
            'not_found_in_trash' => 'No se encontraron cotizaciones en la papelera',
        );

        $args = array(
            'labels'              => $labels,
            'public'              => false,
            'show_ui'             => true,
            'show_in_menu'        => false, // We add it under our custom menu
            'capability_type'     => 'post',
            'has_archive'         => false,
            'hierarchical'        => false,
            'supports'            => array( 'title' ),
            'menu_icon'           => 'dashicons-media-spreadsheet',
            'exclude_from_search' => true,
            'publicly_queryable'  => false,
        );

        register_post_type( 'cotizacion', $args );
    }

    /**
     * Custom columns for the admin list
     */
    public function set_columns( $columns ) {
        $new_columns = array(
            'cb'              => $columns['cb'],
            'title'           => 'ID Cotizacion',
            'cot_nombre'      => 'Nombre',
            'cot_email'       => 'Email',
            'cot_metros'      => 'm&sup2;',
            'cot_accesorios'  => 'Accesorios',
            'cot_total'       => 'Total Santiago (CLP)',
            'cot_entrega'     => 'Entrega',
            'date'            => 'Fecha',
        );
        return $new_columns;
    }

    /**
     * Render custom column values
     */
    public function render_columns( $column, $post_id ) {
        $meta = get_post_meta( $post_id );

        switch ( $column ) {
            case 'cot_nombre':
                echo esc_html( isset( $meta['_cot_nombre'][0] ) ? $meta['_cot_nombre'][0] : '-' );
                break;
            case 'cot_email':
                $email = isset( $meta['_cot_email'][0] ) ? $meta['_cot_email'][0] : '';
                echo '<a href="mailto:' . esc_attr( $email ) . '">' . esc_html( $email ) . '</a>';
                break;
            case 'cot_metros':
                echo esc_html( isset( $meta['_cot_metros'][0] ) ? $meta['_cot_metros'][0] : '-' );
                break;
            case 'cot_accesorios':
                $accesorios = isset( $meta['_cot_accesorios'][0] ) ? maybe_unserialize( $meta['_cot_accesorios'][0] ) : array();
                if ( ! empty( $accesorios ) && is_array( $accesorios ) ) {
                    $count = count( $accesorios );
                    echo esc_html( $count . ' item' . ( $count > 1 ? 's' : '' ) );
                } else {
                    echo 'Ninguno';
                }
                break;
            case 'cot_total':
                $total = isset( $meta['_cot_total_santiago'][0] ) ? $meta['_cot_total_santiago'][0] : 0;
                echo '$' . esc_html( number_format( intval( $total ), 0, ',', '.' ) );
                break;
            case 'cot_entrega':
                $tipo = isset( $meta['_cot_entrega_tipo'][0] ) ? $meta['_cot_entrega_tipo'][0] : 'santiago';
                if ( $tipo === 'otra' ) {
                    $region = isset( $meta['_cot_entrega_region'][0] ) ? $meta['_cot_entrega_region'][0] : '';
                    echo '<span style="color:#e67e22;">Flete por cotizar</span><br><small>' . esc_html( $region ) . '</small>';
                } else {
                    echo '<span style="color:#27ae60;">Puesto en Santiago</span>';
                }
                break;
        }
    }

    /**
     * Sortable columns
     */
    public function sortable_columns( $columns ) {
        $columns['cot_nombre'] = 'cot_nombre';
        $columns['cot_metros'] = 'cot_metros';
        $columns['cot_total']  = 'cot_total';
        return $columns;
    }
}
