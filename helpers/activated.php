<?php
// Activation Hook for GateKeeper Plugin
function gatekeeper_plugin_activated() {
    if (!current_user_can('activate_plugins')) {
        wp_die(__('You do not have sufficient permissions to activate this plugin.'));
    }

    if (version_compare(get_bloginfo('version'), '5.0', '<')) {
        wp_die(__('This plugin requires WordPress version 5.0 or higher. Please update your WordPress installation.'));
    }

    if (!gatekeeper_supports_innodb()) {
        wp_die(__('Your database engine does not support InnoDB, which is required for this plugin. Please switch to a compatible database.'));
    }

    if (!gatekeeper_create_tables()) {
        wp_die(__('An error occurred while creating the database tables for the plugin. Please check your database configuration and try again.'));
    }

    gatekeeper_set_default_options();
}
