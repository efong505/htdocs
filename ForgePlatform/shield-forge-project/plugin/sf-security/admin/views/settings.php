<?php if (!defined('ABSPATH')) exit; ?>
<div class="wrap sfs-wrap">
    <div class="sfs-header">
        <img src="<?php echo esc_url(SFS_PLUGIN_URL . 'assets/logo-full.png'); ?>" alt="ShieldForge" class="sfs-logo">
        <span class="sfs-header-subtitle">Settings</span>
    </div>

    <?php if (isset($_GET['saved'])): ?>
        <div class="sfs-notice success">Settings saved.</div>
    <?php endif; ?>

    <form method="post">
        <?php wp_nonce_field('sfs_settings', 'sfs_nonce'); ?>
        <input type="hidden" name="sfs_save_settings" value="1">

        <div class="sfs-settings-grid">
            <!-- Login Security -->
            <div class="sfs-card">
                <h2>Login Security</h2>
                <div class="sfs-form-group">
                    <label>Failed attempts before lockout</label>
                    <input type="number" name="sfs_lockout_threshold" value="<?php echo esc_attr(get_option('sfs_lockout_threshold', 5)); ?>" min="1" max="20">
                </div>
                <div class="sfs-form-group">
                    <label>Time window (minutes)</label>
                    <input type="number" name="sfs_lockout_window" value="<?php echo esc_attr(get_option('sfs_lockout_window', 15)); ?>" min="1" max="60">
                    <p class="description">Count failures within this window.</p>
                </div>
                <div class="sfs-form-group">
                    <label>Lockout duration (minutes)</label>
                    <input type="number" name="sfs_lockout_duration" value="<?php echo esc_attr(get_option('sfs_lockout_duration', 15)); ?>" min="1" max="1440">
                </div>
                <div class="sfs-form-group">
                    <label><input type="checkbox" name="sfs_lockout_escalation" value="1" <?php checked(get_option('sfs_lockout_escalation', '1'), '1'); ?>> Escalate lockout duration on repeat offenders</label>
                </div>
                <div class="sfs-form-group">
                    <label>Permanent ban after N lockouts (0 = never)</label>
                    <input type="number" name="sfs_permanent_ban_after" value="<?php echo esc_attr(get_option('sfs_permanent_ban_after', 3)); ?>" min="0" max="20">
                </div>
            </div>

            <!-- Hardening -->
            <div class="sfs-card">
                <h2>Hardening</h2>
                <div class="sfs-form-group">
                    <label><input type="checkbox" name="sfs_disable_xmlrpc" value="1" <?php checked(get_option('sfs_disable_xmlrpc', '1'), '1'); ?>> Disable XML-RPC</label>
                    <p class="description">Blocks xmlrpc.php — used by most brute force bots.</p>
                </div>
                <div class="sfs-form-group">
                    <label><input type="checkbox" name="sfs_hide_login_errors" value="1" <?php checked(get_option('sfs_hide_login_errors', '1'), '1'); ?>> Hide login error details</label>
                    <p class="description">Don't reveal whether username or password was wrong.</p>
                </div>
                <div class="sfs-form-group">
                    <label><input type="checkbox" name="sfs_block_user_enum" value="1" <?php checked(get_option('sfs_block_user_enum', '1'), '1'); ?>> Block user enumeration</label>
                    <p class="description">Blocks ?author=N and REST API user endpoints for unauthenticated users.</p>
                </div>
            </div>

            <!-- Rate Limiting -->
            <div class="sfs-card">
                <h2>Rate Limiting</h2>
                <div class="sfs-form-group">
                    <label>General rate limit (requests/minute)</label>
                    <input type="number" name="sfs_rate_limit" value="<?php echo esc_attr(get_option('sfs_rate_limit', 120)); ?>" min="10" max="1000">
                </div>
                <div class="sfs-form-group">
                    <label>Login page rate limit (requests/minute)</label>
                    <input type="number" name="sfs_rate_limit_login" value="<?php echo esc_attr(get_option('sfs_rate_limit_login', 10)); ?>" min="1" max="60">
                </div>
            </div>

            <!-- Firewall -->
            <div class="sfs-card">
                <h2>Firewall</h2>
                <div class="sfs-form-group">
                    <label><input type="checkbox" name="sfs_waf_enabled" value="1" <?php checked(get_option('sfs_waf_enabled', '1'), '1'); ?>> Enable Web Application Firewall</label>
                    <p class="description">Blocks SQL injection, XSS, path traversal, and other common attacks.</p>
                </div>
            </div>

            <!-- Notifications -->
            <div class="sfs-card">
                <h2>Notifications</h2>
                <div class="sfs-form-group">
                    <label><input type="checkbox" name="sfs_notify_lockout" value="1" <?php checked(get_option('sfs_notify_lockout', '1'), '1'); ?>> Email admin on lockout</label>
                    <p class="description">Sends to <?php echo esc_html(get_option('admin_email')); ?></p>
                </div>
            </div>

            <!-- Maintenance -->
            <div class="sfs-card">
                <h2>Maintenance</h2>
                <div class="sfs-form-group">
                    <label>Log retention (days)</label>
                    <input type="number" name="sfs_log_retention_days" value="<?php echo esc_attr(get_option('sfs_log_retention_days', 90)); ?>" min="7" max="365">
                    <p class="description">Automatically delete log entries older than this.</p>
                </div>
            </div>
        </div>

        <p style="margin-top:20px;">
            <button type="submit" class="sfs-btn sfs-btn-primary">Save Settings</button>
        </p>
    </form>
</div>
