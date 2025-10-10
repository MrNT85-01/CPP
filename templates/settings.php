<?php
if (!defined('ABSPATH')) exit;
?>
<div class="wrap">
    <h1><?php echo __('تنظیمات افزونه مدیریت قیمت‌ها','cpp-full'); ?></h1>
    <form method="post" action="options.php">
        <?php settings_fields('cpp_full_settings_grp'); ?>
        <?php do_settings_sections('cpp_full_settings_grp'); ?>
        
        <h2><?php _e('تنظیمات اعلان‌ها','cpp-full'); ?></h2>
        <table class="form-table">
            <tr valign="top">
                <th scope="row"><?php _e('فعال‌سازی ارسال ایمیل','cpp-full'); ?></th>
                <td>
                    <input type="checkbox" name="cpp_enable_email" value="1" <?php checked(get_option('cpp_enable_email'), 1); ?> />
                    <p class="description"><?php _e('ارسال ایمیل برای سفارشات جدید را فعال می‌کند.','cpp-full'); ?></p>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row"><?php _e('ایمیل مدیر','cpp-full'); ?></th>
                <td>
                    <input type="email" name="cpp_admin_email" value="<?php echo esc_attr(get_option('cpp_admin_email')); ?>" class="regular-text" />
                    <p class="description"><?php _e('ایمیل گیرنده سفارشات و نوتیفیکیشن‌ها.','cpp-full'); ?></p>
                </td>
            </tr>

            <tr valign="top">
                <th scope="row"><?php _e('عنوان پیش فرض ایمیل سفارش جدید','cpp-full'); ?></th>
                <td><input type="text" name="cpp_email_subject_template" value="<?php echo esc_attr(get_option('cpp_email_subject_template', 'سفارش جدید: {product_name}')); ?>" class="regular-text" /></td>
            </tr>
            <tr valign="top">
                <th scope="row"><?php _e('بدنه پیش فرض ایمیل سفارش جدید','cpp-full'); ?></th>
                <td>
                    <textarea name="cpp_email_body_template" rows="5" cols="50" class="large-text"><?php echo esc_textarea(get_option('cpp_email_body_template')); ?></textarea>
                    <p class="description"><?php _e('متغیرهای مجاز: {product_name}, {customer_name}, {phone}, {qty}, {note}','cpp-full'); ?></p>
                </td>
            </tr>
            
            <tr valign="top">
                <th scope="row"><?php _e('غیرفعال کردن نمایش قیمت پایه', 'cpp-full'); ?></th>
                <td>
                    <input type="checkbox" name="cpp_disable_base_price" value="1" <?php checked(get_option('cpp_disable_base_price'), 1); ?> />
                    <p class="description"><?php _e('در صورت فعال‌سازی، ستون "قیمت پایه/استاندارد" در لیست محصولات فرانت‌اند و شورت‌کدها نمایش داده نخواهد شد. فقط "بازه قیمت" نمایش داده می‌شود.', 'cpp-full'); ?></p>
                </td>
            </tr>
            </table>
        
        <h2><?php _e('تنظیمات پیامک (SMS)','cpp-full'); ?></h2>
        <table class="form-table">
            <tr valign="top">
                <th scope="row"><?php _e('سرویس دهنده پیامک','cpp-full'); ?></th>
                <td>
                    <select name="cpp_sms_service">
                        <option value=""><?php _e('-- انتخاب کنید --','cpp-full'); ?></option>
                        <option value="melipayamak" <?php selected(get_option('cpp_sms_service'), 'melipayamak'); ?>>ملی پیامک</option>
                        <option value="kavenegar" <?php selected(get_option('cpp_sms_service'), 'kavenegar'); ?>>کاوه نگار</option>
                        <option value="ippanel" <?php selected(get_option('cpp_sms_service'), 'ippanel'); ?>>IP Panel</option>
                    </select>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row"><?php _e('API Key','cpp-full'); ?></th>
                <td><input type="text" name="cpp_sms_api_key" value="<?php echo esc_attr(get_option('cpp_sms_api_key')); ?>" class="regular-text" /></td>
            </tr>
            <tr valign="top">
                <th scope="row"><?php _e('شماره ارسال کننده','cpp-full'); ?></th>
                <td><input type="text" name="cpp_sms_sender" value="<?php echo esc_attr(get_option('cpp_sms_sender')); ?>" class="regular-text" /></td>
            </tr>
            <tr valign="top">
                <th scope="row"><?php _e('شماره مدیر (دریافت کننده)','cpp-full'); ?></th>
                <td><input type="text" name="cpp_admin_phone" value="<?php echo esc_attr(get_option('cpp_admin_phone')); ?>" class="regular-text" /></td>
            </tr>

            <tr valign="top">
                <th scope="row"><?php _e('متن پیش فرض پیامک سفارش جدید','cpp-full'); ?></th>
                <td>
                    <textarea name="cpp_sms_text_template" rows="3" cols="50" class="large-text"><?php echo esc_textarea(get_option('cpp_sms_text_template', "سفارش جدید: {product_name} - {customer_name} - {phone}")); ?></textarea>
                    <p class="description"><?php _e('متغیرهای مجاز: {product_name}, {customer_name}, {phone}, {qty}, {note}','cpp-full'); ?></p>
                </td>
            </tr>

        </table>
        
        <?php submit_button(); ?>
    </form>
</div>