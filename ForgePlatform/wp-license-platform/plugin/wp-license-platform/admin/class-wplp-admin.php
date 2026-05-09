<?php
/**
 * Admin panel — menus, settings, product/order/license management.
 *
 * @package WP_License_Platform
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPLP_Admin {

	const MAX_UPLOAD_SIZE = 104857600; // 100MB
	const ALLOWED_TYPES   = array( 'application/zip', 'application/x-zip-compressed', 'application/octet-stream' );

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'register_menus' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
		add_action( 'admin_init', array( __CLASS__, 'handle_settings_save' ) );
		add_action( 'admin_init', array( __CLASS__, 'handle_product_save' ) );
		add_action( 'admin_init', array( __CLASS__, 'handle_delete_order' ) );
		add_action( 'admin_init', array( __CLASS__, 'handle_delete_license' ) );
		add_action( 'wp_ajax_wplp_test_paypal', array( __CLASS__, 'ajax_test_paypal' ) );
		add_action( 'wp_ajax_wplp_update_order_status', array( __CLASS__, 'ajax_update_order_status' ) );
		add_action( 'wp_ajax_wplp_update_license_status', array( __CLASS__, 'ajax_update_license_status' ) );
		add_action( 'wp_ajax_wplp_upload_product_file', array( __CLASS__, 'ajax_upload_product_file' ) );
		add_action( 'wp_ajax_wplp_delete_product_file', array( __CLASS__, 'ajax_delete_product_file' ) );
	}

	public static function register_menus() {
		add_menu_page(
			__( 'License Platform', 'wp-license-platform' ),
			__( 'License Platform', 'wp-license-platform' ),
			'manage_options',
			'wplp_dashboard',
			array( __CLASS__, 'render_dashboard' ),
			'dashicons-admin-network',
			4
		);

		add_submenu_page( 'wplp_dashboard', __( 'Dashboard', 'wp-license-platform' ), __( 'Dashboard', 'wp-license-platform' ), 'manage_options', 'wplp_dashboard', array( __CLASS__, 'render_dashboard' ) );
		add_submenu_page( 'wplp_dashboard', __( 'Products', 'wp-license-platform' ), __( 'Products', 'wp-license-platform' ), 'manage_options', 'wplp_products', array( __CLASS__, 'render_products' ) );
		add_submenu_page( 'wplp_dashboard', __( 'Orders', 'wp-license-platform' ), __( 'Orders', 'wp-license-platform' ), 'manage_options', 'wplp_orders', array( __CLASS__, 'render_orders' ) );
		add_submenu_page( 'wplp_dashboard', __( 'Licenses', 'wp-license-platform' ), __( 'Licenses', 'wp-license-platform' ), 'manage_options', 'wplp_licenses', array( __CLASS__, 'render_licenses' ) );
		add_submenu_page( 'wplp_dashboard', __( 'Settings', 'wp-license-platform' ), __( 'Settings', 'wp-license-platform' ), 'manage_options', 'wplp_settings', array( __CLASS__, 'render_settings' ) );

		// Upgrade page (only if not Pro)
		$is_pro = function_exists( 'wplp_is_pro_active' ) && wplp_is_pro_active();
		if ( ! $is_pro ) {
			add_submenu_page(
				'wplp_dashboard',
				__( 'Upgrade to Pro', 'wp-license-platform' ),
				'<span style="color:#9b59b6;">' . __( 'Upgrade to Pro', 'wp-license-platform' ) . '</span>',
				'manage_options',
				'wplp_upgrade',
				array( __CLASS__, 'render_upgrade' )
			);
		}
	}

	public static function enqueue_assets( $hook ) {
		if ( strpos( $hook, 'wplp_' ) === false ) {
			return;
		}
		wp_enqueue_style( 'wplp-admin', WPLP_PLUGIN_URL . 'admin/css/admin.css', array(), WPLP_VERSION );
		wp_enqueue_script( 'wplp-admin', WPLP_PLUGIN_URL . 'admin/js/admin.js', array( 'jquery' ), WPLP_VERSION, true );
		wp_localize_script( 'wplp-admin', 'wplp_admin', array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'wplp_admin' ),
		) );
	}

	// ─── Dashboard ───────────────────────────────────

	public static function render_dashboard() {
		$stats = array(
			'orders'    => WPLP_DB::get_order_count(),
			'revenue'   => WPLP_DB::get_total_revenue(),
			'licenses'  => WPLP_DB::get_active_license_count(),
			'customers' => WPLP_DB::get_customer_count(),
		);
		$recent_orders = WPLP_Order::get_all( '', 10 );
		include WPLP_PLUGIN_DIR . 'admin/views/dashboard.php';
	}

	// ─── Products ────────────────────────────────────

	public static function render_products() {
		$action = isset( $_GET['action'] ) ? sanitize_key( $_GET['action'] ) : 'list'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$id     = absint( $_GET['id'] ?? 0 ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( 'edit' === $action || 'new' === $action ) {
			$product = $id ? WPLP_DB::get_product( $id ) : null;
			$tiers   = $id ? WPLP_DB::get_tiers( $id, '' ) : array();
			include WPLP_PLUGIN_DIR . 'admin/views/product-edit.php';
		} else {
			$products = WPLP_DB::get_products( '' );
			include WPLP_PLUGIN_DIR . 'admin/views/products-list.php';
		}
	}

	public static function handle_product_save() {
		if ( ! isset( $_POST['wplp_save_product'] ) ) {
			return;
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		check_admin_referer( 'wplp_save_product', 'wplp_product_nonce' );

		global $wpdb;
		$id = absint( $_POST['product_id'] ?? 0 );

		$data = array(
			'name'           => sanitize_text_field( wp_unslash( $_POST['product_name'] ?? '' ) ),
			'slug'           => sanitize_title( wp_unslash( $_POST['product_slug'] ?? '' ) ),
			'description'    => sanitize_textarea_field( wp_unslash( $_POST['product_description'] ?? '' ) ),
			'version'        => sanitize_text_field( wp_unslash( $_POST['product_version'] ?? '1.0.0' ) ),
			'file_path'      => sanitize_text_field( wp_unslash( $_POST['product_file_path'] ?? '' ) ),
			'license_prefix' => strtoupper( sanitize_text_field( wp_unslash( $_POST['license_prefix'] ?? 'WPLP' ) ) ),
			'status'         => sanitize_key( $_POST['product_status'] ?? 'active' ),
			'updated_at'     => current_time( 'mysql' ),
		);

		if ( $id ) {
			$wpdb->update( $wpdb->prefix . 'wplp_products', $data, array( 'id' => $id ) );
		} else {
			$data['created_at'] = current_time( 'mysql' );
			$wpdb->insert( $wpdb->prefix . 'wplp_products', $data );
			$id = $wpdb->insert_id;
		}

		// Save tiers
		$tier_names    = $_POST['tier_name'] ?? array();
		$tier_displays = $_POST['tier_display'] ?? array();
		$tier_prices   = $_POST['tier_price'] ?? array();
		$tier_sites    = $_POST['tier_sites'] ?? array();
		$tier_ids      = $_POST['tier_id'] ?? array();
		$tier_featured = $_POST['tier_featured'] ?? array();

		// Delete removed tiers
		$keep_ids = array_filter( array_map( 'absint', $tier_ids ) );
		if ( $keep_ids ) {
			$placeholders = implode( ',', array_fill( 0, count( $keep_ids ), '%d' ) );
			$wpdb->query( $wpdb->prepare(
				"DELETE FROM {$wpdb->prefix}wplp_product_tiers WHERE product_id = %d AND id NOT IN ($placeholders)", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				array_merge( array( $id ), $keep_ids )
			) );
		} else {
			$wpdb->delete( $wpdb->prefix . 'wplp_product_tiers', array( 'product_id' => $id ) );
		}

		// Insert/update tiers
		for ( $i = 0; $i < count( $tier_names ); $i++ ) {
			$tier_data = array(
				'product_id'   => $id,
				'name'         => sanitize_key( $tier_names[ $i ] ?? '' ),
				'display_name' => sanitize_text_field( $tier_displays[ $i ] ?? '' ),
				'price'        => floatval( $tier_prices[ $i ] ?? 0 ),
				'sites_allowed' => absint( $tier_sites[ $i ] ?? 1 ),
				'is_featured'  => in_array( $i, array_map( 'intval', $tier_featured ), true ) ? 1 : 0,
				'sort_order'   => $i,
				'status'       => 'active',
				'created_at'   => current_time( 'mysql' ),
			);

			$tier_id = absint( $tier_ids[ $i ] ?? 0 );
			if ( $tier_id ) {
				unset( $tier_data['created_at'] );
				$wpdb->update( $wpdb->prefix . 'wplp_product_tiers', $tier_data, array( 'id' => $tier_id ) );
			} else {
				$wpdb->insert( $wpdb->prefix . 'wplp_product_tiers', $tier_data );
			}
		}

		wp_safe_redirect( admin_url( 'admin.php?page=wplp_products&action=edit&id=' . $id . '&saved=1' ) );
		exit;
	}

	// ─── Orders ──────────────────────────────────────

	public static function render_orders() {
		$orders = WPLP_Order::get_all();
		include WPLP_PLUGIN_DIR . 'admin/views/orders-list.php';
	}

	// ─── Licenses ────────────────────────────────────

	public static function render_licenses() {
		$licenses = WPLP_License::get_all();
		include WPLP_PLUGIN_DIR . 'admin/views/licenses-list.php';
	}

	// ─── Settings ────────────────────────────────────

	public static function render_settings() {
		$settings = get_option( 'wplp_settings', array() );
		include WPLP_PLUGIN_DIR . 'admin/views/settings-page.php';
	}

	public static function handle_settings_save() {
		if ( ! isset( $_POST['wplp_save_settings'] ) ) {
			return;
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		check_admin_referer( 'wplp_save_settings', 'wplp_settings_nonce' );

		$settings = array(
			'business_name'    => sanitize_text_field( wp_unslash( $_POST['business_name'] ?? '' ) ),
			'business_address' => sanitize_textarea_field( wp_unslash( $_POST['business_address'] ?? '' ) ),
			'business_country' => sanitize_text_field( wp_unslash( $_POST['business_country'] ?? '' ) ),
			'vat_number'       => sanitize_text_field( wp_unslash( $_POST['vat_number'] ?? '' ) ),
			'paypal_sandbox'   => isset( $_POST['paypal_sandbox'] ) ? 1 : 0,
			'support_email'    => sanitize_email( wp_unslash( $_POST['support_email'] ?? '' ) ),
		);

		update_option( 'wplp_settings', $settings );

		// Save PayPal credentials (encrypted)
		$client_id     = sanitize_text_field( wp_unslash( $_POST['paypal_client_id'] ?? '' ) );
		$client_secret = sanitize_text_field( wp_unslash( $_POST['paypal_client_secret'] ?? '' ) );

		if ( ! empty( $client_id ) && false === strpos( $client_id, '****' ) &&
		     ! empty( $client_secret ) && false === strpos( $client_secret, '****' ) ) {
			$encrypted = WPLP_Crypto::encrypt( wp_json_encode( array(
				'client_id'     => $client_id,
				'client_secret' => $client_secret,
			) ) );
			update_option( 'wplp_paypal_credentials', $encrypted );
		}

		$webhook_id = sanitize_text_field( wp_unslash( $_POST['paypal_webhook_id'] ?? '' ) );
		if ( ! empty( $webhook_id ) ) {
			update_option( 'wplp_paypal_webhook_id', $webhook_id );
		}

		// Save page assignments
		$page_keys = array( 'pricing', 'checkout', 'thank_you', 'account', 'licenses', 'downloads', 'invoices' );
		$wplp_pages = get_option( 'wplp_pages', array() );
		foreach ( $page_keys as $key ) {
			$page_id = absint( $_POST[ 'wplp_page_' . $key ] ?? 0 );
			if ( $page_id ) {
				$wplp_pages[ $key ] = $page_id;
			}
		}
		update_option( 'wplp_pages', $wplp_pages );

		add_settings_error( 'wplp_settings', 'saved', __( 'Settings saved.', 'wp-license-platform' ), 'success' );
	}

	// ─── Order Actions ────────────────────────────

	public static function handle_delete_order() {
		if ( ! isset( $_GET['wplp_delete_order'] ) ) return;
		$id = absint( $_GET['wplp_delete_order'] );
		check_admin_referer( 'wplp_delete_order_' . $id );
		if ( ! current_user_can( 'manage_options' ) ) return;

		global $wpdb;
		$wpdb->delete( $wpdb->prefix . 'wplp_orders', array( 'id' => $id ) );
		$wpdb->delete( $wpdb->prefix . 'wplp_licenses', array( 'order_id' => $id ) );

		wp_safe_redirect( admin_url( 'admin.php?page=wplp_orders&deleted=1' ) );
		exit;
	}

	public static function ajax_update_order_status() {
		check_ajax_referer( 'wplp_admin', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Unauthorized.' );

		$id     = absint( $_POST['order_id'] ?? 0 );
		$status = sanitize_key( $_POST['status'] ?? '' );
		$valid  = array( 'pending', 'completed', 'refunded', 'failed' );

		if ( ! $id || ! in_array( $status, $valid, true ) ) wp_send_json_error( 'Invalid.' );

		WPLP_Order::update_status( $id, $status );
		wp_send_json_success( 'Status updated.' );
	}

	// ─── License Actions ──────────────────────────

	public static function handle_delete_license() {
		if ( ! isset( $_GET['wplp_delete_license'] ) ) return;
		$id = absint( $_GET['wplp_delete_license'] );
		check_admin_referer( 'wplp_delete_license_' . $id );
		if ( ! current_user_can( 'manage_options' ) ) return;

		global $wpdb;
		$wpdb->delete( $wpdb->prefix . 'wplp_activations', array( 'license_id' => $id ) );
		$wpdb->delete( $wpdb->prefix . 'wplp_licenses', array( 'id' => $id ) );

		wp_safe_redirect( admin_url( 'admin.php?page=wplp_licenses&deleted=1' ) );
		exit;
	}

	public static function ajax_update_license_status() {
		check_ajax_referer( 'wplp_admin', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Unauthorized.' );

		$id     = absint( $_POST['license_id'] ?? 0 );
		$status = sanitize_key( $_POST['status'] ?? '' );
		$valid  = array( 'active', 'expired', 'revoked', 'suspended' );

		if ( ! $id || ! in_array( $status, $valid, true ) ) wp_send_json_error( 'Invalid.' );

		global $wpdb;
		$wpdb->update( $wpdb->prefix . 'wplp_licenses', array( 'status' => $status, 'updated_at' => current_time( 'mysql' ) ), array( 'id' => $id ) );
		wp_send_json_success( 'Status updated.' );
	}

	// ─── Upgrade Page ────────────────────────────────

	public static function render_upgrade() {
		include WPLP_PLUGIN_DIR . 'admin/views/upgrade-page.php';
	}

	// ─── AJAX ────────────────────────────────────────

	// ─── File Upload ──────────────────────────────────

	public static function ajax_upload_product_file() {
		check_ajax_referer( 'wplp_admin', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Unauthorized.', 'wp-license-platform' ) );
		}

		if ( empty( $_FILES['product_zip'] ) || $_FILES['product_zip']['error'] !== UPLOAD_ERR_OK ) {
			$code = $_FILES['product_zip']['error'] ?? -1;
			wp_send_json_error( sprintf( __( 'Upload error (code %d).', 'wp-license-platform' ), $code ) );
		}

		$file = $_FILES['product_zip'];

		// Validate file size
		if ( $file['size'] > self::MAX_UPLOAD_SIZE ) {
			wp_send_json_error( sprintf( __( 'File too large. Maximum: %s.', 'wp-license-platform' ), size_format( self::MAX_UPLOAD_SIZE ) ) );
		}

		// Validate extension
		$ext = strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) );
		if ( 'zip' !== $ext ) {
			wp_send_json_error( __( 'Only .zip files are allowed.', 'wp-license-platform' ) );
		}

		// Validate MIME type
		$finfo = finfo_open( FILEINFO_MIME_TYPE );
		$mime  = finfo_file( $finfo, $file['tmp_name'] );
		finfo_close( $finfo );
		if ( ! in_array( $mime, self::ALLOWED_TYPES, true ) ) {
			wp_send_json_error( sprintf( __( 'Invalid file type: %s. Only zip archives are allowed.', 'wp-license-platform' ), $mime ) );
		}

		// Validate it's actually a zip by trying to open it
		$zip = new ZipArchive();
		if ( true !== $zip->open( $file['tmp_name'] ) ) {
			wp_send_json_error( __( 'File is not a valid zip archive.', 'wp-license-platform' ) );
		}
		$zip->close();

		// Ensure downloads directory exists and is protected
		WPLP_Plugin::create_downloads_dir();
		$downloads_dir = WPLP_Plugin::get_downloads_dir();

		// Sanitize filename
		$safe_name = sanitize_file_name( $file['name'] );
		$dest_path = $downloads_dir . '/' . $safe_name;

		// If file already exists, add a timestamp suffix
		if ( file_exists( $dest_path ) ) {
			$name_part = pathinfo( $safe_name, PATHINFO_FILENAME );
			$dest_path = $downloads_dir . '/' . $name_part . '-' . time() . '.zip';
			$safe_name = basename( $dest_path );
		}

		if ( ! move_uploaded_file( $file['tmp_name'], $dest_path ) ) {
			wp_send_json_error( __( 'Failed to move uploaded file.', 'wp-license-platform' ) );
		}

		// Set restrictive permissions
		chmod( $dest_path, 0644 );

		wp_send_json_success( array(
			'file_path' => $dest_path,
			'file_name' => $safe_name,
			'file_size' => size_format( filesize( $dest_path ) ),
		) );
	}

	public static function ajax_delete_product_file() {
		check_ajax_referer( 'wplp_admin', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Unauthorized.', 'wp-license-platform' ) );
		}

		$file_path    = sanitize_text_field( wp_unslash( $_POST['file_path'] ?? '' ) );
		$downloads_dir = WPLP_Plugin::get_downloads_dir();

		// Security: only allow deleting files inside the downloads directory
		$real_path = realpath( $file_path );
		$real_dir  = realpath( $downloads_dir );
		if ( ! $real_path || ! $real_dir || 0 !== strpos( $real_path, $real_dir ) ) {
			wp_send_json_error( __( 'Invalid file path.', 'wp-license-platform' ) );
		}

		if ( file_exists( $real_path ) ) {
			unlink( $real_path );
		}

		wp_send_json_success();
	}

	public static function ajax_test_paypal() {
		check_ajax_referer( 'wplp_admin', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Unauthorized.' );
		}

		$paypal = new WPLP_PayPal();
		if ( ! $paypal->is_configured() ) {
			wp_send_json_error( __( 'PayPal credentials not configured. Save settings first.', 'wp-license-platform' ) );
		}

		$result = $paypal->test_connection();
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( $result->get_error_message() );
		}

		wp_send_json_success( __( 'PayPal connection successful!', 'wp-license-platform' ) );
	}
}

/**
 * Check if the Pro version of the platform is active.
 */
function wplp_is_pro_active() {
	return class_exists( 'WPLP_Pro' ) && method_exists( 'WPLP_Pro', 'is_licensed' ) && WPLP_Pro::is_licensed();
}
