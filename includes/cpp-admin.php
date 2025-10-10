<?php
if (!defined('ABSPATH')) exit;

// --- ۱. ثبت دارایی‌های ادمین (Admin Assets) ---
add_action('admin_enqueue_scripts', 'cpp_admin_assets');
function cpp_admin_assets($hook) {
    if (strpos($hook, 'custom-prices') === false && $hook !== 'post.php' && $hook !== 'post-new.php' && !isset($_GET['elementor-preview'])) return; 

    // برای آپلود عکس
    wp_enqueue_media(); 
    
    // Chart.js برای نمایش نمودار قیمت
    wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js', [], null, true);

    // جاوا اسکریپت اصلی ادمین 
    wp_enqueue_script('cpp-admin-js', CPP_ASSETS_URL . 'js/admin.js', ['jquery', 'wp-i18n', 'chart-js'], CPP_VERSION, true);
    
    // ارسال داده‌های لازم به جاوا اسکریپت
    wp_localize_script('cpp-admin-js', 'cpp_admin_vars', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('cpp_admin_nonce'), // Nonce برای ویرایش سریع
        // **رفع خطای URL دکمه ویرایش**: ارسال URL پایه به جاوا اسکریپت
        'edit_url_base' => admin_url('admin.php?page=custom-prices-product-edit&id='),
    ]);
    
    // استایل‌های ادمین 
    wp_enqueue_style('cpp-admin-css', CPP_ASSETS_URL . 'css/admin.css', [], CPP_VERSION);
    
    // **استایل‌های پاپ آپ ویرایش** (اضافه شده)
    $custom_css = "
        .cpp-modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            z-index: 10000;
            display: none;
            overflow-y: auto;
        }
        .cpp-modal-container {
            background: #fff;
            margin: 5% auto;
            padding: 20px;
            border-radius: 5px;
            width: 90%;
            max-width: 800px;
            position: relative;
        }
        .cpp-close-modal {
            position: absolute;
            top: 10px;
            left: 10px;
            font-size: 20px;
            cursor: pointer;
            color: #ccc;
        }
        .cpp-modal-container.loading {
            min-height: 200px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
    ";
    wp_add_inline_style('cpp-admin-css', $custom_css);

    // **فعال سازی Media Uploader در پاپ‌آپ**
    wp_add_inline_script('cpp-admin-js', '
        window.cpp_init_media_uploader = function() {
            var mediaUploader;
            jQuery(document).off("click", ".cpp-upload-btn").on("click", ".cpp-upload-btn", function(e) {
                e.preventDefault();
                var button = jQuery(this);
                var input_field = button.siblings("input[type=\"text\"]");
                var preview_img_container = button.siblings(".cpp-image-preview");

                if (mediaUploader) {
                    mediaUploader.open();
                    return;
                }

                mediaUploader = wp.media({
                    title: "انتخاب یا آپلود تصویر",
                    button: {
                        text: "استفاده از این تصویر"
                    },
                    multiple: false
                });

                mediaUploader.on("select", function() {
                    var attachment = mediaUploader.state().get("selection").first().toJSON();
                    input_field.val(attachment.url);
                    preview_img_container.html("<img src=\"" + attachment.url + "\" style=\"max-width: 100px; height: auto; margin-top: 10px;\">");
                });

                mediaUploader.open();
            });
        };
    ', 'after');
}

// --- ۲. تعریف منوهای مدیریت (بدون تغییر) ---
add_action('admin_menu', 'cpp_admin_menu');
function cpp_admin_menu() {
    $main_page = add_menu_page(
        __('مدیریت قیمت‌ها و سفارشات', 'cpp-full'),
        __('مدیریت قیمت', 'cpp-full'),
        'manage_options',
        'custom-prices-products',
        'cpp_products_page',
        'dashicons-tag',
        30
    );
    add_submenu_page('custom-prices-products', __('دسته‌بندی‌ها', 'cpp-full'), __('دسته‌بندی‌ها', 'cpp-full'), 'manage_options', 'custom-prices-categories', 'cpp_categories_page');
    add_submenu_page('custom-prices-products', __('سفارشات', 'cpp-full'), __('سفارشات مشتری', 'cpp-full'), 'manage_options', 'custom-prices-orders', 'cpp_orders_page');
    add_submenu_page('custom-prices-products', __('شورت‌کدها', 'cpp-full'), __('شورت‌کدها', 'cpp-full'), 'manage_options', 'custom-prices-shortcodes', 'cpp_shortcodes_page');
    add_submenu_page('custom-prices-products', __('تنظیمات', 'cpp-full'), __('تنظیمات', 'cpp-full'), 'manage_options', 'custom-prices-settings', 'cpp_settings_page');
    
    // زیرمنوی مخفی برای صفحه ویرایش محصول (در صورت عدم استفاده از پاپ آپ به کار می‌آید)
    add_submenu_page(
        null, 
        __('ویرایش محصول', 'cpp-full'),
        __('ویرایش محصول', 'cpp-full'),
        'manage_options',
        'custom-prices-product-edit',
        'cpp_product_edit_page'
    );
}

// --- ۳. پیاده‌سازی صفحات منو و پاپ‌آپ ویرایش ---
function cpp_products_page() { 
    include CPP_TEMPLATES_DIR . 'products.php'; 
    // افزودن پاپ‌آپ ویرایش در پایان صفحه محصولات
    echo '
        <div id="cpp-edit-modal" class="cpp-modal-overlay" style="display: none;">
            <div class="cpp-modal-container">
                <span class="cpp-close-modal">×</span>
                <div class="cpp-edit-modal-content">
                    </div>
            </div>
        </div>
    ';
}
function cpp_categories_page() { include CPP_TEMPLATES_DIR . 'categories.php'; }
function cpp_orders_page() { include CPP_TEMPLATES_DIR . 'orders.php'; }
function cpp_settings_page() { include CPP_TEMPLATES_DIR . 'settings.php'; }
function cpp_shortcodes_page() { include CPP_TEMPLATES_DIR . 'shortcodes.php'; }
function cpp_product_edit_page() { include CPP_TEMPLATES_DIR . 'product-edit.php'; }


// --- ۴. مدیریت فرم‌ها و حذف آیتم‌ها (بدون تغییر در منطق افزودن/حذف/ویرایش) ---
// ... (این بخش از کد بدون تغییر باقی می‌ماند تا منطق POST اصلی حفظ شود) ...
add_action('admin_init', 'cpp_handle_admin_actions');
function cpp_handle_admin_actions() {
    global $wpdb;
    
    // ... (کد افزودن دسته‌بندی) ...
    if (isset($_POST['cpp_add_category'])) {
        if (!isset($_POST['cpp_add_cat_nonce']) || !wp_verify_nonce($_POST['cpp_add_cat_nonce'], 'cpp_add_cat_action')) {
            wp_die(__('بررسی امنیتی ناموفق بود.', 'cpp-full'));
        }
        $name = sanitize_text_field($_POST['name']);
        $slug = sanitize_title($_POST['slug']);
        $image_url = esc_url_raw($_POST['image_url']);
        if (empty($slug)) $slug = sanitize_title($name);
        $inserted = $wpdb->insert(CPP_DB_CATEGORIES, array('name' => $name,'slug' => $slug,'image_url' => $image_url));
        if ($inserted) {$redirect_url = add_query_arg('cpp_message', 'category_added', admin_url('admin.php?page=custom-prices-categories'));} else {$redirect_url = add_query_arg('cpp_message', 'category_add_failed', admin_url('admin.php?page=custom-prices-categories'));}
        wp_redirect($redirect_url);
        exit;
    }

    // ... (کد افزودن محصول) ...
    if (isset($_POST['cpp_add_product'])) {
        if (!isset($_POST['cpp_add_product_nonce']) || !wp_verify_nonce($_POST['cpp_add_product_nonce'], 'cpp_add_product_action')) {
            wp_die(__('بررسی امنیتی ناموفق بود.', 'cpp-full'));
        }
        $data = ['cat_id' => intval($_POST['cat_id']),'name' => sanitize_text_field($_POST['name']),'price' => sanitize_text_field($_POST['price']),'min_price' => sanitize_text_field($_POST['min_price']),'max_price' => sanitize_text_field($_POST['max_price']),'product_type' => sanitize_text_field($_POST['product_type']),'unit' => sanitize_text_field($_POST['unit']),'load_location' => sanitize_text_field($_POST['load_location']),'is_active' => intval($_POST['is_active']),'description' => sanitize_textarea_field($_POST['description']),'image_url' => esc_url_raw($_POST['image_url']),'last_updated_at' => current_time('mysql')];
        $inserted = $wpdb->insert(CPP_DB_PRODUCTS, $data);
        if ($inserted) {$product_id = $wpdb->insert_id; CPP_Core::save_price_history($product_id, $data['price']); $redirect_url = add_query_arg('cpp_message', 'product_added', admin_url('admin.php?page=custom-prices-products'));} else {$redirect_url = add_query_arg('cpp_message', 'product_add_failed', admin_url('admin.php?page=custom-prices-products'));}
        wp_redirect($redirect_url);
        exit;
    }

    // **مهم**: حذف این منطق POST برای ویرایش محصول، زیرا اکنون با AJAX انجام می‌شود
    // اگر این بخش را حذف کنیم، دیگر نیازی به تابع POST برای ویرایش نیست و فقط AJAX هندل می‌کند.
    /*
    if (isset($_POST['cpp_update_product'])) {
        // ... (منطق ویرایش محصول که اکنون در AJAX هندل می‌شود) ...
    }
    */
    

    // ... (کد مدیریت حذف آیتم‌ها) ...
    if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
        $id = intval($_GET['id']);
        $page = isset($_GET['page']) ? sanitize_key($_GET['page']) : '';
        $redirect_url = admin_url('admin.php?page=' . $page);
        $deleted = false;
        $db_table = '';
        $message_success = '';
        $message_failed = '';
        if ($page == 'custom-prices-categories' && wp_verify_nonce($_GET['_wpnonce'], 'cpp_delete_cat_' . $id)) {$db_table = CPP_DB_CATEGORIES;$message_success = 'category_deleted';$message_failed = 'category_delete_failed';} 
        elseif ($page == 'custom-prices-products' && wp_verify_nonce($_GET['_wpnonce'], 'cpp_delete_product_' . $id)) {$db_table = CPP_DB_PRODUCTS;$message_success = 'product_deleted';$message_failed = 'product_delete_failed';} 
        elseif ($page == 'custom-prices-orders' && wp_verify_nonce($_GET['_wpnonce'], 'cpp_delete_order_' . $id)) {$db_table = CPP_DB_ORDERS;$message_success = 'order_deleted';$message_failed = 'order_delete_failed';}
        if ($db_table) {$deleted = $wpdb->delete($db_table, array('id' => $id));}
        if ($deleted) {$redirect_url = add_query_arg('cpp_message', $message_success, $redirect_url);} else {$redirect_url = add_query_arg('cpp_message', $message_failed, $redirect_url);}
        wp_redirect($redirect_url);
        exit;
    }
}


