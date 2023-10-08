<?php

function gatekeeper_invitation_management_page() {
    if (isset($_GET['action'])) {
        $action = sanitize_text_field($_GET['action']);
        $invitation_id = isset($_GET['id']) ? absint($_GET['id']) : 0;

        switch ($action) {
            case 'view':
                gatekeeper_view_invitation($invitation_id);
                break;
            case 'edit':
                gatekeeper_edit_invitation($invitation_id);
                break;
            case 'delete':
                gatekeeper_delete_invitation($invitation_id);
                break;
            case 'resend':
                gatekeeper_resend_invitation($invitation_id);
                break;
            case 'create':
                gatekeeper_create_invitation(); // Implement this function for creating new invitations
                break;
            default:
                // Display the main page with invitation list
                gatekeeper_display_invitations_table();
                echo '<a href="?page=gatekeeper_invitation_management&action=create" class="button button-primary">Create New Invitation</a>';
                break;
        }
    } else {
        // Display the main page with invitation list
        gatekeeper_display_invitations_table();
        echo '<a href="?page=gatekeeper_invitation_management&action=create" class="button button-primary">Create New Invitation</a>';
    }
}

function gatekeeper_display_invitations_table() {
    $invitation_data = gatekeeper_get_invitations();
    ?>
    <div class="wrap">
        <h2>Invitation Management</h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Invite Key</th>
                    <th>Role</th>
                    <th>Inviter</th>
                    <th>Invitee</th>
                    <th>Usage Limit</th>
                    <th>Expiry Date</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($invitation_data as $invitation) : ?>
                    <tr>
                        <td><?php echo esc_html($invitation->id); ?></td>
                        <td><?php echo esc_html($invitation->invite_key); ?></td>
                        <td><?php echo esc_html($invitation->role); ?></td>
                        <td><?php echo esc_html(get_user_by('ID', $invitation->inviter)->user_login); ?></td>
                        <td><?php echo esc_html(get_user_by('ID', $invitation->invitee)->user_login); ?></td>
                        <td><?php echo esc_html($invitation->usage_limit); ?></td>
                        <td><?php echo esc_html($invitation->expiry_date); ?></td>
                        <td><?php echo esc_html($invitation->status); ?></td>
                        <td>
                            <a href="?page=gatekeeper_invitation_management&action=view&id=<?php echo esc_attr($invitation->id); ?>">View</a> |
                            <a href="?page=gatekeeper_invitation_management&action=edit&id=<?php echo esc_attr($invitation->id); ?>">Edit</a> |
                            <a href="?page=gatekeeper_invitation_management&action=delete&id=<?php echo esc_attr($invitation->id); ?>">Delete</a> |
                            <a href="?page=gatekeeper_invitation_management&action=resend&id=<?php echo esc_attr($invitation->id); ?>">Resend</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}

// Function to get invitation data from the database
function gatekeeper_get_invitations() {
    global $wpdb;
    $invite_keys_table = $wpdb->prefix . 'gatekeeper_invite_keys';

    $invitation_data = $wpdb->get_results("SELECT * FROM $invite_keys_table");

    return $invitation_data;
}

