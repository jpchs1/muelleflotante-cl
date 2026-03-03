<?php
/**
 * Frontend cotizacion form template
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$regiones_chile = array(
    'Arica y Parinacota',
    'Tarapaca',
    'Antofagasta',
    'Atacama',
    'Coquimbo',
    'Valparaiso',
    'Metropolitana de Santiago',
    'O\'Higgins',
    'Maule',
    'Nuble',
    'Biobio',
    'La Araucania',
    'Los Rios',
    'Los Lagos',
    'Aysen',
    'Magallanes y la Antartica Chilena',
);

$accesorios = get_option( 'cot_accesorios', array() );
$accesorios_activos = array();
if ( is_array( $accesorios ) ) {
    foreach ( $accesorios as $index => $acc ) {
        if ( ! empty( $acc['activo'] ) ) {
            $accesorios_activos[ $index ] = $acc;
        }
    }
}
?>

<div id="cot-form-wrapper" class="cot-form-wrapper">
    <form id="cot-form" class="cot-form" novalidate>

        <!-- Honeypot -->
        <div style="position:absolute;left:-9999px;" aria-hidden="true">
            <label for="cot_website_url">No llenar este campo</label>
            <input type="text" name="website_url" id="cot_website_url" tabindex="-1" autocomplete="off" value="">
        </div>

        <!-- Section 1: Customer Info -->
        <div class="cot-section">
            <div class="cot-section-header">
                <span class="cot-section-number">1</span>
                <h2 class="cot-section-title">Datos de Contacto</h2>
            </div>
            <div class="cot-section-body">
                <div class="cot-row">
                    <div class="cot-col">
                        <label for="cot_nombre" class="cot-label">Nombre Completo <span class="cot-required">*</span></label>
                        <input type="text" id="cot_nombre" name="nombre" class="cot-input" required placeholder="Ingrese su nombre completo">
                    </div>
                    <div class="cot-col">
                        <label for="cot_email" class="cot-label">Correo Electronico <span class="cot-required">*</span></label>
                        <input type="email" id="cot_email" name="email" class="cot-input" required placeholder="ejemplo@correo.com">
                    </div>
                </div>
                <div class="cot-row">
                    <div class="cot-col">
                        <label for="cot_telefono" class="cot-label">Telefono / WhatsApp <span class="cot-required">*</span></label>
                        <input type="tel" id="cot_telefono" name="telefono" class="cot-input" required placeholder="+56 9 XXXX XXXX">
                    </div>
                    <div class="cot-col">
                        <label for="cot_empresa" class="cot-label">Empresa <span class="cot-optional">(opcional)</span></label>
                        <input type="text" id="cot_empresa" name="empresa" class="cot-input" placeholder="Nombre de la empresa">
                    </div>
                </div>
                <div class="cot-row">
                    <div class="cot-col cot-col-half">
                        <label for="cot_rut" class="cot-label">RUT <span class="cot-optional">(opcional)</span></label>
                        <input type="text" id="cot_rut" name="rut" class="cot-input" placeholder="12.345.678-9">
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 2: M2 -->
        <div class="cot-section">
            <div class="cot-section-header">
                <span class="cot-section-number">2</span>
                <h2 class="cot-section-title">Tamano del Muelle Flotante</h2>
            </div>
            <div class="cot-section-body">
                <label for="cot_metros" class="cot-label">Metros cuadrados (m&sup2;) <span class="cot-required">*</span></label>
                <p class="cot-helper">Ingrese los m&sup2; aproximados de su muelle</p>
                <div class="cot-metros-input-wrapper">
                    <input type="number" id="cot_metros" name="metros" class="cot-input cot-input-metros" min="10" step="5" value="10" required>
                    <span class="cot-metros-unit">m&sup2;</span>
                </div>
                <div class="cot-metros-price-preview">
                    <span class="cot-price-label">Subtotal m&sup2;:</span>
                    <span id="cot-subtotal-m2" class="cot-price-value">$0</span>
                </div>
            </div>
        </div>

        <!-- Section 3: Accessories -->
        <div class="cot-section">
            <div class="cot-section-header">
                <span class="cot-section-number">3</span>
                <h2 class="cot-section-title">Accesorios</h2>
            </div>
            <div class="cot-section-body">
                <p class="cot-helper">Seleccione los accesorios que desea incluir y ajuste las cantidades.</p>
                <?php if ( ! empty( $accesorios_activos ) ) : ?>
                <div class="cot-accessories-grid" id="cot-accessories-grid">
                    <?php foreach ( $accesorios_activos as $index => $acc ) : ?>
                    <div class="cot-accessory-card" data-acc-id="<?php echo esc_attr( $index ); ?>">
                            <?php if ( ! empty( $acc['imagen'] ) ) : ?>
                            <div class="cot-accessory-img">
                                <img src="<?php echo esc_url( COT_ONLINE_URL . 'assets/img/accesorios/' . $acc['imagen'] ); ?>" alt="<?php echo esc_attr( $acc['nombre'] ); ?>" loading="lazy">
                            </div>
                            <?php endif; ?>
                            <div class="cot-accessory-check">
                                <input type="checkbox" id="cot_acc_<?php echo esc_attr( $index ); ?>" class="cot-acc-checkbox" data-acc-id="<?php echo esc_attr( $index ); ?>">
                                <label for="cot_acc_<?php echo esc_attr( $index ); ?>" class="cot-accessory-name"><?php echo esc_html( $acc['nombre'] ); ?></label>
                            </div>
                        <div class="cot-accessory-price">
                            $<?php echo esc_html( number_format( intval( $acc['precio'] ), 0, ',', '.' ) ); ?> c/u
                        </div>
                        <div class="cot-accessory-qty" style="display:none;">
                            <button type="button" class="cot-qty-btn cot-qty-minus" aria-label="Disminuir cantidad">-</button>
                            <input type="number" class="cot-qty-input" value="1" min="1" max="99" data-acc-id="<?php echo esc_attr( $index ); ?>">
                            <button type="button" class="cot-qty-btn cot-qty-plus" aria-label="Aumentar cantidad">+</button>
                        </div>
                        <div class="cot-accessory-subtotal" style="display:none;">
                            <span class="cot-acc-subtotal-value">$<?php echo esc_html( number_format( intval( $acc['precio'] ), 0, ',', '.' ) ); ?></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else : ?>
                <p class="cot-no-accessories">No hay accesorios disponibles en este momento.</p>
                <?php endif; ?>
                <div class="cot-accessories-total">
                    <span class="cot-price-label">Total Accesorios:</span>
                    <span id="cot-total-accesorios" class="cot-price-value">$0</span>
                </div>
            </div>
        </div>

        <!-- Section 4: Delivery -->
        <div class="cot-section">
            <div class="cot-section-header">
                <span class="cot-section-number">4</span>
                <h2 class="cot-section-title">Entrega</h2>
            </div>
            <div class="cot-section-body">
                <div class="cot-delivery-options">
                    <label class="cot-delivery-option cot-delivery-active">
                        <input type="radio" name="entrega_tipo" value="santiago" checked class="cot-delivery-radio">
                        <span class="cot-delivery-radio-custom"></span>
                        <div class="cot-delivery-info">
                            <strong>Solo mercaderia (sin flete)</strong>
                            <span class="cot-delivery-desc">Total incluye muelle + accesorios, sin considerar transporte/despacho</span>
                        </div>
                    </label>
                    <label class="cot-delivery-option">
                        <input type="radio" name="entrega_tipo" value="otra" class="cot-delivery-radio">
                        <span class="cot-delivery-radio-custom"></span>
                        <div class="cot-delivery-info">
                            <strong>Cotizar flete a otra ubicacion</strong>
                            <span class="cot-delivery-desc">Solicitar valor de despacho (el total no incluye flete)</span>
                        </div>
                    </label>
                </div>

                <div id="cot-delivery-fields" class="cot-delivery-fields" style="display:none;">
                    <div class="cot-row">
                        <div class="cot-col">
                            <label for="cot_entrega_region" class="cot-label">Region</label>
                            <select id="cot_entrega_region" name="entrega_region" class="cot-input cot-select">
                                <option value="">Seleccione una region</option>
                                <?php foreach ( $regiones_chile as $region ) : ?>
                                <option value="<?php echo esc_attr( $region ); ?>"><?php echo esc_html( $region ); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="cot-col">
                            <label for="cot_entrega_ciudad" class="cot-label">Ciudad / Comuna</label>
                            <input type="text" id="cot_entrega_ciudad" name="entrega_ciudad" class="cot-input" placeholder="Ej: Temuco">
                        </div>
                    </div>
                    <div class="cot-row">
                        <div class="cot-col">
                            <label for="cot_entrega_direccion" class="cot-label">Direccion</label>
                            <input type="text" id="cot_entrega_direccion" name="entrega_direccion" class="cot-input" placeholder="Calle, numero, depto.">
                        </div>
                    </div>
                    <div class="cot-row">
                        <div class="cot-col">
                            <label for="cot_entrega_comentarios" class="cot-label">Comentarios <span class="cot-optional">(opcional)</span></label>
                            <textarea id="cot_entrega_comentarios" name="entrega_comentarios" class="cot-input cot-textarea" rows="3" placeholder="Instrucciones adicionales para el despacho..."></textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 5: Summary -->
        <div class="cot-section cot-section-summary">
            <div class="cot-section-header">
                <span class="cot-section-number">5</span>
                <h2 class="cot-section-title">Resumen de Cotizacion</h2>
            </div>
            <div class="cot-section-body">
                <div class="cot-summary-box">
                    <div class="cot-summary-row">
                        <span class="cot-summary-label">Subtotal Muelle Flotante (<span id="cot-summary-m2">10</span> m&sup2;):</span>
                        <span id="cot-summary-subtotal-m2" class="cot-summary-value">$0</span>
                    </div>
                    <div class="cot-summary-row">
                        <span class="cot-summary-label">Accesorios:</span>
                        <span id="cot-summary-accesorios" class="cot-summary-value">$0</span>
                    </div>
                    <div class="cot-summary-divider"></div>
                    <div class="cot-summary-row cot-summary-total">
                        <span class="cot-summary-label">Total Cotizacion:</span>
                        <span id="cot-summary-total" class="cot-summary-value cot-total-value">$0</span>
                    </div>
                    <div id="cot-summary-flete-badge" class="cot-flete-badge" style="display:none;">
                        Flete a otra ubicacion: <strong>por cotizar</strong>
                    </div>
                </div>

                <div class="cot-summary-note">
                    <p>Los precios son referenciales y estan sujetos a confirmacion y disponibilidad de stock.</p>
                </div>
            </div>
        </div>

        <!-- Submit -->
        <div class="cot-submit-section">
            <div id="cot-form-messages" class="cot-form-messages" style="display:none;"></div>
            <button type="submit" id="cot-submit-btn" class="cot-submit-btn">
                <span class="cot-submit-text">Solicitar Cotizacion</span>
                <span class="cot-submit-loading" style="display:none;">
                    <svg class="cot-spinner" viewBox="0 0 24 24" width="20" height="20"><circle cx="12" cy="12" r="10" fill="none" stroke="currentColor" stroke-width="3" stroke-dasharray="31.42" stroke-dashoffset="10" stroke-linecap="round"><animateTransform attributeName="transform" type="rotate" from="0 12 12" to="360 12 12" dur="0.8s" repeatCount="indefinite"/></circle></svg>
                    Enviando...
                </span>
            </button>
        </div>

    </form>

    <!-- Success message (hidden by default) -->
    <div id="cot-success" class="cot-success" style="display:none;">
        <div class="cot-success-icon">
            <svg viewBox="0 0 24 24" width="60" height="60" fill="none" stroke="#27ae60" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M9 12l2 2 4-4"/></svg>
        </div>
        <h2 class="cot-success-title">Cotizacion enviada exitosamente</h2>
        <p class="cot-success-text">Tu cotizacion <strong id="cot-success-id"></strong> ha sido recibida. Te enviaremos un resumen a tu correo electronico y un ejecutivo se pondra en contacto contigo a la brevedad.</p>
        <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="cot-success-btn">Volver al inicio</a>
    </div>
</div>
