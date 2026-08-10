<?php
if (!defined('ABSPATH')) exit;

$active_plan = $active_sub ? get_post($active_sub->package_id) : null;
$phone       = !empty($orders[0]->phone_number) ? $orders[0]->phone_number : 'N/A';
$btn_label   = get_option('msfm_portal_btn_label', 'Launch App Portal');
$btn_url     = get_option('msfm_portal_btn_url', 'https://qaff.xyz');
?>

<div class="qaff-portal-wrapper" style="max-width: 1000px; margin: 0 auto; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
    
    <?php if (isset($_GET['order_success'])): ?>
        <div style="background: #c6f6d5; color: #22543d; padding: 15px; border-radius: 8px; border: 1px solid #9ae6b4; margin-bottom: 25px; font-weight: 500;">
            🎉 Thank you! Your order #<?php echo esc_html($_GET['order_success']); ?> has been received and is being processed.
        </div>
    <?php endif; ?>

    <!-- Dashboard Header -->
    <div style="display: flex; justify-content: space-between; align-items: center; background: #ffffff; padding: 20px 25px; border-radius: 8px; border: 1px solid #e2e8f0; margin-bottom: 25px;">
        <div>
            <h2 style="margin: 0; color: #2d3748; font-size: 22px;">My Profile</h2>
            <p style="margin: 5px 0 0 0; color: #718096; font-size: 14px;">Welcome back, <strong><?php echo esc_html($current_user->display_name ? $current_user->display_name : $current_user->user_email); ?></strong></p>
        </div>
        <div>
            <?php if (!empty($btn_url)): ?>
                <a href="<?php echo esc_url($btn_url); ?>" target="_blank" style="background: #3182ce; color: #ffffff; font-weight: bold; text-decoration: none; padding: 10px 18px; border-radius: 6px; font-size: 14px; margin-right: 10px; display: inline-block;">
                    🚀 <?php echo esc_html($btn_label); ?>
                </a>
            <?php endif; ?>
            <a href="<?php echo esc_url(wp_logout_url()); ?>" style="background: #edf2f7; color: #4a5568; font-weight: 600; text-decoration: none; padding: 10px 18px; border-radius: 6px; font-size: 14px; display: inline-block;">
                Logout
            </a>
        </div>
    </div>

    <!-- Cards Layout Grid -->
    <div style="display: flex; flex-wrap: wrap; gap: 25px; margin-bottom: 30px;">
        
        <div style="flex: 1 1 350px; background: #ffffff; padding: 25px; border-radius: 8px; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
            <h3 style="margin-top: 0; padding-bottom: 12px; border-bottom: 2px solid #e2e8f0; color: #2d3748; font-size: 18px;">Profile Details</h3>
            <p style="margin: 12px 0;"><strong>Name:</strong> <?php echo esc_html($current_user->display_name ? $current_user->display_name : 'N/A'); ?></p>
            <p style="margin: 12px 0;"><strong>Email:</strong> <?php echo esc_html($current_user->user_email); ?></p>
            <p style="margin: 12px 0;"><strong>Phone:</strong> <?php echo esc_html($phone); ?></p>
            <p style="margin: 12px 0;"><strong>Member Since:</strong> <?php echo esc_html(date('F Y', strtotime($current_user->user_registered))); ?></p>
        </div>

        <div style="flex: 1 1 350px; background: #ffffff; padding: 25px; border-radius: 8px; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
            <h3 style="margin-top: 0; padding-bottom: 12px; border-bottom: 2px solid #e2e8f0; color: #2d3748; font-size: 18px;">Active Subscription</h3>
            <?php if ($is_active && $active_plan): ?>
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                    <span style="font-size: 20px; font-weight: bold; color: #2b6cb0;"><?php echo esc_html($active_plan->post_title); ?></span>
                    <span style="background: #c6f6d5; color: #22543d; font-weight: bold; padding: 4px 10px; border-radius: 12px; font-size: 12px;">ACTIVE</span>
                </div>
                <p style="margin: 8px 0;"><strong>Billing Cycle:</strong> <?php echo esc_html(strtoupper($active_sub->billing_cycle)); ?></p>
                <p style="margin: 8px 0;"><strong>Price:</strong> <?php echo esc_html(MSFM_Settings::format_price($active_sub->amount)); ?></p>
                <p style="margin: 8px 0;"><strong>Remaining Time:</strong> <strong style="color: #2b6cb0;"><?php echo esc_html($days_remaining); ?> Days</strong></p>
            <?php else: ?>
                <div style="text-align: center; padding: 20px 0;">
                    <span style="background: #fed7d7; color: #9b2c2c; font-weight: bold; padding: 6px 14px; border-radius: 12px; font-size: 13px;">NO ACTIVE PLAN</span>
                    <p style="color: #718096; margin-top: 15px; font-size: 14px;">You do not have an active subscription.</p>
                </div>
            <?php endif; ?>
        </div>

    </div>

    <!-- Payment & Invoice History Table -->
    <div style="background: #ffffff; padding: 25px; border-radius: 8px; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
        <h3 style="margin-top: 0; padding-bottom: 12px; border-bottom: 2px solid #e2e8f0; color: #2d3748; font-size: 18px;">Payment & Invoice History</h3>
        <table style="width: 100%; border-collapse: collapse; margin-top: 15px; font-size: 14px; text-align: left;">
            <thead>
                <tr style="background: #f7fafc; border-bottom: 2px solid #e2e8f0;">
                    <th style="padding: 12px;">Order ID</th>
                    <th style="padding: 12px;">Plan Name</th>
                    <th style="padding: 12px;">Billing</th>
                    <th style="padding: 12px;">Amount</th>
                    <th style="padding: 12px;">Method</th>
                    <th style="padding: 12px;">Status</th>
                    <th style="padding: 12px;">Date</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($orders)): ?>
                    <tr><td colspan="7" style="padding: 20px; text-align: center; color: #a0aec0;">No records found.</td></tr>
                <?php else: foreach ($orders as $order): 
                    $pkg = get_post($order->package_id);
                ?>
                    <tr style="border-bottom: 1px solid #edf2f7;">
                        <td style="padding: 12px;"><strong>#<?php echo esc_html($order->id); ?></strong></td>
                        <td style="padding: 12px;"><?php echo esc_html($pkg ? $pkg->post_title : 'N/A'); ?></td>
                        <td style="padding: 12px;"><?php echo esc_html(strtoupper($order->billing_cycle)); ?></td>
                        <td style="padding: 12px;"><?php echo esc_html(MSFM_Settings::format_price($order->amount)); ?></td>
                        <td style="padding: 12px;"><?php echo esc_html(strtoupper($order->payment_method ? $order->payment_method : 'Fawaterak')); ?></td>
                        <td style="padding: 12px;">
                            <strong><?php echo esc_html(strtoupper($order->payment_status)); ?></strong>
                        </td>
                        <td style="padding: 12px; color: #718096;"><?php echo esc_html(date('M d, Y', strtotime($order->created_at))); ?></td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>

</div>