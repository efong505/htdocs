<?php if (!defined('ABSPATH')) exit; ?>
<div class="wrap nlde-wrap">
    <h1>Sequences</h1>

    <?php if (isset($_GET['deleted'])): ?>
        <div class="nlde-notice success">Sequence deleted.</div>
    <?php endif; ?>

    <div class="nlde-card">
        <h2>Create New Sequence</h2>
        <form method="post">
            <?php wp_nonce_field('nlde_admin_action', 'nlde_nonce'); ?>
            <input type="hidden" name="nlde_action" value="create_sequence">
            <div class="nlde-form-group">
                <label>Sequence Name</label>
                <input type="text" name="name" required placeholder="e.g. Survival Kit Drip">
            </div>
            <div class="nlde-form-group">
                <label>Description (optional)</label>
                <input type="text" name="description" placeholder="Brief description of this sequence">
            </div>
            <button type="submit" class="nlde-btn nlde-btn-primary">Create Sequence</button>
        </form>
    </div>

    <?php if (empty($sequences)): ?>
    <div class="nlde-card" style="text-align:center;padding:40px 20px;">
        <p style="font-size:16px;color:#666;margin-bottom:16px;">No sequences yet. Create one above or start from a template.</p>
        <a href="<?php echo admin_url('admin.php?page=nlde-templates'); ?>" class="nlde-btn nlde-btn-primary">📋 Browse Sequence Templates</a>
    </div>
    <?php endif; ?>

    <?php if (!empty($sequences)): ?>
    <table class="nlde-table">
        <thead>
            <tr>
                <th>Name</th>
                <th>Slug (for shortcode)</th>
                <th>Status</th>
                <th>Created</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($sequences as $seq): ?>
            <tr>
                <td><strong><a href="<?php echo admin_url('admin.php?page=nlde-sequences&view=edit&id=' . $seq->id); ?>"><?php echo esc_html($seq->name); ?></a></strong></td>
                <td><code><?php echo esc_html($seq->slug); ?></code></td>
                <td><span class="nlde-badge <?php echo esc_attr($seq->status); ?>"><?php echo esc_html($seq->status); ?></span></td>
                <td><?php echo esc_html($seq->created_at); ?></td>
                <td>
                    <a href="<?php echo admin_url('admin.php?page=nlde-sequences&view=edit&id=' . $seq->id); ?>" class="nlde-btn nlde-btn-primary" style="padding:4px 10px;font-size:12px;">Edit</a>
                    <form method="post" style="display:inline;" onsubmit="return confirm('Delete this sequence and all its emails?');">
                        <?php wp_nonce_field('nlde_admin_action', 'nlde_nonce'); ?>
                        <input type="hidden" name="nlde_action" value="delete_sequence">
                        <input type="hidden" name="sequence_id" value="<?php echo esc_attr($seq->id); ?>">
                        <button type="submit" class="nlde-btn nlde-btn-danger" style="padding:4px 10px;font-size:12px;">Delete</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>
