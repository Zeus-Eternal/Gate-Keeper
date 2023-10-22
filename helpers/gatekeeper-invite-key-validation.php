<?php
// Validate invite key during registration
function gatekeeper_validate_invite_key($errors, $sanitized_user_login, $user_email) {
    // Check if the invite key field is empty
    if (empty($_POST['invite_key'])) {
        $errors->add('invite_key_error', __('Please enter a valid invite key.', 'gatekeeper'));
    } else {
        $invite_key = sanitize_text_field($_POST['invite_key']);

        // Check if the email field is entered or the option is checked
        $email_entered = !empty($user_email); // Check if the user provided an email during registration
        $option_checked = isset($_POST['email_option']) && $_POST['email_option'] === 'yes'; // Check if the option is checked

        // Validate the invite key considering email and option
        if (!gatekeeper_is_valid_invite_key($invite_key, $email_entered, $option_checked)) {
            $errors->add('invite_key_error', __('Invalid invite key or email.', 'gatekeeper'));
        }
    }

    return $errors;
}
add_filter('registration_errors', 'gatekeeper_validate_invite_key', 10, 3);
