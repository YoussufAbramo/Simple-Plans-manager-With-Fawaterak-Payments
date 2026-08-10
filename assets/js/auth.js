jQuery(document).ready(function ($) {
    $('#msfm-magic-form').on('submit', function (e) {
        e.preventDefault();
        
        var $form = $(this);
        var $msg = $('#msfm-login-msg');
        var email = $form.find('input[name="user_email"]').val();

        $msg.html('<p style="color:#666;">Sending magic link...</p>');

        $.ajax({
            url: msfm_vars.ajax_url,
            type: 'POST',
            data: {
                action: 'msfm_send_magic_link',
                email: email,
                nonce: msfm_vars.nonce
            },
            success: function (response) {
                if (response.success) {
                    $msg.html('<p style="color:green;">' + response.data + '</p>');
                    $form[0].reset();
                } else {
                    $msg.html('<p style="color:red;">' + response.data + '</p>');
                }
            },
            error: function () {
                $msg.html('<p style="color:red;">An error occurred. Please try again.</p>');
            }
        });
    });
});