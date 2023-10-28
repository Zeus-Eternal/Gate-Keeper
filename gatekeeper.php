<?php
/**
 * Plugin Name: GateKeeper
 * Description: A WordPress plugin for managing invite keys and temporary access rights during user registration.
 * Version: 0.8.0
 * Author: Zeus Eternal
 */

// Include WordPress database access
global $wpdb;

require_once(plugin_dir_path(__FILE__) . 'includes/shortcodes/gatekeeper-shortcodes.php');

// Function to create the plugin's database tables
function gatekeeper_create_tables() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    $table_name_keys = $wpdb->prefix . 'gatekeeper_invite_keys';
    $table_name_users = $wpdb->prefix . 'gatekeeper_invited_users';
    $table_name_access_logs = $wpdb->prefix . 'gatekeeper_access_logs';
    $table_name_options = $wpdb->prefix . 'gatekeeper_options';

    // Check if the users table exists before creating your tables
    if ($wpdb->get_var("SHOW TABLES LIKE '{$wpdb->users}'") == $wpdb->users) {
        // Define SQL queries to create tables

        $sql_keys = "CREATE TABLE $table_name_keys (
            id INT NOT NULL AUTO_INCREMENT,
            invite_key VARCHAR(255) NOT NULL,
            status VARCHAR(255) NOT NULL,
            invitee_id INT,
            inviter_id INT NOT NULL,
            inviter_role VARCHAR(255) NOT NULL,
            invitee_role VARCHAR(255),
            invitee_email VARCHAR(255),
            unlimited TINYINT(1) NOT NULL,
            share_limit INT,
            usage_limit INT,
            exp_date DATETIME NULL,
            destroy_date DATETIME NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY (id)
        ) $charset_collate;";

        $sql_users = "CREATE TABLE $table_name_users (
            id INT NOT NULL AUTO_INCREMENT,
            user_id INT NOT NULL, -- This column will store the WordPress user ID
            invite_key VARCHAR(255) NOT NULL,
            invite_status VARCHAR(255) NOT NULL,
            inviter_id INT NOT NULL, -- Set inviter_id to the logged-in WordPress user ID
            user_role VARCHAR(255) NOT NULL,
            usage_limit INT NOT NULL,
            exp_date DATETIME NULL,
            PRIMARY KEY (id)
        ) $charset_collate;";

        $sql_access_logs = "CREATE TABLE $table_name_access_logs (
            id INT NOT NULL AUTO_INCREMENT,
            user_id INT NOT NULL,
            action VARCHAR(255) NOT NULL,
            action_time DATETIME NOT NULL,
            PRIMARY KEY (id)
        ) $charset_collate;";

        // Execute SQL queries to create tables
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_keys);
        dbDelta($sql_users);
        dbDelta($sql_access_logs);

        // Create the options table for plugin settings
        $sql_options = "CREATE TABLE $table_name_options (
            option_id INT NOT NULL AUTO_INCREMENT,
            option_name VARCHAR(255) NOT NULL,
            option_value LONGTEXT NOT NULL,
            PRIMARY KEY (option_id)
        ) $charset_collate;";

        dbDelta($sql_options);

        // Store default plugin options
        add_option('gatekeeper_custom_redirect_enabled', false);
    } else {
        // Handle the situation where the users table doesn't exist
        // You can add custom logic or show an error message here
    }
}

// Hook to create the plugin's database tables during plugin activation with a priority of 11
register_activation_hook(__FILE__, 'gatekeeper_create_tables', 11);

// Function to delete the plugin's database tables
function gatekeeper_delete_tables() {
    global $wpdb;
    $table_name_keys = $wpdb->prefix . 'gatekeeper_invite_keys';
    $table_name_users = $wpdb->prefix . 'gatekeeper_invited_users';
    $table_name_access_logs = $wpdb->prefix . 'gatekeeper_access_logs';
    $table_name_options = $wpdb->prefix . 'gatekeeper_options';

    // Define SQL queries to delete tables
    $sql_keys = "DROP TABLE IF EXISTS $table_name_keys;";
    $sql_users = "DROP TABLE IF EXISTS $table_name_users;";
    $sql_access_logs = "DROP TABLE IF EXISTS $table_name_access_logs;";
    $sql_options = "DROP TABLE IF EXISTS $table_name_options;";

    // Execute SQL queries to delete tables
    $wpdb->query($sql_keys);
    $wpdb->query($sql_users);
    $wpdb->query($sql_access_logs);
    $wpdb->query($sql_options);

    // Delete plugin options
    delete_option('gatekeeper_custom_redirect_enabled');
}

