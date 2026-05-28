(function($) {
    'use strict';

    // Add IP to blocklist
    $('#sfs-add-ip-btn').on('click', function() {
        var ip = $('#sfs-ip-input').val().trim();
        var type = $('#sfs-ip-type').val();
        var reason = $('#sfs-ip-reason').val().trim();

        if (!ip) { alert('Enter an IP address.'); return; }

        $.post(sfs_ajax.url, {
            action: 'sfs_blocklist_action',
            nonce: sfs_ajax.nonce,
            blocklist_action: 'add',
            ip: ip,
            type: type,
            reason: reason
        }, function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert(response.data || 'Error');
            }
        });
    });

    // Remove from blocklist
    $(document).on('click', '.sfs-remove-btn', function() {
        if (!confirm('Remove this entry?')) return;
        var id = $(this).data('id');

        $.post(sfs_ajax.url, {
            action: 'sfs_blocklist_action',
            nonce: sfs_ajax.nonce,
            blocklist_action: 'remove',
            id: id
        }, function(response) {
            if (response.success) location.reload();
        });
    });

    // Unlock lockout
    $(document).on('click', '.sfs-unlock-btn', function() {
        var ip = $(this).data('ip');

        $.post(sfs_ajax.url, {
            action: 'sfs_blocklist_action',
            nonce: sfs_ajax.nonce,
            blocklist_action: 'unlock',
            ip: ip
        }, function(response) {
            if (response.success) location.reload();
        });
    });

})(jQuery);
