<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wplp-portal">
	<h2><?php printf( esc_html__( 'Welcome, %s', 'wp-license-platform' ), esc_html( $customer->first_name ?: $customer->email ) ); ?></h2>
	<div class="wplp-portal-grid">
		<div class="wplp-portal-card">
			<span class="wplp-portal-count"><?php echo esc_html( $active_count ); ?></span>
			<span class="wplp-portal-label"><?php esc_html_e( 'Active Licenses', 'wp-license-platform' ); ?></span>
		</div>
	</div>

	<?php if ( ! empty( $recent_orders ) ) : ?>
	<h3><?php esc_html_e( 'Recent Orders', 'wp-license-platform' ); ?></h3>
	<table class="wplp-table">
		<thead><tr><th><?php esc_html_e( 'Order', 'wp-license-platform' ); ?></th><th><?php esc_html_e( 'Product', 'wp-license-platform' ); ?></th><th><?php esc_html_e( 'Total', 'wp-license-platform' ); ?></th><th><?php esc_html_e( 'Status', 'wp-license-platform' ); ?></th><th><?php esc_html_e( 'Date', 'wp-license-platform' ); ?></th></tr></thead>
		<tbody>
		<?php foreach ( $recent_orders as $order ) : ?>
			<tr>
				<td><?php echo esc_html( $order->order_number ); ?></td>
				<td><?php echo esc_html( $order->product_name ); ?></td>
				<td>$<?php echo esc_html( number_format( $order->total, 2 ) ); ?></td>
				<td><?php echo esc_html( ucfirst( $order->status ) ); ?></td>
				<td><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $order->created_at ) ) ); ?></td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
	<?php endif; ?>
</div>
