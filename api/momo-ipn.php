<?php
/**
 * MoMo IPN (Instant Payment Notification) Handler
 * Xử lý callback từ MoMo server (không redirect user)
 */

require_once('../config/constants.php');
require_once('../config/momo-payment.php');

// Log IPN để debug
function logMoMoIPN($data) {
    $log_file = __DIR__ . '/../logs/momo_ipn.log';
    $log_entry = date('Y-m-d H:i:s') . " - MoMo IPN\n";
    $log_entry .= "Data: " . print_r($data, true) . "\n";
    $log_entry .= "POST: " . print_r($_POST, true) . "\n";
    $log_entry .= "GET: " . print_r($_GET, true) . "\n";
    $log_entry .= str_repeat("-", 80) . "\n";
    file_put_contents($log_file, $log_entry, FILE_APPEND);
}

// Lấy dữ liệu từ IPN
$ipn_data = $_POST ?: $_GET;
logMoMoIPN($ipn_data);

// Verify signature
$momo = new MomoPayment();
$isValid = $momo->verifyCallbackSignature($ipn_data);

if (!$isValid) {
    logMoMoIPN(['error' => 'Invalid signature']);
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid signature']);
    exit();
}

// Lấy thông tin từ IPN
$resultCode = intval($ipn_data['resultCode'] ?? -1);
$orderId = $ipn_data['orderId'] ?? '';
$amount = floatval($ipn_data['amount'] ?? 0);
$transId = $ipn_data['transId'] ?? '';
$message = $ipn_data['message'] ?? '';

// Xử lý theo resultCode
if ($resultCode === 0) {
    // Thanh toán thành công
    $paymentId = $orderId;
    
    // Tìm order theo payment_id
    $order_sql = "SELECT order_code, user_id FROM tbl_order WHERE payment_id = ? LIMIT 1";
    $stmt = mysqli_prepare($conn, $order_sql);
    $order_code = null;
    
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
    
    logMoMoIPN(['success' => true, 'order_code' => $order_code, 'paymentId' => $paymentId]);
    http_response_code(200);
    echo json_encode(['success' => true, 'message' => 'Payment processed']);
} else {
    // Thanh toán thất bại
    $paymentId = $orderId;
    
    // Cập nhật payment status thành failed
    $update_sql = "UPDATE tbl_order SET payment_status = 'failed' WHERE payment_id = ?";
    $stmt = mysqli_prepare($conn, $update_sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "s", $paymentId);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
    
    logMoMoIPN(['success' => false, 'resultCode' => $resultCode, 'message' => $message]);
    http_response_code(200);
    echo json_encode(['success' => false, 'message' => $message]);
}