// Create New Invitation
function gatekeeper_create_invitation() {
    global $wpdb;
    $invite_keys_table = $wpdb->prefix . 'gatekeeper_invite_keys';

    // Auto-generate the Invite Key (you can customize this generation logic)
    $invite_key = uniqid('invitation_', true);

    if (isset($_POST['create_invitation'])) {
        // Handle form submission to create a new invitation
        $role = sanitize_text_field($_POST['role']);
        $inviter = get_current_user_id(); // Assuming the current user is the inviter
        $invitee = sanitize_email($_POST['invitee']);
        $usage_limit = isset($_POST['usage_limit']) ? absint($_POST['usage_limit']) : 5; // Default to 5
        $expiry_date = isset($_POST['expiry_date']) ? sanitize_text_field($_POST['expiry_date']) : date('Y-m-d', strtotime('+90 days')); // Default to 90 days from today
        $status = 'Pending'; // You can set an initial status for the invitation

        // Validate the input data (you can add more validation rules as needed)
        if (empty($role) || empty($invitee) || empty($usage_limit) || empty($expiry_date)) {
            echo '<div class="error">Please fill in all required fields.</div>';
        } else {
            // Insert the new invitation into the database
            $wpdb->insert(
                $invite_keys_table,
                array(
                    'invite_key' => $invite_key,
                    'role' => $role,
                    'inviter' => $inviter,
                    'invitee' => $invitee,
                    'usage_limit' => $usage_limit,
                    'expiry_date' => $expiry_date,
                    'status' => $status,
                ),
                array(
                    '%s',
                    '%s',
                    '%d',
                    '%s',
                    '%d',
                    '%s',
                    '%s',
                )
            );

            echo '<div class="updated">Invitation created successfully.</div>';
        }
    }

    // Display the form to create a new invitation
    ?>
    <div class="wrap">
        <h2>Create New Invitation</h2>
        <form method="post">
            <table class="form-table">
                <tr>
                    <th scope="row">Invite Key</th>
                    <td><input type="text" value="<?php echo esc_attr($invite_key); ?>" readonly></td>
                </tr>
                <tr>
                    <th scope="row">Role</th>
                    <td>
                        <select name="role" required>
                            <?php
                            // Get a list of WordPress core user roles
                            $roles = get_editable_roles();
                            foreach ($roles as $role_key => $role_data) {
                                echo '<option value="' . esc_attr($role_key) . '">' . esc_html($role_data['name']) . '</option>';
                            }
                            ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Invitee Email</th>
                    <td><input type="email" name="invitee" required></td>
                </tr>
                <tr>
                    <th scope="row">Usage Limit</th>
                    <td><input type="number" name="usage_limit" value="5" min="1" required></td>
                </tr>
                <tr>
                    <th scope="row">Expiry Date</th>
                    <td><input type="date" name="expiry_date" value="<?php echo esc_attr($expiry_date); ?>" required></td>
                </tr>
            </table>
            <input type="hidden" name="create_invitation" value="1">
            <input type="submit" class="button button-primary" value="Create Invitation">
        </form>
    </div>
    <?php
}

// View invitation details
function gatekeeper_view_invitation($invitation_id) {
    global $wpdb;
    $invite_keys_table = $wpdb->prefix . 'gatekeeper_invite_keys';

    $invitation = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT * FROM $invite_keys_table WHERE id = %d",
            $invitation_id
        )
    );

    if ($invitation) {
        echo '<div class="wrap">';
        echo '<h2>View Invitation</h2>';
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
        echo '<tr>';
        echo '<th>Inviter</th>';
        echo '<td>' . esc_html($invitation->inviter) . '</td>';
        echo '</tr>';
        echo '<tr>';
        echo '<th>Invitee</th>';
        echo '<td>' . esc_html($invitation->invitee) . '</td>';
        echo '</tr>';
        echo '<tr>';
        echo '<th>Status</th>';
        echo '<td>' . esc_html($invitation->status) . '</td>';
        echo '</tr>';
        echo '</table>';
        echo '<a href="?page=gatekeeper_invitation_management" class="button button-primary">Back to Invitations</a>';
        echo '</div>';
    } else {
        echo '<div class="error">Invitation not found.</div>';
    }
}

