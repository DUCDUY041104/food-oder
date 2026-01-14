<?php
include('../config/constants.php');

if(!isset($_SESSION['user_id'])) {
    header('location:'.SITEURL.'user/login.php');
    exit();
}

$order_code = $_GET['order_code'] ?? '';

if(empty($order_code)) {
    header('location:'.SITEURL.'user/order-history.php');
    exit();
}

include('../partials-front/menu.php');
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thanh toán thành công - WowFood</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .success-container {
            max-width: 600px;
            margin: 100px auto 50px;
            padding: 40px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            text-align: center;
        }
        .success-icon {
            font-size: 80px;
            margin-bottom: 20px;
        }
        .success-message {
            color: #2f3542;
            margin-bottom: 30px;
        }
        .success-message h1 {
            color: #2ed573;
            margin-bottom: 10px;
        }
        .order-info {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            text-align: left;
        }
        .btn-group {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-top: 30px;
        }
        .btn {
            padding: 12px 30px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: bold;
            transition: all 0.3s;
        }
        .btn-primary {
            background: #ff6b81;
            color: white;
        }
        .btn-primary:hover {
            background: #ff4757;
        }
        .btn-secondary {
            background: #f1f2f6;
            color: #2f3542;
        }
        .btn-secondary:hover {
            background: #dfe4ea;
        }
    </style>
</head>
<body>
    <div class="success-container">
        <div class="success-icon">✅</div>
        <div class="success-message">
            <h1>Thanh toán thành công!</h1>
            <p>Cảm ơn bạn đã thanh toán. Đơn hàng của bạn đã được xác nhận.</p>
        </div>
        
        <div class="order-info">
            <p><strong>Mã đơn hàng:</strong> <?php echo htmlspecialchars($order_code); ?></p>
            <p><strong>Thời gian:</strong> <?php echo date('d/m/Y H:i:s'); ?></p>
        </div>
        
        <div class="btn-group">
            <a href="<?php echo SITEURL; ?>user/order-history.php" class="btn btn-primary">Xem đơn hàng</a>
            <a href="<?php echo SITEURL; ?>food.php" class="btn btn-secondary">Tiếp tục mua sắm</a>
        </div>
    </div>

    <?php include('../partials-front/footer.php'); ?>
</body>
</html>

