<?php
/**
 * Plugin Name: GateKeeper
 * Description: Advanced user access control plugin for WordPress.
 * Version: 0.01.01
 * Author: Zeus Eternal
 */

// Define plugin constants
define('GATEKEEPER_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('GATEKEEPER_PLUGIN_URL', plugin_dir_url(__FILE__));

// Register activation and deactivation hooks
register_activation_hook(__FILE__, 'gatekeeper_plugin_activated');
register_deactivation_hook(__FILE__, 'gatekeeper_plugin_deactivated');

// Add scripts and styles for SPA
function gatekeeper_enqueue_scripts() {
    wp_enqueue_script('gatekeeper-app', GATEKEEPER_PLUGIN_URL . 'js/app.js', array('jquery'), '1.0.0', true);
    wp_enqueue_style('gatekeeper-styles', GATEKEEPER_PLUGIN_URL . 'css/styles.css', array(), '1.0.0');
}
add_action('wp_enqueue_scripts', 'gatekeeper_enqueue_scripts');

/**
 * Activation Hook for GateKeeper Plugin
 */
function gatekeeper_plugin_activated() {
    gatekeeper_create_tables();
    gatekeeper_set_default_options();
}

/**
 * Deactivation Hook for GateKeeper Plugin
 */
function gatekeeper_plugin_deactivated() {
    gatekeeper_remove_tables();
    gatekeeper_remove_options();
}

/**
 * Function to create necessary database tables during plugin activation
 */
function gatekeeper_create_tables() {
    global $wpdb;

    // Define the plugin's table name prefix
    $table_prefix = 'gatekeeper_';

    // Define table names with the plugin's naming convention
    $invite_keys_table = $wpdb->prefix . $table_prefix . 'invite_keys';
    $user_role_relationships_table = $wpdb->prefix . $table_prefix . 'user_role_relationships';

    // Ensure we have access to the $wpdb->query method
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    // SQL query to create the invite keys table (Enhanced with foreign keys)
    $invite_keys_sql = "
    CREATE TABLE IF NOT EXISTS $invite_keys_table (
        id INT AUTO_INCREMENT PRIMARY KEY,
        invite_key VARCHAR(255) NOT NULL,
        role_id INT NOT NULL,
        inviter INT NOT NULL,
        invitee INT NOT NULL,
        usage_limit INT NOT NULL,
        is_expiry BOOL NOT NULL,
        expiry_date DATETIME NOT NULL,
        status VARCHAR(20) NOT NULL,
        accepted BOOL NOT NULL,
        accepted_at TINYINT(1) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (role_id) REFERENCES $user_roles_table(id),
        FOREIGN KEY (inviter) REFERENCES $wpdb->users(ID),
        FOREIGN KEY (invitee) REFERENCES $wpdb->users(ID)
    ) ENGINE=InnoDB;
    ";

    // SQL query to create the user role relationships table (Enhanced with foreign keys)
    $user_role_relationships_sql = "
    CREATE TABLE IF NOT EXISTS $user_role_relationships_table (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        role_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_user_role (user_id, role_id),
        FOREIGN KEY (user_id) REFERENCES $wpdb->users(ID),
        FOREIGN KEY (role_id) REFERENCES $user_roles_table(id)
    ) ENGINE=InnoDB;
    ";

    // Add similar queries for other tables if needed

    // Create the tables
    dbDelta($invite_keys_sql);
    dbDelta($user_role_relationships_sql);
    // Add similar dbDelta calls for other tables if needed
}

/**
 * Function to remove database tables during plugin deactivation
 */
function gatekeeper_remove_tables() {
    global $wpdb;

    // Define the plugin's table name prefix
    $table_prefix = 'gatekeeper_';

    // Define table names with the plugin's naming convention
    $invite_keys_table = $wpdb->prefix . $table_prefix . 'invite_keys';
    $user_role_relationships_table = $wpdb->prefix . $table_prefix . 'user_role_relationships';

    // SQL query to drop the invite keys table
    $wpdb->query("DROP TABLE IF EXISTS $invite_keys_table");
    $wpdb->query("DROP TABLE IF EXISTS $user_role_relationships_table");
    // Add similar queries for other tables if needed
}

// Shortcode handler for registration form
function gatekeeper_registration_shortcode() {
    ob_start(); // Start output buffering

    if (isset($_POST['register'])) {
        // Handle the registration form submission
        $registration_result = gatekeeper_process_registration();

        if (is_wp_error($registration_result)) {
            // Registration failed, display error messages
            foreach ($registration_result->get_error_messages() as $error_message) {
                echo '<p class="error">' . esc_html($error_message) . '</p>';
            }
        } elseif ($registration_result === true) {
            // Registration successful, display a success message
            echo '<p class="success">Registration successful! You can now log in.</p>';
        }
    } else {
        // Display the registration form
        ?>
        <form id="registration-form" method="post" action="">
            <label for="username">Username:</label>
            <input type="text" id="username" name="username" required>

            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required>

            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required>

            <!-- Add more form fields as needed for user registration -->

            <input type="submit" name="register" value="Register">
        </form>
        <?php
    }

    return ob_get_clean(); // Return the buffered output
}

// Shortcode handler for invitation form
function gatekeeper_send_invite_shortcode() {
    ob_start(); // Start output buffering

    if (isset($_POST['send_invite'])) {
        // Handle the invitation form submission
        $invite_result = gatekeeper_process_invite();

        if (is_wp_error($invite_result)) {
            // Invitation failed, display error messages
            foreach ($invite_result->get_error_messages() as $error_message) {
                echo '<p class="error">' . esc_html($error_message) . '</p>';
            }
        } elseif ($invite_result === true) {
            // Invitation sent successfully, display a success message
            echo '<p class="success">Invitation sent successfully!</p>';
        }
    } else {
        // Display the invitation form
        ?>
        <form id="invitation-form" method="post" action="">
            <label for="invitee_email">Invitee's Email:</label>
            <input type="email" id="invitee_email" name="invitee_email" required>

            <!-- Add more form fields as needed for your invitation form -->

            <input type="submit" name="send_invite" value="Send Invitation">
        </form>
        <?php
    }

    return ob_get_clean(); // Return the buffered output
}

/**
 * Function to set default options during plugin activation
 */
function gatekeeper_set_default_options() {
    // Define an array of default options and their default values
    $default_options = array(
        'gatekeeper_enable_feature' => 'yes',
        'gatekeeper_default_role' => 'subscriber',
        'gatekeeper_invitation_limit' => 5,
    );

    // Loop through the default options and add them if they don't exist
    foreach ($default_options as $option_name => $default_value) {
        // Check if the option already exists in the database
        if (get_option($option_name) === false) {
            // Option doesn't exist, so add it with the default value
            add_option($option_name, $default_value);
        }
    }
}

/**
 * Handle registration form submission
 */
function gatekeeper_process_registration() {
    // Check if the registration form data has been submitted
    if (isset($_POST['register'])) {
        $errors = new WP_Error();

        // Retrieve and sanitize user input from the registration form
        $username = sanitize_user($_POST['username']);
        $email = sanitize_email($_POST['email']);
        $password = sanitize_text_field($_POST['password']);

        // Validate username
        if (empty($username)) {
            $errors->add('username_required', 'Username is required.');
        }

        // Validate email
        if (empty($email)) {
            $errors->add('email_required', 'Email is required.');
        } elseif (!is_email($email)) {
            $errors->add('invalid_email', 'Invalid email address.');
        }

        // Validate password
        if (empty($password)) {
            $errors->add('password_required', 'Password is required.');
        }

        // Check if there are any errors
        if ($errors->has_errors()) {
            return $errors;
        }

        // Attempt to create a new user account using WordPress function wp_insert_user
        $user_data = array(
            'user_login' => $username,
            'user_email' => $email,
            'user_pass' => $password,
        );

        $user_id = wp_insert_user($user_data);

        if (!is_wp_error($user_id)) {
            // User registered successfully

            // Assign a role to the user (you can modify this based on your role logic)
            $role = 'subscriber'; // Change this to your desired role
            wp_update_user(array('ID' => $user_id, 'role' => $role));

            // Set authentication cookies to log in the user
            wp_set_auth_cookie($user_id, true);

            // Redirect the user to a success page (you can change the URL)
            wp_redirect('your-success-page-url');
            exit;
        } else {
            // Registration failed, return an error message
            return $user_id;
        }
    }

    // If the form hasn't been submitted yet, you can return an empty string here.
    return '';
}

/**
 * Function to process invitation form submission
 */
function gatekeeper_process_invite() {
    // Check if the invitation form data has been submitted
    if (isset($_POST['send_invite'])) {
        $errors = new WP_Error();

        // Retrieve and sanitize user input from the invitation form
        $invitee_email = sanitize_email($_POST['invitee_email']);

        // Validate email
        if (empty($invitee_email)) {
            $errors->add('email_required', 'Email is required.');
        } elseif (!is_email($invitee_email)) {
            $errors->add('invalid_email', 'Invalid email address.');
        }

        // Check if there are any errors
        if ($errors->has_errors()) {
            return $errors;
        }

        // Process the invitation and send it to the specified email address
        $invitation_result = gatekeeper_send_invitation($invitee_email);

        if ($invitation_result === true) {
            // Invitation sent successfully

            // You can add additional logic here if needed

            return true;
        } else {
            // Invitation failed, return an error message
            $errors->add('invitation_failed', 'Failed to send invitation.');
            return $errors;
        }
    }

    // If the form hasn't been submitted yet, you can return an empty string here.
    return '';
}

/**
 * Function to send an invitation email
 */
function gatekeeper_send_invitation($invitee_email) {
    // Perform necessary checks and validations here
    // Send the invitation email to $invitee_email using WordPress email functions

    // Example:
    $subject = 'Invitation to join our site';
    $message = 'You have been invited to join our site. Click the link below to register:';
    $headers = 'From: Your Name <yourname@example.com>' . "\r\n";

    // Send the email
    $email_sent = wp_mail($invitee_email, $subject, $message, $headers);

    if ($email_sent) {
        // Email sent successfully
        return true;
    } else {
        // Email sending failed
        return false;
    }
}

// Shortcode for registration form
add_shortcode('gatekeeper_registration', 'gatekeeper_registration_shortcode');

// Shortcode for invitation form
add_shortcode('gatekeeper_send_invite', 'gatekeeper_send_invite_shortcode');
