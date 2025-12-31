<?php
require_once('partials/menu.php');
?>

<div class="main-content">
    <div class="wrapper">
        <h1>Quản lý Chat</h1>
        <br><br>

        <div class="chat-admin-container">
            <div class="chat-list-panel">
                <h3>Danh sách Chat</h3>
                <div id="chatList" class="chat-list">
                    <!-- Chat list will be loaded here -->
                </div>
            </div>

            <div class="chat-messages-panel">
                <div id="chatHeader" class="chat-header-admin">
                    <h3>Chọn một cuộc trò chuyện để bắt đầu</h3>
                </div>
                
                <div id="chatMessagesAdmin" class="chat-messages-admin">
                    <!-- Messages will be displayed here -->
                </div>
                
                <div id="chatInputContainer" class="chat-input-container-admin" style="display: none;">
                    <form id="chatFormAdmin" class="chat-form-admin">
                        <input type="hidden" id="currentUserId" value="">
                        <input type="text" id="messageInputAdmin" placeholder="Nhập tin nhắn..." autocomplete="off" required>
                        <button type="submit" id="sendButtonAdmin">Gửi</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<link rel="stylesheet" href="../css/chat.css">
<script>
    let currentUserId = 0;
    let lastMessageId = 0;
    let pollingInterval;

    // Load chat list
    function loadChatList() {
        fetch('../api/get-chat-list.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayChatList(data.chat_list);
                }
            })
            .catch(error => console.error('Error loading chat list:', error));
    }

    // Display chat list
    function displayChatList(chatList) {
        const chatListDiv = document.getElementById('chatList');
        chatListDiv.innerHTML = '';
        
        if (chatList.length === 0) {
            chatListDiv.innerHTML = '<p style="padding: 20px; text-align: center; color: #999;">Chưa có cuộc trò chuyện nào</p>';
            return;
        }
        
        chatList.forEach(chat => {
            const chatItem = document.createElement('div');
            chatItem.className = `chat-item ${chat.user_id === currentUserId ? 'active' : ''}`;
            chatItem.onclick = () => selectChat(chat.user_id, chat.user_name);
            
            const unreadBadge = chat.unread_count > 0 
                ? `<span class="unread-badge">${chat.unread_count}</span>` 
                : '';
            
            const lastMessage = chat.last_message 
                ? (chat.last_message.length > 50 
                    ? chat.last_message.substring(0, 50) + '...' 
                    : chat.last_message)
                : 'Chưa có tin nhắn';
            
            chatItem.innerHTML = `
                <div class="chat-item-header">
                    <strong>${escapeHtml(chat.user_name)}</strong>
                    ${unreadBadge}
                </div>
                <div class="chat-item-message">${escapeHtml(lastMessage)}</div>
                <div class="chat-item-time">${formatTime(chat.last_message_time)}</div>
            `;
            
            chatListDiv.appendChild(chatItem);
        });
    }

    // Select chat
    function selectChat(userId, userName) {
        currentUserId = userId;
        lastMessageId = 0;
        
        // Update header
        document.getElementById('chatHeader').innerHTML = `
            <h3>💬 Chat với ${escapeHtml(userName)}</h3>
        `;
        
        // Show input
        document.getElementById('chatInputContainer').style.display = 'block';
        document.getElementById('currentUserId').value = userId;
        
        // Clear messages
        document.getElementById('chatMessagesAdmin').innerHTML = '';
        
        // Load messages
        lastMessageId = 0; // Reset để load tất cả tin nhắn
        loadMessages(true);
        
        // Update active state
        document.querySelectorAll('.chat-item').forEach(item => {
            item.classList.remove('active');
        });
        event.currentTarget.classList.add('active');
        
        // Reload chat list to update unread count
        loadChatList();
    }

    // Load messages
    function loadMessages(isInitial = false) {
        if (!currentUserId) return;
        
        const url = isInitial
            ? `../api/get-messages.php?user_id=${currentUserId}&last_id=0`
            : `../api/get-messages.php?user_id=${currentUserId}&last_id=${lastMessageId}`;
        
        fetch(url)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.messages.length > 0) {
                    if (isInitial) {
                        // Clear messages on initial load
                        document.getElementById('chatMessagesAdmin').innerHTML = '';
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
        const messagesDiv = document.getElementById('chatMessagesAdmin');
        const messageDiv = document.createElement('div');
        messageDiv.className = `message ${msg.sender_type === 'admin' ? 'message-sent' : 'message-received'}`;
        
        const time = new Date(msg.created_at).toLocaleTimeString('vi-VN', { 
            hour: '2-digit', 
            minute: '2-digit' 
        });
        
        const senderName = msg.sender_type === 'admin' 
            ? (msg.admin_name || 'Admin') 
            : (msg.user_name || 'User');
        
        messageDiv.innerHTML = `
            <div class="message-content">
                <div class="message-sender">${escapeHtml(senderName)}</div>
                <div class="message-text">${escapeHtml(msg.message)}</div>
                <div class="message-time">${time}</div>
            </div>
        `;
        
        messagesDiv.appendChild(messageDiv);
    }

    // Send message
    document.getElementById('chatFormAdmin').addEventListener('submit', function(e) {
        e.preventDefault();
        if (!currentUserId) return;
        
        const input = document.getElementById('messageInputAdmin');
        const message = input.value.trim();
        
        if (!message) return;
        
        const sendButton = document.getElementById('sendButtonAdmin');
        sendButton.disabled = true;
        sendButton.textContent = 'Đang gửi...';
        
        const formData = new FormData();
        formData.append('message', message);
        formData.append('user_id', currentUserId);
        
        fetch('../api/send-message.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                input.value = '';
                loadMessages(false); // Reload to show sent message
                loadChatList(); // Reload chat list
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
        const messagesDiv = document.getElementById('chatMessagesAdmin');
        messagesDiv.scrollTop = messagesDiv.scrollHeight;
    }

    // Escape HTML
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Format time
    function formatTime(timeString) {
        if (!timeString) return '';
        const date = new Date(timeString);
        const now = new Date();
        const diff = now - date;
        const minutes = Math.floor(diff / 60000);
        
        if (minutes < 1) return 'Vừa xong';
        if (minutes < 60) return `${minutes} phút trước`;
        if (minutes < 1440) return `${Math.floor(minutes / 60)} giờ trước`;
        return date.toLocaleDateString('vi-VN');
    }

    // Start polling
    function startPolling() {
        pollingInterval = setInterval(() => {
            if (currentUserId) {
                loadMessages(false); // Only load new messages
            }
            loadChatList();
        }, 2000);
    }

    // Initialize
    window.addEventListener('load', function() {
        loadChatList();
        startPolling();
    });

    window.addEventListener('beforeunload', function() {
        if (pollingInterval) {
            clearInterval(pollingInterval);
        }
    });
</script>

<?php require 'partials/footer.php'; ?>

