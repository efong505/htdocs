(function($) {
    $(document).on('submit', '.nlde-signup-form', function(e) {
        e.preventDefault();

        var $form = $(this);
        var $btn = $form.find('.nlde-submit');
        var $msg = $form.find('.nlde-message');
        var originalText = $btn.text();

        $btn.prop('disabled', true).text('Please wait...');
        $msg.hide().removeClass('success error');

        $.ajax({
            url: nlde_ajax.url,
            type: 'POST',
            data: {
                action: 'nlde_subscribe',
                nonce: nlde_ajax.nonce,
                nlde_email: $form.find('[name="nlde_email"]').val(),
                nlde_first_name: $form.find('[name="nlde_first_name"]').val() || '',
                nlde_hp: $form.find('[name="nlde_hp"]').val() || '',
                sequence: $form.data('sequence') || ''
            },
            success: function(response) {
                if (response.success) {
                    $msg.addClass('success').text(response.data.message).show();
                    $form.find('input').val('');

                    var redirect = $form.data('redirect');
                    if (redirect) {
                        setTimeout(function() {
                            window.location.href = redirect;
                        }, 1000);
                    }
                } else {
                    $msg.addClass('error').text(response.data.message).show();
                }
            },
            error: function() {
                $msg.addClass('error').text('Something went wrong. Please try again.').show();
            },
            complete: function() {
                $btn.prop('disabled', false).text(originalText);
            }
        });
    });
})(jQuery);
