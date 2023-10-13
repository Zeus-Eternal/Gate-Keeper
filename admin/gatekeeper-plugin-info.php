<?php
// Function to display plugin information
function gatekeeper_display_plugin_info() {
    $plugin_data = get_plugin_data(plugin_dir_path(__FILE__) . 'gatekeeper.php');

    $plugin_name = $plugin_data['Name'];
    $plugin_version = $plugin_data['Version'];
    $plugin_author = $plugin_data['Author'];
    $plugin_description = $plugin_data['Description'];

    ?>
    <div class="wrap">
        <h1>GateKeeper Plugin Info</h1>
        <h2><?php echo esc_html($plugin_name); ?></h2>
        <ul>
            <li>Version: <?php echo esc_html($plugin_version); ?></li>
            <li>Author: <?php echo esc_html($plugin_author); ?></li>
            <li>Description: <?php echo esc_html($plugin_description); ?></li>
        </ul>
        <h2>Additional Information</h2>
        <p>You can find more information, documentation, and support for this plugin on the following resources:</p>
        <ul>
            <li><a href="https://example.com/plugin-docs" target="_blank">Documentation</a></li>
            <li><a href="https://example.com/plugin-support" target="_blank">Support Forum</a></li>
        </ul>
    </div>
    <?php
}

// Add the "Plugin Info" section to the menu
function gatekeeper_settings_plugin_info_page() {
    ?>
    <div class="wrap">
        <?php gatekeeper_display_plugin_info(); ?>
    </div>
    <?php
}

// Hook to add the "Plugin Info" section to the menu
add_action('admin_menu', 'gatekeeper_add_plugin_info_settings_page');
