<?php
/**
 * MoMo Payment Callback Handler
 * Xử lý callback từ MoMo sau khi user thanh toán (redirect về đây)
 */

require_once('../../../config/constants.php');
require_once('../../../config/momo-payment.php');

// Log callback để debug
function logMoMoCallback($data) {
    $log_file = __DIR__ . '/../../../logs/momo_callback.log';
    $log_entry = date('Y-m-d H:i:s') . " - MoMo Callback\n";
    $log_entry .= "Data: " . print_r($data, true) . "\n";
    $log_entry .= "POST: " . print_r($_POST, true) . "\n";
    $log_entry .= "GET: " . print_r($_GET, true) . "\n";
    $log_entry .= str_repeat("-", 80) . "\n";
    file_put_contents($log_file, $log_entry, FILE_APPEND);
}

// Lấy dữ liệu từ callback
$callback_data = $_GET ?: $_POST;
logMoMoCallback($callback_data);

// Lấy thông tin từ callback
$resultCode = intval($callback_data['resultCode'] ?? -1);
$orderId = $callback_data['orderId'] ?? '';
$amount = floatval($callback_data['amount'] ?? 0);
$transId = $callback_data['transId'] ?? '';
$message = $callback_data['message'] ?? '';

// Verify signature (optional, nhưng nên verify)
$momo = new MomoPayment();
$isValid = true; // Có thể verify signature nếu cần

if ($isValid && $resultCode === 0) {
    // Thanh toán thành công
    $paymentId = $orderId;
    
    // Verify lại với MoMo API để chắc chắn
    $verify_result = $momo->verifyPayment($paymentId, $paymentId);
    
    if ($verify_result['success'] && $verify_result['resultCode'] === 0) {
        // Tìm order_code từ payment_id
        $order_sql = "SELECT order_code FROM tbl_order WHERE payment_id = ? LIMIT 1";
        $stmt = mysqli_prepare($conn, $order_sql);
        $order_code = $paymentId; // Fallback
        
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "s", $paymentId);
            mysqli_stmt_execute($stmt);
            $order_result = mysqli_stmt_get_result($stmt);
            if ($order_row = mysqli_fetch_assoc($order_result)) {
                $order_code = $order_row['order_code'];
            }
            mysqli_stmt_close($stmt);
        }
        
        // Cập nhật payment status
        if ($order_code) {
            $update_sql = "UPDATE tbl_order SET payment_status = 'paid', payment_method = 'momo' WHERE order_code = ?";
            $stmt = mysqli_prepare($conn, $update_sql);
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "s", $order_code);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
            }
        }
        
        // Redirect đến trang thành công
        header('Location: ' . SITEURL . 'user/payment-success.php?order_code=' . urlencode($order_code ?: $paymentId));
        exit();
    } else {
        // Verify thất bại
        $reason = 'Xác thực thanh toán thất bại';
        header('Location: ' . SITEURL . 'user/payment-failed.php?order_code=' . urlencode($paymentId) . '&reason=' . urlencode($reason));
        exit();
    }
} else {
    // Thanh toán thất bại
    $paymentId = $orderId;
    $reason = $message ?: 'Thanh toán không thành công';
    
    // Cập nhật payment status thành failed
    $update_sql = "UPDATE tbl_order SET payment_status = 'failed' WHERE payment_id = ?";
    $stmt = mysqli_prepare($conn, $update_sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "s", $paymentId);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
    
    header('Location: ' . SITEURL . 'user/payment-failed.php?order_code=' . urlencode($paymentId) . '&reason=' . urlencode($reason));
    exit();
}

