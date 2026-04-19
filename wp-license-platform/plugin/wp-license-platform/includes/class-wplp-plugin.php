<?php
/**
 * Main plugin class — activation, deactivation, cron, initialization.
 *
 * @package WP_License_Platform
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPLP_Plugin {

	const CRON_HOOK = 'wplp_daily_license_check';

	public static function init() {
		WPLP_API::init();
		WPLP_Checkout::init();
		WPLP_Portal::init();
		WPLP_Admin::init();

		add_action( self::CRON_HOOK, array( __CLASS__, 'daily_license_check' ) );
		add_action( 'admin_init', array( __CLASS__, 'maybe_schedule_cron' ) );
		add_filter( 'plugin_action_links_' . WPLP_PLUGIN_BASENAME, array( __CLASS__, 'add_action_links' ) );
	}

	public static function activate() {
		WPLP_DB::create_tables();
		self::create_downloads_dir();
		self::create_default_pages();

		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time(), 'daily', self::CRON_HOOK );
		}

		flush_rewrite_rules();
	}

	/**
	 * Get the downloads directory path.
	 */
	public static function get_downloads_dir() {
		return WP_CONTENT_DIR . '/wplp-downloads';
	}

	/**
	 * Create and protect the downloads directory.
	 */
	/**
	 * Create and protect the downloads directory.
	 */
	public static function create_downloads_dir() {
		$dir = self::get_downloads_dir();

		if ( ! is_dir( $dir ) ) {
			wp_mkdir_p( $dir );
		}

		// .htaccess — blocks direct HTTP access (Apache/LiteSpeed)
		$htaccess = $dir . '/.htaccess';
		if ( ! file_exists( $htaccess ) ) {
			file_put_contents( $htaccess, "Deny from all\n" );
		}

		// index.php — prevents directory listing on servers without .htaccess
		$index = $dir . '/index.php';
		if ( ! file_exists( $index ) ) {
			file_put_contents( $index, "<?php\n// Silence is golden.\n" );
		}
	}

	public static function deactivate() {
		$timestamp = wp_next_scheduled( self::CRON_HOOK );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, self::CRON_HOOK );
		}
	}

	public static function maybe_schedule_cron() {
		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time(), 'daily', self::CRON_HOOK );
		}
	}

	/**
	 * Create default pages with shortcodes on activation.
	 */
	public static function create_default_pages() {
		$pages = get_option( 'wplp_pages', array() );

		$defaults = array(
			'pricing' => array(
				'title'   => 'Pricing',
				'content' => '[wplp_pricing]',
			),
			'checkout' => array(
				'title'   => 'Checkout',
				'content' => '[wplp_checkout]',
			),
			'thank_you' => array(
				'title'   => 'Thank You',
				'content' => '[wplp_thank_you]',
			),
			'account' => array(
				'title'   => 'My Account',
				'content' => '[wplp_portal]',
			),
			'licenses' => array(
				'title'   => 'My Licenses',
				'content' => '[wplp_licenses]',
			),
			'downloads' => array(
				'title'   => 'My Downloads',
				'content' => '[wplp_downloads]',
			),
			'invoices' => array(
				'title'   => 'My Invoices',
				'content' => '[wplp_invoices]',
			),
		);

		foreach ( $defaults as $key => $page ) {
			// Skip if already created
			if ( ! empty( $pages[ $key ] ) && get_post_status( $pages[ $key ] ) ) {
				continue;
			}

			$id = wp_insert_post( array(
				'post_title'   => $page['title'],
				'post_content' => $page['content'],
				'post_status'  => 'publish',
				'post_type'    => 'page',
			) );

			if ( ! is_wp_error( $id ) ) {
				$pages[ $key ] = $id;
			}
		}

		update_option( 'wplp_pages', $pages );
	}

	public static function daily_license_check() {
		global $wpdb;

		// Expire licenses past their date
		$expired = $wpdb->get_results( $wpdb->prepare(
			"SELECT l.*, c.email, p.name as product_name
			 FROM {$wpdb->prefix}wplp_licenses l
			 LEFT JOIN {$wpdb->prefix}wplp_customers c ON l.customer_id = c.id
			 LEFT JOIN {$wpdb->prefix}wplp_products p ON l.product_id = p.id
			 WHERE l.status = 'active' AND l.expires_at IS NOT NULL AND l.expires_at < %s",
			current_time( 'mysql' )
		) );

		foreach ( $expired as $license ) {
			$wpdb->update(
				$wpdb->prefix . 'wplp_licenses',
				array( 'status' => 'expired', 'updated_at' => current_time( 'mysql' ) ),
				array( 'id' => $license->id )
			);
			WPLP_Email::send_license_expired( $license );
		}

		// Send renewal reminders
		$reminder_days = array( 30, 7, 1 );
		foreach ( $reminder_days as $days ) {
			$target_date = gmdate( 'Y-m-d', strtotime( "+{$days} days" ) );
			$licenses = $wpdb->get_results( $wpdb->prepare(
				"SELECT l.*, c.email, c.first_name, p.name as product_name
				 FROM {$wpdb->prefix}wplp_licenses l
				 JOIN {$wpdb->prefix}wplp_customers c ON l.customer_id = c.id
				 JOIN {$wpdb->prefix}wplp_products p ON l.product_id = p.id
				 WHERE l.status = 'active' AND DATE(l.expires_at) = %s",
				$target_date
			) );

			foreach ( $licenses as $license ) {
				WPLP_Email::send_renewal_reminder( $license, $days );
			}
		}
	}

	public static function add_action_links( $links ) {
		$settings_link = sprintf(
			'<a href="%s">%s</a>',
			esc_url( admin_url( 'admin.php?page=wplp_dashboard' ) ),
			esc_html__( 'Settings', 'wp-license-platform' )
		);
		array_unshift( $links, $settings_link );
		return $links;
	}
}
