<div style="max-width:600px;margin:0 auto;font-family:Arial,sans-serif;color:#333;">
	<h2>Refund Processed</h2>
	<p>Hi <?php echo esc_html( $customer->first_name ?: 'there' ); ?>,</p>
	<p>Your refund for order <strong><?php echo esc_html( $order->order_number ); ?></strong> has been processed.</p>
	<p><strong>Amount:</strong> $<?php echo esc_html( number_format( $order->total, 2 ) ); ?> <?php echo esc_html( $order->currency ); ?></p>
	<p>The refund will appear in your PayPal account within 5-10 business days. Your license has been deactivated.</p>
	<p>If you have any questions, reply to this email.</p>
</div>
