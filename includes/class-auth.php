<?php
if (!defined('ABSPATH')) exit;

class MSFM_Auth {

    public function __construct() {
        add_shortcode('saas_login_form', array($this, 'render_login_form'));
        add_action('admin_post_nopriv_msfm_password_login', array($this, 'process_password_login'));
        add_action('admin_post_msfm_password_login', array($this, 'process_password_login'));
        add_action('admin_post_nopriv_msfm_send_magic_link', array($this, 'process_magic_link_request'));
        add_action('init', array($this, 'verify_magic_link'));
    }

    public function render_login_form() {
        if (is_user_logged_in()) {
            $portal_page_id = get_option('msfm_portal_page_id');
            $portal_url = $portal_page_id ? get_permalink($portal_page_id) : home_url('/my-profile');
            return '<div class="smpl_pkg_mngr-notice smpl_pkg_mngr-notice-info" style="max-width:480px; margin:30px auto; padding:20px; background:#ebf8ff; border:1px solid #bee3f8; border-radius:8px; text-align:center;">
                <p style="margin:0 0 15px 0; color:#2b6cb0;">You are already logged in.</p>
                <a href="' . esc_url($portal_url) . '" class="smpl_pkg_mngr-btn smpl_pkg_mngr-btn-primary" style="display:inline-block; padding:10px 20px; background:#3182ce; color:#fff; text-decoration:none; border-radius:6px; font-weight:bold;">Go to My Profile &rarr;</a>
            </div>';
        }

        $auth_mode = isset($_GET['mode']) && $_GET['mode'] === 'magic' ? 'magic' : 'password';

        ob_start();
        ?>
        <div class="smpl_pkg_mngr-login-wrapper" style="max-width: 440px; margin: 40px auto; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #ffffff; padding: 35px; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.05);">
            
            <?php if (isset($_GET['login_error'])): ?>
                <div class="smpl_pkg_mngr-alert smpl_pkg_mngr-alert-error" style="background: #fed7d7; color: #9b2c2c; padding: 12px 15px; border-radius: 6px; font-size: 13px; margin-bottom: 20px;">
                    <?php echo esc_html(urldecode($_GET['login_error'])); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['magic_sent'])): ?>
                <div class="smpl_pkg_mngr-alert smpl_pkg_mngr-alert-success" style="background: #c6f6d5; color: #22543d; padding: 12px 15px; border-radius: 6px; font-size: 13px; margin-bottom: 20px;">
                    Magic login link sent! Please check your email inbox.
                </div>
            <?php endif; ?>

            <div class="smpl_pkg_mngr-login-header" style="text-align: center; margin-bottom: 25px;">
                <h2 class="smpl_pkg_mngr-login-title" style="margin: 0 0 8px 0; color: #1a202c; font-size: 22px;">Account Login</h2>
                <p class="smpl_pkg_mngr-login-subtitle" style="margin: 0; color: #718096; font-size: 14px;">Access your active subscriptions and invoices</p>
            </div>

            <!-- Login Method Tabs -->
            <div class="smpl_pkg_mngr-login-tabs" style="display: flex; background: #f8fafc; padding: 4px; border-radius: 8px; margin-bottom: 25px; border: 1px solid #e2e8f0;">
                <a href="?mode=password" class="smpl_pkg_mngr-tab-btn <?php echo $auth_mode === 'password' ? 'smpl_pkg_mngr-tab-active' : ''; ?>" style="flex: 1; text-align: center; padding: 8px; font-size: 13px; font-weight: 600; text-decoration: none; border-radius: 6px; <?php echo $auth_mode === 'password' ? 'background: #ffffff; color: #2b6cb0; box-shadow: 0 1px 3px rgba(0,0,0,0.05);' : 'color: #718096;'; ?>">Password Login</a>
                <a href="?mode=magic" class="smpl_pkg_mngr-tab-btn <?php echo $auth_mode === 'magic' ? 'smpl_pkg_mngr-tab-active' : ''; ?>" style="flex: 1; text-align: center; padding: 8px; font-size: 13px; font-weight: 600; text-decoration: none; border-radius: 6px; <?php echo $auth_mode === 'magic' ? 'background: #ffffff; color: #2b6cb0; box-shadow: 0 1px 3px rgba(0,0,0,0.05);' : 'color: #718096;'; ?>">Magic Link</a>
            </div>

            <?php if ($auth_mode === 'password'): ?>
                <!-- Password-Based Login Form -->
                <form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="POST" class="smpl_pkg_mngr-form smpl_pkg_mngr-login-form">
                    <?php wp_nonce_field('msfm_password_login_action', 'msfm_pwd_nonce'); ?>
                    <input type="hidden" name="action" value="msfm_password_login">

                    <div class="smpl_pkg_mngr-form-group" style="margin-bottom: 18px;">
                        <label class="smpl_pkg_mngr-form-label" style="display: block; font-size: 13px; font-weight: 600; color: #4a5568; margin-bottom: 6px;">Email or Username</label>
                        <input type="text" name="user_log" required class="smpl_pkg_mngr-input-text" style="width: 100%; padding: 11px; border: 1px solid #cbd5e0; border-radius: 6px; box-sizing: border-box;">
                    </div>

                    <div class="smpl_pkg_mngr-form-group" style="margin-bottom: 22px;">
                        <label class="smpl_pkg_mngr-form-label" style="display: block; font-size: 13px; font-weight: 600; color: #4a5568; margin-bottom: 6px;">Password</label>
                        <input type="password" name="user_pwd" required class="smpl_pkg_mngr-input-password" style="width: 100%; padding: 11px; border: 1px solid #cbd5e0; border-radius: 6px; box-sizing: border-box;">
                    </div>

                    <button type="submit" class="smpl_pkg_mngr-btn smpl_pkg_mngr-btn-submit" style="width: 100%; background: #3182ce; color: #ffffff; padding: 12px; border: none; border-radius: 6px; font-weight: bold; font-size: 15px; cursor: pointer;">
                        Log In
                    </button>
                </form>
            <?php else: ?>
                <!-- Passwordless Magic Link Form -->
                <form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="POST" class="smpl_pkg_mngr-form smpl_pkg_mngr-magic-form">
                    <?php wp_nonce_field('msfm_magic_link_action', 'msfm_magic_nonce'); ?>
                    <input type="hidden" name="action" value="msfm_send_magic_link">

                    <div class="smpl_pkg_mngr-form-group" style="margin-bottom: 22px;">
                        <label class="smpl_pkg_mngr-form-label" style="display: block; font-size: 13px; font-weight: 600; color: #4a5568; margin-bottom: 6px;">Email Address</label>
                        <input type="email" name="user_email" required class="smpl_pkg_mngr-input-email" placeholder="name@example.com" style="width: 100%; padding: 11px; border: 1px solid #cbd5e0; border-radius: 6px; box-sizing: border-box;">
                    </div>

                    <button type="submit" class="smpl_pkg_mngr-btn smpl_pkg_mngr-btn-submit" style="width: 100%; background: #3182ce; color: #ffffff; padding: 12px; border: none; border-radius: 6px; font-weight: bold; font-size: 15px; cursor: pointer;">
                        Send Magic Login Link
                    </button>
                </form>
            <?php endif; ?>

        </div>
        <?php
        return ob_get_clean();
    }

    public function process_password_login() {
        if (!isset($_POST['msfm_pwd_nonce']) || !wp_verify_nonce($_POST['msfm_pwd_nonce'], 'msfm_password_login_action')) {
            wp_die('Security check failed.');
        }

        $login_page_id = get_option('msfm_login_page_id');
        $base_login_url = $login_page_id ? get_permalink($login_page_id) : home_url('/login');

        $creds = array(
            'user_login'    => sanitize_text_field($_POST['user_log']),
            'user_password' => $_POST['user_pwd'],
            'remember'      => true
        );

        $user = wp_signon($creds, is_ssl());

        if (is_wp_error($user)) {
            $error_msg = urlencode($user->get_error_message());
            wp_redirect(add_query_arg(array('login_error' => $error_msg, 'mode' => 'password'), $base_login_url));
            exit;
        }

        $portal_page_id = get_option('msfm_portal_page_id');
        $portal_url = $portal_page_id ? get_permalink($portal_page_id) : home_url('/my-profile');
        wp_redirect($portal_url);
        exit;
    }

    public function process_magic_link_request() {
        if (!isset($_POST['msfm_magic_nonce']) || !wp_verify_nonce($_POST['msfm_magic_nonce'], 'msfm_magic_link_action')) {
            wp_die('Security check failed.');
        }

        $login_page_id  = get_option('msfm_login_page_id');
        $base_login_url = $login_page_id ? get_permalink($login_page_id) : home_url('/login');
        $email          = sanitize_email($_POST['user_email']);

        $user = get_user_by('email', $email);
        if (!$user) {
            wp_redirect(add_query_arg(array('login_error' => urlencode('No account associated with this email address.'), 'mode' => 'magic'), $base_login_url));
            exit;
        }

        $token = wp_generate_password(24, false);
        set_transient('msfm_magic_token_' . $token, $user->ID, 15 * MINUTE_IN_SECONDS);

        $magic_url = add_query_arg(array(
            'msfm_action' => 'verify_magic',
            'token'       => $token
        ), $base_login_url);

        $subject = 'Your Magic Login Link - ' . get_bloginfo('name');
        $message = "Hello,\n\nClick the link below to automatically log in to your account:\n\n" . $magic_url . "\n\nThis link will expire in 15 minutes.";

        wp_mail($email, $subject, $message);

        wp_redirect(add_query_arg(array('magic_sent' => '1', 'mode' => 'magic'), $base_login_url));
        exit;
    }

    public function verify_magic_link() {
        if (isset($_GET['msfm_action']) && $_GET['msfm_action'] === 'verify_magic' && isset($_GET['token'])) {
            $token   = sanitize_text_field($_GET['token']);
            $user_id = get_transient('msfm_magic_token_' . $token);

            if ($user_id) {
                delete_transient('msfm_magic_token_' . $token);
                wp_set_current_user($user_id);
                wp_set_auth_cookie($user_id);

                $portal_page_id = get_option('msfm_portal_page_id');
                $portal_url = $portal_page_id ? get_permalink($portal_page_id) : home_url('/my-profile');
                wp_redirect($portal_url);
                exit;
            } else {
                $login_page_id  = get_option('msfm_login_page_id');
                $base_login_url = $login_page_id ? get_permalink($login_page_id) : home_url('/login');
                wp_redirect(add_query_arg(array('login_error' => urlencode('Invalid or expired Magic Link. Please try again.'), 'mode' => 'magic'), $base_login_url));
                exit;
            }
        }
    }
}