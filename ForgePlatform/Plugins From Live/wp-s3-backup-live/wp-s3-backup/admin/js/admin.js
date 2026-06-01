/**
 * BackForge — Admin JavaScript
 */
(function ($) {
    'use strict';

    var pollTimer = null;
    var elapsedTimer = null;
    var backupStart = 0;
    var autoRefreshTimer = null;

    $(document).ready(function () {

        // ─── Background Backup ─────────────────────────

        if ($('#bf-backup-now').length) {
            checkBackupStatus();
        }

        $('#bf-backup-now').on('click', function (e) {
            e.preventDefault();
            var $btn = $(this);
            $btn.prop('disabled', true);

            $.post(wps3b_ajax.url, {
                action: 'wps3b_backup_step',
                nonce: wps3b_ajax.backup_nonce,
                step: 'start'
            }, function (res) {
                if (!res.success) {
                    $btn.prop('disabled', false);
                    alert(res.data);
                    return;
                }
                showProgressCard();
                startPolling();
            }).fail(function () {
                $btn.prop('disabled', false);
                alert('Request failed.');
            });
        });

        function checkBackupStatus() {
            $.post(wps3b_ajax.url, {
                action: 'wps3b_backup_step',
                nonce: wps3b_ajax.backup_nonce,
                step: 'poll'
            }, function (res) {
                if (!res.success || !res.data) return;
                var d = res.data;
                if (d.running) {
                    showProgressCard();
                    updateFromStatus(d);
                    startPolling();
                } else if (d.step === 'complete') {
                    // Auto-dismiss completed status
                    $.post(wps3b_ajax.url, { action: 'wps3b_backup_step', nonce: wps3b_ajax.backup_nonce, step: 'dismiss' });
                } else if (d.step === 'error') {
                    showProgressCard();
                    updateFromStatus(d);
                }
            });
        }

        function startPolling() {
            backupStart = Date.now();
            elapsedTimer = setInterval(updateElapsed, 1000);
            pollTimer = setInterval(function () {
                $.post(wps3b_ajax.url, {
                    action: 'wps3b_backup_step',
                    nonce: wps3b_ajax.backup_nonce,
                    step: 'poll'
                }, function (res) {
                    if (!res.success) return;
                    updateFromStatus(res.data);
                    if (!res.data.running) {
                        stopPolling();
                    }
                });
            }, 3000);
        }

        function stopPolling() {
            if (pollTimer) { clearInterval(pollTimer); pollTimer = null; }
            if (elapsedTimer) { clearInterval(elapsedTimer); elapsedTimer = null; }
        }

        function showProgressCard() {
            $('#bf-backup-now').prop('disabled', true).html('<span class="dashicons dashicons-update bf-spinning"></span> Backup in progress...');
            $('#bf-backup-progress').show();
        }

        function updateFromStatus(data) {
            if (!data) return;

            var pct = data.progress || 0;
            var step = data.step || '';

            // Progress bar
            $('#bf-backup-bar').css('width', pct + '%');
            $('#bf-backup-pct').text(pct + '%');

            // Title
            $('#bf-backup-title').text(data.message || 'Working...');

            // Per-file status updates
            updateFileStatus('db', step, data.steps || []);
            updateFileStatus('files', step, data.steps || []);
            updateFileStatus('manifest', step, data.steps || []);

            // Elapsed
            if (data.started) {
                backupStart = data.started * 1000;
            }

            // Complete
            if (step === 'complete') {
                $('#bf-backup-title').text('Backup Complete!');
                $('#bf-backup-progress').css('border-color', 'var(--bf-success)');
                $('#bf-backup-progress .bf-backup-card__header .dashicons').first().removeClass('bf-spinning').css('color', 'var(--bf-success)');
                $('#bf-backup-badge').removeClass('bf-status--warn').addClass('bf-status--ok').html('<span class="bf-status__dot"></span> Complete');
                $('#bf-backup-now').prop('disabled', false).html('<span class="dashicons dashicons-cloud-upload"></span> Create Backup Now');
                setTimeout(function () {
                    $.post(wps3b_ajax.url, { action: 'wps3b_backup_step', nonce: wps3b_ajax.backup_nonce, step: 'dismiss' });
                    location.reload();
                }, 3000);
            }

            // Error
            if (step === 'error') {
                $('#bf-backup-title').text('Backup Failed');
                $('#bf-backup-progress').css('border-color', 'var(--bf-danger)');
                $('#bf-backup-progress .bf-backup-card__header .dashicons').first().removeClass('bf-spinning').css('color', 'var(--bf-danger)');
                $('#bf-backup-badge').removeClass('bf-status--warn').addClass('bf-status--err').html('<span class="bf-status__dot"></span> Failed');
                $('#bf-backup-now').prop('disabled', false).html('<span class="dashicons dashicons-cloud-upload"></span> Create Backup Now');
            }
        }

        function updateFileStatus(type, currentStep, completedSteps) {
            var $status = $('#bf-file-' + type + '-status');
            var $meta = $('#bf-file-' + type + '-meta');
            var joined = completedSteps.join('|');

            // Map steps to file states
            var states = {
                db: {
                    exporting: ['export_db'],
                    exported: 'Database exported',
                    uploading: ['upload_db'],
                    uploaded: 'Database uploaded'
                },
                files: {
                    exporting: ['export_files'],
                    exported: 'Files archived',
                    uploading: ['upload_files'],
                    uploaded: 'Files uploaded'
                },
                manifest: {
                    exporting: ['manifest'],
                    exported: 'Backup complete',
                    uploading: [],
                    uploaded: 'Backup complete'
                }
            };

            var s = states[type];
            if (!s) return;

            // Check if uploaded (in completed steps)
            if (joined.indexOf(s.uploaded) !== -1) {
                $status.attr('class', 'bf-status bf-status--ok').css('font-size', '11px').html('<span class="bf-status__dot"></span> Uploaded');
                // Extract size from step message
                var sizeMatch = findStepDetail(completedSteps, s.exported);
                $meta.text(sizeMatch || 'Done');
                return;
            }

            // Check if currently uploading
            if (s.uploading.indexOf(currentStep) !== -1) {
                $status.attr('class', 'bf-status bf-status--warn').css('font-size', '11px').html('<span class="bf-status__dot"></span> Uploading...');
                var exportDetail = findStepDetail(completedSteps, s.exported);
                $meta.text(exportDetail || 'Uploading...');
                return;
            }

            // Check if exported (in completed steps)
            if (joined.indexOf(s.exported) !== -1) {
                $status.attr('class', 'bf-status bf-status--ok').css('font-size', '11px').html('<span class="bf-status__dot"></span> Ready');
                var detail = findStepDetail(completedSteps, s.exported);
                $meta.text(detail || 'Exported');
                return;
            }

            // Check if currently exporting
            if (s.exporting.indexOf(currentStep) !== -1) {
                $status.attr('class', 'bf-status bf-status--warn').css('font-size', '11px').html('<span class="bf-status__dot"></span> Working...');
                $meta.text(type === 'db' ? 'Exporting database...' : type === 'files' ? 'Archiving files...' : 'Generating...');
                return;
            }

            // Pending
            $status.attr('class', 'bf-status bf-status--off').css('font-size', '11px').html('<span class="bf-status__dot"></span> Pending');
            $meta.text('Waiting');
        }

        function findStepDetail(steps, keyword) {
            for (var i = 0; i < steps.length; i++) {
                if (steps[i].indexOf(keyword) !== -1) {
                    return steps[i];
                }
            }
            return '';
        }

        function updateElapsed() {
            if (!backupStart) return;
            var secs = Math.floor((Date.now() - backupStart) / 1000);
            var m = Math.floor(secs / 60);
            var s = secs % 60;
            $('#bf-backup-elapsed').text((m > 0 ? m + 'm ' : '') + s + 's');
        }

        // ─── Test Connection ─────────────────────────

        $('#wps3b-test-connection').on('click', function (e) {
            e.preventDefault();
            var $btn = $(this);
            var $result = $('#bf-test-result');

            $btn.prop('disabled', true);
            $result.removeClass('bf-test-success bf-test-error').text(wps3b_ajax.i18n.testing);

            $.post(wps3b_ajax.url, {
                action: 'wps3b_test_connection',
                nonce: wps3b_ajax.nonce
            }, function (res) {
                $result.addClass(res.success ? 'bf-test-success' : 'bf-test-error').text(res.data);
                $btn.prop('disabled', false);
            }).fail(function () {
                $result.addClass('bf-test-error').text('Request failed.');
                $btn.prop('disabled', false);
            });
        });

        // ─── Credential Field Handling ───────────────

        $('#wps3b_access_key, #wps3b_secret_key').on('focus', function () {
            var val = $(this).val();
            if (val && val.indexOf('****') !== -1) {
                $(this).val('').attr('type', 'text');
            }
        });

        // ─── Log Refresh ─────────────────────────────

        function refreshLogs(showStatus) {
            var $container = $('#wps3b-log-container');
            var $status = $('#bf-refresh-status');
            var $icon = $('#wps3b-refresh-logs .wps3b-spin-icon');

            if (!$container.length) return;

            $icon.addClass('bf-spinning');
            if (showStatus) {
                $status.removeClass('bf-refresh-done').text(wps3b_ajax.i18n.refreshing);
            }

            $.post(wps3b_ajax.url, {
                action: 'wps3b_get_logs',
                nonce: wps3b_ajax.logs_nonce
            }, function (res) {
                if (res.success) {
                    $container.html(res.data.html);
                    if (showStatus) {
                        $status.addClass('bf-refresh-done').text(wps3b_ajax.i18n.updated + ' ' + new Date().toLocaleTimeString());
                    }
                }
                $icon.removeClass('bf-spinning');
            });
        }

        $('#wps3b-refresh-logs').on('click', function (e) {
            e.preventDefault();
            refreshLogs(true);
        });

        $('#wps3b-auto-refresh').on('change', function () {
            if ($(this).is(':checked')) {
                refreshLogs(true);
                autoRefreshTimer = setInterval(function () { refreshLogs(true); }, 5000);
            } else {
                if (autoRefreshTimer) { clearInterval(autoRefreshTimer); autoRefreshTimer = null; }
                $('#bf-refresh-status').text('');
            }
        });

        $(window).on('beforeunload', function () {
            if (autoRefreshTimer) clearInterval(autoRefreshTimer);
        });

        // ─── Background Restore ─────────────────────────

        var restorePollTimer = null;
        var restoreElapsedTimer = null;
        var restoreStart = 0;
        var restorePolling = false;

        if ($('#bf-restore-progress').length) {
            checkRestoreStatus();
        }

        $(document).on('click', '.bf-restore-backup', function (e) {
            e.preventDefault();
            if (!confirm('This will restore your entire site from this backup. Are you sure?')) return;

            var ts = $(this).data('timestamp');
            $(this).prop('disabled', true).text('Starting...');

            $.post(wps3b_ajax.url, {
                action: 'wps3b_restore_step',
                nonce: wps3b_ajax.restore_nonce,
                step: 'start',
                timestamp: ts
            }, function (res) {
                if (!res.success) {
                    alert(res.data);
                    $('.bf-restore-backup').prop('disabled', false).html('<span class="dashicons dashicons-backup"></span> Restore');
                    return;
                }
                showRestoreCard();
                startRestorePolling();
            }).fail(function () {
                alert('Request failed.');
                $('.bf-restore-backup').prop('disabled', false).html('<span class="dashicons dashicons-backup"></span> Restore');
            });
        });

        function checkRestoreStatus() {
            $.post(wps3b_ajax.url, {
                action: 'wps3b_restore_step',
                nonce: wps3b_ajax.restore_nonce,
                step: 'poll'
            }, function (res) {
                if (!res.success || !res.data) return;
                var d = res.data;
                if (d.running) {
                    showRestoreCard();
                    updateRestoreFromStatus(d);
                    startRestorePolling();
                } else if (d.step === 'complete') {
                    $.post(wps3b_ajax.url, { action: 'wps3b_restore_step', nonce: wps3b_ajax.restore_nonce, step: 'dismiss' });
                } else if (d.step === 'error') {
                    showRestoreCard();
                    updateRestoreFromStatus(d);
                }
            });
        }

        function startRestorePolling() {
            restoreStart = Date.now();
            restoreElapsedTimer = setInterval(updateRestoreElapsed, 1000);
            restorePollTimer = setInterval(function () {
                if (restorePolling) return;
                restorePolling = true;
                $.post(wps3b_ajax.url, {
                    action: 'wps3b_restore_step',
                    nonce: wps3b_ajax.restore_nonce,
                    step: 'poll'
                }, function (res) {
                    restorePolling = false;
                    if (!res.success) return;
                    updateRestoreFromStatus(res.data);
                    if (!res.data.running) {
                        stopRestorePolling();
                    }
                }).fail(function () {
                    restorePolling = false;
                });
            }, 3000);
        }

        function stopRestorePolling() {
            if (restorePollTimer) { clearInterval(restorePollTimer); restorePollTimer = null; }
            if (restoreElapsedTimer) { clearInterval(restoreElapsedTimer); restoreElapsedTimer = null; }
        }

        function showRestoreCard() {
            $('.bf-restore-backup').prop('disabled', true);
            $('#bf-restore-progress').show();
        }

        function updateRestoreFromStatus(data) {
            if (!data) return;

            var pct = data.progress || 0;
            $('#bf-restore-bar').css('width', pct + '%');
            $('#bf-restore-pct').text(pct + '%');
            $('#bf-restore-title').text(data.message || 'Restoring...');

            // Render steps
            var $steps = $('#bf-restore-steps');
            $steps.empty();
            if (data.steps && data.steps.length) {
                $.each(data.steps, function (i, msg) {
                    $steps.append(
                        '<div class="bf-backup-file" style="border:none;background:transparent;padding:6px 12px;">' +
                        '<span class="dashicons dashicons-yes-alt" style="color:var(--bf-success);font-size:16px;"></span>' +
                        '<span class="bf-backup-file__name">' + msg + '</span></div>'
                    );
                });
            }
            if (data.running && data.message) {
                $steps.append(
                    '<div class="bf-backup-file" style="border:none;background:transparent;padding:6px 12px;">' +
                    '<span class="dashicons dashicons-update bf-spinning" style="color:#f59e0b;font-size:16px;"></span>' +
                    '<span class="bf-backup-file__name">' + data.message + '</span></div>'
                );
            }

            if (data.started) {
                restoreStart = data.started * 1000;
            }

            if (data.step === 'complete') {
                $('#bf-restore-cancel').hide();
                $('#bf-restore-title').text('Restore Complete!');
                $('#bf-restore-progress').css('border-color', 'var(--bf-success)');
                $('#bf-restore-progress .bf-backup-card__header .dashicons').first().removeClass('bf-pulse').css('color', 'var(--bf-success)');
                $('#bf-restore-badge').removeClass('bf-status--warn').addClass('bf-status--ok').html('<span class="bf-status__dot"></span> Complete');
                $('.bf-restore-backup').prop('disabled', false).html('<span class="dashicons dashicons-backup"></span> Restore');
                setTimeout(function () {
                    $.post(wps3b_ajax.url, { action: 'wps3b_restore_step', nonce: wps3b_ajax.restore_nonce, step: 'dismiss' });
                    location.reload();
                }, 3000);
            }

            if (data.step === 'error') {
                $('#bf-restore-cancel').hide();
                $steps.append(
                    '<div class="bf-backup-file" style="border:none;background:transparent;padding:6px 12px;">' +
                    '<span class="dashicons dashicons-dismiss" style="color:var(--bf-danger);font-size:16px;"></span>' +
                    '<span class="bf-backup-file__name" style="color:var(--bf-danger);">' + (data.error || 'Unknown error') + '</span></div>'
                );
                $('#bf-restore-title').text('Restore Failed');
                $('#bf-restore-progress').css('border-color', 'var(--bf-danger)');
                $('#bf-restore-progress .bf-backup-card__header .dashicons').first().removeClass('bf-pulse').css('color', 'var(--bf-danger)');
                $('#bf-restore-badge').removeClass('bf-status--warn').addClass('bf-status--err').html('<span class="bf-status__dot"></span> Failed');
                $('.bf-restore-backup').prop('disabled', false).html('<span class="dashicons dashicons-backup"></span> Restore');
            }
        }

        function updateRestoreElapsed() {
            if (!restoreStart) return;
            var secs = Math.floor((Date.now() - restoreStart) / 1000);
            var m = Math.floor(secs / 60);
            var s = secs % 60;
            $('#bf-restore-elapsed').text((m > 0 ? m + 'm ' : '') + s + 's');
        }

        $(document).on('click', '#bf-restore-cancel', function () {
            if (!confirm('Cancel the restore?')) return;
            var $btn = $(this);
            $btn.prop('disabled', true).text('Cancelling...');
            stopRestorePolling();
            $.post(wps3b_ajax.url, {
                action: 'wps3b_restore_step',
                nonce: wps3b_ajax.restore_nonce,
                step: 'cancel'
            }, function () {
                $('#bf-restore-progress').hide();
                $('.bf-restore-backup').prop('disabled', false).html('<span class="dashicons dashicons-backup"></span> Restore');
            });
        });

        // ─── AJAX Delete Backup ──────────────────────

        $(document).on('click', '.bf-delete-backup', function (e) {
            e.preventDefault();
            if (!confirm('Delete this backup? This cannot be undone.')) return;

            var $btn = $(this);
            var $card = $btn.closest('.bf-backup-card');
            var ts = $btn.data('timestamp');

            $btn.prop('disabled', true).text('Deleting...');

            $.post(wps3b_ajax.url, {
                action: 'wps3b_delete_backup',
                nonce: wps3b_ajax.backup_nonce,
                timestamp: ts
            }, function (res) {
                if (res.success) {
                    $card.css({ opacity: 0, transform: 'translateX(20px)', transition: 'all 0.3s ease' });
                    setTimeout(function () { $card.remove(); }, 300);
                } else {
                    alert(res.data || 'Delete failed.');
                    $btn.prop('disabled', false).html('<span class="dashicons dashicons-trash"></span> Delete');
                }
            }).fail(function () {
                alert('Request failed.');
                $btn.prop('disabled', false).html('<span class="dashicons dashicons-trash"></span> Delete');
            });
        });
    });
})(jQuery);
