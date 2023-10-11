<?php
/**
 * Plugin Name: GateKeeper
 * Description: A WordPress plugin for managing invite keys during user registration.
 * Version: 1.0.0
 * Author: Your Name
 */

// Function to add the invite key to the registration form
function gatekeeper_add_invite_key_field() {
    ?>
    <p>
        <label for="invite_key"><?php _e('Invite Key', 'gatekeeper'); ?><br />
            <input type="text" name="invite_key" id="invite_key" class="input" value="<?php echo esc_attr(wp_unslash(isset($_POST['invite_key']) ? $_POST['invite_key'] : '')); ?>" size="25" required />
        </label>
    </p>
    <?php
}
add_action('register_form', 'gatekeeper_add_invite_key_field');

// Function to generate an invite key
function gatekeeper_generate_invite_key($length = 10) {
    $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    $invite_key = '';
    for ($i = 0; $i < $length; $i++) {
        $invite_key .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $invite_key;
}

// Function to generate and assign invite keys to users
function gatekeeper_generate_and_assign_invite_keys($user_id, $count = 5) {
    global $wpdb;

    for ($i = 0; $i < $count; $i++) {
        $invite_key = gatekeeper_generate_invite_key();
        $user_role = sanitize_text_field($_POST['user_role']);
        
        // Insert invite key into the database with the user's selected role
        $insert_result = $wpdb->insert(
            $wpdb->prefix . 'gatekeeper_invite_keys',
            array(
                'invite_key' => $invite_key,
                'user_id' => $user_id,
                'user_role' => $user_role,
                'invite_status' => 'Active',
                'usage_limit' => 0, // Initialize usage limit to 0
                'inviter_id' => $user_id, // The user is the initial inviter
                'key_exp_acc' => null, // Initialize key access expiration
                'key_exp_date' => null, // Initialize key expiration date
            )
        );

        if ($insert_result === false) {
            // An error occurred while inserting data
            error_log("Error inserting invite key for user $user_id: " . $wpdb->last_error);
        }
    }
}

// Hook to generate and assign invite keys for new users
function gatekeeper_generate_invite_keys_for_new_users($user_id) {
    gatekeeper_generate_and_assign_invite_keys($user_id);
}
add_action('user_register', 'gatekeeper_generate_invite_keys_for_new_users');

// Function to handle the registration process
function gatekeeper_registration_process($user_id) {
    // Check if an invite key was provided during registration
    if (isset($_POST['invite_key']) && !empty($_POST['invite_key'])) {
        $invite_key = sanitize_text_field($_POST['invite_key']);
        
        // Validate the invite key and get the related invitation details
        $invitation_details = gatekeeper_validate_invite_key($invite_key);
        
        if ($invitation_details) {
            // Insert the user as an invitee into the invited users table
            $insert_result = gatekeeper_insert_invited_user($invitation_details, $user_id);
            
            if ($insert_result) {
                // Mark the invite key as used
                gatekeeper_mark_invite_key_as_used($invite_key, $user_id);
            }
        } else {
            // Handle invalid or expired invite key
            // You can add error messages or custom handling here
        }
    }
}

// Hook to process registration and track invitations
function gatekeeper_process_registration_and_invitations($user_id) {
    // Process registration
    gatekeeper_registration_process($user_id);
    
    // Track invitations
    if (isset($_POST['invite_key']) && !empty($_POST['invite_key'])) {
        $invite_key = sanitize_text_field($_POST['invite_key']);
        $inviter_id = gatekeeper_get_inviter_id($invite_key);
        
        if ($inviter_id) {
            // Track the user relationship (inviter to invitee)
            gatekeeper_track_user_relationship($inviter_id, $user_id, $invite_key);
        }
    }
}
add_action('user_register', 'gatekeeper_process_registration_and_invitations');

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

// Function to insert an invitee into the invited users table
function gatekeeper_insert_invited_user($invitation_details, $invitee_id) {
    global $wpdb;

    $insert_result = $wpdb->insert(
        $wpdb->prefix . 'gatekeeper_invited_users',
        array(
            'invite_key' => $invitation_details->invite_key,
            'invite_status' => $invitation_details->invite_status,
            'inviter_id' => $invitation_details->inviter_id,
            'invitee_id' => $invitee_id,
            'user_role' => $invitation_details->user_role,
            'usage_limit' => $invitation_details->usage_limit,
            'key_exp_acc' => $invitation_details->key_exp_acc,
            'created_at' => current_time('mysql'),
        )
    );

    return $insert_result !== false;
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

// Function to mark an invite key as used
function gatekeeper_mark_invite_key_as_used($invite_key, $invitee_id) {
    global $wpdb;

    // Increment the usage count for the invite key
    $wpdb->query(
        $wpdb->prepare(
            "UPDATE {$wpdb->prefix}gatekeeper_invite_keys SET usage_limit = usage_limit + 1 WHERE invite_key = %s",
            $invite_key
        )
    );

    // Update the invite status to 'Used'
    $wpdb->update(
        $wpdb->prefix . 'gatekeeper_invite_keys',
        array('invite_status' => 'Used'),
        array('invite_key' => $invite_key)
    );

    // Update the inviter ID for the invitee
    $wpdb->update(
        $wpdb->prefix . 'gatekeeper_invited_users',
        array('inviter_id' => $invitee_id),
        array('invite_key' => $invite_key, 'invitee_id' => $invitee_id)
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

// Function to create necessary database tables during plugin activation
function gatekeeper_create_tables() {
    global $wpdb;

    // Define the table name for invite keys
    $invite_keys_table = $wpdb->prefix . 'gatekeeper_invite_keys';
    $invited_users_table = $wpdb->prefix . 'gatekeeper_invited_users';

    // Define SQL queries for creating the invite keys and invited users tables
    $invite_keys_sql = "
        CREATE TABLE IF NOT EXISTS $invite_keys_table (
            id INT AUTO_INCREMENT PRIMARY KEY,
            invite_key VARCHAR(255) NOT NULL,
            user_id INT NOT NULL,
            user_role VARCHAR(255) NOT NULL,
            invite_status VARCHAR(255) NOT NULL,
            usage_limit INT NOT NULL,
            inviter_id INT NOT NULL,
            key_exp_acc DATETIME,
            key_exp_date DATETIME,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE (invite_key)
        ) ENGINE=InnoDB;
    ";
    
    $invited_users_sql = "
        CREATE TABLE IF NOT EXISTS $invited_users_table (
            id INT AUTO_INCREMENT PRIMARY KEY,
            invite_key VARCHAR(255) NOT NULL,
            invite_status VARCHAR(255) NOT NULL,
            inviter_id INT NOT NULL,
            invitee_id INT NOT NULL,
            user_role VARCHAR(255) NOT NULL,
            usage_limit INT NOT NULL,
            key_exp_acc DATETIME,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY (invite_key, invitee_id)
        ) ENGINE=InnoDB;
    ";

    // Create the invite keys and invited users tables
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($invite_keys_sql);
    dbDelta($invited_users_sql);
}
register_activation_hook(__FILE__, 'gatekeeper_create_tables');

// Function to populate gatekeeper_invited_users with existing members
function gatekeeper_populate_existing_users() {
    global $wpdb;

    // Define the table names for invited users and invite keys
    $invited_users_table = $wpdb->prefix . 'gatekeeper_invited_users';
    $invite_keys_table = $wpdb->prefix . 'gatekeeper_invite_keys';

    // Get all existing users except administrators
    $users = get_users(array('role__not_in' => 'administrator'));

    // Assuming the admin ID is 1. Replace with the actual admin ID if different.
    $admin_id = 1;

    // Iterate through each existing user
    foreach ($users as $user) {
        $invite_key = gatekeeper_generate_invite_key(); // Generate a new invite key

        // Insert invite key into the database for each existing member
        $insert_key_result = $wpdb->insert(
            $invite_keys_table,
            array(
                'invite_key' => $invite_key,
                'user_id' => $user->ID,
                'user_role' => implode(',', $user->roles), // Save all roles as a comma-separated string
                'invite_status' => 'Active',
                'usage_limit' => 0, // Initialize usage limit to 0
                'inviter_id' => $admin_id, // The admin is the initial inviter
                'key_exp_acc' => null, // Initialize key access expiration
                'key_exp_date' => null, // Initialize key expiration date
                'created_at' => current_time('mysql'),
            )
        );

        if ($insert_key_result === false) {
            // An error occurred while inserting the invite key data
            error_log("Error inserting invite key for existing user {$user->ID}: " . $wpdb->last_error);
        }

        // Insert an entry into the invited users table for each existing user
        $insert_user_result = $wpdb->insert(
            $invited_users_table,
            array(
                'invite_key' => $invite_key,
                'invite_status' => 'Accepted', // Assuming status is 'Accepted' for existing users
                'inviter_id' => $admin_id, // The admin is the inviter
                'invitee_id' => $user->ID,
                'user_role' => implode(',', $user->roles), // Save all roles as a comma-separated string
                'usage_limit' => 0, // Initialize usage limit to 0
                'key_exp_acc' => null, // Initialize key access expiration
                'created_at' => current_time('mysql'),
            )
        );

        if ($insert_user_result === false) {
            // An error occurred while inserting the invited user data
            error_log("Error inserting invited user data for existing user {$user->ID}: " . $wpdb->last_error);
        }
    }
}

// Call the function on plugin activation
register_activation_hook(__FILE__, 'gatekeeper_populate_existing_users');
