<?php if (!defined('ABSPATH')) exit;
$presets = SFS_Firewall::get_presets();
$profiles = SFS_Firewall::get_saved_profiles();
?>
<div class="wrap sfs-wrap">
    <div class="sfs-header">
        <img src="<?php echo esc_url(SFS_PLUGIN_URL . 'assets/logo-full.png'); ?>" alt="ShieldForge" class="sfs-logo">
        <span class="sfs-header-subtitle">Firewall</span>
    </div>

    <!-- Configuration Bar -->
    <div class="sfs-card sfs-config-bar">
        <div class="sfs-config-row">
            <div class="sfs-config-left">
                <label style="color:#94a3b8;font-size:13px;margin-right:8px;">Configuration:</label>
                <select id="sfs-preset-select" class="sfs-select">
                    <optgroup label="Presets">
                        <?php foreach ($presets as $key => $preset): ?>
                        <option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($preset['name']); ?> — <?php echo esc_html($preset['description']); ?></option>
                        <?php endforeach; ?>
                    </optgroup>
                    <?php if (!empty($profiles)): ?>
                    <optgroup label="Saved Profiles">
                        <?php foreach ($profiles as $slug => $profile): ?>
                        <option value="<?php echo esc_attr($slug); ?>"><?php echo esc_html($profile['name']); ?> (saved <?php echo esc_html(human_time_diff(strtotime($profile['saved_at']), time())); ?> ago)</option>
                        <?php endforeach; ?>
                    </optgroup>
                    <?php endif; ?>
                </select>
                <button class="sfs-btn sfs-btn-primary sfs-btn-sm" id="sfs-load-preset">Load</button>
                <?php if (!empty($profiles)): ?>
                <button class="sfs-btn sfs-btn-danger sfs-btn-sm" id="sfs-delete-profile">Delete</button>
                <?php endif; ?>
            </div>
            <div class="sfs-config-right">
                <input type="text" id="sfs-profile-name" placeholder="Profile name..." class="sfs-input-sm">
                <button class="sfs-btn sfs-btn-secondary sfs-btn-sm" id="sfs-save-profile">Save Current</button>
            </div>
        </div>
    </div>

    <!-- WAF Status -->
    <div class="sfs-card">
        <h2>WAF Status</h2>
        <p>
            <?php if (get_option('sfs_waf_enabled', '1') === '1'): ?>
                <span class="sfs-badge sfs-badge-info">Active</span> — <?php echo count(array_filter($rules, fn($r) => $r['enabled'])); ?> of <?php echo count($rules); ?> rules enabled
            <?php else: ?>
                <span class="sfs-badge sfs-badge-critical">Disabled</span> — <a href="<?php echo admin_url('admin.php?page=sfs-settings'); ?>">Enable in Settings</a>
            <?php endif; ?>
        </p>
    </div>

    <!-- Rules -->
    <div class="sfs-card">
        <h2>Firewall Rules</h2>
        <table class="sfs-table">
            <thead><tr><th>ID</th><th>Rule</th><th>Category</th><th>Status</th><th>Toggle</th></tr></thead>
            <tbody>
                <?php foreach ($rules as $rule): ?>
                <tr data-rule-id="<?php echo esc_attr($rule['id']); ?>">
                    <td><code><?php echo esc_html($rule['id']); ?></code></td>
                    <td><?php echo esc_html($rule['name']); ?></td>
                    <td><span class="sfs-badge sfs-badge-info"><?php echo esc_html($rule['category']); ?></span></td>
                    <td class="sfs-rule-status"><?php echo $rule['enabled'] ? '<span class="sfs-badge sfs-badge-info">Active</span>' : '<span class="sfs-badge sfs-badge-warning">Disabled</span>'; ?></td>
                    <td>
                        <label class="sfs-toggle">
                            <input type="checkbox" class="sfs-rule-toggle" data-rule="<?php echo esc_attr($rule['id']); ?>" <?php checked($rule['enabled']); ?>>
                            <span class="sfs-toggle-slider"></span>
                        </label>
                    </td>
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
