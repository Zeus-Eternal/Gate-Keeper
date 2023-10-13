<?php
// Add the main settings page
function gatekeeper_add_settings_page() {
    add_menu_page(
        'GateKeeper Settings',
        'GateKeeper',
        'manage_options',
        'gatekeeper_settings',
        'gatekeeper_usage_activities_page', // Use the main landing page as the callback
        'dashicons-shield',
        30
    );

    // Add submenus
    add_submenu_page(
        'gatekeeper_settings',
        'General Settings',
        'General',
        'manage_options',
        'gatekeeper_general_settings',
        'gatekeeper_settings_general_page_callback'
    );

    add_submenu_page(
        'gatekeeper_settings',
        'Invite Keys Settings',
        'Invite Keys',
        'manage_options',
        'gatekeeper_invite_keys_settings',
        'gatekeeper_settings_invite_keys_page_callback'
    );

    add_submenu_page(
        'gatekeeper_settings',
        'Invited Users Settings',
        'Invited Users',
        'manage_options',
        'gatekeeper_invited_users_settings',
        'gatekeeper_settings_invited_users_page_callback'
    );

    add_submenu_page(
        'gatekeeper_settings',
        'Plugin Info Settings',
        'Plugin Info',
        'manage_options',
        'gatekeeper_plugin_info_settings',
        'gatekeeper_settings_plugin_info_page_callback'
    );

    add_submenu_page(
        'gatekeeper_settings',
        'Advanced Settings',
        'Advanced',
        'manage_options',
        'gatekeeper_advanced_settings',
        'gatekeeper_settings_advanced_page_callback'
    );

    add_submenu_page(
        'gatekeeper_settings',
        'Roles & Permissions Settings',
        'Roles & Permissions',
        'manage_options',
        'gatekeeper_roles_permissions_settings',
        'gatekeeper_settings_roles_permissions_page_callback'
    );
}

// Register the plugin settings
function gatekeeper_register_settings() {
    // Register settings for each page if needed
}

// Add actions for the settings pages
add_action('admin_menu', 'gatekeeper_add_settings_page');
add_action('admin_init', 'gatekeeper_register_settings');

    // Include code for the usage and activities page here
include_once(plugin_dir_path(__FILE__) . 'gatekeeper-activities.php');
    
    // Include code for the General Settings page here
include_once(plugin_dir_path(__FILE__) . 'gatekeeper-settings-general.php');

    // Include code for the Invite Keys Settings page here
include_once(plugin_dir_path(__FILE__) . 'gatekeeper-settings-invite-keys.php');

    // Include code for the Invited Users Settings page here
include_once(plugin_dir_path(__FILE__) . 'gatekeeper-settings-invited-users.php');

    // Include code for the Plugin Info Settings page here
include_once(plugin_dir_path(__FILE__) . 'gatekeeper-settings-plugin-info.php');

    // Include code for the Advanced Settings page here
include_once(plugin_dir_path(__FILE__) . 'gatekeeper-settings-advanced.php');

    // Include code for the Roles & Permissions Settings page here
include_once(plugin_dir_path(__FILE__) . 'gatekeeper-settings-roles-permissions.php');