// Hook to delete the plugin's database tables during plugin deactivation
register_deactivation_hook(__FILE__, 'gatekeeper_delete_tables');

// Populate our Tables: 1. Populate gatekeeper_invited_users | 2. gatekeeper_invite_keys

// 1. Populate gatekeeper_invited_users with all current WP users, including admins with gatekeeper roles
/**
 * Get the ID of the admin user.
 *
 * @return int|false Admin user ID or false if not found.
 */
function get_admin_user_id() {
    // Get the admin user by the 'administrator' role
    $admin_user = get_users(array('role' => 'administrator'));

    // Check if an admin user was found
    if (!empty($admin_user)) {
        // Return the ID of the first admin user (assuming there's only one)
        return $admin_user[0]->ID;
    } else {
        // If no admin user is found, return false
        return false;
    }
}
$gk_admin = get_admin_user_id();

function gatekeeper_populate_invited_users_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'gatekeeper_invited_users';

    // Get all existing users
    $users = get_users();

    // Loop through users and populate the gatekeeper_invited_users table
    foreach ($users as $user) {
        $invite_key = gatekeeper_generate_invite_key();
        $user_id = $user->ID;
        $inviter_id = $gk_admin; // Admin user ID (you can change this to the actual admin user ID)
        $user_role = $user->roles[0]; // Get the user's role

        // Check if the user has the gatekeeper role (adjust 'gatekeeper' to your specific role)
        if (in_array('gatekeeper', $user->roles)) {
            // Set the usage limit for users with the gatekeeper role
            $usage_limit = 10; // You can adjust this limit as needed
        } else {
            $usage_limit = 0; // Usage limit is 0 for other users
        }

        // Create a record in the gatekeeper_invited_users table for the user
        $data = array(
            'user_id' => intval($user_id), // Store the WordPress user ID in 'user_id'
            'invite_key' => sanitize_text_field($invite_key),
            'invite_status' => 'Active',
            'inviter_id' => intval($inviter_id), // Admin user ID as the inviter
            'user_role' => sanitize_text_field($user_role),
            'usage_limit' => intval($usage_limit),
        );

        $format = array(
            '%d', // Format for user_id is changed to integer
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
register_activation_hook(__FILE__, 'gatekeeper_populate_invited_users_table');

// 2. Populate gatekeeper_invite_keys with 20 keys (without prefix) from admin (10 unlimited usage keys with "spec_" prefix)
function gatekeeper_populate_invite_keys_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'gatekeeper_invite_keys';

    // Check if the keys have already been populated
    $keys_populated = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");

    if ($keys_populated < 20) {
        // Generate 10 unlimited invite keys with "UNLTD" prefix
        for ($i = 0; $i < 10; $i++) {
            $invite_key = 'UNLTD_' . gatekeeper_generate_invite_key(); // Set to 'UNLIMITED' for unlimited usage
            $invitee_id = null; // No invitee for unlimited keys
            $inviter_id = $gk_admin; // Admin user ID (you can change this to the actual admin user ID)
            $inviter_role = get_userdata($inviter_id)->roles[0]; // Get the inviter's role
            $invitee_role = null; // No invitee role for unlimited keys
            $invitee_email = null; // No invitee email for unlimited keys
            $share_limit = null; // Share limit is null for unlimited keys
            $usage_limit = null; // Usage limit is null for unlimited keys

            // Create a record in the gatekeeper_invite_keys table for the key
            gatekeeper_create_invite_key($invite_key, $invitee_id, $inviter_id, $inviter_role, $invitee_role, $invitee_email, true, $share_limit, $usage_limit);
        }

        // Generate 10 limited invite keys without prefix
        for ($i = 0; $i < 10; $i++) {
            $invite_key = gatekeeper_generate_invite_key(); // Generate a random invite key
            $invitee_id = null; // No invitee for this key
            $inviter_id = $gk_admin; // Admin user ID (you can change this to the actual admin user ID)
            $inviter_role = get_userdata($inviter_id)->roles[0]; // Get the inviter's role
            $invitee_role = null; // No invitee role for this key
            $invitee_email = null; // No invitee email for this key
            $share_limit = null; // Share limit is null for this key
            $usage_limit = null; // Usage limit is null for this key

            // Create a record in the gatekeeper_invite_keys table for the key
            gatekeeper_create_invite_key($invite_key, $invitee_id, $inviter_id, $inviter_role, $invitee_role, $invitee_email, false, $share_limit, $usage_limit);
        }
    }
}

// Hook to populate gatekeeper_invite_keys table with invite keys on plugin activation
register_activation_hook(__FILE__, 'gatekeeper_populate_invite_keys_table');

// Hook to remove gatekeeper_invite_keys table with invite keys on plugin deactivation
register_deactivation_hook(__FILE__, 'gatekeeper_remove_used_invite_key');

/**
 * Generate a secure and robust invite key.
 *
 * @param int $length The length of the invite key.
 * @return string The generated invite key.
 */

// Function for our invite key generator logic
function gatekeeper_generate_invite_key($length = 5) {
    // Define characters to use in the invite key
    $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    $character_length = strlen($characters);

    // Initialize the invite key
    $invite_key = '';

    // Generate the invite key by randomly selecting characters
    for ($i = 0; $i < $length; $i++) {
        $invite_key .= $characters[rand(0, $character_length - 1)];
    }

    return $invite_key;
}
// Function to create a new invite key
function gatekeeper_create_invite_key($invite_key, $invitee_id, $inviter_id, $inviter_role, $invitee_role, $invitee_email, $unlimited, $share_limit, $usage_limit) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'gatekeeper_invite_keys';

    // Create a record in the gatekeeper_invite_keys table for the key
    $data = array(
        'invite_key' => sanitize_text_field($invite_key),
        'invitee_id' => intval($invitee_id),
        'inviter_id' => intval($inviter_id),
        'inviter_role' => sanitize_text_field($inviter_role),
        'invitee_role' => sanitize_text_field($invitee_role),
        'invitee_email' => sanitize_email($invitee_email),
        'unlimited' => $unlimited ? 1 : 0,
        'share_limit' => intval($share_limit),
        'usage_limit' => intval($usage_limit),
        'created_at' => current_time('mysql'),
        'updated_at' => current_time('mysql'),
    );

    $format = array(
        '%s',
        '%d',
        '%d',
        '%s',
        '%s',
        '%s',
        '%d',
        '%d',
        '%d',
        '%s',
        '%s',
    );

    $wpdb->insert($table_name, $data, $format);
}

