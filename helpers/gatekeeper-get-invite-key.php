<?php
// Function to get the invite key from the database and decode it
function gatekeeper_get_invite_key($invite_key_from_db) {
    // Decode the invite key from hexadecimal to binary
    $decoded_key = hex2bin($invite_key_from_db);

    return $decoded_key;
}
