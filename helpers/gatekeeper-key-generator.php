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

 function gatekeeper_generate_invite_key($length = 5) {
    // Define characters to use in the invite key
    $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    $character_length = strlen($characters);

    // Initialize the invite key
    $invite_key = '';

    // Generate random bytes securely
    if (function_exists('random_bytes')) {
        $bytes = random_bytes($length);
    } elseif (function_exists('openssl_random_pseudo_bytes')) {
        $bytes = openssl_random_pseudo_bytes($length, $strong);
        if (!$strong) {
            // OpenSSL didn't provide strong randomness, fallback to less secure method
            $bytes = '';
        }
    }

    // If neither random_bytes nor openssl_random_pseudo_bytes is available, fall back to mt_rand
    if (empty($bytes)) {
        for ($i = 0; $i < $length; $i++) {
            $invite_key .= $characters[mt_rand(0, $character_length - 1)];
        }
    } else {
        for ($i = 0; $i < $length; $i++) {
            $invite_key .= $characters[ord($bytes[$i]) % $character_length];
        }
    }

    return $invite_key;
}