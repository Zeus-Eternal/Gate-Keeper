jQuery(document).ready(function ($) {
    // Generate Keys Button Click Event
    $('#generate-keys-button').on('click', function () {
        // Make an AJAX request to generate keys
        $.ajax({
            type: 'POST',
            url: ajaxurl, // Use the WordPress AJAX URL
            data: {
                action: 'generate_keys' // Custom action name
            },
            success: function (response) {
                // Handle the response (e.g., display generated keys)
                alert('Keys generated successfully: ' + response);
            },
            error: function (error) {
                // Handle errors
                alert('Error generating keys: ' + error.responseText);
            }
        });
    });

    // JavaScript code for filtering, bulk actions, etc.
});
