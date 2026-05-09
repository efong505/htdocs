<?php if (!defined('ABSPATH')) exit; ?>
<div class="wrap nlde-wrap">
    <h1>DripForge — Settings</h1>

    <?php if (isset($_GET['saved'])): ?>
        <div class="nlde-notice success">Settings saved.</div>
    <?php endif; ?>

    <form method="post">
        <?php wp_nonce_field('nlde_admin_action', 'nlde_nonce'); ?>
        <input type="hidden" name="nlde_action" value="save_settings">

        <div class="nlde-card">
            <h2>Sender Information</h2>
            <div class="nlde-form-group">
                <label>From Name</label>
                <input type="text" name="nlde_from_name" value="<?php echo esc_attr(get_option('nlde_from_name')); ?>">
            </div>
            <div class="nlde-form-group">
                <label>From Email</label>
                <input type="email" name="nlde_from_email" value="<?php echo esc_attr(get_option('nlde_from_email')); ?>">
            </div>
        </div>

        <div class="nlde-card">
            <h2>SMTP Configuration</h2>
            <p style="color:#666;font-size:13px;">Enable SMTP for reliable email delivery. Recommended: Amazon SES, SendGrid, or Brevo.</p>
            <div class="nlde-form-group">
                <label>Enable SMTP</label>
                <select name="nlde_smtp_enabled">
                    <option value="0" <?php selected(get_option('nlde_smtp_enabled'), '0'); ?>>Disabled (use WordPress default)</option>
                    <option value="1" <?php selected(get_option('nlde_smtp_enabled'), '1'); ?>>Enabled</option>
                </select>
            </div>
            <div class="nlde-form-group">
                <label>SMTP Host</label>
                <input type="text" name="nlde_smtp_host" value="<?php echo esc_attr(get_option('nlde_smtp_host')); ?>" placeholder="e.g. email-smtp.us-west-2.amazonaws.com">
            </div>
            <div class="nlde-form-group">
                <label>SMTP Port</label>
                <input type="number" name="nlde_smtp_port" value="<?php echo esc_attr(get_option('nlde_smtp_port', '587')); ?>" placeholder="587">
            </div>
            <div class="nlde-form-group">
                <label>SMTP Username</label>
                <input type="text" name="nlde_smtp_user" value="<?php echo esc_attr(get_option('nlde_smtp_user')); ?>">
            </div>
            <div class="nlde-form-group">
                <label>SMTP Password</label>
                <input type="password" name="nlde_smtp_pass" value="<?php echo esc_attr(get_option('nlde_smtp_pass')); ?>">
            </div>
            <div class="nlde-form-group">
                <label>Encryption</label>
                <select name="nlde_smtp_secure">
                    <option value="tls" <?php selected(get_option('nlde_smtp_secure'), 'tls'); ?>>TLS</option>
                    <option value="ssl" <?php selected(get_option('nlde_smtp_secure'), 'ssl'); ?>>SSL</option>
                    <option value="" <?php selected(get_option('nlde_smtp_secure'), ''); ?>>None</option>
                </select>
            </div>
        </div>

        <button type="submit" class="nlde-btn nlde-btn-primary">Save Settings</button>
    </form>

    <div class="nlde-card" style="margin-top:20px;">
        <h2>Send Test Email</h2>
        <p style="color:#666;font-size:13px;">Send a test email using your current SMTP settings to verify everything works.</p>
        <div class="nlde-form-group">
            <label>Recipient Email</label>
            <input type="email" id="nlde-test-email" value="<?php echo esc_attr(get_option('admin_email')); ?>" placeholder="you@example.com">
        </div>
        <button type="button" id="nlde-send-test" class="nlde-btn nlde-btn-primary">Send Test Email</button>
        <button type="button" id="nlde-debug-drip" class="nlde-btn nlde-btn-secondary" style="margin-left:8px;">Debug Drip Queue</button>
        <span id="nlde-test-result" style="margin-left:12px;font-size:13px;"></span>
    </div>

    <pre id="nlde-debug-output" style="display:none;background:#1e1e1e;color:#0f0;padding:15px;border-radius:6px;margin-top:12px;font-size:12px;overflow-x:auto;"></pre>

    <script>
    document.getElementById('nlde-send-test').addEventListener('click', function() {
        var btn = this;
        var result = document.getElementById('nlde-test-result');
        var email = document.getElementById('nlde-test-email').value;
        if (!email) { result.textContent = 'Enter an email address.'; result.style.color = '#dc3545'; return; }
        btn.disabled = true;
        btn.textContent = 'Sending...';
        result.textContent = '';
        var formData = new FormData();
        formData.append('action', 'nlde_send_test_email');
        formData.append('nonce', '<?php echo wp_create_nonce("nlde_test_email"); ?>');
        formData.append('email', email);
        fetch(ajaxurl, { method: 'POST', body: formData })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.success) {
                    result.textContent = '✅ ' + data.data;
                    result.style.color = '#28a745';
                } else {
                    result.textContent = '❌ ' + data.data;
                    result.style.color = '#dc3545';
                }
            })
            .catch(function() {
                result.textContent = '❌ Request failed.';
                result.style.color = '#dc3545';
            })
            .finally(function() {
                btn.disabled = false;
                btn.textContent = 'Send Test Email';
            });
    });

    document.getElementById('nlde-debug-drip').addEventListener('click', function() {
        var btn = this;
        var output = document.getElementById('nlde-debug-output');
        btn.disabled = true;
        btn.textContent = 'Checking...';
        var formData = new FormData();
        formData.append('action', 'nlde_debug_drip');
        formData.append('nonce', '<?php echo wp_create_nonce("nlde_test_email"); ?>');
        fetch(ajaxurl, { method: 'POST', body: formData })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                output.style.display = 'block';
                output.textContent = JSON.stringify(data, null, 2);
            })
            .catch(function() {
                output.style.display = 'block';
                output.textContent = 'Request failed.';
            })
            .finally(function() {
                btn.disabled = false;
                btn.textContent = 'Debug Drip Queue';
            });
    });
    </script>

    <div class="nlde-card" style="margin-top:20px;">
        <h2>SMTP Quick Setup Guides</h2>
        <h3>Amazon SES</h3>
        <ol>
            <li>Go to <a href="https://console.aws.amazon.com/ses/" target="_blank">Amazon SES Console</a></li>
            <li>Verify your sending domain under Verified Identities</li>
            <li>Go to SMTP Settings → Create SMTP Credentials</li>
            <li>Host: <code>email-smtp.[your-region].amazonaws.com</code></li>
            <li>Port: <code>587</code> | Encryption: <code>TLS</code></li>
            <li>Use the generated SMTP username and password</li>
        </ol>

        <h3>SendGrid</h3>
        <ol>
            <li>Sign up at <a href="https://sendgrid.com" target="_blank">sendgrid.com</a></li>
            <li>Go to Settings → API Keys → Create API Key</li>
            <li>Host: <code>smtp.sendgrid.net</code></li>
            <li>Port: <code>587</code> | Encryption: <code>TLS</code></li>
            <li>Username: <code>apikey</code> (literally)</li>
            <li>Password: Your API key</li>
        </ol>

        <h3>Brevo (Sendinblue)</h3>
        <ol>
            <li>Sign up at <a href="https://www.brevo.com" target="_blank">brevo.com</a></li>
            <li>Go to Settings → SMTP & API</li>
            <li>Host: <code>smtp-relay.brevo.com</code></li>
            <li>Port: <code>587</code> | Encryption: <code>TLS</code></li>
            <li>Use the provided login and SMTP key</li>
        </ol>
    </div>
</div>
