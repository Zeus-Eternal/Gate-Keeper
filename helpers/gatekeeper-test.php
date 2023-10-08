<?php
// Include GateKeeper functions
include_once(plugin_dir_path(__FILE__) . 'gatekeeper-key-generator.php'); // Adjust the path as needed

// Function to create an invite key
function create_invite_key() {
    global $wpdb;

    // Determine the inviter's user ID (Sanitize)
    $inviter = absint(get_admin_user_id());

    // Determine the invitee's user IDs (Sanitize)
    $invitees = array_map('absint', get_invitee_user_ids());

    // Determine the status (Validate)
    $status = determine_invite_status();

    // Generate a unique invite key
    $invite_key = sanitize_text_field(gatekeeper_generate_invite_key());

    // Insert the invite key into the database
    $wpdb->insert(
        $wpdb->prefix . 'gatekeeper_invite_keys',
        array(
            'invite_key' => $invite_key,
            'role' => 'subscriber', // Replace with the desired role
            'inviter' => $inviter,
            'usage_limit' => 1,
            'is_expiry' => 1,
            'expiry_date' => date('Y-m-d H:i:s', strtotime('+90 days')),
            'status' => $status,
            'accepted' => 0,
            'accepted_at' => 0,
        )
    );

    // Insert invitee information into a separate table
    foreach ($invitees as $invitee) {
        $wpdb->insert(
            $wpdb->prefix . 'gatekeeper_invitee_info',
            array(
                'invite_key' => $invite_key,
                'invitee_id' => $invitee,
            )
        );
    }

    return $invite_key;
}

// Function to get the inviter's user ID (admin user)
function get_admin_user_id() {
    $admins = get_users(array('role' => 'administrator'));
    if (!empty($admins)) {
        return $admins[0]->ID; // Use the first admin user found
    } else {
        return 1; // If no admin user found, return a default user ID
    }
}

// Function to get the invitee's user IDs (all existing members)
function get_invitee_user_ids() {
    global $wpdb;
    $user_ids = $wpdb->get_col("SELECT ID FROM $wpdb->users");
    return $user_ids;
}

// Function to determine the status (custom logic)
function determine_invite_status() {
    // Implement your custom logic here to determine the status
    // You can check user actions or other conditions
    // If none of the conditions apply, consider it 'active'
    return 'active';
}

// Function to read an invite key
function read_invite_key($invite_key) {
    global $wpdb;

    // Sanitize input
    $invite_key = sanitize_text_field($invite_key);

    // Retrieve the invite key details from the database
    $invite_key_details = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}gatekeeper_invite_keys WHERE invite_key = %s",
            $invite_key
        )
    );

    return $invite_key_details;
}

// Function to update an invite key
function update_invite_key($invite_key, $status) {
    global $wpdb;

    // Sanitize input
    $invite_key = sanitize_text_field($invite_key);
    $status = sanitize_text_field($status);

    // Update the status of the invite key in the database
    $wpdb->update(
        $wpdb->prefix . 'gatekeeper_invite_keys',
        array('status' => $status),
        array('invite_key' => $invite_key)
    );

    // Check if the update was successful
    return $wpdb->rows_affected > 0;
}

// Function to delete an invite key
function delete_invite_key($invite_key) {
    global $wpdb;

    // Sanitize input
    $invite_key = sanitize_text_field($invite_key);

    // Delete the invite key from the database
    $wpdb->delete(
        $wpdb->prefix . 'gatekeeper_invite_keys',
        array('invite_key' => $invite_key)
    );

    // Check if the deletion was successful
    return $wpdb->rows_affected > 0;
}

// Test the create, read, update, and delete operations
$created_invite_key = create_invite_key();
echo "Created Invite Key: $created_invite_key<br>";

$read_invite_key = read_invite_key($created_invite_key);
if ($read_invite_key) {
    echo "Read Invite Key: {$read_invite_key->invite_key}, Status: {$read_invite_key->status}<br>";

    $updated = update_invite_key($read_invite_key->invite_key, 'expired');
    if ($updated) {
        echo "Updated Invite Key Status to 'expired'<br>";
    } else {
        echo "Failed to Update Invite Key Status<br>";
    }

    $deleted = delete_invite_key($read_invite_key->invite_key);
    if ($deleted) {
        echo "Deleted Invite Key<br>";
    } else {
        echo "Failed to Delete Invite Key<br>";
    }
} else {
    echo "Failed to Read Invite Key<br>";
}