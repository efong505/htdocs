<?php
/**
 * Email + Slack + webhook notifications on backup success/failure.
 *
 * @package WP_S3_Backup_Pro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPS3B_Pro_Notifications {

	public static function init() {
		add_action( 'wps3b_after_backup', array( __CLASS__, 'on_success' ) );
		add_action( 'wps3b_backup_failed', array( __CLASS__, 'on_failure' ) );
	}

	/**
	 * Handle successful backup notification.
	 */
	public static function on_success( $manifest ) {
		$settings = WPS3B_Pro::get_settings();
		if ( ! $settings['notify_success'] ) {
			return;
		}

		$db_size    = isset( $manifest['backup_contents']['database']['size'] ) ? size_format( $manifest['backup_contents']['database']['size'] ) : 'N/A';
		$files_size = isset( $manifest['backup_contents']['files']['size'] ) ? size_format( $manifest['backup_contents']['files']['size'] ) : 'N/A';
		$site_name  = get_bloginfo( 'name' );

		// Email
		if ( ! empty( $settings['notification_email'] ) ) {
			$subject = sprintf( '[%s] Backup completed successfully', $site_name );
			$body    = sprintf(
				"Backup completed at %s\n\nSite: %s\nDatabase: %s\nFiles: %s\n",
				$manifest['timestamp'] ?? gmdate( 'Y-m-d H:i:s' ),
				get_site_url(),
				$db_size,
				$files_size
			);
			wp_mail( $settings['notification_email'], $subject, $body );
		}

		// Webhook
		if ( ! empty( $settings['webhook_url'] ) ) {
			self::send_webhook( $settings['webhook_url'], array(
				'event'    => 'backup_success',
				'site'     => get_site_url(),
				'site_name' => $site_name,
				'timestamp' => $manifest['timestamp'] ?? gmdate( 'Y-m-d\TH:i:s\Z' ),
				'database' => $db_size,
				'files'    => $files_size,
			));
		}
	}

	/**
	 * Handle failed backup notification.
	 */
	public static function on_failure( $error ) {
		$settings = WPS3B_Pro::get_settings();
		if ( ! $settings['notify_failure'] ) {
			return;
		}

		$error_msg = is_wp_error( $error ) ? $error->get_error_message() : (string) $error;
		$site_name = get_bloginfo( 'name' );

		// Email
		if ( ! empty( $settings['notification_email'] ) ) {
			$subject = sprintf( '[%s] ⚠ Backup FAILED', $site_name );
			$body    = sprintf(
				"Backup failed at %s\n\nSite: %s\nError: %s\n",
				gmdate( 'Y-m-d H:i:s' ),
				get_site_url(),
				$error_msg
			);
			wp_mail( $settings['notification_email'], $subject, $body );
		}

		// Webhook
		if ( ! empty( $settings['webhook_url'] ) ) {
			self::send_webhook( $settings['webhook_url'], array(
				'event'     => 'backup_failed',
				'site'      => get_site_url(),
				'site_name' => $site_name,
				'timestamp' => gmdate( 'Y-m-d\TH:i:s\Z' ),
				'error'     => $error_msg,
			));
		}
	}

	/**
	 * Send a webhook/Slack notification.
	 */
	private static function send_webhook( $url, $payload ) {
		// Detect Slack webhook and format accordingly
		if ( false !== strpos( $url, 'hooks.slack.com' ) ) {
			$text = 'backup_success' === $payload['event']
				? sprintf( '✅ *%s* — Backup completed. DB: %s, Files: %s', $payload['site_name'], $payload['database'] ?? 'N/A', $payload['files'] ?? 'N/A' )
				: sprintf( '❌ *%s* — Backup failed: %s', $payload['site_name'], $payload['error'] ?? 'Unknown' );
			$payload = array( 'text' => $text );
		}

		wp_remote_post( $url, array(
			'headers' => array( 'Content-Type' => 'application/json' ),
			'body'    => wp_json_encode( $payload ),
			'timeout' => 10,
		));
	}
}
