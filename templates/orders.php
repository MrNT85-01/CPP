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
    // --- شروع تغییر: جستجو در فیلدهای جدید ---
    $where_clause = $wpdb->prepare( " WHERE product_name LIKE %s OR customer_name LIKE %s OR phone LIKE %s OR qty LIKE %s OR unit LIKE %s OR load_location LIKE %s OR note LIKE %s OR admin_note LIKE %s", $like, $like, $like, $like, $like, $like, $like, $like );
    // --- پایان تغییر ---
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
        <?php
        // Simple search box implementation
        ?>
        <p class="search-box">
	        <label class="screen-reader-text" for="cpp-orders-search-input"><?php _e('جستجوی سفارشات:', 'cpp-full'); ?></label>
	        <input type="search" id="cpp-orders-search-input" name="s" value="<?php echo esc_attr($search_term); ?>">
	        <input type="submit" id="search-submit" class="button" value="<?php esc_attr_e('جستجوی سفارشات', 'cpp-full'); ?>">
        </p>
    </form>

    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th scope="col" class="manage-column column-id" style="width: 5%;"><?php _e('ID', 'cpp-full'); ?></th>
                <th scope="col" class="manage-column"><?php _e('نام محصول', 'cpp-full'); ?></th>
                 <th scope="col" class="manage-column"><?php _e('محل بارگیری', 'cpp-full'); ?></th>
                <th scope="col" class="manage-column"><?php _e('نام مشتری', 'cpp-full'); ?></th>
                <th scope="col" class="manage-column"><?php _e('تلفن', 'cpp-full'); ?></th>
                <th scope="col" class="manage-column"><?php _e('مقدار', 'cpp-full'); ?></th>
                 <th scope="col" class="manage-column"><?php _e('واحد', 'cpp-full'); ?></th>
                <th scope="col" class="manage-column"><?php _e('یادداشت مشتری', 'cpp-full'); ?></th>
                 <th scope="col" class="manage-column"><?php _e('وضعیت (دبل کلیک)', 'cpp-full'); ?></th>
                <th scope="col" class="manage-column"><?php _e('یادداشت مدیر (دبل کلیک)', 'cpp-full'); ?></th>
                <th scope="col" class="manage-column column-date"><?php _e('تاریخ ثبت', 'cpp-full'); ?></th>
                <th scope="col" class="manage-column column-actions"><?php _e('عملیات', 'cpp-full'); ?></th>
            </tr>
        </thead>
        <tbody>
        <?php if ($orders) : foreach ($orders as $order) : ?>
            <tr>
                <td><?php echo $order->id; ?></td>
                <td><?php echo esc_html($order->product_name); ?></td>
                <td><?php echo esc_html($order->load_location); ?></td>
                <td><?php echo esc_html($order->customer_name); ?></td>
                <td><?php echo esc_html($order->phone); ?></td>
                <td><?php echo esc_html($order->qty); ?></td>
                 <td><?php echo esc_html($order->unit); ?></td>
                <td><?php echo esc_html(nl2br($order->note)); // Display customer note ?></td>
                 <td class="cpp-quick-edit-select" data-id="<?php echo $order->id; ?>" data-field="status" data-table-type="orders" data-current="<?php echo esc_attr($order->status); ?>">
                    <?php echo isset($order_statuses[$order->status]) ? $order_statuses[$order->status] : esc_html($order->status); ?>
                </td>
                <td class="cpp-quick-edit" data-id="<?php echo $order->id; ?>" data-field="admin_note" data-table-type="orders">
                    <?php echo wp_kses_post(nl2br($order->admin_note)); // Use wp_kses_post for admin note ?>
                </td>
                <td><?php echo date_i18n('Y/m/d H:i:s', strtotime(get_date_from_gmt($order->created))); // Convert GMT to local time ?></td>
                <td>
                    <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=custom-prices-orders&action=delete&id=' . $order->id), 'cpp_delete_order_' . $order->id); ?>" class="button button-small" onclick="return confirm('<?php esc_attr_e('آیا مطمئنید؟', 'cpp-full'); ?>')"><?php _e('حذف', 'cpp-full'); ?></a>
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
</div>
