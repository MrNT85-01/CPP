<?php
/**
 * Plugin Name: Custom Prices & Orders
 * Description: افزونه مستقل برای مدیریت بازه قیمت‌ها، دسته‌بندی محصولات و ثبت سفارش‌های درخواست شده (بدون ووکامرس). شامل شورت‌کدهای نمایش (کامل، براساس دسته، یا براساس آی‌دی‌ها)، پاپ‌آپ سفارش، صفحه تنظیمات و خروجی اکسل/CSV سفارشات.
 * Version: 3.1.0
 * Author: Mr.NT
 */

if (!defined('ABSPATH')) exit;

global $wpdb;
define('CPP_VERSION', '3.1.0');
define('CPP_PATH', plugin_dir_path(__FILE__));
define('CPP_URL', plugin_dir_url(__FILE__));
define('CPP_TEMPLATES_DIR', CPP_PATH . 'templates/');
define('CPP_ASSETS_URL', CPP_URL . 'assets/');
define('CPP_DB_PRODUCTS', $wpdb->prefix . 'cpp_products');
define('CPP_DB_ORDERS', $wpdb->prefix . 'cpp_orders');
define('CPP_DB_CATEGORIES', $wpdb->prefix . 'cpp_categories');
define('CPP_DB_PRICE_HISTORY', $wpdb->prefix . 'cpp_price_history');
define('CPP_PLUGIN_SLUG','custom-prices');

// بارگذاری فایل‌های ضروری افزونه
require_once(CPP_PATH . 'includes/cpp-core.php');
require_once(CPP_PATH . 'includes/cpp-admin.php');
require_once(CPP_PATH . 'includes/cpp-settings.php');
if (file_exists(CPP_PATH . 'includes/cpp-email.php')) require_once(CPP_PATH . 'includes/cpp-email.php');
if (file_exists(CPP_PATH . 'includes/cpp-sms.php')) require_once(CPP_PATH . 'includes/cpp-sms.php');

// تابع فعال‌سازی افزونه: ایجاد جداول و ثبت مقادیر پیش‌فرض برای تنظیمات
register_activation_hook(__FILE__, 'cpp_activate');
function cpp_activate() {
    CPP_Core::create_db_tables();
    // تنظیمات پیش‌فرض برای ایمیل
    if (get_option('cpp_email_subject_template') === false) {
        update_option('cpp_email_subject_template', 'سفارش جدید: {product_name}');
        update_option('cpp_email_body_template', '<p style="direction:rtl; text-align:right;">سفارش جدیدی از طریق وب‌سایت ثبت شده است:<br><br><strong>محصول:</strong> {product_name}<br><strong>نام مشتری:</strong> {customer_name}<br><strong>شماره تماس:</strong> {phone}<br><strong>تعداد/مقدار:</strong> {qty}<br><strong>توضیحات مشتری:</strong> {note}<br></p>');
    }
    // تنظیمات پیش‌فرض برای نمایش محصولات
    if (get_option('cpp_products_per_page') === false) {
        update_option('cpp_products_per_page', 5);
    }
    // تنظیمات پیش‌فرض برای رنگ دکمه‌ها و نمایش تصویر در شورت‌کدها
    if (get_option('cpp_grid_with_date_button_color') === false) {
        update_option('cpp_grid_with_date_button_color', '#ffc107');
    }
    if (get_option('cpp_grid_no_date_button_color') === false) {
        update_option('cpp_grid_no_date_button_color', '#0073aa');
    }
    if (get_option('cpp_grid_with_date_show_image') === false) {
        update_option('cpp_grid_with_date_show_image', 1);
    }
    if (get_option('cpp_grid_no_date_show_image') === false) {
        update_option('cpp_grid_no_date_show_image', 1);
    }
    // تنظیمات پیش‌فرض برای دسترسی
    if (get_option('cpp_admin_capability') === false) {
        update_option('cpp_admin_capability', 'manage_options');
    }
}

