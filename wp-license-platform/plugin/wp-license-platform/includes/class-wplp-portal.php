<?php
/**
 * Customer portal — dashboard, licenses, downloads, invoices.
 *
 * @package WP_License_Platform
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPLP_Portal {

	public static function init() {
		add_shortcode( 'wplp_portal', array( __CLASS__, 'render_dashboard' ) );
		add_shortcode( 'wplp_licenses', array( __CLASS__, 'render_licenses' ) );
		add_shortcode( 'wplp_downloads', array( __CLASS__, 'render_downloads' ) );
		add_shortcode( 'wplp_invoices', array( __CLASS__, 'render_invoices' ) );
		add_action( 'init', array( __CLASS__, 'handle_download' ) );
		add_action( 'init', array( __CLASS__, 'handle_view_invoice' ) );
		add_action( 'init', array( __CLASS__, 'handle_deactivate_site' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_styles' ) );
	}

	public static function enqueue_styles() {
		if ( is_singular() ) {
			global $post;
			if ( $post && preg_match( '/\[wplp_(portal|licenses|downloads|invoices)/', $post->post_content ) ) {
				wp_enqueue_style( 'wplp-public', WPLP_PLUGIN_URL . 'public/css/public.css', array(), WPLP_VERSION );
			}
		}
	}

	/**
	 * Render the portal navigation bar.
	 */
	public static function render_nav() {
		$pages = get_option( 'wplp_pages', array() );
		$nav_items = array(
			'account'   => array( 'label' => 'Dashboard', 'icon' => 'dashicons-dashboard' ),
			'licenses'  => array( 'label' => 'Licenses', 'icon' => 'dashicons-admin-network' ),
			'downloads' => array( 'label' => 'Downloads', 'icon' => 'dashicons-download' ),
			'invoices'  => array( 'label' => 'Invoices', 'icon' => 'dashicons-media-text' ),
		);

		$current_url = get_permalink();

		echo '<nav class="wplp-portal-nav">';
		foreach ( $nav_items as $key => $item ) {
			$url = ! empty( $pages[ $key ] ) ? get_permalink( $pages[ $key ] ) : '#';
			$active = ( $url && rtrim( $url, '/' ) === rtrim( $current_url, '/' ) ) ? ' wplp-nav-active' : '';
			echo '<a href="' . esc_url( $url ) . '" class="wplp-nav-item' . $active . '">';
			echo '<span class="dashicons ' . esc_attr( $item['icon'] ) . '"></span> ';
			echo esc_html( $item['label'] );
			echo '</a>';
		}
		echo '</nav>';
	}

	private static function get_customer() {
		if ( ! is_user_logged_in() ) {
			return null;
		}
		return WPLP_Customer::find_by_wp_user( get_current_user_id() );
	}

	private static function login_prompt() {
		return '<div class="wplp-login-prompt"><p>' .
			sprintf(
				esc_html__( 'Please %s to access your account.', 'wp-license-platform' ),
				'<a href="' . esc_url( wp_login_url( get_permalink() ) ) . '">' . esc_html__( 'log in', 'wp-license-platform' ) . '</a>'
			) . '</p></div>';
	}

	public static function render_dashboard() {
		$customer = self::get_customer();
		if ( ! $customer ) {
			return self::login_prompt();
		}

		$licenses      = WPLP_License::get_customer_licenses( $customer->id );
		$recent_orders = WPLP_Order::get_customer_orders( $customer->id, 5 );
		$active_count  = 0;
		foreach ( $licenses as $l ) {
			if ( 'active' === $l->status ) {
				$active_count++;
			}
		}

		ob_start();
		include WPLP_PLUGIN_DIR . 'public/views/portal-dashboard.php';
		return ob_get_clean();
	}

	public static function render_licenses() {
		$customer = self::get_customer();
		if ( ! $customer ) {
			return self::login_prompt();
		}

		$licenses = WPLP_License::get_customer_licenses( $customer->id );
		foreach ( $licenses as &$license ) {
			$license->activations = WPLP_License::get_activations( $license->id );
		}

		ob_start();
		include WPLP_PLUGIN_DIR . 'public/views/portal-licenses.php';
		return ob_get_clean();
	}

	public static function render_downloads() {
		$customer = self::get_customer();
		if ( ! $customer ) {
			return self::login_prompt();
		}

		$licenses = WPLP_License::get_customer_licenses( $customer->id );
		$downloads = array();
		foreach ( $licenses as $license ) {
			if ( 'active' !== $license->status ) {
				continue;
			}
			$product = WPLP_DB::get_product( $license->product_id );
			if ( $product && $product->file_path && file_exists( $product->file_path ) ) {
				$downloads[] = array(
					'product'    => $product,
					'license'    => $license,
					'download_url' => self::get_download_url( $license->id, $product->id ),
				);
			}
		}

		ob_start();
		include WPLP_PLUGIN_DIR . 'public/views/portal-downloads.php';
		return ob_get_clean();
	}

	public static function render_invoices() {
		$customer = self::get_customer();
		if ( ! $customer ) {
			return self::login_prompt();
		}

		$orders = WPLP_Order::get_customer_orders( $customer->id );

		ob_start();
		include WPLP_PLUGIN_DIR . 'public/views/portal-invoices.php';
		return ob_get_clean();
	}

	public static function get_download_url( $license_id, $product_id ) {
		$token  = wp_generate_password( 32, false );
		$expiry = time() + HOUR_IN_SECONDS;

		set_transient( 'wplp_dl_' . $token, array(
			'license_id' => $license_id,
			'product_id' => $product_id,
			'expiry'     => $expiry,
		), HOUR_IN_SECONDS );

		return add_query_arg( 'wplp_download', $token, home_url( '/' ) );
	}

	public static function get_invoice_url( $order_id ) {
		$token = wp_create_nonce( 'wplp_invoice_' . $order_id );
		return add_query_arg( array(
			'wplp_invoice' => absint( $order_id ),
			'_wpnonce'     => $token,
		), home_url( '/' ) );
	}

	public static function handle_view_invoice() {
		if ( empty( $_GET['wplp_invoice'] ) ) {
			return;
		}
		if ( ! is_user_logged_in() ) {
			auth_redirect();
		}

		$order_id = absint( $_GET['wplp_invoice'] );
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ?? '' ) ), 'wplp_invoice_' . $order_id ) ) {
			wp_die( esc_html__( 'Invalid invoice link.', 'wp-license-platform' ) );
		}

		$order    = WPLP_Order::find( $order_id );
		$customer = self::get_customer();
		if ( ! $order || ! $customer || (int) $order->customer_id !== (int) $customer->id ) {
			wp_die( esc_html__( 'Invoice not found.', 'wp-license-platform' ) );
		}
		if ( 'completed' !== $order->status ) {
			wp_die( esc_html__( 'Invoice not available.', 'wp-license-platform' ) );
		}

		$html = WPLP_Invoice::render_html( $order_id );

		// Inject print/download toolbar before </body>.
		$toolbar = '<div style="position:fixed;top:0;left:0;right:0;background:#1E293B;padding:10px 20px;display:flex;gap:12px;align-items:center;z-index:9999;box-shadow:0 2px 8px rgba(0,0,0,.3)">' .
			'<button onclick="window.print()" style="background:linear-gradient(135deg,#6366F1,#4F46E5);color:#fff;border:none;padding:8px 20px;border-radius:6px;font-weight:600;cursor:pointer;font-size:13px">' . esc_html__( 'Print / Save as PDF', 'wp-license-platform' ) . '</button>' .
			'<button onclick="history.back()" style="background:transparent;color:#94A3B8;border:1px solid #475569;padding:8px 20px;border-radius:6px;cursor:pointer;font-size:13px">' . esc_html__( 'Back', 'wp-license-platform' ) . '</button>' .
			'</div>' .
			'<style>@media print{div[style*="position:fixed"]{display:none!important}body{padding-top:0!important}}</style>';
		$html = str_replace( '<body>', '<body style="padding-top:60px">' . $toolbar, $html );

		echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Full HTML document.
		exit;
	}

	public static function handle_download() {
		if ( empty( $_GET['wplp_download'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}

		$token = sanitize_text_field( wp_unslash( $_GET['wplp_download'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$data  = get_transient( 'wplp_dl_' . $token );

		if ( ! $data || $data['expiry'] < time() ) {
			wp_die( esc_html__( 'Download link has expired.', 'wp-license-platform' ) );
		}

		$license = WPLP_License::find( $data['license_id'] );
		if ( ! $license || 'active' !== $license->status ) {
			wp_die( esc_html__( 'License is no longer active.', 'wp-license-platform' ) );
		}

		$product = WPLP_DB::get_product( $data['product_id'] );
		if ( ! $product || ! $product->file_path || ! file_exists( $product->file_path ) ) {
			wp_die( esc_html__( 'File not found.', 'wp-license-platform' ) );
		}

		delete_transient( 'wplp_dl_' . $token );

		header( 'Content-Type: application/zip' );
		header( 'Content-Disposition: attachment; filename="' . basename( $product->file_path ) . '"' );
		header( 'Content-Length: ' . filesize( $product->file_path ) );
		header( 'Cache-Control: no-cache, must-revalidate' );
		readfile( $product->file_path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile
		exit;
	}

	public static function handle_deactivate_site() {
		if ( ! isset( $_POST['wplp_deactivate_site'] ) ) {
			return;
		}
		if ( ! is_user_logged_in() ) {
			return;
		}
		check_admin_referer( 'wplp_deactivate_site' );

		$license_id = absint( $_POST['license_id'] ?? 0 );
		$site_url   = esc_url_raw( wp_unslash( $_POST['site_url'] ?? '' ) );

		$license  = WPLP_License::find( $license_id );
		$customer = self::get_customer();

		if ( $license && $customer && (int) $license->customer_id === (int) $customer->id ) {
			WPLP_License::deactivate_site( $license_id, $site_url );
		}
	}
}
