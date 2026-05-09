<?php
if (!defined('ABSPATH')) exit;

class NLDE_Analytics {

    public static function init() {
        add_action('init', [__CLASS__, 'handle_tracking']);
        add_action('init', [__CLASS__, 'handle_unsubscribe']);
    }

    public static function handle_tracking() {
        // Open tracking via pixel
        if (isset($_GET['nlde_track_open']) && !empty($_GET['hash'])) {
            self::record_open(sanitize_text_field($_GET['hash']));
            header('Content-Type: image/gif');
            echo base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');
            exit;
        }

        // Click tracking
        if (isset($_GET['nlde_track_click']) && !empty($_GET['hash']) && !empty($_GET['url'])) {
            self::record_click(sanitize_text_field($_GET['hash']));
            $url = esc_url_raw(urldecode($_GET['url']));
            if ($url) {
                wp_redirect($url);
                exit;
            }
        }
    }

    public static function handle_unsubscribe() {
        if (!isset($_GET['nlde_unsubscribe'])) return;

        $email = sanitize_email(urldecode($_GET['email'] ?? ''));
        $hash  = sanitize_text_field($_GET['hash'] ?? '');

        if (!$email || wp_hash($email) !== $hash) return;

        NLDE_Subscriber::unsubscribe($email);

        wp_die(
            '<div style="text-align:center;padding:50px;font-family:Arial,sans-serif;">' .
            '<h2>You\'ve been unsubscribed</h2>' .
            '<p>You will no longer receive emails from us.</p>' .
            '<p><a href="' . home_url() . '">Return to website</a></p></div>',
            'Unsubscribed',
            ['response' => 200]
        );
    }

    private static function record_open($hash) {
        global $wpdb;
        $wpdb->query($wpdb->prepare(
            "UPDATE {$wpdb->prefix}nlde_send_log SET opened_at = %s, status = 'opened' WHERE tracking_hash = %s AND opened_at IS NULL",
            current_time('mysql'), $hash
        ));
    }

    private static function record_click($hash) {
        global $wpdb;
        $wpdb->query($wpdb->prepare(
            "UPDATE {$wpdb->prefix}nlde_send_log SET clicked_at = %s, status = 'clicked' WHERE tracking_hash = %s AND clicked_at IS NULL",
            current_time('mysql'), $hash
        ));
    }

    public static function get_sequence_stats($sequence_id) {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare("
            SELECT se.position, se.subject, se.delay_days,
                COUNT(sl.id) as total_sent,
                SUM(CASE WHEN sl.opened_at IS NOT NULL THEN 1 ELSE 0 END) as total_opened,
                SUM(CASE WHEN sl.clicked_at IS NOT NULL THEN 1 ELSE 0 END) as total_clicked
            FROM {$wpdb->prefix}nlde_sequence_emails se
            LEFT JOIN {$wpdb->prefix}nlde_send_log sl ON sl.sequence_email_id = se.id
            WHERE se.sequence_id = %d
            GROUP BY se.id
            ORDER BY se.position ASC
        ", $sequence_id));
    }

    public static function get_overview_stats() {
        global $wpdb;
        return [
            'total_subscribers' => NLDE_Subscriber::count(),
            'active_subscribers' => NLDE_Subscriber::count('active'),
            'total_sent' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}nlde_send_log WHERE status != 'failed'"),
            'total_opened' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}nlde_send_log WHERE opened_at IS NOT NULL"),
            'total_clicked' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}nlde_send_log WHERE clicked_at IS NOT NULL"),
        ];
    }

    public static function get_send_log($args = []) {
        global $wpdb;

        $where = ['1=1'];
        $params = [];

        if (!empty($args['sequence_id'])) {
            $where[] = 'se.sequence_id = %d';
            $params[] = (int) $args['sequence_id'];
        }
        if (isset($args['email_position'])) {
            $where[] = 'se.position = %d';
            $params[] = (int) $args['email_position'];
        }
        if (!empty($args['filter'])) {
            switch ($args['filter']) {
                case 'opened':
                    $where[] = 'sl.opened_at IS NOT NULL';
                    break;
                case 'clicked':
                    $where[] = 'sl.clicked_at IS NOT NULL';
                    break;
                case 'sent':
                    $where[] = "sl.status != 'failed'";
                    break;
                case 'failed':
                    $where[] = "sl.status = 'failed'";
                    break;
            }
        }
        if (!empty($args['subscriber_id'])) {
            $where[] = 'sl.subscriber_id = %d';
            $params[] = (int) $args['subscriber_id'];
        }

        $where_sql = implode(' AND ', $where);
        $query = "SELECT sl.*, s.email, s.first_name, s.last_name,
                         se.subject, se.position, se.delay_days,
                         seq.name as sequence_name
                  FROM {$wpdb->prefix}nlde_send_log sl
                  JOIN {$wpdb->prefix}nlde_subscribers s ON sl.subscriber_id = s.id
                  JOIN {$wpdb->prefix}nlde_sequence_emails se ON sl.sequence_email_id = se.id
                  JOIN {$wpdb->prefix}nlde_sequences seq ON se.sequence_id = seq.id
                  WHERE {$where_sql}
                  ORDER BY sl.sent_at DESC
                  LIMIT 100";

        if (!empty($params)) {
            return $wpdb->get_results($wpdb->prepare($query, ...$params));
        }
        return $wpdb->get_results($query);
    }
}

NLDE_Analytics::init();
