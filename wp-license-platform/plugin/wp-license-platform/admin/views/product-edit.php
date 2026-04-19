<?php
if ( ! defined( 'ABSPATH' ) ) exit;
$is_edit = ! empty( $product );
if ( isset( $_GET['saved'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended
?>
<div class="notice notice-success"><p><?php esc_html_e( 'Product saved.', 'wp-license-platform' ); ?></p></div>
<?php endif; ?>

<div class="wrap lf-wrap">
	<div class="lf-header">
		<h1 class="lf-header__title">
			<span class="lf-header__icon"><span class="dashicons dashicons-admin-network"></span></span>
			<?php echo $is_edit ? 'Edit Product' : 'Add New Product'; ?>
		</h1>
	</div>

	<form method="post">
		<?php wp_nonce_field( 'wplp_save_product', 'wplp_product_nonce' ); ?>
		<input type="hidden" name="product_id" value="<?php echo esc_attr( $product->id ?? 0 ); ?>" />

		<table class="form-table">
			<tr>
				<th><label for="product_name"><?php esc_html_e( 'Product Name', 'wp-license-platform' ); ?></label></th>
				<td><input type="text" id="product_name" name="product_name" value="<?php echo esc_attr( $product->name ?? '' ); ?>" class="regular-text" required /></td>
			</tr>
			<tr>
				<th><label for="product_slug"><?php esc_html_e( 'Slug', 'wp-license-platform' ); ?></label></th>
				<td><input type="text" id="product_slug" name="product_slug" value="<?php echo esc_attr( $product->slug ?? '' ); ?>" class="regular-text" required />
				<p class="description"><?php esc_html_e( 'Used in shortcodes: [wplp_pricing product="your-slug"]', 'wp-license-platform' ); ?></p></td>
			</tr>
			<tr>
				<th><label for="product_description"><?php esc_html_e( 'Description', 'wp-license-platform' ); ?></label></th>
				<td><textarea id="product_description" name="product_description" rows="3" class="large-text"><?php echo esc_textarea( $product->description ?? '' ); ?></textarea></td>
			</tr>
			<tr>
				<th><label for="product_version"><?php esc_html_e( 'Version', 'wp-license-platform' ); ?></label></th>
				<td><input type="text" id="product_version" name="product_version" value="<?php echo esc_attr( $product->version ?? '1.0.0' ); ?>" class="small-text" /></td>
			</tr>
			<tr>
				<th><label for="license_prefix"><?php esc_html_e( 'License Prefix', 'wp-license-platform' ); ?></label></th>
				<td><input type="text" id="license_prefix" name="license_prefix" value="<?php echo esc_attr( $product->license_prefix ?? 'WPLP' ); ?>" class="small-text" maxlength="10" />
				<p class="description"><?php esc_html_e( 'Prefix for license keys (e.g., WPS3B generates WPS3B-XXXX-XXXX-XXXX)', 'wp-license-platform' ); ?></p></td>
			</tr>
			<tr>
				<th><label><?php esc_html_e( 'Downloadable File', 'wp-license-platform' ); ?></label></th>
				<td>
					<input type="hidden" id="product_file_path" name="product_file_path" value="<?php echo esc_attr( $product->file_path ?? '' ); ?>" />

					<div id="wplp-file-upload-area">
						<?php if ( ! empty( $product->file_path ) && file_exists( $product->file_path ) ) : ?>
						<!-- Current file -->
						<div id="wplp-current-file" class="wplp-file-info">
							<span class="dashicons dashicons-media-archive"></span>
							<strong id="wplp-file-name"><?php echo esc_html( basename( $product->file_path ) ); ?></strong>
							<span id="wplp-file-size" class="wplp-file-meta">(<?php echo esc_html( size_format( filesize( $product->file_path ) ) ); ?>)</span>
							<button type="button" class="button button-small" id="wplp-replace-file"><?php esc_html_e( 'Replace', 'wp-license-platform' ); ?></button>
							<button type="button" class="button button-small wplp-delete-file" style="color:#d63638;"><?php esc_html_e( 'Remove', 'wp-license-platform' ); ?></button>
						</div>
						<?php elseif ( ! empty( $product->file_path ) ) : ?>
						<!-- Path set but file missing -->
						<div id="wplp-current-file" class="wplp-file-info wplp-file-missing">
							<span class="dashicons dashicons-warning" style="color:#d63638;"></span>
							<strong><?php echo esc_html( basename( $product->file_path ) ); ?></strong>
							<span style="color:#d63638;"><?php esc_html_e( '(file not found)', 'wp-license-platform' ); ?></span>
							<button type="button" class="button button-small wplp-delete-file"><?php esc_html_e( 'Clear', 'wp-license-platform' ); ?></button>
						</div>
						<?php else : ?>
						<div id="wplp-current-file" style="display:none;" class="wplp-file-info">
							<span class="dashicons dashicons-media-archive"></span>
							<strong id="wplp-file-name"></strong>
							<span id="wplp-file-size" class="wplp-file-meta"></span>
							<button type="button" class="button button-small" id="wplp-replace-file"><?php esc_html_e( 'Replace', 'wp-license-platform' ); ?></button>
							<button type="button" class="button button-small wplp-delete-file" style="color:#d63638;"><?php esc_html_e( 'Remove', 'wp-license-platform' ); ?></button>
						</div>
						<?php endif; ?>

						<!-- Upload area -->
						<div id="wplp-upload-zone" <?php echo ( ! empty( $product->file_path ) && file_exists( $product->file_path ) ) ? 'style="display:none;"' : ''; ?>>
							<input type="file" id="wplp-file-input" accept=".zip" style="display:none;" />
							<button type="button" class="button button-secondary" id="wplp-browse-btn">
								<span class="dashicons dashicons-upload" style="vertical-align:middle;margin-top:-2px;"></span>
								<?php esc_html_e( 'Upload Zip File', 'wp-license-platform' ); ?>
							</button>
							<span id="wplp-upload-status" style="margin-left:10px;"></span>
						</div>

						<!-- Progress bar -->
						<div id="wplp-upload-progress" style="display:none;margin-top:8px;">
							<div style="background:#f0f0f1;border-radius:3px;height:20px;width:300px;">
								<div id="wplp-progress-bar" style="background:#2271b1;height:100%;border-radius:3px;width:0%;transition:width 0.3s;"></div>
							</div>
							<span id="wplp-progress-text" style="margin-left:8px;font-size:12px;"></span>
						</div>
					</div>

					<p class="description">
						<?php
						printf(
							esc_html__( 'Upload a .zip file (max %s). Files are stored securely in %s and can only be downloaded by licensed customers through the portal.', 'wp-license-platform' ),
							esc_html( size_format( 104857600 ) ),
							'<code>wp-content/wplp-downloads/</code>'
						);
						?>
					</p>
				</td>
			</tr>
			<tr>
				<th><label for="product_status"><?php esc_html_e( 'Status', 'wp-license-platform' ); ?></label></th>
				<td>
					<select id="product_status" name="product_status">
						<option value="active" <?php selected( $product->status ?? 'active', 'active' ); ?>><?php esc_html_e( 'Active', 'wp-license-platform' ); ?></option>
						<option value="inactive" <?php selected( $product->status ?? '', 'inactive' ); ?>><?php esc_html_e( 'Inactive', 'wp-license-platform' ); ?></option>
					</select>
				</td>
			</tr>
		</table>

		<h2><?php esc_html_e( 'Pricing Tiers', 'wp-license-platform' ); ?></h2>
		<table class="widefat" id="wplp-tiers-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Name (key)', 'wp-license-platform' ); ?></th>
					<th><?php esc_html_e( 'Display Name', 'wp-license-platform' ); ?></th>
					<th><?php esc_html_e( 'Price ($)', 'wp-license-platform' ); ?></th>
					<th><?php esc_html_e( 'Sites', 'wp-license-platform' ); ?></th>
					<th><?php esc_html_e( 'Featured', 'wp-license-platform' ); ?></th>
					<th></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( ! empty( $tiers ) ) : foreach ( $tiers as $i => $tier ) : ?>
				<tr>
					<td><input type="hidden" name="tier_id[]" value="<?php echo esc_attr( $tier->id ); ?>" /><input type="text" name="tier_name[]" value="<?php echo esc_attr( $tier->name ); ?>" class="small-text" /></td>
					<td><input type="text" name="tier_display[]" value="<?php echo esc_attr( $tier->display_name ); ?>" /></td>
					<td><input type="number" name="tier_price[]" value="<?php echo esc_attr( $tier->price ); ?>" step="0.01" class="small-text" /></td>
					<td><input type="number" name="tier_sites[]" value="<?php echo esc_attr( $tier->sites_allowed ); ?>" class="small-text" /> <span class="description">0=unlimited</span></td>
					<td><input type="checkbox" name="tier_featured[]" value="<?php echo esc_attr( $i ); ?>" <?php checked( $tier->is_featured, 1 ); ?> /></td>
					<td><button type="button" class="button wplp-remove-tier">&times;</button></td>
				</tr>
				<?php endforeach; endif; ?>
			</tbody>
		</table>
		<p><button type="button" class="button" id="wplp-add-tier"><?php esc_html_e( 'Add Tier', 'wp-license-platform' ); ?></button></p>

		<p class="submit">
			<input type="submit" name="wplp_save_product" class="button button-primary" value="<?php esc_attr_e( 'Save Product', 'wp-license-platform' ); ?>" />
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=wplp_products' ) ); ?>" class="button"><?php esc_html_e( 'Cancel', 'wp-license-platform' ); ?></a>
		</p>
	</form>
</div>
