<?php
/**
 * CRUD Logic for Invite Keys in the GateKeeper Plugin
 */

// Include WordPress core
require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

// Function to create a new invite key
function gatekeeper_create_invite_key($user_id, $user_role, $usage_limit = 0) {
    global $wpdb;

    // Generate a new invite key
    $invite_key = gatekeeper_generate_invite_key();

    // Insert the invite key into the database
    $insert_result = $wpdb->insert(
        $wpdb->prefix . 'gatekeeper_invite_keys',
        array(
            'invite_key' => $invite_key,
            'user_id' => $user_id,
            'user_role' => $user_role,
            'invite_status' => 'Active',
            'usage_limit' => $usage_limit,
            'inviter_id' => $user_id,
            'key_exp_acc' => null,
            'key_exp_date' => null,
        )
    );

    if ($insert_result !== false) {
        return $invite_key;
    } else {
        return false;
    }
}

// Function to read invite key details
function gatekeeper_read_invite_key($invite_key) {
    global $wpdb;

    // Retrieve invite key details from the database
    $invitation_details = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}gatekeeper_invite_keys WHERE invite_key = %s",
            $invite_key
        )
    );

    return $invitation_details;
}

// Function to update invite key details
function gatekeeper_update_invite_key($invite_key, $update_data) {
    global $wpdb;

    // Update invite key information in the database
    $update_result = $wpdb->update(
        $wpdb->prefix . 'gatekeeper_invite_keys',
        $update_data,
        array('invite_key' => $invite_key)
    );

    return $update_result !== false;
}

// Function to delete an invite key
function gatekeeper_delete_invite_key($invite_key) {
    global $wpdb;

    // Delete the invite key from the database
    $delete_result = $wpdb->delete(
        $wpdb->prefix . 'gatekeeper_invite_keys',
        array('invite_key' => $invite_key)
    );

    return $delete_result !== false;
}

// Function to list all invite keys for a specific user
function gatekeeper_list_invite_keys($user_id) {
    global $wpdb;

    // Retrieve all invite keys associated with the user
    $invite_keys = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}gatekeeper_invite_keys WHERE user_id = %d",
            $user_id
        )
    );

    return $invite_keys;
}

// Function to generate an invite key
function gatekeeper_generate_invite_key($length = 5) {
    $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    $invite_key = '';
    for ($i = 0; $i < $length; $i++) {
        $invite_key .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $invite_key;
}
