<?php
if (!defined('ABSPATH')) exit;

class NLDE_Drip_Sequence {

    public static function create($data) {
        global $wpdb;
        $wpdb->insert($wpdb->prefix . 'nlde_sequences', [
            'name'        => sanitize_text_field($data['name']),
            'slug'        => sanitize_title($data['name']),
            'description' => sanitize_textarea_field($data['description'] ?? ''),
            'status'      => $data['status'] ?? 'draft',
        ]);
        return $wpdb->insert_id;
    }

    public static function update($id, $data) {
        global $wpdb;
        $update = [];
        if (isset($data['name'])) {
            $update['name'] = sanitize_text_field($data['name']);
            $update['slug'] = sanitize_title($data['name']);
        }
        if (isset($data['description'])) $update['description'] = sanitize_textarea_field($data['description']);
        if (isset($data['status'])) $update['status'] = $data['status'];

        return $wpdb->update($wpdb->prefix . 'nlde_sequences', $update, ['id' => $id]);
    }

    public static function get($id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}nlde_sequences WHERE id = %d", $id
        ));
    }

    public static function get_by_slug($slug) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}nlde_sequences WHERE slug = %s", $slug
        ));
    }

    public static function get_all() {
        global $wpdb;
        return $wpdb->get_results("SELECT * FROM {$wpdb->prefix}nlde_sequences ORDER BY created_at DESC");
    }

    public static function delete($id) {
        global $wpdb;
        $wpdb->delete($wpdb->prefix . 'nlde_sequence_emails', ['sequence_id' => $id]);
        $wpdb->delete($wpdb->prefix . 'nlde_subscriber_sequences', ['sequence_id' => $id]);
        return $wpdb->delete($wpdb->prefix . 'nlde_sequences', ['id' => $id]);
    }

    // Sequence Emails
    public static function add_email($data) {
        global $wpdb;
        $wpdb->insert($wpdb->prefix . 'nlde_sequence_emails', [
            'sequence_id' => (int) $data['sequence_id'],
            'position'    => (int) $data['position'],
            'subject'     => sanitize_text_field(wp_unslash($data['subject'])),
            'body'        => wp_kses_post(wp_unslash($data['body'])),
            'delay_days'  => (int) $data['delay_days'],
            'status'      => $data['status'] ?? 'active',
        ]);
        return $wpdb->insert_id;
    }

    public static function update_email($id, $data) {
        global $wpdb;
        $update = [];
        if (isset($data['subject'])) $update['subject'] = sanitize_text_field(wp_unslash($data['subject']));
        if (isset($data['body'])) $update['body'] = wp_kses_post(wp_unslash($data['body']));
        if (isset($data['delay_days'])) $update['delay_days'] = (int) $data['delay_days'];
        if (isset($data['position'])) $update['position'] = (int) $data['position'];
        if (isset($data['status'])) $update['status'] = $data['status'];

        return $wpdb->update($wpdb->prefix . 'nlde_sequence_emails', $update, ['id' => $id]);
    }

    public static function get_email($id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}nlde_sequence_emails WHERE id = %d", $id
        ));
    }

    public static function get_emails($sequence_id) {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}nlde_sequence_emails WHERE sequence_id = %d ORDER BY position ASC",
            $sequence_id
        ));
    }

    public static function delete_email($id) {
        global $wpdb;
        return $wpdb->delete($wpdb->prefix . 'nlde_sequence_emails', ['id' => $id]);
    }

    // Enrollment
    public static function enroll_subscriber($subscriber_id, $sequence_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'nlde_subscriber_sequences';

        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE subscriber_id = %d AND sequence_id = %d",
            $subscriber_id, $sequence_id
        ));

        if ($existing) {
            if ($existing->status === 'completed' || $existing->status === 'paused') {
                $wpdb->update($table, [
                    'status' => 'active',
                    'current_position' => 0,
                    'enrolled_at' => current_time('mysql'),
                ], ['id' => $existing->id]);
            }
            return $existing->id;
        }

        $wpdb->insert($table, [
            'subscriber_id'    => $subscriber_id,
            'sequence_id'      => $sequence_id,
            'current_position' => 0,
            'status'           => 'active',
        ]);
        return $wpdb->insert_id;
    }

    public static function get_pending_sends() {
        global $wpdb;
        $now = current_time('mysql');

        return $wpdb->get_results("
            SELECT ss.*, se.id as email_id, se.subject, se.body, se.delay_days,
                   s.email, s.first_name, s.last_name, s.status as sub_status,
                   seq.status as seq_status
            FROM {$wpdb->prefix}nlde_subscriber_sequences ss
            JOIN {$wpdb->prefix}nlde_sequences seq ON ss.sequence_id = seq.id
            JOIN {$wpdb->prefix}nlde_sequence_emails se ON se.sequence_id = ss.sequence_id AND se.position = ss.current_position
            JOIN {$wpdb->prefix}nlde_subscribers s ON ss.subscriber_id = s.id
            LEFT JOIN {$wpdb->prefix}nlde_send_log sl ON sl.subscriber_id = ss.subscriber_id AND sl.sequence_email_id = se.id
            WHERE ss.status = 'active'
            AND seq.status = 'active'
            AND se.status = 'active'
            AND s.status = 'active'
            AND sl.id IS NULL
            AND DATE_ADD(ss.enrolled_at, INTERVAL se.delay_days DAY) <= '$now'
            ORDER BY ss.enrolled_at ASC
            LIMIT 50
        ");
    }

    public static function advance_subscriber($subscriber_id, $sequence_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'nlde_subscriber_sequences';

        $enrollment = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE subscriber_id = %d AND sequence_id = %d",
            $subscriber_id, $sequence_id
        ));

        if (!$enrollment) return;

        $next_position = $enrollment->current_position + 1;
        $next_email = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}nlde_sequence_emails WHERE sequence_id = %d AND position = %d",
            $sequence_id, $next_position
        ));

        if ($next_email) {
            $wpdb->update($table, ['current_position' => $next_position], ['id' => $enrollment->id]);
        } else {
            $wpdb->update($table, ['status' => 'completed'], ['id' => $enrollment->id]);
        }
    }
}
