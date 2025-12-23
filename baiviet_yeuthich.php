<?php
session_start();

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header("Location: dangnhap.php");
    exit();
}

$conn = new mysqli('localhost', 'root', '', 'web_cay');
if ($conn->connect_error) die("Lỗi kết nối: " . $conn->connect_error);
$conn->set_charset("utf8mb4");

$user_id = $_SESSION['user_id'];

// Lấy bài viết đã yêu thích
$posts = [];
$sql = "SELECT bv.*, nd.ho_ten, 
        (SELECT COUNT(*) FROM bai_viet_yeuthich WHERE bai_viet_id = bv.id) as total_likes
        FROM bai_viet bv
        JOIN bai_viet_yeuthich bvy ON bv.id = bvy.bai_viet_id
        LEFT JOIN nguoi_dung nd ON bv.nguoi_dung_id = nd.id
        WHERE bvy.nguoi_dung_id = ? AND bv.trang_thai = 'approved'
        ORDER BY bvy.ngay_yeu_thich DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $posts[] = $row;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Bài Viết Yêu Thích - HuynhHoan</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Quicksand:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/theme.css">
    <style>
        /* Tận dụng lại CSS từ baiviet.php hoặc include file css chung */
        body { font-family: 'Quicksand', sans-serif; background-color: #f0fdf4; color: #1d3e1f; }
        .container { max-width: 1200px; margin: 0 auto; padding: 0 1rem; }
        .post-item { background: white; padding: 2rem; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 2rem; }
        .post-title a { text-decoration: none; color: #3d6b3f; font-size: 1.5rem; font-weight: bold; }
        .post-meta { color: #718096; font-size: 0.9rem; margin-bottom: 1rem; }
        .btn-back { display: inline-block; margin: 2rem 0; padding: 10px 20px; background: #3d6b3f; color: white; text-decoration: none; border-radius: 5px; }
    </style>
</head>
<body>
    <header class="header" style="background: white; padding: 1rem 0; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
        <div class="container" style="display:flex; justify-content: space-between; align-items:center;">
            <a href="trangchu.php" style="display:flex; align-items:center; gap:12px; text-decoration:none;">
                <img src="images/logo.jpg" alt="HuynhHoan Logo" style="height:45px; width:auto; border-radius:8px;">
                <span style="font-weight:600; font-size:1.4rem; color:#3d6b3f;">HuynhHoan</span>
            </a>
            <a href="baiviet.php" class="btn-back" style="margin:0;">Quay lại bài viết</a>
        </div>
    </header>

    <div class="container">
        <h1 style="text-align: center; color: #3d6b3f; margin: 2rem 0;">
            <i class="fas fa-heart" style="color: #e74c3c;"></i> Bài Viết Đã Yêu Thích
        </h1>

        <?php if (empty($posts)): ?>
            <div style="text-align: center; padding: 3rem; color: #718096;">
                <i class="far fa-heart" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                <p>Bạn chưa yêu thích bài viết nào.</p>
                <a href="baiviet.php" style="color: #3d6b3f; font-weight: bold;">Khám phá ngay</a>
            </div>
        <?php else: ?>
            <?php foreach ($posts as $post): ?>
                <div class="post-item">
                    <div class="post-meta">
                        Đăng bởi <strong><?php echo htmlspecialchars($post['ho_ten']); ?></strong> 
                        vào <?php echo date('d/m/Y', strtotime($post['ngay_tao'])); ?>
                    </div>
                    <div class="post-title">
                        <a href="#"><?php echo htmlspecialchars($post['tieu_de']); ?></a>
                    </div>
                    <div style="margin-top: 10px;">
                        <?php echo nl2br(substr(strip_tags($post['noi_dung']), 0, 200)); ?>...
                    </div>
                    <div style="margin-top: 1rem; color: #e74c3c; font-weight: bold;">
                        <i class="fas fa-heart"></i> <?php echo $post['total_likes']; ?> lượt thích
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <?php
// Kiểm tra trạng thái đăng nhập để JS sử dụng
$chat_is_logged = isset($_SESSION['user_id']) ? 'true' : 'false';
?>

<div id="live-chat-widget">
    <button id="chatLauncher" type="button" onclick="toggleChat()">
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
            <button class="close-chat" type="button" onclick="toggleChat()"><i class="fas fa-times"></i></button>
        </div>

        <div id="chatMessages" class="chat-body">
            <div class="message bot-msg">
                Xin chào! 👋<br>Shop có thể giúp gì cho bạn ạ?
            </div>
        </div>

        <div class="chat-footer">
            <input type="text" id="chatInput" placeholder="Nhập tin nhắn..." autocomplete="off" onkeypress="handleEnter(event)">
            <button onclick="sendMessage()" type="button" id="btnSend"><i class="fas fa-paper-plane"></i></button>
        </div>
    </div>
</div>

<style>
    /* CSS CHAT - Z-index cực cao để đè lên mọi thứ */
    #live-chat-widget { position: fixed; bottom: 30px; right: 30px; z-index: 2147483647; font-family: sans-serif; }
    
    #chatLauncher { 
        width: 60px; height: 60px; background: #1d3e1f; color: white; border-radius: 50%; 
        border: none; cursor: pointer; box-shadow: 0 4px 15px rgba(0,0,0,0.3); font-size: 26px; 
        display: flex; align-items: center; justify-content: center; position: relative; transition: transform 0.2s; 
    }
    #chatLauncher:hover { transform: scale(1.1); }
    
    .online-status { position: absolute; top: 0; right: 0; width: 14px; height: 14px; background: #2ecc71; border: 2px solid #fff; border-radius: 50%; }
    
    #chatWindow { 
        position: absolute; bottom: 80px; right: 0; width: 320px; height: 400px; background: #fff; 
        border-radius: 12px; box-shadow: 0 5px 30px rgba(0,0,0,0.2); display: none; flex-direction: column; overflow: hidden; border: 1px solid #ddd; 
    }
    #chatWindow.chat-visible { display: flex; animation: chatPopUp 0.3s ease-out; }
    
    .chat-header { background: #1d3e1f; color: white; padding: 12px; display: flex; justify-content: space-between; align-items: center; }
    .header-info { display: flex; align-items: center; gap: 10px; }
    .avatar-wrap { position: relative; width: 35px; height: 35px; }
    .avatar-wrap img { width: 100%; height: 100%; border-radius: 50%; border: 2px solid #fff; }
    .dot-online { position: absolute; bottom: 0; right: 0; width: 8px; height: 8px; background: #2ecc71; border-radius: 50%; }
    .staff-name { font-weight: bold; font-size: 0.9rem; display: block; }
    .staff-status { font-size: 0.7rem; opacity: 0.9; }
    .close-chat { background: transparent; border: none; color: white; font-size: 1.1rem; cursor: pointer; }
    
    .chat-body { flex: 1; padding: 10px; overflow-y: auto; background: #f5f7f9; display: flex; flex-direction: column; gap: 8px; }
    .message { max-width: 80%; padding: 8px 12px; font-size: 0.9rem; border-radius: 10px; word-wrap: break-word; }
    .bot-msg { background: white; color: #333; align-self: flex-start; box-shadow: 0 1px 2px rgba(0,0,0,0.1); }
    .user-msg { background: #1d3e1f; color: white; align-self: flex-end; }
    
    .chat-footer { padding: 10px; background: white; border-top: 1px solid #eee; display: flex; gap: 5px; }
    #chatInput { flex: 1; padding: 8px 12px; border: 1px solid #ddd; border-radius: 20px; outline: none; }
    #btnSend { width: 36px; height: 36px; border-radius: 50%; border: none; background: #1d3e1f; color: white; cursor: pointer; display: flex; align-items: center; justify-content: center; }
    
    @keyframes chatPopUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
</style>

<script>
    // Link API Chat (đảm bảo file này tồn tại)
    const API_URL_CHAT = 'api_chat_live.php'; 
    const isUserLoggedIn = <?php echo $chat_is_logged; ?>;
    let chatInterval;

    function toggleChat() {
        // 1. Kiểm tra đăng nhập
        if (!isUserLoggedIn) {
            if (confirm("Bạn cần Đăng nhập để chat với nhân viên.\nĐến trang đăng nhập ngay?")) {
                window.location.href = 'dangnhap.php';
            }
            return;
        }

        // 2. Mở chat
        const win = document.getElementById('chatWindow');
        win.classList.toggle('chat-visible');
        
        if (win.classList.contains('chat-visible')) {
            document.getElementById('chatInput').focus();
            loadLiveMessages(); // Tải tin nhắn ngay
            chatInterval = setInterval(loadLiveMessages, 3000); // Tự động cập nhật 3s/lần
        } else {
            clearInterval(chatInterval); // Tắt cập nhật khi đóng
        }
    }

    function handleEnter(e) { if (e.key === 'Enter') sendMessage(); }

    function sendMessage() {
        const input = document.getElementById('chatInput');
        const text = input.value.trim();
        if (!text) return;
        
        // Hiện tin nhắn tạm thời
        appendMessage(text, 'user-msg');
        input.value = '';
        
        // Gửi lên server
        const fd = new FormData();
        fd.append('action', 'send_message');
        fd.append('message', text);
        
        fetch(API_URL_CHAT, { method: 'POST', body: fd })
            .catch(err => console.error(err));
    }

    function loadLiveMessages() {
        const fd = new FormData();
        fd.append('action', 'get_messages');
        
        fetch(API_URL_CHAT, { method: 'POST', body: fd })
            .then(r => r.json())
            .then(data => {
                const body = document.getElementById('chatMessages');
                
                // Giữ lại tin nhắn chào
                let html = '<div class="message bot-msg">Xin chào! 👋<br>Shop có thể giúp gì cho bạn ạ?</div>';
                
                if (data.messages && data.messages.length > 0) {
                    data.messages.forEach(msg => {
                        const type = (msg.is_from_admin == 1) ? 'bot-msg' : 'user-msg';
                        html += `<div class="message ${type}">${msg.message}</div>`;
                    });
                }
                body.innerHTML = html;
                body.scrollTop = body.scrollHeight; // Tự cuộn xuống dưới
            })
            .catch(err => console.log('Lỗi chat:', err));
    }

    function appendMessage(text, cls) {
        const div = document.createElement('div');
        div.className = `message ${cls}`;
        div.textContent = text;
        const body = document.getElementById('chatMessages');
        body.appendChild(div);
        body.scrollTop = body.scrollHeight;
    }
</script>
</body>
</html>