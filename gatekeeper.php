<?php
/**
 * Plugin Name: GateKeeper
 * Description: A WordPress plugin for managing invite keys and temporary access rights during user registration.
 * Version: 2.0.0
 * Author: John Doe
 */

// Include WordPress database access
global $wpdb;

// GateKeeper Class
class GateKeeper {
    private $wpdb;
    
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;

        // Create necessary database tables and initialize plugin settings on activation
        register_activation_hook(__FILE__, array($this, 'activate_plugin'));
        
        // Remove database tables and perform cleanup on deactivation
        register_deactivation_hook(__FILE__, array($this, 'deactivate_plugin'));
    }

    // Function to generate a random invite key
    public function generate_invite_key() {
        return wp_generate_password(12, false);
    }

    // Function to create an invite key and store it in the database
    public function create_invite_key($invite_key, $inviter_id, $invitee_id, $inviter_role, $invitee_role) {
        $table_name = $this->wpdb->prefix . 'gatekeeper_invite_keys';

        $data_inviter = array(
            'invite_key' => sanitize_text_field($invite_key),
            'inviter_id' => intval($inviter_id),
            'invitee_id' => intval($invitee_id),
            'inviter_role' => sanitize_text_field($inviter_role),
            'invitee_role' => sanitize_text_field($invitee_role),
            'invite_status' => 'Active',
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

        $this->wpdb->insert($table_name, $data_inviter, $format);
    }

    // Function to store the user role associated with an invite key
    public function store_user_role($invite_key, $user_role) {
        $table_name = $this->wpdb->prefix . 'gatekeeper_invited_users';

        $data = array(
            'invite_key' => sanitize_text_field($invite_key),
            'user_role' => sanitize_text_field($user_role),
        );

        $format = array(
            '%s',
            '%s',
        );

        $this->wpdb->insert($table_name, $data, $format);
    }

    // Function to insert an invited user into the database
    public function insert_invited_user($invitation_details, $user_id) {
        $table_name = $this->wpdb->prefix . 'gatekeeper_invited_users';

        $data = array(
            'invite_key' => sanitize_text_field($invitation_details->invite_key),
            'invite_status' => 'Accepted',
            'inviter_id' => $this->get_inviter_id($invitation_details->invite_key),
            'user_role' => $this->get_default_user_role(),
            'usage_limit' => 0,
        );

        $where = array(
            'invitee_id' => intval($user_id),
        );

        $this->wpdb->update($table_name, $data, $where);
    }

    // Function to validate an invite key and return invitation details if valid
    public function validate_invite_key($invite_key) {
        $table_name = $this->wpdb->prefix . 'gatekeeper_invite_keys';

        $sql = $this->wpdb->prepare(
            "SELECT * FROM $table_name WHERE invite_key = %s AND invite_status = 'Active'",
            sanitize_text_field($invite_key)
        );

        $invitation_details = $this->wpdb->get_row($sql);

        if ($invitation_details) {
            return $invitation_details;
        }

        return false;
    }

    // Function to get the inviter's ID from an invite key
    public function get_inviter_id($invite_key) {
        $table_name = $this->wpdb->prefix . 'gatekeeper_invite_keys';

        $sql = $this->wpdb->prepare(
            "SELECT inviter_id FROM $table_name WHERE invite_key = %s",
            sanitize_text_field($invite_key)
        );

        $inviter_id = $this->wpdb->get_var($sql);

        if ($inviter_id) {
            return intval($inviter_id);
        }

        return false;
    }

    // Function to get the default user role for invited users
    public function get_default_user_role() {
        // You can customize this function to return the desired default role.
        return 'subscriber';
    }

    // Function to create the plugin's database tables
    public function create_tables() {
        $charset_collate = $this->wpdb->get_charset_collate();
        $table_name_keys = $this->wpdb->prefix . 'gatekeeper_invite_keys';
        $table_name_users = $this->wpdb->prefix . 'gatekeeper_invited_users';
        $table_name_access_logs = $this->wpdb->prefix . 'gatekeeper_access_logs';
        $table_name_options = $this->wpdb->prefix . 'gatekeeper_options';

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
    }

    // Function to remove the plugin's database tables upon deactivation
    public function deactivate_plugin() {
        $table_name_keys = $this->wpdb->prefix . 'gatekeeper_invite_keys';
        $table_name_users = $this->wpdb->prefix . 'gatekeeper_invited_users';
        $table_name_access_logs = $this->wpdb->prefix . 'gatekeeper_access_logs';
        $table_name_options = $this->wpdb->prefix . 'gatekeeper_options';

        $this->wpdb->query("DROP TABLE IF EXISTS $table_name_keys");
        $this->wpdb->query("DROP TABLE IF EXISTS $table_name_users");
        $this->wpdb->query("DROP TABLE IF EXISTS $table_name_access_logs");
        $this->wpdb->query("DROP TABLE IF EXISTS $table_name_options");
    }

    // Function to populate the gatekeeper_access_logs table with dummy content
    public function populate_access_logs() {
        $table_name = $this->wpdb->prefix . 'gatekeeper_access_logs';

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
            $this->wpdb->insert($table_name, $data);
        }
    }

    // Function to generate and populate the gatekeeper_invited_users table with existing users (excluding the inviter)
    public function populate_invited_users() {
        $table_name = $this->wpdb->prefix . 'gatekeeper_invited_users';

        // Get existing users (excluding the inviter with ID 1)
        $existing_users = get_users(array(
            'exclude' => array(1), // Exclude the inviter with ID 1
        ));

        // Dummy data
        $dummy_data = array();

        foreach ($existing_users as $user) {
            $invite_key = $this->generate_invite_key();
            $dummy_data[] = array(
                'invite_key' => sanitize_text_field($invite_key),
                'invite_status' => 'Active', // You may set this to 'Pending' initially
                'inviter_id' => 1, // Assuming the inviter is always user ID 1
                'invitee_id' => $user->ID,
                'user_role' => 'Subscriber', // Customize the default role as needed
                'usage_limit' => 0,
            );
        }

        foreach ($dummy_data as $data) {
            $this->wpdb->insert($table_name, $data);
        }
    }

    // Function to generate and populate the gatekeeper_invite_keys table with a specified number of keys from the inviter
    public function generate_and_populate_invite_keys($count) {
        $table_name = $this->wpdb->prefix . 'gatekeeper_invite_keys';

        $inviter_id = 1; // Assuming the inviter is always user ID 1

        for ($i = 0; $i < $count; $i++) {
            $invite_key = $this->generate_invite_key();

            $data_inviter = array(
                'invite_key' => sanitize_text_field($invite_key),
                'inviter_id' => intval($inviter_id),
                'invitee_id' => 0, // Initial value for invitee ID
                'inviter_role' => 'Administrator', // Customize the inviter's role as needed
                'invitee_role' => '', // Initially empty, to be filled when the key is used
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

            $this->wpdb->insert($table_name, $data_inviter, $format);
        }
    }

    // Function to generate and populate the gatekeeper_options table with actual data
    public function generate_and_populate_options() {
        $table_name = $this->wpdb->prefix . 'gatekeeper_options';

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

            $this->wpdb->insert($table_name, $data, $format);
        }
    }

    // Function to run when the plugin is activated
    public function activate_plugin() {
        // Create necessary database tables and initialize plugin settings.
        $this->create_tables();

        // Populate the gatekeeper_access_logs table with dummy content
        $this->populate_access_logs();

        // Populate the gatekeeper_invited_users table with existing users (excluding the inviter)
        $this->populate_invited_users();

        // Generate and populate the gatekeeper_invite_keys table with 20 random keys from the inviter
        $this->generate_and_populate_invite_keys(20);

        // Generate and populate the gatekeeper_options table with actual data
        $this->generate_and_populate_options();

        // Auto-invite all WordPress administrators as GateKeeper (Super User)
        $this->auto_invite_admins_as_gatekeepers();
    }

    // Function to auto-invite all WordPress administrators as GateKeeper (Super User)
    private function auto_invite_admins_as_gatekeepers() {
        $admins = get_users(array('role' => 'administrator'));
        foreach ($admins as $admin) {
            $invite_key = $this->generate_invite_key();
            $this->create_invite_key($invite_key, 1, $admin->ID, 'GateKeeper', 'GateKeeper');
            $this->store_user_role($invite_key, 'GateKeeper');
        }
    }
}

// Instantiate the GateKeeper class
$gatekeeper = new GateKeeper();
