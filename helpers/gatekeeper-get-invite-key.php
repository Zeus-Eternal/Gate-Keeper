<?php

// Function to create an invite key
function gatekeeper_create_invite_key($invite_key, $invitee_id, $inviter_id, $inviter_role, $invitee_role, $invitee_email, $unlimited = false) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'gatekeeper_invite_keys';

    $data = array(
        'invite_key' => sanitize_text_field($invite_key),
        'invitee_id' => intval($invitee_id),
        'inviter_id' => intval($inviter_id),
        'inviter_role' => sanitize_text_field($inviter_role),
        'invitee_role' => sanitize_text_field($invitee_role),
        'invite_status' => 'Active',
        'key_exp_acc' => null,
        'key_exp_date' => ($unlimited) ? null : date('Y-m-d H:i:s', strtotime('+30 days')), // Set expiration date
        'share_limit' => ($unlimited) ? 0 : 1,   // Set the share limit based on invite key
        'usage_limit' => ($unlimited) ? 0 : 1,   // Set the usage limit based on invite key
        'invitee_email' => sanitize_email($invitee_email),  // Store the invitee's email
        'created_at' => current_time('mysql'),
    );

    $format = array(
        '%s',
        '%d',
        '%d',
        '%s',
        '%s',
        '%s',
        '%s',
        '%s',
        '%d', // Format for share_limit
        '%d', // Format for usage_limit
        '%s', // Format for invitee_email
    );

    $wpdb->insert($table_name, $data, $format);
}