// Function to remove a used invite key
function gatekeeper_remove_used_invite_key($invite_key) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'gatekeeper_invite_keys';

    // Check if the invite key is not 'Unlimited'
    $sql = $wpdb->prepare(
        "SELECT unlimited FROM $table_name WHERE invite_key = %s",
        $invite_key
    );

    $unlimited = $wpdb->get_var($sql);

    if ($unlimited !== '1') {
        // If it's not 'Unlimited' (unlimited is typically represented as '1' in the database),
        // delete the used key
        $wpdb->delete(
            $table_name,
            array('invite_key' => $invite_key)
        );
    }
}

// Function to mark an invite key as used
function gatekeeper_mark_invite_key_as_used($invite_key, $user_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'gatekeeper_invite_keys';

    // Update the invite key record with the invitee's ID and usage time
    $data = array(
        'invitee_id' => intval($user_id),
        'updated_at' => current_time('mysql'),
    );

    $where = array('invite_key' => $invite_key);

    $wpdb->update($table_name, $data, $where);
}

// Function to check if an invite key is valid
function gatekeeper_is_valid_invite_key($invite_key) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'gatekeeper_invite_keys';

    // Check if the invite key exists in the database
    $result = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE invite_key = %s", $invite_key));

    if ($result > 0) {
        return true;
    } else {
        return false;
    }
}

