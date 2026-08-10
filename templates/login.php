<?php
if (!defined('ABSPATH')) exit;
?>

<div class="msfm-login-wrapper" style="max-width: 450px; margin: 30px auto; padding: 30px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);">
    
    <?php if (isset($_GET['logged_out']) && $_GET['logged_out'] == '1'): ?>
        <div style="background: #c6f6d5; color: #22543d; padding: 12px 15px; border-radius: 6px; border: 1px solid #9ae6b4; margin-bottom: 20px; font-weight: 500; font-size: 14px;">
            ✓ You have been logged out successfully.
        </div>
    <?php endif; ?>

    <h3 style="margin-top: 0; text-align: center; color: #2d3748; font-size: 22px;">Account Login</h3>
    <p style="text-align: center; color: #718096; font-size: 14px; margin-bottom: 25px;">Enter your email address below to receive a instant passwordless Magic Link.</p>

    <form id="msfm-magic-form" method="POST">
        <p style="margin-bottom: 20px;">
            <label for="msfm_email" style="display: block; font-weight: 600; color: #4a5568; margin-bottom: 6px;">Email Address</label>
            <input type="email" id="msfm_email" name="user_email" required placeholder="your.name@example.com" style="width: 100%; padding: 12px; border: 1px solid #cbd5e0; border-radius: 6px; font-size: 15px; box-sizing: border-box;">
        </p>

        <p style="margin-bottom: 0;">
            <button type="submit" name="msfm_login_submit" style="width: 100%; background: #3182ce; color: #ffffff; font-weight: bold; font-size: 16px; padding: 12px; border: none; border-radius: 6px; cursor: pointer; transition: background 0.2s;">
                Send Magic Link
            </button>
        </p>
    </form>

    <div id="msfm-login-msg" style="margin-top: 15px; text-align: center; font-size: 14px;"></div>
</div>