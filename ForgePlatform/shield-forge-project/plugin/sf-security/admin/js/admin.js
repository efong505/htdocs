(function($) {
    'use strict';

    // --- Blocklist ---

    $('#sfs-add-ip-btn').on('click', function() {
        var ip = $('#sfs-ip-input').val().trim();
        var type = $('#sfs-ip-type').val();
        var reason = $('#sfs-ip-reason').val().trim();

        if (!ip) { alert('Enter an IP address.'); return; }

        $.post(sfs_ajax.url, {
            action: 'sfs_blocklist_action',
            nonce: sfs_ajax.nonce,
            blocklist_action: 'add',
            ip: ip, type: type, reason: reason
        }, function(response) {
            if (response.success) location.reload();
            else alert(response.data || 'Error');
        });
    });

    $(document).on('click', '.sfs-remove-btn', function() {
        if (!confirm('Remove this entry?')) return;
        $.post(sfs_ajax.url, {
            action: 'sfs_blocklist_action',
            nonce: sfs_ajax.nonce,
            blocklist_action: 'remove',
            id: $(this).data('id')
        }, function(response) {
            if (response.success) location.reload();
        });
    });

    $(document).on('click', '.sfs-unlock-btn', function() {
        $.post(sfs_ajax.url, {
            action: 'sfs_blocklist_action',
            nonce: sfs_ajax.nonce,
            blocklist_action: 'unlock',
            ip: $(this).data('ip')
        }, function(response) {
            if (response.success) location.reload();
        });
    });

    // --- WAF Rule Toggle ---

    $(document).on('change', '.sfs-rule-toggle', function() {
        var $toggle = $(this);
        var ruleId = $toggle.data('rule');
        var enabled = $toggle.is(':checked') ? '1' : '0';
        var $row = $toggle.closest('tr');
        var $status = $row.find('.sfs-rule-status');

        $.post(sfs_ajax.url, {
            action: 'sfs_waf_toggle_rule',
            nonce: sfs_ajax.nonce,
            rule_id: ruleId,
            enabled: enabled
        }, function(response) {
            if (response.success) {
                if (enabled === '1') {
                    $status.html('<span class="sfs-badge sfs-badge-info">Active</span>');
                } else {
                    $status.html('<span class="sfs-badge sfs-badge-warning">Disabled</span>');
                }
            }
        });
    });

    // --- Load Preset / Profile ---

    $('#sfs-load-preset').on('click', function() {
        var preset = $('#sfs-preset-select').val();
        if (!preset) return;

        if (!confirm('Load this configuration? Current rule states will be overwritten.')) return;

        $.post(sfs_ajax.url, {
            action: 'sfs_waf_load_preset',
            nonce: sfs_ajax.nonce,
            preset: preset
        }, function(response) {
            if (response.success) location.reload();
            else alert(response.data || 'Error loading preset.');
        });
    });

    // --- Save Profile ---

    $('#sfs-save-profile').on('click', function() {
        var name = $('#sfs-profile-name').val().trim();
        if (!name) { alert('Enter a profile name.'); return; }

        $.post(sfs_ajax.url, {
            action: 'sfs_waf_save_profile',
            nonce: sfs_ajax.nonce,
            profile_name: name
        }, function(response) {
            if (response.success) {
                alert('Profile "' + response.data.name + '" saved.');
                location.reload();
            } else {
                alert(response.data || 'Error saving profile.');
            }
        });
    });

    // --- Delete Profile ---

    $('#sfs-delete-profile').on('click', function() {
        var slug = $('#sfs-preset-select').val();
        if (!slug) return;

        // Only allow deleting saved profiles, not built-in presets
        var builtIn = ['default', 'strict', 'minimal', 'paranoid'];
        if (builtIn.indexOf(slug) !== -1) {
            alert('Cannot delete built-in presets.');
            return;
        }

        if (!confirm('Delete this saved profile?')) return;

        $.post(sfs_ajax.url, {
            action: 'sfs_waf_delete_profile',
            nonce: sfs_ajax.nonce,
            profile_slug: slug
        }, function(response) {
            if (response.success) location.reload();
        });
    });

})(jQuery);
