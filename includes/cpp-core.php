<?php
if (!defined('ABSPATH')) exit;

class CPP_Core {

    // تابع برای شروع Session در صورت نیاز
    public static function init_session() {
        if (!session_id() && !headers_sent()) {
            try {
                 @session_start(); // Suppress errors if session already started elsewhere
            } catch (Exception $e) {
                 error_log('CPP Error starting session: ' . $e->getMessage());
            }
        }
    }


    public static function create_db_tables() {
        global $wpdb;
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        $charset_collate = $wpdb->get_charset_collate();

        $sql1 = "CREATE TABLE " . CPP_DB_CATEGORIES . " ( id mediumint(9) NOT NULL AUTO_INCREMENT, name varchar(200) NOT NULL, slug varchar(200) NOT NULL, image_url varchar(255) DEFAULT '', created datetime DEFAULT CURRENT_TIMESTAMP NOT NULL, PRIMARY KEY  (id), UNIQUE KEY slug (slug) ) $charset_collate;"; // Added unique slug, NOT NULL timestamp
        $sql2 = "CREATE TABLE " . CPP_DB_PRODUCTS . " ( id mediumint(9) NOT NULL AUTO_INCREMENT, cat_id mediumint(9) NOT NULL, name varchar(200) NOT NULL, price varchar(50) DEFAULT '', min_price varchar(50) DEFAULT '', max_price varchar(50) DEFAULT '', product_type varchar(100) DEFAULT '', unit varchar(50) DEFAULT '', load_location varchar(200) DEFAULT '', is_active tinyint(1) DEFAULT 1, description text, image_url varchar(255) DEFAULT '', last_updated_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL, created datetime DEFAULT CURRENT_TIMESTAMP NOT NULL, PRIMARY KEY  (id), KEY cat_id (cat_id) ) $charset_collate;"; // NOT NULL timestamps
        $sql3 = "CREATE TABLE " . CPP_DB_ORDERS . " ( id mediumint(9) NOT NULL AUTO_INCREMENT, product_id mediumint(9) NOT NULL, product_name varchar(200) NOT NULL, customer_name varchar(200) NOT NULL, phone varchar(50) NOT NULL, qty varchar(50) NOT NULL, note text, admin_note text, status varchar(50) DEFAULT 'new_order', created datetime DEFAULT CURRENT_TIMESTAMP NOT NULL, PRIMARY KEY  (id), KEY product_id (product_id), KEY phone (phone) ) $charset_collate;"; // NOT NULL timestamp
        $sql4 = "CREATE TABLE " . CPP_DB_PRICE_HISTORY . " ( id bigint(20) NOT NULL AUTO_INCREMENT, product_id mediumint(9) NOT NULL, price varchar(50) DEFAULT NULL, min_price varchar(50) DEFAULT NULL, max_price varchar(50) DEFAULT NULL, change_time datetime DEFAULT CURRENT_TIMESTAMP NOT NULL, PRIMARY KEY (id), KEY product_id (product_id) ) $charset_collate;"; // NOT NULL timestamp

        dbDelta($sql1);
        dbDelta($sql2);
        dbDelta($sql3);
        dbDelta($sql4);

        // --- Add upgrade logic if needed ---
        $table_name_history = CPP_DB_PRICE_HISTORY;
        if($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name_history)) == $table_name_history) {
             $history_columns = $wpdb->get_col("DESC `{$table_name_history}`");
             if(!in_array('min_price', $history_columns)) {
                 $wpdb->query("ALTER TABLE `{$table_name_history}` ADD `min_price` VARCHAR(50) DEFAULT NULL AFTER `price`");
             }
             if(!in_array('max_price', $history_columns)) {
                 $wpdb->query("ALTER TABLE `{$table_name_history}` ADD `max_price` VARCHAR(50) DEFAULT NULL AFTER `min_price`");
             }
             // Ensure change_time is not NULL
             $wpdb->query("ALTER TABLE `{$table_name_history}` MODIFY `change_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP");
        }
         // Ensure other timestamps are not NULL
        $wpdb->query("ALTER TABLE `" . CPP_DB_CATEGORIES . "` MODIFY `created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP");
        $wpdb->query("ALTER TABLE `" . CPP_DB_PRODUCTS . "` MODIFY `created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP");
        $wpdb->query("ALTER TABLE `" . CPP_DB_PRODUCTS . "` MODIFY `last_updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP");
        $wpdb->query("ALTER TABLE `" . CPP_DB_ORDERS . "` MODIFY `created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP");


    }

    /**
     * Save price history. Now accepts only the new value for the specific field changed.
     * @param int $product_id
     * @param string $new_value The new value for price, min_price, or max_price
     * @param string $field_name The name of the field being changed ('price', 'min_price', 'max_price')
     * @return bool
     */
     public static function save_price_history($product_id, $new_value, $field_name = 'price') {
         global $wpdb;
         $product_id = intval($product_id);
         if (!$product_id || !in_array($field_name, ['price', 'min_price', 'max_price'])) {
             return false;
         }

         $data_to_insert = [
             'product_id' => $product_id,
             'change_time' => current_time('mysql'),
             'price' => null, // Default to null
             'min_price' => null,
             'max_price' => null,
         ];
         // Set the specific field that changed
         $data_to_insert[$field_name] = sanitize_text_field($new_value);


         $inserted = $wpdb->insert(CPP_DB_PRICE_HISTORY, $data_to_insert);

         if ($inserted) {
              // Update the main product's last_updated_at timestamp regardless of which price changed
              $wpdb->update(CPP_DB_PRODUCTS, ['last_updated_at' => current_time('mysql')], ['id' => $product_id]);
              return true;
         }
          return false;
     }

    // ... (توابع get_chart_data, get_all_categories, get_all_orders بدون تغییر) ...
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
             $last_price = null;
             $last_min = null;
             $last_max = null;
             foreach ($history as $row) {
                 $labels[] = date_i18n('Y/m/d H:i', strtotime($row->change_time)); // Include time for more detail

                 // Carry forward the last known value if the current row doesn't have one
                 if ($row->price !== null) $last_price = (float)str_replace(',', '', $row->price);
                 if ($row->min_price !== null) $last_min = (float)str_replace(',', '', $row->min_price);
                 if ($row->max_price !== null) $last_max = (float)str_replace(',', '', $row->max_price);

                 $prices[] = (!$disable_base_price) ? $last_price : null;
                 $min_prices[] = $last_min;
                 $max_prices[] = $last_max;
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
        // Ensure table exists before querying
        if($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", CPP_DB_CATEGORIES)) != CPP_DB_CATEGORIES) {
            return [];
        }
        return $wpdb->get_results("SELECT id, name, slug, image_url, created FROM " . CPP_DB_CATEGORIES . " ORDER BY name ASC");
    }

    public static function get_all_orders() {
        global $wpdb;
         // Ensure table exists before querying
        if($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", CPP_DB_ORDERS)) != CPP_DB_ORDERS) {
            return [];
        }
        return $wpdb->get_results("SELECT * FROM " . CPP_DB_ORDERS . " ORDER BY created DESC");
    }

} // End CPP_Core Class


