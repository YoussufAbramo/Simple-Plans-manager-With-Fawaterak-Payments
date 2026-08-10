<?php
if (!defined('ABSPATH')) exit;

class MSFM_Settings {

    public function __construct() {
        add_action('admin_menu', array($this, 'add_settings_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_post_msfm_auto_create_page', array($this, 'handle_auto_create_page'));
    }

    public static function get_or_create_page($option_key, $title, $content, $slug) {
        $page_id = get_option($option_key);
        if ($page_id && get_post($page_id) && get_post_status($page_id) === 'publish') {
            return $page_id;
        }

        $new_page_id = wp_insert_post(array(
            'post_title'   => $title,
            'post_content' => $content,
            'post_status'  => 'publish',
            'post_type'    => 'page',
            'post_name'    => $slug
        ));

        if ($new_page_id && !is_wp_error($new_page_id)) {
            update_option($option_key, $new_page_id);
            return $new_page_id;
        }

        return 0;
    }

    public static function format_price($price) {
        $symbol   = get_option('msfm_currency_symbol', '$');
        $position = get_option('msfm_currency_position', 'before');
        
        if ($position === 'after') {
            return $price . ' ' . $symbol;
        }
        return $symbol . $price;
    }

    public function add_settings_menu() {
        add_submenu_page(
            'edit.php?post_type=saas_package',
            'Qaff Store Settings',
            'Settings',
            'manage_options',
            'qaff-settings',
            array($this, 'render_settings_page')
        );
    }

    public function register_settings() {
        // General Tab
        register_setting('msfm_settings_general', 'msfm_currency', array('default' => 'USD'));
        register_setting('msfm_settings_general', 'msfm_currency_symbol', array('default' => '$'));
        register_setting('msfm_settings_general', 'msfm_currency_position', array('default' => 'before'));
        register_setting('msfm_settings_general', 'msfm_portal_btn_label', array('default' => 'Call To Action'));
        register_setting('msfm_settings_general', 'msfm_portal_btn_url', array('default' => '#'));
        register_setting('msfm_settings_general', 'msfm_enable_renewal_emails', array('default' => '1'));

        // Pages Tab
        register_setting('msfm_settings_pages', 'msfm_checkout_page_id', array('default' => '0'));
        register_setting('msfm_settings_pages', 'msfm_login_page_id', array('default' => '0'));
        register_setting('msfm_settings_pages', 'msfm_portal_page_id', array('default' => '0'));

        // Payments Tab
        register_setting('msfm_settings_payments', 'msfm_fawaterak_env', array('default' => 'sandbox'));
        register_setting('msfm_settings_payments', 'msfm_fawaterak_client_id', array('default' => ''));
        register_setting('msfm_settings_payments', 'msfm_fawaterak_client_secret', array('default' => ''));
        register_setting('msfm_settings_payments', 'msfm_fawaterak_token_url', array('default' => ''));
        register_setting('msfm_settings_payments', 'msfm_enable_cod', array('default' => '1'));
        register_setting('msfm_settings_payments', 'msfm_cod_label', array('default' => 'Pay on Delivery / Cash on Delivery'));
    }

    public function render_settings_page() {
        if (!current_user_can('manage_options')) return;

        $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'general';
        ?>
        <div class="wrap">
            <h2>Qaff Micro SaaS Settings</h2>

            <h2 class="nav-tab-wrapper">
                <a href="?post_type=saas_package&page=qaff-settings&tab=general" class="nav-tab <?php echo $active_tab == 'general' ? 'nav-tab-active' : ''; ?>">General</a>
                <a href="?post_type=saas_package&page=qaff-settings&tab=pages" class="nav-tab <?php echo $active_tab == 'pages' ? 'nav-tab-active' : ''; ?>">Pages</a>
                <a href="?post_type=saas_package&page=qaff-settings&tab=payments" class="nav-tab <?php echo $active_tab == 'payments' ? 'nav-tab-active' : ''; ?>">Payments</a>
            </h2>

            <form method="post" action="options.php" style="margin-top: 20px;">
                <?php
                if ($active_tab == 'general') {
                    settings_fields('msfm_settings_general');
                    $this->render_general_tab();
                } elseif ($active_tab == 'pages') {
                    settings_fields('msfm_settings_pages');
                    $this->render_pages_tab();
                } elseif ($active_tab == 'payments') {
                    settings_fields('msfm_settings_payments');
                    $this->render_payments_tab();
                }
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    private function render_general_tab() {
        $currency          = get_option('msfm_currency', 'USD');
        $currency_symbol   = get_option('msfm_currency_symbol', '$');
        $currency_position = get_option('msfm_currency_position', 'before');
        $btn_label         = get_option('msfm_portal_btn_label', 'Call To Action');
        $btn_url           = get_option('msfm_portal_btn_url', '#');
        $enable_renewal    = get_option('msfm_enable_renewal_emails', '1');
        ?>
        <h3>Currency Configuration</h3>
        <table class="form-table">
            <tr valign="top">
                <th scope="row">Store Currency Code</th>
                <td>
                    <select name="msfm_currency">
                        <option value="USD" <?php selected($currency, 'USD'); ?>>USD - US Dollar</option>
                        <option value="EGP" <?php selected($currency, 'EGP'); ?>>EGP - Egyptian Pound</option>
                        <option value="SAR" <?php selected($currency, 'SAR'); ?>>SAR - Saudi Riyal</option>
                        <option value="AED" <?php selected($currency, 'AED'); ?>>AED - UAE Dirham</option>
                        <option value="EUR" <?php selected($currency, 'EUR'); ?>>EUR - Euro</option>
                    </select>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row">Currency Symbol</th>
                <td>
                    <input type="text" name="msfm_currency_symbol" value="<?php echo esc_attr($currency_symbol); ?>" class="regular-text">
                </td>
            </tr>
            <tr valign="top">
                <th scope="row">Symbol Position</th>
                <td>
                    <label><input type="radio" name="msfm_currency_position" value="before" <?php checked($currency_position, 'before'); ?>> Before Price ($99)</label>&nbsp;&nbsp;
                    <label><input type="radio" name="msfm_currency_position" value="after" <?php checked($currency_position, 'after'); ?>> After Price (99 $)</label>
                </td>
            </tr>
        </table>

        <hr>

        <h3>Automated Email Notifications</h3>
        <table class="form-table">
            <tr valign="top">
                <th scope="row">Renewal Reminders</th>
                <td>
                    <label>
                        <input type="checkbox" name="msfm_enable_renewal_emails" value="1" <?php checked($enable_renewal, '1'); ?>>
                        Send automated renewal emails 3 days before subscription expires
                    </label>
                    <p class="description">Includes a personalized message and a direct checkout link for the user.</p>
                </td>
            </tr>
        </table>

        <hr>

        <h3>My Profile Action Button</h3>
        <table class="form-table">
            <tr valign="top">
                <th scope="row">Button Label</th>
                <td>
                    <input type="text" name="msfm_portal_btn_label" value="<?php echo esc_attr($btn_label); ?>" class="regular-text">
                </td>
            </tr>
            <tr valign="top">
                <th scope="row">Redirect Target URL</th>
                <td>
                    <input type="text" name="msfm_portal_btn_url" value="<?php echo esc_attr($btn_url); ?>" class="regular-text">
                </td>
            </tr>
        </table>
        <?php
    }

    private function render_pages_tab() {
        $checkout_page_id = self::get_or_create_page('msfm_checkout_page_id', 'Checkout', '[saas_checkout_page]', 'checkout');
        $login_page_id    = self::get_or_create_page('msfm_login_page_id', 'Login', '[saas_login_form]', 'login');
        $portal_page_id   = self::get_or_create_page('msfm_portal_page_id', 'My Profile', '[saas_user_portal]', 'my-profile');
        ?>
        <p class="description">Note: Pages are automatically linked or generated if missing.</p>
        <table class="form-table">
            <tr valign="top">
                <th scope="row">Checkout Page</th>
                <td>
                    <?php wp_dropdown_pages(array('name' => 'msfm_checkout_page_id', 'selected' => $checkout_page_id)); ?>
                    <?php if ($checkout_page_id): ?>
                        <a href="<?php echo esc_url(get_permalink($checkout_page_id)); ?>" target="_blank" class="button button-small" style="margin-left:10px; vertical-align:middle;">View Page &rarr;</a>
                    <?php endif; ?>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row">Login Page</th>
                <td>
                    <?php wp_dropdown_pages(array('name' => 'msfm_login_page_id', 'selected' => $login_page_id)); ?>
                    <?php if ($login_page_id): ?>
                        <a href="<?php echo esc_url(get_permalink($login_page_id)); ?>" target="_blank" class="button button-small" style="margin-left:10px; vertical-align:middle;">View Page &rarr;</a>
                    <?php endif; ?>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row">My Profile Page</th>
                <td>
                    <?php wp_dropdown_pages(array('name' => 'msfm_portal_page_id', 'selected' => $portal_page_id)); ?>
                    <?php if ($portal_page_id): ?>
                        <a href="<?php echo esc_url(get_permalink($portal_page_id)); ?>" target="_blank" class="button button-small" style="margin-left:10px; vertical-align:middle;">View Page &rarr;</a>
                    <?php endif; ?>
                </td>
            </tr>
        </table>
        <?php
    }

    private function render_payments_tab() {
        $fawaterak_env = get_option('msfm_fawaterak_env', 'sandbox');
        $client_id     = get_option('msfm_fawaterak_client_id');
        $client_secret = get_option('msfm_fawaterak_client_secret');
        $token_url     = get_option('msfm_fawaterak_token_url');
        $enable_cod    = get_option('msfm_enable_cod', '1');
        $cod_label     = get_option('msfm_cod_label', 'Pay on Delivery / Cash on Delivery');
        $webhook_url   = rest_url('qaff/v1/fawaterak-webhook');
        ?>
        <div style="background: #ebf8ff; border: 1px solid #3182ce; padding: 15px; border-radius: 6px; margin-bottom: 20px;">
            <h4 style="margin-top:0; color:#2b6cb0;">🔗 Fawaterak Webhook Listener URL</h4>
            <p style="margin-bottom:8px;">Copy and paste this Webhook URL into your <strong>Fawaterak Vendor Dashboard Account Settings</strong> to enable automated background order status updates:</p>
            <code style="font-size:14px; background:#fff; padding:6px 10px; border:1px solid #cbd5e0; border-radius:4px; display:inline-block; font-weight:bold;"><?php echo esc_url($webhook_url); ?></code>
        </div>

        <h3>Fawaterak Online Gateway (OAuth 2.0)</h3>
        <table class="form-table">
            <tr valign="top">
                <th scope="row">Environment</th>
                <td>
                    <label><input type="radio" name="msfm_fawaterak_env" value="sandbox" <?php checked($fawaterak_env, 'sandbox'); ?>> Sandbox / Staging</label>&nbsp;&nbsp;
                    <label><input type="radio" name="msfm_fawaterak_env" value="live" <?php checked($fawaterak_env, 'live'); ?>> Production / Live</label>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row">OAuth Token URL</th>
                <td>
                    <input type="url" name="msfm_fawaterak_token_url" value="<?php echo esc_attr($token_url); ?>" class="regular-text" placeholder="https://app.fawaterk.com/oauth/token">
                </td>
            </tr>
            <tr valign="top">
                <th scope="row">Client ID</th>
                <td><input type="text" name="msfm_fawaterak_client_id" value="<?php echo esc_attr($client_id); ?>" class="regular-text"></td>
            </tr>
            <tr valign="top">
                <th scope="row">Client Secret</th>
                <td><input type="password" name="msfm_fawaterak_client_secret" value="<?php echo esc_attr($client_secret); ?>" class="regular-text"></td>
            </tr>
        </table>
        
        <hr>
        
        <h3>Pay on Delivery</h3>
        <table class="form-table">
            <tr valign="top">
                <th scope="row">Enable Pay on Delivery</th>
                <td><label><input type="checkbox" name="msfm_enable_cod" value="1" <?php checked($enable_cod, '1'); ?>> Enable offline payment option at checkout</label></td>
            </tr>
            <tr valign="top">
                <th scope="row">Gateway Label</th>
                <td><input type="text" name="msfm_cod_label" value="<?php echo esc_attr($cod_label); ?>" class="regular-text"></td>
            </tr>
        </table>
        <?php
    }

    public function handle_auto_create_page() {
        if (!current_user_can('manage_options') || !check_admin_referer('msfm_auto_page_nonce')) {
            wp_die('Unauthorized request.');
        }
        wp_redirect(admin_url('edit.php?post_type=saas_package&page=qaff-settings&tab=pages'));
        exit;
    }
}