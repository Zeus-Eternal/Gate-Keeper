<?php
// Add the invite key input field to the registration form
add_action('register_form', 'gatekeeper_add_invite_key_input');

// Add an Invite Key input form option below the registration form
function gatekeeper_add_invite_key_input() {
    ?>
    <label for="invite_key">Invite Key</label>
    <input type="text" name="invite_key" id="invite_key" class="input" value="<?php echo esc_attr(wp_unslash($_POST['invite_key'])); ?>" size="20" />
    <?php
}

// Function to handle registration form submission and invite key validation
function gatekeeper_registration_check($errors, $sanitized_user_login, $user_email) {
    global $wpdb;

    if (isset($_POST['invite_key']) && !empty($_POST['invite_key'])) {
        $invite_key = sanitize_text_field($_POST['invite_key']);

        // Validate the invite key by querying the database
        $invitation_details = gatekeeper_validate_invite_key($invite_key);

        if ($invitation_details) {
            // Check if the invite key is available
            if ($invitation_details->invite_status === 'Available') {
                // Key is valid and available, proceed with registration
                $inviter_id = $invitation_details->inviter_id;
                $invitee_id = get_current_user_id(); // Get the ID of the currently registered user

                // Check if the user is already registered
                if ($invitee_id) {
                    $errors->add('invite_key_error', 'You are already registered. No need to use an invite key.');
                } /*else {
                    // Check if the email provided matches the email associated with the invitee's user account
                    $invitee_email = $user_email;
                    $expected_email = $invitation_details->invitee_email;

                    if ($invitee_email === $expected_email) {
                        // Insert the user into the invited_users table
                        gatekeeper_insert_invited_user($invitation_details, $invitee_id);

                        // Update the user's role based on the invite key
                        $user_role = $invitation_details->invitee_role;

                        // Set the user's role to the role specified in the invite key (if available)
                        if (!empty($user_role)) {
                            wp_update_user(array('ID' => $invitee_id, 'role' => $user_role));
                        }

                        // Update the invite key status to 'Used'
                        gatekeeper_update_invite_key_status($invite_key, 'Used');

                        // Log the registration access
                        gatekeeper_log_access($invitee_id, 'Registration', $invite_key);

                        // Redirect to a success page or perform other actions as needed
                        wp_safe_redirect(home_url('/registration-success/'));
                        exit;
                    } else {
                        // Email does not match the invitee's email
                        $errors->add('invite_key_error', 'The provided email does not match the invitee\'s email. Please use the correct email associated with the invite key.');
                    }
                } */
            } else {
                // Invite key is not available, display an error message
                $errors->add('invite_key_error', 'Invite key is no longer available. Please use a valid invite key.');
            }
        } else {
            // Invalid invite key, display an error message
            $errors->add('invite_key_error', 'Invalid invite key. Please check and try again.');
        }
    } else {
        // No invite key provided, display an error message
        $errors->add('invite_key_error', 'Invite key is required. Please enter a valid invite key.');
    }

    return $errors;
}
add_filter('registration_errors', 'gatekeeper_registration_check', 10, 3);
// Hook into registration actions
add_action('user_register', 'gatekeeper_user_registration_handler', 10, 1);
add_filter('registration_redirect', 'gatekeeper_registration_redirect');

// Function to handle user registration after validation
function gatekeeper_user_registration_handler($user_id) {
    // Additional actions after successful registration if needed
}

// Function to redirect users after registration
function gatekeeper_registration_redirect($redirect_to) {
    // Redirect to a custom success page or any other URL
    return home_url('/registration-success/');
}