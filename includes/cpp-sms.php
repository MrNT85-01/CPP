<?php
if (!defined('ABSPATH')) exit;

class CPP_Full_SMS {
    /**
     * ارسال پیامک اعلان سفارش جدید به مدیر با جایگزینی متغیرها در الگو
     * @param array $placeholders شامل: {product_name}, {customer_name}, {phone}, {qty}, {note}
     */
    public static function send_notification($placeholders){
        $service    = get_option('cpp_sms_service');
        $apiKey     = get_option('cpp_sms_api_key');
        $sender     = get_option('cpp_sms_sender');
        $adminPhone = get_option('cpp_admin_phone');
        $pattern_code = get_option('cpp_sms_pattern_code');

        // بررسی می‌کنیم که سرویس IPPanel فعال باشد و همه مقادیر لازم پر شده باشند
        if (!$service || $service !== 'ippanel' || !$apiKey || !$adminPhone || !$sender || !$pattern_code) {
            return false;
        }

        // --- تبدیل متغیرهای افزونه ({key}) به متغیرهای الگو (key) ---
        $variables = [];
        foreach ($placeholders as $key => $value) {
            $new_key = str_replace(['{', '}'], '', $key);
            $variables[$new_key] = $value;
        }

        // --- فقط تابع ارسال الگوی IPPanel فراخوانی می‌شود ---
        return self::ippanel_send_pattern($apiKey, $sender, $adminPhone, $pattern_code, $variables);
    }

    /**
     * تابع ارسال پیامک با الگوی IPPanel با استفاده از wp_remote_post
     * @param string $apiKey
     * @param string $sender
     * @param string $to
     * @param string $pattern_code
     * @param array $variables آرایه‌ای از متغیرهای الگو به شکل ['var_name' => 'value']
     * @return bool True on success, False on failure
     */
    // --- تغییر: تابع عمومی شده تا برای مشتری هم استفاده شود ---
    public static function ippanel_send_pattern($apiKey, $sender, $to, $pattern_code, $variables){

        $url = 'https://api2.ippanel.com/api/v1/sms/pattern/normal/send';
        $data = [
            'code'      => $pattern_code,
            'sender'    => $sender,
            'recipient' => $to, // گیرنده باید فقط یک شماره باشد
            'variable'  => $variables,
        ];

        // اطمینان از اینکه recipient آرایه نباشد و پاکسازی شماره
        if (is_array($data['recipient'])) {
             $data['recipient'] = $data['recipient'][0];
        }
        $data['recipient'] = preg_replace('/[^0-9+]/', '', $data['recipient']); // Keep only digits and +

        $body = json_encode($data);
        $headers = [
            'Content-Type' => 'application/json',
            'apikey'       => $apiKey
        ];

        $args = [
            'body'        => $body,
            'headers'     => $headers,
            'method'      => 'POST',
            'data_format' => 'body',
            'timeout'     => 20,
        ];

        $response = wp_remote_post($url, $args);

        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            error_log('CPP IPPanel WP HTTP Error (Pattern Send to '.$to.'): ' . $error_message);
            return false;
        } else {
            $http_code = wp_remote_retrieve_response_code($response);
            $response_body = wp_remote_retrieve_body($response);

            if ($http_code >= 200 && $http_code < 300) {
                $result = json_decode($response_body);
                // بررسی دقیق‌تر پاسخ موفق IPPanel
                if ($result && isset($result->data->message_id)) {
                    error_log('CPP IPPanel SMS Sent Successfully to '.$to.'. Message ID: '.$result->data->message_id); // Log success
                    return true; // ارسال موفقیت آمیز بود
                } else {
                     $api_error = isset($result->status->message) ? $result->status->message : 'Unknown API Logic Error';
                     error_log('CPP IPPanel API Logic Error (Pattern Send to '.$to.'): ' . $api_error . ' | Response: ' . $response_body);
                     return false;
                }
            } else {
                error_log('CPP IPPanel HTTP Error (Pattern Send to '.$to.'): Code ' . $http_code . ' | Response: ' . $response_body);
                return false;
            }
        }
    }
}
?>
