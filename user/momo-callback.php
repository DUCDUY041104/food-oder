<?php
/**
 * MoMo Payment Callback Handler
 * Xử lý callback từ MoMo sau khi thanh toán
 */

include('../config/constants.php');

// Log callback để debug
function logMoMoCallback($data) {
    $log_file = __DIR__ . '/../logs/momo_callback.log';
    $log_entry = date('Y-m-d H:i:s') . " - MoMo Callback\n";
    $log_entry .= "Data: " . print_r($data, true) . "\n";
    $log_entry .= str_repeat("-", 80) . "\n";
    file_put_contents($log_file, $log_entry, FILE_APPEND);
}

// Lấy dữ liệu từ callback
$callback_data = $_GET ?: $_POST;
logMoMoCallback($callback_data);

// Lấy thông tin từ callback
$resultCode = $callback_data['resultCode'] ?? '';
$orderId = $callback_data['orderId'] ?? '';
$amount = $callback_data['amount'] ?? 0;
$transId = $callback_data['transId'] ?? '';
$message = $callback_data['message'] ?? '';

// Kiểm tra kết quả thanh toán
if($resultCode === '0' || $resultCode === 0) {
    // Thanh toán thành công
    // Gọi API Node.js để verify và update payment status
    try {
        $api_url = 'http://localhost:3000/api/orders/verify-momo'; // Thay đổi thành URL API thực tế
        
        $post_data = [
            'paymentId' => $orderId
        ];
        
        $ch = curl_init($api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post_data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if($http_code == 200) {
            $result = json_decode($response, true);
            if(isset($result['message']) && $result['message'] === 'Ok') {
                // Redirect đến trang thành công
                header('Location: ' . SITEURL . 'user/payment-success.php?order_code=' . urlencode($orderId));
                exit();
            }
        }
    } catch (Exception $e) {
        logMoMoCallback(['error' => $e->getMessage()]);
    }
    
    // Nếu verify thành công hoặc không có API, redirect đến success
    header('Location: ' . SITEURL . 'user/payment-success.php?order_code=' . urlencode($orderId));
    exit();
} else {
    // Thanh toán thất bại
    $reason = $message ?: 'Thanh toán không thành công';
    header('Location: ' . SITEURL . 'user/payment-failed.php?order_code=' . urlencode($orderId) . '&reason=' . urlencode($reason));
    exit();
}

