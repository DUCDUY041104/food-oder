<?php include('config/constants.php');?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <!-- Important to make website responsive -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WowFood - Food Delivery</title>

    <!-- Link our CSS file -->
    <link rel="stylesheet" href="<?php echo SITEURL; ?>css/style.css">
</head>
<style>
    .food-search{
    background-image: url(<?php echo SITEURL; ?>image/bg.jpg);
    background-size: cover;
    background-repeat: no-repeat;
    background-position: center;
    padding: 7% 0;
}
</style>

<body>
    <!-- Navbar Section Starts Here -->
    <section class="navbar" style="position: fixed;top: 0;left: 0;width: 100%;background-color: white;z-index: 1000;border-bottom: 1px solid; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);height: 79px;">
        <div class="container">
            <div class="logo">
                <a href="<?php echo SITEURL; ?>" title="WowFood - Food Delivery">
                    <img src="<?php echo SITEURL; ?>image/logo.png" alt="WowFood Logo" class="img-responsive">
                </a>
            </div>

            <div class="menu text-right">
                <ul>
                    <li>
                        <a href="<?php echo SITEURL ;?>">Trang chủ</a>
                    </li>
                    <li>
                        <a href="<?php echo SITEURL ;?>categories.php">Danh mục</a>
                    </li>
                    <li>
                        <a href="<?php echo SITEURL ;?>food.php">Món ăn</a>
                    </li>
                    <?php
                    if(isset($_SESSION['user'])){
                        $display_name = isset($_SESSION['user_full_name']) ? $_SESSION['user_full_name'] : $_SESSION['user'];
                        ?>
                        <?php if(isset($_SESSION['user_id'])): ?>
                        <li>
                            <a href="<?php echo SITEURL; ?>user/cart.php" style="position: relative;">
                                 Giỏ hàng
                                <span id="cartBadge" class="chat-badge" style="display: none;">0</span>
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo SITEURL; ?>user/order-history.php">Đơn hàng</a>
                        </li>
                        <li>
                            <a href="<?php echo SITEURL; ?>user/chat.php" id="chatLink" style="position: relative;">
                                 Chat
                                <span id="chatBadge" class="chat-badge" style="display: none;">0</span>
                            </a>
                        </li>
                        <?php endif; ?>
                        <li>
                            <a href="#" onclick="confirmLogout('<?php echo SITEURL; ?>user/logout.php'); return false;">Đăng xuất (<?php echo htmlspecialchars($display_name); ?>)</a>
                        </li>
                        <?php
                    }
                    else{
                        ?>
                        <li>
                            <a href="<?php echo SITEURL ;?>user/login.php">Đăng nhập</a>
                        </li>
                    
                     <?php
                     }
                     ?>
                 </ul>
            </div>

            <div class="clearfix"></div>
        </div>
    </section>
    <!-- Navbar Section Ends Here -->
    
    <!-- SweetAlert2 for Logout Confirmation -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .chat-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background: linear-gradient(135deg, #ff6b81 0%, #ff4757 100%);
            color: white;
            border-radius: 50%;
            padding: 2px 6px;
            font-size: 11px;
            font-weight: bold;
            min-width: 18px;
            height: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
            animation: pulse 2s infinite;
        }
        
        /* Tắt scroll cho SweetAlert2 */
        .swal2-no-scroll {
            overflow: hidden !important;
        }
        
        .swal2-popup.swal2-no-scroll {
            overflow-y: visible !important;
            max-height: none !important;
        }
        
        .swal2-html-container.swal2-no-scroll {
            overflow: visible !important;
            max-height: none !important;
        }
        
        @keyframes pulse {
            0%, 100% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.1);
            }
        }
        
        #chatLink {
            position: relative;
            display: inline-block;
        }
    </style>
    <script>
        function confirmLogout(logoutUrl) {
            Swal.fire({
                title: 'Bạn có chắc chắn muốn đăng xuất?',
                text: 'Bạn sẽ phải đăng nhập lại để tiếp tục sử dụng',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Có, đăng xuất',
                cancelButtonText: 'Hủy'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = logoutUrl;
                }
            });
        }
        
        // Load và cập nhật số tin nhắn chưa đọc
        function updateChatBadge() {
            const chatBadge = document.getElementById('chatBadge');
            if (!chatBadge) return;
            
            fetch('<?php echo SITEURL; ?>api/get-unread-count.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const count = data.unread_count || 0;
                        if (count > 0) {
                            chatBadge.textContent = count > 99 ? '99+' : count;
                            chatBadge.style.display = 'flex';
                        } else {
                            chatBadge.style.display = 'none';
                        }
                    }
                })
                .catch(error => console.error('Error loading unread count:', error));
        }
        
        // Cập nhật badge khi trang load
        if (document.getElementById('chatBadge')) {
            updateChatBadge();
            // Cập nhật mỗi 5 giây
            setInterval(updateChatBadge, 5000);
        }

        // Hàm thêm vào giỏ hàng
        function addToCart(foodId, quantity = 1, note = '') {
            <?php if(!isset($_SESSION['user_id'])): ?>
            Swal.fire({
                icon: 'warning',
                title: 'Yêu cầu đăng nhập',
                text: 'Vui lòng đăng nhập để thêm vào giỏ hàng!',
                confirmButtonColor: '#ff6b81',
                showCancelButton: true,
                confirmButtonText: 'Đăng nhập',
                cancelButtonText: 'Hủy'
            }).then((result) => {
                if(result.isConfirmed) {
                    window.location.href = '<?php echo SITEURL; ?>user/login.php';
                }
            });
            return;
            <?php endif; ?>

            Swal.fire({
                title: '🛒 Thêm vào giỏ hàng',
                html: `
                    <div style="text-align: left; padding: 10px 0;">
                        <div style="margin-bottom: 20px;">
                            <label style="display: block; margin-bottom: 8px; font-weight: bold; color: #2f3542;">Số lượng:</label>
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <button type="button" onclick="decreaseQty()" style="width: 35px; height: 35px; border: 1px solid #ddd; background: white; border-radius: 5px; cursor: pointer; font-size: 1.2em;">-</button>
                                <input type="number" id="swal-quantity" value="${quantity}" min="1" style="width: 80px; text-align: center; border: 1px solid #ddd; border-radius: 5px; padding: 8px; font-size: 1em;">
                                <button type="button" onclick="increaseQty()" style="width: 35px; height: 35px; border: 1px solid #ddd; background: white; border-radius: 5px; cursor: pointer; font-size: 1.2em;">+</button>
                            </div>
                        </div>
                        <div>
                            <label style="display: block; margin-bottom: 8px; font-weight: bold; color: #2f3542;">Ghi chú (tùy chọn):</label>
                            <input type="text" id="swal-note" placeholder="VD: ăn cay, không cay, nhiều, ít..." style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 0.95em; box-sizing: border-box;">
                        </div>
                    </div>
                `,
                width: '450px',
                showCancelButton: true,
                confirmButtonText: '✅ Thêm vào giỏ',
                cancelButtonText: '❌ Hủy',
                confirmButtonColor: '#ff6b81',
                cancelButtonColor: '#6c757d',
                customClass: {
                    popup: 'swal2-no-scroll',
                    htmlContainer: 'swal2-no-scroll'
                },
                didOpen: () => {
                    window.decreaseQty = function() {
                        const input = document.getElementById('swal-quantity');
                        if(parseInt(input.value) > 1) {
                            input.value = parseInt(input.value) - 1;
                        }
                    };
                    window.increaseQty = function() {
                        const input = document.getElementById('swal-quantity');
                        input.value = parseInt(input.value) + 1;
                    };
                },
                preConfirm: () => {
                    const qty = document.getElementById('swal-quantity').value;
                    const note = document.getElementById('swal-note').value;
                    if(!qty || qty < 1) {
                        Swal.showValidationMessage('Số lượng phải lớn hơn 0!');
                        return false;
                    }
                    return {quantity: parseInt(qty), note: note};
                }
            }).then((result) => {
                if(result.isConfirmed) {
                    const formData = new FormData();
                    formData.append('food_id', foodId);
                    formData.append('quantity', result.value.quantity);
                    formData.append('note', result.value.note);

                    fetch('<?php echo SITEURL; ?>api/add-to-cart.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if(data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Thành công!',
                                text: data.message,
                                confirmButtonColor: '#ff6b81',
                                showCancelButton: true,
                                confirmButtonText: 'Xem giỏ hàng',
                                cancelButtonText: 'Tiếp tục mua',
                                timer: 3000
                            }).then((result) => {
                                if(result.isConfirmed) {
                                    window.location.href = '<?php echo SITEURL; ?>user/cart.php';
                                }
                            });
                            updateCartBadge();
                        } else {
                            Swal.fire('Lỗi!', data.message, 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire('Lỗi!', 'Có lỗi xảy ra, vui lòng thử lại!', 'error');
                    });
                }
            });
        }

        // Cập nhật badge giỏ hàng
        function updateCartBadge() {
            const badge = document.getElementById('cartBadge');
            if(!badge) return;
            
            fetch('<?php echo SITEURL; ?>api/get-cart-count.php')
                .then(response => response.json())
                .then(data => {
                    if(data.count > 0) {
                        badge.textContent = data.count > 99 ? '99+' : data.count;
                        badge.style.display = 'flex';
                    } else {
                        badge.style.display = 'none';
                    }
                })
                .catch(error => console.error('Error loading cart count:', error));
        }

        // Load badge khi trang load
        <?php if(isset($_SESSION['user_id'])): ?>
        updateCartBadge();
        setInterval(updateCartBadge, 3000);
        <?php endif; ?>
    </script>