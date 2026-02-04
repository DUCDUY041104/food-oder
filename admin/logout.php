<?php 
    require '../config/constants.php';
    require_once('../config/auth.php');

    // Chỉ đăng xuất admin (không ảnh hưởng user session nếu có)
    logoutAdmin();

    header('location:login.php');
    exit();
?>