// Function to check if an invite key is unlimited
function gatekeeper_is_unlimited_invite_key($invite_key) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'gatekeeper_invite_keys';

    // Check if the invite key is unlimited
    $result = $wpdb->get_var($wpdb->prepare("SELECT unlimited FROM $table_name WHERE invite_key = %s", $invite_key));

    if ($result == 1) {
        return true;
    } else {
        return false;
    }
}

// Function to check if an invite key has been used
function gatekeeper_is_invite_key_used($invite_key) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'gatekeeper_invite_keys';

    // Check if the invite key has been used
    $result = $wpdb->get_var($wpdb->prepare("SELECT invitee_id FROM $table_name WHERE invite_key = %s", $invite_key));

    if ($result !== null) {
        return true;
    } else {
        return false;
    }
}

// Hook to associate roles with the "GateKeeper Invite" plugin during plugin activation
register_activation_hook(__FILE__, 'gatekeeper_associate_roles');

//////////////////////////////////////////////////+++++++++++++++++++++++++++++++++++++++++++++
//
//

// Function to add the invite key field to the registration form
function gatekeeper_add_invite_key_field_to_registration_form() {
    // Check if the user is not already logged in
    if (!is_user_logged_in()) {
        // Get the custom redirect option status
        $custom_redirect_enabled = get_option('gatekeeper_custom_redirect_enabled');
        // Add your HTML for the invite key input field here
        ?>
        <p>
            <label for="invite_key">Invite Key:</label>
            <input type="text" name="invite_key" id="invite_key" class="input" value="<?php echo esc_attr(isset($_POST['invite_key']) ? $_POST['invite_key'] : ''); ?>" required />
        </p>
        <?php
        // Add a hidden field for the custom redirect option
        if ($custom_redirect_enabled) {
            ?>
            <input type="hidden" name="custom_redirect_enabled" value="1" />
            <?php
        }
    }
}

// Function to validate the invite key during registration
function gatekeeper_validate_invite_key($errors, $sanitized_user_login, $user_email) {
    if (!is_user_logged_in()) { // Only validate if the user is not logged in
        if (empty($_POST['invite_key'])) {
            // Invite key is required
            $errors->add('invite_key_error', '<strong>ERROR</strong>: Please enter a valid invite key.');
        } else {
            $invite_key = sanitize_text_field($_POST['invite_key']);
            // Check if the invite key exists and is valid
            if (!gatekeeper_is_valid_invite_key($invite_key)) {
                $errors->add('invite_key_error', '<strong>ERROR</strong>: The invite key is invalid.');
            }
        }
    }

    return $errors;
}

// Hook to add invite key field to the registration form
add_action('register_form', 'gatekeeper_add_invite_key_field_to_registration_form');

// Hook to validate invite key during registration
add_filter('registration_errors', 'gatekeeper_validate_invite_key', 10, 3);

// Function to redirect users after successful registration
function gatekeeper_registration_redirect($user_id) {
    // Check if the user is not already logged in
    if (!is_user_logged_in()) {
        // Check if the custom redirect option is enabled
        $custom_redirect_enabled = get_option('gatekeeper_custom_redirect_enabled');

        if ($custom_redirect_enabled) {
            // Redirect to your custom page URL
            wp_redirect(home_url('/custom-page/')); // Change '/custom-page/' to your desired URL
        } else {
            // Use the default WordPress registration redirect
            wp_redirect(wp_registration_url());
        }

        exit();
    }
}

// Hook to redirect users after successful registration
add_action('woocommerce_registration_redirect', 'gatekeeper_registration_redirect');

// Hook to perform actions after a user has been registered
add_action('user_register', 'gatekeeper_after_registration');

/**
 * Perform actions after a user has been registered.
 *
 * @param int $user_id The ID of the registered user.
 */
