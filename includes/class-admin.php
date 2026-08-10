<?php
if (!defined('ABSPATH')) exit;

class MSFM_Admin {

    public function __construct() {
        add_action('init', array($this, 'register_package_cpt'));
        add_action('add_meta_boxes', array($this, 'add_package_metaboxes'));
        add_action('save_post', array($this, 'save_package_meta'));
        add_action('admin_menu', array($this, 'add_orders_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));

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
            'name'                  => _x('Qaff Plans', 'Post Type General Name', 'qaff-microsaas'),
            'singular_name'         => _x('Qaff Plan', 'Post Type Singular Name', 'qaff-microsaas'),
            'menu_name'             => __('Qaff Plans', 'qaff-microsaas'),
            'name_admin_bar'        => __('Qaff Plan', 'qaff-microsaas'),
            'archives'              => __('Plan Archives', 'qaff-microsaas'),
            'all_items'             => __('All Plans', 'qaff-microsaas'),
            'add_new_item'          => __('Add New Plan', 'qaff-microsaas'),
            'add_new'               => __('Add New Plan', 'qaff-microsaas'),
            'new_item'              => __('New Plan', 'qaff-microsaas'),
            'edit_item'             => __('Edit Plan', 'qaff-microsaas'),
            'update_item'           => __('Update Plan', 'qaff-microsaas'),
            'view_item'             => __('View Plan', 'qaff-microsaas'),
            'search_items'          => __('Search Plans', 'qaff-microsaas'),
        );

        register_post_type('saas_package', array(
            'label'                 => __('Qaff Plan', 'qaff-microsaas'),
            'description'           => __('Micro SaaS Pricing Plans', 'qaff-microsaas'),
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
        $new_columns['title']              = __('Plan Name', 'qaff-microsaas');
        $new_columns['monthly_price']      = __('Monthly Price', 'qaff-microsaas');
        $new_columns['monthly_sale_price'] = __('Monthly Sale', 'qaff-microsaas');
        $new_columns['annual_price']       = __('Annual Price', 'qaff-microsaas');
        $new_columns['annual_sale_price']  = __('Annual Sale', 'qaff-microsaas');
        $new_columns['shortcode']          = __('Shortcode', 'qaff-microsaas');
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

    public function render_orders_page() {
        if (!current_user_can('manage_options')) return;
        global $wpdb;
        $orders = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}microsaas_orders ORDER BY created_at DESC");
        ?>
        <div class="wrap">
            <h2>Qaff Micro SaaS Orders & Payments</h2>
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
                            <td><strong><?php echo esc_html(strtoupper($order->payment_status)); ?></strong></td>
                            <td><?php echo esc_html($order->created_at); ?></td>
                            <td>
                                <a href="#TB_inline?width=600&height=450&inlineId=order-details-modal-<?php echo $order->id; ?>" class="thickbox button button-small">View Details</a>
                                <div id="order-details-modal-<?php echo $order->id; ?>" style="display:none;">
                                    <div style="padding: 15px;">
                                        <h2>Order Details #<?php echo esc_html($order->id); ?></h2>
                                        <hr>
                                        <div style="display: flex; gap: 20px;">
                                            <div style="flex: 1;">
                                                <h3>Customer Information</h3>
                                                <p><strong>Full Name:</strong> <?php echo esc_html($order->full_name ? $order->full_name : 'N/A'); ?></p>
                                                <p><strong>Email:</strong> <?php echo esc_html($user ? $user->user_email : 'N/A'); ?></p>
                                                <p><strong>Phone:</strong> <?php echo esc_html($order->phone_number ? $order->phone_number : 'N/A'); ?></p>
                                                <p><strong>Country:</strong> <?php echo esc_html($order->country ? $order->country : 'N/A'); ?></p>
                                                <p><strong>Address:</strong> <?php echo esc_html($order->address ? $order->address : 'N/A'); ?></p>
                                                <p><strong>Zip Code:</strong> <?php echo esc_html($order->zip_code ? $order->zip_code : 'N/A'); ?></p>
                                            </div>
                                            <div style="flex: 1;">
                                                <h3>Order & Payment Details</h3>
                                                <p><strong>Selected Plan:</strong> <?php echo esc_html($package ? $package->post_title : 'N/A'); ?></p>
                                                <p><strong>Billing Cycle:</strong> <?php echo esc_html(strtoupper($order->billing_cycle)); ?></p>
                                                <p><strong>Total Amount:</strong> <?php echo esc_html(MSFM_Settings::format_price($order->amount)); ?></p>
                                                <p><strong>Payment Method:</strong> <?php echo esc_html(strtoupper($order->payment_method ? $order->payment_method : 'Fawaterak')); ?></p>
                                                <p><strong>Status:</strong> <strong><?php echo esc_html(strtoupper($order->payment_status)); ?></strong></p>
                                                <p><strong>Fawaterak Invoice ID:</strong> <?php echo esc_html($order->fawaterak_invoice_id ? $order->fawaterak_invoice_id : 'N/A'); ?></p>
                                                <p><strong>Date Placed:</strong> <?php echo esc_html($order->created_at); ?></p>
                                            </div>
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