// --- ۵. AJAX برای پاپ آپ ویرایش (جدید) ---
add_action('wp_ajax_cpp_fetch_product_edit_form', 'cpp_fetch_product_edit_form');
function cpp_fetch_product_edit_form() {
    // برای امنیت بیشتر، Nonce را چک کنید.
    check_ajax_referer('cpp_admin_nonce', 'security');

    $product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    if (!$product_id) {
        wp_send_json_error(__('شناسه محصول نامعتبر است.', 'cpp-full'));
    }

    ob_start();
    // این فایل شامل فرم ویرایش است. 
    // ما با قرار دادن include در اینجا، محتوای آن را به عنوان یک رشته HTML برمی‌گردانیم.
    include CPP_TEMPLATES_DIR . 'product-edit.php';
    $html = ob_get_clean();

    wp_send_json_success([
        'html' => $html
    ]);
}

// --- ۶. AJAX برای هندل کردن ذخیره فرم پاپ آپ (جدید) ---
add_action('wp_ajax_cpp_handle_edit_product_ajax', 'cpp_handle_edit_product_ajax');
function cpp_handle_edit_product_ajax() {
    global $wpdb;
    
    // ۱. بررسی امنیتی Nonce
    if (!isset($_POST['cpp_edit_product_nonce']) || !wp_verify_nonce($_POST['cpp_edit_product_nonce'], 'cpp_edit_product_action')) {
        wp_send_json_error(__('بررسی امنیتی ناموفق بود.', 'cpp-full'));
    }

    // ۲. بررسی شناسه محصول
    $product_id = intval($_POST['product_id']);
    if (!$product_id) {
        wp_send_json_error(__('شناسه محصول نامعتبر است.', 'cpp-full'));
    }
    
    // ۳. واکشی قیمت قدیمی برای تاریخچه
    $old_price = $wpdb->get_var($wpdb->prepare("SELECT price FROM " . CPP_DB_PRODUCTS . " WHERE id = %d", $product_id));

    // ۴. ساخت آرایه داده‌های ورودی
    $data = [
        'cat_id'          => intval($_POST['cat_id']),
        'name'            => sanitize_text_field($_POST['name']),
        'price'           => sanitize_text_field($_POST['price']),
        'min_price'       => sanitize_text_field($_POST['min_price']),
        'max_price'       => sanitize_text_field($_POST['max_price']),
        'product_type'    => sanitize_text_field($_POST['product_type']),
        'unit'            => sanitize_text_field($_POST['unit']),
        'load_location'   => sanitize_text_field($_POST['load_location']),
        'is_active'       => intval($_POST['is_active']),
        'description'     => sanitize_textarea_field($_POST['description']),
        'image_url'       => esc_url_raw($_POST['image_url']),
        'last_updated_at' => current_time('mysql')
    ];

    // ۵. به‌روزرسانی در دیتابیس
    $updated = $wpdb->update(CPP_DB_PRODUCTS, $data, ['id' => $product_id]);

    if ($updated !== false) {
        // ۶. ثبت تاریخچه قیمت در صورت تغییر
        if ($old_price != $data['price']) {
            // فرض بر این است که CPP_Core::save_price_history وجود دارد.
            CPP_Core::save_price_history($product_id, $data['price']);
        }
        wp_send_json_success(__('محصول با موفقیت به‌روزرسانی شد.', 'cpp-full'));
    } else {
        wp_send_json_error(__('خطا در به‌روزرسانی محصول.', 'cpp-full'));
    }
}


