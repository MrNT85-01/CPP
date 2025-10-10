<?php if (!defined('ABSPATH')) exit; ?>

<div id="cpp-order-modal" class="cpp-modal-overlay" style="display:none;">
    <div class="cpp-modal-container">
        <button class="cpp-modal-close">&times;</button>
        <h3>ثبت سفارش برای: <span class="cpp-modal-product-name"></span></h3>
        <form id="cpp-order-form">
            <input type="hidden" name="product_id" id="cpp-order-product-id" value="">
            <div class="cpp-form-field">
                <label for="customer_name">نام و نام خانوادگی <span class="required">*</span></label>
                <input type="text" name="customer_name" id="customer_name" required>
            </div>
            <div class="cpp-form-field">
                <label for="phone">شماره تماس <span class="required">*</span></label>
                <input type="tel" name="phone" id="phone" required>
            </div>
            <div class="cpp-form-field">
                <label for="qty">مقدار/تعداد درخواستی <span class="required">*</span></label>
                <input type="text" name="qty" id="qty" required>
            </div>
            <div class="cpp-form-field">
                <label for="note">توضیحات (اختیاری)</label>
                <textarea name="note" id="note" rows="4"></textarea>
            </div>
            <div class="cpp-form-field">
                <button type="submit">ثبت درخواست</button>
            </div>
        </form>
    </div>
</div>

<div id="cpp-front-chart-modal" class="cpp-modal-overlay" style="display:none;">
     <div class="cpp-modal-container cpp-chart-container">
        <button class="cpp-modal-close">&times;</button>
        <h3>نمودار تغییرات قیمت</h3>
        <div class="cpp-chart-inner">
            <canvas id="cppFrontPriceChart"></canvas>
        </div>
    </div>
</div>