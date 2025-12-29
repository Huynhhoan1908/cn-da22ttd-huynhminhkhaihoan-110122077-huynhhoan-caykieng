<?php
// API lấy thông báo cho khách hàng
header('Content-Type: application/json; charset=utf-8');
session_start();
require_once 'connect.php';

$action = $_GET['action'] ?? '';
$user_id = $_SESSION['user_id'] ?? 0;

try {
    if ($action === 'get_notifications') {
        // Lấy thông báo cá nhân
        $notifications = [];
        $unreadCount = 0;
        if ($user_id > 0) {
            $stmt = $conn->prepare("
                SELECT id, type, title, message, link, is_read, created_at
                FROM thong_bao
                WHERE user_id = ?
                ORDER BY created_at DESC
                LIMIT 20
            ");
            $stmt->execute([$user_id]);
            $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($notifications as &$notif) {
                if (!$notif['is_read']) $unreadCount++;
            }
        }

        // Lấy thông báo chung cho tất cả user
        $stmt2 = $conn->prepare("SELECT id, 'announcement' as type, tieu_de as title, noi_dung as message, duong_dan as link, 0 as is_read, ngay_tao as created_at FROM thong_bao_chung ORDER BY ngay_tao DESC LIMIT 10");
        $stmt2->execute();
        $chung = $stmt2->fetchAll(PDO::FETCH_ASSOC);

        // Gộp thông báo cá nhân và chung (ưu tiên cá nhân trước)
        $all = array_merge($notifications, $chung);

        // Sắp xếp lại theo thời gian mới nhất
        usort($all, function($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });

        echo json_encode([
            'success' => true,
            'notifications' => $all,
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
