<?php
// Function to populate the gatekeeper_invited_users table with existing users (excluding the inviter) and auto-invite administrators as GateKeepers
function gatekeeper_populate_and_auto_invite_users() {
    global $wpdb;
    $table_name_invited_users = $wpdb->prefix . 'gatekeeper_invited_users';
    $table_name_invite_keys = $wpdb->prefix . 'gatekeeper_invite_keys';

    // Get existing users (excluding the inviter with ID 1)
    $existing_users = get_users(array(
        'exclude' => array(1), // Exclude the inviter with ID 1
    ));

    foreach ($existing_users as $user) {
        // Generate an invite key
        $invite_key = gatekeeper_generate_invite_key();

        // Define the data for the invited user
        $invited_user_data = array(
            'invite_key' => sanitize_text_field($invite_key),
            'invite_status' => 'Active', // You may set this to 'Pending' initially
            'inviter_id' => 1, // Assuming the inviter is always user ID 1
            'invitee_id' => $user->ID,
            'user_role' => gatekeeper_get_default_user_role(), // Customize the default role as needed
            'usage_limit' => 0,
        );

        // Insert the invited user data into the gatekeeper_invited_users table
        $wpdb->insert($table_name_invited_users, $invited_user_data);

        // Create the invite key and store it in the database
        gatekeeper_create_invite_key($invite_key, 1, $user->ID, 'GateKeeper', 'GateKeeper', ''); // Pass an empty string for invitee's email

        // Remove the invite key from the gatekeeper_invite_keys table
        $wpdb->delete($table_name_invite_keys, array('invite_key' => $invite_key));
    }

    // Auto-invite all WordPress administrators as GateKeeper (Super User)
    $admins = get_users(array('role' => 'administrator'));

    foreach ($admins as $admin) {
        // Generate an invite key
        $invite_key = gatekeeper_generate_invite_key();

        // Define the data for auto-invited administrators
        $auto_invited_admin_data = array(
            'invite_key' => sanitize_text_field($invite_key),
            'invite_status' => 'Active', // You may set this to 'Pending' initially
            'inviter_id' => 1, // Assuming the inviter is always user ID 1
            'invitee_id' => $admin->ID,
            'user_role' => 'GateKeeper', // Set the role as GateKeeper for administrators
            'usage_limit' => 0,
        );

        // Insert the auto-invited administrator data into the gatekeeper_invited_users table
        $wpdb->insert($table_name_invited_users, $auto_invited_admin_data);

        // Create the invite key and store it in the database
        gatekeeper_create_invite_key($invite_key, 1, $admin->ID, 'GateKeeper', 'GateKeeper', ''); // Pass an empty string for invitee's email

        // Remove the invite key from the gatekeeper_invite_keys table
        $wpdb->delete($table_name_invite_keys, array('invite_key' => $invite_key));
    }
}
