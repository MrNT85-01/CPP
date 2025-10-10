<?php
if (!defined('ABSPATH')) exit;

class CPP_Core {
    
    public static function create_db_tables() {
        global $wpdb;
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        $charset_collate = $wpdb->get_charset_collate();

        $sql1 = "CREATE TABLE " . CPP_DB_CATEGORIES . " ( id mediumint(9) NOT NULL AUTO_INCREMENT, name varchar(200) NOT NULL, slug varchar(200) NOT NULL, image_url varchar(255) DEFAULT '', created datetime DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY  (id) ) $charset_collate;";
        $sql2 = "CREATE TABLE " . CPP_DB_PRODUCTS . " ( id mediumint(9) NOT NULL AUTO_INCREMENT, cat_id mediumint(9) NOT NULL, name varchar(200) NOT NULL, price varchar(50) NOT NULL, min_price varchar(50) DEFAULT '0', max_price varchar(50) DEFAULT '0', product_type varchar(100) DEFAULT '', unit varchar(50) DEFAULT '', load_location varchar(200) DEFAULT '', is_active tinyint(1) DEFAULT 1, description text, image_url varchar(255) DEFAULT '', last_updated_at datetime DEFAULT CURRENT_TIMESTAMP, created datetime DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY  (id) ) $charset_collate;";
        
        // --- شروع تغییر: افزودن ستون status به جدول سفارشات ---
        $sql3 = "CREATE TABLE " . CPP_DB_ORDERS . " ( id mediumint(9) NOT NULL AUTO_INCREMENT, product_id mediumint(9) NOT NULL, product_name varchar(200) NOT NULL, customer_name varchar(200) NOT NULL, phone varchar(50) NOT NULL, qty varchar(50) NOT NULL, note text, admin_note text, status varchar(50) DEFAULT 'new_order', created datetime DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY  (id) ) $charset_collate;";
        // --- پایان تغییر ---

        $sql4 = "CREATE TABLE " . CPP_DB_PRICE_HISTORY . " ( id mediumint(9) NOT NULL AUTO_INCREMENT, product_id mediumint(9) NOT NULL, price varchar(200) NOT NULL, change_time datetime DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (id) ) $charset_collate;";
        
        dbDelta($sql1);
        dbDelta($sql2);
        dbDelta($sql3);
        dbDelta($sql4);
    }

    public static function save_price_history($product_id, $new_price) {
        global $wpdb;
        $wpdb->insert(CPP_DB_PRICE_HISTORY, array('product_id' => intval($product_id), 'price' => sanitize_text_field($new_price)));
        $wpdb->update(CPP_DB_PRODUCTS, array('last_updated_at' => current_time('mysql')), array('id' => intval($product_id)));
    }
    
    public static function get_chart_data($product_id, $months = 6) {
        global $wpdb;
        
        $disable_base_price = get_option('cpp_disable_base_price', 0);
        $labels = [];
        $prices = [];
        $min_prices = [];
        $max_prices = [];
        
        if (!$disable_base_price) {
            $history = $wpdb->get_results($wpdb->prepare("
                SELECT price, change_time 
                FROM " . CPP_DB_PRICE_HISTORY . " 
                WHERE product_id=%d AND change_time >= DATE_SUB(NOW(), INTERVAL %d MONTH) 
                ORDER BY change_time ASC
            ", $product_id, $months));

            foreach ($history as $row) {
                 $labels[] = date_i18n('Y/m/d', strtotime($row->change_time)); 
                 $prices[] = (float) str_replace(',', '', $row->price);
            }
        }

        $product = $wpdb->get_row($wpdb->prepare("SELECT min_price, max_price FROM " . CPP_DB_PRODUCTS . " WHERE id = %d", $product_id));

        if ($product && !empty($product->min_price) && !empty($product->max_price)) {
            if(empty($labels)){
                $labels_history = $wpdb->get_col($wpdb->prepare("SELECT change_time FROM " . CPP_DB_PRICE_HISTORY . " WHERE product_id=%d AND change_time >= DATE_SUB(NOW(), INTERVAL %d MONTH) ORDER BY change_time ASC LIMIT 5", $product_id, $months));
                if (!empty($labels_history)) {
                    foreach($labels_history as $label_date) {
                        $labels[] = date_i18n('Y/m/d', strtotime($label_date));
                    }
                } else {
                    $labels = [date_i18n('Y/m/d')];
                }
            }
            foreach ($labels as $_) {
                 $min_prices[] = (float) str_replace(',', '', $product->min_price);
                 $max_prices[] = (float) str_replace(',', '', $product->max_price);
            }
        }
        
        return [
            'labels' => $labels, 
            'prices' => $prices,
            'min_prices' => $min_prices,
            'max_prices' => $max_prices
        ];
    }
    
    public static function get_all_categories() {
        global $wpdb;
        return $wpdb->get_results("SELECT id, name, slug, image_url, created FROM " . CPP_DB_CATEGORIES . " ORDER BY name ASC");
    }

    public static function get_all_orders() {
        global $wpdb;
        return $wpdb->get_results("SELECT * FROM " . CPP_DB_ORDERS . " ORDER BY created DESC");
    }
}

add_action('wp_ajax_cpp_get_chart_data', 'cpp_ajax_get_chart_data');
add_action('wp_ajax_nopriv_cpp_get_chart_data', 'cpp_ajax_get_chart_data');
function cpp_ajax_get_chart_data() {
    $product_id = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;
    if (!$product_id) wp_send_json_error('Invalid Product ID');
    $data = CPP_Core::get_chart_data($product_id);
    if(empty($data['labels'])) {
        wp_send_json_error('No data');
    }
    wp_send_json_success($data);
}

add_action('wp_ajax_cpp_submit_order', 'cpp_submit_order');
add_action('wp_ajax_nopriv_cpp_submit_order', 'cpp_submit_order'); 
function cpp_submit_order() {
    check_ajax_referer('cpp_front_nonce','nonce');
    global $wpdb;
    
    $product_id = intval($_POST['product_id']);
    $product = $wpdb->get_row($wpdb->prepare("SELECT * FROM " . CPP_DB_PRODUCTS . " WHERE id=%d", $product_id));
    if (!$product) wp_send_json_error('محصول یافت نشد.');

    $customer_name = sanitize_text_field($_POST['customer_name']);
    $phone = sanitize_text_field($_POST['phone']);
    $qty = sanitize_text_field($_POST['qty']);
    $note = sanitize_textarea_field($_POST['note']);

    if(empty($customer_name) || empty($phone) || empty($qty)){
        wp_send_json_error('لطفا تمام فیلدهای ستاره‌دار را پر کنید.');
    }

    $wpdb->insert(CPP_DB_ORDERS, [
        'product_id'=>$product_id,
        'product_name'=>$product->name,
        'customer_name'=>$customer_name,
        'phone'=>$phone,
        'qty'=>$qty,
        'note'=>$note,
        'status' => 'new_order' // وضعیت پیش‌فرض برای سفارش جدید
    ]);

    $placeholders = [
        '{product_name}'  => $product->name,
        '{customer_name}' => $customer_name,
        '{phone}'         => $phone,
        '{qty}'           => $qty,
        '{note}'          => $note,
    ];

    if (class_exists('CPP_Full_Email')) {
        CPP_Full_Email::send_notification($placeholders);
    }
    if (class_exists('CPP_Full_SMS')) {
        CPP_Full_SMS::send_notification($placeholders);
    }
    
    wp_send_json_success('درخواست شما با موفقیت ثبت شد.');
}
