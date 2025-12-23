<?php
header('Content-Type: application/json; charset=utf-8');

try {
    // Kết nối database
    require_once 'connect.php';
    
    // Check if table exists
    $check = $conn->query("SHOW TABLES LIKE 'khuyen_mai'");
    if ($check->rowCount() == 0) {
        echo json_encode([
            'success' => true,
            'promos' => [],
            'message' => 'Chưa có bảng khuyến mãi'
        ]);
        exit;
    }
    
    // Lấy danh sách mã khuyến mãi còn hiệu lực
    $now = date('Y-m-d H:i:s');
    $stmt = $conn->prepare("
        SELECT 
            ma_khuyen_mai,
            ten_khuyen_mai,
            mo_ta,
            loai_giam,
            gia_tri_giam,
            gia_tri_don_toi_thieu,
            gia_tri_giam_toi_da,
            so_luong_ma,
            so_lan_da_dung,
            ngay_bat_dau,
            ngay_ket_thuc
        FROM khuyen_mai
        WHERE trang_thai = 1
        AND ngay_bat_dau <= ?
        AND ngay_ket_thuc >= ?
        AND (so_luong_ma IS NULL OR so_lan_da_dung < so_luong_ma)
        ORDER BY ngay_bat_dau DESC
    ");
    $stmt->execute([$now, $now]);
    $promos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format data
    $formatted_promos = [];
    foreach ($promos as $promo) {
        $discount_text = '';
        if ($promo['loai_giam'] === 'phan_tram') {
            $discount_text = 'Giảm ' . number_format($promo['gia_tri_giam'], 0) . '%';
            if ($promo['gia_tri_giam_toi_da']) {
                $discount_text .= ' (tối đa ' . number_format($promo['gia_tri_giam_toi_da'], 0) . 'đ)';
            }
        } else {
            $discount_text = 'Giảm ' . number_format($promo['gia_tri_giam'], 0) . 'đ';
        }
        
        $condition_text = '';
        if ($promo['gia_tri_don_toi_thieu'] > 0) {
            $condition_text = 'Đơn từ ' . number_format($promo['gia_tri_don_toi_thieu'], 0) . 'đ';
        } else {
            $condition_text = 'Không giới hạn';
        }
        
        $quantity_text = '';
        if ($promo['so_luong_ma']) {
            $remaining = $promo['so_luong_ma'] - $promo['so_lan_da_dung'];
            $quantity_text = 'Còn ' . $remaining . ' mã';
        } else {
            $quantity_text = 'Không giới hạn';
        }
        
        $formatted_promos[] = [
            'code' => $promo['ma_khuyen_mai'],
            'name' => $promo['ten_khuyen_mai'],
            'description' => $promo['mo_ta'],
            'discount_text' => $discount_text,
            'condition_text' => $condition_text,
            'quantity_text' => $quantity_text,
            'end_date' => date('d/m/Y', strtotime($promo['ngay_ket_thuc']))
        ];
    }
    
    echo json_encode([
        'success' => true,
        'promos' => $formatted_promos
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Không thể tải danh sách mã khuyến mãi',
        'error' => $e->getMessage()
    ]);
}
