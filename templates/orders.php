<?php
if (!defined('ABSPATH')) exit;
global $wpdb;

// --- شروع تغییر: افزودن لیست وضعیت‌ها برای نمایش ---
$order_statuses = [
    'new_order'     => __('سفارش جدید', 'cpp-full'),
    'negotiating'   => __('در حال مذاکره', 'cpp-full'),
    'cancelled'     => __('کنسل شد', 'cpp-full'),
    'completed'     => __('خرید انجام شد', 'cpp-full'),
];
// --- پایان تغییر ---

$search_term = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
$where_clause = '';
if (!empty($search_term)) {
    $like = '%' . $wpdb->esc_like($search_term) . '%';
    $where_clause = $wpdb->prepare( " WHERE product_name LIKE %s OR customer_name LIKE %s OR phone LIKE %s OR qty LIKE %s OR note LIKE %s OR admin_note LIKE %s", $like, $like, $like, $like, $like, $like );
}
$orders = $wpdb->get_results("SELECT * FROM " . CPP_DB_ORDERS . $where_clause . " ORDER BY created DESC");
?>

<div class="wrap">
    <h1><?php _e('سفارشات مشتریان', 'cpp-full'); ?></h1>

    <div class="notice notice-info">
        <p><?php _e('برای ویرایش وضعیت سفارش یا ثبت یادداشت، روی سلول مورد نظر **دوبار کلیک (Double Click)** کنید.', 'cpp-full'); ?></p>
    </div>

    <form method="get">
        <input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>">
        <?php
        if (!class_exists('WP_List_Table')) { require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php'); }
        $search_box = new WP_List_Table();
        $search_box->search_box(__('جستجوی سفارشات', 'cpp-full'), 'cpp-orders-search');
        ?>
    </form>

    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th scope="col">ID</th>
                <th scope="col"><?php _e('نام محصول', 'cpp-full'); ?></th>
                <th scope="col"><?php _e('نام مشتری', 'cpp-full'); ?></th>
                <th scope="col"><?php _e('تلفن', 'cpp-full'); ?></th>
                <th scope="col"><?php _e('تعداد', 'cpp-full'); ?></th>
                <th scope="col"><?php _e('وضعیت سفارش (دبل کلیک)', 'cpp-full'); ?></th>
                <th scope="col"><?php _e('یادداشت مدیر (دبل کلیک)', 'cpp-full'); ?></th>
                <th scope="col"><?php _e('تاریخ ثبت', 'cpp-full'); ?></th>
                <th scope="col"><?php _e('عملیات', 'cpp-full'); ?></th>
            </tr>
        </thead>
        <tbody>
        <?php if ($orders) : foreach ($orders as $order) : ?>
            <tr>
                <td><?php echo $order->id; ?></td>
                <td><?php echo esc_html($order->product_name); ?></td>
                <td><?php echo esc_html($order->customer_name); ?></td>
                <td><?php echo esc_html($order->phone); ?></td>
                <td><?php echo esc_html($order->qty); ?></td>
                <td class="cpp-quick-edit-select" data-id="<?php echo $order->id; ?>" data-field="status" data-table-type="orders" data-current="<?php echo esc_attr($order->status); ?>">
                    <?php 
                        // اگر وضعیت در لیست تعریف شده بود، نام فارسی آن را نمایش بده، در غیر این صورت خود کلید را
                        echo isset($order_statuses[$order->status]) ? $order_statuses[$order->status] : esc_html($order->status); 
                    ?>
                </td>
                <td class="cpp-quick-edit" data-id="<?php echo $order->id; ?>" data-field="admin_note" data-table-type="orders">
                    <?php echo esc_html(nl2br($order->admin_note)); ?>
                </td>
                <td><?php echo date_i18n('Y/m/d H:i:s', strtotime($order->created)); ?></td>
                <td>
                    <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=custom-prices-orders&action=delete&id=' . $order->id), 'cpp_delete_order_' . $order->id); ?>" class="button button-small" onclick="return confirm('<?php _e('آیا مطمئنید؟', 'cpp-full'); ?>')"><?php _e('حذف', 'cpp-full'); ?></a>
                </td>
            </tr>
        <?php endforeach; else: ?>
            <tr><td colspan="9"><?php _e('سفارشی یافت نشد.', 'cpp-full'); ?></td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>
