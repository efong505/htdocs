<?php
if (!defined('ABSPATH')) exit;

class NLDE_Cron {

    private static $instance = null;

    public static function instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_filter('cron_schedules', [$this, 'add_schedules']);
        add_action('nlde_process_drip_emails', [$this, 'process_pending_emails']);
    }

    public function add_schedules($schedules) {
        $schedules['nlde_every_5_minutes'] = [
            'interval' => 300,
            'display'  => __('Every 5 Minutes (DripForge)', 'nl-drip-engine'),
        ];
        return $schedules;
    }

    public static function schedule_events() {
        if (!wp_next_scheduled('nlde_process_drip_emails')) {
            wp_schedule_event(time(), 'nlde_every_5_minutes', 'nlde_process_drip_emails');
        }
    }

    public static function clear_events() {
        wp_clear_scheduled_hook('nlde_process_drip_emails');
    }

    public function process_pending_emails() {
        $pending = NLDE_Drip_Sequence::get_pending_sends();

        if (empty($pending)) return;

        $sent_count = 0;
        foreach ($pending as $item) {
            $result = NLDE_Email_Sender::send_sequence_email($item);
            if ($result) $sent_count++;

            // Rate limit: small delay between sends
            if ($sent_count % 10 === 0) {
                sleep(1);
            }
        }

        if ($sent_count > 0 && defined('WP_DEBUG') && WP_DEBUG) {
            error_log("DripForge: Sent {$sent_count} emails.");
        }
    }
}
