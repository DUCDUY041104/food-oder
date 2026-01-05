<?php
/**
 * Gửi email sử dụng PHPMailer
 * Sử dụng các file PHPMailer trong thư mục src/
 */

require_once(__DIR__ . '/../config/constants.php');
require_once(__DIR__ . '/../config/email-config.php');

// Include PHPMailer files từ thư mục src/
$phpmailer_path = __DIR__ . '/../src/PHPMailer.php';
$smtp_path = __DIR__ . '/../src/SMTP.php';
$exception_path = __DIR__ . '/../src/Exception.php';

if (file_exists($phpmailer_path) && file_exists($smtp_path) && file_exists($exception_path)) {
    require_once($exception_path);
    require_once($smtp_path);
    require_once($phpmailer_path);
} else {
    // Nếu không tìm thấy file, log lỗi
    $error_log = __DIR__ . '/../logs/email_errors.log';
    if (!file_exists(dirname($error_log))) {
        mkdir(dirname($error_log), 0755, true);
    }
    $error_message = date('Y-m-d H:i:s') . " - PHPMailer files not found. Paths checked:\n";
    $error_message .= "  - PHPMailer.php: " . ($phpmailer_path . " - " . (file_exists($phpmailer_path) ? "EXISTS" : "NOT FOUND")) . "\n";
    $error_message .= "  - SMTP.php: " . ($smtp_path . " - " . (file_exists($smtp_path) ? "EXISTS" : "NOT FOUND")) . "\n";
    $error_message .= "  - Exception.php: " . ($exception_path . " - " . (file_exists($exception_path) ? "EXISTS" : "NOT FOUND")) . "\n";
    file_put_contents($error_log, $error_message, FILE_APPEND);
    return false;
}

function sendEmailWithPHPMailer($to, $subject, $body) {
    try {
        // Kiểm tra xem class PHPMailer có tồn tại không (có thể là PHPMailer\PHPMailer\PHPMailer hoặc PHPMailer)
        if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            $exceptionClass = 'PHPMailer\PHPMailer\Exception';
            $encryption = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        } elseif (class_exists('PHPMailer')) {
            $mail = new PHPMailer(true);
            $exceptionClass = 'phpmailerException';
            $encryption = PHPMailer::ENCRYPTION_STARTTLS;
        } else {
            error_log("PHPMailer class not found");
            return false;
        }
        
        // Server settings
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USERNAME;
        $mail->Password   = SMTP_PASSWORD;
        $mail->SMTPSecure = $encryption;
        $mail->Port       = SMTP_PORT;
        $mail->CharSet    = 'UTF-8';
        $mail->SMTPDebug  = 0; // Tắt debug (đặt = 2 để bật debug khi cần)
        // Chuyển debug output vào file thay vì output ra browser
        $mail->Debugoutput = function($str, $level) {
            $error_log = __DIR__ . '/../logs/email_errors.log';
            if (!file_exists(dirname($error_log))) {
                mkdir(dirname($error_log), 0755, true);
            }
            file_put_contents($error_log, date('Y-m-d H:i:s') . " - SMTP Debug: " . trim($str) . "\n", FILE_APPEND);
        };
        
        // Recipients
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($to);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        $mail->AltBody = strip_tags($body);
        
        $mail->send();
        return true;
    } catch (\PHPMailer\PHPMailer\Exception $e) {
        $error_log = __DIR__ . '/../logs/email_errors.log';
        if (!file_exists(dirname($error_log))) {
            mkdir(dirname($error_log), 0755, true);
        }
        $error_message = date('Y-m-d H:i:s') . " - PHPMailer Error: " . $mail->ErrorInfo . " | Exception: " . $e->getMessage() . "\n";
        file_put_contents($error_log, $error_message, FILE_APPEND);
        error_log("PHPMailer Error: " . $mail->ErrorInfo);
        return false;
    } catch (\Exception $e) {
        $error_log = __DIR__ . '/../logs/email_errors.log';
        if (!file_exists(dirname($error_log))) {
            mkdir(dirname($error_log), 0755, true);
        }
        $error_message = date('Y-m-d H:i:s') . " - General Error: " . $e->getMessage() . " | File: " . $e->getFile() . " | Line: " . $e->getLine() . "\n";
        file_put_contents($error_log, $error_message, FILE_APPEND);
        error_log("General Error: " . $e->getMessage());
        return false;
    }
}

?>

