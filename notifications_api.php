<?php
// API lấy thông báo cho khách hàng
header('Content-Type: application/json; charset=utf-8');
session_start();
require_once 'connect.php';

$action = $_GET['action'] ?? '';
$user_id = $_SESSION['user_id'] ?? 0;

try {
    if ($action === 'get_notifications') {
        // Lấy thông báo từ bảng thong_bao cho user hiện tại
        if ($user_id <= 0) {
            echo json_encode(['success' => true, 'notifications' => [], 'unread_count' => 0]);
            exit;
        }
        $stmt = $conn->prepare("
            SELECT id, type, title, message, link, is_read, created_at
            FROM thong_bao
            WHERE user_id = ?
            ORDER BY created_at DESC
            LIMIT 20
        ");
        $stmt->execute([$user_id]);
        $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $unreadCount = 0;
        foreach ($notifications as &$notif) {
            if (!$notif['is_read']) $unreadCount++;
        }
        echo json_encode([
            'success' => true,
            'notifications' => $notifications,
            'unread_count' => $unreadCount
        ]);
        
    } elseif ($action === 'mark_read') {
        if ($user_id <= 0) {
            throw new Exception('Cần đăng nhập');
        }
        
        $notification_id = intval($_POST['notification_id'] ?? 0);
        $stmt = $conn->prepare("UPDATE thong_bao SET is_read = 1 WHERE id = ? AND user_id = ?");
        $stmt->execute([$notification_id, $user_id]);
        echo json_encode(['success' => true]);
        
    } elseif ($action === 'mark_all_read') {
        if ($user_id <= 0) {
            throw new Exception('Cần đăng nhập');
        }
        
        // Đánh dấu tất cả thông báo là đã đọc
        $stmt = $conn->prepare("UPDATE thong_bao SET is_read = 1 WHERE user_id = ?");
        $stmt->execute([$user_id]);
        echo json_encode(['success' => true]);
        
    } else {
        throw new Exception('Action không hợp lệ');
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
