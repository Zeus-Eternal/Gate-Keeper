<?php

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
