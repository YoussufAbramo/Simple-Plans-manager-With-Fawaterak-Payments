<?php
if (!defined('ABSPATH')) exit;

// 1. Handle Embedded IFrame Render
if (isset($_GET['pay_iframe']) && isset($_GET['order_id'])) {
    $order_id = intval($_GET['order_id']);
    $iframe_url = get_transient('msfm_iframe_url_' . $order_id);
    if ($iframe_url) {
        ?>
        <div class="qaff-checkout-wrapper" style="max-width: 900px; margin: 30px auto; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;">
            <h2 style="text-align: center; margin-top: 0; margin-bottom: 20px; color: #1a202c; font-size: 26px;">Complete Your Payment</h2>
            <div style="background: #ffffff; padding: 15px; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.05);">
                <iframe src="<?php echo esc_url($iframe_url); ?>" width="100%" height="750px" frameborder="0" style="border-radius: 8px;"></iframe>
            </div>
            <div style="text-align:center; margin-top: 15px;">
                <a href="<?php echo esc_url(remove_query_arg(array('pay_iframe', 'order_id'))); ?>" style="color: #718096; text-decoration: none;">&laquo; Cancel & Return to Checkout</a>
            </div>
        </div>
        <?php
        return; 
    }
}

$enable_cod   = get_option('msfm_enable_cod', '1');
$cod_label    = get_option('msfm_cod_label', 'Pay on Delivery / Cash on Delivery');
$current_user = is_user_logged_in() ? wp_get_current_user() : null;
?>

