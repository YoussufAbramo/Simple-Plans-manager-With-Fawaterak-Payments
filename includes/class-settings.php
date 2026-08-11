<?php
if (!defined('ABSPATH')) exit;

class MSFM_Settings {

    public function __construct() {
        add_action('admin_menu', array($this, 'add_settings_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_post_msfm_manage_single_page', array($this, 'handle_manage_single_page'));
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
            'Simple Plans Settings',
            'Settings',
            'manage_options',
            'qaff-settings',
            array($this, 'render_settings_page')
        );
    }

    public function register_settings() {
        register_setting('msfm_settings_general', 'msfm_currency', array('default' => 'USD'));
        register_setting('msfm_settings_general', 'msfm_currency_symbol', array('default' => '$'));
        register_setting('msfm_settings_general', 'msfm_currency_position', array('default' => 'before'));
        register_setting('msfm_settings_general', 'msfm_portal_btn_label', array('default' => 'Call To Action'));
        register_setting('msfm_settings_general', 'msfm_portal_btn_url', array('default' => '#'));
        register_setting('msfm_settings_general', 'msfm_enable_renewal_emails', array('default' => '1'));

        register_setting('msfm_settings_pages', 'msfm_checkout_page_id', array('default' => '0'));
        register_setting('msfm_settings_pages', 'msfm_login_page_id', array('default' => '0'));
        register_setting('msfm_settings_pages', 'msfm_portal_page_id', array('default' => '0'));

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
            <h2>Simple Plans & Fawaterak Settings</h2>

            <?php if (isset($_GET['page_action_success'])): ?>
                <div class="notice notice-success is-dismissible">
                    <p><strong>Success:</strong> Page action executed successfully.</p>
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

        $pages_config = array(
            'msfm_checkout_page_id' => array('label' => 'Checkout Page', 'shortcode' => '[saas_checkout_page]', 'title' => 'Checkout', 'slug' => 'checkout', 'id' => $checkout_page_id),
            'msfm_login_page_id'    => array('label' => 'Login Page', 'shortcode' => '[saas_login_form]', 'title' => 'Login', 'slug' => 'login', 'id' => $login_page_id),
            'msfm_portal_page_id'   => array('label' => 'My Profile Page', 'shortcode' => '[saas_user_portal]', 'title' => 'My Profile', 'slug' => 'my-profile', 'id' => $portal_page_id),
        );
        ?>
        <p class="description">Select or create the pages you want to use for the core plugin features.</p>
        <table class="form-table">
            <?php foreach ($pages_config as $opt_key => $cfg): ?>
                <tr valign="top">
                    <th scope="row"><?php echo esc_html($cfg['label']); ?><br><small><code><?php echo esc_html($cfg['shortcode']); ?></code></small></th>
                    <td>
                        <?php wp_dropdown_pages(array('name' => $opt_key, 'selected' => $cfg['id'], 'show_option_none' => '&mdash; Select Page &mdash;')); ?>
                        
                        <?php if ($cfg['id'] && get_post_status($cfg['id']) === 'publish'): ?>
                            <a href="<?php echo esc_url(get_permalink($cfg['id'])); ?>" target="_blank" class="button button-small" style="margin-left:8px; vertical-align:middle;">View Page &rarr;</a>
                            <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=msfm_manage_single_page&sub_action=delete&option_key=' . $opt_key), 'msfm_page_action', 'msfm_p_nonce')); ?>" class="button button-small" style="margin-left:8px; vertical-align:middle; background: #dc3545; border-color: #dc3545; color: #ffffff; font-weight: 600;" onclick="return confirm('Are you sure you want to delete this page permanently?');">Delete Page</a>
                        <?php else: ?>
                            <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=msfm_manage_single_page&sub_action=create&option_key=' . $opt_key), 'msfm_page_action', 'msfm_p_nonce')); ?>" class="button button-small button-secondary" style="margin-left:8px; vertical-align:middle;">Create & Assign Page</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
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
            <h4 style="margin-top:0; color:#2b6cb0;">Webhook Listener URL</h4>
            <p style="margin-bottom:8px;">Copy and paste this Webhook URL into your <strong>Fawaterak Dashboard Integrations</strong> to enable automated background order status updates:</p>
            <code style="font-size:14px; background:#fff; padding:6px 10px; border:1px solid #cbd5e0; border-radius:4px; display:inline-block; font-weight:bold;"><?php echo esc_url($webhook_url); ?></code>
        </div>

        <h3>Fawaterak Online Gateway</h3>
        <p class="description">Fawaterak uses a static Bearer Token (API Key) for authentication. Retrieve this from your Fawaterak Dashboard -> Integrations.</p>
        
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

                    <div id="fawaterak_test_cards" style="display: <?php echo ($fawaterak_env === 'sandbox') ? 'block' : 'none'; ?>; margin-top: 25px; padding: 20px; background: #111315; border-radius: 12px; max-width: 1050px;">
                        <h4 style="color: #f6ad55; margin: 0 0 5px 0; font-size: 16px;">Sandbox Test Cards Reference</h4>
                        <p style="color: #a0aec0; margin: 0 0 20px 0; font-size: 12px;">Use these test cards for exercising payment gateways in staging mode. They will not work in Production.</p>
                        
                        <style>
                        .qaff-cards-grid { display: flex; flex-wrap: wrap; gap: 15px; }
                        .card { background-color: #1a1d20; border: 1px solid #2d3748; border-radius: 12px; color: #fff; padding: 1.25rem; width: 100%; max-width: 300px; box-shadow: 0 10px 20px rgba(0, 0, 0, 0.25); box-sizing: border-box; transition: transform 0.2s, border-color 0.2s; }
                        .card:hover { transform: translateY(-2px); border-color: #4a5568; }
                        .card--success { border-left: 4px solid #38a169; }
                        .card--fail { border-left: 4px solid #e53e3e; background-color: #221517; }
                        .card__header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.5rem; }
                        .card__title { color: #edf2f7; font-size: 1.05rem; font-weight: 600; }
                        .card__logo { width: 44px; height: 28px; display: flex; align-items: center; justify-content: center; }
                        .card__logo svg { width: 40px; height: auto; }
                        .card__number { margin-bottom: 1.25rem; }
                        .card__label { display: block; color: #a0aec0; font-size: 0.7rem; letter-spacing: 0.08em; text-transform: uppercase; margin-bottom: 0.3rem; }
                        .card__number__value-wrapper { display: flex; align-items: center; justify-content: space-between; }
                        .card__number__value { color: #fff; font-size: 1.15rem; letter-spacing: 0.06em; font-family: monospace; white-space: nowrap; }
                        .qaff-copy-btn { background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.15); border-radius: 4px; color: #fff; cursor: pointer; padding: 4px 6px; display: flex; align-items: center; justify-content: center; position: relative; transition: background 0.2s; }
                        .qaff-copy-btn:hover { background: rgba(255,255,255,0.2); }
                        .qaff-copy-btn svg { width: 14px; height: 14px; fill: currentColor; }
                        .qaff-copy-btn .qaff-tooltip { visibility: hidden; width: 60px; background-color: #38a169; color: #fff; text-align: center; border-radius: 4px; padding: 3px 0; position: absolute; z-index: 10; bottom: 130%; left: 50%; transform: translateX(-50%); opacity: 0; transition: opacity 0.2s; font-size: 10px; font-weight: bold; }
                        .qaff-copy-btn.copied .qaff-tooltip { visibility: visible; opacity: 1; }
                        .card__details { display: flex; justify-content: space-between; align-items: flex-end; border-top: 1px dashed rgba(255,255,255,0.1); padding-top: 10px; }
                        .card__expiration, .card__ccv { display: flex; flex-direction: column; }
                        .card__value { display: block; color: #e2e8f0; font-size: 0.9rem; font-family: monospace; }
                        .card__ccv { text-align: right; }
                        </style>

                        <?php 
                        $render_card = function($brand, $num, $exp, $ccv, $logo_svg, $is_fail = false) {
                            $card_class = $is_fail ? 'card card--fail' : 'card card--success';
                            $clean_num  = str_replace(' ', '', $num);
                            echo '<div class="' . $card_class . '">';
                            echo '<div class="card__header"><span class="card__title">' . esc_html($brand) . '</span><div class="card__logo">' . $logo_svg . '</div></div>';
                            echo '<div class="card__body">';
                            echo '<div class="card__number">';
                            echo '<span class="card__label">Card Number</span>';
                            echo '<div class="card__number__value-wrapper">';
                            echo '<span class="card__number__value">' . esc_html($num) . '</span>';
                            echo '<button type="button" class="qaff-copy-btn" onclick="qaffCopyCard(\'' . esc_attr($clean_num) . '\', this)" title="Copy Card Number">';
                            echo '<svg viewBox="0 0 24 24"><path d="M16 1H4c-1.1 0-2 .9-2 2v14h2V3h12V1zm3 4H8c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h11c1.1 0 2-.9 2-2V7c0-1.1-.9-2-2-2zm0 16H8V7h11v14z"/></svg>';
                            echo '<span class="qaff-tooltip">Copied!</span>';
                            echo '</button>';
                            echo '</div></div>';
                            echo '<div class="card__details">';
                            echo '<div class="card__expiration"><span class="card__label">Expiration</span><span class="card__value">' . esc_html($exp) . '</span></div>';
                            echo '<div class="card__ccv"><span class="card__label">CCV</span><span class="card__value">' . esc_html($ccv) . '</span></div>';
                            echo '</div></div></div>';
                        };

                        $svg_mc = '<svg width="36" height="24" viewBox="0 0 24 24"><circle cx="9" cy="12" r="7" fill="#eb001b"/><circle cx="15" cy="12" r="7" fill="#f79e1b"/><path d="M12 7.2a7 7 0 0 1 0 9.6 7 7 0 0 0 0-9.6z" fill="#ff5f00"/></svg>';
                        $svg_visa = '<svg xmlns="http://www.w3.org/2000/svg" width="48" height="24" viewBox="0 0 175.7 53.9"><style>.visa{fill:#fff;}</style><path class="visa" d="M61.9 53.1l8.9-52.2h14.2l-8.9 52.2zm65.8-50.9c-2.8-1.1-7.2-2.2-12.7-2.2-14.1 0-24 7.1-24 17.2-.1 7.5 7.1 11.7 12.5 14.2 5.5 2.6 7.4 4.2 7.4 6.5 0 3.5-4.4 5.1-8.5 5.1-5.7 0-8.7-.8-13.4-2.7l-2-.9-2 11.7c3.3 1.5 9.5 2.7 15.9 2.8 15 0 24.7-7 24.8-17.8.1-5.9-3.7-10.5-11.9-14.2-5-2.4-8-4-8-6.5 0-2.2 2.6-4.5 8.1-4.5 4.7-.1 8 .9 10.6 2l1.3.6 1.9-11.3M164.2 1h-11c-3.4 0-6 .9-7.5 4.3l-21.1 47.8h14.9s2.4-6.4 3-7.8h18.2c.4 1.8 1.7 7.8 1.7 7.8h13.2l-11.4-52.1m-17.5 33.6c1.2-3 5.7-14.6 5.7-14.6-.1.1 1.2-3 1.9-5l1 4.5s2.7 12.5 3.3 15.1h-11.9zm-96.7-33.7l-14 35.6-1.5-7.2c-2.5-8.3-10.6-17.4-19.6-21.9l12.7 45.7h15.1l22.4-52.2h-15.1"/><path fill="#F7A600" d="M23.1.9h-22.9l-.2 1.1c17.9 4.3 29.7 14.8 34.6 27.3l-5-24c-.9-3.3-3.4-4.3-6.5-4.4"/></svg>';
                        $svg_meeza = '<svg xmlns="http://www.w3.org/2000/svg" width="48" height="24" viewBox="0 0 163 88"><style>.st0{fill:#FFFFFF;}.st1{fill:#510C76;}.st2{fill:#EB6B24;}.st3{fill:#FFFFFF;stroke:#FFFFFF;stroke-miterlimit:10;}</style><path class="st0" d="M147.2,12.5L143,65.8c-0.4,5.4-4.9,9.6-10.3,9.7h-87c-21.5,0-23-8.3-21.9-24l3.5-39L147.2,12.5z"/><path class="st1" d="M60.8,12.5l-4.5,63H34.5c-21.5,0-23-8.3-21.9-24l3.5-39H60.8z"/><path class="st1" d="M65.9,38l-0.8,11.5c-0.4,4.2,2.7,7.9,6.9,8.3c0.2,0,0.5,0,0.7,0h3.5c4.6-0.1,8.4-3.7,8.7-8.3l1.3-19.8H74.7C70,29.8,66.2,33.4,65.9,38z M77.9,49.5c-0.1,0.6-0.6,1.1-1.2,1.1h-3.5c-0.6,0-1-0.4-1-1c0,0,0-0.1,0-0.1L73,38c0.1-0.6,0.6-1.1,1.2-1.1h4.6L77.9,49.5z"/><path class="st1" d="M130.8,29.7h-11.7l-1.3,19.8c0,0.8,0,1.5,0.2,2.2h-0.5c-3.7,0-5.5-2.6-5.2-7.4l0.6-8.4l0,0l0.2-3c0.1-1.7-1.2-3.1-2.9-3.3c-0.1,0-0.1,0-0.2,0h-4l-0.2,3.3l-0.5,8v0.5v0.8l-0.1,0.9v0.4V44v0.3l0,0l-0.1,2c-0.1,1.6,0,3.2,0.3,4.8c-0.9,0.5-1.8,0.7-2.8,0.7c-3.2,0-7.3-1-7.3-6.6L95.9,33c0.2-1.7-1.1-3.2-2.8-3.3c-0.1,0-0.2,0-0.2,0h-4L88.7,33l-1.9,28.7c-0.1,1.8-0.4,6.3-0.4,6.3h3.9c1.8,0,3.4-1.5,3.5-3.3l0.4-7c1.1,0.6,3.9,0.9,5.6,0.9c2.8,0,5.4-1.2,7.2-3.3c1.7,2.1,4.3,3.3,7.7,3.3c2.3,0,4.5-0.7,6.3-2c0.7,0.7,1.5,1.2,2.5,1.5l0,0c0.6,0.2,1.3,0.2,1.9,0.2h3.5c4.6-0.1,8.4-3.7,8.8-8.3l0.8-11.9c0.4-4.2-2.7-7.9-6.9-8.3C131.3,29.7,131,29.7,130.8,29.7z M130.6,49.9c0,0.6-0.6,1.1-1.2,1.1h-3.5c-0.6,0-1-0.4-1-1c0,0,0-0.1,0-0.1l0.8-13h4.6c0.6,0,1,0.4,1,1c0,0,0,0.1,0,0.1L130.6,49.9z"/><path class="st1" d="M70,22.9l-0.1,1.6c-0.1,1.5,1,2.9,2.5,3c0.1,0,0.2,0,0.2,0h1.3c1.7,0,3-1.3,3.2-3l0.3-4.6h-4.3C71.5,19.9,70.1,21.2,70,22.9z"/><path class="st1" d="M78.1,22.9L78,24.5c-0.1,1.5,1,2.9,2.6,3c0.1,0,0.1,0,0.2,0H82c1.7,0,3-1.3,3.2-3l0.3-4.6h-4.2C79.6,19.9,78.2,21.2,78.1,22.9z"/><path class="st2" d="M91.9,27.4h1.6c1.5,0.1,2.9-1,3-2.6c0,0,0-0.1,0-0.1l0.1-1.3c0.1-1.7-0.8-3.5-2.4-3.5l-4.9,0l-0.3,4.2C88.9,25.8,90.2,27.3,91.9,27.4z"/><path class="st1" d="M95.6,63.4L95.5,65c-0.1,1.5,1,2.9,2.5,3c0.1,0,0.2,0,0.3,0h1.3c1.7,0,3-1.3,3.2-3l0.3-4.6h-4.2C97.1,60.4,95.7,61.8,95.6,63.4z"/><path class="st1" d="M104.2,63.4l-0.1,1.6c-0.1,1.5,1,2.9,2.5,3c0.1,0,0.2,0,0.2,0h1.3c1.7,0,3-1.3,3.2-3l0.3-4.6h-4.2C105.7,60.4,104.3,61.8,104.2,63.4z"/><path class="st1" d="M109.4,27.3h-0.9c-0.2,0-0.2-0.1-0.2-0.2l0.3-3.8c0-0.3,0-0.6,0-0.9c0-0.4-0.2-0.6-0.6-0.6s-0.7,0-1,0c-0.1,0-0.1,0-0.1,0.1c0.1,0.6,0.1,1.2,0,1.7c-0.1,0.8-0.1,1.7-0.2,2.5c0,0.3,0,0.6-0.1,0.9s-0.1,0.2-0.3,0.2h-1.8c-0.2,0-0.2,0-0.2-0.2c0.1-1.3,0.2-2.6,0.3-3.9c0-0.3,0-0.6,0-0.9c0-0.3-0.2-0.5-0.4-0.5c0,0-0.1,0-0.1,0c-0.3,0-0.7,0-1,0c-0.1,0-0.1,0-0.1,0.1c-0.1,0.8-0.1,1.6-0.2,2.4s-0.1,1.8-0.2,2.7c0,0.2-0.1,0.3-0.3,0.3h-1.8c-0.2,0-0.3-0.1-0.2-0.3c0.1-1.3,0.2-2.7,0.3-4c0.1-0.9,0.1-1.8,0.2-2.7c0-0.2,0.1-0.2,0.3-0.3c0.5-0.1,0.9-0.1,1.4-0.2c0.8-0.1,1.5-0.1,2.3,0c0.4,0,0.9,0.2,1.3,0.3c0.1,0,0.1,0,0.2,0c0.8-0.3,1.7-0.4,2.5-0.4c0.4,0,0.7,0.1,1.1,0.2c0.6,0.2,1.1,0.7,1.2,1.3c0.1,0.5,0.1,1.1,0,1.6l-0.2,3.3c0,0.3,0,0.6-0.1,0.9s-0.1,0.2-0.3,0.2L109.4,27.3z"/><path class="st1" d="M123.1,24.3h-2c-0.1,0-0.2,0-0.2,0.2c0,0.6,0.2,0.9,0.8,0.9c0.5,0,0.9,0,1.4,0h1.4c0.2,0,0.3,0,0.3,0.3c0,0.4,0,0.8-0.1,1.3c0,0.1-0.1,0.2-0.2,0.2c0,0,0,0,0,0c-0.7,0.1-1.3,0.2-2,0.2c-0.6,0-1.2,0-1.8,0c-0.4,0-0.8-0.2-1.2-0.4c-0.4-0.3-0.7-0.7-0.7-1.2c-0.1-0.7-0.1-1.4,0-2.1c0-0.6,0.1-1.2,0.3-1.8c0.1-0.6,0.5-1.2,1.1-1.5c0.3-0.2,0.7-0.3,1.1-0.4c0.9-0.1,1.8-0.1,2.6,0c0.4,0.1,0.8,0.2,1.1,0.5c0.3,0.3,0.6,0.8,0.6,1.3c0.1,0.8,0.1,1.6,0,2.4c0,0.1-0.1,0.2-0.2,0.2H125L123.1,24.3z M121.1,22.9h1.8c0.5,0,0.5,0,0.4-0.5c0-0.3-0.3-0.5-0.6-0.6c-0.2,0-0.5,0-0.7,0c-0.4,0-0.7,0.2-0.8,0.6C121.1,22.5,121.1,22.7,121.1,22.9z"/><path class="st1" d="M115.9,24.3c-0.7,0-1.3,0-1.9,0c-0.2,0-0.2,0.1-0.2,0.2c0,0.6,0.2,0.8,0.8,0.9c0.2,0,0.4,0,0.5,0h2.3c0.2,0,0.3,0,0.2,0.2c0,0.4-0.1,0.9-0.1,1.3c0,0.1-0.1,0.2-0.2,0.2c0,0,0,0,0,0c-0.6,0.1-1.3,0.1-1.9,0.2c-0.7,0-1.4,0-2.2-0.1c-0.3,0-0.6-0.2-0.9-0.3c-0.4-0.3-0.7-0.7-0.8-1.3c-0.1-0.8-0.1-1.5,0-2.3c0-0.5,0.1-1,0.2-1.5c0.1-0.7,0.5-1.3,1.1-1.6c0.3-0.2,0.7-0.3,1.1-0.3c0.9-0.1,1.8-0.1,2.7,0c0.4,0.1,0.8,0.2,1.1,0.5c0.4,0.3,0.6,0.8,0.6,1.3c0,0.6,0,1.2,0,1.7c0,0.2,0,0.4,0,0.5c0,0.1-0.1,0.3-0.3,0.3L115.9,24.3L115.9,24.3z M116.1,22.9c0-0.2,0-0.4,0-0.6c0-0.3-0.3-0.5-0.5-0.5c-0.2,0-0.5,0-0.7,0c-0.6,0-0.9,0.5-0.9,1.1L116.1,22.9z"/><path class="st1" d="M135.1,21.7h-1.3c-0.2,0-0.3-0.1-0.2-0.2c0-0.4,0.1-0.9,0.1-1.3c0-0.1,0.1-0.2,0.2-0.2c1-0.2,2.1-0.3,3.1-0.2c0.5,0,0.9,0.1,1.4,0.2c0.7,0.2,1.2,0.9,1.2,1.6c0,0.9-0.1,1.8-0.2,2.8l-0.1,2.1c0,0.4-0.1,0.5-0.5,0.6c-1.3,0.2-2.6,0.3-3.9,0.2c-0.3,0-0.7-0.1-1-0.1c-0.5-0.1-0.9-0.6-1-1.1c-0.1-0.7-0.1-1.4,0.2-2c0.2-0.6,0.8-1.1,1.5-1.1c0.4-0.1,0.7-0.1,1.1-0.1c0.5,0,1,0,1.5,0c0.1,0,0.1,0,0.1-0.1v-0.1c0-0.5-0.2-0.8-0.8-0.8L135.1,21.7z M137.1,24.1c-0.5,0-1,0-1.4,0c-0.3,0-0.6,0.2-0.6,0.5c0,0.1,0,0.2,0,0.3c0,0.3,0.2,0.5,0.4,0.5c0,0,0,0,0.1,0c0.5,0,1,0,1.4-0.1c0,0,0.1,0,0.1-0.1C137,24.9,137,24.5,137.1,24.1L137.1,24.1z"/><path class="st1" d="M129.1,25.3h3c0.1,0,0.1,0.1,0.1,0.2c0,0.5-0.1,1-0.1,1.5c0,0.2-0.1,0.2-0.3,0.2H126c-0.3,0-0.3-0.1-0.3-0.3s0-0.7,0.1-1c0-0.1,0.1-0.2,0.1-0.3l2.8-3.3l0.5-0.6h-2.7c-0.3,0-0.3,0-0.3-0.3l0.1-1.3c0-0.2,0.1-0.3,0.3-0.3h5.6c0.2,0,0.3,0.1,0.2,0.3c0,0.4,0,0.7-0.1,1.1c0,0.1,0,0.2-0.1,0.3c-1,1.2-2,2.5-3,3.7L129.1,25.3z"/><path class="st2" d="M117.1,60.8c-1.8,0.1-3.2,1.5-3.3,3.3l-0.3,3.9h20c1.8-0.1,3.2-1.5,3.3-3.3l0.3-3.9H117.1z"/><path class="st3" d="M130.4,77H34.5c-10.1,0-16.2-1.7-19.9-5.6c-4.4-4.8-4.3-12.3-3.8-20.2l3.6-40.7l134.7,0.1l-4.3,53C144.1,71.1,137.9,76.8,130.4,77z M17.7,14l-3.3,37.5C13.8,60,14.1,65.6,17.2,69c2.9,3.1,8.4,4.5,17.3,4.5h95.9c5.7-0.2,10.4-4.5,11.1-10.2l4-49.2L17.7,14z"/><path class="st0" d="M40.8,58H29.4c-1.7,0-3-1.3-3-3c0-0.1,0-0.2,0-0.3l1.6-22c0.2-1.8,1.7-3.2,3.5-3.3h11.3c1.7,0,3,1.3,3,3c0,0.1,0,0.2,0,0.3l-1.6,22C44.1,56.5,42.6,58,40.8,58z M31.3,31.7c-0.6,0-1,0.4-1.1,1l-1.6,22c0,0.6,0.4,1,1,1H41c0.6,0,1-0.4,1.1-1l1.6-22c0-0.6-0.4-1-1-1H31.3z"/><polygon class="st0" points="43.7,51 43.5,53.2 27.7,53.2 27.9,51 "/></svg>';

                        // Successful Grid
                        echo '<p style="margin: 0 0 10px 0; font-weight: 600; color: #48bb78; font-size: 13px;">Successful Test Transactions:</p>';
                        echo '<div class="qaff-cards-grid" style="margin-bottom: 20px;">';
                        $render_card('Mastercard', '5123 4500 0000 0008', '12/26', '100', $svg_mc);
                        $render_card('Visa', '4005 5500 0000 0001', '12/26', '100', $svg_visa);
                        $render_card('Meeza', '5078 0362 4660 0381', '12/26', '100', $svg_meeza);
                        echo '</div>';

                        // Failed Grid
                        echo '<p style="margin: 0 0 10px 0; font-weight: 600; color: #f56565; font-size: 13px;">Failed Test Transactions:</p>';
                        echo '<div class="qaff-cards-grid">';
                        $render_card('Mastercard (Fail)', '5543 4740 0224 9996', '05/26', '123', $svg_mc, true);
                        $render_card('Visa (Fail)', '4222 0000 0672 4235', '12/26', '123', $svg_visa, true);
                        $render_card('Meeza (Fail)', '5078 0362 4278 3546', '12/26', '123', $svg_meeza, true);
                        echo '</div>';
                        ?>

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
                <th scope="row">Fawaterak API Key (Bearer Token)</th>
                <td>
                    <input type="password" name="msfm_fawaterak_api_key" value="<?php echo esc_attr($api_key); ?>" class="regular-text">
                </td>
            </tr>
            <tr valign="top" id="msfm_provider_key_row" style="<?php echo ($integration_type === 'iframe') ? '' : 'display: none;'; ?>">
                <th scope="row">Fawaterak Provider Key</th>
                <td>
                    <input type="password" name="msfm_fawaterak_provider_key" value="<?php echo esc_attr($provider_key); ?>" class="regular-text">
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
        
        // Pure JavaScript Copy to Clipboard with execCommand Fallback
        function qaffCopyCard(text, btn) {
            function showSuccess() {
                btn.classList.add('copied');
                setTimeout(function() {
                    btn.classList.remove('copied');
                }, 1500);
            }

            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(text).then(showSuccess).catch(function() {
                    fallbackCopy(text);
                });
            } else {
                fallbackCopy(text);
            }

            function fallbackCopy(val) {
                var textArea = document.createElement("textarea");
                textArea.value = val;
                textArea.style.position = "fixed";
                textArea.style.left = "-999999px";
                textArea.style.top = "-999999px";
                document.body.appendChild(textArea);
                textArea.focus();
                textArea.select();
                try {
                    document.execCommand('copy');
                    showSuccess();
                } catch (err) {
                    console.error('Fallback copy failed', err);
                }
                document.body.removeChild(textArea);
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

    public function handle_manage_single_page() {
        if (!current_user_can('manage_options') || !isset($_GET['msfm_p_nonce']) || !wp_verify_nonce($_GET['msfm_p_nonce'], 'msfm_page_action')) {
            wp_die('Unauthorized request.');
        }

        $sub_action = isset($_GET['sub_action']) ? sanitize_text_field($_GET['sub_action']) : '';
        $option_key = isset($_GET['option_key']) ? sanitize_text_field($_GET['option_key']) : '';

        $pages_meta = array(
            'msfm_checkout_page_id' => array('title' => 'Checkout', 'content' => '[saas_checkout_page]', 'slug' => 'checkout'),
            'msfm_login_page_id'    => array('title' => 'Login', 'content' => '[saas_login_form]', 'slug' => 'login'),
            'msfm_portal_page_id'   => array('title' => 'My Profile', 'content' => '[saas_user_portal]', 'slug' => 'my-profile'),
        );

        if (array_key_exists($option_key, $pages_meta)) {
            if ($sub_action === 'create') {
                $meta = $pages_meta[$option_key];
                $new_id = self::get_or_create_page($option_key, $meta['title'], $meta['content'], $meta['slug']);
                if ($new_id) {
                    update_option($option_key, $new_id);
                }
            } elseif ($sub_action === 'delete') {
                $page_id = get_option($option_key);
                if ($page_id) {
                    wp_delete_post($page_id, true);
                    delete_option($option_key);
                }
            }
        }

        wp_redirect(admin_url('edit.php?post_type=saas_package&page=qaff-settings&tab=pages&page_action_success=1'));
        exit;
    }
}