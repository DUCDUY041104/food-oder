<?php
require_once('../config/constants.php');

// Kiểm tra nếu đã có thông tin đăng ký trong session
if (!isset($_SESSION['pending_registration'])) {
    $_SESSION['register'] = "Không tìm thấy thông tin đăng ký. Vui lòng đăng ký lại.";
    header('location:'.SITEURL.'user/register.php');
    exit();
}

$pending_data = $_SESSION['pending_registration'];
$verification_type = 'email'; // Chỉ dùng email
$identifier = $pending_data['email'];

// Xử lý xác minh mã
if (isset($_POST['verify_code'])) {
    $entered_code = trim($_POST['code']);
    
    if (empty($entered_code)) {
        $_SESSION['verify_error'] = "Vui lòng nhập mã xác minh";
    } else {
        // Kiểm tra mã xác minh (chỉ email)
        // Sử dụng UTC để đảm bảo timezone đúng
        $sql = "SELECT * FROM tbl_verification WHERE 
            verification_code = ? AND 
            verification_type = 'email' AND 
            email = ? AND
            is_verified = 0 AND 
            expires_at > UTC_TIMESTAMP() AND
            attempts < 5
            ORDER BY created_at DESC
            LIMIT 1";
        
        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) {
            $_SESSION['verify_error'] = "Lỗi database: " . mysqli_error($conn);
        } else {
            mysqli_stmt_bind_param($stmt, "ss", $entered_code, $identifier);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            // Debug: Log thông tin để kiểm tra
            $debug_log = __DIR__ . '/../logs/verify_debug.log';
            if (!file_exists(dirname($debug_log))) {
                mkdir(dirname($debug_log), 0755, true);
            }
            $row_count = mysqli_num_rows($result);
            $debug_info = date('Y-m-d H:i:s') . " - Code: {$entered_code}, Email: {$identifier}, Found: {$row_count}\n";
            file_put_contents($debug_log, $debug_info, FILE_APPEND);
            
            if ($row_count > 0) {
                $verification = mysqli_fetch_assoc($result);
                mysqli_stmt_close($stmt);
                
                // Tăng số lần thử
                $update_attempts = "UPDATE tbl_verification SET attempts = attempts + 1 WHERE id = ?";
                $stmt2 = mysqli_prepare($conn, $update_attempts);
                mysqli_stmt_bind_param($stmt2, "i", $verification['id']);
                mysqli_stmt_execute($stmt2);
                mysqli_stmt_close($stmt2);
                
                // Đánh dấu đã xác minh
                $mark_verified = "UPDATE tbl_verification SET is_verified = 1 WHERE id = ?";
                $stmt3 = mysqli_prepare($conn, $mark_verified);
                mysqli_stmt_bind_param($stmt3, "i", $verification['id']);
                mysqli_stmt_execute($stmt3);
                mysqli_stmt_close($stmt3);
            
            // Hoàn tất đăng ký
            $full_name = $pending_data['full_name'];
            $email = $pending_data['email'];
            $password = $pending_data['password'];
            $phone = $pending_data['phone'];
            $address = $pending_data['address'];
            
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
                // Xóa thông tin đăng ký tạm
                unset($_SESSION['pending_registration']);
                
                $_SESSION['register-success'] = "Đăng ký thành công! Vui lòng đăng nhập.";
                header('location:'.SITEURL.'user/login.php');
                exit();
            }
            else{
                $_SESSION['verify_error'] = "Đăng ký thất bại! Vui lòng thử lại.";
            }
            } else {
                mysqli_stmt_close($stmt);
                
                // Kiểm tra chi tiết lỗi
                $check_sql = "SELECT * FROM tbl_verification WHERE 
                    email = ? AND 
                    verification_type = 'email'
                    ORDER BY created_at DESC
                    LIMIT 1";
                $check_stmt = mysqli_prepare($conn, $check_sql);
                mysqli_stmt_bind_param($check_stmt, "s", $identifier);
                mysqli_stmt_execute($check_stmt);
                $check_result = mysqli_stmt_get_result($check_stmt);
                
                if (mysqli_num_rows($check_result) > 0) {
                    $check_row = mysqli_fetch_assoc($check_result);
                    $now = time();
                    $expires = strtotime($check_row['expires_at']);
                    
                    if ($check_row['is_verified'] == 1) {
                        $_SESSION['verify_error'] = "Mã xác minh này đã được sử dụng!";
                    } elseif ($check_row['attempts'] >= 5) {
                        $_SESSION['verify_error'] = "Đã vượt quá số lần thử cho phép (5 lần). Vui lòng yêu cầu mã mới.";
                    } elseif ($expires < $now) {
                        $_SESSION['verify_error'] = "Mã xác minh đã hết hạn! Vui lòng yêu cầu mã mới.";
                    } else {
                        $_SESSION['verify_error'] = "Mã xác minh không đúng! Vui lòng kiểm tra lại.";
                    }
                } else {
                    $_SESSION['verify_error'] = "Không tìm thấy mã xác minh cho email này. Vui lòng yêu cầu mã mới.";
                }
                mysqli_stmt_close($check_stmt);
            }
        }
    }
}

