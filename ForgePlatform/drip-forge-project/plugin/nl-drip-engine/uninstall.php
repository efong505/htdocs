<?php
/**
 * Uninstall handler — removes all plugin data when deleted.
 *
 * @package NL_Drip_Engine
 */

if (!defined('WP_UNINSTALL_PLUGIN')) exit;

global $wpdb;

// Drop custom tables (order matters due to foreign key references)
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}nlde_send_log");
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}nlde_subscriber_sequences");
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}nlde_sequence_emails");
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}nlde_sequences");
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}nlde_subscribers");

// Delete options
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE 'nlde_%'");

// Delete transients
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_nlde_%' OR option_name LIKE '_transient_timeout_nlde_%'");

// Clear scheduled cron
wp_clear_scheduled_hook('nlde_process_drip_emails');
