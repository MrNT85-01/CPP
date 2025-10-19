<?php
if (!defined('ABSPATH')) exit;
global $wpdb;

$order_statuses = [
    'new_order'     => __('سفارش جدید', 'cpp-full'),
    'negotiating'   => __('در حال مذاکره', 'cpp-full'),
    'cancelled'     => __('کنسل شد', 'cpp-full'),
    'completed'     => __('خرید انجام شد', 'cpp-full'),
];

$search_term = isset($_GET['s']) ? sanitize_text_field(wp_unslash($_GET['s'])) : '';
$where_clause = '';
if (!empty($search_term)) {
    $like = '%' . $wpdb->esc_like($search_term) . '%';
    $where_clause = $wpdb->prepare( " WHERE product_name LIKE %s OR customer_name LIKE %s OR phone LIKE %s OR qty LIKE %s OR unit LIKE %s OR load_location LIKE %s OR note LIKE %s OR admin_note LIKE %s OR status LIKE %s OR id LIKE %s", $like, $like, $like, $like, $like, $like, $like, $like, $like, $like );
}
$orders = $wpdb->get_results("SELECT * FROM " . CPP_DB_ORDERS . $where_clause . " ORDER BY created DESC");
?>

<div class="wrap">
    <h1><?php _e('سفارشات مشتریان', 'cpp-full'); ?></h1>

    <div class="notice notice-info">
        <p><?php _e('برای ویرایش وضعیت سفارش یا ثبت یادداشت مدیر، روی سلول مورد نظر **دوبار کلیک (Double Click)** کنید.', 'cpp-full'); ?></p>
    </div>

    <form method="get">
        <input type="hidden" name="page" value="<?php echo isset($_REQUEST['page']) ? esc_attr($_REQUEST['page']) : ''; ?>">
        <p class="search-box">
	        <label class="screen-reader-text" for="cpp-orders-search-input"><?php _e('جستجوی سفارشات:', 'cpp-full'); ?></label>
	        <input type="search" id="cpp-orders-search-input" name="s" value="<?php echo esc_attr($search_term); ?>">
	        <input type="submit" id="search-submit" class="button" value="<?php esc_attr_e('جستجوی سفارشات', 'cpp-full'); ?>">
        </p>
    </form>

    <div style="overflow-x: auto; -webkit-overflow-scrolling: touch;">
        <table class="wp-list-table widefat fixed striped" style="min-width: 1000px;"> <thead>
                <tr>
                    <th scope="col" class="manage-column column-id" style="width: 5%;"><?php _e('ID', 'cpp-full'); ?></th>
                    <th scope="col" class="manage-column"><?php _e('نام محصول', 'cpp-full'); ?></th>
                    <th scope="col" class="manage-column"><?php _e('محل بارگیری', 'cpp-full'); ?></th>
                    <th scope="col" class="manage-column"><?php _e('نام مشتری', 'cpp-full'); ?></th>
                    <th scope="col" class="manage-column"><?php _e('تلفن', 'cpp-full'); ?></th>
                    <th scope="col" class="manage-column"><?php _e('مقدار', 'cpp-full'); ?></th>
                    <th scope="col" class="manage-column"><?php _e('واحد', 'cpp-full'); ?></th>
                    <th scope="col" class="manage-column column-note"><?php _e('یادداشت مشتری', 'cpp-full'); ?></th>
                    <th scope="col" class="manage-column column-status"><?php _e('وضعیت (دبل کلیک)', 'cpp-full'); ?></th>
                    <th scope="col" class="manage-column column-admin_note"><?php _e('یادداشت مدیر (دبل کلیک)', 'cpp-full'); ?></th>
                    <th scope="col" class="manage-column column-date"><?php _e('تاریخ ثبت', 'cpp-full'); ?></th>
                    <th scope="col" class="manage-column column-actions"><?php _e('عملیات', 'cpp-full'); ?></th>
                </tr>
            </thead>
            <tbody id="the-list">
            <?php if ($orders) : foreach ($orders as $order) : ?>
                <tr id="order-<?php echo $order->id; ?>">
                    <td><?php echo $order->id; ?></td>
                    <td data-colname="<?php esc_attr_e('نام محصول', 'cpp-full'); ?>"><?php echo esc_html($order->product_name); ?></td>
                    <td data-colname="<?php esc_attr_e('محل بارگیری', 'cpp-full'); ?>"><?php echo esc_html($order->load_location); ?></td>
                    <td data-colname="<?php esc_attr_e('نام مشتری', 'cpp-full'); ?>"><?php echo esc_html($order->customer_name); ?></td>
                    <td data-colname="<?php esc_attr_e('تلفن', 'cpp-full'); ?>"><?php echo esc_html($order->phone); ?></td>
                    <td data-colname="<?php esc_attr_e('مقدار', 'cpp-full'); ?>"><?php echo esc_html($order->qty); ?></td>
                    <td data-colname="<?php esc_attr_e('واحد', 'cpp-full'); ?>"><?php echo esc_html($order->unit); ?></td>
                    <td class="column-note" data-colname="<?php esc_attr_e('یادداشت مشتری', 'cpp-full'); ?>">
                        <?php
                        $full_note = esc_html($order->note);
                        if (mb_strlen($full_note) > 40) { // Shorter truncation
                            echo '<span title="' . esc_attr($full_note) . '">' . esc_html(mb_substr($full_note, 0, 40)) . '...</span>';
                        } else {
                            echo nl2br($full_note); // Keep nl2br for shorter notes
                        }
                        ?>
                    </td>
                    <td class="cpp-quick-edit-select column-status" data-colname="<?php esc_attr_e('وضعیت', 'cpp-full'); ?>" data-id="<?php echo $order->id; ?>" data-field="status" data-table-type="orders" data-current="<?php echo esc_attr($order->status); ?>">
                        <?php echo isset($order_statuses[$order->status]) ? $order_statuses[$order->status] : esc_html($order->status); ?>
                    </td>
                     <td class="cpp-quick-edit column-admin_note" data-colname="<?php esc_attr_e('یادداشت مدیر', 'cpp-full'); ?>" data-id="<?php echo $order->id; ?>" data-field="admin_note" data-table-type="orders">
                        <?php
                        $full_admin_note = esc_html($order->admin_note);
                        if (mb_strlen($full_admin_note) > 40) { // Shorter truncation
                            echo '<span title="' . esc_attr($full_admin_note) . '">' . esc_html(mb_substr($full_admin_note, 0, 40)) . '...</span>';
                        } else {
                            echo nl2br($full_admin_note);
                        }
                        ?>
                     </td>
                    <td data-colname="<?php esc_attr_e('تاریخ ثبت', 'cpp-full'); ?>"><?php echo date_i18n('Y/m/d H:i', strtotime(get_date_from_gmt($order->created))); ?></td>
                    <td data-colname="<?php esc_attr_e('عملیات', 'cpp-full'); ?>">
                        <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=custom-prices-orders&action=delete&id=' . $order->id), 'cpp_delete_order_' . $order->id); ?>" class="button button-link-delete" onclick="return confirm('<?php esc_attr_e('آیا مطمئنید؟', 'cpp-full'); ?>')"><?php _e('حذف', 'cpp-full'); ?></a>
                    </td>
                </tr>
            <?php endforeach; else: ?>
                <tr><td colspan="12"><?php _e('سفارشی یافت نشد.', 'cpp-full'); ?></td></tr>
            <?php endif; ?>
            </tbody>
             <tfoot>
                <tr>
                     <th scope="col"><?php _e('ID', 'cpp-full'); ?></th>
                    <th scope="col"><?php _e('نام محصول', 'cpp-full'); ?></th>
                    <th scope="col"><?php _e('محل بارگیری', 'cpp-full'); ?></th>
                    <th scope="col"><?php _e('نام مشتری', 'cpp-full'); ?></th>
                    <th scope="col"><?php _e('تلفن', 'cpp-full'); ?></th>
                    <th scope="col"><?php _e('مقدار', 'cpp-full'); ?></th>
                    <th scope="col"><?php _e('واحد', 'cpp-full'); ?></th>
                    <th scope="col"><?php _e('یادداشت مشتری', 'cpp-full'); ?></th>
                    <th scope="col"><?php _e('وضعیت (دبل کلیک)', 'cpp-full'); ?></th>
                    <th scope="col"><?php _e('یادداشت مدیر (دبل کلیک)', 'cpp-full'); ?></th>
                    <th scope="col"><?php _e('تاریخ ثبت', 'cpp-full'); ?></th>
                    <th scope="col"><?php _e('عملیات', 'cpp-full'); ?></th>
                </tr>
            </tfoot>
        </table>
    </div> </div>
<style>
/* Add some basic styling for truncated notes and responsive admin table */
.column-note span[title],
.column-admin_note span[title] {
    cursor: help;
    border-bottom: 1px dotted #999;
}
/* Basic responsive styles for WP List Table (might conflict with WP core styles) */
@media screen and (max-width: 782px) {
    .wp-list-table th, .wp-list-table td {
        font-size: 13px; /* Slightly smaller font */
    }
    /* Hide less important columns on smaller admin screens */
    .wp-list-table .column-date,
    .wp-list-table .column-load_location, /* Example: hide location */
    .wp-list-table .column-unit,      /* Example: hide unit */
    .wp-list-table .column-note        /* Example: hide customer note */
    {
       /* display: none; */ /* Uncomment to hide */
    }
    /* Ensure quick edit still works okay */
    .cpp-quick-edit-input { font-size: 13px; }
}
</style>
