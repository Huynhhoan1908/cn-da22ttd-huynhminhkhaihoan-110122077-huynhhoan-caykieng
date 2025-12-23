<?php
require_once 'connect.php';

// Xóa cột size khỏi bảng chi_tiet_don_hang
$sql = "ALTER TABLE chi_tiet_don_hang DROP COLUMN IF EXISTS size";

try {
    if ($conn->query($sql) === TRUE) {
        echo "✅ Đã xóa cột 'size' khỏi bảng 'chi_tiet_don_hang' thành công!<br>";
    } else {
        echo "❌ Lỗi: " . $conn->error . "<br>";
    }
} catch (Exception $e) {
    echo "⚠️ Lỗi hoặc cột 'size' không tồn tại: " . $e->getMessage() . "<br>";
}

$conn->close();

echo "<br><strong>🎉 Hoàn tất! Đã xóa hoàn toàn chức năng size khỏi hệ thống.</strong><br>";
echo "<br><a href='trangchu.php'>→ Về trang chủ</a>";
?>
