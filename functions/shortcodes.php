<?php
// Shortcode handler for registration form
function gatekeeper_registration_shortcode() {
    // Our registration form shortcode logic
}

// Shortcode handler for invitation form
function gatekeeper_send_invite_shortcode() {
    // Our invitation form shortcode logic
}

// Add shortcode for registration form
add_shortcode('gatekeeper_registration', 'gatekeeper_registration_shortcode');

// Add shortcode for invitation form
add_shortcode('gatekeeper_send_invite', 'gatekeeper_send_invite_shortcode');
