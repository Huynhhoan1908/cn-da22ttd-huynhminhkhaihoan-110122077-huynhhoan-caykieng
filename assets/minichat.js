// assets/minichat.js - Mini chat hai chiều cho khách và admin

(function(){
  // Tạo UI
  const chatHTML = `
    <div id="minichat-widget">
      <button id="minichat-toggle" aria-label="Mở chat hỗ trợ">💬</button>
      <div id="minichat-window">
        <div id="minichat-header">
          <span>Hỗ trợ khách hàng</span>
          <button id="minichat-close">&times;</button>
        </div>
        <div id="minichat-messages"></div>
        <form id="minichat-form" autocomplete="off">
          <input id="minichat-input" type="text" placeholder="Nhập tin nhắn..." maxlength="500" />
          <button type="submit">Gửi</button>
        </form>
      </div>
    </div>
  `;
  document.body.insertAdjacentHTML('beforeend', chatHTML);

  // Elements
  const $toggle = document.getElementById('minichat-toggle');
  const $window = document.getElementById('minichat-window');
  const $close = document.getElementById('minichat-close');
  const $messages = document.getElementById('minichat-messages');
  const $form = document.getElementById('minichat-form');
  const $input = document.getElementById('minichat-input');

  let isOpen = false;
  let sessionId = getSessionId();
  let polling = null;

  function getSessionId() {
    let sid = localStorage.getItem('minichat_session_id');
    if (!sid) {
      sid = 'chat_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
      localStorage.setItem('minichat_session_id', sid);
    }
    return sid;
  }

  function openChat() {
    isOpen = true;
    $window.style.display = 'flex';
    $input.focus();
    loadMessages();
    if (!polling) polling = setInterval(loadMessages, 3000);
  }
  function closeChat() {
    isOpen = false;
    $window.style.display = 'none';
    if (polling) { clearInterval(polling); polling = null; }
  }

  $toggle.onclick = openChat;
  $close.onclick = closeChat;

  $form.onsubmit = function(e) {
    e.preventDefault();
    const text = $input.value.trim();
    if (!text) return;
    sendMessage(text);
    $input.value = '';
  };

  function sendMessage(text) {
    addMessage({message: text, sender: 'user', created_at: new Date().toISOString()}, true);
    fetch('chat_api.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/x-www-form-urlencoded'},
      body: `action=send&session_id=${encodeURIComponent(sessionId)}&message=${encodeURIComponent(text)}`
    });
  }

  function addMessage(msg, local) {
    const div = document.createElement('div');
    div.className = 'minichat-msg ' + (msg.sender === 'admin' ? 'admin' : 'user');
    div.innerHTML = `<div class="bubble">${escapeHtml(msg.message)}</div><div class="time">${formatTime(msg.created_at)}</div>`;
    $messages.appendChild(div);
    $messages.scrollTop = $messages.scrollHeight;
    if (local) setTimeout(loadMessages, 500);
  }

  function loadMessages() {
    fetch('chat_api.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/x-www-form-urlencoded'},
      body: `action=load&session_id=${encodeURIComponent(sessionId)}`
    })
    .then(r=>r.json())
    .then(data => {
      if (data.success && data.messages) {
        $messages.innerHTML = '';
        data.messages.forEach(msg => addMessage(msg));
      }
    });
  }

  function escapeHtml(s) {
    return (s||'').replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m]));
  }
  function formatTime(t) {
    try { return new Date(t).toLocaleTimeString('vi-VN', {hour:'2-digit',minute:'2-digit'}); } catch { return ''; }
  }

  // Giao diện responsive
  const style = document.createElement('style');
  style.textContent = `
    #minichat-widget{position:fixed;bottom:24px;right:24px;z-index:9999;font-family:sans-serif}
    #minichat-toggle{background:#5a8a5a;color:#fff;border:none;border-radius:50%;width:56px;height:56px;font-size:2rem;box-shadow:0 2px 8px #0002;cursor:pointer;transition:.2s}
    #minichat-toggle:hover{background:#7fb27f}
    #minichat-window{display:none;flex-direction:column;width:340px;max-width:95vw;height:420px;max-height:80vh;background:#fff;border-radius:16px;box-shadow:0 8px 32px #0004;overflow:hidden}
    #minichat-header{background:#5a8a5a;color:#fff;padding:14px 18px;display:flex;align-items:center;justify-content:space-between;font-weight:600}
    #minichat-header button{background:none;border:none;color:#fff;font-size:1.5rem;cursor:pointer}
    #minichat-messages{flex:1;overflow-y:auto;padding:16px;background:#f8f9fa;display:flex;flex-direction:column;gap:10px}
    .minichat-msg{display:flex;flex-direction:column;align-items:flex-start;gap:2px}
    .minichat-msg.admin{align-items:flex-end}
    .minichat-msg .bubble{max-width:80%;padding:10px 16px;border-radius:16px;background:#e8f5e8;color:#222;font-size:1rem;word-break:break-word}
    .minichat-msg.admin .bubble{background:#5a8a5a;color:#fff}
    .minichat-msg .time{font-size:11px;color:#888;margin-top:2px}
    #minichat-form{display:flex;padding:12px 10px;background:#fff;gap:8px}
    #minichat-input{flex:1;padding:10px 12px;border-radius:8px;border:1px solid #c8d6c8;font-size:1rem}
    #minichat-form button{background:#5a8a5a;color:#fff;border:none;border-radius:8px;padding:0 18px;font-size:1rem;cursor:pointer;transition:.2s}
    #minichat-form button:hover{background:#7fb27f}
    @media(max-width:600px){#minichat-window{width:98vw;height:60vh;right:1vw;bottom:1vw}}
  `;
  document.head.appendChild(style);
})();
