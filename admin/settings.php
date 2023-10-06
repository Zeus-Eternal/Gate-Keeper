
<?php
// Enable/Disable GateKeeper Feature
function gatekeeper_settings() {
    register_setting('gatekeeper_general_settings', 'gatekeeper_enable_feature');
}

// Add the General Settings page to the admin menu
function gatekeeper_add_settings_page() {
    add_menu_page(
        'GateKeeper General Settings',
        'GateKeeper',
        'manage_options',
        'gatekeeper_general_settings',
        'gatekeeper_general_settings_page',
        'dashicons-shield', // Icon
        30 // Menu position
    );
}

add_action('admin_menu', 'gatekeeper_add_settings_page');

// Include fucntions
include_once(plugin_dir_path(__FILE__) . 'settings-invitation-management.php');
add_action('admin_menu', 'gatekeeper_add_invitation_management_page');

// Include fucntions
include_once(plugin_dir_path(__FILE__) . 'settings-general.php');
