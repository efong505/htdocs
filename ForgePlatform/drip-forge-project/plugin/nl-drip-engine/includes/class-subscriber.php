<?php
if (!defined('ABSPATH')) exit;

class NLDE_Subscriber {

    public static function create($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'nlde_subscribers';

        $existing = self::get_by_email($data['email']);
        if ($existing) {
            if ($existing->status === 'unsubscribed') {
                $wpdb->update($table, [
                    'status' => 'active',
                    'first_name' => sanitize_text_field($data['first_name'] ?? $existing->first_name),
                    'unsubscribed_at' => null,
                    'subscribed_at' => current_time('mysql'),
                ], ['id' => $existing->id]);
                return $existing->id;
            }
            return $existing->id;
        }

        $wpdb->insert($table, [
            'email'      => sanitize_email($data['email']),
            'first_name' => sanitize_text_field($data['first_name'] ?? ''),
            'last_name'  => sanitize_text_field($data['last_name'] ?? ''),
            'ip_address' => self::get_ip(),
            'status'     => 'active',
        ]);

        return $wpdb->insert_id;
    }

    public static function get($id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}nlde_subscribers WHERE id = %d", $id
        ));
    }

    public static function get_by_email($email) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}nlde_subscribers WHERE email = %s", $email
        ));
    }

    public static function unsubscribe($email) {
        global $wpdb;
        return $wpdb->update(
            $wpdb->prefix . 'nlde_subscribers',
            ['status' => 'unsubscribed', 'unsubscribed_at' => current_time('mysql')],
            ['email' => sanitize_email($email)]
        );
    }

    public static function delete($id) {
        global $wpdb;
        $wpdb->delete($wpdb->prefix . 'nlde_subscriber_sequences', ['subscriber_id' => $id]);
        $wpdb->delete($wpdb->prefix . 'nlde_send_log', ['subscriber_id' => $id]);
        return $wpdb->delete($wpdb->prefix . 'nlde_subscribers', ['id' => $id]);
    }

    public static function get_all($args = []) {
        global $wpdb;
        $defaults = ['status' => '', 'per_page' => 20, 'page' => 1, 'search' => ''];
        $args = wp_parse_args($args, $defaults);

        $where = "WHERE 1=1";
        $params = [];

        if ($args['status']) {
            $where .= " AND status = %s";
            $params[] = $args['status'];
        }
        if ($args['search']) {
            $where .= " AND (email LIKE %s OR first_name LIKE %s OR last_name LIKE %s)";
            $search = '%' . $wpdb->esc_like($args['search']) . '%';
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
        }

        $offset = ($args['page'] - 1) * $args['per_page'];
        $limit = "LIMIT %d OFFSET %d";
        $params[] = $args['per_page'];
        $params[] = $offset;

        $sql = "SELECT * FROM {$wpdb->prefix}nlde_subscribers $where ORDER BY subscribed_at DESC $limit";
        return $wpdb->get_results($params ? $wpdb->prepare($sql, $params) : $sql);
    }

    public static function count($status = '') {
        global $wpdb;
        $where = $status ? $wpdb->prepare("WHERE status = %s", $status) : '';
        return (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}nlde_subscribers $where");
    }

    public static function export_csv() {
        global $wpdb;
        $subscribers = $wpdb->get_results("SELECT email, first_name, last_name, status, subscribed_at FROM {$wpdb->prefix}nlde_subscribers ORDER BY subscribed_at DESC");

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="nlde-subscribers-' . date('Y-m-d') . '.csv"');

        $output = fopen('php://output', 'w');
        fputcsv($output, ['Email', 'First Name', 'Last Name', 'Status', 'Subscribed']);
        foreach ($subscribers as $sub) {
            fputcsv($output, [$sub->email, $sub->first_name, $sub->last_name, $sub->status, $sub->subscribed_at]);
        }
        fclose($output);
        exit;
    }

    private static function get_ip() {
        $keys = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
        foreach ($keys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = explode(',', $_SERVER[$key]);
                return sanitize_text_field(trim($ip[0]));
            }
        }
        return '';
    }
}
