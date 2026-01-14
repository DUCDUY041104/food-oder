<?php
include('../config/constants.php');

// Kiểm tra đăng nhập
if(!isset($_SESSION['user_id'])) {
    $_SESSION['no-login-message'] = "Vui lòng đăng nhập để thanh toán!";
    header('location:'.SITEURL.'user/login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Lấy thông tin user
$user_sql = "SELECT * FROM tbl_user WHERE id = ?";
$stmt = mysqli_prepare($conn, $user_sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user_data = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

// Xử lý thanh toán
if(isset($_POST['submit'])) {
    // Lấy giỏ hàng
    $cart_sql = "SELECT * FROM tbl_cart WHERE user_id = ?";
    $stmt = mysqli_prepare($conn, $cart_sql);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $cart_result = mysqli_stmt_get_result($stmt);
    
    if(mysqli_num_rows($cart_result) == 0) {
        mysqli_stmt_close($stmt);
        $_SESSION['checkout-error'] = "Giỏ hàng trống!";
        header('location:'.SITEURL.'user/cart.php');
        exit();
    }
    
    $payment_method = $_POST['payment_method'];
    $customer_name = mysqli_real_escape_string($conn, $_POST['full-name']);
    $customer_contact = mysqli_real_escape_string($conn, $_POST['contact']);
    $customer_email = mysqli_real_escape_string($conn, $_POST['email']);
    $customer_address = mysqli_real_escape_string($conn, $_POST['address']);
    
    // Tạo mã đơn hàng
    function generateOrderCode($conn) {
        $prefix = 'ORD';
        $date = date('Ymd');
        $max_attempts = 10;
        $attempt = 0;
        
        do {
            $random = strtoupper(substr(uniqid(), -6));
            $order_code = $prefix . $date . $random;
            
            $check_sql = "SELECT id FROM tbl_order WHERE order_code = '$order_code'";
            $check_result = mysqli_query($conn, $check_sql);
            
            if (mysqli_num_rows($check_result) == 0) {
                return $order_code;
            }
            
            $attempt++;
        } while ($attempt < $max_attempts);
        
        return $prefix . $date . strtoupper(substr(md5(time() . rand()), 0, 6));
    }
    
    $order_code = generateOrderCode($conn);
    $order_date = date("Y-m-d H:i:s");
    $status = $payment_method == 'online' ? 'pending' : 'Ordered';
    $payment_status = $payment_method == 'online' ? 'pending' : 'paid';
    
    // Tạo đơn hàng cho từng món trong giỏ hàng
    $success_count = 0;
    while($cart_item = mysqli_fetch_assoc($cart_result)) {
        $food = mysqli_real_escape_string($conn, $cart_item['food_name']);
        $price = floatval($cart_item['price']);
        $qty = intval($cart_item['quantity']);
        $total = $price * $qty;
        $note = mysqli_real_escape_string($conn, $cart_item['note']);
        
        $insert_sql = "INSERT INTO tbl_order SET
            order_code = ?,
            user_id = ?,
            food = ?,
            price = ?,
            qty = ?,
            total = ?,
            order_date = ?,
            status = ?,
            customer_name = ?,
            customer_contact = ?,
            customer_email = ?,
            customer_address = ?,
            note = ?,
            payment_method = ?,
            payment_status = ?";
        
        $stmt2 = mysqli_prepare($conn, $insert_sql);
        
        if($stmt2 === false) {
            // Kiểm tra xem có phải do thiếu cột payment_method/payment_status không
            $error = mysqli_error($conn);
            error_log("SQL Prepare Error in checkout.php: " . $error);
            
            // Thử insert không có payment_method và payment_status (fallback cho database cũ)
            $insert_sql_fallback = "INSERT INTO tbl_order SET
                order_code = ?,
                user_id = ?,
                food = ?,
                price = ?,
                qty = ?,
                total = ?,
                order_date = ?,
                status = ?,
                customer_name = ?,
                customer_contact = ?,
                customer_email = ?,
                customer_address = ?,
                note = ?";
            
            $stmt2 = mysqli_prepare($conn, $insert_sql_fallback);
            if($stmt2 === false) {
                $_SESSION['checkout-error'] = "Lỗi database: " . mysqli_error($conn) . ". Vui lòng chạy file sql/payment_system.sql để cập nhật database.";
                error_log("SQL Fallback Error: " . mysqli_error($conn));
                break;
            }
            // Type string: s(order_code), i(user_id), s(food), d(price), i(qty), d(total), s(order_date), s(status), s(customer_name), s(customer_contact), s(customer_email), s(customer_address), s(note)
            mysqli_stmt_bind_param($stmt2, "sisdidsssssss", 
                $order_code, $user_id, $food, $price, $qty, $total,
                $order_date, $status, $customer_name, $customer_contact,
                $customer_email, $customer_address, $note);
        } else {
            // Type string: s(order_code), i(user_id), s(food), d(price), i(qty), d(total), s(order_date), s(status), s(customer_name), s(customer_contact), s(customer_email), s(customer_address), s(note), s(payment_method), s(payment_status)
            mysqli_stmt_bind_param($stmt2, "sisdidsssssssss", 
                $order_code, $user_id, $food, $price, $qty, $total,
                $order_date, $status, $customer_name, $customer_contact,
                $customer_email, $customer_address, $note, $payment_method, $payment_status);
        }
        
        if(mysqli_stmt_execute($stmt2)) {
            $success_count++;
            $order_id = mysqli_insert_id($conn);
            
            // Nếu dùng fallback (không có payment columns), cập nhật sau
            if($order_id && strpos($insert_sql, 'payment_method') === false) {
                // Thử cập nhật payment_method và payment_status nếu cột tồn tại
                $update_sql = "UPDATE tbl_order SET payment_method = ?, payment_status = ? WHERE id = ?";
                $update_stmt = mysqli_prepare($conn, $update_sql);
                if($update_stmt) {
                    mysqli_stmt_bind_param($update_stmt, "ssi", $payment_method, $payment_status, $order_id);
                    mysqli_stmt_execute($update_stmt);
                    mysqli_stmt_close($update_stmt);
                }
            }
        } else {
            error_log("Execute Error in checkout.php: " . mysqli_stmt_error($stmt2));
        }
        
        if($stmt2) {
            mysqli_stmt_close($stmt2);
        }
    }
    mysqli_stmt_close($stmt);
    
    if($success_count > 0) {
        // Xóa giỏ hàng
        $delete_sql = "DELETE FROM tbl_cart WHERE user_id = ?";
        $stmt = mysqli_prepare($conn, $delete_sql);
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        
        if($payment_method == 'online') {
            // Redirect đến trang thanh toán online
            $_SESSION['order_code'] = $order_code;
            header('location:'.SITEURL.'user/payment.php?order_code='.$order_code);
            exit();
        } else {
            $_SESSION['order-success'] = "Đặt hàng thành công! Mã đơn hàng: " . $order_code;
            header('location:'.SITEURL.'user/order-history.php');
            exit();
        }
    } else {
        $_SESSION['checkout-error'] = "Có lỗi xảy ra khi đặt hàng!";
    }
}

include('../partials-front/menu.php');
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thanh toán - WowFood</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .checkout-container {
            max-width: 900px;
            margin: 100px auto 50px;
            padding: 20px;
        }
        .checkout-header {
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 2px solid #ff6b81;
        }
        .checkout-header h1 {
            color: #2f3542;
        }
        .checkout-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }
        .checkout-section {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .checkout-section h2 {
            color: #2f3542;
            margin-bottom: 20px;
            font-size: 1.3em;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #2f3542;
            font-weight: bold;
        }
        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1em;
            box-sizing: border-box;
        }
        .payment-methods {
            display: flex;
            gap: 15px;
            margin-top: 10px;
        }
        .payment-option {
            flex: 1;
            padding: 15px;
            border: 2px solid #ddd;
            border-radius: 8px;
            cursor: pointer;
            text-align: center;
            transition: all 0.3s;
        }
        .payment-option:hover {
            border-color: #ff6b81;
        }
        .payment-option.active {
            border-color: #ff6b81;
            background: #fff5f7;
        }
        .payment-option input[type="radio"] {
            display: none;
        }
        .payment-icon {
            font-size: 2em;
            margin-bottom: 5px;
        }
        .order-summary-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        .order-summary-item:last-child {
            border-bottom: none;
        }
        .item-note {
            font-size: 0.85em;
            color: #666;
            font-style: italic;
        }
        .summary-total {
            font-size: 1.5em;
            font-weight: bold;
            color: #ff6b81;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 2px solid #eee;
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
        @media (max-width: 768px) {
            .checkout-content {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="checkout-container">
        <div class="checkout-header">
            <h1>💳 Thanh toán</h1>
        </div>

        <form method="POST" action="">
            <div class="checkout-content">
                <!-- Thông tin giao hàng -->
                <div class="checkout-section">
                    <h2>📋 Thông tin giao hàng</h2>
                    <div class="form-group">
                        <label>Họ tên *</label>
                        <input type="text" name="full-name" value="<?php echo htmlspecialchars($user_data['full_name'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Số điện thoại *</label>
                        <input type="tel" name="contact" value="<?php echo htmlspecialchars($user_data['phone'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Email *</label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($user_data['email'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Địa chỉ giao hàng *</label>
                        <textarea name="address" rows="4" required><?php echo htmlspecialchars($user_data['address'] ?? ''); ?></textarea>
                    </div>
                </div>

                <!-- Tóm tắt đơn hàng -->
                <div class="checkout-section">
                    <h2>🛒 Tóm tắt đơn hàng</h2>
                    <div id="orderSummary">
                        <!-- Order items will be loaded here -->
                    </div>
                    <div class="summary-total" id="orderTotal">0 đ</div>

                    <h2 style="margin-top: 30px;">💳 Phương thức thanh toán</h2>
                    <div class="payment-methods">
                        <label class="payment-option active" onclick="selectPayment('cash')">
                            <input type="radio" name="payment_method" value="cash" checked>
                            <div class="payment-icon">💵</div>
                            <div>Tiền mặt</div>
                        </label>
                        <label class="payment-option" onclick="selectPayment('online')">
                            <input type="radio" name="payment_method" value="online">
                            <div class="payment-icon">💳</div>
                            <div>Online</div>
                        </label>
                    </div>

                    <button type="submit" name="submit" class="submit-btn">Xác nhận đặt hàng</button>
                </div>
            </div>
        </form>
    </div>

    <?php include('../partials-front/footer.php'); ?>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        function selectPayment(method) {
            document.querySelectorAll('.payment-option').forEach(opt => {
                opt.classList.remove('active');
            });
            event.currentTarget.classList.add('active');
            document.querySelector(`input[value="${method}"]`).checked = true;
        }

        // Load giỏ hàng
        fetch('../api/get-cart.php')
            .then(response => response.json())
            .then(data => {
                if(data.success && data.items.length > 0) {
                    let html = '';
                    data.items.forEach(item => {
                        html += `
                            <div class="order-summary-item">
                                <div>
                                    <div><strong>${item.food_name}</strong> x${item.quantity}</div>
                                    ${item.note ? `<div class="item-note">📝 ${item.note}</div>` : ''}
                                </div>
                                <div>${formatPrice(item.item_total)} đ</div>
                            </div>
                        `;
                    });
                    document.getElementById('orderSummary').innerHTML = html;
                    document.getElementById('orderTotal').textContent = formatPrice(data.total) + ' đ';
                } else {
                    window.location.href = '<?php echo SITEURL; ?>user/cart.php';
                }
            });

        function formatPrice(price) {
            return new Intl.NumberFormat('vi-VN').format(price);
        }
    </script>
</body>
</html>

