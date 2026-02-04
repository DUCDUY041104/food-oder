<?php
/**
 * User Login Check
 * Kiểm tra user đã đăng nhập chưa
 * Chỉ cho phép user, không cho admin
 * 
 * Sử dụng: require_once('partials/login-check.php');
 */
if (!defined('SITEURL')) {
    require_once('../../config/constants.php');
}
require_once('../../config/auth.php');

// Yêu cầu user phải đăng nhập (chỉ user, không phải admin)
requireUserLogin();

