<?php
require_once('../config/constants.php');

header('Content-Type: application/json');

// Kiểm tra đăng nhập
if(!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập!', 'items' => []]);
    exit();
}

$user_id = intval($_SESSION['user_id']);

$sql = "SELECT c.*, f.image_name 
        FROM tbl_cart c 
        LEFT JOIN tbl_food f ON c.food_id = f.id 
        WHERE c.user_id = ? 
        ORDER BY c.created_at DESC";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$items = [];
$total = 0;

while($row = mysqli_fetch_assoc($result)) {
    $item_total = floatval($row['price']) * intval($row['quantity']);
    $total += $item_total;
    
    $items[] = [
        'id' => intval($row['id']),
        'food_id' => intval($row['food_id']),
        'food_name' => $row['food_name'],
        'price' => floatval($row['price']),
        'quantity' => intval($row['quantity']),
        'note' => $row['note'],
        'image_name' => $row['image_name'],
        'item_total' => $item_total
    ];
}

mysqli_stmt_close($stmt);

echo json_encode([
    'success' => true,
    'items' => $items,
    'total' => $total,
    'count' => count($items)
]);
?>

