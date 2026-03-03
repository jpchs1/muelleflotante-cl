<?php
/**
 * Admin settings page template
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$precio_m2          = 290000; // precio fijo
$admin_email        = get_option( 'cot_admin_email', 'info@muelleflotante.cl' );
$accesorios         = get_option( 'cot_accesorios', array() );
?>

<div class="wrap">
    <h1>Configuracion de Precios - Cotizacion Online</h1>

    <form method="post" action="options.php" id="cot-settings-form">
        <?php settings_fields( 'cot_settings_group' ); ?>

        <div class="card" style="max-width:800px;padding:20px;margin-bottom:20px;">
            <h2>Precio del Muelle Flotante</h2>
            <p class="description">Precio fijo por metro cuadrado de muelle flotante.</p>
            <table class="form-table">
                <tr>
                    <th><label for="cot_precio_m2">Precio por m&sup2; (CLP)</label></th>
                    <td>
                        <input type="number" id="cot_precio_m2" value="<?php echo esc_attr( $precio_m2 ); ?>" class="regular-text" min="0" step="1" disabled>
                        <p class="description">Precio unitario por cada metro cuadrado de muelle flotante. Minimo cotizable: 10 m&sup2;, incrementos de 5 m&sup2;.</p>
                    </td>
                </tr>
            </table>
        </div>

        <div class="card" style="max-width:800px;padding:20px;margin-bottom:20px;">
            <h2>Email de Notificacion</h2>
            <table class="form-table">
                <tr>
                    <th><label for="cot_admin_email">Email del administrador</label></th>
                    <td>
                        <input type="email" id="cot_admin_email" name="cot_admin_email" value="<?php echo esc_attr( $admin_email ); ?>" class="regular-text">
                        <p class="description">Email donde se recibiran las nuevas cotizaciones.</p>
                    </td>
                </tr>
            </table>
        </div>

        <div class="card" style="max-width:800px;padding:20px;margin-bottom:20px;">
            <h2>Accesorios</h2>
            <p class="description">Administra la lista de accesorios disponibles para cotizar. Los accesorios inactivos no se mostraran en el formulario.</p>

            <table class="widefat" id="cot-accesorios-table">
                <thead>
                    <tr>
                        <th style="width:30px;">Activo</th>
                        <th>Nombre del Accesorio</th>
                        <th style="width:180px;">Precio Unitario (CLP)</th>
                        <th style="width:180px;">Imagen (archivo)</th>
                        <th style="width:80px;">Acciones</th>
                    </tr>
                </thead>
                <tbody id="cot-accesorios-body">
                    <?php if ( ! empty( $accesorios ) ) : ?>
                        <?php foreach ( $accesorios as $i => $acc ) : ?>
                        <tr class="cot-acc-row">
                            <td><input type="checkbox" name="cot_accesorios[<?php echo esc_attr( $i ); ?>][activo]" value="1" <?php checked( ! empty( $acc['activo'] ) ); ?>></td>
                            <td><input type="text" name="cot_accesorios[<?php echo esc_attr( $i ); ?>][nombre]" value="<?php echo esc_attr( $acc['nombre'] ); ?>" class="regular-text" style="width:100%;"></td>
                            <td><input type="number" name="cot_accesorios[<?php echo esc_attr( $i ); ?>][precio]" value="<?php echo esc_attr( $acc['precio'] ); ?>" class="regular-text" style="width:100%;" min="0"></td>
                            <td><input type="text" name="cot_accesorios[<?php echo esc_attr( $i ); ?>][imagen]" value="<?php echo esc_attr( isset( $acc['imagen'] ) ? $acc['imagen'] : '' ); ?>" class="regular-text" style="width:100%;" placeholder="ej: cornamusa.jpg"></td>
                            <td><button type="button" class="button cot-remove-acc">Eliminar</button></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="5">
                            <button type="button" class="button button-secondary" id="cot-add-acc">+ Agregar Accesorio</button>
                        </td>
                    </tr>
                </tfoot>
            </table>
            <p class="description" style="margin-top:10px;">Las imagenes deben estar en: <code>wp-content/plugins/cotizacion-online/assets/img/accesorios/</code></p>
        </div>

        <?php submit_button( 'Guardar Configuracion' ); ?>
    </form>
</div>

<script>
jQuery(function($) {
    var accIndex = <?php echo esc_js( count( $accesorios ) ); ?>;

    // Add new accessory row
    $('#cot-add-acc').on('click', function() {
        var row = '<tr class="cot-acc-row">' +
            '<td><input type="checkbox" name="cot_accesorios[' + accIndex + '][activo]" value="1" checked></td>' +
            '<td><input type="text" name="cot_accesorios[' + accIndex + '][nombre]" value="" class="regular-text" style="width:100%;" placeholder="Nombre del accesorio"></td>' +
            '<td><input type="number" name="cot_accesorios[' + accIndex + '][precio]" value="0" class="regular-text" style="width:100%;" min="0"></td>' +
            '<td><input type="text" name="cot_accesorios[' + accIndex + '][imagen]" value="" class="regular-text" style="width:100%;" placeholder="ej: nombre.jpg"></td>' +
            '<td><button type="button" class="button cot-remove-acc">Eliminar</button></td>' +
            '</tr>';
        $('#cot-accesorios-body').append(row);
        accIndex++;
    });

    // Remove accessory row
    $(document).on('click', '.cot-remove-acc', function() {
        $(this).closest('tr').remove();
    });

});
</script>
