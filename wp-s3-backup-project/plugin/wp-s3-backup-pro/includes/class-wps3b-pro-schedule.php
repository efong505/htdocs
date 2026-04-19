<?php
/**
 * Custom cron schedules — hourly, every 6 hours, specific time of day.
 *
 * @package WP_S3_Backup_Pro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPS3B_Pro_Schedule {

	public static function init() {
		add_filter( 'cron_schedules', array( __CLASS__, 'add_schedules' ) );
	}

	/**
	 * Add Pro cron intervals.
	 */
	public static function add_schedules( $schedules ) {
		$schedules['every_6_hours'] = array(
			'interval' => 21600,
			'display'  => esc_html__( 'Every 6 Hours', 'wp-s3-backup-pro' ),
		);
		if ( ! isset( $schedules['hourly'] ) ) {
			$schedules['hourly'] = array(
				'interval' => 3600,
				'display'  => esc_html__( 'Hourly', 'wp-s3-backup-pro' ),
			);
		}
		$schedules['every_4_hours'] = array(
			'interval' => 14400,
			'display'  => esc_html__( 'Every 4 Hours', 'wp-s3-backup-pro' ),
		);
		return $schedules;
	}

	/**
	 * Reschedule the backup cron with a custom schedule and optional time.
	 */
	public static function reschedule( $frequency, $time_of_day = '' ) {
		$hook      = 'wps3b_scheduled_backup';
		$timestamp = wp_next_scheduled( $hook );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, $hook );
		}

		// Calculate next run time based on time-of-day preference
		$next_run = time();
		if ( ! empty( $time_of_day ) && preg_match( '/^(\d{2}):(\d{2})$/', $time_of_day, $m ) ) {
			$target = strtotime( 'today ' . $time_of_day, current_time( 'timestamp' ) );
			if ( $target <= current_time( 'timestamp' ) ) {
				$target += DAY_IN_SECONDS;
			}
			$next_run = $target - ( current_time( 'timestamp' ) - time() ); // Convert to UTC
		}

		wp_schedule_event( $next_run, $frequency, $hook );

		// Also update the free plugin's settings to reflect the new frequency
		$settings = get_option( 'wps3b_settings', array() );
		$settings['frequency'] = $frequency;
		$settings['enabled']   = 1;
		update_option( 'wps3b_settings', $settings );
	}
}
