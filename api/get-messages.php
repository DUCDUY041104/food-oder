<?php
require_once('../config/constants.php');
header('Content-Type: application/json');

// Kiểm tra đăng nhập
if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập']);
    exit();
}

$last_message_id = intval($_GET['last_id'] ?? 0);
$user_id = null;
$admin_id = null;

// Xác định người yêu cầu
if (isset($_SESSION['admin_id'])) {
    $admin_id = $_SESSION['admin_id'];
    $target_user_id = intval($_GET['user_id'] ?? 0);
    
    // Admin xem tin nhắn với user cụ thể
    if ($target_user_id > 0) {
        if ($last_message_id > 0) {
            // Chỉ lấy tin nhắn mới
            $sql = "SELECT c.*, 
                           u.full_name as user_name,
                           a.full_name as admin_name
                    FROM tbl_chat c
                    LEFT JOIN tbl_user u ON c.user_id = u.id
                    LEFT JOIN tbl_admin a ON c.admin_id = a.id
                    WHERE c.user_id = ? AND c.id > ?
                    ORDER BY c.created_at ASC";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "ii", $target_user_id, $last_message_id);
        } else {
            // Lấy tất cả tin nhắn
            $sql = "SELECT c.*, 
                           u.full_name as user_name,
                           a.full_name as admin_name
                    FROM tbl_chat c
                    LEFT JOIN tbl_user u ON c.user_id = u.id
                    LEFT JOIN tbl_admin a ON c.admin_id = a.id
                    WHERE c.user_id = ?
                    ORDER BY c.created_at ASC";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "i", $target_user_id);
        }
    } else {
        // Admin xem tất cả tin nhắn chưa đọc
        $sql = "SELECT c.*, 
                       u.full_name as user_name,
                       a.full_name as admin_name
                FROM tbl_chat c
                LEFT JOIN tbl_user u ON c.user_id = u.id
                LEFT JOIN tbl_admin a ON c.admin_id = a.id
                WHERE c.sender_type = 'user' AND c.is_read = 0
                ORDER BY c.created_at DESC
                LIMIT 50";
        $stmt = mysqli_prepare($conn, $sql);
    }
} elseif (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    
    // User xem tin nhắn của mình
    $sql = "SELECT c.*, 
                   u.full_name as user_name,
                   a.full_name as admin_name
            FROM tbl_chat c
            LEFT JOIN tbl_user u ON c.user_id = u.id
            LEFT JOIN tbl_admin a ON c.admin_id = a.id
            WHERE c.user_id = ? AND c.id > ?
            ORDER BY c.created_at ASC";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $user_id, $last_message_id);
} else {
    echo json_encode(['success' => false, 'message' => 'Không xác định được người dùng']);
    exit();
}

if ($stmt) {
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $messages = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $messages[] = [
            'id' => $row['id'],
            'sender_type' => $row['sender_type'],
            'message' => $row['message'],
            'user_name' => $row['user_name'] ?? 'User',
            'admin_name' => $row['admin_name'] ?? 'Admin',
            'created_at' => $row['created_at'],
            'is_read' => $row['is_read']
        ];
    }
    
    // Đánh dấu tin nhắn đã đọc nếu là admin
    if (isset($_SESSION['admin_id']) && $target_user_id > 0 && !empty($messages)) {
        $update_sql = "UPDATE tbl_chat SET is_read = 1 
                       WHERE user_id = ? AND sender_type = 'user' AND is_read = 0";
        $update_stmt = mysqli_prepare($conn, $update_sql);
        if ($update_stmt) {
            mysqli_stmt_bind_param($update_stmt, "i", $target_user_id);
            mysqli_stmt_execute($update_stmt);
            mysqli_stmt_close($update_stmt);
        }
    }
    
    mysqli_stmt_close($stmt);
    
    echo json_encode([
        'success' => true,
        'messages' => $messages
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Lỗi database']);
}
?>

