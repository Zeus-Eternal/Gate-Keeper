<?php
// Define plugin constants
define('GATEKEEPER_TABLE_KEYS', $wpdb->prefix . 'gatekeeper_invite_keys');
define('GATEKEEPER_TABLE_USERS', $wpdb->prefix . 'gatekeeper_invited_users');
define('GATEKEEPER_TABLE_ACCESS_LOGS', $wpdb->prefix . 'gatekeeper_access_logs');
define('GATEKEEPER_TABLE_OPTIONS', $wpdb->prefix . 'gatekeeper_options');

// Add plugin actions and filters
add_action('init', 'gatekeeper_process_invitation');
add_action('user_register', 'gatekeeper_register_user');
add_action('woocommerce_registration_redirect', 'gatekeeper_redirect_after_registration', 10, 1);
add_shortcode('gatekeeper_invitation_link', 'gatekeeper_invitation_link_shortcode');
add_action('admin_menu', 'gatekeeper_menu');
add_action('admin_init', 'gatekeeper_register_settings');
add_action('wp_ajax_gatekeeper_generate_invite_key', 'gatekeeper_ajax_generate_invite_key');
add_action('wp_ajax_gatekeeper_delete_invite_key', 'gatekeeper_ajax_delete_invite_key');
add_action('wp_ajax_gatekeeper_reset_invite_key', 'gatekeeper_ajax_reset_invite_key');
add_action('wp_ajax_gatekeeper_display_available_keys', 'gatekeeper_ajax_display_available_keys');
add_action('wp_ajax_gatekeeper_display_invited_users', 'gatekeeper_ajax_display_invited_users');
add_action('wp_ajax_gatekeeper_reset_invite_limit', 'gatekeeper_ajax_reset_invite_limit');
add_action('wp_ajax_gatekeeper_reset_activation_limit', 'gatekeeper_ajax_reset_activation_limit');
add_action('wp_ajax_gatekeeper_generate_activation_key', 'gatekeeper_ajax_generate_activation_key');
add_action('wp_ajax_gatekeeper_delete_activation_key', 'gatekeeper_ajax_delete_activation_key');
add_action('wp_ajax_gatekeeper_display_access_logs', 'gatekeeper_ajax_display_access_logs');
add_action('wp_ajax_gatekeeper_reset_access_limit', 'gatekeeper_ajax_reset_access_limit');

// Register plugin settings
function gatekeeper_register_settings() {
    register_setting('gatekeeper-settings', 'gatekeeper_max_invites', 'intval');
    register_setting('gatekeeper-settings', 'gatekeeper_activation_limit', 'intval');
}

// Plugin dashboard page
function gatekeeper_dashboard() {
    ?>
    <div class="wrap">
        <h1>Gatekeeper Dashboard</h1>
        <p>Welcome to the Gatekeeper plugin dashboard.</p>
    </div>
    <?php
}

// Plugin invite keys page
function gatekeeper_invite_keys() {
    ?>
    <div class="wrap">
        <h1>Gatekeeper Invite Keys</h1>
        <p>Manage invite keys here.</p>
    </div>
    <?php
}

// Plugin activation keys page
function gatekeeper_activation_keys() {
    ?>
    <div class="wrap">
        <h1>Gatekeeper Activation Keys</h1>
        <p>Manage activation keys here.</p>
    </div>
    <?php
}

// Plugin access logs page
function gatekeeper_access_logs() {
    ?>
    <div class="wrap">
        <h1>Gatekeeper Access Logs</h1>
        <p>View access logs here.</p>
    </div>
    <?php
}

