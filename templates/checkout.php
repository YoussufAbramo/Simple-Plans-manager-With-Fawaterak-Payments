<?php
if (!defined('ABSPATH')) exit;

// 1. Embedded IFrame Payment Render with Auto-Resizing & Developer Classes
if (isset($_GET['pay_iframe']) && isset($_GET['order_id'])) {
    $order_id = intval($_GET['order_id']);
    $iframe_url = get_transient('msfm_iframe_url_' . $order_id);
    
    global $wpdb;
    $order = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}microsaas_orders WHERE id = %d", $order_id));
    $package = $order ? get_post($order->package_id) : null;

    if (!$iframe_url && $order && !empty($order->fawaterak_url)) {
        $iframe_url = $order->fawaterak_url;
    }
    
    if ($iframe_url && $order) {
        ?>
        <div class="smpl_pkg_mngr-iframe-container" style="max-width: 850px; margin: 40px auto; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; color: #2d3748;">
            
            <div class="smpl_pkg_mngr-iframe-card" style="background: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.05); overflow: hidden;">
                
                <div class="smpl_pkg_mngr-iframe-header" style="background: #f8fafc; padding: 25px 30px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
                    <div class="smpl_pkg_mngr-iframe-header-details">
                        <span class="smpl_pkg_mngr-badge-label" style="font-size: 12px; font-weight: bold; color: #718096; text-transform: uppercase; letter-spacing: 0.05em; display: block; margin-bottom: 3px;">Complete Payment</span>
                        <h2 class="smpl_pkg_mngr-iframe-title" style="margin: 0; font-size: 22px; color: #1a202c; display: flex; align-items: center; gap: 10px;">
                            Order <span class="smpl_pkg_mngr-order-id" style="color: #3182ce;">#<?php echo esc_html($order->id); ?></span>
                        </h2>
                        <div class="smpl_pkg_mngr-plan-info" style="font-size: 13px; color: #4a5568; margin-top: 4px;">
                            Plan: <strong><?php echo esc_html($package ? $package->post_title : 'Subscription'); ?></strong> (<?php echo esc_html(ucfirst($order->billing_cycle)); ?>)
                        </div>
                    </div>
                    <div class="smpl_pkg_mngr-iframe-header-total" style="text-align: right;">
                        <span class="smpl_pkg_mngr-total-label" style="font-size: 12px; color: #718096; display: block; margin-bottom: 2px;">Total Due:</span>
                        <span class="smpl_pkg_mngr-total-amount" style="font-size: 24px; font-weight: 800; color: #2b6cb0;">
                            <?php echo esc_html(MSFM_Settings::format_price($order->amount)); ?>
                        </span>
                    </div>
                </div>

                <div class="smpl_pkg_mngr-trust-indicator" style="background: #ebf8ff; border-bottom: 1px solid #bee3f8; padding: 10px 30px; display: flex; align-items: center; justify-content: center; gap: 8px; font-size: 12px; color: #2b6cb0; font-weight: 500;">
                    <svg class="smpl_pkg_mngr-icon-lock" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    256-Bit SSL Encrypted & Secure Fawaterak Payment
                </div>

                <div class="smpl_pkg_mngr-iframe-body" style="position: relative; background: #ffffff; min-height: 1350px;">
                    <div id="qaff-iframe-loader" class="smpl_pkg_mngr-iframe-loader" style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; display: flex; flex-direction: column; align-items: center; justify-content: center; background: #ffffff; z-index: 10;">
                        <div class="smpl_pkg_mngr-spinner" style="width: 40px; height: 40px; border: 4px solid #e2e8f0; border-top-color: #3182ce; border-radius: 50%; animation: smpl-spin 1s linear infinite;"></div>
                        <p class="smpl_pkg_mngr-loader-text" style="margin-top: 15px; color: #718096; font-size: 14px;">Loading Secure Payment Checkout...</p>
                    </div>

                    <iframe id="qaff-fawaterak-iframe" class="smpl_pkg_mngr-iframe-element" src="<?php echo esc_url($iframe_url); ?>" width="100%" height="1350px" frameborder="0" style="border: 0; width: 100%; height: 1350px; border-radius: 0 0 12px 12px; display: block; position: relative; z-index: 20;" onload="document.getElementById('qaff-iframe-loader').style.display='none';"></iframe>
                </div>

            </div>

            <div class="smpl_pkg_mngr-cancel-wrapper" style="text-align: center; margin-top: 20px;">
                <a href="<?php echo esc_url(remove_query_arg(array('pay_iframe', 'order_id'))); ?>" class="smpl_pkg_mngr-btn-cancel" style="color: #e53e3e; text-decoration: none; font-size: 14px; font-weight: 600; display: inline-flex; align-items: center; gap: 5px;">
                    &laquo; Cancel Transaction & Return to Checkout
                </a>
            </div>

            <style>
            @keyframes smpl-spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
            </style>
        </div>
        <?php
        return; 
    }
}

