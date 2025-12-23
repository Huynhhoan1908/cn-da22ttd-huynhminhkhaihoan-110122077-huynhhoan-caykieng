<?php
// admin/chat_support.php
session_start();
// Kiểm tra quyền admin ở đây nếu cần
$page_title = 'Hỗ trợ trực tuyến';
$current_page = 'chat_support';
include 'header.php';
?>
<style>
    body { background: #f8fafc; font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; }
    .main-content { padding: 0; min-height: 100vh; background: #f8fafc; }
    .chat-admin-wrapper { display: flex; height: 100vh; }
    .chat-sidebar {
        width: 320px;
        background: #fff;
        border-right: 1.5px solid #e5e7eb;
        overflow-y: auto;
        box-shadow: 2px 0 8px rgba(0,0,0,0.03);
        display: flex;
        flex-direction: column;
    }
    .chat-sidebar-header {
        padding: 1.5rem 1rem 1rem 1rem;
        border-bottom: 1px solid #f1f1f1;
        background: #f8fafc;
        font-weight: 700;
        font-size: 1.1rem;
        color: #1d3e1f;
        letter-spacing: 0.01em;
    }
    .user-list { flex: 1; overflow-y: auto; }
    .user-item {
        padding: 1rem 1.25rem 0.75rem 1.25rem;
        border-bottom: 1px solid #f5f5f5;
        cursor: pointer;
        background: #fff;
        transition: background 0.18s;
        position: relative;
        display: flex;
        flex-direction: column;
        gap: 2px;
    }
    .user-item:hover, .user-item.active {
        background: #e8edc7;
    }
    .user-item strong {
        font-size: 1rem;
        color: #1d3e1f;
        font-weight: 600;
        margin-bottom: 2px;
    }
    .user-item small {
        color: #5a7a4f;
        font-size: 0.92em;
        opacity: 0.85;
    }
    .unread-badge {
        background: #e74c3c;
        color: #fff;
        padding: 2px 8px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 600;
        position: absolute;
        right: 18px;
        top: 18px;
        box-shadow: 0 2px 8px rgba(231,76,60,0.08);
    }
    .chat-area-admin {
        flex: 1;
        display: flex;
        flex-direction: column;
        background: #f8fafc;
        min-width: 0;
    }
    .chat-header-admin {
        padding: 1.25rem 1.5rem 1rem 1.5rem;
        border-bottom: 1px solid #e5e7eb;
        background: #fff;
        font-size: 1.1rem;
        font-weight: 600;
        color: #1d3e1f;
        letter-spacing: 0.01em;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }
    #adminChatBox {
        flex: 1;
        padding: 2rem 2.5rem 1.5rem 2.5rem;
        overflow-y: auto;
        background: #f8fafc;
        min-height: 0;
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }
    .msg-row {
        margin-bottom: 8px;
        display: flex;
        align-items: flex-end;
    }
    .msg-row.admin { justify-content: flex-end; }
    .msg-bubble {
        padding: 10px 16px;
        border-radius: 18px;
        max-width: 70%;
        font-size: 1rem;
        line-height: 1.5;
        box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        word-break: break-word;
    }
    .msg-row.client .msg-bubble {
        background: #fff;
        color: #222;
        border-bottom-left-radius: 4px;
        border-top-right-radius: 18px;
        border-top-left-radius: 18px;
        border-bottom-right-radius: 18px;
        border: 1.5px solid #e5e7eb;
    }
    .msg-row.admin .msg-bubble {
        background: #7fa84e;
        color: #fff;
        border-bottom-right-radius: 4px;
        border-top-left-radius: 18px;
        border-top-right-radius: 18px;
        border-bottom-left-radius: 18px;
        border: 1.5px solid #7fa84e;
    }
    .input-area-admin {
        padding: 1.25rem 1.5rem;
        border-top: 1px solid #e5e7eb;
        background: #fff;
        display: flex;
        gap: 12px;
        align-items: center;
    }
    .input-area-admin input[type="text"] {
        flex: 1;
        padding: 12px 16px;
        border-radius: 8px;
        border: 1.5px solid #e5e7eb;
        font-size: 1rem;
        background: #f8fafc;
        transition: border 0.2s;
    }
    .input-area-admin input[type="text"]:focus {
        border-color: #7fa84e;
        outline: none;
        background: #fff;
    }
    .input-area-admin button {
        padding: 12px 28px;
        background: linear-gradient(90deg, #7fa84e 0%, #5a7a4f 100%);
        color: #fff;
        border: none;
        border-radius: 8px;
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        transition: background 0.2s, transform 0.1s;
        box-shadow: 0 2px 8px rgba(61,107,63,0.08);
    }
    .input-area-admin button:active {
        transform: translateY(1px);
    }
    @media (max-width: 900px) {
        .chat-admin-wrapper { flex-direction: column; }
        .chat-sidebar { width: 100%; min-height: 180px; border-right: none; border-bottom: 1.5px solid #e5e7eb; }
        .chat-area-admin { min-height: 300px; }
    }
</style>

<div class="chat-admin-wrapper">
    <div class="chat-sidebar">
        <div class="chat-sidebar-header">
            <i class="fas fa-comments" style="margin-right:8px;color:#7fa84e;"></i>Hỗ trợ khách hàng
        </div>
        <div class="user-list" id="userList"></div>
    </div>
    <div class="chat-area-admin">
        <div class="chat-header-admin">
            <i class="fas fa-user-shield" style="color:#7fa84e;"></i> Khu vực chat với khách hàng
        </div>
        <div id="adminChatBox">
            <p style="text-align:center; color:#888; margin-top: 50px;">Chọn một khách hàng để bắt đầu chat</p>
        </div>
        <div class="input-area-admin">
            <input type="hidden" id="targetSessionId">
            <input type="text" id="adminInput" placeholder="Nhập câu trả lời..." disabled>
            <button onclick="adminSend()" id="sendBtn" disabled>Gửi</button>
        </div>
    </div>
</div>

<script>
    let currentSession = null;

    // 1. Load danh sách khách hàng
    function loadConversations() {
        const formData = new FormData();
        formData.append('action', 'get_conversations');
        fetch('../api_chat_live.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                const list = document.getElementById('userList');
                let html = '';
                data.forEach(user => {
                    const activeClass = (user.session_id === currentSession) ? 'active' : '';
                    const badge = user.unread_count > 0 ? `<span class="unread-badge">${user.unread_count}</span>` : '';
                    html += `
                        <div class="user-item ${activeClass}" onclick="selectUser('${user.session_id}')">
                            <strong>${user.customer_name}</strong>
                            <small>${user.last_message ? user.last_message.substring(0, 30) + '...' : ''}</small>
                            ${badge}
                        </div>
                    `;
                });
                list.innerHTML = html;
            });
    }

    // 2. Chọn khách hàng để chat
    function selectUser(sessionId) {
        currentSession = sessionId;
        document.getElementById('targetSessionId').value = sessionId;
        document.getElementById('adminInput').disabled = false;
        document.getElementById('sendBtn').disabled = false;
        
        loadAdminMessages(); // Tải tin nhắn ngay
    }

    // 3. Tải tin nhắn chi tiết
    function loadAdminMessages() {
        if (!currentSession) return;

        const formData = new FormData();
        formData.append('action', 'get_messages');
        formData.append('target_session', currentSession);
        formData.append('is_admin', 1); // Đánh dấu là admin xem để xóa unread

        fetch('../api_chat_live.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                const box = document.getElementById('adminChatBox');
                let html = '';
                data.messages.forEach(msg => {
                    const type = (msg.is_from_admin == 1) ? 'admin' : 'client';
                    html += `
                        <div class="msg-row ${type}">
                            <div class="msg-bubble">${msg.message}</div>
                        </div>
                    `;
                });
                box.innerHTML = html;
                // box.scrollTop = box.scrollHeight; // Auto scroll
            });
    }

    // 4. Admin gửi tin nhắn
    function adminSend() {
        const text = document.getElementById('adminInput').value;
        if (!text || !currentSession) return;

        const formData = new FormData();
        formData.append('action', 'send_message');
        formData.append('message', text);
        formData.append('target_session', currentSession);
        formData.append('is_admin', 1); // Quan trọng: Đánh dấu là Admin gửi

        fetch('../api_chat_live.php', { method: 'POST', body: formData })
            .then(() => {
                document.getElementById('adminInput').value = '';
                loadAdminMessages();
            });
    }

    // Tự động cập nhật mỗi 3 giây
    setInterval(() => {
        loadConversations();
        if (currentSession) loadAdminMessages();
    }, 3000);

    // Chạy lần đầu
    loadConversations();
</script>

</body>
</html>