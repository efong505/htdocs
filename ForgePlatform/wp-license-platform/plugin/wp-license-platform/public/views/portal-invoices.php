<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wplp-portal">
	<?php WPLP_Portal::render_nav(); ?>
	<h2><?php esc_html_e( 'Invoices', 'wp-license-platform' ); ?></h2>
	<?php if ( empty( $orders ) ) : ?>
		<p><?php esc_html_e( 'No invoices found.', 'wp-license-platform' ); ?></p>
	<?php else : ?>
	<table class="wplp-table">
		<thead><tr><th><?php esc_html_e( 'Invoice', 'wp-license-platform' ); ?></th><th><?php esc_html_e( 'Product', 'wp-license-platform' ); ?></th><th><?php esc_html_e( 'Total', 'wp-license-platform' ); ?></th><th><?php esc_html_e( 'Date', 'wp-license-platform' ); ?></th><th></th></tr></thead>
		<tbody>
		<?php foreach ( $orders as $order ) : if ( 'completed' !== $order->status ) continue; ?>
			<?php $url = WPLP_Portal::get_invoice_url( $order->id ); ?>
			<tr>
				<td><a href="<?php echo esc_url( $url ); ?>" class="wplp-invoice-link"><?php echo esc_html( $order->invoice_number ?: $order->order_number ); ?></a></td>
				<td><?php echo esc_html( $order->product_name ); ?></td>
				<td>$<?php echo esc_html( number_format( $order->total, 2 ) ); ?></td>
				<td><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $order->created_at ) ) ); ?></td>
				<td><a href="<?php echo esc_url( $url ); ?>" class="wplp-btn-download" title="<?php esc_attr_e( 'View & Download', 'wp-license-platform' ); ?>"><span class="dashicons dashicons-download"></span></a></td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
	<?php endif; ?>
</div>
