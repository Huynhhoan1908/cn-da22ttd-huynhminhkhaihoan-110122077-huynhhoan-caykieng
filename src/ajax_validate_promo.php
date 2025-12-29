<?php
require_once __DIR__ . '/connect.php';

header('Content-Type: application/json');

// Debug logging
error_log("=== AJAX VALIDATE PROMO DEBUG ===");
error_log("POST: " . print_r($_POST, true));
error_log("Raw input: " . file_get_contents('php://input'));

// Accept both parameter names for compatibility
$promo_code = $_POST['promo_code'] ?? $_POST['ma_khuyen_mai'] ?? '';
$cart_items = $_POST['cart_items'] ?? $_POST['gio_hang'] ?? '';

error_log("Parsed promo_code: '$promo_code'");
error_log("Parsed cart_items: '$cart_items'");

if (empty($promo_code) || empty($cart_items)) {
    error_log("ERROR: Missing data - promo_code empty: " . (empty($promo_code) ? 'YES' : 'NO') . ", cart_items empty: " . (empty($cart_items) ? 'YES' : 'NO'));
    echo json_encode(['success' => false, 'message' => 'Thiếu thông tin', 'debug' => ['promo' => $promo_code, 'cart' => $cart_items]]);
    exit;
}

$ma_khuyen_mai = strtoupper(trim($promo_code));
$gio_hang = json_decode($cart_items, true);

if (empty($ma_khuyen_mai) || empty($gio_hang)) {
    echo json_encode(['success' => false, 'message' => 'Mã khuyến mãi hoặc giỏ hàng không hợp lệ']);
    exit;
}

try {
    // Lấy thông tin mã khuyến mãi
    $stmt = $conn->prepare("
        SELECT * FROM khuyen_mai 
        WHERE ma_khuyen_mai = ? 
        AND trang_thai = 1
        AND ngay_bat_dau <= NOW() 
        AND ngay_ket_thuc >= NOW()
    ");
    $stmt->execute([$ma_khuyen_mai]);
    $promo = $stmt->fetch();
    
    if (!$promo) {
        echo json_encode(['success' => false, 'message' => 'Mã khuyến mãi không tồn tại hoặc đã hết hạn']);
        exit;
    }
    
    // Kiểm tra số lượng mã còn lại
    if ($promo['so_luong_ma']) {
        $stmt = $conn->prepare("SELECT COUNT(*) as used FROM lich_su_khuyen_mai WHERE khuyen_mai_id = ?");
        $stmt->execute([$promo['id']]);
        $used = $stmt->fetch()['used'];
        
        if ($used >= $promo['so_luong_ma']) {
            echo json_encode(['success' => false, 'message' => 'Mã khuyến mãi đã hết lượt sử dụng']);
            exit;
        }
    }
    
    // Tính tổng tiền giỏ hàng
    $tong_tien = 0;
    $product_ids = [];
    
    foreach ($gio_hang as $item) {
        // Support both Vietnamese and English field names
        $item_id = $item['id'] ?? $item['product_id'] ?? 0;
        $item_qty = $item['quantity'] ?? $item['so_luong'] ?? 1;
        
        $product_ids[] = (int)$item_id;
        
        // Lấy giá từ database để tránh gian lận
        $stmt = $conn->prepare("SELECT gia FROM san_pham WHERE id = ?");
        $stmt->execute([(int)$item_id]);
        $product = $stmt->fetch();
        
        if ($product) {
            $tong_tien += (float)$product['gia'] * (int)$item_qty;
        }
    }
    
    // Kiểm tra giá trị đơn tối thiểu
    if ($tong_tien < $promo['gia_tri_don_toi_thieu']) {
        echo json_encode([
            'success' => false, 
            'message' => 'Đơn hàng tối thiểu ' . number_format($promo['gia_tri_don_toi_thieu'], 0, ',', '.') . 'đ để áp dụng mã này'
        ]);
        exit;
    }
    
    // Kiểm tra loại áp dụng
    $can_apply = false;
    
    if ($promo['loai_ap_dung'] === 'tat_ca') {
        $can_apply = true;
    } elseif ($promo['loai_ap_dung'] === 'danh_muc' && !empty($product_ids)) {
        // Kiểm tra xem có sản phẩm nào thuộc danh mục được khuyến mãi không
        $placeholders = implode(',', array_fill(0, count($product_ids), '?'));
        $stmt = $conn->prepare("
            SELECT COUNT(DISTINCT sp.id) as count
            FROM san_pham sp
            INNER JOIN khuyen_mai_danh_muc kmdm ON sp.danh_muc_id = kmdm.danh_muc_id
            WHERE kmdm.khuyen_mai_id = ? 
            AND sp.id IN ($placeholders)
        ");
        $params = array_merge([$promo['id']], $product_ids);
        $stmt->execute($params);
        $count = $stmt->fetch()['count'];
        
        $can_apply = $count > 0;
        
        if (!$can_apply) {
            echo json_encode(['success' => false, 'message' => 'Mã này chỉ áp dụng cho một số danh mục sản phẩm cụ thể']);
            exit;
        }
    } elseif ($promo['loai_ap_dung'] === 'san_pham' && !empty($product_ids)) {
        // Kiểm tra xem có sản phẩm nào được khuyến mãi không
        $placeholders = implode(',', array_fill(0, count($product_ids), '?'));
        $stmt = $conn->prepare("
            SELECT COUNT(*) as count
            FROM khuyen_mai_san_pham
            WHERE khuyen_mai_id = ? 
            AND san_pham_id IN ($placeholders)
        ");
        $params = array_merge([$promo['id']], $product_ids);
        $stmt->execute($params);
        $count = $stmt->fetch()['count'];
        
        $can_apply = $count > 0;
        
        if (!$can_apply) {
            echo json_encode(['success' => false, 'message' => 'Mã này chỉ áp dụng cho một số sản phẩm cụ thể']);
            exit;
        }
    }
    
    // Tính giá trị giảm
    $gia_tri_giam = 0;
    
    if ($promo['loai_giam'] === 'phan_tram') {
        $gia_tri_giam = $tong_tien * ($promo['gia_tri_giam'] / 100);
    } else {
        $gia_tri_giam = $promo['gia_tri_giam'];
    }
    
    // Áp dụng giảm tối đa (nếu có)
    if ($promo['gia_tri_giam_toi_da'] && $gia_tri_giam > $promo['gia_tri_giam_toi_da']) {
        $gia_tri_giam = $promo['gia_tri_giam_toi_da'];
    }
    
    // Đảm bảo giảm không vượt quá tổng tiền
    if ($gia_tri_giam > $tong_tien) {
        $gia_tri_giam = $tong_tien;
    }
    
    $tong_sau_giam = $tong_tien - $gia_tri_giam;
    
    echo json_encode([
        'success' => true,
        'message' => 'Áp dụng mã thành công!',
        'promo' => [
            'id' => $promo['id'],
            'ma' => $promo['ma_khuyen_mai'],
            'ten' => $promo['ten_khuyen_mai'],
            'loai_giam' => $promo['loai_giam'],
            'gia_tri_giam_hien_thi' => $promo['loai_giam'] === 'phan_tram' ? $promo['gia_tri_giam'] . '%' : number_format($promo['gia_tri_giam'], 0, ',', '.') . 'đ'
        ],
        'tong_tien' => $tong_tien,
        'gia_tri_giam' => $gia_tri_giam,
        'tong_sau_giam' => $tong_sau_giam
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
}