// --- ۷. AJAX برای ویرایش سریع (Quick Edit) محصولات و سفارشات (بدون تغییر) ---
add_action('wp_ajax_cpp_quick_update', 'cpp_quick_update');
function cpp_quick_update() {
    check_ajax_referer('cpp_admin_nonce', 'security'); 
    
    global $wpdb;
    $id    = intval($_POST['id']);
    $field = sanitize_key($_POST['field']);
    $table_type = sanitize_key($_POST['table_type']);
    
    // ... (منطق Quick Update بدون تغییر) ...
    if ($field === 'description' || $field === 'admin_note') {$value = sanitize_textarea_field($_POST['value']);} elseif ($field === 'is_active') {$value = intval($_POST['value']);} else {$value = sanitize_text_field($_POST['value']);}
    if (!$id) wp_send_json_error('شناسه نامعتبر است.');
    $table = '';
    $allowed_fields = [];
    if ($table_type === 'products') {$table = CPP_DB_PRODUCTS;$allowed_fields = ['name', 'price', 'min_price', 'max_price', 'product_type', 'unit', 'load_location', 'is_active', 'description', 'image_url', 'cat_id'];} 
    elseif ($table_type === 'orders') {$table = CPP_DB_ORDERS;$allowed_fields = ['admin_note'];} 
    elseif ($table_type === 'categories') {$table = CPP_DB_CATEGORIES;$allowed_fields = ['name', 'slug', 'image_url'];} 
    else {wp_send_json_error('نوع جدول نامعتبر است.');}
    if (!in_array($field, $allowed_fields)) {wp_send_json_error('فیلد مورد نظر برای ویرایش نامعتبر است.');}

    $data_to_update = [$field => $value];
    $response_data = ['message' => 'با موفقیت به‌روزرسانی شد.'];
    if ($table_type === 'products' && $field === 'price') {
        $old_price = $wpdb->get_var($wpdb->prepare("SELECT price FROM " . CPP_DB_PRODUCTS . " WHERE id = %d", $id));
        if ($old_price !== $value) {
            CPP_Core::save_price_history($id, $value); 
            $response_data['new_time'] = date_i18n('Y/m/d H:i:s', current_time('timestamp'));
        }
    }
    
    $updated = $wpdb->update($table, $data_to_update, ['id' => $id]);
    
    if ($updated === false) {
        wp_send_json_error('خطا در به‌روزرسانی دیتابیس.');
    }
    
    wp_send_json_success($response_data);
}

