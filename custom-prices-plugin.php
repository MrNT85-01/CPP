<?php
/**
 * Plugin Name: Custom Prices & Orders
 * Description: افزونه مستقل برای مدیریت بازه قیمت‌ها، دسته‌بندی محصولات و ثبت سفارش‌های درخواست شده (بدون ووکامرس). شامل شورت‌کدهای نمایش (کامل، براساس دسته، یا براساس آی‌دی‌ها)، پاپ‌آپ سفارش، صفحه تنظیمات و خروجی اکسل/CSV سفارشات.
 * Version: 2.4
 * Author: Mr.NT
 */

if (!defined('ABSPATH')) exit;

global $wpdb;
define('CPP_VERSION', '2.4.0'); 
define('CPP_PATH', plugin_dir_path(__FILE__));
define('CPP_URL', plugin_dir_url(__FILE__));
define('CPP_TEMPLATES_DIR', CPP_PATH . 'templates/');
define('CPP_ASSETS_URL', CPP_URL . 'assets/');
define('CPP_DB_PRODUCTS', $wpdb->prefix . 'cpp_products');
define('CPP_DB_ORDERS', $wpdb->prefix . 'cpp_orders');
define('CPP_DB_CATEGORIES', $wpdb->prefix . 'cpp_categories');
define('CPP_DB_PRICE_HISTORY', $wpdb->prefix . 'cpp_price_history'); 
define('CPP_PLUGIN_SLUG','custom-prices');

require_once(CPP_PATH . 'includes/cpp-core.php');
require_once(CPP_PATH . 'includes/cpp-admin.php');
require_once(CPP_PATH . 'includes/cpp-settings.php');
if (file_exists(CPP_PATH . 'includes/cpp-email.php')) require_once(CPP_PATH . 'includes/cpp-email.php');
if (file_exists(CPP_PATH . 'includes/cpp-sms.php')) require_once(CPP_PATH . 'includes/cpp-sms.php');

register_activation_hook(__FILE__, 'cpp_activate');
function cpp_activate() {
    CPP_Core::create_db_tables(); 
    if (get_option('cpp_email_subject_template') === false) {
        update_option('cpp_email_subject_template', 'سفارش جدید: {product_name}');
        update_option('cpp_email_body_template', '<p style="direction:rtl; text-align:right;">سفارش جدیدی از طریق وب‌سایت ثبت شده است:<br><br><strong>محصول:</strong> {product_name}<br><strong>نام مشتری:</strong> {customer_name}<br><strong>شماره تماس:</strong> {phone}<br><strong>تعداد/مقدار:</strong> {qty}<br><strong>توضیحات مشتری:</strong> {note}<br></p>');
    }
}

// Shortcode [cpp_products_list]
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
    
    $where_sql = 'WHERE 1=1';
    if (!empty($where_clauses)) { $where_sql .= ' AND ' . implode(' AND ', $where_clauses); }
    
    $query = "SELECT p.*, c.name as category_name FROM " . CPP_DB_PRODUCTS . " p LEFT JOIN " . CPP_DB_CATEGORIES . " c ON p.cat_id = c.id {$where_sql} ORDER BY p.id DESC";
    $products = $wpdb->get_results($wpdb->prepare($query, $query_params));

    if (!$products) { return '<p class="cpp-no-products">محصولی برای نمایش یافت نشد.</p>'; }
    ob_start();
    include CPP_TEMPLATES_DIR . 'shortcode-list.php'; 
    return ob_get_clean();
}

// Shortcode [cpp_products_grid_view]
add_shortcode('cpp_products_grid_view', 'cpp_products_grid_view_shortcode');
function cpp_products_grid_view_shortcode($atts) {
    $atts = shortcode_atts(['more_link' => ''], $atts, 'cpp_products_grid_view');
    global $wpdb;
    $categories = CPP_Core::get_all_categories();
    $products = $wpdb->get_results("SELECT * FROM " . CPP_DB_PRODUCTS . " WHERE is_active = 1 ORDER BY id DESC");
    if (!$products) { return '<p class="cpp-no-products">محصولی برای نمایش یافت نشد.</p>'; }
    ob_start();
    include CPP_TEMPLATES_DIR . 'shortcode-grid-view.php';
    return ob_get_clean();
}

add_action('wp_enqueue_scripts', 'cpp_front_assets');
function cpp_front_assets() {
    wp_enqueue_style('cpp-front-css', CPP_ASSETS_URL . 'css/front.css', [], CPP_VERSION);
    // بازگرداندن فایل CSS برای شورت‌کد گرید
    wp_enqueue_style('cpp-grid-view-css', CPP_ASSETS_URL . 'css/grid-view.css', [], CPP_VERSION);
    
    wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js', [], null, true);
    wp_enqueue_script('cpp-front-js', CPP_ASSETS_URL . 'js/front.js', ['jquery', 'chart-js'], CPP_VERSION, true);
    wp_localize_script('cpp-front-js', 'cpp_front_vars', array( 'ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('cpp_front_nonce') ));
}

add_action('wp_footer', 'cpp_add_modals_to_footer');
function cpp_add_modals_to_footer() {
    $modals_template = CPP_TEMPLATES_DIR . 'modals-frontend.php';
    if (file_exists($modals_template)) { include $modals_template; }
}
