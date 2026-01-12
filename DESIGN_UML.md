**Mục tiêu**
- **Mô tả**: Tài liệu này phân tích thiết kế hướng đối tượng (UML) cho dự án Food_order dựa vào cấu trúc mã nguồn và sơ đồ CSDL.

**Thực thể chính (từ CSDL)**
- **User**: `tbl_user` — id, full_name, username, password, email, phone, address, status, created_at
- **Admin**: `tbl_admin` — id, full_name, email, username, password
- **Category**: `tbl_category` — id, title, featured, active, image_name
- **Food**: `tbl_food` — id, title, description, price, image_name, category_id, featured, active
- **Order**: `tbl_order` — id, order_code, user_id, food, price, qty, total, order_date, status, customer_*
- **ChatMessage**: `tbl_chat` — id, user_id, admin_id, sender_type, message, is_read, created_at

**Tóm tắt lớp đề xuất (class-level design)**
Sản phẩm hiện tại sử dụng PHP kiểu thủ tục; đề xuất sau tách mã thành lớp/khối có trách nhiệm rõ ràng.

classDiagram
    class User {
      +int id
      +string fullName
      +string username
      +string passwordHash
      +string email
      +string phone
      +string address
      +string status
      +DateTime createdAt
      +login()
      +register()
      +updateProfile()
    }

    class Admin {
      +int id
      +string fullName
      +string email
      +string username
      +string passwordHash
      +login()
      +manageData()
    }

    class Category {
      +int id
      +string title
      +bool featured
      +bool active
      +string imageName
      +loadAll()
      +save()
    }

    class Food {
      +int id
      +string title
      +string description
      +decimal price
      +string imageName
      +int categoryId
      +bool featured
      +bool active
      +loadByCategory()
      +save()
    }

    class Order {
      +int id
      +string orderCode
      +int userId
      +array items
      +decimal total
      +DateTime orderDate
      +string status
      +create()
      +cancel()
      +updateStatus()
    }

    class ChatMessage {
      +int id
      +int userId
      +int adminId
      +string senderType
      +string message
      +bool isRead
      +DateTime createdAt
      +send()
      +markRead()
    }

    class CartService {
      +addItem(userId, foodId, qty)
      +removeItem(userId, foodId)
      +updateItem(userId, foodId, qty)
      +getCart(userId)
    }

    class OrderService {
      +placeOrder(userId, cart)
      +generateOrderCode()
      +getOrderHistory(userId)
    }

    %% Relationships
    User "1" -- "*" Order : places
    User "1" -- "*" ChatMessage : sends
    Admin "1" -- "*" ChatMessage : replies
    Category "1" -- "*" Food : contains
    Order "1" -- "*" Food : includes
    User ..> CartService : uses
    User ..> OrderService : uses

**Sequence Diagram: đặt hàng (đơn giản)**
sequenceDiagram
    participant Customer as User
    participant Cart as CartService
    participant OrderS as OrderService
    participant DB as Database

    Customer->>Cart: addItem(foodId, qty)
    Cart-->>DB: persist cart item
    Customer->>OrderS: placeOrder(userId)
    OrderS->>Cart: getCart(userId)
    OrderS->>DB: insert tbl_order and related data
    OrderS-->>Customer: return order confirmation (orderCode)

**Sequence Diagram: chat tin nhắn**
sequenceDiagram
    participant U as User
    participant A as Admin
    participant Chat as ChatMessageService
    participant DB as Database

    U->>Chat: sendMessage(text)
    Chat->>DB: insert tbl_chat (sender_type=user)
    Chat-->>A: notify admin (websocket/polling)
    A->>Chat: sendMessage(reply)
    Chat->>DB: insert tbl_chat (sender_type=admin)
    Chat-->>U: deliver reply

**Quan hệ trách nhiệm (Responsibilities)**
- **User / Admin**: chịu trách nhiệm xác thực, quản lý hồ sơ.
- **Category / Food**: mô hình dữ liệu, tải danh sách, truy vấn theo bộ lọc.
- **CartService**: quản lý giỏ hàng (thực thể tạm thời hoặc lưu DB/session).
- **OrderService**: xử lý đặt hàng, tạo mã đơn, lưu vào `tbl_order`.
- **ChatMessageService**: gửi/nhận ghi chat vào `tbl_chat`, đánh dấu đọc.

**Gợi ý refactor cấp độ engineering**
- **Repository layer**: tạo `UserRepository`, `FoodRepository`, `OrderRepository`, `ChatRepository` để tách truy cập DB.
- **Service layer**: `AuthService`, `CartService`, `OrderService`, `ChatService` cho logic nghiệp vụ.
- **Controller / Router**: map HTTP endpoints (`api/*`, `user/*`, `admin/*`) tới các service.
- **DTOs / Value Objects**: dùng để truyền dữ liệu giữa layer (OrderItem, CartItem).
- **Error handling & Validation**: gói chung vào `Exceptions` và `Validators`.

**Tệp tham chiếu**
- **Cấu trúc DB**: sql/food-oder.sql
- **PHPMailer classes**: src/PHPMailer.php, src/SMTP.php, src/Exception.php
- **Các file nghiệp vụ hiện tại**: admin/*.php, api/*.php, user/*.php (hiện dạng thủ tục)

**Kết luận ngắn**
- **Mức độ OO hiện tại**: thấp (chủ yếu thủ tục). Tài liệu này trình bày mô hình hướng đối tượng thích hợp để refactor, tách rõ Repository/Service/Controller và dùng các lớp domain cho `User`, `Admin`, `Category`, `Food`, `Order`, `ChatMessage`.

---
File được tạo tự động: [DESIGN_UML.md](DESIGN_UML.md)
