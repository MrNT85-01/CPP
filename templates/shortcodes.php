<?php
if (!defined('ABSPATH')) exit;
?>

<div class="wrap">
    <h1><?php _e('راهنمای شورت‌کدهای افزونه', 'cpp-full'); ?></h1>
    <p class="description"><?php _e('شما می‌توانید از شورت‌کدهای زیر برای نمایش لیست محصولات در صفحات و نوشته‌های خود استفاده کنید:', 'cpp-full'); ?></p>
    
    <hr>
    
    <h2>۱. نمایش لیست محصولات (نمای جدولی)</h2>
    <p>این شورت‌کد تمامی محصولات فعال را به ترتیب ID و در یک جدول استاندارد نمایش می‌دهد.</p>
    <div class="cpp-shortcode-example">
        <code>[cpp_products_list]</code>
    </div>

    <hr>
    
    <h2>۲. نمایش محصولات (نمای گرید با فیلتر)</h2>
    <p>این شورت‌کد محصولات را در یک نمای گرافیکی شبیه به تصویر نمونه، همراه با دکمه‌های فیلتر بر اساس دسته‌بندی نمایش می‌دهد.</p>
    <div class="cpp-shortcode-example">
        <code>[cpp_products_grid_view]</code>
    </div>

    <hr>

    <h2>۳. پارامترهای پیشرفته برای شورت‌کد <code>[cpp_products_list]</code></h2>
    <p>شما می‌توانید شورت‌کد جدولی را با پارامترهای زیر سفارشی‌سازی کنید:</p>

    <h3>الف. نمایش محصولات بر اساس دسته‌بندی</h3>
    <p>با استفاده از پارامتر <code>cat_id</code> می‌توانید محصولات یک یا چند دسته‌بندی خاص را نمایش دهید. ID دسته‌بندی را از جدول "مدیریت دسته‌بندی‌ها" پیدا کنید.</p>
    <div class="cpp-shortcode-example">
        <p><strong>مثال ۱:</strong> نمایش محصولات دسته‌بندی با ID=12</p>
        <code>[cpp_products_list cat_id="12"]</code>
        <p><strong>مثال ۲:</strong> نمایش محصولات دسته‌بندی‌های با IDهای 12 و 15</p>
        <code>[cpp_products_list cat_id="12,15"]</code>
    </div>

    <h3>ب. نمایش محصولات بر اساس IDهای خاص</h3>
    <p>با استفاده از پارامتر <code>ids</code> می‌توانید محصولات خاصی را که ID آن‌ها را می‌دانید، نمایش دهید.</p>
    <div class="cpp-shortcode-example">
        <code>[cpp_products_list ids="5,8,1"]</code>
    </div>

    <h3>ج. فیلتر نمایش بر اساس فعال/غیرفعال بودن</h3>
    <p>به صورت پیش‌فرض، فقط محصولات **فعال** (<code>is_active=1</code>) نمایش داده می‌شوند. اگر می‌خواهید همه محصولات یا فقط غیرفعال‌ها را نمایش دهید، از پارامتر <code>status</code> استفاده کنید.</p>
    <div class="cpp-shortcode-example">
        <p><strong>مثال:</strong> نمایش همه محصولات (فعال و غیرفعال)</p>
        <code>[cpp_products_list status="all"]</code>
    </div>

    <style>
        .cpp-shortcode-example {
            background-color: #f3f4f6;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border: 1px solid #e0e0e0;
        }
        .cpp-shortcode-example code {
            display: inline-block;
            background-color: #ffffff;
            padding: 5px 10px;
            border: 1px dashed #ccc;
            font-size: 1.1em;
            font-weight: bold;
        }
    </style>
</div>
