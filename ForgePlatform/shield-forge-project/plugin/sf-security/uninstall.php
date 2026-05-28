<?php
if (!defined('WP_UNINSTALL_PLUGIN')) exit;

global $wpdb;

// Drop tables
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}sfs_log");
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}sfs_blocklist");
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}sfs_login_attempts");
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}sfs_lockouts");

// Delete options
$options = $wpdb->get_col("SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE 'sfs_%'");
foreach ($options as $option) {
    delete_option($option);
}

// Delete transients
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_sfs_%' OR option_name LIKE '_transient_timeout_sfs_%'");

// Remove mu-plugin if exists
$mu_file = WPMU_PLUGIN_DIR . '/sfs-early-block.php';
if (file_exists($mu_file)) {
    @unlink($mu_file);
}
