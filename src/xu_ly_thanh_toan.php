<?php
session_start();
require_once 'connect.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

try {
    // Lấy thông tin từ POST
    $fullname = $_POST['fullname'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $email = $_POST['email'] ?? '';
    $address = $_POST['address'] ?? '';
    $city = $_POST['city'] ?? '';
    $payment = $_POST['payment'] ?? '';
    $note = $_POST['note'] ?? '';
    $total = floatval($_POST['total'] ?? 0);
    $promo_code = $_POST['promo_code'] ?? null;
    $discount = floatval($_POST['discount'] ?? 0);
    
    // Kiểm tra thông tin bắt buộc
    if (empty($fullname) || empty($phone) || empty($address) || empty($city) || empty($payment) || $total <= 0) {
        echo json_encode(['success' => false, 'message' => 'Thiếu thông tin đặt hàng']);
        exit();
    }
    
    // Lấy user_id nếu đã đăng nhập
    $user_id = $_SESSION['user_id'] ?? null;
    
    // Xác định nguồn đơn hàng: từ giỏ hàng session hoặc mua ngay
    $cart_items = [];
    
    if (!empty($_POST['product_id'])) {
        // Mua ngay từ trang chi tiết sản phẩm
        $cart_items = [[
            'id' => intval($_POST['product_id']),
            'name' => $_POST['product_name'] ?? '',
            'price' => floatval($_POST['product_price']),
            'quantity' => intval($_POST['quantity'] ?? 1)
        ]];
    } elseif (!empty($_POST['cart_items'])) {
        // Từ giỏ hàng (JSON string)
        $cart_items = json_decode($_POST['cart_items'], true);
        if (!is_array($cart_items)) {
            echo json_encode(['success' => false, 'message' => 'Dữ liệu giỏ hàng không hợp lệ']);
            exit();
        }
    } elseif (!empty($_SESSION['cart'])) {
        // Từ giỏ hàng session (legacy)
        $cart_items = $_SESSION['cart'];
    }
    
    if (empty($cart_items)) {
        echo json_encode(['success' => false, 'message' => 'Giỏ hàng trống']);
        exit();
    }
    
    // Bắt đầu transaction
    $conn->beginTransaction();
    
    // Tạo mã đơn hàng
    $ma_don_hang = 'DH' . date('YmdHis') . rand(100, 999);
    
    // Tính phí vận chuyển và tổng thanh toán
    $shipping_fee = 30000; // 30,000đ phí vận chuyển
    $tong_thanh_toan = $total; // Total đã bao gồm shipping và discount
    
    // Tạo đơn hàng
    $stmt = $conn->prepare("
        INSERT INTO don_hang (ma_don_hang, nguoi_dung_id, ten_khach_hang, so_dien_thoai, email, 
                             dia_chi, phuong_thuc_thanh_toan, ghi_chu, tong_tien, phi_van_chuyen, 
                             ma_khuyen_mai, giam_gia, tong_thanh_toan, trang_thai, ngay_dat) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Chờ xác nhận', NOW())
    ");
    
    // Tính lại tổng tiền sản phẩm (không bao gồm shipping)
    $subtotal = 0;
    foreach ($cart_items as $item) {
        $subtotal += floatval($item['price']) * intval($item['quantity']);
    }
    
    $full_address = $address . ', ' . $city;
    
    $stmt->execute([
        $ma_don_hang,
        $user_id,
        $fullname,
        $phone,
        $email ?: null,
        $full_address,
        $payment === 'cod' ? 'Thanh toán khi nhận hàng' : 'Chuyển khoản ngân hàng',
        $note ?: null,
        $subtotal,
        $shipping_fee,
        $promo_code,
        $discount,
        $tong_thanh_toan
    ]);
    
    // --- Đặt ngay sau khi đơn hàng được INSERT thành công ---
    $order_id = $conn->lastInsertId(); // Lấy ID của đơn hàng vừa tạo
    $customer_name = $fullname ?? '';
    $total_amount_formatted = number_format($tong_thanh_toan, 0, ',', '.');
    $message = "Đơn hàng mới #{$order_id} trị giá {$total_amount_formatted}₫ từ {$customer_name} vừa được đặt.";
    $link = "admin/qldonhang.php?order_id={$order_id}";
    $type = 'new_order';
    $stmt_noti = $conn->prepare("INSERT INTO admin_notifications (type, message, link) VALUES (?, ?, ?)");
    $stmt_noti->execute([$type, $message, $link]);
    
    // Thêm chi tiết đơn hàng
    $stmt_detail = $conn->prepare("
        INSERT INTO chi_tiet_don_hang (don_hang_id, san_pham_id, ten_san_pham, gia, so_luong, thanh_tien) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    
    foreach ($cart_items as $item) {
        $product_id = intval($item['id'] ?? $item['product_id'] ?? 0);
        $product_name = $item['name'] ?? $item['ten_san_pham'] ?? '';
        $quantity = intval($item['quantity'] ?? $item['so_luong'] ?? 1);
        $price = floatval($item['price'] ?? $item['gia'] ?? 0);
        $thanh_tien = $price * $quantity;
        
        if ($product_id > 0 && $quantity > 0 && $price > 0) {
            $stmt_detail->execute([$order_id, $product_id, $product_name, $price, $quantity, $thanh_tien]);
            
            // Cập nhật số lượng tồn kho
            $stmt_update = $conn->prepare("UPDATE san_pham SET so_luong = so_luong - ? WHERE id = ?");
            $stmt_update->execute([$quantity, $product_id]);
        }
    }
    
    // Commit transaction
    $conn->commit();
    
    // Xóa giỏ hàng session nếu có
    if (!empty($_SESSION['cart'])) {
        unset($_SESSION['cart']);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Đặt hàng thành công!',
        'order_id' => $order_id
    ]);
    
} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    
    error_log("Order error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
    ]);
}
?>