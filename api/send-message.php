<?php
require_once('../config/constants.php');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

// Kiểm tra đăng nhập
if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập']);
    exit();
}

$message = trim($_POST['message'] ?? '');
if (empty($message)) {
    echo json_encode(['success' => false, 'message' => 'Tin nhắn không được để trống']);
    exit();
}

$sender_type = '';
$user_id = null;
$admin_id = null;

// Xác định người gửi
if (isset($_SESSION['admin_id'])) {
    $sender_type = 'admin';
    $admin_id = $_SESSION['admin_id'];
    $user_id = intval($_POST['user_id'] ?? 0); // Admin trả lời user nào
} elseif (isset($_SESSION['user_id'])) {
    $sender_type = 'user';
    $user_id = $_SESSION['user_id'];
} else {
    echo json_encode(['success' => false, 'message' => 'Không xác định được người gửi']);
    exit();
}

// Làm sạch tin nhắn
$message = mysqli_real_escape_string($conn, $message);

// Lưu tin nhắn vào database
$sql = "INSERT INTO tbl_chat (user_id, admin_id, sender_type, message, is_read) 
        VALUES (?, ?, ?, ?, 0)";

$stmt = mysqli_prepare($conn, $sql);
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "iiss", $user_id, $admin_id, $sender_type, $message);
    
    if (mysqli_stmt_execute($stmt)) {
        echo json_encode([
            'success' => true, 
            'message' => 'Gửi tin nhắn thành công',
            'message_id' => mysqli_insert_id($conn)
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Lỗi khi gửi tin nhắn']);
    }
    mysqli_stmt_close($stmt);
} else {
    echo json_encode(['success' => false, 'message' => 'Lỗi database']);
}
?>

