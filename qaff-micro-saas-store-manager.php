<?php
/**
 * Plugin Name:       Qaff Micro Saas Store Manager
 * Plugin URI:        https://codecom.dev
 * Description:       An all-in-one WordPress platform for managing Micro SaaS businesses. Includes dynamic pricing plans, passwordless Magic Link authentication, automated subscription renewal email reminders, seamless Fawaterak online payment gateway (Redirect & Embedded IFrame) and Cash on Delivery support, customer profile dashboard, and complete admin order management with editable payment statuses.
 * Version:           1.6.2
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            CodeCom.dev
 * Author URI:        https://codecom.dev
 * Text Domain:       qaff-microsaas
 * License:           GPLv2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

define('MSFM_VERSION', '1.6.2');
define('MSFM_PATH', plugin_dir_path(__FILE__));
define('MSFM_URL', plugin_dir_url(__FILE__));

require_once MSFM_PATH . 'includes/class-admin.php';
require_once MSFM_PATH . 'includes/class-settings.php';
require_once MSFM_PATH . 'includes/class-auth.php';
require_once MSFM_PATH . 'includes/class-checkout.php';
require_once MSFM_PATH . 'includes/class-fawaterak.php';
require_once MSFM_PATH . 'includes/class-user-portal.php';
require_once MSFM_PATH . 'includes/class-notifications.php';

class Qaff_MicroSaaS_Plugin {

    public function __construct() {
        register_activation_hook(__FILE__, array($this, 'activate_plugin'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate_plugin'));
        add_action('plugins_loaded', array($this, 'check_db_version'));
        
        new MSFM_Admin();
        new MSFM_Settings();
        new MSFM_Auth();
        new MSFM_Checkout();
        new MSFM_User_Portal();
        new MSFM_Notifications();
    }

    public function activate_plugin() {
        $this->create_db_tables();
        MSFM_Admin::register_package_cpt();
        
        if (!wp_next_scheduled('msfm_daily_expiration_check')) {
            wp_schedule_event(time(), 'daily', 'msfm_daily_expiration_check');
        }
        
        flush_rewrite_rules();
    }

    public function deactivate_plugin() {
        $timestamp = wp_next_scheduled('msfm_daily_expiration_check');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'msfm_daily_expiration_check');
        }
    }

    public function check_db_version() {
        if (get_option('msfm_db_version') !== MSFM_VERSION) {
            $this->create_db_tables();
            add_action('admin_init', array($this, 'ensure_pages_exist'));
            update_option('msfm_db_version', MSFM_VERSION);
        }
    }

    public function ensure_pages_exist() {
        MSFM_Settings::get_or_create_page('msfm_checkout_page_id', 'Checkout', '[saas_checkout_page]', 'checkout');
        MSFM_Settings::get_or_create_page('msfm_login_page_id', 'Login', '[saas_login_form]', 'login');
        MSFM_Settings::get_or_create_page('msfm_portal_page_id', 'My Profile', '[saas_user_portal]', 'my-profile');
    }

    private function create_db_tables() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'microsaas_orders';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            package_id bigint(20) NOT NULL,
            billing_cycle varchar(20) NOT NULL,
            amount decimal(10,2) NOT NULL,
            currency varchar(10) DEFAULT 'USD',
            payment_status varchar(20) DEFAULT 'pending',
            payment_method varchar(50) DEFAULT 'fawaterak',
            fawaterak_invoice_id varchar(100) DEFAULT '',
            full_name varchar(191) DEFAULT '',
            phone_number varchar(50) DEFAULT '',
            country varchar(100) DEFAULT '',
            address text DEFAULT '',
            zip_code varchar(20) DEFAULT '',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}

new Qaff_MicroSaaS_Plugin();