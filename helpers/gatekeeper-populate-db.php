<?php
// Function to populate the invited_users table with existing core WP members
function gatekeeper_populate_invited_users() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'gatekeeper_invited_users';

    // Get existing users
    $existing_users = get_users();

    // PopulateDatabase with data
    $populate_data = array();

    foreach ($existing_users as $user) {
        $invite_key = gatekeeper_generate_invite_key();
        $populate_data[] = array(
            'invite_key' => sanitize_text_field($invite_key),
            'invite_status' => 'Active', // You may set this to 'Pending' initially
            'inviter_id' => 1, // Assuming the inviter is always user ID 1
            'invitee_id' => $user->ID,
            'user_role' => gatekeeper_get_default_user_role(), // Customize the default role as needed
            'usage_limit' => 0,
        );
    }

    foreach ($populate_data as $data) {
        $wpdb->insert($table_name, $data);
    }
}

// Function to generate and populate invite_keys by a logged-in admin
function gatekeeper_generate_and_populate_invite_keys($count) {
    if (is_user_logged_in()) {
        $current_user = wp_get_current_user();
        if (current_user_can('administrator')) {
            global $wpdb;
            $table_name = $wpdb->prefix . 'gatekeeper_invite_keys';

            $inviter_id = $current_user->ID;

            for ($i = 0; $i < $count; $i++) {
                $invite_key = gatekeeper_generate_invite_key();

                $data_inviter = array(
                    'invite_key' => sanitize_text_field($invite_key),
                    'inviter_id' => intval($inviter_id),
                    'invitee_id' => 0, // Default value set to NULL unless set to invitee ID
                    'inviter_role' => 'GateKeeper', // Customize the inviter's role as needed
                    'invitee_role' => '', // Initially empty, to be filled when the key is used or pre-assigned as the username for the invitee
                    'invite_status' => 'Available',
                    'key_exp_acc' => null,
                    'key_exp_date' => null,
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
                );

                $wpdb->insert($table_name, $data_inviter, $format);
            }
        }
    }
}

// Modify the gatekeeper_activate_plugin function to call the population functions
function gatekeeper_activate_plugin() {
    // Check if tables exist; create them if not
    gatekeeper_create_tables();

    // Populate invited_users with existing WP members
    gatekeeper_populate_invited_users();

    // Generate and populate invite_keys if an admin is logged in
    gatekeeper_generate_and_populate_invite_keys(20);
}

// Call the population functions when the plugin is activated
register_activation_hook(__FILE__, 'gatekeeper_activate_plugin');
