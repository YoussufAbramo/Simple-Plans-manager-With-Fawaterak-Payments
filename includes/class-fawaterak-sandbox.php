<?php
if (!defined('ABSPATH')) exit;

class MSFM_Fawaterak_Sandbox {

    private $api_key = 'SANDBOX_VENDOR_KEY_HERE'; // Replace with Fawaterak Sandbox API key
    private $api_url = 'https://staging.fawaterk.com/api/v2/createInvoiceLink'; // Staging Endpoint

    public function create_invoice($order_id, $amount, $user_email) {
        $body = array(
            'cartTotal' => $amount,
            'currency' => 'EGP',
            'customer' => array(
                'first_name' => 'SaaS User',
                'last_name' => '#' . $order_id,
                'email' => $user_email,
                'phone' => '01000000000'
            ),
            'redirectionUrls' => array(
                'successUrl' => home_url('/thank-you?order_id=' . $order_id),
                'failUrl' => home_url('/payment-failed?order_id=' . $order_id),
                'pendingUrl' => home_url('/payment-pending')
            ),
            'cartItems' => array(
                array(
                    'name' => 'SaaS Package Subscription',
                    'price' => $amount,
                    'quantity' => 1
                )
            )
        );

        $response = wp_remote_post($this->api_url, array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode($body)
        ));

        if (is_wp_error($response)) {
            return false;
        }

        $result = json_decode(wp_remote_retrieve_body($response), true);

        if (isset($result['status']) && $result['status'] === 'success') {
            // Save Fawaterak Invoice ID to MySQL table
            global $wpdb;
            $wpdb->update(
                $wpdb->prefix . 'microsaas_orders',
                array('fawaterak_invoice_id' => $result['data']['invoice_id']),
                array('id' => $order_id)
            );

            return $result['data']['url']; // Redirect user directly to hosted Fawaterak page
        }

        return false;
    }
}