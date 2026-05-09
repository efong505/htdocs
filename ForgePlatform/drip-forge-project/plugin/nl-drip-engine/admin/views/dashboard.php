<?php if (!defined('ABSPATH')) exit; ?>
<div class="wrap nlde-wrap">
    <h1>DripForge — Dashboard</h1>

    <?php if (!get_user_meta(get_current_user_id(), 'nlde_dismiss_inline_guide', true)): ?>
    <div class="nlde-inline-guide nlde-card" id="nlde-inline-guide">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;">
            <h2>👋 New to DripForge?</h2>
            <a href="#" class="nlde-dismiss-guide" onclick="jQuery.post(ajaxurl,{action:'nlde_dismiss_guide',nonce:'<?php echo wp_create_nonce('nlde_dismiss_guide'); ?>'},function(){document.getElementById('nlde-inline-guide').style.display='none';});return false;" style="color:#999;text-decoration:none;font-size:18px;" title="Dismiss">&times;</a>
        </div>
        <p>Here are some resources to get you up and running:</p>
        <div style="display:flex;gap:12px;flex-wrap:wrap;">
            <a href="<?php echo admin_url('admin.php?page=nlde-templates'); ?>" class="nlde-btn nlde-btn-primary">📋 Browse Templates</a>
            <a href="<?php echo admin_url('admin.php?page=nlde-guide'); ?>" class="nlde-btn nlde-btn-secondary">📖 Read the Guide</a>
            <a href="<?php echo admin_url('admin.php?page=nlde-sequences'); ?>" class="nlde-btn nlde-btn-secondary">✏️ Create a Sequence</a>
        </div>
    </div>
    <?php endif; ?>

    <div class="nlde-stats">
        <div class="nlde-stat-card">
            <div class="number"><?php echo esc_html($stats['active_subscribers']); ?></div>
            <div class="label">Active Subscribers</div>
        </div>
        <div class="nlde-stat-card">
            <div class="number"><?php echo esc_html($stats['total_subscribers']); ?></div>
            <div class="label">Total Subscribers</div>
        </div>
        <div class="nlde-stat-card">
            <div class="number"><?php echo esc_html($stats['total_sent']); ?></div>
            <div class="label">Emails Sent</div>
        </div>
        <div class="nlde-stat-card">
            <div class="number"><?php echo esc_html($open_rate); ?>%</div>
            <div class="label">Open Rate</div>
        </div>
        <div class="nlde-stat-card">
            <div class="number"><?php echo esc_html($click_rate); ?>%</div>
            <div class="label">Click Rate</div>
        </div>
    </div>

    <div class="nlde-card">
        <h2>Quick Start</h2>
        <ol>
            <li><strong>Configure SMTP</strong> — Go to <a href="<?php echo admin_url('admin.php?page=nlde-settings'); ?>">Settings</a> and enter your SMTP credentials.</li>
            <li><strong>Create a Sequence</strong> — Go to <a href="<?php echo admin_url('admin.php?page=nlde-sequences'); ?>">Sequences</a> and create your drip campaign.</li>
            <li><strong>Add Emails</strong> — Add emails to your sequence with delay timing.</li>
            <li><strong>Embed the Form</strong> — Use the shortcode on any page:
                <br><code>[nl_signup_form sequence="your-sequence-slug" button_text="Get My Free Kit" redirect="/thank-you/"]</code>
            </li>
            <li><strong>Activate</strong> — Set your sequence status to "Active" and you're live!</li>
        </ol>
    </div>

    <div class="nlde-card">
        <h2>Available Merge Tags</h2>
        <div class="nlde-merge-tags">
            <code>{first_name}</code> — Subscriber's first name<br>
            <code>{last_name}</code> — Subscriber's last name<br>
            <code>{email}</code> — Subscriber's email<br>
            <code>{site_name}</code> — Your site name<br>
            <code>{site_url}</code> — Your site URL<br>
            <code>{unsubscribe_link}</code> — Unsubscribe link<br>
            <code>{rfp_link}</code> — Request for Proposal page<br>
            <code>{download_link}</code> — Survival Kit download page
        </div>
    </div>
</div>
