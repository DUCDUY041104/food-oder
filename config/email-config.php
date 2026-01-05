<?php
/**
 * Cấu hình Email SMTP
 * Sử dụng Gmail SMTP để gửi email thật
 */

// Gmail SMTP Settings
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'wowfood6868@gmail.com'); // Thay bằng email Gmail của bạn
define('SMTP_PASSWORD', 'zddfjimwdngfougb'); // App Password (16 ký tự, KHÔNG có khoảng trắng)
define('SMTP_FROM_EMAIL', 'wowfood6868@gmail.com'); // Email gửi đi
define('SMTP_FROM_NAME', 'WowFood');

// Lưu ý: 
// 1. Cần bật "Less secure app access" hoặc tạo "App Password" trong Gmail
// 2. App Password: https://myaccount.google.com/apppasswords
// 3. Nếu dùng 2FA, bắt buộc phải dùng App Password

?>

