<div style="max-width:600px;margin:0 auto;font-family:Arial,sans-serif;color:#333;">
	<h2 style="color:#2271b1;">Thank you for your purchase!</h2>
	<p>Hi <?php echo esc_html( $customer->first_name ?: 'there' ); ?>,</p>
	<p>Your order <strong><?php echo esc_html( $order->order_number ); ?></strong> has been completed.</p>

	<table style="width:100%;border-collapse:collapse;margin:20px 0;">
		<tr style="background:#f5f5f5;"><td style="padding:10px;border:1px solid #ddd;"><strong>Product</strong></td><td style="padding:10px;border:1px solid #ddd;"><?php echo esc_html( $order->product_name . ' — ' . $order->tier_name ); ?></td></tr>
		<tr><td style="padding:10px;border:1px solid #ddd;"><strong>License Key</strong></td><td style="padding:10px;border:1px solid #ddd;"><code style="background:#e8f5e9;padding:4px 8px;"><?php echo esc_html( $license->license_key ); ?></code></td></tr>
		<tr style="background:#f5f5f5;"><td style="padding:10px;border:1px solid #ddd;"><strong>Sites Allowed</strong></td><td style="padding:10px;border:1px solid #ddd;"><?php echo esc_html( $license->sites_allowed ?: 'Unlimited' ); ?></td></tr>
		<tr><td style="padding:10px;border:1px solid #ddd;"><strong>Expires</strong></td><td style="padding:10px;border:1px solid #ddd;"><?php echo $license->expires_at ? esc_html( date_i18n( 'F j, Y', strtotime( $license->expires_at ) ) ) : 'Lifetime'; ?></td></tr>
		<tr style="background:#f5f5f5;"><td style="padding:10px;border:1px solid #ddd;"><strong>Total</strong></td><td style="padding:10px;border:1px solid #ddd;">$<?php echo esc_html( number_format( $order->total, 2 ) ); ?> <?php echo esc_html( $order->currency ); ?></td></tr>
	</table>

	<h3>Next Steps</h3>
	<ol>
		<li>Download the Pro plugin from your <a href="<?php echo esc_url( $site_url . '/portal/downloads/' ); ?>">customer portal</a></li>
		<li>Install and activate the plugin on your WordPress site</li>
		<li>Enter your license key in the plugin settings</li>
	</ol>
	<p>If you have any questions, reply to this email.</p>
</div>
