<?php
if (!defined('ABSPATH')) exit;

$active_plan = $active_sub ? get_post($active_sub->package_id) : null;

$first_name  = $current_user->user_firstname;
$last_name   = $current_user->user_lastname;
$display_nam = trim($first_name . ' ' . $last_name);
if (empty($display_nam)) { $display_nam = $current_user->display_name ? $current_user->display_name : $current_user->user_email; }

$user_company = get_user_meta($current_user->ID, 'billing_company', true);
if (empty($user_company) && !empty($orders[0]->company)) { $user_company = $orders[0]->company; }

$user_phone = get_user_meta($current_user->ID, 'billing_phone', true);
if (empty($user_phone) && !empty($orders[0]->phone_number)) { $user_phone = $orders[0]->phone_number; }

$user_country = get_user_meta($current_user->ID, 'billing_country', true);
if (empty($user_country) && !empty($orders[0]->country)) { $user_country = $orders[0]->country; }
if (empty($user_country)) { $user_country = 'Egypt'; }

$btn_label   = get_option('msfm_portal_btn_label', 'Call To Action');
$btn_url     = get_option('msfm_portal_btn_url', '#');

$checkout_page_id  = get_option('msfm_checkout_page_id');
$checkout_base_url = $checkout_page_id ? get_permalink($checkout_page_id) : home_url('/checkout');
$integration_type  = get_option('msfm_fawaterak_integration_type', 'redirect');

$site_name   = get_bloginfo('name');
$site_url    = home_url('/');
$admin_email = get_option('admin_email');
$custom_logo_id = get_theme_mod('custom_logo');
$logo_url    = $custom_logo_id ? wp_get_attachment_image_src($custom_logo_id, 'full')[0] : '';
?>

