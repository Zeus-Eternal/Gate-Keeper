<?php
// Manage Invitation Keys
function gatekeeper_invitation_management_page() {
    global $wpdb;

    // Define the invite keys table
    $invite_keys_table = $wpdb->prefix . 'gatekeeper_invite_keys';

    // Handle actions (View, Edit, Delete, Resend)
    if (isset($_GET['action']) && !empty($_GET['invitation_id'])) {
        $invitation_id = intval($_GET['invitation_id']);

        if ($_GET['action'] === 'delete') {
            // Delete an invitation
            $wpdb->delete($invite_keys_table, array('id' => $invitation_id));
            echo '<div class="updated"><p>Invitation deleted successfully.</p></div>';
        } elseif ($_GET['action'] === 'view') {
            // View invitation details
            gatekeeper_view_invitation($invitation_id);
            return;
        } elseif ($_GET['action'] === 'edit') {
            // Edit invitation details
            gatekeeper_edit_invitation($invitation_id);
            return;
        } elseif ($_GET['action'] === 'resend') {
            // Resend an invitation
            gatekeeper_resend_invitation($invitation_id);
            return;
        }
    }

    // Fetch invitation data
    $invitation_data = $wpdb->get_results("SELECT * FROM $invite_keys_table");

    // Invitation Management HTML and logic
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
                        <td><?php echo esc_html($invitation->inviter); ?></td>
                        <td><?php echo esc_html($invitation->status); ?></td>
                        <td>
                            <a href="?page=gatekeeper_invitation_management&action=view&invitation_id=<?php echo esc_attr($invitation->id); ?>" class="button-secondary">View</a>
                            <a href="?page=gatekeeper_invitation_management&action=edit&invitation_id=<?php echo esc_attr($invitation->id); ?>" class="button-secondary">Edit</a>
                            <a href="?page=gatekeeper_invitation_management&action=delete&invitation_id=<?php echo esc_attr($invitation->id); ?>" class="button-secondary">Delete</a>
                            <a href="?page=gatekeeper_invitation_management&action=resend&invitation_id=<?php echo esc_attr($invitation->id); ?>" class="button-secondary">Resend</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}

// Function to view invitation details
function gatekeeper_view_invitation($invitation_id) {
    global $wpdb;

    // Fetch invitation data by ID
    $invite_keys_table = $wpdb->prefix . 'gatekeeper_invite_keys';
    $invitation = $wpdb->get_row($wpdb->prepare("SELECT * FROM $invite_keys_table WHERE id = %d", $invitation_id));

    if (!$invitation) {
        echo '<div class="error"><p>Invitation not found.</p></div>';
        return;
    }

    // Display invitation details
    ?>
    <div class="wrap">
        <h2>View Invitation</h2>
        <table class="form-table">
            <tr>
                <th>ID:</th>
                <td><?php echo esc_html($invitation->id); ?></td>
            </tr>
            <tr>
                <th>Invite Key:</th>
                <td><?php echo esc_html($invitation->invite_key); ?></td>
            </tr>
            <tr>
                <th>Role:</th>
                <td><?php echo esc_html($invitation->role); ?></td>
            </tr>
            <tr>
                <th>Inviter:</th>
                <td><?php echo esc_html($invitation->inviter); ?></td>
            </tr>
            <tr>
                <th>Status:</th>
                <td><?php echo esc_html($invitation->status); ?></td>
            </tr>
        </table>
    </div>
    <?php
}

// Function to load the edit form for an invitation
function gatekeeper_edit_invitation($invitation_id) {
    global $wpdb;

    // Fetch invitation data by ID
    $invite_keys_table = $wpdb->prefix . 'gatekeeper_invite_keys';
    $invitation = $wpdb->get_row($wpdb->prepare("SELECT * FROM $invite_keys_table WHERE id = %d", $invitation_id));

    if (!$invitation) {
        echo '<div class="error"><p>Invitation not found.</p></div>';
        return;
    }

  // Handle form submission for editing an invitation
if (isset($_POST['edit_invitation'])) {
    // Validate and update invitation details here
    $new_role = sanitize_text_field($_POST['new_role']);
    $new_status = sanitize_text_field($_POST['new_status']);

    // Implement extended business validation and update logic
    if (!in_array($new_role, array('subscriber', 'editor', 'custom_role'))) {
        // Role is not valid, display an error message
        echo '<div class="error"><p>Invalid role selected.</p></div>';
    } elseif (!in_array($new_status, array('pending', 'accepted', 'rejected'))) {
        // Status is not valid, display an error message
        echo '<div class="error"><p>Invalid status selected.</p></div>';
    } else {
        // Validation passed, update the invitation
        global $wpdb;
        $invite_keys_table = $wpdb->prefix . 'gatekeeper_invite_keys';
        $invitation_id = intval($_POST['invitation_id']);

        // Update the invitation record in the database
        $wpdb->update(
            $invite_keys_table,
            array(
                'role' => $new_role,
                'status' => $new_status,
            ),
            array('id' => $invitation_id),
            array('%s', '%s'),
            array('%d')
        );

        echo '<div class="updated"><p>Invitation updated successfully.</p></div>';
    }

    // After updating, redirect back to the Invitation Management page
    wp_redirect(admin_url('admin.php?page=gatekeeper_invitation_management'));
    exit;
}
    // Display the edit form
    ?>
    <div class="wrap">
        <h2>Edit Invitation</h2>
        <form method="post" action="">
            <input type="hidden" name="invitation_id" value="<?php echo esc_attr($invitation->id); ?>">
            <table class="form-table">
                <tr>
                    <th>Invite Key:</th>
                    <td><?php echo esc_html($invitation->invite_key); ?></td>
                </tr>
                <tr>
                    <th>Role:</th>
                    <td>
                        <select name="new_role">
                            <option value="subscriber" <?php selected('subscriber', $invitation->role); ?>>Subscriber</option>
                            <option value="editor" <?php selected('editor', $invitation->role); ?>>Editor</option>
                            <!-- Add more role options as needed -->
                        </select>
                    </td>
                </tr>
                <tr>
                    <th>Status:</th>
                    <td>
                        <select name="new_status">
                            <option value="pending" <?php selected('pending', $invitation->status); ?>>Pending</option>
                            <option value="accepted" <?php selected('accepted', $invitation->status); ?>>Accepted</option>
                            <option value="rejected" <?php selected('rejected', $invitation->status); ?>>Rejected</option>
                            <!-- Add more status options as needed -->
                        </select>
                    </td>
                </tr>
            </table>
            <input type="submit" name="edit_invitation" class="button-primary" value="Save Changes">
        </form>
    </div>
    <?php
}

// Function to handle resending an invitation
function gatekeeper_resend_invitation($invitation_id) {
    global $wpdb;

    // Fetch invitation data by ID
    $invite_keys_table = $wpdb->prefix . 'gatekeeper_invite_keys';
    $invitation = $wpdb->get_row($wpdb->prepare("SELECT * FROM $invite_keys_table WHERE id = %d", $invitation_id));

    if (!$invitation) {
        echo '<div class="error"><p>Invitation not found.</p></div>';
        return;
    }

    // Implement your business logic to resend an invitation
    // You may send an email with the invitation key or perform other actions

    // After resending, redirect back to the Invitation Management page
    wp_redirect(admin_url('admin.php?page=gatekeeper_invitation_management'));
    exit;
}
