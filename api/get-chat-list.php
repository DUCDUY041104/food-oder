<?php
require_once('../config/constants.php');
header('Content-Type: application/json');

// Chỉ admin mới có thể xem danh sách chat
if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Không có quyền truy cập']);
    exit();
}

// Lấy danh sách user đã chat, với tin nhắn mới nhất và số tin nhắn chưa đọc
$sql = "SELECT 
            u.id as user_id,
            u.full_name as user_name,
            u.email as user_email,
            MAX(c.created_at) as last_message_time,
            (SELECT COUNT(*) FROM tbl_chat WHERE user_id = u.id AND sender_type = 'user' AND is_read = 0) as unread_count,
            (SELECT message FROM tbl_chat WHERE user_id = u.id ORDER BY created_at DESC LIMIT 1) as last_message
        FROM tbl_user u
        INNER JOIN tbl_chat c ON u.id = c.user_id
        GROUP BY u.id, u.full_name, u.email
        ORDER BY last_message_time DESC";

$result = mysqli_query($conn, $sql);

$chat_list = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $chat_list[] = [
            'user_id' => $row['user_id'],
            'user_name' => $row['user_name'],
            'user_email' => $row['user_email'],
            'last_message_time' => $row['last_message_time'],
            'unread_count' => intval($row['unread_count']),
            'last_message' => $row['last_message']
        ];
    }
}

echo json_encode([
    'success' => true,
    'chat_list' => $chat_list
]);
?>

