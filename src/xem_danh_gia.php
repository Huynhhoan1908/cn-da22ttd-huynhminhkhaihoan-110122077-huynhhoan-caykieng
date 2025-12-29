<?php
require_once 'connect.php';

echo "<h1>📊 DANH SÁCH ĐÁNH GIÁ</h1>";
echo "<style>
    body { font-family: Arial; padding: 20px; background: #f5f5f5; }
    table { width: 100%; border-collapse: collapse; background: white; margin: 20px 0; }
    th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
    th { background: #4CAF50; color: white; }
    tr:hover { background: #f5f5f5; }
    .empty { text-align: center; padding: 40px; color: #999; }
    .info { background: #e3f2fd; padding: 15px; border-radius: 5px; margin: 20px 0; }
    .btn { display: inline-block; padding: 10px 20px; background: #2196F3; color: white; text-decoration: none; border-radius: 5px; margin: 5px; }
    .btn:hover { background: #1976D2; }
</style>";

// Đếm số đánh giá
try {
    $count = $conn->query("SELECT COUNT(*) as total FROM danh_gia")->fetch();
    
    echo "<div class='info'>";
    echo "<strong>Tổng số đánh giá:</strong> <span style='font-size: 24px; color: #4CAF50;'>{$count['total']}</span>";
    echo "</div>";
    
    if ($count['total'] > 0) {
        // Lấy tất cả đánh giá
        $reviews = $conn->query("
            SELECT 
                dg.*,
                sp.ten_san_pham,
                sp.ma_san_pham,
                sp.hinh_anh
            FROM danh_gia dg
            LEFT JOIN san_pham sp ON dg.san_pham_id = sp.id
            ORDER BY dg.created_at DESC
        ");
        
        echo "<table>";
        echo "<tr>
            <th>ID</th>
            <th>Sản phẩm</th>
            <th>Người đánh giá</th>
            <th>Rating</th>
            <th>Nội dung</th>
            <th>Ngày tạo</th>
        </tr>";
        
        while ($row = $reviews->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr>";
            echo "<td>{$row['id']}</td>";
            echo "<td>
                <strong>{$row['ten_san_pham']}</strong><br>
                <small>Mã: {$row['ma_san_pham']}</small>
            </td>";
            echo "<td>
                <strong>{$row['user_name']}</strong><br>
                <small>{$row['user_email']}</small><br>
                <small>User ID: {$row['user_id']}</small>
            </td>";
            echo "<td>" . str_repeat('⭐', $row['rating']) . "</td>";
            echo "<td>" . htmlspecialchars($row['comment']) . "</td>";
            echo "<td>" . date('d/m/Y H:i', strtotime($row['created_at'])) . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    } else {
        echo "<div class='empty'>
            <h2>⚠️ CHƯA CÓ ĐÁNH GIÁ NÀO!</h2>
            <p>Bảng 'danh_gia' đang trống.</p>
        </div>";
    }
    
} catch (Exception $e) {
    echo "<div style='background: #ffebee; color: #c62828; padding: 20px; border-radius: 5px;'>";
    echo "<strong>❌ LỖI:</strong><br>";
    echo $e->getMessage();
    echo "</div>";
}

echo "<hr>";
echo "<a href='don_hang_cua_toi.php' class='btn'>← Đơn hàng của tôi</a>";
echo "<a href='qtvtrangchu.php#danh-gia' class='btn'>→ Trang quản trị</a>";
echo "<a href='test_gui_danh_gia.php' class='btn'>🧪 Test gửi đánh giá</a>";
?>
