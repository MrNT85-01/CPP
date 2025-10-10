<?php
if (!defined('ABSPATH')) exit;
global $wpdb;

// بخش جستجو
$search_term = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
$where_clause = '';

if (!empty($search_term)) {
    $like = '%' . $wpdb->esc_like($search_term) . '%';
    $where_clause = $wpdb->prepare(
        " WHERE product_name LIKE %s OR customer_name LIKE %s OR phone LIKE %s OR qty LIKE %s OR note LIKE %s OR admin_note LIKE %s",
        $like, $like, $like, $like, $like, $like
    );
}

$orders = $wpdb->get_results("SELECT * FROM " . CPP_DB_ORDERS . $where_clause . " ORDER BY created DESC");
?>

<div class="wrap">
    <h1><?php _e('سفارشات مشتریان', 'cpp-full'); ?></h1>

    <div class="notice notice-info">
        <p>
            <?php _e('برای ثبت یادداشت یا پیگیری سفارش، روی ستون **یادداشت مدیر** دوبار کلیک (Double Click) کنید.', 'cpp-full'); ?>
        </p>
    </div>

    <form method="get">
        <input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>">
        <?php
        // WordPress search box UI
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
                <th scope="col"><?php _e('تعداد درخواستی', 'cpp-full'); ?></th>
                <th scope="col"><?php _e('توضیحات مشتری', 'cpp-full'); ?></th>
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
                <td><?php echo esc_html(nl2br($order->note)); ?></td>
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