// شورت‌کد [cpp_products_list] برای نمایش جدولی ساده
add_shortcode('cpp_products_list', 'cpp_products_list_shortcode');
function cpp_products_list_shortcode($atts) {
    $atts = shortcode_atts( array( 'cat_id' => '', 'ids' => '', 'status' => '1' ), $atts, 'cpp_products_list' );
    global $wpdb;
    $where_clauses = [];
    $query_params = [];

    if ($atts['status'] !== 'all') {
        $where_clauses[] = 'p.is_active = %d';
        $query_params[] = intval($atts['status']);
    }
    if (!empty($atts['cat_id'])) {
        $cat_ids = array_map('intval', explode(',', $atts['cat_id']));
        if (!empty($cat_ids)) {
            $placeholders = implode(', ', array_fill(0, count($cat_ids), '%d'));
            $where_clauses[] = "p.cat_id IN ({$placeholders})";
            $query_params = array_merge($query_params, $cat_ids);
        }
    }
    if (!empty($atts['ids'])) {
        $product_ids = array_map('intval', explode(',', $atts['ids']));
        if (!empty($product_ids)) {
            $placeholders = implode(', ', array_fill(0, count($product_ids), '%d'));
            $where_clauses[] = "p.id IN ({$placeholders})";
            $query_params = array_merge($query_params, $product_ids);
        }
    }

    $where_sql = !empty($where_clauses) ? ' WHERE ' . implode(' AND ', $where_clauses) : '';
    $query = "SELECT p.*, c.name as category_name FROM " . CPP_DB_PRODUCTS . " p LEFT JOIN " . CPP_DB_CATEGORIES . " c ON p.cat_id = c.id {$where_sql} ORDER BY p.id DESC";
    $products = $wpdb->get_results($wpdb->prepare($query, $query_params));

    if (!$products) { return '<p class="cpp-no-products">' . __('محصولی برای نمایش یافت نشد.', 'cpp-full') . '</p>'; }
    
    ob_start();
    include CPP_TEMPLATES_DIR . 'shortcode-list.php';
    return ob_get_clean();
}

// شورت‌کد [cpp_products_grid_view] با ستون تاریخ
add_shortcode('cpp_products_grid_view', 'cpp_products_grid_view_shortcode');
function cpp_products_grid_view_shortcode($atts) {
    global $wpdb;
    $categories = CPP_Core::get_all_categories();
    $products_per_page = get_option('cpp_products_per_page', 5);
    $products = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM " . CPP_DB_PRODUCTS . " WHERE is_active = 1 ORDER BY id DESC LIMIT %d",
        $products_per_page
    ));
    $total_products = $wpdb->get_var("SELECT COUNT(id) FROM " . CPP_DB_PRODUCTS . " WHERE is_active = 1");

    if (!$products) { return '<p class="cpp-no-products">' . __('محصولی برای نمایش یافت نشد.', 'cpp-full') . '</p>'; }
    
    ob_start();
    include CPP_TEMPLATES_DIR . 'shortcode-grid-view.php';
    return ob_get_clean();
}

// شورت‌کد [cpp_products_grid_view_no_date] بدون ستون تاریخ
add_shortcode('cpp_products_grid_view_no_date', 'cpp_products_grid_view_no_date_shortcode');
function cpp_products_grid_view_no_date_shortcode($atts) {
    global $wpdb;
    $categories = CPP_Core::get_all_categories();
    $products_per_page = get_option('cpp_products_per_page', 5);
    $products = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM " . CPP_DB_PRODUCTS . " WHERE is_active = 1 ORDER BY id DESC LIMIT %d",
        $products_per_page
    ));
    $total_products = $wpdb->get_var("SELECT COUNT(id) FROM " . CPP_DB_PRODUCTS . " WHERE is_active = 1");
    $last_updated_time = $wpdb->get_var("SELECT MAX(last_updated_at) FROM " . CPP_DB_PRODUCTS . " WHERE is_active = 1");

    if (!$products) { return '<p class="cpp-no-products">' . __('محصولی برای نمایش یافت نشد.', 'cpp-full') . '</p>'; }
    
    ob_start();
    include CPP_TEMPLATES_DIR . 'shortcode-grid-view-no-date.php';
    return ob_get_clean();
}

// بارگذاری اسکریپت‌ها و استایل‌های بخش کاربری
add_action('wp_enqueue_scripts', 'cpp_front_assets');
function cpp_front_assets() {
    wp_enqueue_style('cpp-front-css', CPP_ASSETS_URL . 'css/front.css', [], CPP_VERSION);
    wp_enqueue_style('cpp-grid-view-css', CPP_ASSETS_URL . 'css/grid-view.css', [], CPP_VERSION);

    wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js', [], null, true);
    wp_enqueue_script('cpp-front-js', CPP_ASSETS_URL . 'js/front.js', ['jquery', 'chart-js'], CPP_VERSION, true);
    
    wp_localize_script('cpp-front-js', 'cpp_front_vars', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('cpp_front_nonce'),
        'i18n' => [
            'view_more' => __('مشاهده بیشتر', 'cpp-full'),
            'loading' => __('در حال بارگذاری...', 'cpp-full'),
            'no_more_products' => __('محصول دیگری برای نمایش وجود ندارد.', 'cpp-full'),
        ]
    ));
}

