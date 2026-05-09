<?php if (!defined('ABSPATH')) exit; ?>
<div class="wrap nlde-wrap">
    <h1>
        <a href="<?php echo admin_url('admin.php?page=nlde-sequences'); ?>">&larr; Sequences</a> /
        <?php echo esc_html($sequence->name); ?>
    </h1>

    <?php if (isset($_GET['updated'])): ?>
        <div class="nlde-notice success">Sequence updated.</div>
    <?php endif; ?>

    <!-- Sequence Settings -->
    <div class="nlde-card">
        <h2>Sequence Settings</h2>
        <form method="post">
            <?php wp_nonce_field('nlde_admin_action', 'nlde_nonce'); ?>
            <input type="hidden" name="nlde_action" value="update_sequence">
            <input type="hidden" name="sequence_id" value="<?php echo esc_attr($sequence->id); ?>">
            <div class="nlde-form-group">
                <label>Name</label>
                <input type="text" name="name" value="<?php echo esc_attr($sequence->name); ?>" required>
            </div>
            <div class="nlde-form-group">
                <label>Description</label>
                <input type="text" name="description" value="<?php echo esc_attr($sequence->description); ?>">
            </div>
            <div class="nlde-form-group">
                <label>Status</label>
                <select name="status">
                    <option value="draft" <?php selected($sequence->status, 'draft'); ?>>Draft</option>
                    <option value="active" <?php selected($sequence->status, 'active'); ?>>Active</option>
                    <option value="paused" <?php selected($sequence->status, 'paused'); ?>>Paused</option>
                </select>
            </div>
            <button type="submit" class="nlde-btn nlde-btn-primary">Save Settings</button>
        </form>
        <p style="margin-top:12px;font-size:13px;color:#666;">
            Shortcode: <code>[nl_signup_form sequence="<?php echo esc_attr($sequence->slug); ?>" button_text="Subscribe" redirect="/thank-you/"]</code>
        </p>
    </div>

    <!-- Email Stats -->
    <?php if (!empty($stats)): ?>
    <div class="nlde-card">
        <h2>Performance</h2>
        <table class="nlde-table">
            <thead>
                <tr><th>#</th><th>Subject</th><th>Day</th><th>Sent</th><th>Opened</th><th>Clicked</th><th>Open Rate</th></tr>
            </thead>
            <tbody>
                <?php foreach ($stats as $s): ?>
                <tr>
                    <td><?php echo esc_html($s->position + 1); ?></td>
                    <td><?php echo esc_html($s->subject); ?></td>
                    <td>Day <?php echo esc_html($s->delay_days); ?></td>
                    <td><a href="<?php echo esc_url(admin_url('admin.php?page=nlde-activity&sequence_id=' . $sequence->id . '&email_position=' . $s->position . '&filter=sent')); ?>"><?php echo esc_html($s->total_sent); ?></a></td>
                    <td><a href="<?php echo esc_url(admin_url('admin.php?page=nlde-activity&sequence_id=' . $sequence->id . '&email_position=' . $s->position . '&filter=opened')); ?>"><?php echo esc_html($s->total_opened); ?></a></td>
                    <td><a href="<?php echo esc_url(admin_url('admin.php?page=nlde-activity&sequence_id=' . $sequence->id . '&email_position=' . $s->position . '&filter=clicked')); ?>"><?php echo esc_html($s->total_clicked); ?></a></td>
                    <td><?php echo $s->total_sent > 0 ? round(($s->total_opened / $s->total_sent) * 100, 1) . '%' : '—'; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <!-- Emails in Sequence -->
    <div class="nlde-card">
        <h2>Emails in Sequence</h2>
        <?php if (!empty($emails)): ?>
        <ul class="nlde-email-list">
            <?php foreach ($emails as $em): ?>
            <li>
                <div class="email-info">
                    <strong>Email #<?php echo esc_html($em->position + 1); ?>: <?php echo esc_html($em->subject); ?></strong>
                    <div class="meta">Day <?php echo esc_html($em->delay_days); ?> &bull; Position <?php echo esc_html($em->position); ?></div>
                </div>
                <div class="email-actions">
                    <a href="<?php echo esc_url( admin_url('admin-ajax.php?action=nlde_preview_email&email_id=' . $em->id . '&_wpnonce=' . wp_create_nonce('nlde_preview')) ); ?>" target="_blank" class="nlde-btn nlde-btn-secondary" style="padding:4px 10px;font-size:12px;">Preview</a>
                    <button type="button" class="nlde-btn nlde-btn-secondary nlde-send-now" style="padding:4px 10px;font-size:12px;"
                        data-email-id="<?php echo esc_attr($em->id); ?>"
                        data-subject="<?php echo esc_attr($em->subject); ?>">Send Now</button>
                    <a href="#email-form" class="nlde-btn nlde-btn-primary nlde-edit-email" style="padding:4px 10px;font-size:12px;"
                       data-id="<?php echo esc_attr($em->id); ?>"
                       data-position="<?php echo esc_attr($em->position); ?>"
                       data-subject="<?php echo esc_attr($em->subject); ?>"
                       data-delay="<?php echo esc_attr($em->delay_days); ?>"
                       data-body-id="nlde-body-<?php echo esc_attr($em->id); ?>">Edit</a>
                    <script type="text/html" id="nlde-body-<?php echo esc_attr($em->id); ?>"><?php echo $em->body; ?></script>
                    <form method="post" style="display:inline;" onsubmit="return confirm('Delete this email?');">
                        <?php wp_nonce_field('nlde_admin_action', 'nlde_nonce'); ?>
                        <input type="hidden" name="nlde_action" value="delete_email">
                        <input type="hidden" name="email_id" value="<?php echo esc_attr($em->id); ?>">
                        <input type="hidden" name="sequence_id" value="<?php echo esc_attr($sequence->id); ?>">
                        <button type="submit" class="nlde-btn nlde-btn-danger" style="padding:4px 10px;font-size:12px;">Delete</button>
                    </form>
                </div>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php else: ?>
            <p style="color:#999;">No emails yet. Add your first email below.</p>
        <?php endif; ?>
    </div>

    <!-- Add/Edit Email Form -->
    <div class="nlde-card" id="email-form">
        <h2>Add / Edit Email</h2>
        <div class="nlde-merge-tags">
            <strong>Merge Tags:</strong>
            <code>{first_name}</code> <code>{last_name}</code> <code>{email}</code>
            <code>{site_name}</code> <code>{site_url}</code> <code>{unsubscribe_link}</code>
            <code>{rfp_link}</code> <code>{download_link}</code>
        </div>
        <form method="post">
            <?php wp_nonce_field('nlde_admin_action', 'nlde_nonce'); ?>
            <input type="hidden" name="nlde_action" value="save_email">
            <input type="hidden" name="sequence_id" value="<?php echo esc_attr($sequence->id); ?>">
            <input type="hidden" name="email_id" id="email_id" value="0">
            <div class="nlde-form-group">
                <label>Position (0-based)</label>
                <input type="number" name="position" id="email_position" value="<?php echo count($emails); ?>" min="0" required>
            </div>
            <div class="nlde-form-group">
                <label>Delay (days after enrollment)</label>
                <input type="number" name="delay_days" id="email_delay" value="0" min="0" required>
            </div>
            <div class="nlde-form-group">
                <label>Subject Line</label>
                <input type="text" name="subject" id="email_subject" required placeholder="e.g. Your Web Dev Survival Kit Is Ready">
            </div>
            <div class="nlde-form-group">
                <label>Email Body (use merge tags above)</label>
                <?php wp_editor('', 'email_body', [
                    'textarea_name' => 'body',
                    'textarea_rows' => 12,
                    'media_buttons' => true,
                    'tinymce'       => [
                        'toolbar1' => 'bold,italic,underline,strikethrough,|,bullist,numlist,|,link,unlink,|,forecolor,|,alignleft,aligncenter,|,undo,redo',
                        'toolbar2' => '',
                    ],
                    'quicktags'     => true,
                ]); ?>
            </div>
            <button type="submit" class="nlde-btn nlde-btn-primary">Save Email</button>
            <a href="#" class="nlde-btn nlde-btn-secondary" id="nlde-clear-form">Clear / New</a>
        </form>
    </div>

    <script>
    document.querySelectorAll('.nlde-edit-email').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            document.getElementById('email_id').value = this.dataset.id;
            document.getElementById('email_position').value = this.dataset.position;
            document.getElementById('email_subject').value = this.dataset.subject;
            document.getElementById('email_delay').value = this.dataset.delay;
            // Get body from hidden script tag to preserve HTML
            var bodyHtml = document.getElementById(this.dataset.bodyId).innerHTML;
            if (typeof tinymce !== 'undefined' && tinymce.get('email_body')) {
                tinymce.get('email_body').setContent(bodyHtml);
            }
            document.getElementById('email_body').value = bodyHtml;
            document.getElementById('email-form').scrollIntoView({behavior: 'smooth'});
        });
    });
    document.getElementById('nlde-clear-form').addEventListener('click', function(e) {
        e.preventDefault();
        document.getElementById('email_id').value = '0';
        document.getElementById('email_subject').value = '';
        document.getElementById('email_position').value = '<?php echo count($emails); ?>';
        document.getElementById('email_delay').value = '0';
        if (typeof tinymce !== 'undefined' && tinymce.get('email_body')) {
            tinymce.get('email_body').setContent('');
        }
        document.getElementById('email_body').value = '';
    });

    document.querySelectorAll('.nlde-send-now').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var emailId = this.dataset.emailId;
            var subject = this.dataset.subject;
            var to = prompt('Send "' + subject + '" to which email address?');
            if (!to) return;
            var sendBtn = this;
            sendBtn.disabled = true;
            sendBtn.textContent = 'Sending...';
            var formData = new FormData();
            formData.append('action', 'nlde_send_sequence_email_now');
            formData.append('nonce', '<?php echo wp_create_nonce('nlde_test_email'); ?>');
            formData.append('email_id', emailId);
            formData.append('to', to);
            fetch(ajaxurl, { method: 'POST', body: formData })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    alert(data.success ? data.data : 'Error: ' + data.data);
                })
                .catch(function() { alert('Request failed.'); })
                .finally(function() {
                    sendBtn.disabled = false;
                    sendBtn.textContent = 'Send Now';
                });
        });
    });
    </script>
</div>
