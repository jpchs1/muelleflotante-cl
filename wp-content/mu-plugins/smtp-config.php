<?php
/**
 * Plugin Name: SMTP Config – Muelle Flotante
 * Description: Asegura que WP Mail SMTP esté activo y fuerza From Email a info@muelleflotante.cl.
 * Version: 1.0.0
 * Author: Muelle Flotante
 *
 * Este mu-plugin se carga automáticamente antes de cualquier plugin normal,
 * garantizando que la configuración SMTP se aplique siempre.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Forzar el encabezado "From" en todos los correos de WordPress,
 * incluso si WP Mail SMTP no procesa algún caso edge.
 */
add_filter( 'wp_mail_from', function ( $from_email ) {
	return 'info@muelleflotante.cl';
}, 999 );

add_filter( 'wp_mail_from_name', function ( $from_name ) {
	return 'Muelle Flotante';
}, 999 );
