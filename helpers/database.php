<?php

// Function to create necessary database tables during plugin activation
function gatekeeper_create_tables() {
    global $wpdb;

    $table_prefix = 'gatekeeper_';

    $invite_keys_table = $wpdb->prefix . $table_prefix . 'invite_keys';
    $user_role_relationships_table = $wpdb->prefix . $table_prefix . 'user_role_relationships';
    $user_roles_table = $wpdb->prefix . $table_prefix . 'user_roles';
    $user_permissions_table = $wpdb->prefix . $table_prefix . 'user_permissions';
    $relationships_table = $wpdb->prefix . $table_prefix . 'user_relationships';

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    $invite_keys_sql = "
    CREATE TABLE IF NOT EXISTS $invite_keys_table (
        id INT AUTO_INCREMENT PRIMARY KEY,
        invite_key VARCHAR(255) NOT NULL,
        role_id INT NOT NULL,
        inviter INT NOT NULL,
        invitee INT NOT NULL,
        usage_limit INT NOT NULL,
        is_expiry BOOL NOT NULL,
        expiry_date DATETIME NOT NULL,
        status VARCHAR(20) NOT NULL,
        accepted BOOL NOT NULL,
        accepted_at TINYINT(1) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (role_id) REFERENCES $user_roles_table(id),
        FOREIGN KEY (inviter) REFERENCES $wpdb->users(ID),
        FOREIGN KEY (invitee) REFERENCES $wpdb->users(ID)
    ) ENGINE=InnoDB;
    ";

    $user_role_relationships_sql = "
    CREATE TABLE IF NOT EXISTS $user_role_relationships_table (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        role_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_user_role (user_id, role_id),
        FOREIGN KEY (user_id) REFERENCES $wpdb->users(ID),
        FOREIGN KEY (role_id) REFERENCES $user_roles_table(id)
    ) ENGINE=InnoDB;
    ";

    $user_roles_sql = "
    CREATE TABLE IF NOT EXISTS $user_roles_table (
        id INT AUTO_INCREMENT PRIMARY KEY,
        role_name VARCHAR(50) NOT NULL,
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB;
    ";

    $user_permissions_sql = "
    CREATE TABLE IF NOT EXISTS $user_permissions_table (
        id INT AUTO_INCREMENT PRIMARY KEY,
        permission_name VARCHAR(50) NOT NULL,
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB;
    ";

    $relationships_sql = "
    CREATE TABLE IF NOT EXISTS $relationships_table (
        id INT AUTO_INCREMENT PRIMARY KEY,
        inviter_user_id INT NOT NULL,
        invitee_user_id INT NOT NULL,
        relationship_type VARCHAR(20) NOT NULL,
        invite_sent_at DATETIME,
        invite_status VARCHAR(20),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_relationship (inviter_user_id, invitee_user_id),
        FOREIGN KEY (inviter_user_id) REFERENCES $wpdb->users(ID),
        FOREIGN KEY (invitee_user_id) REFERENCES $wpdb->users(ID)
    ) ENGINE=InnoDB;
    ";

    dbDelta($invite_keys_sql);
    dbDelta($user_role_relationships_sql);
    dbDelta($user_roles_sql);
    dbDelta($user_permissions_sql);
    dbDelta($relationships_sql);
}
