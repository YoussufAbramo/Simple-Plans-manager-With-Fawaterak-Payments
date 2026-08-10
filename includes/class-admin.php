<?php
if (!defined('ABSPATH')) exit;

class MSFM_Admin {

    public function __construct() {
        add_action('init', array($this, 'register_package_cpt'));
        add_action('add_meta_boxes', array($this, 'add_package_metaboxes'));
        add_action('save_post', array($this, 'save_package_meta'));
        add_action('admin_menu', array($this, 'add_orders_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        add_action('admin_post_msfm_update_order_status', array($this, 'handle_update_order_status'));

        add_filter('manage_saas_package_posts_columns', array($this, 'set_custom_plan_columns'));
        add_action('manage_saas_package_posts_custom_column', array($this, 'render_custom_plan_columns'), 10, 2);
    }

    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'msfm-orders') !== false) {
            add_thickbox();
        }
    }

    public static function register_package_cpt() {
        $labels = array(
            'name'                  => _x('Plans Manager', 'Post Type General Name', 'simple-plans-manager'),
            'singular_name'         => _x('Plan', 'Post Type Singular Name', 'simple-plans-manager'),
            'menu_name'             => __('Plans Manager', 'simple-plans-manager'),
            'name_admin_bar'        => __('Plan', 'simple-plans-manager'),
            'archives'              => __('Plan Archives', 'simple-plans-manager'),
            'all_items'             => __('All Plans', 'simple-plans-manager'),
            'add_new_item'          => __('Add New Plan', 'simple-plans-manager'),
            'add_new'               => __('Add New Plan', 'simple-plans-manager'),
            'new_item'              => __('New Plan', 'simple-plans-manager'),
            'edit_item'             => __('Edit Plan', 'simple-plans-manager'),
            'update_item'           => __('Update Plan', 'simple-plans-manager'),
            'view_item'             => __('View Plan', 'simple-plans-manager'),
            'search_items'          => __('Search Plans', 'simple-plans-manager'),
        );

        register_post_type('saas_package', array(
            'label'                 => __('Plan', 'simple-plans-manager'),
            'description'           => __('Subscription Pricing Plans', 'simple-plans-manager'),
            'labels'                => $labels,
            'public'                => true,
            'show_in_menu'          => true,
            'supports'              => array('title', 'editor'),
            'menu_icon'             => 'dashicons-products',
            'has_archive'           => false,
            'rewrite'               => array('slug' => 'plans'),
        ));
    }

    public function set_custom_plan_columns($columns) {
        $new_columns = array();
        $new_columns['cb']                 = $columns['cb'];
        $new_columns['title']              = __('Plan Name', 'simple-plans-manager');
        $new_columns['monthly_price']      = __('Monthly Price', 'simple-plans-manager');
        $new_columns['monthly_sale_price'] = __('Monthly Sale', 'simple-plans-manager');
        $new_columns['annual_price']       = __('Annual Price', 'simple-plans-manager');
        $new_columns['annual_sale_price']  = __('Annual Sale', 'simple-plans-manager');
        $new_columns['shortcode']          = __('Shortcode', 'simple-plans-manager');
        $new_columns['date']               = $columns['date'];

        return $new_columns;
    }

    public function render_custom_plan_columns($column, $post_id) {
        switch ($column) {
            case 'monthly_price':
                $regular = get_post_meta($post_id, '_monthly_price', true);
                echo $regular ? esc_html(MSFM_Settings::format_price($regular)) : '<em>—</em>';
                break;
            case 'monthly_sale_price':
                $sale = get_post_meta($post_id, '_monthly_sale_price', true);
                echo ($sale !== '' && $sale !== false) ? '<strong style="color:#080;">' . esc_html(MSFM_Settings::format_price($sale)) . '</strong>' : '<em>—</em>';
                break;
            case 'annual_price':
                $regular = get_post_meta($post_id, '_annual_price', true);
                echo $regular ? esc_html(MSFM_Settings::format_price($regular)) : '<em>—</em>';
                break;
            case 'annual_sale_price':
                $sale = get_post_meta($post_id, '_annual_sale_price', true);
                echo ($sale !== '' && $sale !== false) ? '<strong style="color:#080;">' . esc_html(MSFM_Settings::format_price($sale)) . '</strong>' : '<em>—</em>';
                break;
            case 'shortcode':
                echo '<code>[saas_checkout_button package_id="' . esc_attr($post_id) . '"] Buy Plan [/saas_checkout_button]</code>';
                break;
        }
    }

    public function add_package_metaboxes() {
        add_meta_box('msfm_package_pricing', 'Plan Pricing Details', array($this, 'render_pricing_metabox'), 'saas_package', 'side', 'high');
    }

    public function render_pricing_metabox($post) {
        $currency_symbol    = get_option('msfm_currency_symbol', '$');
        $monthly_price      = get_post_meta($post->ID, '_monthly_price', true);
        $monthly_sale_price = get_post_meta($post->ID, '_monthly_sale_price', true);
        $annual_price       = get_post_meta($post->ID, '_annual_price', true);
        $annual_sale_price  = get_post_meta($post->ID, '_annual_sale_price', true);
        
        wp_nonce_field('msfm_save_package', 'msfm_package_nonce');
        ?>
        <div style="display: flex; flex-direction: column; gap: 15px; margin-bottom: 15px;">
            <div style="background: #f9f9f9; padding: 12px; border: 1px solid #e5e5e5; border-radius: 4px;">
                <h4 style="margin: 0 0 10px 0;">Monthly Billing</h4>
                <p style="margin-bottom: 8px;">
                    <label><strong>Regular Price (<?php echo esc_html($currency_symbol); ?>):</strong></label><br>
                    <input type="number" step="0.01" name="monthly_price" value="<?php echo esc_attr($monthly_price); ?>" class="widefat">
                </p>
                <p style="margin: 0;">
                    <label><strong>Sale Price (<?php echo esc_html($currency_symbol); ?>):</strong></label><br>
                    <input type="number" step="0.01" name="monthly_sale_price" value="<?php echo esc_attr($monthly_sale_price); ?>" class="widefat">
                </p>
            </div>
            <div style="background: #f9f9f9; padding: 12px; border: 1px solid #e5e5e5; border-radius: 4px;">
                <h4 style="margin: 0 0 10px 0;">Annual Billing</h4>
                <p style="margin-bottom: 8px;">
                    <label><strong>Regular Price (<?php echo esc_html($currency_symbol); ?>):</strong></label><br>
                    <input type="number" step="0.01" name="annual_price" value="<?php echo esc_attr($annual_price); ?>" class="widefat">
                </p>
                <p style="margin: 0;">
                    <label><strong>Sale Price (<?php echo esc_html($currency_symbol); ?>):</strong></label><br>
                    <input type="number" step="0.01" name="annual_sale_price" value="<?php echo esc_attr($annual_sale_price); ?>" class="widefat">
                </p>
            </div>
        </div>
        <hr>
        <p style="margin: 0;">
            <strong>Shortcode:</strong><br>
            <code style="word-break: break-all; display: block; margin-top: 5px;">[saas_checkout_button package_id="<?php echo $post->ID; ?>"] Buy Plan [/saas_checkout_button]</code>
        </p>
        <?php
    }

    public function save_package_meta($post_id) {
        if (!isset($_POST['msfm_package_nonce']) || !wp_verify_nonce($_POST['msfm_package_nonce'], 'msfm_save_package')) return;
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;

        $fields = array('monthly_price', 'monthly_sale_price', 'annual_price', 'annual_sale_price');
        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                update_post_meta($post_id, '_' . $field, sanitize_text_field($_POST[$field]));
            }
        }
    }

    public function add_orders_menu() {
        add_submenu_page(
            'edit.php?post_type=saas_package',
            'Orders & Transactions',
            'Orders',
            'manage_options',
            'msfm-orders',
            array($this, 'render_orders_page')
        );
    }

    public function handle_update_order_status() {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized user.');
        }
        check_admin_referer('msfm_update_order_status_action', 'msfm_order_status_nonce');

        $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
        $status   = isset($_POST['payment_status']) ? sanitize_text_field($_POST['payment_status']) : '';

        $allowed_statuses = array('pending', 'completed', 'paid', 'processing', 'failed', 'cancelled');
        
        if ($order_id > 0 && in_array(strtolower($status), $allowed_statuses)) {
            global $wpdb;
            $wpdb->update(
                $wpdb->prefix . 'microsaas_orders',
                array('payment_status' => strtolower($status)),
                array('id' => $order_id)
            );
        }

        wp_redirect(admin_url('edit.php?post_type=saas_package&page=msfm-orders&updated_status=1'));
        exit;
    }

    public function render_orders_page() {
        if (!current_user_can('manage_options')) return;
        global $wpdb;
        $orders = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}microsaas_orders ORDER BY created_at DESC");
        ?>
        <div class="wrap">
            <h2>Simple Plans & Fawaterak Orders</h2>

            <?php if (isset($_GET['updated_status'])): ?>
                <div class="notice notice-success is-dismissible">
                    <p><strong>Success:</strong> Order payment status has been updated successfully.</p>
                </div>
            <?php endif; ?>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Plan</th>
                        <th>Billing Cycle</th>
                        <th>Amount</th>
                        <th>Method</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($orders)): ?>
                        <tr><td colspan="9">No orders found.</td></tr>
                    <?php else: foreach ($orders as $order): 
                        $user = get_userdata($order->user_id);
                        $package = get_post($order->package_id);
                        $customer_name = !empty($order->full_name) ? $order->full_name : ($user ? $user->display_name : 'Guest');
                        
                        $status_str = strtolower($order->payment_status);
                        $bg = '#edf2f7'; $col = '#4a5568';
                        if (in_array($status_str, ['completed', 'paid'])) { $bg = '#c6f6d5'; $col = '#22543d'; }
                        elseif ($status_str === 'pending') { $bg = '#feebc8'; $col = '#744210'; }
                        elseif (in_array($status_str, ['failed', 'cancelled'])) { $bg = '#fed7d7'; $col = '#9b2c2c'; }
                    ?>
                        <tr>
                            <td>#<?php echo esc_html($order->id); ?></td>
                            <td>
                                <strong><?php echo esc_html($customer_name); ?></strong><br>
                                <small><?php echo esc_html($user ? $user->user_email : 'N/A'); ?></small>
                            </td>
                            <td><?php echo esc_html($package ? $package->post_title : 'N/A'); ?></td>
                            <td><?php echo esc_html(strtoupper($order->billing_cycle)); ?></td>
                            <td><?php echo esc_html(MSFM_Settings::format_price($order->amount)); ?></td>
                            <td><?php echo esc_html(strtoupper($order->payment_method ? $order->payment_method : 'Fawaterak')); ?></td>
                            <td>
                                <span style="background: <?php echo $bg; ?>; color: <?php echo $col; ?>; padding: 3px 8px; border-radius: 12px; font-size: 11px; font-weight: bold; text-transform: uppercase;">
                                    <?php echo esc_html($status_str); ?>
                                </span>
                            </td>
                            <td><?php echo esc_html(date('M d, Y', strtotime($order->created_at))); ?></td>
                            <td>
                                <a href="#TB_inline?width=750&height=520&inlineId=order-details-modal-<?php echo $order->id; ?>" class="thickbox button button-small">View Details</a>
                                
                                <div id="order-details-modal-<?php echo $order->id; ?>" style="display:none;">
                                    <div style="padding: 30px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #2d3748; background: #f8fafc; height: 100%; box-sizing: border-box;">
                                        
                                        <div style="background: #ffffff; padding: 20px; border-radius: 10px; border: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.02);">
                                            <div>
                                                <span style="font-size: 12px; font-weight: bold; color: #718096; text-transform: uppercase; letter-spacing: 0.05em; display: block; margin-bottom: 2px;">Transaction Details</span>
                                                <h2 style="margin: 0; font-size: 22px; color: #1a202c;">Order <span style="color: #3182ce;">#<?php echo esc_html($order->id); ?></span></h2>
                                                <div style="margin-top: 4px; font-size: 12px; color: #a0aec0;"><?php echo esc_html(date('F j, Y — g:i a', strtotime($order->created_at))); ?></div>
                                            </div>
                                            <div style="text-align: right;">
                                                <span style="display: inline-block; background: <?php echo $bg; ?>; color: <?php echo $col; ?>; padding: 6px 14px; border-radius: 20px; font-weight: bold; font-size: 12px; letter-spacing: 0.05em; text-transform: uppercase;">
                                                    ● <?php echo esc_html($status_str); ?>
                                                </span>
                                            </div>
                                        </div>

                                        <div style="display: flex; gap: 20px; flex-wrap: wrap; margin-bottom: 20px;">
                                            <div style="flex: 1 1 300px; background: #ffffff; padding: 20px; border-radius: 10px; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,0.02);">
                                                <h4 style="margin: 0 0 12px 0; color: #4a5568; font-size: 14px; text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 2px solid #edf2f7; padding-bottom: 8px;">Customer Information</h4>
                                                <table style="width: 100%; font-size: 13px; border-collapse: collapse;">
                                                    <tr><td style="padding: 4px 0; color: #718096; width: 35%;">Full Name:</td><td style="padding: 4px 0; font-weight: 600; color: #2d3748;"><?php echo esc_html($order->full_name ? $order->full_name : 'N/A'); ?></td></tr>
                                                    <tr><td style="padding: 4px 0; color: #718096;">Email Address:</td><td style="padding: 4px 0; font-weight: 600; color: #2d3748;"><?php echo esc_html($user ? $user->user_email : 'N/A'); ?></td></tr>
                                                    <tr><td style="padding: 4px 0; color: #718096;">Phone Number:</td><td style="padding: 4px 0; font-weight: 600; color: #2d3748;"><?php echo esc_html($order->phone_number ? $order->phone_number : 'N/A'); ?></td></tr>
                                                    <tr><td style="padding: 4px 0; color: #718096;">Country:</td><td style="padding: 4px 0; font-weight: 600; color: #2d3748;"><?php echo esc_html($order->country ? $order->country : 'N/A'); ?></td></tr>
                                                    <tr><td style="padding: 4px 0; color: #718096;">Street Address:</td><td style="padding: 4px 0; font-weight: 600; color: #2d3748;"><?php echo esc_html($order->address ? $order->address : 'N/A'); ?></td></tr>
                                                    <tr><td style="padding: 4px 0; color: #718096;">ZIP Code:</td><td style="padding: 4px 0; font-weight: 600; color: #2d3748;"><?php echo esc_html($order->zip_code ? $order->zip_code : 'N/A'); ?></td></tr>
                                                </table>
                                            </div>

                                            <div style="flex: 1 1 300px; background: #ffffff; padding: 20px; border-radius: 10px; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,0.02);">
                                                <h4 style="margin: 0 0 12px 0; color: #4a5568; font-size: 14px; text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 2px solid #edf2f7; padding-bottom: 8px;">Subscription Breakdown</h4>
                                                <table style="width: 100%; font-size: 13px; border-collapse: collapse;">
                                                    <tr><td style="padding: 6px 0; color: #718096; width: 40%;">Selected Plan:</td><td style="padding: 6px 0; font-weight: 600; color: #2d3748;"><?php echo esc_html($package ? $package->post_title : 'N/A'); ?></td></tr>
                                                    <tr><td style="padding: 6px 0; color: #718096;">Billing Cycle:</td><td style="padding: 6px 0; font-weight: 600; color: #2d3748; text-transform: capitalize;"><?php echo esc_html($order->billing_cycle); ?></td></tr>
                                                    <tr><td style="padding: 6px 0; color: #718096;">Total Amount:</td><td style="padding: 6px 0; font-weight: bold; color: #2b6cb0; font-size: 15px;"><?php echo esc_html(MSFM_Settings::format_price($order->amount)); ?></td></tr>
                                                    <tr><td style="padding: 6px 0; color: #718096;">Payment Method:</td><td style="padding: 6px 0; font-weight: 600; color: #2d3748; text-transform: uppercase;"><?php echo esc_html($order->payment_method ? $order->payment_method : 'Fawaterak'); ?></td></tr>
                                                    <tr><td style="padding: 6px 0; color: #718096;">Gateway Invoice:</td><td style="padding: 6px 0; font-weight: 600; color: #2d3748;"><?php echo esc_html($order->fawaterak_invoice_id ? $order->fawaterak_invoice_id : '—'); ?></td></tr>
                                                </table>
                                            </div>
                                        </div>

                                        <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 18px 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.02);">
                                            <form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="POST" style="margin: 0;">
                                                <?php wp_nonce_field('msfm_update_order_status_action', 'msfm_order_status_nonce'); ?>
                                                <input type="hidden" name="action" value="msfm_update_order_status">
                                                <input type="hidden" name="order_id" value="<?php echo esc_attr($order->id); ?>">
                                                
                                                <div style="display: flex; align-items: center; justify-content: space-between; gap: 15px; flex-wrap: wrap;">
                                                    <div>
                                                        <span style="font-weight: bold; font-size: 13px; color: #1a202c; display: block; margin-bottom: 2px;">Manage Payment Status</span>
                                                        <span style="font-size: 12px; color: #718096;">Change order state manually and trigger system updates.</span>
                                                    </div>
                                                    <div style="display: flex; gap: 10px; align-items: center;">
                                                        <select name="payment_status" style="padding: 8px 12px; border-radius: 6px; border: 1px solid #cbd5e0; font-size: 13px; background: #f8fafc;">
                                                            <option value="pending" <?php selected($status_str, 'pending'); ?>>Pending</option>
                                                            <option value="completed" <?php selected(in_array($status_str, ['completed', 'paid']), true); ?>>Completed / Paid</option>
                                                            <option value="processing" <?php selected($status_str, 'processing'); ?>>Processing</option>
                                                            <option value="failed" <?php selected($status_str, 'failed'); ?>>Failed</option>
                                                            <option value="cancelled" <?php selected($status_str, 'cancelled'); ?>>Cancelled</option>
                                                        </select>
                                                        <button type="submit" class="button button-primary" style="padding: 0 16px; height: 34px; line-height: 32px; font-weight: 600;">Update Status</button>
                                                    </div>
                                                </div>
                                            </form>
                                        </div>

                                    </div>
                                </div>

                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
}