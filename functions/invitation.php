<?php
// Function to process invitation form submission
function gatekeeper_process_invite() {
    if (isset($_POST['send_invite'])) {
        $errors = new WP_Error();

        $invitee_email = sanitize_email($_POST['invitee_email']);

        if (empty($invitee_email)) {
            $errors->add('email_required', 'Email is required.');
        } elseif (!is_email($invitee_email)) {
            $errors->add('invalid_email', 'Invalid email address.');
        }

        if ($errors->has_errors()) {
            return $errors;
        }

        $invitation_result = gatekeeper_send_invitation($invitee_email);

        if ($invitation_result === true) {
            return true;
        } else {
            $errors->add('invitation_failed', 'Failed to send invitation.');
            return $errors;
        }
    }

    return '';
}

// Function to send an invitation email
function gatekeeper_send_invitation($invitee_email) {
    $invite_key = gatekeeper_generate_invite_key();

    $subject = 'Invitation to join our site';
    $message = 'You have been invited to join our site. Click the link below to register:';
    $message .= "\n\n";
    $message .= 'Invitation Key: ' . $invite_key;
    $headers = 'From: Your Name <yourname@example.com>' . "\r\n";

    $email_sent = wp_mail($invitee_email, $subject, $message, $headers);

    if ($email_sent) {
        return true;
    } else {
        return false;
    }
}

// Function to generate a random invitation key
function gatekeeper_generate_invite_key() {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $key_length = 10;
    $invite_key = '';

    for ($i = 0; $i < $key_length; $i++) {
        $invite_key .= $characters[rand(0, strlen($characters) - 1)];
    }

    return $invite_key;
}
