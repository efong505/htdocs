<?php if ( ! defined( 'ABSPATH' ) ) exit;
$selected_tier = absint( $_GET['tier'] ?? 0 );
?>
<div class="wplp-checkout">
	<h2><?php echo esc_html( $product->name ); ?></h2>

	<div class="wplp-checkout-tiers">
		<?php foreach ( $tiers as $tier ) :
			$is_selected = $selected_tier ? ( (int) $tier->id === $selected_tier ) : $tier->is_featured;
		?>
		<label class="wplp-tier-option <?php echo $tier->is_featured ? 'wplp-tier-featured' : ''; ?>">
			<input type="radio" name="wplp_tier" value="<?php echo esc_attr( $tier->id ); ?>" data-price="<?php echo esc_attr( $tier->price ); ?>" <?php checked( $is_selected ); ?> />
			<span class="wplp-tier-name"><?php echo esc_html( $tier->display_name ); ?></span>
			<span class="wplp-tier-price">$<?php echo esc_html( number_format( $tier->price, 2 ) ); ?><small>/<?php esc_html_e( 'year', 'wp-license-platform' ); ?></small></span>
			<span class="wplp-tier-sites"><?php echo $tier->sites_allowed ? esc_html( $tier->sites_allowed . ' ' . _n( 'site', 'sites', $tier->sites_allowed, 'wp-license-platform' ) ) : esc_html__( 'Unlimited sites', 'wp-license-platform' ); ?></span>
		</label>
		<?php endforeach; ?>
	</div>

	<div class="wplp-checkout-billing">
		<h3><?php esc_html_e( 'Billing Information', 'wp-license-platform' ); ?></h3>
		<p>
			<label for="wplp-country"><?php esc_html_e( 'Country', 'wp-license-platform' ); ?></label>
			<select id="wplp-country">
				<option value=""><?php esc_html_e( 'Select your country...', 'wp-license-platform' ); ?></option>
				<?php foreach ( $countries as $code => $name ) : ?>
				<option value="<?php echo esc_attr( $code ); ?>"><?php echo esc_html( $name ); ?></option>
				<?php endforeach; ?>
			</select>
		</p>
		<p class="wplp-vat-field" style="display:none;">
			<label for="wplp-vat-number"><?php esc_html_e( 'EU VAT Number (optional, for business purchases)', 'wp-license-platform' ); ?></label>
			<input type="text" id="wplp-vat-number" placeholder="DE123456789" />
		</p>
	</div>

	<div class="wplp-checkout-summary">
		<div class="wplp-summary-line"><span><?php esc_html_e( 'Subtotal', 'wp-license-platform' ); ?></span><span id="wplp-subtotal">$0.00</span></div>
		<div class="wplp-summary-line wplp-summary-tax" style="display:none;"><span id="wplp-tax-label"><?php esc_html_e( 'VAT', 'wp-license-platform' ); ?> (0%)</span><span id="wplp-tax-amount">$0.00</span></div>
		<div class="wplp-summary-line wplp-summary-total"><span><?php esc_html_e( 'Total', 'wp-license-platform' ); ?></span><span id="wplp-total">$0.00</span></div>
		<div class="wplp-reverse-charge" style="display:none;"><?php esc_html_e( 'Reverse charge applies — VAT will be accounted for by the buyer.', 'wp-license-platform' ); ?></div>
	</div>

	<div id="paypal-button-container" style="margin-top:20px;"></div>

	<p class="wplp-checkout-disclosure">
		<?php printf(
			esc_html__( 'Payments processed securely by PayPal. By purchasing, you agree to our %s.', 'wp-license-platform' ),
			'<a href="#">' . esc_html__( 'Terms of Service', 'wp-license-platform' ) . '</a>'
		); ?>
	</p>
</div>
