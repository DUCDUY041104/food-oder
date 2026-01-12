# Mô tả chức năng chi tiết — Dự án Food_order

## 1. Giới thiệu tổng quan
- Tên dự án: Food_order
- Mục tiêu: Xây dựng hệ thống đặt đồ ăn trực tuyến đơn giản, có giao diện người dùng thân thiện, API cho thao tác giỏ hàng và nhắn tin, cùng trang quản trị để quản lý danh mục, món ăn, đơn hàng và cuộc hội thoại.
- Phạm vi báo cáo: mô tả chức năng, luồng dữ liệu, API chính, cấu trúc cơ sở dữ liệu, xác thực, bảo mật cơ bản và hướng dẫn chạy môi trường local để minh họa.

> Lưu ý: nội dung trình bày sau là mô tả chức năng nguyên bản, viết lại và mở rộng để phù hợp cho báo cáo đồ án, tránh sao chép trực tiếp từ nguồn khác.

---

## 2. Kiến trúc và công nghệ chính
- Ngôn ngữ server: PHP
- Cơ sở dữ liệu: MySQL
- Thư viện gửi mail: PHPMailer (đặt trong `src/`)
- Frontend: HTML/CSS, JavaScript (AJAX cho gọi API)
- Cấu trúc thư mục chính: file gốc (frontend), `admin/` (quản trị), `api/` (endpoints), `user/` (trang người dùng), `config/`, `src/`, `css/`, `js/`, `image/`, `sql/`.

---

## 3. Tổng quan các chức năng người dùng (User-facing)

3.1. Duyệt và tìm món
- `index.php`: Trang chủ hiển thị các danh mục nổi bật và các món ăn tiêu biểu. Cho phép dẫn link tới `category-food.php` hoặc `food.php`.
- `categories.php`: Liệt kê toàn bộ danh mục kèm ảnh minh họa.
- `category-food.php`: Hiển thị danh sách món theo `category_id` kèm phân trang nếu cần.
- `food-search.php` và `search-suggestions.php`: Tìm kiếm tên món (có AJAX gợi ý khi nhập từ khóa).

3.2. Xem chi tiết và đặt món
- `food.php`: Hiển thị thông tin chi tiết món (tên, mô tả, thành phần, giá, ảnh), cho phép chọn số lượng và thêm vào giỏ hàng.
- Thêm vào giỏ có thể thực hiện qua AJAX gọi `api/add-to-cart.php`.

3.3. Giỏ hàng và thanh toán
- `user/cart.php`: Hiện danh sách món trong giỏ, cho phép sửa số lượng, xóa mục.
- `user/checkout.php`: Form nhập địa chỉ giao hàng, phương thức thanh toán và tóm tắt đơn.
- `user/payment.php`: Xử lý trả về trạng thái thanh toán (trong đồ án có thể mô phỏng thành công/thất bại).
- `order.php` (file gốc): Tạo bản ghi đơn hàng nếu dự án thiết kế như vậy.

3.4. Quản lý tài khoản
- `user/register.php`, `user/login.php`, `user/logout.php`: Đăng ký/đăng nhập/đăng xuất người dùng.
- `user/forgot-password.php`, `user/reset-password.php`, `api/send-verification.php`, `user/verify-code.php`: Luồng khôi phục mật khẩu bằng mã xác minh gửi email.

3.5. Tin nhắn / Chat (user ↔ admin)
- `user/chat.php`: Giao diện chat cho user.
- API hỗ trợ: `api/send-message.php`, `api/get-messages.php`, `api/get-chat-list.php`, `api/get-unread-count.php`, `api/mark-messages-read.php`.

---

## 4. Chức năng quản trị (Admin)

- `admin/login.php`, `admin/logout.php`: Xác thực admin (session-based). Kiểm tra quyền truy cập bằng `admin/partials/login-check.php`.
- `admin/index.php`: Bảng điều khiển hiển thị thông tin tổng quan: đơn mới, doanh thu, số tin nhắn chưa đọc.
- Quản lý admin: `admin/manage-admin.php`, `admin/add-admin.php`, `admin/update-admin.php`, `admin/delete-admin.php`.
- Quản lý danh mục: `admin/manage-category.php`, `admin/add-category.php`, `admin/update-category.php`, `admin/delete-category.php`.
- Quản lý món ăn: `admin/manage-food.php`, `admin/add-food.php`, `admin/update-food.php`, `admin/delete-food.php`.
- Quản lý đơn hàng: `admin/manage-order.php`, `admin/update-order.php`.
- Quản lý chat: `admin/manage-chat.php` để xem và trả lời khách.

---

## 5. API chi tiết (Endpoints chính, method, params, response mẫu)

Lưu ý: tất cả API trả JSON; các endpoint có thể yêu cầu xác thực session nếu thao tác cần quyền.

1) `api/add-to-cart.php` (POST)
- Mô tả: Thêm món vào giỏ hàng (session hoặc DB nếu user đã đăng nhập).
- Tham số (POST): `food_id` (int), `qty` (int), `user_id` (optional nếu quản lý DB cho user)
- Response (200):
```
{
  "success": true,
  "message": "Added to cart",
  "cart_count": 3,
  "cart_total": 450000
}
```

2) `api/get-cart.php` (GET)
- Mô tả: Lấy chi tiết các mục trong giỏ hàng cho user/session.
- Params: `user_id` (optional)
- Response: danh sách mục, subtotal và tổng.

3) `api/update-cart-item.php` (POST)
- Tham số: `cart_item_id` hoặc `food_id`, `qty`
- Response: success, updated totals.

4) `api/remove-cart-item.php` (POST)
- Tham số: `cart_item_id` hoặc `food_id`
- Response: success, cart_count, cart_total.

