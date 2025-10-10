<?php
if (!defined('ABSPATH')) exit;

/**
 * مدیریت تنظیمات افزونه
 */

// هوک برای ثبت تنظیمات در وردپرس
add_action('admin_init', 'cpp_register_settings');

function cpp_register_settings() {
    // گروه تنظیمات عمومی ایمیل
    register_setting('cpp_full_settings_grp', 'cpp_enable_email');
    register_setting('cpp_full_settings_grp', 'cpp_admin_email');
    register_setting('cpp_full_settings_grp', 'cpp_email_subject_template');
    register_setting('cpp_full_settings_grp', 'cpp_email_body_template');
    
    // --- تنظیمات جدید: غیرفعال کردن نمایش قیمت پایه ---
    register_setting('cpp_full_settings_grp', 'cpp_disable_base_price'); 
    // --------------------------------------------------

    // گروه تنظیمات پیامک
    register_setting('cpp_full_settings_grp', 'cpp_sms_service');
    register_setting('cpp_full_settings_grp', 'cpp_sms_api_key');
    register_setting('cpp_full_settings_grp', 'cpp_sms_sender');
    register_setting('cpp_full_settings_grp', 'cpp_admin_phone');
    register_setting('cpp_full_settings_grp', 'cpp_sms_text_template');
}

// ***توجه: تابع cpp_settings_page() از این فایل حذف شده است تا خطای "Cannot redeclare" رفع شود.*** // این تابع فقط در includes/cpp-admin.php تعریف می‌شود.