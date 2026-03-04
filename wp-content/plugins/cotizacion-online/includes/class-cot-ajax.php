<?php
/**
 * AJAX handler for form submission
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class COT_Ajax {

    public function __construct() {
        add_action( 'wp_ajax_cot_submit_cotizacion', array( $this, 'handle_submission' ) );
        add_action( 'wp_ajax_nopriv_cot_submit_cotizacion', array( $this, 'handle_submission' ) );

        add_action( 'wp_ajax_cot_validate_admin_pricing', array( $this, 'validate_admin_pricing' ) );
        add_action( 'wp_ajax_nopriv_cot_validate_admin_pricing', array( $this, 'validate_admin_pricing' ) );
    }

    /**
     * Validate vendor password to unlock admin pricing fields.
     */
    public function validate_admin_pricing() {
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'cot_online_nonce' ) ) {
            wp_send_json_error( array( 'message' => 'Error de seguridad.' ) );
        }

        $password = isset( $_POST['password'] ) ? sanitize_text_field( wp_unslash( $_POST['password'] ) ) : '';

        $expected_password = '';
        if ( defined( 'COT_ADMIN_PRICING_PASSWORD' ) ) {
            $expected_password = (string) COT_ADMIN_PRICING_PASSWORD;
        } else {
            $expected_password = (string) get_option( 'cot_admin_pricing_password', '' );
        }

        if ( empty( $expected_password ) ) {
            wp_send_json_error( array( 'message' => 'Clave no configurada. Configurala en Cotizaciones > Configuracion.' ) );
        }

        if ( hash_equals( $expected_password, $password ) ) {
            wp_send_json_success( array(
                'admin_pricing_nonce' => wp_create_nonce( 'cot_admin_pricing' ),
            ) );
        }

        wp_send_json_error( array( 'message' => 'Clave incorrecta.' ) );
    }

    /**
     * Handle form submission via AJAX
     */
    public function handle_submission() {
        // Verify nonce
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'cot_online_nonce' ) ) {
            wp_send_json_error( array( 'message' => 'Error de seguridad. Por favor recarga la pagina e intenta nuevamente.' ) );
        }

        // Honeypot check
        if ( ! empty( $_POST['website_url'] ) ) {
            wp_send_json_error( array( 'message' => 'Envio no valido.' ) );
        }

        // Validate required fields
        $nombre   = isset( $_POST['nombre'] ) ? sanitize_text_field( wp_unslash( $_POST['nombre'] ) ) : '';
        $email    = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
        $telefono = isset( $_POST['telefono'] ) ? sanitize_text_field( wp_unslash( $_POST['telefono'] ) ) : '';
        $empresa  = isset( $_POST['empresa'] ) ? sanitize_text_field( wp_unslash( $_POST['empresa'] ) ) : '';
        $rut      = isset( $_POST['rut'] ) ? sanitize_text_field( wp_unslash( $_POST['rut'] ) ) : '';
        $metros   = isset( $_POST['metros'] ) ? absint( $_POST['metros'] ) : 0;
        if ( $metros > 0 ) {
            $metros = intval( ceil( $metros / 5 ) * 5 );
        }

        $errors = array();
        if ( empty( $nombre ) ) {
            $errors[] = 'El nombre es obligatorio.';
        }
        if ( empty( $email ) || ! is_email( $email ) ) {
            $errors[] = 'Ingresa un email valido.';
        }
        if ( empty( $telefono ) ) {
            $errors[] = 'El telefono es obligatorio.';
        }
        if ( $metros < 10 ) {
            $errors[] = 'Debes ingresar al menos 10 m2.';
        }

        if ( ! empty( $errors ) ) {
            wp_send_json_error( array( 'message' => implode( '<br>', $errors ) ) );
        }

        // Get tiered pricing
        $precio_m2 = cot_online_get_price_per_m2( $metros );

        // Calculate m2 subtotal
        $subtotal_m2 = $metros * $precio_m2;

        // Process accessories
        $accesorios_config = get_option( 'cot_accesorios', array() );
        $accesorios_seleccionados = array();
        $total_accesorios = 0;

        if ( isset( $_POST['accesorios'] ) && is_array( $_POST['accesorios'] ) ) {
            foreach ( $_POST['accesorios'] as $acc_data ) {
                $acc_id  = isset( $acc_data['id'] ) ? absint( $acc_data['id'] ) : -1;
                $acc_qty = isset( $acc_data['cantidad'] ) ? absint( $acc_data['cantidad'] ) : 0;

                if ( $acc_qty < 1 || ! isset( $accesorios_config[ $acc_id ] ) ) {
                    continue;
                }

                $acc_info  = $accesorios_config[ $acc_id ];
                $acc_price = intval( $acc_info['precio'] );

                // Handle escalera peldaños
                $peldanos = 0;
                if ( stripos( $acc_info['nombre'], 'Escala de acceso' ) !== false ) {
                    $peldanos = isset( $acc_data['peldanos'] ) ? absint( $acc_data['peldanos'] ) : 2;
                    if ( $peldanos < 2 ) $peldanos = 2;
                    if ( $peldanos > 5 ) $peldanos = 5;
                    $acc_price = $acc_price + ( $peldanos - 2 ) * 65000;
                }

                $acc_subtotal = $acc_price * $acc_qty;

                $acc_entry = array(
                    'id'       => $acc_id,
                    'nombre'   => sanitize_text_field( $acc_info['nombre'] ),
                    'cantidad' => $acc_qty,
                    'precio'   => $acc_price,
                    'subtotal' => $acc_subtotal,
                );
                if ( $peldanos > 0 ) {
                    $acc_entry['peldanos'] = $peldanos;
                    $acc_entry['nombre'] = sanitize_text_field( $acc_info['nombre'] ) . ' (' . $peldanos . ' peldanos)';
                }
                $accesorios_seleccionados[] = $acc_entry;

                $total_accesorios += $acc_subtotal;
            }
        }

        // Admin pricing: flete and installation (requires server-validated nonce)
        $flete_precio = 0;
        $instalacion_precio = 0;
        $admin_pricing_nonce = isset( $_POST['admin_pricing_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['admin_pricing_nonce'] ) ) : '';
        if ( ! empty( $admin_pricing_nonce ) && wp_verify_nonce( $admin_pricing_nonce, 'cot_admin_pricing' ) ) {
            $flete_precio = isset( $_POST['flete_precio'] ) ? absint( $_POST['flete_precio'] ) : 0;
            $instalacion_precio = isset( $_POST['instalacion_precio'] ) ? absint( $_POST['instalacion_precio'] ) : 0;
        }

        // Total
        $costo_stgo_calculado = 0;
        $total_santiago = $subtotal_m2 + $total_accesorios + $flete_precio + $instalacion_precio;

        // Delivery info
        $entrega_tipo = isset( $_POST['entrega_tipo'] ) ? sanitize_text_field( wp_unslash( $_POST['entrega_tipo'] ) ) : 'santiago';
        $entrega_region      = '';
        $entrega_ciudad      = '';
        $entrega_direccion   = '';
        $entrega_comentarios = '';

        if ( $entrega_tipo === 'otra' ) {
            $entrega_region      = isset( $_POST['entrega_region'] ) ? sanitize_text_field( wp_unslash( $_POST['entrega_region'] ) ) : '';
            $entrega_ciudad      = isset( $_POST['entrega_ciudad'] ) ? sanitize_text_field( wp_unslash( $_POST['entrega_ciudad'] ) ) : '';
            $entrega_direccion   = isset( $_POST['entrega_direccion'] ) ? sanitize_text_field( wp_unslash( $_POST['entrega_direccion'] ) ) : '';
            $entrega_comentarios = isset( $_POST['entrega_comentarios'] ) ? sanitize_textarea_field( wp_unslash( $_POST['entrega_comentarios'] ) ) : '';
        }

        // Generate quote ID
        $quote_number = intval( get_option( 'cot_ultimo_numero', 0 ) ) + 1;
        update_option( 'cot_ultimo_numero', $quote_number );
        $quote_id = 'COT-' . str_pad( $quote_number, 5, '0', STR_PAD_LEFT );

        // Create CPT entry
        $post_id = wp_insert_post( array(
            'post_type'   => 'cotizacion',
            'post_title'  => $quote_id . ' - ' . $nombre . ' - ' . $metros . ' m2',
            'post_status' => 'publish',
        ) );

        if ( is_wp_error( $post_id ) ) {
            wp_send_json_error( array( 'message' => 'Error al guardar la cotizacion. Intenta nuevamente.' ) );
        }

        // Save meta data
        update_post_meta( $post_id, '_cot_quote_id', $quote_id );
        update_post_meta( $post_id, '_cot_nombre', $nombre );
        update_post_meta( $post_id, '_cot_email', $email );
        update_post_meta( $post_id, '_cot_telefono', $telefono );
        update_post_meta( $post_id, '_cot_empresa', $empresa );
        update_post_meta( $post_id, '_cot_rut', $rut );
        update_post_meta( $post_id, '_cot_metros', $metros );
        update_post_meta( $post_id, '_cot_precio_m2', $precio_m2 );
        update_post_meta( $post_id, '_cot_subtotal_m2', $subtotal_m2 );
        update_post_meta( $post_id, '_cot_accesorios', $accesorios_seleccionados );
        update_post_meta( $post_id, '_cot_total_accesorios', $total_accesorios );
        update_post_meta( $post_id, '_cot_costo_santiago', $costo_stgo_calculado );
        update_post_meta( $post_id, '_cot_total_santiago', $total_santiago );
        update_post_meta( $post_id, '_cot_flete_precio', $flete_precio );
        update_post_meta( $post_id, '_cot_instalacion_precio', $instalacion_precio );
        update_post_meta( $post_id, '_cot_entrega_tipo', $entrega_tipo );
        update_post_meta( $post_id, '_cot_entrega_region', $entrega_region );
        update_post_meta( $post_id, '_cot_entrega_ciudad', $entrega_ciudad );
        update_post_meta( $post_id, '_cot_entrega_direccion', $entrega_direccion );
        update_post_meta( $post_id, '_cot_entrega_comentarios', $entrega_comentarios );

        // Prepare email data
        $email_data = array(
            'quote_id'              => $quote_id,
            'post_id'               => $post_id,
            'nombre'                => $nombre,
            'email'                 => $email,
            'telefono'              => $telefono,
            'empresa'               => $empresa,
            'rut'                   => $rut,
            'metros'                => $metros,
            'precio_m2'             => $precio_m2,
            'subtotal_m2'           => $subtotal_m2,
            'accesorios'            => $accesorios_seleccionados,
            'total_accesorios'      => $total_accesorios,
            'costo_santiago'        => $costo_stgo_calculado,
            'total_santiago'        => $total_santiago,
            'flete_precio'          => $flete_precio,
            'instalacion_precio'    => $instalacion_precio,
            'entrega_tipo'          => $entrega_tipo,
            'entrega_region'        => $entrega_region,
            'entrega_ciudad'        => $entrega_ciudad,
            'entrega_direccion'     => $entrega_direccion,
            'entrega_comentarios'   => $entrega_comentarios,
        );

        // Send emails
        $emailer = new COT_Email();
        $emailer->send_admin_email( $email_data );
        $emailer->send_customer_email( $email_data );

        wp_send_json_success( array(
            'message'  => 'Tu cotizacion ha sido enviada exitosamente. Te contactaremos pronto.',
            'quote_id' => $quote_id,
        ) );
    }
}
