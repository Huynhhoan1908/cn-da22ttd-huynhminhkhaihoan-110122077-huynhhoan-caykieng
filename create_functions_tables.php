<?php
require_once 'connect.php';

// Tạo bảng chuc_nang
$sql1 = "CREATE TABLE IF NOT EXISTS chuc_nang (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ten_chuc_nang VARCHAR(255) NOT NULL,
    mo_ta TEXT,
    ngay_tao TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql1) === TRUE) {
    echo "✅ Bảng 'chuc_nang' đã được tạo thành công<br>";
} else {
    echo "❌ Lỗi khi tạo bảng 'chuc_nang': " . $conn->error . "<br>";
}

// Tạo bảng san_pham_chuc_nang (junction table)
$sql2 = "CREATE TABLE IF NOT EXISTS san_pham_chuc_nang (
    san_pham_id INT NOT NULL,
    chuc_nang_id INT NOT NULL,
    PRIMARY KEY (san_pham_id, chuc_nang_id),
    FOREIGN KEY (san_pham_id) REFERENCES san_pham(id) ON DELETE CASCADE,
    FOREIGN KEY (chuc_nang_id) REFERENCES chuc_nang(id) ON DELETE CASCADE
)";

if ($conn->query($sql2) === TRUE) {
    echo "✅ Bảng 'san_pham_chuc_nang' đã được tạo thành công<br>";
} else {
    echo "❌ Lỗi khi tạo bảng 'san_pham_chuc_nang': " . $conn->error . "<br>";
}

// Tạo bảng san_pham_danh_muc (junction table)
$sql3 = "CREATE TABLE IF NOT EXISTS san_pham_danh_muc (
    san_pham_id INT NOT NULL,
    danh_muc_id INT NOT NULL,
    PRIMARY KEY (san_pham_id, danh_muc_id),
    FOREIGN KEY (san_pham_id) REFERENCES san_pham(id) ON DELETE CASCADE,
    FOREIGN KEY (danh_muc_id) REFERENCES danh_muc(id) ON DELETE CASCADE
)";

if ($conn->query($sql3) === TRUE) {
    echo "✅ Bảng 'san_pham_danh_muc' đã được tạo thành công<br>";
} else {
    echo "❌ Lỗi khi tạo bảng 'san_pham_danh_muc': " . $conn->error . "<br>";
}

// Migrate dữ liệu từ san_pham.danh_muc_id sang san_pham_danh_muc
$sql_migrate = "INSERT IGNORE INTO san_pham_danh_muc (san_pham_id, danh_muc_id)
                SELECT id, danh_muc_id 
                FROM san_pham 
                WHERE danh_muc_id IS NOT NULL";

if ($conn->query($sql_migrate) === TRUE) {
    echo "✅ Đã migrate dữ liệu từ san_pham.danh_muc_id sang bảng san_pham_danh_muc<br>";
} else {
    echo "❌ Lỗi khi migrate dữ liệu: " . $conn->error . "<br>";
}

// Thêm một số chức năng mẫu
$sample_functions = [
    ['Lọc Không Khí', 'Cây có khả năng lọc không khí, loại bỏ độc tố'],
    ['Dễ Chăm Sóc', 'Cây dễ trồng, phù hợp cho người mới bắt đầu'],
    ['Tốt Cho Phong Thủy', 'Cây mang lại may mắn, tài lộc theo phong thủy'],
    ['Chịu Bóng Tốt', 'Cây có thể sống trong điều kiện ánh sáng yếu'],
    ['Chống Bức Xạ', 'Cây có khả năng chống bức xạ từ thiết bị điện tử'],
    ['Tạo Oxy', 'Cây tạo oxy mạnh, cải thiện không khí'],
    ['Chịu Hạn', 'Cây có khả năng chịu hạn tốt, không cần tưới nhiều']
];

$stmt = $conn->prepare("INSERT IGNORE INTO chuc_nang (ten_chuc_nang, mo_ta) VALUES (?, ?)");
foreach ($sample_functions as $func) {
    $stmt->bind_param("ss", $func[0], $func[1]);
    if ($stmt->execute()) {
        echo "✅ Đã thêm chức năng: " . $func[0] . "<br>";
    }
}
$stmt->close();

echo "<br><strong>🎉 Hoàn tất! Tất cả các bảng đã được tạo và dữ liệu mẫu đã được thêm.</strong><br>";
echo "<br><a href='admin/functions.php'>→ Đi tới Quản Lý Chức Năng</a>";

$conn->close();
?>