// افزودن مودال‌ها و استایل‌های داینامیک به فوتر سایت
add_action('wp_footer', 'cpp_add_modals_to_footer');
function cpp_add_modals_to_footer() {
    $modals_template = CPP_TEMPLATES_DIR . 'modals-frontend.php';
    if (file_exists($modals_template)) { include $modals_template; }

    $color_with_date = get_option('cpp_grid_with_date_button_color', '#ffc107');
    $color_no_date = get_option('cpp_grid_no_date_button_color', '#0073aa');

    $custom_css = "
        .cpp-grid-view-wrapper.with-date-shortcode .cpp-grid-view-filters .filter-btn.active {
            background-color: " . esc_attr($color_with_date) . " !important;
            border-color: " . esc_attr($color_with_date) . " !important;
        }
        .cpp-grid-view-wrapper.no-date-shortcode .cpp-grid-view-filters .filter-btn.active {
            background-color: " . esc_attr($color_no_date) . " !important;
            border-color: " . esc_attr($color_no_date) . " !important;
        }
    ";
    echo '<style type="text/css">' . $custom_css . '</style>';
}

// تابع ایجکس برای بارگذاری محصولات بیشتر ("مشاهده بیشتر")
add_action('wp_ajax_cpp_load_more_products', 'cpp_load_more_products');
add_action('wp_ajax_nopriv_cpp_load_more_products', 'cpp_load_more_products');
function cpp_load_more_products() {
    check_ajax_referer('cpp_front_nonce', 'nonce');
    global $wpdb;

    $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
    $products_per_page = get_option('cpp_products_per_page', 5);
    $offset = $page * $products_per_page;
    $shortcode_type = isset($_POST['shortcode_type']) ? sanitize_key($_POST['shortcode_type']) : 'with_date';

    if ($shortcode_type === 'with_date') {
        $show_image = get_option('cpp_grid_with_date_show_image', 1);
        $show_date_column = true;
    } else {
        $show_image = get_option('cpp_grid_no_date_show_image', 1);
        $show_date_column = false;
    }

    $products = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM " . CPP_DB_PRODUCTS . " WHERE is_active = 1 ORDER BY id DESC LIMIT %d OFFSET %d",
        $products_per_page,
        $offset
    ));

    if ($products) {
        ob_start();
        $default_image = get_option('cpp_default_product_image', CPP_ASSETS_URL . 'images/default-product.png');
        foreach ($products as $product) {
            $disable_base_price = get_option('cpp_disable_base_price', 0);
            $cart_icon_url = CPP_ASSETS_URL . 'images/cart-icon.png';
            $chart_icon_url = CPP_ASSETS_URL . 'images/chart-icon.png';
            $product_image_url = !empty($product->image_url) ? $product->image_url : $default_image;
            ?>
            <tr class="product-row" data-cat-id="<?php echo esc_attr($product->cat_id); ?>">
                <td class="col-product-name">
                    <?php if ($show_image) : ?>
                        <img src="<?php echo esc_url($product_image_url); ?>">
                    <?php endif; ?>
                    <span><?php echo esc_html($product->name); ?></span>
                </td>
                <td><?php echo esc_html($product->product_type); ?></td>
                <td><?php echo esc_html($product->unit); ?></td>
                <td><?php echo esc_html($product->load_location); ?></td>
                
                <?php if ($show_date_column): ?>
                <td><?php echo esc_html(date_i18n('Y/m/d H:i', strtotime($product->last_updated_at))); ?></td>
                <?php endif; ?>
                
                <?php if (!$disable_base_price) : ?>
                <td class="col-price">
                    <?php 
                        if (!empty($product->price) && is_numeric(str_replace(',', '', $product->price))) {
                            echo esc_html(number_format_i18n((float)str_replace(',', '', $product->price)));
                        } else {
                            echo esc_html($product->price);
                        }
                    ?>
                </td>
                <?php endif; ?>

                 <td class="col-price-range">
                    <?php if (!empty($product->min_price) && !empty($product->max_price)) : ?>
                        <?php echo esc_html(number_format_i18n(str_replace(',', '', $product->min_price))); ?> - <?php echo esc_html(number_format_i18n(str_replace(',', '', $product->max_price))); ?>
                    <?php else: ?>
                        <span class="cpp-price-not-set"><?php _e('تماس بگیرید', 'cpp-full'); ?></span>
                    <?php endif; ?>
                </td>

                <td class="col-actions">
                    <button class="cpp-icon-btn cpp-order-btn" data-product-id="<?php echo esc_attr($product->id); ?>" data-product-name="<?php echo esc_attr($product->name); ?>" title="<?php _e('خرید', 'cpp-full'); ?>"><img src="<?php echo esc_url($cart_icon_url); ?>" alt="<?php _e('خرید', 'cpp-full'); ?>"></button>
                    <button class="cpp-icon-btn cpp-chart-btn" data-product-id="<?php echo esc_attr($product->id); ?>" title="<?php _e('نمودار', 'cpp-full'); ?>"><img src="<?php echo esc_url($chart_icon_url); ?>" alt="<?php _e('نمودار', 'cpp-full'); ?>"></button>
                </td>
            </tr>
            <?php
        }
        $html = ob_get_clean();
        wp_send_json_success(['html' => $html]);
    } else {
        wp_send_json_error();
    }
}
?>
