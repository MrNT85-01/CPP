<?php if (!defined('ABSPATH')) exit; ?>

<div id="cpp-order-modal" class="cpp-modal-overlay" style="display:none;">
    <div class="cpp-modal-container">
        <button class="cpp-modal-close">&times;</button>
        <h3><?php _e('ثبت سفارش برای:', 'cpp-full'); ?> <span class="cpp-modal-product-name"></span><span class="cpp-modal-product-location"></span></h3>
        <form id="cpp-order-form">
            <input type="hidden" name="product_id" id="cpp-order-product-id" value="">
            <div class="cpp-form-field">
                <label for="customer_name"><?php _e('نام و نام خانوادگی', 'cpp-full'); ?> <span class="required">*</span></label>
                <input type="text" name="customer_name" id="customer_name" required>
            </div>
            <div class="cpp-form-field">
                <label for="phone"><?php _e('شماره تماس', 'cpp-full'); ?> <span class="required">*</span></label>
                <input type="tel" name="phone" id="phone" required class="ltr" style="direction:ltr; text-align:left;">
            </div>
            <div class="cpp-form-field">
                 <label for="qty"><?php _e('مقدار/تعداد درخواستی', 'cpp-full'); ?> <span class="cpp-modal-product-unit"></span> <span class="required">*</span></label>
                 <input type="text" name="qty" id="qty" required>
            </div>
            <div class="cpp-form-field">
                <label for="note"><?php _e('توضیحات (اختیاری)', 'cpp-full'); ?></label>
                <textarea name="note" id="note" rows="3"></textarea>
            </div>

            <div class="cpp-form-field cpp-captcha-field">
                 <label for="captcha_input"><?php _e('کد امنیتی را وارد کنید:', 'cpp-full'); ?> <span class="required">*</span></label>
                 <div class="cpp-captcha-wrap">
                     <span class="cpp-captcha-code">----</span>
                     <button type="button" class="cpp-refresh-captcha" title="<?php esc_attr_e('کد جدید', 'cpp-full'); ?>">↺</button>
                     <input type="text" name="captcha_input" id="captcha_input" required maxlength="4" autocomplete="off" class="ltr" style="direction:ltr; text-align:center;">
                 </div>
                 <style>
                    .cpp-captcha-wrap { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
                    .cpp-captcha-code {
                        background-color: #f0f0f0; padding: 8px 15px; border-radius: 4px;
                        font-weight: bold; font-size: 1.2em; letter-spacing: 5px;
                        font-family: monospace; border: 1px solid #ccc;
                        min-width: 80px; text-align: center; user-select: none; /* Prevent text selection */
                    }
                    .cpp-refresh-captcha { background: none; border: none; font-size: 1.5em; cursor: pointer; color: #555; padding: 0 5px;}
                    .cpp-refresh-captcha:hover { color: #000; }
                    #captcha_input { width: 100px !important; flex-grow: 0; }
                    .cpp-modal-product-location::before { content: " - "; font-weight: normal; } /* Add separator */
                    .cpp-modal-product-unit::before { content: " ("; } /* Add parenthesis */
                    .cpp-modal-product-unit::after { content: ")"; }
                    .cpp-modal-product-unit:empty::before, .cpp-modal-product-unit:empty::after { content: ""; } /* Hide parenthesis if unit is empty */
                    .cpp-modal-product-location:empty::before { content: ""; } /* Hide separator if location is empty */
                 </style>
            </div>

            <div class="cpp-form-field">
                <button type="submit"><?php _e('ثبت درخواست', 'cpp-full'); ?></button>
            </div>
        </form>
    </div>
</div>

<div id="cpp-front-chart-modal" class="cpp-modal-overlay" style="display:none;">
     <div class="cpp-modal-container cpp-chart-container">
        <button class="cpp-modal-close">&times;</button>
        <h3><?php _e('نمودار تغییرات قیمت', 'cpp-full'); ?></h3>
        <div class="cpp-chart-inner">
            <canvas id="cppFrontPriceChart"></canvas>
        </div>
    </div>
</div>
