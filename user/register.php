<?php 
include('../config/constants.php'); 

// Xử lý đăng ký trước khi output HTML
if(isset($_POST['submit'])){
    $full_name = mysqli_real_escape_string($conn, $_POST['full_name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $phone = isset($_POST['phone']) ? mysqli_real_escape_string($conn, $_POST['phone']) : '';
    $address = isset($_POST['address']) ? mysqli_real_escape_string($conn, $_POST['address']) : '';
    
    // Validate password match
    if($password !== $confirm_password){
        $_SESSION['register'] = "Passwords do not match!";
        header('location:'.SITEURL.'user/register.php');
        exit();
    }
    
    // Validate password length
    if(strlen($password) < 6){
        $_SESSION['register'] = "Password must be at least 6 characters!";
        header('location:'.SITEURL.'user/register.php');
        exit();
    }
    
    // Check if email already exists
    $check_sql = "SELECT * FROM tbl_user WHERE email=?";
    $stmt = mysqli_prepare($conn, $check_sql);
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if(mysqli_num_rows($result) > 0){
        mysqli_stmt_close($stmt);
        $_SESSION['register'] = "Email already exists!";
        header('location:'.SITEURL.'user/register.php');
        exit();
    }
    mysqli_stmt_close($stmt);
    
    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Generate username from email
    $username = explode('@', $email)[0];
    $username = preg_replace('/[^a-zA-Z0-9_]/', '', $username);
    
    // Make sure username is unique
    $check_username = $username;
    $counter = 1;
    while(true){
        $check_sql = "SELECT * FROM tbl_user WHERE username='$check_username'";
        $check_res = mysqli_query($conn, $check_sql);
        if(mysqli_num_rows($check_res) == 0){
            break;
        }
        $check_username = $username . $counter;
        $counter++;
    }
    $username = $check_username;
    
    // Insert new user
    $sql = "INSERT INTO tbl_user SET
        full_name=?,
        username=?,
        password=?,
        email=?,
        phone=?,
        address=?,
        status='Active'
    ";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ssssss", $full_name, $username, $hashed_password, $email, $phone, $address);
    $res = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    
    if($res){
        $_SESSION['register-success'] = "Đăng ký thành công! Vui lòng đăng nhập.";
        header('location:'.SITEURL.'user/login.php');
        exit();
    }
    else{
        $_SESSION['register'] = "Đăng ký thất bại! Vui lòng thử lại.";
        header('location:'.SITEURL.'user/register.php');
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Register - Food Order System</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .register-container {
            max-width: 500px;
            margin: 50px auto;
            padding: 30px;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        .register-container h1 {
            text-align: center;
            color: #2f3542;
            margin-bottom: 30px;
        }
        .register-form input[type="text"],
        .register-form input[type="password"],
        .register-form input[type="email"],
        .register-form input[type="tel"],
        .register-form textarea {
            width: 100%;
            padding: 12px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
            box-sizing: border-box;
            font-family: Arial, Helvetica, sans-serif;
        }
        .register-form textarea {
            resize: vertical;
            min-height: 80px;
        }
        .register-form input[type="submit"] {
            width: 100%;
            padding: 12px;
            background-color: #ff6b81;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
            cursor: pointer;
        }
        .register-form input[type="submit"]:hover {
            background-color: #ff4757;
        }
        .login-link {
            text-align: center;
            margin-top: 20px;
        }
        .login-link a {
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
    
    <div class="register-container">
        <h1>Đăng ký</h1>
        
        <form action="" method="POST" class="register-form">
            <input type="text" name="full_name" placeholder="Họ tên" required>
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="password" placeholder="Mật khẩu" required minlength="6">
            <input type="password" name="confirm_password" placeholder="Xác nhận mật khẩu" required>
            <input type="tel" name="phone" placeholder="Số điện thoại (Tùy chọn)">
            <textarea name="address" placeholder="Địa chỉ (Tùy chọn)"></textarea>
            <input type="submit" name="submit" value="Đăng ký" class="btn-primary">
        </form>
        
        <div class="login-link">
            <p>Đã có tài khoản? <a href="<?php echo SITEURL; ?>user/login.php">Đăng nhập tại đây</a></p>
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
        
        $sessionMessages = ['register-success', 'register'];
        
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
                             strpos(strtolower($message), 'không khớp') !== false ||
                             strpos(strtolower($message), 'đã tồn tại') !== false) {
                        $icon = 'error';
                        $title = 'Lỗi!';
                    } elseif(strpos(strtolower($message), 'warning') !== false) {
                        $icon = 'warning';
                        $title = 'Cảnh báo!';
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
