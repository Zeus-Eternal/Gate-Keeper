<?php
/**
 * Plugin Name: GateKeeper
 * Description: A WordPress plugin for managing invite keys and temporary access rights during user registration.
 * Version: 0.8.0
 * Author: Zeus Eternal
 */

// Include WordPress database access
global $wpdb;

// Include registration integration
//include_once(plugin_dir_path(__FILE__) . '/includes/gatekeeper-registration.php');

// Include decoder
//include_once(plugin_dir_path(__FILE__) . '/helpers/gatekeeper-decoder.php');

// Include invite key validation
//include_once(plugin_dir_path(__FILE__) . '/helpers/gatekeeper-invite-key-validation.php');

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

// // Function to create an invite key and store it in the database
function gatekeeper_create_invite_key($invite_key, $inviter_id, $invitee_id, $inviter_role, $invitee_role) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'gatekeeper_invite_keys';

    $data = array(
        'invite_key' => sanitize_text_field($invite_key),
        'inviter_id' => intval($inviter_id),
        'invitee_id' => intval($invitee_id),
        'inviter_role' => sanitize_text_field($inviter_role),
        'invitee_role' => sanitize_text_field($invitee_role),
        'invite_status' => 'Available', // Default status
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

    $wpdb->insert($table_name, $data, $format);
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


/////////++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++

// // Shortcode to display invite keys in a table with pagination and additional information
function gatekeeper_display_keys_table($atts) {
    global $wpdb;
    $table_name_keys = $wpdb->prefix . 'gatekeeper_invite_keys';

    // Define default attributes and parse user attributes
    $atts = shortcode_atts(array(
        'per_page' => 10, // Number of keys per page
    ), $atts);

    // Validate and sanitize per_page attribute
    $per_page = absint($atts['per_page']);

    if ($per_page <= 0) {
        $per_page = 10; // Use a default value if the provided value is invalid
    }

    // Get the current page from the URL query parameter
    $current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;

    // Calculate the offset for pagination
    $offset = ($current_page - 1) * $per_page;

    // Query to retrieve invite keys with pagination
    $query = $wpdb->prepare(
        "SELECT invite_key, inviter_id, inviter_role, invitee_role, invite_status, key_exp_acc, key_exp_date, created_at 
        FROM $table_name_keys
        ORDER BY created_at DESC
        LIMIT %d OFFSET %d",
        $per_page,
        $offset
    );

    $keys = $wpdb->get_results($query, OBJECT);

    ob_start(); // Start output buffering

    if (!empty($keys)) {
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead>';
        echo '<tr>';
        echo '<th>Invite Key</th>';
        echo '<th>Inviter</th>';
        echo '<th>Inviter Role</th>';
        echo '<th>Assigned Role</th>';
        echo '<th>Key Status</th>';
        echo '<th>Invite Duration</th>';
        echo '<th>Key Expiration</th>';
        echo '<th>Created At</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';

        foreach ($keys as $key) {
            // Validate and sanitize the invite key and other fields
            $invite_key = sanitize_text_field($key->invite_key);
            $inviter_id = intval($key->inviter_id);
            $inviter_role = sanitize_text_field($key->inviter_role);
            $invitee_role = sanitize_text_field($key->invitee_role);
            $invite_status = sanitize_text_field($key->invite_status);
            $key_exp_acc = sanitize_text_field($key->key_exp_acc);
            $key_exp_date = sanitize_text_field($key->key_exp_date);
            $created_at = sanitize_text_field($key->created_at);

            echo '<tr>';
            echo '<td>' . esc_html($invite_key) . '</td>';
            echo '<td>' . esc_html(get_user_by('ID', $inviter_id)->display_name) . '</td>';
            echo '<td>' . esc_html($inviter_role) . '</td>';
            echo '<td>' . esc_html($invitee_role) . '</td>';
            echo '<td>' . esc_html($invite_status) . '</td>';
            echo '<td>' . esc_html($key_exp_acc) . '</td>';
            echo '<td>' . esc_html($key_exp_date) . '</td>';
            echo '<td>' . esc_html($created_at) . '</td>';
            echo '</tr>';
        }

        echo '</tbody>';
        echo '</table>';

        // Pagination
        $total_keys = $wpdb->query("SELECT COUNT(*) FROM $table_name_keys");
        $total_pages = ceil($total_keys / $per_page);

        echo '<div class="tablenav">';
        echo '<div class="tablenav-pages">';
        echo paginate_links(array(
            'base' => add_query_arg('page', '%#%'),
            'format' => '',
            'prev_text' => '&laquo;',
            'next_text' => '&raquo;',
            'total' => $total_pages,
            'current' => $current_page,
        ));
        echo '</div>';
        echo '</div>';
    } else {
        echo '<p>No invite keys found.</p>';
    }

    $output = ob_get_clean(); // Get the buffered output
    return $output;
}

// Register the shortcode
add_shortcode('gatekeeper_invite_keys', 'gatekeeper_display_keys_table');

/////////++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++

// // Add a custom menu item to the admin menu
function gatekeeper_admin_menu() {
    add_menu_page(
        'GateKeeper',
        'GateKeeper',
        'manage_options',
        'gatekeeper-settings',
        'gatekeeper_settings_page'
    );
}

// // Callback function for the settings page
function gatekeeper_settings_page() {
    ?>
    <div class="wrap">
        <h2>GateKeeper Settings</h2>
        <p>Configure the settings for the GateKeeper plugin here.</p>
        <!-- Add your settings options and form here -->
    </div>
    <?php
}

// // Hook to add the custom menu item
add_action('admin_menu', 'gatekeeper_admin_menu');

/////////++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++

// // Function to handle plugin options
function gatekeeper_handle_options() {
    if (isset($_POST['gatekeeper_save_settings'])) {
        // Handle the form submission and save options here
        // Make sure to validate and sanitize user input
    }
}

// // Hook to handle plugin options
add_action('admin_init', 'gatekeeper_handle_options');

/////////++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++

// // Function to add custom settings fields to the plugin settings page
function gatekeeper_settings_fields() {
    // Add your custom settings fields here
}

// // Hook to add custom settings fields
add_action('admin_init', 'gatekeeper_settings_fields');

/////////++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++

// // Function to enqueue styles and scripts for the plugin
function gatekeeper_enqueue_assets() {
    // Enqueue your styles and scripts here
}

// // Hook to enqueue styles and scripts
add_action('admin_enqueue_scripts', 'gatekeeper_enqueue_assets');

/////////++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++

// // Function to add links to the plugin's action links on the Plugins page
function gatekeeper_plugin_action_links($links) {
    $settings_link = '<a href="admin.php?page=gatekeeper-settings">Settings</a>';
    array_unshift($links, $settings_link);
    return $links;
}

// // Hook to add action links
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'gatekeeper_plugin_action_links');

