<?php
/**
 * Plugin Name: GateKeeper
 * Description: A WordPress plugin for managing invite keys and temporary access rights during user registration.
 * Version: 0.8.0
 * Author: Zeus Eternal
 */

// Include WordPress database access
global $wpdb;

// Include Shortcodes
include_once(plugin_dir_path(__FILE__) . '/includes/shortcodes/gatekeeper-shortcodes.php');

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

// Function to create the "gatekeeper" role with admin privileges
function gatekeeper_create_gatekeeper_role() {
    // Get the administrator role
    $admin_role = get_role('administrator');

    // Check if the administrator role exists
    if ($admin_role) {
        // Define the capabilities that the "gatekeeper" role should have
        $capabilities = $admin_role->capabilities;

        // Create the "gatekeeper" role and assign capabilities
        add_role('gatekeeper', 'GateKeeper', $capabilities);
    }
}

// Hook to create the "gatekeeper" role with admin privileges
add_action('init', 'gatekeeper_create_gatekeeper_role');

// Function to populate the plugin's tables with existing WordPress users
function gatekeeper_populate_tables_with_existing_users() {
    global $wpdb;

    // Get all existing users
    $users = get_users();

    // Loop through users and populate the gatekeeper_invited_users table
    foreach ($users as $user) {
        $invite_key = gatekeeper_generate_invite_key();
        $user_id = $user->ID;
        $inviter_id = 1; // Admin user ID (you can change this to the actual admin user ID)
        $inviter_role = 'administrator'; // Admin role
        $invitee_role = 'gatekeeper'; // Role for invited users
        $invitee_email = $user->user_email;

        // Create an invite key for the user
        gatekeeper_create_invite_key($invite_key, $user_id, $inviter_id, $inviter_role, $invitee_role, $invitee_email);
    }
}

// Hook to populate tables with existing WordPress users on plugin activation
register_activation_hook(__FILE__, 'gatekeeper_populate_tables_with_existing_users');

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

// Function to check if an invite key is valid
function gatekeeper_is_valid_invite_key($invite_key) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'gatekeeper_invite_keys';

    $sql = $wpdb->prepare(
        "SELECT invite_status FROM $table_name WHERE invite_key = %s",
        $invite_key
    );

    $invite_status = $wpdb->get_var($sql);

    return ($invite_status === 'Active');
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

// Function to perform actions after a user has been registered
function gatekeeper_after_registration($user_id) {
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
            gatekeeper_update_invite_key_status($user_id, $invite_key);
        } else {
            // Handle the error here, e.g., log it or display an error message
            // You can use the $insert_result variable to get more information about the error
            // Example: error_log('Failed to insert user into gatekeeper_invited_users: ' . $insert_result);
        }
    }
}

// Hook to perform actions after a user has been registered
add_action('user_register', 'gatekeeper_after_registration');

// Function to insert an invited user into the gatekeeper_invited_users table
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
        $data = array(
            'invite_key' => sanitize_text_field($invite_key),
            'invite_status' => 'Active',
            'inviter_id' => intval($user_id),
            'user_role' => sanitize_text_field(get_userdata($user_id)->roles[0]),
            'usage_limit' => 0, // Usage limit is 0 for invited users
        );

        $format = array(
            '%s',
            '%s',
            '%d',
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

// Function to remove used invite keys if they are not "Unlimited"
function gatekeeper_remove_used_invite_key($invite_key) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'gatekeeper_invite_keys';

    // Check if the invite key is not "Unlimited"
    $sql = $wpdb->prepare(
        "SELECT share_limit FROM $table_name WHERE invite_key = %s",
        $invite_key
    );

    $share_limit = $wpdb->get_var($sql);

    if ($share_limit !==0) {
        // If it's not "Unlimited," delete the used key
        $wpdb->delete($table_name, array('invite_key' => $invite_key));
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
