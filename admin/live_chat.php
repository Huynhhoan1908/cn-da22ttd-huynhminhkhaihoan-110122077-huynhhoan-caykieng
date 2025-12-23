<div id="live-chat-widget">
    <button id="chatLauncher" onclick="toggleChat()">
        <i class="fas fa-comments"></i>
        <span class="online-status"></span>
    </button>

    <div id="chatWindow" class="chat-hidden">
        <div class="chat-header">
            <div class="header-info">
                <div class="avatar-wrap">
                    <img src="https://ui-avatars.com/api/?name=Admin&background=fff&color=1d3e1f" alt="Admin">
                    <span class="dot-online"></span>
                </div>
                <div>
                    <span class="staff-name">Hỗ Trợ Khách Hàng</span>
                    <span class="staff-status">Đang hoạt động</span>
                </div>
            </div>
            <button class="close-chat" onclick="toggleChat()"><i class="fas fa-times"></i></button>
        </div>

        <div id="chatMessages" class="chat-body">
            <div class="message bot-msg">
                Xin chào! 👋<br>Shop có thể giúp gì cho bạn ạ?
            </div>
        </div>

        <div class="chat-footer">
            <input type="text" id="chatInput" placeholder="Nhập tin nhắn..." autocomplete="off" onkeypress="handleEnter(event)">
            <button onclick="sendMessage()" id="btnSend"><i class="fas fa-paper-plane"></i></button>
        </div>
    </div>
</div>

<style>
    /* CSS GIAO DIỆN CHAT */
    #live-chat-widget {
        position: fixed; bottom: 25px; right: 25px; z-index: 2147483647;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    }
    #chatLauncher {
        width: 60px; height: 60px; background: #1d3e1f; color: white; border-radius: 50%;
        border: none; cursor: pointer; box-shadow: 0 4px 15px rgba(0,0,0,0.3);
        font-size: 26px; transition: transform 0.3s; position: relative;
        display: flex; align-items: center; justify-content: center;
    }
    #chatLauncher:hover { transform: scale(1.1); }
    .online-status {
        position: absolute; top: 0; right: 0; width: 14px; height: 14px;
        background: #2ecc71; border: 2px solid #fff; border-radius: 50%;
        animation: pulse-green 2s infinite;
    }
    #chatWindow {
        position: absolute; bottom: 80px; right: 0; width: 340px; height: 450px;
        background: #fff; border-radius: 12px; box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        display: flex; flex-direction: column; overflow: hidden;
        transition: all 0.3s ease; transform-origin: bottom right;
        opacity: 0; transform: scale(0); pointer-events: none;
    }
    #chatWindow.chat-visible { opacity: 1; transform: scale(1); pointer-events: all; }
    .chat-header { background: #1d3e1f; color: white; padding: 15px; display: flex; justify-content: space-between; align-items: center; }
    .header-info { display: flex; align-items: center; gap: 10px; }
    .avatar-wrap { position: relative; width: 35px; height: 35px; }
    .avatar-wrap img { width: 100%; height: 100%; border-radius: 50%; border: 2px solid #fff; }
    .dot-online { position: absolute; bottom: 0; right: 0; width: 8px; height: 8px; background: #2ecc71; border-radius: 50%; }
    .staff-name { font-weight: 600; font-size: 0.95rem; display: block; }
    .staff-status { font-size: 0.75rem; opacity: 0.9; }
    .close-chat { background: transparent; border: none; color: white; font-size: 1.2rem; cursor: pointer; }
    .chat-body { flex: 1; padding: 15px; overflow-y: auto; background: #f5f7f9; display: flex; flex-direction: column; gap: 10px; }
    .message { max-width: 80%; padding: 10px 14px; font-size: 0.9rem; line-height: 1.4; border-radius: 12px; word-wrap: break-word; }
    .bot-msg { background: white; color: #333; align-self: flex-start; border-bottom-left-radius: 2px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
    .user-msg { background: #1d3e1f; color: white; align-self: flex-end; border-bottom-right-radius: 2px; }
    .chat-footer { padding: 10px; background: white; border-top: 1px solid #eee; display: flex; gap: 8px; }
    #chatInput { flex: 1; padding: 10px 15px; border: 1px solid #ddd; border-radius: 20px; outline: none; font-size: 0.9rem; }
    #chatInput:focus { border-color: #1d3e1f; }
    #btnSend { width: 40px; height: 40px; border-radius: 50%; border: none; background: #1d3e1f; color: white; cursor: pointer; display: flex; align-items: center; justify-content: center; }
    #btnSend:hover { background: #142d16; }
    @keyframes pulse-green {
        0% { box-shadow: 0 0 0 0 rgba(46, 204, 113, 0.7); }
        70% { box-shadow: 0 0 0 10px rgba(46, 204, 113, 0); }
        100% { box-shadow: 0 0 0 0 rgba(46, 204, 113, 0); }
    }
</style>

<script>
    const API_URL = 'api_chat_live.php'; 
    let isChatOpen = false;
    let autoRefresh;

    function toggleChat() {
        const win = document.getElementById('chatWindow');
        win.classList.toggle('chat-visible');
        isChatOpen = win.classList.contains('chat-visible');
        
        if (isChatOpen) {
            document.getElementById('chatInput').focus();
            scrollToBottom();
            loadMessages();
            if (!autoRefresh) autoRefresh = setInterval(loadMessages, 3000);
        } else {
            if (autoRefresh) { clearInterval(autoRefresh); autoRefresh = null; }
        }
    }

    function handleEnter(e) { if (e.key === 'Enter') sendMessage(); }

    function sendMessage() {
        const input = document.getElementById('chatInput');
        const text = input.value.trim();
        if (!text) return;
        appendMessage(text, 'user-msg');
        input.value = '';
        scrollToBottom();
        const formData = new FormData();
        formData.append('action', 'send_message');
        formData.append('message', text);
        fetch(API_URL, { method: 'POST', body: formData }).catch(err => console.error(err));
    }

    async function loadMessages() {
        if (!isChatOpen) return;
        try {
            const formData = new FormData();
            formData.append('action', 'get_messages');
            const response = await fetch(API_URL, { method: 'POST', body: formData });
            const data = await response.json();
            const body = document.getElementById('chatMessages');
            body.innerHTML = ''; 
            body.innerHTML = '<div class="message bot-msg">Xin chào! 👋<br>Shop có thể giúp gì cho bạn ạ?</div>';
            if (data.messages && data.messages.length > 0) {
                data.messages.forEach(msg => {
                    const type = (msg.is_from_admin == 1) ? 'bot-msg' : 'user-msg';
                    appendMessage(msg.message, type);
                });
            }
        } catch (error) { console.error(error); }
    }

    function appendMessage(text, className) {
        const body = document.getElementById('chatMessages');
        const div = document.createElement('div');
        div.className = `message ${className}`;
        div.textContent = text;
        body.appendChild(div);
    }

    function scrollToBottom() {
        const body = document.getElementById('chatMessages');
        body.scrollTop = body.scrollHeight;
    }
</script>