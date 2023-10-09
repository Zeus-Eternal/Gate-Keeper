<?php
/**
 * Plugin Name: GateKeeper
 * Description: A WordPress plugin for managing invite keys during user registration and tracking user relationships.
 * Version: 1.0.0
 * Author: Your Name
 */

// Function to add the invite key and user role fields to the registration form
function gatekeeper_add_invite_key_and_user_role_fields() {
    ?>
    <p>
        <label for="invite_key"><?php _e('Invite Key', 'gatekeeper'); ?><br />
            <input type="text" name="invite_key" id="invite_key" class="input" value="<?php echo esc_attr(wp_unslash(isset($_POST['invite_key']) ? $_POST['invite_key'] : '')); ?>" size="25" required />
        </label>
    </p>
    <p>
        <label for="user_role"><?php _e('User Role', 'gatekeeper'); ?><br />
            <input type="text" name="user_role" id="user_role" class="input" value="<?php echo esc_attr(wp_unslash(isset($_POST['user_role']) ? $_POST['user_role'] : 'Subscriber')); ?>" size="25" required />
        </label>
    </p>
    <?php
}
add_action('register_form', 'gatekeeper_add_invite_key_and_user_role_fields');

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
        $inviter_id = get_current_user_id();  // Get the ID of the inviter

        // Insert invite key into the database with the user's selected role and inviter's ID
        $user_role = sanitize_text_field($_POST['user_role']);
        $insert_result = $wpdb->insert(
            $wpdb->prefix . 'gatekeeper_invite_keys',
            array(
                'invite_key' => $invite_key,
                'user_id' => $user_id,
                'user_role' => $user_role,
                'status' => 'Active',
                'inviter_id' => $inviter_id,  // Store the ID of the inviter
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

// Function to get the inviter of a user
function gatekeeper_get_inviter($user_id) {
    global $wpdb;

    $inviter_id = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT inviter_id FROM {$wpdb->prefix}gatekeeper_invite_keys WHERE user_id = %d",
            $user_id
        )
    );

    if ($inviter_id) {
        return get_userdata($inviter_id);
    } else {
        return null;
    }
}

// Example usage: Display inviter on user profile
function gatekeeper_display_inviter_on_profile($user_id) {
    $inviter = gatekeeper_get_inviter($user_id);

    if ($inviter) {
        echo '<p>Invited by: ' . esc_html($inviter->user_login) . '</p>';
    }
}

// Function to create necessary database tables during plugin activation
function gatekeeper_create_tables() {
    global $wpdb;

    // Define the table name for invite keys
    $invite_keys_table = $wpdb->prefix . 'gatekeeper_invite_keys';

    // Define SQL query for creating the invite keys table
    $invite_keys_sql = "
        CREATE TABLE IF NOT EXISTS $invite_keys_table (
            id INT AUTO_INCREMENT PRIMARY KEY,
            invite_key VARCHAR(255) NOT NULL,
            user_id INT NOT NULL,
            user_role VARCHAR(255) NOT NULL,
            status VARCHAR(255) NOT NULL,
            inviter_id INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE (invite_key)
        ) ENGINE=InnoDB;
    ";

    // Create the invite keys table
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($invite_keys_sql);
}
register_activation_hook(__FILE__, 'gatekeeper_create_tables');

// Hook to allow users to update their invite keys
function gatekeeper_update_invite_keys_for_users() {
    $current_user = wp_get_current_user();
    if (is_user_logged_in()) {
        // Allow users to update their invite keys
        gatekeeper_generate_and_assign_invite_keys($current_user->ID, 5);
    }
}
add_action('init', 'gatekeeper_update_invite_keys_for_users');
