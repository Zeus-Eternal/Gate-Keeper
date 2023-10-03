<?php
// Shortcode handler for invitation form
function gatekeeper_send_invite_shortcode() {
    ob_start();

    if (isset($_POST['send_invite'])) {
        $invite_result = gatekeeper_process_invite();

        if (is_wp_error($invite_result)) {
            foreach ($invite_result->get_error_messages() as $error_message) {
                echo '<p class="error">' . esc_html($error_message) . '</p>';
            }
        } elseif ($invite_result === true) {
            echo '<p class="success">Invitation sent successfully!</p>';
        }
    } else {
        ?>
        <form id="invitation-form" method="post" action="">
            <label for="invitee_email">Invitee's Email:</label>
            <input type="email" id="invitee_email" name="invitee_email" required>

            <input type="submit" name="send_invite" value="Send Invitation">
        </form>
        <?php
    }

    return ob_get_clean();
}

// Add shortcode for registration form
add_shortcode('gatekeeper_registration', 'gatekeeper_registration_shortcode');