// هوک برای شروع Session در هر بار بارگذاری وردپرس (قبل از ارسال هدرها)
add_action('init', ['CPP_Core', 'init_session'], 1); // Run early


// اکشن AJAX برای دریافت کد کپچا
add_action('wp_ajax_cpp_get_captcha', 'cpp_ajax_get_captcha');
add_action('wp_ajax_nopriv_cpp_get_captcha', 'cpp_ajax_get_captcha');
function cpp_ajax_get_captcha() {
    // Nonce check is crucial
    check_ajax_referer('cpp_front_nonce', 'nonce');

    CPP_Core::init_session(); // Ensure session is started

    // Generate 4-digit random number
    $captcha_code = rand(1000, 9999);

    // Store in session - use a specific key
    $_SESSION['cpp_captcha_code'] = (string) $captcha_code; // Store as string

    // Send the code back to JS (for display)
    wp_send_json_success(['code' => (string) $captcha_code]); // Send as string
    wp_die();
}

// اکشن AJAX برای دریافت داده نمودار
add_action('wp_ajax_cpp_get_chart_data', 'cpp_ajax_get_chart_data');
add_action('wp_ajax_nopriv_cpp_get_chart_data', 'cpp_ajax_get_chart_data');
function cpp_ajax_get_chart_data() {
    // Added nonce check
     check_ajax_referer('cpp_front_nonce', 'nonce'); // Check nonce from GET/POST

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

// اکشن AJAX برای ثبت سفارش
add_action('wp_ajax_cpp_submit_order', 'cpp_submit_order');
add_action('wp_ajax_nopriv_cpp_submit_order', 'cpp_submit_order');
function cpp_submit_order() {
    check_ajax_referer('cpp_front_nonce','nonce');
    global $wpdb;

    // --- اعتبارسنجی کپچا ---
    CPP_Core::init_session(); // Ensure session is started
    $user_captcha = isset($_POST['captcha_input']) ? trim(sanitize_text_field($_POST['captcha_input'])) : '';
    $session_captcha = isset($_SESSION['cpp_captcha_code']) ? $_SESSION['cpp_captcha_code'] : '';
    error_log("CAPTCHA Check: User entered '".$user_captcha."', Session expected '".$session_captcha."'"); // Debug log

    // Unset captcha immediately after retrieving to prevent reuse ONLY IF IT MATCHES
    // If it doesn't match, keep it for potential retry display (though JS refreshes anyway)
    // unset($_SESSION['cpp_captcha_code']); // Moved lower

    if (empty($user_captcha) || empty($session_captcha) || $user_captcha !== $session_captcha) {
         // Optionally, keep the session captcha for one retry if desired, but refreshing is usually better.
         // unset($_SESSION['cpp_captcha_code']); // Unset even on failure to force refresh
        wp_send_json_error(['message' => __('کد امنیتی وارد شده صحیح نیست.', 'cpp-full'), 'code' => 'captcha_error'], 400);
        wp_die();
    }
     // Captcha matched, unset it now
     unset($_SESSION['cpp_captcha_code']);


    // --- اعتبارسنجی سایر فیلدها ---
    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    $product = $wpdb->get_row($wpdb->prepare("SELECT name FROM " . CPP_DB_PRODUCTS . " WHERE id=%d AND is_active = 1", $product_id));
    if (!$product) {
        wp_send_json_error(['message' => __('محصول انتخاب شده یافت نشد یا فعال نیست.', 'cpp-full')], 404);
        wp_die();
    }

    $customer_name = isset($_POST['customer_name']) ? sanitize_text_field(wp_unslash($_POST['customer_name'])) : '';
    $phone = isset($_POST['phone']) ? sanitize_text_field(wp_unslash($_POST['phone'])) : '';
    $qty = isset($_POST['qty']) ? sanitize_text_field(wp_unslash($_POST['qty'])) : '';
    $note = isset($_POST['note']) ? sanitize_textarea_field(wp_unslash($_POST['note'])) : '';

    if(empty($customer_name) || empty($phone) || empty($qty)){
        wp_send_json_error(['message' => __('لطفا تمام فیلدهای ستاره‌دار (نام، شماره تماس، مقدار) را پر کنید.', 'cpp-full')], 400);
        wp_die();
    }
    // Simple phone validation (starts with 09, total 11 digits) - adjust if needed
    if (!preg_match('/^09[0-9]{9}$/', $phone)) {
       // wp_send_json_error(['message' => __('لطفا شماره تماس معتبر (مانند 0912...) وارد کنید.', 'cpp-full')], 400);
       // wp_die();
       // OR allow other formats but log a warning
       error_log("CPP Order Warning: Phone number format might be invalid: ".$phone);
    }


    // --- ثبت سفارش در دیتابیس ---
    $inserted = $wpdb->insert(CPP_DB_ORDERS, [
        'product_id'    => $product_id,
        'product_name'  => $product->name,
        'customer_name' => $customer_name,
        'phone'         => $phone,
        'qty'           => $qty,
        'note'          => $note,
        'status'        => 'new_order',
        'created'       => current_time('mysql', 1) // Use GMT time for DB consistency
    ], ['%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s']); // Specify formats


    if (!$inserted) {
         wp_send_json_error(['message' => __('خطا در ثبت سفارش در دیتابیس.', 'cpp-full') . ' ' . $wpdb->last_error], 500);
         wp_die();
    }

    $order_id = $wpdb->insert_id;

    // --- ارسال اعلان‌ها ---
    $placeholders = [
        '{product_name}'  => $product->name,
        '{customer_name}' => $customer_name,
        '{phone}'         => $phone,
        '{qty}'           => $qty,
        '{note}'          => $note,
    ];

    // ۱. ارسال ایمیل به مدیر
    if (get_option('cpp_enable_email') && class_exists('CPP_Full_Email')) {
        CPP_Full_Email::send_notification($placeholders);
    }

    // ۲. ارسال پیامک به مدیر (با الگو)
    if (get_option('cpp_sms_service') === 'ippanel' && class_exists('CPP_Full_SMS')) {
        CPP_Full_SMS::send_notification($placeholders); // This now only handles admin SMS
    }

    // ۳. ارسال پیامک به مشتری (با الگو)
    if (get_option('cpp_sms_service') === 'ippanel' && get_option('cpp_sms_customer_enable') && class_exists('CPP_Full_SMS')) {
        $customer_pattern_code = get_option('cpp_sms_customer_pattern_code');
        $api_key = get_option('cpp_sms_api_key');
        $sender = get_option('cpp_sms_sender');

        if ($customer_pattern_code && $api_key && $sender) {
             // آماده سازی متغیرهای مورد نیاز الگوی مشتری
            $customer_variables = [
                'customer_name' => $customer_name,
                'product_name'  => $product->name,
                // Add other variables if your customer pattern needs them
            ];
             // فراخوانی تابع ارسال الگو با اطلاعات مشتری
             CPP_Full_SMS::ippanel_send_pattern($api_key, $sender, $phone, $customer_pattern_code, $customer_variables);
             // Log attempt, maybe not success/failure unless needed
             error_log("Attempted to send customer SMS for order ID: ".$order_id." to ".$phone);
        } else {
             error_log("CPP Customer SMS Error for Order ID ".$order_id.": Customer pattern code, API Key, or Sender is missing in settings.");
        }
    }

    // --- پاسخ موفقیت آمیز ---
    wp_send_json_success(['message' => __('درخواست شما با موفقیت ثبت شد. همکاران ما به زودی با شما تماس خواهند گرفت.', 'cpp-full')]);
    wp_die();
}

?>
