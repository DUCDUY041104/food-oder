<?php
require_once('../config/constants.php');

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    $_SESSION['access-denied'] = "Vui lòng đăng nhập để xem lịch sử đặt hàng";
    header('location:'.SITEURL.'user/login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Lấy lịch sử đặt hàng của user với thông tin thanh toán
$sql = "SELECT o.*, p.payment_status, p.transaction_id, p.payment_method 
        FROM tbl_order o 
        LEFT JOIN tbl_payment p ON o.order_code = p.order_code AND p.payment_status = 'success'
        WHERE o.user_id = $user_id 
        GROUP BY o.order_code, o.id
        ORDER BY o.order_date DESC";
$res = mysqli_query($conn, $sql);

include('../partials-front/menu.php');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lịch sử đặt hàng - WowFood</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .order-history-container {
            margin-top: 100px;
            padding: 20px;
            max-width: 1200px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .order-history-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .order-history-header h1 {
            color: #2f3542;
            margin-bottom: 10px;
        }
        
        .order-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-bottom: 20px;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .order-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
        }
        
        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f1f2f6;
        }
        
        .order-code {
            font-size: 18px;
            font-weight: bold;
            color: #ff6b81;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .order-date {
            color: #747d8c;
            font-size: 14px;
        }
        
        .order-status {
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .status-ordered {
            background: #ffa502;
            color: white;
        }
        
        .status-on-delivery {
            background: #ff6348;
            color: white;
        }
        
        .status-delivered {
            background: #2ed573;
            color: white;
        }
        
        .status-cancelled {
            background: #ff4757;
            color: white;
        }
        
        .order-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .order-detail-item {
            display: flex;
            flex-direction: column;
        }
        
        .order-detail-label {
            font-size: 12px;
            color: #747d8c;
            margin-bottom: 5px;
        }
        
        .order-detail-value {
            font-size: 16px;
            font-weight: bold;
            color: #2f3542;
        }
        
        .order-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        
        .btn-chat {
            background: linear-gradient(135deg, #ff6b81 0%, #ff4757 100%);
            color: white;
            padding: 8px 20px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 14px;
            font-weight: bold;
            transition: transform 0.2s;
        }
        
        .btn-chat:hover {
            transform: translateY(-2px);
        }
        
        .no-orders {
            text-align: center;
            padding: 60px 20px;
            color: #747d8c;
        }
        
        .no-orders-icon {
            font-size: 64px;
            margin-bottom: 20px;
        }
        
        .copy-code-btn {
            background: #f1f2f6;
            border: none;
            padding: 5px 10px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 12px;
            margin-left: 10px;
            transition: background 0.3s;
        }
        
        .copy-code-btn:hover {
            background: #dfe4ea;
        }
        .payment-paid {
            color: #2ed573;
            font-weight: bold;
        }
        .payment-pending {
            color: #ffa502;
            font-weight: bold;
        }
        .payment-failed {
            color: #ff4757;
            font-weight: bold;
        }
        .payment-refunded {
            color: #a55eea;
            font-weight: bold;
        }
        .payment-unpaid {
            color: #747d8c;
        }
    </style>
</head>
<body>
    <div class="order-history-container">
        <div class="order-history-header">
            <h1>📦 Lịch sử đặt hàng</h1>
            <p>Xem và quản lý các đơn hàng của bạn</p>
        </div>
        
        <?php
        if(isset($_SESSION['refund-success'])) {
            echo '<div class="success text-center" style="background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin-bottom: 20px; border: 1px solid #c3e6cb;">✅ '.$_SESSION['refund-success'].'</div>';
            unset($_SESSION['refund-success']);
        }
        if(isset($_SESSION['refund-error'])) {
            echo '<div class="error text-center" style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin-bottom: 20px; border: 1px solid #f5c6cb;">❌ '.$_SESSION['refund-error'].'</div>';
            unset($_SESSION['refund-error']);
        }
        ?>

        <?php
        if (mysqli_num_rows($res) > 0) {
            // Group orders by order_code
            $orders_by_code = [];
            while ($row = mysqli_fetch_assoc($res)) {
                $order_code = $row['order_code'] ?? 'N/A';
                if(!isset($orders_by_code[$order_code])) {
                    $orders_by_code[$order_code] = [
                        'order_code' => $order_code,
                        'order_date' => $row['order_date'],
                        'status' => $row['status'],
                        'customer_name' => $row['customer_name'],
                        'customer_address' => $row['customer_address'],
                        'payment_status' => $row['payment_status'] ?? null,
                        'payment_method' => $row['payment_method'] ?? null,
                        'transaction_id' => $row['transaction_id'] ?? null,
                        'items' => [],
                        'total' => 0
                    ];
                }
                $orders_by_code[$order_code]['items'][] = [
                    'food' => $row['food'],
                    'qty' => $row['qty'],
                    'price' => $row['price'],
                    'total' => $row['total']
                ];
                $orders_by_code[$order_code]['total'] += floatval($row['total']);
            }
            
            foreach($orders_by_code as $order_code => $order_data) {
                $order_code = $order_data['order_code'];
                $order_date = $order_data['order_date'];
                $status = $order_data['status'];
                $customer_name = $order_data['customer_name'];
                $customer_address = $order_data['customer_address'];
                $order_total = $order_data['total'];
                
                // Xác định class cho status
                $status_class = 'status-ordered';
                if ($status == 'On Delivery') {
                    $status_class = 'status-on-delivery';
                } elseif ($status == 'Delivered') {
                    $status_class = 'status-delivered';
                } elseif ($status == 'Cancelled') {
                    $status_class = 'status-cancelled';
                }
                
                // Format ngày
                $formatted_date = date('d/m/Y H:i', strtotime($order_date));
                ?>
                <div class="order-card">
                    <div class="order-header">
                        <div>
                            <div class="order-code">
                                Mã đơn: <?php echo htmlspecialchars($order_code); ?>
                                <button class="copy-code-btn" onclick="copyOrderCode('<?php echo $order_code; ?>')" title="Copy mã đơn hàng">
                                    📋 Copy
                                </button>
                            </div>
                            <div class="order-date"><?php echo $formatted_date; ?></div>
                        </div>
                        <span class="order-status <?php echo $status_class; ?>">
                            <?php 
                            $status_text = $status;
                            if ($status == 'Ordered') {
                                $status_text = 'Đã đặt hàng';
                            } elseif ($status == 'On Delivery') {
                                $status_text = 'Đang giao hàng';
                            } elseif ($status == 'Delivered') {
                                $status_text = 'Đã giao hàng';
                            } elseif ($status == 'Cancelled') {
                                $status_text = 'Đã hủy';
                            }
                            echo htmlspecialchars($status_text); 
                            ?>
                        </span>
                    </div>
                    
                    <div class="order-details">
                        <?php foreach($order_data['items'] as $item): ?>
                        <div class="order-detail-item" style="grid-column: span 2;">
                            <span class="order-detail-label"><?php echo htmlspecialchars($item['food']); ?> x<?php echo $item['qty']; ?></span>
                            <span class="order-detail-value"><?php echo number_format($item['total'], 0, ',', '.'); ?> đ</span>
                        </div>
                        <?php endforeach; ?>
                        <div class="order-detail-item" style="grid-column: span 2; margin-top: 10px; padding-top: 10px; border-top: 2px solid #eee;">
                            <span class="order-detail-label"><strong>Tổng tiền đơn hàng</strong></span>
                            <span class="order-detail-value" style="color: #ff6b81; font-size: 1.2em;"><?php echo number_format($order_total, 0, ',', '.'); ?> đ</span>
                        </div>
                    </div>
                    
                    <!-- Thông tin thanh toán -->
                    <?php
                    $payment_status = $order_data['payment_status'] ?? 'pending';
                    $payment_method = $order_data['payment_method'] ?? '';
                    $transaction_id = $order_data['transaction_id'] ?? '';
                    
                    if($payment_status == 'paid' || $payment_status == 'success') {
                        $payment_status_text = 'Đã thanh toán';
                        $payment_status_class = 'payment-paid';
                    } elseif($payment_status == 'pending') {
                        $payment_status_text = 'Chờ thanh toán';
                        $payment_status_class = 'payment-pending';
                    } elseif($payment_status == 'failed') {
                        $payment_status_text = 'Thanh toán thất bại';
                        $payment_status_class = 'payment-failed';
                    } elseif($payment_status == 'refunded') {
                        $payment_status_text = 'Đã hoàn tiền';
                        $payment_status_class = 'payment-refunded';
                    } else {
                        $payment_status_text = 'Chưa thanh toán';
                        $payment_status_class = 'payment-unpaid';
                    }
                    ?>
                    <div class="order-details" style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-top: 15px;">
                        <div class="order-detail-item">
                            <span class="order-detail-label">Trạng thái thanh toán</span>
                            <span class="order-detail-value <?php echo $payment_status_class; ?>">
                                <?php echo $payment_status_text; ?>
                            </span>
                        </div>
                        <?php if($payment_method): ?>
                        <div class="order-detail-item">
                            <span class="order-detail-label">Phương thức</span>
                            <span class="order-detail-value">
                                <?php 
                                $method_names = [
                                    'vnpay' => 'VNPay',
                                    'momo' => 'MoMo',
                                    'bank' => 'Chuyển khoản',
                                    'cash' => 'Tiền mặt'
                                ];
                                echo $method_names[$payment_method] ?? strtoupper($payment_method);
                                ?>
                            </span>
                        </div>
                        <?php endif; ?>
                        <?php if($transaction_id): ?>
                        <div class="order-detail-item">
                            <span class="order-detail-label">Mã giao dịch</span>
                            <span class="order-detail-value" style="font-size: 0.9em;"><?php echo htmlspecialchars($transaction_id); ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if($payment_status == 'pending' && $status == 'pending'): ?>
                        <div class="order-detail-item" style="grid-column: span 3;">
                            <a href="<?php echo SITEURL; ?>user/payment.php?order_code=<?php echo urlencode($order_code); ?>" 
                               class="btn-chat" style="display: inline-block; margin-top: 10px;">
                                💳 Thanh toán ngay
                            </a>
                        </div>
                        <?php endif; ?>
                        
                        <?php 
                        // Kiểm tra đã có yêu cầu hoàn tiền chưa
                        $has_refund_request = false;
                        $refund_status_text = '';
                        $check_refund_sql = "SELECT refund_status FROM tbl_refund WHERE order_code = ? AND refund_status IN ('pending', 'processing', 'completed') LIMIT 1";
                        $check_refund_stmt = mysqli_prepare($conn, $check_refund_sql);
                        if($check_refund_stmt) {
                            mysqli_stmt_bind_param($check_refund_stmt, "s", $order_code);
                            mysqli_stmt_execute($check_refund_stmt);
                            $refund_result = mysqli_stmt_get_result($check_refund_stmt);
                            if($refund_row = mysqli_fetch_assoc($refund_result)) {
                                $has_refund_request = true;
                                $refund_status = $refund_row['refund_status'];
                                if($refund_status == 'pending') {
                                    $refund_status_text = 'Đã gửi yêu cầu hoàn tiền (Chờ xử lý)';
                                } elseif($refund_status == 'processing') {
                                    $refund_status_text = 'Yêu cầu hoàn tiền đang được xử lý';
                                } elseif($refund_status == 'completed') {
                                    $refund_status_text = 'Đã hoàn tiền';
                                }
                            }
                            mysqli_stmt_close($check_refund_stmt);
                        }
                        
                        if(($payment_status == 'paid' || $payment_status == 'success') && $status != 'Cancelled'): 
                            if($has_refund_request): ?>
                        <div class="order-detail-item" style="grid-column: span 3;">
                            <span style="color: #ff9800; font-weight: bold;"><?php echo $refund_status_text; ?></span>
                        </div>
                            <?php else: ?>
                        <div class="order-detail-item" style="grid-column: span 3;">
                            <a href="<?php echo SITEURL; ?>user/request-refund.php?order_code=<?php echo urlencode($order_code); ?>" 
                               class="btn-chat" style="display: inline-block; margin-top: 10px; background: #ff9800;">
                                💰 Yêu cầu hoàn tiền
                            </a>
                        </div>
                            <?php endif; 
                        endif; ?>
                    </div>
                    
                    <div class="order-details">
                        <div class="order-detail-item">
                            <span class="order-detail-label">Người nhận</span>
                            <span class="order-detail-value"><?php echo htmlspecialchars($customer_name); ?></span>
                        </div>
                        <div class="order-detail-item" style="grid-column: span 2;">
                            <span class="order-detail-label">Địa chỉ giao hàng</span>
                            <span class="order-detail-value"><?php echo htmlspecialchars($customer_address); ?></span>
                        </div>
                    </div>
                    
                    <div class="order-actions">
                        <a href="<?php echo SITEURL; ?>user/chat.php?order_code=<?php echo urlencode($order_code); ?>" class="btn-chat">
                            💬 Chat hỗ trợ đơn này
                        </a>
                    </div>
                </div>
                <?php
            }
        } else {
            ?>
            <div class="no-orders">
                <div class="no-orders-icon">📭</div>
                <h2>Chưa có đơn hàng nào</h2>
                <p>Bạn chưa đặt đơn hàng nào. Hãy khám phá menu và đặt món ngay!</p>
                <a href="<?php echo SITEURL; ?>food.php" style="display: inline-block; margin-top: 20px; padding: 12px 30px; background: linear-gradient(135deg, #ff6b81 0%, #ff4757 100%); color: white; text-decoration: none; border-radius: 5px; font-weight: bold;">
                    Xem menu
                </a>
            </div>
            <?php
        }
        ?>
    </div>

    <?php include('../partials-front/footer.php'); ?>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        function copyOrderCode(orderCode) {
            navigator.clipboard.writeText(orderCode).then(function() {
                Swal.fire({
                    icon: 'success',
                    title: 'Đã copy!',
                    text: 'Mã đơn hàng: ' + orderCode,
                    timer: 2000,
                    showConfirmButton: false
                });
            }, function(err) {
                // Fallback cho trình duyệt không hỗ trợ clipboard API
                const textArea = document.createElement('textarea');
                textArea.value = orderCode;
                document.body.appendChild(textArea);
                textArea.select();
                document.execCommand('copy');
                document.body.removeChild(textArea);
                
                Swal.fire({
                    icon: 'success',
                    title: 'Đã copy!',
                    text: 'Mã đơn hàng: ' + orderCode,
                    timer: 2000,
                    showConfirmButton: false
                });
            });
        }
    </script>
</body>
</html>

