<?php
require_once('../config/constants.php');

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    $_SESSION['access-denied'] = "Vui lòng đăng nhập để sử dụng tính năng chat";
    header('location:'.SITEURL.'user/login.php');
    exit();
}

include('../partials-front/menu.php');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat với Admin - WowFood</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/chat.css">
</head>
<body>
    <div class="chat-container" style="margin-top: 100px; padding: 20px;">
        <div class="chat-wrapper">
            <div class="chat-header">
                <h2>💬 Chat với Admin</h2>
                <p>Chúng tôi sẽ phản hồi trong thời gian sớm nhất</p>
            </div>
            
            <div class="chat-messages" id="chatMessages">
                <!-- Messages will be loaded here -->
            </div>
            
            <div class="chat-input-container">
                <?php
                $order_code = $_GET['order_code'] ?? '';
                if ($order_code) {
                    echo '<div style="background: #e8f5e9; padding: 10px; margin-bottom: 10px; border-radius: 5px; border-left: 4px solid #4caf50;">';
                    echo '📦 Bạn đang chat về đơn hàng: <strong>' . htmlspecialchars($order_code) . '</strong>';
                    echo '<button type="button" onclick="insertOrderCode(\'' . htmlspecialchars($order_code) . '\')" style="margin-left: 10px; padding: 5px 10px; background: #4caf50; color: white; border: none; border-radius: 3px; cursor: pointer; font-size: 12px;">Chèn mã đơn</button>';
                    echo '</div>';
                }
                ?>
                <form id="chatForm" class="chat-form">
                    <input type="text" id="messageInput" placeholder="Nhập tin nhắn của bạn..." autocomplete="off" required>
                    <button type="submit" id="sendButton">Gửi</button>
                </form>
            </div>
        </div>
    </div>

    <?php include('../partials-front/footer.php'); ?>

    <script>
        let lastMessageId = 0;
        let pollingInterval;

        // Load messages
        function loadMessages(isInitial = false) {
            const url = isInitial 
                ? `../api/get-messages.php?last_id=0`
                : `../api/get-messages.php?last_id=${lastMessageId}`;
            
            fetch(url)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.messages.length > 0) {
                        if (isInitial) {
                            // Clear messages on initial load
                            document.getElementById('chatMessages').innerHTML = '';
                        }
                        data.messages.forEach(msg => {
                            addMessageToChat(msg);
                            lastMessageId = Math.max(lastMessageId, msg.id);
                        });
                        scrollToBottom();
                    }
                })
                .catch(error => console.error('Error loading messages:', error));
        }

        // Add message to chat
        function addMessageToChat(msg) {
            const messagesDiv = document.getElementById('chatMessages');
            const messageDiv = document.createElement('div');
            messageDiv.className = `message ${msg.sender_type === 'user' ? 'message-sent' : 'message-received'}`;
            
            const time = new Date(msg.created_at).toLocaleTimeString('vi-VN', { 
                hour: '2-digit', 
                minute: '2-digit' 
            });
            
            messageDiv.innerHTML = `
                <div class="message-content">
                    <div class="message-text">${escapeHtml(msg.message)}</div>
                    <div class="message-time">${time}</div>
                </div>
            `;
            
            messagesDiv.appendChild(messageDiv);
        }

        // Send message
        document.getElementById('chatForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const input = document.getElementById('messageInput');
            const message = input.value.trim();
            
            if (!message) return;
            
            const sendButton = document.getElementById('sendButton');
            sendButton.disabled = true;
            sendButton.textContent = 'Đang gửi...';
            
            const formData = new FormData();
            formData.append('message', message);
            
            fetch('../api/send-message.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    input.value = '';
                    loadMessages(false); // Reload to show sent message
                } else {
                    alert('Lỗi: ' + data.message);
                }
                sendButton.disabled = false;
                sendButton.textContent = 'Gửi';
            })
            .catch(error => {
                console.error('Error sending message:', error);
                alert('Có lỗi xảy ra khi gửi tin nhắn');
                sendButton.disabled = false;
                sendButton.textContent = 'Gửi';
            });
        });

        // Scroll to bottom
        function scrollToBottom() {
            const messagesDiv = document.getElementById('chatMessages');
            messagesDiv.scrollTop = messagesDiv.scrollHeight;
        }

        // Escape HTML
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        // Insert order code into message input
        function insertOrderCode(orderCode) {
            const input = document.getElementById('messageInput');
            const currentText = input.value.trim();
            const orderText = 'Mã đơn hàng: ' + orderCode;
            
            if (currentText) {
                input.value = currentText + ' - ' + orderText;
            } else {
                input.value = 'Xin chào, tôi cần hỗ trợ về ' + orderText;
            }
            
            input.focus();
        }

        // Start polling for new messages
        function startPolling() {
            pollingInterval = setInterval(() => loadMessages(false), 2000); // Poll every 2 seconds
        }

        // Stop polling
        function stopPolling() {
            if (pollingInterval) {
                clearInterval(pollingInterval);
            }
        }

        // Mark messages as read when page loads
        function markMessagesAsRead() {
            fetch('../api/mark-messages-read.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update badge after marking as read
                        if (window.updateChatBadge) {
                            updateChatBadge();
                        }
                    }
                })
                .catch(error => console.error('Error marking messages as read:', error));
        }

        // Start when page loads
        window.addEventListener('load', function() {
            loadMessages(true); // Load all messages initially
            markMessagesAsRead(); // Mark messages as read
            startPolling();
        });

        // Stop when page unloads
        window.addEventListener('beforeunload', function() {
            stopPolling();
        });
    </script>
</body>
</html>

