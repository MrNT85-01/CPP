<?php
// Template for [cpp_products_list] shortcode
if (!defined('ABSPATH')) exit;

$disable_base_price = get_option('cpp_disable_base_price', 0);
$cart_icon_url = CPP_ASSETS_URL . 'images/cart-icon.png';
$chart_icon_url = CPP_ASSETS_URL . 'images/chart-icon.png';
$default_image = get_option('cpp_default_product_image', CPP_ASSETS_URL . 'images/default-product.png');

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
        <?php foreach ($products as $product) :
            $product_image_url = !empty($product->image_url) ? esc_url($product->image_url) : esc_url($default_image);
        ?>
            <tr data-id="<?php echo $product->id; ?>">
                <td class="col-product-name" data-colname="<?php esc_attr_e('محصول', 'cpp-full'); ?>">
                    <div class="cpp-product-info">
                        <img src="<?php echo $product_image_url; ?>" alt="<?php echo esc_attr($product->name); ?>">
                        <div class="cpp-product-details">
                            <span class="cpp-product-name"><?php echo esc_html($product->name); ?></span>
                        </div>
                    </div>
                </td>
                <td data-colname="<?php esc_attr_e('نوع', 'cpp-full'); ?>"><?php echo esc_html($product->product_type); ?></td>
                <td data-colname="<?php esc_attr_e('واحد', 'cpp-full'); ?>"><?php echo esc_html($product->unit); ?></td>
                <td data-colname="<?php esc_attr_e('محل بارگیری', 'cpp-full'); ?>"><?php echo esc_html($product->load_location); ?></td>
                <td data-colname="<?php esc_attr_e('آخرین بروزرسانی', 'cpp-full'); ?>"><?php echo date_i18n('Y/m/d H:i', strtotime(get_date_from_gmt($product->last_updated_at))); ?></td>
                <?php if (!$disable_base_price) : ?>
                    <td class="cpp-base-price col-price" data-colname="<?php esc_attr_e('قیمت پایه', 'cpp-full'); ?>">
                         <?php
                            $price_cleaned = str_replace(',', '', $product->price);
                            echo is_numeric($price_cleaned) ? esc_html(number_format_i18n((float)$price_cleaned)) : esc_html($product->price);
                         ?>
                    </td>
                <?php endif; ?>
                <td class="cpp-price-range col-price-range" data-colname="<?php esc_attr_e('بازه قیمت', 'cpp-full'); ?>">
                    <?php if (!empty($product->min_price) && !empty($product->max_price)) :
                         $min_cleaned = str_replace(',', '', $product->min_price);
                         $max_cleaned = str_replace(',', '', $product->max_price);
                    ?>
                        <?php echo is_numeric($min_cleaned) ? esc_html(number_format_i18n((float)$min_cleaned)) : esc_html($product->min_price); ?> - <?php echo is_numeric($max_cleaned) ? esc_html(number_format_i18n((float)$max_cleaned)) : esc_html($product->max_price); ?>
                    <?php else: ?>
                        <span class="cpp-price-not-set"><?php _e('تماس بگیرید', 'cpp-full'); ?></span>
                    <?php endif; ?>
                </td>
                <td class="cpp-actions-cell col-actions" data-colname="<?php esc_attr_e('عملیات', 'cpp-full'); ?>">
                    <button class="cpp-icon-btn cpp-order-btn"
                            data-product-id="<?php echo esc_attr($product->id); ?>"
                            data-product-name="<?php echo esc_attr($product->name); ?>"
                            data-product-unit="<?php echo esc_attr($product->unit); ?>"
                            data-product-location="<?php echo esc_attr($product->load_location); ?>"
                            title="<?php esc_attr_e('ثبت سفارش', 'cpp-full'); ?>">
                        <img src="<?php echo esc_url($cart_icon_url); ?>" alt="<?php esc_attr_e('ثبت سفارش', 'cpp-full'); ?>">
                    </button>
                    <button class="cpp-icon-btn cpp-chart-btn" data-product-id="<?php echo esc_attr($product->id); ?>" title="<?php esc_attr_e('نمودار', 'cpp-full'); ?>">
                         <img src="<?php echo esc_url($chart_icon_url); ?>" alt="<?php esc_attr_e('نمودار', 'cpp-full'); ?>">
                    </button>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
         <tfoot>
             <tr>
                 <th><?php _e('محصول', 'cpp-full'); ?></th>
                 <th><?php _e('نوع', 'cpp-full'); ?></th>
                 <th><?php _e('واحد', 'cpp-full'); ?></th>
                 <th><?php _e('محل بارگیری', 'cpp-full'); ?></th>
                 <th><?php _e('آخرین بروزرسانی', 'cpp-full'); ?></th>
                 <?php if (!$disable_base_price) : ?><th><?php _e('قیمت پایه', 'cpp-full'); ?></th><?php endif; ?>
                 <th><?php _e('بازه قیمت', 'cpp-full'); ?></th>
                 <th><?php _e('عملیات', 'cpp-full'); ?></th>
             </tr>
         </tfoot>
    </table>
</div>
