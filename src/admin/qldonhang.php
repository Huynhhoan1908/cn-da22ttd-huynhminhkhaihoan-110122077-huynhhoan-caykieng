<?php
require_once 'config.php';

$current_page = 'orders';
$page_title = 'Quản Lý Đơn Hàng - HuynhHoan';

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_status') {
        $id = (int)($_POST['order_id'] ?? 0);
        $status = trim($_POST['status'] ?? '');
        $stmt = $conn->prepare("UPDATE don_hang SET trang_thai = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $id);
        if ($stmt->execute()) {
            // Lấy thông tin đơn hàng và user để gửi thông báo
            $orderInfo = $conn->query("SELECT dh.*, nd.email, nd.id as user_id FROM don_hang dh LEFT JOIN nguoi_dung nd ON dh.nguoi_dung_id = nd.id WHERE dh.id = $id");
            $order = $orderInfo ? $orderInfo->fetch_assoc() : null;
            if ($order && file_exists(dirname(__DIR__) . '/notification_helpers.php')) {
                require_once dirname(__DIR__) . '/notification_helpers.php';
                // Ghi log debug trước khi gọi notify_order_status_user
                file_put_contents(dirname(__DIR__) . '/debug_goi_thong_bao.txt',
                    date('Y-m-d H:i:s') . " | Gọi notify_order_status_user với user_id: {$order['user_id']} | ma_don_hang: {$order['ma_don_hang']} | status: $status\n",
                    FILE_APPEND
                );
                try {
                    notify_order_status_user($order['user_id'], $order['ma_don_hang'], $status);
                } catch (Exception $ex) { error_log('Lỗi gửi notification trạng thái đơn: ' . $ex->getMessage()); }
            }
            echo json_encode(['success' => true, 'message' => 'Cập nhật thành công']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Lỗi cập nhật']);
        }
        exit();
    }
    if ($_POST['action'] === 'delete_order') {
        $id = (int)($_POST['order_id'] ?? 0);
        // Delete order items first
        $conn->query("DELETE FROM chi_tiet_don_hang WHERE don_hang_id = $id");
        // Delete order
        if ($conn->query("DELETE FROM don_hang WHERE id = $id")) {
            echo json_encode(['success' => true, 'message' => 'Xóa thành công']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Lỗi xóa']);
        }
        exit();
    }
}

// Load orders
$orders = [];
$result = $conn->query("SELECT dh.*, nd.ho_ten AS ten_nguoi_dung 
                        FROM don_hang dh 
                        LEFT JOIN nguoi_dung nd ON dh.nguoi_dung_id = nd.id 
                        ORDER BY dh.ngay_dat DESC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }
}

include 'header.php';
?>

<div class="page-header">
    <h1>Quản Lý Đơn Hàng</h1>
    <div class="breadcrumb">
        <i class="fas fa-home"></i>
        <span>Dashboard</span>
        <i class="fas fa-chevron-right"></i>
        <span>Đơn Hàng</span>
    </div>
</div>

