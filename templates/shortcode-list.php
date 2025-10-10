<?php
if (!defined('ABSPATH')) exit;

$disable_base_price = get_option('cpp_disable_base_price', 0);
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
                <td>
                    <button class="cpp-order-btn" data-product-id="<?php echo $product->id; ?>" data-product-name="<?php echo esc_attr($product->name); ?>"><?php _e('ثبت سفارش', 'cpp-full'); ?></button>
                    <button class="cpp-chart-btn" data-product-id="<?php echo $product->id; ?>"><?php _e('نمودار', 'cpp-full'); ?></button>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
