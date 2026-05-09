<?php
if ( ! defined( 'ABSPATH' ) ) exit;
$encrypted = get_option( 'wplp_paypal_credentials', '' );
$has_creds = ! empty( $encrypted );
$masked_id = '';
$masked_secret = '';
if ( $has_creds ) {
	$dec = WPLP_Crypto::decrypt( $encrypted );
	$c   = $dec ? json_decode( $dec, true ) : array();
	if ( ! empty( $c['client_id'] ) ) {
		$masked_id = substr( $c['client_id'], 0, 8 ) . str_repeat( '*', max( 0, strlen( $c['client_id'] ) - 12 ) ) . substr( $c['client_id'], -4 );
	}
	if ( ! empty( $c['client_secret'] ) ) {
		$masked_secret = str_repeat( '*', max( 0, strlen( $c['client_secret'] ) - 4 ) ) . substr( $c['client_secret'], -4 );
	}
}
$webhook_id = get_option( 'wplp_paypal_webhook_id', '' );
?>
<div class="wrap lf-wrap">
	<div class="lf-header">
		<h1 class="lf-header__title">
			<span class="lf-header__icon"><span class="dashicons dashicons-admin-network"></span></span>
			LicenseForge — Settings
		</h1>
	</div>
	<?php settings_errors( 'wplp_settings' ); ?>

	<form method="post">
		<?php wp_nonce_field( 'wplp_save_settings', 'wplp_settings_nonce' ); ?>

		<h2><?php esc_html_e( 'Business Information', 'wp-license-platform' ); ?></h2>
		<table class="form-table">
			<tr>
				<th><label for="business_name"><?php esc_html_e( 'Business Name', 'wp-license-platform' ); ?></label></th>
				<td><input type="text" id="business_name" name="business_name" value="<?php echo esc_attr( $settings['business_name'] ?? '' ); ?>" class="regular-text" /></td>
			</tr>
			<tr>
				<th><label for="business_address"><?php esc_html_e( 'Business Address', 'wp-license-platform' ); ?></label></th>
				<td><textarea id="business_address" name="business_address" rows="3" class="large-text"><?php echo esc_textarea( $settings['business_address'] ?? '' ); ?></textarea></td>
			</tr>
			<tr>
				<th><label for="business_country"><?php esc_html_e( 'Country', 'wp-license-platform' ); ?></label></th>
				<td>
					<select id="business_country" name="business_country">
						<option value=""><?php esc_html_e( 'Select...', 'wp-license-platform' ); ?></option>
						<?php foreach ( WPLP_VAT::get_country_list() as $code => $name ) : ?>
						<option value="<?php echo esc_attr( $code ); ?>" <?php selected( $settings['business_country'] ?? '', $code ); ?>><?php echo esc_html( $name ); ?></option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
			<tr>
				<th><label for="vat_number"><?php esc_html_e( 'VAT Number', 'wp-license-platform' ); ?></label></th>
				<td><input type="text" id="vat_number" name="vat_number" value="<?php echo esc_attr( $settings['vat_number'] ?? '' ); ?>" class="regular-text" placeholder="GB123456789" />
				<p class="description"><?php esc_html_e( 'Your VAT registration number (if registered). Shown on invoices.', 'wp-license-platform' ); ?></p></td>
			</tr>
			<tr>
				<th><label for="support_email"><?php esc_html_e( 'Support Email', 'wp-license-platform' ); ?></label></th>
				<td><input type="email" id="support_email" name="support_email" value="<?php echo esc_attr( $settings['support_email'] ?? get_option( 'admin_email' ) ); ?>" class="regular-text" /></td>
			</tr>
		</table>

		<h2><?php esc_html_e( 'PayPal Configuration', 'wp-license-platform' ); ?></h2>
		<table class="form-table">
			<tr>
				<th><?php esc_html_e( 'Environment', 'wp-license-platform' ); ?></th>
				<td>
					<label><input type="checkbox" name="paypal_sandbox" value="1" <?php checked( $settings['paypal_sandbox'] ?? 1, 1 ); ?> /> <?php esc_html_e( 'Sandbox mode (for testing)', 'wp-license-platform' ); ?></label>
					<p class="description"><?php esc_html_e( 'Uncheck for live payments.', 'wp-license-platform' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><label for="paypal_client_id"><?php esc_html_e( 'Client ID', 'wp-license-platform' ); ?></label></th>
				<td><input type="text" id="paypal_client_id" name="paypal_client_id" value="<?php echo esc_attr( $masked_id ); ?>" class="large-text" autocomplete="off" /></td>
			</tr>
			<tr>
				<th><label for="paypal_client_secret"><?php esc_html_e( 'Client Secret', 'wp-license-platform' ); ?></label></th>
				<td><input type="password" id="paypal_client_secret" name="paypal_client_secret" value="<?php echo esc_attr( $masked_secret ); ?>" class="large-text" autocomplete="off" />
				<p class="description"><?php esc_html_e( 'Encrypted at rest using your WordPress security salts.', 'wp-license-platform' ); ?></p></td>
			</tr>
			<tr>
				<th><label for="paypal_webhook_id"><?php esc_html_e( 'Webhook ID', 'wp-license-platform' ); ?></label></th>
				<td><input type="text" id="paypal_webhook_id" name="paypal_webhook_id" value="<?php echo esc_attr( $webhook_id ); ?>" class="regular-text" />
				<p class="description"><?php printf( esc_html__( 'Webhook URL: %s', 'wp-license-platform' ), '<code>' . esc_html( rest_url( 'wplp/v1/paypal-webhook' ) ) . '</code>' ); ?></p></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Test Connection', 'wp-license-platform' ); ?></th>
				<td>
					<button type="button" id="wplp-test-paypal" class="button"><?php esc_html_e( 'Test PayPal Connection', 'wp-license-platform' ); ?></button>
					<span id="wplp-test-result"></span>
				</td>
			</tr>
		</table>

		<h2><?php esc_html_e( 'Pages', 'wp-license-platform' ); ?></h2>
		<p class="description"><?php esc_html_e( 'These pages were created automatically. You can change which page is used for each function, or edit the pages directly.', 'wp-license-platform' ); ?></p>
		<?php
		$wplp_pages = get_option( 'wplp_pages', array() );
		$all_pages  = get_pages( array( 'post_status' => 'publish', 'sort_column' => 'post_title' ) );
		$page_map   = array(
			'pricing'   => __( 'Pricing Page', 'wp-license-platform' ),
			'checkout'  => __( 'Checkout Page', 'wp-license-platform' ),
			'thank_you' => __( 'Thank You Page', 'wp-license-platform' ),
			'account'   => __( 'My Account', 'wp-license-platform' ),
			'licenses'  => __( 'My Licenses', 'wp-license-platform' ),
			'downloads' => __( 'My Downloads', 'wp-license-platform' ),
			'invoices'  => __( 'My Invoices', 'wp-license-platform' ),
		);
		?>
		<table class="form-table">
			<?php foreach ( $page_map as $key => $label ) :
				$current_id = $wplp_pages[ $key ] ?? 0;
			?>
			<tr>
				<th><label for="wplp_page_<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></label></th>
				<td>
					<select id="wplp_page_<?php echo esc_attr( $key ); ?>" name="wplp_page_<?php echo esc_attr( $key ); ?>">
						<option value="0"><?php esc_html_e( '— Select a page —', 'wp-license-platform' ); ?></option>
						<?php foreach ( $all_pages as $pg ) : ?>
						<option value="<?php echo esc_attr( $pg->ID ); ?>" <?php selected( $current_id, $pg->ID ); ?>><?php echo esc_html( $pg->post_title ); ?></option>
						<?php endforeach; ?>
					</select>
					<?php if ( $current_id && get_post_status( $current_id ) ) : ?>
						<a href="<?php echo esc_url( get_edit_post_link( $current_id ) ); ?>" class="button button-small" style="margin-left:8px;"><?php esc_html_e( 'Edit', 'wp-license-platform' ); ?></a>
						<a href="<?php echo esc_url( get_permalink( $current_id ) ); ?>" class="button button-small" target="_blank"><?php esc_html_e( 'View', 'wp-license-platform' ); ?></a>
					<?php endif; ?>
				</td>
			</tr>
			<?php endforeach; ?>
		</table>

		<p class="submit">
			<input type="submit" name="wplp_save_settings" class="button button-primary" value="<?php esc_attr_e( 'Save Settings', 'wp-license-platform' ); ?>" />
		</p>
	</form>
</div>
