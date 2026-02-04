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
        .refund-page {
            max-width: 900px;
            margin: 100px auto 50px;
            padding: 20px;
        }
        .refund-header {
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 2px solid #ff6b81;
        }
        .refund-header h1 {
            color: #2f3542;
            margin-bottom: 8px;
        }
        .refund-subtitle {
            color: #747d8c;
            font-size: 0.95rem;
        }
        .refund-content {
            display: grid;
            grid-template-columns: 1.1fr 0.9fr;
            gap: 25px;
        }
        .refund-section {
            background: #ffffff;
            border-radius: 10px;
            padding: 20px 22px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        .refund-section h2 {
            font-size: 1.2rem;
            margin-bottom: 18px;
            color: #2f3542;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .refund-section h2 span.emoji {
            font-size: 1.3rem;
        }
        .order-info-box {
            background: #f8f9fb;
            border-radius: 8px;
            padding: 16px 18px;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid #e3e6ea;
            font-size: 0.95rem;
        }
        .info-row:last-child {
            border-bottom: none;
            margin-top: 4px;
            font-size: 1.1rem;
            font-weight: 600;
            color: #ff6b81;
        }
        .info-label {
            color: #57606f;
        }
        .info-value {
            font-weight: 500;
            color: #2f3542;
        }
        .form-group {
            margin-bottom: 18px;
        }
        .form-group label {
            display: block;
            margin-bottom: 6px;
            color: #2f3542;
            font-weight: 600;
            font-size: 0.95rem;
        }
        .form-group small {
            display: block;
            margin-top: 5px;
            color: #8395a7;
            font-size: 0.85rem;
        }
        .form-control,
        .form-textarea {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #dde1e7;
            border-radius: 7px;
            font-size: 0.95rem;
            box-sizing: border-box;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }
        .form-textarea {
            min-height: 120px;
            resize: vertical;
        }
        .form-control:focus,
        .form-textarea:focus {
            outline: none;
            border-color: #ff6b81;
            box-shadow: 0 0 0 3px rgba(255,107,129,0.15);
        }
        .note-box {
            background: #fff7f9;
            border-radius: 8px;
            padding: 14px 16px;
            font-size: 0.9rem;
            color: #555;
            margin-top: 10px;
            border-left: 3px solid #ff6b81;
        }
        .note-box ul {
            margin: 8px 0 0;
            padding-left: 18px;
        }
        .note-box li {
            margin-bottom: 4px;
        }
        .helper-text {
            font-size: 0.85rem;
            color: #95a5a6;
            margin-top: 6px;
        }
        .submit-btn {
            width: 100%;
            padding: 13px 0;
            background: linear-gradient(135deg, #ff6b81, #ff8fa6);
            color: #fff;
            border: none;
            border-radius: 999px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            margin-top: 18px;
            box-shadow: 0 8px 18px rgba(255,107,129,0.35);
            transition: transform 0.15s ease, box-shadow 0.15s ease, background 0.15s ease;
        }
        .submit-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 10px 22px rgba(255,107,129,0.45);
            background: linear-gradient(135deg, #ff526b, #ff7b92);
        }
        .submit-btn:active {
            transform: translateY(0);
            box-shadow: 0 4px 10px rgba(255,107,129,0.3);
        }
        .error-message,
        .success-message {
            margin-bottom: 18px;
            padding: 12px 14px;
            border-radius: 8px;
            font-size: 0.9rem;
            display: flex;
            gap: 8px;
            align-items: center;
        }
        .error-message {
            background: #ffebee;
            color: #c62828;
            border-left: 4px solid #c62828;
        }
        .success-message {
            background: #e8f5e9;
            color: #2e7d32;
            border-left: 4px solid #2e7d32;
        }
        .refund-meta {
            margin-top: 12px;
            font-size: 0.86rem;
            color: #96a0b5;
        }
        @media (max-width: 768px) {
            .refund-page {
                margin-top: 80px;
            }
            .refund-content {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="refund-page">
        <div class="refund-header">
            <h1>💰 Yêu cầu hoàn tiền</h1>
            <p class="refund-subtitle">
                Chúng tôi luôn mong muốn mang lại trải nghiệm tốt nhất. Nếu đơn hàng có vấn đề, hãy gửi yêu cầu hoàn tiền và đội ngũ WowFood sẽ hỗ trợ bạn sớm nhất có thể.
            </p>
            <div class="refund-meta">
                Mã đơn hàng: <strong><?php echo htmlspecialchars($order_code); ?></strong>
            </div>
        </div>

        <?php if(isset($_SESSION['refund-error'])): ?>
            <div class="error-message">
                <span>❌</span>
                <span><?php echo htmlspecialchars($_SESSION['refund-error']); ?></span>
                <?php unset($_SESSION['refund-error']); ?>
            </div>
        <?php endif; ?>

        <div class="refund-content">
            <!-- Thông tin đơn hàng -->
            <div class="refund-section">
                <h2><span class="emoji">🧾</span><span>Thông tin đơn hàng</span></h2>
                <div class="order-info-box">
                    <div class="info-row">
                        <span class="info-label">Món ăn</span>
                        <span class="info-value"><?php echo htmlspecialchars($order['food']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Số lượng</span>
                        <span class="info-value"><?php echo $order['qty']; ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Ngày đặt</span>
                        <span class="info-value"><?php echo date('d/m/Y H:i', strtotime($order['order_date'])); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Tổng tiền</span>
                        <span class="info-value"><?php echo number_format($order_total, 0, ',', '.'); ?> đ</span>
                    </div>
                </div>

                <div class="note-box">
                    <strong>Gợi ý nhỏ:</strong>
                    <ul>
                        <li>Hãy mô tả chi tiết vấn đề để chúng tôi hỗ trợ nhanh hơn.</li>
                        <li>Nếu có ảnh chụp món ăn lỗi, hãy chuẩn bị để gửi qua chat khi được yêu cầu.</li>
                    </ul>
                </div>
            </div>

            <!-- Form hoàn tiền -->
            <div class="refund-section">
                <h2><span class="emoji">✉️</span><span>Thông tin yêu cầu hoàn tiền</span></h2>

                <form method="POST" action="">
                    <div class="form-group">
                        <label>Số tiền muốn hoàn *</label>
                        <input
                            type="number"
                            name="refund_amount"
                            class="form-control"
                            value="<?php echo $order_total; ?>"
                            min="0"
                            max="<?php echo $order_total; ?>"
                            step="0.01"
                            required
                        >
                        <small>Tối đa: <?php echo number_format($order_total, 0, ',', '.'); ?> đ (bạn có thể yêu cầu hoàn một phần hoặc toàn bộ)</small>
                    </div>

                    <div class="form-group">
                        <label>Lý do yêu cầu hoàn tiền *</label>
                        <textarea
                            name="refund_reason"
                            class="form-textarea"
                            required
                            maxlength="500"
                            placeholder="Ví dụ: Món ăn bị nguội, giao thiếu món, đơn hàng bị hủy, không nhận được hàng..."
                        ><?php echo htmlspecialchars($_POST['refund_reason'] ?? ''); ?></textarea>
                        <small>Tối đa 500 ký tự – bạn mô tả càng rõ, chúng tôi hỗ trợ càng nhanh.</small>
                    </div>

                    <p class="helper-text">
                        Bằng việc gửi yêu cầu, bạn đồng ý để WowFood kiểm tra lại đơn hàng và liên hệ với bạn nếu cần thêm thông tin.
                    </p>

                    <button type="submit" name="submit_refund_request" class="submit-btn">
                        Gửi yêu cầu hoàn tiền
                    </button>
                </form>
            </div>
        </div>
    </div>

    <?php include('../partials-front/footer.php'); ?>
</body>
</html>

