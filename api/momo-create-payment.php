<?php
/**
 * API: Tạo MoMo Payment URL
 * Endpoint: POST /api/momo-create-payment.php
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

// Kiểm tra đăng nhập user
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Vui lòng đăng nhập'
    ]);
    exit();
}

// Lấy dữ liệu từ POST
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    $input = $_POST;
}

$amount = floatval($input['amount'] ?? 0);
$orderCode = $input['order_code'] ?? '';
$paymentId = $input['payment_id'] ?? '';

// Validate
if ($amount <= 0) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Số tiền không hợp lệ'
    ]);
    exit();
}

if (empty($orderCode) && empty($paymentId)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Thiếu mã đơn hàng hoặc payment ID'
    ]);
    exit();
}

$user_id = $_SESSION['user_id'];

// Nếu có order_code, lấy thông tin đơn hàng
if ($orderCode) {
    $order_sql = "SELECT SUM(total) as total FROM tbl_order WHERE order_code = ? AND user_id = ?";
    $stmt = mysqli_prepare($conn, $order_sql);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "si", $orderCode, $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $order_data = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        
        if ($order_data && $order_data['total'] > 0) {
            $amount = floatval($order_data['total']);
        }
    }
}

// Tạo payment ID nếu chưa có
if (empty($paymentId)) {
    $paymentId = 'PAY_' . time() . '_' . uniqid();
}

// Tạo order ID (dùng payment ID làm order ID)
$orderId = $paymentId;
$requestId = $paymentId;

// Tạo redirect URL và IPN URL
$host = $_SERVER['HTTP_HOST'];
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$baseUrl = $protocol . '://' . $host . dirname(dirname($_SERVER['SCRIPT_NAME']));

$redirectUrl = $baseUrl . '/user/thanhtoan/momo/callback.php';
$ipnUrl = $baseUrl . '/api/momo-ipn.php'; // IPN handler

// Tạo MoMo payment
$momo = new MomoPayment();
$result = $momo->createPayment([
    'amount' => $amount,
    'orderId' => $orderId,
    'orderInfo' => 'Thanh toán đơn hàng ' . ($orderCode ?: $paymentId),
    'redirectUrl' => $redirectUrl,
    'ipnUrl' => $ipnUrl,
    'requestId' => $requestId,
    'extraData' => json_encode([
        'order_code' => $orderCode,
        'user_id' => $user_id
    ])
]);

if ($result['success']) {
    // Lưu payment vào database (nếu có bảng payment)
    // Hoặc cập nhật order với payment_id
    if ($orderCode) {
        $update_sql = "UPDATE tbl_order SET payment_id = ?, payment_method = 'momo', payment_status = 'pending' WHERE order_code = ?";
        $stmt = mysqli_prepare($conn, $update_sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "ss", $paymentId, $orderCode);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
    }
    
    echo json_encode([
        'success' => true,
        'payUrl' => $result['payUrl'],
        'paymentId' => $paymentId,
        'message' => $result['message']
    ]);
} else {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $result['message']
    ]);
}

