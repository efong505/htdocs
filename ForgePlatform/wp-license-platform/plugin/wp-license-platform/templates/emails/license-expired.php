<div style="max-width:600px;margin:0 auto;font-family:Arial,sans-serif;color:#333;">
	<h2>Your license has expired</h2>
	<p>Hi <?php echo esc_html( $customer->first_name ?: 'there' ); ?>,</p>
	<p>Your license for <strong><?php echo esc_html( $license->product_name ); ?></strong> has expired.</p>
	<p>Pro features have been disabled, but the free version continues to work normally. Your data is safe.</p>
	<p>You can renew at any time from your <a href="<?php echo esc_url( $site_url . '/portal/' ); ?>">customer portal</a>.</p>
</div>