<div class="table-container">
    <div class="section-header">
        <h2>Danh Sách Đơn Hàng (<?php echo count($orders); ?>)</h2>
        <div style="display:flex;gap:1rem;">
            <select class="status-select" onchange="filterOrders(this.value)">
                <option value="">Tất cả trạng thái</option>
                <option value="Chờ xác nhận">Chờ xác nhận</option>
                <option value="Đã xác nhận">Đã xác nhận</option>
                <option value="Đang giao">Đang giao</option>
                <option value="Đã giao">Đã giao</option>
                <option value="Đã hủy">Đã hủy</option>
            </select>
        </div>
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Mã Đơn</th>
                <th>Khách Hàng</th>
                <th>Ngày Đặt</th>
                <th>Tổng Tiền</th>
                <th>Thanh Toán</th>
                <th>Trạng Thái</th>
                <th>Thao Tác</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($orders as $order): ?>
            <tr>
                <td><?php echo $order['id']; ?></td>
                <td><strong><?php echo htmlspecialchars($order['ma_don_hang']); ?></strong></td>
                <td><?php echo htmlspecialchars($order['ten_khach_hang'] ?? $order['ten_nguoi_dung'] ?? 'N/A'); ?></td>
                <td><?php echo date('d/m/Y H:i', strtotime($order['ngay_dat'])); ?></td>
                <td><strong><?php echo number_format($order['tong_thanh_toan'], 0, ',', '.'); ?>₫</strong></td>
                <td><?php echo $order['phuong_thuc_thanh_toan']; ?></td>
                <td>
                    <select class="status-select" onchange="updateStatus(<?php echo $order['id']; ?>, this.value)" <?php echo ($order['trang_thai'] == 'Đã giao' || $order['trang_thai'] == 'Đã hủy') ? 'disabled' : ''; ?>>
                        <option value="Chờ xác nhận" <?php echo $order['trang_thai'] == 'Chờ xác nhận' ? 'selected' : ''; ?>>Chờ xác nhận</option>
                        <option value="Đã xác nhận" <?php echo $order['trang_thai'] == 'Đã xác nhận' ? 'selected' : ''; ?>>Đã xác nhận</option>
                        <option value="Đang giao" <?php echo $order['trang_thai'] == 'Đang giao' ? 'selected' : ''; ?>>Đang giao</option>
                        <option value="Đã giao" <?php echo $order['trang_thai'] == 'Đã giao' ? 'selected' : ''; ?>>Đã giao</option>
                        <option value="Đã hủy" <?php echo $order['trang_thai'] == 'Đã hủy' ? 'selected' : ''; ?>>Đã hủy</option>
                    </select>
                </td>
                <td>
                    <button class="btn btn-info btn-sm" onclick="viewOrderDetail(<?php echo $order['id']; ?>)">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button class="btn btn-danger btn-sm" onclick="deleteOrder(<?php echo $order['id']; ?>)">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>


<!-- Modal chi tiết đơn hàng -->
<div class="modal" id="orderDetailModal" tabindex="-1" style="display:none;position:fixed;z-index:9999;left:0;top:0;width:100vw;height:100vh;background:rgba(0,0,0,0.4);align-items:center;justify-content:center;">
  <div style="background:#fff;padding:2rem;max-width:600px;width:90%;border-radius:8px;position:relative;">
    <button onclick="closeOrderDetailModal()" style="position:absolute;top:10px;right:10px;font-size:1.2rem;">&times;</button>
    <h3>Chi Tiết Đơn Hàng</h3>
    <div id="order-detail-content">
      <div style="text-align:center;padding:2rem;">Đang tải...</div>
    </div>
  </div>
</div>

<script>
function updateStatus(orderId, status) {
    $.ajax({
        url: 'qldonhang.php',
        method: 'POST',
        data: {
            action: 'update_status',
            order_id: orderId,
            status: status
        },
        success: function(response) {
            if (response.success) {
                toastr.success(response.message);
            } else {
                toastr.error(response.message);
            }
            location.reload();
        },
        error: function() {
            location.reload();
        }
    });
}

function deleteOrder(orderId) {
    if (!confirm('Bạn có chắc muốn xóa đơn hàng này?')) return;
    
    $.ajax({
        url: 'qldonhang.php',
        method: 'POST',
        data: {
            action: 'delete_order',
            order_id: orderId
        },
        success: function(response) {
            if (response.success) {
                toastr.success(response.message);
                location.reload();
            } else {
                toastr.error(response.message);
            }
        }
    });
}

function filterOrders(status) {
    const rows = document.querySelectorAll('.data-table tbody tr');
    rows.forEach(row => {
        // Get the status from the 7th cell (index 6)
        const statusCell = row.querySelector('td:nth-child(7) select');
        const currentStatus = statusCell ? statusCell.value : '';
        if (status === '' || currentStatus === status) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

function viewOrderDetail(orderId) {
    document.getElementById('orderDetailModal').style.display = 'flex';
    document.getElementById('order-detail-content').innerHTML = '<div style="text-align:center;padding:2rem;">Đang tải...</div>';
    $.ajax({
        url: 'ajax_get_order_detail.php',
        method: 'POST',
        data: { order_id: orderId },
        dataType: 'html',
        success: function(html) {
            document.getElementById('order-detail-content').innerHTML = html;
        },
        error: function() {
            document.getElementById('order-detail-content').innerHTML = '<div style="color:red;">Không thể tải chi tiết đơn hàng.</div>';
        }
    });
}

function closeOrderDetailModal() {
    document.getElementById('orderDetailModal').style.display = 'none';
}
</script>

<?php include 'footer.php'; ?>