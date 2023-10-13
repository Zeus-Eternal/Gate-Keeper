<?php
// Function to display the list of invited users
function gatekeeper_display_invited_users() {
    // Retrieve the list of invited users from the database
    $invited_users = get_invited_users(); // Implement this function to retrieve data from your database
    
    if (empty($invited_users)) {
        echo '<p>No invited users found.</p>';
        return;
    }
    
    echo '<table class="widefat">';
    echo '<thead>';
    echo '<tr>';
    echo '<th>ID</th>';
    echo '<th>Username</th>';
    echo '<th>Email</th>';
    echo '<th>Invite Status</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';
    
    foreach ($invited_users as $user) {
        echo '<tr>';
        echo '<td>' . esc_html($user->ID) . '</td>';
        echo '<td>' . esc_html($user->user_login) . '</td>';
        echo '<td>' . esc_html($user->user_email) . '</td>';
        echo '<td>' . get_invite_status($user->ID) . '</td>'; // Implement this function to retrieve invite status
        echo '</tr>';
    }
    
    echo '</tbody>';
    echo '</table>';
}

// Function to get invite status for a user
function get_invite_status($user_id) {
    // Implement logic to retrieve and display invite status for the user
    // You can check if the user is accepted, pending, etc.
    // You may use database queries to fetch this information
    return 'Pending'; // Placeholder; replace with actual logic
}

// Add the "Invited Users" section to the "Invited Users Settings" page
function gatekeeper_settings_invited_users_page() {
    ?>
    <div class="wrap">
        <h1>GateKeeper Invited Users</h1>
        <?php gatekeeper_display_invited_users(); ?>
    </div>
    <?php
}

// Hook to add the "Invited Users" section to the menu
add_action('admin_menu', 'gatekeeper_add_invited_users_settings_page');

// Create a field to view the list of invited users
function gatekeeper_view_invited_users_field() {
    // Retrieve the list of invited users from the database
    $invited_users = get_option('gatekeeper_invited_users', array());
    ?>
    <h2>Invited Users</h2>
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th>User ID</th>
                <th>Username</th>
                <th>Email</th>
                <th>Invite Status</th>
                <th>Invited By</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($invited_users as $user) : ?>
                <tr>
                    <td><?php echo esc_html($user['user_id']); ?></td>
                    <td><?php echo esc_html($user['username']); ?></td>
                    <td><?php echo esc_html($user['email']); ?></td>
                    <td><?php echo esc_html($user['invite_status']); ?></td>
                    <td><?php echo esc_html($user['invited_by']); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php
}

// Add the field to the "Invited Users" page
function gatekeeper_settings_invited_users_page() {
    ?>
    <div class="wrap">
        <h1>GateKeeper Invited Users</h1>
        <?php gatekeeper_view_invited_users_field(); ?>
    </div>
    <?php
}

// Hook to add the field to the "Invited Users" page
add_action('gatekeeper_invited_users_settings', 'gatekeeper_view_invited_users_field');