5) `api/send-verification.php` (POST)
- Mô tả: Gửi mã OTP/verification qua email (sử dụng `src/PHPMailer.php`).
- Tham số: `email`, `purpose` (e.g., "reset_password"), `user_id` (optional)
- Response: success/fail.

6) Chat endpoints
- `api/send-message.php` (POST): `sender_id`, `receiver_id`, `content` → trả về message_id, timestamp.
- `api/get-messages.php` (GET): `conversation_id` hoặc `user_id`/`other_id` → trả về mảng tin nhắn.

Ghi chú: Trong báo cáo, bạn có thể mở rộng mỗi endpoint thành mô tả đầy đủ gồm ví dụ Request (curl), ví dụ Response và mã lỗi phổ biến.

---

## 6. Cấu trúc cơ sở dữ liệu đề xuất (bảng và field chính)

Lưu ý: đây là mô tả schema tham khảo; tên bảng/field có thể khác trong mã nguồn thực tế.

- `users`
  - `id` INT PK
  - `email` VARCHAR
  - `password` VARCHAR (hash)
  - `name` VARCHAR
  - `phone` VARCHAR
  - `created_at` DATETIME

- `admins`
  - `id`, `username`, `password`, `role`, `created_at`

- `categories`
  - `id`, `title`, `image_name`, `active` (flag)

- `foods`
  - `id`, `title`, `description`, `price` (DECIMAL), `image_name`, `category_id` (FK), `active`, `created_at`

- `carts` (nếu lưu DB cho người đã đăng nhập)
  - `id`, `user_id`, `food_id`, `qty`, `created_at`

- `orders`
  - `id`, `user_id`, `total_amount`, `status` (e.g., pending, confirmed, delivered), `address`, `phone`, `created_at`

- `order_items`
  - `id`, `order_id`, `food_id`, `qty`, `price`

- `messages`
  - `id`, `sender_id`, `receiver_id`, `content`, `is_read` (boolean), `created_at`

Trong báo cáo, bạn có thể thêm sơ đồ ER đơn giản minh họa quan hệ giữa `users`↔`orders`↔`order_items` và `foods`↔`categories`.

---

## 7. Luồng nghiệp vụ chi tiết (ví dụ mở rộng)

7.1. Luồng "Thêm món vào giỏ" (chi tiết)
1. Người dùng click "Add to cart" trên `food.php`.
2. Frontend gửi AJAX POST tới `api/add-to-cart.php` với `food_id` và `qty`.
3. Backend kiểm tra: món tồn tại, còn hàng (nếu có quản lý tồn kho), xác thực session.
4. Nếu user đã login: lưu/ghép vào bảng `carts` trong DB; nếu chưa: lưu vào session (mảng PHP).
5. Trả về JSON chứa `success`, `cart_count`, `cart_total`.
6. Frontend cập nhật badge giỏ hàng và hiển thị thông báo thành công.

7.2. Luồng "Thanh toán" (checkout)
1. User mở `checkout.php` và submit form gồm thông tin giao hàng.
2. Server kiểm tra hợp lệ dữ liệu, tạo bản ghi `orders`, duyệt các mục trong giỏ và tạo `order_items` tương ứng.
3. (Tùy implement) Giảm tồn kho, gửi email xác nhận bằng `api/phpmailer-send.php`.
4. Xóa giỏ hàng (session hoặc DB) sau khi tạo đơn thành công.

---

## 8. An ninh, xác thực và các kiểm soát cần thiết
- Hash mật khẩu (bcrypt/ password_hash). Không lưu mật khẩu thuần.
- Sử dụng prepared statements / parameterized queries để tránh SQL injection.
- Kiểm tra session/role cho trang `admin/*` và các API nhạy cảm.
- Giới hạn kích thước upload ảnh và kiểm tra loại file khi admin upload ảnh món.
- Lưu log các hành động quản trị quan trọng (xóa món, thay đổi đơn hàng).

---

## 9. Kiểm thử và kiểm chứng
- Kiểm thử chức năng: thêm/xóa/sửa giỏ hàng, tạo đơn, gửi/nhận tin nhắn.
- Kiểm thử giao diện: responsive trên mobile và desktop.
- Kiểm thử bảo mật: thử SQL injection cơ bản, kiểm tra upload file, xác thực session.
- Kiểm thử hiệu năng: kiểm thử tải đơn giản với một vài người dùng đồng thời (đo thời gian phản hồi API quan trọng).

---

## 10. Hướng dẫn chạy nhanh (môi trường local)
1. Cài XAMPP (Apache + MySQL), đặt project vào `htdocs/Food_order`.
2. Tạo database và import `sql/food-oder.sql`.
3. Chỉnh `config/constants.php` với thông tin DB và `config/email-config.php` với cấu hình SMTP nếu cần gửi mail.
4. Mở trình duyệt: `http://localhost/Food_order/` để kiểm tra.

---

## 11. Phần trình bày báo cáo (gợi ý cấu trúc phần viết đồ án)
1. Mở đầu: mục tiêu, phạm vi, công nghệ.
2. Mô tả chức năng theo module (frontend, user, admin, API, DB).
3. Luồng nghiệp vụ chi tiết (ví dụ add-to-cart, checkout, chat).
4. Thiết kế cơ sở dữ liệu: bảng, quan hệ.
5. Bảo mật và kiểm thử.
6. Hướng dẫn triển khai và kết luận.

---

Nếu bạn muốn, tôi sẽ tiếp tục mở rộng thành phần: *bảng API chi tiết (mỗi endpoint có ví dụ curl và response mẫu)*, hoặc *mô tả từng file PHP quan trọng (hàm chính, input/output)* để bạn đưa trực tiếp vào báo cáo tránh đạo văn.
