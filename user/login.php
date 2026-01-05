<?php 
include('../config/constants.php'); 

// Xử lý đăng nhập trước khi output HTML
if(isset($_POST['submit'])){
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    // Use prepared statement to prevent SQL injection
    $sql = "SELECT * FROM tbl_user WHERE email=? AND status='Active'";
    $stmt = mysqli_prepare($conn, $sql);
    
    if($stmt){
        // Bind the parameters
        mysqli_stmt_bind_param($stmt, "s", $email);
        
        // Execute the statement
        mysqli_stmt_execute($stmt);
        
        // Get the result
        $result = mysqli_stmt_get_result($stmt);
        
        // Check the number of rows returned
        $count = mysqli_num_rows($result);
        
        if($count == 1){
            // Get user data
            $row = mysqli_fetch_assoc($result);
            
            // Verify password
            if(password_verify($password, $row['password'])){
                // Login successful
                $_SESSION['user'] = $row['username'];
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['user_full_name'] = $row['full_name'];
                $_SESSION['login-success'] = "Đăng nhập thành công!";
                
                // Kiểm tra nếu có redirect_food_id thì chuyển đến trang order
                if(isset($_SESSION['redirect_food_id'])) {
                    $food_id = $_SESSION['redirect_food_id'];
                    unset($_SESSION['redirect_food_id']);
                    header('location:'.SITEURL.'order.php?food_id='.$food_id);
                } else {
                    header('location:'.SITEURL.'index.php');
                }
                exit();
            }
            else{
                // Password incorrect
                $_SESSION['login'] = "Email hoặc mật khẩu không đúng!";
                header('location:'.SITEURL.'user/login.php');
                exit();
            }
        }
        else{
            // User not found
            $_SESSION['login'] = "Email hoặc mật khẩu không đúng!";
            header('location:'.SITEURL.'user/login.php');
            exit();
        }
        
        // Close the statement
        mysqli_stmt_close($stmt);
    }
    else{
        $_SESSION['login'] = "Lỗi database!";
        header('location:'.SITEURL.'user/login.php');
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Login - Food Order System</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .login-container {
            max-width: 400px;
            margin: 100px auto;
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
        .login-form input[type="email"],
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
        .error {
            color: red;
            text-align: center;
            margin-bottom: 15px;
            padding: 10px;
            background-color: #ffe6e6;
            border-radius: 5px;
        }
        .success {
            color: green;
            text-align: center;
            margin-bottom: 15px;
            padding: 10px;
            background-color: #e6ffe6;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <?php include('../partials-front/menu.php'); ?>
    
    <div class="login-container">
        <h1>Đăng nhập</h1>
        
        <form action="" method="POST" class="login-form">
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="password" placeholder="Mật khẩu" required>
            <input type="submit" name="submit" value="Đăng nhập" class="btn-primary">
        </form>
        
        <div class="register-link">
            <p>Chưa có tài khoản? <a href="<?php echo SITEURL; ?>user/register.php">Đăng ký tại đây</a></p>
            <p style="margin-top: 10px;"><a href="<?php echo SITEURL; ?>user/forgot-password.php">🔐 Quên mật khẩu?</a></p>
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
        
        $sessionMessages = ['login-success', 'register-success', 'login', 'no-login-message', 'reset-password-success'];
        
        foreach($sessionMessages as $key) {
            if(isset($_SESSION[$key]) && !empty($_SESSION[$key])) {
                $message = extractMessage($_SESSION[$key]);
                if(!empty($message)) {
                    $icon = 'info';
                    $title = 'Thông báo';
                    
                    if(strpos(strtolower($_SESSION[$key]), 'success') !== false || 
                       strpos(strtolower($message), 'thành công') !== false ||
                       strpos(strtolower($message), 'successfully') !== false) {
                        $icon = 'success';
                        $title = 'Thành công!';
                    } elseif(strpos(strtolower($_SESSION[$key]), 'error') !== false || 
                             strpos(strtolower($message), 'lỗi') !== false ||
                             strpos(strtolower($message), 'failed') !== false ||
                             strpos(strtolower($message), 'không đúng') !== false) {
                        $icon = 'error';
                        $title = 'Lỗi!';
                    } elseif(strpos(strtolower($message), 'warning') !== false || 
                             strpos(strtolower($message), 'đăng nhập') !== false ||
                             strpos(strtolower($message), 'đặt hàng') !== false) {
                        $icon = 'warning';
                        $title = 'Yêu cầu đăng nhập!';
                    }
                    
                    echo "Swal.fire({
                        icon: '" . $icon . "',
                        title: '" . $title . "',
                        text: '" . addslashes($message) . "',
                        showConfirmButton: true,
                        timer: 3000
                    });";
                }
                unset($_SESSION[$key]);
            }
        }
        ?>
    </script>
</body>
</html>
