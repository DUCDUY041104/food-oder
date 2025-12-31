<?php include('config/constants.php');?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <!-- Important to make website responsive -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WowFood - Food Delivery</title>

    <!-- Link our CSS file -->
    <link rel="stylesheet" href="css/style.css">
</head>
<style>
    .food-search{
    background-image: url(./image/bg.jpg);
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
                        <a href="<?php echo SITEURL ;?>">🏠 Trang chủ</a>
                    </li>
                    <li>
                        <a href="<?php echo SITEURL ;?>categories.php">📂 Danh mục</a>
                    </li>
                    <li>
                        <a href="<?php echo SITEURL ;?>food.php">🍽️ Món ăn</a>
                    </li>
                    <?php
                    if(isset($_SESSION['user'])){
                        $display_name = isset($_SESSION['user_full_name']) ? $_SESSION['user_full_name'] : $_SESSION['user'];
                        ?>
                        <?php if(isset($_SESSION['user_id'])): ?>
                        <li>
                            <a href="<?php echo SITEURL; ?>user/order-history.php">📦 Đơn hàng</a>
                        </li>
                        <li>
                            <a href="<?php echo SITEURL; ?>user/chat.php" id="chatLink" style="position: relative;">
                                💬 Chat
                                <span id="chatBadge" class="chat-badge" style="display: none;">0</span>
                            </a>
                        </li>
                        <?php endif; ?>
                        <li>
                            <a href="#" onclick="confirmLogout('<?php echo SITEURL; ?>user/logout.php'); return false;">🚪 Đăng xuất (<?php echo htmlspecialchars($display_name); ?>)</a>
                        </li>
                        <?php
                    }
                    else{
                        ?>
                        <li>
                            <a href="<?php echo SITEURL ;?>user/login.php">🔐 Đăng nhập</a>
                        </li>
                        <li>
                            <a href="<?php echo SITEURL ;?>user/register.php">📝 Đăng ký</a>
                        </li>
                        <?php
                    }
                    ?>
                    <?php
                    // Chỉ hiển thị link Admin nếu:
                    // 1. Chưa đăng nhập, hoặc
                    // 2. Đã đăng nhập bằng tài khoản admin (có admin_id)
                    // Không hiển thị nếu đã đăng nhập bằng tài khoản user thường (có user_id nhưng không có admin_id)
                    if(!isset($_SESSION['user']) || isset($_SESSION['admin_id'])){
                        ?>
                        <li>
                            <a href="<?php echo SITEURL ;?>admin/login.php">⚙️ Admin</a>
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
    </script>