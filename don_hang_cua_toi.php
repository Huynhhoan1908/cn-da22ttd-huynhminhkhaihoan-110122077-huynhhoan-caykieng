<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'connect.php';

// DEBUG: Log session và orders ra file log
file_put_contents(__DIR__ . '/debug_session.log',
    "==== " . date('Y-m-d H:i:s') . " ====" . PHP_EOL .
    '$_SESSION: ' . print_r($_SESSION, true) . PHP_EOL,
    FILE_APPEND
);

// Sau khi lấy $orders:
// (Chèn sau đoạn lấy $orders, nhưng ở đây log tạm đầu file để kiểm tra session trước)

// AJAX handler for cancel order
if (isset($_POST['cancel_order'])) {
    header('Content-Type: application/json');
    $order_id = (int)$_POST['order_id'];
    $user_id = $_SESSION['user_id'] ?? null;
    $user_email = $_SESSION['email'] ?? null;
    
    try {
        // Kiểm tra đơn hàng có thuộc về user không
        if ($user_id) {
            $stmt = $conn->prepare("SELECT trang_thai FROM don_hang WHERE id = ? AND nguoi_dung_id = ?");
            $stmt->execute([$order_id, $user_id]);
        } elseif ($user_email) {
            $stmt = $conn->prepare("SELECT trang_thai FROM don_hang WHERE id = ? AND email = ?");
            $stmt->execute([$order_id, $user_email]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Không có quyền truy cập']);
            exit;
        }
        
        $order = $stmt->fetch();
        
        if (!$order) {
            echo json_encode(['success' => false, 'message' => 'Không tìm thấy đơn hàng']);
            exit;
        }
        
        // Chỉ cho phép hủy đơn hàng có trạng thái "Chờ xác nhận"
        $status = trim($order['trang_thai']);
        if ($status !== 'Chờ xác nhận') {
            echo json_encode(['success' => false, 'message' => 'Không thể hủy đơn hàng đã được xác nhận']);
            exit;
        }
        
        // Cập nhật trạng thái thành "Đã hủy"
        $updateStmt = $conn->prepare("UPDATE don_hang SET trang_thai = 'Đã hủy' WHERE id = ?");
        $updateStmt->execute([$order_id]);
        
        echo json_encode(['success' => true, 'message' => 'Đã hủy đơn hàng thành công']);
        exit;
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }
}

// AJAX handler for order details (phải xử lý trước khi output HTML)
if (isset($_GET['get_order_details'])) {
    header('Content-Type: application/json');
    $order_id = (int)$_GET['get_order_details'];
    $user_id = $_SESSION['user_id'] ?? null;
    $user_email = $_SESSION['email'] ?? null;
    
    try {
        // Lấy thông tin đơn hàng
        if ($user_id) {
            $stmt = $conn->prepare("SELECT * FROM don_hang WHERE id = ? AND nguoi_dung_id = ?");
            $stmt->execute([$order_id, $user_id]);
        } elseif ($user_email) {
            $stmt = $conn->prepare("SELECT * FROM don_hang WHERE id = ? AND email = ?");
            $stmt->execute([$order_id, $user_email]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Không có quyền truy cập']);
            exit;
        }
        
        $order = $stmt->fetch();
        
        if (!$order) {
            echo json_encode(['success' => false, 'message' => 'Không tìm thấy đơn hàng']);
            exit;
        }
        
        // Lấy chi tiết sản phẩm
        $stmt = $conn->prepare("
            SELECT cd.*, sp.ten_san_pham, sp.gia
            FROM chi_tiet_don_hang cd
            LEFT JOIN san_pham sp ON cd.san_pham_id = sp.id
            WHERE cd.don_hang_id = ?
        ");
        $stmt->execute([$order_id]);
        $items = $stmt->fetchAll();
        
        // Lấy đánh giá cho từng sản phẩm (nếu có)
        $reviews = [];
        if ($user_id || $user_email) {
            foreach ($items as $item) {
                $reviewStmt = $conn->prepare("
                    SELECT rating, comment, admin_reply, created_at
                    FROM danh_gia 
                    WHERE san_pham_id = ? 
                    AND (user_id = ? OR user_email = ?)
                    LIMIT 1
                ");
                $reviewStmt->execute([$item['san_pham_id'], $user_id, $user_email]);
                $review = $reviewStmt->fetch();
                if ($review) {
                    $reviews[$item['san_pham_id']] = $review;
                }
            }
        }
        
        echo json_encode([
            'success' => true,
            'order' => $order,
            'items' => $items,
            'reviews' => $reviews
        ]);
        exit;
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }
}


// Lấy thông tin user
$user_id = $_SESSION['user_id'] ?? null;
$user_email = $_SESSION['email'] ?? null;

// Lấy danh sách đơn hàng (CHỈ KHÁCH HÀNG)
$orders = [];

try {
    $table_check = $conn->query("SHOW TABLES LIKE 'don_hang'");
    if ($table_check->rowCount() > 0) {
        if ($user_id && $user_email) {
            // Có cả user_id và email: Tìm theo CẢ HAI
            $stmt = $conn->prepare("SELECT * FROM don_hang WHERE nguoi_dung_id = ? OR email = ? ORDER BY ngay_dat DESC");
            $stmt->execute([$user_id, $user_email]);
        } elseif ($user_id) {
            // Chỉ có user_id: Tìm theo user_id
            $stmt = $conn->prepare("SELECT * FROM don_hang WHERE nguoi_dung_id = ? ORDER BY ngay_dat DESC");
            $stmt->execute([$user_id]);
        } elseif ($user_email) {
            // Chỉ có email: Tìm theo email
            $stmt = $conn->prepare("SELECT * FROM don_hang WHERE email = ? ORDER BY ngay_dat DESC");
            $stmt->execute([$user_email]);
        } else {
            $stmt = null;
        }
        
        if ($stmt) {
            $orders = $stmt->fetchAll();
            // DEBUG: Log orders ra file log
            file_put_contents(__DIR__ . '/debug_orders.log',
                "==== " . date('Y-m-d H:i:s') . " ====" . PHP_EOL .
                'orders: ' . print_r($orders, true) . PHP_EOL,
                FILE_APPEND
            );
        }
    }
} catch (PDOException $e) {
    error_log("Error loading orders: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đơn Hàng Của Tôi - HuynhHoan</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
            /* CHUÔNG THÔNG BÁO ĐỒNG BỘ GIAO DIỆN (giống hình san-pham.php) */
            .icon-btn {
                position: relative;
                background: none;
                border: none;
                outline: none;
                cursor: pointer;
                border-radius: 50%;
                width: 40px;
                height: 40px;
                display: flex;
                align-items: center;
                justify-content: center;
                transition: box-shadow 0.2s, background 0.2s;
            }
            .icon-btn:focus, .icon-btn.active, .icon-btn:hover {
                background: #f4f8f4;
                box-shadow: 0 0 0 2px #c8d96f33;
            }
            .icon-btn i {
                font-size: 1.3rem;
                color: #316339ff !important;
            }
            .noti-badge {
                position: absolute;
                top: 7px;
                right: 7px;
                background: #dc3545;
                color: #fff;
                font-size: 12px;
                font-weight: bold;
                border-radius: 999px;
                padding: 0 6px;
                min-width: 18px;
                height: 18px;
                line-height: 18px;
                text-align: center;
                z-index: 2;
                display: none;
                box-shadow: 0 2px 6px rgba(220,53,69,0.12);
            }
            .noti-dropdown {
                display: none;
                position: absolute;
                right: 0;
                top: 48px;
                min-width: 340px;
                background: #fff;
                border-radius: 16px;
                box-shadow: 0 8px 32px rgba(0,0,0,0.13);
                border: 1px solid #eee;
                z-index: 100;
                overflow: hidden;
                animation: fadeIn 0.2s;
            }
            .noti-dropdown.active { display: block; }
            .noti-header {
                background: #fff;
                padding: 18px 20px 14px 20px;
                font-weight: bold;
                border-bottom: 1px solid #eee;
                color: #222;
                font-size: 1.1rem;
                letter-spacing: 0.01em;
            }
            .noti-item {
                display: block;
                padding: 16px 20px 10px 20px;
                border-bottom: 1px solid #f1f1f1;
                text-decoration: none !important;
                transition: background 0.2s;
                border-radius: 0;
            }
            .noti-item:last-child { border-bottom: none; }
            .noti-item:hover { background-color: #f0fdf4; }
            .noti-item h4 {
                margin: 0 0 5px 0;
                font-size: 15px;
                font-weight: 700;
                color: #2e8b57;
                line-height: 1.3;
                display: flex;
                align-items: center;
                gap: 6px;
            }
            .noti-item p {
                margin: 0;
                font-size: 13px;
                color: #555;
                line-height: 1.5;
            }
            .noti-item small {
                display: block;
                margin-top: 5px;
                font-size: 11px;
                color: #999;
            }
            @media (max-width: 600px) {
                .noti-dropdown { min-width: 95vw; right: 0; left: 0; margin: 0 auto; }
            }
            @keyframes fadeIn {
                from { opacity: 0; transform: translateY(-10px); }
                to { opacity: 1; transform: translateY(0); }
            }
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            /* Logo-Inspired Theme - Xanh lá vàng gradient */
            --stone-50: #fffef5;
            --stone-100: #fdfbe8;
            --stone-200: #f5f2d4;
            --stone-300: #e8edc7;
            --stone-400: #c8d96f;
            --stone-500: #9bc26f;
            --stone-600: #7fa84e;
            --stone-700: #5a7a4f;
            --stone-800: #3d6b3f;
            --stone-900: #1d3e1f;
            --white: #ffffff;
            --rose-500: #9bc26f;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: #fffef5;
            color: #1d3e1f;
            min-height: 100vh;
            padding: 0;
            margin: 0;
        }

        /* Announcement Bar */
        .announcement-bar {
            background-color: #1d3e1f;
            color: #fdfbe8;
            padding: 0.625rem 0;
            text-align: center;
            font-size: 0.875rem;
        }
        .announcement-bar p {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            margin: 0;
        }

        /* Header */
        .header {
            position: sticky;
            top: 0;
            z-index: 40;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--stone-200);
        }
        .header .header-container {
            max-width: 1280px;
            margin: 0 auto;
            padding: 0 1rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            height: 4rem;
        }
        .brand-logo {
            font-size: 1.5rem;
            font-weight: 600;
            letter-spacing: -0.025em;
            color: var(--stone-900);
            text-decoration: none;
        }
        .nav {
            display: none;
            align-items: center;
            gap: 2rem;
        }
        @media (min-width: 1024px) {
            .nav { display: flex; }
        }
        .nav a {
            font-size: 0.875rem;
            color: #5a7a4f;
            transition: all 0.3s ease;
            padding: 0.625rem 1.25rem;
            border-radius: 25px;
            border: 2px solid #f5f2d4;
            font-weight: 500;
            position: relative;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: white;
            text-decoration: none;
        }
        .nav a:hover {
            color: #3d6b3f;
            background: #fdfbe8;
            border-color: #e8edc7;
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(61,107,63,0.08);
        }
        .nav a.active {
            color: #1d3e1f;
            background: #fdfbe8;
            border-color: #7fa84e;
            font-weight: 600;
            box-shadow: 0 2px 12px rgba(61,107,63,0.12);
        }
        .header-actions {
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .icon-btn {
            width: 2.5rem;
            height: 2.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #7fa84e;
            border-radius: 0.5rem;
            transition: all 0.2s;
            position: relative;
            text-decoration: none;
        }
        .icon-btn:hover {
            color: #3d6b3f;
            background-color: #fdfbe8;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .page-header {
            background: white;
            padding: 2rem;
            border-radius: 16px;
            box-shadow: 0 4px 12px rgba(29, 62, 31, 0.08);
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .page-title {
            display: flex;
            align-items: center;
            gap: 1rem;
            color: var(--stone-900);
        }

        .page-title h1 {
            font-size: 2rem;
            font-weight: 700;
        }

        .page-title .icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, var(--stone-600), var(--stone-500));
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
        }

        .header-actions {
            display: flex;
            gap: 1rem;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            border: none;
            cursor: pointer;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--berry), var(--coral));
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(166, 70, 116, 0.3);
        }

        .btn-secondary {
            background: white;
            color: var(--indigo);
            border: 2px solid var(--indigo);
        }

        .btn-secondary:hover {
            background: var(--indigo);
            color: white;
        }

        .user-info {
            background: linear-gradient(135deg, var(--beige-1), var(--beige-2));
            padding: 1rem 1.5rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .user-info i {
            font-size: 1.5rem;
            color: var(--indigo);
        }

        .user-info .info {
            flex: 1;
        }

        .user-info .username {
            font-weight: 700;
            color: var(--indigo);
            font-size: 1.1rem;
        }

        .user-info .email {
            color: #6b7280;
            font-size: 0.9rem;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            text-align: center;
        }

        .stat-card .number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--coral);
            margin-bottom: 0.5rem;
        }

        .stat-card .label {
            color: #6b7280;
            font-size: 0.9rem;
        }

        .orders-section {
            background: white;
            padding: 2rem;
            border-radius: 16px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .section-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--indigo);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: #6b7280;
        }

        .empty-state i {
            font-size: 5rem;
            margin-bottom: 1rem;
            opacity: 0.3;
        }

        .empty-state h3 {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }

        .order-card {
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            transition: all 0.3s;
        }

        .order-card:hover {
            border-color: var(--berry);
            box-shadow: 0 4px 12px rgba(166, 70, 116, 0.15);
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #e5e7eb;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .order-code {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--indigo);
        }

        .order-code i {
            margin-right: 0.5rem;
            color: var(--berry);
        }

        .order-date {
            color: #6b7280;
            font-size: 0.9rem;
        }

        .order-date i {
            margin-right: 0.5rem;
        }

        .order-body {
            margin-bottom: 1rem;
        }

        .order-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .info-item {
            display: flex;
            align-items: flex-start;
            gap: 0.5rem;
        }

        .info-item i {
            color: var(--berry);
            margin-top: 0.25rem;
        }

        .info-item .value {
            flex: 1;
            color: var(--indigo);
        }

        .info-item .label {
            font-size: 0.85rem;
            color: #6b7280;
        }

        .order-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .status-badge.pending {
            background: #fef3c7;
            color: #92400e;
        }

        .status-badge.confirmed {
            background: #dbeafe;
            color: #1e40af;
        }

        .status-badge.shipping {
            background: #e0e7ff;
            color: #4338ca;
        }

        .status-badge.delivered {
            background: #d1fae5;
            color: #065f46;
        }

        .status-badge.cancelled {
            background: #fee2e2;
            color: #991b1b;
        }

        .order-total {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--coral);
        }

        .order-actions {
            display: flex;
            gap: 0.5rem;
        }

        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.9rem;
        }

        .btn-outline {
            background: white;
            color: var(--berry);
            border: 2px solid var(--berry);
        }

        .btn-outline:hover {
            background: var(--berry);
            color: white;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: white;
            border-radius: 16px;
            max-width: 800px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
            padding: 2rem;
            position: relative;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #e5e7eb;
        }

        .modal-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--indigo);
        }

        .close-btn {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: none;
            background: #f3f4f6;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
        }

        .close-btn:hover {
            background: var(--coral);
            color: white;
        }

        .order-items {
            margin-top: 1.5rem;
        }

        .item-card {
            display: flex;
            gap: 1rem;
            padding: 1rem;
            background: #f9fafb;
            border-radius: 8px;
            margin-bottom: 1rem;
        }

        .item-info {
            flex: 1;
        }

        .item-name {
            font-weight: 600;
            color: var(--indigo);
            margin-bottom: 0.25rem;
        }

        .item-details {
            color: #6b7280;
            font-size: 0.875rem;
        }

        .item-price {
            font-weight: 700;
            color: var(--coral);
            white-space: nowrap;
        }

        .order-summary {
            margin-top: 1.5rem;
            padding: 1.5rem;
            background: linear-gradient(135deg, var(--beige-1), var(--beige-2));
            border-radius: 12px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.75rem;
            color: var(--indigo);
        }

        .summary-row.total {
            font-size: 1.25rem;
            font-weight: 700;
            border-top: 2px solid rgba(61, 68, 87, 0.2);
            padding-top: 0.75rem;
            margin-top: 0.75rem;
        }

        @media (max-width: 768px) {
            .page-header {
                text-align: center;
            }

            .page-title {
                flex-direction: column;
            }

            .order-header,
            .order-footer {
                flex-direction: column;
                align-items: flex-start;
            }

            .order-actions {
                width: 100%;
            }

            .order-actions .btn {
                flex: 1;
            }
        }
        /* CSS TỐI ƯU CHO CHUÔNG */
        .notification-wrapper {
            position: relative;
        }
        .noti-badge {
            position: absolute; top: -4px; right: -4px;
            background-color: #e74c3c; color: white;
            font-size: 10px; font-weight: bold;
            height: 18px; width: 18px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            border: 2px solid white;
            z-index: 50; /* Quan trọng để số đỏ nằm trên chuông */
        }
        .noti-dropdown {
            display: none;
            position: absolute;
            top: 48px;
            right: 0;
            width: 350px;
            background: rgba(255,255,255,0.98);
            border-radius: 18px;
            box-shadow: 0 8px 32px rgba(61,107,63,0.18), 0 1.5px 8px rgba(0,0,0,0.08);
            border: 1.5px solid #e5e7eb;
            z-index: 9999;
            overflow: hidden;
            padding: 0 0 8px 0;
            backdrop-filter: blur(8px);
        }
        .noti-dropdown.active { display: block; }
        .noti-header {
            padding: 18px 22px 12px 22px;
            font-weight: 700;
            font-size: 1.1rem;
            border-bottom: 1.5px solid #e5e7eb;
            background: linear-gradient(90deg, #f9fafb 80%, #e8edc7 100%);
            color: #2e8b57;
            letter-spacing: 0.5px;
        }
        .noti-list {
            max-height: 350px;
            overflow-y: auto;
            padding: 0 10px;
        }
        .noti-item {
            background: transparent;
            margin: 10px 0;
            border-radius: 12px;
            padding: 14px 16px 12px 16px;
            box-shadow: 0 1px 4px rgba(61,107,63,0.04);
            border: 1px solid #f3f4f6;
            transition: background 0.2s, box-shadow 0.2s;
            cursor: pointer;
        }
        .noti-item:hover {
            background: #f0fdf4;
            box-shadow: 0 2px 8px rgba(61,107,63,0.10);
            border-color: #c8d96f;
        }
        .noti-item h4 {
            color: #3d6b3f;
            font-size: 1rem;
            font-weight: 700;
            margin-bottom: 2px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .noti-item p {
            color: #555;
            font-size: 0.97rem;
            margin: 0 0 4px 0;
        }
        .noti-item small {
            color: #999;
            font-size: 0.85rem;
        }
        .noti-badge {
            position: absolute;
            top: -4px;
            right: -4px;
            background: linear-gradient(135deg, #e74c3c, #f59e0b);
            color: #fff;
            font-size: 12px;
            font-weight: bold;
            height: 20px;
            width: 20px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid #fff;
            box-shadow: 0 2px 6px rgba(231,76,60,0.15);
            z-index: 50;
        }
        @media (max-width: 600px) {
            .noti-dropdown { min-width: 95vw; right: 0; left: 0; margin: 0 auto; }
        }
</style>
</head>
<body>
    <!-- Announcement Bar -->

    <!-- Header -->
    <header class="header">
        <div class="header-container">
            <a href="/" class="brand-logo" style="display:flex;align-items:center;gap:12px;">
                <img src="images/logo.jpg" alt="HuynhHoan Logo" style="height:45px;width:auto;border-radius:8px;">
                <span style="font-weight:600;font-size:1.4rem;">HuynhHoan</span>
            </a>

        <nav class="nav">
            <a href="trangchu.php">Trang chủ</a>
            <a href="san-pham.php">Sản Phẩm</a>
            <a href="baiviet.php" >Bài Viết</a>
            <a href="lienhe.php">Liên Hệ</a>
        </nav>

            <div class="header-actions">
                <a href="giohang.php" class="icon-btn">
                    <i class="fas fa-shopping-bag"></i>
                </a>
                <?php 
                    // Các biến session cần thiết (có thể đã được load từ đầu file)
                    $user_id = $_SESSION['user_id'] ?? null;
                    $user_email = $_SESSION['email'] ?? null;
                    ?>
                    
                    <?php if ($user_id || $user_email): ?>
                        <a href="logout.php" class="icon-btn" title="Đăng xuất">
                            <i class="fas fa-sign-out-alt"></i>
                        </a>
                    <?php else: ?>
                        <a href="dangnhap.php" class="icon-btn" title="Đăng nhập">
                            <i class="fas fa-sign-in-alt"></i>
                        </a>
                    <?php endif; ?>
            </div>
        </div>
    </header>

    <div class="container" style="padding: 2rem 1rem;">
        <div class="page-header">
            <div class="page-title">
                <div class="icon">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <h1>Đơn Hàng Của Tôi</h1>
            </div>
        </div>

        <div class="user-info">
            <i class="fas fa-user-circle"></i>
            <div class="info">
                <div class="username">
                    <?php echo htmlspecialchars($_SESSION['username'] ?? 'Khách hàng'); ?>
                </div>
                <?php if ($user_email): ?>
                    <div class="email"><?php echo htmlspecialchars($user_email); ?></div>
                <?php endif; ?>
            </div>
        </div>

        <div class="stats">
            <div class="stat-card">
                <div class="number"><?php echo count($orders); ?></div>
                <div class="label">Tổng đơn hàng</div>
            </div>
            <div class="stat-card">
                <div class="number">
                    <?php 
                    $pending = array_filter($orders, function($o) { return $o['trang_thai'] === 'Chờ xác nhận'; });
                    echo count($pending);
                    ?>
                </div>
                <div class="label">Chờ xác nhận</div>
            </div>
            <div class="stat-card">
                <div class="number">
                    <?php 
                    $shipping = array_filter($orders, function($o) { return $o['trang_thai'] === 'Đang giao'; });
                    echo count($shipping);
                    ?>
                </div>
                <div class="label">Đang giao hàng</div>
            </div>
            <div class="stat-card">
                <div class="number">
                    <?php 
                    $delivered = array_filter($orders, function($o) { return $o['trang_thai'] === 'Đã giao'; });
                    echo count($delivered);
                    ?>
                </div>
                <div class="label">Đã hoàn thành</div>
            </div>
        </div>

        <div class="orders-section">
            <div class="section-title">
                <i class="fas fa-list"></i>
                Danh Sách Đơn Hàng
            </div>

            <?php if (empty($orders)): ?>
                <div class="empty-state">
                    <i class="fas fa-shopping-bag"></i>
                    <h3>Chưa có đơn hàng nào</h3>
                    <p>Bạn chưa có đơn hàng nào. Hãy bắt đầu mua sắm ngay!</p>
                    <br>
                    <a href="trangchu.php" class="btn btn-primary">
                        <i class="fas fa-shopping-cart"></i>
                        Mua Sắm Ngay
                    </a>
                </div>
            <?php else: ?>
                <?php foreach ($orders as $order): ?>
                    <div class="order-card">
                        <div class="order-header">
                            <div>
                                <div class="order-code">
                                    <i class="fas fa-receipt"></i>
                                    <?php echo htmlspecialchars($order['ma_don_hang']); ?>
                                </div>
                                <div class="order-date">
                                    <i class="far fa-calendar"></i>
                                    <?php echo date('d/m/Y H:i', strtotime($order['ngay_dat'])); ?>
                                </div>
                            </div>
                        </div>

                        <div class="order-body">
                            <div class="order-info">
                                <div class="info-item">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <div class="value">
                                        <div class="label">Địa chỉ giao hàng</div>
                                        <?php echo htmlspecialchars($order['dia_chi'] ?? 'N/A'); ?>
                                    </div>
                                </div>
                                <div class="info-item">
                                    <i class="fas fa-credit-card"></i>
                                    <div class="value">
                                        <div class="label">Thanh toán</div>
                                        <?php echo htmlspecialchars($order['phuong_thuc_thanh_toan']); ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="order-footer">
                            <div>
                                <?php
                                $statusClass = '';
                                $statusIcon = '';
                                switch ($order['trang_thai']) {
                                    case 'Chờ xác nhận':
                                        $statusClass = 'pending';
                                        $statusIcon = 'fa-clock';
                                        break;
                                    case 'Đã xác nhận':
                                        $statusClass = 'confirmed';
                                        $statusIcon = 'fa-check-circle';
                                        break;
                                    case 'Đang giao':
                                        $statusClass = 'shipping';
                                        $statusIcon = 'fa-shipping-fast';
                                        break;
                                    case 'Đã giao':
                                        $statusClass = 'delivered';
                                        $statusIcon = 'fa-check-double';
                                        break;
                                    case 'Đã hủy':
                                        $statusClass = 'cancelled';
                                        $statusIcon = 'fa-times-circle';
                                        break;
                                }
                                ?>
                                <div class="status-badge <?php echo $statusClass; ?>">
                                    <i class="fas <?php echo $statusIcon; ?>"></i>
                                    <?php echo htmlspecialchars($order['trang_thai']); ?>
                                </div>
                                <div class="order-total">
                                    <?php echo number_format($order['tong_thanh_toan'], 0, ',', '.'); ?>₫
                                </div>
                            </div>
                            <div class="order-actions">
                                <button class="btn btn-outline btn-sm" onclick="viewOrderDetails(<?php echo $order['id']; ?>)">
                                    <i class="fas fa-eye"></i>
                                    Chi Tiết
                                </button>
                                <?php if (trim($order['trang_thai']) === 'Chờ xác nhận'): ?>
                                <button class="btn btn-outline btn-sm" style="border-color: #ef4444; color: #ef4444;" onclick="cancelOrder(<?php echo $order['id']; ?>)">
                                    <i class="fas fa-times"></i>
                                    Hủy Đơn
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal Chi Tiết Đơn Hàng -->
    <div class="modal" id="orderDetailModal">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-title" id="modalOrderCode">Chi Tiết Đơn Hàng</div>
                <button class="close-btn" onclick="closeModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div id="modalBody"></div>
        </div>
    </div>

    <script>
    // ====== THÔNG BÁO CHUÔNG (giống san-pham.php) ======
    // (Đã thay thế bằng bản đồng bộ với san-pham.php ở cuối file)

    function cancelOrder(orderId) {
        if (!confirm('Bạn có chắc chắn muốn hủy đơn hàng này?')) {
            return;
        }
        const formData = new FormData();
        formData.append('cancel_order', '1');
        formData.append('order_id', orderId);
        fetch('', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('✅ ' + data.message);
                location.reload();
            } else {
                alert('❌ ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Có lỗi xảy ra khi hủy đơn hàng');
        });
    }

    function viewOrderDetails(orderId) {
        console.log('==> Đã click nút Chi Tiết, orderId:', orderId);
        fetch('?get_order_details=' + orderId)
            .then(response => response.json())
            .then(data => {
                console.log('==> Kết quả fetch chi tiết đơn hàng:', data);
                if (data.success) {
                    displayOrderDetails(data.order, data.items, data.reviews || {});
                } else {
                    alert('Không thể tải chi tiết đơn hàng');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Có lỗi xảy ra');
            });
    }

    function displayOrderDetails(order, items, reviews) {
        document.getElementById('modalOrderCode').textContent = 'Đơn Hàng: ' + order.ma_don_hang;
        let html = `
            <div class="order-info">
                <div class="info-item">
                    <i class="fas fa-sign-out-alt"></i>
                    <div class="value">
                        <div class="label">Khách hàng</div>
                        ${order.ten_khach_hang}
                    </div>
                </div>
                <div class="info-item">
                    <i class="fas fa-phone"></i>
                    <div class="value">
                        <div class="label">Số điện thoại</div>
                        ${order.so_dien_thoai}
                    </div>
                </div>
                <div class="info-item">
                    <i class="fas fa-envelope"></i>
                    <div class="value">
                        <div class="label">Email</div>
                        ${order.email || 'N/A'}
                    </div>
                </div>
                <div class="info-item">
                    <i class="fas fa-map-marker-alt"></i>
                    <div class="value">
                        <div class="label">Địa chỉ</div>
                        ${order.dia_chi || 'N/A'}
                    </div>
                </div>
            </div>
        `;
        if (order.ghi_chu) {
            html += `
                <div class="info-item" style="margin-top: 1rem;">
                    <i class="fas fa-sticky-note"></i>
                    <div class="value">
                        <div class="label">Ghi chú</div>
                        ${order.ghi_chu}
                    </div>
                </div>
            `;
        }
        html += '<div class="order-items"><div class="section-title"><i class="fas fa-box"></i> Sản phẩm</div>';
        // Chỉ cho phép đánh giá khi đơn hàng đã giao
        const statusTrimmed = (order.trang_thai || '').trim();
        const orderCanReview = statusTrimmed === 'Đã giao' || statusTrimmed === 'Da giao' || statusTrimmed === 'Đã Giao';
        console.log('📦 Order ID:', order.id);
        console.log('📦 Order Status (raw):', order.trang_thai);
        console.log('📦 Order Status (trimmed):', statusTrimmed);
        console.log('📦 Order Status (length):', statusTrimmed.length);
        console.log('📦 Comparing with "Đã giao" (length 8)');
        console.log('✅ Can Review:', orderCanReview);
        console.log('✅ Exact match test:', statusTrimmed === 'Đã giao' ? 'YES' : 'NO');
        items.forEach(item => {
            const review = reviews[item.san_pham_id];
            html += `
                <div class="item-card">
                    <div class="item-info">
                        <div class="item-name">${item.ten_san_pham}</div>
                        <div class="item-details">
                            ${item.size ? 'Size: ' + item.size + ' | ' : ''}
                            Số lượng: ${item.so_luong} | 
                            Đơn giá: ${new Intl.NumberFormat('vi-VN').format(item.gia)}₫
                        </div>
                    </div>
                    <div style="display:flex;flex-direction:column;gap:0.5rem;align-items:flex-end;">
                        <div class="item-price">
                            ${new Intl.NumberFormat('vi-VN').format(item.thanh_tien)}₫
                        </div>
                        ${orderCanReview && !review ? `
                            <button class="btn btn-outline btn-sm" onclick="openReviewModal(${item.san_pham_id}, '${item.ten_san_pham.replace(/'/g, "\\'")}', ${order.id})">
                                <i class="fas fa-star"></i> Đánh giá
                            </button>
                        ` : ''}
                    </div>
                </div>
            `;
            // Hiển thị đánh giá và phản hồi (nếu có)
            if (review) {
                html += `
                    <div style="background:#f8f9fa;border-left:4px solid #f59e0b;padding:15px;margin:10px 0;border-radius:8px;">
                        <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px;">
                            <div style="color:#f59e0b;font-size:20px;">
                                ${'★'.repeat(review.rating)}${'☆'.repeat(5-review.rating)}
                            </div>
                            <small style="color:#666;">${new Date(review.created_at).toLocaleDateString('vi-VN')}</small>
                        </div>
                        <div style="color:#333;line-height:1.6;margin-bottom:10px;">
                            <strong>Đánh giá của bạn:</strong><br>
                            ${review.comment}
                        </div>
                        ${review.admin_reply ? `
                            <div style="background:#e8f4f8;border-left:3px solid #3498db;padding:10px;border-radius:6px;margin-top:10px;">
                                <div style="color:#2980b9;font-weight:600;margin-bottom:5px;">
                                    <i class="fas fa-reply"></i> Phản hồi từ Shop:
                                </div>
                                <div style="color:#555;line-height:1.6;">
                                    ${review.admin_reply}
                                </div>
                            </div>
                        ` : '<div style="color:#999;font-style:italic;font-size:13px;"><i class="fas fa-clock"></i> Đang chờ shop phản hồi...</div>'}
                    </div>
                `;
            }
        });
        html += '</div>';
        html += `
            <div class="order-summary">
                <div class="summary-row">
                    <span>Tạm tính:</span>
                    <span>${new Intl.NumberFormat('vi-VN').format(order.tong_tien)}₫</span>
                </div>
                <div class="summary-row">
                    <span>Phí vận chuyển:</span>
                    <span>${order.phi_van_chuyen == 0 ? 'Miễn phí' : new Intl.NumberFormat('vi-VN').format(order.phi_van_chuyen) + '₫'}</span>
                </div>
                <div class="summary-row total">
                    <span>Tổng cộng:</span>
                    <span>${new Intl.NumberFormat('vi-VN').format(order.tong_thanh_toan)}₫</span>
                </div>
            </div>
        `;
        document.getElementById('modalBody').innerHTML = html;
        document.getElementById('orderDetailModal').classList.add('active');
    }

    function closeModal() {
        document.getElementById('orderDetailModal').classList.remove('active');
    }

    // Close modal when clicking outside
    document.getElementById('orderDetailModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeModal();
        }
    });

    // ===== ĐÁNH GIÁ SẢN PHẨM =====
    function openReviewModal(productId, productName, orderId) {
        console.log('🌟 Opening review modal');
        console.log('  Product ID:', productId);
        console.log('  Product Name:', productName);
        console.log('  Order ID:', orderId);
        // Mở modal
        const modal = document.getElementById('reviewModal');
        if (!modal) {
            console.error('❌ Review modal element not found!');
            alert('Lỗi: Không tìm thấy form đánh giá!');
            return;
        }
        // Set values
        document.getElementById('reviewProductId').value = productId;
        document.getElementById('reviewProductName').textContent = productName;
        // Reset form
        document.getElementById('reviewForm').reset();
        document.getElementById('reviewRating').value = 5;
        updateReviewStars(5);
        // Show modal
        modal.classList.add('active');
        console.log('✅ Review modal opened successfully');
    }

    function closeReviewModal() {
        document.getElementById('reviewModal').classList.remove('active');
        document.getElementById('reviewForm').reset();
        updateReviewStars(5);
    }

    function updateReviewStars(rating) {
        document.getElementById('reviewRating').value = rating;
        const stars = document.querySelectorAll('#reviewStars i');
        stars.forEach((star, index) => {
            if (index < rating) {
                star.classList.remove('far');
                star.classList.add('fas');
                star.style.color = '#f59e0b';
            } else {
                star.classList.remove('fas');
                star.classList.add('far');
                star.style.color = '#d1d5db';
            }
        });
    }

    // ===== XỬ LÝ GỬI ĐÁNH GIÁ =====
    // Đợi DOM load xong
    document.addEventListener('DOMContentLoaded', function() {
        const reviewForm = document.getElementById('reviewForm');
        if (!reviewForm) {
            console.error('❌ Review form not found!');
            return;
        }
        console.log('✅ Review form found, attaching submit handler');
        reviewForm.addEventListener('submit', function(e) {
            e.preventDefault();
            console.log('🔍 REVIEW FORM SUBMIT EVENT TRIGGERED');
            const formData = new FormData(this);
            // Debug log - chi tiết form data
            console.log('📋 Form Data:');
            for (let [key, value] of formData.entries()) {
                console.log(`  ${key}: ${value}`);
            }
            console.log('🚀 Sending to xu_ly_danh_gia.php...');
            fetch('xu_ly_danh_gia.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                console.log('📥 Response status:', response.status);
                console.log('📥 Response OK:', response.ok);
                // Kiểm tra response text trước
                return response.text().then(text => {
                    console.log('📄 Raw response:', text);
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        console.error('❌ JSON parse error:', e);
                        console.error('❌ Response was:', text);
                        throw new Error('Invalid JSON response: ' + text.substring(0, 100));
                    }
                });
            })
            .then(data => {
                console.log('✅ Parsed data:', data);
                closeReviewModal();
                if (data.success) {
                    // Chỉ hiển thị thông báo thành công, KHÔNG reload
                    alert('✅ Cảm ơn bạn đã đánh giá sản phẩm!\n\nĐánh giá của bạn đã được gửi đến quản trị viên.');
                } else {
                    alert('❌ ' + (data.message || 'Có lỗi xảy ra khi gửi đánh giá'));
                }
            })
            .catch(error => {
                console.error('❌ Error:', error);
                alert('❌ Có lỗi xảy ra khi gửi đánh giá: ' + error.message);
            });
        });
        console.log('✅ Submit handler attached successfully');
    });

    // Close review modal when clicking outside
    document.getElementById('reviewModal')?.addEventListener('click', function(e) {
        if (e.target === this) {
            closeReviewModal();
        }
    });
    </script>

    <!-- Modal Đánh Giá Sản Phẩm -->
    <div class="modal" id="reviewModal">
        <div class="modal-content" style="max-width: 600px;">
            <div class="modal-header">
                <div class="modal-title">
                    <i class="fas fa-star" style="color: #f59e0b;"></i>
                    Đánh Giá Sản Phẩm
                </div>
                <button class="close-btn" onclick="closeReviewModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div style="background: linear-gradient(135deg, var(--beige-1), var(--beige-2)); padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;">
                    <div style="font-weight: 600; color: var(--indigo);" id="reviewProductName"></div>
                </div>

                <form id="reviewForm">
                    <input type="hidden" name="san_pham_id" id="reviewProductId">
                    <input type="hidden" name="rating" id="reviewRating" value="5">
                    
                    <div style="margin-bottom: 1.5rem;">
                        <label style="display: block; font-weight: 600; color: var(--indigo); margin-bottom: 0.75rem;">
                            <i class="fas fa-star" style="color: #f59e0b;"></i> Đánh giá của bạn:
                        </label>
                        <div id="reviewStars" style="font-size: 2rem; cursor: pointer;">
                            <i class="fas fa-star" onclick="updateReviewStars(1)" style="color: #f59e0b;"></i>
                            <i class="fas fa-star" onclick="updateReviewStars(2)" style="color: #f59e0b;"></i>
                            <i class="fas fa-star" onclick="updateReviewStars(3)" style="color: #f59e0b;"></i>
                            <i class="fas fa-star" onclick="updateReviewStars(4)" style="color: #f59e0b;"></i>
                            <i class="fas fa-star" onclick="updateReviewStars(5)" style="color: #f59e0b;"></i>
                        </div>
                    </div>

                    <div style="margin-bottom: 1.5rem;">
                        <label style="display: block; font-weight: 600; color: var(--indigo); margin-bottom: 0.5rem;">
                            <i class="fas fa-comment"></i> Nhận xét:
                        </label>
                        <textarea 
                            name="comment" 
                            rows="4" 
                            required
                            placeholder="Chia sẻ trải nghiệm của bạn về sản phẩm..."
                            style="width: 100%; padding: 0.75rem; border: 2px solid #e5e7eb; border-radius: 8px; font-family: inherit; resize: vertical;"
                        ></textarea>
                    </div>

                    <div style="display: flex; gap: 1rem;">
                        <button type="submit" class="btn" style="flex: 1; background: linear-gradient(135deg, #7fa84e, #9bc26f); color: white; padding: 0.75rem 1.5rem; border-radius: 8px; font-weight: 600; border: none; cursor: pointer; transition: all 0.3s;">
                            <i class="fas fa-paper-plane"></i>
                            Gửi Đánh Giá
                        </button>
                        <button type="button" class="btn" style="background: white; color: #5a7a4f; border: 2px solid #5a7a4f; padding: 0.75rem 1.5rem; border-radius: 8px; font-weight: 600; cursor: pointer; transition: all 0.3s;" onclick="closeReviewModal()">
                            <i class="fas fa-times"></i>
                            Hủy
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Notification bell/dropdown sẽ được tự động chèn bởi notifications.js nếu chưa có -->

    <!-- Chuông thông báo đồng bộ -->
    <link rel="stylesheet" href="assets/notifications.css">
    <script src="assets/notifications.js" defer></script>
