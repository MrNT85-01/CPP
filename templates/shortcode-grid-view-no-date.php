<?php
if (!defined('ABSPATH')) exit;

// واکشی تنظیمات و URL آیکون‌ها
$disable_base_price = get_option('cpp_disable_base_price', 0);
$cart_icon_url = CPP_ASSETS_URL . 'images/cart-icon.png';
$chart_icon_url = CPP_ASSETS_URL . 'images/chart-icon.png';
?>

<div class="cpp-grid-view-wrapper">
    
    <?php if (!empty($categories) || $last_updated_time) : ?>
        <div class="cpp-grid-view-filters">
            <?php if ($last_updated_time): ?>
            <span class="last-update-display">
                <?php echo __('آخرین بروزرسانی:', 'cpp-full') . ' ' . date_i18n('Y/m/d H:i', strtotime($last_updated_time)); ?>
            </span>
            <?php endif; ?>

            <a href="#" class="filter-btn active" data-cat-id="all"><?php _e('همه دسته‌ها', 'cpp-full'); ?></a>
            <?php foreach ($categories as $cat) : ?>
                <a href="#" class="filter-btn" data-cat-id="<?php echo esc_attr($cat->id); ?>"><?php echo esc_html($cat->name); ?></a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <table class="cpp-grid-view-table">
        <thead>
            <tr>
                <th><?php _e('محصول', 'cpp-full'); ?></th>
                <th><?php _e('نوع', 'cpp-full'); ?></th>
                <th><?php _e('واحد', 'cpp-full'); ?></th>
                <th><?php _e('محل بارگیری', 'cpp-full'); ?></th>
                <?php if (!$disable_base_price) : ?>
                    <th><?php _e('قیمت پایه', 'cpp-full'); ?></th>
                <?php endif; ?>
                <th><?php _e('بازه قیمت', 'cpp-full'); ?></th>
                <th><?php _e('عملیات', 'cpp-full'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($products)) : foreach ($products as $product) : ?>
                <tr class="product-row" data-cat-id="<?php echo esc_attr($product->cat_id); ?>">
                    <td class="col-product-name"><?php echo esc_html($product->name); ?></td>
                    <td><?php echo esc_html($product->product_type); ?></td>
                    <td><?php echo esc_html($product->unit); ?></td>
                    <td><?php echo esc_html($product->load_location); ?></td>
                    
                    <?php if (!$disable_base_price) : ?>
                    <td class="col-price">
                        <?php 
                            if (!empty($product->price) && is_numeric(str_replace(',', '', $product->price))) {
                                echo esc_html(number_format((float)str_replace(',', '', $product->price)));
                            } else {
                                echo esc_html($product->price);
                            }
                        ?>
                    </td>
                    <?php endif; ?>

                     <td class="col-price-range">
                        <?php if (!empty($product->min_price) && !empty($product->max_price)) : ?>
                            <?php echo esc_html($product->min_price); ?> - <?php echo esc_html($product->max_price); ?>
                        <?php else: ?>
                            <span class="cpp-price-not-set"><?php _e('تماس بگیرید', 'cpp-full'); ?></span>
                        <?php endif; ?>
                    </td>

                    <td class="col-actions">
                        <button class="cpp-icon-btn cpp-order-btn" data-product-id="<?php echo esc_attr($product->id); ?>" data-product-name="<?php echo esc_attr($product->name); ?>" title="<?php _e('خرید', 'cpp-full'); ?>">
                            <img src="<?php echo esc_url($cart_icon_url); ?>" alt="<?php _e('خرید', 'cpp-full'); ?>">
                        </button>
                        <button class="cpp-icon-btn cpp-chart-btn" data-product-id="<?php echo esc_attr($product->id); ?>" title="<?php _e('نمودار', 'cpp-full'); ?>">
                            <img src="<?php echo esc_url($chart_icon_url); ?>" alt="<?php _e('نمودار', 'cpp-full'); ?>">
                        </button>
                    </td>
                </tr>
            <?php endforeach; else: ?>
                <tr>
                    <td colspan="<?php echo $disable_base_price ? '6' : '7'; ?>"><?php _e('محصولی برای نمایش یافت نشد.', 'cpp-full'); ?></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <?php if (count($products) < $total_products) : ?>
    <div class="cpp-grid-view-footer">
        <button class="cpp-view-more-btn" data-page="0" data-show-date="false"><?php _e('مشاهده بیشتر', 'cpp-full'); ?></button>
    </div>
    <?php endif; ?>
</div>
