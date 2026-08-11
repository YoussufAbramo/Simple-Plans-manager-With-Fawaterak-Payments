<?php
if (!defined('ABSPATH')) exit;

class MSFM_User_Portal {

    public function __construct() {
        add_shortcode('saas_user_portal', array($this, 'render_user_portal'));
        add_action('admin_post_msfm_update_user_profile', array($this, 'handle_profile_update'));
    }

    public function handle_profile_update() {
        if (!is_user_logged_in() || !isset($_POST['msfm_profile_nonce']) || !wp_verify_nonce($_POST['msfm_profile_nonce'], 'msfm_profile_action')) {
            wp_die('Unauthorized request.');
        }

        $user_id    = get_current_user_id();
        $first_name = sanitize_text_field($_POST['first_name'] ?? '');
        $last_name  = sanitize_text_field($_POST['last_name'] ?? '');
        $company    = sanitize_text_field($_POST['company'] ?? '');
        $phone      = sanitize_text_field($_POST['phone_number'] ?? '');
        $country    = sanitize_text_field($_POST['country'] ?? '');

        wp_update_user(array(
            'ID'         => $user_id,
            'first_name' => $first_name,
            'last_name'  => $last_name,
            'display_name' => trim($first_name . ' ' . $last_name)
        ));

        update_user_meta($user_id, 'billing_company', $company);
        update_user_meta($user_id, 'billing_phone', $phone);
        update_user_meta($user_id, 'billing_country', $country);

        $portal_page_id = MSFM_Settings::get_or_create_page('msfm_portal_page_id', 'My Profile', '[saas_user_portal]', 'my-profile');
        wp_redirect(add_query_arg('profile_updated', '1', get_permalink($portal_page_id)));
        exit;
    }

    public function render_user_portal() {
        if (!is_user_logged_in()) {
            $login_page_id = get_option('msfm_login_page_id');
            $login_url = $login_page_id ? get_permalink($login_page_id) : wp_login_url();
            return '<div style="max-width:500px; margin:40px auto; padding:25px; background:#fff; border:1px solid #e2e8f0; border-radius:8px; text-align:center; font-family:sans-serif;">
                <h3 style="margin-top:0; color:#2d3748;">Access Restricted</h3>
                <p style="color:#718096;">Please log in to view your subscriber profile and active plans.</p>
                <a href="' . esc_url($login_url) . '" class="button" style="display:inline-block; padding:10px 20px; background:#3182ce; color:#fff; text-decoration:none; border-radius:5px; font-weight:bold;">Log In to Account &rarr;</a>
            </div>';
        }

        global $wpdb;
        $current_user = wp_get_current_user();
        $user_id      = $current_user->ID;

        $orders = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}microsaas_orders WHERE user_id = %d ORDER BY created_at DESC",
            $user_id
        ));

        $active_sub = null;
        foreach ($orders as $order) {
            if (in_array(strtolower($order->payment_status), array('completed', 'paid'))) {
                $created_time = strtotime($order->created_at);
                $days_limit   = ($order->billing_cycle === 'annual') ? 365 : 30;
                $expire_time  = strtotime("+{$days_limit} days", $created_time);

                if (time() < $expire_time) {
                    $active_sub = $order;
                    $active_sub->expire_timestamp = $expire_time;
                    break;
                }
            }
        }

        $is_active      = ($active_sub !== null);
        $days_remaining = $is_active ? max(0, ceil(($active_sub->expire_timestamp - time()) / DAY_IN_SECONDS)) : 0;

        ob_start();
        include MSFM_PATH . 'templates/portal.php';
        return ob_get_clean();
    }
}