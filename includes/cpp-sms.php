<?php
if (!defined('ABSPATH')) exit;

class CPP_Full_SMS {
    /**
     * ارسال پیامک اعلان سفارش جدید به مدیر با جایگزینی متغیرها در قالب
     * @param array $placeholders شامل: {product_name}, {customer_name}, {phone}, {qty}, {note}
     */
    public static function send_notification($placeholders){
        $service    = get_option('cpp_sms_service');
        $apiKey     = get_option('cpp_sms_api_key');
        $sender     = get_option('cpp_sms_sender');
        $adminPhone = get_option('cpp_admin_phone');
        
        // بررسی می‌کنیم که سرویس حتما ippanel باشد و مقادیر خالی نباشند
        if (!$service || !$apiKey || !$adminPhone || !$sender || $service !== 'ippanel') return false;

        $sms_text_template = get_option('cpp_sms_text_template', "سفارش جدید: {product_name} - {customer_name} - {phone}");

        // --- جایگزینی متغیرها ---
        $keys = array_keys($placeholders);
        $values = array_values($placeholders);

        $message_to_send = str_replace($keys, $values, $sms_text_template);
        
        // --- منطق ارسال پیامک ---
        switch($service){
            case 'melipayamak': return self::melipayamak_send($apiKey,$sender,$adminPhone,$message_to_send);
            case 'kavenegar': return self::kavenegar_send($apiKey,$sender,$adminPhone,$message_to_send);
            case 'ippanel': return self::ippanel_send($apiKey,$sender,$adminPhone,$message_to_send);
            default: return false;
        }
    }
    
    // --- توابع کمکی ارسال پیامک (نمونه) ---
    private static function melipayamak_send($apiKey,$sender,$to,$text){
        // TODO: پیاده‌سازی اتصال به وب سرویس ملی پیامک
        return true; 
    }
    private static function kavenegar_send($apiKey,$sender,$to,$text){
        // TODO: پیاده‌سازی اتصال به وب سرویس کاوه نگار
        return true; 
    }

    // --- شروع تغییر: پیاده‌سازی کامل تابع ippanel_send با cURL ---
    private static function ippanel_send($apiKey, $sender, $to, $text){
        
        // ۱. اطلاعات مورد نیاز برای ارسال
        // این آدرس از فایل Client.php در SDK استخراج شده است
        $url = 'https://api2.ippanel.com/api/v1/sms/send/webservice/single';

        // ۲. ساختار بدنه درخواست (JSON)
        // این ساختار از متد send در Client.php استخراج شده است
        $data = [
            'sender' => $sender,
            'recipient' => [$to], // گیرنده باید به صورت آرایه باشد
            'message' => $text,
            'description' => [
                'summary' => 'CPP Order Notification',
                'count_recipient' => '1'
            ]
        ];
        $body = json_encode($data);

        // ۳. ساخت هدرهای درخواست
        $headers = [
            'Content-Type: application/json',
            'apikey: ' . $apiKey
        ];

        // ۴. ارسال درخواست با cURL
        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);

            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curl_error = curl_error($ch);
            curl_close($ch);

            // ۵. بررسی خطا
            if ($curl_error) {
                error_log('CPP IPPanel cURL Error: ' . $curl_error);
                return false;
            }

            // اگر ارسال موفقیت آمیز باشد (کدهای 200 یا 201)
            if ($http_code == 200 || $http_code == 201) {
                $result = json_decode($response);
                // اگر شناسه پیامک وجود داشت، موفق بوده است
                if (isset($result->data->message_id)) {
                    return true;
                } else {
                    // خطای منطقی از سمت پنل
                    error_log('CPP IPPanel API Logic Error: ' . $response);
                    return false;
                }
            } else {
                // خطای HTTP (مثل خطای 401 عدم دسترسی یا 422)
                error_log('CPP IPPanel HTTP Error: Code ' . $http_code . ' | Response: ' . $response);
                return false;
            }

        } catch (Exception $e) {
            error_log('CPP IPPanel General Error: ' . $e->getMessage());
            return false;
        }
    }
    // --- پایان تغییر ---
}
?>
