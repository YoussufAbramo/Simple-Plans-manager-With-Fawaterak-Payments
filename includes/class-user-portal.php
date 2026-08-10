<?php
if (!defined('ABSPATH')) exit;

class MSFM_User_Portal {

    public function __construct() {
        add_shortcode('saas_user_portal', array($this, 'render_portal'));
    }

    public function render_portal() {
        if (!is_user_logged_in()) {
            $login_page_id = MSFM_Settings::get_or_create_page('msfm_login_page_id', 'Login', '[saas_login_form]', 'login');
            $login_url     = get_permalink($login_page_id);
            return '<div style="text-align:center; padding:30px; background:#f7fafc; border-radius:8px; border:1px solid #e2e8f0;">
                <p style="font-size:16px;">Please <a href="' . esc_url($login_url) . '" style="color:#2b6cb0; font-weight:bold;">log in</a> to access My Profile.</p>
            </div>';
        }

        $current_user = wp_get_current_user();

        global $wpdb;
        $orders = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}microsaas_orders WHERE user_id = %d ORDER BY created_at DESC",
            $current_user->ID
        ));

        $active_sub = null;
        $days_remaining = 0;
        $is_active = false;

        if (!empty($orders)) {
            foreach ($orders as $ord) {
                if (in_array(strtolower($ord->payment_status), array('completed', 'paid', 'processing'))) {
                    $created_timestamp = strtotime($ord->created_at);
                    $duration_days     = ($ord->billing_cycle === 'annual') ? 365 : 30;
                    $expiration_time   = strtotime("+{$duration_days} days", $created_timestamp);

                    if (time() < $expiration_time) {
                        $active_sub     = $ord;
                        $days_remaining = ceil(($expiration_time - time()) / DAY_IN_SECONDS);
                        $is_active      = true;
                        break;
                    }
                }
            }
        }

        ob_start();
        include MSFM_PATH . 'templates/portal.php';
        return ob_get_clean();
    }
}