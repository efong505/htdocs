<?php
/**
 * Main plugin class — activation, deactivation, cron, menus, assets.
 *
 * @package WP_S3_Backup
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPS3B_Plugin {

	const CRON_HOOK = 'wps3b_scheduled_backup';

	/**
	 * Initialize the plugin.
	 */
	public static function init() {
		WPS3B_Settings::init();

		add_action( 'admin_menu', array( __CLASS__, 'register_menus' ), 20 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
		add_action( self::CRON_HOOK, array( __CLASS__, 'run_scheduled_backup' ) );
		add_action( 'admin_init', array( __CLASS__, 'handle_manual_backup' ) );
		add_action( 'admin_init', array( __CLASS__, 'handle_delete_backup' ) );
		add_action( 'admin_init', array( __CLASS__, 'handle_download_backup' ) );
		add_action( 'admin_init', array( __CLASS__, 'maybe_schedule' ) );
		add_filter( 'cron_schedules', array( __CLASS__, 'add_cron_schedules' ) );
		add_filter( 'plugin_action_links_' . WPS3B_PLUGIN_BASENAME, array( __CLASS__, 'add_action_links' ) );
	}

	/**
	 * Plugin activation.
	 */
	public static function activate() {
		$settings = WPS3B_Settings::get_settings();
		if ( $settings['enabled'] ) {
			if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
				wp_schedule_event( time(), $settings['frequency'], self::CRON_HOOK );
			}
		}
	}

	/**
	 * Plugin deactivation.
	 */
	public static function deactivate() {
		$timestamp = wp_next_scheduled( self::CRON_HOOK );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, self::CRON_HOOK );
		}
	}

	/**
	 * Add custom cron schedules.
	 *
	 * @param array $schedules Existing schedules.
	 * @return array Modified schedules.
	 */
	public static function add_cron_schedules( $schedules ) {
		if ( ! isset( $schedules['weekly'] ) ) {
			$schedules['weekly'] = array(
				'interval' => 604800,
				'display'  => esc_html__( 'Weekly', 'wp-s3-backup' ),
			);
		}
		if ( ! isset( $schedules['monthly'] ) ) {
			$schedules['monthly'] = array(
				'interval' => 2592000,
				'display'  => esc_html__( 'Monthly', 'wp-s3-backup' ),
			);
		}
		return $schedules;
	}

	/**
	 * Ensure cron is scheduled when enabled.
	 */
	public static function maybe_schedule() {
		$settings = WPS3B_Settings::get_settings();
		if ( $settings['enabled'] && ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time(), $settings['frequency'], self::CRON_HOOK );
		}
		if ( ! $settings['enabled'] ) {
			$timestamp = wp_next_scheduled( self::CRON_HOOK );
			if ( $timestamp ) {
				wp_unschedule_event( $timestamp, self::CRON_HOOK );
			}
		}
	}

	/**
	 * Reschedule cron after settings change.
	 *
	 * @param array $settings New settings.
	 */
	public static function reschedule_cron( $settings ) {
		$timestamp = wp_next_scheduled( self::CRON_HOOK );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, self::CRON_HOOK );
		}

		if ( $settings['enabled'] ) {
			wp_schedule_event( time(), $settings['frequency'], self::CRON_HOOK );
		}
	}

	/**
	 * Run a scheduled backup via wp-cron.
	 */
	public static function run_scheduled_backup() {
		if ( ! defined( 'DOING_CRON' ) ) {
			define( 'DOING_CRON', true );
		}
		WPS3B_Backup_Manager::run_backup();
	}

	/**
	 * Handle manual backup button.
	 */
	public static function handle_manual_backup() {
		if ( ! isset( $_POST['wps3b_backup_now'] ) ) {
			return;
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		check_admin_referer( 'wps3b_backup_now', 'wps3b_backup_nonce' );

		$result = WPS3B_Backup_Manager::run_backup();

		if ( is_wp_error( $result ) ) {
			add_settings_error( 'wps3b_backups', 'wps3b_backup_failed',
				sprintf(
					/* translators: Error message */
					esc_html__( 'Backup failed: %s', 'wp-s3-backup' ),
					$result->get_error_message()
				),
				'error'
			);
		} else {
			add_settings_error( 'wps3b_backups', 'wps3b_backup_success',
				esc_html__( 'Backup completed successfully and uploaded to S3.', 'wp-s3-backup' ),
				'success'
			);
		}
	}

	/**
	 * Handle backup deletion.
	 */
	public static function handle_delete_backup() {
		if ( ! isset( $_GET['wps3b_delete'] ) ) {
			return;
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		check_admin_referer( 'wps3b_delete_backup' );

		$timestamp = sanitize_text_field( wp_unslash( $_GET['wps3b_delete'] ) );
		$result    = WPS3B_Backup_Manager::delete_backup( $timestamp );

		if ( is_wp_error( $result ) ) {
			add_settings_error( 'wps3b_backups', 'wps3b_delete_failed', $result->get_error_message(), 'error' );
		} else {
			add_settings_error( 'wps3b_backups', 'wps3b_deleted',
				esc_html__( 'Backup deleted.', 'wp-s3-backup' ),
				'success'
			);
		}
	}

	/**
	 * Handle backup download (redirect to pre-signed URL).
	 */
	public static function handle_download_backup() {
		if ( ! isset( $_GET['wps3b_download'] ) ) {
			return;
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		check_admin_referer( 'wps3b_download_backup' );

		$s3_key = sanitize_text_field( wp_unslash( $_GET['wps3b_download'] ) );
		$url    = WPS3B_Backup_Manager::get_download_url( $s3_key );

		if ( is_wp_error( $url ) ) {
			wp_die( esc_html( $url->get_error_message() ) );
		}

		wp_redirect( $url );
		exit;
	}

	/**
	 * Register admin menu pages.
	 */
	public static function register_menus() {
		add_menu_page(
			__( 'S3 Backup', 'wp-s3-backup' ),
			__( 'S3 Backup', 'wp-s3-backup' ),
			'manage_options',
			'wps3b_settings',
			array( 'WPS3B_Settings', 'render_settings_page' ),
			'dashicons-cloud-upload',
			81
		);

		add_submenu_page(
			'wps3b_settings',
			__( 'Settings', 'wp-s3-backup' ),
			__( 'Settings', 'wp-s3-backup' ),
			'manage_options',
			'wps3b_settings',
			array( 'WPS3B_Settings', 'render_settings_page' )
		);

		add_submenu_page(
			'wps3b_settings',
			__( 'Backups', 'wp-s3-backup' ),
			__( 'Backups', 'wp-s3-backup' ),
			'manage_options',
			'wps3b_backups',
			array( 'WPS3B_Settings', 'render_backups_page' )
		);

		add_submenu_page(
			'wps3b_settings',
			__( 'Logs', 'wp-s3-backup' ),
			__( 'Logs', 'wp-s3-backup' ),
			'manage_options',
			'wps3b_logs',
			array( 'WPS3B_Settings', 'render_logs_page' )
		);
	}

	/**
	 * Enqueue admin CSS and JS on plugin pages only.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public static function enqueue_assets( $hook ) {
		$plugin_pages = array(
			'toplevel_page_wps3b_settings',
			's3-backup_page_wps3b_backups',
			's3-backup_page_wps3b_logs',
		);

		if ( ! in_array( $hook, $plugin_pages, true ) ) {
			return;
		}

		wp_enqueue_style(
			'wps3b-admin',
			WPS3B_PLUGIN_URL . 'admin/css/admin.css',
			array(),
			WPS3B_VERSION
		);

		wp_enqueue_script(
			'wps3b-admin',
			WPS3B_PLUGIN_URL . 'admin/js/admin.js',
			array( 'jquery' ),
			WPS3B_VERSION,
			true
		);

		wp_localize_script( 'wps3b-admin', 'wps3b_ajax', array(
			'url'   => admin_url( 'admin-ajax.php' ),
			'nonce' => wp_create_nonce( 'wps3b_test_connection' ),
			'i18n'  => array(
				'testing'    => esc_html__( 'Testing connection...', 'wp-s3-backup' ),
				'test_btn'   => esc_html__( 'Test Connection', 'wp-s3-backup' ),
			),
		) );
	}

	/**
	 * Add Settings link to plugin list page.
	 *
	 * @param array $links Existing links.
	 * @return array Modified links.
	 */
	public static function add_action_links( $links ) {
		$settings_link = sprintf(
			'<a href="%s">%s</a>',
			esc_url( admin_url( 'admin.php?page=wps3b_settings' ) ),
			esc_html__( 'Settings', 'wp-s3-backup' )
		);
		array_unshift( $links, $settings_link );
		return $links;
	}
}
