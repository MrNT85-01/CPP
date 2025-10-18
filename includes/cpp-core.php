<?php
if (!defined('ABSPATH')) exit;

class CPP_Core {

    // --- شروع تغییر: افزودن تابع برای شروع Session ---
    public static function init_session() {
        if (!session_id() && !headers_sent()) {
            session_start();
        }
    }
    // --- پایان تغییر ---

    public static function create_db_tables() {
        global $wpdb;
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        $charset_collate = $wpdb->get_charset_collate();

        // ... (SQLهای جدول categories, products, orders, price_history بدون تغییر) ...
         $sql1 = "CREATE TABLE " . CPP_DB_CATEGORIES . " ( id mediumint(9) NOT NULL AUTO_INCREMENT, name varchar(200) NOT NULL, slug varchar(200) NOT NULL, image_url varchar(255) DEFAULT '', created datetime DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY  (id) ) $charset_collate;";
        $sql2 = "CREATE TABLE " . CPP_DB_PRODUCTS . " ( id mediumint(9) NOT NULL AUTO_INCREMENT, cat_id mediumint(9) NOT NULL, name varchar(200) NOT NULL, price varchar(50) DEFAULT '', min_price varchar(50) DEFAULT '', max_price varchar(50) DEFAULT '', product_type varchar(100) DEFAULT '', unit varchar(50) DEFAULT '', load_location varchar(200) DEFAULT '', is_active tinyint(1) DEFAULT 1, description text, image_url varchar(255) DEFAULT '', last_updated_at datetime DEFAULT CURRENT_TIMESTAMP, created datetime DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY  (id), KEY cat_id (cat_id) ) $charset_collate;"; // Added index

        $sql3 = "CREATE TABLE " . CPP_DB_ORDERS . " ( id mediumint(9) NOT NULL AUTO_INCREMENT, product_id mediumint(9) NOT NULL, product_name varchar(200) NOT NULL, customer_name varchar(200) NOT NULL, phone varchar(50) NOT NULL, qty varchar(50) NOT NULL, note text, admin_note text, status varchar(50) DEFAULT 'new_order', created datetime DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY  (id), KEY product_id (product_id), KEY phone (phone) ) $charset_collate;"; // Added indexes

        $sql4 = "CREATE TABLE " . CPP_DB_PRICE_HISTORY . " ( id bigint(20) NOT NULL AUTO_INCREMENT, product_id mediumint(9) NOT NULL, price varchar(50) DEFAULT NULL, min_price varchar(50) DEFAULT NULL, max_price varchar(50) DEFAULT NULL, change_time datetime DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (id), KEY product_id (product_id) ) $charset_collate;"; // Changed ID to bigint, added min/max, added index

        dbDelta($sql1);
        dbDelta($sql2);
        dbDelta($sql3);
        dbDelta($sql4);

        // --- Add upgrade logic if needed ---
        // Example: Add min_price/max_price columns to price history if they don't exist
        $table_name = CPP_DB_PRICE_HISTORY;
        if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name) {
             if(!$wpdb->get_var($wpdb->prepare("SHOW COLUMNS FROM `$table_name` LIKE %s", 'min_price'))) {
                 $wpdb->query("ALTER TABLE `$table_name` ADD `min_price` VARCHAR(50) DEFAULT NULL AFTER `price`");
             }
             if(!$wpdb->get_var($wpdb->prepare("SHOW COLUMNS FROM `$table_name` LIKE %s", 'max_price'))) {
                 $wpdb->query("ALTER TABLE `$table_name` ADD `max_price` VARCHAR(50) DEFAULT NULL AFTER `min_price`");
             }
             // Change price column to allow NULL if needed for pattern-only updates
             // $wpdb->query("ALTER TABLE `$table_name` MODIFY `price` VARCHAR(50) DEFAULT NULL");
        }

    }

    /**
     * Save price history. Now accepts an array of price data.
     * @param int $product_id
     * @param array $price_data Associative array e.g., ['price' => '1000', 'min_price' => '900', 'max_price' => '1100']
     */
    public static function save_price_history($product_id, $price_data) {
        global $wpdb;
        if (!is_array($price_data) || empty($price_data) || !intval($product_id)) {
            return false;
        }

        $data_to_insert = [
            'product_id' => intval($product_id),
            'change_time' => current_time('mysql'),
            'price' => isset($price_data['price']) ? sanitize_text_field($price_data['price']) : null,
            'min_price' => isset($price_data['min_price']) ? sanitize_text_field($price_data['min_price']) : null,
            'max_price' => isset($price_data['max_price']) ? sanitize_text_field($price_data['max_price']) : null,
        ];

        // Only insert if at least one price value is provided
        if ($data_to_insert['price'] !== null || $data_to_insert['min_price'] !== null || $data_to_insert['max_price'] !== null) {
            $inserted = $wpdb->insert(CPP_DB_PRICE_HISTORY, $data_to_insert);
            if ($inserted) {
                 // Update the main product's last_updated_at timestamp
                 $wpdb->update(CPP_DB_PRODUCTS, ['last_updated_at' => current_time('mysql')], ['id' => intval($product_id)]);
                 return true;
            }
        }
         return false;
    }


    public static function get_chart_data($product_id, $months = 6) {
        global $wpdb;

        $product_id = intval($product_id);
        $months = intval($months);
        if ($product_id <= 0 || $months <= 0) {
            return ['labels' => [], 'prices' => [], 'min_prices' => [], 'max_prices' => []];
        }

        $disable_base_price = get_option('cpp_disable_base_price', 0);
        $labels = [];
        $prices = [];
        $min_prices = [];
        $max_prices = [];

        // Fetch all relevant history in one query
         $history = $wpdb->get_results($wpdb->prepare("
            SELECT price, min_price, max_price, change_time
            FROM " . CPP_DB_PRICE_HISTORY . "
            WHERE product_id = %d AND change_time >= DATE_SUB(NOW(), INTERVAL %d MONTH)
            ORDER BY change_time ASC
        ", $product_id, $months));

        if ($history) {
             foreach ($history as $row) {
                 $labels[] = date_i18n('Y/m/d H:i', strtotime($row->change_time)); // Include time for more detail
                 // Only add price if it's not disabled and the value exists
                 $prices[] = (!$disable_base_price && $row->price !== null) ? (float)str_replace(',', '', $row->price) : null;
                 $min_prices[] = ($row->min_price !== null) ? (float)str_replace(',', '', $row->min_price) : null;
                 $max_prices[] = ($row->max_price !== null) ? (float)str_replace(',', '', $row->max_price) : null;
             }
         } else {
             // If no history, maybe show current price/range?
             // $product = $wpdb->get_row($wpdb->prepare("SELECT price, min_price, max_price, last_updated_at FROM ".CPP_DB_PRODUCTS." WHERE id = %d", $product_id));
             // if($product){
             //    $labels[] = date_i18n('Y/m/d H:i', strtotime($product->last_updated_at));
             //    $prices[] = (!$disable_base_price && $product->price !== null) ? (float)str_replace(',', '', $product->price) : null;
             //    $min_prices[] = ($product->min_price !== null) ? (float)str_replace(',', '', $product->min_price) : null;
             //    $max_prices[] = ($product->max_price !== null) ? (float)str_replace(',', '', $product->max_price) : null;
             // }
         }


        // Filter out null values for datasets where appropriate? Or let Chart.js handle gaps?
         // Let Chart.js handle gaps by default (null values)


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

// --- شروع تغییر: هوک برای شروع Session ---
add_action('init', ['CPP_Core', 'init_session']);
// --- پایان تغییر ---

// --- شروع تغییر: افزودن اکشن AJAX برای کپچا ---
add_action('wp_ajax_cpp_get_captcha', 'cpp_ajax_get_captcha');
add_action('wp_ajax_nopriv_cpp_get_captcha', 'cpp_ajax_get_captcha');
function cpp_ajax_get_captcha() {
    // Nonce check is important here
    check_ajax_referer('cpp_front_nonce', 'nonce');

    CPP_Core::init_session(); // Ensure session is started

    // Generate 4-digit random number
    $captcha_code = rand(1000, 9999);

    // Store in session
    $_SESSION['cpp_captcha_code'] = $captcha_code;

    // Send the code back to JS
    wp_send_json_success(['code' => $captcha_code]);
    wp_die();
}
// --- پایان تغییر ---

add_action('wp_ajax_cpp_get_chart_data', 'cpp_ajax_get_chart_data');
add_action('wp_ajax_nopriv_cpp_get_chart_data', 'cpp_ajax_get_chart_data');
function cpp_ajax_get_chart_data() {
    // Added nonce check
     check_ajax_referer('cpp_front_nonce', 'nonce');

    $product_id = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;
    if (!$product_id) wp_send_json_error(__('Invalid Product ID', 'cpp-full'), 400);

    $data = CPP_Core::get_chart_data($product_id);

    // Check if there's any data to display
    if(empty($data['labels'])) {
        wp_send_json_error(__('No price history found for this product.', 'cpp-full'), 404);
    }
    wp_send_json_success($data);
    wp_die();
}


add_action('wp_ajax_cpp_submit_order', 'cpp_submit_order');
add_action('wp_ajax_nopriv_cpp_submit_order', 'cpp_submit_order');
function cpp_submit_order() {
    check_ajax_referer('cpp_front_nonce','nonce');
    global $wpdb;

    // --- شروع تغییر: اعتبارسنجی کپچا ---
    CPP_Core::init_session(); // Ensure session is started
    $user_captcha = isset($_POST['captcha_input']) ? sanitize_text_field($_POST['captcha_input']) : '';
    $session_captcha = isset($_SESSION['cpp_captcha_code']) ? $_SESSION['cpp_captcha_code'] : '';

    // Unset captcha immediately after retrieving to prevent reuse
    unset($_SESSION['cpp_captcha_code']);

    if (empty($user_captcha) || empty($session_captcha) || $user_captcha != $session_captcha) {
        wp_send_json_error(['message' => __('کد امنیتی وارد شده صحیح نیست.', 'cpp-full'), 'code' => 'captcha_error'], 400);
        wp_die();
    }
    // --- پایان تغییر ---

    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    $product = $wpdb->get_row($wpdb->prepare("SELECT name FROM " . CPP_DB_PRODUCTS . " WHERE id=%d AND is_active = 1", $product_id)); // Also check if active
    if (!$product) {
        wp_send_json_error(['message' => __('محصول انتخاب شده یافت نشد یا فعال نیست.', 'cpp-full')], 404);
        wp_die();
    }


    $customer_name = isset($_POST['customer_name']) ? sanitize_text_field(wp_unslash($_POST['customer_name'])) : '';
    $phone = isset($_POST['phone']) ? sanitize_text_field(wp_unslash($_POST['phone'])) : ''; // Consider stricter phone validation
    $qty = isset($_POST['qty']) ? sanitize_text_field(wp_unslash($_POST['qty'])) : '';
    $note = isset($_POST['note']) ? sanitize_textarea_field(wp_unslash($_POST['note'])) : '';

    // Basic validation for required fields
    if(empty($customer_name) || empty($phone) || empty($qty)){
        wp_send_json_error(['message' => __('لطفا تمام فیلدهای ستاره‌دار (نام، شماره تماس، مقدار) را پر کنید.', 'cpp-full')], 400);
        wp_die();
    }

    // Insert order into the database
    $inserted = $wpdb->insert(CPP_DB_ORDERS, [
        'product_id'    => $product_id,
        'product_name'  => $product->name,
        'customer_name' => $customer_name,
        'phone'         => $phone,
        'qty'           => $qty,
        'note'          => $note,
        'status'        => 'new_order', // Default status
        'created'       => current_time('mysql')
    ]);

    if (!$inserted) {
         wp_send_json_error(['message' => __('خطا در ثبت سفارش در دیتابیس.', 'cpp-full') . ' ' . $wpdb->last_error], 500);
         wp_die();
    }

    $order_id = $wpdb->insert_id; // Get the newly inserted order ID if needed

    // --- ارسال اعلان‌ها ---
    $placeholders = [
        '{product_name}'  => $product->name,
        '{customer_name}' => $customer_name,
        '{phone}'         => $phone,
        '{qty}'           => $qty,
        '{note}'          => $note,
        // '{order_id}' => $order_id, // Add order ID if needed in templates/patterns
    ];

    // ۱. ارسال ایمیل به مدیر
    if (class_exists('CPP_Full_Email')) {
        CPP_Full_Email::send_notification($placeholders); // Handles enable check internally
    }

    // ۲. ارسال پیامک به مدیر (با الگو)
    if (class_exists('CPP_Full_SMS')) {
        CPP_Full_SMS::send_notification($placeholders); // Handles enable/service check internally
    }

    // --- شروع تغییر: ارسال پیامک به مشتری (با الگو) ---
    if (class_exists('CPP_Full_SMS') && get_option('cpp_sms_customer_enable')) {
        $customer_pattern_code = get_option('cpp_sms_customer_pattern_code');
        $api_key = get_option('cpp_sms_api_key'); // Needed for the function call
        $sender = get_option('cpp_sms_sender');   // Needed for the function call

        if ($customer_pattern_code && $api_key && $sender) {
             // آماده سازی متغیرهای مورد نیاز الگوی مشتری (مثال)
            $customer_variables = [
                'customer_name' => $customer_name,
                'product_name'  => $product->name,
                // Add other variables based on the customer pattern you created
            ];
             // فراخوانی تابع ارسال الگو (همان تابع قبلی، فقط با پارامترهای متفاوت)
             CPP_Full_SMS::ippanel_send_pattern($api_key, $sender, $phone, $customer_pattern_code, $customer_variables);
             // We might not need to check the return value strictly here, just attempt to send.
        } else {
             error_log("CPP Customer SMS Error: Customer pattern code, API Key, or Sender is missing in settings.");
        }
    }
    // --- پایان تغییر ---

    wp_send_json_success(['message' => __('درخواست شما با موفقیت ثبت شد. همکاران ما به زودی با شما تماس خواهند گرفت.', 'cpp-full')]);
    wp_die();
}

// --- Add helper function for session start if needed elsewhere, although init hook is better ---
// function cpp_maybe_start_session(){
//     if (!session_id() && !headers_sent()) {
//        session_start();
//     }
// }

?>