<?php
// Kiểm tra trạng thái đăng nhập để JS sử dụng
$chat_is_logged = isset($_SESSION['user_id']) ? 'true' : 'false';
?>

<div id="live-chat-widget">
    <button id="chatLauncher" type="button" onclick="toggleChat()">
        <i class="fas fa-comments"></i>
        <span class="online-status"></span>
    </button>

    <div id="chatWindow" class="chat-hidden">
        <div class="chat-header">
            <div class="header-info">
                <div class="avatar-wrap">
                    <img src="https://ui-avatars.com/api/?name=Admin&background=fff&color=1d3e1f" alt="Admin">
                    <span class="dot-online"></span>
                </div>
                <div>
                    <span class="staff-name">Hỗ Trợ Khách Hàng</span>
                    <span class="staff-status">Đang hoạt động</span>
                </div>
            </div>
            <button class="close-chat" type="button" onclick="toggleChat()"><i class="fas fa-times"></i></button>
        </div>

        <div id="chatMessages" class="chat-body">
            <div class="message bot-msg">
                Xin chào! 👋<br>Shop có thể giúp gì cho bạn ạ?
            </div>
        </div>

        <div class="chat-footer">
            <input type="text" id="chatInput" placeholder="Nhập tin nhắn..." autocomplete="off" onkeypress="handleEnter(event)">
            <button onclick="sendMessage()" type="button" id="btnSend"><i class="fas fa-paper-plane"></i></button>
        </div>
    </div>
