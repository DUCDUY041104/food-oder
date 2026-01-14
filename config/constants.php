<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Tự động detect SITEURL từ URL hiện tại để tránh lỗi URL rỗng
if (!defined('SITEURL')) {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
    
    // Phương pháp 1: Lấy từ REQUEST_URI (chính xác hơn)
    $request_uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
    $script_name = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '';
    
    // Loại bỏ query string và fragment
    $request_uri = preg_replace('/\?.*$/', '', $request_uri);
    $request_uri = preg_replace('/#.*$/', '', $request_uri);
    
    // Lấy đường dẫn thư mục từ SCRIPT_NAME (ví dụ: /food_order/test-config.php -> /food_order/)
    $base_path = dirname($script_name);
    $base_path = str_replace('\\', '/', $base_path);
    
    // Đảm bảo có dấu / ở đầu và cuối
    if (substr($base_path, 0, 1) !== '/') {
        $base_path = '/' . $base_path;
    }
    if (substr($base_path, -1) !== '/') {
        $base_path .= '/';
    }
    
    // Phương pháp 2: Fallback - detect từ DOCUMENT_ROOT và SCRIPT_FILENAME
    if (empty($base_path) || $base_path === '//' || $base_path === '/./') {
        $doc_root = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']);
        $script_file = str_replace('\\', '/', $_SERVER['SCRIPT_FILENAME']);
        
        // Tìm thư mục gốc của project (thư mục chứa config/constants.php)
        $config_dir = str_replace('\\', '/', __DIR__);
        $relative_path = str_replace($doc_root, '', dirname($config_dir));
        $relative_path = str_replace('\\', '/', $relative_path);
        
        if (substr($relative_path, 0, 1) !== '/') {
            $relative_path = '/' . $relative_path;
        }
        if (substr($relative_path, -1) !== '/') {
            $relative_path .= '/';
        }
        
        $base_path = $relative_path;
    }
    
    // Phương pháp 3: Nếu vẫn không tìm được, dùng giá trị mặc định
    if (empty($base_path) || $base_path === '//' || $base_path === '/./') {
        // Thử các tên thư mục phổ biến (case-insensitive)
        $possible_paths = ['/food_order/', '/Food_order/', '/Food_Order/'];
        $doc_root = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']);
        
        foreach ($possible_paths as $path) {
            $full_path = $doc_root . trim($path, '/');
            if (file_exists($full_path . 'config/constants.php')) {
                $base_path = $path;
                break;
            }
        }
        
        // Cuối cùng, dùng giá trị mặc định
        if (empty($base_path) || $base_path === '//' || $base_path === '/./') {
            $base_path = '/food_order/';
        }
    }
    
    define('SITEURL', $protocol . '://' . $host . $base_path);
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
