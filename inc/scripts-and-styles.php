<?php
// Add scripts and styles for SPA
function gatekeeper_enqueue_scripts() {
    wp_enqueue_script('gatekeeper-app', GATEKEEPER_PLUGIN_URL . 'js/app.js', array('jquery'), '1.0.0', true);
    wp_enqueue_style('gatekeeper-styles', GATEKEEPER_PLUGIN_URL . 'css/styles.css', array(), '1.0.0');
}
add_action('wp_enqueue_scripts', 'gatekeeper_enqueue_scripts');