// Plugin settings page
function gatekeeper_settings() {
    ?>
    <div class="wrap">
        <h1>Gatekeeper Settings</h1>
        <form method="post" action="options.php">
            <?php settings_fields('gatekeeper-settings'); ?>
            <?php do_settings_sections('gatekeeper-settings'); ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Max Invites per User</th>
                    <td>
                        <input type="number" name="gatekeeper_max_invites" value="<?php echo esc_attr(get_option('gatekeeper_max_invites')); ?>" />
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Activation Limit</th>
                    <td>
                        <input type="number" name="gatekeeper_activation_limit" value="<?php echo esc_attr(get_option('gatekeeper_activation_limit')); ?>" />
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

// AJAX functions
function gatekeeper_ajax_generate_invite_key() {
    check_admin_referer('gatekeeper_generate_invite_key_nonce', 'security');
    $invite_key = gatekeeper_generate_invite_key(5);
    echo json_encode(array('invite_key' => $invite_key));
    wp_die();
}

function gatekeeper_ajax_delete_invite_key() {
    check_admin_referer('gatekeeper_delete_invite_key_nonce', 'security');
    if (isset($_POST['invite_key'])) {
        $invite_key = sanitize_text_field($_POST['invite_key']);
        $result = gatekeeper_delete_invite_key($invite_key);
        if ($result) {
            echo json_encode(array('success' => true));
        } else {
            echo json_encode(array('success' => false, 'message' => 'Error deleting invite key.'));
        }
    } else {
        echo json_encode(array('success' => false, 'message' => 'Invite key not provided.'));
    }
    wp_die();
}

function gatekeeper_ajax_reset_invite_key() {
    check_admin_referer('gatekeeper_reset_invite_key_nonce', 'security');
    if (isset($_POST['invite_key'])) {
        $invite_key = sanitize_text_field($_POST['invite_key']);
        $result = gatekeeper_reset_invite_key($invite_key);
        if ($result) {
            echo json_encode(array('success' => true));
        } else {
            echo json_encode(array('success' => false, 'message' => 'Error resetting invite key.'));
        }
    } else {
        echo json_encode(array('success' => false, 'message' => 'Invite key not provided.'));
    }
    wp_die();
}

function gatekeeper_ajax_display_available_keys() {
    check_admin_referer('gatekeeper_display_available_keys_nonce', 'security');
    $keys = gatekeeper_get_available_invite_keys();
    echo json_encode($keys);
    wp_die();
}

function gatekeeper_ajax_display_invited_users() {
    check_admin_referer('gatekeeper_display_invited_users_nonce', 'security');
    $users = gatekeeper_get_invited_users();
    echo json_encode($users);
    wp_die();
}

function gatekeeper_ajax_reset_invite_limit() {
    check_admin_referer('gatekeeper_reset_invite_limit_nonce', 'security');
    if (isset($_POST['user_id'])) {
        $user_id = intval($_POST['user_id']);
        $result = gatekeeper_reset_invite_limit($user_id);
        if ($result) {
            echo json_encode(array('success' => true));
        } else {
            echo json_encode(array('success' => false, 'message' => 'Error resetting invite limit.'));
        }
    } else {
        echo json_encode(array('success' => false, 'message' => 'User ID not provided.'));
    }
    wp_die();
}

function gatekeeper_ajax_reset_activation_limit() {
    check_admin_referer('gatekeeper_reset_activation_limit_nonce', 'security');
    if (isset($_POST['user_id'])) {
        $user_id = intval($_POST['user_id']);
        $result = gatekeeper_reset_activation_limit($user_id);
        if ($result) {
            echo json_encode(array('success' => true));
        } else {
            echo json_encode(array('success' => false, 'message' => 'Error resetting activation limit.'));
        }
    } else {
        echo json_encode(array('success' => false, 'message' => 'User ID not provided.'));
    }
    wp_die();
}

function gatekeeper_ajax_generate_activation_key() {
    check_admin_referer('gatekeeper_generate_activation_key_nonce', 'security');
    $activation_key = gatekeeper_generate_activation_key(8);
    echo json_encode(array('activation_key' => $activation_key));
    wp_die();
}

function gatekeeper_ajax_delete_activation_key() {
    check_admin_referer('gatekeeper_delete_activation_key_nonce', 'security');
    if (isset($_POST['activation_key'])) {
        $activation_key = sanitize_text_field($_POST['activation_key']);
        $result = gatekeeper_delete_activation_key($activation_key);
        if ($result) {
            echo json_encode(array('success' => true));
        } else {
            echo json_encode(array('success' => false, 'message' => 'Error deleting activation key.'));
        }
    } else {
        echo json_encode(array('success' => false, 'message' => 'Activation key not provided.'));
    }
    wp_die();
}

function gatekeeper_ajax_display_access_logs() {
    check_admin_referer('gatekeeper_display_access_logs_nonce', 'security');
    $logs = gatekeeper_get_access_logs();
    echo json_encode($logs);
    wp_die();
}

function gatekeeper_ajax_reset_access_limit() {
    check_admin_referer('gatekeeper_reset_access_limit_nonce', 'security');
    if (isset($_POST['user_id'])) {
        $user_id = intval($_POST['user_id']);
        $result = gatekeeper_reset_access_limit($user_id);
        if ($result) {
            echo json_encode(array('success' => true));
        } else {
            echo json_encode(array('success' => false, 'message' => 'Error resetting access limit.'));
        }
    } else {
        echo json_encode(array('success' => false, 'message' => 'User ID not provided.'));
    }
    wp_die();
}

// Main processing function for user registration
function gatekeeper_process_invitation() {
    if (isset($_GET['invite_key'])) {
        $invite_key = sanitize_text_field($_GET['invite_key']);
        $user_id = get_current_user_id();
        if (gatekeeper_validate_invite_key($invite_key, $user_id)) {
            // Allow user registration
            return;
        } else {
            // Redirect to an error page or display an error message
            wp_die('Invalid or expired invitation key.');
        }
    }
}

// Function to register a user with an activation key
function gatekeeper_register_user($user_id) {
    if (isset($_POST['activation_key'])) {
        $activation_key = sanitize_text_field($_POST['activation_key']);
        if (gatekeeper_validate_activation_key($activation_key, $user_id)) {
            // User registration is valid, continue processing
            return;
        } else {
            // Invalid activation key, prevent user registration
            remove_action('user_register', 'gatekeeper_register_user'); // Remove the action to prevent a loop
            wp_delete_user($user_id); // Delete the invalid user
            // Redirect to an error page or display an error message
            wp_die('Invalid activation key.');
        }
    }
}

// Function to redirect users after registration
function gatekeeper_redirect_after_registration($redirect) {
    if (isset($_POST['activation_key'])) {
        // Redirect users to a custom page or URL after registration
        $redirect = home_url('/activation-success/');
    }
    return $redirect;
}

// Shortcode to generate an invitation link
function gatekeeper_invitation_link_shortcode($atts) {
    $user_id = get_current_user_id();
    $invite_key = gatekeeper_generate_invite_key(5);
    $link = add_query_arg('invite_key', $invite_key, home_url());
    return '<a href="' . esc_url($link) . '">Click here to accept the invitation</a>';
}

// Menu and submenu items
function gatekeeper_menu() {
    add_menu_page('Gatekeeper', 'Gatekeeper', 'manage_options', 'gatekeeper_dashboard', 'gatekeeper_dashboard', 'dashicons-shield');
    add_submenu_page('gatekeeper_dashboard', 'Dashboard', 'Dashboard', 'manage_options', 'gatekeeper_dashboard', 'gatekeeper_dashboard');
    add_submenu_page('gatekeeper_dashboard', 'Invite Keys', 'Invite Keys', 'manage_options', 'gatekeeper_invite_keys', 'gatekeeper_invite_keys');
    add_submenu_page('gatekeeper_dashboard', 'Activation Keys', 'Activation Keys', 'manage_options', 'gatekeeper_activation_keys', 'gatekeeper_activation_keys');
    add_submenu_page('gatekeeper_dashboard', 'Access Logs', 'Access Logs', 'manage_options', 'gatekeeper_access_logs', 'gatekeeper_access_logs');
    add_submenu_page('gatekeeper_dashboard', 'Settings', 'Settings', 'manage_options', 'gatekeeper_settings', 'gatekeeper_settings');
}