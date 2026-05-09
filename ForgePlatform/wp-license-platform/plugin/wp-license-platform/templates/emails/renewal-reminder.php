<div style="max-width:600px;margin:0 auto;font-family:Arial,sans-serif;color:#333;">
	<h2>Your license expires soon</h2>
	<p>Hi <?php echo esc_html( $customer->first_name ?: 'there' ); ?>,</p>
	<p>Your license for <strong><?php echo esc_html( $license->product_name ); ?></strong> expires on <strong><?php echo esc_html( date_i18n( 'F j, Y', strtotime( $license->expires_at ) ) ); ?></strong> (<?php echo esc_html( $days_remaining ); ?> days from now).</p>
	<p>If you don't renew:</p>
	<ul>
		<li>Pro features will be disabled (your data is safe)</li>
		<li>The free version continues to work normally</li>
		<li>You can renew at any time to reactivate</li>
	</ul>
	<p>Visit your <a href="<?php echo esc_url( $site_url . '/portal/' ); ?>">customer portal</a> to manage your license.</p>
</div>
