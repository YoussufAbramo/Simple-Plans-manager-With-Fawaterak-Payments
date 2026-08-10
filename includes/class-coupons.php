<?php
if (!defined('ABSPATH')) exit;

class MSFM_Coupons {

    public function __construct() {
        add_action('init', array($this, 'register_coupon_cpt'));
        add_action('add_meta_boxes', array($this, 'add_coupon_metaboxes'));
        add_action('save_post', array($this, 'save_coupon_meta'));

        // AJAX verification endpoints for live checkout validation
        add_action('wp_ajax_msfm_apply_coupon', array($this, 'ajax_apply_coupon'));
        add_action('wp_ajax_nopriv_msfm_apply_coupon', array($this, 'ajax_apply_coupon'));
    }

    public static function register_coupon_cpt() {
        $labels = array(
            'name'               => _x('Coupons', 'Post Type General Name', 'qaff-microsaas'),
            'singular_name'      => _x('Coupon', 'Post Type Singular Name', 'qaff-microsaas'),
            'menu_name'          => __('Coupons', 'qaff-microsaas'),
            'all_items'          => __('Coupons', 'qaff-microsaas'),
            'add_new_item'       => __('Add New Coupon', 'qaff-microsaas'),
            'add_new'            => __('Add Coupon', 'qaff-microsaas'),
            'edit_item'          => __('Edit Coupon', 'qaff-microsaas'),
        );

        register_post_type('qaff_coupon', array(
            'labels'              => $labels,
            'public'              => false,
            'show_ui'             => true,
            'show_in_menu'        => 'edit.php?post_type=saas_package',
            'supports'            => array('title'),
            'menu_icon'           => 'dashicons-tickets-alt',
        ));
    }

    public function add_coupon_metaboxes() {
        add_meta_box(
            'msfm_coupon_details',
            'Coupon Configuration',
            array($this, 'render_coupon_metabox'),
            'qaff_coupon',
            'normal',
            'high'
        );
    }

    public function render_coupon_metabox($post) {
        $type        = get_post_meta($post->ID, '_coupon_type', true);
        $amount      = get_post_meta($post->ID, '_coupon_amount', true);
        $usage_limit = get_post_meta($post->ID, '_coupon_usage_limit', true);
        $usage_count = get_post_meta($post->ID, '_coupon_usage_count', true);
        $expiry      = get_post_meta($post->ID, '_coupon_expiry', true);

        wp_nonce_field('msfm_save_coupon', 'msfm_coupon_nonce');
        ?>
        <table class="form-table">
            <tr>
                <th><label>Discount Type</label></th>
                <td>
                    <select name="coupon_type">
                        <option value="percent" <?php selected($type, 'percent'); ?>>Percentage Discount (%)</option>
                        <option value="fixed" <?php selected($type, 'fixed'); ?>>Fixed Amount Discount</option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label>Coupon Amount</label></th>
                <td>
                    <input type="number" step="0.01" name="coupon_amount" value="<?php echo esc_attr($amount); ?>" class="regular-text" required>
                </td>
            </tr>
            <tr>
                <th><label>Usage Limit</label></th>
                <td>
                    <input type="number" name="coupon_usage_limit" value="<?php echo esc_attr($usage_limit); ?>" class="regular-text" placeholder="e.g. 100 (Leave empty for unlimited)">
                    <p class="description">Times used so far: <strong><?php echo intval($usage_count); ?></strong></p>
                </td>
            </tr>
            <tr>
                <th><label>Expiration Date</label></th>
                <td>
                    <input type="date" name="coupon_expiry" value="<?php echo esc_attr($expiry); ?>" class="regular-text">
                </td>
            </tr>
        </table>
        <?php
    }

    public function save_coupon_meta($post_id) {
        if (!isset($_POST['msfm_coupon_nonce']) || !wp_verify_nonce($_POST['msfm_coupon_nonce'], 'msfm_save_coupon')) return;
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;

        $fields = array('coupon_type', 'coupon_amount', 'coupon_usage_limit', 'coupon_expiry');
        foreach ($fields as $f) {
            if (isset($_POST[$f])) {
                update_post_meta($post_id, '_' . $f, sanitize_text_field($_POST[$f]));
            }
        }
    }

    /**
     * AJAX Handler for verifying coupon codes at checkout
     */
    public function ajax_apply_coupon() {
        check_ajax_referer('msfm_checkout_action', 'nonce');

        $code  = isset($_POST['code']) ? strtoupper(sanitize_text_field($_POST['code'])) : '';
        $price = isset($_POST['price']) ? floatval($_POST['price']) : 0;

        $coupon_query = new WP_Query(array(
            'post_type'      => 'qaff_coupon',
            'title'          => $code,
            'posts_per_page' => 1
        ));

        if (!$coupon_query->have_posts()) {
            wp_send_json_error(array('message' => 'Invalid or non-existent coupon code.'));
        }

        $coupon_id   = $coupon_query->posts[0]->ID;
        $type        = get_post_meta($coupon_id, '_coupon_type', true);
        $amount      = floatval(get_post_meta($coupon_id, '_coupon_amount', true));
        $usage_limit = get_post_meta($coupon_id, '_coupon_usage_limit', true);
        $usage_count = intval(get_post_meta($coupon_id, '_coupon_usage_count', true));
        $expiry      = get_post_meta($coupon_id, '_coupon_expiry', true);

        // Check expiration
        if (!empty($expiry) && strtotime($expiry) < strtotime(current_time('Y-m-d'))) {
            wp_send_json_error(array('message' => 'This coupon code has expired.'));
        }

        // Check usage limit
        if ($usage_limit !== '' && $usage_count >= intval($usage_limit)) {
            wp_send_json_error(array('message' => 'This coupon has reached its maximum usage limit.'));
        }

        // Calculate discount
        $discount = 0;
        if ($type === 'percent') {
            $discount = ($price * ($amount / 100));
        } else {
            $discount = $amount;
        }

        if ($discount > $price) {
            $discount = $price;
        }

        $final_price = $price - $discount;

        wp_send_json_success(array(
            'code'           => $code,
            'discount'       => $discount,
            'final_price'    => $final_price,
            'formatted_disc' => MSFM_Settings::format_price(number_format($discount, 2)),
            'formatted_final'=> MSFM_Settings::format_price(number_format($final_price, 2)),
            'message'        => 'Coupon successfully applied!'
        ));
    }
}