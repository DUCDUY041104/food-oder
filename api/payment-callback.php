<?php
/**
 * Payment Callback Handler
 * Xử lý callback từ các cổng thanh toán (VNPay, MoMo, Bank Transfer)
 */

include('../config/constants.php');

// Log callback để debug
function logCallback($data) {
    $log_file = __DIR__ . '/../logs/payment_callback.log';
    $log_entry = date('Y-m-d H:i:s') . " - " . json_encode($data) . "\n";
    file_put_contents($log_file, $log_entry, FILE_APPEND);
}

// Lấy dữ liệu từ callback
$callback_data = $_POST ?: $_GET;
logCallback($callback_data);

// Xác thực callback (trong thực tế cần verify signature từ cổng thanh toán)
$order_code = $callback_data['order_code'] ?? $callback_data['vnp_TxnRef'] ?? $callback_data['orderId'] ?? '';
$transaction_id = $callback_data['transaction_id'] ?? $callback_data['vnp_TransactionNo'] ?? $callback_data['transId'] ?? '';
$payment_method = $callback_data['payment_method'] ?? 'vnpay';
$status = $callback_data['status'] ?? $callback_data['vnp_ResponseCode'] ?? $callback_data['resultCode'] ?? '';

// Xác định trạng thái thanh toán
$payment_status = 'failed';
$failure_reason = '';

// VNPay response code: 00 = success
if($payment_method == 'vnpay') {
    if($status == '00' || $status == 'success') {
        $payment_status = 'success';
    } else {
        $payment_status = 'failed';
        $failure_reason = 'Mã lỗi: ' . $status;
    }
}
// MoMo response
elseif($payment_method == 'momo') {
    if($status == '0' || $status == 'success') {
        $payment_status = 'success';
    } else {
        $payment_status = 'failed';
        $failure_reason = 'Mã lỗi: ' . $status;
    }
}
// Bank transfer (manual verification)
elseif($payment_method == 'bank') {
    // Cần admin xác nhận thủ công
    $payment_status = 'pending';
}

if(empty($order_code)) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing order_code']);
    exit();
}

// Tìm payment record
$payment_sql = "SELECT * FROM tbl_payment WHERE order_code = ? AND payment_status = 'pending' ORDER BY id DESC LIMIT 1";
$stmt = mysqli_prepare($conn, $payment_sql);
mysqli_stmt_bind_param($stmt, "s", $order_code);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$payment = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if(!$payment) {
    http_response_code(404);
    echo json_encode(['error' => 'Payment not found']);
    exit();
}

// Cập nhật payment
$update_payment_sql = "UPDATE tbl_payment SET 
    payment_status = ?,
    transaction_id = ?,
    payment_gateway_response = ?,
    failure_reason = ?,
    paid_at = " . ($payment_status == 'success' ? "NOW()" : "NULL") . ",
    updated_at = NOW()
    WHERE id = ?";

$stmt = mysqli_prepare($conn, $update_payment_sql);
$response_json = json_encode($callback_data);
mysqli_stmt_bind_param($stmt, "ssssi", 
    $payment_status, 
    $transaction_id, 
    $response_json, 
    $failure_reason, 
    $payment['id']
);
mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

// Nếu thanh toán thành công, cập nhật order
if($payment_status == 'success') {
    $update_order_sql = "UPDATE tbl_order SET 
        payment_status = 'paid',
        status = 'Ordered'
        WHERE order_code = ? AND payment_status != 'paid'";
    $stmt = mysqli_prepare($conn, $update_order_sql);
    mysqli_stmt_bind_param($stmt, "s", $order_code);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    
    // Gửi email thông báo (nếu cần)
    // sendPaymentConfirmationEmail($order_code);
}

// Trả về response cho cổng thanh toán
if($payment_method == 'vnpay') {
    // VNPay cần response HTML
    if($payment_status == 'success') {
        header('Location: ' . SITEURL . 'user/payment-success.php?order_code=' . $order_code);
    } else {
        header('Location: ' . SITEURL . 'user/payment-failed.php?order_code=' . $order_code . '&reason=' . urlencode($failure_reason));
    }
} else {
    // JSON response cho các cổng khác
    header('Content-Type: application/json');
    echo json_encode([
        'status' => $payment_status,
        'order_code' => $order_code,
        'transaction_id' => $transaction_id,
        'message' => $payment_status == 'success' ? 'Payment successful' : 'Payment failed'
    ]);
}
?>

