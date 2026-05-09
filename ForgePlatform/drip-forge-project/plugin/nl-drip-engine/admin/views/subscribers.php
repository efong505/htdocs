<?php if (!defined('ABSPATH')) exit; ?>
<div class="wrap nlde-wrap">
    <h1>Subscribers
        <form method="post" style="display:inline;">
            <?php wp_nonce_field('nlde_admin_action', 'nlde_nonce'); ?>
            <input type="hidden" name="nlde_action" value="export_subscribers">
            <button type="submit" class="nlde-btn nlde-btn-secondary" style="margin-left:12px;">Export CSV</button>
        </form>
    </h1>

    <?php if (isset($_GET['deleted'])): ?>
        <div class="nlde-notice success">Subscriber deleted.</div>
    <?php endif; ?>
    <?php if (isset($_GET['sent'])): ?>
        <div class="nlde-notice success">Email sent.</div>
    <?php endif; ?>
    <?php if (isset($_GET['send_error'])): ?>
        <div class="nlde-notice error">Failed to send email.</div>
    <?php endif; ?>

    <form method="get" style="margin-bottom:16px;">
        <input type="hidden" name="page" value="nlde-subscribers">
        <input type="text" name="s" value="<?php echo esc_attr($search); ?>" placeholder="Search by email or name..." style="padding:6px 12px;width:300px;">
        <select name="status">
            <option value="">All Statuses</option>
            <option value="active" <?php selected($status, 'active'); ?>>Active</option>
            <option value="unsubscribed" <?php selected($status, 'unsubscribed'); ?>>Unsubscribed</option>
        </select>
        <button type="submit" class="nlde-btn nlde-btn-primary">Filter</button>
    </form>

    <table class="nlde-table">
        <thead>
            <tr>
                <th>Email</th>
                <th>Name</th>
                <th>Status</th>
                <th>Subscribed</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($subscribers)): ?>
                <tr><td colspan="5" style="text-align:center;color:#999;">No subscribers yet.</td></tr>
            <?php else: ?>
                <?php foreach ($subscribers as $sub): ?>
                <tr>
                    <td><?php echo esc_html($sub->email); ?></td>
                    <td><?php echo esc_html(trim($sub->first_name . ' ' . $sub->last_name)); ?></td>
                    <td><span class="nlde-badge <?php echo esc_attr($sub->status); ?>"><?php echo esc_html($sub->status); ?></span></td>
                    <td><?php echo esc_html($sub->subscribed_at); ?></td>
                    <td>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=nlde-activity&subscriber_id=' . $sub->id)); ?>" class="nlde-btn nlde-btn-secondary" style="padding:4px 10px;font-size:12px;">Activity</a>
                        <button type="button" class="nlde-btn nlde-btn-primary nlde-quick-send" style="padding:4px 10px;font-size:12px;" data-email="<?php echo esc_attr($sub->email); ?>" data-name="<?php echo esc_attr($sub->first_name); ?>">Send Email</button>
                        <form method="post" style="display:inline;" onsubmit="return confirm('Delete this subscriber?');">
                            <?php wp_nonce_field('nlde_admin_action', 'nlde_nonce'); ?>
                            <input type="hidden" name="nlde_action" value="delete_subscriber">
                            <input type="hidden" name="subscriber_id" value="<?php echo esc_attr($sub->id); ?>">
                            <button type="submit" class="nlde-btn nlde-btn-danger" style="padding:4px 10px;font-size:12px;">Delete</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <?php if ($total_pages > 1): ?>
    <div class="nlde-pagination">
        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <?php if ($i == $page): ?>
                <span class="current"><?php echo $i; ?></span>
            <?php else: ?>
                <a href="<?php echo esc_url(add_query_arg('paged', $i)); ?>"><?php echo $i; ?></a>
            <?php endif; ?>
        <?php endfor; ?>
    </div>
    <?php endif; ?>

    <!-- Quick Send Modal -->
    <div id="nlde-send-modal" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.6);z-index:9999;">
        <div style="background:#fff;max-width:500px;margin:80px auto;padding:24px;border-radius:8px;">
            <h3 style="margin-top:0;">Send Email to <span id="nlde-modal-to"></span></h3>
            <input type="hidden" id="nlde-modal-email">
            <div class="nlde-form-group">
                <label>Subject</label>
                <input type="text" id="nlde-modal-subject" style="width:100%;padding:8px;">
            </div>
            <div class="nlde-form-group">
                <label>Body (HTML supported)</label>
                <textarea id="nlde-modal-body" rows="8" style="width:100%;padding:8px;"></textarea>
            </div>
            <button type="button" id="nlde-modal-send" class="nlde-btn nlde-btn-primary">Send</button>
            <button type="button" id="nlde-modal-close" class="nlde-btn nlde-btn-secondary" style="margin-left:8px;">Cancel</button>
            <span id="nlde-modal-result" style="margin-left:12px;font-size:13px;"></span>
        </div>
    </div>

    <script>
    document.querySelectorAll('.nlde-quick-send').forEach(function(btn) {
        btn.addEventListener('click', function() {
            document.getElementById('nlde-modal-to').textContent = this.dataset.email;
            document.getElementById('nlde-modal-email').value = this.dataset.email;
            document.getElementById('nlde-modal-subject').value = '';
            document.getElementById('nlde-modal-body').value = '';
            document.getElementById('nlde-modal-result').textContent = '';
            document.getElementById('nlde-send-modal').style.display = 'block';
        });
    });
    document.getElementById('nlde-modal-close').addEventListener('click', function() {
        document.getElementById('nlde-send-modal').style.display = 'none';
    });
    document.getElementById('nlde-modal-send').addEventListener('click', function() {
        var btn = this;
        var result = document.getElementById('nlde-modal-result');
        btn.disabled = true;
        btn.textContent = 'Sending...';
        result.textContent = '';
        var formData = new FormData();
        formData.append('action', 'nlde_manual_send');
        formData.append('nonce', '<?php echo wp_create_nonce('nlde_test_email'); ?>');
        formData.append('email', document.getElementById('nlde-modal-email').value);
        formData.append('subject', document.getElementById('nlde-modal-subject').value);
        formData.append('body', document.getElementById('nlde-modal-body').value);
        fetch(ajaxurl, { method: 'POST', body: formData })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.success) {
                    result.textContent = '\u2705 ' + data.data;
                    result.style.color = '#28a745';
                } else {
                    result.textContent = '\u274c ' + data.data;
                    result.style.color = '#dc3545';
                }
            })
            .catch(function() {
                result.textContent = '\u274c Request failed.';
                result.style.color = '#dc3545';
            })
            .finally(function() {
                btn.disabled = false;
                btn.textContent = 'Send';
            });
    });
    </script>
</div>
