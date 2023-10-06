<?php

// admin-settings-general.php

// General Settings HTML form
function gatekeeper_general_settings_page() {
    // Check if the form has been submitted
    if (isset($_POST['gatekeeper_settings_submit'])) {
        // Handle form submissions and update options
        if (isset($_POST['gatekeeper_enable_feature'])) {
            update_option('gatekeeper_enable_feature', 'yes');
        } else {
            update_option('gatekeeper_enable_feature', 'no');
        }

        $default_role = sanitize_text_field($_POST['gatekeeper_default_role']);
        update_option('gatekeeper_default_role', $default_role);

        $invitation_limit = absint($_POST['gatekeeper_invitation_limit']);
        update_option('gatekeeper_invitation_limit', $invitation_limit);

        // Display a success message
        echo '<div class="updated"><p>Settings saved.</p></div>';
    }

    // Get current option values
    $enable_feature = get_option('gatekeeper_enable_feature', 'no');
    $default_role = get_option('gatekeeper_default_role', 'subscriber');
    $invitation_limit = get_option('gatekeeper_invitation_limit', 5);

    // Display the General Settings form
    ?>
    <div class="wrap">
        <h2>GateKeeper General Settings</h2>
        <form method="post" action="">
            <table class="form-table">
                <tr>
                    <th scope="row">Enable GateKeeper Feature</th>
                    <td>
                        <label>
                            <input type="checkbox" name="gatekeeper_enable_feature" <?php checked($enable_feature, 'yes'); ?>>
                            Enable GateKeeper
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Default User Role</th>
                    <td>
                        <select name="gatekeeper_default_role">
                            <?php
                            // Get all available user roles
                            $roles = wp_roles()->get_names();
                            foreach ($roles as $role => $name) {
                                echo '<option value="' . esc_attr($role) . '" ' . selected($default_role, $role, false) . '>' . esc_html($name) . '</option>';
                            }
                            ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Invitation Limit</th>
                    <td>
                        <input type="number" name="gatekeeper_invitation_limit" value="<?php echo esc_attr($invitation_limit); ?>" min="1">
                    </td>
                </tr>
            </table>
            <p class="submit">
                <input type="submit" name="gatekeeper_settings_submit" class="button-primary" value="Save Changes">
            </p>
        </form>
    </div>
    <?php
}
