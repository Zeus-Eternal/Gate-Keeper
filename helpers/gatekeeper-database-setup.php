<?php

// Function to create the plugin's database tables
function gatekeeper_create_tables() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    $table_name_keys = $wpdb->prefix . 'gatekeeper_invite_keys';
    $table_name_users = $wpdb->prefix . 'gatekeeper_invited_users';
    $table_name_access_logs = $wpdb->prefix . 'gatekeeper_access_logs';
    $table_name_options = $wpdb->prefix . 'gatekeeper_options';

    // SQL statement to create the invite_keys table
    $sql_keys = "CREATE TABLE $table_name_keys (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        invite_key varchar(255) NOT NULL,
        inviter_id mediumint(9) NOT NULL,
        invitee_id mediumint(9) NOT NULL,
        inviter_role varchar(255) NOT NULL,
        invitee_role varchar(255) NOT NULL,
        invite_status varchar(255) NOT NULL,
        key_exp_acc datetime DEFAULT '0000-00-00 00:00:00',
        key_exp_date datetime DEFAULT '0000-00-00 00:00:00',
        share_limit int(11) NOT NULL DEFAULT 0,
        usage_limit int(11) NOT NULL DEFAULT 0,
        invitee_email varchar(255) DEFAULT NULL,
        created_at datetime DEFAULT '0000-00-00 00:00:00',
        PRIMARY KEY (id),
        UNIQUE (invite_key)
    ) $charset_collate;";

    // SQL statement to create the invited_users table
    $sql_users = "CREATE TABLE $table_name_users (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        invite_key varchar(255) NOT NULL,
        invite_status varchar(255) NOT NULL,
        inviter_id mediumint(9) NOT NULL,
        user_role varchar(255) NOT NULL,
        usage_limit int(11) NOT NULL DEFAULT 0,
        PRIMARY KEY (id)
    ) $charset_collate;";

    // SQL statement to create the access_logs table
    $sql_access_logs = "CREATE TABLE $table_name_access_logs (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        user_id mediumint(9) NOT NULL,
        invite_key varchar(255) NOT NULL,
        access_time datetime DEFAULT '0000-00-00 00:00:00',
        PRIMARY KEY (id)
    ) $charset_collate;";

    // SQL statement to create the options table
    $sql_options = "CREATE TABLE $table_name_options (
        option_name varchar(255) NOT NULL,
        option_value longtext NOT NULL,
        PRIMARY KEY (option_name)
    ) $charset_collate;";

    // Create the tables
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql_keys);
    dbDelta($sql_users);
    dbDelta($sql_access_logs);
    dbDelta($sql_options);

    // Store plugin options
    add_option('gatekeeper_custom_redirect_enabled', false);
}

// Hook to create the plugin's database tables during plugin activation
register_activation_hook(__FILE__, 'gatekeeper_create_tables');

// Function to delete the plugin's database tables
function gatekeeper_delete_tables() {
    global $wpdb;
    $table_name_keys = $wpdb->prefix . 'gatekeeper_invite_keys';
    $table_name_users = $wpdb->prefix . 'gatekeeper_invited_users';
    $table_name_access_logs = $wpdb->prefix . 'gatekeeper_access_logs';
    $table_name_options = $wpdb->prefix . 'gatekeeper_options';

    // Delete the tables
    $wpdb->query("DROP TABLE IF EXISTS $table_name_keys");
    $wpdb->query("DROP TABLE IF EXISTS $table_name_users");
    $wpdb->query("DROP TABLE IF EXISTS $table_name_access_logs");
    $wpdb->query("DROP TABLE IF EXISTS $table_name_options");

    // Delete plugin options
    delete_option('gatekeeper_custom_redirect_enabled');
}

// Hook to delete the plugin's database tables during plugin deactivation
register_deactivation_hook(__FILE__, 'gatekeeper_delete_tables');

// Populate our Tables: 1. Populate gatekeeper_invited_users | 2. gatekeeper_invite_keys

// 1. Populate gatekeeper_invited_users with all current WP core users
function gatekeeper_populate_gatekeeper_invited_users_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'gatekeeper_invited_users';

    // Get all existing users
    $users = get_users();

    // Loop through users and populate the gatekeeper_invited_users table
    foreach ($users as $user) {
        $invite_key = gatekeeper_generate_invite_key();
        $user_id = $user->ID;
        $inviter_id = 1; // Admin user ID (you can change this to the actual admin user ID)
        $user_role = $user->roles[0]; // Get the user's role

        // Create a record in the gatekeeper_invited_users table for the user
        $data = array(
            'invite_key' => sanitize_text_field($invite_key),
            'invite_status' => 'Active',
            'inviter_id' => intval($inviter_id),
            'user_role' => sanitize_text_field($user_role),
            'usage_limit' => 0, // Usage limit is 0 for core users
        );

        $format = array(
            '%s',
            '%s',
            '%d',
            '%s',
            '%d',
        );

        $wpdb->insert($table_name, $data, $format);
    }
}

// Hook to populate gatekeeper_invited_users table with existing WordPress users on plugin activation
register_activation_hook(__FILE__, 'gatekeeper_populate_gatekeeper_invited_users_table');

// 2. Populate gatekeeper_invite_keys with 20 keys (without prefix) from admin (10 unlimited usage keys with "spec_" prefix)
function gatekeeper_populate_gatekeeper_invite_keys_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'gatekeeper_invite_keys';

    // Generate 10 unlimited invite keys with "spec_" prefix
    for ($i = 0; $i < 10; $i++) {
        $invite_key = 'spec_' . gatekeeper_generate_invite_key(); // Set to 'UNLIMITED' for unlimited usage
        $invitee_id = 0; // No invitee for unlimited keys
        $inviter_id = 1; // Admin user ID (you can change this to the actual admin user ID)
        $inviter_role = 'administrator'; // Admin role
        $invitee_role = 'gatekeeper'; // Role for invited users
        $invitee_email = ''; // No invitee email for unlimited keys

        // Create a record in the gatekeeper_invite_keys table for the key
        gatekeeper_create_invite_key($invite_key, $invitee_id, $inviter_id, $inviter_role, $invitee_role, $invitee_email, true); // Pass true to indicate unlimited usage
    }

    // Generate 10 limited invite keys without prefix
    for ($i = 0; $i < 10; $i++) {
        $invite_key = gatekeeper_generate_invite_key(); // Generate a random invite key
        $invitee_id = 0; // No invitee for this key
        $inviter_id = 1; // Admin user ID (you can change this to the actual admin user ID)
        $inviter_role = 'administrator'; // Admin role
        $invitee_role = 'gatekeeper'; // Role for invited users
        $invitee_email = ''; // No invitee email for this key

        // Create a record in the gatekeeper_invite_keys table for the key
        gatekeeper_create_invite_key($invite_key, $invitee_id, $inviter_id, $inviter_role, $invitee_role, $invitee_email);
    }
}

// Hook to populate gatekeeper_invite_keys table with invite keys on plugin activation
register_activation_hook(__FILE__, 'gatekeeper_populate_gatekeeper_invite_keys_table');