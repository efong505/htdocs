<?php
if ( ! defined( 'ABSPATH' ) ) exit;
?>
<div class="wrap lf-wrap">
	<div class="lf-header">
		<h1 class="lf-header__title">
			<span class="lf-header__icon"><span class="dashicons dashicons-admin-network"></span></span>
			LicenseForge — Orders
		</h1>
	</div>

	<?php settings_errors( 'wplp_orders' ); ?>

	<?php if ( empty( $orders ) ) : ?>
		<p><?php esc_html_e( 'No orders yet.', 'wp-license-platform' ); ?></p>
	<?php else : ?>
	<table class="widefat striped">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Order', 'wp-license-platform' ); ?></th>
				<th><?php esc_html_e( 'Customer', 'wp-license-platform' ); ?></th>
				<th><?php esc_html_e( 'Product', 'wp-license-platform' ); ?></th>
				<th><?php esc_html_e( 'Total', 'wp-license-platform' ); ?></th>
				<th><?php esc_html_e( 'Status', 'wp-license-platform' ); ?></th>
				<th><?php esc_html_e( 'Date', 'wp-license-platform' ); ?></th>
				<th><?php esc_html_e( 'Actions', 'wp-license-platform' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $orders as $order ) : ?>
			<tr>
				<td><strong><?php echo esc_html( $order->order_number ); ?></strong></td>
				<td><?php echo esc_html( $order->customer_email ?? '—' ); ?></td>
				<td><?php echo esc_html( ( $order->product_name ?? '' ) . ' — ' . ( $order->tier_name ?? '' ) ); ?></td>
				<td><strong>$<?php echo esc_html( number_format( $order->total, 2 ) ); ?></strong></td>
				<td><span class="wplp-status wplp-status-<?php echo esc_attr( $order->status ); ?>"><?php echo esc_html( ucfirst( $order->status ) ); ?></span></td>
				<td><?php echo esc_html( date_i18n( 'M j, Y', strtotime( $order->created_at ) ) ); ?></td>
				<td>
					<select class="wplp-order-status-select" data-order-id="<?php echo esc_attr( $order->id ); ?>" style="font-size:12px;">
						<?php foreach ( array( 'pending', 'completed', 'refunded', 'failed' ) as $s ) : ?>
						<option value="<?php echo esc_attr( $s ); ?>" <?php selected( $order->status, $s ); ?>><?php echo esc_html( ucfirst( $s ) ); ?></option>
						<?php endforeach; ?>
					</select>
					<?php
					$delete_url = wp_nonce_url(
						add_query_arg( array( 'page' => 'wplp_orders', 'wplp_delete_order' => $order->id ), admin_url( 'admin.php' ) ),
						'wplp_delete_order_' . $order->id
					);
					?>
					<a href="<?php echo esc_url( $delete_url ); ?>" class="button button-small" style="color:#d63638;font-size:11px;" onclick="return confirm('Delete this order permanently?');">Delete</a>
				</td>
			</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
	<?php endif; ?>
</div>
