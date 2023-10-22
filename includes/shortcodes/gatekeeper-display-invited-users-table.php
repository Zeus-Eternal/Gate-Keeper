<?php
// Shortcode to display invited users in a table with more information and pagination
function gatekeeper_display_invited_users_table($atts) {
    global $wpdb;
    $table_name_users = $wpdb->prefix . 'gatekeeper_invited_users';

    // Define default attributes and parse user attributes
    $atts = shortcode_atts(array(
        'per_page' => 10, // Number of users per page
    ), $atts);

    // Validate and sanitize per_page attribute
    $per_page = absint($atts['per_page']);

    if ($per_page <= 0) {
        $per_page = 10; // Use a default value if the provided value is invalid
    }

    // Get the current page from the URL query parameter
    $current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;

    // Calculate the offset for pagination
    $offset = ($current_page - 1) * $per_page;

    // Query to retrieve invited users with pagination
    $query = $wpdb->prepare(
        "SELECT invitee_id, user_role, invite_key, invite_status, usage_limit FROM $table_name_users
        WHERE invite_status = 'Active'
        ORDER BY invitee_id ASC
        LIMIT %d OFFSET %d",
        $per_page,
        $offset
    );

    $users = $wpdb->get_results($query, OBJECT);

    ob_start(); // Start output buffering

    if (!empty($users)) {
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead>';
        echo '<tr>';
        echo '<th>User</th>';
        echo '<th>Role</th>';
        echo '<th>Invite Key</th>';
        echo '<th>Key Status</th>';
        echo '<th>Usage Limit</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';

        foreach ($users as $user) {
            // Get user data by ID
            $user_data = get_userdata($user->invitee_id);
            if ($user_data) {
                $username = esc_html($user_data->user_login);
            } else {
                $username = 'N/A';
            }

            echo '<tr>';
            echo '<td>' . $username . '</td>';
            echo '<td>' . esc_html($user->user_role) . '</td>';
            echo '<td>' . esc_html($user->invite_key) . '</td>';
            echo '<td>' . esc_html($user->invite_status) . '</td>';
            echo '<td>' . esc_html($user->usage_limit) . '</td>';
            echo '</tr>';
        }

        echo '</tbody>';
        echo '</table>';

        // Pagination
        $total_users = $wpdb->get_var("SELECT COUNT(*) FROM $table_name_users WHERE invite_status = 'Active'");
        $total_pages = ceil($total_users / $per_page);

        if ($total_pages > 1) {
            echo '<div class="pagination">';
            for ($i = 1; $i <= $total_pages; $i++) {
                $class = ($i === $current_page) ? 'current' : '';
                echo "<a class='page-link $class' href='?page=$i'>$i</a>";
            }
            echo '</div>';
        }
    } else {
        echo '<p>No invited users available.</p>';
    }

    return ob_get_clean(); // Return the buffered content
}

add_shortcode('gatekeeper_display_invited_users', 'gatekeeper_display_invited_users_table');
