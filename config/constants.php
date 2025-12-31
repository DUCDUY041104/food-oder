<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!defined('SITEURL')) {
    define('SITEURL', 'http://localhost/food_order/');
}

$host = "localhost";
$username = "root";
$password = "";
$dbname = "food-oder";


$conn = new mysqli($host, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");
?>