</div>

<style>
    /* CSS CHAT - Z-index cực cao để đè lên mọi thứ */
    #live-chat-widget { position: fixed; bottom: 30px; right: 30px; z-index: 2147483647; font-family: sans-serif; }
    
    #chatLauncher { 
        width: 60px; height: 60px; background: #1d3e1f; color: white; border-radius: 50%; 
        border: none; cursor: pointer; box-shadow: 0 4px 15px rgba(0,0,0,0.3); font-size: 26px; 
        display: flex; align-items: center; justify-content: center; position: relative; transition: transform 0.2s; 
    }
    #chatLauncher:hover { transform: scale(1.1); }
    
    .online-status { position: absolute; top: 0; right: 0; width: 14px; height: 14px; background: #2ecc71; border: 2px solid #fff; border-radius: 50%; }
    
    #chatWindow { 
        position: absolute; bottom: 80px; right: 0; width: 320px; height: 400px; background: #fff; 
        border-radius: 12px; box-shadow: 0 5px 30px rgba(0,0,0,0.2); display: none; flex-direction: column; overflow: hidden; border: 1px solid #ddd; 
    }
    #chatWindow.chat-visible { display: flex; animation: chatPopUp 0.3s ease-out; }
    
    .chat-header { background: #1d3e1f; color: white; padding: 12px; display: flex; justify-content: space-between; align-items: center; }
    .header-info { display: flex; align-items: center; gap: 10px; }
    .avatar-wrap { position: relative; width: 35px; height: 35px; }
    .avatar-wrap img { width: 100%; height: 100%; border-radius: 50%; border: 2px solid #fff; }
    .dot-online { position: absolute; bottom: 0; right: 0; width: 8px; height: 8px; background: #2ecc71; border-radius: 50%; }
    .staff-name { font-weight: bold; font-size: 0.9rem; display: block; }
    .staff-status { font-size: 0.7rem; opacity: 0.9; }
    .close-chat { background: transparent; border: none; color: white; font-size: 1.1rem; cursor: pointer; }
    
    .chat-body { flex: 1; padding: 10px; overflow-y: auto; background: #f5f7f9; display: flex; flex-direction: column; gap: 8px; }
    .message { max-width: 80%; padding: 8px 12px; font-size: 0.9rem; border-radius: 10px; word-wrap: break-word; }
    .bot-msg { background: white; color: #333; align-self: flex-start; box-shadow: 0 1px 2px rgba(0,0,0,0.1); }
    .user-msg { background: #1d3e1f; color: white; align-self: flex-end; }
    
    .chat-footer { padding: 10px; background: white; border-top: 1px solid #eee; display: flex; gap: 5px; }
    #chatInput { flex: 1; padding: 8px 12px; border: 1px solid #ddd; border-radius: 20px; outline: none; }
    #btnSend { width: 36px; height: 36px; border-radius: 50%; border: none; background: #1d3e1f; color: white; cursor: pointer; display: flex; align-items: center; justify-content: center; }
    
    @keyframes chatPopUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
</style>

<script>
    // Link API Chat (đảm bảo file này tồn tại)
    const API_URL_CHAT = 'api_chat_live.php'; 
    const isUserLoggedIn = <?php echo $chat_is_logged; ?>;
    let chatInterval;

    function toggleChat() {
        // 1. Kiểm tra đăng nhập
        if (!isUserLoggedIn) {
            if (confirm("Bạn cần Đăng nhập để chat với nhân viên.\nĐến trang đăng nhập ngay?")) {
                window.location.href = 'dangnhap.php';
            }
            return;
        }

        // 2. Mở chat
        const win = document.getElementById('chatWindow');
        win.classList.toggle('chat-visible');
        
        if (win.classList.contains('chat-visible')) {
            document.getElementById('chatInput').focus();
            loadLiveMessages(); // Tải tin nhắn ngay
            chatInterval = setInterval(loadLiveMessages, 3000); // Tự động cập nhật 3s/lần
        } else {
            clearInterval(chatInterval); // Tắt cập nhật khi đóng
        }
    }

    function handleEnter(e) { if (e.key === 'Enter') sendMessage(); }

    function sendMessage() {
        const input = document.getElementById('chatInput');
        const text = input.value.trim();
        if (!text) return;
        
        // Hiện tin nhắn tạm thời
        appendMessage(text, 'user-msg');
        input.value = '';
        
        // Gửi lên server
        const fd = new FormData();
        fd.append('action', 'send_message');
        fd.append('message', text);
        
        fetch(API_URL_CHAT, { method: 'POST', body: fd })
            .catch(err => console.error(err));
    }

    function loadLiveMessages() {
        const fd = new FormData();
        fd.append('action', 'get_messages');
        
        fetch(API_URL_CHAT, { method: 'POST', body: fd })
            .then(r => r.json())
            .then(data => {
                const body = document.getElementById('chatMessages');
                
                // Giữ lại tin nhắn chào
                let html = '<div class="message bot-msg">Xin chào! 👋<br>Shop có thể giúp gì cho bạn ạ?</div>';
                
                if (data.messages && data.messages.length > 0) {
                    data.messages.forEach(msg => {
                        const type = (msg.is_from_admin == 1) ? 'bot-msg' : 'user-msg';
                        html += `<div class="message ${type}">${msg.message}</div>`;
                    });
                }
                body.innerHTML = html;
                body.scrollTop = body.scrollHeight; // Tự cuộn xuống dưới
            })
            .catch(err => console.log('Lỗi chat:', err));
    }

    function appendMessage(text, cls) {
        const div = document.createElement('div');
        div.className = `message ${cls}`;
        div.textContent = text;
        const body = document.getElementById('chatMessages');
        body.appendChild(div);
        body.scrollTop = body.scrollHeight;
    }
    // Thông báo cá nhân từ notifications_api.php
    function loadPersonalNoti() {
        fetch('notifications_api.php?action=get_notifications')
            .then(response => response.json())
            .then(data => {
                const list = document.getElementById('public-noti-list');
                const badge = document.getElementById('public-noti-badge');
                if (data.success && data.notifications.length > 0) {
                    list.innerHTML = '';
                    let unreadCount = 0;
                    data.notifications.forEach(item => {
                        if (item.is_read == 0) unreadCount++;
                        let icon = item.type === 'order_status' ? '📦' : item.type === 'review_reply' ? '💬' : item.type === 'promo' ? '🎁' : '🔔';
                        list.innerHTML += `
                            <a href="${item.link || '#'}" class="noti-item${item.is_read == 0 ? ' unread' : ''}">
                                <h4>${icon} ${item.title}</h4>
                                <p>${item.message}</p>
                                <small>${item.created_at}</small>
                            </a>`;
                    });
                    if (unreadCount > 0) {
                        badge.innerText = unreadCount > 99 ? '99+' : unreadCount;
                        badge.style.display = 'flex';
                    } else {
                        badge.style.display = 'none';
                    }
                } else {
                    list.innerHTML = '<p style="padding:20px;text-align:center;color:#888">Chưa có thông báo nào</p>';
                    badge.style.display = 'none';
                }
            })
            .catch(err => console.error(err));
    }

    function toggleNotiDropdown() {
        const dropdown = document.getElementById('public-noti-dropdown');
        dropdown.classList.toggle('active');
        if (dropdown.classList.contains('active')) {
            document.getElementById('public-noti-badge').style.display = 'none';
            // Đánh dấu tất cả là đã đọc
            fetch('notifications_api.php?action=mark_all_read', { method: 'POST' });
        }
    }

    // Tự động chạy khi tải trang
    document.addEventListener('DOMContentLoaded', loadPersonalNoti);
</script>
</body>
</html>
