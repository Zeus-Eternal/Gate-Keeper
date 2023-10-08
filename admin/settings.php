
<?php
// Add the main settings page
function gatekeeper_add_settings_page() {
    add_menu_page(
        'GateKeeper Settings',
        'General',
        'manage_options',
        'gatekeeper_settings_general',
        'gatekeeper_settings_general_page', // Callback function for the main settings page
        'dashicons-shield', // Icon
        30 // Menu position
    );

    add_submenu_page(
        'gatekeeper_settings',
        'Invite Keys Settings',
        'Invite Keys',
        'manage_options',
        'gatekeeper_settings_invite_keys',
        'gatekeeper_settings_invite_keys_page' // Callback function for the invite keys settings page
    );

    add_submenu_page(
        'gatekeeper_settings',
        'Invitees Settings',
        'Invitees',
        'manage_options',
        'gatekeeper_settings_invitees',
        'gatekeeper_settings_invitees_page' // Callback function for the invitees settings page
    );

    add_submenu_page(
        'gatekeeper_settings',
        'Roles & Permissions Settings',
        'Roles & Permissions',
        'manage_options',
        'gatekeeper_settings_roles_permissions',
        'gatekeeper_settings_roles_permissions_page' // Callback function for the roles & permissions settings page
    );

    add_submenu_page(
        'gatekeeper_settings',
        'Advanced Settings',
        'Advanced',
        'manage_options',
        'gatekeeper_settings_advanced',
        'gatekeeper_settings_advanced_page' // Callback function for the advanced settings page
    );
}

// Callback function for the general settings page
include_once(plugin_dir_path(__FILE__) . 'gatekeeper-settings-invite-keys.php');

// Callback function for the invite keys settings page
include_once(plugin_dir_path(__FILE__) . 'gatekeeper-settings-general.php');

// Callback function for the invitees settings page
function gatekeeper_settings_invitees_page() {
    // Invitees settings page content here
    echo '<div class="wrap">';
    echo '<h1>Invitees Settings</h1>';
    // Add your invitees settings page content here
    echo '</div>';
}

// Callback function for the roles & permissions settings page
function gatekeeper_settings_roles_permissions_page() {
    // Roles & permissions settings page content here
    echo '<div class="wrap">';
    echo '<h1>Roles & Permissions Settings</h1>';
    // Add your roles & permissions settings page content here
    echo '</div>';
}

// Callback function for the advanced settings page
function gatekeeper_settings_advanced_page() {
    // Advanced settings page content here
    echo '<div class="wrap">';
    echo '<h1>Advanced Settings</h1>';
    // Add your advanced settings page content here
    echo '</div>';
}

// Hook to add the settings pages
add_action('admin_menu', 'gatekeeper_add_settings_page');