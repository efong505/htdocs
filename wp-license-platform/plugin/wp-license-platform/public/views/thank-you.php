<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wplp-thank-you">
	<?php if ( $order && 'completed' === $order->status ) : ?>
		<h2>🎉 <?php esc_html_e( 'Thank you for your purchase!', 'wp-license-platform' ); ?></h2>
		<div class="wplp-order-summary">
			<p><strong><?php esc_html_e( 'Order:', 'wp-license-platform' ); ?></strong> <?php echo esc_html( $order->order_number ); ?></p>
			<p><strong><?php esc_html_e( 'Product:', 'wp-license-platform' ); ?></strong> <?php echo esc_html( $order->product_name . ' — ' . $order->tier_name ); ?></p>
			<p><strong><?php esc_html_e( 'Total:', 'wp-license-platform' ); ?></strong> $<?php echo esc_html( number_format( $order->total, 2 ) ); ?></p>
			<?php if ( $license ) : ?>
			<p><strong><?php esc_html_e( 'License Key:', 'wp-license-platform' ); ?></strong> <code><?php echo esc_html( $license->license_key ); ?></code></p>
			<?php endif; ?>
		</div>
		<h3><?php esc_html_e( 'Next Steps', 'wp-license-platform' ); ?></h3>
		<ol>
			<li><?php esc_html_e( 'Check your email for the purchase confirmation with your license key.', 'wp-license-platform' ); ?></li>
			<li><?php esc_html_e( 'Download the Pro plugin from your customer portal.', 'wp-license-platform' ); ?></li>
			<li><?php esc_html_e( 'Install and activate the plugin on your WordPress site.', 'wp-license-platform' ); ?></li>
			<li><?php esc_html_e( 'Enter your license key in the plugin settings.', 'wp-license-platform' ); ?></li>
		</ol>
	<?php else : ?>
		<h2><?php esc_html_e( 'Order not found or still processing.', 'wp-license-platform' ); ?></h2>
		<p><?php esc_html_e( 'If you just completed a purchase, please wait a moment and refresh this page.', 'wp-license-platform' ); ?></p>
	<?php endif; ?>
</div>
