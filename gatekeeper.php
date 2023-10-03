<?php
/**
 * Plugin Name: GateKeeper
 * Description: Advanced user access control plugin for WordPress.
 * Version: 1.0.0
 * Author: Zeus Eternal
 */

// Include other PHP files
require_once(plugin_dir_path(__FILE__) . 'inc/scripts-and-styles.php');
require_once(plugin_dir_path(__FILE__) . 'functions/shortcodes.php');
require_once(plugin_dir_path(__FILE__) . 'functions/registration.php');
require_once(plugin_dir_path(__FILE__) . 'functions/invitation.php');
require_once(plugin_dir_path(__FILE__) . 'functions/core.php'); // Core functions
require_once(plugin_dir_path(__FILE__) . 'helpers/utils.php');
require_once(plugin_dir_path(__FILE__) . 'helpers/database.php');
require_once(plugin_dir_path(__FILE__) . 'helpers/activated.php');
require_once(plugin_dir_path(__FILE__) . 'helpers/deactivated.php');

// Activation and deactivation hooks
register_activation_hook(__FILE__, 'gatekeeper_plugin_activated');
register_deactivation_hook(__FILE__, 'gatekeeper_plugin_deactivated');
