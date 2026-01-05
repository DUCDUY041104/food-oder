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
$food_id = isset($_POST['food_id']) ? intval($_POST['food_id']) : 0;
$quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;
$note = isset($_POST['note']) ? trim($_POST['note']) : '';

if($food_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Món ăn không hợp lệ!']);
    exit();
}

if($quantity <= 0) {
    echo json_encode(['success' => false, 'message' => 'Số lượng phải lớn hơn 0!']);
    exit();
}

// Lấy thông tin món ăn
$food_sql = "SELECT title, price FROM tbl_food WHERE id = ?";
$stmt = mysqli_prepare($conn, $food_sql);
mysqli_stmt_bind_param($stmt, "i", $food_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if(mysqli_num_rows($result) == 0) {
    mysqli_stmt_close($stmt);
    echo json_encode(['success' => false, 'message' => 'Món ăn không tồn tại!']);
    exit();
}

$food = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

$food_name = $food['title'];
$price = floatval($food['price']);

// Kiểm tra xem món đã có trong giỏ hàng chưa
$check_sql = "SELECT id, quantity FROM tbl_cart WHERE user_id = ? AND food_id = ?";
$stmt = mysqli_prepare($conn, $check_sql);
mysqli_stmt_bind_param($stmt, "ii", $user_id, $food_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if(mysqli_num_rows($result) > 0) {
    // Cập nhật số lượng và ghi chú
    $cart_item = mysqli_fetch_assoc($result);
    $new_quantity = $cart_item['quantity'] + $quantity;
    
    $update_sql = "UPDATE tbl_cart SET quantity = ?, note = ? WHERE id = ?";
    $stmt2 = mysqli_prepare($conn, $update_sql);
    mysqli_stmt_bind_param($stmt2, "isi", $new_quantity, $note, $cart_item['id']);
    $result2 = mysqli_stmt_execute($stmt2);
    mysqli_stmt_close($stmt2);
    mysqli_stmt_close($stmt);
    
    if($result2) {
        echo json_encode(['success' => true, 'message' => 'Đã cập nhật giỏ hàng!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Lỗi khi cập nhật giỏ hàng!']);
    }
} else {
    // Thêm mới vào giỏ hàng
    mysqli_stmt_close($stmt);
    
    $insert_sql = "INSERT INTO tbl_cart (user_id, food_id, food_name, price, quantity, note) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $insert_sql);
    mysqli_stmt_bind_param($stmt, "iisdis", $user_id, $food_id, $food_name, $price, $quantity, $note);
    $result = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    
    if($result) {
        echo json_encode(['success' => true, 'message' => 'Đã thêm vào giỏ hàng!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Lỗi khi thêm vào giỏ hàng!']);
    }
}
?>

