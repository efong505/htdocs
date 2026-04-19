<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><title><?php echo esc_html( $order->invoice_number ); ?></title>
<style>body{font-family:Arial,sans-serif;color:#333;max-width:800px;margin:0 auto;padding:40px}
.inv-header{display:flex;justify-content:space-between;margin-bottom:40px}
.inv-title{font-size:28px;font-weight:700;color:#2271b1}
.inv-meta{text-align:right;font-size:13px;color:#666}
.inv-parties{display:flex;justify-content:space-between;margin-bottom:30px}
.inv-party{width:45%}
.inv-party h3{margin:0 0 8px;font-size:14px;color:#999;text-transform:uppercase}
table{width:100%;border-collapse:collapse;margin:20px 0}
th{background:#f5f5f5;text-align:left;padding:10px;border-bottom:2px solid #ddd;font-size:13px}
td{padding:10px;border-bottom:1px solid #eee}
.inv-totals{text-align:right;margin-top:20px}
.inv-totals table{width:300px;margin-left:auto}
.inv-totals td{border:none;padding:6px 10px}
.inv-total-row{font-weight:700;font-size:18px;border-top:2px solid #333}
.inv-note{margin-top:30px;font-size:12px;color:#999;border-top:1px solid #eee;padding-top:20px}
</style></head>
<body>
<div class="inv-header">
	<div><div class="inv-title"><?php esc_html_e( 'INVOICE', 'wp-license-platform' ); ?></div></div>
	<div class="inv-meta">
		<strong><?php echo esc_html( $order->invoice_number ); ?></strong><br>
		<?php echo esc_html( date_i18n( 'F j, Y', strtotime( $order->created_at ) ) ); ?>
	</div>
</div>

<div class="inv-parties">
	<div class="inv-party">
		<h3><?php esc_html_e( 'From', 'wp-license-platform' ); ?></h3>
		<strong><?php echo esc_html( $settings['business_name'] ?? get_bloginfo( 'name' ) ); ?></strong><br>
		<?php echo nl2br( esc_html( $settings['business_address'] ?? '' ) ); ?>
		<?php if ( ! empty( $settings['vat_number'] ) ) : ?><br><?php esc_html_e( 'VAT:', 'wp-license-platform' ); ?> <?php echo esc_html( $settings['vat_number'] ); ?><?php endif; ?>
	</div>
	<div class="inv-party">
		<h3><?php esc_html_e( 'To', 'wp-license-platform' ); ?></h3>
		<strong><?php echo esc_html( trim( ( $customer->first_name ?? '' ) . ' ' . ( $customer->last_name ?? '' ) ) ?: $customer->email ); ?></strong><br>
		<?php echo esc_html( $customer->email ); ?>
		<?php if ( $customer->company ) : ?><br><?php echo esc_html( $customer->company ); ?><?php endif; ?>
		<?php if ( $customer->country_code ) : ?><br><?php echo esc_html( $customer->country_code ); ?><?php endif; ?>
		<?php if ( $customer->vat_number ) : ?><br><?php esc_html_e( 'VAT:', 'wp-license-platform' ); ?> <?php echo esc_html( $customer->vat_number ); ?><?php endif; ?>
	</div>
</div>

<table>
	<thead><tr><th><?php esc_html_e( 'Description', 'wp-license-platform' ); ?></th><th><?php esc_html_e( 'Qty', 'wp-license-platform' ); ?></th><th style="text-align:right"><?php esc_html_e( 'Amount', 'wp-license-platform' ); ?></th></tr></thead>
	<tbody>
		<tr>
			<td><?php echo esc_html( ( $order->product_name ?? 'Product' ) . ' — ' . ( $order->tier_name ?? '' ) ); ?></td>
			<td>1</td>
			<td style="text-align:right">$<?php echo esc_html( number_format( $order->subtotal, 2 ) ); ?></td>
		</tr>
	</tbody>
</table>

<div class="inv-totals">
	<table>
		<tr><td><?php esc_html_e( 'Subtotal', 'wp-license-platform' ); ?></td><td style="text-align:right">$<?php echo esc_html( number_format( $order->subtotal, 2 ) ); ?></td></tr>
		<?php if ( $order->tax_amount > 0 ) : ?>
		<tr><td><?php printf( esc_html__( 'VAT (%s%%)', 'wp-license-platform' ), esc_html( $order->tax_rate ) ); ?></td><td style="text-align:right">$<?php echo esc_html( number_format( $order->tax_amount, 2 ) ); ?></td></tr>
		<?php endif; ?>
		<tr class="inv-total-row"><td><?php esc_html_e( 'Total', 'wp-license-platform' ); ?></td><td style="text-align:right">$<?php echo esc_html( number_format( $order->total, 2 ) ); ?> <?php echo esc_html( $order->currency ); ?></td></tr>
	</table>
</div>

<?php if ( $order->tax_amount == 0 && ! empty( $customer->vat_number ) ) : ?>
<p style="margin-top:20px;font-style:italic;"><?php esc_html_e( 'Reverse charge: VAT to be accounted for by the recipient.', 'wp-license-platform' ); ?></p>
<?php endif; ?>

<div class="inv-note">
	<p><?php esc_html_e( 'Payment processed via PayPal. This is a digital goods transaction — no shipping applies.', 'wp-license-platform' ); ?></p>
</div>
</body></html>
