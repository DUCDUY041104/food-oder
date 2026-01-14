<?php 
require_once('../config/constants.php');
require_once('login-check.php');
?>

<html>
    <head>
        <title>food oder</title>

        <link rel="stylesheet" href="../css/admin.css">
    </head>

    <body>
        <!-- menu section starts-->
         <div class="menu text-center">
            <div class="wrapper">
               <ul>
                <li><a href="index.php">Trang chủ</a></li>
                <li><a href="manage-admin.php">Quản trị viên</a></li>
                <li><a href="manage-user.php">Người dùng</a></li>
                <li><a href="manage-category.php">Danh mục</a></li>
                <li><a href="manage-food.php">Món ăn</a></li>
                <li><a href = "manage-order.php">Đơn hàng</a></li>
                <li><a href = "manage-payment.php">Thanh toán</a></li>
                <li><a href = "refund.php">Hoàn tiền</a></li>
                <li>
                    <a href="manage-chat.php" id="chatLinkAdmin" style="position: relative;">
                         Chat
                        <span id="chatBadgeAdmin" class="chat-badge" style="display: none;">0</span>
                    </a>
                </li>
                <li><a href="#" onclick="confirmLogout('logout.php'); return false;">Đăng xuất</a></li>
               </ul>
               
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
                   
                   #chatLinkAdmin {
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
                           confirmButtonText: 'đăng xuất',
                           cancelButtonText: 'Hủy'
                       }).then((result) => {
                           if (result.isConfirmed) {
                               window.location.href = logoutUrl;
                           }
                       });
                   }
                   
                   // Load và cập nhật số tin nhắn chưa đọc cho admin
                   function updateChatBadgeAdmin() {
                       const chatBadge = document.getElementById('chatBadgeAdmin');
                       if (!chatBadge) return;
                       
                       fetch('../api/get-unread-count.php')
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
                   if (document.getElementById('chatBadgeAdmin')) {
                       updateChatBadgeAdmin();
                       // Cập nhật mỗi 5 giây
                       setInterval(updateChatBadgeAdmin, 5000);
                   }
               </script>
            </div>
         </div>
        <!-- menu section ends-->