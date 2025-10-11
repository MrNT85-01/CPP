<div class="wrap cpp-settings-wrap">
    <h1><?php echo __('تنظیمات افزونه مدیریت قیمت‌ها','cpp-full'); ?></h1>

    <?php
    $active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'general';
    ?>

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
        } else {
            settings_fields('cpp_notification_settings_grp');
            // چون فیلدهای اعلان زیاد هستند، آن‌ها را مستقیما اینجا می‌نویسیم
            ?>
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
                        <textarea name="cpp_email_body_template" rows="5" cols="50" class="large-text"><?php echo esc_textarea(get_option('cpp_email_body_template')); ?></textarea>
                        <p class="description"><?php _e('متغیرهای مجاز: {product_name}, {customer_name}, {phone}, {qty}, {note}','cpp-full'); ?></p>
                    </td>
                </tr>
                 <tr><td colspan="2"><hr></td></tr>
                 <tr valign="top">
                    <th scope="row" colspan="2"><h3><?php _e('تنظیمات پیامک (SMS)','cpp-full'); ?></h3></th>
                </tr>
                </table>
            <?php
        }
        submit_button();
        ?>
    </form>
</div>
