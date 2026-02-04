<?php
include('../config/constants.php');
require_once('partials/login-check.php');
require_once('partials/menu.php');
?>

<div class="main-content">
    <div class="wrapper">
        <h1 style="margin-bottom: 10px;">Quản lý thanh toán</h1>
        <p style="color:#747d8c;margin-bottom:25px;">Theo dõi chi tiết các giao dịch thanh toán của khách hàng theo thời gian thực.</p>
        
        <div style="background:#ffffff;border-radius:12px;padding:18px 20px;box-shadow:0 4px 14px rgba(0,0,0,0.06);border:1px solid #ecf0f1;overflow-x:auto;">
        <table class="tbl-full" style="width:100%;border-collapse:separate;border-spacing:0;font-size:14px;">
            <thead>
            <tr style="background:#f8f9fb;">
                <th style="padding:12px 10px;border-bottom:1px solid #e0e6ed;text-align:left;">ID</th>
                <th style="padding:12px 10px;border-bottom:1px solid #e0e6ed;text-align:left;">Mã đơn hàng</th>
                <th style="padding:12px 10px;border-bottom:1px solid #e0e6ed;text-align:left;">Phương thức</th>
                <th style="padding:12px 10px;border-bottom:1px solid #e0e6ed;text-align:right;">Số tiền</th>
                <th style="padding:12px 10px;border-bottom:1px solid #e0e6ed;text-align:left;">Trạng thái</th>
                <th style="padding:12px 10px;border-bottom:1px solid #e0e6ed;text-align:left;">Mã giao dịch</th>
                <th style="padding:12px 10px;border-bottom:1px solid #e0e6ed;text-align:left;">Ngày tạo</th>
                <th style="padding:12px 10px;border-bottom:1px solid #e0e6ed;text-align:left;">Ngày thanh toán</th>
                <th style="padding:12px 10px;border-bottom:1px solid #e0e6ed;text-align:center;">Thao tác</th>
            </tr>
            </thead>
            <tbody>
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
                    <tr style="border-bottom:1px solid #f0f2f5;">
                        <td style="padding:10px 8px;"><?php echo $payment['id']; ?></td>
                        <td style="padding:10px 8px;font-weight:600;color:#2c3e50;">
                            <?php echo htmlspecialchars($payment['order_code']); ?>
                        </td>
                        <td style="padding:10px 8px;">
                            <span style="display:inline-block;padding:4px 10px;border-radius:999px;background:#f1f2f6;font-size:12px;color:#57606f;">
                                <?php echo strtoupper($payment['payment_method']); ?>
                            </span>
                        </td>
                        <td style="padding:10px 8px;text-align:right;font-weight:600;color:#ff6b81;">
                            <?php echo number_format($payment['amount'], 0, ',', '.'); ?> đ
                        </td>
                        <td style="padding:10px 8px;">
                            <span <?php echo $status_class; ?> style="display:inline-block;padding:4px 10px;border-radius:999px;font-size:12px;font-weight:600;<?php echo trim(str_replace('style="','',str_replace('"','',$status_class))); ?>;background:#fdf2f2;">
                                <?php echo $status_text; ?>
                            </span>
                        </td>
                        <td style="padding:10px 8px;"><?php echo htmlspecialchars($payment['transaction_id'] ?? 'N/A'); ?></td>
                        <td style="padding:10px 8px;"><?php echo date('d/m/Y H:i', strtotime($payment['created_at'])); ?></td>
                        <td style="padding:10px 8px;"><?php echo $payment['paid_at'] ? date('d/m/Y H:i', strtotime($payment['paid_at'])) : 'N/A'; ?></td>
                        <td style="padding:10px 8px;text-align:center;white-space:nowrap;">
                            <a href="<?php echo SITEURL; ?>admin/manage-order.php?search=<?php echo urlencode($payment['order_code']); ?>" 
                               class="btn-secondary"
                               style="display:inline-block;padding:6px 12px;font-size:12px;border-radius:999px;background:#ecf0f1;color:#2c3e50;font-weight:500;border:1px solid #dfe4ea;margin-right:4px;transition:all 0.15s;">
                                Xem đơn hàng
                            </a>
                            <?php if($payment['payment_status'] == 'success'): ?>
                            <a href="<?php echo SITEURL; ?>admin/refund.php?order_code=<?php echo urlencode($payment['order_code']); ?>" 
                               class="btn-secondary"
                               style="display:inline-block;padding:6px 12px;font-size:12px;border-radius:999px;background:#ff6b81;color:#ffffff;font-weight:500;border:1px solid #ff6b81;transition:all 0.15s;">
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
            </tbody>
        </table>
        </div>
    </div>
</div>

<?php include('partials/footer.php'); ?>

