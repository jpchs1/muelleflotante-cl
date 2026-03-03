<?php
/**
 * Email handler for cotizaciones
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class COT_Email {

    /**
     * Format a number as CLP currency
     */
    private function format_clp( $number ) {
        return '$' . number_format( intval( $number ), 0, ',', '.' );
    }

    /**
     * Get email header HTML
     */
    private function get_header() {
        return '
        <div style="max-width:600px;margin:0 auto;font-family:Arial,Helvetica,sans-serif;color:#333;">
            <div style="background:#1a3a5c;padding:25px;text-align:center;">
                <h1 style="color:#fff;margin:0;font-size:22px;">Muelle Flotante</h1>
                <p style="color:#f0c040;margin:5px 0 0;font-size:13px;">muelleflotante.cl</p>
            </div>
        ';
    }

    /**
     * Get email footer HTML
     */
    private function get_footer() {
        return '
            <div style="background:#f5f5f5;padding:15px;text-align:center;font-size:12px;color:#888;border-top:2px solid #1a3a5c;">
                <p style="margin:0;">&copy; ' . gmdate( 'Y' ) . ' Muelle Flotante Chile | <a href="https://muelleflotante.cl" style="color:#1a3a5c;">muelleflotante.cl</a></p>
                <p style="margin:5px 0 0;">info@muelleflotante.cl | +56 9 40211459</p>
            </div>
        </div>';
    }

    /**
     * Build accessories table HTML
     */
    private function build_accessories_table( $accesorios ) {
        if ( empty( $accesorios ) || ! is_array( $accesorios ) ) {
            return '<p style="color:#888;font-style:italic;">Sin accesorios seleccionados</p>';
        }

        $html = '<table style="width:100%;border-collapse:collapse;margin:10px 0;">';
        $html .= '<tr style="background:#1a3a5c;color:#fff;">';
        $html .= '<th style="padding:8px;text-align:left;font-size:13px;">Accesorio</th>';
        $html .= '<th style="padding:8px;text-align:center;font-size:13px;">Cant.</th>';
        $html .= '<th style="padding:8px;text-align:right;font-size:13px;">P. Unit.</th>';
        $html .= '<th style="padding:8px;text-align:right;font-size:13px;">Subtotal</th>';
        $html .= '</tr>';

        $i = 0;
        foreach ( $accesorios as $acc ) {
            $bg = ( $i % 2 === 0 ) ? '#fff' : '#f9f9f9';
            $html .= '<tr style="background:' . $bg . ';">';
            $html .= '<td style="padding:6px 8px;border-bottom:1px solid #eee;font-size:13px;">' . esc_html( $acc['nombre'] ) . '</td>';
            $html .= '<td style="padding:6px 8px;border-bottom:1px solid #eee;text-align:center;font-size:13px;">' . esc_html( $acc['cantidad'] ) . '</td>';
            $html .= '<td style="padding:6px 8px;border-bottom:1px solid #eee;text-align:right;font-size:13px;">' . $this->format_clp( $acc['precio'] ) . '</td>';
            $html .= '<td style="padding:6px 8px;border-bottom:1px solid #eee;text-align:right;font-size:13px;">' . $this->format_clp( $acc['subtotal'] ) . '</td>';
            $html .= '</tr>';
            $i++;
        }

        $html .= '</table>';
        return $html;
    }

    /**
     * Send admin notification email
     */
    public function send_admin_email( $data ) {
        $admin_email = get_option( 'cot_admin_email', 'info@muelleflotante.cl' );
        $subject     = 'Nueva Cotizacion Online - ' . $data['nombre'] . ' - ' . $data['metros'] . ' m2';

        $admin_url = admin_url( 'post.php?post=' . $data['post_id'] . '&action=edit' );

        $body = $this->get_header();
        $body .= '<div style="padding:25px;">';

        // Quote ID
        $body .= '<div style="background:#e8f4fd;border-left:4px solid #1a3a5c;padding:12px 15px;margin-bottom:20px;">';
        $body .= '<strong style="font-size:16px;">Cotizacion: ' . esc_html( $data['quote_id'] ) . '</strong>';
        $body .= '</div>';

        // Customer info
        $body .= '<h2 style="color:#1a3a5c;font-size:16px;border-bottom:1px solid #ddd;padding-bottom:8px;">Datos del Cliente</h2>';
        $body .= '<table style="width:100%;margin-bottom:20px;">';
        $body .= '<tr><td style="padding:4px 0;font-size:13px;color:#666;width:130px;">Nombre:</td><td style="padding:4px 0;font-size:13px;"><strong>' . esc_html( $data['nombre'] ) . '</strong></td></tr>';
        $body .= '<tr><td style="padding:4px 0;font-size:13px;color:#666;">Email:</td><td style="padding:4px 0;font-size:13px;"><a href="mailto:' . esc_attr( $data['email'] ) . '">' . esc_html( $data['email'] ) . '</a></td></tr>';
        $body .= '<tr><td style="padding:4px 0;font-size:13px;color:#666;">Telefono:</td><td style="padding:4px 0;font-size:13px;">' . esc_html( $data['telefono'] ) . '</td></tr>';
        if ( ! empty( $data['empresa'] ) ) {
            $body .= '<tr><td style="padding:4px 0;font-size:13px;color:#666;">Empresa:</td><td style="padding:4px 0;font-size:13px;">' . esc_html( $data['empresa'] ) . '</td></tr>';
        }
        if ( ! empty( $data['rut'] ) ) {
            $body .= '<tr><td style="padding:4px 0;font-size:13px;color:#666;">RUT:</td><td style="padding:4px 0;font-size:13px;">' . esc_html( $data['rut'] ) . '</td></tr>';
        }
        $body .= '</table>';

        // M2
        $body .= '<h2 style="color:#1a3a5c;font-size:16px;border-bottom:1px solid #ddd;padding-bottom:8px;">Muelle Flotante</h2>';
        $body .= '<table style="width:100%;margin-bottom:15px;">';
        $body .= '<tr><td style="padding:4px 0;font-size:13px;color:#666;width:160px;">Metros cuadrados:</td><td style="padding:4px 0;font-size:13px;"><strong>' . esc_html( $data['metros'] ) . ' m&sup2;</strong></td></tr>';
        $body .= '<tr><td style="padding:4px 0;font-size:13px;color:#666;">Precio por m&sup2;:</td><td style="padding:4px 0;font-size:13px;">' . $this->format_clp( $data['precio_m2'] ) . ' CLP</td></tr>';
        $body .= '<tr><td style="padding:4px 0;font-size:13px;color:#666;">Subtotal m&sup2;:</td><td style="padding:4px 0;font-size:13px;"><strong>' . $this->format_clp( $data['subtotal_m2'] ) . ' CLP</strong></td></tr>';
        $body .= '</table>';

        // Accessories
        $body .= '<h2 style="color:#1a3a5c;font-size:16px;border-bottom:1px solid #ddd;padding-bottom:8px;">Accesorios</h2>';
        $body .= $this->build_accessories_table( $data['accesorios'] );
        if ( ! empty( $data['accesorios'] ) ) {
            $body .= '<p style="text-align:right;font-size:14px;"><strong>Total Accesorios: ' . $this->format_clp( $data['total_accesorios'] ) . ' CLP</strong></p>';
        }

        // Totals
        $body .= '<div style="background:#f0f7ec;border:2px solid #27ae60;border-radius:6px;padding:15px;margin:20px 0;">';
        $body .= '<table style="width:100%;">';
        $body .= '<tr><td style="padding:4px 0;font-size:13px;">Subtotal m&sup2;:</td><td style="padding:4px 0;font-size:13px;text-align:right;">' . $this->format_clp( $data['subtotal_m2'] ) . '</td></tr>';
        $body .= '<tr><td style="padding:4px 0;font-size:13px;">Accesorios:</td><td style="padding:4px 0;font-size:13px;text-align:right;">' . $this->format_clp( $data['total_accesorios'] ) . '</td></tr>';
        $body .= '<tr><td style="padding:4px 0;font-size:13px;">Costo logistico Santiago:</td><td style="padding:4px 0;font-size:13px;text-align:right;">' . $this->format_clp( $data['costo_santiago'] ) . '</td></tr>';
        $body .= '<tr><td colspan="2" style="border-top:1px solid #27ae60;padding-top:8px;"></td></tr>';
        $body .= '<tr><td style="padding:4px 0;font-size:16px;"><strong>Total Puesto en Santiago:</strong></td><td style="padding:4px 0;font-size:16px;text-align:right;color:#27ae60;"><strong>' . $this->format_clp( $data['total_santiago'] ) . ' CLP</strong></td></tr>';
        $body .= '</table>';
        $body .= '</div>';

        // Delivery
        $body .= '<h2 style="color:#1a3a5c;font-size:16px;border-bottom:1px solid #ddd;padding-bottom:8px;">Entrega</h2>';
        if ( $data['entrega_tipo'] === 'otra' ) {
            $body .= '<div style="background:#fdf2e9;border-left:4px solid #e67e22;padding:12px 15px;margin-bottom:15px;">';
            $body .= '<strong style="color:#e67e22;">Flete a otra ubicacion: POR COTIZAR</strong>';
            $body .= '</div>';
            $body .= '<table style="width:100%;margin-bottom:15px;">';
            $body .= '<tr><td style="padding:4px 0;font-size:13px;color:#666;width:130px;">Region:</td><td style="padding:4px 0;font-size:13px;">' . esc_html( $data['entrega_region'] ) . '</td></tr>';
            $body .= '<tr><td style="padding:4px 0;font-size:13px;color:#666;">Ciudad/Comuna:</td><td style="padding:4px 0;font-size:13px;">' . esc_html( $data['entrega_ciudad'] ) . '</td></tr>';
            $body .= '<tr><td style="padding:4px 0;font-size:13px;color:#666;">Direccion:</td><td style="padding:4px 0;font-size:13px;">' . esc_html( $data['entrega_direccion'] ) . '</td></tr>';
            if ( ! empty( $data['entrega_comentarios'] ) ) {
                $body .= '<tr><td style="padding:4px 0;font-size:13px;color:#666;">Comentarios:</td><td style="padding:4px 0;font-size:13px;">' . esc_html( $data['entrega_comentarios'] ) . '</td></tr>';
            }
            $body .= '</table>';
        } else {
            $body .= '<div style="background:#e8f8f0;border-left:4px solid #27ae60;padding:12px 15px;">';
            $body .= '<strong style="color:#27ae60;">Puesto en Santiago - Incluido en el total</strong>';
            $body .= '</div>';
        }

        // Admin link
        $body .= '<div style="margin-top:25px;text-align:center;">';
        $body .= '<a href="' . esc_url( $admin_url ) . '" style="display:inline-block;background:#1a3a5c;color:#fff;padding:10px 25px;text-decoration:none;border-radius:4px;font-size:14px;">Ver en el administrador</a>';
        $body .= '</div>';

        $body .= '</div>';
        $body .= $this->get_footer();

        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: Muelle Flotante <info@muelleflotante.cl>',
        );

        wp_mail( $admin_email, $subject, $body, $headers );
    }

    /**
     * Send customer confirmation email
     */
    public function send_customer_email( $data ) {
        $subject = 'Recibimos tu solicitud de cotizacion - Muelleflotante.cl';

        $body = $this->get_header();
        $body .= '<div style="padding:25px;">';

        // Greeting
        $body .= '<h2 style="color:#1a3a5c;font-size:18px;margin-top:0;">Hola ' . esc_html( $data['nombre'] ) . ',</h2>';
        $body .= '<p style="font-size:14px;line-height:1.6;color:#555;">Hemos recibido tu solicitud de cotizacion. A continuacion te compartimos un resumen:</p>';

        // Quote ID
        $body .= '<div style="background:#e8f4fd;border-left:4px solid #1a3a5c;padding:12px 15px;margin-bottom:20px;">';
        $body .= '<strong>N&deg; de Cotizacion: ' . esc_html( $data['quote_id'] ) . '</strong>';
        $body .= '</div>';

        // Summary
        $body .= '<h3 style="color:#1a3a5c;font-size:15px;border-bottom:1px solid #ddd;padding-bottom:8px;">Resumen de tu Cotizacion</h3>';
        $body .= '<table style="width:100%;margin-bottom:15px;">';
        $body .= '<tr><td style="padding:4px 0;font-size:13px;color:#666;">Muelle flotante:</td><td style="padding:4px 0;font-size:13px;text-align:right;"><strong>' . esc_html( $data['metros'] ) . ' m&sup2;</strong></td></tr>';
        $body .= '<tr><td style="padding:4px 0;font-size:13px;color:#666;">Subtotal m&sup2;:</td><td style="padding:4px 0;font-size:13px;text-align:right;">' . $this->format_clp( $data['subtotal_m2'] ) . '</td></tr>';
        $body .= '</table>';

        // Accessories
        if ( ! empty( $data['accesorios'] ) ) {
            $body .= '<h3 style="color:#1a3a5c;font-size:15px;border-bottom:1px solid #ddd;padding-bottom:8px;">Accesorios Seleccionados</h3>';
            $body .= $this->build_accessories_table( $data['accesorios'] );
            $body .= '<p style="text-align:right;font-size:13px;"><strong>Total Accesorios: ' . $this->format_clp( $data['total_accesorios'] ) . ' CLP</strong></p>';
        }

        // Total
        $body .= '<div style="background:#f0f7ec;border:2px solid #27ae60;border-radius:6px;padding:15px;margin:20px 0;text-align:center;">';
        $body .= '<p style="margin:0 0 5px;font-size:13px;color:#555;">Total Mercaderia Puesta en Santiago</p>';
        $body .= '<p style="margin:0;font-size:22px;color:#27ae60;"><strong>' . $this->format_clp( $data['total_santiago'] ) . ' CLP</strong></p>';
        $body .= '</div>';

        // Freight note
        if ( $data['entrega_tipo'] === 'otra' ) {
            $body .= '<div style="background:#fdf2e9;border-left:4px solid #e67e22;padding:12px 15px;margin-bottom:20px;">';
            $body .= '<p style="margin:0;font-size:13px;"><strong style="color:#e67e22;">Flete a otra ubicacion solicitado</strong></p>';
            $body .= '<p style="margin:5px 0 0;font-size:13px;color:#555;">Te contactaremos con el valor del flete a ' . esc_html( $data['entrega_region'] ) . '.</p>';
            $body .= '</div>';
        }

        // Disclaimer
        $body .= '<div style="background:#f9f9f9;padding:12px 15px;border-radius:4px;margin:20px 0;">';
        $body .= '<p style="margin:0;font-size:11px;color:#888;line-height:1.5;">';
        $body .= '<strong>Nota:</strong> Los precios son referenciales y estan sujetos a confirmacion, disponibilidad de stock y condiciones vigentes al momento de la compra. ';
        $body .= 'Un ejecutivo se pondra en contacto contigo para confirmar tu cotizacion.';
        $body .= '</p>';
        $body .= '</div>';

        // Contact
        $body .= '<div style="text-align:center;margin-top:20px;">';
        $body .= '<p style="font-size:13px;color:#555;">Si tienes preguntas, contactanos:</p>';
        $body .= '<p style="font-size:14px;"><a href="mailto:info@muelleflotante.cl" style="color:#1a3a5c;">info@muelleflotante.cl</a> | +56 9 40211459</p>';
        $body .= '</div>';

        $body .= '</div>';
        $body .= $this->get_footer();

        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: Muelle Flotante <info@muelleflotante.cl>',
        );

        wp_mail( $data['email'], $subject, $body, $headers );
    }
}
