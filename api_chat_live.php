<?php
session_start();
require_once 'connect.php'; 

header('Content-Type: application/json');

$action = $_POST['action'] ?? '';

// ==================================================================
// LOGIC ĐỊNH DANH (QUAN TRỌNG ĐỂ LƯU LỊCH SỬ)
// ==================================================================

$chatSessionId = '';

if (isset($_SESSION['user_id'])) {
    // 1. TRƯỜNG HỢP LÀ THÀNH VIÊN (Đã đăng nhập)
    // Luôn dùng ID cố định theo tài khoản -> Đi đâu, máy nào cũng thấy tin nhắn cũ
    $chatSessionId = 'user_' . $_SESSION['user_id'];
    
} else {
    // 2. TRƯỜNG HỢP LÀ KHÁCH VÃNG LAI (Chưa đăng nhập)
    // Ưu tiên 1: Lấy ID từ Cookie (nếu khách tắt trình duyệt mở lại vẫn nhớ)
    if (isset($_COOKIE['chat_guest_id'])) {
        $chatSessionId = $_COOKIE['chat_guest_id'];
    } 
    // Ưu tiên 2: Nếu chưa có Cookie, lấy từ Session hiện tại
    elseif (isset($_SESSION['chat_session_id'])) {
        $chatSessionId = $_SESSION['chat_session_id'];
    } 
    // Ưu tiên 3: Tạo mới hoàn toàn
    else {
        $chatSessionId = 'guest_' . uniqid();
    }

    // Lưu lại ID khách vào Session và Cookie (30 ngày) để lần sau vào lại vẫn nhớ
    $_SESSION['chat_session_id'] = $chatSessionId;
    setcookie('chat_guest_id', $chatSessionId, time() + (86400 * 30), "/"); // Cookie 30 ngày
}

// ==================================================================
// CÁC HÀM XỬ LÝ
// ==================================================================

// Hàm lấy tên hiển thị
function getCustomerName($conn, $sessionId) {
    // Nếu là user đăng nhập
    if (strpos($sessionId, 'user_') === 0) {
        $userId = str_replace('user_', '', $sessionId);
        $stmt = $conn->prepare("SELECT ho_ten FROM nguoi_dung WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user && !empty($user['ho_ten'])) {
            return $user['ho_ten'];
        }
    }
    // Nếu là khách vãng lai
    return 'Khách ' . substr($sessionId, -4);
}

// 1. GỬI TIN NHẮN
if ($action == 'send_message') {
    $message = trim($_POST['message']);
    $isAdmin = isset($_POST['is_admin']) ? 1 : 0; 
    
    // Nếu Admin gửi -> target là ID của khách. Nếu Khách gửi -> target là chính mình.
    $targetSession = ($isAdmin == 1) ? ($_POST['target_session'] ?? '') : $chatSessionId;

    if (!empty($message) && !empty($targetSession)) {
        // Lưu tin nhắn
        $stmt = $conn->prepare("INSERT INTO chat_messages (session_id, message, is_from_admin, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$targetSession, $message, $isAdmin]);

        // Cập nhật phiên chat
        if (!$isAdmin) {
            $realName = getCustomerName($conn, $targetSession);
            $check = $conn->prepare("SELECT id FROM chat_sessions WHERE session_id = ?");
            $check->execute([$targetSession]);
            
            if ($check->rowCount() > 0) {
                // Cập nhật tin nhắn cuối và tên (đề phòng khách mới đăng nhập thì cập nhật tên thật luôn)
                $upd = $conn->prepare("UPDATE chat_sessions SET last_message = ?, last_message_time = NOW(), unread_count = unread_count + 1, customer_name = ? WHERE session_id = ?");
                $upd->execute([$message, $realName, $targetSession]);
            } else {
                // Tạo phiên mới
                $ins = $conn->prepare("INSERT INTO chat_sessions (session_id, customer_name, last_message, unread_count) VALUES (?, ?, ?, 1)");
                $ins->execute([$targetSession, $realName, $message]);
            }
        } else {
            // Admin gửi
            $upd = $conn->prepare("UPDATE chat_sessions SET last_message = ?, last_message_time = NOW() WHERE session_id = ?");
            $upd->execute(['Bạn: ' . $message, $targetSession]);
            // Gửi thông báo cho user nếu là admin trả lời
            if (file_exists(__DIR__ . '/notification_helpers.php')) {
                require_once __DIR__ . '/notification_helpers.php';
                try {
                    // Nếu session là user_x thì lấy id, còn guest thì bỏ qua
                    if (strpos($targetSession, 'user_') === 0) {
                        $user_id = intval(str_replace('user_', '', $targetSession));
                        notify_chat_reply_user($user_id);
                    }
                } catch (Exception $ex) { error_log('Lỗi gửi notification chat: ' . $ex->getMessage()); }
            }
        }
        echo json_encode(['status' => 'success']);
    }
    exit;
}

// 2. LẤY TIN NHẮN (LOAD LỊCH SỬ)
if ($action == 'get_messages') {
    // --- BỔ SUNG ĐOẠN NÀY ĐỂ XÓA SỐ ĐỎ ---
if (isset($_POST['is_admin']) && $_POST['is_admin'] == 1 && isset($_POST['target_session'])) {
    $target_session = $_POST['target_session'];
    // Cập nhật tất cả tin nhắn của khách (is_from_admin = 0) thành đã đọc (is_read = 1)
    $stmt = $conn->prepare("UPDATE chat_messages SET is_read = 1 WHERE session_id = ? AND is_from_admin = 0");
    $stmt->execute([$target_session]);
}
// -------------------------------------
    // Xác định ID cần lấy tin
    $targetSession = $_POST['target_session'] ?? $chatSessionId;
    
    // Nếu là Admin xem -> Reset tin chưa đọc
    if (isset($_POST['is_admin']) && $_POST['is_admin'] == 1) {
        $conn->prepare("UPDATE chat_sessions SET unread_count = 0 WHERE session_id = ?")->execute([$targetSession]);
    }

    // Lấy toàn bộ lịch sử tin nhắn của session đó
    $stmt = $conn->prepare("SELECT * FROM chat_messages WHERE session_id = ? ORDER BY created_at ASC");
    $stmt->execute([$targetSession]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['messages' => $messages]);
    exit;
}

// 3. ADMIN: LẤY DANH SÁCH KHÁCH HÀNG
if ($action == 'get_conversations') {
    $stmt = $conn->query("SELECT * FROM chat_sessions ORDER BY last_message_time DESC");
    $conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($conversations);
    exit;
}
?>