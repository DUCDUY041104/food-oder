<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Site URL - Thay đổi theo domain của bạn
define('SITEURL', 'http://localhost/food_order/');

// ============================================
// CẤU HÌNH DATABASE
// ============================================
// Nếu MySQL của bạn chạy trên port khác (ví dụ: 3307), 
// hãy thay đổi giá trị $port bên dưới
$host = "localhost";
$port = 3306; // Port MySQL (mặc định là 3306, nếu dùng 3307 thì thay đổi)
$username = "root";
$password = ""; // Nhập mật khẩu MySQL nếu có
$dbname = "food-oder";

// Kết nối với port
$conn = new mysqli($host, $username, $password, $dbname, $port);

if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");
?>
