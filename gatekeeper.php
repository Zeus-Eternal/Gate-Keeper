<?php
/**
 * Plugin Name: GateKeeper
 * Description: A WordPress plugin for managing invite keys and temporary access rights during user registration.
 * Version: 0.8.0
 * Author: Zeus Eternal
 */

// Include WordPress database access
global $wpdb;

// Include registration intergration
include_once(plugin_dir_path(__FILE__) . '/includes/gatekeeper-registration.php');

// Include decoder
include_once(plugin_dir_path(__FILE__) . '/helpers/gatekeeper-decoder.php');

// Shortcode function to display Available Keys and Invited Users
function gatekeeper_display_shortcode($atts) {
    ob_start();

    // Add your HTML and PHP code to display Available Keys and Invited Users here
    ?>
    <div class="gatekeeper-display">
        <h2>Available Keys</h2>
        <?php gatekeeper_display_available_keys(); ?>

        <h2>Invited Users</h2>
        <?php gatekeeper_display_invited_users(); ?>
    </div>
    <?php

    return ob_get_clean();
}

/**
 * Generate a secure and robust invite key.
 *
 * @param int $length The length of the invite key.
 * @return string The generated invite key.
 */
function gatekeeper_generate_invite_key($length = 5) {
    // Define characters to use in the invite key
    $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    $character_length = strlen($characters);

    // Initialize the invite key
    $invite_key = '';

    // Generate random bytes securely
    if (function_exists('random_bytes')) {
        $bytes = random_bytes($length);
    } elseif (function_exists('openssl_random_pseudo_bytes')) {
        $bytes = openssl_random_pseudo_bytes($length, $strong);
        if (!$strong) {
            // OpenSSL didn't provide strong randomness, fallback to less secure method
            $bytes = '';
        }
    }

    // If neither random_bytes nor openssl_random_pseudo_bytes is available, fall back to mt_rand
    if (empty($bytes)) {
        for ($i = 0; $i < $length; $i++) {
            $invite_key .= $characters[mt_rand(0, $character_length - 1)];
        }
    } else {
        for ($i = 0; $i < $length; $i++) {
            $invite_key .= $characters[ord($bytes[$i]) % $character_length];
        }
    }

    return $invite_key;
}



// Function to create an invite key and store it in the database
function gatekeeper_create_invite_key($invite_key, $inviter_id, $invitee_id, $inviter_role, $invitee_role) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'gatekeeper_invite_keys';

    $data_inviter = array(
        'invite_key' => sanitize_text_field($invite_key),
        'inviter_id' => intval($inviter_id),
        'invitee_id' => intval($invitee_id),
        'inviter_role' => sanitize_text_field($inviter_role),
        'invitee_role' => sanitize_text_field($invitee_role),
        'invite_status' => 'Active',
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

// Function to store the user role associated with an invite key
function gatekeeper_store_user_role($invite_key, $user_role) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'gatekeeper_invited_users';

    $data = array(
        'invite_key' => sanitize_text_field($invite_key),
        'user_role' => sanitize_text_field($user_role),
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
        'invite_key' => sanitize_text_field($invitation_details->invite_key),
        'invite_status' => 'Accepted',
        'inviter_id' => gatekeeper_get_inviter_id($invitation_details->invite_key),
        'user_role' => gatekeeper_get_default_user_role(),
        'usage_limit' => 0,
    );

    $where = array(
        'invitee_id' => intval($user_id),
    );

    $wpdb->update($table_name, $data, $where);
}

// Function to get the inviter's ID from an invite key
function gatekeeper_get_inviter_id($invite_key) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'gatekeeper_invite_keys';

    $sql = $wpdb->prepare(
        "SELECT inviter_id FROM $table_name WHERE invite_key = %s",
        sanitize_text_field($invite_key)
    );

    $inviter_id = $wpdb->get_var($sql);

    if ($inviter_id) {
        return intval($inviter_id);
    }

    return false;
}

// Function to get the default user role for invited users
function gatekeeper_get_default_user_role() {
    // Customize this function to return the desired default role.
    return 'subscriber';
}

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
        invitee_id mediumint(9) NOT NULL,
        user_role varchar(255) NOT NULL,
        usage_limit int(11) NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";

    // SQL statement to create the access_logs table
    $sql_access_logs = "CREATE TABLE $table_name_access_logs (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        user_id mediumint(9) NOT NULL,
        access_type varchar(255) NOT NULL,
        access_key varchar(255) NOT NULL,
        accessed_at datetime DEFAULT '0000-00-00 00:00:00',
        PRIMARY KEY (id)
    ) $charset_collate;";

    // SQL statement to create the options table (customize based on your requirements)
    $sql_options = "CREATE TABLE $table_name_options (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        option_name varchar(255) NOT NULL,
        option_value text,
        PRIMARY KEY (id)
    ) $charset_collate;";

    // Create the tables
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql_keys);
    dbDelta($sql_users);
    dbDelta($sql_access_logs);
    dbDelta($sql_options);

    // Populate the gatekeeper_access_logs table with dummy content
    gatekeeper_populate_access_logs();

    // Populate the gatekeeper_invited_users table with existing users (excluding the inviter)
    gatekeeper_populate_invited_users();

    // Generate and populate the gatekeeper_invite_keys table with 20 random keys from the inviter
    gatekeeper_generate_and_populate_invite_keys(20);

    // Generate and populate the gatekeeper_options table with actual data
    gatekeeper_generate_and_populate_options();

    // Auto-invite all WordPress administrators as GateKeeper (Super User)
    gatekeeper_auto_invite_admins_as_gatekeepers();
}

