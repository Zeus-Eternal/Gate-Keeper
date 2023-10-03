<?php
// Shortcode handler for registration form
function gatekeeper_registration_shortcode() {
    ob_start();

    if (isset($_POST['register'])) {
        $registration_result = gatekeeper_process_registration();

        if (is_wp_error($registration_result)) {
            foreach ($registration_result->get_error_messages() as $error_message) {
                echo '<p class="error">' . esc_html($error_message) . '</p>';
            }
        } elseif ($registration_result === true) {
            echo '<p class="success">Registration successful! You can now log in.</p>';
        }
    } else {
        ?>
        <form id="registration-form" method="post" action="">
            <label for="username">Username:</label>
            <input type="text" id="username" name="username" required>

            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required>

            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required>

            <input type="submit" name="register" value="Register">
        </form>
        <?php
    }

    return ob_get_clean();
}
