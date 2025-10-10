<?php
if (!defined('ABSPATH')) exit;
global $wpdb;

// دریافت دسته‌بندی‌ها برای فیلد آبشاری
$categories = CPP_Core::get_all_categories(); 
$products = $wpdb->get_results("SELECT p.*, c.name as category_name FROM " . CPP_DB_PRODUCTS . " p LEFT JOIN " . CPP_DB_CATEGORIES . " c ON p.cat_id = c.id ORDER BY p.id DESC");

// لیست وضعیت‌ها برای فیلد فعال/غیرفعال (برای استفاده در جاوا اسکریپت)
$status_options = [
    '1' => __('فعال', 'cpp-full'),
    '0' => __('غیرفعال', 'cpp-full'),
];

// تعیین URL تصویر پیش‌فرض (لوگوی سایت یا تصویر موجود در افزونه)
$default_image_url = CPP_ASSETS_URL . 'images/default-product.png';
$site_icon = get_site_icon_url(100);
if ($site_icon) {
    $default_image_url = $site_icon;
}
?>

<div class="wrap">
    <h1><?php _e('مدیریت محصولات', 'cpp-full'); ?></h1>

    <?php 
    // نمایش پیام‌های پس از عملیات افزودن/حذف
    if (isset($_GET['cpp_message'])) {
        $message_key = sanitize_key($_GET['cpp_message']);
        $messages = [
            'product_added' => [ 'type' => 'success', 'text' => __('محصول جدید با موفقیت اضافه شد.', 'cpp-full') ],
            'product_add_failed' => [ 'type' => 'error', 'text' => __('خطا در اضافه کردن محصول. لطفاً دوباره امتحان کنید.', 'cpp-full') ],
            'product_deleted' => [ 'type' => 'success', 'text' => __('محصول با موفقیت حذف شد.', 'cpp-full') ],
            'product_delete_failed' => [ 'type' => 'error', 'text' => __('خطا در حذف محصول.', 'cpp-full') ],
        ];
        if (isset($messages[$message_key])) {
            echo '<div class="notice notice-' . $messages[$message_key]['type'] . ' is-dismissible"><p>' . $messages[$message_key]['text'] . '</p></div>';
        }
    }
    ?>

    <div class="notice notice-info">
        <p>
            <?php _e('برای ویرایش سریع اطلاعات محصول، روی سلول مورد نظر **دوبار کلیک (Double Click)** کنید.', 'cpp-full'); ?>
        </p>
    </div>

    <div class="cpp-accordion-wrap">
        <h2 class="cpp-accordion-header"><?php _e('➕ افزودن محصول جدید', 'cpp-full'); ?></h2>
        <div class="cpp-accordion-content">
            <form method="post" id="cpp-add-product-form">
                <?php wp_nonce_field('cpp_add_product_action', 'cpp_add_product_nonce'); ?>
                <table class="form-table">
                    <tr>
                        <th><?php _e('نام محصول', 'cpp-full'); ?></th>
                        <td><input type="text" name="name" required class="regular-text"></td>
                        <th><?php _e('دسته‌بندی', 'cpp-full'); ?></th>
                        <td>
                            <select name="cat_id" required>
                                <option value=""><?php _e('انتخاب کنید', 'cpp-full'); ?></option>
                                <?php foreach ($categories as $cat) : ?>
                                    <option value="<?php echo $cat->id; ?>"><?php echo $cat->name; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('نوع', 'cpp-full'); ?></th>
                        <td><input type="text" name="product_type" class="regular-text" placeholder="<?php _e('مثال: میلگرد، ورق', 'cpp-full'); ?>"></td>
                        <th><?php _e('واحد', 'cpp-full'); ?></th>
                        <td><input type="text" name="unit" class="regular-text" placeholder="<?php _e('مثال: تن، کیلوگرم', 'cpp-full'); ?>"></td>
                    </tr>
                    <tr>
                        <th><?php _e('محل بارگیری', 'cpp-full'); ?></th>
                        <td><input type="text" name="load_location" class="regular-text" placeholder="<?php _e('مثال: اصفهان، تهران', 'cpp-full'); ?>"></td>
                        <th><?php _e('وضعیت', 'cpp-full'); ?></th>
                        <td>
                            <select name="is_active">
                                <option value="1"><?php _e('فعال', 'cpp-full'); ?></option>
                                <option value="0"><?php _e('غیرفعال', 'cpp-full'); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('قیمت پایه/استاندارد', 'cpp-full'); ?></th>
                        <td><input type="text" name="price" required class="regular-text" placeholder="<?php _e('قیمتی که به صورت پیش‌فرض نمایش داده می‌شود.', 'cpp-full'); ?>"></td>
                        <th><?php _e('بازه قیمت (حداقل - حداکثر)', 'cpp-full'); ?></th>
                        <td>
                            <input type="text" name="min_price" class="small-text" placeholder="<?php _e('حداقل', 'cpp-full'); ?>">
                            -
                            <input type="text" name="max_price" class="small-text" placeholder="<?php _e('حداکثر', 'cpp-full'); ?>">
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('عکس محصول', 'cpp-full'); ?></th>
                        <td colspan="3">
                            <input type="text" name="image_url" id="product_image_url" class="regular-text" placeholder="<?php _e('آدرس تصویر', 'cpp-full'); ?>">
                            <button type="button" class="button cpp-upload-btn"><?php _e('انتخاب تصویر', 'cpp-full'); ?></button>
                            <div class="cpp-image-preview"></div>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('توضیحات', 'cpp-full'); ?></th>
                        <td colspan="3"><textarea name="description" rows="5" class="large-text"></textarea></td>
                    </tr>
                </table>
                <p class="submit"><input type="submit" name="cpp_add_product" id="submit" class="button button-primary" value="<?php _e('افزودن محصول', 'cpp-full'); ?>"></p>
            </form>
        </div>
    </div>

    <h2 class="title"><?php _e('لیست محصولات', 'cpp-full'); ?></h2>
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th scope="col">ID</th>
                <th scope="col"><?php _e('عکس', 'cpp-full'); ?></th>
                <th scope="col"><?php _e('نام محصول (دبل کلیک)', 'cpp-full'); ?></th>
                <th scope="col"><?php _e('دسته', 'cpp-full'); ?></th>
                <th scope="col"><?php _e('نوع / واحد', 'cpp-full'); ?></th>
                <th scope="col"><?php _e('بارگیری', 'cpp-full'); ?></th>
                <th scope="col"><?php _e('قیمت پایه', 'cpp-full'); ?></th>
                <th scope="col"><?php _e('بازه قیمت (حداقل-حداکثر)', 'cpp-full'); ?></th>
                <th scope="col"><?php _e('فعال؟', 'cpp-full'); ?></th> 
                <th scope="col"><?php _e('آخرین آپدیت', 'cpp-full'); ?></th> 
                <th scope="col"><?php _e('عملیات', 'cpp-full'); ?></th>
            </tr>
        </thead>
        <tbody class="cpp-products-list">
        <?php if ($products) : foreach ($products as $product) : 
            $img_src = esc_url($product->image_url) ? esc_url($product->image_url) : $default_image_url;
        ?>
            <tr data-id="<?php echo $product->id; ?>">
                <td><?php echo $product->id; ?></td>
                <td><img src="<?php echo $img_src; ?>" style="width: 50px; height: 50px; object-fit: cover;"></td>
                <td class="cpp-quick-edit" data-id="<?php echo $product->id; ?>" data-field="name" data-table-type="products"><?php echo esc_html($product->name); ?></td>
                <td><?php echo esc_html($product->category_name); ?></td>
                <td>
                    <span class="cpp-quick-edit" data-id="<?php echo $product->id; ?>" data-field="product_type" data-table-type="products"><?php echo esc_html($product->product_type); ?></span>
                    / 
                    <span class="cpp-quick-edit" data-id="<?php echo $product->id; ?>" data-field="unit" data-table-type="products"><?php echo esc_html($product->unit); ?></span>
                </td>
                <td class="cpp-quick-edit" data-id="<?php echo $product->id; ?>" data-field="load_location" data-table-type="products"><?php echo esc_html($product->load_location); ?></td>
                <td class="cpp-quick-edit" data-id="<?php echo $product->id; ?>" data-field="price" data-table-type="products"><?php echo esc_html($product->price); ?></td>
                <td>
                    <span class="cpp-quick-edit" data-id="<?php echo $product->id; ?>" data-field="min_price" data-table-type="products"><?php echo esc_html($product->min_price); ?></span>
                    -
                    <span class="cpp-quick-edit" data-id="<?php echo $product->id; ?>" data-field="max_price" data-table-type="products"><?php echo esc_html($product->max_price); ?></span>
                </td>
                <td class="cpp-quick-edit-select" data-id="<?php echo $product->id; ?>" data-field="is_active" data-table-type="products" data-current="<?php echo $product->is_active; ?>">
                    <?php echo $product->is_active ? __('فعال', 'cpp-full') : __('غیرفعال', 'cpp-full'); ?>
                </td>
                <td class="cpp-last-update"><?php echo date_i18n('Y/m/d H:i:s', strtotime($product->last_updated_at)); ?></td>
                <td>
                    <button type="button" class="button button-primary button-small cpp-edit-button" data-product-id="<?php echo $product->id; ?>"><?php _e('ویرایش', 'cpp-full'); ?></button>
                    <button type="button" class="button button-secondary button-small cpp-show-chart" data-product-id="<?php echo $product->id; ?>"><?php _e('نمودار', 'cpp-full'); ?></button>
                    <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=custom-prices-products&action=delete&id=' . $product->id), 'cpp_delete_product_' . $product->id); ?>" class="button button-small" onclick="return confirm('<?php _e('آیا مطمئنید؟', 'cpp-full'); ?>')"><?php _e('حذف', 'cpp-full'); ?></a>
                </td>
            </tr>
        <?php endforeach; else: ?>
            <tr><td colspan="12"><?php _e('محصولی یافت نشد.', 'cpp-full'); ?></td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<div id="cpp-chart-modal" class="cpp-modal-overlay" style="display:none;">
    <div class="cpp-modal-container">
        <span class="cpp-close-modal" onclick="jQuery('#cpp-chart-modal').hide();">×</span>
        <h2><?php _e('نمودار تغییرات قیمت', 'cpp-full'); ?></h2>
        <div class="cpp-chart-modal-content">
             <canvas id="cppPriceChart" width="400" height="150"></canvas>
        </div>
    </div>
</div>

<script>
// انتقال آپشن‌های فعال/غیرفعال به جاوا اسکریپت برای ویرایش سریع فیلدهای select
var cppStatusOptions = <?php echo json_encode($status_options); ?>;
</script>