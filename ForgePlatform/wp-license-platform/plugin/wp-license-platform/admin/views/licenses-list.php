<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// If viewing a specific license detail
$detail_id = absint( $_GET['license_id'] ?? 0 );
if ( $detail_id ) :
	$license     = WPLP_License::find( $detail_id );
	$activations = $license ? WPLP_License::get_activations( $detail_id ) : array();
	$tier        = $license ? WPLP_DB::get_tier( $license->tier_id ) : null;
	$product     = $license ? WPLP_DB::get_product( $license->product_id ) : null;
	$customer    = $license ? WPLP_Customer::find( $license->customer_id ) : null;
?>
<div class="wrap lf-wrap">
	<div class="lf-header">
		<h1 class="lf-header__title">
			<span class="lf-header__icon"><span class="dashicons dashicons-admin-network"></span></span>
			LicenseForge — License Detail
		</h1>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=wplp_licenses' ) ); ?>" class="lf-btn lf-btn--outline lf-btn--sm">← Back to Licenses</a>
	</div>

	<?php if ( ! $license ) : ?>
		<div class="notice notice-error"><p><?php esc_html_e( 'License not found.', 'wp-license-platform' ); ?></p></div>
	<?php else : ?>
	<table class="widefat striped" style="max-width: 600px;">
		<tbody>
			<tr><th><?php esc_html_e( 'License Key', 'wp-license-platform' ); ?></th><td><code><?php echo esc_html( $license->license_key ); ?></code></td></tr>
			<tr><th><?php esc_html_e( 'Status', 'wp-license-platform' ); ?></th><td><span class="wplp-status wplp-status-<?php echo esc_attr( $license->status ); ?>"><?php echo esc_html( ucfirst( $license->status ) ); ?></span></td></tr>
			<tr><th><?php esc_html_e( 'Customer', 'wp-license-platform' ); ?></th><td><?php echo esc_html( $customer ? $customer->email : '—' ); ?></td></tr>
			<tr><th><?php esc_html_e( 'Product', 'wp-license-platform' ); ?></th><td><?php echo esc_html( $product ? $product->name : '—' ); ?></td></tr>
			<tr><th><?php esc_html_e( 'Tier', 'wp-license-platform' ); ?></th><td><?php echo esc_html( $tier ? $tier->display_name : '—' ); ?></td></tr>
			<tr><th><?php esc_html_e( 'Sites', 'wp-license-platform' ); ?></th><td><?php echo esc_html( $license->sites_active . ' / ' . ( $license->sites_allowed ?: '∞' ) ); ?></td></tr>
			<tr><th><?php esc_html_e( 'Expires', 'wp-license-platform' ); ?></th><td><?php echo $license->expires_at ? esc_html( date_i18n( 'M j, Y g:i A', strtotime( $license->expires_at ) ) ) : esc_html__( 'Lifetime', 'wp-license-platform' ); ?></td></tr>
			<tr><th><?php esc_html_e( 'Created', 'wp-license-platform' ); ?></th><td><?php echo esc_html( date_i18n( 'M j, Y g:i A', strtotime( $license->created_at ) ) ); ?></td></tr>
		</tbody>
	</table>

	<h2><?php esc_html_e( 'Activated Sites', 'wp-license-platform' ); ?></h2>
	<?php if ( empty( $activations ) ) : ?>
		<p><?php esc_html_e( 'No sites activated yet.', 'wp-license-platform' ); ?></p>
	<?php else : ?>
	<table class="widefat striped" style="max-width: 600px;">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Site URL', 'wp-license-platform' ); ?></th>
				<th><?php esc_html_e( 'Activated', 'wp-license-platform' ); ?></th>
				<th><?php esc_html_e( 'Last Check', 'wp-license-platform' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $activations as $act ) : ?>
			<tr>
				<td><a href="<?php echo esc_url( $act->site_url ); ?>" target="_blank"><?php echo esc_html( $act->site_url ); ?></a></td>
				<td><?php echo esc_html( date_i18n( 'M j, Y g:i A', strtotime( $act->activated_at ) ) ); ?></td>
				<td><?php echo $act->last_checked ? esc_html( date_i18n( 'M j, Y g:i A', strtotime( $act->last_checked ) ) ) : '—'; ?></td>
			</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
	<?php endif; ?>
	<?php endif; ?>
</div>

<?php else : ?>

<div class="wrap lf-wrap">
	<div class="lf-header">
		<h1 class="lf-header__title">
			<span class="lf-header__icon"><span class="dashicons dashicons-admin-network"></span></span>
			LicenseForge — Licenses
		</h1>
	</div>
	<?php if ( empty( $licenses ) ) : ?>
		<p><?php esc_html_e( 'No licenses yet.', 'wp-license-platform' ); ?></p>
	<?php else : ?>
	<table class="widefat striped">
		<thead>
			<tr>
				<th><?php esc_html_e( 'License Key', 'wp-license-platform' ); ?></th>
				<th><?php esc_html_e( 'Customer', 'wp-license-platform' ); ?></th>
				<th><?php esc_html_e( 'Product', 'wp-license-platform' ); ?></th>
				<th><?php esc_html_e( 'Sites', 'wp-license-platform' ); ?></th>
				<th><?php esc_html_e( 'Status', 'wp-license-platform' ); ?></th>
				<th><?php esc_html_e( 'Expires', 'wp-license-platform' ); ?></th>
				<th><?php esc_html_e( 'Actions', 'wp-license-platform' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $licenses as $license ) : ?>
			<tr>
				<td>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=wplp_licenses&license_id=' . $license->id ) ); ?>">
						<code><?php echo esc_html( $license->license_key ); ?></code>
					</a>
				</td>
				<td><?php echo esc_html( $license->customer_email ?? '—' ); ?></td>
				<td><?php echo esc_html( ( $license->product_name ?? '' ) . ' — ' . ( $license->tier_name ?? '' ) ); ?></td>
				<td><?php echo esc_html( $license->sites_active . ' / ' . ( $license->sites_allowed ?: '∞' ) ); ?></td>
				<td><span class="wplp-status wplp-status-<?php echo esc_attr( $license->status ); ?>"><?php echo esc_html( ucfirst( $license->status ) ); ?></span></td>
				<td><?php echo $license->expires_at ? esc_html( date_i18n( 'M j, Y', strtotime( $license->expires_at ) ) ) : esc_html__( 'Lifetime', 'wp-license-platform' ); ?></td>
				<td>
					<select class="wplp-license-status-select" data-license-id="<?php echo esc_attr( $license->id ); ?>" style="font-size:12px;">
						<?php foreach ( array( 'active', 'expired', 'revoked', 'suspended' ) as $s ) : ?>
						<option value="<?php echo esc_attr( $s ); ?>" <?php selected( $license->status, $s ); ?>><?php echo esc_html( ucfirst( $s ) ); ?></option>
						<?php endforeach; ?>
					</select>
					<?php
					$delete_url = wp_nonce_url(
						add_query_arg( array( 'page' => 'wplp_licenses', 'wplp_delete_license' => $license->id ), admin_url( 'admin.php' ) ),
						'wplp_delete_license_' . $license->id
					);
					?>
					<a href="<?php echo esc_url( $delete_url ); ?>" class="button button-small" style="color:#d63638;font-size:11px;" onclick="return confirm('Delete this license and all its activations?');">Delete</a>
				</td>
			</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
	<?php endif; ?>
</div>
<?php endif; ?>
