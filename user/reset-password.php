<?php 
include('../config/constants.php'); 

// Kiểm tra xem có email trong session không
if(!isset($_SESSION['reset_password_email'])){
    $_SESSION['forgot-password'] = "Vui lòng yêu cầu đặt lại mật khẩu trước!";
    header('location:'.SITEURL.'user/forgot-password.php');
    exit();
}

$email = $_SESSION['reset_password_email'];

// Xử lý reset password
if(isset($_POST['submit'])){
    $code = trim($_POST['code']);
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate password match
    if($new_password !== $confirm_password){
        $_SESSION['reset-password'] = "Mật khẩu không khớp!";
        header('location:'.SITEURL.'user/reset-password.php');
        exit();
    }
    
    // Validate password length
    if(strlen($new_password) < 6){
        $_SESSION['reset-password'] = "Mật khẩu phải có ít nhất 6 ký tự!";
        header('location:'.SITEURL.'user/reset-password.php');
        exit();
    }
    
    // Kiểm tra mã xác minh
    $check_sql = "SELECT * FROM tbl_verification WHERE 
        email = ? AND 
        verification_code = ? AND 
        verification_type = 'email' AND 
        is_verified = 0 AND 
        expires_at > UTC_TIMESTAMP() AND
        attempts < 5
        ORDER BY created_at DESC LIMIT 1";
    $stmt = mysqli_prepare($conn, $check_sql);
    
    if($stmt){
        mysqli_stmt_bind_param($stmt, "ss", $email, $code);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $count = mysqli_num_rows($result);
        
        if($count == 1){
            // Mã hợp lệ, cập nhật mật khẩu
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            
            $update_sql = "UPDATE tbl_user SET password = ? WHERE email = ? AND status = 'Active'";
            $stmt2 = mysqli_prepare($conn, $update_sql);
            
            if($stmt2){
                mysqli_stmt_bind_param($stmt2, "ss", $hashed_password, $email);
                $result2 = mysqli_stmt_execute($stmt2);
                mysqli_stmt_close($stmt2);
                
                if($result2){
                    // Đánh dấu mã đã được sử dụng
                    $mark_sql = "UPDATE tbl_verification SET is_verified = 1 WHERE email = ? AND verification_code = ?";
                    $stmt3 = mysqli_prepare($conn, $mark_sql);
                    if($stmt3){
                        mysqli_stmt_bind_param($stmt3, "ss", $email, $code);
                        mysqli_stmt_execute($stmt3);
                        mysqli_stmt_close($stmt3);
                    }
                    
                    // Xóa session
                    unset($_SESSION['reset_password_email']);
                    
                    $_SESSION['reset-password-success'] = "Đặt lại mật khẩu thành công! Vui lòng đăng nhập với mật khẩu mới.";
                    header('location:'.SITEURL.'user/login.php');
                    exit();
                } else {
                    $_SESSION['reset-password'] = "Lỗi khi cập nhật mật khẩu. Vui lòng thử lại.";
                }
            } else {
                $_SESSION['reset-password'] = "Lỗi database!";
            }
        } else {
            // Tăng số lần thử
            $update_attempts_sql = "UPDATE tbl_verification SET attempts = attempts + 1 WHERE email = ? AND verification_code = ?";
            $stmt4 = mysqli_prepare($conn, $update_attempts_sql);
            if($stmt4){
                mysqli_stmt_bind_param($stmt4, "ss", $email, $code);
                mysqli_stmt_execute($stmt4);
                mysqli_stmt_close($stmt4);
            }
            
            $_SESSION['reset-password'] = "Mã xác minh không đúng hoặc đã hết hạn!";
        }
        
        mysqli_stmt_close($stmt);
    } else {
        $_SESSION['reset-password'] = "Lỗi database!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đặt lại mật khẩu - Food Order System</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .login-container {
            max-width: 400px;
            margin: 50px auto;
            padding: 30px;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        .login-container h1 {
            text-align: center;
            color: #2f3542;
            margin-bottom: 30px;
        }
        .login-form input[type="text"],
        .login-form input[type="password"] {
            width: 100%;
            padding: 12px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
            box-sizing: border-box;
        }
        .login-form input[type="submit"] {
            width: 100%;
            padding: 12px;
            background-color: #ff6b81;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
            cursor: pointer;
        }
        .login-form input[type="submit"]:hover {
            background-color: #ff4757;
        }
        .register-link {
            text-align: center;
            margin-top: 20px;
        }
        .register-link a {
            color: #ff6b81;
        }
        .back-link {
            text-align: center;
            margin-top: 15px;
        }
        .back-link a {
            color: #666;
            text-decoration: none;
        }
        .back-link a:hover {
            color: #ff6b81;
        }
        .info-box {
            background-color: #e8f5e9;
            border-left: 4px solid #4CAF50;
            padding: 12px;
            margin-bottom: 20px;
            border-radius: 5px;
            font-size: 0.9em;
        }
        .email-display {
            background-color: #f5f5f5;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
            text-align: center;
            font-weight: bold;
            color: #666;
        }
    </style>
</head>
<body>
    <?php include('../partials-front/menu.php'); ?>
    
    <div class="login-container">
        <h1>🔑 Đặt lại mật khẩu</h1>
        
        <div class="info-box">
            <strong>✓</strong> Mã đặt lại mật khẩu đã được gửi đến email của bạn.
        </div>
        
        <div class="email-display">
            📧 <?php echo htmlspecialchars($email); ?>
        </div>
        
        <form action="" method="POST" class="login-form">
            <input type="text" name="code" placeholder="Nhập mã xác minh (6 số)" required maxlength="6" pattern="[0-9]{6}" title="Mã xác minh gồm 6 chữ số">
            <input type="password" name="new_password" placeholder="Mật khẩu mới" required minlength="6">
            <input type="password" name="confirm_password" placeholder="Xác nhận mật khẩu mới" required minlength="6">
            <input type="submit" name="submit" value="Đặt lại mật khẩu" class="btn-primary">
        </form>
        
        <div class="back-link">
            <a href="<?php echo SITEURL; ?>user/forgot-password.php">Gửi lại mã</a> | 
            <a href="<?php echo SITEURL; ?>user/login.php">Quay lại đăng nhập</a>
        </div>
    </div>
    
    <?php include('../partials-front/footer.php'); ?>
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        <?php
        function extractMessage($html) {
            $html = strip_tags($html);
            return trim($html);
        }
        
        if(isset($_SESSION['reset-password']) && !empty($_SESSION['reset-password'])) {
            $message = extractMessage($_SESSION['reset-password']);
            if(!empty($message)) {
                echo "Swal.fire({
                    icon: 'error',
                    title: 'Lỗi!',
                    text: '" . addslashes($message) . "',
                    confirmButtonColor: '#ff6b81'
                });";
                unset($_SESSION['reset-password']);
            }
        }
        
        if(isset($_SESSION['reset-password-success']) && !empty($_SESSION['reset-password-success'])) {
            $message = extractMessage($_SESSION['reset-password-success']);
            if(!empty($message)) {
                echo "Swal.fire({
                    icon: 'success',
                    title: 'Thành công!',
                    text: '" . addslashes($message) . "',
                    confirmButtonColor: '#ff6b81',
                    showConfirmButton: true,
                    timer: 5000
                });";
                unset($_SESSION['reset-password-success']);
            }
        }
        ?>
    </script>
</body>
</html>

