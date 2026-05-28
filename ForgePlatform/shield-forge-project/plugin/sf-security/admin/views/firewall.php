<?php if (!defined('ABSPATH')) exit; ?>
<div class="wrap sfs-wrap">
    <div class="sfs-header">
        <img src="<?php echo esc_url(SFS_PLUGIN_URL . 'assets/logo-full.png'); ?>" alt="ShieldForge" class="sfs-logo">
        <span class="sfs-header-subtitle">Firewall</span>
    </div>

    <div class="sfs-card">
        <h2>WAF Status</h2>
        <p>
            <?php if (get_option('sfs_waf_enabled', '1') === '1'): ?>
                <span class="sfs-badge sfs-badge-info">Active</span> — <?php echo count($rules); ?> rules loaded
            <?php else: ?>
                <span class="sfs-badge sfs-badge-critical">Disabled</span> — <a href="<?php echo admin_url('admin.php?page=sfs-settings'); ?>">Enable in Settings</a>
            <?php endif; ?>
        </p>
    </div>

    <!-- Rules -->
    <div class="sfs-card">
        <h2>Active Rules</h2>
        <table class="sfs-table">
            <thead><tr><th>ID</th><th>Rule</th><th>Category</th><th>Status</th></tr></thead>
            <tbody>
                <?php foreach ($rules as $rule): ?>
                <tr>
                    <td><code><?php echo esc_html($rule['id']); ?></code></td>
                    <td><?php echo esc_html($rule['name']); ?></td>
                    <td><span class="sfs-badge sfs-badge-info"><?php echo esc_html($rule['category']); ?></span></td>
                    <td><?php echo $rule['enabled'] ? '<span class="sfs-badge sfs-badge-info">Active</span>' : '<span class="sfs-badge sfs-badge-warning">Disabled</span>'; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Recent WAF Blocks -->
    <div class="sfs-card">
        <h2>Recent Firewall Blocks</h2>
        <?php if (empty($recent)): ?>
            <p style="color:#64748b;">No firewall blocks recorded yet.</p>
        <?php else: ?>
        <table class="sfs-table">
            <thead><tr><th>Time</th><th>IP</th><th>Rule</th><th>URI</th></tr></thead>
            <tbody>
                <?php foreach ($recent as $event): ?>
                <?php $details = json_decode($event->details, true); ?>
                <tr>
                    <td style="white-space:nowrap;"><?php echo esc_html(human_time_diff(strtotime($event->created_at), time())); ?> ago</td>
                    <td><code><?php echo esc_html($event->ip_address); ?></code></td>
                    <td><?php echo esc_html($details['rule_name'] ?? $details['rule_id'] ?? '—'); ?></td>
                    <td style="max-width:300px;overflow:hidden;text-overflow:ellipsis;"><code><?php echo esc_html($details['uri'] ?? '—'); ?></code></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>