<div class="smpl_pkg_mngr-portal-wrapper" style="max-width: 1000px; margin: 0 auto; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; color: #2d3748;">
    
    <?php if (isset($_GET['profile_updated'])): ?>
        <div class="smpl_pkg_mngr-alert smpl_pkg_mngr-alert-success" style="background: #c6f6d5; color: #22543d; padding: 12px 20px; border-radius: 8px; border: 1px solid #9ae6b4; margin-bottom: 20px; font-weight: 500;">
            Profile updated successfully.
        </div>
    <?php endif; ?>

    <div class="smpl_pkg_mngr-portal-header" style="display: flex; justify-content: space-between; align-items: center; background: #ffffff; padding: 20px 25px; border-radius: 10px; border: 1px solid #e2e8f0; margin-bottom: 25px; box-shadow: 0 1px 3px rgba(0,0,0,0.02);">
        <div class="smpl_pkg_mngr-user-info">
            <h2 class="smpl_pkg_mngr-header-title" style="margin: 0; color: #1a202c; font-size: 22px;">My Profile</h2>
            <p class="smpl_pkg_mngr-header-welcome" style="margin: 4px 0 0 0; color: #718096; font-size: 14px;">Welcome back, <strong><?php echo esc_html($display_nam); ?></strong></p>
        </div>
        <div class="smpl_pkg_mngr-header-actions" style="display: flex; gap: 10px; align-items: center;">
            <?php if (!empty($btn_url)): ?>
                <a href="<?php echo esc_url($btn_url); ?>" class="smpl_pkg_mngr-btn smpl_pkg_mngr-btn-cta" style="background: #3182ce; color: #ffffff; font-weight: bold; text-decoration: none; padding: 9px 16px; border-radius: 6px; font-size: 13px; display: inline-block;">
                    <?php echo esc_html($btn_label); ?>
                </a>
            <?php endif; ?>
            <a href="<?php echo esc_url(wp_logout_url()); ?>" class="smpl_pkg_mngr-btn smpl_pkg_mngr-btn-logout" style="background: #edf2f7; color: #4a5568; font-weight: 600; text-decoration: none; padding: 9px 16px; border-radius: 6px; font-size: 13px; display: inline-block;">
                Logout
            </a>
        </div>
    </div>

    <div class="smpl_pkg_mngr-portal-grid" style="display: flex; flex-wrap: wrap; gap: 25px; margin-bottom: 30px;">
        
        <div class="smpl_pkg_mngr-card smpl_pkg_mngr-card-profile" style="flex: 1 1 350px; background: #ffffff; padding: 25px; border-radius: 10px; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,0.02);">
            <div class="smpl_pkg_mngr-card-header" style="display: flex; justify-content: space-between; align-items: center; padding-bottom: 12px; border-bottom: 2px solid #edf2f7; margin-bottom: 15px;">
                <h3 class="smpl_pkg_mngr-card-title" style="margin: 0; color: #2d3748; font-size: 17px;">Profile Details</h3>
                <button type="button" class="smpl_pkg_mngr-btn-link" onclick="document.getElementById('qaff-edit-profile-modal').style.display='flex'" style="background: none; border: none; color: #3182ce; font-size: 13px; font-weight: bold; cursor: pointer; padding: 0;">Edit Profile</button>
            </div>
            
            <p class="smpl_pkg_mngr-profile-item" style="margin: 10px 0; font-size: 14px;"><strong>Full Name:</strong> <?php echo esc_html($display_nam); ?></p>
            <p class="smpl_pkg_mngr-profile-item" style="margin: 10px 0; font-size: 14px;"><strong>Company:</strong> <?php echo !empty($user_company) ? esc_html($user_company) : '<em>—</em>'; ?></p>
            <p class="smpl_pkg_mngr-profile-item" style="margin: 10px 0; font-size: 14px;"><strong>Email:</strong> <?php echo esc_html($current_user->user_email); ?></p>
            <p class="smpl_pkg_mngr-profile-item" style="margin: 10px 0; font-size: 14px;"><strong>Phone:</strong> <?php echo !empty($user_phone) ? esc_html($user_phone) : '<em>—</em>'; ?></p>
            <p class="smpl_pkg_mngr-profile-item" style="margin: 10px 0; font-size: 14px;"><strong>Country:</strong> <?php echo esc_html($user_country); ?></p>
        </div>

        <div class="smpl_pkg_mngr-card smpl_pkg_mngr-card-sub" style="flex: 1 1 350px; background: #ffffff; padding: 25px; border-radius: 10px; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,0.02);">
            <h3 class="smpl_pkg_mngr-card-title" style="margin-top: 0; padding-bottom: 12px; border-bottom: 2px solid #edf2f7; color: #2d3748; font-size: 17px; margin-bottom: 15px;">Active Subscription</h3>
            <?php if ($is_active && $active_plan): ?>
                <div class="smpl_pkg_mngr-sub-row" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                    <span class="smpl_pkg_mngr-plan-name" style="font-size: 20px; font-weight: bold; color: #2b6cb0;"><?php echo esc_html($active_plan->post_title); ?></span>
                    <span class="smpl_pkg_mngr-badge smpl_pkg_mngr-badge-active" style="background: #c6f6d5; color: #22543d; font-weight: bold; padding: 4px 10px; border-radius: 12px; font-size: 11px; text-transform: uppercase;">ACTIVE</span>
                </div>
                <p class="smpl_pkg_mngr-sub-item" style="margin: 8px 0; font-size: 14px;"><strong>Billing Cycle:</strong> <?php echo esc_html(strtoupper($active_sub->billing_cycle)); ?></p>
                <p class="smpl_pkg_mngr-sub-item" style="margin: 8px 0; font-size: 14px;"><strong>Amount Paid:</strong> <?php echo esc_html(MSFM_Settings::format_price($active_sub->amount)); ?></p>
                <p class="smpl_pkg_mngr-sub-item" style="margin: 8px 0; font-size: 14px;"><strong>Days Remaining:</strong> <strong style="color: #2b6cb0;"><?php echo esc_html($days_remaining); ?> Days</strong></p>
            <?php else: ?>
                <div class="smpl_pkg_mngr-empty-sub" style="text-align: center; padding: 15px 0;">
                    <span class="smpl_pkg_mngr-badge smpl_pkg_mngr-badge-none" style="background: #fed7d7; color: #9b2c2c; font-weight: bold; padding: 5px 12px; border-radius: 12px; font-size: 12px;">NO ACTIVE PLAN</span>
                    <p style="color: #718096; margin-top: 12px; font-size: 13px;">You currently do not have an active subscription.</p>
                </div>
            <?php endif; ?>
        </div>

    </div>

    <div class="smpl_pkg_mngr-history-wrapper" style="background: #ffffff; padding: 25px; border-radius: 10px; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,0.02);">
        <h3 class="smpl_pkg_mngr-history-title" style="margin-top: 0; padding-bottom: 12px; border-bottom: 2px solid #edf2f7; color: #2d3748; font-size: 18px;">Payment History</h3>
        
        <table class="smpl_pkg_mngr-table" style="width: 100%; border-collapse: collapse; margin-top: 15px; font-size: 14px; text-align: left;">
            <thead>
                <tr class="smpl_pkg_mngr-table-header" style="background: #f8fafc; border-bottom: 2px solid #e2e8f0; color: #4a5568;">
                    <th style="padding: 12px;">Order ID</th>
                    <th style="padding: 12px;">Plan Name</th>
                    <th style="padding: 12px;">Billing</th>
                    <th style="padding: 12px;">Amount</th>
                    <th style="padding: 12px;">Method</th>
                    <th style="padding: 12px;">Status</th>
                    <th style="padding: 12px;">Date</th>
                    <th style="padding: 12px; text-align: right;">Action / Invoice</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($orders)): ?>
                    <tr class="smpl_pkg_mngr-table-row"><td colspan="8" style="padding: 20px; text-align: center; color: #a0aec0;">No payment history found.</td></tr>
                <?php else: foreach ($orders as $order): 
                    $pkg = get_post($order->package_id);
                    $status_raw = strtolower($order->payment_status);
                    
                    $is_paid = in_array($status_raw, array('completed', 'paid'));
                    $is_pending = in_array($status_raw, array('pending', 'failed'));
                    
                    if ($integration_type === 'iframe') {
                        $pay_now_link = add_query_arg(array('pay_iframe' => '1', 'order_id' => $order->id), $checkout_base_url);
                    } else {
                        if (!empty($order->fawaterak_url) && filter_var($order->fawaterak_url, FILTER_VALIDATE_URL)) {
                            $pay_now_link = $order->fawaterak_url;
                        } else {
                            $pay_now_link = add_query_arg('package_id', $order->package_id, $checkout_base_url);
                        }
                    }
                ?>
                    <tr class="smpl_pkg_mngr-table-row" style="border-bottom: 1px solid #edf2f7;">
                        <td style="padding: 12px;"><strong>#<?php echo esc_html($order->id); ?></strong></td>
                        <td style="padding: 12px;"><?php echo esc_html($pkg ? $pkg->post_title : 'N/A'); ?></td>
                        <td style="padding: 12px;"><?php echo esc_html(strtoupper($order->billing_cycle)); ?></td>
                        <td style="padding: 12px;"><?php echo esc_html(MSFM_Settings::format_price($order->amount)); ?></td>
                        <td style="padding: 12px; text-transform: uppercase;"><?php echo esc_html($order->payment_method ? $order->payment_method : 'Fawaterak'); ?></td>
                        <td style="padding: 12px;">
                            <span class="smpl_pkg_mngr-status-text" style="font-weight: bold; text-transform: uppercase; font-size: 12px; <?php echo $is_paid ? 'color:#28a745;' : ($is_pending ? 'color:#e67e22;' : 'color:#dc3545;'); ?>">
                                <?php echo esc_html($status_raw); ?>
                            </span>
                        </td>
                        <td style="padding: 12px; color: #718096;"><?php echo esc_html(date('M d, Y', strtotime($order->created_at))); ?></td>
                        
                        <td style="padding: 12px; text-align: right;">
                            <?php if ($is_paid): ?>
                                <button type="button" class="smpl_pkg_mngr-btn smpl_pkg_mngr-btn-invoice" onclick="document.getElementById('qaff-invoice-modal-<?php echo $order->id; ?>').style.display='flex'" style="background: #edf2f7; color: #2d3748; border: 1px solid #cbd5e0; padding: 6px 12px; border-radius: 5px; font-size: 12px; font-weight: bold; cursor: pointer;">View Invoice</button>
                            <?php elseif ($is_pending): ?>
                                <a href="<?php echo esc_url($pay_now_link); ?>" class="smpl_pkg_mngr-btn smpl_pkg_mngr-btn-pay" style="background: #3182ce; color: #ffffff; text-decoration: none; padding: 6px 12px; border-radius: 5px; font-size: 12px; font-weight: bold; display: inline-block;">Pay Now &rarr;</a>
                            <?php else: ?>
                                <span style="color: #a0aec0;">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>

