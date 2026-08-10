<?php
if (!defined('ABSPATH')) exit;

class MSFM_Checkout {

    public function __construct() {
        add_shortcode('saas_checkout_button', array($this, 'render_buy_button'));
        add_shortcode('saas_checkout_page', array($this, 'render_checkout_page'));
        add_action('admin_post_nopriv_msfm_process_checkout', array($this, 'process_checkout'));
        add_action('admin_post_msfm_process_checkout', array($this, 'process_checkout'));
    }

    public function render_buy_button($atts, $content = 'Buy Plan') {
        $a = shortcode_atts(array('package_id' => 0), $atts);
        $checkout_page_id = MSFM_Settings::get_or_create_page('msfm_checkout_page_id', 'Checkout', '[saas_checkout_page]', 'checkout');
        $base_url = get_permalink($checkout_page_id);
        
        $checkout_url = add_query_arg('package_id', $a['package_id'], $base_url);
        return '<a href="' . esc_url($checkout_url) . '" class="button msfm-buy-btn">' . esc_html($content) . '</a>';
    }

    public function render_checkout_page() {
        $package_id = isset($_GET['package_id']) ? intval($_GET['package_id']) : 0;
        $packages = get_posts(array('post_type' => 'saas_package', 'numberposts' => -1));

        if (!$package_id && !empty($packages)) {
            $package_id = $packages[0]->ID;
        }

        $monthly_reg  = get_post_meta($package_id, '_monthly_price', true);
        $monthly_sale = get_post_meta($package_id, '_monthly_sale_price', true);
        $annual_reg   = get_post_meta($package_id, '_annual_price', true);
        $annual_sale  = get_post_meta($package_id, '_annual_sale_price', true);

        $monthly = ($monthly_sale !== '' && $monthly_sale !== false) ? $monthly_sale : $monthly_reg;
        $annual  = ($annual_sale !== '' && $annual_sale !== false) ? $annual_sale : $annual_reg;

        ob_start();
        include MSFM_PATH . 'templates/checkout.php';
        return ob_get_clean();
    }

    public function process_checkout() {
        if (!isset($_POST['msfm_checkout_nonce']) || !wp_verify_nonce($_POST['msfm_checkout_nonce'], 'msfm_checkout_action')) {
            wp_die('Security check failed.');
        }

        global $wpdb;

        $package_id     = intval($_POST['package_id']);
        $billing_cycle = sanitize_text_field($_POST['billing_cycle']);
        $payment_method = sanitize_text_field($_POST['payment_method']);
        $coupon_code    = isset($_POST['applied_coupon_code']) ? strtoupper(sanitize_text_field($_POST['applied_coupon_code'])) : '';
        $currency       = get_option('msfm_currency', 'USD');

        $full_name    = sanitize_text_field($_POST['full_name']);
        $phone_number = sanitize_text_field($_POST['phone_number']);
        $country      = isset($_POST['country']) ? sanitize_text_field($_POST['country']) : '';
        $address      = isset($_POST['address']) ? sanitize_textarea_field($_POST['address']) : '';
        $zip_code     = isset($_POST['zip_code']) ? sanitize_text_field($_POST['zip_code']) : '';
        
        if (is_user_logged_in()) {
            $user_id = get_current_user_id();
        } else {
            $email    = sanitize_email($_POST['user_email']);
            $password = $_POST['user_password'];

            if (email_exists($email)) {
                wp_die('An account with this email already exists. Please log in first.');
            }

            $user_id = wp_create_user($email, $password, $email);
            wp_set_current_user($user_id);
            wp_set_auth_cookie($user_id);
        }

        if (!empty($full_name)) {
            $name_parts = explode(' ', $full_name, 2);
            wp_update_user(array(
                'ID'         => $user_id,
                'first_name' => $name_parts[0],
                'last_name'  => isset($name_parts[1]) ? $name_parts[1] : ''
            ));
        }

        // Calculate price
        if ($billing_cycle === 'annual') {
            $sale    = get_post_meta($package_id, '_annual_sale_price', true);
            $regular = get_post_meta($package_id, '_annual_price', true);
        } else {
            $sale    = get_post_meta($package_id, '_monthly_sale_price', true);
            $regular = get_post_meta($package_id, '_monthly_price', true);
        }

        $base_amount     = ($sale !== '' && $sale !== false) ? floatval($sale) : floatval($regular);
        $discount_amount = 0.00;

        // Process Coupon if provided
        if (!empty($coupon_code)) {
            $c_query = new WP_Query(array('post_type' => 'qaff_coupon', 'title' => $coupon_code, 'posts_per_page' => 1));
            if ($c_query->have_posts()) {
                $c_id   = $c_query->posts[0]->ID;
                $c_type = get_post_meta($c_id, '_coupon_type', true);
                $c_amt  = floatval(get_post_meta($c_id, '_coupon_amount', true));

                if ($c_type === 'percent') {
                    $discount_amount = ($base_amount * ($c_amt / 100));
                } else {
                    $discount_amount = $c_amt;
                }

                if ($discount_amount > $base_amount) {
                    $discount_amount = $base_amount;
                }

                // Increment redemption count
                $count = intval(get_post_meta($c_id, '_coupon_usage_count', true));
                update_post_meta($c_id, '_coupon_usage_count', $count + 1);
            }
        }

        $final_amount = $base_amount - $discount_amount;
        $status       = ($payment_method === 'cod') ? 'processing' : 'pending';

        $wpdb->insert($wpdb->prefix . 'microsaas_orders', array(
            'user_id'         => $user_id,
            'package_id'      => $package_id,
            'billing_cycle'   => $billing_cycle,
            'amount'          => $final_amount,
            'discount_amount' => $discount_amount,
            'coupon_code'     => $coupon_code,
            'currency'        => $currency,
            'payment_status'  => $status,
            'payment_method'  => $payment_method,
            'full_name'       => $full_name,
            'phone_number'    => $phone_number,
            'country'         => $country,
            'address'         => $address,
            'zip_code'        => $zip_code,
        ));

        $order_id = $wpdb->insert_id;

        if ($payment_method === 'cod') {
            $portal_page_id = MSFM_Settings::get_or_create_page('msfm_portal_page_id', 'My Profile', '[saas_user_portal]', 'my-profile');
            wp_redirect(add_query_arg('order_success', $order_id, get_permalink($portal_page_id)));
            exit;
        }

        $payment_handler = new MSFM_Fawaterak();
        $redirect_url = $payment_handler->create_invoice($order_id, $final_amount, get_userdata($user_id)->user_email);

        if ($redirect_url) {
            wp_redirect($redirect_url);
            exit;
        } else {
            wp_die('Fawaterak Gateway Connection Failed. Please check your API settings.');
        }
    }
}