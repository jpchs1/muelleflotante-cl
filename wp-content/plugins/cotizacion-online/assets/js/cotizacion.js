/**
 * Cotizacion Online - Frontend JavaScript
 * Real-time calculation and form handling
 */
(function($) {
    'use strict';

    // Format number as CLP
    function formatCLP(num) {
        return '$' + Math.round(num).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }

    // Build accessories price map
    var accPriceMap = {};
    if (cotData.accesorios && cotData.accesorios.length) {
        for (var i = 0; i < cotData.accesorios.length; i++) {
            accPriceMap[cotData.accesorios[i].id] = cotData.accesorios[i].precio;
        }
    }

    // Set after vendor password unlock (server-validated nonce)
    var adminPricingNonce = null;

    // Get tiered price per m2
    function getPrecioM2(metros) {
        if (metros >= 65) return parseInt(cotData.precio_m2_default) || 185000;
        var tiers = cotData.precio_m2_tiers || {};
        if (tiers[metros]) return parseInt(tiers[metros]);
        // Find closest lower tier
        var keys = Object.keys(tiers).map(Number).sort(function(a, b) { return b - a; });
        for (var i = 0; i < keys.length; i++) {
            if (metros >= keys[i]) return parseInt(tiers[keys[i]]);
        }
        return 265000;
    }

    /**
     * Recalculate all totals in real-time
     */
    function recalculate() {
        var metros = parseInt($('#cot_metros').val()) || 10;
        if (metros < 10) metros = 10;
        metros = Math.ceil(metros / 5) * 5;
        if (parseInt($('#cot_metros').val()) !== metros) {
            $('#cot_metros').val(metros);
        }

        // Tiered price per m2
        var precioM2 = getPrecioM2(metros);
        var subtotalM2 = metros * precioM2;

        // Accessories total
        var totalAcc = 0;
        $('.cot-acc-checkbox:checked').each(function() {
            var accId = $(this).data('acc-id');
            var card = $(this).closest('.cot-accessory-card');
            var qty = parseInt(card.find('.cot-qty-input').val()) || 1;
            var price = accPriceMap[accId] || 0;

            // Check if this is an escalera with step selector
            var stepSelect = card.find('.cot-escalera-select');
            if (stepSelect.length) {
                var steps = parseInt(stepSelect.val()) || 2;
                price = price + (steps - 2) * 65000;
            }

            var lineTotal = price * qty;
            totalAcc += lineTotal;

            // Update line subtotal display
            card.find('.cot-acc-subtotal-value').text(formatCLP(lineTotal));
        });

        // Admin pricing (flete + installation)
        var fletePrecio = 0;
        var instalacionPrecio = 0;
        if (adminPricingNonce) {
            fletePrecio = parseInt($('#cot_flete_precio').val()) || 0;
            instalacionPrecio = parseInt($('#cot_instalacion_precio').val()) || 0;
        }

        // Total
        var totalSantiago = subtotalM2 + totalAcc + fletePrecio + instalacionPrecio;

        // Update UI
        $('#cot-subtotal-m2').text(formatCLP(subtotalM2));
        $('#cot-total-accesorios').text(formatCLP(totalAcc));
        $('#cot-summary-m2').text(metros);
        $('#cot-summary-subtotal-m2').text(formatCLP(subtotalM2));
        $('#cot-summary-accesorios').text(formatCLP(totalAcc));

        // Flete row in summary
        if (adminPricingNonce && fletePrecio > 0) {
            $('#cot-summary-flete-row').show();
            $('#cot-summary-flete').text(formatCLP(fletePrecio));
        } else {
            $('#cot-summary-flete-row').hide();
        }

        // Installation row in summary
        if (adminPricingNonce && instalacionPrecio > 0) {
            $('#cot-summary-instalacion-row').show();
            $('#cot-summary-instalacion').text(formatCLP(instalacionPrecio));
            $('#cot-summary-armado-row').show();
        } else {
            $('#cot-summary-instalacion-row').hide();
            $('#cot-summary-armado-row').hide();
        }

        $('#cot-summary-total').text(formatCLP(totalSantiago));

        // Flete badge
        var entregaTipo = $('input[name="entrega_tipo"]:checked').val();
        if (entregaTipo === 'otra' && fletePrecio <= 0) {
            $('#cot-summary-flete-badge').show();
        } else {
            $('#cot-summary-flete-badge').hide();
        }
    }

    // Initialize on DOM ready
    $(function() {

        // Recalculate on m2 change
        $('#cot_metros').on('input change', function() {
            recalculate();
        });

        // Accessory checkbox toggle
        $(document).on('change', '.cot-acc-checkbox', function() {
            var card = $(this).closest('.cot-accessory-card');
            if ($(this).is(':checked')) {
                card.addClass('cot-acc-selected');
                card.find('.cot-accessory-qty').show();
                card.find('.cot-accessory-subtotal').show();
                card.find('.cot-escalera-steps').show();
            } else {
                card.removeClass('cot-acc-selected');
                card.find('.cot-accessory-qty').hide();
                card.find('.cot-accessory-subtotal').hide();
                card.find('.cot-escalera-steps').hide();
                card.find('.cot-qty-input').val(1);
                card.find('.cot-escalera-select').val(2);
            }
            recalculate();
        });

        // Escalera steps change
        $(document).on('change', '.cot-escalera-select', function() {
            recalculate();
        });

        // Quantity buttons
        $(document).on('click', '.cot-qty-minus', function() {
            var input = $(this).siblings('.cot-qty-input');
            var val = parseInt(input.val()) || 1;
            if (val > 1) {
                input.val(val - 1);
                recalculate();
            }
        });

        $(document).on('click', '.cot-qty-plus', function() {
            var input = $(this).siblings('.cot-qty-input');
            var val = parseInt(input.val()) || 1;
            if (val < 99) {
                input.val(val + 1);
                recalculate();
            }
        });

        $(document).on('input change', '.cot-qty-input', function() {
            var val = parseInt($(this).val()) || 1;
            if (val < 1) $(this).val(1);
            if (val > 99) $(this).val(99);
            recalculate();
        });

        // Admin pricing unlock
        $('#cot-admin-unlock-btn').on('click', function() {
            var $btn = $(this);
            var pwd = $('#cot_admin_password').val();

            $('#cot-admin-password-error').hide();

            if (!pwd) {
                $('#cot_admin_password').focus();
                return;
            }

            $btn.prop('disabled', true);

            $.ajax({
                url: cotData.ajaxurl,
                type: 'POST',
                data: {
                    action: 'cot_validate_admin_pricing',
                    nonce: cotData.nonce,
                    password: pwd
                },
                success: function(response) {
                    if (response && response.success && response.data && response.data.admin_pricing_nonce) {
                        adminPricingNonce = response.data.admin_pricing_nonce;
                        $('#cot-admin-lock').hide();
                        $('#cot-admin-fields').show();
                        $('#cot-admin-password-error').hide();
                        recalculate();
                    } else {
                        $('#cot-admin-password-error').show();
                        $('#cot_admin_password').val('').focus();
                    }
                },
                error: function() {
                    $('#cot-admin-password-error').text('Error de conexion').show();
                },
                complete: function() {
                    $btn.prop('disabled', false);
                }
            });
        });

        // Allow Enter key to unlock
        $('#cot_admin_password').on('keypress', function(e) {
            if (e.which === 13) {
                e.preventDefault();
                $('#cot-admin-unlock-btn').click();
            }
        });

        // Flete & installation price change
        $('#cot_flete_precio, #cot_instalacion_precio').on('input change', function() {
            recalculate();
        });

        // Delivery option toggle
        $('input[name="entrega_tipo"]').on('change', function() {
            var val = $(this).val();

            // Update active class
            $('.cot-delivery-option').removeClass('cot-delivery-active');
            $(this).closest('.cot-delivery-option').addClass('cot-delivery-active');

            // Show/hide fields
            if (val === 'otra') {
                $('#cot-delivery-fields').slideDown(300);
            } else {
                $('#cot-delivery-fields').slideUp(300);
            }

            recalculate();
        });

        // Form submission
        $('#cot-form').on('submit', function(e) {
            e.preventDefault();

            var $form = $(this);
            var $btn = $('#cot-submit-btn');
            var $messages = $('#cot-form-messages');
            var $textSpan = $btn.find('.cot-submit-text');
            var $loadingSpan = $btn.find('.cot-submit-loading');

            // Clear previous messages
            $messages.hide().removeClass('cot-msg-error cot-msg-success');

            // Client-side validation
            var errors = [];
            if (!$('#cot_nombre').val().trim()) errors.push('Nombre es obligatorio');
            if (!$('#cot_email').val().trim() || !isValidEmail($('#cot_email').val())) errors.push('Email valido es obligatorio');
            if (!$('#cot_telefono').val().trim()) errors.push('Telefono es obligatorio');
            if (parseInt($('#cot_metros').val()) < 10) errors.push('Ingrese al menos 10 m\u00B2');

            if (errors.length) {
                $messages.html(errors.join('<br>')).addClass('cot-msg-error').show();
                $('html, body').animate({ scrollTop: $messages.offset().top - 100 }, 400);
                return;
            }

            // Disable button
            $btn.prop('disabled', true);
            $textSpan.hide();
            $loadingSpan.show();

            // Build form data
            var formData = {
                action: 'cot_submit_cotizacion',
                nonce: cotData.nonce,
                nombre: $('#cot_nombre').val().trim(),
                email: $('#cot_email').val().trim(),
                telefono: $('#cot_telefono').val().trim(),
                empresa: $('#cot_empresa').val().trim(),
                rut: $('#cot_rut').val().trim(),
                metros: parseInt($('#cot_metros').val()) || 0,
                website_url: $('#cot_website_url').val(),
                entrega_tipo: $('input[name="entrega_tipo"]:checked').val(),
                accesorios: []
            };

            // Collect selected accessories
            $('.cot-acc-checkbox:checked').each(function() {
                var accId = $(this).data('acc-id');
                var card = $(this).closest('.cot-accessory-card');
                var qty = parseInt(card.find('.cot-qty-input').val()) || 1;
                var accData = {
                    id: accId,
                    cantidad: qty
                };
                // Include escalera steps if applicable
                var stepSelect = card.find('.cot-escalera-select');
                if (stepSelect.length) {
                    accData.peldanos = parseInt(stepSelect.val()) || 2;
                }
                formData.accesorios.push(accData);
            });

            // Delivery fields
            if (formData.entrega_tipo === 'otra') {
                formData.entrega_region = $('#cot_entrega_region').val();
                formData.entrega_ciudad = $('#cot_entrega_ciudad').val();
                formData.entrega_direccion = $('#cot_entrega_direccion').val();
                formData.entrega_comentarios = $('#cot_entrega_comentarios').val();
            }

            // Admin pricing fields (flete + installation)
            if (adminPricingNonce) {
                var fletePrecio = parseInt($('#cot_flete_precio').val()) || 0;
                var instalacionPrecio = parseInt($('#cot_instalacion_precio').val()) || 0;

                formData.admin_pricing_nonce = adminPricingNonce;

                if (fletePrecio > 0) {
                    formData.flete_precio = fletePrecio;
                }
                if (instalacionPrecio > 0) {
                    formData.instalacion_precio = instalacionPrecio;
                }
            }

            // AJAX submit
            $.ajax({
                url: cotData.ajaxurl,
                type: 'POST',
                data: formData,
                success: function(response) {
                    if (response.success) {
                        // Show success state
                        $('#cot-form').hide();
                        $('#cot-success-id').text(response.data.quote_id);
                        $('#cot-success').fadeIn(400);
                        $('html, body').animate({ scrollTop: $('#cot-success').offset().top - 50 }, 400);
                    } else {
                        $messages.html(response.data.message).addClass('cot-msg-error').show();
                        $('html, body').animate({ scrollTop: $messages.offset().top - 100 }, 400);
                    }
                },
                error: function() {
                    $messages.html('Error de conexion. Por favor intenta nuevamente.').addClass('cot-msg-error').show();
                },
                complete: function() {
                    $btn.prop('disabled', false);
                    $textSpan.show();
                    $loadingSpan.hide();
                }
            });
        });

        // Initial calculation
        recalculate();
    });

    // Email validation helper
    function isValidEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }

})(jQuery);
