<?php
if (!defined('ABSPATH')) exit;

/**
 * مدیریت و ثبت تنظیمات افزونه
 */
add_action('admin_init', 'cpp_register_settings_and_fields');

function cpp_register_settings_and_fields() {
    // === ثبت تنظیمات ===
    register_setting('cpp_general_settings_grp', 'cpp_disable_base_price');
    register_setting('cpp_general_settings_grp', 'cpp_products_per_page');

    register_setting('cpp_shortcode_settings_grp', 'cpp_grid_with_date_show_image');
    register_setting('cpp_shortcode_settings_grp', 'cpp_grid_no_date_show_image');
    register_setting('cpp_shortcode_settings_grp', 'cpp_grid_with_date_button_color');
    register_setting('cpp_shortcode_settings_grp', 'cpp_grid_no_date_button_color');

    register_setting('cpp_notification_settings_grp', 'cpp_enable_email');
    register_setting('cpp_notification_settings_grp', 'cpp_admin_email');
    register_setting('cpp_notification_settings_grp', 'cpp_email_subject_template');
    register_setting('cpp_notification_settings_grp', 'cpp_email_body_template');
    register_setting('cpp_notification_settings_grp', 'cpp_sms_service');
    register_setting('cpp_notification_settings_grp', 'cpp_sms_api_key');
    register_setting('cpp_notification_settings_grp', 'cpp_sms_sender');
    register_setting('cpp_notification_settings_grp', 'cpp_admin_phone');
    register_setting('cpp_notification_settings_grp', 'cpp_sms_text_template');

    // === بخش‌های تنظیمات (برای هر تب) ===
    add_settings_section('cpp_general_section', __('تنظیمات عمومی', 'cpp-full'), null, 'cpp_general_settings_page');
    add_settings_section('cpp_shortcode_section', __('تنظیمات شورت‌کدها', 'cpp-full'), null, 'cpp_shortcode_settings_page');
    add_settings_section('cpp_notification_section', __('تنظیمات اعلان‌ها', 'cpp-full'), null, 'cpp_notification_settings_page');

    // === فیلدهای تنظیمات ===
    add_settings_field('cpp_disable_base_price', __('غیرفعال کردن قیمت پایه', 'cpp-full'), 'cpp_disable_base_price_callback', 'cpp_general_settings_page', 'cpp_general_section');
    add_settings_field('cpp_products_per_page', __('تعداد محصولات در هر بار بارگذاری', 'cpp-full'), 'cpp_products_per_page_callback', 'cpp_general_settings_page', 'cpp_general_section');

    add_settings_field('cpp_grid_with_date_show_image', __('نمایش تصویر (شورت‌کد با تاریخ)', 'cpp-full'), 'cpp_grid_with_date_show_image_callback', 'cpp_shortcode_settings_page', 'cpp_shortcode_section');
    add_settings_field('cpp_grid_no_date_show_image', __('نمایش تصویر (شورت‌کد بدون تاریخ)', 'cpp-full'), 'cpp_grid_no_date_show_image_callback', 'cpp_shortcode_settings_page', 'cpp_shortcode_section');
    add_settings_field('cpp_grid_with_date_button_color', __('رنگ دکمه (شورت‌کد با تاریخ)', 'cpp-full'), 'cpp_grid_with_date_button_color_callback', 'cpp_shortcode_settings_page', 'cpp_shortcode_section');
    add_settings_field('cpp_grid_no_date_button_color', __('رنگ دکمه (شورت‌کد بدون تاریخ)', 'cpp-full'), 'cpp_grid_no_date_button_color_callback', 'cpp_shortcode_settings_page', 'cpp_shortcode_section');
}

// === توابع Callback برای رندر کردن فیلدها ===
function cpp_disable_base_price_callback() {
    echo '<input type="checkbox" name="cpp_disable_base_price" value="1" ' . checked(1, get_option('cpp_disable_base_price'), false) . ' />';
    echo '<p class="description">' . __('با فعال کردن این گزینه، فیلد "قیمت پایه" در تمام بخش‌های افزونه مخفی می‌شود.', 'cpp-full') . '</p>';
}

function cpp_products_per_page_callback() {
    echo '<input type="number" name="cpp_products_per_page" value="' . esc_attr(get_option('cpp_products_per_page', 5)) . '" class="small-text" min="1" />';
    echo '<p class="description">' . __('این تعداد محصول در شورت‌کد گرید در ابتدا نمایش داده می‌شود.', 'cpp-full') . '</p>';
}

function cpp_grid_with_date_show_image_callback() {
    echo '<input type="checkbox" name="cpp_grid_with_date_show_image" value="1" ' . checked(1, get_option('cpp_grid_with_date_show_image', 1), false) . ' />';
    echo '<p class="description">' . __('تصویر محصول را کنار نام آن در شورت‌کد <code>[cpp_products_grid_view]</code> نمایش بده.', 'cpp-full') . '</p>';
}

function cpp_grid_no_date_show_image_callback() {
    echo '<input type="checkbox" name="cpp_grid_no_date_show_image" value="1" ' . checked(1, get_option('cpp_grid_no_date_show_image', 1), false) . ' />';
    echo '<p class="description">' . __('تصویر محصول را کنار نام آن در شورت‌کد <code>[cpp_products_grid_view_no_date]</code> نمایش بده.', 'cpp-full') . '</p>';
}

function cpp_grid_with_date_button_color_callback() {
    echo '<input type="text" name="cpp_grid_with_date_button_color" value="' . esc_attr(get_option('cpp_grid_with_date_button_color', '#ffc107')) . '" class="cpp-color-picker" />';
    echo '<p class="description">' . __('رنگ دکمه فعال در شورت‌کد <code>[cpp_products_grid_view]</code>.', 'cpp-full') . '</p>';
}

function cpp_grid_no_date_button_color_callback() {
    echo '<input type="text" name="cpp_grid_no_date_button_color" value="' . esc_attr(get_option('cpp_grid_no_date_button_color', '#0073aa')) . '" class="cpp-color-picker" />';
    echo '<p class="description">' . __('رنگ دکمه فعال در شورت‌کد <code>[cpp_products_grid_view_no_date]</code>.', 'cpp-full') . '</p>';
}
