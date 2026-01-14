<?php
include('../config/constants.php');
require_once('partials/login-check.php');
require_once('partials/menu.php');
?>

<div class="main-content">
    <div class="wrapper">
        <h1>Quản lý thanh toán</h1>
        <br><br>
        
        <table class="tbl-full">
            <tr>
                <th>ID</th>
                <th>Mã đơn hàng</th>
                <th>Phương thức</th>
                <th>Số tiền</th>
                <th>Trạng thái</th>
                <th>Mã giao dịch</th>
                <th>Ngày tạo</th>
                <th>Ngày thanh toán</th>
                <th>Thao tác</th>
            </tr>
            <?php
            $payment_sql = "SELECT * FROM tbl_payment ORDER BY id DESC";
            $payment_res = mysqli_query($conn, $payment_sql);
            
            if(mysqli_num_rows($payment_res) > 0) {
                while($payment = mysqli_fetch_assoc($payment_res)) {
                    $status_class = '';
                    $status_text = '';
                    switch($payment['payment_status']) {
                        case 'pending':
                            $status_class = 'style="color: orange;"';
                            $status_text = 'Chờ thanh toán';
                            break;
                        case 'success':
                            $status_class = 'style="color: green;"';
                            $status_text = 'Thành công';
                            break;
                        case 'failed':
                            $status_class = 'style="color: red;"';
                            $status_text = 'Thất bại';
                            break;
                        case 'cancelled':
                            $status_class = 'style="color: gray;"';
                            $status_text = 'Đã hủy';
                            break;
                        case 'refunded':
                            $status_class = 'style="color: purple;"';
                            $status_text = 'Đã hoàn tiền';
                            break;
                    }
                    ?>
                    <tr>
                        <td><?php echo $payment['id']; ?></td>
                        <td><strong><?php echo htmlspecialchars($payment['order_code']); ?></strong></td>
                        <td><?php echo strtoupper($payment['payment_method']); ?></td>
                        <td><?php echo number_format($payment['amount'], 0, ',', '.'); ?> đ</td>
                        <td <?php echo $status_class; ?>><?php echo $status_text; ?></td>
                        <td><?php echo htmlspecialchars($payment['transaction_id'] ?? 'N/A'); ?></td>
                        <td><?php echo date('d/m/Y H:i', strtotime($payment['created_at'])); ?></td>
                        <td><?php echo $payment['paid_at'] ? date('d/m/Y H:i', strtotime($payment['paid_at'])) : 'N/A'; ?></td>
                        <td>
                            <a href="<?php echo SITEURL; ?>admin/manage-order.php?search=<?php echo urlencode($payment['order_code']); ?>" 
                               class="btn-secondary" style="padding: 5px 10px; font-size: 12px;">
                                Xem đơn hàng
                            </a>
                            <?php if($payment['payment_status'] == 'success'): ?>
                            <a href="<?php echo SITEURL; ?>admin/refund.php?order_code=<?php echo urlencode($payment['order_code']); ?>" 
                               class="btn-secondary" style="padding: 5px 10px; font-size: 12px; background: #ff6b81; color: white;">
                                Hoàn tiền
                            </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php
                }
            } else {
                echo '<tr><td colspan="9" class="error">Chưa có giao dịch thanh toán nào</td></tr>';
            }
            ?>
        </table>
    </div>
</div>

<?php include('partials/footer.php'); ?>

