<?php if (!defined('ABSPATH')) exit; ?>
<div class="wrap sfs-wrap">
    <div class="sfs-header">
        <img src="<?php echo esc_url(SFS_PLUGIN_URL . 'assets/logo-full.png'); ?>" alt="ShieldForge" class="sfs-logo">
        <span class="sfs-header-subtitle">IP Blocklist</span>
    </div>

    <!-- Add IP Form -->
    <div class="sfs-card">
        <h2>Add IP</h2>
        <div class="sfs-inline-form" id="sfs-add-ip-form">
            <input type="text" id="sfs-ip-input" placeholder="IP address or CIDR (e.g. 192.168.1.0/24)" style="width:220px;">
            <select id="sfs-ip-type">
                <option value="block">Block</option>
                <option value="allow">Allow</option>
            </select>
            <input type="text" id="sfs-ip-reason" placeholder="Reason (optional)" style="width:200px;">
            <button class="sfs-btn sfs-btn-primary" id="sfs-add-ip-btn">Add</button>
        </div>
    </div>

    <!-- Active Lockouts -->
    <?php if (!empty($lockouts)): ?>
    <div class="sfs-card">
        <h2>Active Lockouts (<?php echo count($lockouts); ?>)</h2>
        <table class="sfs-table">
            <thead><tr><th>IP</th><th>Reason</th><th>#</th><th>Expires</th><th>Action</th></tr></thead>
            <tbody>
                <?php foreach ($lockouts as $lock): ?>
                <tr>
                    <td><code><?php echo esc_html($lock->ip_address); ?></code></td>
                    <td><?php echo esc_html($lock->reason); ?></td>
                    <td><?php echo esc_html($lock->lockout_count); ?></td>
                    <td><?php echo esc_html(human_time_diff(time(), strtotime($lock->expires_at))); ?></td>
                    <td><button class="sfs-btn sfs-btn-sm sfs-btn-secondary sfs-unlock-btn" data-ip="<?php echo esc_attr($lock->ip_address); ?>">Unlock</button></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <!-- Blocked IPs -->
    <div class="sfs-card">
        <h2>Blocked IPs (<?php echo count($blocked); ?>)</h2>
        <?php if (empty($blocked)): ?>
            <p style="color:#64748b;">No blocked IPs.</p>
        <?php else: ?>
        <table class="sfs-table">
            <thead><tr><th>IP</th><th>Reason</th><th>Source</th><th>Added</th><th>Expires</th><th>Action</th></tr></thead>
            <tbody>
                <?php foreach ($blocked as $entry): ?>
                <tr>
                    <td><code><?php echo esc_html($entry->ip_address); ?></code></td>
                    <td><?php echo esc_html($entry->reason); ?></td>
                    <td><span class="sfs-badge sfs-badge-info"><?php echo esc_html($entry->source); ?></span></td>
                    <td><?php echo esc_html(human_time_diff(strtotime($entry->created_at), time())); ?> ago</td>
                    <td><?php echo $entry->expires_at ? esc_html(human_time_diff(time(), strtotime($entry->expires_at))) : 'Permanent'; ?></td>
                    <td><button class="sfs-btn sfs-btn-sm sfs-btn-danger sfs-remove-btn" data-id="<?php echo esc_attr($entry->id); ?>">Remove</button></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

    <!-- Allowed IPs -->
    <div class="sfs-card">
        <h2>Allowed IPs (<?php echo count($allowed); ?>)</h2>
        <?php if (empty($allowed)): ?>
            <p style="color:#64748b;">No allowlisted IPs. Allowlisted IPs bypass all security checks.</p>
        <?php else: ?>
        <table class="sfs-table">
            <thead><tr><th>IP</th><th>Reason</th><th>Added</th><th>Action</th></tr></thead>
            <tbody>
                <?php foreach ($allowed as $entry): ?>
                <tr>
                    <td><code><?php echo esc_html($entry->ip_address); ?></code></td>
                    <td><?php echo esc_html($entry->reason); ?></td>
                    <td><?php echo esc_html(human_time_diff(strtotime($entry->created_at), time())); ?> ago</td>
                    <td><button class="sfs-btn sfs-btn-sm sfs-btn-danger sfs-remove-btn" data-id="<?php echo esc_attr($entry->id); ?>">Remove</button></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>
