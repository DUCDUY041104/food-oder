# Danh sách các file liên quan đến chức năng gửi mail xác minh

## 📧 Core Email Sending Files (File gửi email chính)

### 1. `api/phpmailer-send.php`
- **Chức năng**: File chính chứa function `sendEmailWithPHPMailer()` để gửi email
- **Nhiệm vụ**: 
  - Load PHPMailer library
  - Cấu hình SMTP (Gmail)
  - Gửi email với HTML format
  - Log lỗi vào `logs/email_errors.log`

### 2. `config/email-config.php`
- **Chức năng**: Cấu hình SMTP email
- **Thông tin**:
  - SMTP Host: smtp.gmail.com
  - Port: 587
  - Username/Password: Gmail credentials
  - From Email/Name: Thông tin người gửi

### 3. PHPMailer Library Files
- `src/PHPMailer.php` - Class PHPMailer chính
- `src/SMTP.php` - Class xử lý SMTP
- `src/Exception.php` - Exception handling

---

## 🔌 API Endpoints

### 4. `api/send-verification.php`
- **Chức năng**: API endpoint để gửi mã xác minh
- **Method**: POST
- **Input**: email
- **Output**: JSON response
- **Nhiệm vụ**:
  - Validate email (chỉ Gmail)
  - Generate mã 6 số
  - Lưu vào database `tbl_verification`
  - Gọi function `sendEmailVerification()` để gửi email
  - Log vào `logs/email_send.log`

---

## 👤 User Pages (Trang người dùng)

### 5. `user/register.php`
- **Chức năng**: Trang đăng ký tài khoản
- **Nhiệm vụ**:
  - Validate thông tin đăng ký
  - Tạo mã xác minh 6 số
  - Lưu thông tin vào session `pending_registration`
  - Gọi trực tiếp `sendEmailWithPHPMailer()` để gửi email
  - Redirect đến `verify-code.php`
  - Log mã xác minh vào `logs/verification_codes.log` (localhost only)

### 6. `user/verify-code.php`
- **Chức năng**: Trang xác minh mã
- **Nhiệm vụ**:
  - Hiển thị form nhập mã 6 số
  - Verify mã với database
  - Kiểm tra: mã hợp lệ, chưa hết hạn, chưa vượt quá 5 lần thử
  - Hoàn tất đăng ký nếu mã đúng
  - Cho phép gửi lại mã (gọi API `send-verification.php`)
  - Log debug vào `logs/verify_debug.log`

### 7. `user/forgot-password.php`
- **Chức năng**: Trang quên mật khẩu
- **Nhiệm vụ**:
  - Validate email (chỉ Gmail)
  - Kiểm tra email có tồn tại trong database
  - Tạo mã reset password 6 số
  - Lưu vào `tbl_verification`
  - Gửi email bằng `sendEmailWithPHPMailer()`
  - Lưu email vào session `reset_password_email`
  - Redirect đến `reset-password.php`
  - Log mã vào `logs/verification_codes.log` (localhost only)

### 8. `user/reset-password.php`
- **Chức năng**: Trang đặt lại mật khẩu
- **Nhiệm vụ**:
  - Verify mã xác minh từ email
  - Kiểm tra mã hợp lệ, chưa hết hạn
  - Update mật khẩu mới (hashed)
  - Đánh dấu mã đã sử dụng (`is_verified = 1`)
  - Tăng số lần thử nếu mã sai

---

## 🗄️ Database

### 9. `sql/food-oder.sql`
- **Bảng**: `tbl_verification`
- **Cấu trúc**:
  - `id` - Primary key
  - `email` - Email người dùng
  - `phone` - Số điện thoại (NULL, không dùng)
  - `verification_code` - Mã 6 số
  - `verification_type` - Loại xác minh ('email' hoặc 'phone', chỉ dùng 'email')
  - `expires_at` - Thời gian hết hạn (10 phút)
  - `is_verified` - Đã xác minh chưa (0/1)
  - `attempts` - Số lần thử (tối đa 5)
  - `created_at` - Thời gian tạo

---

## 📝 Log Files (File log)

### 10. `logs/verification_codes.log`
- **Nội dung**: Log mã xác minh được tạo
- **Format**: `YYYY-MM-DD HH:MM:SS - Email: {email}, Code: {code}`
- **Lưu ý**: Chỉ log trên localhost (để test)

