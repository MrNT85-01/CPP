<?php
if (!defined('ABSPATH')) exit;

// --- ۱. ثبت دارایی‌های ادمین (Admin Assets) ---
add_action('admin_enqueue_scripts', 'cpp_admin_assets');
function cpp_admin_assets($hook) {
    if (strpos($hook, 'custom-prices') === false && $hook !== 'post.php' && $hook !== 'post-new.php' && !isset($_GET['elementor-preview'])) return; 

    wp_enqueue_media(); 
    wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js', [], null, true);
    wp_enqueue_script('cpp-admin-js', CPP_ASSETS_URL . 'js/admin.js', ['jquery', 'wp-i18n', 'chart-js'], CPP_VERSION, true);
    
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
    
    wp_enqueue_style('cpp-admin-css', CPP_ASSETS_URL . 'css/admin.css', [], CPP_VERSION);
    
    $custom_css = "
        .cpp-modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.7); z-index: 10000; display: none; overflow-y: auto; }
        .cpp-modal-container { background: #fff; margin: 5% auto; padding: 20px; border-radius: 5px; width: 90%; max-width: 800px; position: relative; }
        .cpp-close-modal { position: absolute; top: 10px; left: 10px; font-size: 20px; cursor: pointer; color: #ccc; }
        .cpp-modal-container.loading { min-height: 200px; display: flex; align-items: center; justify-content: center; }
    ";
    wp_add_inline_style('cpp-admin-css', $custom_css);

    wp_add_inline_script('cpp-admin-js', '
        window.cpp_init_media_uploader = function() {
            var mediaUploader;
            jQuery(document).off("click", ".cpp-upload-btn").on("click", ".cpp-upload-btn", function(e) {
                e.preventDefault();
                var button = jQuery(this);
                var input_field = button.siblings("input[type=\"text\"]");
                var preview_img_container = button.siblings(".cpp-image-preview");

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
    ', 'after');
}

add_action('admin_menu', 'cpp_admin_menu');
function cpp_admin_menu() {
    add_menu_page( __('مدیریت قیمت‌ها و سفارشات', 'cpp-full'), __('مدیریت قیمت', 'cpp-full'), 'manage_options', 'custom-prices-products', 'cpp_products_page', 'dashicons-tag', 30 );
    add_submenu_page('custom-prices-products', __('دسته‌بندی‌ها', 'cpp-full'), __('دسته‌بندی‌ها', 'cpp-full'), 'manage_options', 'custom-prices-categories', 'cpp_categories_page');
    add_submenu_page('custom-prices-products', __('سفارشات', 'cpp-full'), __('سفارشات مشتری', 'cpp-full'), 'manage_options', 'custom-prices-orders', 'cpp_orders_page');
    add_submenu_page('custom-prices-products', __('شورت‌کدها', 'cpp-full'), __('شورت‌کدها', 'cpp-full'), 'manage_options', 'custom-prices-shortcodes', 'cpp_shortcodes_page');
    add_submenu_page('custom-prices-products', __('تنظیمات', 'cpp-full'), __('تنظیمات', 'cpp-full'), 'manage_options', 'custom-prices-settings', 'cpp_settings_page');
    add_submenu_page( null, __('ویرایش محصول', 'cpp-full'), __('ویرایش محصول', 'cpp-full'), 'manage_options', 'custom-prices-product-edit', 'cpp_product_edit_page' );
}

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

add_action('admin_init', 'cpp_handle_admin_actions');
function cpp_handle_admin_actions() {
    global $wpdb;
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
    if (isset($_POST['cpp_add_product'])) {
        if (!isset($_POST['cpp_add_product_nonce']) || !wp_verify_nonce($_POST['cpp_add_product_nonce'], 'cpp_add_product_action')) { wp_die(__('بررسی امنیتی ناموفق بود.', 'cpp-full')); }
        $data = ['cat_id' => intval($_POST['cat_id']),'name' => sanitize_text_field($_POST['name']),'price' => sanitize_text_field($_POST['price']),'min_price' => sanitize_text_field($_POST['min_price']),'max_price' => sanitize_text_field($_POST['max_price']),'product_type' => sanitize_text_field($_POST['product_type']),'unit' => sanitize_text_field($_POST['unit']),'load_location' => sanitize_text_field($_POST['load_location']),'is_active' => intval($_POST['is_active']),'description' => sanitize_textarea_field($_POST['description']),'image_url' => esc_url_raw($_POST['image_url']),'last_updated_at' => current_time('mysql')];
        $inserted = $wpdb->insert(CPP_DB_PRODUCTS, $data);
        if ($inserted) { $product_id = $wpdb->insert_id; CPP_Core::save_price_history($product_id, $data['price']); }
        $redirect_url = add_query_arg('cpp_message', $inserted ? 'product_added' : 'product_add_failed', admin_url('admin.php?page=custom-prices-products'));
        wp_redirect($redirect_url); exit;
    }
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

// --- AJAX برای محصولات ---
add_action('wp_ajax_cpp_fetch_product_edit_form', 'cpp_fetch_product_edit_form');
function cpp_fetch_product_edit_form() {
    // --- شروع تغییر: اصلاح روش بررسی امنیتی ---
    if (!isset($_GET['security']) || !wp_verify_nonce($_GET['security'], 'cpp_admin_nonce')) {
        wp_send_json_error(__('بررسی امنیتی ناموفق بود.', 'cpp-full'));
    }
    // --- پایان تغییر ---
    
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
    $old_price = $wpdb->get_var($wpdb->prepare("SELECT price FROM " . CPP_DB_PRODUCTS . " WHERE id = %d", $product_id));
    $data = [
        'cat_id' => intval($_POST['cat_id']), 'name' => sanitize_text_field($_POST['name']), 'price' => sanitize_text_field($_POST['price']),
        'min_price' => sanitize_text_field($_POST['min_price']), 'max_price' => sanitize_text_field($_POST['max_price']), 'product_type' => sanitize_text_field($_POST['product_type']),
        'unit' => sanitize_text_field($_POST['unit']), 'load_location' => sanitize_text_field($_POST['load_location']), 'is_active' => intval($_POST['is_active']),
        'description' => sanitize_textarea_field($_POST['description']), 'image_url' => esc_url_raw($_POST['image_url']), 'last_updated_at' => current_time('mysql')
    ];
    $updated = $wpdb->update(CPP_DB_PRODUCTS, $data, ['id' => $product_id]);
    if ($updated !== false) {
        if ($old_price != $data['price']) { CPP_Core::save_price_history($product_id, $data['price']); }
        wp_send_json_success(__('محصول با موفقیت به‌روزرسانی شد.', 'cpp-full'));
    } else {
        wp_send_json_error(__('خطا در به‌روزرسانی محصول.', 'cpp-full'));
    }
}

// --- AJAX برای دسته‌بندی‌ها ---
add_action('wp_ajax_cpp_fetch_category_edit_form', 'cpp_fetch_category_edit_form');
function cpp_fetch_category_edit_form() {
    // --- شروع تغییر: اصلاح روش بررسی امنیتی ---
    if (!isset($_GET['security']) || !wp_verify_nonce($_GET['security'], 'cpp_admin_nonce')) {
        wp_send_json_error(__('بررسی امنیتی ناموفق بود.', 'cpp-full'));
    }
    // --- پایان تغییر ---

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

// --- AJAX برای ویرایش سریع ---
add_action('wp_ajax_cpp_quick_update', 'cpp_quick_update');
function cpp_quick_update() {
    check_ajax_referer('cpp_admin_nonce', 'security'); 
    global $wpdb;
    $id    = intval($_POST['id']);
    $field = sanitize_key($_POST['field']);
    $table_type = sanitize_key($_POST['table_type']);
    if ($field === 'description' || $field === 'admin_note') { $value = sanitize_textarea_field($_POST['value']); } 
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
    if ($table_type === 'products' && $field === 'price') {
        $old_price = $wpdb->get_var($wpdb->prepare("SELECT price FROM " . CPP_DB_PRODUCTS . " WHERE id = %d", $id));
        if ($old_price !== $value) {
            CPP_Core::save_price_history($id, $value); 
            $response_data['new_time'] = date_i18n('Y/m/d H:i:s', current_time('timestamp'));
        }
    }
    $updated = $wpdb->update($table, $data_to_update, ['id' => $id]);
    if ($updated === false) { wp_send_json_error('خطا در به‌روزرسانی دیتابیس.'); }
    wp_send_json_success($response_data);
}

add_action('elementor/frontend/after_register_styles', 'cpp_enqueue_styles_elementor');
function cpp_enqueue_styles_elementor() {
    if (!wp_style_is('cpp-front-css', 'enqueued')) {
         cpp_front_assets();
    }
}
