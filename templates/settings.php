<?php
if (!defined('ABSPATH')) exit;
?>
<div class="wrap">
    <h1><?php echo __('تنظیمات افزونه مدیریت قیمت‌ها','cpp-full'); ?></h1>
    <form method="post" action="options.php">
        <?php settings_fields('cpp_full_settings_grp'); ?>
        <?php do_settings_sections('cpp_full_settings_grp'); ?>
        
        <h2><?php _e('تنظیمات عمومی و اعلان‌ها','cpp-full'); ?></h2>
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
                    <p class="description"><?php _e('ایمیل گیرنده سفارشات. در صورت خالی بودن، از ایمیل مدیر سایت استفاده می‌شود.','cpp-full'); ?></p>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row"><?php _e('عنوان ایمیل سفارش','cpp-full'); ?></th>
                <td><input type="text" name="cpp_email_subject_template" value="<?php echo esc_attr(get_option('cpp_email_subject_template', 'سفارش جدید: {product_name}')); ?>" class="regular-text" /></td>
            </tr>
            <tr valign="top">
                <th scope="row"><?php _e('قالب ایمیل سفارش','cpp-full'); ?></th>
                <td>
                    <textarea name="cpp_email_body_template" rows="5" cols="50" class="large-text"><?php echo esc_textarea(get_option('cpp_email_body_template')); ?></textarea>
                    <p class="description"><?php _e('متغیرهای مجاز: {product_name}, {customer_name}, {phone}, {qty}, {note}','cpp-full'); ?></p>
                </td>
            </tr>
        </table>

        <h2><?php _e('تنظیمات نمایش محصولات (شورت‌کدها)','cpp-full'); ?></h2>
        <table class="form-table">
             <tr valign="top">
                <th scope="row"><?php _e('غیرفعال کردن قیمت پایه', 'cpp-full'); ?></th>
                <td>
                    <input type="checkbox" name="cpp_disable_base_price" value="1" <?php checked(get_option('cpp_disable_base_price'), 1); ?> />
                    <p class="description"><?php _e('با فعال کردن این گزینه، فیلد "قیمت پایه" در تمام بخش‌های افزونه (شامل مدیریت و شورت‌کدها) مخفی می‌شود.', 'cpp-full'); ?></p>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row"><?php _e('تعداد محصولات در هر بار بارگذاری', 'cpp-full'); ?></th>
                <td>
                    <input type="number" name="cpp_products_per_page" value="<?php echo esc_attr(get_option('cpp_products_per_page', 5)); ?>" class="small-text" min="1" />
                    <p class="description"><?php _e('این تعداد محصول در شورت‌کد گرید در ابتدا نمایش داده شده و با هر بار کلیک روی "مشاهده بیشتر" همین تعداد اضافه می‌شود.', 'cpp-full'); ?></p>
                </td>
            </tr>
            </table>
        
        <h2><?php _e('تنظیمات پیامک (SMS)','cpp-full'); ?></h2>
        <?php submit_button(); ?>
    </form>
</div>
