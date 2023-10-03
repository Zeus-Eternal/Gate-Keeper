<?php
// Activation Hook for GateKeeper Plugin
function gatekeeper_plugin_activated() {
    if (!current_user_can('activate_plugins')) {
        wp_die(__('You do not have sufficient permissions to activate this plugin.'));
    }

    if (version_compare(get_bloginfo('version'), '5.0', '<')) {
        wp_die(__('This plugin requires WordPress version 5.0 or higher. Please update your WordPress installation.'));
    }

    if (!gatekeeper_supports_innodb()) {
        wp_die(__('Your database engine does not support InnoDB, which is required for this plugin. Please switch to a compatible database.'));
    }

    if (!gatekeeper_create_tables()) {
        wp_die(__('An error occurred while creating the database tables for the plugin. Please check your database configuration and try again.'));
    }

    gatekeeper_set_default_options();
}

// Function to check if the database engine supports InnoDB (required for foreign keys)
function gatekeeper_supports_innodb() {
    global $wpdb;

    $database_engine = strtolower($wpdb->get_var("SHOW ENGINES"));

    return (strpos($database_engine, 'innodb') !== false);
}

// Function to create necessary database tables during plugin activation
function gatekeeper_create_tables() {
    // ... (This function was defined in the previous code and creates database tables)
}

// Function to set default options during plugin activation
function gatekeeper_set_default_options() {
    $default_options = array(
        'gatekeeper_enable_feature' => 'yes',
        'gatekeeper_default_role' => 'subscriber',
        'gatekeeper_invitation_limit' => 5,
    );

    foreach ($default_options as $option_name => $default_value) {
        if (get_option($option_name) === false) {
            add_option($option_name, $default_value);
        }
    }
}
