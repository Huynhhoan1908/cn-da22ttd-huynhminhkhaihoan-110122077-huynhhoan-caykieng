<?php
// admin/api_kho_hang.php
header('Content-Type: application/json; charset=utf-8');
error_reporting(0); // Tắt báo lỗi HTML để tránh làm hỏng JSON
ini_set('display_errors', 0);

require_once '../connect.php';

// Hàm helper trả về JSON
function sendJson($success, $message, $data = null) {
    echo json_encode(['success' => $success, 'message' => $message, 'data' => $data]);
    exit;
}

$action = isset($_GET['action']) ? $_GET['action'] : '';

try {
    // Router xử lý các hành động
    switch ($action) {
        case 'get_products':
            getProducts($conn);
            break;
        case 'get_statistics':
            getStatistics($conn);
            break;
        case 'import_stock':
            importStock($conn);
            break;
        case 'adjust_stock': // Xuất/Điều chỉnh kho
            adjustStock($conn);
            break;
        case 'add_product':
            addProduct($conn);
            break;
        case 'delete_product':
            deleteProduct($conn);
            break;
        case 'update_product':
            updateProduct($conn);
            break;
        case 'get_categories':
            getCategories($conn);
            break;
        case 'get_history': // Lấy lịch sử
            getHistory($conn);
            break;
        default:
            sendJson(false, 'Action không hợp lệ');
    }
} catch (Exception $e) {
    sendJson(false, 'Lỗi hệ thống: ' . $e->getMessage());
}

// =================================================================
// CÁC HÀM XỬ LÝ (CHUẨN PDO)
// =================================================================

function getProducts($conn) {
    $sql = "SELECT sp.id, sp.ma_san_pham, sp.ten_san_pham, sp.gia as gia_ban, sp.gia_nhap, sp.so_luong as ton_kho, sp.mo_ta, sp.hinh_anh FROM san_pham sp WHERE sp.trang_thai = 1 ORDER BY sp.id DESC";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($products as &$product) {
        // Lấy danh mục
        $stmt_cat = $conn->prepare("SELECT dm.id, dm.ten_san_pham FROM san_pham_danh_muc spdm JOIN danh_muc dm ON spdm.danh_muc_id = dm.id WHERE spdm.san_pham_id = ?");
        $stmt_cat->execute([$product['id']]);
        $categories = $stmt_cat->fetchAll(PDO::FETCH_ASSOC);
        
        $product['ten_danh_muc'] = !empty($categories) ? implode(', ', array_column($categories, 'ten_san_pham')) : 'Chưa phân loại';
        $product['danh_muc_ids'] = !empty($categories) ? array_column($categories, 'id') : [];
        $product['hinh_anh_url'] = ($product['hinh_anh']) ? '../uploads/' . $product['hinh_anh'] : 'https://via.placeholder.com/150?text=No+Image';
    }
    sendJson(true, 'Thành công', $products);
}

function getStatistics($conn) {
    $stmt = $conn->prepare("SELECT COUNT(*) as total_products, COALESCE(SUM(so_luong), 0) as total_stock, SUM(CASE WHEN so_luong < 5 THEN 1 ELSE 0 END) as low_stock_count FROM san_pham WHERE trang_thai = 1");
    $stmt->execute();
    sendJson(true, 'Ok', $stmt->fetch(PDO::FETCH_ASSOC));
}

function importStock($conn) {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = intval($data['product_id']);
    $qty = intval($data['quantity']);
    $note = $data['note'] ?? '';
    $price = isset($data['import_price']) && $data['import_price'] !== '' ? floatval($data['import_price']) : null;

    if ($qty <= 0) sendJson(false, 'Số lượng phải > 0');

    // Lấy thông tin cũ
    $stmt = $conn->prepare("SELECT so_luong, gia_nhap FROM san_pham WHERE id = ?");
    $stmt->execute([$id]);
    $prod = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$prod) sendJson(false, 'Không tìm thấy SP');

    $new_stock = $prod['so_luong'] + $qty;
    $gia_luu = ($price !== null && $price > 0) ? $price : $prod['gia_nhap'];

    // Cập nhật kho
    $sql = "UPDATE san_pham SET so_luong = ?, gia_nhap = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    
    if ($stmt->execute([$new_stock, $gia_luu, $id])) {
        // Ghi lịch sử NHẬP
        try {
            $hist = $conn->prepare("INSERT INTO lich_su_kho (san_pham_id, hanh_dong, so_luong, gia_nhap, ghi_chu) VALUES (?, 'nhap', ?, ?, ?)");
            $hist->execute([$id, $qty, $gia_luu, $note]);
        } catch (Exception $e) {}
        
        sendJson(true, "Đã nhập kho thành công");
    } else {
        sendJson(false, "Lỗi cập nhật database");
    }
}

