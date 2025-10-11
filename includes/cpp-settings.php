<?php
if (!defined('ABSPATH')) exit;

/**
 * مدیریت تنظیمات افزونه
 */

add_action('admin_init', 'cpp_register_settings');

function cpp_register_settings() {
    // گروه تنظیمات عمومی
    register_setting('cpp_full_settings_grp', 'cpp_enable_email');
    register_setting('cpp_full_settings_grp', 'cpp_admin_email');
    register_setting('cpp_full_settings_grp', 'cpp_email_subject_template');
    register_setting('cpp_full_settings_grp', 'cpp_email_body_template');
    register_setting('cpp_full_settings_grp', 'cpp_disable_base_price'); 
    
    register_setting('cpp_full_settings_grp', 'cpp_products_per_page');

    // --- شروع تغییر: افزودن تنظیم رنگ دکمه ---
    register_setting('cpp_full_settings_grp', 'cpp_grid_button_color');
    // --- پایان تغییر ---

    // گروه تنظیمات پیامک
    register_setting('cpp_full_settings_grp', 'cpp_sms_service');
    register_setting('cpp_full_settings_grp', 'cpp_sms_api_key');
    register_setting('cpp_full_settings_grp', 'cpp_sms_sender');
    register_setting('cpp_full_settings_grp', 'cpp_admin_phone');
    register_setting('cpp_full_settings_grp', 'cpp_sms_text_template');
}
