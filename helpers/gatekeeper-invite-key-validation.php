<?php
// Function to validate an invite key and return invitation details if valid
function gatekeeper_validate_invite_key($invite_key) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'gatekeeper_invite_keys';

    $sql = $wpdb->prepare(
        "SELECT * FROM $table_name WHERE invite_key = %s AND invite_status = 'Available'",
        sanitize_text_field($invite_key)
    );

    $invitation_details = $wpdb->get_row($sql);

    if ($invitation_details) {
        return $invitation_details;
    }

    return false;
}
