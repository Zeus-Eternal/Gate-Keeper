<?php
/**
 * Plugin Name: GateKeeper
 * Description: A WordPress plugin for managing invitations and roles.
 * Version: 0.0.1
 * Author: Zeus Eternal
 */

// Function to generate a unique Invite Key
function gatekeeper_generate_invite_key($length = 5) {
    $invite_key = wp_generate_password($length, false);

    // Enforce a specific pattern and format if required
    // Example: $invite_key = strtoupper($invite_key); // Convert to uppercase

    return $invite_key;
} 

// Register activation and deactivation hooks
register_activation_hook(__FILE__, 'gatekeeper_plugin_activate');
register_deactivation_hook(__FILE__, 'gatekeeper_plugin_deactivate');

// Include fucntions
include_once(plugin_dir_path(__FILE__) . 'admin/settings.php');

// Include scripts and styles for SPA
function gatekeeper_enqueue_scripts() {
    wp_enqueue_script('gatekeeper-app', plugin_dir_url(__FILE__) . 'js/app.js', array('jquery'), '1.0.0', true);
    wp_enqueue_style('gatekeeper-styles', plugin_dir_url(__FILE__) . 'css/styles.css', array(), '1.0.0');
}
add_action('wp_enqueue_scripts', 'gatekeeper_enqueue_scripts');

// Activation Hook for GateKeeper Plugin
function gatekeeper_plugin_activate() {
    global $wpdb;

    // Create necessary database tables
    gatekeeper_create_tables();

    // Set default options and update existing members
    gatekeeper_set_default_options();
}

// Deactivation Hook for GateKeeper Plugin
function gatekeeper_plugin_deactivate() {
    // Remove plugin options or perform other cleanup on deactivation
    gatekeeper_remove_options();

    // Remove database tables
    gatekeeper_remove_tables();
}

// Function to create necessary database tables during plugin activation
function gatekeeper_create_tables() {
    global $wpdb;

    // Define the table names
    $invite_keys_table = $wpdb->prefix . 'gatekeeper_invite_keys';
    // ... Define other table names ...

    // Define SQL queries for creating tables
    $invite_keys_sql = "
        CREATE TABLE IF NOT EXISTS $invite_keys_table (
            id INT AUTO_INCREMENT PRIMARY KEY,
            invite_key VARCHAR(255) NOT NULL,
            role VARCHAR(255) NOT NULL,
            inviter INT NOT NULL,
            invitee INT NOT NULL,
            usage_limit INT NOT NULL,
            is_expiry BOOL NOT NULL,
            expiry_date DATETIME NOT NULL,
            status VARCHAR(20) NOT NULL,
            accepted BOOL NOT NULL,
            accepted_at TINYINT(1) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (inviter) REFERENCES $wpdb->users(ID),
            FOREIGN KEY (invitee) REFERENCES $wpdb->users(ID)
        ) ENGINE=InnoDB;
    ";

    // ... Define SQL queries for other tables ...

    // Create tables
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($invite_keys_sql);
    // ... Create other tables ...
}

// Function to set default options during plugin activation
function gatekeeper_set_default_options() {
    // Define your default options here
    $default_options = array(
        'gatekeeper_enable_feature' => 'yes',
        'gatekeeper_default_role' => 'subscriber',
        'gatekeeper_invitation_limit' => 5,
        // Add more options as needed
    );

    foreach ($default_options as $option_name => $option_default) {
        if (!get_option($option_name)) {
            add_option($option_name, $option_default);
        }
    }

    // Update user/GateKeeper tables for existing members
    gatekeeper_update_existing_members();
}

