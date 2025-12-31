<?php
include('../config/constants.php');

// Destroy all session data
session_destroy();

// Redirect to login page
header('location:'.SITEURL.'user/login.php');
?>

