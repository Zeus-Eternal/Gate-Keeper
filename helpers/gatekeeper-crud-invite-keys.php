<?php
/**
 * Helper functions for managing invite keys in GateKeeper plugin.
 */

// Include WordPress database access
global $wpdb;

// Function to generate a random invite key
function gatekeeper_generate_invite_key() {
    $invite_key = wp_generate_password(12, false);
    return $invite_key;
}

// Function to create an invite key and store it in the database
function gatekeeper_create_invite_key($user_role, $inviter_id) {
    global $wpdb;

    $invite_key = gatekeeper_generate_invite_key(); // Generate an invite key

    $table_name = $wpdb->prefix . 'gatekeeper_invite_keys';

    $data = array(
        'invite_key' => $invite_key,
        'user_id' => 0, // You might want to update this to the actual user ID if needed
        'user_role' => $user_role,
        'invite_status' => 'Active',
        'usage_limit' => 0, // Initialize usage limit to 0
        'inviter_id' => $inviter_id, // Set the inviter ID
        'key_exp_acc' => null, // Initialize key access expiration
        'key_exp_date' => null, // Initialize key expiration
    );

    $format = array(
        '%s',
        '%d',
        '%s',
        '%s',
        '%d',
        '%d',
        '%s',
        '%s',
    );

    $wpdb->insert($table_name, $data, $format);

    // Store the user role in the invited users table
    gatekeeper_store_user_role($invite_key, $user_role);
}


// Function to store the user role associated with an invite key
function gatekeeper_store_user_role($invite_key, $user_role) {
    global $wpdb;

    $table_name = $wpdb->prefix . 'gatekeeper_invited_users';

    $data = array(
        'invite_key' => $invite_key,
        'user_role' => $user_role,
    );

    $format = array(
        '%s',
        '%s',
    );

    $wpdb->insert($table_name, $data, $format);
}

// Function to insert an invited user into the database
function gatekeeper_insert_invited_user($invitation_details, $user_id) {
    global $wpdb;

    $table_name = $wpdb->prefix . 'gatekeeper_invited_users';

    $data = array(
        'invite_key' => $invitation_details->invite_key,
        'invite_status' => 'Accepted', // Mark the invitation as accepted
        'inviter_id' => $invitation_details->inviter_id,
        'invitee_id' => $user_id,
        'user_role' => $invitation_details->user_role,
        'usage_limit' => 0, // Initialize usage limit to 0
        'key_exp_acc' => null, // Initialize key access expiration
        'created_at' => current_time('mysql'),
    );

    $format = array(
        '%s',
        '%s',
        '%d',
        '%d',
        '%s',
        '%d',
        '%s',
        '%s',
    );

    return $wpdb->insert($table_name, $data, $format);
}

// Function to validate the invite key and get invitation details
function gatekeeper_validate_invite_key($invite_key) {
    global $wpdb;
    
    // Check if the invite key exists and is active
    $invitation_details = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}gatekeeper_invite_keys WHERE invite_key = %s AND invite_status = 'Active'",
            $invite_key
        )
    );
    
    if ($invitation_details) {
        // Check if the usage limit has been reached
        if ($invitation_details->usage_limit > 0) {
            $usage_count = gatekeeper_get_invite_key_usage_count($invite_key);
            
            if ($usage_count >= $invitation_details->usage_limit) {
                // Invite key has reached its usage limit
                return false;
            }
        }
        
        // Check if the key access expiration date is valid
        if ($invitation_details->key_exp_acc && $invitation_details->key_exp_date) {
            $current_date = current_time('mysql');
            
            if ($current_date < $invitation_details->key_exp_acc || $current_date > $invitation_details->key_exp_date) {
                // Invite key has expired
                return false;
            }
        }
        
        return $invitation_details;
    }
    
    return false;
}

// Function to get the number of times an invite key has been used
function gatekeeper_get_invite_key_usage_count($invite_key) {
    global $wpdb;

    return (int)$wpdb->get_var(
        $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}gatekeeper_invited_users WHERE invite_key = %s",
            $invite_key
        )
    );
}

// Function to get the inviter ID based on the invite key
function gatekeeper_get_inviter_id($invite_key) {
    global $wpdb;

    return (int)$wpdb->get_var(
        $wpdb->prepare(
            "SELECT inviter_id FROM {$wpdb->prefix}gatekeeper_invite_keys WHERE invite_key = %s",
            $invite_key
        )
    );
}

// Function to track user relationships (inviter to invitee)
function gatekeeper_track_user_relationship($inviter_id, $invitee_id, $invite_key) {
    global $wpdb;

    $wpdb->insert(
        $wpdb->prefix . 'gatekeeper_invited_users',
        array(
            'invite_key' => $invite_key,
            'invite_status' => 'Accepted', // Mark the invitation as accepted
            'inviter_id' => $inviter_id,
            'invitee_id' => $invitee_id,
            'user_role' => 'Subscriber', // Set the default role for invitees
            'usage_limit' => 0, // Initialize usage limit to 0
            'key_exp_acc' => null, // Initialize key access expiration
            'created_at' => current_time('mysql'),
        )
    );
}
?>
