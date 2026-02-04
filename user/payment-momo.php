<?php
// Bắt đầu output buffering để tránh output trước header redirect
ob_start();

// Bật error reporting để debug
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Log file để debug
$log_file = __DIR__ . '/../logs/payment_momo_debug.log';

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
$payment_id = isset($_GET['payment_id']) ? $_GET['payment_id'] : '';

if(empty($order_code) && empty($payment_id)) {
    $_SESSION['payment-error'] = "Không tìm thấy đơn hàng!";
    header('location:'.SITEURL.'user/order-history.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Lấy thông tin đơn hàng từ API Node.js hoặc database
// Giả sử bạn có API endpoint để lấy thông tin đơn hàng
$order_total = 0;
$order_info = null;

// Nếu có order_code, lấy từ database
if($order_code) {
    $order_sql = "SELECT * FROM tbl_order WHERE order_code = ? AND user_id = ? LIMIT 1";
    $stmt = mysqli_prepare($conn, $order_sql);
    if($stmt) {
        mysqli_stmt_bind_param($stmt, "si", $order_code, $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if(mysqli_num_rows($result) > 0) {
            $order_info = mysqli_fetch_assoc($result);
            
            // Tính tổng tiền đơn hàng
            $total_sql = "SELECT SUM(total) as total FROM tbl_order WHERE order_code = ?";
            $stmt2 = mysqli_prepare($conn, $total_sql);
            if($stmt2) {
                mysqli_stmt_bind_param($stmt2, "s", $order_code);
                mysqli_stmt_execute($stmt2);
                $total_result = mysqli_stmt_get_result($stmt2);
                $total_data = mysqli_fetch_assoc($total_result);
                $order_total = floatval($total_data['total']);
                mysqli_stmt_close($stmt2);
            }
        }
        mysqli_stmt_close($stmt);
    }
}

// Kiểm tra trạng thái thanh toán
if($order_info) {
    $payment_status = $order_info['payment_status'] ?? 'pending';
    if($payment_status == 'paid') {
        $_SESSION['payment-error'] = "Đơn hàng này đã được thanh toán!";
        header('location:'.SITEURL.'user/order-history.php');
        exit();
    }
}

// Không xử lý POST ở đây, để JavaScript xử lý qua AJAX
$error_message = '';
$api_base_url = SITEURL . 'api'; // API PHP local

// Chỉ include menu nếu không phải POST request
if(!isset($_POST['pay_now'])) {
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
    <title>Thanh toán MoMo - WowFood</title>
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
            border-bottom: 2px solid #A50064;
        }
        .payment-header h1 {
            color: #2f3542;
            margin-bottom: 10px;
        }
        .momo-logo {
            font-size: 3em;
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
            color: #A50064;
        }
        .momo-info {
            background: linear-gradient(135deg, #A50064 0%, #D1007F 100%);
            color: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        .momo-info h3 {
            margin-top: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .momo-features {
            list-style: none;
            padding: 0;
            margin: 15px 0 0 0;
        }
        .momo-features li {
            padding: 8px 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .momo-features li:before {
            content: "✓";
            background: white;
            color: #A50064;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 12px;
        }
        .pay-button {
            width: 100%;
            padding: 18px;
            background: linear-gradient(135deg, #A50064 0%, #D1007F 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1.2em;
            font-weight: bold;
            cursor: pointer;
            margin-top: 20px;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        .pay-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(165, 0, 100, 0.3);
        }
        .pay-button:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
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
        .security-badge {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-top: 20px;
            color: #666;
            font-size: 0.9em;
        }
        .loading-spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255,255,255,.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s ease-in-out infinite;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        @media (max-width: 768px) {
            .payment-container {
                margin: 50px 10px;
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="payment-container">
        <div class="payment-header">
            <div class="momo-logo">💜</div>
            <h1>Thanh toán MoMo</h1>
            <?php if($order_code): ?>
                <p>Mã đơn hàng: <strong><?php echo htmlspecialchars($order_code); ?></strong></p>
            <?php endif; ?>
        </div>

        <?php if($error_message): ?>
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

        <div class="order-info">
            <div class="info-row">
                <span>Tổng tiền đơn hàng:</span>
                <span><?php echo number_format($order_total, 0, ',', '.'); ?> đ</span>
            </div>
        </div>

        <div class="momo-info">
            <h3>
                <span>💜</span>
                <span>Ví điện tử MoMo</span>
            </h3>
            <ul class="momo-features">
                <li>Thanh toán nhanh chóng và an toàn</li>
                <li>Không cần nhập thông tin thẻ</li>
                <li>Hỗ trợ đầy đủ các ngân hàng</li>
                <li>Xác nhận thanh toán tức thì</li>
            </ul>
        </div>

        <form method="POST" action="" id="paymentForm">
            <input type="hidden" name="order_code" value="<?php echo htmlspecialchars($order_code); ?>">
            <input type="hidden" name="payment_id" id="paymentId" value="<?php echo htmlspecialchars($payment_id); ?>">
            <input type="hidden" name="amount" id="orderAmount" value="<?php echo $order_total; ?>">
            
            <button type="button" class="pay-button" id="payButton">
                <span>💜</span>
                <span>Thanh toán bằng MoMo</span>
            </button>
        </form>

        <div class="note">
            <strong>📝 Lưu ý:</strong> 
            <ul style="margin: 10px 0; padding-left: 20px;">
                <li>Bạn sẽ được chuyển đến trang thanh toán MoMo</li>
                <li>Vui lòng hoàn tất thanh toán trong vòng 15 phút</li>
                <li>Đơn hàng sẽ tự động hủy nếu không thanh toán trong thời gian quy định</li>
                <li>Sau khi thanh toán thành công, bạn sẽ được chuyển về trang xác nhận</li>
            </ul>
        </div>

        <div class="security-badge">
            <span>🔒</span>
            <span>Giao dịch được bảo mật bởi MoMo</span>
        </div>
    </div>

    <?php include('../partials-front/footer.php'); ?>

    <script>
        // API Base URL
        const API_BASE_URL = '<?php echo $api_base_url; ?>';
        const SITE_URL = '<?php echo SITEURL; ?>';
        const ORDER_CODE = '<?php echo htmlspecialchars($order_code); ?>';
        const ORDER_AMOUNT = <?php echo $order_total; ?>;
        
        // Xử lý khi click nút thanh toán
        const payButton = document.getElementById('payButton');
        const paymentIdInput = document.getElementById('paymentId');
        
        if(payButton) {
            payButton.addEventListener('click', async function() {
                // Disable button và hiển thị loading
                payButton.disabled = true;
                payButton.innerHTML = '<span class="loading-spinner"></span> <span>Đang tạo link thanh toán...</span>';
                
                try {
                    // Tạo payment ID nếu chưa có
                    let paymentId = paymentIdInput.value;
                    if(!paymentId) {
                        paymentId = 'PAY_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
                        paymentIdInput.value = paymentId;
                    }
                    
                    // Gọi API PHP để lấy payment URL
                    const response = await fetch(API_BASE_URL + '/momo-create-payment.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            amount: ORDER_AMOUNT,
                            order_code: ORDER_CODE,
                            payment_id: paymentId
                        })
                    });
                    
                    const data = await response.json();
                    
                    if(data.success && data.payUrl) {
                        // Redirect đến MoMo payment page
                        window.location.href = data.payUrl;
                    } else {
                        // Hiển thị lỗi
                        showError(data.message || 'Không thể tạo link thanh toán. Vui lòng thử lại!');
                        payButton.disabled = false;
                        payButton.innerHTML = '<span>💜</span> <span>Thanh toán bằng MoMo</span>';
                    }
                } catch (error) {
                    console.error('Payment error:', error);
                    showError('Lỗi kết nối đến hệ thống thanh toán. Vui lòng thử lại sau!');
                    payButton.disabled = false;
                    payButton.innerHTML = '<span>💜</span> <span>Thanh toán bằng MoMo</span>';
                }
            });
        }
        
        // Hàm hiển thị lỗi
        function showError(message) {
            // Xóa error message cũ nếu có
            const oldError = document.querySelector('.error-message');
            if(oldError) oldError.remove();
            
            const errorDiv = document.createElement('div');
            errorDiv.className = 'error-message';
            errorDiv.innerHTML = '<strong>❌ Lỗi:</strong> ' + message;
            
            const container = document.querySelector('.payment-container');
            const header = container.querySelector('.payment-header');
            header.insertAdjacentElement('afterend', errorDiv);
            
            // Scroll to error
            errorDiv.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            
            // Tự động ẩn sau 5 giây
            setTimeout(() => {
                errorDiv.remove();
            }, 5000);
        }
    </script>
</body>
</html>

