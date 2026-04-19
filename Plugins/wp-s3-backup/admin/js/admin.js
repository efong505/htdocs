/**
 * WP S3 Backup — Admin JavaScript
 */
(function ($) {
    'use strict';

    $(document).ready(function () {
        // Test Connection button
        $('#wps3b-test-connection').on('click', function (e) {
            e.preventDefault();

            var $btn = $(this);
            var $result = $('#wps3b-test-result');

            $btn.prop('disabled', true);
            $result
                .removeClass('wps3b-test-success wps3b-test-error')
                .text(wps3b_ajax.i18n.testing);

            $.ajax({
                url: wps3b_ajax.url,
                type: 'POST',
                data: {
                    action: 'wps3b_test_connection',
                    nonce: wps3b_ajax.nonce,
                },
                success: function (response) {
                    if (response.success) {
                        $result
                            .addClass('wps3b-test-success')
                            .text(response.data);
                    } else {
                        $result
                            .addClass('wps3b-test-error')
                            .text(response.data);
                    }
                },
                error: function () {
                    $result
                        .addClass('wps3b-test-error')
                        .text('Request failed. Please try again.');
                },
                complete: function () {
                    $btn.prop('disabled', false);
                },
            });
        });

        // Clear masked credential fields on focus (so user can enter new ones)
        $('#wps3b_access_key, #wps3b_secret_key').on('focus', function () {
            var val = $(this).val();
            if (val && val.indexOf('****') !== -1) {
                $(this).val('').attr('type', 'text');
            }
        });

        // Restore mask on blur if empty (user didn't enter anything)
        $('#wps3b_access_key').on('blur', function () {
            if ($(this).val() === '' && $(this).data('masked')) {
                $(this).val($(this).data('masked'));
            }
        });
    });
})(jQuery);
