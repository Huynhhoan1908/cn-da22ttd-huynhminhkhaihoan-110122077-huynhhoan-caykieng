<?php
require_once 'config.php';

$order_id = (int)($_POST['order_id'] ?? 0);
if (!$order_id) {
    echo '<div style="color:red;">Thiếu mã đơn hàng.</div>';
    exit;
}

// Lấy thông tin đơn hàng

$sql = "SELECT dh.*, nd.ho_ten AS ten_nguoi_dung, nd.email FROM don_hang dh LEFT JOIN nguoi_dung nd ON dh.nguoi_dung_id = nd.id WHERE dh.id = ?";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo '<div style="color:red;">Lỗi truy vấn: ' . htmlspecialchars($conn->error) . '<br>SQL: ' . htmlspecialchars($sql) . '</div>';
    exit;
}
$stmt->bind_param("i", $order_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
if (!$order) {
    echo '<div style="color:red;">Không tìm thấy đơn hàng.</div>';
    exit;
}

// Lấy danh sách sản phẩm trong đơn hàng
$items = [];

$sql2 = "SELECT ctdh.*, sp.ten_san_pham FROM chi_tiet_don_hang ctdh LEFT JOIN san_pham sp ON ctdh.san_pham_id = sp.id WHERE ctdh.don_hang_id = ?";
$stmt2 = $conn->prepare($sql2);
if (!$stmt2) {
    echo '<div style=\'color:red;\'>Lỗi truy vấn: ' . htmlspecialchars($conn->error) . '<br>SQL: ' . htmlspecialchars($sql2) . '</div>';
    exit;
}
$stmt2->bind_param("i", $order_id);
$stmt2->execute();
$result2 = $stmt2->get_result();
while ($row = $result2->fetch_assoc()) {
    $items[] = $row;
}
?>
<div>
    <h4>Thông tin đơn hàng</h4>
    <ul>
        <li><b>Mã đơn hàng:</b> <?php echo htmlspecialchars($order['ma_don_hang']); ?></li>
        <li><b>Khách hàng:</b> <?php echo htmlspecialchars($order['ten_nguoi_dung'] ?? 'N/A'); ?></li>
        <li><b>Email:</b> <?php echo htmlspecialchars($order['email'] ?? ''); ?></li>
        <!-- <li><b>SĐT:</b> <?php // echo htmlspecialchars($order['sdt'] ?? ''); ?></li> -->
        <!-- <li><b>Địa chỉ:</b> <?php // echo htmlspecialchars($order['dia_chi'] ?? ''); ?></li> -->
        <li><b>Ngày đặt:</b> <?php echo date('d/m/Y H:i', strtotime($order['ngay_dat'])); ?></li>
        <li><b>Phương thức thanh toán:</b> <?php echo htmlspecialchars($order['phuong_thuc_thanh_toan']); ?></li>
        <li><b>Trạng thái:</b> <?php echo htmlspecialchars($order['trang_thai']); ?></li>
        <li><b>Tổng thanh toán:</b> <strong><?php echo number_format($order['tong_thanh_toan'], 0, ',', '.'); ?>₫</strong></li>
    </ul>
    <h4>Sản phẩm trong đơn hàng</h4>
    <table style="width:100%;border-collapse:collapse;font-size:1rem;box-shadow:0 2px 8px #eee;">
        <thead>
            <tr style="background:#4CAF50;color:#fff;">
                <th style="border:1px solid #e0e0e0;padding:10px 8px;">Tên sản phẩm</th>
                <th style="border:1px solid #e0e0e0;padding:10px 8px;">Số lượng</th>
                <th style="border:1px solid #e0e0e0;padding:10px 8px;">Đơn giá</th>
                <th style="border:1px solid #e0e0e0;padding:10px 8px;">Thành tiền</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $item): ?>
            <tr style="background:#fff;transition:background 0.2s;">
                <td style="border:1px solid #e0e0e0;padding:8px;">
                    <?php echo htmlspecialchars($item['ten_san_pham'] ?? ''); ?>
                </td>
                <td style="border:1px solid #e0e0e0;padding:8px;text-align:center;">
                    <?php echo $item['so_luong']; ?>
                </td>
                <td style="border:1px solid #e0e0e0;padding:8px;text-align:right;">
                    <?php echo number_format($item['gia'], 0, ',', '.'); ?>₫
                </td>
                <td style="border:1px solid #e0e0e0;padding:8px;text-align:right;">
                    <?php echo number_format($item['thanh_tien'], 0, ',', '.'); ?>₫
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
