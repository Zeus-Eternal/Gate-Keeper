<?php
// Check if the form has been submitted
if (isset($_POST['submit'])) {
    
    // Update Require Invite Key for Registration setting
    update_option('gatekeeper_require_invite_key', isset($_POST['gatekeeper_require_invite_key']) ? '1' : '0');
    
    // Update Default User Role for Invitees setting
    update_option('gatekeeper_default_user_role', sanitize_text_field($_POST['gatekeeper_default_user_role']));
    
    // Update Keys Per Member (Inviter) setting
    update_option('gatekeeper_keys_per_member', intval($_POST['gatekeeper_keys_per_member']));
    
    // Update Key Length setting
    update_option('gatekeeper_key_length', intval($_POST['gatekeeper_key_length']));
    
    // Update Default Expiration for Invite Keys setting
    update_option('gatekeeper_default_expiration', intval($_POST['gatekeeper_default_expiration']));
    
    // Add success message
    add_settings_error('gatekeeper_general_settings', 'settings_updated', __('General settings updated', 'gatekeeper'), 'updated');
}

// Display the settings form
?>
<div class="wrap">
    <h2><?php _e('GateKeeper General Settings', 'gatekeeper'); ?></h2>
    
    <form method="post" action="">
        <?php settings_fields('gatekeeper_general_settings'); ?>
        <?php do_settings_sections('gatekeeper_general_settings'); ?>
        
        <table class="form-table">
            
            <!-- Require Invite Key for Registration Setting -->
            <tr valign="top">
                <th scope="row"><?php _e('Require Invite Key for Registration', 'gatekeeper'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="gatekeeper_require_invite_key" value="1" <?php checked(get_option('gatekeeper_require_invite_key', '0'), '1'); ?> />
                        <?php _e('Require invite key for registration', 'gatekeeper'); ?>
                    </label>
                    <p class="description"><?php _e('If enabled, users will need an invite key to register.', 'gatekeeper'); ?></p>
                </td>
            </tr>

            <!-- Default User Role for Invitees Setting -->
            <tr valign="top">
                <th scope="row"><?php _e('Default User Role for Invitees', 'gatekeeper'); ?></th>
                <td>
                    <select name="gatekeeper_default_user_role">
                        <?php
                        $selected_role = get_option('gatekeeper_default_user_role', 'subscriber');
                        $user_roles = wp_roles()->roles;
                        
                        foreach ($user_roles as $role => $data) {
                            echo '<option value="' . esc_attr($role) . '" ' . selected($selected_role, $role, false) . '>' . esc_html($data['name']) . '</option>';
                        }
                        ?>
                    </select>
                    <p class="description"><?php _e('Select the default WordPress user role for invitees.', 'gatekeeper'); ?></p>
                </td>
            </tr>
            
            <!-- Keys Per Member (Inviter) Setting -->
            <tr valign="top">
                <th scope="row"><?php _e('Keys Per Member (Inviter)', 'gatekeeper'); ?></th>
                <td>
                    <input type="number" name="gatekeeper_keys_per_member" value="<?php echo esc_attr(get_option('gatekeeper_keys_per_member', 5)); ?>" />
                    <p class="description"><?php _e('Enter the maximum number of invite keys allowed per member (inviter).', 'gatekeeper'); ?></p>
                </td>
            </tr>
            
            <!-- Key Length Setting -->
            <tr valign="top">
                <th scope="row"><?php _e('Key Length', 'gatekeeper'); ?></th>
                <td>
                    <input type="number" name="gatekeeper_key_length" value="<?php echo esc_attr(get_option('gatekeeper_key_length', 10)); ?>" />
                    <p class="description"><?php _e('Enter the desired length for invite keys.', 'gatekeeper'); ?></p>
                </td>
            </tr>
            
            <!-- Default Expiration for Invite Keys Setting -->
            <tr valign="top">
                <th scope="row"><?php _e('Default Expiration for Invite Keys (in days)', 'gatekeeper'); ?></th>
                <td>
                    <input type="number" name="gatekeeper_default_expiration" value="<?php echo esc_attr(get_option('gatekeeper_default_expiration', 30)); ?>" />
                    <p class="description"><?php _e('Enter the default expiration period for invite keys (in days).', 'gatekeeper'); ?></p>
                </td>
            </tr>
        </table>
        
        <?php submit_button(); ?>
    </form>
</div
