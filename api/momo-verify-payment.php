<?php
/**
 * API: Verify MoMo Payment
 * Endpoint: POST /api/momo-verify-payment.php
 */

header('Content-Type: application/json');
require_once('../config/constants.php');
require_once('../config/momo-payment.php');

// Chỉ cho phép POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ]);
    exit();
}

// Lấy dữ liệu từ POST
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    $input = $_POST;
}

$paymentId = $input['payment_id'] ?? $input['orderId'] ?? '';

if (empty($paymentId)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Thiếu payment ID'
    ]);
    exit();
}

// Verify payment với MoMo
$momo = new MomoPayment();
$result = $momo->verifyPayment($paymentId, $paymentId);

if ($result['success'] && $result['resultCode'] === 0) {
    // Thanh toán thành công
    // Cập nhật database
    $paymentId_escaped = mysqli_real_escape_string($conn, $paymentId);
    
    // Tìm order theo payment_id
    $order_sql = "SELECT order_code FROM tbl_order WHERE payment_id = ? LIMIT 1";
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
    
    echo json_encode([
        'success' => true,
        'resultCode' => 0,
        'message' => 'Thanh toán thành công',
        'order_code' => $order_code,
        'paymentId' => $paymentId
    ]);
} else {
    // Thanh toán thất bại
    $resultCode = $result['resultCode'] ?? -1;
    $message = $result['message'] ?? 'Thanh toán thất bại';
    
    // Cập nhật payment status thành failed
    $paymentId_escaped = mysqli_real_escape_string($conn, $paymentId);
    $update_sql = "UPDATE tbl_order SET payment_status = 'failed' WHERE payment_id = ?";
    $stmt = mysqli_prepare($conn, $update_sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "s", $paymentId);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'resultCode' => $resultCode,
        'message' => $message
    ]);
}