// Function to remove the plugin's database tables upon deactivation
function gatekeeper_deactivate_plugin() {
    global $wpdb;
    $table_name_keys = $wpdb->prefix . 'gatekeeper_invite_keys';
    $table_name_users = $wpdb->prefix . 'gatekeeper_invited_users';
    $table_name_access_logs = $wpdb->prefix . 'gatekeeper_access_logs';
    $table_name_options = $wpdb->prefix . 'gatekeeper_options';

    $wpdb->query("DROP TABLE IF EXISTS $table_name_keys");
    $wpdb->query("DROP TABLE IF EXISTS $table_name_users");
    $wpdb->query("DROP TABLE IF EXISTS $table_name_access_logs");
    $wpdb->query("DROP TABLE IF EXISTS $table_name_options");
}

// Function to populate the gatekeeper_access_logs table with dummy content
function gatekeeper_populate_access_logs() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'gatekeeper_access_logs';

    // Dummy data
    $dummy_data = array(
        array(
            'user_id' => 1,
            'access_type' => 'Login',
            'access_key' => 'login_key_1',
            'accessed_at' => current_time('mysql'),
        ),
        array(
            'user_id' => 2,
            'access_type' => 'Page Visit',
            'access_key' => 'page_key_1',
            'accessed_at' => current_time('mysql'),
        ),
        // Add more dummy data as needed
    );

    foreach ($dummy_data as $data) {
        $wpdb->insert($table_name, $data);
    }
}

// Function to generate and populate the gatekeeper_invited_users table with existing users (excluding the inviter)
function gatekeeper_populate_invited_users() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'gatekeeper_invited_users';

    // Get existing users (excluding the inviter with ID 1)
    $existing_users = get_users(array(
        'exclude' => array(1), // Exclude the inviter with ID 1
    ));

    // Dummy data
    $dummy_data = array();

    foreach ($existing_users as $user) {
        $invite_key = gatekeeper_generate_invite_key();
        $dummy_data[] = array(
            'invite_key' => sanitize_text_field($invite_key),
            'invite_status' => 'Active', // You may set this to 'Pending' initially
            'inviter_id' => 1, // Assuming the inviter is always user ID 1
            'invitee_id' => $user->ID,
            'user_role' => gatekeeper_get_default_user_role(), // Customize the default role as needed
            'usage_limit' => 0,
        );
    }

    foreach ($dummy_data as $data) {
        $wpdb->insert($table_name, $data);
    }
}

// Function to generate and populate the gatekeeper_invite_keys table with a specified number of keys from the inviter
function gatekeeper_generate_and_populate_invite_keys($count) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'gatekeeper_invite_keys';

    $inviter_id = 1; // Assuming the inviter is always user ID 1

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

// Function to generate and populate the gatekeeper_options table with actual data
function gatekeeper_generate_and_populate_options() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'gatekeeper_options';

    // Define the options and their values
    $options = array(
        'option_name_1' => 'option_value_1',
        'option_name_2' => 'option_value_2',
        // Add more options as needed
    );

    foreach ($options as $option_name => $option_value) {
        $data = array(
            'option_name' => sanitize_text_field($option_name),
            'option_value' => sanitize_text_field($option_value),
        );

        $format = array(
            '%s',
            '%s',
        );

        $wpdb->insert($table_name, $data, $format);
    }
}

// Function to run when the plugin is activated
function gatekeeper_activate_plugin() {
    // Check if tables exist; create them if not
    gatekeeper_create_tables();
}

// Function to auto-invite all WordPress administrators as GateKeeper (Super User)
function gatekeeper_auto_invite_admins_as_gatekeepers() {
    global $wpdb;

    $admins = get_users(array('role' => 'administrator'));

    foreach ($admins as $admin) {
        $invite_key = gatekeeper_generate_invite_key();

        // Create the invite key and store it in the database
        gatekeeper_create_invite_key($invite_key, 1, $admin->ID, 'GateKeeper', 'GateKeeper');
        gatekeeper_store_user_role($invite_key, 'GateKeeper');

        // After storing the invite key in the user_role table, remove it from the invite_keys table
        $table_name_keys = $wpdb->prefix . 'gatekeeper_invite_keys';
        $wpdb->delete($table_name_keys, array('invite_key' => $invite_key));
    }
}

// Include registration intergration
include_once(plugin_dir_path(__FILE__) . '/helpers/gatekeeper-invite-key-validation.php');


// Validate the invite key during registration
add_filter('registration_errors', 'gatekeeper_registration_check', 10, 3);

// Plugin activation hook
register_activation_hook(__FILE__, 'gatekeeper_activate_plugin');

// Plugin deactivation hook
register_deactivation_hook(__FILE__, 'gatekeeper_deactivate_plugin');
