<?php
// Bắt đầu output buffering để tránh output trước header redirect
ob_start();

// Bật error reporting để debug
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Log file để debug
$log_file = __DIR__ . '/../logs/payment_debug.log';

try {
    include('../config/constants.php');
} catch (Exception $e) {
    file_put_contents($log_file, "ERROR: Failed to include constants.php - " . $e->getMessage() . "\n", FILE_APPEND);
    die("Lỗi hệ thống. Vui lòng thử lại sau.");
}

// Kiểm tra đăng nhập
if(!isset($_SESSION['user_id'])) {
    ob_end_clean();
    header('location:'.SITEURL.'user/login.php');
    exit();
}

$order_code = isset($_GET['order_code']) ? $_GET['order_code'] : (isset($_SESSION['order_code']) ? $_SESSION['order_code'] : '');

if(empty($order_code)) {
    $_SESSION['payment-error'] = "Không tìm thấy đơn hàng!";
    header('location:'.SITEURL.'user/order-history.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Lấy thông tin đơn hàng
$order_sql = "SELECT * FROM tbl_order WHERE order_code = ? AND user_id = ? LIMIT 1";
$stmt = mysqli_prepare($conn, $order_sql);
mysqli_stmt_bind_param($stmt, "si", $order_code, $user_id);
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

// Kiểm tra trạng thái thanh toán
$payment_status = $order['payment_status'] ?? 'pending';
if($payment_status == 'paid') {
    $_SESSION['payment-error'] = "Đơn hàng này đã được thanh toán!";
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

// Kiểm tra payment record đã tồn tại chưa (kiểm tra bảng có tồn tại không)
$existing_payment = null;
$payment_table_exists = false;

// Kiểm tra xem bảng tbl_payment có tồn tại không
$check_table_sql = "SHOW TABLES LIKE 'tbl_payment'";
$table_result = mysqli_query($conn, $check_table_sql);
if(mysqli_num_rows($table_result) > 0) {
    $payment_table_exists = true;
    $payment_check_sql = "SELECT * FROM tbl_payment WHERE order_code = ? AND payment_status = 'pending' ORDER BY id DESC LIMIT 1";
    $stmt = mysqli_prepare($conn, $payment_check_sql);
    if($stmt) {
        mysqli_stmt_bind_param($stmt, "s", $order_code);
        mysqli_stmt_execute($stmt);
        $payment_result = mysqli_stmt_get_result($stmt);
        $existing_payment = mysqli_fetch_assoc($payment_result);
        mysqli_stmt_close($stmt);
    }
}

// Kiểm tra timeout (15 phút)
$payment_expired = false;
if($existing_payment && $existing_payment['expires_at']) {
    if(strtotime($existing_payment['expires_at']) < time()) {
        $payment_expired = true;
        // Cập nhật payment status thành cancelled
        $cancel_sql = "UPDATE tbl_payment SET payment_status = 'cancelled', failure_reason = 'Hết thời gian thanh toán' WHERE id = ?";
        $stmt = mysqli_prepare($conn, $cancel_sql);
        mysqli_stmt_bind_param($stmt, "i", $existing_payment['id']);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
}

// Log mọi request để debug
if(!isset($log_file)) {
    $log_file = __DIR__ . '/../logs/payment_debug.log';
}
$initial_log = "\n" . str_repeat("=", 80) . "\n";
$initial_log .= date('Y-m-d H:i:s') . " - PAGE LOADED\n";
$initial_log .= "REQUEST METHOD: " . $_SERVER['REQUEST_METHOD'] . "\n";
$initial_log .= "POST data exists: " . (isset($_POST) ? 'Yes' : 'No') . "\n";
$initial_log .= "POST confirm_payment: " . (isset($_POST['confirm_payment']) ? $_POST['confirm_payment'] : 'Not set') . "\n";
$initial_log .= "GET order_code: " . (isset($_GET['order_code']) ? $_GET['order_code'] : 'Not set') . "\n";
file_put_contents($log_file, $initial_log, FILE_APPEND);

// Xử lý thanh toán - PHẢI XỬ LÝ TRƯỚC KHI INCLUDE MENU
// Kiểm tra: có confirm_payment HOẶC có order_code + payment_method trong POST
$is_payment_submit = (isset($_POST['confirm_payment']) && !empty($_POST['confirm_payment'])) 
                     || (isset($_POST['order_code']) && isset($_POST['payment_method']) && $_SERVER['REQUEST_METHOD'] == 'POST');

if($is_payment_submit) {
    // Nếu không có confirm_payment nhưng có order_code, tự động set
    if(!isset($_POST['confirm_payment']) && isset($_POST['order_code'])) {
        $_POST['confirm_payment'] = '1';
    }
    // Wrap toàn bộ xử lý trong try-catch để bắt lỗi
    try {
    $log_entry = "\n" . str_repeat("=", 80) . "\n";
    $log_entry .= date('Y-m-d H:i:s') . " - PAYMENT FORM SUBMITTED\n";
    $log_entry .= str_repeat("=", 80) . "\n";
    $log_entry .= "POST data: " . print_r($_POST, true) . "\n";
    $log_entry .= "GET data: " . print_r($_GET, true) . "\n";
    $log_entry .= "Order code: " . $order_code . "\n";
    $log_entry .= "User ID: " . $user_id . "\n";
    $log_entry .= "Order total: " . $order_total . "\n";
    $log_entry .= "Payment method: " . ($_POST['payment_method'] ?? 'not set') . "\n";
    $log_entry .= "Headers sent: " . (headers_sent() ? 'Yes' : 'No') . "\n";
    $log_entry .= "Output buffer level: " . ob_get_level() . "\n";
    file_put_contents($log_file, $log_entry, FILE_APPEND);
    
    // Log vào error_log cũng
    error_log("=== PAYMENT FORM SUBMITTED ===");
    error_log("POST data: " . print_r($_POST, true));
    error_log("Order code: " . $order_code);
    
    $payment_method = $_POST['payment_method'] ?? 'momo';
    $payment_id = null;
    
    $log_entry = "Step 1: Payment method = " . $payment_method . "\n";
    file_put_contents($log_file, $log_entry, FILE_APPEND);
    
    $log_entry = "Step 2: Payment table exists = " . ($payment_table_exists ? 'Yes' : 'No') . "\n";
    file_put_contents($log_file, $log_entry, FILE_APPEND);
    
    // Nếu bảng payment tồn tại, tạo/update payment record
    if($payment_table_exists) {
        // Tạo hoặc cập nhật payment record
        if($existing_payment && !$payment_expired) {
            $payment_id = $existing_payment['id'];
            $log_entry = "Step 3: Using existing payment ID = " . $payment_id . "\n";
            file_put_contents($log_file, $log_entry, FILE_APPEND);
            
            // Cập nhật payment method nếu khác
            $update_payment_sql = "UPDATE tbl_payment SET payment_method = ?, updated_at = NOW() WHERE id = ?";
            $stmt = mysqli_prepare($conn, $update_payment_sql);
            if($stmt) {
                mysqli_stmt_bind_param($stmt, "si", $payment_method, $payment_id);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
                $log_entry = "Step 4: Updated existing payment record\n";
                file_put_contents($log_file, $log_entry, FILE_APPEND);
            } else {
                $log_entry = "Step 4 ERROR: Failed to prepare update payment SQL: " . mysqli_error($conn) . "\n";
                file_put_contents($log_file, $log_entry, FILE_APPEND);
            }
        } else {
            // Tạo payment record mới
            $expires_at = date('Y-m-d H:i:s', time() + 900); // 15 phút
            $log_entry = "Step 3: Creating new payment record, expires_at = " . $expires_at . "\n";
            file_put_contents($log_file, $log_entry, FILE_APPEND);
            
            $insert_payment_sql = "INSERT INTO tbl_payment (order_code, user_id, payment_method, amount, payment_status, expires_at) VALUES (?, ?, ?, ?, 'pending', ?)";
            $stmt = mysqli_prepare($conn, $insert_payment_sql);
            if($stmt) {
                mysqli_stmt_bind_param($stmt, "sisds", $order_code, $user_id, $payment_method, $order_total, $expires_at);
                if(mysqli_stmt_execute($stmt)) {
                    $payment_id = mysqli_insert_id($conn);
                    $log_entry = "Step 4: Created new payment record, ID = " . $payment_id . "\n";
                    file_put_contents($log_file, $log_entry, FILE_APPEND);
                } else {
                    $log_entry = "Step 4 ERROR: Failed to execute insert payment: " . mysqli_stmt_error($stmt) . "\n";
                    file_put_contents($log_file, $log_entry, FILE_APPEND);
                }
                mysqli_stmt_close($stmt);
            } else {
                $log_entry = "Step 4 ERROR: Failed to prepare insert payment SQL: " . mysqli_error($conn) . "\n";
                file_put_contents($log_file, $log_entry, FILE_APPEND);
            }
            
            // Cập nhật order với payment_id và expires_at (nếu cột tồn tại)
            $update_order_sql = "UPDATE tbl_order SET payment_id = ?, expires_at = ? WHERE order_code = ?";
            $stmt = mysqli_prepare($conn, $update_order_sql);
            if($stmt) {
                mysqli_stmt_bind_param($stmt, "iss", $payment_id, $expires_at, $order_code);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
            }
        }
    }
    
    // Xử lý thanh toán
    // Nếu là MoMo, có thể tích hợp MoMo sandbox ở đây
    // Hiện tại mô phỏng thanh toán thành công
    
    $payment_success = true; // Mặc định thành công
    $log_entry = "Step 5: Processing payment, success = " . ($payment_success ? 'Yes' : 'No') . "\n";
    file_put_contents($log_file, $log_entry, FILE_APPEND);
    
    if($payment_success) {
        // Thanh toán thành công (mô phỏng)
        $transaction_id = 'TXN' . time() . rand(1000, 9999);
        $log_entry = "Step 6: Transaction ID generated = " . $transaction_id . "\n";
        file_put_contents($log_file, $log_entry, FILE_APPEND);
        
        // Cập nhật payment nếu có
        if($payment_table_exists && $payment_id) {
            $success_sql = "UPDATE tbl_payment SET 
                payment_status = 'success', 
                transaction_id = ?, 
                paid_at = NOW(),
                payment_gateway_response = ? 
                WHERE id = ?";
            $stmt = mysqli_prepare($conn, $success_sql);
            if($stmt) {
                $response = json_encode(['status' => 'success', 'method' => $payment_method, 'transaction_id' => $transaction_id]);
                mysqli_stmt_bind_param($stmt, "ssi", $transaction_id, $response, $payment_id);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
            }
        }
        
        // Cập nhật order (kiểm tra cột payment_status có tồn tại không)
        $log_entry = "Step 7: Updating order status\n";
        file_put_contents($log_file, $log_entry, FILE_APPEND);
        
        $update_order_sql = "UPDATE tbl_order SET payment_status = 'paid', status = 'Ordered' WHERE order_code = ?";
        $stmt = mysqli_prepare($conn, $update_order_sql);
        if($stmt) {
            mysqli_stmt_bind_param($stmt, "s", $order_code);
            $update_result = mysqli_stmt_execute($stmt);
            if($update_result) {
                $log_entry = "Step 7a: Order updated successfully\n";
                file_put_contents($log_file, $log_entry, FILE_APPEND);
            } else {
                $log_entry = "Step 7a ERROR: Order update failed - " . mysqli_stmt_error($stmt) . "\n";
                file_put_contents($log_file, $log_entry, FILE_APPEND);
                error_log("Order update error: " . mysqli_stmt_error($stmt));
            }
            mysqli_stmt_close($stmt);
        } else {
            // Fallback: chỉ cập nhật status nếu không có cột payment_status
            $log_entry = "Step 7b: Trying fallback order update (no payment_status column)\n";
            file_put_contents($log_file, $log_entry, FILE_APPEND);
            
            $update_order_sql = "UPDATE tbl_order SET status = 'Ordered' WHERE order_code = ?";
            $stmt = mysqli_prepare($conn, $update_order_sql);
            if($stmt) {
                mysqli_stmt_bind_param($stmt, "s", $order_code);
                $update_result = mysqli_stmt_execute($stmt);
                if($update_result) {
                    $log_entry = "Step 7b: Fallback order update successful\n";
                    file_put_contents($log_file, $log_entry, FILE_APPEND);
                } else {
                    $log_entry = "Step 7b ERROR: Fallback order update failed - " . mysqli_stmt_error($stmt) . "\n";
                    file_put_contents($log_file, $log_entry, FILE_APPEND);
                }
                mysqli_stmt_close($stmt);
            } else {
                $log_entry = "Step 7b ERROR: Failed to prepare fallback SQL - " . mysqli_error($conn) . "\n";
                file_put_contents($log_file, $log_entry, FILE_APPEND);
                error_log("Order update fallback error: " . mysqli_error($conn));
            }
        }
        
        unset($_SESSION['order_code']);
        $_SESSION['order-success'] = "Thanh toán thành công! Mã giao dịch: " . $transaction_id;
        
        $log_entry = "Step 8: Session updated, preparing redirect\n";
        $log_entry .= "Redirect URL will be: " . SITEURL . "user/order-history.php\n";
        $log_entry .= "Output buffer level before clean: " . ob_get_level() . "\n";
        file_put_contents($log_file, $log_entry, FILE_APPEND);
        
        // Đảm bảo không có output trước redirect
        while(ob_get_level() > 0) {
            ob_end_clean();
        }
        
        $log_entry = "Step 9: Output buffer cleaned, level now: " . ob_get_level() . "\n";
        $log_entry .= "Headers sent: " . (headers_sent() ? 'Yes' : 'No') . "\n";
        file_put_contents($log_file, $log_entry, FILE_APPEND);
        
        // Redirect - luôn dùng JavaScript để đảm bảo hoạt động
        $redirect_url = SITEURL . 'user/order-history.php';
        
        $log_entry = "Step 10: Sending JavaScript redirect to: " . $redirect_url . "\n";
        $log_entry .= "END OF PAYMENT PROCESSING\n";
        file_put_contents($log_file, $log_entry, FILE_APPEND);
        
        echo '<!DOCTYPE html><html><head><meta charset="UTF-8">';
        echo '<script>';
        echo 'console.log("Payment redirect triggered");';
        echo 'window.location.href = "' . htmlspecialchars($redirect_url, ENT_QUOTES) . '";';
        echo '</script>';
        echo '<meta http-equiv="refresh" content="0;url=' . htmlspecialchars($redirect_url, ENT_QUOTES) . '">';
        echo '</head><body>';
        echo '<p style="text-align:center;padding:50px;font-size:18px;">Đang chuyển hướng... <a href="' . htmlspecialchars($redirect_url, ENT_QUOTES) . '">Nhấn vào đây nếu không tự động chuyển</a></p>';
        echo '</body></html>';
        exit();
    } else {
        // Thanh toán thất bại (mô phỏng)
        $failure_reason = "Thanh toán thất bại do lỗi kết nối. Vui lòng thử lại.";
        
        if($payment_table_exists && $payment_id) {
            $fail_sql = "UPDATE tbl_payment SET 
                payment_status = 'failed', 
                failure_reason = ?,
                updated_at = NOW()
                WHERE id = ?";
            $stmt = mysqli_prepare($conn, $fail_sql);
            if($stmt) {
                mysqli_stmt_bind_param($stmt, "si", $failure_reason, $payment_id);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
            }
        }
        
        $error_message = "Thanh toán thất bại: " . $failure_reason;
        // Không redirect, hiển thị lỗi trên trang
    }
    
    } catch (Exception $e) {
        // Bắt mọi lỗi và log lại
        $error_msg = "FATAL ERROR in payment processing: " . $e->getMessage() . "\n";
        $error_msg .= "Stack trace: " . $e->getTraceAsString() . "\n";
        file_put_contents($log_file, $error_msg, FILE_APPEND);
        error_log("Payment processing error: " . $e->getMessage());
        
        $_SESSION['payment-error'] = "Có lỗi xảy ra khi xử lý thanh toán. Vui lòng thử lại hoặc liên hệ hỗ trợ.";
        $error_message = "Lỗi hệ thống: " . $e->getMessage();
    } catch (Error $e) {
        // Bắt fatal errors
        $error_msg = "FATAL PHP ERROR in payment processing: " . $e->getMessage() . "\n";
        $error_msg .= "Stack trace: " . $e->getTraceAsString() . "\n";
        file_put_contents($log_file, $error_msg, FILE_APPEND);
        error_log("Payment processing fatal error: " . $e->getMessage());
        
        $_SESSION['payment-error'] = "Có lỗi nghiêm trọng xảy ra. Vui lòng liên hệ hỗ trợ.";
        $error_message = "Lỗi nghiêm trọng: " . $e->getMessage();
    }
}

// Nếu đã xử lý POST và redirect, không chạy code bên dưới
// Nếu không, tiếp tục hiển thị form

// Lấy payment info để hiển thị
$payment_info = null;
if($existing_payment && !$payment_expired) {
    $payment_info = $existing_payment;
}

// Chỉ include menu nếu không phải POST request (để tránh output trước redirect)
if(!isset($_POST['confirm_payment'])) {
    try {
        include('../partials-front/menu.php');
    } catch (Exception $e) {
        if(isset($log_file)) {
            file_put_contents($log_file, "ERROR: Failed to include menu.php - " . $e->getMessage() . "\n", FILE_APPEND);
        }
        error_log("Failed to include menu.php: " . $e->getMessage());
    }
}
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
            display: flex;
            align-items: center;
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
        .submit-payment-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
        .note {
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
        .warning-message {
            background: #fff3e0;
            color: #e65100;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #e65100;
        }
        .timer {
            text-align: center;
            font-size: 1.2em;
            color: #ff6b81;
            font-weight: bold;
            margin: 15px 0;
        }
        .payment-info {
            background: #f1f8e9;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 0.9em;
        }
    </style>
</head>
<body>
    <div class="payment-container">
        <div class="payment-header">
            <h1>💳 Thanh toán online</h1>
            <p>Mã đơn hàng: <strong><?php echo htmlspecialchars($order_code); ?></strong></p>
        </div>

        <?php if(isset($error_message)): ?>
            <div class="error-message">
                <strong>❌ Lỗi:</strong> <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>
        
        <?php if(isset($_SESSION['payment-error'])): ?>
            <div class="error-message">
                <strong>❌ Lỗi:</strong> <?php echo htmlspecialchars($_SESSION['payment-error']); ?>
                <?php unset($_SESSION['payment-error']); ?>
            </div>
        <?php endif; ?>
        
        <?php 
        // Debug info (chỉ hiển thị trong development)
        if(isset($_GET['debug']) && $_GET['debug'] == '1'): 
        ?>
            <div style="background: #f0f0f0; padding: 10px; margin: 10px 0; font-size: 12px;">
                <strong>Debug Info:</strong><br>
                Payment Table Exists: <?php echo $payment_table_exists ? 'Yes' : 'No'; ?><br>
                Existing Payment: <?php echo $existing_payment ? 'Yes' : 'No'; ?><br>
                Payment Expired: <?php echo $payment_expired ? 'Yes' : 'No'; ?><br>
                Order Total: <?php echo $order_total; ?><br>
                Order Code: <?php echo htmlspecialchars($order_code); ?><br>
                POST Data: <?php print_r($_POST); ?>
            </div>
        <?php endif; ?>

        <?php if($payment_expired): ?>
            <div class="warning-message">
                <strong>⏰ Hết hạn:</strong> Phiên thanh toán đã hết hạn. Vui lòng tạo lại thanh toán.
            </div>
        <?php endif; ?>

        <?php if($payment_info && !$payment_expired): ?>
            <div class="payment-info">
                <strong>ℹ️ Thông tin:</strong> Bạn đã bắt đầu thanh toán. Vui lòng hoàn tất trong vòng 15 phút.
                <?php if($payment_info['expires_at']): ?>
                    <div class="timer" id="countdown">
                        Thời gian còn lại: <span id="time-left">--:--</span>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="order-info">
            <div class="info-row">
                <span>Tổng tiền đơn hàng:</span>
                <span><?php echo number_format($order_total, 0, ',', '.'); ?> đ</span>
            </div>
        </div>

        <form method="POST" action="" id="paymentForm">
            <input type="hidden" name="order_code" value="<?php echo htmlspecialchars($order_code); ?>">
            <div class="payment-methods-list">
                <label class="payment-method-item active" onclick="selectMethod('momo')">
                    <input type="radio" name="payment_method" value="momo" checked style="display: none;">
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
                <strong>📝 Lưu ý:</strong> 
                <ul style="margin: 10px 0; padding-left: 20px;">
                    <li>Thời gian thanh toán: 15 phút</li>
                    <li>Đơn hàng sẽ tự động hủy nếu không thanh toán trong thời gian quy định</li>
                    <li>Trong môi trường thực tế, bạn sẽ được chuyển đến cổng thanh toán</li>
                </ul>
            </div>

            <!-- Hidden field để đảm bảo form submit -->
            <input type="hidden" name="order_code" value="<?php echo htmlspecialchars($order_code); ?>">
            <input type="hidden" name="confirm_payment" value="1">
            
            <button type="submit" class="submit-payment-btn" id="submitBtn">
                <?php echo $payment_info && !$payment_expired ? 'Tiếp tục thanh toán' : 'Xác nhận thanh toán'; ?>
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

        // Countdown timer
        <?php if($payment_info && $payment_info['expires_at'] && !$payment_expired): ?>
        (function() {
            const expiresAt = new Date('<?php echo $payment_info['expires_at']; ?>').getTime();
            
            function updateTimer() {
                const now = new Date().getTime();
                const distance = expiresAt - now;
                
                if (distance < 0) {
                    document.getElementById('time-left').textContent = '00:00';
                    document.getElementById('submitBtn').disabled = true;
                    alert('Phiên thanh toán đã hết hạn. Vui lòng tải lại trang.');
                    return;
                }
                
                const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                const seconds = Math.floor((distance % (1000 * 60)) / 1000);
                
                document.getElementById('time-left').textContent = 
                    String(minutes).padStart(2, '0') + ':' + String(seconds).padStart(2, '0');
            }
            
            updateTimer();
            setInterval(updateTimer, 1000);
        })();
        <?php endif; ?>

        // Prevent double submission và đảm bảo form submit
        const paymentForm = document.getElementById('paymentForm');
        if(paymentForm) {
            // Debug: Kiểm tra form có tồn tại không
            console.log('Payment form loaded. Form element:', paymentForm);
            console.log('Submit button:', document.getElementById('submitBtn'));
            console.log('Form can be submitted:', paymentForm.checkValidity());
            
            paymentForm.addEventListener('submit', function(e) {
                console.log('=== FORM SUBMIT EVENT TRIGGERED ===');
                console.log('Form action:', this.action);
                console.log('Form method:', this.method);
                
                // Log form data
                const formData = new FormData(this);
                console.log('Form data entries:');
                for(let pair of formData.entries()) {
                    console.log('  ' + pair[0] + ': ' + pair[1]);
                }
                
                const btn = document.getElementById('submitBtn');
                if(btn) {
                    btn.disabled = true;
                    btn.textContent = 'Đang xử lý...';
                    console.log('Button disabled and text changed');
                }
                
                // KHÔNG preventDefault - cho phép form submit bình thường
                console.log('Allowing form to submit normally (no preventDefault)...');
                
                // Gửi log đến server trước khi submit
                fetch('<?php echo SITEURL; ?>api/log-payment-submit.php', {
                    method: 'POST',
                    body: formData
                }).catch(err => console.log('Log request failed:', err));
            });
        } else {
            console.error('Không tìm thấy form paymentForm!');
        }
    </script>
</body>
</html>