</div>

<!-- EDIT PROFILE MODAL POPUP -->
<div id="qaff-edit-profile-modal" class="smpl_pkg_mngr-modal-overlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 99999; justify-content: center; align-items: center; box-sizing: border-box; padding: 20px;">
    <div class="smpl_pkg_mngr-modal-content" style="background: #ffffff; width: 100%; max-width: 500px; padding: 30px; border-radius: 12px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); position: relative;">
        <h3 class="smpl_pkg_mngr-modal-title" style="margin-top: 0; color: #1a202c; font-size: 20px; border-bottom: 2px solid #edf2f7; padding-bottom: 10px; margin-bottom: 20px;">Edit Profile Details</h3>
        
        <form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="POST" class="smpl_pkg_mngr-form">
            <?php wp_nonce_field('msfm_profile_action', 'msfm_profile_nonce'); ?>
            <input type="hidden" name="action" value="msfm_update_user_profile">

            <div class="smpl_pkg_mngr-form-row" style="display: flex; gap: 10px; margin-bottom: 12px;">
                <div class="smpl_pkg_mngr-form-group" style="flex: 1;">
                    <label class="smpl_pkg_mngr-form-label" style="display: block; font-size: 12px; font-weight: bold; color: #4a5568; margin-bottom: 4px;">First Name</label>
                    <input type="text" name="first_name" value="<?php echo esc_attr($first_name); ?>" required class="smpl_pkg_mngr-input-field" style="width: 100%; padding: 10px; border: 1px solid #cbd5e0; border-radius: 6px; box-sizing: border-box;">
                </div>
                <div class="smpl_pkg_mngr-form-group" style="flex: 1;">
                    <label class="smpl_pkg_mngr-form-label" style="display: block; font-size: 12px; font-weight: bold; color: #4a5568; margin-bottom: 4px;">Last Name</label>
                    <input type="text" name="last_name" value="<?php echo esc_attr($last_name); ?>" required class="smpl_pkg_mngr-input-field" style="width: 100%; padding: 10px; border: 1px solid #cbd5e0; border-radius: 6px; box-sizing: border-box;">
                </div>
            </div>

            <div class="smpl_pkg_mngr-form-group" style="margin-bottom: 12px;">
                <label class="smpl_pkg_mngr-form-label" style="display: block; font-size: 12px; font-weight: bold; color: #4a5568; margin-bottom: 4px;">Company Name</label>
                <input type="text" name="company" value="<?php echo esc_attr($user_company); ?>" placeholder="e.g. Acme Corp" class="smpl_pkg_mngr-input-field" style="width: 100%; padding: 10px; border: 1px solid #cbd5e0; border-radius: 6px; box-sizing: border-box;">
            </div>

            <div class="smpl_pkg_mngr-form-group" style="margin-bottom: 12px;">
                <label class="smpl_pkg_mngr-form-label" style="display: block; font-size: 12px; font-weight: bold; color: #4a5568; margin-bottom: 4px;">Phone Number</label>
                <input type="text" name="phone_number" value="<?php echo esc_attr($user_phone); ?>" required class="smpl_pkg_mngr-input-field" style="width: 100%; padding: 10px; border: 1px solid #cbd5e0; border-radius: 6px; box-sizing: border-box;">
            </div>

            <div class="smpl_pkg_mngr-form-group" style="margin-bottom: 20px;">
                <label class="smpl_pkg_mngr-form-label" style="display: block; font-size: 12px; font-weight: bold; color: #4a5568; margin-bottom: 4px;">Country</label>
                <input type="text" name="country" value="<?php echo esc_attr($user_country); ?>" required class="smpl_pkg_mngr-input-field" style="width: 100%; padding: 10px; border: 1px solid #cbd5e0; border-radius: 6px; box-sizing: border-box;">
            </div>

            <div class="smpl_pkg_mngr-modal-actions" style="display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" onclick="document.getElementById('qaff-edit-profile-modal').style.display='none'" class="smpl_pkg_mngr-btn smpl_pkg_mngr-btn-cancel" style="background: #edf2f7; color: #4a5568; border: none; padding: 10px 16px; border-radius: 6px; cursor: pointer; font-weight: bold;">Cancel</button>
                <button type="submit" class="smpl_pkg_mngr-btn smpl_pkg_mngr-btn-submit" style="background: #3182ce; color: #ffffff; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; font-weight: bold;">Save Profile</button>
            </div>
        </form>
    </div>
