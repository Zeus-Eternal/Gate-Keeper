<?php
// Function to process registration form submission
function gatekeeper_process_registration() {
    if (isset($_POST['register'])) {
        $errors = new WP_Error();

        $username = sanitize_user($_POST['username']);
        $email = sanitize_email($_POST['email']);
        $password = sanitize_text_field($_POST['password']);

        if (empty($username)) {
            $errors->add('username_required', 'Username is required.');
        }

        if (empty($email)) {
            $errors->add('email_required', 'Email is required.');
        } elseif (!is_email($email)) {
            $errors->add('invalid_email', 'Invalid email address.');
        }

        if (empty($password)) {
            $errors->add('password_required', 'Password is required.');
        }

        if ($errors->has_errors()) {
            return $errors;
        }

        $user_data = array(
            'user_login' => $username,
            'user_email' => $email,
            'user_pass' => $password,
        );

        $user_id = wp_insert_user($user_data);

        if (!is_wp_error($user_id)) {
            wp_set_auth_cookie($user_id, true);
            wp_redirect('your-success-page-url');
            exit;
        } else {
            return $user_id;
        }
    }

    return '';
}
