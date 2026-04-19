<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wplp-portal">
	<h2><?php esc_html_e( 'Your Licenses', 'wp-license-platform' ); ?></h2>
	<?php if ( empty( $licenses ) ) : ?>
		<p><?php esc_html_e( 'No licenses found.', 'wp-license-platform' ); ?></p>
	<?php else : foreach ( $licenses as $license ) : ?>
	<div class="wplp-license-card">
		<div class="wplp-license-header">
			<strong><?php echo esc_html( $license->product_name ); ?></strong> — <?php echo esc_html( $license->tier_name ); ?>
			<span class="wplp-status wplp-status-<?php echo esc_attr( $license->status ); ?>"><?php echo esc_html( ucfirst( $license->status ) ); ?></span>
		</div>
		<div class="wplp-license-key"><code><?php echo esc_html( $license->license_key ); ?></code></div>
		<div class="wplp-license-meta">
			<span><?php printf( esc_html__( 'Sites: %d / %s', 'wp-license-platform' ), $license->sites_active, $license->sites_allowed ?: '∞' ); ?></span>
			<span><?php echo $license->expires_at ? sprintf( esc_html__( 'Expires: %s', 'wp-license-platform' ), esc_html( date_i18n( get_option( 'date_format' ), strtotime( $license->expires_at ) ) ) ) : esc_html__( 'Lifetime', 'wp-license-platform' ); ?></span>
		</div>
		<?php if ( ! empty( $license->activations ) ) : ?>
		<div class="wplp-activations">
			<strong><?php esc_html_e( 'Active Sites:', 'wp-license-platform' ); ?></strong>
			<ul>
			<?php foreach ( $license->activations as $act ) : ?>
				<li>
					<?php echo esc_html( $act->site_url ); ?>
					<form method="post" style="display:inline;">
						<?php wp_nonce_field( 'wplp_deactivate_site' ); ?>
						<input type="hidden" name="license_id" value="<?php echo esc_attr( $license->id ); ?>" />
						<input type="hidden" name="site_url" value="<?php echo esc_attr( $act->site_url ); ?>" />
						<button type="submit" name="wplp_deactivate_site" class="wplp-btn-small"><?php esc_html_e( 'Deactivate', 'wp-license-platform' ); ?></button>
					</form>
				</li>
			<?php endforeach; ?>
			</ul>
		</div>
		<?php endif; ?>
	</div>
	<?php endforeach; endif; ?>
</div>