</div>

<!-- INVOICE POPUP MODALS FOR COMPLETED ORDERS -->
<?php foreach ($orders as $order): if (in_array(strtolower($order->payment_status), array('completed', 'paid'))): 
    $pkg = get_post($order->package_id);
    $inv_num = 'INV-' . str_pad($order->id, 6, '0', STR_PAD_LEFT);
?>
<div id="qaff-invoice-modal-<?php echo $order->id; ?>" class="smpl_pkg_mngr-modal-overlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 99999; justify-content: center; align-items: center; box-sizing: border-box; padding: 20px;">
    
    <div id="qaff-printable-area-<?php echo $order->id; ?>" class="smpl_pkg_mngr-invoice-container" style="background: #ffffff; width: 100%; max-width: 700px; padding: 40px; border-radius: 12px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); position: relative; color: #2d3748; box-sizing: border-box; max-height: 90vh; overflow-y: auto;">
        
        <div class="smpl_pkg_mngr-invoice-header" style="display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #edf2f7; padding-bottom: 20px; margin-bottom: 25px;">
            <div class="smpl_pkg_mngr-invoice-brand">
                <?php if ($logo_url): ?>
                    <img src="<?php echo esc_url($logo_url); ?>" alt="<?php echo esc_attr($site_name); ?>" class="smpl_pkg_mngr-invoice-logo" style="max-height: 50px; margin-bottom: 8px;">
                <?php else: ?>
                    <h2 class="smpl_pkg_mngr-invoice-site-title" style="margin: 0; color: #1a202c; font-size: 24px; font-weight: 800;"><?php echo esc_html($site_name); ?></h2>
                <?php endif; ?>
                <div class="smpl_pkg_mngr-invoice-site-url" style="font-size: 12px; color: #718096; margin-top: 4px;"><?php echo esc_url($site_url); ?></div>
            </div>
            <div class="smpl_pkg_mngr-invoice-meta" style="text-align: right;">
                <h3 class="smpl_pkg_mngr-invoice-number" style="margin: 0; color: #3182ce; font-size: 20px; font-weight: bold;"><?php echo esc_html($inv_num); ?></h3>
                <div class="smpl_pkg_mngr-invoice-date" style="font-size: 13px; color: #718096; margin-top: 4px;">Date: <?php echo esc_html(date('F j, Y', strtotime($order->created_at))); ?></div>
                <span class="smpl_pkg_mngr-badge smpl_pkg_mngr-badge-paid" style="display: inline-block; background: #c6f6d5; color: #22543d; font-size: 11px; font-weight: bold; padding: 3px 8px; border-radius: 10px; margin-top: 6px; text-transform: uppercase;">PAID</span>
            </div>
        </div>

        <div class="smpl_pkg_mngr-invoice-parties" style="display: flex; gap: 20px; margin-bottom: 30px; font-size: 13px;">
            <div class="smpl_pkg_mngr-merchant-card" style="flex: 1; background: #f8fafc; padding: 15px; border-radius: 8px; border: 1px solid #e2e8f0;">
                <h4 style="margin: 0 0 8px 0; color: #4a5568; font-size: 13px; text-transform: uppercase;">Merchant Details</h4>
                <strong><?php echo esc_html($site_name); ?></strong><br>
                Email: <?php echo esc_html($admin_email); ?><br>
                Website: <?php echo esc_html($site_url); ?>
            </div>
            <div class="smpl_pkg_mngr-customer-card" style="flex: 1; background: #f8fafc; padding: 15px; border-radius: 8px; border: 1px solid #e2e8f0;">
                <h4 style="margin: 0 0 8px 0; color: #4a5568; font-size: 13px; text-transform: uppercase;">Billed To</h4>
                <strong><?php echo esc_html($order->full_name ? $order->full_name : $display_nam); ?></strong><br>
                <?php if (!empty($order->company)): ?>Company: <?php echo esc_html($order->company); ?><br><?php endif; ?>
                Email: <?php echo esc_html($current_user->user_email); ?><br>
                Phone: <?php echo esc_html($order->phone_number); ?><br>
                Country: <?php echo esc_html($order->country); ?>
            </div>
        </div>

        <table class="smpl_pkg_mngr-invoice-table" style="width: 100%; border-collapse: collapse; margin-bottom: 30px; font-size: 14px;">
            <thead>
                <tr style="background: #edf2f7; color: #2d3748;">
                    <th style="padding: 10px 12px; text-align: left;">Subscription Item</th>
                    <th style="padding: 10px 12px; text-align: center;">Billing Cycle</th>
                    <th style="padding: 10px 12px; text-align: center;">Qty</th>
                    <th style="padding: 10px 12px; text-align: right;">Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr style="border-bottom: 1px solid #e2e8f0;">
                    <td style="padding: 12px;"><strong><?php echo esc_html($pkg ? $pkg->post_title : 'Plan Subscription'); ?></strong></td>
                    <td style="padding: 12px; text-align: center; text-transform: capitalize;"><?php echo esc_html($order->billing_cycle); ?></td>
                    <td style="padding: 12px; text-align: center;">1</td>
                    <td style="padding: 12px; text-align: right; font-weight: bold;"><?php echo esc_html(MSFM_Settings::format_price($order->amount)); ?></td>
                </tr>
            </tbody>
        </table>

        <div class="smpl_pkg_mngr-invoice-total" style="text-align: right; margin-bottom: 30px; font-size: 16px;">
            <strong style="color: #4a5568;">Total Paid: </strong>
            <span style="font-size: 22px; font-weight: 800; color: #2b6cb0; margin-left: 10px;">
                <?php echo esc_html(MSFM_Settings::format_price($order->amount)); ?>
            </span>
        </div>

        <div class="smpl_pkg_mngr-modal-actions" style="display: flex; justify-content: space-between; border-top: 2px solid #edf2f7; padding-top: 20px;">
            <button type="button" onclick="document.getElementById('qaff-invoice-modal-<?php echo $order->id; ?>').style.display='none'" class="smpl_pkg_mngr-btn smpl_pkg_mngr-btn-cancel" style="background: #edf2f7; color: #4a5568; border: none; padding: 10px 18px; border-radius: 6px; cursor: pointer; font-weight: bold;">Close</button>
            <button type="button" onclick="window.print()" class="smpl_pkg_mngr-btn smpl_pkg_mngr-btn-print" style="background: #3182ce; color: #ffffff; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; font-weight: bold;">Print Invoice</button>
        </div>

    </div>
</div>
<?php endif; endforeach; ?>