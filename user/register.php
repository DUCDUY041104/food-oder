<?php 
include('../config/constants.php'); 

// Xử lý đăng ký trước khi output HTML
if(isset($_POST['submit'])){
    $full_name = mysqli_real_escape_string($conn, $_POST['full_name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
    $address = isset($_POST['address']) ? mysqli_real_escape_string($conn, $_POST['address']) : '';
    $verification_type = isset($_POST['verification_type']) ? $_POST['verification_type'] : 'email';
    
    // Validate password match
    if($password !== $confirm_password){
        $_SESSION['register'] = "Mật khẩu không khớp!";
        header('location:'.SITEURL.'user/register.php');
        exit();
    }
    
    // Validate password length
    if(strlen($password) < 6){
        $_SESSION['register'] = "Mật khẩu phải có ít nhất 6 ký tự!";
        header('location:'.SITEURL.'user/register.php');
        exit();
    }
    
    // Validate email format
    if(!filter_var($email, FILTER_VALIDATE_EMAIL)){
        $_SESSION['register'] = "Email không hợp lệ!";
        header('location:'.SITEURL.'user/register.php');
        exit();
    }
    
    // Chỉ cho phép đăng ký bằng Gmail
    $email_domain = substr(strrchr($email, "@"), 1);
    if(strtolower($email_domain) !== 'gmail.com'){
        $_SESSION['register'] = "Chỉ chấp nhận đăng ký bằng Gmail!";
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
        $_SESSION['register'] = "Email đã tồn tại!";
        header('location:'.SITEURL.'user/register.php');
        exit();
    }
    mysqli_stmt_close($stmt);
    
    // Lưu thông tin đăng ký vào session để xác minh (chỉ email)
    $_SESSION['pending_registration'] = [
        'full_name' => $full_name,
        'email' => $email,
        'password' => $password, // Lưu password gốc để hash sau khi xác minh
        'phone' => $phone,
        'address' => $address,
        'verification_type' => 'email' // Chỉ dùng email
    ];
    
    // Gửi mã xác minh qua email - Gọi trực tiếp function thay vì dùng cURL
    // Include function sendEmailVerification
    require_once(__DIR__ . '/../api/phpmailer-send.php');
    
    // Tạo mã xác minh và gửi email trực tiếp
    $verification_code = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
    $expires_at = date('Y-m-d H:i:s', time() + 600);
    
    // Xóa mã cũ
    $delete_sql = "DELETE FROM tbl_verification WHERE 
        email = ? AND 
        is_verified = 0 AND 
        expires_at < UTC_TIMESTAMP()";
    $stmt = mysqli_prepare($conn, $delete_sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
    
    // Lưu mã mới
    $insert_sql = "INSERT INTO tbl_verification SET
        email = ?,
        phone = NULL,
        verification_code = ?,
        verification_type = 'email',
        expires_at = ?,
        is_verified = 0,
        attempts = 0";
    $stmt = mysqli_prepare($conn, $insert_sql);
    if (!$stmt) {
        $_SESSION['register'] = "Lỗi khi chuẩn bị lưu mã xác minh: " . mysqli_error($conn);
        unset($_SESSION['pending_registration']);
        header('location:'.SITEURL.'user/register.php');
        exit();
    }
    
    mysqli_stmt_bind_param($stmt, "sss", $email, $verification_code, $expires_at);
    $result = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    
    if (!$result) {
        $_SESSION['register'] = "Lỗi khi lưu mã xác minh: " . mysqli_error($conn);
        unset($_SESSION['pending_registration']);
        header('location:'.SITEURL.'user/register.php');
        exit();
    }
    
    // Gửi email sử dụng function từ send-verification.php
    $subject = "Mã xác minh đăng ký - WowFood";
    $message = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .code { font-size: 32px; font-weight: bold; color: #ff6b81; text-align: center; padding: 20px; background: #f1f2f6; border-radius: 10px; margin: 20px 0; letter-spacing: 5px; }
            .footer { margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; color: #666; font-size: 0.9em; }
        </style>
    </head>
    <body>
        <div class='container'>
            <h2>Xác minh đăng ký tài khoản WowFood</h2>
            <p>Xin chào,</p>
            <p>Cảm ơn bạn đã đăng ký tài khoản tại WowFood. Vui lòng sử dụng mã xác minh sau để hoàn tất đăng ký:</p>
            <div class='code'>{$verification_code}</div>
            <p><strong>Mã này có hiệu lực trong 10 phút.</strong></p>
            <p>Nếu bạn không yêu cầu mã này, vui lòng bỏ qua email này.</p>
            <div class='footer'>
                <p>Trân trọng,<br>Đội ngũ WowFood</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    // Gửi email bằng PHPMailer
    $sent = false;
    if (function_exists('sendEmailWithPHPMailer')) {
        $sent = sendEmailWithPHPMailer($email, $subject, $message);
    }
    
    // Log mã xác minh để test (chỉ trên localhost)
    $is_localhost = (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || 
                     strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false);
    if ($is_localhost) {
        $log_file = __DIR__ . '/../logs/verification_codes.log';
        $log_message = date('Y-m-d H:i:s') . " - Email: {$email}, Code: {$verification_code}\n";
        file_put_contents($log_file, $log_message, FILE_APPEND);
    }
    
    if ($sent) {
        // Chuyển đến trang xác minh mã
        header('location:'.SITEURL.'user/verify-code.php');
        exit();
    } else {
        $_SESSION['register'] = "Không thể gửi mã xác minh. Vui lòng kiểm tra cấu hình email hoặc thử lại sau.";
        unset($_SESSION['pending_registration']);
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
        
        <form action="" method="POST" class="register-form" id="registerForm">
            <input type="text" name="full_name" placeholder="Họ tên" required>
            <input type="email" name="email" placeholder="(Ví dụ: example@gmail.com)" required pattern="[a-zA-Z0-9._%+-]+@gmail\.com$" title="Chỉ chấp nhận địa chỉ Gmail">
            <input type="password" name="password" placeholder="Mật khẩu" required minlength="6">
            <input type="password" name="confirm_password" placeholder="Xác nhận mật khẩu" required>
            <input type="tel" name="phone" placeholder="Số điện thoại">
            <textarea name="address" placeholder="Địa chỉ"></textarea>
            
            <div style="margin-bottom: 15px; padding: 10px; background-color: #e3f2fd; border-radius: 5px; font-size: 0.9em; color: #1976d2;">
                <strong>📧 Lưu ý:</strong> Mã xác minh sẽ được gửi đến email Gmail của bạn.
            </div>
            
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
                             strpos(strtolower($message), 'đã tồn tại') !== false ||
                             strpos(strtolower($message), 'không hợp lệ') !== false ||
                             strpos(strtolower($message), 'bắt buộc') !== false ||
                             strpos(strtolower($message), 'already exists') !== false ||
                             strpos(strtolower($message), 'do not match') !== false) {
                        $icon = 'error';
                        $title = 'Lỗi!';
                    } elseif(strpos(strtolower($message), 'warning') !== false) {
                        $icon = 'warning';
                        $title = 'Cảnh báo!';
                    } elseif($key === 'register-info') {
                        $icon = 'info';
                        $title = 'Thông tin test';
                    }
                    
                    // Xử lý HTML trong message (cho register-info)
                    $htmlContent = '';
                    if($key === 'register-info' && strpos($_SESSION[$key], '<') !== false) {
                        $htmlContent = ', html: `' . $_SESSION[$key] . '`';
                    }
                    
                    echo "Swal.fire({
                        icon: '" . $icon . "',
                        title: '" . $title . "',
                        " . ($htmlContent ? $htmlContent : "text: '" . addslashes($message) . "'") . ",
                        showConfirmButton: true,
                        timer: " . ($key === 'register-info' ? '5000' : '3000') . "
                    });";
                }
                unset($_SESSION[$key]);
            }
        }
        ?>
    </script>
</body>
</html>
