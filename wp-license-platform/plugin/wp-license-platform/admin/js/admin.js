(function($) {
    'use strict';
    $(document).ready(function() {
        // Add tier row
        var tierIndex = $('#wplp-tiers-table tbody tr').length;
        $('#wplp-add-tier').on('click', function() {
            var row = '<tr>' +
                '<td><input type="hidden" name="tier_id[]" value="0" /><input type="text" name="tier_name[]" value="" class="small-text" placeholder="personal" /></td>' +
                '<td><input type="text" name="tier_display[]" value="" placeholder="Personal" /></td>' +
                '<td><input type="number" name="tier_price[]" value="49" step="0.01" class="small-text" /></td>' +
                '<td><input type="number" name="tier_sites[]" value="1" class="small-text" /></td>' +
                '<td><input type="checkbox" name="tier_featured[]" value="' + tierIndex + '" /></td>' +
                '<td><button type="button" class="button wplp-remove-tier">&times;</button></td>' +
                '</tr>';
            $('#wplp-tiers-table tbody').append(row);
            tierIndex++;
        });

        $(document).on('click', '.wplp-remove-tier', function() {
            $(this).closest('tr').remove();
        });

        // Test PayPal
        $('#wplp-test-paypal').on('click', function() {
            var $btn = $(this), $result = $('#wplp-test-result');
            $btn.prop('disabled', true);
            $result.text('Testing...');
            $.post(wplp_admin.ajax_url, { action: 'wplp_test_paypal', nonce: wplp_admin.nonce }, function(res) {
                $result.text(res.success ? res.data : res.data).css('color', res.success ? '#00a32a' : '#d63638');
                $btn.prop('disabled', false);
            });
        });

        // ─── File Upload ──────────────────────────────

        // Browse button triggers hidden file input
        $('#wplp-browse-btn').on('click', function() {
            $('#wplp-file-input').trigger('click');
        });

        // Replace button shows upload zone
        $(document).on('click', '#wplp-replace-file', function() {
            $('#wplp-upload-zone').show();
            $('#wplp-file-input').trigger('click');
        });

        // File selected — upload via AJAX
        $('#wplp-file-input').on('change', function() {
            var file = this.files[0];
            if (!file) return;

            // Client-side validation
            if (!file.name.match(/\.zip$/i)) {
                showUploadStatus('Only .zip files are allowed.', true);
                return;
            }
            if (file.size > 104857600) {
                showUploadStatus('File too large. Maximum 100 MB.', true);
                return;
            }

            var formData = new FormData();
            formData.append('action', 'wplp_upload_product_file');
            formData.append('nonce', wplp_admin.nonce);
            formData.append('product_zip', file);

            $('#wplp-browse-btn').prop('disabled', true);
            $('#wplp-upload-progress').show();
            showUploadStatus('Uploading...', false);

            $.ajax({
                url: wplp_admin.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                xhr: function() {
                    var xhr = new XMLHttpRequest();
                    xhr.upload.addEventListener('progress', function(e) {
                        if (e.lengthComputable) {
                            var pct = Math.round((e.loaded / e.total) * 100);
                            $('#wplp-progress-bar').css('width', pct + '%');
                            $('#wplp-progress-text').text(pct + '%');
                        }
                    });
                    return xhr;
                },
                success: function(res) {
                    $('#wplp-upload-progress').hide();
                    $('#wplp-browse-btn').prop('disabled', false);
                    $('#wplp-progress-bar').css('width', '0%');

                    if (res.success) {
                        // Update hidden field
                        $('#product_file_path').val(res.data.file_path);

                        // Show file info
                        $('#wplp-file-name').text(res.data.file_name);
                        $('#wplp-file-size').text('(' + res.data.file_size + ')');
                        $('#wplp-current-file').removeClass('wplp-file-missing').show();
                        $('#wplp-upload-zone').hide();

                        showUploadStatus('Uploaded successfully!', false, true);
                    } else {
                        showUploadStatus(res.data, true);
                    }

                    // Reset file input
                    $('#wplp-file-input').val('');
                },
                error: function() {
                    $('#wplp-upload-progress').hide();
                    $('#wplp-browse-btn').prop('disabled', false);
                    showUploadStatus('Upload failed. Please try again.', true);
                    $('#wplp-file-input').val('');
                }
            });
        });

        // Remove/delete file
        $(document).on('click', '.wplp-delete-file', function() {
            var filePath = $('#product_file_path').val();
            if (!filePath) return;

            if (!confirm('Remove this file? The file will be deleted from the server.')) return;

            $.post(wplp_admin.ajax_url, {
                action: 'wplp_delete_product_file',
                nonce: wplp_admin.nonce,
                file_path: filePath
            }, function(res) {
                $('#product_file_path').val('');
                $('#wplp-current-file').hide();
                $('#wplp-upload-zone').show();
                showUploadStatus('', false);
            });
        });

        function showUploadStatus(msg, isError, isSuccess) {
            var $el = $('#wplp-upload-status');
            $el.text(msg)
               .removeClass('wplp-upload-error wplp-upload-success')
               .addClass(isError ? 'wplp-upload-error' : (isSuccess ? 'wplp-upload-success' : ''));
        }

        // ─── Order Status Change ──────────────────────

        $(document).on('change', '.wplp-order-status-select', function() {
            var $sel = $(this);
            $.post(wplp_admin.ajax_url, {
                action: 'wplp_update_order_status',
                nonce: wplp_admin.nonce,
                order_id: $sel.data('order-id'),
                status: $sel.val()
            }, function(res) {
                var $badge = $sel.closest('tr').find('.wplp-status');
                $badge.attr('class', 'wplp-status wplp-status-' + $sel.val()).text($sel.val().charAt(0).toUpperCase() + $sel.val().slice(1));
            });
        });

        // ─── License Status Change ───────────────────

        $(document).on('change', '.wplp-license-status-select', function() {
            var $sel = $(this);
            $.post(wplp_admin.ajax_url, {
                action: 'wplp_update_license_status',
                nonce: wplp_admin.nonce,
                license_id: $sel.data('license-id'),
                status: $sel.val()
            }, function(res) {
                var $badge = $sel.closest('tr').find('.wplp-status');
                $badge.attr('class', 'wplp-status wplp-status-' + $sel.val()).text($sel.val().charAt(0).toUpperCase() + $sel.val().slice(1));
            });
        });
    });
})(jQuery);
