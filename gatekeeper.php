<?php
/**
 * Plugin Name: GateKeeper
 * Description: A WordPress plugin for managing invite keys during user registration.
 * Version: 1.0.0
 * Author: Zeus Eternal
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

// Function to generate and assign invite keys to users with a specific role
function gatekeeper_generate_and_assign_invite_keys($user_id, $count = 5, $role = 'subscriber') {
    global $wpdb;

    // Define the table names for invite keys and invited users
    $invite_keys_table = $wpdb->prefix . 'gatekeeper_invite_keys';
    $invited_users_table = $wpdb->prefix . 'gatekeeper_invited_users';

    for ($i = 0; $i < $count; $i++) {
        $invite_key = gatekeeper_generate_invite_key();
        $inviter_id = get_current_user_id();  // Get the ID of the inviter

        // Get the default WordPress role for the user
        $default_role = get_option('default_role');
        $user_role = sanitize_text_field($role);

        // Ensure the selected role exists and is a valid WordPress role
        if (empty($user_role) || !in_array($user_role, wp_roles()->roles)) {
            $user_role = $default_role; // Use the default WordPress role
        }

        // Insert invite key into the database with the user's selected role and inviter's ID
        $insert_result = $wpdb->insert(
            $invite_keys_table,
            array(
                'invite_key' => $invite_key,
                'user_role' => $user_role,
                'invite_status' => 'Active',
                'mem_exp_date' => date('Y-m-d', strtotime('+30 days')), // 30 days from now
                'key_exp_date' => date('Y-m-d', strtotime('+3 months')), // 3 months from now
                'created_at' => current_time('mysql'),
                'user_id' => ($user_id !== 0) ? $user_id : 1, // Default to admin if user_id is 0
                'inviter_id' => $inviter_id,  // Store the ID of the inviter
            )
        );

        if ($insert_result === false) {
            // An error occurred while inserting data
            error_log("Error inserting invite key for user $user_id: " . $wpdb->last_error);
        }

        // Insert the relationship into the invited users table
        $wpdb->insert(
            $invited_users_table,
            array(
                'inviter_id' => $inviter_id,
                'invitee_id' => $user_id,
                'role' => $user_role,
                'mem_exp_date' => date('Y-m-d', strtotime('+30 days')), // 30 days from now
                'created_at' => current_time('mysql'),
            )
        );
    }
}

// Hook to generate and assign invite keys for new users
function gatekeeper_generate_invite_keys_for_new_users($user_id) {
    gatekeeper_generate_and_assign_invite_keys($user_id, 5);
}
add_action('user_register', 'gatekeeper_generate_invite_keys_for_new_users');

// Function to update invite keys for a user
function gatekeeper_update_invite_keys($user_id, $count = 5) {
    global $wpdb;

    // Define the table name for invite keys
    $invite_keys_table = $wpdb->prefix . 'gatekeeper_invite_keys';

    // Delete existing invite keys for the user
    $wpdb->delete(
        $invite_keys_table,
        array('user_id' => $user_id)
    );

    // Generate and insert new invite keys with the user's selected role
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
            user_role VARCHAR(255) NOT NULL,
            invite_status VARCHAR(255) NOT NULL,
            mem_exp_date DATE NOT NULL,
            key_exp_date DATE NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            user_id INT NOT NULL,
            inviter_id INT NOT NULL,
            UNIQUE (invite_key)
        ) ENGINE=InnoDB;
    ";

    // Create the invite keys table
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($invite_keys_sql);

    // Define the table name for invited users
    $invited_users_table = $wpdb->prefix . 'gatekeeper_invited_users';

    // Define SQL query for creating the invited users table
    $invited_users_sql = "
        CREATE TABLE IF NOT EXISTS $invited_users_table (
            id INT AUTO_INCREMENT PRIMARY KEY,
            inviter_id INT NOT NULL,
            invitee_id INT NOT NULL,
            role VARCHAR(255) NOT NULL,
            mem_exp_date DATE NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB;
    ";

    // Create the invited users table
    dbDelta($invited_users_sql);
}
register_activation_hook(__FILE__, 'gatekeeper_create_tables');

// Function to track user relationships when an invite key is used
function gatekeeper_track_user_relationships($user_id, $invite_key) {
    global $wpdb;

    // Get the inviter's user ID based on the invite key
    $inviter_id = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT inviter_id FROM " . $wpdb->prefix . "gatekeeper_invite_keys WHERE invite_key = %s",
            $invite_key
        )
    );

    if ($inviter_id) {
        // Add a user relationship record
        gatekeeper_add_user_relationship($inviter_id, $user_id);
    }
}

// Hook to track user relationships after user registration
function gatekeeper_track_user_relationships_after_registration($user_id) {
    if (isset($_POST['invite_key'])) {
        $invite_key = sanitize_text_field($_POST['invite_key']);
        gatekeeper_track_user_relationships($user_id, $invite_key);
    }
}
add_action('user_register', 'gatekeeper_track_user_relationships_after_registration');

// Function to check user permissions for viewing core content
function gatekeeper_check_core_content_permissions() {
    $current_user = wp_get_current_user();
    
    // Check if the user has the 'subscriber' role to view core content
    if (in_array('subscriber', $current_user->roles)) {
        // User has permission to view core content
        // You can implement specific actions here for core content
        echo "User is allowed to view core content: Categories, Posts, Pages<br>";
    }
}

// Function to check user permissions for viewing WooCommerce content
function gatekeeper_check_woocommerce_permissions() {
    $current_user = wp_get_current_user();
    
    // Check if the user has the 'customer' role to view WooCommerce content
    if (in_array('customer', $current_user->roles)) {
        // User has permission to view WooCommerce content
        // You can implement more specific checks based on your requirements
        
        // Example: Check if the user is allowed to view product categories
        if (gatekeeper_check_permissions($current_user->ID, 'WooCommerce', 'view_product_categories')) {
            // User can view product categories
            echo "User is allowed to view WooCommerce product categories.<br>";
        }
    }
}

// Function to check user permissions for viewing both core and WooCommerce content
function gatekeeper_check_content_permissions() {
    // Check if the user has permission to view core content
    gatekeeper_check_core_content_permissions();
    
    // Check if the user has permission to view WooCommerce content
    gatekeeper_check_woocommerce_permissions();
}

// Example usage: Call the gatekeeper_check_content_permissions function
// gatekeeper_check_content_permissions();

// Function to add user relationship data
function gatekeeper_add_user_relationship($inviter_id, $invitee_id) {
    global $wpdb;
    
    // Define the table name for user relationships
    $relationships_table = $wpdb->prefix . 'gatekeeper_invited_users';

    // Insert user relationship into the database
    $wpdb->insert(
        $relationships_table,
        array(
            'inviter_id' => $inviter_id,
            'invitee_id' => $invitee_id,
            'role' => 'Unassigned',
            'mem_exp_date' => date('Y-m-d', strtotime('+30 days')), // 30 days from now
            'created_at' => current_time('mysql'),
        )
    );
}

// Hook to assign invite keys to existing members with admin as the default inviter
function gatekeeper_assign_invite_keys_to_existing_members() {
    global $wpdb;
    
    // Get all existing users with the 'subscriber' role
    $existing_users = get_users(array('role' => 'subscriber'));

    // Default inviter ID (admin)
    $default_inviter_id = 1;

    foreach ($existing_users as $user) {
        // Generate and assign invite keys to existing users with the default inviter
        gatekeeper_generate_and_assign_invite_keys($user->ID, 5, 'subscriber');
        gatekeeper_add_user_relationship($default_inviter_id, $user->ID);
    }
}
add_action('init', 'gatekeeper_assign_invite_keys_to_existing_members');

// Add roles to the database and integrate with WP core roles
function gatekeeper_add_roles_to_db() {
    global $wpdb;
    
    // Define the table name for invite keys
    $invite_keys_table = $wpdb->prefix . 'gatekeeper_invite_keys';

    // Get all available WordPress roles
    $wp_roles = wp_roles();
    $roles = $wp_roles->roles;

    // Insert each role into the database if it doesn't exist
    foreach ($roles as $role_name => $role_data) {
        $wpdb->replace(
            $invite_keys_table,
            array(
                'user_role' => $role_name,
                'invite_status' => 'Unassigned',
                'mem_exp_date' => 'Unassigned',
                'key_exp_date' => 'Unassigned',
            ),
            array('%s', '%s', '%s', '%s')
        );
    }
}
add_action('init', 'gatekeeper_add_roles_to_db');

// Function to create necessary database tables for Invitees
function gatekeeper_create_invitees_table() {
    global $wpdb;

    // Define the table name for Invitees
    $invitees_table = $wpdb->prefix . 'gatekeeper_invitees';

    // Define SQL query for creating the Invitees table
    $invitees_sql = "
        CREATE TABLE IF NOT EXISTS $invitees_table (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            invite_key_id INT NOT NULL,
            keys_available INT DEFAULT 5, // Default to 5 keys
            FOREIGN KEY (user_id) REFERENCES " . $wpdb->prefix . "users(ID),
            FOREIGN KEY (invite_key_id) REFERENCES " . $wpdb->prefix . "gatekeeper_invite_keys(id)
        ) ENGINE=InnoDB;
    ";

    // Create the Invitees table
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($invitees_sql);
}
register_activation_hook(__FILE__, 'gatekeeper_create_invitees_table');

// Function to create necessary database tables for Permissions
function gatekeeper_create_permissions_table() {
    global $wpdb;

    // Define the table name for Permissions
    $permissions_table = $wpdb->prefix . 'gatekeeper_permissions';

    // Define SQL query for creating the Permissions table
    $permissions_sql = "
        CREATE TABLE IF NOT EXISTS $permissions_table (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            content_type VARCHAR(255) NOT NULL,
            permission_type VARCHAR(255) NOT NULL,
            allowed TINYINT(1) NOT NULL,
            FOREIGN KEY (user_id) REFERENCES " . $wpdb->prefix . "users(ID)
        ) ENGINE=InnoDB;
    ";

    // Create the Permissions table
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($permissions_sql);
}
register_activation_hook(__FILE__, 'gatekeeper_create_permissions_table');

// Function to check user permissions for viewing content
function gatekeeper_check_permissions($user_id, $content_type, $permission_type) {
    global $wpdb;
    
    $permissions_table = $wpdb->prefix . 'gatekeeper_permissions';

    $result = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT allowed FROM $permissions_table WHERE user_id = %d AND content_type = %s AND permission_type = %s",
            $user_id,
            $content_type,
            $permission_type
        )
    );

    return $result === '1';
}

// Check if a user is allowed to view a specific content
$user_id = get_current_user_id();
if (gatekeeper_check_permissions($user_id, 'WP', 'view_post')) {
    // Logic for when User is allowed to view posts
    // Implement specific actions here for post content
}

// Assign at least 50 keys by admin to the database
function gatekeeper_assign_initial_invite_keys() {
    for ($i = 0; $i < 50; $i++) {
        gatekeeper_generate_and_assign_invite_keys(0, 1, 'subscriber'); // Assign keys to admin (user_id = 0)
    }
}
add_action('init', 'gatekeeper_assign_initial_invite_keys');
?>