function adjustStock($conn) {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = intval($data['product_id']);
    $qty = intval($data['quantity']);
    $reason = $data['reason'] ?? '';
    $note = $data['note'] ?? '';

    if ($qty <= 0) sendJson(false, 'Số lượng phải > 0');

    $stmt = $conn->prepare("SELECT so_luong, gia_nhap FROM san_pham WHERE id = ?");
    $stmt->execute([$id]);
    $prod = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($prod['so_luong'] < $qty) sendJson(false, 'Không đủ hàng để xuất/giảm');

    $new_stock = $prod['so_luong'] - $qty;
    $stmt = $conn->prepare("UPDATE san_pham SET so_luong = ? WHERE id = ?");
    
    if ($stmt->execute([$new_stock, $id])) {
        // Ghi lịch sử XUẤT/ĐIỀU CHỈNH
        try {
            $full_note = "$reason. $note";
            // Lưu ý: hanh_dong = 'dieu_chinh' hoặc 'xuat'
            $hist = $conn->prepare("INSERT INTO lich_su_kho (san_pham_id, hanh_dong, so_luong, gia_nhap, ghi_chu) VALUES (?, 'dieu_chinh', ?, ?, ?)");
            // Lưu số lượng âm hoặc dương tùy logic hiển thị, ở đây lưu số lượng thực tế bị trừ
            $hist->execute([$id, $qty, $prod['gia_nhap'], $full_note]);
        } catch (Exception $e) {}

        sendJson(true, "Đã điều chỉnh giảm kho thành công");
    }
}

function updateProduct($conn) {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = intval($data['id']);
    $ten = $data['ten_san_pham'];
    $gia_nhap = floatval($data['gia_nhap']);
    $cats = isset($data['danh_muc_ids']) ? $data['danh_muc_ids'] : [];

    $stmt = $conn->prepare("UPDATE san_pham SET ten_san_pham=?, gia_nhap=? WHERE id=?");
    $stmt->execute([$ten, $gia_nhap, $id]);

    $conn->prepare("DELETE FROM san_pham_danh_muc WHERE san_pham_id = ?")->execute([$id]);
    if (!empty($cats)) {
        $stmt_in = $conn->prepare("INSERT INTO san_pham_danh_muc (san_pham_id, danh_muc_id) VALUES (?, ?)");
        foreach ($cats as $c) $stmt_in->execute([$id, intval($c)]);
    }
    sendJson(true, 'Cập nhật thành công');
}

function deleteProduct($conn) {
    $data = json_decode(file_get_contents('php://input'), true);
    $stmt = $conn->prepare("UPDATE san_pham SET trang_thai = 0 WHERE id = ?");
    $stmt->execute([intval($data['id'])]);
    sendJson(true, 'Đã xóa sản phẩm');
}

function getCategories($conn) {
    $stmt = $conn->prepare("SELECT id, ten_san_pham as ten_danh_muc FROM danh_muc ORDER BY id");
    $stmt->execute();
    sendJson(true, 'Ok', $stmt->fetchAll(PDO::FETCH_ASSOC));
}

// HÀM LẤY LỊCH SỬ (Đã sửa để lấy cả NHẬP và XUẤT)
function getHistory($conn) {
    // Bỏ điều kiện WHERE hanh_dong = 'nhap' để lấy tất cả
    $sql = "SELECT h.*, s.ten_san_pham, s.hinh_anh 
            FROM lich_su_kho h 
            LEFT JOIN san_pham s ON h.san_pham_id = s.id 
            ORDER BY h.ngay_tao DESC LIMIT 50";
            
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Xử lý ảnh
    foreach ($data as &$row) {
        if (!empty($row['hinh_anh'])) {
            $row['hinh_anh'] = '../uploads/' . $row['hinh_anh'];
        } else {
            $row['hinh_anh'] = 'https://via.placeholder.com/50';
        }
    }
    
    sendJson(true, 'Ok', $data);
}
?>