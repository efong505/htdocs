(function($) {
    'use strict';

    var selectedTierId = null;
    var currentCountry = '';
    var currentVatNumber = '';
    var internalOrderId = null;

    function updateSummary() {
        var $tier = $('input[name="wplp_tier"]:checked');
        if (!$tier.length) return;

        selectedTierId = $tier.val();
        var price = parseFloat($tier.data('price'));
        $('#wplp-subtotal').text('$' + price.toFixed(2));

        currentCountry = $('#wplp-country').val();
        currentVatNumber = $('#wplp-vat-number').val();

        // Show/hide VAT field for EU countries
        var euCountries = ['AT','BE','BG','HR','CY','CZ','DK','EE','FI','FR','DE','GR','HU','IE','IT','LV','LT','LU','MT','NL','PL','PT','RO','SK','SI','ES','SE'];
        if (euCountries.indexOf(currentCountry) !== -1) {
            $('.wplp-vat-field').show();
        } else {
            $('.wplp-vat-field').hide();
        }

        if (!currentCountry) {
            $('.wplp-summary-tax').hide();
            $('.wplp-reverse-charge').hide();
            $('#wplp-total').text('$' + price.toFixed(2));
            return;
        }

        // Calculate tax via API
        $.post(wplp_checkout.api_url + 'calculate-tax', {
            tier_id: selectedTierId,
            country: currentCountry,
            vat_number: currentVatNumber
        }, function(res) {
            if (res.tax_rate > 0) {
                $('#wplp-tax-label').text('VAT (' + res.tax_rate + '%)');
                $('#wplp-tax-amount').text('$' + res.tax_amount);
                $('.wplp-summary-tax').show();
                $('.wplp-reverse-charge').hide();
            } else if (res.reverse_charge) {
                $('.wplp-summary-tax').hide();
                $('.wplp-reverse-charge').show();
            } else {
                $('.wplp-summary-tax').hide();
                $('.wplp-reverse-charge').hide();
            }
            $('#wplp-total').text('$' + res.total);
        });
    }

    $(document).ready(function() {
        $('input[name="wplp_tier"]').on('change', updateSummary);
        $('#wplp-country').on('change', updateSummary);
        $('#wplp-vat-number').on('blur', updateSummary);

        // Initial update
        updateSummary();

        // PayPal buttons
        if (typeof paypal !== 'undefined') {
            paypal.Buttons({
                createOrder: function() {
                    var tierId = $('input[name="wplp_tier"]:checked').val();
                    var country = $('#wplp-country').val();
                    var vatNumber = $('#wplp-vat-number').val();

                    if (!tierId) { alert('Please select a plan.'); return; }
                    if (!country) { alert('Please select your country.'); return; }

                    return fetch(wplp_checkout.api_url + 'create-order', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            tier_id: tierId,
                            billing_country: country,
                            vat_number: vatNumber,
                            nonce: wplp_checkout.nonce
                        })
                    })
                    .then(function(res) { return res.json(); })
                    .then(function(data) {
                        if (data.error) { alert(data.error); return; }
                        // Store our internal order ID for the capture step
                        internalOrderId = data.order_id;
                        return data.paypal_order_id;
                    });
                },
                onApprove: function(data) {
                    return fetch(wplp_checkout.api_url + 'capture-order', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            paypal_order_id: data.orderID,
                            order_id: internalOrderId,
                            nonce: wplp_checkout.nonce
                        })
                    })
                    .then(function(res) { return res.json(); })
                    .then(function(result) {
                        if (result.success && result.redirect_url) {
                            window.location.href = result.redirect_url;
                        } else {
                            alert('Payment processing error. Please contact support.');
                        }
                    });
                },
                onError: function(err) {
                    console.error('PayPal error:', err);
                    alert('Payment failed. Please try again.');
                }
            }).render('#paypal-button-container');
        }
    });
})(jQuery);