// --- ۸. رفع نهایی مشکل شورت‌کد در المنتور با استفاده از هوک Elementor ---
add_action('elementor/frontend/after_register_styles', 'cpp_enqueue_styles_elementor');
function cpp_enqueue_styles_elementor() {
    // از آنجایی که در فایل اصلی (custom-prices-plugin.php) تابع cpp_products_list_shortcode ثبت شده است
    // و تابع cpp_front_assets برای افزودن استایل‌ها و اسکریپت‌ها به فرانت‌اند فراخوانی شده است،
    // برای تضمین بارگذاری در المنتور، آن را به هوک elementor/frontend متصل می‌کنیم.
    // اگر فایل اصلی شما شامل add_action('wp_enqueue_scripts', 'cpp_front_assets'); است، 
    // فقط کافی است مطمئن شویم که شورت‌کد در آنجا ثبت شده.
    // اگر مشکل پابرجاست، دلیل آن احتمالاً عدم فراخوانی add_shortcode در زمان مناسب در المنتور است.
    
    // اینجا نیازی به ثبت مجدد add_shortcode نیست، چون در فایل اصلی انجام شده است.
    // اما برای اطمینان از بارگذاری دارایی‌های فرانت در المنتور، آن را فراخوانی می‌کنیم.
    if (!wp_style_is('cpp-front-css', 'enqueued')) {
         cpp_front_assets();
    }
}
