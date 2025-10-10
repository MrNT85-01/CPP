<?php
if (!defined('ABSPATH')) exit;

// URL آیکون‌ها
$cart_icon_url = CPP_ASSETS_URL . 'images/cart-icon.png';
$chart_icon_url = CPP_ASSETS_URL . 'images/chart-icon.png';
?>

<div class="cpp-grid-view-wrapper">
    <?php if (!empty($categories)) : ?>
        <div class="cpp-grid-view-filters">
            <a href="#" class="filter-btn active" data-cat-id="all">همه دسته‌ها</a>
            <?php foreach ($categories as $cat) : ?>
                <a href="#" class="filter-btn" data-cat-id="<?php echo esc_attr($cat->id); ?>"><?php echo esc_html($cat->name); ?></a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <table class="cpp-grid-view-table">
        <thead>
            <tr>
                <th class="col-product-name"><?php _e('عنوان محصول', 'cpp-full'); ?></th>
                <th class="col-factory"><?php _e('کارخانه', 'cpp-full'); ?></th>
                <th class="col-size"><?php _e('سایز', 'cpp-full'); ?></th>
                <th class="col-date"><?php _e('تاریخ بروز رسانی', 'cpp-full'); ?></th>
                <th class="col-price"><?php _e('قیمت', 'cpp-full'); ?></th>
                <th class="col-buy"><?php _e('خرید', 'cpp-full'); ?></th>
                <th class="col-chart"><?php _e('نمودار', 'cpp-full'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($products)) : foreach ($products as $product) : ?>
                <tr class="product-row" data-cat-id="<?php echo esc_attr($product->cat_id); ?>">
                    <td class="col-product-name"><?php echo esc_html($product->name); ?></td>
                    <td class="col-factory"><?php echo esc_html($product->load_location); ?></td>
                    <td class="col-size"><?php echo esc_html($product->product_type); ?></td>
                    <td class="col-date"><?php echo esc_html(date_i18n('Y/m/d', strtotime($product->last_updated_at))); ?></td>
                    <td class="col-price">
                        <?php 
                            if (!empty($product->price) && is_numeric(str_replace(',', '', $product->price))) {
                                echo esc_html(number_format((float)str_replace(',', '', $product->price)));
                            } else {
                                echo esc_html($product->price);
                            }
                        ?>
                    </td>
                    <td class="col-buy">
                        <button class="cpp-order-btn" data-product-id="<?php echo esc_attr($product->id); ?>" data-product-name="<?php echo esc_attr($product->name); ?>">
                            <img src="<?php echo esc_url($cart_icon_url); ?>" alt="<?php _e('خرید', 'cpp-full'); ?>">
                        </button>
                    </td>
                    <td class="col-chart">
                         <button class="cpp-chart-btn" data-product-id="<?php echo esc_attr($product->id); ?>">
                            <img src="<?php echo esc_url($chart_icon_url); ?>" alt="<?php _e('نمودار', 'cpp-full'); ?>">
                        </button>
                    </td>
                </tr>
            <?php endforeach; else: ?>
                <tr>
                    <td colspan="7"><?php _e('محصولی برای نمایش یافت نشد.', 'cpp-full'); ?></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="cpp-grid-view-footer">
        <a href="#" class="cpp-view-more-btn"><?php _e('مشاهده بیشتر', 'cpp-full'); ?></a>
    </div>
</div>
