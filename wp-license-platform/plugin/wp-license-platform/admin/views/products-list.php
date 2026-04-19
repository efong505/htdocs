<?php
if ( ! defined( 'ABSPATH' ) ) exit;
?>
<div class="wrap lf-wrap">
	<div class="lf-header">
		<h1 class="lf-header__title">
			<span class="lf-header__icon"><span class="dashicons dashicons-admin-network"></span></span>
			LicenseForge — Products
		</h1>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=wplp_products&action=new' ) ); ?>" class="lf-btn lf-btn--primary lf-btn--sm">Add New</a>
	</div>

	<?php if ( empty( $products ) ) : ?>
		<p><?php esc_html_e( 'No products yet. Create your first product to start selling.', 'wp-license-platform' ); ?></p>
	<?php else : ?>
	<table class="widefat striped">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Name', 'wp-license-platform' ); ?></th>
				<th><?php esc_html_e( 'Slug', 'wp-license-platform' ); ?></th>
				<th><?php esc_html_e( 'Version', 'wp-license-platform' ); ?></th>
				<th><?php esc_html_e( 'Status', 'wp-license-platform' ); ?></th>
				<th><?php esc_html_e( 'Tiers', 'wp-license-platform' ); ?></th>
				<th><?php esc_html_e( 'Actions', 'wp-license-platform' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $products as $p ) :
				$tiers = WPLP_DB::get_tiers( $p->id, '' );
			?>
			<tr>
				<td><strong><?php echo esc_html( $p->name ); ?></strong></td>
				<td><code><?php echo esc_html( $p->slug ); ?></code></td>
				<td><?php echo esc_html( $p->version ); ?></td>
				<td><span class="wplp-status wplp-status-<?php echo esc_attr( $p->status ); ?>"><?php echo esc_html( ucfirst( $p->status ) ); ?></span></td>
				<td><?php echo esc_html( count( $tiers ) ); ?></td>
				<td>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=wplp_products&action=edit&id=' . $p->id ) ); ?>"><?php esc_html_e( 'Edit', 'wp-license-platform' ); ?></a>
				</td>
			</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
	<?php endif; ?>
</div>
