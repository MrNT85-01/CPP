<div class="wrap cpp-settings-wrap">
    <h1><?php echo __('تنظیمات افزونه مدیریت قیمت‌ها','cpp-full'); ?></h1>

    <?php $active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'general'; ?>

    <h2 class="nav-tab-wrapper">
        <a href="?page=custom-prices-settings&tab=general" class="nav-tab <?php echo $active_tab == 'general' ? 'nav-tab-active' : ''; ?>"><?php _e('عمومی', 'cpp-full'); ?></a>
        <a href="?page=custom-prices-settings&tab=shortcodes" class="nav-tab <?php echo $active_tab == 'shortcodes' ? 'nav-tab-active' : ''; ?>"><?php _e('نمایش شورت‌کدها', 'cpp-full'); ?></a>
        <a href="?page=custom-prices-settings&tab=notifications" class="nav-tab <?php echo $active_tab == 'notifications' ? 'nav-tab-active' : ''; ?>"><?php _e('اعلان‌ها', 'cpp-full'); ?></a>
    </h2>

    <form method="post" action="options.php">
        <?php
        if ($active_tab == 'general') {
            settings_fields('cpp_general_settings_grp');
            do_settings_sections('cpp_general_settings_page');
        } elseif ($active_tab == 'shortcodes') {
            settings_fields('cpp_shortcode_settings_grp');
            do_settings_sections('cpp_shortcode_settings_page');
        } else { // Tab Notifications
            settings_fields('cpp_notification_settings_grp');
            ?>
            <h3><?php _e('تنظیمات ایمیل', 'cpp-full'); ?></h3>
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
                        <input type="email" name="cpp_admin_email" value="<?php echo esc_attr(get_option('cpp_admin_email', get_option('admin_email'))); ?>" class="regular-text" />
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
                        <?php
                            $content = get_option('cpp_email_body_template');
                            $editor_id = 'cpp_email_body_template';
                            wp_editor(wp_kses_post($content), $editor_id, ['textarea_name' => 'cpp_email_body_template', 'media_buttons' => false, 'textarea_rows' => 15]);
                        ?>
                        <p class="description"><?php _e('متغیرهای مجاز: {product_name}, {customer_name}, {phone}, {qty}, {note}','cpp-full'); ?></p>
                        <button type="button" id="cpp-load-email-template" class="button" style="margin-top:10px;"><?php _e('بارگذاری قالب پیش‌فرض زیبا', 'cpp-full'); ?></button>
                    </td>
                </tr>
            </table>

            <hr>
            <?php do_settings_sections('cpp_notification_settings_page'); // این بخش تست ایمیل را نمایش می‌دهد ?>
            
            <hr>
            <h3><?php _e('تنظیمات پیامک (SMS)','cpp-full'); ?></h3>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><?php _e('سرویس‌دهنده پیامک','cpp-full'); ?></th>
                    <td>
                        <select name="cpp_sms_service">
                            <option value="" <?php selected(get_option('cpp_sms_service'), ''); ?>><?php _e('غیرفعال', 'cpp-full'); ?></option>
                            <option value="melipayamak" <?php selected(get_option('cpp_sms_service'), 'melipayamak'); ?>>MeliPayamak</option>
                            <option value="kavenegar" <?php selected(get_option('cpp_sms_service'), 'kavenegar'); ?>>Kavenegar</option>
                            <option value="ippanel" <?php selected(get_option('cpp_sms_service'), 'ippanel'); ?>>IPPanel</option>
                        </select>
                    </td>
                </tr>
                 <tr valign="top">
                    <th scope="row"><?php _e('کلید API','cpp-full'); ?></th>
                    <td><input type="text" name="cpp_sms_api_key" value="<?php echo esc_attr( get_option('cpp_sms_api_key') ); ?>" class="regular-text"/></td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php _e('شماره فرستنده','cpp-full'); ?></th>
                    <td><input type="text" name="cpp_sms_sender" value="<?php echo esc_attr( get_option('cpp_sms_sender') ); ?>" class="regular-text"/></td>
                </tr>
                 <tr valign="top">
                    <th scope="row"><?php _e('شماره موبایل مدیر','cpp-full'); ?></th>
                    <td><input type="text" name="cpp_admin_phone" value="<?php echo esc_attr( get_option('cpp_admin_phone') ); ?>" class="regular-text"/></td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php _e('قالب متن پیامک','cpp-full'); ?></th>
                    <td>
                        <textarea name="cpp_sms_text_template" rows="5" class="large-text"><?php echo esc_textarea( get_option('cpp_sms_text_template', "سفارش جدید:\nمحصول: {product_name}\nمشتری: {customer_name}\nتلفن: {phone}") ); ?></textarea>
                         <p class="description"><?php _e('متغیرهای مجاز: {product_name}, {customer_name}, {phone}, {qty}, {note}','cpp-full'); ?></p>
                    </td>
                </tr>
            </table>
            <?php
        }
        submit_button();
        ?>
    </form>
</div>

<template id="cpp-email-template-html">
    <div style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; background-color: #f4f4f4; margin: 0; padding: 0;">
        <div style="max-width: 600px; margin: 20px auto; padding: 20px; background-color: #ffffff; border: 1px solid #dddddd; border-radius: 5px;">
            <div style="text-align: center; border-bottom: 1px solid #dddddd; padding-bottom: 10px; margin-bottom: 20px;">
                <h2 style="color: #0056b3;">اطلاع‌رسانی سفارش جدید</h2>
            </div>
            <div style="direction: rtl; text-align: right;">
                <p>سلام،</p>
                <p>یک سفارش جدید از طریق وب‌سایت ثبت شده است. جزئیات به شرح زیر است:</p>
                <table style="width: 100%; border-collapse: collapse; margin-top: 20px; margin-bottom: 20px;">
                    <tbody>
                        <tr style="background-color: #f9f9f9;">
                            <td style="padding: 10px; border: 1px solid #dddddd; font-weight: bold; width: 120px;">محصول:</td>
                            <td style="padding: 10px; border: 1px solid #dddddd;">{product_name}</td>
                        </tr>
                        <tr>
                            <td style="padding: 10px; border: 1px solid #dddddd; font-weight: bold;">نام مشتری:</td>
                            <td style="padding: 10px; border: 1px solid #dddddd;">{customer_name}</td>
                        </tr>
                        <tr style="background-color: #f9f9f9;">
                            <td style="padding: 10px; border: 1px solid #dddddd; font-weight: bold;">شماره تماس:</td>
                            <td style="padding: 10px; border: 1px solid #dddddd;">{phone}</td>
                        </tr>
                        <tr>
                            <td style="padding: 10px; border: 1px solid #dddddd; font-weight: bold;">مقدار/تعداد:</td>
                            <td style="padding: 10px; border: 1px solid #dddddd;">{qty}</td>
                        </tr>
                        <tr style="background-color: #f9f9f9;">
                            <td style="padding: 10px; border: 1px solid #dddddd; font-weight: bold;">توضیحات:</td>
                            <td style="padding: 10px; border: 1px solid #dddddd;">{note}</td>
                        </tr>
                    </tbody>
                </table>
                <p>لطفاً در اسرع وقت جهت پیگیری با مشتری تماس بگیرید.</p>
            </div>
            <div style="text-align: center; font-size: 12px; color: #777777; border-top: 1px solid #dddddd; padding-top: 10px; margin-top: 20px;">
                <p>این ایمیل به صورت خودکار ارسال شده است.</p>
            </div>
        </div>
    </div>
</template>
