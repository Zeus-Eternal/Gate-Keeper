<?php
/**
 * Function to generate a unique Invite Key with various options.
 *
 * @param int    $length      The length of the invite key.
 * @param bool   $use_uppercase Whether to include uppercase letters in the invite key.
 * @param bool   $use_lowercase Whether to include lowercase letters in the invite key.
 * @param bool   $use_numbers  Whether to include numbers in the invite key.
 * @param bool   $use_special  Whether to include special characters in the invite key.
 * @return string The generated invite key.
 */
function gatekeeper_generate_invite_key($length = 5, $use_uppercase = true, $use_lowercase = true, $use_numbers = true, $use_special = false) {
    // Define character sets based on options
    $uppercase_characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $lowercase_characters = 'abcdefghijklmnopqrstuvwxyz';
    $number_characters = '0123456789';
    $additional_characters = '!@#$%^&*()_+[]{}|;:,.<>?';

    // Initialize the character set for the invite key
    $characters = '';

    // Build the character set based on options
    if ($use_uppercase) {
        $characters .= $uppercase_characters;
    }
    if ($use_lowercase) {
        $characters .= $lowercase_characters;
    }
    if ($use_numbers) {
        $characters .= $number_characters;
    }
    if ($use_special) {
        $characters .= $additional_characters;
    }

    // Check if at least one character set is selected
    if (empty($characters)) {
        return ''; // Return an empty string if no character set is selected
    }

    // Generate a random invite key of the specified length
    $invite_key = '';
    $max_index = strlen($characters) - 1;
    for ($i = 0; $i < $length; $i++) {
        $random_index = mt_rand(0, $max_index);
        $invite_key .= $characters[$random_index];
    }

    return $invite_key;
}
