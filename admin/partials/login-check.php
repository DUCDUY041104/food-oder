<?php
/**
 * Admin Login Check
 * Kiểm tra admin đã đăng nhập chưa
 * Chỉ cho phép admin, không cho user
 * LƯU Ý: dùng __DIR__ để include đúng đường dẫn, tránh lỗi khi gọi từ file index.php
 */
require_once(__DIR__ . '/../../config/constants.php');
require_once(__DIR__ . '/../../config/auth.php');

// Yêu cầu admin phải đăng nhập (chỉ admin, không phải user)
requireAdminLogin();
?>