// Xử lý gửi lại mã (chỉ email)
if (isset($_POST['resend_code'])) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, SITEURL . 'api/send-verification.php');
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'email' => $pending_data['email'],
        'phone' => '',
        'type' => 'email'
    ]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);
    
    $result = json_decode($response, true);
    if ($result && $result['success']) {
        $_SESSION['verify_success'] = "Mã xác minh mới đã được gửi đến email Gmail của bạn!";
    } else {
        $_SESSION['verify_error'] = $result['message'] ?? "Không thể gửi lại mã xác minh";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Xác minh tài khoản - WowFood</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .verify-container {
            max-width: 500px;
            margin: 100px auto;
            padding: 30px;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        .verify-container h1 {
            text-align: center;
            color: #2f3542;
            margin-bottom: 20px;
        }
        .verify-info {
            text-align: center;
            margin-bottom: 30px;
            padding: 15px;
            background-color: #f1f2f6;
            border-radius: 5px;
        }
        .verify-info strong {
            color: #ff6b81;
        }
        .code-input {
            width: 100%;
            padding: 15px;
            font-size: 24px;
            text-align: center;
            letter-spacing: 10px;
            border: 2px solid #ddd;
            border-radius: 5px;
            margin-bottom: 20px;
            box-sizing: border-box;
        }
        .code-input:focus {
            outline: none;
            border-color: #ff6b81;
        }
        .verify-form input[type="submit"] {
            width: 100%;
            padding: 12px;
            background-color: #ff6b81;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
            cursor: pointer;
            margin-bottom: 10px;
        }
        .verify-form input[type="submit"]:hover {
            background-color: #ff4757;
        }
        .resend-link {
            text-align: center;
            margin-top: 20px;
        }
        .resend-link form {
            display: inline;
        }
        .resend-link button {
            background: none;
            border: none;
            color: #ff6b81;
            cursor: pointer;
            text-decoration: underline;
            font-size: 1rem;
        }
        .resend-link button:hover {
            color: #ff4757;
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
    
    <div class="verify-container">
        <h1>🔐 Xác minh tài khoản</h1>
        
        <div class="verify-info">
            <p>📧 Mã xác minh đã được gửi đến email Gmail:</p>
            <p><strong><?php echo htmlspecialchars($identifier); ?></strong></p>
            <p style="font-size: 0.9em; color: #666; margin-top: 10px;">
                Vui lòng kiểm tra hộp thư đến (và cả thư mục Spam) và nhập mã 6 chữ số để hoàn tất đăng ký
            </p>
        </div>
        
        <?php if(isset($_SESSION['verify_error'])): ?>
            <div class="error"><?php echo htmlspecialchars($_SESSION['verify_error']); unset($_SESSION['verify_error']); ?></div>
        <?php endif; ?>
        
        <?php if(isset($_SESSION['verify_success'])): ?>
            <div class="success"><?php echo htmlspecialchars($_SESSION['verify_success']); unset($_SESSION['verify_success']); ?></div>
        <?php endif; ?>
        
        <form action="" method="POST" class="verify-form">
            <input type="text" 
                   name="code" 
                   class="code-input" 
                   placeholder="000000" 
                   maxlength="6" 
                   pattern="[0-9]{6}" 
                   required 
                   autocomplete="off"
                   autofocus>
            <input type="submit" name="verify_code" value="Xác minh" class="btn-primary">
        </form>
        
        <div class="resend-link">
            <p>Không nhận được mã? 
                <form method="POST" style="display: inline;">
                    <button type="submit" name="resend_code">Gửi lại mã</button>
                </form>
            </p>
        </div>
    </div>
    
    <?php include('../partials-front/footer.php'); ?>
    
    <script>
        // Auto-focus và format input
        document.querySelector('.code-input').addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '');
        });
    </script>
</body>
</html>

