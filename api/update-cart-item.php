<?php
require_once('../config/constants.php');

header('Content-Type: application/json');

// Kiểm tra đăng nhập
if(!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập!']);
    exit();
}

if($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$user_id = intval($_SESSION['user_id']);
$cart_id = isset($_POST['cart_id']) ? intval($_POST['cart_id']) : 0;
$quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 0;
$note = isset($_POST['note']) ? trim($_POST['note']) : '';

if($cart_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID giỏ hàng không hợp lệ!']);
    exit();
}

if($quantity <= 0) {
    // Xóa item nếu quantity = 0
    $delete_sql = "DELETE FROM tbl_cart WHERE id = ? AND user_id = ?";
    $stmt = mysqli_prepare($conn, $delete_sql);
    mysqli_stmt_bind_param($stmt, "ii", $cart_id, $user_id);
    $result = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    
    if($result) {
        echo json_encode(['success' => true, 'message' => 'Đã xóa khỏi giỏ hàng!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Lỗi khi xóa!']);
    }
} else {
    // Cập nhật số lượng và ghi chú
    $update_sql = "UPDATE tbl_cart SET quantity = ?, note = ? WHERE id = ? AND user_id = ?";
    $stmt = mysqli_prepare($conn, $update_sql);
    mysqli_stmt_bind_param($stmt, "isii", $quantity, $note, $cart_id, $user_id);
    $result = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    
    if($result) {
        echo json_encode(['success' => true, 'message' => 'Đã cập nhật giỏ hàng!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Lỗi khi cập nhật!']);
    }
}
?>

