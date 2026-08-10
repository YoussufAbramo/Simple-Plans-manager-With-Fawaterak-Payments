<?php
if (!defined('ABSPATH')) exit;

class MSFM_Fawaterak {

    private function get_access_token() {
        $auth_type = get_option('msfm_fawaterak_auth_type', 'bearer');

        if ($auth_type === 'bearer') {
            return get_option('msfm_fawaterak_vendor_key', '');
        }

        $cached_token = get_transient('msfm_fawaterak_oauth_token');
        if ($cached_token) {
            return $cached_token;
        }

        $client_id     = get_option('msfm_fawaterak_client_id', '');
        $client_secret = get_option('msfm_fawaterak_client_secret', '');
        $token_url     = get_option('msfm_fawaterak_token_url', '');

        if (empty($client_id) || empty($client_secret) || empty($token_url)) {
            return false;
        }

        $response = wp_remote_post($token_url, array(
            'headers' => array(
                'Authorization' => 'Basic ' . base64_encode($client_id . ':' . $client_secret),
                'Content-Type'  => 'application/x-www-form-urlencoded'
            ),
            'body' => array(
                'grant_type'    => 'client_credentials',
                'client_id'     => $client_id,
                'client_secret' => $client_secret
            )
        ));

        if (is_wp_error($response)) {
            return false;
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);

        if (isset($data['access_token'])) {
            $token      = $data['access_token'];
            $expires_in = isset($data['expires_in']) ? (intval($data['expires_in']) - 60) : 3540;
            set_transient('msfm_fawaterak_oauth_token', $token, $expires_in);
            return $token;
        }

        return false;
    }

    public function create_invoice($order_id, $amount, $user_email) {
        $env      = get_option('msfm_fawaterak_env', 'sandbox');
        $currency = get_option('msfm_currency', 'USD');

        $api_key = $this->get_access_token();
        if (empty($api_key)) {
            return false;
        }

        $api_url = ($env === 'live')
            ? 'https://app.fawaterk.com/api/v2/createInvoiceLink'
            : 'https://staging.fawaterk.com/api/v2/createInvoiceLink';

        $body = array(
            'cartTotal' => $amount,
            'currency'  => $currency,
            'customer'  => array(
                'first_name' => 'SaaS User',
                'last_name'  => '#' . $order_id,
                'email'      => $user_email,
                'phone'      => '01000000000'
            ),
            'redirectionUrls' => array(
                'successUrl' => home_url('/thank-you?order_id=' . $order_id),
                'failUrl'    => home_url('/payment-failed?order_id=' . $order_id),
                'pendingUrl' => home_url('/payment-pending')
            ),
            'cartItems' => array(
                array(
                    'name'     => 'Qaff Plan Subscription',
                    'price'    => $amount,
                    'quantity' => 1
                )
            )
        );

        $response = wp_remote_post($api_url, array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type'  => 'application/json'
            ),
            'body' => json_encode($body)
        ));

        if (is_wp_error($response)) {
            return false;
        }

        $result = json_decode(wp_remote_retrieve_body($response), true);

        if (isset($result['status']) && $result['status'] === 'success') {
            global $wpdb;
            $wpdb->update(
                $wpdb->prefix . 'microsaas_orders',
                array(
                    'fawaterak_invoice_id' => $result['data']['invoice_id'],
                    'currency'             => $currency
                ),
                array('id' => $order_id)
            );

            return $result['data']['url'];
        }

        return false;
    }
}