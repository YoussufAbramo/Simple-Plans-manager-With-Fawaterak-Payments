<?php
if (!defined('ABSPATH')) exit;

class MSFM_Notifications {

    public function __construct() {
        add_action('msfm_daily_expiration_check', array($this, 'process_expirations'));
    }

    public function process_expirations() {
        if (get_option('msfm_enable_renewal_emails', '1') !== '1') {
            return;
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'microsaas_orders';
        $orders = $wpdb->get_results("SELECT * FROM $table_name WHERE payment_status IN ('completed', 'paid', 'processing')");

        $checkout_page_id = get_option('msfm_checkout_page_id');
        if (!$checkout_page_id) return;
        
        $checkout_url = get_permalink($checkout_page_id);

        foreach ($orders as $order) {
            if (get_option('_msfm_renewal_sent_order_' . $order->id)) {
                continue;
            }

            $created_time = strtotime($order->created_at);
            $duration_days = ($order->billing_cycle === 'annual') ? 365 : 30;
            $expiration_time = strtotime("+{$duration_days} days", $created_time);

            $days_until_expiration = floor(($expiration_time - time()) / DAY_IN_SECONDS);

            if ($days_until_expiration === 3) {
                $user = get_userdata($order->user_id);
                if (!$user) continue;

                $package = get_post($order->package_id);
                $package_name = $package ? $package->post_title : 'Subscription Plan';

                $renew_link = add_query_arg(array(
                    'package_id' => $order->package_id,
                    'renew_order' => $order->id
                ), $checkout_url);

                $to = $user->user_email;
                $subject = "Action Required: Your {$package_name} subscription expires in 3 days";
                
                $customer_name = esc_html($order->full_name ? $order->full_name : $user->display_name);
                $formatted_date = date('F j, Y', $expiration_time);
                $store_name = get_bloginfo('name');

                $message = "
                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; border: 1px solid #e2e8f0; border-radius: 8px; padding: 20px; background-color: #ffffff;'>
                    <h2 style='color: #2d3748; margin-top: 0; border-bottom: 2px solid #e2e8f0; padding-bottom: 10px;'>Subscription Expiring Soon</h2>
                    <p style='color: #4a5568; font-size: 16px;'>Hello <strong>{$customer_name}</strong>,</p>
                    <p style='color: #4a5568; font-size: 16px;'>Your subscription for <strong>{$package_name}</strong> is set to expire in exactly 3 days on <strong style='color:#e53e3e;'>{$formatted_date}</strong>.</p>
                    <p style='color: #4a5568; font-size: 16px;'>To avoid any interruption in your service, please renew your subscription by clicking the button below:</p>
                    <p style='text-align: center; margin: 30px 0;'>
                        <a href='" . esc_url($renew_link) . "' style='background-color: #3182ce; color: #ffffff; padding: 14px 28px; text-decoration: none; border-radius: 6px; font-weight: bold; display: inline-block; font-size: 16px;'>Renew Subscription Now</a>
                    </p>
                    <p style='color: #718096; font-size: 14px;'>If you have already renewed or no longer wish to continue, please ignore this email.</p>
                    <hr style='border: 0; border-top: 1px solid #e2e8f0; margin: 20px 0;' />
                    <p style='color: #a0aec0; font-size: 13px; text-align: center;'>Thank you,<br><strong>{$store_name}</strong></p>
                </div>
                ";

                $headers = array('Content-Type: text/html; charset=UTF-8');

                $mail_sent = wp_mail($to, $subject, $message, $headers);

                if ($mail_sent) {
                    update_option('_msfm_renewal_sent_order_' . $order->id, time());
                }
            }
        }
    }
}