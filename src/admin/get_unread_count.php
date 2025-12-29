<?php
// File: admin/get_unread_count.php
require_once '../connect.php'; // Kết nối database

// Đếm số tin nhắn từ khách (is_from_admin = 0) và chưa đọc (is_read = 0)
$sql = "SELECT COUNT(*) as total FROM chat_messages WHERE is_from_admin = 0 AND is_read = 0";

if ($conn instanceof PDO) {
    $stmt = $conn->query($sql);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
} else {
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();
}

// Trả về kết quả JSON
header('Content-Type: application/json');
echo json_encode(['count' => (int)$row['total']]);
?>