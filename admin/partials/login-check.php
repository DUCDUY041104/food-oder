<?php
    // Kiểm tra nếu chưa đăng nhập
    if (!isset($_SESSION['user'])) {
        $_SESSION['no-login-message'] = "<div class='error text-center'>Vui lòng đăng nhập để truy cập Admin Panel.</div>";
        header('location: ' . SITEURL . 'admin/login.php');
        exit();
    }
    
    // Kiểm tra nếu đăng nhập bằng tài khoản user (không phải admin)
    if (isset($_SESSION['user_id']) && !isset($_SESSION['admin_id'])) {
        $_SESSION['access-denied'] = "Bạn không có quyền truy cập trang Admin. Vui lòng đăng nhập bằng tài khoản Admin.";
        header('location: ' . SITEURL . 'index.php');
        exit();
    }
    
    // Kiểm tra nếu không có admin_id (bảo vệ bổ sung)
    if (!isset($_SESSION['admin_id'])) {
        $_SESSION['no-login-message'] = "<div class='error text-center'>Vui lòng đăng nhập bằng tài khoản Admin để truy cập Admin Panel.</div>";
        header('location: ' . SITEURL . 'admin/login.php');
        exit();
    }
?>