// Function to update user/GateKeeper tables for existing members
function gatekeeper_update_existing_members() {
    global $wpdb;

    // Define the missing table name
    $user_role_relationships_table = $wpdb->prefix . 'gatekeeper_user_role_relationships';

    // Define the table names
    $invite_keys_table = $wpdb->prefix . 'gatekeeper_invite_keys';

    // Get the ID of the 'subscriber' role or create it if it doesn't exist
    $subscriber_role_id = gatekeeper_get_or_create_role_id('subscriber', 'Default role for members');

    // Get the admin user ID
    $admin_user = get_user_by('role', 'administrator');
    $admin_user_id = ($admin_user) ? $admin_user->ID : 0; // Check if the admin user exists

    // Get all user IDs
    $user_ids = $wpdb->get_col("SELECT ID FROM $wpdb->users");

    foreach ($user_ids as $user_id) {
        // Check if the user already has a 'subscriber' role
        $existing_role = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT role_id FROM $user_role_relationships_table WHERE user_id = %d",
                $user_id
            )
        );

        if (!$existing_role) {
            // If the user doesn't have a 'subscriber' role, assign it
            $wpdb->insert(
                $user_role_relationships_table,
                array(
                    'user_id' => $user_id,
                    'role_id' => $subscriber_role_id,
                )
            );
        }

        // Generate and assign invitation keys if the user has fewer than the limit
        $existing_invitation_count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM $invite_keys_table WHERE invitee = %d",
                $user_id
            )
        );

        $invitation_limit = get_option('gatekeeper_invitation_limit', 5);

        if ($existing_invitation_count < $invitation_limit) {
            $invitations_to_generate = $invitation_limit - $existing_invitation_count;

            for ($i = 0; $i < $invitations_to_generate; $i++) {
                $invite_key = gatekeeper_generate_invite_key();
                $expiry_date = date('Y-m-d H:i:s', strtotime('+90 days'));

                $wpdb->insert(
                    $invite_keys_table,
                    array(
                        'invite_key' => $invite_key,
                        'role' => 'subscriber',
                        'inviter' => $admin_user_id, // Use the admin user ID if available
                        'invitee' => $user_id,
                        'usage_limit' => 1,
                        'is_expiry' => 1,
                        'expiry_date' => $expiry_date,
                        'status' => 'pending',
                        'accepted' => 0,
                        'accepted_at' => 0,
                    )
                );
            }
        }
    }
}

// Helper function to get or create a role ID
function gatekeeper_get_or_create_role_id($role_name, $description) {
    global $wpdb;
    
    $role_id = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}gatekeeper_user_roles WHERE role_name = %s",
            $role_name
        )
    );

    if (!$role_id) {
        $wpdb->insert(
            $wpdb->prefix . 'gatekeeper_user_roles',
            array(
                'role_name' => $role_name,
                'description' => $description,
            )
        );
        $role_id = $wpdb->insert_id;
    }

    return $role_id;
}

// Function to remove plugin options during deactivation
function gatekeeper_remove_options() {
    $option_names = array(
        'gatekeeper_enable_feature',
        'gatekeeper_default_role',
        'gatekeeper_invitation_limit',
        // Add more option names as needed
    );

    foreach ($option_names as $option_name) {
        delete_option($option_name);
    }
}

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

// Function to validate the invite key during registration
function gatekeeper_validate_invite_key($errors, $sanitized_user_login, $user_email) {
    if (empty($_POST['invite_key'])) {
        $errors->add('invite_key_empty', __('Please enter an invite key.', 'gatekeeper'));
    } else {
        $invite_key = sanitize_text_field($_POST['invite_key']);
        
        // Add your validation logic here
        if (!gatekeeper_is_valid_invite_key($invite_key)) {
            $errors->add('invalid_invite_key', __('Invalid invite key.', 'gatekeeper'));
        }
    }

    return $errors;
}
add_filter('registration_errors', 'gatekeeper_validate_invite_key', 10, 3);

// Function to handle registration errors and display alerts
function gatekeeper_handle_registration_errors($user_id) {
    if (is_wp_error($user_id)) {
        $errors = $user_id->get_error_messages();

        foreach ($errors as $error) {
            echo '<div class="error">' . esc_html($error) . '</div>';
        }
    }
}
add_action('register_post', 'gatekeeper_handle_registration_errors', 10, 3);

// Function to check if the invite key is valid (You need to implement this)
function gatekeeper_is_valid_invite_key($invite_key) {
    global $wpdb;

    // Define the invite keys table
    $invite_keys_table = $wpdb->prefix . 'gatekeeper_invite_keys';

    // Check if the invite key exists in the database and is valid
    $query = $wpdb->prepare(
        "SELECT id FROM $invite_keys_table WHERE invite_key = %s AND status = 'pending' AND expiry_date >= NOW()",
        $invite_key
    );

    $result = $wpdb->get_var($query);

    // Return true if the invite key is found and valid, otherwise false
    return !empty($result);
}
