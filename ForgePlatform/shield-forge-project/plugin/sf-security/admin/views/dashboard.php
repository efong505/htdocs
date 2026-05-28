<?php if (!defined('ABSPATH')) exit; ?>
<div class="wrap sfs-wrap">
    <div class="sfs-hero-banner">
        <img src="<?php echo esc_url(SFS_PLUGIN_URL . 'assets/hero.png'); ?>" alt="ShieldForge" class="sfs-hero-img">
    </div>

    <div class="sfs-header">
        <img src="<?php echo esc_url(SFS_PLUGIN_URL . 'assets/logo-full.png'); ?>" alt="ShieldForge" class="sfs-logo">
        <span class="sfs-version">v<?php echo esc_html(SFS_VERSION); ?></span>
    </div>

    <div class="sfs-stats">
        <div class="sfs-stat-card">
            <div class="sfs-stat-icon">🛡️</div>
            <div class="sfs-stat-value"><?php echo esc_html($stats['blocked']); ?></div>
            <div class="sfs-stat-label">Threats Blocked (7d)</div>
        </div>
        <div class="sfs-stat-card">
            <div class="sfs-stat-icon">🔒</div>
            <div class="sfs-stat-value"><?php echo count($lockouts); ?></div>
            <div class="sfs-stat-label">Active Lockouts</div>
        </div>
        <div class="sfs-stat-card">
            <div class="sfs-stat-icon">⚠️</div>
            <div class="sfs-stat-value"><?php echo esc_html($stats['login_failures']); ?></div>
            <div class="sfs-stat-label">Failed Logins (7d)</div>
        </div>
        <div class="sfs-stat-card">
            <div class="sfs-stat-icon">🚫</div>
            <div class="sfs-stat-value"><?php echo esc_html($blocked_count); ?></div>
            <div class="sfs-stat-label">IPs Blocked</div>
        </div>
        <div class="sfs-stat-card">
            <div class="sfs-stat-icon">🌐</div>
            <div class="sfs-stat-value"><?php echo esc_html($stats['unique_ips_blocked']); ?></div>
            <div class="sfs-stat-label">Unique IPs Blocked (7d)</div>
        </div>
    </div>

    <!-- Active Lockouts -->
    <?php if (!empty($lockouts)): ?>
    <div class="sfs-card">
        <h2>Active Lockouts</h2>
        <table class="sfs-table">
            <thead><tr><th>IP Address</th><th>Reason</th><th>Lockout #</th><th>Expires</th><th>Action</th></tr></thead>
            <tbody>
                <?php foreach ($lockouts as $lock): ?>
                <tr>
                    <td><code><?php echo esc_html($lock->ip_address); ?></code></td>
                    <td><?php echo esc_html($lock->reason); ?></td>
                    <td><?php echo esc_html($lock->lockout_count); ?></td>
                    <td><?php echo esc_html(human_time_diff(time(), strtotime($lock->expires_at))); ?> remaining</td>
                    <td>
                        <button class="sfs-btn sfs-btn-sm sfs-btn-secondary sfs-unlock-btn" data-ip="<?php echo esc_attr($lock->ip_address); ?>">Unlock</button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <!-- Recent Activity -->
    <div class="sfs-card">
        <h2>Recent Activity <a href="<?php echo admin_url('admin.php?page=sfs-log'); ?>" class="sfs-btn sfs-btn-sm sfs-btn-secondary" style="float:right;">View All</a></h2>
        <?php if (empty($recent)): ?>
            <p style="color:#64748b;">No security events recorded yet.</p>
        <?php else: ?>
        <table class="sfs-table">
            <thead><tr><th>Time</th><th>Event</th><th>IP</th><th>Details</th></tr></thead>
            <tbody>
                <?php foreach ($recent as $event): ?>
                <tr>
                    <td style="white-space:nowrap;"><?php echo esc_html(human_time_diff(strtotime($event->created_at), time())); ?> ago</td>
                    <td><span class="sfs-badge sfs-badge-<?php echo esc_attr($event->severity); ?>"><?php echo esc_html($event->event_type); ?></span></td>
                    <td><code><?php echo esc_html($event->ip_address); ?></code></td>
                    <td><?php
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

    <!-- Security Status -->
    <div class="sfs-card">
        <h2>Security Status</h2>
        <ul class="sfs-checklist">
            <li class="<?php echo get_option('sfs_disable_xmlrpc', '1') === '1' ? 'sfs-check-pass' : 'sfs-check-warn'; ?>">
                XML-RPC <?php echo get_option('sfs_disable_xmlrpc', '1') === '1' ? 'disabled ✓' : 'enabled ⚠️'; ?>
            </li>
            <li class="<?php echo get_option('sfs_hide_login_errors', '1') === '1' ? 'sfs-check-pass' : 'sfs-check-warn'; ?>">
                Login errors <?php echo get_option('sfs_hide_login_errors', '1') === '1' ? 'hidden ✓' : 'visible ⚠️'; ?>
            </li>
            <li class="<?php echo get_option('sfs_block_user_enum', '1') === '1' ? 'sfs-check-pass' : 'sfs-check-warn'; ?>">
                User enumeration <?php echo get_option('sfs_block_user_enum', '1') === '1' ? 'blocked ✓' : 'allowed ⚠️'; ?>
            </li>
            <li class="<?php echo get_option('sfs_waf_enabled', '1') === '1' ? 'sfs-check-pass' : 'sfs-check-fail'; ?>">
                Firewall <?php echo get_option('sfs_waf_enabled', '1') === '1' ? 'active ✓' : 'disabled ✗'; ?>
            </li>
            <li class="sfs-check-pass">Brute force protection active ✓</li>
            <li class="sfs-check-pass">Rate limiting active ✓</li>
        </ul>
    </div>
</div>
