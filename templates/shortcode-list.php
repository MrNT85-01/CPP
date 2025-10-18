<?php
if (!defined('ABSPATH')) exit;

// واکشی تنظیمات و URL آیکون‌ها
$disable_base_price = get_option('cpp_disable_base_price', 0);
$cart_icon_url = CPP_ASSETS_URL . 'images/cart-icon.png';
$chart_icon_url = CPP_ASSETS_URL . 'images/chart-icon.png';
?>

<div class="cpp-products-list-container">
    <table class="cpp-products-table">
        <thead>
            <tr>
                <th><?php _e('محصول', 'cpp-full'); ?></th>
                <th><?php _e('نوع', 'cpp-full'); ?></th>
                <th><?php _e('واحد', 'cpp-full'); ?></th>
                <th><?php _e('محل بارگیری', 'cpp-full'); ?></th>
                <th><?php _e('آخرین بروزرسانی', 'cpp-full'); ?></th>
                <?php if (!$disable_base_price) : ?>
                    <th><?php _e('قیمت پایه', 'cpp-full'); ?></th>
                <?php endif; ?>
                <th><?php _e('بازه قیمت', 'cpp-full'); ?></th>
                <th><?php _e('عملیات', 'cpp-full'); ?></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($products as $product) : ?>
            <tr data-id="<?php echo $product->id; ?>">
                <td>
                    <div class="cpp-product-info">
                        <img src="<?php echo esc_url($product->image_url) ? esc_url($product->image_url) : CPP_ASSETS_URL . 'images/default-product.png'; ?>" alt="<?php echo esc_attr($product->name); ?>">
                        <div class="cpp-product-details">
                            <span class="cpp-product-name"><?php echo esc_html($product->name); ?></span>
                        </div>
                    </div>
                </td>
                <td><?php echo esc_html($product->product_type); ?></td>
                <td><?php echo esc_html($product->unit); ?></td>
                <td><?php echo esc_html($product->load_location); ?></td>
                <td><?php echo date_i18n('Y/m/d H:i', strtotime($product->last_updated_at)); ?></td>
                <?php if (!$disable_base_price) : ?>
                    <td class="cpp-base-price"><?php echo esc_html($product->price); ?></td>
                <?php endif; ?>
                <td class="cpp-price-range">
                    <?php if (!empty($product->min_price) && !empty($product->max_price)) : ?>
                        <?php echo esc_html($product->min_price); ?> - <?php echo esc_html($product->max_price); ?>
                    <?php else: ?>
                        <span class="cpp-price-not-set"><?php _e('تماس بگیرید', 'cpp-full'); ?></span>
                    <?php endif; ?>
                </td>
                <td class="cpp-actions-cell">
                    <button class="cpp-icon-btn cpp-order-btn"
                            data-product-id="<?php echo $product->id; ?>"
                            data-product-name="<?php echo esc_attr($product->name); ?>"
                            data-product-load-location="<?php echo esc_attr($product->load_location); // Added ?>"
                            data-product-unit="<?php echo esc_attr($product->unit); // Added ?>"
                            title="<?php _e('ثبت سفارش', 'cpp-full'); ?>">
                        <img src="<?php echo esc_url($cart_icon_url); ?>" alt="<?php _e('ثبت سفارش', 'cpp-full'); ?>">
                    </button>
                    <button class="cpp-icon-btn cpp-chart-btn" data-product-id="<?php echo $product->id; ?>" title="<?php _e('نمودار', 'cpp-full'); ?>">
                         <img src="<?php echo esc_url($chart_icon_url); ?>" alt="<?php _e('نمودار', 'cpp-full'); ?>">
                    </button>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
