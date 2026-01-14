<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Tự động detect SITEURL từ URL hiện tại để tránh lỗi URL rỗng
if (!defined('SITEURL')) {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
    
    // Lấy đường dẫn thư mục gốc của project từ DOCUMENT_ROOT
    $doc_root = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']);
    $script_file = str_replace('\\', '/', $_SERVER['SCRIPT_FILENAME']);
    $relative_path = str_replace($doc_root, '', dirname($script_file));
    $relative_path = str_replace('\\', '/', $relative_path);
    
    // Đảm bảo có dấu / ở đầu và cuối
    if (substr($relative_path, 0, 1) !== '/') {
        $relative_path = '/' . $relative_path;
    }
    if (substr($relative_path, -1) !== '/') {
        $relative_path .= '/';
    }
    
    // Nếu không tìm được, dùng giá trị mặc định
    if (empty($relative_path) || $relative_path === '//') {
        $relative_path = '/food_order/';
    }
    
    define('SITEURL', $protocol . '://' . $host . $relative_path);
}

// ============================================
// CẤU HÌNH DATABASE
// ============================================
// Nếu MySQL của bạn chạy trên port khác (ví dụ: 3306), 
// hãy thay đổi giá trị $port bên dưới
$host = "localhost";
$port = 3306; // Port MySQL (mặc định là 3306, nếu dùng 3307 thì giữ nguyên)
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
