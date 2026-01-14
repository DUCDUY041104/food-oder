<?php
/**
 * File test để kiểm tra cấu hình database và SITEURL
 * Chạy file này trên trình duyệt: http://localhost/food_order/test-config.php
 */

// Include file constants
require_once 'config/constants.php';

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Cấu Hình - Food Order</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .test-box {
            background: white;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .success {
            color: #28a745;
            font-weight: bold;
        }
        .error {
            color: #dc3545;
            font-weight: bold;
        }
        .info {
            background: #e7f3ff;
            padding: 10px;
            border-left: 4px solid #2196F3;
            margin: 10px 0;
        }
        h1 {
            color: #333;
        }
        code {
            background: #f4f4f4;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
        }
    </style>
</head>
<body>
    <h1>🔧 Test Cấu Hình Food Order</h1>
    
    <div class="test-box">
        <h2>1. Kiểm tra SITEURL</h2>
        <?php if (defined('SITEURL') && !empty(SITEURL)): ?>
            <p class="success">✅ SITEURL đã được định nghĩa thành công!</p>
            <div class="info">
                <strong>SITEURL:</strong> <code><?php echo htmlspecialchars(SITEURL); ?></code>
            </div>
        <?php else: ?>
            <p class="error">❌ SITEURL chưa được định nghĩa hoặc rỗng!</p>
        <?php endif; ?>
    </div>

    <div class="test-box">
        <h2>2. Kiểm tra Kết nối Database</h2>
        <?php if (isset($conn)): ?>
            <?php if ($conn->connect_error): ?>
                <p class="error">❌ Kết nối database thất bại!</p>
                <div class="info">
                    <strong>Lỗi:</strong> <?php echo htmlspecialchars($conn->connect_error); ?>
                </div>
            <?php else: ?>
                <p class="success">✅ Kết nối database thành công!</p>
                <div class="info">
                    <strong>Host:</strong> <?php echo htmlspecialchars($host ?? 'N/A'); ?><br>
                    <strong>Port:</strong> <?php echo htmlspecialchars($port ?? 'N/A'); ?><br>
                    <strong>Database:</strong> <?php echo htmlspecialchars($dbname ?? 'N/A'); ?><br>
                    <strong>Username:</strong> <?php echo htmlspecialchars($username ?? 'N/A'); ?>
                </div>
                
                <?php
                // Test query đơn giản
                $test_query = "SELECT 1 as test";
                $result = $conn->query($test_query);
                if ($result) {
                    echo '<p class="success">✅ Test query thành công!</p>';
                } else {
                    echo '<p class="error">❌ Test query thất bại: ' . htmlspecialchars($conn->error) . '</p>';
                }
                ?>
            <?php endif; ?>
        <?php else: ?>
            <p class="error">❌ Biến $conn không tồn tại!</p>
        <?php endif; ?>
    </div>

    <div class="test-box">
        <h2>3. Kiểm tra Thông tin Server</h2>
        <div class="info">
            <strong>HTTP Host:</strong> <?php echo htmlspecialchars($_SERVER['HTTP_HOST'] ?? 'N/A'); ?><br>
            <strong>Document Root:</strong> <?php echo htmlspecialchars($_SERVER['DOCUMENT_ROOT'] ?? 'N/A'); ?><br>
            <strong>Script Name:</strong> <?php echo htmlspecialchars($_SERVER['SCRIPT_NAME'] ?? 'N/A'); ?><br>
            <strong>Script Filename:</strong> <?php echo htmlspecialchars($_SERVER['SCRIPT_FILENAME'] ?? 'N/A'); ?><br>
            <strong>Protocol:</strong> <?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'HTTPS' : 'HTTP'; ?>
        </div>
    </div>

    <div class="test-box">
        <h2>4. Kiểm tra Bảng Database</h2>
        <?php if (isset($conn) && !$conn->connect_error): ?>
            <?php
            $tables = ['tbl_user', 'tbl_admin', 'tbl_category', 'tbl_food', 'tbl_order', 'tbl_chat', 'tbl_verification'];
            $existing_tables = [];
            $missing_tables = [];
            
            foreach ($tables as $table) {
                $check_query = "SHOW TABLES LIKE '$table'";
                $result = $conn->query($check_query);
                if ($result && $result->num_rows > 0) {
                    $existing_tables[] = $table;
                } else {
                    $missing_tables[] = $table;
                }
            }
            ?>
            
            <?php if (count($existing_tables) > 0): ?>
                <p class="success">✅ Tìm thấy <?php echo count($existing_tables); ?> bảng:</p>
                <ul>
                    <?php foreach ($existing_tables as $table): ?>
                        <li><code><?php echo htmlspecialchars($table); ?></code></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
            
            <?php if (count($missing_tables) > 0): ?>
                <p class="error">❌ Thiếu <?php echo count($missing_tables); ?> bảng:</p>
                <ul>
                    <?php foreach ($missing_tables as $table): ?>
                        <li><code><?php echo htmlspecialchars($table); ?></code></li>
                    <?php endforeach; ?>
                </ul>
                <div class="info">
                    <strong>Lưu ý:</strong> Hãy import file <code>sql/food-oder.sql</code> vào database!
                </div>
            <?php endif; ?>
        <?php else: ?>
            <p class="error">❌ Không thể kiểm tra bảng vì kết nối database thất bại!</p>
        <?php endif; ?>
    </div>

    <div class="test-box">
        <h2>📝 Hướng dẫn</h2>
        <ul>
            <li>Nếu SITEURL rỗng: Kiểm tra lại đường dẫn thư mục project trong htdocs</li>
            <li>Nếu kết nối database thất bại: Kiểm tra port MySQL (hiện tại: 3307) và thông tin đăng nhập</li>
            <li>Nếu thiếu bảng: Import file <code>sql/food-oder.sql</code> vào database</li>
            <li>Sau khi test xong, có thể xóa file <code>test-config.php</code> này</li>
        </ul>
    </div>
</body>
</html>

