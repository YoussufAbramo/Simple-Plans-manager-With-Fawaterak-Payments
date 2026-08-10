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
        register_setting('msfm_settings_payments', 'msfm_fawaterak_integration_type', array('default' => 'redirect'));
        register_setting('msfm_settings_payments', 'msfm_fawaterak_api_key', array('default' => ''));
        register_setting('msfm_settings_payments', 'msfm_fawaterak_provider_key', array('default' => ''));
        register_setting('msfm_settings_payments', 'msfm_enable_cod', array('default' => '1'));
        register_setting('msfm_settings_payments', 'msfm_cod_label', array('default' => 'Pay on Delivery / Cash on Delivery'));
    }

    public function render_settings_page() {
        if (!current_user_can('manage_options')) return;

        $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'general';
        ?>
        <div class="wrap">
            <h2>Qaff Micro SaaS Settings</h2>

            <?php if (isset($_GET['pages_restored']) && $_GET['pages_restored'] == '1'): ?>
                <div class="notice notice-success is-dismissible">
                    <p><strong>Success:</strong> Core pages have been successfully restored and linked.</p>
                </div>
            <?php endif; ?>

            <h2 class="nav-tab-wrapper">
                <a href="?post_type=saas_package&page=qaff-settings&tab=general" class="nav-tab <?php echo $active_tab == 'general' ? 'nav-tab-active' : ''; ?>">General</a>
                <a href="?post_type=saas_package&page=qaff-settings&tab=pages" class="nav-tab <?php echo $active_tab == 'pages' ? 'nav-tab-active' : ''; ?>">Pages</a>
                <a href="?post_type=saas_package&page=qaff-settings&tab=payments" class="nav-tab <?php echo $active_tab == 'payments' ? 'nav-tab-active' : ''; ?>">Payments</a>
            </h2>

            <?php if ($active_tab !== 'pages'): ?>
                <form method="post" action="options.php" style="margin-top: 20px;">
                    <?php
                    if ($active_tab == 'general') {
                        settings_fields('msfm_settings_general');
                        $this->render_general_tab();
                    } elseif ($active_tab == 'payments') {
                        settings_fields('msfm_settings_payments');
                        $this->render_payments_tab();
                    }
                    submit_button();
                    ?>
                </form>
            <?php else: ?>
                <div style="margin-top: 20px;">
                    <form method="post" action="options.php">
                        <?php
                        settings_fields('msfm_settings_pages');
                        $this->render_pages_tab();
                        submit_button('Save Page Links');
                        ?>
                    </form>
                </div>
            <?php endif; ?>
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
        $checkout_page_id = get_option('msfm_checkout_page_id');
        $login_page_id    = get_option('msfm_login_page_id');
        $portal_page_id   = get_option('msfm_portal_page_id');
        ?>
        <p class="description">Select the pages you want to use for the core plugin features. The shortcodes must be placed inside the content of these pages.</p>
        <table class="form-table">
            <tr valign="top">
                <th scope="row">Checkout Page<br><small><code>[saas_checkout_page]</code></small></th>
                <td>
                    <?php wp_dropdown_pages(array('name' => 'msfm_checkout_page_id', 'selected' => $checkout_page_id, 'show_option_none' => '&mdash; Select Page &mdash;')); ?>
                    <?php if ($checkout_page_id && get_post_status($checkout_page_id) === 'publish'): ?>
                        <a href="<?php echo esc_url(get_permalink($checkout_page_id)); ?>" target="_blank" class="button button-small" style="margin-left:10px; vertical-align:middle;">View Page &rarr;</a>
                    <?php endif; ?>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row">Login Page<br><small><code>[saas_login_form]</code></small></th>
                <td>
                    <?php wp_dropdown_pages(array('name' => 'msfm_login_page_id', 'selected' => $login_page_id, 'show_option_none' => '&mdash; Select Page &mdash;')); ?>
                    <?php if ($login_page_id && get_post_status($login_page_id) === 'publish'): ?>
                        <a href="<?php echo esc_url(get_permalink($login_page_id)); ?>" target="_blank" class="button button-small" style="margin-left:10px; vertical-align:middle;">View Page &rarr;</a>
                    <?php endif; ?>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row">My Profile Page<br><small><code>[saas_user_portal]</code></small></th>
                <td>
                    <?php wp_dropdown_pages(array('name' => 'msfm_portal_page_id', 'selected' => $portal_page_id, 'show_option_none' => '&mdash; Select Page &mdash;')); ?>
                    <?php if ($portal_page_id && get_post_status($portal_page_id) === 'publish'): ?>
                        <a href="<?php echo esc_url(get_permalink($portal_page_id)); ?>" target="_blank" class="button button-small" style="margin-left:10px; vertical-align:middle;">View Page &rarr;</a>
                    <?php endif; ?>
                </td>
            </tr>
        </table>
        
        <hr style="margin: 30px 0;">
        
        <h3>Page Repair Tools</h3>
        <p class="description">If you accidentally deleted a page, click the button below to safely restore the required pages and inject the correct shortcodes.</p>
        <div style="background: #fff; padding: 15px; border: 1px solid #ccd0d4; border-left: 4px solid #3182ce; display: inline-block;">
            <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=msfm_recreate_pages'), 'msfm_recreate_pages_action', 'msfm_recreate_nonce')); ?>" class="button button-secondary">
                Restore Missing / Broken Pages
            </a>
        </div>
        <?php
    }

    private function render_payments_tab() {
        $fawaterak_env   = get_option('msfm_fawaterak_env', 'sandbox');
        $integration_type= get_option('msfm_fawaterak_integration_type', 'redirect');
        $api_key         = get_option('msfm_fawaterak_api_key');
        $provider_key    = get_option('msfm_fawaterak_provider_key');
        $enable_cod      = get_option('msfm_enable_cod', '1');
        $cod_label       = get_option('msfm_cod_label', 'Pay on Delivery / Cash on Delivery');
        $webhook_url     = rest_url('qaff/v1/fawaterak-webhook');
        ?>
        <div style="background: #ebf8ff; border: 1px solid #3182ce; padding: 15px; border-radius: 6px; margin-bottom: 20px;">
            <h4 style="margin-top:0; color:#2b6cb0;">🔗 Fawaterak Webhook Listener URL</h4>
            <p style="margin-bottom:8px;">Copy and paste this Webhook URL into your <strong>Fawaterak Dashboard Integrations</strong> to enable automated background order status updates:</p>
            <code style="font-size:14px; background:#fff; padding:6px 10px; border:1px solid #cbd5e0; border-radius:4px; display:inline-block; font-weight:bold;"><?php echo esc_url($webhook_url); ?></code>
        </div>

        <h3>Fawaterak Online Gateway</h3>
        <p class="description">Fawaterak uses a static Bearer Token (API Key) for authentication. Retrieve this from your Fawaterak Dashboard &rarr; Integrations.</p>
        
        <table class="form-table">
            <tr valign="top">
                <th scope="row">Environment</th>
                <td>
                    <label>
                        <input type="radio" name="msfm_fawaterak_env" value="sandbox" <?php checked($fawaterak_env, 'sandbox'); ?> onclick="toggleTestCards('sandbox')"> Sandbox / Staging
                    </label>&nbsp;&nbsp;
                    <label>
                        <input type="radio" name="msfm_fawaterak_env" value="live" <?php checked($fawaterak_env, 'live'); ?> onclick="toggleTestCards('live')"> Production / Live
                    </label>

                    <!-- Dynamic Test Cards Table for Staging Environment -->
                    <div id="fawaterak_test_cards" style="display: <?php echo ($fawaterak_env === 'sandbox') ? 'block' : 'none'; ?>; background: #fdfaf6; border: 1px solid #e2c08d; border-radius: 6px; padding: 15px; margin-top: 15px; max-width: 800px;">
                        <h4 style="margin-top: 0; color: #b7791f; font-size: 15px;">🛠️ Sandbox Test Cards</h4>
                        <p style="margin-bottom: 15px;">Use these test card numbers when testing Mastercard, Visa, and Meeza flows on staging mode. <strong>Do not use these in production.</strong></p>
                        
                        <h5 style="margin-bottom: 5px; color: #2f855a; font-size: 14px;">✅ Cards that always return a SUCCESSFUL transaction</h5>
                        <table class="wp-list-table widefat fixed striped" style="margin-bottom: 20px;">
                            <thead>
                                <tr><th>Brand</th><th>Card number</th><th>Card holder name</th><th>Expiry date</th><th>CSV</th></tr>
                            </thead>
                            <tbody>
                                <tr><td>Mastercard</td><td><code>5123450000000008</code></td><td>Fawaterak test</td><td>12/26</td><td>100</td></tr>
                                <tr><td>Visa</td><td><code>4005 5500 0000 0001</code></td><td>Fawaterak test</td><td>12/26</td><td>100</td></tr>
                                <tr><td>Meeza</td><td><code>5078 0362 4660 0381</code></td><td>Fawaterak test</td><td>12/26</td><td>100</td></tr>
                            </tbody>
                        </table>

                        <h5 style="margin-bottom: 5px; color: #c53030; font-size: 14px;">❌ Cards that always return a FAILED transaction</h5>
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr><th>Brand</th><th>Card number</th><th>Card holder name</th><th>Expiry date</th><th>CSV</th></tr>
                            </thead>
                            <tbody>
                                <tr><td>Mastercard</td><td><code>5543474002249996</code></td><td>Fawaterak test</td><td>05/26</td><td>123</td></tr>
                                <tr><td>Visa</td><td><code>4222 0000 0672 4235</code></td><td>Fawaterak test</td><td>12/26</td><td>123</td></tr>
                                <tr><td>Meeza</td><td><code>5078 0362 4278 3546</code></td><td>Fawaterak test</td><td>12/26</td><td>123</td></tr>
                            </tbody>
                        </table>
                    </div>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row">Integration Method</th>
                <td>
                    <label>
                        <input type="radio" name="msfm_fawaterak_integration_type" value="redirect" <?php checked($integration_type, 'redirect'); ?> onchange="toggleFawaterakKeys()"> Gateway Redirect (Invoice Link)
                    </label><br>
                    <label style="margin-top: 5px; display: inline-block;">
                        <input type="radio" name="msfm_fawaterak_integration_type" value="iframe" <?php checked($integration_type, 'iframe'); ?> onchange="toggleFawaterakKeys()"> Embedded IFrame (Hosted Checkout)
                    </label>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row">Fawaterak API Key (Bearer/Vendor Key)</th>
                <td>
                    <input type="password" name="msfm_fawaterak_api_key" value="<?php echo esc_attr($api_key); ?>" class="regular-text">
                </td>
            </tr>
            <tr valign="top" id="msfm_provider_key_row" style="<?php echo ($integration_type === 'iframe') ? '' : 'display: none;'; ?>">
                <th scope="row">Fawaterak Provider Key</th>
                <td>
                    <input type="password" name="msfm_fawaterak_provider_key" value="<?php echo esc_attr($provider_key); ?>" class="regular-text">
                    <p class="description">Required exclusively for the <strong>Embedded IFrame</strong> method.</p>
                </td>
            </tr>
        </table>
        
        <script type="text/javascript">
        function toggleFawaterakKeys() {
            var selectedIntegration = document.querySelector('input[name="msfm_fawaterak_integration_type"]:checked').value;
            var providerRow = document.getElementById('msfm_provider_key_row');
            if (selectedIntegration === 'iframe') {
                providerRow.style.display = 'table-row';
            } else {
                providerRow.style.display = 'none';
            }
        }
        function toggleTestCards(env) {
            var testCardsSection = document.getElementById('fawaterak_test_cards');
            if (env === 'sandbox') {
                testCardsSection.style.display = 'block';
            } else {
                testCardsSection.style.display = 'none';
            }
        }
        </script>
        
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

    public function handle_recreate_pages() {
        if (!current_user_can('manage_options') || !isset($_GET['msfm_recreate_nonce']) || !wp_verify_nonce($_GET['msfm_recreate_nonce'], 'msfm_recreate_pages_action')) {
            wp_die('Unauthorized request.');
        }

        $core_pages = array(
            'msfm_checkout_page_id' => array('title' => 'Checkout', 'content' => '[saas_checkout_page]', 'slug' => 'checkout'),
            'msfm_login_page_id'    => array('title' => 'Login', 'content' => '[saas_login_form]', 'slug' => 'login'),
            'msfm_portal_page_id'   => array('title' => 'My Profile', 'content' => '[saas_user_portal]', 'slug' => 'my-profile'),
        );

        foreach ($core_pages as $option_key => $data) {
            $page_id = get_option($option_key);
            $page_exists = false;
            if ($page_id) {
                $page = get_post($page_id);
                if ($page && in_array($page->post_type, array('page', 'post'))) {
                    $page_exists = true;
                    wp_update_post(array('ID' => $page_id, 'post_content' => $data['content'], 'post_status'  => 'publish'));
                }
            }
            if (!$page_exists) {
                self::get_or_create_page($option_key, $data['title'], $data['content'], $data['slug']);
            }
        }

        wp_redirect(admin_url('edit.php?post_type=saas_package&page=qaff-settings&tab=pages&pages_restored=1'));
        exit;
    }
}