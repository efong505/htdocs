<?php if (!defined('ABSPATH')) exit; ?>
<div class="wrap sfs-wrap">
    <div class="sfs-header">
        <img src="<?php echo esc_url(SFS_PLUGIN_URL . 'assets/logo-full.png'); ?>" alt="ShieldForge" class="sfs-logo">
        <span class="sfs-header-subtitle">Activity Log</span>
    </div>

    <div class="sfs-card">
        <form method="get" class="sfs-inline-form">
            <input type="hidden" name="page" value="sfs-log">
            <select name="type">
                <option value="">All Events</option>
                <option value="login_failed" <?php selected($filter_type, 'login_failed'); ?>>Login Failed</option>
                <option value="login_success" <?php selected($filter_type, 'login_success'); ?>>Login Success</option>
                <option value="lockout" <?php selected($filter_type, 'lockout'); ?>>Lockout</option>
                <option value="blocked_ip" <?php selected($filter_type, 'blocked_ip'); ?>>Blocked IP</option>
                <option value="waf_block" <?php selected($filter_type, 'waf_block'); ?>>WAF Block</option>
                <option value="rate_limited" <?php selected($filter_type, 'rate_limited'); ?>>Rate Limited</option>
                <option value="auto_banned" <?php selected($filter_type, 'auto_banned'); ?>>Auto Banned</option>
            </select>
            <select name="severity">
                <option value="">All Severities</option>
                <option value="info" <?php selected($filter_severity, 'info'); ?>>Info</option>
                <option value="warning" <?php selected($filter_severity, 'warning'); ?>>Warning</option>
                <option value="critical" <?php selected($filter_severity, 'critical'); ?>>Critical</option>
            </select>
            <button type="submit" class="sfs-btn sfs-btn-primary">Filter</button>
        </form>
    </div>

    <div class="sfs-card">
        <?php if (empty($events)): ?>
            <p style="color:#64748b;">No events match your filter.</p>
        <?php else: ?>
        <table class="sfs-table">
            <thead><tr><th>Time</th><th>Severity</th><th>Event</th><th>IP</th><th>Username</th><th>Details</th></tr></thead>
            <tbody>
                <?php foreach ($events as $event): ?>
                <tr>
                    <td style="white-space:nowrap;font-size:12px;"><?php echo esc_html($event->created_at); ?></td>
                    <td><span class="sfs-badge sfs-badge-<?php echo esc_attr($event->severity); ?>"><?php echo esc_html($event->severity); ?></span></td>
                    <td><?php echo esc_html($event->event_type); ?></td>
                    <td><code><?php echo esc_html($event->ip_address); ?></code></td>
                    <td><?php echo esc_html($event->username ?: '—'); ?></td>
                    <td style="max-width:250px;overflow:hidden;text-overflow:ellipsis;font-size:12px;"><?php
                        $details = json_decode($event->details, true);
                        if (is_array($details)) {
                            echo esc_html(implode(', ', array_map(fn($k, $v) => "$k: $v", array_keys($details), $details)));
                        } else {
                            echo esc_html($event->details);
                        }
                    ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>
