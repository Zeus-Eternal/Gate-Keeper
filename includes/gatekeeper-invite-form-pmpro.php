<?php
// Hook to add invite key field to the PMPro registration form
add_action('pmpro_checkout_boxes', 'gatekeeper_add_invite_key_field_to_pmpro_registration');

function gatekeeper_add_invite_key_field_to_pmpro_registration() {
    // Check if the user is not already logged in
    if (!is_user_logged_in()) {
        // Get the custom redirect option status
        $custom_redirect_enabled = get_option('gatekeeper_custom_redirect_enabled');
        // Add your HTML for the invite key input field here
        ?>
        <div id="gatekeeper_invite_key" class="pmpro_checkout-field pmpro_checkout-field-invite_key">
            <label for="invite_key">Invite Key:</label>
            <input type="text" name="invite_key" id="invite_key" class="input" value="<?php echo esc_attr(isset($_POST['invite_key']) ? $_POST['invite_key'] : ''); ?>" required />
        </div>
        <?php
        // Add a hidden field for the custom redirect option
        if ($custom_redirect_enabled) {
            ?>
            <input type="hidden" name="custom_redirect_enabled" value="1" />
            <?php
        }

        // Add a hidden field to check if the invitee_email exists
        ?>
        <input type="hidden" name="invitee_email_exists" id="invitee_email_exists" value="0" />
        <?php
    }
}

// Function to validate the invite key during PMPro registration
function gatekeeper_validate_invite_key_pmpro($pmpro_continue_registration) {
    if (!is_user_logged_in()) { // Only validate if the user is not logged in
        if (empty($_POST['invite_key'])) {
            // Invite key is required
            pmpro_setMessage('Please enter a valid invite key.', 'pmpro_error');
            $pmpro_continue_registration = false;
        } else {
            $invite_key = sanitize_text_field($_POST['invite_key']);
            // Check if the invite key exists and is valid
            if (!gatekeeper_is_valid_invite_key($invite_key)) {
                pmpro_setMessage('The invite key is invalid.', 'pmpro_error');
                $pmpro_continue_registration = false;
            }
        }

        // Check if invitee_email exists
        if (isset($_POST['invitee_email_exists']) && $_POST['invitee_email_exists'] == '1') {
            $pmpro_continue_registration = false;
        }
    }

    return $pmpro_continue_registration;
}

// Hook to validate invite key during PMPro registration
add_filter('pmpro_registration_checks', 'gatekeeper_validate_invite_key_pmpro');

// Function to perform actions after a user has been registered with PMPro
function gatekeeper_after_registration_pmpro($user_id) {
    // Get the invite key from the registration form
    $invite_key = isset($_POST['invite_key']) ? sanitize_text_field($_POST['invite_key']) : '';

    // Check if the invite key is not empty
    if (!empty($invite_key)) {
        // Insert the user into the gatekeeper_invited_users table
        $insert_result = gatekeeper_insert_invited_user($invite_key, $user_id);

        // Check if the insertion was successful
        if ($insert_result === true) {
            // Remove used invite keys after successful registration
            gatekeeper_remove_used_invite_key($invite_key);

            // Update invite key status after successful registration
            gatekeeper_update_invite_key_status($user_id, $invite_key);
        } else {
            // Handle the error here, e.g., log it or display an error message
            // You can use the $insert_result variable to get more information about the error
            // Example: error_log('Failed to insert user into gatekeeper_invited_users: ' . $insert_result);
        }
    }
}

// Hook to perform actions after a user has been registered with PMPro
add_action('pmpro_after_checkout', 'gatekeeper_after_registration_pmpro');
