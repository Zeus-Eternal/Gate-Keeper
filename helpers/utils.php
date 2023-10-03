<?php
// Function to send an invitation email
function gatekeeper_send_invitation($invitee_email) {
    // Perform necessary checks and validations here
    // Send the invitation email to $invitee_email using WordPress email functions

    // Example:
    $subject = 'Invitation to join our site';
    $message = 'You have been invited to join our site. Click the link below to register:';
    $headers = 'From: Your Name <yourname@example.com>' . "\r\n";

    // Send the email
    $email_sent = wp_mail($invitee_email, $subject, $message, $headers);

    if ($email_sent) {
        // Email sent successfully
        return true;
    } else {
        // Email sending failed
        return false;
    }
}
