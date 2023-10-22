<?php

// // Shortcode to display invite keys in a table with pagination and additional information
function gatekeeper_display_keys_table($atts) {
    global $wpdb;
    $table_name_keys = $wpdb->prefix . 'gatekeeper_invite_keys';

    // Define default attributes and parse user attributes
    $atts = shortcode_atts(array(
        'per_page' => 10, // Number of keys per page
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

    // Query to retrieve invite keys with pagination
    $query = $wpdb->prepare(
        "SELECT invite_key, inviter_id, inviter_role, invitee_role, invite_status, key_exp_acc, key_exp_date, created_at 
        FROM $table_name_keys
        ORDER BY created_at DESC
        LIMIT %d OFFSET %d",
        $per_page,
        $offset
    );

    $keys = $wpdb->get_results($query, OBJECT);

    ob_start(); // Start output buffering

    if (!empty($keys)) {
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead>';
        echo '<tr>';
        echo '<th>Invite Key</th>';
        echo '<th>Inviter</th>';
        echo '<th>Inviter Role</th>';
        echo '<th>Assigned Role</th>';
        echo '<th>Key Status</th>';
        echo '<th>Invite Duration</th>';
        echo '<th>Key Expiration</th>';
        echo '<th>Created At</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';

        foreach ($keys as $key) {
            // Validate and sanitize the invite key and other fields
            $invite_key = sanitize_text_field($key->invite_key);
            $inviter_id = intval($key->inviter_id);
            $inviter_role = sanitize_text_field($key->inviter_role);
            $invitee_role = sanitize_text_field($key->invitee_role);
            $invite_status = sanitize_text_field($key->invite_status);
            $key_exp_acc = sanitize_text_field($key->key_exp_acc);
            $key_exp_date = sanitize_text_field($key->key_exp_date);
            $created_at = sanitize_text_field($key->created_at);

            echo '<tr>';
            echo '<td>' . esc_html($invite_key) . '</td>';
            echo '<td>' . esc_html(get_user_by('ID', $inviter_id)->display_name) . '</td>';
            echo '<td>' . esc_html($inviter_role) . '</td>';
            echo '<td>' . esc_html($invitee_role) . '</td>';
            echo '<td>' . esc_html($invite_status) . '</td>';
            echo '<td>' . esc_html($key_exp_acc) . '</td>';
            echo '<td>' . esc_html($key_exp_date) . '</td>';
            echo '<td>' . esc_html($created_at) . '</td>';
            echo '</tr>';
        }

        echo '</tbody>';
        echo '</table>';

        // Pagination
        $total_keys = $wpdb->get_var("SELECT COUNT(*) FROM $table_name_keys");
        $total_pages = ceil($total_keys / $per_page);

        if ($total_pages > 1) {
            echo '<div class="pagination">';
            for ($i = 1; $i <= $total_pages; $i++) {
                $class = ($i === $current_page) ? 'current' : '';
                echo "<a class='page-link $class' href='?page=$i'>$i</a>";
            }
            echo '</div>';
        }
    } else {
        echo '<p>No keys available.</p>';
    }

    return ob_get_clean(); // Return the buffered content
}

add_shortcode('gatekeeper_display_keys', 'gatekeeper_display_keys_table');