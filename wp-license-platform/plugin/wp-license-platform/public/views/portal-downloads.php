<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wplp-portal">
	<h2><?php esc_html_e( 'Downloads', 'wp-license-platform' ); ?></h2>
	<?php if ( empty( $downloads ) ) : ?>
		<p><?php esc_html_e( 'No downloads available. Purchase a product to access downloads.', 'wp-license-platform' ); ?></p>
	<?php else : ?>
	<table class="wplp-table">
		<thead><tr><th><?php esc_html_e( 'Product', 'wp-license-platform' ); ?></th><th><?php esc_html_e( 'Version', 'wp-license-platform' ); ?></th><th><?php esc_html_e( 'License', 'wp-license-platform' ); ?></th><th></th></tr></thead>
		<tbody>
		<?php foreach ( $downloads as $dl ) : ?>
			<tr>
				<td><strong><?php echo esc_html( $dl['product']->name ); ?></strong></td>
				<td><?php echo esc_html( $dl['product']->version ); ?></td>
				<td><code><?php echo esc_html( $dl['license']->license_key ); ?></code></td>
				<td><a href="<?php echo esc_url( $dl['download_url'] ); ?>" class="button"><?php esc_html_e( 'Download', 'wp-license-platform' ); ?></a></td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
	<?php endif; ?>
</div>
