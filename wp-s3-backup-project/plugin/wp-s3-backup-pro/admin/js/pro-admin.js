/**
 * WP S3 Backup Pro — Admin JS
 */
(function($) {
	'use strict';

	// Enable/disable Apply button based on select value
	$(document).on('change', '.wps3b-storage-select', function() {
		var $btn = $(this).siblings('.wps3b-change-storage-btn');
		$btn.prop('disabled', !$(this).val());
	});

	// Handle storage class change
	$(document).on('click', '.wps3b-change-storage-btn', function() {
		var $btn    = $(this);
		var $select = $btn.siblings('.wps3b-storage-select');
		var $result = $btn.siblings('.wps3b-storage-result');
		var s3Key   = $select.data('key');
		var newClass = $select.val();

		if (!s3Key || !newClass) return;

		$btn.prop('disabled', true).text('Updating...');
		$result.text('').removeClass('success error');

		$.post(wps3b_pro.ajax_url, {
			action: 'wps3b_pro_change_storage',
			nonce: wps3b_pro.nonce,
			s3_key: s3Key,
			storage_class: newClass
		}, function(response) {
			if (response.success) {
				$result.text(response.data).addClass('success');
				// Update the current class display
				var $row = $btn.closest('tr');
				$row.find('.wps3b-storage-badge').text(newClass);
				$select.val('');
			} else {
				$result.text(response.data).addClass('error');
			}
			$btn.prop('disabled', true).text('Apply');
		}).fail(function() {
			$result.text('Request failed.').addClass('error');
			$btn.prop('disabled', false).text('Apply');
		});
	});

	// ─── Upload & Restore ─────────────────────────────

	$(document).on('click', '.wps3b-upload-btn', function() {
		var target = $(this).data('target');
		$('#wps3b-upload-' + target).trigger('click');
	});

	$(document).on('change', '#wps3b-upload-db, #wps3b-upload-files', function() {
		var file = this.files[0];
		if (!file) return;

		var isDb = this.id === 'wps3b-upload-db';
		var target = isDb ? 'db' : 'files';
		var $status = $('#wps3b-upload-' + target + '-status');

		var formData = new FormData();
		formData.append('action', 'wps3b_pro_upload_backup');
		formData.append('nonce', wps3b_pro.nonce);
		formData.append('backup_file', file);

		$status.text('Uploading...').css('color', '#787c82');
		$('#wps3b-upload-restore-progress').show();

		$.ajax({
			url: wps3b_pro.ajax_url,
			type: 'POST',
			data: formData,
			processData: false,
			contentType: false,
			xhr: function() {
				var xhr = new XMLHttpRequest();
				xhr.upload.addEventListener('progress', function(e) {
					if (e.lengthComputable) {
						var pct = Math.round((e.loaded / e.total) * 100);
						$('#wps3b-upload-restore-bar').css('width', pct + '%');
						$('#wps3b-upload-restore-pct').text(pct + '%');
					}
				});
				return xhr;
			},
			success: function(res) {
				$('#wps3b-upload-restore-progress').hide();
				$('#wps3b-upload-restore-bar').css('width', '0%');

				if (res.success) {
					$status.html('<span class="dashicons dashicons-yes" style="color:#00a32a;vertical-align:middle;"></span> ' + res.data.file_name + ' (' + res.data.file_size + ')').css('color', '#00a32a');
					$('#wps3b-upload-' + target + '-path').val(res.data.file_path);
					$('#wps3b-upload-' + target + '-path-form').val(res.data.file_path);
					showUploadRestoreForm();
				} else {
					$status.text(res.data).css('color', '#d63638');
				}
			},
			error: function() {
				$('#wps3b-upload-restore-progress').hide();
				$status.text('Upload failed.').css('color', '#d63638');
			}
		});

		$(this).val('');
	});

	function showUploadRestoreForm() {
		var hasDb = $('#wps3b-upload-db-path').val();
		var hasFiles = $('#wps3b-upload-files-path').val();
		if (hasDb || hasFiles) {
			$('#wps3b-upload-restore-form').show();
		}
	}

	// ─── Prefix Browser ────────────────────────────────

	$(document).on('click', '#wps3b-load-prefixes', function() {
		var $btn = $(this);
		var $select = $('#wps3b-prefix-select');
		var $status = $('#wps3b-prefix-status');

		$btn.prop('disabled', true).text('Loading...');
		$status.text('').css('color', '');

		$.post(wps3b_pro.ajax_url, {
			action: 'wps3b_list_prefixes',
			nonce: wps3b_pro.nonce
		}, function(res) {
			$btn.prop('disabled', false).text('Load Sites');
			if (!res.success) {
				$status.text(res.data || 'Failed to load.').css('color', 'var(--bf-danger)');
				return;
			}

			$select.empty().append('<option value="">\u2014 Select a site from your bucket \u2014</option>');
			$.each(res.data, function(i, p) {
				var label = p.prefix + ' (' + p.file_count + ' files, ' + p.total_size_formatted + ')';
				if (p.is_current) label += ' \u2190 current site';
				$select.append('<option value="' + p.prefix + '"' + (p.is_current ? ' style="color:var(--bf-teal);"' : '') + '>' + label + '</option>');
			});

			$status.text(res.data.length + ' site(s) found.').css('color', 'var(--bf-success)');
		}).fail(function() {
			$btn.prop('disabled', false).text('Load Sites');
			$status.text('Request failed.').css('color', 'var(--bf-danger)');
		});
	});

	$(document).on('change', '#wps3b-prefix-select', function() {
		var val = $(this).val();
		if (val) {
			$('#wps3b-ext-prefix').val(val);
		}
	});

	// ─── External Restore ─────────────────────────────

	// Browse external backups
	$(document).on('click', '#wps3b-ext-browse', function() {
		var prefix = $('#wps3b-ext-prefix').val().trim();
		if (!prefix) {
			$('#wps3b-ext-status').text('Enter a path prefix first.').css('color', '#d63638');
			return;
		}

		$('#wps3b-ext-browse').prop('disabled', true);
		$('#wps3b-ext-status').text('Searching...').css('color', '#787c82');
		$('#wps3b-ext-results').hide();
		$('#wps3b-ext-restore-form').hide();

		$.post(wps3b_pro.ajax_url, {
			action: 'wps3b_pro_list_external',
			nonce: wps3b_pro.nonce,
			external_prefix: prefix
		}, function(res) {
			$('#wps3b-ext-browse').prop('disabled', false);
			if (!res.success) {
				$('#wps3b-ext-status').text(res.data).css('color', '#d63638');
				return;
			}

			$('#wps3b-ext-status').text(res.data.length + ' backup(s) found.').css('color', '#00a32a');
			var $tbody = $('#wps3b-ext-tbody').empty();

			$.each(res.data, function(i, backup) {
				var filesHtml = backup.files.map(function(f) { return '<div style="font-size:12px;">' + f + '</div>'; }).join('');
				$tbody.append(
					'<tr>' +
					'<td><strong>' + backup.date + '</strong></td>' +
					'<td>' + filesHtml + '</td>' +
					'<td>' + formatSize(backup.total_size) + '</td>' +
					'<td><button type="button" class="button button-small wps3b-ext-select" data-ts="' + backup.timestamp + '">Select</button></td>' +
					'</tr>'
				);
			});

			$('#wps3b-ext-results').show();
		}).fail(function() {
			$('#wps3b-ext-browse').prop('disabled', false);
			$('#wps3b-ext-status').text('Request failed.').css('color', '#d63638');
		});
	});

	// Select an external backup to restore
	$(document).on('click', '.wps3b-ext-select', function() {
		var ts = $(this).data('ts');
		var prefix = $('#wps3b-ext-prefix').val().trim();

		$('#wps3b-ext-form-prefix').val(prefix);
		$('#wps3b-ext-form-timestamp').val(ts);
		$('#wps3b-ext-restore-form').show();

		// Highlight selected row
		$('.wps3b-ext-select').text('Select');
		$(this).text('Selected ✓');

		$('html, body').animate({ scrollTop: $('#wps3b-ext-restore-form').offset().top - 50 }, 300);
	});

	function formatSize(bytes) {
		if (bytes < 1024) return bytes + ' B';
		if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
		if (bytes < 1073741824) return (bytes / 1048576).toFixed(1) + ' MB';
		return (bytes / 1073741824).toFixed(2) + ' GB';
	}

})(jQuery);