### 11. `logs/email_errors.log`
- **Nội dung**: Log lỗi khi gửi email
- **Format**: `YYYY-MM-DD HH:MM:SS - Error message`
- **Bao gồm**: 
  - PHPMailer errors
  - SMTP debug messages
  - General errors

### 12. `logs/email_send.log`
- **Nội dung**: Log kết quả gửi email (thành công/thất bại)
- **Format**: `YYYY-MM-DD HH:MM:SS - Email to {email}: SUCCESS/FAILED - Code: {code}`

### 13. `logs/verify_debug.log`
- **Nội dung**: Log debug khi verify mã
- **Format**: `YYYY-MM-DD HH:MM:SS - Code: {code}, Email: {email}, Found: {count}`

---

## ⚙️ Configuration Files

### 14. `config/constants.php`
- **Chức năng**: Cấu hình chung
- **Bao gồm**:
  - Database connection
  - Site URL (`SITEURL`)
  - Session start

---

## 📊 Flow hoạt động

### Flow đăng ký:
1. User điền form → `user/register.php`
2. Tạo mã 6 số → Lưu vào `tbl_verification`
3. Gửi email → `api/phpmailer-send.php` → `sendEmailWithPHPMailer()`
4. Redirect → `user/verify-code.php`
5. User nhập mã → Verify với database
6. Nếu đúng → Hoàn tất đăng ký → Insert vào `tbl_user`

### Flow quên mật khẩu:
1. User nhập email → `user/forgot-password.php`
2. Tạo mã reset → Lưu vào `tbl_verification`
3. Gửi email → `api/phpmailer-send.php`
4. Redirect → `user/reset-password.php`
5. User nhập mã + mật khẩu mới → Verify mã
6. Nếu đúng → Update password trong `tbl_user`

### Flow gửi lại mã:
1. User click "Gửi lại mã" → `user/verify-code.php` (POST resend_code)
2. Gọi API → `api/send-verification.php`
3. Tạo mã mới → Xóa mã cũ → Gửi email

---

## 🔑 Key Functions

### `sendEmailWithPHPMailer($to, $subject, $body)`
- **Location**: `api/phpmailer-send.php`
- **Chức năng**: Gửi email sử dụng PHPMailer
- **Return**: `true` nếu thành công, `false` nếu thất bại

### `sendEmailVerification($email, $code)`
- **Location**: `api/send-verification.php`
- **Chức năng**: Tạo email template và gửi mã xác minh
- **Return**: `true` nếu thành công, `false` nếu thất bại

---

## 📌 Lưu ý quan trọng

1. **Chỉ chấp nhận Gmail**: Tất cả các trang đều validate chỉ chấp nhận email @gmail.com
2. **Mã 6 số**: Mã xác minh là 6 chữ số (000000-999999)
3. **Thời gian hết hạn**: 10 phút (600 giây)
4. **Số lần thử**: Tối đa 5 lần
5. **Logging**: Mã xác minh chỉ được log trên localhost (để test)
6. **PHPMailer**: Sử dụng Gmail SMTP với App Password
7. **Fallback**: Nếu PHPMailer fail, sẽ dùng hàm `mail()` của PHP

---

## 📁 Cấu trúc thư mục

```
Food_order/
├── api/
│   ├── phpmailer-send.php          # Core email function
│   └── send-verification.php       # API endpoint
├── config/
│   ├── constants.php                # Database & site config
│   └── email-config.php            # Email SMTP config
├── src/
│   ├── PHPMailer.php               # PHPMailer library
│   ├── SMTP.php                    # SMTP library
│   └── Exception.php               # Exception library
├── user/
│   ├── register.php                # Đăng ký
│   ├── verify-code.php             # Xác minh mã
│   ├── forgot-password.php         # Quên mật khẩu
│   └── reset-password.php          # Đặt lại mật khẩu
├── logs/
│   ├── verification_codes.log     # Log mã xác minh
│   ├── email_errors.log            # Log lỗi email
│   ├── email_send.log              # Log kết quả gửi
│   └── verify_debug.log            # Log debug verify
└── sql/
    └── food-oder.sql               # Database schema
```

---

*Tài liệu này được tạo tự động để liệt kê tất cả các file liên quan đến chức năng gửi mail xác minh.*