/////////++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++

// // Function to process invite key submission via AJAX
function gatekeeper_process_invite_key_submission() {
    // Check for AJAX request and nonce verification
    if (isset($_POST['action']) && $_POST['action'] === 'gatekeeper_submit_invite_key' && check_ajax_referer('gatekeeper_nonce', 'security')) {
        // Handle the invite key submission and validation here

        // Example response:
        $response = array(
            'success' => true, // Set to false if validation fails
            'message' => 'Invite key successfully validated.', // Error message if validation fails
        );

        // Send the JSON response
        wp_send_json($response);
    }
}

// // Hook to process invite key submission via AJAX
add_action('wp_ajax_gatekeeper_submit_invite_key', 'gatekeeper_process_invite_key_submission');
add_action('wp_ajax_nopriv_gatekeeper_submit_invite_key', 'gatekeeper_process_invite_key_submission');

/////////++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++

// // Function to display the invite key submission form
function gatekeeper_display_invite_key_form() {
    ob_start(); // Start output buffering
    ?>
    <div id="gatekeeper-invite-key-form">
        <h3>Submit Invite Key</h3>
        <form id="gatekeeper-invite-key-submit" action="<?php echo admin_url('admin-ajax.php'); ?>" method="post">
            <input type="text" name="invite_key" id="invite_key" placeholder="Enter your invite key" required>
            <?php wp_nonce_field('gatekeeper_nonce', 'security'); ?>
            <input type="hidden" name="action" value="gatekeeper_submit_invite_key">
            <input type="submit" value="Submit">
        </form>
        <div id="gatekeeper-invite-key-response"></div>
    </div>
    <script>
        jQuery(document).ready(function ($) {
            // Handle invite key submission via AJAX
            $('#gatekeeper-invite-key-submit').submit(function (e) {
                e.preventDefault();

                $.ajax({
                    type: 'POST',
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    data: $(this).serialize(),
                    success: function (response) {
                        if (response.success) {
                            // Invite key is valid
                            $('#gatekeeper-invite-key-response').html('<p class="success">' + response.message + '</p>');
                        } else {
                            // Invite key is invalid
                            $('#gatekeeper-invite-key-response').html('<p class="error">' + response.message + '</p>');
                        }
                    }
                });
            });
        });
    </script>
    <?php
    $output = ob_get_clean(); // Get the buffered output
    return $output;
}

// // Shortcode to display the invite key submission form
add_shortcode('gatekeeper_invite_key_form', 'gatekeeper_display_invite_key_form');

/////////++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++

// // Function to log user access to restricted content
function gatekeeper_log_access($user_id, $access_type, $access_key) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'gatekeeper_access_logs';

    $data = array(
        'user_id' => intval($user_id),
        'access_type' => sanitize_text_field($access_type),
        'access_key' => sanitize_text_field($access_key),
        'accessed_at' => current_time('mysql'),
    );

    $format = array(
        '%d',
        '%s',
        '%s',
        '%s',
    );

    $wpdb->insert($table_name, $data, $format);
}

