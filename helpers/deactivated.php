<?php

// Function to remove database tables during plugin deactivation
function gatekeeper_plugin_deactivated() {
    gatekeeper_remove_tables();
}

// Function to remove database tables during plugin deactivation
function gatekeeper_remove_tables() {
    global $wpdb;

    $table_prefix = 'gatekeeper_';

    $invite_keys_table = $wpdb->prefix . $table_prefix . 'invite_keys';
    $user_role_relationships_table = $wpdb->prefix . $table_prefix . 'user_role_relationships';
    $user_roles_table = $wpdb->prefix . $table_prefix . 'user_roles';
    $user_permissions_table = $wpdb->prefix . $table_prefix . 'user_permissions';
    $relationships_table = $wpdb->prefix . $table_prefix . 'user_relationships';

    $wpdb->query("DROP TABLE IF EXISTS $invite_keys_table");
    $wpdb->query("DROP TABLE IF EXISTS $user_role_relationships_table");
    $wpdb->query("DROP TABLE IF EXISTS $user_roles_table");
    $wpdb->query("DROP TABLE IF EXISTS $user_permissions_table");
    $wpdb->query("DROP TABLE IF EXISTS $relationships_table");

    return true;
}

