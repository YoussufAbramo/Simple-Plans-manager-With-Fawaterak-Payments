<?php
if (!defined('ABSPATH')) exit;

class MSFM_Fawaterak {

    public function __construct() {
        add_action('rest_api_init', array($this, 'register_webhook_route'));
    }

    public function register_webhook_route() {
        register_rest_route('qaff/v1', '/fawaterak-webhook', array(
            'methods'             => 'POST',
            'callback'            => array($this, 'handle_webhook_payload'),
            'permission_callback' => '__return_true',
        ));
    }

    public function handle_webhook_payload($request) {
        $data = $request->get_json_params();

        if (empty($data)) {
            $data = $request->get_body_params();
        }

        $invoice_id     = isset($data['invoice_id']) ? sanitize_text_field($data['invoice_id']) : '';
        $invoice_key    = isset($data['invoice_key']) ? sanitize_text_field($data['invoice_key']) : '';
        $payment_method = isset($data['payment_method']) ? sanitize_text_field($data['payment_method']) : '';
        $status         = isset($data['invoice_status']) ? strtolower(sanitize_text_field($data['invoice_status'])) : '';
        $hash_key_rec   = isset($data['hashKey']) ? sanitize_text_field($data['hashKey']) : '';

        // Generate HMAC SHA256 Signature to validate sender
        $secret_key  = get_option('msfm_fawaterak_api_key', '');
        $query_param = "InvoiceId={$invoice_id}&InvoiceKey={$invoice_key}&PaymentMethod={$payment_method}";
        $hash_key_gen = hash_hmac('sha256', $query_param, $secret_key, false);

        if ($hash_key_rec !== $hash_key_gen) {
            return new WP_REST_Response(array('status' => 'error', 'message' => 'Invalid webhook signature.'), 401);
        }

        if (!empty($invoice_id) && in_array($status, array('paid', 'success', 'completed'))) {
            global $wpdb;
            $updated = $wpdb->update(
                $wpdb->prefix . 'microsaas_orders',
                array('payment_status' => 'completed'),
                array('fawaterak_invoice_id' => $invoice_id)
            );

            if ($updated !== false) {
                return new WP_REST_Response(array('status' => 'success', 'message' => 'Order marked as completed'), 200);
            }
        }

        return new WP_REST_Response(array('status' => 'ignored', 'message' => 'Invoice status not paid or not found.'), 200);
    }

    public function create_invoice($order_id, $amount, $user_email, $first_name, $last_name, $phone, $plan_name, $address = '') {
        $env      = get_option('msfm_fawaterak_env', 'sandbox');
        $currency = get_option('msfm_currency', 'USD');
        $api_key  = get_option('msfm_fawaterak_api_key', '');

        if (empty($api_key)) {
            return array('error' => 'Your Fawaterak API Key is missing. Please save it in the Qaff Settings -> Payments tab.');
        }

        $api_url = ($env === 'live')
            ? 'https://app.fawaterk.com/api/v2/createInvoiceLink'
            : 'https://staging.fawaterk.com/api/v2/createInvoiceLink';

        $portal_page_id   = get_option('msfm_portal_page_id');
        $checkout_page_id = get_option('msfm_checkout_page_id');
        
        $base_checkout_url = $checkout_page_id ? get_permalink($checkout_page_id) : home_url('/');
        
        $success_url = $portal_page_id ? add_query_arg('order_success', $order_id, get_permalink($portal_page_id)) : home_url('/?order_success=' . $order_id);
        $fail_url    = add_query_arg(array('payment_failed' => '1', 'order_id' => $order_id), $base_checkout_url);
        $pending_url = add_query_arg(array('payment_pending' => '1', 'order_id' => $order_id), $base_checkout_url);

        // Strict String Parsing for the payload to prevent API 422 Bad Request errors
        $body = array(
            'cartTotal' => strval($amount),
            'currency'  => $currency,
            'customer'  => array(
                'first_name' => strval($first_name),
                'last_name'  => strval($last_name),
                'email'      => strval($user_email),
                'phone'      => strval($phone),
                'address'    => strval($address)
            ),
            'redirectionUrls' => array(
                'successUrl' => esc_url_raw($success_url),
                'failUrl'    => esc_url_raw($fail_url),
                'pendingUrl' => esc_url_raw($pending_url)
            ),
            'cartItems' => array(
                array(
                    'name'     => strval($plan_name),
                    'price'    => strval($amount),
                    'quantity' => "1"
                )
            )
        );

        $response = wp_remote_post($api_url, array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json'
            ),
            'body'    => json_encode($body),
            'timeout' => 30 // Increased from 5s to 30s to prevent cURL error 28 timeouts
        ));

        if (is_wp_error($response)) {
            return array('error' => $response->get_error_message());
        }

        $result = json_decode(wp_remote_retrieve_body($response), true);

        if (isset($result['status']) && $result['status'] === 'success' && isset($result['data']['url'])) {
            global $wpdb;
            $invoice_id = isset($result['data']['invoice_id']) ? $result['data']['invoice_id'] : '';
            $wpdb->update(
                $wpdb->prefix . 'microsaas_orders',
                array(
                    'fawaterak_invoice_id' => $invoice_id,
                    'currency'             => $currency
                ),
                array('id' => $order_id)
            );

            return $result['data']['url'];
        }

        // Output exact Fawaterak API Error Message for staging/live issues
        $error_msg = isset($result['message']) ? (is_array($result['message']) ? json_encode($result['message']) : $result['message']) : json_encode($result);
        return array('error' => 'Fawaterak API Rejected Request: ' . $error_msg);
    }
}