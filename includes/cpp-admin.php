<?php
if (!defined('ABSPATH')) exit;

/**
 * مدیریت بخش پیشخوان وردپرس افزونه
 * شامل ثبت منوها، اسکریپت‌ها، استایل‌ها و مدیریت ایجکس
 */

// ۱. ثبت و بارگذاری اسکریپت‌ها و استایل‌های بخش مدیریت
add_action('admin_enqueue_scripts', 'cpp_admin_assets');
function cpp_admin_assets($hook) {
    // بررسی اینکه آیا در یکی از صفحات افزونه هستیم یا خیر
    $is_cpp_page = strpos($hook, 'custom-prices') !== false;

    if (!$is_cpp_page && $hook !== 'post.php' && $hook !== 'post-new.php' && !isset($_GET['elementor-preview'])) return;

    // کتابخانه‌های عمومی مورد نیاز
    wp_enqueue_media();
    wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js', [], null, true);

    // اسکریپت اصلی مدیریت
    wp_enqueue_script('cpp-admin-js', CPP_ASSETS_URL . 'js/admin.js', ['jquery', 'wp-i18n', 'chart-js'], CPP_VERSION, true);

    // افزودن اسکریپت و استایل انتخاب‌گر رنگ فقط برای صفحه تنظیمات
    if ($hook === 'toplevel_page_custom-prices-products' || $hook === 'custom-prices_page_custom-prices-settings') {
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('cpp-color-picker-init', CPP_ASSETS_URL . 'js/admin-color-picker.js', ['wp-color-picker', 'jquery'], CPP_VERSION, true);
    }

    // آماده‌سازی متغیرها برای ارسال به جاوا اسکریپت
    $order_statuses = [
        'new_order'     => __('سفارش جدید', 'cpp-full'),
        'negotiating'   => __('در حال مذاکره', 'cpp-full'),
        'cancelled'     => __('کنسل شد', 'cpp-full'),
        'completed'     => __('خرید انجام شد', 'cpp-full'),
    ];

    wp_localize_script('cpp-admin-js', 'cpp_admin_vars', [
        'ajax_url'      => admin_url('admin-ajax.php'),
        'nonce'         => wp_create_nonce('cpp_admin_nonce'),
        'edit_url_base' => admin_url('admin.php?page=custom-prices-product-edit&id='),
        'order_statuses' => $order_statuses,
    ]);

    // استایل اصلی بخش مدیریت
    wp_enqueue_style('cpp-admin-css', CPP_ASSETS_URL . 'css/admin.css', [], CPP_VERSION);

    // استایل‌های درون‌خطی برای مودال (پاپ‌آپ)
    $custom_css = "
        .cpp-modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.7); z-index: 10000; display: none; overflow-y: auto; }
        .cpp-modal-container { background: #fff; margin: 5% auto; padding: 20px; border-radius: 5px; width: 90%; max-width: 800px; position: relative; }
        .cpp-close-modal { position: absolute; top: 10px; left: 10px; font-size: 20px; cursor: pointer; color: #ccc; }
        .cpp-modal-container.loading { min-height: 200px; display: flex; align-items: center; justify-content: center; }
    ";
    wp_add_inline_style('cpp-admin-css', $custom_css);

    // اسکریپت درون‌خطی برای مدیریت آپلودر رسانه وردپرس
    wp_add_inline_script('cpp-admin-js', '
        window.cpp_init_media_uploader = function() {
            var mediaUploader;
            jQuery(document).off("click", ".cpp-upload-btn").on("click", ".cpp-upload-btn", function(e) {
                e.preventDefault();
                var button = jQuery(this);
                var inputId = button.data("input-id") || button.siblings("input[type=\"text\"]").attr("id");
                var input_field = jQuery("#" + inputId);
                var preview_img_container = input_field.closest(".cpp-image-uploader-wrapper").find(".cpp-image-preview");

                if (mediaUploader) { mediaUploader.open(); return; }
                mediaUploader = wp.media({ title: "انتخاب یا آپلود تصویر", button: { text: "استفاده از این تصویر" }, multiple: false });
                mediaUploader.on("select", function() {
                    var attachment = mediaUploader.state().get("selection").first().toJSON();
                    input_field.val(attachment.url);
                    preview_img_container.html("<img src=\"" + attachment.url + "\" style=\"max-width: 100px; height: auto; margin-top: 10px;\">");
                });
                mediaUploader.open();
            });
        };
        // فراخوانی اولیه برای صفحاتی که فرم در آن‌ها مستقیم لود می‌شود
        jQuery(document).ready(function(){
            if(typeof window.cpp_init_media_uploader === "function"){
                window.cpp_init_media_uploader();
            }
        });
    ', 'after');
}

// ۲. ثبت منوهای افزونه در پیشخوان وردپرس
add_action('admin_menu', 'cpp_admin_menu');
function cpp_admin_menu() {
    // خواندن سطح دسترسی از تنظیمات با مقدار پیش‌فرض 'manage_options' (برای مدیرکل)
    $capability = get_option('cpp_admin_capability', 'manage_options');

    add_menu_page( __('مدیریت قیمت‌ها و سفارشات', 'cpp-full'), __('مدیریت قیمت', 'cpp-full'), $capability, 'custom-prices-products', 'cpp_products_page', 'dashicons-tag', 30 );
    add_submenu_page('custom-prices-products', __('دسته‌بندی‌ها', 'cpp-full'), __('دسته‌بندی‌ها', 'cpp-full'), $capability, 'custom-prices-categories', 'cpp_categories_page');
    add_submenu_page('custom-prices-products', __('سفارشات', 'cpp-full'), __('سفارشات مشتری', 'cpp-full'), $capability, 'custom-prices-orders', 'cpp_orders_page');
    add_submenu_page('custom-prices-products', __('شورت‌کدها', 'cpp-full'), __('شورت‌کدها', 'cpp-full'), $capability, 'custom-prices-shortcodes', 'cpp_shortcodes_page');
    add_submenu_page('custom-prices-products', __('تنظیمات', 'cpp-full'), __('تنظیمات', 'cpp-full'), $capability, 'custom-prices-settings', 'cpp_settings_page');
    // صفحه مخفی برای ویرایش محصول
    add_submenu_page( null, __('ویرایش محصول', 'cpp-full'), __('ویرایش محصول', 'cpp-full'), $capability, 'custom-prices-product-edit', 'cpp_product_edit_page' );
}

// ۳. افزودن نشانگر عددی تعداد سفارشات جدید به منو
add_action('admin_menu', 'cpp_add_order_count_bubble', 99);
function cpp_add_order_count_bubble() {
    global $wpdb, $menu;
    
    $capability = get_option('cpp_admin_capability', 'manage_options');
    if (!current_user_can($capability)) {
        return;
    }

    $count = $wpdb->get_var("SELECT COUNT(id) FROM " . CPP_DB_ORDERS . " WHERE status = 'new_order'");
    
    if ($count > 0) {
        foreach ($menu as $key => $value) {
            if ($menu[$key][2] == 'custom-prices-products') {
                $menu[$key][0] .= ' <span class="update-plugins count-' . $count . '"><span class="plugin-count">' . $count . '</span></span>';
                return;
            }
        }
    }
}

// ۴. توابع Callback برای نمایش محتوای هر صفحه از منو
function cpp_products_page() { 
    include CPP_TEMPLATES_DIR . 'products.php'; 
    echo '<div id="cpp-edit-modal" class="cpp-modal-overlay" style="display: none;"><div class="cpp-modal-container"><span class="cpp-close-modal">×</span><div class="cpp-edit-modal-content"></div></div></div>';
}
function cpp_categories_page() { 
    include CPP_TEMPLATES_DIR . 'categories.php'; 
    echo '<div id="cpp-edit-modal" class="cpp-modal-overlay" style="display: none;"><div class="cpp-modal-container"><span class="cpp-close-modal">×</span><div class="cpp-edit-modal-content"></div></div></div>';
}
function cpp_orders_page() { include CPP_TEMPLATES_DIR . 'orders.php'; }
function cpp_settings_page() { include CPP_TEMPLATES_DIR . 'settings.php'; }
function cpp_shortcodes_page() { include CPP_TEMPLATES_DIR . 'shortcodes.php'; }
function cpp_product_edit_page() { include CPP_TEMPLATES_DIR . 'product-edit.php'; }

// ۵. مدیریت فرم‌های POST (افزودن و حذف)
add_action('admin_init', 'cpp_handle_admin_actions');
function cpp_handle_admin_actions() {
    global $wpdb;

    // افزودن دسته‌بندی جدید
    if (isset($_POST['cpp_add_category'])) {
        if (!isset($_POST['cpp_add_cat_nonce']) || !wp_verify_nonce($_POST['cpp_add_cat_nonce'], 'cpp_add_cat_action')) { wp_die(__('بررسی امنیتی ناموفق بود.', 'cpp-full')); }
        $name = sanitize_text_field($_POST['name']);
        $slug = sanitize_title($_POST['slug']);
        $image_url = esc_url_raw($_POST['image_url']);
        if (empty($slug)) $slug = sanitize_title($name);
        $inserted = $wpdb->insert(CPP_DB_CATEGORIES, array('name' => $name,'slug' => $slug,'image_url' => $image_url));
        $redirect_url = add_query_arg('cpp_message', $inserted ? 'category_added' : 'category_add_failed', admin_url('admin.php?page=custom-prices-categories'));
        wp_redirect($redirect_url); exit;
    }

    // افزودن محصول جدید
    if (isset($_POST['cpp_add_product'])) {
        if (!isset($_POST['cpp_add_product_nonce']) || !wp_verify_nonce($_POST['cpp_add_product_nonce'], 'cpp_add_product_action')) { wp_die(__('بررسی امنیتی ناموفق بود.', 'cpp-full')); }
        $data = ['cat_id' => intval($_POST['cat_id']),'name' => sanitize_text_field($_POST['name']),'price' => sanitize_text_field($_POST['price']),'min_price' => sanitize_text_field($_POST['min_price']),'max_price' => sanitize_text_field($_POST['max_price']),'product_type' => sanitize_text_field($_POST['product_type']),'unit' => sanitize_text_field($_POST['unit']),'load_location' => sanitize_text_field($_POST['load_location']),'is_active' => intval($_POST['is_active']),'description' => sanitize_textarea_field($_POST['description']),'image_url' => esc_url_raw($_POST['image_url']),'last_updated_at' => current_time('mysql')];
        $inserted = $wpdb->insert(CPP_DB_PRODUCTS, $data);
        if ($inserted) { $product_id = $wpdb->insert_id; CPP_Core::save_price_history($product_id, ['price' => $data['price']]); }
        $redirect_url = add_query_arg('cpp_message', $inserted ? 'product_added' : 'product_add_failed', admin_url('admin.php?page=custom-prices-products'));
        wp_redirect($redirect_url); exit;
    }

    // حذف آیتم‌ها (محصول، دسته‌بندی، سفارش)
    if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
        $id = intval($_GET['id']);
        $page = isset($_GET['page']) ? sanitize_key($_GET['page']) : '';
        $redirect_url = admin_url('admin.php?page=' . $page);
        $deleted = false;
        $db_table = '';
        $message_success = '';
        $message_failed = '';

        if ($page == 'custom-prices-categories' && wp_verify_nonce($_GET['_wpnonce'], 'cpp_delete_cat_' . $id)) { $db_table = CPP_DB_CATEGORIES; $message_success = 'category_deleted'; $message_failed = 'category_delete_failed';} 
        elseif ($page == 'custom-prices-products' && wp_verify_nonce($_GET['_wpnonce'], 'cpp_delete_product_' . $id)) { $db_table = CPP_DB_PRODUCTS; $message_success = 'product_deleted'; $message_failed = 'product_delete_failed';} 
        elseif ($page == 'custom-prices-orders' && wp_verify_nonce($_GET['_wpnonce'], 'cpp_delete_order_' . $id)) { $db_table = CPP_DB_ORDERS; $message_success = 'order_deleted'; $message_failed = 'order_delete_failed';}
        
        if ($db_table) { $deleted = $wpdb->delete($db_table, array('id' => $id)); }
        
        $redirect_url = add_query_arg('cpp_message', $deleted ? $message_success : $message_failed, $redirect_url);
        wp_redirect($redirect_url); exit;
    }
}

// ۶. توابع AJAX برای محصولات
add_action('wp_ajax_cpp_fetch_product_edit_form', 'cpp_fetch_product_edit_form');
function cpp_fetch_product_edit_form() {
    if (!isset($_GET['security']) || !wp_verify_nonce($_GET['security'], 'cpp_admin_nonce')) {
        wp_send_json_error(__('بررسی امنیتی ناموفق بود.', 'cpp-full'));
    }
    
    $_GET['id'] = isset($_GET['id']) ? intval($_GET['id']) : 0;
    if (!$_GET['id']) { wp_send_json_error(__('شناسه محصول نامعتبر است.', 'cpp-full')); }

    ob_start();
    include CPP_TEMPLATES_DIR . 'product-edit.php';
    $html = ob_get_clean();
    wp_send_json_success(['html' => $html]);
}

add_action('wp_ajax_cpp_handle_edit_product_ajax', 'cpp_handle_edit_product_ajax');
function cpp_handle_edit_product_ajax() {
    global $wpdb;
    if (!isset($_POST['cpp_edit_product_nonce']) || !wp_verify_nonce($_POST['cpp_edit_product_nonce'], 'cpp_edit_product_action')) { wp_send_json_error(__('بررسی امنیتی ناموفق بود.', 'cpp-full')); }
    
    $product_id = intval($_POST['product_id']);
    if (!$product_id) { wp_send_json_error(__('شناسه محصول نامعتبر است.', 'cpp-full')); }
    
    $old_data = $wpdb->get_row($wpdb->prepare("SELECT price, min_price, max_price FROM " . CPP_DB_PRODUCTS . " WHERE id = %d", $product_id));
    $data = [
        'cat_id' => intval($_POST['cat_id']), 'name' => sanitize_text_field($_POST['name']), 'price' => sanitize_text_field($_POST['price']),
        'min_price' => sanitize_text_field($_POST['min_price']), 'max_price' => sanitize_text_field($_POST['max_price']), 'product_type' => sanitize_text_field($_POST['product_type']),
        'unit' => sanitize_text_field($_POST['unit']), 'load_location' => sanitize_text_field($_POST['load_location']), 'is_active' => intval($_POST['is_active']),
        'description' => wp_kses_post($_POST['description']), 'image_url' => esc_url_raw($_POST['image_url']), 'last_updated_at' => current_time('mysql')
    ];
    
    $updated = $wpdb->update(CPP_DB_PRODUCTS, $data, ['id' => $product_id]);
    
    if ($updated !== false) {
        $price_data_changed = [];
        if($old_data->price != $data['price']) $price_data_changed['price'] = $data['price'];
        if($old_data->min_price != $data['min_price']) $price_data_changed['min_price'] = $data['min_price'];
        if($old_data->max_price != $data['max_price']) $price_data_changed['max_price'] = $data['max_price'];

        if(!empty($price_data_changed)){
            CPP_Core::save_price_history($product_id, $price_data_changed);
        }
        wp_send_json_success(__('محصول با موفقیت به‌روزرسانی شد.', 'cpp-full'));
    } else {
        wp_send_json_error(__('خطا در به‌روزرسانی محصول.', 'cpp-full'));
    }
}

// ۷. توابع AJAX برای دسته‌بندی‌ها
add_action('wp_ajax_cpp_fetch_category_edit_form', 'cpp_fetch_category_edit_form');
function cpp_fetch_category_edit_form() {
    if (!isset($_GET['security']) || !wp_verify_nonce($_GET['security'], 'cpp_admin_nonce')) {
        wp_send_json_error(__('بررسی امنیتی ناموفق بود.', 'cpp-full'));
    }

    $_GET['id'] = isset($_GET['id']) ? intval($_GET['id']) : 0;
    if (!$_GET['id']) { wp_send_json_error(__('شناسه دسته‌بندی نامعتبر است.', 'cpp-full')); }

    ob_start();
    include CPP_TEMPLATES_DIR . 'category-edit.php';
    $html = ob_get_clean();
    wp_send_json_success(['html' => $html]);
}

add_action('wp_ajax_cpp_handle_edit_category_ajax', 'cpp_handle_edit_category_ajax');
function cpp_handle_edit_category_ajax() {
    global $wpdb;
    if (!isset($_POST['cpp_edit_cat_nonce']) || !wp_verify_nonce($_POST['cpp_edit_cat_nonce'], 'cpp_edit_cat_action')) { wp_send_json_error(__('بررسی امنیتی ناموفق بود.', 'cpp-full')); }
    
    $cat_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0;
    if (!$cat_id) { wp_send_json_error(__('شناسه دسته‌بندی نامعتبر است.', 'cpp-full')); }
    
    $data = [ 'name' => sanitize_text_field($_POST['name']), 'slug' => sanitize_title($_POST['slug']), 'image_url' => esc_url_raw($_POST['image_url']) ];
    if (empty($data['slug'])) $data['slug'] = sanitize_title($data['name']);
    
    $updated = $wpdb->update(CPP_DB_CATEGORIES, $data, ['id' => $cat_id]);
    
    if ($updated !== false) { wp_send_json_success(__('دسته‌بندی با موفقیت به‌روزرسانی شد.', 'cpp-full')); } 
    else { wp_send_json_error(__('خطا در به‌روزرسانی دسته‌بندی.', 'cpp-full')); }
}

// ۸. تابع AJAX برای ویرایش سریع (Quick Edit)
add_action('wp_ajax_cpp_quick_update', 'cpp_quick_update');
function cpp_quick_update() {
    check_ajax_referer('cpp_admin_nonce', 'security'); 
    global $wpdb;

    $id    = intval($_POST['id']);
    $field = sanitize_key($_POST['field']);
    $table_type = sanitize_key($_POST['table_type']);
    
    if ($field === 'description' || $field === 'admin_note') { $value = wp_kses_post($_POST['value']); } 
    elseif ($field === 'is_active') { $value = intval($_POST['value']); } 
    else { $value = sanitize_text_field($_POST['value']); }
    
    if (!$id) wp_send_json_error('شناسه نامعتبر است.');
    
    $table = '';
    $allowed_fields = [];
    
    if ($table_type === 'products') { $table = CPP_DB_PRODUCTS; $allowed_fields = ['name', 'price', 'min_price', 'max_price', 'product_type', 'unit', 'load_location', 'is_active', 'description', 'image_url', 'cat_id']; } 
    elseif ($table_type === 'orders') { $table = CPP_DB_ORDERS; $allowed_fields = ['admin_note', 'status']; } 
    elseif ($table_type === 'categories') { $table = CPP_DB_CATEGORIES; $allowed_fields = ['name', 'slug', 'image_url']; } 
    else { wp_send_json_error('نوع جدول نامعتبر است.'); }
    
    if (!in_array($field, $allowed_fields)) { wp_send_json_error('فیلد مورد نظر برای ویرایش نامعتبر است.'); }
    
    $data_to_update = [$field => $value];
    $response_data = ['message' => 'با موفقیت به‌روزرسانی شد.'];
    
    if ($table_type === 'products') {
        $data_to_update['last_updated_at'] = current_time('mysql');
        $old_data = $wpdb->get_row($wpdb->prepare("SELECT price, min_price, max_price FROM " . CPP_DB_PRODUCTS . " WHERE id = %d", $id));
        if (in_array($field, ['price', 'min_price', 'max_price']) && $old_data->$field != $value) {
            CPP_Core::save_price_history($id, [$field => $value]); 
        }
        $response_data['new_time'] = date_i18n('Y/m/d H:i:s', current_time('timestamp'));
    }
    
    $updated = $wpdb->update($table, $data_to_update, ['id' => $id]);
    
    if ($updated === false) { wp_send_json_error('خطا در به‌روزرسانی دیتابیس.'); }
    
    wp_send_json_success($response_data);
}

// ۹. هوک برای سازگاری با المنتور
add_action('elementor/frontend/after_register_styles', 'cpp_enqueue_styles_elementor');
function cpp_enqueue_styles_elementor() {
    if (!wp_style_is('cpp-front-css', 'enqueued')) {
         cpp_front_assets();
    }
}

// ۱۰. تابع AJAX برای تست ارسال ایمیل
add_action('wp_ajax_cpp_test_email', 'cpp_ajax_test_email');
function cpp_ajax_test_email() {
    check_ajax_referer('cpp_admin_nonce', 'security');
    
    $capability = get_option('cpp_admin_capability', 'manage_options');
    if (!current_user_can($capability)) {
        wp_send_json_error(['log' => 'Error: You do not have permission to perform this action.']);
    }

    $log = "--- Starting Email Test ---\n";
    $log .= "Time: " . current_time('mysql') . "\n";

    $to = get_option('cpp_admin_email', get_option('admin_email'));
    if (empty($to) || !is_email($to)) {
        $log .= "Error: Invalid or empty admin email address configured.\n";
        wp_send_json_error(['log' => $log]);
    }

    $log .= "Attempting to send a test email to: " . $to . "\n";

    $subject = 'ایمیل آزمایشی از افزونه مدیریت قیمت';
    $message = '<p style="direction:rtl; text-align:right;">این یک ایمیل آزمایشی برای بررسی صحت عملکرد سیستم ارسال ایمیل وب‌سایت شماست.</p>';
    $headers = array('Content-Type: text/html; charset=UTF-8');

    // استفاده از هوک برای گرفتن خطاهای دقیق
    $error_message = '';
    add_action('wp_mail_failed', function ($wp_error) use (&$error_message) {
        $error_message = $wp_error->get_error_message();
    });

    $sent = wp_mail($to, $subject, $message, $headers);

    if ($sent) {
        $log .= "Success: The wp_mail() function was executed successfully.\n";
        $log .= "This does not guarantee delivery. Please check the inbox at " . $to . ".\n";
        $log .= "If the email is not received, please use an SMTP plugin like 'WP Mail SMTP' to improve deliverability.\n";
        wp_send_json_success(['log' => $log]);
    } else {
        $log .= "Error: The wp_mail() function failed to execute.\n";
        if (!empty($error_message)) {
            $log .= "Error Details: " . $error_message . "\n";
        } else {
            $log .= "No specific error message was returned. This often happens due to server configuration issues.\n";
        }
        $log .= "Recommendation: Install and configure an SMTP plugin (e.g., WP Mail SMTP) to resolve this.\n";
        wp_send_json_error(['log' => $log]);
    }
}

// --- شروع تغییر: افزودن تابع AJAX برای تست پیامک ---
add_action('wp_ajax_cpp_test_sms', 'cpp_ajax_test_sms');
function cpp_ajax_test_sms() {
    check_ajax_referer('cpp_admin_nonce', 'security');
    
    $capability = get_option('cpp_admin_capability', 'manage_options');
    if (!current_user_can($capability)) {
        wp_send_json_error(['log' => 'Error: You do not have permission.']);
    }

    $log = "--- Starting SMS Test ---\n";
    $log .= "Time: " . current_time('mysql') . "\n";

    // ۱. دریافت تنظیمات پیامک
    $service    = get_option('cpp_sms_service');
    $apiKey     = get_option('cpp_sms_api_key');
    $sender     = get_option('cpp_sms_sender');
    $adminPhone = get_option('cpp_admin_phone');

    // ۲. اعتبارسنجی تنظیمات
    if (empty($service) || $service !== 'ippanel') {
        $log .= "Error: SMS Service is not enabled or not set to IPPanel.\n";
        wp_send_json_error(['log' => $log]);
    }
    if (empty($apiKey)) {
        $log .= "Error: IPPanel API Key is not set.\n";
        wp_send_json_error(['log' => $log]);
    }
    if (empty($sender)) {
        $log .= "Error: IPPanel Sender Number is not set.\n";
        wp_send_json_error(['log' => $log]);
    }
    if (empty($adminPhone)) {
        $log .= "Error: Admin Phone Number is not set.\n";
        wp_send_json_error(['log' => $log]);
    }

    $log .= "Attempting to send a test SMS to: " . $adminPhone . "\n";
    $log .= "From Sender: " . $sender . "\n";
    
    // ۳. ارسال پیامک تستی با cURL (کپی منطق از cpp-sms.php)
    $url = 'https://api2.ippanel.com/api/v1/sms/send/webservice/single';
    $test_message = "این یک پیامک آزمایشی از افزونه مدیریت قیمت (CPP) است.";
    $data = [
        'sender' => $sender,
        'recipient' => [$adminPhone],
        'message' => $test_message,
        'description' => [ 'summary' => 'CPP Test SMS' ]
    ];
    $body = json_encode($data);
    $headers = [ 'Content-Type: application/json', 'apikey: ' . $apiKey ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    // ۴. بررسی نتیجه
    if ($curl_error) {
        $log .= "cURL Error: " . $curl_error . "\n";
        wp_send_json_error(['log' => $log]);
    }

    if ($http_code == 200 || $http_code == 201) {
        $result = json_decode($response);
        if (isset($result->data->message_id)) {
            $log .= "Success: SMS sent successfully!\n";
            $log .= "Message ID: " . $result->data->message_id . "\n";
            $log .= "Please check the inbox at " . $adminPhone . ".\n";
            wp_send_json_success(['log' => $log]);
        } else {
            $log .= "API Error: SMS was not sent. Response from IPPanel:\n";
            $log .= $response . "\n";
            wp_send_json_error(['log' => $log]);
        }
    } else {
        $log .= "HTTP Error: Failed to connect to IPPanel API.\n";
        $log .= "Status Code: " . $http_code . "\n";
        $log .= "Response: " . $response . "\n";
        wp_send_json_error(['log' => $log]);
    }
}
// --- پایان تغییر ---
?>
