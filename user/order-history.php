<?php
require_once('../config/constants.php');

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    $_SESSION['access-denied'] = "Vui lòng đăng nhập để xem lịch sử đặt hàng";
    header('location:'.SITEURL.'user/login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Lấy lịch sử đặt hàng của user
$sql = "SELECT * FROM tbl_order WHERE user_id = $user_id ORDER BY order_date DESC";
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
    </style>
</head>
<body>
    <div class="order-history-container">
        <div class="order-history-header">
            <h1>📦 Lịch sử đặt hàng</h1>
            <p>Xem và quản lý các đơn hàng của bạn</p>
        </div>

        <?php
        if (mysqli_num_rows($res) > 0) {
            while ($row = mysqli_fetch_assoc($res)) {
                $order_code = $row['order_code'] ?? 'N/A';
                $order_date = $row['order_date'];
                $status = $row['status'];
                $food = $row['food'];
                $qty = $row['qty'];
                $price = $row['price'];
                $total = $row['total'];
                $customer_name = $row['customer_name'];
                $customer_address = $row['customer_address'];
                
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
                        <div class="order-detail-item">
                            <span class="order-detail-label">Món ăn</span>
                            <span class="order-detail-value"><?php echo htmlspecialchars($food); ?></span>
                        </div>
                        <div class="order-detail-item">
                            <span class="order-detail-label">Số lượng</span>
                            <span class="order-detail-value"><?php echo $qty; ?></span>
                        </div>
                        <div class="order-detail-item">
                            <span class="order-detail-label">Đơn giá</span>
                            <span class="order-detail-value">$<?php echo number_format($price, 2); ?></span>
                        </div>
                        <div class="order-detail-item">
                            <span class="order-detail-label">Tổng tiền</span>
                            <span class="order-detail-value" style="color: #ff6b81;">$<?php echo number_format($total, 2); ?></span>
                        </div>
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