// Edit invitation details
function gatekeeper_edit_invitation($invitation_id) {
    global $wpdb;
    $invite_keys_table = $wpdb->prefix . 'gatekeeper_invite_keys';

    $invitation = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT * FROM $invite_keys_table WHERE id = %d",
            $invitation_id
        )
    );

    if ($invitation) {
        if (isset($_POST['update_invitation'])) {
            // Handle form submission to update invitation details
            $updated_data = array(
                'invite_key' => sanitize_text_field($_POST['invite_key']),
                'role' => sanitize_text_field($_POST['role']),
                // Add other fields here
            );

            // Update the invitation in the database
            $wpdb->update(
                $invite_keys_table,
                $updated_data,
                array('id' => $invitation_id),
                array('%s', '%s') // Data formats for fields
            );

            echo '<div class="updated">Invitation updated successfully.</div>';
        }

        echo '<div class="wrap">';
        echo '<h2>Edit Invitation</h2>';
        echo '<form method="post">';
        echo '<table class="form-table">';
        echo '<tr>';
        echo '<th>ID</th>';
        echo '<td>' . esc_html($invitation->id) . '</td>';
        echo '</tr>';
        echo '<tr>';
        echo '<th>Invite Key</th>';
        echo '<td>';
        echo '<input type="text" name="invite_key" value="' . esc_attr($invitation->invite_key) . '" />';
        echo '</td>';
        echo '</tr>';
        echo '<tr>';
        echo '<th>Role</th>';
        echo '<td>';
        echo '<select name="role">';
        // Output role options and select the appropriate one based on $invitation->role
        // You can fetch roles from the database or define a list of roles here
        // Example: $roles = get_roles(); foreach ($roles as $role) { ... }
        echo '</select>';
        echo '</td>';
        echo '</tr>';
        // Add other invitation fields for editing here
        echo '</table>';
        echo '<input type="hidden" name="update_invitation" value="1" />';
        echo '<input type="submit" class="button button-primary" value="Update Invitation" />';
        echo '</form>';
        echo '<a href="?page=gatekeeper_invitation_management" class="button">Back to Invitations</a>';
        echo '</div>';
    } else {
        echo '<div class="error">Invitation not found.</div>';
    }
}

// Delete an invitation
function gatekeeper_delete_invitation($invitation_id) {
    global $wpdb;
    $invite_keys_table = $wpdb->prefix . 'gatekeeper_invite_keys';

    // Check if the invitation exists before deleting
    $invitation = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT * FROM $invite_keys_table WHERE id = %d",
            $invitation_id
        )
    );

    if ($invitation) {
        // Delete the invitation key from the database
        $wpdb->delete(
            $invite_keys_table,
            array('id' => $invitation_id),
            array('%d')
        );

        echo '<div class="updated">Invitation deleted successfully.</div>';
        gatekeeper_display_invitations_table(); // Refresh the invitations table
    } else {
        echo '<div class="error">Invitation not found.</div>';
    }
}

// Resend an invitation
function gatekeeper_resend_invitation($invitation_id) {
    global $wpdb;
    $invite_keys_table = $wpdb->prefix . 'gatekeeper_invite_keys';

    $invitation = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT * FROM $invite_keys_table WHERE id = %d",
            $invitation_id
        )
    );

    if ($invitation) {
        // Assuming you have an email field in your invitation data, replace 'email' with the actual field name
        $invitee_email = $invitation->email;

        // Customize the email subject and message as needed
        $email_subject = 'Invitation Resent';
        $email_message = 'Your invitation has been resent. Here is your invitation key: ' . $invitation->invite_key;

        // Use the wp_mail function to send the email
        $sent = wp_mail($invitee_email, $email_subject, $email_message);

        if ($sent) {
            echo '<div class="updated">Invitation resent successfully.</div>';
            gatekeeper_display_invitations_table(); // Refresh the invitations table
        } else {
            echo '<div class="error">Failed to resend the invitation email.</div>';
        }
    } else {
        echo '<div class="error">Invitation not found.</div>';
    }
}

// Add the Invitation Management page to the admin menu
function gatekeeper_add_invitation_management_page() {
    add_submenu_page(
        'gatekeeper_general_settings',
        'Invitation Management',
        'Invitation Management',
        'manage_options',
        'gatekeeper_invitation_management',
        'gatekeeper_invitation_management_page'
    );
}

// Hook the menu page function to WordPress
add_action('admin_menu', 'gatekeeper_add_invitation_management_page');

?>
