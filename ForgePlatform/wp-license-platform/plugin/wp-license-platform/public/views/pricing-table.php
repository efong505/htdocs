<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wplp-pricing-table">
	<div class="wplp-pricing-grid">
		<?php foreach ( $tiers as $tier ) : ?>
		<div class="wplp-pricing-card <?php echo $tier->is_featured ? 'wplp-pricing-featured' : ''; ?>">
			<?php if ( $tier->is_featured ) : ?>
			<div class="wplp-pricing-popular"><?php esc_html_e( 'Most Popular', 'wp-license-platform' ); ?></div>
			<?php endif; ?>
			<h3><?php echo esc_html( $tier->display_name ); ?></h3>
			<div class="wplp-pricing-price">
				<span class="wplp-pricing-amount">$<?php echo esc_html( number_format( $tier->price, 0 ) ); ?></span>
				<span class="wplp-pricing-period">/<?php esc_html_e( 'year', 'wp-license-platform' ); ?></span>
			</div>
			<p><?php echo $tier->sites_allowed ? esc_html( $tier->sites_allowed . ' ' . _n( 'site license', 'site licenses', $tier->sites_allowed, 'wp-license-platform' ) ) : esc_html__( 'Unlimited sites', 'wp-license-platform' ); ?></p>
			<?php if ( $checkout_url ) : ?>
			<a href="<?php echo esc_url( add_query_arg( array( 'product' => $product->slug, 'tier' => $tier->id ), $checkout_url ) ); ?>" class="wplp-pricing-btn"><?php esc_html_e( 'Buy Now', 'wp-license-platform' ); ?></a>
			<?php endif; ?>
		</div>
		<?php endforeach; ?>
	</div>
</div>
