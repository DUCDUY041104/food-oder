<?php
include('../config/constants.php');
require_once('../config/auth.php');

// Chỉ đăng xuất user (không ảnh hưởng admin session nếu có)
logoutUser();

// Redirect to login page
header('location:'.SITEURL.'user/login.php');
exit();
?>