function gatekeeper_after_registration($user_id) {
    // Get the invite key from the registration form
    $invite_key = isset($_POST['invite_key']) ? sanitize_text_field($_POST['invite_key']) : '';

    // Check if the invite key is not empty
    if (!empty($invite_key)) {
        // Attempt to insert the user into the gatekeeper_invited_users table
        $insert_result = gatekeeper_insert_invited_user($invite_key, $user_id);

        if (is_wp_error($insert_result)) {
            // Handle insertion errors
            $error_message = $insert_result->get_error_message();
            // Log the error or display it to the user
            error_log('Failed to insert user into gatekeeper_invited_users: ' . $error_message);
            wp_die('There was an error processing your registration. Please try again later.');
        } elseif ($insert_result === true) {
            // Remove used invite keys after successful registration
            gatekeeper_remove_used_invite_key($invite_key);
            // Update invite key status after successful registration
            gatekeeper_update_invite_key_status($user_id, $invite_key);
        } else {
            // Handle other unexpected results
            wp_die('An unexpected error occurred during registration. Please contact support.');
        }
    }
}

// Function to update invitee_id in the gatekeeper_invited_users table
function gatekeeper_update_invitee_id($user_id, $invite_key) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'gatekeeper_invited_users';

    // Update the invitee_id for the specific invite key
    $wpdb->update(
        $table_name,
        array('invitee_id' => intval($user_id)),
        array('invite_key' => sanitize_text_field($invite_key))
    );
}

function gatekeeper_insert_invited_user($invite_key, $user_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'gatekeeper_invited_users';

    // Check if the invite key exists in the gatekeeper_invite_keys table
    $key_exists = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}gatekeeper_invite_keys WHERE invite_key = %s",
            $invite_key
        )
    );

    // If the invite key exists, insert the user into the gatekeeper_invited_users table
    if ($key_exists > 0) {
        // Get the logged-in user's ID as the inviter
        $inviter_id = get_current_user_id();

        $data = array(
            'user_id' => intval($user_id), // Set user_id to the WordPress user ID
            'invite_key' => sanitize_text_field($invite_key),
            'invite_status' => 'Active',
            'inviter_id' => intval($inviter_id), // Set inviter_id to the logged-in user's WordPress user ID
            'user_role' => sanitize_text_field(get_userdata($user_id)->roles[0]),
            'usage_limit' => 0, // Usage limit is 0 for invited users
        );

        $format = array(
            '%d', // Format for user_id is changed to integer
            '%s',
            '%s',
            '%d', // Format for inviter_id is changed to integer
            '%s',
            '%d',
        );

        // Insert the user data into the gatekeeper_invited_users table
        $insert_result = $wpdb->insert($table_name, $data, $format);

        // Return true if the insertion was successful, otherwise return an error message
        return $insert_result === false ? $wpdb->last_error : true;
    } else {
        // Return an error message if the invite key does not exist
        return 'Invite key does not exist.';
    }
}

// Function to update invite key status after successful registration
function gatekeeper_update_invite_key_status($user_id, $invite_key) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'gatekeeper_invite_keys';

    // Update the invite key status to "Used" for the registered user
    $wpdb->update(
        $table_name,
        array('invite_status' => 'Used'),
        array(
            'invite_key' => sanitize_text_field($invite_key),
            'invitee_id' => intval($user_id),
        )
    );
}

// Hook to add invite key field to the PMPro registration form
add_action('pmpro_checkout_boxes', 'gatekeeper_add_invite_key_field_to_pmpro_registration');

function gatekeeper_add_invite_key_field_to_pmpro_registration() {
    // Check if the user is not already logged in
    if (!is_user_logged_in()) {
        // Get the custom redirect option status
        $custom_redirect_enabled = get_option('gatekeeper_custom_redirect_enabled');
        // Add your HTML for the invite key input field here
        ?>
        <div id="gatekeeper_invite_key" class="pmpro_checkout-field pmpro_checkout-field-invite_key">
            <label for="invite_key">Invite Key:</label>
            <input type="text" name="invite_key" id="invite_key" class="input" value="<?php echo esc_attr(isset($_POST['invite_key']) ? $_POST['invite_key'] : ''); ?>" required />
        </div>
        <?php
        // Add a hidden field for the custom redirect option
        if ($custom_redirect_enabled) {
            ?>
            <input type="hidden" name="custom_redirect_enabled" value="1" />
            <?php
        }

        // Add a hidden field to check if the invitee_email exists
        ?>
        <input type="hidden" name="invitee_email_exists" id="invitee_email_exists" value="0" />
        <?php
    }
}

