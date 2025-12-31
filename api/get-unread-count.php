<?php
require_once('../config/constants.php');
header('Content-Type: application/json');

// Kiểm tra đăng nhập
if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'unread_count' => 0]);
    exit();
}

$unread_count = 0;

if (isset($_SESSION['user_id'])) {
    // User: đếm tin nhắn từ admin chưa đọc
    $sql = "SELECT COUNT(*) as count 
            FROM tbl_chat 
            WHERE user_id = ? AND sender_type = 'admin' AND is_read = 0";
    $stmt = mysqli_prepare($conn, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if ($row = mysqli_fetch_assoc($result)) {
            $unread_count = intval($row['count']);
        }
        mysqli_stmt_close($stmt);
    }
} elseif (isset($_SESSION['admin_id'])) {
    // Admin: đếm tổng số tin nhắn từ user chưa đọc
    $sql = "SELECT COUNT(*) as count 
            FROM tbl_chat 
            WHERE sender_type = 'user' AND is_read = 0";
    $result = mysqli_query($conn, $sql);
    if ($row = mysqli_fetch_assoc($result)) {
        $unread_count = intval($row['count']);
    }
}

echo json_encode([
    'success' => true,
    'unread_count' => $unread_count
]);
?>