// // Function to check if a user has access to a specific content based on their role
function gatekeeper_check_access($user_id, $access_type, $access_key) {
    // Add your access control logic here
    // Return true if the user has access, false otherwise

    // Example access control logic:
    $user = get_user_by('ID', $user_id);
    $allowed_roles = array('administrator', 'editor', 'contributor'); // Define allowed roles

    if (in_array($user->roles[0], $allowed_roles)) {
        // User has access if their role is in the allowed_roles array
        return true;
    }

    return false;
}

// // Hook to restrict access to content and log access
add_action('template_redirect', 'gatekeeper_restrict_access', 10, 3);

// Function to restrict access to content and log access
function gatekeeper_restrict_access($user_id, $access_type, $access_key) {
    if (!gatekeeper_check_access($user_id, $access_type, $access_key)) {
        // User does not have access
        // Redirect or display an access denied message here

        // Example redirect:
        wp_redirect(home_url('/access-denied/'));
        exit();
    }

    // User has access, log the access
    gatekeeper_log_access($user_id, $access_type, $access_key);
}


/////////++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++

// // Function to get the default user role for invitees
function gatekeeper_get_default_user_role() {
    // Return the default user role for invitees
    return 'Subscriber'; // Change this to the desired default role
}

// // Function to set the expiration date for an invite key
function gatekeeper_set_invite_key_expiration($invite_key, $expiration_date) {
    // Set the expiration date for the invite key
    // You can implement this logic using options, user meta, or a custom table
}

// // Function to get the expiration date for an invite key
function gatekeeper_get_invite_key_expiration($invite_key) {
    // Get the expiration date for the invite key
    // You can implement this logic using options, user meta, or a custom table
    return null; // Return the expiration date (null if not set)
}

/////////++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++

// // Function to validate an invite key before registration
function gatekeeper_validate_invite_key($invite_key) {
    // Check if the invite key is valid
    // You can implement this logic using options, user meta, or a custom table

    // Example validation logic:
    global $wpdb;
    $table_name_keys = $wpdb->prefix . 'gatekeeper_invite_keys';

    // Query to check if the invite key exists and is available
    $query = $wpdb->prepare(
        "SELECT invite_key, invite_status
        FROM $table_name_keys
        WHERE invite_key = %s AND invite_status = 'Available'",
        $invite_key
    );

    $result = $wpdb->get_row($query);

    if ($result) {
        // Invite key is valid
        return true;
    }

    // Invite key is invalid or already used
    return false;
}

// // Function to handle invite key validation during registration
function gatekeeper_registration_check($errors, $sanitized_user_login, $user_email) {
    if (isset($_POST['invite_key'])) {
        $invite_key = sanitize_text_field($_POST['invite_key']);

        // Validate the invite key
        if (!gatekeeper_validate_invite_key($invite_key)) {
            // Invalid invite key
            $errors->add('invalid_invite_key', __('Invalid invite key. Please enter a valid invite key.', 'gatekeeper'));
        }
    }

    return $errors;
}

/////////++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++

// // Function to check if a user has a specific role
function gatekeeper_user_has_role($user_id, $role) {
    $user = get_userdata($user_id);

    if ($user && in_array($role, $user->roles)) {
        return true;
    }

    return false;
}

// // Function to add a user to a specific role
function gatekeeper_add_user_to_role($user_id, $role) {
    $user = get_userdata($user_id);

    if ($user && !in_array($role, $user->roles)) {
        $user->add_role($role);
    }
}

// // Function to remove a user from a specific role
function gatekeeper_remove_user_from_role($user_id, $role) {
    $user = get_userdata($user_id);

    if ($user && in_array($role, $user->roles)) {
        $user->remove_role($role);
    }
}

// // Function to update a user's role
function gatekeeper_update_user_role($user_id, $new_role) {
    $user = get_userdata($user_id);

    if ($user) {
        $user->set_role($new_role);
    }
}

// // Function to retrieve all users with a specific role
function gatekeeper_get_users_with_role($role) {
    $users = get_users(array(
        'role' => $role,
    ));

    return $users;
}

// // Function to retrieve all user roles
function gatekeeper_get_all_user_roles() {
    $roles = wp_roles()->get_names();

    return $roles;
}

// // Function to add a custom role
function gatekeeper_add_custom_role($role_name, $role_display_name, $capabilities = array()) {
    add_role($role_name, $role_display_name, $capabilities);
}

// // Function to remove a custom role
function gatekeeper_remove_custom_role($role_name) {
    remove_role($role_name);
}

// // Function to add capabilities to a role
function gatekeeper_add_capabilities_to_role($role_name, $capabilities) {
    $role = get_role($role_name);

    if ($role) {
        foreach ($capabilities as $capability) {
            $role->add_cap($capability);
        }
    }
}

// // Function to remove capabilities from a role
function gatekeeper_remove_capabilities_from_role($role_name, $capabilities) {
    $role = get_role($role_name);

    if ($role) {
        foreach ($capabilities as $capability) {
            $role->remove_cap($capability);
        }
    }
}
