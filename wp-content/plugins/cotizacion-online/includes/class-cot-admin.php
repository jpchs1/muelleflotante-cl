<?php
/**
 * Admin settings page and menu
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class COT_Admin {

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'add_meta_boxes', array( $this, 'add_cotizacion_meta_box' ) );
    }

    /**
     * Add admin menu items
     */
    public function add_admin_menu() {
        // Main menu
        add_menu_page(
            'Cotizaciones Online',
            'Cotizaciones',
            'manage_options',
            'cotizaciones-online',
            array( $this, 'render_cotizaciones_page' ),
            'dashicons-media-spreadsheet',
            30
        );

        // Submenu: All Cotizaciones
        add_submenu_page(
            'cotizaciones-online',
            'Todas las Cotizaciones',
            'Todas las Cotizaciones',
            'manage_options',
            'edit.php?post_type=cotizacion'
        );

        // Submenu: Settings
        add_submenu_page(
            'cotizaciones-online',
            'Configuracion de Precios',
            'Configuracion',
            'manage_options',
            'cot-configuracion',
            array( $this, 'render_settings_page' )
        );
    }

    /**
     * Register settings
     */
    public function register_settings() {
        register_setting( 'cot_settings_group', 'cot_admin_email', array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_email',
            'default'           => 'info@muelleflotante.cl',
        ) );

        register_setting( 'cot_settings_group', 'cot_admin_pricing_password', array(
            'type'              => 'string',
            'sanitize_callback' => array( $this, 'sanitize_admin_pricing_password' ),
            'default'           => '',
        ) );
        register_setting( 'cot_settings_group', 'cot_accesorios', array(
            'type'              => 'array',
            'sanitize_callback' => array( $this, 'sanitize_accesorios' ),
        ) );
    }

    /**
     * Sanitize admin pricing password.
     * If blank, keep existing password.
     */
    public function sanitize_admin_pricing_password( $input ) {
        $input = is_string( $input ) ? trim( $input ) : '';
        if ( $input === '' ) {
            return (string) get_option( 'cot_admin_pricing_password', '' );
        }
        return sanitize_text_field( $input );
    }

    /**
     * Sanitize accessories array
     */
    public function sanitize_accesorios( $input ) {
        if ( ! is_array( $input ) ) {
            return array();
        }

        $sanitized = array();
        foreach ( $input as $item ) {
            if ( empty( $item['nombre'] ) ) {
                continue;
            }
            $sanitized[] = array(
                'nombre' => sanitize_text_field( $item['nombre'] ),
                'precio' => absint( $item['precio'] ),
                'imagen' => ! empty( $item['imagen'] ) ? sanitize_file_name( wp_basename( $item['imagen'] ) ) : '',
                'activo' => isset( $item['activo'] ) ? 1 : 0,
            );
        }
        return $sanitized;
    }

    /**
     * Render main cotizaciones page (redirect to list)
     */
    public function render_cotizaciones_page() {
        include COT_ONLINE_PATH . 'templates/admin-dashboard.php';
    }

    /**
     * Render settings page
     */
    public function render_settings_page() {
        include COT_ONLINE_PATH . 'templates/admin-settings.php';
    }

    /**
     * Add meta box to cotizacion detail view
     */
    public function add_cotizacion_meta_box() {
        add_meta_box(
            'cot_detalle',
            'Detalle de la Cotizacion',
            array( $this, 'render_cotizacion_meta_box' ),
            'cotizacion',
            'normal',
            'high'
        );
    }

    /**
     * Render the cotizacion detail meta box
     */
    public function render_cotizacion_meta_box( $post ) {
        $meta = get_post_meta( $post->ID );

        $nombre      = isset( $meta['_cot_nombre'][0] ) ? $meta['_cot_nombre'][0] : '';
        $email       = isset( $meta['_cot_email'][0] ) ? $meta['_cot_email'][0] : '';
        $telefono    = isset( $meta['_cot_telefono'][0] ) ? $meta['_cot_telefono'][0] : '';
        $empresa     = isset( $meta['_cot_empresa'][0] ) ? $meta['_cot_empresa'][0] : '';
        $rut         = isset( $meta['_cot_rut'][0] ) ? $meta['_cot_rut'][0] : '';
        $metros      = isset( $meta['_cot_metros'][0] ) ? $meta['_cot_metros'][0] : '';
        $precio_m2   = isset( $meta['_cot_precio_m2'][0] ) ? $meta['_cot_precio_m2'][0] : '';
        $subtotal_m2 = isset( $meta['_cot_subtotal_m2'][0] ) ? $meta['_cot_subtotal_m2'][0] : '';
        $accesorios  = isset( $meta['_cot_accesorios'][0] ) ? maybe_unserialize( $meta['_cot_accesorios'][0] ) : array();
        $total_acc   = isset( $meta['_cot_total_accesorios'][0] ) ? $meta['_cot_total_accesorios'][0] : 0;
        $total_stgo  = isset( $meta['_cot_total_santiago'][0] ) ? $meta['_cot_total_santiago'][0] : 0;
        $flete_precio = isset( $meta['_cot_flete_precio'][0] ) ? $meta['_cot_flete_precio'][0] : 0;
        $instalacion_precio = isset( $meta['_cot_instalacion_precio'][0] ) ? $meta['_cot_instalacion_precio'][0] : 0;
        $entrega     = isset( $meta['_cot_entrega_tipo'][0] ) ? $meta['_cot_entrega_tipo'][0] : 'santiago';
        $region      = isset( $meta['_cot_entrega_region'][0] ) ? $meta['_cot_entrega_region'][0] : '';
        $ciudad      = isset( $meta['_cot_entrega_ciudad'][0] ) ? $meta['_cot_entrega_ciudad'][0] : '';
        $direccion   = isset( $meta['_cot_entrega_direccion'][0] ) ? $meta['_cot_entrega_direccion'][0] : '';
        $comentarios = isset( $meta['_cot_entrega_comentarios'][0] ) ? $meta['_cot_entrega_comentarios'][0] : '';

        ?>
        <style>
            .cot-detail-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
            .cot-detail-table th { text-align: left; padding: 8px 12px; background: #f5f5f5; width: 200px; border: 1px solid #ddd; }
            .cot-detail-table td { padding: 8px 12px; border: 1px solid #ddd; }
            .cot-section-title { font-size: 14px; font-weight: 600; margin: 20px 0 10px; padding-bottom: 5px; border-bottom: 2px solid #0073aa; }
            .cot-acc-table { width: 100%; border-collapse: collapse; }
            .cot-acc-table th, .cot-acc-table td { padding: 6px 10px; border: 1px solid #ddd; text-align: left; }
            .cot-acc-table th { background: #f9f9f9; }
            .cot-badge-flete { display: inline-block; background: #e67e22; color: #fff; padding: 3px 10px; border-radius: 3px; font-size: 12px; }
            .cot-badge-santiago { display: inline-block; background: #27ae60; color: #fff; padding: 3px 10px; border-radius: 3px; font-size: 12px; }
        </style>

        <h3 class="cot-section-title">Datos del Cliente</h3>
        <table class="cot-detail-table">
            <tr><th>Nombre</th><td><?php echo esc_html( $nombre ); ?></td></tr>
            <tr><th>Email</th><td><a href="mailto:<?php echo esc_attr( $email ); ?>"><?php echo esc_html( $email ); ?></a></td></tr>
            <tr><th>Telefono / WhatsApp</th><td><?php echo esc_html( $telefono ); ?></td></tr>
            <?php if ( $empresa ) : ?><tr><th>Empresa</th><td><?php echo esc_html( $empresa ); ?></td></tr><?php endif; ?>
            <?php if ( $rut ) : ?><tr><th>RUT</th><td><?php echo esc_html( $rut ); ?></td></tr><?php endif; ?>
        </table>

        <h3 class="cot-section-title">Muelle Flotante</h3>
        <table class="cot-detail-table">
            <tr><th>Metros cuadrados (m&sup2;)</th><td><?php echo esc_html( $metros ); ?> m&sup2;</td></tr>
            <tr><th>Precio por m&sup2;</th><td>$<?php echo esc_html( number_format( intval( $precio_m2 ), 0, ',', '.' ) ); ?> CLP</td></tr>
            <tr><th>Subtotal m&sup2;</th><td><strong>$<?php echo esc_html( number_format( intval( $subtotal_m2 ), 0, ',', '.' ) ); ?> CLP</strong></td></tr>
        </table>

        <?php if ( ! empty( $accesorios ) && is_array( $accesorios ) ) : ?>
        <h3 class="cot-section-title">Accesorios</h3>
        <table class="cot-acc-table">
            <thead>
                <tr><th>Accesorio</th><th>Cantidad</th><th>Precio Unitario</th><th>Subtotal</th></tr>
            </thead>
            <tbody>
                <?php foreach ( $accesorios as $acc ) : ?>
                <tr>
                    <td><?php echo esc_html( $acc['nombre'] ); ?></td>
                    <td><?php echo esc_html( $acc['cantidad'] ); ?></td>
                    <td>$<?php echo esc_html( number_format( intval( $acc['precio'] ), 0, ',', '.' ) ); ?></td>
                    <td>$<?php echo esc_html( number_format( intval( $acc['subtotal'] ), 0, ',', '.' ) ); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr><th colspan="3">Total Accesorios</th><td><strong>$<?php echo esc_html( number_format( intval( $total_acc ), 0, ',', '.' ) ); ?> CLP</strong></td></tr>
            </tfoot>
        </table>
        <?php endif; ?>

        <h3 class="cot-section-title">Totales</h3>
        <table class="cot-detail-table">
            <tr><th>Subtotal m&sup2;</th><td>$<?php echo esc_html( number_format( intval( $subtotal_m2 ), 0, ',', '.' ) ); ?> CLP</td></tr>
            <tr><th>Total Accesorios</th><td>$<?php echo esc_html( number_format( intval( $total_acc ), 0, ',', '.' ) ); ?> CLP</td></tr>
            <?php if ( intval( $flete_precio ) > 0 ) : ?>
                <tr><th>Flete</th><td>$<?php echo esc_html( number_format( intval( $flete_precio ), 0, ',', '.' ) ); ?> CLP</td></tr>
            <?php endif; ?>
            <?php if ( intval( $instalacion_precio ) > 0 ) : ?>
                <tr><th>Instalacion (muertos + fijado a tierra)</th><td>$<?php echo esc_html( number_format( intval( $instalacion_precio ), 0, ',', '.' ) ); ?> CLP</td></tr>
                <tr><th>Armado</th><td><strong style="color:#27ae60;">GRATIS</strong></td></tr>
            <?php endif; ?>
            <tr><th>Total Cotizacion</th><td><strong style="font-size:16px;">$<?php echo esc_html( number_format( intval( $total_stgo ), 0, ',', '.' ) ); ?> CLP</strong></td></tr>
        </table>

        <h3 class="cot-section-title">Entrega</h3>
        <table class="cot-detail-table">
            <tr>
                <th>Tipo de entrega</th>
                <td>
                    <?php if ( intval( $flete_precio ) > 0 ) : ?>
                        <span class="cot-badge-santiago">Flete incluido</span>
                    <?php elseif ( $entrega === 'otra' ) : ?>
                        <span class="cot-badge-flete">Flete por cotizar</span>
                    <?php else : ?>
                        <span class="cot-badge-santiago">Sin flete</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php if ( $entrega === 'otra' ) : ?>
            <tr><th>Region</th><td><?php echo esc_html( $region ); ?></td></tr>
            <tr><th>Ciudad / Comuna</th><td><?php echo esc_html( $ciudad ); ?></td></tr>
            <tr><th>Direccion</th><td><?php echo esc_html( $direccion ); ?></td></tr>
            <?php if ( $comentarios ) : ?><tr><th>Comentarios</th><td><?php echo esc_html( $comentarios ); ?></td></tr><?php endif; ?>
            <?php endif; ?>
        </table>
        <?php
    }
}
