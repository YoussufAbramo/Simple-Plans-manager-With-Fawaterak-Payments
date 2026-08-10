<?php
if (!defined('ABSPATH')) exit;

class MSFM_Auth {

    public function __construct() {
        add_shortcode('saas_login_form', array($this, 'render_login_form'));
        add_action('init', array($this, 'handle_magic_link_login'));
        add_action('wp_logout', array($this, 'handle_logout_redirect'));
        add_action('wp_ajax_nopriv_msfm_send_magic_link', array($this, 'send_magic_link'));
        add_action('wp_ajax_msfm_send_magic_link', array($this, 'send_magic_link'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_auth_scripts'));
    }

    public function enqueue_auth_scripts() {
        wp_enqueue_script('msfm-auth-js', MSFM_URL . 'assets/js/auth.js', array('jquery'), MSFM_VERSION, true);
        wp_localize_script('msfm-auth-js', 'msfm_vars', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('msfm_magic_nonce')
        ));
    }

    public function render_login_form() {
        if (is_user_logged_in()) {
            $portal_page_id = MSFM_Settings::get_or_create_page('msfm_portal_page_id', 'My Profile', '[saas_user_portal]', 'my-profile');
            $portal_url     = get_permalink($portal_page_id);
            
            return '<div style="background:#f7fafc; padding:20px; border-radius:8px; border:1px solid #e2e8f0; text-align:center;">
                <p style="margin-bottom:15px; font-size:16px;">You are currently logged in as <strong>' . esc_html(wp_get_current_user()->user_email) . '</strong>.</p>
                <a href="' . esc_url($portal_url) . '" class="button button-primary" style="margin-right:10px;">Go to My Profile</a>
                <a href="' . esc_url(wp_logout_url()) . '" class="button button-secondary">Logout</a>
            </div>';
        }

        ob_start();
        include MSFM_PATH . 'templates/login.php';
        return ob_get_clean();
    }

    public function send_magic_link() {
        check_ajax_referer('msfm_magic_nonce', 'nonce');

        $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
        $user  = get_user_by('email', $email);

        if (!$user) {
            wp_send_json_error('No account registered with this email address.');
        }

        $token = wp_generate_password(20, false);
        set_transient('msfm_magic_' . $token, $user->ID, 15 * MINUTE_IN_SECONDS);

        $login_url = add_query_arg(array('msfm_token' => $token), home_url('/'));

        $subject = 'Your Magic Login Link';
        $message = "Hello,\n\nClick the link below to log in to your account automatically:\n\n" . $login_url . "\n\nNote: This link will expire in 15 minutes.";
        
        wp_mail($email, $subject, $message);
        wp_send_json_success('Magic Login Link sent! Check your inbox.');
    }

    public function handle_magic_link_login() {
        if (isset($_GET['msfm_token'])) {
            $token   = sanitize_text_field($_GET['msfm_token']);
            $user_id = get_transient('msfm_magic_' . $token);

            if ($user_id) {
                delete_transient('msfm_magic_' . $token);
                wp_set_current_user($user_id);
                wp_set_auth_cookie($user_id);

                $portal_page_id = MSFM_Settings::get_or_create_page('msfm_portal_page_id', 'My Profile', '[saas_user_portal]', 'my-profile');
                wp_redirect(get_permalink($portal_page_id));
                exit;
            } else {
                wp_die('Invalid or expired Magic Link token.');
            }
        }
    }

    public function handle_logout_redirect() {
        $login_page_id = MSFM_Settings::get_or_create_page('msfm_login_page_id', 'Login', '[saas_login_form]', 'login');
        $login_url     = get_permalink($login_page_id);
        
        wp_redirect(add_query_arg('logged_out', '1', $login_url));
        exit;
    }
}