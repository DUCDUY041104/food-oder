<?php
include('../config/constants.php');

// Kiểm tra đăng nhập
if(!isset($_SESSION['user_id'])) {
    $_SESSION['no-login-message'] = "Vui lòng đăng nhập để xem giỏ hàng!";
    header('location:'.SITEURL.'user/login.php');
    exit();
}

include('../partials-front/menu.php');
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giỏ hàng - WowFood</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .cart-container {
            max-width: 800px;
            margin: 100px auto 50px;
            padding: 20px;
        }
        .cart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #ff6b81;
        }
        .cart-header h1 {
            color: #2f3542;
            margin: 0;
        }
        .cart-badge {
            background: #ff6b81;
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.9em;
        }
        .cart-item {
            background: white;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            display: flex;
            gap: 15px;
        }
        .cart-item-image {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 8px;
        }
        .cart-item-info {
            flex: 1;
        }
        .cart-item-name {
            font-size: 1.1em;
            font-weight: bold;
            color: #2f3542;
            margin-bottom: 5px;
        }
        .cart-item-price {
            color: #ff6b81;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .cart-item-controls {
            display: flex;
            gap: 10px;
            align-items: center;
            margin-bottom: 10px;
        }
        .quantity-btn {
            width: 30px;
            height: 30px;
            border: 1px solid #ddd;
            background: white;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1.2em;
        }
        .quantity-input {
            width: 50px;
            text-align: center;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 5px;
        }
        .note-input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 0.9em;
            margin-top: 5px;
        }
        .note-input::placeholder {
            color: #999;
        }
        .remove-btn {
            background: #ff4757;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9em;
        }
        .cart-summary {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            position: sticky;
            bottom: 0;
            margin-top: 20px;
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        .summary-total {
            font-size: 1.3em;
            font-weight: bold;
            color: #ff6b81;
            border-top: 2px solid #eee;
            padding-top: 10px;
        }
        .checkout-btn {
            width: 100%;
            padding: 15px;
            background: #ff6b81;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1.1em;
            font-weight: bold;
            cursor: pointer;
            margin-top: 15px;
        }
        .checkout-btn:hover {
            background: #ff4757;
        }
        .checkout-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
        .empty-cart {
            text-align: center;
            padding: 50px 20px;
        }
        .empty-cart-icon {
            font-size: 5em;
            margin-bottom: 20px;
        }
        .empty-cart h2 {
            color: #666;
            margin-bottom: 10px;
        }
        .empty-cart a {
            color: #ff6b81;
            text-decoration: none;
            font-weight: bold;
        }
        @media (max-width: 768px) {
            .cart-container {
                margin: 80px auto 20px;
                padding: 10px;
            }
            .cart-item {
                flex-direction: column;
            }
            .cart-item-image {
                width: 100%;
                height: 200px;
            }
        }
    </style>
</head>
<body>
    <div class="cart-container">
        <div class="cart-header">
            <h1> Giỏ hàng</h1>
            <span class="cart-badge" id="cartCount">0 món</span>
        </div>

        <div id="cartItems">
            <!-- Cart items will be loaded here -->
        </div>

        <div class="cart-summary" id="cartSummary" style="display: none;">
            <div class="summary-row">
                <span>Tổng cộng:</span>
                <span id="cartTotal">0 đ</span>
            </div>
            <button class="checkout-btn" onclick="goToCheckout()">Thanh toán</button>
        </div>
    </div>

    <?php include('../partials-front/footer.php'); ?>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        let cartItems = [];

        // Load giỏ hàng
        function loadCart() {
            fetch('../api/get-cart.php')
                .then(response => response.json())
                .then(data => {
                    if(data.success) {
                        cartItems = data.items;
                        renderCart();
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                });
        }

        // Render giỏ hàng
        function renderCart() {
            const container = document.getElementById('cartItems');
            const summary = document.getElementById('cartSummary');
            const countBadge = document.getElementById('cartCount');
            const totalElement = document.getElementById('cartTotal');

            if(cartItems.length === 0) {
                container.innerHTML = `
                    <div class="empty-cart">
                        <div class="empty-cart-icon">🛒</div>
                        <h2>Giỏ hàng trống</h2>
                        <p>Hãy thêm món ăn vào giỏ hàng!</p>
                        <a href="<?php echo SITEURL; ?>food.php">Xem món ăn</a>
                    </div>
                `;
                summary.style.display = 'none';
                countBadge.textContent = '0 món';
                return;
            }

            let html = '';
            let total = 0;

            cartItems.forEach(item => {
                const itemTotal = item.price * item.quantity;
                total += itemTotal;

                html += `
                    <div class="cart-item" data-cart-id="${item.id}">
                        <img src="<?php echo SITEURL; ?>image/food/${item.image_name || 'default.jpg'}" 
                             alt="${item.food_name}" class="cart-item-image">
                        <div class="cart-item-info">
                            <div class="cart-item-name">${item.food_name}</div>
                            <div class="cart-item-price">${formatPrice(item.price)} đ</div>
                            <div class="cart-item-controls">
                                <button class="quantity-btn" onclick="updateQuantity(${item.id}, ${item.quantity - 1})">-</button>
                                <input type="number" class="quantity-input" value="${item.quantity}" 
                                       min="1" onchange="updateQuantity(${item.id}, this.value)">
                                <button class="quantity-btn" onclick="updateQuantity(${item.id}, ${item.quantity + 1})">+</button>
                                <button class="remove-btn" onclick="removeItem(${item.id})">Xóa</button>
                            </div>
                            <input type="text" class="note-input" 
                                   placeholder="Ghi chú (VD: ăn cay, không cay, nhiều, ít...)" 
                                   value="${item.note || ''}"
                                   onblur="updateNote(${item.id}, this.value)">
                        </div>
                    </div>
                `;
            });

            container.innerHTML = html;
            totalElement.textContent = formatPrice(total) + ' đ';
            countBadge.textContent = cartItems.length + ' món';
            summary.style.display = 'block';
        }

        // Cập nhật số lượng
        function updateQuantity(cartId, quantity) {
            if(quantity < 1) {
                removeItem(cartId);
                return;
            }

            const item = cartItems.find(i => i.id === cartId);
            if(!item) return;

            const formData = new FormData();
            formData.append('cart_id', cartId);
            formData.append('quantity', quantity);
            formData.append('note', item.note || '');

            fetch('../api/update-cart-item.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    loadCart();
                } else {
                    Swal.fire('Lỗi!', data.message, 'error');
                }
            });
        }

        // Cập nhật ghi chú
        function updateNote(cartId, note) {
            const item = cartItems.find(i => i.id === cartId);
            if(!item) return;

            const formData = new FormData();
            formData.append('cart_id', cartId);
            formData.append('quantity', item.quantity);
            formData.append('note', note);

            fetch('../api/update-cart-item.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    loadCart();
                }
            });
        }

        // Xóa item
        function removeItem(cartId) {
            Swal.fire({
                title: 'Xác nhận',
                text: 'Bạn có chắc muốn xóa món này?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ff6b81',
                cancelButtonColor: '#ccc',
                confirmButtonText: 'Xóa',
                cancelButtonText: 'Hủy'
            }).then((result) => {
                if(result.isConfirmed) {
                    const formData = new FormData();
                    formData.append('cart_id', cartId);

                    fetch('../api/remove-cart-item.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if(data.success) {
                            Swal.fire('Thành công!', 'Đã xóa khỏi giỏ hàng', 'success');
                            loadCart();
                        } else {
                            Swal.fire('Lỗi!', data.message, 'error');
                        }
                    });
                }
            });
        }

        // Đi đến trang thanh toán
        function goToCheckout() {
            if(cartItems.length === 0) {
                Swal.fire('Thông báo', 'Giỏ hàng trống!', 'info');
                return;
            }
            window.location.href = '<?php echo SITEURL; ?>user/checkout.php';
        }

        // Format giá
        function formatPrice(price) {
            return new Intl.NumberFormat('vi-VN').format(price);
        }

        // Load giỏ hàng khi trang load
        loadCart();
    </script>
</body>
</html>

