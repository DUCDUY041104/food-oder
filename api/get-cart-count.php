<?php
require_once('../config/constants.php');

header('Content-Type: application/json');

if(!isset($_SESSION['user_id'])) {
    echo json_encode(['count' => 0]);
    exit();
}

$user_id = intval($_SESSION['user_id']);

$sql = "SELECT COUNT(*) as count FROM tbl_cart WHERE user_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

echo json_encode(['count' => intval($row['count'])]);
?>

