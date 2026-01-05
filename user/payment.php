<?php
include('../config/constants.php');

// Kiểm tra đăng nhập
if(!isset($_SESSION['user_id'])) {
    header('location:'.SITEURL.'user/login.php');
    exit();
}

$order_code = isset($_GET['order_code']) ? $_GET['order_code'] : (isset($_SESSION['order_code']) ? $_SESSION['order_code'] : '');

if(empty($order_code)) {
    $_SESSION['payment-error'] = "Không tìm thấy đơn hàng!";
    header('location:'.SITEURL.'user/order-history.php');
    exit();
}

// Lấy thông tin đơn hàng
$order_sql = "SELECT * FROM tbl_order WHERE order_code = ? AND user_id = ? LIMIT 1";
$stmt = mysqli_prepare($conn, $order_sql);
mysqli_stmt_bind_param($stmt, "si", $order_code, $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if(mysqli_num_rows($result) == 0) {
    mysqli_stmt_close($stmt);
    $_SESSION['payment-error'] = "Không tìm thấy đơn hàng!";
    header('location:'.SITEURL.'user/order-history.php');
    exit();
}

$order = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

// Tính tổng tiền đơn hàng
$total_sql = "SELECT SUM(total) as total FROM tbl_order WHERE order_code = ?";
$stmt = mysqli_prepare($conn, $total_sql);
mysqli_stmt_bind_param($stmt, "s", $order_code);
mysqli_stmt_execute($stmt);
$total_result = mysqli_stmt_get_result($stmt);
$total_data = mysqli_fetch_assoc($total_result);
$order_total = floatval($total_data['total']);
mysqli_stmt_close($stmt);

// Xử lý thanh toán (mock - có thể tích hợp VNPay, Momo, etc.)
if(isset($_POST['confirm_payment'])) {
    // Cập nhật trạng thái đơn hàng
    $update_sql = "UPDATE tbl_order SET status = 'ordered' WHERE order_code = ?";
    $stmt = mysqli_prepare($conn, $update_sql);
    mysqli_stmt_bind_param($stmt, "s", $order_code);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    
    unset($_SESSION['order_code']);
    $_SESSION['order-success'] = "Thanh toán thành công! Mã đơn hàng: " . $order_code;
    header('location:'.SITEURL.'user/order-history.php');
    exit();
}

include('../partials-front/menu.php');
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thanh toán online - WowFood</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .payment-container {
            max-width: 600px;
            margin: 100px auto 50px;
            padding: 30px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .payment-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #ff6b81;
        }
        .payment-header h1 {
            color: #2f3542;
            margin-bottom: 10px;
        }
        .order-info {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #ddd;
        }
        .info-row:last-child {
            border-bottom: none;
            font-size: 1.3em;
            font-weight: bold;
            color: #ff6b81;
        }
        .payment-methods-list {
            margin: 20px 0;
        }
        .payment-method-item {
            padding: 15px;
            border: 2px solid #ddd;
            border-radius: 8px;
            margin-bottom: 10px;
            cursor: pointer;
            transition: all 0.3s;
        }
        .payment-method-item:hover {
            border-color: #ff6b81;
        }
        .payment-method-item.active {
            border-color: #ff6b81;
            background: #fff5f7;
        }
        .method-icon {
            font-size: 2em;
            margin-right: 10px;
        }
        .submit-payment-btn {
            width: 100%;
            padding: 15px;
            background: #ff6b81;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1.2em;
            font-weight: bold;
            cursor: pointer;
            margin-top: 20px;
        }
        .submit-payment-btn:hover {
            background: #ff4757;
        }
        .note {
            background: #e3f2fd;
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
            font-size: 0.9em;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="payment-container">
        <div class="payment-header">
            <h1>💳 Thanh toán online</h1>
            <p>Mã đơn hàng: <strong><?php echo htmlspecialchars($order_code); ?></strong></p>
        </div>

        <div class="order-info">
            <div class="info-row">
                <span>Tổng tiền đơn hàng:</span>
                <span><?php echo number_format($order_total, 0, ',', '.'); ?> đ</span>
            </div>
        </div>

        <form method="POST" action="">
            <div class="payment-methods-list">
                <label class="payment-method-item active" onclick="selectMethod('vnpay')">
                    <input type="radio" name="payment_method" value="vnpay" checked style="display: none;">
                    <span class="method-icon">🏦</span>
                    <span><strong>VNPay</strong> - Thanh toán qua cổng VNPay</span>
                </label>
                <label class="payment-method-item" onclick="selectMethod('momo')">
                    <input type="radio" name="payment_method" value="momo" style="display: none;">
                    <span class="method-icon">💜</span>
                    <span><strong>MoMo</strong> - Ví điện tử MoMo</span>
                </label>
                <label class="payment-method-item" onclick="selectMethod('bank')">
                    <input type="radio" name="payment_method" value="bank" style="display: none;">
                    <span class="method-icon">🏧</span>
                    <span><strong>Chuyển khoản</strong> - Chuyển khoản ngân hàng</span>
                </label>
            </div>

            <div class="note">
                <strong>📝 Lưu ý:</strong> Đây là trang thanh toán mô phỏng. Trong môi trường thực tế, bạn sẽ được chuyển đến cổng thanh toán của nhà cung cấp.
            </div>

            <button type="submit" name="confirm_payment" class="submit-payment-btn">
                Xác nhận thanh toán
            </button>
        </form>
    </div>

    <?php include('../partials-front/footer.php'); ?>

    <script>
        function selectMethod(method) {
            document.querySelectorAll('.payment-method-item').forEach(item => {
                item.classList.remove('active');
            });
            event.currentTarget.classList.add('active');
            event.currentTarget.querySelector('input[type="radio"]').checked = true;
        }
    </script>
</body>
</html>

