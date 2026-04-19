<?php
if ( ! defined( 'ABSPATH' ) ) exit;
$has_products = ! empty( WPLP_DB::get_products() );
$has_creds    = ! empty( get_option( 'wplp_paypal_credentials', '' ) );
$show_welcome = ! $has_products;
?>
<div class="wrap lf-wrap">
	<div class="lf-header">
		<h1 class="lf-header__title">
			<span class="lf-header__icon"><span class="dashicons dashicons-admin-network"></span></span>
			LicenseForge
		</h1>
	</div>

	<?php if ( $show_welcome ) : ?>

	<div class="lf-welcome-hero">
		<h2><?php esc_html_e( 'Welcome to LicenseForge', 'wp-license-platform' ); ?></h2>
		<p><?php esc_html_e( 'Sell your WordPress plugins directly from your website. Zero platform fees.', 'wp-license-platform' ); ?></p>
	</div>

	<!-- Quick Start -->
	<div class="lf-card">
		<div class="lf-card__header"><span class="dashicons dashicons-flag"></span> <?php esc_html_e( 'Quick Start', 'wp-license-platform' ); ?></div>
		<div class="lf-card__body">
			<div style="display:flex;flex-direction:column;gap:16px;">
				<div style="display:flex;gap:12px;align-items:flex-start;padding:12px;border:1px solid <?php echo $has_creds ? 'rgba(34,197,94,0.3)' : 'var(--lf-border)'; ?>;border-radius:var(--lf-radius-sm);<?php echo $has_creds ? 'background:rgba(34,197,94,0.05);' : ''; ?>">
					<span style="display:flex;align-items:center;justify-content:center;width:28px;height:28px;border-radius:50%;background:<?php echo $has_creds ? 'var(--lf-success)' : 'var(--lf-indigo)'; ?>;color:#fff;font-size:13px;font-weight:700;min-width:28px;"><?php echo $has_creds ? '✓' : '1'; ?></span>
					<div>
						<strong style="color:#fff;"><?php esc_html_e( 'Configure PayPal', 'wp-license-platform' ); ?></strong>
						<p style="margin:4px 0 8px;color:var(--lf-text-muted);font-size:13px;"><?php esc_html_e( 'Enter your PayPal API credentials.', 'wp-license-platform' ); ?></p>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=wplp_settings' ) ); ?>" class="lf-btn lf-btn--outline lf-btn--sm"><?php esc_html_e( 'Go to Settings', 'wp-license-platform' ); ?></a>
					</div>
				</div>
				<div style="display:flex;gap:12px;align-items:flex-start;padding:12px;border:1px solid <?php echo $has_products ? 'rgba(34,197,94,0.3)' : 'var(--lf-border)'; ?>;border-radius:var(--lf-radius-sm);<?php echo $has_products ? 'background:rgba(34,197,94,0.05);' : ''; ?>">
					<span style="display:flex;align-items:center;justify-content:center;width:28px;height:28px;border-radius:50%;background:<?php echo $has_products ? 'var(--lf-success)' : 'var(--lf-indigo)'; ?>;color:#fff;font-size:13px;font-weight:700;min-width:28px;"><?php echo $has_products ? '✓' : '2'; ?></span>
					<div>
						<strong style="color:#fff;"><?php esc_html_e( 'Add Your First Product', 'wp-license-platform' ); ?></strong>
						<p style="margin:4px 0 8px;color:var(--lf-text-muted);font-size:13px;"><?php esc_html_e( 'Create a product with pricing tiers.', 'wp-license-platform' ); ?></p>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=wplp_products&action=new' ) ); ?>" class="lf-btn lf-btn--outline lf-btn--sm"><?php esc_html_e( 'Add Product', 'wp-license-platform' ); ?></a>
					</div>
				</div>
				<div style="display:flex;gap:12px;align-items:flex-start;padding:12px;border:1px solid var(--lf-border);border-radius:var(--lf-radius-sm);">
					<span style="display:flex;align-items:center;justify-content:center;width:28px;height:28px;border-radius:50%;background:var(--lf-indigo);color:#fff;font-size:13px;font-weight:700;min-width:28px;">3</span>
					<div>
						<strong style="color:#fff;"><?php esc_html_e( 'Start Selling', 'wp-license-platform' ); ?></strong>
						<p style="margin:4px 0 0;color:var(--lf-text-muted);font-size:13px;"><?php esc_html_e( 'Pages are created automatically. Test with PayPal sandbox, then go live.', 'wp-license-platform' ); ?></p>
					</div>
				</div>
			</div>
		</div>
	</div>

	<?php else : ?>

	<!-- Stats Dashboard -->
	<div class="lf-dashboard">
		<div class="lf-stat-card">
			<div class="lf-stat-card__icon lf-stat-card__icon--revenue"><span class="dashicons dashicons-money-alt"></span></div>
			<span class="lf-stat-card__value">$<?php echo esc_html( number_format( $stats['revenue'], 2 ) ); ?></span>
			<span class="lf-stat-card__label"><?php esc_html_e( 'Total Revenue', 'wp-license-platform' ); ?></span>
		</div>
		<div class="lf-stat-card">
			<div class="lf-stat-card__icon lf-stat-card__icon--orders"><span class="dashicons dashicons-cart"></span></div>
			<span class="lf-stat-card__value"><?php echo esc_html( $stats['orders'] ); ?></span>
			<span class="lf-stat-card__label"><?php esc_html_e( 'Completed Orders', 'wp-license-platform' ); ?></span>
		</div>
		<div class="lf-stat-card">
			<div class="lf-stat-card__icon lf-stat-card__icon--licenses"><span class="dashicons dashicons-admin-network"></span></div>
			<span class="lf-stat-card__value"><?php echo esc_html( $stats['licenses'] ); ?></span>
			<span class="lf-stat-card__label"><?php esc_html_e( 'Active Licenses', 'wp-license-platform' ); ?></span>
		</div>
		<div class="lf-stat-card">
			<div class="lf-stat-card__icon lf-stat-card__icon--customers"><span class="dashicons dashicons-groups"></span></div>
			<span class="lf-stat-card__value"><?php echo esc_html( $stats['customers'] ); ?></span>
			<span class="lf-stat-card__label"><?php esc_html_e( 'Customers', 'wp-license-platform' ); ?></span>
		</div>
	</div>

	<!-- Recent Orders -->
	<div class="lf-card">
		<div class="lf-card__header"><span class="dashicons dashicons-cart"></span> <?php esc_html_e( 'Recent Orders', 'wp-license-platform' ); ?></div>
		<div class="lf-card__body" style="padding:0;">
			<?php if ( empty( $recent_orders ) ) : ?>
				<p style="padding:20px;color:var(--lf-text-muted);"><?php esc_html_e( 'No orders yet.', 'wp-license-platform' ); ?></p>
			<?php else : ?>
			<table class="widefat" style="border:none;">
				<thead><tr>
					<th><?php esc_html_e( 'Order', 'wp-license-platform' ); ?></th>
					<th><?php esc_html_e( 'Customer', 'wp-license-platform' ); ?></th>
					<th><?php esc_html_e( 'Product', 'wp-license-platform' ); ?></th>
					<th><?php esc_html_e( 'Total', 'wp-license-platform' ); ?></th>
					<th><?php esc_html_e( 'Status', 'wp-license-platform' ); ?></th>
					<th><?php esc_html_e( 'Date', 'wp-license-platform' ); ?></th>
				</tr></thead>
				<tbody>
					<?php foreach ( $recent_orders as $order ) : ?>
					<tr>
						<td><strong><?php echo esc_html( $order->order_number ); ?></strong></td>
						<td><?php echo esc_html( $order->customer_email ?? '—' ); ?></td>
						<td><?php echo esc_html( ( $order->product_name ?? '' ) . ' — ' . ( $order->tier_name ?? '' ) ); ?></td>
						<td><strong>$<?php echo esc_html( number_format( $order->total, 2 ) ); ?></strong></td>
						<td><span class="wplp-status wplp-status-<?php echo esc_attr( $order->status ); ?>"><?php echo esc_html( ucfirst( $order->status ) ); ?></span></td>
						<td><?php echo esc_html( date_i18n( 'M j, Y', strtotime( $order->created_at ) ) ); ?></td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<?php endif; ?>
		</div>
	</div>

	<!-- API Info -->
	<div class="lf-card">
		<div class="lf-card__header"><span class="dashicons dashicons-rest-api"></span> <?php esc_html_e( 'REST API Endpoints', 'wp-license-platform' ); ?></div>
		<div class="lf-card__body" style="padding:0;">
			<table class="widefat" style="border:none;">
				<tr><td><code>POST</code></td><td><code><?php echo esc_html( rest_url( 'wplp/v1/validate' ) ); ?></code></td><td style="color:var(--lf-text-muted);"><?php esc_html_e( 'Validate a license key', 'wp-license-platform' ); ?></td></tr>
				<tr><td><code>POST</code></td><td><code><?php echo esc_html( rest_url( 'wplp/v1/activate' ) ); ?></code></td><td style="color:var(--lf-text-muted);"><?php esc_html_e( 'Activate a site', 'wp-license-platform' ); ?></td></tr>
				<tr><td><code>POST</code></td><td><code><?php echo esc_html( rest_url( 'wplp/v1/deactivate' ) ); ?></code></td><td style="color:var(--lf-text-muted);"><?php esc_html_e( 'Deactivate a site', 'wp-license-platform' ); ?></td></tr>
			</table>
		</div>
	</div>

	<?php endif; ?>
</div>