$enable_cod   = get_option('msfm_enable_cod', '1');
$cod_label    = get_option('msfm_cod_label', 'Pay on Delivery / Cash on Delivery');
$current_user = is_user_logged_in() ? wp_get_current_user() : null;
$saved_company= $current_user ? get_user_meta($current_user->ID, 'billing_company', true) : '';
?>

<div class="smpl_pkg_mngr-checkout-wrapper" style="max-width: 900px; margin: 30px auto; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #ffffff; padding: 35px; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.05);">
    
    <?php if (isset($_GET['payment_failed_api'])): ?>
        <div class="smpl_pkg_mngr-alert smpl_pkg_mngr-alert-error" style="background: #fed7d7; color: #9b2c2c; padding: 15px; border-radius: 8px; border: 1px solid #f56565; margin-bottom: 25px; font-weight: 500;">
            ❌ <strong>Payment Connection Failed.</strong> <?php echo esc_html(urldecode($_GET['error_msg'])); ?>
        </div>
    <?php endif; ?>

    <h2 class="smpl_pkg_mngr-checkout-title" style="text-align: center; margin-top: 0; margin-bottom: 30px; color: #1a202c; font-size: 26px;">Checkout & Subscription</h2>

    <form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="POST" id="qaff-checkout-form" class="smpl_pkg_mngr-form smpl_pkg_mngr-checkout-form">
        <?php wp_nonce_field('msfm_checkout_action', 'msfm_checkout_nonce'); ?>
        <input type="hidden" name="action" value="msfm_process_checkout">

        <div class="smpl_pkg_mngr-checkout-grid" style="display: flex; flex-wrap: wrap; gap: 35px;">
            
            <div class="smpl_pkg_mngr-customer-section" style="flex: 1 1 450px;">
                <h3 class="smpl_pkg_mngr-section-title" style="margin-top: 0; padding-bottom: 10px; border-bottom: 2px solid #3182ce; color: #2d3748; font-size: 18px;">1. Customer Details</h3>
                
                <div class="smpl_pkg_mngr-form-row" style="display: flex; gap: 15px; margin-bottom: 15px;">
                    <div class="smpl_pkg_mngr-form-group" style="flex: 1;">
                        <label class="smpl_pkg_mngr-form-label" style="display: block; font-weight: 600; color: #4a5568; margin-bottom: 5px;">First Name <span style="color:red;">*</span></label>
                        <input type="text" name="first_name" required value="<?php echo $current_user ? esc_attr($current_user->user_firstname) : ''; ?>" class="smpl_pkg_mngr-input-field" style="width: 100%; padding: 12px; border: 1px solid #cbd5e0; border-radius: 6px; box-sizing: border-box;">
                    </div>
                    <div class="smpl_pkg_mngr-form-group" style="flex: 1;">
                        <label class="smpl_pkg_mngr-form-label" style="display: block; font-weight: 600; color: #4a5568; margin-bottom: 5px;">Last Name <span style="color:red;">*</span></label>
                        <input type="text" name="last_name" required value="<?php echo $current_user ? esc_attr($current_user->user_lastname) : ''; ?>" class="smpl_pkg_mngr-input-field" style="width: 100%; padding: 12px; border: 1px solid #cbd5e0; border-radius: 6px; box-sizing: border-box;">
                    </div>
                </div>

                <div class="smpl_pkg_mngr-form-group" style="margin-bottom: 15px;">
                    <label class="smpl_pkg_mngr-form-label" style="display: block; font-weight: 600; color: #4a5568; margin-bottom: 5px;">Company Name <span style="color:#718096; font-weight:normal;">(Optional)</span></label>
                    <input type="text" name="company" value="<?php echo esc_attr($saved_company); ?>" placeholder="e.g. Acme Corp" class="smpl_pkg_mngr-input-field" style="width: 100%; padding: 12px; border: 1px solid #cbd5e0; border-radius: 6px; box-sizing: border-box;">
                </div>

                <div class="smpl_pkg_mngr-form-group" style="margin-bottom: 15px;">
                    <label class="smpl_pkg_mngr-form-label" style="display: block; font-weight: 600; color: #4a5568; margin-bottom: 5px;">Phone Number <span style="color:red;">*</span></label>
                    <div class="smpl_pkg_mngr-phone-group" style="display: flex; gap: 10px;">
                        <select name="phone_code" required class="smpl_pkg_mngr-select-code" style="width: 130px; padding: 12px; border: 1px solid #cbd5e0; border-radius: 6px; box-sizing: border-box; background: #ffffff;">
                            <option value="20">🇪🇬 +20</option>
                            <option value="966">🇸🇦 +966</option>
                            <option value="971">🇦🇪 +971</option>
                            <option value="965">🇰🇼 +965</option>
                            <option value="974">🇶🇦 +974</option>
                            <option value="968">🇴🇲 +968</option>
                            <option value="973">🇧🇭 +973</option>
                            <option value="1">🇺🇸 +1</option>
                            <option value="44">🇬🇧 +44</option>
                        </select>
                        <input type="tel" name="phone_number" required placeholder="10xxxxxxxxx" class="smpl_pkg_mngr-input-field" style="flex: 1; padding: 12px; border: 1px solid #cbd5e0; border-radius: 6px; box-sizing: border-box;">
                    </div>
                </div>

                <?php if (!is_user_logged_in()): ?>
                    <div class="smpl_pkg_mngr-form-group" style="margin-bottom: 15px;">
                        <label class="smpl_pkg_mngr-form-label" style="display: block; font-weight: 600; color: #4a5568; margin-bottom: 5px;">Email Address <span style="color:red;">*</span></label>
                        <input type="email" name="user_email" required class="smpl_pkg_mngr-input-field" style="width: 100%; padding: 12px; border: 1px solid #cbd5e0; border-radius: 6px; box-sizing: border-box;">
                    </div>
                    <div class="smpl_pkg_mngr-form-group" style="margin-bottom: 15px;">
                        <label class="smpl_pkg_mngr-form-label" style="display: block; font-weight: 600; color: #4a5568; margin-bottom: 5px;">Create Password <span style="color:red;">*</span></label>
                        <input type="password" name="user_password" required class="smpl_pkg_mngr-input-field" style="width: 100%; padding: 12px; border: 1px solid #cbd5e0; border-radius: 6px; box-sizing: border-box;">
                    </div>
                <?php endif; ?>

                <h3 class="smpl_pkg_mngr-section-title" style="margin-top: 30px; padding-bottom: 10px; border-bottom: 2px solid #3182ce; color: #2d3748; font-size: 18px;">2. Address Information</h3>

                <div class="smpl_pkg_mngr-form-group" style="margin-bottom: 15px;">
                    <label class="smpl_pkg_mngr-form-label" style="display: block; font-weight: 600; color: #4a5568; margin-bottom: 5px;">Country <span style="color:red;">*</span></label>
                    <input type="text" name="country" required value="<?php echo isset($_POST['country']) ? esc_attr($_POST['country']) : 'Egypt'; ?>" class="smpl_pkg_mngr-input-field" style="width: 100%; padding: 12px; border: 1px solid #cbd5e0; border-radius: 6px; box-sizing: border-box;">
                </div>

                <div class="smpl_pkg_mngr-form-group" style="margin-bottom: 15px;">
                    <label class="smpl_pkg_mngr-form-label" style="display: block; font-weight: 600; color: #4a5568; margin-bottom: 5px;">Street Address</label>
                    <textarea name="address" rows="2" class="smpl_pkg_mngr-textarea" style="width: 100%; padding: 12px; border: 1px solid #cbd5e0; border-radius: 6px; box-sizing: border-box;"></textarea>
                </div>

                <div class="smpl_pkg_mngr-form-group" style="margin-bottom: 15px;">
                    <label class="smpl_pkg_mngr-form-label" style="display: block; font-weight: 600; color: #4a5568; margin-bottom: 5px;">ZIP / Postal Code</label>
                    <input type="text" name="zip_code" class="smpl_pkg_mngr-input-field" style="width: 100%; padding: 12px; border: 1px solid #cbd5e0; border-radius: 6px; box-sizing: border-box;">
                </div>
            </div>

            <div class="smpl_pkg_mngr-summary-section" style="flex: 1 1 320px; background: #f8fafc; padding: 25px; border-radius: 8px; border: 1px solid #e2e8f0; height: fit-content;">
                <h3 class="smpl_pkg_mngr-section-title" style="margin-top: 0; padding-bottom: 10px; border-bottom: 2px solid #3182ce; color: #2d3748; font-size: 18px;">3. Order Summary</h3>

                <div class="smpl_pkg_mngr-form-group" style="margin-bottom: 15px;">
                    <label class="smpl_pkg_mngr-form-label" style="display: block; font-weight: 600; color: #4a5568; margin-bottom: 5px;">Selected Plan</label>
                    <select name="package_id" id="package-select" class="smpl_pkg_mngr-plan-select" onchange="window.location.href='?package_id='+this.value" style="width: 100%; padding: 12px; border: 1px solid #cbd5e0; border-radius: 6px; background: #ffffff; box-sizing: border-box;">
                        <?php foreach ($packages as $pkg): ?>
                            <option value="<?php echo $pkg->ID; ?>" <?php selected($pkg->ID, $package_id); ?>>
                                <?php echo esc_html($pkg->post_title); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="smpl_pkg_mngr-billing-group" style="margin-bottom: 20px; background: #ffffff; padding: 15px; border: 1px solid #e2e8f0; border-radius: 6px;">
                    <label class="smpl_pkg_mngr-form-label" style="display: block; font-weight: 600; color: #4a5568; margin-bottom: 10px;">Billing Frequency</label>
                    <label class="smpl_pkg_mngr-radio-label" style="display: block; margin-bottom: 8px; cursor: pointer;">
                        <input type="radio" name="billing_cycle" value="monthly" checked class="smpl_pkg_mngr-radio"> Monthly: 
                        <?php 
                            if ($monthly_sale !== '' && $monthly_sale !== false) {
                                echo '<del style="color:#a00;">' . esc_html(MSFM_Settings::format_price($monthly_reg)) . '</del> <strong style="color:#2b6cb0;">' . esc_html(MSFM_Settings::format_price($monthly_sale)) . '</strong>';
                            } else {
                                echo '<strong>' . esc_html(MSFM_Settings::format_price($monthly_reg)) . '</strong>';
                            }
                        ?>
                    </label>
                    <label class="smpl_pkg_mngr-radio-label" style="display: block; cursor: pointer;">
                        <input type="radio" name="billing_cycle" value="annual" class="smpl_pkg_mngr-radio"> Annual: 
                        <?php 
                            if ($annual_sale !== '' && $annual_sale !== false) {
                                echo '<del style="color:#a00;">' . esc_html(MSFM_Settings::format_price($annual_reg)) . '</del> <strong style="color:#2b6cb0;">' . esc_html(MSFM_Settings::format_price($annual_sale)) . '</strong>';
                            } else {
                                echo '<strong>' . esc_html(MSFM_Settings::format_price($annual_reg)) . '</strong>';
                            }
                        ?>
                    </label>
                </div>

                <h3 class="smpl_pkg_mngr-section-title" style="margin-top: 25px; padding-bottom: 10px; border-bottom: 2px solid #3182ce; color: #2d3748; font-size: 18px;">4. Payment Method</h3>

                <div class="smpl_pkg_mngr-payment-methods" style="background: #ffffff; padding: 15px; border: 1px solid #e2e8f0; border-radius: 6px; margin-bottom: 20px;">
                    <label class="smpl_pkg_mngr-radio-label" style="display: block; margin-bottom: 10px; cursor: pointer;">
                        <input type="radio" name="payment_method" value="fawaterak" checked class="smpl_pkg_mngr-radio"> <strong>Fawaterak Online Gateway</strong>
                        <br><small style="color: #718096; margin-left: 20px; display: block;">Credit Cards, Fawry, Mobile Wallets.</small>
                    </label>
                    <?php if ($enable_cod == '1'): ?>
                        <hr style="margin: 10px 0; border: 0; border-top: 1px solid #edf2f7;">
                        <label class="smpl_pkg_mngr-radio-label" style="display: block; cursor: pointer;">
                            <input type="radio" name="payment_method" value="cod" class="smpl_pkg_mngr-radio"> <strong><?php echo esc_html($cod_label); ?></strong>
                            <br><small style="color: #718096; margin-left: 20px; display: block;">Complete order now and pay offline later.</small>
                        </label>
                    <?php endif; ?>
                </div>

                <button type="submit" id="qaff-submit-btn" class="smpl_pkg_mngr-btn smpl_pkg_mngr-btn-submit" style="width: 100%; background: #3182ce; color: #ffffff; font-size: 16px; font-weight: bold; padding: 14px; border: none; border-radius: 6px; cursor: pointer; transition: 0.2s;">
                    Complete Purchase
                </button>
            </div>

        </div>
    </form>
</div>