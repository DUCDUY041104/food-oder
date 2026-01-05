<?php
require_once('../config/constants.php');

// Tạo thư mục logs nếu chưa có
if (!file_exists('../logs')) {
    mkdir('../logs', 0755, true);
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$type = 'email'; // Chỉ dùng email

// Validate email
if (empty($email)) {
    echo json_encode(['success' => false, 'message' => 'Email không được để trống']);
    exit();
}

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Email không hợp lệ']);
    exit();
}

// Chỉ chấp nhận Gmail
$email_domain = substr(strrchr($email, "@"), 1);
if(strtolower($email_domain) !== 'gmail.com'){
    echo json_encode(['success' => false, 'message' => 'Chỉ chấp nhận đăng ký bằng Gmail!']);
    exit();
}

// Generate 6-digit verification code
$verification_code = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);

// Set expiration time (10 minutes) - Sử dụng UTC để tránh lỗi timezone
$expires_at = date('Y-m-d H:i:s', time() + 600);

// Delete old unverified codes for this email
$delete_sql = "DELETE FROM tbl_verification WHERE 
    email = ? AND 
    is_verified = 0 AND 
    expires_at < UTC_TIMESTAMP()";
$stmt = mysqli_prepare($conn, $delete_sql);
mysqli_stmt_bind_param($stmt, "s", $email);
mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

// Insert new verification code
$insert_sql = "INSERT INTO tbl_verification SET
    email = ?,
    phone = NULL,
    verification_code = ?,
    verification_type = 'email',
    expires_at = ?,
    is_verified = 0,
    attempts = 0";
$stmt = mysqli_prepare($conn, $insert_sql);
mysqli_stmt_bind_param($stmt, "sss", $email, $verification_code, $expires_at);
$result = mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

if (!$result) {
    echo json_encode(['success' => false, 'message' => 'Lỗi khi lưu mã xác minh']);
    exit();
}

// Send verification code via email
$sent = sendEmailVerification($email, $verification_code);

if ($sent) {
    echo json_encode([
        'success' => true, 
        'message' => 'Mã xác minh đã được gửi đến email Gmail của bạn! Vui lòng kiểm tra hộp thư đến.',
        'type' => 'email'
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Không thể gửi mã xác minh. Vui lòng kiểm tra cấu hình email hoặc thử lại sau.']);
}

// Function to send email verification
function sendEmailVerification($email, $code) {
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
            <div class='code'>{$code}</div>
            <p><strong>Mã này có hiệu lực trong 10 phút.</strong></p>
            <p>Nếu bạn không yêu cầu mã này, vui lòng bỏ qua email này.</p>
            <div class='footer'>
                <p>Trân trọng,<br>Đội ngũ WowFood</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    // Thử dùng PHPMailer nếu có
    if (file_exists(__DIR__ . '/phpmailer-send.php')) {
        require_once(__DIR__ . '/phpmailer-send.php');
        if (function_exists('sendEmailWithPHPMailer')) {
            $result = sendEmailWithPHPMailer($email, $subject, $message);
            if ($result) {
                // Log thành công
                $log_file = __DIR__ . '/../logs/email_send.log';
                $log_message = date('Y-m-d H:i:s') . " - Email to {$email}: SUCCESS (PHPMailer) - Code: {$code}\n";
                file_put_contents($log_file, $log_message, FILE_APPEND);
                return true;
            } else {
                // Log lỗi PHPMailer
                $error_log = __DIR__ . '/../logs/email_errors.log';
                $error_message = date('Y-m-d H:i:s') . " - PHPMailer failed for {$email}, trying fallback mail()\n";
                file_put_contents($error_log, $error_message, FILE_APPEND);
            }
            // Nếu PHPMailer fail, tiếp tục dùng mail()
        } else {
            $error_log = __DIR__ . '/../logs/email_errors.log';
            $error_message = date('Y-m-d H:i:s') . " - sendEmailWithPHPMailer function not found\n";
            file_put_contents($error_log, $error_message, FILE_APPEND);
        }
    } else {
        $error_log = __DIR__ . '/../logs/email_errors.log';
        $error_message = date('Y-m-d H:i:s') . " - phpmailer-send.php file not found\n";
        file_put_contents($error_log, $error_message, FILE_APPEND);
    }
    
    // Fallback: Sử dụng hàm mail() của PHP
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: WowFood <noreply@wowfood.com>" . "\r\n";
    $headers .= "Reply-To: noreply@wowfood.com" . "\r\n";
    
    $result = @mail($email, $subject, $message, $headers);
    
    // Log kết quả
    $log_file = __DIR__ . '/../logs/email_send.log';
    $log_message = date('Y-m-d H:i:s') . " - Email to {$email}: " . ($result ? 'SUCCESS' : 'FAILED') . " - Code: {$code}\n";
    file_put_contents($log_file, $log_message, FILE_APPEND);
    
    if (!$result) {
        $error = error_get_last();
        $error_log = __DIR__ . '/../logs/email_errors.log';
        $error_message = date('Y-m-d H:i:s') . " - Failed to send email to {$email}. Error: " . ($error ? $error['message'] : 'Unknown error') . "\n";
        file_put_contents($error_log, $error_message, FILE_APPEND);
    }
    
    return $result;
}

// SMS verification đã bị loại bỏ - chỉ dùng email

