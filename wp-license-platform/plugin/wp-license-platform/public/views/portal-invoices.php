<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wplp-portal">
	<h2><?php esc_html_e( 'Invoices', 'wp-license-platform' ); ?></h2>
	<?php if ( empty( $orders ) ) : ?>
		<p><?php esc_html_e( 'No invoices found.', 'wp-license-platform' ); ?></p>
	<?php else : ?>
	<table class="wplp-table">
		<thead><tr><th><?php esc_html_e( 'Invoice', 'wp-license-platform' ); ?></th><th><?php esc_html_e( 'Product', 'wp-license-platform' ); ?></th><th><?php esc_html_e( 'Total', 'wp-license-platform' ); ?></th><th><?php esc_html_e( 'Date', 'wp-license-platform' ); ?></th></tr></thead>
		<tbody>
		<?php foreach ( $orders as $order ) : if ( 'completed' !== $order->status ) continue; ?>
			<tr>
				<td><?php echo esc_html( $order->invoice_number ?: $order->order_number ); ?></td>
				<td><?php echo esc_html( $order->product_name ); ?></td>
				<td>$<?php echo esc_html( number_format( $order->total, 2 ) ); ?></td>
				<td><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $order->created_at ) ) ); ?></td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
	<?php endif; ?>
</div>
