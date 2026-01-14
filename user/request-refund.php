<?php
include('../config/constants.php');

// Kiểm tra đăng nhập
if(!isset($_SESSION['user_id'])) {
    $_SESSION['no-login-message'] = "Vui lòng đăng nhập!";
    header('location:'.SITEURL.'user/login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$order_code = $_GET['order_code'] ?? '';

if(empty($order_code)) {
    $_SESSION['refund-error'] = "Không tìm thấy mã đơn hàng!";
    header('location:'.SITEURL.'user/order-history.php');
    exit();
}

// Lấy thông tin đơn hàng
$order_sql = "SELECT * FROM tbl_order WHERE order_code = ? AND user_id = ? LIMIT 1";
$stmt = mysqli_prepare($conn, $order_sql);
mysqli_stmt_bind_param($stmt, "si", $order_code, $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$order = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if(!$order) {
    $_SESSION['refund-error'] = "Không tìm thấy đơn hàng!";
    header('location:'.SITEURL.'user/order-history.php');
    exit();
}

// Tính tổng tiền đơn hàng
$total_sql = "SELECT SUM(total) as total FROM tbl_order WHERE order_code = ?";
$stmt = mysqli_prepare($conn, $total_sql);
mysqli_stmt_bind_param($stmt, "s", $order_code);
mysqli_stmt_execute($stmt);
$total_result = mysqli_stmt_get_result($stmt);
$total_data = mysqli_fetch_assoc($total_result);
$order_total = floatval($total_data['total']);
mysqli_stmt_close($stmt);

// Kiểm tra đơn hàng đã được thanh toán chưa
$payment_status = $order['payment_status'] ?? 'pending';
if($payment_status != 'paid' && $payment_status != 'success') {
    $_SESSION['refund-error'] = "Chỉ có thể yêu cầu hoàn tiền cho đơn hàng đã thanh toán!";
    header('location:'.SITEURL.'user/order-history.php');
    exit();
}

// Kiểm tra đã yêu cầu hoàn tiền chưa
$check_refund_sql = "SELECT * FROM tbl_refund WHERE order_code = ? AND refund_status IN ('pending', 'processing')";
$stmt = mysqli_prepare($conn, $check_refund_sql);
if($stmt) {
    mysqli_stmt_bind_param($stmt, "s", $order_code);
    mysqli_stmt_execute($stmt);
    $refund_check = mysqli_stmt_get_result($stmt);
    if(mysqli_num_rows($refund_check) > 0) {
        mysqli_stmt_close($stmt);
        $_SESSION['refund-error'] = "Bạn đã gửi yêu cầu hoàn tiền cho đơn hàng này. Vui lòng chờ admin xử lý!";
        header('location:'.SITEURL.'user/order-history.php');
        exit();
    }
    mysqli_stmt_close($stmt);
}

// Xử lý submit yêu cầu hoàn tiền
if(isset($_POST['submit_refund_request'])) {
    $refund_reason = mysqli_real_escape_string($conn, $_POST['refund_reason'] ?? '');
    $refund_amount = floatval($_POST['refund_amount'] ?? $order_total);
    
    if(empty($refund_reason)) {
        $_SESSION['refund-error'] = "Vui lòng nhập lý do hoàn tiền!";
    } elseif($refund_amount <= 0 || $refund_amount > $order_total) {
        $_SESSION['refund-error'] = "Số tiền hoàn tiền không hợp lệ!";
    } else {
        // Kiểm tra bảng refund có tồn tại không
        $refund_table_exists = false;
        $check_table_sql = "SHOW TABLES LIKE 'tbl_refund'";
        $table_result = mysqli_query($conn, $check_table_sql);
        if($table_result && mysqli_num_rows($table_result) > 0) {
            $refund_table_exists = true;
        }
        
        // Tìm payment_id nếu có
        $payment_id = null;
        $check_payment_sql = "SHOW TABLES LIKE 'tbl_payment'";
        $payment_table_result = mysqli_query($conn, $check_payment_sql);
        if($payment_table_result && mysqli_num_rows($payment_table_result) > 0) {
            $payment_sql = "SELECT id FROM tbl_payment WHERE order_code = ? AND payment_status IN ('success', 'paid') ORDER BY id DESC LIMIT 1";
            $stmt = mysqli_prepare($conn, $payment_sql);
            if($stmt) {
                mysqli_stmt_bind_param($stmt, "s", $order_code);
                mysqli_stmt_execute($stmt);
                $payment_result = mysqli_stmt_get_result($stmt);
                $payment_data = mysqli_fetch_assoc($payment_result);
                if($payment_data) {
                    $payment_id = $payment_data['id'];
                }
                mysqli_stmt_close($stmt);
            }
        }
        
        // Tạo refund request
        if($refund_table_exists) {
            $insert_sql = "INSERT INTO tbl_refund (order_code, payment_id, user_id, refund_amount, refund_reason, refund_status, refund_method, processed_by) 
                           VALUES (?, ?, ?, ?, ?, 'pending', 'original', NULL)";
            $stmt = mysqli_prepare($conn, $insert_sql);
            if($stmt) {
                mysqli_stmt_bind_param($stmt, "siids", $order_code, $payment_id, $user_id, $refund_amount, $refund_reason);
                if(mysqli_stmt_execute($stmt)) {
                    $refund_id = mysqli_insert_id($conn);
                    mysqli_stmt_close($stmt);
                    
                    $_SESSION['refund-success'] = "Đã gửi yêu cầu hoàn tiền thành công! Mã yêu cầu: #" . $refund_id . ". Admin sẽ xử lý trong thời gian sớm nhất.";
                    header('location:'.SITEURL.'user/order-history.php');
                    exit();
                } else {
                    $_SESSION['refund-error'] = "Có lỗi xảy ra khi tạo yêu cầu hoàn tiền!";
                }
            }
        } else {
            // Nếu bảng chưa tồn tại, lưu vào session để admin xử lý thủ công
            $_SESSION['refund-requests'][$order_code] = [
                'order_code' => $order_code,
                'user_id' => $user_id,
                'refund_amount' => $refund_amount,
                'refund_reason' => $refund_reason,
                'created_at' => date('Y-m-d H:i:s')
            ];
            $_SESSION['refund-success'] = "Đã gửi yêu cầu hoàn tiền! Admin sẽ xử lý trong thời gian sớm nhất.";
            header('location:'.SITEURL.'user/order-history.php');
            exit();
        }
    }
}

include('../partials-front/menu.php');
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yêu cầu hoàn tiền - WowFood</title>
    <link rel="stylesheet" href="<?php echo SITEURL; ?>css/style.css">
    <style>
        .refund-container {
            max-width: 700px;
            margin: 100px auto 50px;
            padding: 30px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .refund-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #ff6b81;
        }
        .refund-header h1 {
            color: #2f3542;
            margin-bottom: 10px;
        }
        .order-info-box {
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
            font-size: 1.2em;
            font-weight: bold;
            color: #ff6b81;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #2f3542;
            font-weight: bold;
        }
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 1em;
            box-sizing: border-box;
        }
        .form-group textarea {
            min-height: 120px;
            resize: vertical;
        }
        .submit-btn {
            width: 100%;
            padding: 15px;
            background: #ff6b81;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1.1em;
            font-weight: bold;
            cursor: pointer;
            margin-top: 20px;
        }
        .submit-btn:hover {
            background: #ff4757;
        }
        .note-box {
            background: #e3f2fd;
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
            font-size: 0.9em;
            color: #666;
        }
        .error-message {
            background: #ffebee;
            color: #c62828;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #c62828;
        }
        .success-message {
            background: #e8f5e9;
            color: #2e7d32;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #2e7d32;
        }
    </style>
</head>
<body>
    <div class="refund-container">
        <div class="refund-header">
            <h1>💰 Yêu cầu hoàn tiền</h1>
            <p>Mã đơn hàng: <strong><?php echo htmlspecialchars($order_code); ?></strong></p>
        </div>

        <?php if(isset($_SESSION['refund-error'])): ?>
            <div class="error-message">
                <strong>❌ Lỗi:</strong> <?php echo htmlspecialchars($_SESSION['refund-error']); ?>
                <?php unset($_SESSION['refund-error']); ?>
            </div>
        <?php endif; ?>

        <div class="order-info-box">
            <div class="info-row">
                <span>Món ăn:</span>
                <span><?php echo htmlspecialchars($order['food']); ?></span>
            </div>
            <div class="info-row">
                <span>Số lượng:</span>
                <span><?php echo $order['qty']; ?></span>
            </div>
            <div class="info-row">
                <span>Ngày đặt:</span>
                <span><?php echo date('d/m/Y H:i', strtotime($order['order_date'])); ?></span>
            </div>
            <div class="info-row">
                <span>Tổng tiền đơn hàng:</span>
                <span><?php echo number_format($order_total, 0, ',', '.'); ?> đ</span>
            </div>
        </div>

        <form method="POST" action="">
            <div class="form-group">
                <label>Số tiền yêu cầu hoàn *</label>
                <input type="number" name="refund_amount" value="<?php echo $order_total; ?>" 
                       min="0" max="<?php echo $order_total; ?>" step="0.01" required>
                <small style="color: #666;">Tối đa: <?php echo number_format($order_total, 0, ',', '.'); ?> đ</small>
            </div>

            <div class="form-group">
                <label>Lý do yêu cầu hoàn tiền *</label>
                <textarea name="refund_reason" required placeholder="Vui lòng mô tả lý do bạn yêu cầu hoàn tiền (ví dụ: Đơn hàng bị hủy, sản phẩm lỗi, không nhận được hàng...)" 
                          maxlength="500"><?php echo htmlspecialchars($_POST['refund_reason'] ?? ''); ?></textarea>
                <small style="color: #666;">Tối đa 500 ký tự</small>
            </div>

            <div class="note-box">
                <strong>📝 Lưu ý:</strong>
                <ul style="margin: 10px 0; padding-left: 20px;">
                    <li>Yêu cầu hoàn tiền sẽ được gửi đến admin để xem xét</li>
                    <li>Thời gian xử lý: 1-3 ngày làm việc</li>
                    <li>Bạn sẽ nhận được thông báo khi yêu cầu được xử lý</li>
                    <li>Tiền sẽ được hoàn về phương thức thanh toán ban đầu</li>
                </ul>
            </div>

            <button type="submit" name="submit_refund_request" class="submit-btn">
                Gửi yêu cầu hoàn tiền
            </button>
        </form>
    </div>

    <?php include('../partials-front/footer.php'); ?>
</body>
</html>

