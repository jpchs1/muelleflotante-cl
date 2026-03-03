<?php
/**
 * Admin dashboard page
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Get stats
$total_cotizaciones = wp_count_posts( 'cotizacion' );
$total_count = isset( $total_cotizaciones->publish ) ? $total_cotizaciones->publish : 0;

// Get recent cotizaciones
$recent = get_posts( array(
    'post_type'      => 'cotizacion',
    'posts_per_page' => 5,
    'orderby'        => 'date',
    'order'          => 'DESC',
) );

// Get page URL
$page = get_page_by_path( 'cotizaciononline' );
$page_url = $page ? get_permalink( $page ) : '';
?>

<div class="wrap">
    <h1>Cotizaciones Online</h1>

    <div style="display:flex;gap:20px;flex-wrap:wrap;margin-top:20px;">
        <!-- Stats Card -->
        <div class="card" style="flex:1;min-width:250px;padding:20px;">
            <h2 style="margin-top:0;">Resumen</h2>
            <p style="font-size:36px;font-weight:bold;color:#1a3a5c;margin:10px 0;"><?php echo esc_html( $total_count ); ?></p>
            <p style="color:#666;">Cotizaciones recibidas</p>
            <p>
                <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=cotizacion' ) ); ?>" class="button button-primary">Ver todas las cotizaciones</a>
            </p>
        </div>

        <!-- Quick Links -->
        <div class="card" style="flex:1;min-width:250px;padding:20px;">
            <h2 style="margin-top:0;">Accesos Rapidos</h2>
            <ul style="list-style:none;padding:0;">
                <li style="margin-bottom:10px;">
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=cot-configuracion' ) ); ?>" class="button">Configurar Precios y Accesorios</a>
                </li>
                <?php if ( $page_url ) : ?>
                <li style="margin-bottom:10px;">
                    <a href="<?php echo esc_url( $page_url ); ?>" class="button" target="_blank">Ver Pagina de Cotizacion</a>
                </li>
                <?php endif; ?>
                <li>
                    <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=cotizacion' ) ); ?>" class="button">Listado de Cotizaciones</a>
                </li>
            </ul>
        </div>
    </div>

    <!-- Recent -->
    <?php if ( ! empty( $recent ) ) : ?>
    <div class="card" style="max-width:800px;padding:20px;margin-top:20px;">
        <h2 style="margin-top:0;">Cotizaciones Recientes</h2>
        <table class="widefat striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Cliente</th>
                    <th>m&sup2;</th>
                    <th>Total Santiago</th>
                    <th>Fecha</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $recent as $cot ) :
                    $quote_id = get_post_meta( $cot->ID, '_cot_quote_id', true );
                    $nombre   = get_post_meta( $cot->ID, '_cot_nombre', true );
                    $metros   = get_post_meta( $cot->ID, '_cot_metros', true );
                    $total    = get_post_meta( $cot->ID, '_cot_total_santiago', true );
                ?>
                <tr>
                    <td><strong><?php echo esc_html( $quote_id ); ?></strong></td>
                    <td><?php echo esc_html( $nombre ); ?></td>
                    <td><?php echo esc_html( $metros ); ?> m&sup2;</td>
                    <td>$<?php echo esc_html( number_format( intval( $total ), 0, ',', '.' ) ); ?></td>
                    <td><?php echo esc_html( get_the_date( 'd/m/Y H:i', $cot ) ); ?></td>
                    <td><a href="<?php echo esc_url( get_edit_post_link( $cot->ID ) ); ?>">Ver</a></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <!-- Documentation -->
    <div class="card" style="max-width:800px;padding:20px;margin-top:20px;">
        <h2 style="margin-top:0;">Documentacion</h2>
        <h3>Como funciona</h3>
        <ol>
            <li>El usuario visita <code>/cotizaciononline/</code> y llena el formulario.</li>
            <li>El sistema calcula el precio en tiempo real usando los precios configurados.</li>
            <li>Al enviar, se guarda como una cotizacion y se envian 2 emails (admin + cliente).</li>
        </ol>

        <h3>Como editar precios</h3>
        <ul>
            <li><strong>Precio por m&sup2;:</strong> Ve a <a href="<?php echo esc_url( admin_url( 'admin.php?page=cot-configuracion' ) ); ?>">Configuracion</a> y modifica el "Precio por m&sup2;".</li>
            <li><strong>Costo logistico Santiago:</strong> En Configuracion, ajusta el "Costo Logistico Puesto en Santiago". Puede ser un monto por m&sup2; o un porcentaje sobre el subtotal.</li>
            <li><strong>Accesorios:</strong> En Configuracion, agrega, edita o elimina accesorios. Marca como "Activo" los que desees mostrar en el formulario.</li>
        </ul>

        <h3>Donde se almacena la informacion</h3>
        <ul>
            <li>Precios: <code>wp_options</code> (opciones <code>cot_precio_m2</code>, <code>cot_costo_santiago</code>, <code>cot_accesorios</code>).</li>
            <li>Cotizaciones: Custom Post Type <code>cotizacion</code> con meta datos asociados.</li>
        </ul>
    </div>
</div>
