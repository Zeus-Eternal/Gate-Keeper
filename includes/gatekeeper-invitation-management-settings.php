function gatekeeper_invite_key_management() {
    global $wpdb;
    $invite_keys_table = $wpdb->prefix . 'gatekeeper_invite_keys';

    if (isset($_GET['action'])) {
        $action = sanitize_text_field($_GET['action']);

        switch ($action) {
            case 'create':
                handle_create_action($wpdb, $invite_keys_table);
                break;
            case 'read':
                handle_read_action($wpdb, $invite_keys_table);
                break;
            case 'delete':
                handle_delete_action($wpdb, $invite_keys_table);
                break;
            case 'reset_expired':
                handle_reset_expired_action($wpdb, $invite_keys_table);
                break;
            default:
                display_invite_key_management_page($wpdb, $invite_keys_table);
        }
    } else {
        display_invite_key_management_page($wpdb, $invite_keys_table);
    }
}

function handle_create_action($wpdb, $table_name) {
    $new_invite_key = gatekeeper_generate_invite_key(); // Use the custom key generation function
    $default_role = 'subscriber'; // Set the default role

    $result = $wpdb->insert(
        $table_name,
        array(
            'invite_key' => $new_invite_key,
            'role' => $default_role,
            // Add other fields as needed
        ),
        array(
            '%s', // invite_key is a string
            '%s', // role is a string
            // Add other placeholders as needed
        )
    );

    if ($result) {
        echo '<div class="updated">Invite key created successfully. Key: ' . esc_html($new_invite_key) . '</div>';
    } else {
        echo '<div class="error">Error creating invite key.</div>';
    }

    display_invite_key_management_page($wpdb, $table_name);
}

function handle_read_action($wpdb, $table_name) {
    $invitation_id = isset($_GET['id']) ? absint($_GET['id']) : 0;

    if ($invitation_id > 0) {
        $invitation = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table_name WHERE id = %d",
                $invitation_id
            )
        );

        if ($invitation) {
            // Display invite key details
            // ...
        } else {
            echo '<div class="error">Invitation key not found.</div>';
        }
    } else {
        echo '<div class="error">Invalid invitation key ID.</div>';
    }

    display_invite_key_management_page($wpdb, $table_name);
}

function handle_delete_action($wpdb, $table_name) {
    $invitation_id = isset($_GET['id']) ? absint($_GET['id']) : 0;

    if ($invitation_id > 0) {
        $result = delete_invite_key($invitation_id); // Replace with your delete logic

        if ($result) {
            echo '<div class="updated">Invite key deleted successfully.</div>';
        } else {
            echo '<div class="error">Error deleting invite key.</div>';
        }
    } else {
        echo '<div class="error">Invalid invitation key ID.</div>';
    }

    display_invite_key_management_page($wpdb, $table_name);
}

function handle_reset_expired_action($wpdb, $table_name) {
    $result = reset_expired_invite_keys(); // Replace with your reset logic

    if ($result) {
        echo '<div class="updated">Expired invite keys reset successfully.</div>';
    } else {
        echo '<div class="error">Error resetting expired invite keys.</div>';
    }

    display_invite_key_management_page($wpdb, $table_name);
}

function display_invite_key_management_page($wpdb, $table_name) {
    $invitation_data = $wpdb->get_results("SELECT * FROM $table_name");

    echo '<div class="wrap">';
    echo '<h2>Invite Key Management</h2>';
    echo '<a href="?page=gatekeeper_invite_key_management&action=create" class="button button-primary">Create New Invite Key</a>';
    echo '<table class="wp-list-table widefat fixed striped">';
    echo '<thead>';
    echo '<tr>';
    echo '<th>ID</th>';
    echo '<th>Invite Key</th>';
    echo '<th>Role</th>';
    echo '<th>Actions</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';

    foreach ($invitation_data as $invitation) {
        echo '<tr>';
        echo '<td>' . esc_html($invitation->id) . '</td>';
        echo '<td>' . esc_html($invitation->invite_key) . '</td>';
        echo '<td>' . esc_html($invitation->role) . '</td>';
        echo '<td>';
        echo '<a href="?page=gatekeeper_invite_key_management&action=read&id=' . esc_attr($invitation->id) . '">View</a>';
        echo '<a href="?page=gatekeeper_invite_key_management&action=delete&id=' . esc_attr($invitation->id) . '">Delete</a>';
        echo '</td>';
        echo '</tr>';
    }

    echo '</tbody>';
    echo '</table>';
    echo '</div>';

    // Display invite key details if invitation_id is greater than 0
    if (isset($_GET['id'])) {
        $invitation_id = absint($_GET['id']);
        if ($invitation_id > 0) {
            $invitation = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT * FROM $table_name WHERE id = %d",
                    $invitation_id
                )
            );

            if ($invitation) {
                echo '<div class="wrap">';
                echo '<h2>Invite Key Details</h2>';
                echo '<table class="form-table">';
                echo '<tr>';
                echo '<th>ID</th>';
                echo '<td>' . esc_html($invitation->id) . '</td>';
                echo '</tr>';
                echo '<tr>';
                echo '<th>Invite Key</th>';
                echo '<td>' . esc_html($invitation->invite_key) . '</td>';
                echo '</tr>';
                echo '<tr>';
                echo '<th>Role</th>';
                echo '<td>' . esc_html($invitation->role) . '</td>';
                echo '</tr>';
                // Add other fields here if needed
                echo '</table>';
                echo '<a href="?page=gatekeeper_invite_key_management" class="button button-primary">Back to Invite Keys</a>';
                echo '</div>';
            } else {
                echo '<div class="error">Invitation key not found.</div>';
            }
        } else {
            echo '<div class="error">Invalid invitation key ID.</div>';
        }
    }
}

function gatekeeper_generate_invite_key($length = 12) {
    // Use WordPress's secure key generation function
    $invite_key = wp_generate_password($length, false);

    // Check for uniqueness and enforce specific patterns
    while (is_invite_key_not_unique($invite_key)) {
        // Regenerate the key until it's unique
        $invite_key = wp_generate_password($length, false);
    }

    // Enforce a specific pattern and format if required
    // Example: $invite_key = strtoupper($invite_key); // Convert to uppercase

    return $invite_key;
}

// Add your other functions as needed...

