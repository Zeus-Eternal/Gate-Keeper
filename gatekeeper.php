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

        // Insert invite key into the database
        $insert_result = $wpdb->insert(
            $wpdb->prefix . 'gatekeeper_invite_keys',
            array(
                'invite_key' => $invite_key,
                'user_id' => $user_id,
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

// Function to update invite keys for a user
function gatekeeper_update_invite_keys($user_id, $count = 5) {
    global $wpdb;

    // Delete existing invite keys for the user
    $wpdb->delete(
        $wpdb->prefix . 'gatekeeper_invite_keys',
        array('user_id' => $user_id)
    );

    // Generate and insert new invite keys
    gatekeeper_generate_and_assign_invite_keys($user_id, $count);
}

// Hook to allow users to update their invite keys
function gatekeeper_update_invite_keys_for_users() {
    $current_user = wp_get_current_user();
    if (is_user_logged_in()) {
        // Allow users to update their invite keys
        gatekeeper_update_invite_keys($current_user->ID);
    }
}
add_action('init', 'gatekeeper_update_invite_keys_for_users');

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
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE (invite_key)
        ) ENGINE=InnoDB;
    ";

    // Create the invite keys table
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($invite_keys_sql);
}
register_activation_hook(__FILE__, 'gatekeeper_create_tables');