<div class="qaff-checkout-wrapper" style="max-width: 900px; margin: 30px auto; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background: #ffffff; padding: 35px; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.05);">
    
    <!-- API Connection & Gateway Feedback Banners -->
    <?php if (isset($_GET['payment_failed_api'])): ?>
        <div style="background: #fed7d7; color: #9b2c2c; padding: 15px; border-radius: 8px; border: 1px solid #f56565; margin-bottom: 25px; font-weight: 500;">
            ❌ <strong>Payment Connection Failed.</strong> <?php echo esc_html(urldecode($_GET['error_msg'])); ?>
        </div>
    <?php endif; ?>
    <?php if (isset($_GET['payment_failed'])): ?>
        <div style="background: #fed7d7; color: #9b2c2c; padding: 15px; border-radius: 8px; border: 1px solid #f56565; margin-bottom: 25px; font-weight: 500;">
            ❌ <strong>Payment Declined.</strong> Your transaction was canceled or declined. Please check your payment details and try again.
        </div>
    <?php endif; ?>

    <h2 style="text-align: center; margin-top: 0; margin-bottom: 30px; color: #1a202c; font-size: 26px;">Checkout & Subscription</h2>

    <form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="POST" id="qaff-checkout-form">
        <?php wp_nonce_field('msfm_checkout_action', 'msfm_checkout_nonce'); ?>
        <input type="hidden" name="action" value="msfm_process_checkout">

        <div style="display: flex; flex-wrap: wrap; gap: 35px;">
            
            <div style="flex: 1 1 450px;">
                <h3 style="margin-top: 0; padding-bottom: 10px; border-bottom: 2px solid #3182ce; color: #2d3748; font-size: 18px;">1. Customer Details</h3>
                
                <div style="display: flex; gap: 15px; margin-bottom: 15px;">
                    <div style="flex: 1;">
                        <label style="display: block; font-weight: 600; color: #4a5568; margin-bottom: 5px;">First Name <span style="color:red;">*</span></label>
                        <input type="text" name="first_name" required value="<?php echo $current_user ? esc_attr($current_user->user_firstname) : ''; ?>" style="width: 100%; padding: 12px; border: 1px solid #cbd5e0; border-radius: 6px; box-sizing: border-box;">
                    </div>
                    <div style="flex: 1;">
                        <label style="display: block; font-weight: 600; color: #4a5568; margin-bottom: 5px;">Last Name <span style="color:red;">*</span></label>
                        <input type="text" name="last_name" required value="<?php echo $current_user ? esc_attr($current_user->user_lastname) : ''; ?>" style="width: 100%; padding: 12px; border: 1px solid #cbd5e0; border-radius: 6px; box-sizing: border-box;">
                    </div>
                </div>

                <div style="margin-bottom: 15px;">
                    <label style="display: block; font-weight: 600; color: #4a5568; margin-bottom: 5px;">Phone Number <span style="color:red;">*</span></label>
                    <div style="display: flex; gap: 10px;">
                        <select name="phone_code" required style="width: 130px; padding: 12px; border: 1px solid #cbd5e0; border-radius: 6px; box-sizing: border-box; background: #ffffff;">
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
                        <input type="tel" name="phone_number" required placeholder="10xxxxxxxxx" style="flex: 1; padding: 12px; border: 1px solid #cbd5e0; border-radius: 6px; box-sizing: border-box;">
                    </div>
                </div>

                <?php if (!is_user_logged_in()): ?>
                    <p style="margin-bottom: 15px;">
                        <label style="display: block; font-weight: 600; color: #4a5568; margin-bottom: 5px;">Email Address <span style="color:red;">*</span></label>
                        <input type="email" name="user_email" required style="width: 100%; padding: 12px; border: 1px solid #cbd5e0; border-radius: 6px; box-sizing: border-box;">
                    </p>
                    <p style="margin-bottom: 15px;">
                        <label style="display: block; font-weight: 600; color: #4a5568; margin-bottom: 5px;">Create Password <span style="color:red;">*</span></label>
                        <input type="password" name="user_password" required style="width: 100%; padding: 12px; border: 1px solid #cbd5e0; border-radius: 6px; box-sizing: border-box;">
                    </p>
                <?php endif; ?>

                <h3 style="margin-top: 30px; padding-bottom: 10px; border-bottom: 2px solid #3182ce; color: #2d3748; font-size: 18px;">2. Address Information</h3>

                <!-- Country input field defaults to "Egypt" -->
                <p style="margin-bottom: 15px;">
                    <label style="display: block; font-weight: 600; color: #4a5568; margin-bottom: 5px;">Country <span style="color:red;">*</span></label>
                    <input type="text" name="country" required value="<?php echo isset($_POST['country']) ? esc_attr($_POST['country']) : 'Egypt'; ?>" placeholder="e.g. Egypt, Saudi Arabia, United States" style="width: 100%; padding: 12px; border: 1px solid #cbd5e0; border-radius: 6px; box-sizing: border-box;">
                </p>

                <p style="margin-bottom: 15px;">
                    <label style="display: block; font-weight: 600; color: #4a5568; margin-bottom: 5px;">Street Address</label>
                    <textarea name="address" rows="2" style="width: 100%; padding: 12px; border: 1px solid #cbd5e0; border-radius: 6px; box-sizing: border-box;"></textarea>
                </p>

                <p style="margin-bottom: 15px;">
                    <label style="display: block; font-weight: 600; color: #4a5568; margin-bottom: 5px;">ZIP / Postal Code</label>
                    <input type="text" name="zip_code" style="width: 100%; padding: 12px; border: 1px solid #cbd5e0; border-radius: 6px; box-sizing: border-box;">
                </p>
            </div>

            <div style="flex: 1 1 320px; background: #f8fafc; padding: 25px; border-radius: 8px; border: 1px solid #e2e8f0; height: fit-content;">
                <h3 style="margin-top: 0; padding-bottom: 10px; border-bottom: 2px solid #3182ce; color: #2d3748; font-size: 18px;">3. Order Summary</h3>

                <p style="margin-bottom: 15px;">
                    <label style="display: block; font-weight: 600; color: #4a5568; margin-bottom: 5px;">Selected Plan</label>
                    <select name="package_id" id="package-select" onchange="window.location.href='?package_id='+this.value" style="width: 100%; padding: 12px; border: 1px solid #cbd5e0; border-radius: 6px; background: #ffffff; box-sizing: border-box;">
                        <?php foreach ($packages as $pkg): ?>
                            <option value="<?php echo $pkg->ID; ?>" <?php selected($pkg->ID, $package_id); ?>>
                                <?php echo esc_html($pkg->post_title); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </p>

                <div style="margin-bottom: 20px; background: #ffffff; padding: 15px; border: 1px solid #e2e8f0; border-radius: 6px;">
                    <label style="display: block; font-weight: 600; color: #4a5568; margin-bottom: 10px;">Billing Frequency</label>
                    <label style="display: block; margin-bottom: 8px; cursor: pointer;">
                        <input type="radio" name="billing_cycle" value="monthly" checked> Monthly: 
                        <?php 
                            if ($monthly_sale !== '' && $monthly_sale !== false) {
                                echo '<del style="color:#a00;">' . esc_html(MSFM_Settings::format_price($monthly_reg)) . '</del> <strong style="color:#2b6cb0;">' . esc_html(MSFM_Settings::format_price($monthly_sale)) . '</strong>';
                            } else {
                                echo '<strong>' . esc_html(MSFM_Settings::format_price($monthly_reg)) . '</strong>';
                            }
                        ?>
                    </label>
                    <label style="display: block; cursor: pointer;">
                        <input type="radio" name="billing_cycle" value="annual"> Annual: 
                        <?php 
                            if ($annual_sale !== '' && $annual_sale !== false) {
                                echo '<del style="color:#a00;">' . esc_html(MSFM_Settings::format_price($annual_reg)) . '</del> <strong style="color:#2b6cb0;">' . esc_html(MSFM_Settings::format_price($annual_sale)) . '</strong>';
                            } else {
                                echo '<strong>' . esc_html(MSFM_Settings::format_price($annual_reg)) . '</strong>';
                            }
                        ?>
                    </label>
                </div>

                <h3 style="margin-top: 25px; padding-bottom: 10px; border-bottom: 2px solid #3182ce; color: #2d3748; font-size: 18px;">4. Payment Method</h3>

                <div style="background: #ffffff; padding: 15px; border: 1px solid #e2e8f0; border-radius: 6px; margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 10px; cursor: pointer;">
                        <input type="radio" name="payment_method" value="fawaterak" checked> <strong>Fawaterak Online Gateway</strong>
                        <br><small style="color: #718096; margin-left: 20px; display: block;">Credit Cards, Fawry, Mobile Wallets.</small>
                    </label>
                    <?php if ($enable_cod == '1'): ?>
                        <hr style="margin: 10px 0; border: 0; border-top: 1px solid #edf2f7;">
                        <label style="display: block; cursor: pointer;">
                            <input type="radio" name="payment_method" value="cod"> <strong><?php echo esc_html($cod_label); ?></strong>
                            <br><small style="color: #718096; margin-left: 20px; display: block;">Complete order now and pay offline later.</small>
                        </label>
                    <?php endif; ?>
                </div>

                <button type="submit" id="qaff-submit-btn" style="width: 100%; background: #3182ce; color: #ffffff; font-size: 16px; font-weight: bold; padding: 14px; border: none; border-radius: 6px; cursor: pointer; transition: 0.2s;">
                    Complete Purchase
                </button>
            </div>

        </div>
    </form>
</div>

<script>
document.getElementById('qaff-checkout-form').addEventListener('submit', function() {
    var btn = document.getElementById('qaff-submit-btn');
    btn.innerHTML = 'Processing Order... ⏳';
    btn.style.opacity = '0.7';
    btn.style.pointerEvents = 'none';
});
</script>