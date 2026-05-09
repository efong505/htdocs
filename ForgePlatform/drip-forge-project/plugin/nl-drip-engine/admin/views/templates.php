<?php if (!defined('ABSPATH')) exit; ?>
<div class="wrap nlde-wrap">
    <h1>Sequence Templates</h1>
    <p style="color:#666;margin-bottom:20px;">Pre-built drip sequences you can import with one click. Customize the emails after importing to match your brand and voice.</p>

    <?php if (isset($_GET['imported'])): ?>
        <div class="nlde-notice success">
            Template imported! <a href="<?php echo esc_url(admin_url('admin.php?page=nlde-sequences&view=edit&id=' . (int) $_GET['seq_id'])); ?>">Edit your new sequence →</a>
        </div>
    <?php endif; ?>

    <div class="nlde-template-grid">
        <?php foreach ($templates as $slug => $template): ?>
        <div class="nlde-template-card">
            <div class="nlde-template-category"><?php echo esc_html($template['category']); ?></div>
            <h3><?php echo esc_html($template['name']); ?></h3>
            <p><?php echo esc_html($template['description']); ?></p>
            <div class="nlde-template-meta">
                <span><?php echo count($template['emails']); ?> emails</span>
                <span><?php echo esc_html($template['emails'][count($template['emails']) - 1]['delay_days']); ?> days</span>
            </div>
            <div class="nlde-template-timeline">
                <?php foreach ($template['emails'] as $email): ?>
                <div class="nlde-template-email">
                    <span class="nlde-template-day">Day <?php echo esc_html($email['delay_days']); ?></span>
                    <span class="nlde-template-subject"><?php echo esc_html($email['subject']); ?></span>
                </div>
                <?php endforeach; ?>
            </div>
            <form method="post" style="margin-top:12px;">
                <?php wp_nonce_field('nlde_admin_action', 'nlde_nonce'); ?>
                <input type="hidden" name="nlde_action" value="import_template">
                <input type="hidden" name="template_slug" value="<?php echo esc_attr($slug); ?>">
                <button type="submit" class="nlde-btn nlde-btn-primary" style="width:100%;">Import as Draft</button>
            </form>
        </div>
        <?php endforeach; ?>
    </div>
</div>
