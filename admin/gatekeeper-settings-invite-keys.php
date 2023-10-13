<?php
// Create a field to set the key length for invite keys
function gatekeeper_invite_key_length_field() {
    $key_length = get_option('gatekeeper_invite_key_length', 12); // Default key length
    ?>
    <input type="number" name="gatekeeper_invite_key_length" value="<?php echo esc_attr($key_length); ?>" min="6" />
    <label for="gatekeeper_invite_key_length">Invite Key Length</label>
    <?php
}

// Create a field to set the number of keys to generate
function gatekeeper_number_of_keys_field() {
    $num_keys = get_option('gatekeeper_number_of_keys', 10); // Default number of keys to generate
    ?>
    <input type="number" name="gatekeeper_number_of_keys" value="<?php echo esc_attr($num_keys); ?>" min="1" />
    <label for="gatekeeper_number_of_keys">Number of Invite Keys to Generate</label>
    <?php
}

// Add the fields to the "Invite Keys Settings" page
function gatekeeper_settings_invite_keys_page() {
    ?>
    <div class="wrap">
        <h1>GateKeeper Invite Keys Settings</h1>
        <form method="post" action="options.php">
            <?php settings_fields('gatekeeper_invite_keys_settings'); ?>
            <?php do_settings_sections('gatekeeper_invite_keys_settings'); ?>
            <?php gatekeeper_invite_key_length_field(); ?>
            <?php gatekeeper_number_of_keys_field(); ?>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

// Register the plugin settings for invite keys
function gatekeeper_register_invite_keys_settings() {
    register_setting('gatekeeper_invite_keys_settings', 'gatekeeper_invite_key_length');
    register_setting('gatekeeper_invite_keys_settings', 'gatekeeper_number_of_keys');
}

// Hook to add the settings sections and fields for invite keys
add_action('admin_init', 'gatekeeper_register_invite_keys_settings');

// Hook to add the "Invite Keys Settings" page to the menu
add_action('admin_menu', 'gatekeeper_add_invite_keys_settings_page');