// Function to validate the invite key during PMPro registration
function gatekeeper_validate_invite_key_pmpro($pmpro_continue_registration) {
    if (!is_user_logged_in()) { // Only validate if the user is not logged in
        if (empty($_POST['invite_key'])) {
            // Invite key is required
            pmpro_setMessage('Please enter a valid invite key.', 'pmpro_error');
            $pmpro_continue_registration = false;
        } else {
            $invite_key = sanitize_text_field($_POST['invite_key']);
            // Check if the invite key exists and is valid
            if (!gatekeeper_is_valid_invite_key($invite_key)) {
                pmpro_setMessage('The invite key is invalid.', 'pmpro_error');
                $pmpro_continue_registration = false;
            }
        }

        // Check if invitee_email exists
        if (isset($_POST['invitee_email_exists']) && $_POST['invitee_email_exists'] == '1') {
            $pmpro_continue_registration = false;
        }
    }

    return $pmpro_continue_registration;
}

// Hook to validate invite key during PMPro registration
add_filter('pmpro_registration_checks', 'gatekeeper_validate_invite_key_pmpro');

/**
 * Perform actions after a user has been registered with PMPro integration.
 *
 * @param int $user_id The ID of the registered user.
 */
function gatekeeper_after_registration_pmpro($user_id) {
    // Get the invite key from the registration form
    $invite_key = isset($_POST['invite_key']) ? sanitize_text_field($_POST['invite_key']) : '';

    // Check if the invite key is not empty
    if (!empty($invite_key)) {
        // Insert the user into the gatekeeper_invited_users table
        $insert_result = gatekeeper_insert_invited_user($invite_key, $user_id);

        // Check if the insertion was successful
        if ($insert_result === true) {
            // Remove used invite keys after successful registration
            gatekeeper_remove_used_invite_key($invite_key);

            // Update invite key status after successful registration
            gatekeeper_update_invite_key_status($invite_key, $user_id);
        } else {
            // Handle the error here, e.g., log it or display an error message
            error_log('Failed to insert user into gatekeeper_invited_users: ' . $insert_result);
            // You can also display an error message to the user
            pmpro_setMessage('There was an error processing your registration. Please try again later.', 'pmpro_error');
        }
    }
}

// Hook to perform actions after a user has been registered with PMPro
add_action('pmpro_after_checkout', 'gatekeeper_after_registration_pmpro');

// Function to create the "GateKeeper" user role and assign capabilities
function gatekeeper_create_role() {
    // Define capabilities for the "GateKeeper" role
    $capabilities = array(
        'read' => true,
        'edit_gatekeeper' => true, // Capability to edit GateKeeper content
        'create_gatekeeper' => true, // Capability to create GateKeeper content
        'edit_own_gatekeeper' => true, // Capability to edit their own GateKeeper content
        'delete_gatekeeper' => true, // Capability to delete GateKeeper content
        // Add other capabilities as needed
    );

    // Create the "GateKeeper" role
    add_role('gatekeeper', 'GateKeeper', $capabilities);
}

// Hook to create the "GateKeeper" role during plugin activation
register_activation_hook(__FILE__, 'gatekeeper_create_role');

// Function to associate other roles with the "GateKeeper Invite" plugin
function gatekeeper_associate_roles() {
    // Get the "GateKeeper" role
    $gatekeeper_role = get_role('gatekeeper');

    // Check if the role exists
    if ($gatekeeper_role) {
        // Define other roles to associate with the "GateKeeper" plugin
        $roles_to_associate = array('editor', 'author', 'contributor'); // Add roles as needed

        // Loop through roles and add them to the "GateKeeper" role
        foreach ($roles_to_associate as $role) {
            $gatekeeper_role->add_cap("edit_$role");
            $gatekeeper_role->add_cap("create_$role");
            $gatekeeper_role->add_cap("edit_own_$role");
            $gatekeeper_role->add_cap("delete_$role");
            // Add other capabilities as needed
        }
    }
}

// Hook to associate roles with the "GateKeeper Invite" plugin during plugin activation
register_activation_hook(__FILE__, 'gatekeeper_associate_roles');
