<?php
include('../config/constants.php');

if(!isset($_SESSION['user_id'])) {
    header('location:'.SITEURL.'user/login.php');
    exit();
}

$order_code = $_GET['order_code'] ?? '';
$reason = $_GET['reason'] ?? 'Lỗi không xác định';

include('../partials-front/menu.php');
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thanh toán thất bại - WowFood</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .failed-container {
            max-width: 600px;
            margin: 100px auto 50px;
            padding: 40px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            text-align: center;
        }
        .failed-icon {
            font-size: 80px;
            margin-bottom: 20px;
        }
        .failed-message {
            color: #2f3542;
            margin-bottom: 30px;
        }
        .failed-message h1 {
            color: #ff4757;
            margin-bottom: 10px;
        }
        .error-reason {
            background: #ffebee;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            color: #c62828;
            border-left: 4px solid #c62828;
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
    <div class="failed-container">
        <div class="failed-icon">❌</div>
        <div class="failed-message">
            <h1>Thanh toán thất bại</h1>
            <p>Rất tiếc, thanh toán của bạn không thành công.</p>
        </div>
        
        <div class="error-reason">
            <strong>Lý do:</strong> <?php echo htmlspecialchars($reason); ?>
        </div>
        
        <?php if($order_code): ?>
        <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin: 20px 0;">
            <p><strong>Mã đơn hàng:</strong> <?php echo htmlspecialchars($order_code); ?></p>
        </div>
        <?php endif; ?>
        
        <div class="btn-group">
            <a href="<?php echo SITEURL; ?>user/payment.php?order_code=<?php echo urlencode($order_code); ?>" class="btn btn-primary">Thử lại thanh toán</a>
            <a href="<?php echo SITEURL; ?>user/order-history.php" class="btn btn-secondary">Xem đơn hàng</a>
        </div>
    </div>

    <?php include('../partials-front/footer.php'); ?>
</body>
</html>

