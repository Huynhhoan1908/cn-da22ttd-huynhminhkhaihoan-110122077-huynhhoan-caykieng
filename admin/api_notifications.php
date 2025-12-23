
<?php
// File: admin/api_notifications.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config.php'; // Đảm bảo đúng đường dẫn

header('Content-Type: application/json; charset=utf-8');

try {

    // 1. Đánh dấu đã đọc từng loại
    if (isset($_GET['mark_type'])) {
        $type = $_GET['mark_type'];
        $stmt = $conn->prepare("UPDATE admin_notifications SET is_read = 1 WHERE type = ?");
        $stmt->bind_param("s", $type);
        $stmt->execute();
    }
    // Đánh dấu đã đọc từng id (giữ lại nếu có dùng ở nơi khác)
    if (isset($_GET['mark_read'])) {
        $id = (int)$_GET['mark_read'];
        $stmt = $conn->prepare("UPDATE admin_notifications SET is_read = 1 WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
    }

    // 2. Đếm số lượng chưa đọc theo từng loại
    $count_sql = "SELECT type, COUNT(*) AS count 
                  FROM admin_notifications 
                  WHERE is_read = 0 
                  GROUP BY type";
    $stmt_count = $conn->query($count_sql);
    $counts = [];
    if ($stmt_count) {
        while ($row = $stmt_count->fetch_assoc()) {
            $counts[$row['type']] = (int)$row['count'];
        }
    }

    // 3. Lấy 10 thông báo mới nhất
    $stmt_noti = $conn->query("SELECT id, message, link, is_read, created_at, type FROM admin_notifications ORDER BY created_at DESC LIMIT 10");
    $notifications = [];
    if ($stmt_noti) {
        while ($row = $stmt_noti->fetch_assoc()) {
            $notifications[] = $row;
        }
    }

    echo json_encode([
        'success' => true, 
        'notifications' => $notifications, 
        'counts_by_type' => $counts
    ]);

} catch (Exception $e) {
    error_log("Notification API Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'API Error']);
}
?>
