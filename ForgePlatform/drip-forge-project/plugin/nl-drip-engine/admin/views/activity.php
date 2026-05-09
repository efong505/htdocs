<?php if (!defined('ABSPATH')) exit;

$sequence_id = (int) ($_GET['sequence_id'] ?? 0);
$email_position = isset($_GET['email_position']) ? (int) $_GET['email_position'] : null;
$filter = sanitize_text_field($_GET['filter'] ?? '');
$subscriber_id = (int) ($_GET['subscriber_id'] ?? 0);

$args = [];
if ($sequence_id) $args['sequence_id'] = $sequence_id;
if ($email_position !== null) $args['email_position'] = $email_position;
if ($filter) $args['filter'] = $filter;
if ($subscriber_id) $args['subscriber_id'] = $subscriber_id;

$logs = NLDE_Analytics::get_send_log($args);

// Build page title
$title = 'Activity Log';
if ($filter) $title .= ' — ' . ucfirst($filter);
if ($subscriber_id) {
	$sub = NLDE_Subscriber::get($subscriber_id);
	if ($sub) $title .= ' — ' . esc_html($sub->email);
}
?>
<div class="wrap nlde-wrap">
	<h1>
		<?php if ($sequence_id): ?>
			<a href="<?php echo admin_url('admin.php?page=nlde-sequences&view=edit&id=' . $sequence_id); ?>">&larr; Back to Sequence</a> /
		<?php elseif ($subscriber_id): ?>
			<a href="<?php echo admin_url('admin.php?page=nlde-subscribers'); ?>">&larr; Back to Subscribers</a> /
		<?php endif; ?>
		<?php echo esc_html($title); ?>
	</h1>

	<?php if ($sequence_id && $filter): ?>
	<div style="margin-bottom:16px;">
		<a href="<?php echo esc_url(admin_url('admin.php?page=nlde-activity&sequence_id=' . $sequence_id . '&email_position=' . $email_position . '&filter=sent')); ?>" class="nlde-btn <?php echo $filter === 'sent' ? 'nlde-btn-primary' : 'nlde-btn-secondary'; ?>" style="padding:4px 12px;font-size:12px;">Sent</a>
		<a href="<?php echo esc_url(admin_url('admin.php?page=nlde-activity&sequence_id=' . $sequence_id . '&email_position=' . $email_position . '&filter=opened')); ?>" class="nlde-btn <?php echo $filter === 'opened' ? 'nlde-btn-primary' : 'nlde-btn-secondary'; ?>" style="padding:4px 12px;font-size:12px;">Opened</a>
		<a href="<?php echo esc_url(admin_url('admin.php?page=nlde-activity&sequence_id=' . $sequence_id . '&email_position=' . $email_position . '&filter=clicked')); ?>" class="nlde-btn <?php echo $filter === 'clicked' ? 'nlde-btn-primary' : 'nlde-btn-secondary'; ?>" style="padding:4px 12px;font-size:12px;">Clicked</a>
	</div>
	<?php endif; ?>

	<div class="nlde-card">
		<table class="nlde-table">
			<thead>
				<tr>
					<th>Subscriber</th>
					<th>Email Subject</th>
					<th>Sequence</th>
					<th>Sent</th>
					<th>Opened</th>
					<th>Clicked</th>
					<th>Status</th>
				</tr>
			</thead>
			<tbody>
				<?php if (empty($logs)): ?>
					<tr><td colspan="7" style="text-align:center;color:#999;">No activity found.</td></tr>
				<?php else: ?>
					<?php foreach ($logs as $log): ?>
					<tr>
						<td>
							<a href="<?php echo esc_url(admin_url('admin.php?page=nlde-activity&subscriber_id=' . $log->subscriber_id)); ?>">
								<?php echo esc_html($log->first_name ? $log->first_name . ' ' . $log->last_name : $log->email); ?>
							</a>
							<div style="font-size:11px;color:#999;"><?php echo esc_html($log->email); ?></div>
						</td>
						<td><?php echo esc_html($log->subject); ?></td>
						<td><?php echo esc_html($log->sequence_name); ?></td>
						<td><?php echo $log->sent_at ? esc_html(date('M j, Y g:ia', strtotime($log->sent_at))) : '—'; ?></td>
						<td><?php echo $log->opened_at ? esc_html(date('M j, Y g:ia', strtotime($log->opened_at))) : '—'; ?></td>
						<td><?php echo $log->clicked_at ? esc_html(date('M j, Y g:ia', strtotime($log->clicked_at))) : '—'; ?></td>
						<td><span class="nlde-badge <?php echo esc_attr($log->status); ?>"><?php echo esc_html($log->status); ?></span></td>
					</tr>
					<?php endforeach; ?>
				<?php endif; ?>
			</tbody>
		</table>
	</div>
</div>
