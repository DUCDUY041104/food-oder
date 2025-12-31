<?php
require_once('../config/constants.php');
header('Content-Type: application/json');

// Kiểm tra đăng nhập
if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập']);
    exit();
}

if (isset($_SESSION['user_id'])) {
    // User: đánh dấu tất cả tin nhắn từ admin là đã đọc
    $sql = "UPDATE tbl_chat 
            SET is_read = 1 
            WHERE user_id = ? AND sender_type = 'admin' AND is_read = 0";
    $stmt = mysqli_prepare($conn, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        echo json_encode(['success' => true, 'message' => 'Đã đánh dấu đã đọc']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Lỗi database']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Không xác định được người dùng']);
}
?>

