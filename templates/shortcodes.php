<?php
if (!defined('ABSPATH')) exit;
?>

<div class="wrap">
    <h1><?php _e('راهنمای شورت‌کدهای افزونه', 'cpp-full'); ?></h1>
    <p class="description"><?php _e('شما می‌توانید از شورت‌کدهای زیر برای نمایش لیست محصولات در صفحات و نوشته‌های خود استفاده کنید:', 'cpp-full'); ?></p>
    
    <hr>
    
    <h2><?php _e('۱. نمایش لیست محصولات (نمای جدولی)', 'cpp-full'); ?></h2>
    <p><?php _e('این شورت‌کد تمامی محصولات فعال را به ترتیب ID و در یک جدول استاندارد نمایش می‌دهد.', 'cpp-full'); ?></p>
    <div class="cpp-shortcode-example">
        <code>[cpp_products_list]</code>
    </div>

    <hr>
    
    <h2><?php _e('۲. نمایش محصولات (نمای گرید با فیلتر)', 'cpp-full'); ?></h2>
    <p><?php _e('این شورت‌کد محصولات را در یک نمای گرافیکی، همراه با دکمه‌های فیلتر بر اساس دسته‌بندی نمایش می‌دهد. این نما شامل ستون «آخرین بروزرسانی» برای هر محصول است.', 'cpp-full'); ?></p>
    <div class="cpp-shortcode-example">
        <code>[cpp_products_grid_view]</code>
    </div>

    <hr>

    <h2><?php _e('۳. نمایش محصولات (نمای گرید بدون ستون تاریخ)', 'cpp-full'); ?></h2>
    <p><?php _e('این شورت‌کد نیز محصولات را در نمای گرید نمایش می‌دهد، اما ستون «آخرین بروزرسانی» را ندارد. به جای آن، تاریخ و ساعت جدیدترین بروزرسانی در کل محصولات، به صورت یک دکمه در کنار فیلترها نمایش داده می‌شود.', 'cpp-full'); ?></p>
    <div class="cpp-shortcode-example">
        <code>[cpp_products_grid_view_no_date]</code>
    </div>

    <hr>

    <h2><?php _e('۴. پارامترهای پیشرفته برای شورت‌کد', 'cpp-full'); ?> <code>[cpp_products_list]</code></h2>
    <p><?php _e('شما می‌توانید شورت‌کد جدولی را با پارامترهای زیر سفارشی‌سازی کنید:', 'cpp-full'); ?></p>

    <h3><?php _e('الف. نمایش محصولات بر اساس دسته‌بندی', 'cpp-full'); ?></h3>
    <p><?php _e('با استفاده از پارامتر <code>cat_id</code> می‌توانید محصولات یک یا چند دسته‌بندی خاص را نمایش دهید. ID دسته‌بندی را از جدول "مدیریت دسته‌بندی‌ها" پیدا کنید.', 'cpp-full'); ?></p>
    <div class="cpp-shortcode-example">
        <p><strong><?php _e('مثال ۱:', 'cpp-full'); ?></strong> <?php _e('نمایش محصولات دسته‌بندی با ID=12', 'cpp-full'); ?></p>
        <code>[cpp_products_list cat_id="12"]</code>
        <p><strong><?php _e('مثال ۲:', 'cpp-full'); ?></strong> <?php _e('نمایش محصولات دسته‌بندی‌های با IDهای 12 و 15', 'cpp-full'); ?></p>
        <code>[cpp_products_list cat_id="12,15"]</code>
    </div>

    <h3><?php _e('ب. نمایش محصولات بر اساس IDهای خاص', 'cpp-full'); ?></h3>
    <p><?php _e('با استفاده از پارامتر <code>ids</code> می‌توانید محصولات خاصی را که ID آن‌ها را می‌دانید، نمایش دهید.', 'cpp-full'); ?></p>
    <div class="cpp-shortcode-example">
        <code>[cpp_products_list ids="5,8,1"]</code>
    </div>

    <h3><?php _e('ج. فیلتر نمایش بر اساس فعال/غیرفعال بودن', 'cpp-full'); ?></h3>
    <p><?php _e('به صورت پیش‌فرض، فقط محصولات **فعال** (<code>is_active=1</code>) نمایش داده می‌شوند. اگر می‌خواهید همه محصولات یا فقط غیرفعال‌ها را نمایش دهید، از پارامتر <code>status</code> استفاده کنید.', 'cpp-full'); ?></p>
    <div class="cpp-shortcode-example">
        <p><strong><?php _e('مثال:', 'cpp-full'); ?></strong> <?php _e('نمایش همه محصولات (فعال و غیرفعال)', 'cpp-full'); ?></p>
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
