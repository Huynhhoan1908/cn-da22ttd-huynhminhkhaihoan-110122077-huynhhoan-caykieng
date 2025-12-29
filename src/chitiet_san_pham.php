<?php
require_once __DIR__ . '/auth_gate.php';
require_once __DIR__ . '/connect.php';

// Lấy thông tin user nếu đã đăng nhập
$user_info = null;
if (isset($_SESSION['user_id'])) {
    $stmt_user = $conn->prepare("SELECT ho_ten, email FROM nguoi_dung WHERE id = ?");
    $stmt_user->execute([$_SESSION['user_id']]);
    $user_info = $stmt_user->fetch(PDO::FETCH_ASSOC);
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    http_response_code(400);
    die('Thiếu hoặc sai ID sản phẩm');
}

// Lấy thông tin sản phẩm
try {
    $stmt = $conn->prepare(
        "SELECT sp.*, dm.ten_san_pham AS ten_danh_muc
         FROM san_pham sp
         LEFT JOIN danh_muc dm ON sp.danh_muc_id = dm.id
         WHERE sp.id = ? LIMIT 1"
    );
    $stmt->execute([$id]);
    $san_pham = $stmt->fetch();
} catch (Throwable $e) {
    $san_pham = false;
}

if (!$san_pham) {
    http_response_code(404);
    die('Không tìm thấy sản phẩm');
}

// Lấy đánh giá từ bảng danh_gia
try {
    $cstmt = $conn->prepare("SELECT id, user_name, rating, comment, created_at, admin_reply FROM danh_gia WHERE san_pham_id = ? ORDER BY created_at DESC");
    $cstmt->execute([$id]);
    $comments = $cstmt->fetchAll();

    // Tính điểm trung bình
    $avg = 0; $count = count($comments);
    if ($count > 0) {
        $sum = 0;
        foreach ($comments as $c) $sum += (int)$c['rating'];
        $avg = round($sum / $count, 1);
    }
} catch (Throwable $e) {
    $comments = [];
    $avg = 0;
    $count = 0;
}

// Check if product is locked

// Tự động khoá sản phẩm nếu hết hàng
if (isset($san_pham['so_luong']) && (int)$san_pham['so_luong'] <= 0 && isset($san_pham['trang_thai']) && $san_pham['trang_thai'] != 0) {
    // Cập nhật trạng thái sản phẩm về khoá (0)
    try {
        $stmt = $conn->prepare("UPDATE san_pham SET trang_thai = 0 WHERE id = ?");
        $stmt->execute([$san_pham['id']]);
        $san_pham['trang_thai'] = 0;
    } catch (Throwable $e) {
        // Nếu lỗi thì bỏ qua, không khoá được cũng không ảnh hưởng
    }
}
$isLocked = isset($san_pham['trang_thai']) && $san_pham['trang_thai'] == 0;

// Kiểm tra xem user đã mua và hoàn thành đơn hàng chưa
$has_purchased = false;
if (isset($_SESSION['user_id']) || isset($_SESSION['email'])) {
    try {
        $user_id = $_SESSION['user_id'] ?? null;
        $user_email = $_SESSION['email'] ?? null;
        
        if ($user_id) {
            $check_stmt = $conn->prepare("
                SELECT COUNT(*) as purchased FROM don_hang dh
                INNER JOIN chi_tiet_don_hang ct ON dh.id = ct.don_hang_id
                WHERE ct.san_pham_id = ? AND dh.nguoi_dung_id = ? AND dh.trang_thai = 'Đã giao'
            ");
            $check_stmt->execute([$id, $user_id]);
        } elseif ($user_email) {
            $check_stmt = $conn->prepare("
                SELECT COUNT(*) as purchased FROM don_hang dh
                INNER JOIN chi_tiet_don_hang ct ON dh.id = ct.don_hang_id
                WHERE ct.san_pham_id = ? AND dh.email = ? AND dh.trang_thai = 'Đã giao'
            ");
            $check_stmt->execute([$id, $user_email]);
        }
        
        if (isset($check_stmt)) {
            $result = $check_stmt->fetch();
            $has_purchased = $result['purchased'] > 0;
        }
    } catch (Throwable $e) {
        $has_purchased = false;
    }
}

// Lấy đánh giá từ bảng danh_gia
try {
    $cstmt = $conn->prepare("SELECT id, user_name, rating, comment, created_at, admin_reply FROM danh_gia WHERE san_pham_id = ? ORDER BY created_at DESC");
    $cstmt->execute([$id]);
    $comments = $cstmt->fetchAll();

    // Tính điểm trung bình
    $avg = 0; $count = count($comments);
    if ($count > 0) {
        $sum = 0;
        foreach ($comments as $c) $sum += (int)$c['rating'];
        $avg = round($sum / $count, 1);
    }
} catch (Throwable $e) {
    $comments = [];
    $avg = 0;
    $count = 0;
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($san_pham['ten_san_pham']); ?> - HuynhHoan</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/theme.css">
    <style>
        body { 
            font-family: 'Poppins', sans-serif; 
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
            margin: 0; 
            color: #1d3e1f; 
        }
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .breadcrumbs { 
            font-size: 14px; 
            margin: 10px 0 20px;
            padding: 12px 18px;
            background: rgba(255,255,255,0.8);
            border-radius: 8px;
            backdrop-filter: blur(10px);
        }
        .breadcrumbs a { color: #1d3e1f; text-decoration: none; transition: color 0.3s; }
        .breadcrumbs a:hover { color: #7fa84e; }
        .card { 
            background: linear-gradient(135deg, #ffffff 0%, #fefefe 100%);
            border-radius: 20px; 
            box-shadow: 0 10px 40px rgba(61,107,63,0.15);
            overflow: hidden;
            border: 1px solid rgba(159,194,111,0.2);
        }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 40px; padding: 40px; }
        .image { 
            width: 100%; 
            aspect-ratio: 1/1; 
            object-fit: cover; 
            border-radius: 16px;
            box-shadow: 0 8px 24px rgba(61,107,63,0.2);
            transition: transform 0.3s ease;
        }
        .image:hover { transform: scale(1.02); }
        .name { 
            font-size: 28px; 
            font-weight: 700; 
            margin: 0 0 12px;
            color: #1d3e1f;
            line-height: 1.3;
        }
        .category { 
            color: #7fa84e; 
            margin-bottom: 12px;
            font-size: 15px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #f5f2d4;
            padding: 6px 14px;
            border-radius: 20px;
        }
        .price { 
            font-size: 32px; 
            font-weight: 700; 
            color: #059669; 
            margin: 16px 0;
            text-shadow: 0 2px 4px rgba(5,150,105,0.1);
        }
        .stock { 
            color: #059669; 
            margin-bottom: 16px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            background: #ecfdf5;
            padding: 10px 16px;
            border-radius: 10px;
            width: fit-content;
        }
        .desc { 
            line-height: 1.8; 
            white-space: pre-line; 
            margin-top: 20px;
            color: #3d6b3f;
            background: #fdfbe8;
            padding: 20px;
            border-radius: 12px;
            border-left: 4px solid #9bc26f;
        }
        /* Comments and rating */
        .comments { 
            margin: 20px 0; 
            padding: 30px; 
            background: linear-gradient(135deg, #ffffff 0%, #fefefe 100%);
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(61,107,63,0.15);
            border: 1px solid rgba(159,194,111,0.2);
        }
        .comments h3 {
            color: #1d3e1f;
            font-size: 24px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .comment-list { margin-top: 20px; }
        .comment { 
            border-top: 1px solid #e8edc7; 
            padding: 16px 0;
            transition: background 0.3s;
        }
        .comment:hover {
            background: #fdfbe8;
            padding-left: 12px;
            margin-left: -12px;
            border-radius: 8px;
        }
        .comment:first-child { border-top: none; }
        .comment .meta { color: #7fa84e; font-size: 13px; margin-bottom: 8px; font-weight: 500; }
        .stars { color: #f59e0b; }
        .rating-input { display: inline-block; }
        .star-btn { cursor: pointer; color: #cbd5e1; font-size: 24px; transition: all 0.2s; }
        .star-btn:hover { transform: scale(1.2); }
        .star-btn.active { color: #f59e0b; }
        .qty { 
            display: flex; 
            align-items: center; 
            gap: 12px; 
            margin: 20px 0;
            background: #fdfbe8;
            padding: 16px;
            border-radius: 12px;
            width: fit-content;
        }
        .qty label { font-weight: 600; color: #1d3e1f; }
        .qty input { 
            width: 100px; 
            padding: 10px 14px; 
            border: 2px solid #9bc26f; 
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            text-align: center;
            transition: all 0.3s;
        }
        .qty input:focus {
            outline: none;
            border-color: #7fa84e;
            box-shadow: 0 0 0 3px rgba(127,168,78,0.1);
        }
        .actions { 
            display: flex; 
            gap: 16px; 
            margin-top: 24px;
        }
        .btn { 
            border: none; 
            padding: 16px 32px; 
            border-radius: 12px; 
            cursor: pointer; 
            font-weight: 700;
            font-size: 16px;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            flex: 1;
        }
        .btn-cart { 
            background: linear-gradient(135deg, #1d3e1f 0%, #3d6b3f 100%);
            color: #fff;
            box-shadow: 0 4px 12px rgba(29,62,31,0.3);
        }
        .btn-cart:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(29,62,31,0.4);
        }
        .btn-buy { 
            background: linear-gradient(135deg, #059669 0%, #10b981 100%);
            color: #fff;
            box-shadow: 0 4px 12px rgba(5,150,105,0.3);
        }
        .btn-buy:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(5,150,105,0.4);
        }
        .back { 
            display: inline-flex; 
            align-items: center; 
            gap: 8px; 
            margin-bottom: 20px; 
            color: #1d3e1f; 
            text-decoration: none;
            font-weight: 600;
            padding: 10px 18px;
            background: rgba(255,255,255,0.8);
            border-radius: 10px;
            backdrop-filter: blur(10px);
            transition: all 0.3s;
        }
        .back:hover {
            background: #fdfbe8;
            transform: translateX(-4px);
        }
        
        /* Mini Cart Offcanvas */
        .offcanvas-backdrop { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1040; display: none; }
        .offcanvas-backdrop.show { display: block; }
        .offcanvas { position: fixed; top: 0; right: -400px; width: 400px; max-width: 90%; height: 100%; background: white; z-index: 1050; transition: right 0.3s ease; box-shadow: -2px 0 8px rgba(0,0,0,0.15); display: flex; flex-direction: column; }
        .offcanvas.show { right: 0; }
        .offcanvas-header { 
            padding: 1.5rem; 
            border-bottom: 2px solid #e8edc7; 
            display: flex; 
            justify-content: space-between; 
            align-items: center;
            background: linear-gradient(135deg, #fdfbe8 0%, #f5f2d4 100%);
        }
        .offcanvas-title { 
            font-size: 1.5rem; 
            font-weight: 700; 
            color: #1d3e1f;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .btn-close { background: none; border: none; font-size: 2rem; cursor: pointer; color: #7fa84e; transition: all 0.3s; }
        .btn-close:hover { color: #1d3e1f; transform: rotate(90deg); }
        .offcanvas-body { flex: 1; overflow-y: auto; padding: 1.5rem; background: #fffef5; }
        .mini-cart-item { 
            display: flex; 
            gap: 1rem; 
            padding: 1.25rem; 
            background: white;
            border-radius: 12px; 
            margin-bottom: 1rem;
            border: 2px solid #e8edc7;
            transition: all 0.3s;
        }
        .mini-cart-item:hover {
            border-color: #9bc26f;
            box-shadow: 0 4px 12px rgba(127,168,78,0.15);
        }
        .mini-cart-img { width: 70px; height: 70px; object-fit: cover; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .mini-cart-info { flex: 1; }
        .mini-cart-name { font-weight: 600; color: #1d3e1f; font-size: 15px; margin-bottom: 6px; line-height: 1.3; }
        .mini-cart-price { color: #059669; font-weight: 700; font-size: 16px; }
        .mini-cart-qty { display: flex; align-items: center; gap: 10px; margin-top: 10px; }
        .mini-cart-qty button { 
            width: 28px; 
            height: 28px; 
            border: 2px solid #9bc26f; 
            background: white; 
            border-radius: 6px; 
            cursor: pointer;
            font-weight: 700;
            color: #1d3e1f;
            transition: all 0.3s;
        }
        .mini-cart-qty button:hover {
            background: #9bc26f;
            color: white;
        }
        .mini-cart-qty span { min-width: 35px; text-align: center; font-weight: 600; color: #1d3e1f; }
        .mini-cart-remove { 
            background: none; 
            border: none; 
            color: #ef4444; 
            cursor: pointer; 
            font-size: 1.3rem;
            transition: all 0.3s;
            padding: 5px;
        }
        .mini-cart-remove:hover {
            transform: scale(1.2);
            color: #dc2626;
        }
        .offcanvas-footer { 
            padding: 1.5rem; 
            border-top: 2px solid #e8edc7;
            background: linear-gradient(135deg, #fdfbe8 0%, #f5f2d4 100%);
        }
        .mini-cart-total { 
            display: flex; 
            justify-content: space-between; 
            font-size: 1.4rem; 
            font-weight: 700; 
            margin-bottom: 1.25rem;
            color: #1d3e1f;
            padding: 16px;
            background: white;
            border-radius: 10px;
            border: 2px solid #9bc26f;
        }
        .mini-cart-buttons { display: flex; flex-direction: column; gap: 0.75rem; }
        .mini-cart-buttons .btn { width: 100%; justify-content: center; }
        .btn-view-cart { 
            background: white; 
            color: #1d3e1f; 
            border: 2px solid #9bc26f;
            font-weight: 700;
        }
        .btn-view-cart:hover {
            background: #fdfbe8;
            border-color: #7fa84e;
        }
        .btn-checkout { 
            background: linear-gradient(135deg, #059669 0%, #10b981 100%);
            color: white;
            font-weight: 700;
            box-shadow: 0 4px 12px rgba(5,150,105,0.3);
        }
        .btn-checkout:hover {
            box-shadow: 0 6px 20px rgba(5,150,105,0.4);
        }
        .empty-mini-cart { 
            text-align: center; 
            padding: 3rem 1rem; 
            color: #7fa84e;
        }
        .empty-mini-cart i {
            color: #c8d96f;
        }
        
        @media (max-width: 900px){ .grid{ grid-template-columns: 1fr; } }
    </style>
    <script>
        // Đồng bộ key giỏ hàng với trang giohang.php
        var currentUserId = null;
        try {
            currentUserId = <?php echo isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 'null'; ?>;
        } catch (e) { currentUserId = null; }
        const CART_KEY = currentUserId ? 'myshop_cart_' + currentUserId : 'myshop_cart_guest';
        
        function getCartItems() {
            try {
                return JSON.parse(localStorage.getItem(CART_KEY) || '[]');
            } catch(e) {
                return [];
            }
        }
        
        function saveCartItems(items) {
            localStorage.setItem(CART_KEY, JSON.stringify(items));
            updateCartBadge();
        }
        
        function updateCartBadge() {
            const items = getCartItems();
            const count = items.reduce((sum, item) => sum + item.quantity, 0);
            localStorage.setItem('myshop_cart_count', String(count));
        }
        
        function addToCart() {
            var isLoggedIn = <?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>;
            if (!isLoggedIn) {
                if (confirm('Bạn cần đăng nhập để thêm sản phẩm vào giỏ hàng. Đăng nhập ngay?')) {
                    window.location.href = 'dangnhap.php?redirect=' + encodeURIComponent(window.location.pathname + window.location.search);
                }
                return;
            }
            const productId = <?php echo (int)$san_pham['id']; ?>;
            const productName = <?php echo json_encode($san_pham['ten_san_pham']); ?>;
            const productPrice = <?php echo (int)$san_pham['gia']; ?>;
            const productImage = <?php echo json_encode($san_pham['hinh_anh'] ?? ''); ?>;
            const quantity = parseInt(document.getElementById('so_luong').value) || 1;
            let items = getCartItems();
            const existingIndex = items.findIndex(item => item.id === productId);
            if (existingIndex !== -1) {
                items[existingIndex].quantity += quantity;
            } else {
                items.push({
                    id: productId,
                    name: productName,
                    price: productPrice,
                    image: productImage,
                    quantity: quantity
                });
            }
            saveCartItems(items);
            renderMiniCart();
            showMiniCart();
            showAddToCartNotification(productName, quantity);
        }

        function showAddToCartNotification(name, qty) {
            let noti = document.getElementById('addToCartNoti');
            if (!noti) {
                noti = document.createElement('div');
                noti.id = 'addToCartNoti';
                noti.style.position = 'fixed';
                noti.style.top = '30px';
                noti.style.right = '30px';
                noti.style.zIndex = '99999';
                noti.style.background = '#059669';
                noti.style.color = 'white';
                noti.style.padding = '18px 32px';
                noti.style.borderRadius = '10px';
                noti.style.fontWeight = 'bold';
                noti.style.fontSize = '1.1rem';
                noti.style.boxShadow = '0 4px 16px rgba(5,150,105,0.15)';
                noti.style.display = 'none';
                document.body.appendChild(noti);
            }
            noti.innerHTML = `<i class='fas fa-check-circle' style='margin-right:8px;'></i> Đã thêm <b>${qty}</b> sản phẩm <b>${name}</b> vào giỏ hàng!`;
            noti.style.display = 'block';
            setTimeout(() => { noti.style.display = 'none'; }, 2000);
        }
        
        function buyNow() {
            // Kiểm tra đăng nhập phía client (dựa vào biến PHP)
            var isLoggedIn = <?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>;
            if (!isLoggedIn) {
                if (confirm('Bạn cần đăng nhập để mua hàng. Đăng nhập ngay?')) {
                    window.location.href = 'dangnhap.php?redirect=' + encodeURIComponent(window.location.pathname + window.location.search);
                }
                return;
            }
            const productId = <?php echo (int)$san_pham['id']; ?>;
            const productName = <?php echo json_encode($san_pham['ten_san_pham']); ?>;
            const productPrice = <?php echo (int)$san_pham['gia']; ?>;
            const productImage = <?php echo json_encode($san_pham['hinh_anh'] ?? ''); ?>;
            const quantity = parseInt(document.getElementById('so_luong').value) || 1;
            // Show checkout modal directly
            openQuickCheckout(productId, productName, productPrice, productImage, quantity);
        }
        
        let quickAppliedPromo = null;
        let quickPromoListLoaded = false;
        
        function openQuickCheckout(id, name, price, image, quantity) {
            // Update modal content
            document.getElementById('quickProductName').textContent = name;
            document.getElementById('quickProductQty').textContent = quantity;
            document.getElementById('quickProductImage').src = 'uploads/' + (image || 'no-image.jpg');
            
            const subtotal = price * quantity;
            const shipping = 30000;
            const total = subtotal + shipping;
            
            document.getElementById('quickSubtotal').textContent = formatPrice(subtotal);
            document.getElementById('quickShipping').textContent = formatPrice(shipping);
            document.getElementById('quickTotal').textContent = formatPrice(total);
            
            // Reset promo
            quickAppliedPromo = null;
            document.getElementById('quickPromoApplied').style.display = 'none';
            document.getElementById('quickPromoInput').value = '';
            
            // Store data for submission
            window.quickCheckoutData = { id, name, price, image, quantity, subtotal, shipping };
            
            // Setup payment method listener
            const paymentSelect = document.getElementById('quickPaymentSelect');
            paymentSelect.addEventListener('change', function() {
                const bankInfo = document.getElementById('quickBankTransferInfo');
                if (this.value === 'bank_transfer') {
                    bankInfo.style.display = 'block';
                } else {
                    bankInfo.style.display = 'none';
                    document.getElementById('quickQrCodeSection').style.display = 'none';
                    document.getElementById('quickShowQRCheckbox').checked = false;
                }
            });
            
            // Setup QR checkbox listener
            document.getElementById('quickShowQRCheckbox').addEventListener('change', function() {
                const qrSection = document.getElementById('quickQrCodeSection');
                qrSection.style.display = this.checked ? 'block' : 'none';
            });
            
            // Show modal
            document.getElementById('quickCheckoutModal').classList.add('active');
            document.body.style.overflow = 'hidden';
        }
        
        function closeQuickCheckout() {
            document.getElementById('quickCheckoutModal').classList.remove('active');
            document.body.style.overflow = '';
            
            // Reset
            document.getElementById('quickBankTransferInfo').style.display = 'none';
            document.getElementById('quickQrCodeSection').style.display = 'none';
            document.getElementById('quickPaymentSelect').value = '';
            document.getElementById('quickShowQRCheckbox').checked = false;
        }
        
        function formatPrice(price) {
            return new Intl.NumberFormat('vi-VN').format(price) + 'đ';
        }
        
        function updateQuickSummary() {
            const data = window.quickCheckoutData;
            if (!data) return;
            
            const subtotal = data.subtotal;
            const shipping = data.shipping;
            let total = subtotal + shipping;
            
            if (quickAppliedPromo) {
                total = subtotal - quickAppliedPromo.giam + shipping;
            }
            
            document.getElementById('quickTotal').textContent = formatPrice(total);
        }
        
        // Promo functions
        function toggleQuickPromoList() {
            const dropdown = document.getElementById('quickPromoListDropdown');
            const toggle = document.getElementById('quickPromoListToggle');
            
            if (dropdown.style.display === 'none') {
                dropdown.style.display = 'block';
                toggle.classList.remove('fa-chevron-down');
                toggle.classList.add('fa-chevron-up');
                
                if (!quickPromoListLoaded) {
                    loadQuickPromoList();
                }
            } else {
                dropdown.style.display = 'none';
                toggle.classList.remove('fa-chevron-up');
                toggle.classList.add('fa-chevron-down');
            }
        }
        
        function loadQuickPromoList() {
            fetch('ajax_get_promo_list.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.promos) {
                        quickPromoListLoaded = true;
                        renderQuickPromoList(data.promos);
                    } else {
                        document.getElementById('quickPromoListContent').innerHTML = '<p style="text-align: center; color: #64748b; padding: 1rem;">Không có mã khuyến mãi nào</p>';
                    }
                })
                .catch(error => {
                    console.error('Error loading promo list:', error);
                    document.getElementById('quickPromoListContent').innerHTML = '<p style="text-align: center; color: #ef4444; padding: 1rem;"><i class="fas fa-exclamation-circle"></i> Không thể tải danh sách mã</p>';
                });
        }
        
        function renderQuickPromoList(promos) {
            const content = document.getElementById('quickPromoListContent');
            
            if (promos.length === 0) {
                content.innerHTML = '<p style="text-align: center; color: #64748b; padding: 1rem;">Không có mã khuyến mãi nào</p>';
                return;
            }
            
            const html = promos.map(promo => `
                <div onclick="selectQuickPromo('${promo.code}')" style="cursor: pointer; padding: 0.75rem; border: 1px solid #e5e7eb; border-radius: 8px; transition: all 0.2s; background: white;">
                    <div onmouseover="this.parentElement.style.borderColor='#059669'; this.parentElement.style.background='#f0fdf4';" 
                         onmouseout="this.parentElement.style.borderColor='#e5e7eb'; this.parentElement.style.background='white';">
                        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 0.5rem;">
                            <strong style="color: #059669; font-size: 1rem;">${promo.code}</strong>
                            <span style="background: #dcfce7; color: #166534; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.75rem; font-weight: 600;">${promo.discount_text}</span>
                        </div>
                        <p style="color: #1f2937; margin: 0.25rem 0; font-size: 0.875rem;">${promo.name}</p>
                        <div style="display: flex; gap: 1rem; margin-top: 0.5rem; font-size: 0.75rem; color: #64748b;">
                            <span><i class="fas fa-shopping-cart"></i> ${promo.condition_text}</span>
                            <span><i class="fas fa-ticket-alt"></i> ${promo.quantity_text}</span>
                        </div>
                        <p style="color: #64748b; margin: 0.25rem 0 0 0; font-size: 0.75rem;"><i class="fas fa-clock"></i> HSD: ${promo.end_date}</p>
                    </div>
                </div>
            `).join('');
            
            content.innerHTML = html;
        }
        
        function selectQuickPromo(code) {
            document.getElementById('quickPromoInput').value = code;
            document.getElementById('quickPromoListDropdown').style.display = 'none';
            document.getElementById('quickPromoListToggle').classList.remove('fa-chevron-up');
            document.getElementById('quickPromoListToggle').classList.add('fa-chevron-down');
        }
        
        function applyQuickPromo() {
            const promoCode = document.getElementById('quickPromoInput').value.trim();
            if (!promoCode) {
                alert('Vui lòng nhập mã khuyến mãi');
                return;
            }
            
            const data = window.quickCheckoutData;
            if (!data) {
                alert('Có lỗi xảy ra. Vui lòng thử lại!');
                return;
            }
            
            const cartItems = [{
                id: data.id,
                quantity: data.quantity
            }];
            
            fetch('ajax_validate_promo.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `promo_code=${encodeURIComponent(promoCode)}&cart_items=${encodeURIComponent(JSON.stringify(cartItems))}&total=${data.subtotal}`
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    quickAppliedPromo = {
                        ma: result.promo.ma,
                        ten: result.promo.ten,
                        giam: parseFloat(result.gia_tri_giam) || 0,
                        tong_sau_giam: parseFloat(result.tong_sau_giam) || data.subtotal
                    };
                    
                    document.getElementById('quickPromoText').innerHTML = `<i class="fas fa-check-circle"></i> ${quickAppliedPromo.ma} - Giảm ${formatPrice(quickAppliedPromo.giam)}`;
                    document.getElementById('quickPromoApplied').style.display = 'block';
                    document.getElementById('quickPromoInput').value = '';
                    
                    updateQuickSummary();
                    alert('Áp dụng mã thành công!');
                } else {
                    alert(result.message || 'Mã không hợp lệ');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Có lỗi xảy ra khi áp dụng mã');
            });
        }
        
        function removeQuickPromo() {
            quickAppliedPromo = null;
            document.getElementById('quickPromoApplied').style.display = 'none';
            updateQuickSummary();
        }
        
        function handleQuickCheckout(event) {
            event.preventDefault();
            const form = event.target;
            const data = window.quickCheckoutData;
            
            if (!data) {
                alert('Có lỗi xảy ra. Vui lòng thử lại!');
                return false;
            }
            
            // Calculate final total
            let total = data.subtotal + data.shipping;
            if (quickAppliedPromo) {
                total = data.subtotal - quickAppliedPromo.giam + data.shipping;
            }
            
            // Prepare order data
            const orderData = new FormData(form);
            orderData.append('product_id', data.id);
            orderData.append('product_name', data.name);
            orderData.append('product_price', data.price);
            orderData.append('quantity', data.quantity);
            orderData.append('total', total);
            
            if (quickAppliedPromo) {
                orderData.append('promo_code', quickAppliedPromo.ma);
                orderData.append('discount', quickAppliedPromo.giam);
            }
            
            // Submit order
            fetch('xu_ly_thanh_toan.php', {
                method: 'POST',
                body: orderData
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    // Only show the simple success message
                    alert('Đặt hàng thành công!');
                    closeQuickCheckout();
                    form.reset();
                    window.location.href = 'don_hang_cua_toi.php';
                } else {
                    alert('Lỗi: ' + (result.message || 'Không thể đặt hàng'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Có lỗi xảy ra khi đặt hàng!');
            });
            
            return false;
        }
        
        function renderMiniCart() {
            const items = getCartItems();
            const container = document.getElementById('miniCartItems');
            const emptyCart = document.getElementById('emptyMiniCart');
            const footer = document.querySelector('.offcanvas-footer');
            
            if (items.length === 0) {
                container.style.display = 'none';
                emptyCart.style.display = 'block';
                footer.style.display = 'none';
                return;
            }
            
            container.style.display = 'block';
            emptyCart.style.display = 'none';
            footer.style.display = 'block';
            
            container.innerHTML = items.map((item, index) => `
                <div class="mini-cart-item">
                    <img src="uploads/${item.image || 'no-image.jpg'}" alt="${item.name}" class="mini-cart-img">
                    <div class="mini-cart-info">
                        <div class="mini-cart-name">${item.name}</div>
                        <div class="mini-cart-price">${new Intl.NumberFormat('vi-VN').format(item.price)}₫</div>
                        <div class="mini-cart-qty">
                            <button onclick="updateMiniCartQty(${index}, -1)">-</button>
                            <span>${item.quantity}</span>
                            <button onclick="updateMiniCartQty(${index}, 1)">+</button>
                        </div>
                    </div>
                    <button class="mini-cart-remove" onclick="removeFromMiniCart(${index})">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            `).join('');
            
            const total = items.reduce((sum, item) => sum + (item.price * item.quantity), 0);
            document.getElementById('miniCartTotal').textContent = new Intl.NumberFormat('vi-VN').format(total) + '₫';
        }
        
        function updateMiniCartQty(index, delta) {
            const items = getCartItems();
            if (items[index]) {
                items[index].quantity += delta;
                if (items[index].quantity < 1) items[index].quantity = 1;
                saveCartItems(items);
                renderMiniCart();
            }
        }
        
        function removeFromMiniCart(index) {
            const items = getCartItems();
            items.splice(index, 1);
            saveCartItems(items);
            renderMiniCart();
        }
        
        function showMiniCart() {
            document.getElementById('miniCartBackdrop').classList.add('show');
            document.getElementById('miniCart').classList.add('show');
            document.body.style.overflow = 'hidden';
        }
        
        function hideMiniCart() {
            document.getElementById('miniCartBackdrop').classList.remove('show');
            document.getElementById('miniCart').classList.remove('show');
            document.body.style.overflow = '';
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            renderMiniCart();
            // Auto-refresh reviews every 10 seconds
            setInterval(loadReviews, 10000);
        });
        
        // Load reviews via AJAX
        function loadReviews() {
            fetch('ajax_load_reviews.php?product_id=<?php echo $id; ?>')
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.html) {
                        document.getElementById('reviewsList').innerHTML = data.html;
                    }
                })
                .catch(err => console.error('Error loading reviews:', err));
        }
    </script>
    </head>
<body>
    <!-- Cart Icon with Badge at Top (fa-bag-shopping, badge xanh đậm) -->
    <div style="position: fixed; top: 18px; right: 24px; z-index: 1100;">
        <a href="giohang.php" style="background: white; border: 2px solid #1d3e1f; border-radius: 50%; width: 54px; height: 54px; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 8px rgba(0,0,0,0.08); position: relative; cursor: pointer; transition: box-shadow 0.2s; text-decoration: none;">
            <i class="fas fa-bag-shopping" style="font-size: 1.7rem; color: #1d3e1f;"></i>
            <span id="cartBadge" style="position: absolute; top: 7px; right: 7px; background: #295c2a; color: white; font-size: 0.85rem; font-weight: bold; border-radius: 50%; padding: 0 6px; min-width: 18px; height: 18px; line-height: 18px; text-align: center; display: none;">0</span>
        </a>
    </div>
    <script>
    // Update cart badge on page load and after cart changes
    function updateCartBadge() {
        const items = getCartItems();
        const count = items.reduce((sum, item) => sum + item.quantity, 0);
        const badge = document.getElementById('cartBadge');
        if (badge) {
            if (count > 0) {
                badge.textContent = count;
                badge.style.display = 'inline-block';
            } else {
                badge.style.display = 'none';
            }
        }
        localStorage.setItem('myshop_cart_count', String(count));
    }
    document.addEventListener('DOMContentLoaded', function() {
        updateCartBadge();
        // ...existing code...
    });
    </script>
    <div class="container">
        <a href="san-pham.php" class="back"><i class="fas fa-arrow-left"></i> Quay lại Sản phẩm</a>

        <div class="card">
            <div class="grid">
                <div>
                    <?php if (!empty($san_pham['hinh_anh'])): ?>
                        <img class="image" src="uploads/<?php echo htmlspecialchars($san_pham['hinh_anh']); ?>" alt="<?php echo htmlspecialchars($san_pham['ten_san_pham']); ?>">
                    <?php else: ?>
                        <img class="image" src="images/no-image.jpg" alt="No image">
                    <?php endif; ?>
                </div>
                <div>
                    <h1 class="name"><?php echo htmlspecialchars($san_pham['ten_san_pham']); ?></h1>
                    <div class="category">Danh mục: <?php echo htmlspecialchars($san_pham['ten_danh_muc']); ?></div>
                    <div class="price"><?php echo number_format((int)$san_pham['gia'], 0, ',', '.'); ?>đ</div>
                    <?php if($isLocked): ?>
                        <div class="stock" style="color: #e74c3c; font-weight: 600;"><i class="fas fa-times-circle"></i> Hết hàng</div>
                    <?php else: ?>
                        <div class="stock">Còn lại: <?php echo (int)$san_pham['so_luong']; ?> sản phẩm</div>
                    <?php endif; ?>
                    <?php if(!$isLocked): ?>
                    <div class="qty">
                        <label for="so_luong"><i class="fas fa-boxes"></i> Số lượng:</label>
                        <input type="number" name="so_luong" id="so_luong" value="1" min="1" max="<?php echo (int)$san_pham['so_luong']; ?>" required>
                    </div>
                    <div class="actions">
                        <button type="button" class="btn btn-cart" onclick="addToCart()" id="addToCartBtn">
                            <i class="fas fa-shopping-cart"></i> Thêm vào giỏ
                        </button>
                        <button type="button" class="btn btn-buy" onclick="buyNow()">
                            <i class="fas fa-bolt"></i> Mua ngay
                        </button>
                    </div>
                    <?php else: ?>
                    <div style="padding: 20px; background: linear-gradient(135deg, #fee 0%, #fdd 100%); border-radius: 12px; color: #c33; margin: 16px 0; border-left: 4px solid #ef4444;">
                        <i class="fas fa-exclamation-triangle"></i> Sản phẩm hiện đang hết hàng. Vui lòng quay lại sau.
                    </div>
                    <?php endif; ?>
                    <div class="desc"><?php echo nl2br(htmlspecialchars((string)$san_pham['mo_ta'])); ?></div>
                </div>
            </div>
        </div>

        <div id="comments" class="comments">
            <h3><i class="fas fa-comments"></i> Đánh Giá & Nhận Xét</h3>
            <div style="background: #fdfbe8; padding: 16px; border-radius: 10px; border-left: 4px solid #9bc26f;">
                <strong style="color: #1d3e1f;">Điểm trung bình:</strong> <span class="stars">
                    <?php for ($i=1;$i<=5;$i++): ?>
                        <?php if ($i <= round($avg)): ?><i class="fas fa-star"></i><?php else: ?><i class="far fa-star"></i><?php endif; ?>
                    <?php endfor; ?>
                </span>
                <span style="margin-left:8px;color:#7fa84e;font-weight:600;">(<?php echo $avg; ?> / 5 — <?php echo $count; ?> đánh giá)</span>
            </div>

            <?php if ($has_purchased): ?>
                <!-- Hiển thị form đánh giá chỉ khi đã mua hàng -->
                <div style="background:#ecfdf5;padding:16px;border-radius:12px;margin:16px 0;border-left:4px solid #10b981;">
                    <p style="color:#059669;font-weight:600;margin-bottom:12px;"><i class="fas fa-check-circle"></i> Bạn đã mua sản phẩm này! Hãy chia sẻ đánh giá của bạn.</p>
                    <a href="don_hang_cua_toi.php" class="btn btn-buy" style="font-size:14px;padding:10px 20px;width:auto;display:inline-flex;">
                        <i class="fas fa-star"></i> Đánh giá ngay tại Đơn hàng của tôi
                    </a>
                </div>
            <?php else: ?>
                <div style="background:#fef3c7;padding:16px;border-radius:12px;margin:16px 0;border-left:4px solid #f59e0b;">
                    <p style="color:#92400e;font-weight:600;"><i class="fas fa-info-circle"></i> Chỉ khách hàng đã mua và nhận hàng mới có thể đánh giá sản phẩm này.</p>
                </div>
            <?php endif; ?>

            <!-- Danh sách đánh giá -->
            <div class="comment-list" id="reviewsList">
                <?php if (empty($comments)): ?>
                    <p style="color:#999;font-style:italic;margin-top:20px;">Chưa có đánh giá nào.</p>
                <?php else: ?>
                    <?php foreach ($comments as $c): ?>
                        <div class="comment" data-review-id="<?php echo $c['id']; ?>">
                            <div class="meta">
                                <strong><?php echo htmlspecialchars($c['user_name']); ?></strong>
                                - <?php echo date('d/m/Y H:i', strtotime($c['created_at'])); ?>
                                <span class="stars"><?php for($x=1;$x<=5;$x++){ echo $x <= (int)$c['rating'] ? '<i class="fas fa-star"></i>' : '<i class="far fa-star"></i>'; } ?></span>
                            </div>
                            <p><?php echo nl2br(htmlspecialchars($c['comment'])); ?></p>
                            <?php if (!empty($c['admin_reply'])): ?>
                                <div class="admin-reply" style="margin-top:12px;padding:12px;background:#fdfbe8;border-left:3px solid #7fa84e;border-radius:8px;">
                                    <strong style="color:#1d3e1f;"><i class="fas fa-reply"></i> Phản hồi từ Shop:</strong>
                                    <p style="margin-top:8px;color:#3d6b3f;"><?php echo nl2br(htmlspecialchars($c['admin_reply'])); ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

<!-- Quick Checkout Modal -->
<div class="modal-overlay" id="quickCheckoutModal">
    <div class="checkout-modal" style="max-width: 650px;">
        <div class="modal-header">
            <h3 class="modal-title"><i class="fas fa-shopping-bag"></i> Thanh toán nhanh</h3>
            <button class="modal-close" onclick="closeQuickCheckout()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div class="modal-body">
            <!-- Product Summary -->
            <div style="background: white; border: 2px solid #e8edc7; border-radius: 12px; padding: 1.5rem; margin-bottom: 1.5rem;">
                <h4 style="color: #1d3e1f; margin-bottom: 1rem; font-size: 1.125rem;">
                    <i class="fas fa-box"></i> Sản phẩm
                </h4>
                <div style="display: flex; gap: 1rem; align-items: center;">
                    <img id="quickProductImage" src="" alt="" style="width: 80px; height: 80px; object-fit: cover; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                    <div style="flex: 1;">
                        <div id="quickProductName" style="font-weight: 600; color: #1d3e1f; font-size: 1.1rem; margin-bottom: 0.5rem;"></div>
                        <div style="color: #7fa84e; font-weight: 600;">Số lượng: <span id="quickProductQty"></span></div>
                    </div>
                </div>
            </div>
            
            <!-- Checkout Form -->
            <form id="quickCheckoutForm" onsubmit="return handleQuickCheckout(event)" class="checkout-form">
                <div class="form-group">
                    <label class="form-label">Họ và tên *</label>
                    <input type="text" class="form-input" name="fullname" required placeholder="Nguyễn Văn A" 
                           value="<?php echo isset($user_info['ho_ten']) ? htmlspecialchars($user_info['ho_ten']) : ''; ?>">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Số điện thoại *</label>
                        <input type="tel" class="form-input" name="phone" required placeholder="0123456789">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-input" name="email" placeholder="email@example.com" 
                               value="<?php echo isset($user_info['email']) ? htmlspecialchars($user_info['email']) : ''; ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Địa chỉ giao hàng *</label>
                    <input type="text" class="form-input" name="address" required placeholder="Số nhà, tên đường" 
                           value="<?php echo isset($user_info['dia_chi']) ? htmlspecialchars($user_info['dia_chi']) : ''; ?>">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Thành phố *</label>
                        <select class="form-select" name="city" required>
                            <option value="">Chọn thành phố</option>
                            <option value="Hồ Chí Minh">Hồ Chí Minh</option>
                            <option value="Hà Nội">Hà Nội</option>
                            <option value="Đà Nẵng">Đà Nẵng</option>
                            <option value="Cần Thơ">Cần Thơ</option>
                            <option value="Vĩnh Long">Vĩnh Long</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Phương thức thanh toán *</label>
                        <select class="form-select" name="payment" id="quickPaymentSelect" required>
                            <option value="">Chọn phương thức thanh toán</option>
                            <option value="cod">Thanh toán khi nhận hàng (COD)</option>
                            <option value="bank_transfer">Chuyển khoản ngân hàng</option>
                        </select>
                    </div>
                </div>
                
                <!-- Bank Transfer Info (Hidden by default) -->
                <div id="quickBankTransferInfo" style="display: none; background: #fdfbe8; padding: 1.5rem; border-radius: 12px; border: 2px solid #9bc26f; margin-top: 1rem;">
                    <h4 style="color: #1d3e1f; margin-bottom: 1rem; font-size: 1.125rem;">
                        <i class="fas fa-university"></i> Thông tin chuyển khoản
                    </h4>
                    <div style="background: white; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
                        <p style="margin: 0.5rem 0;"><strong>Chủ tài khoản:</strong> HUYNH MINH KHAI HOAN</p>
                        <p style="margin: 0.5rem 0;"><strong>Số tài khoản:</strong> <span style="color: #059669; font-weight: 700; font-size: 1.1rem;">0795474219</span></p>
                        <p style="margin: 0.5rem 0;"><strong>Ngân hàng:</strong> MB Bank</p>
                    </div>
                    
                    <div style="margin-top: 1rem;">
                        <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                            <input type="checkbox" id="quickShowQRCheckbox" style="width: 18px; height: 18px; cursor: pointer;">
                            <span style="font-weight: 600; color: #1d3e1f;">Hiển thị mã QR thanh toán</span>
                        </label>
                    </div>
                </div>
                
                <!-- QR Code Section (Hidden by default) -->
                <div id="quickQrCodeSection" style="display: none; margin-top: 1.5rem; text-align: center; background: white; padding: 2rem; border-radius: 12px; border: 2px solid #9bc26f;">
                    <h4 style="color: #1d3e1f; margin-bottom: 1rem;">
                        <i class="fas fa-qrcode"></i> Quét mã QR để thanh toán
                    </h4>
                    <img src="images/maqr.jpg" alt="QR Code" style="max-width: 300px; width: 100%; height: auto; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                    <p style="color: #64748b; margin-top: 1rem; font-size: 0.875rem;">
                        Quét mã QR bằng ứng dụng ngân hàng của bạn để thanh toán
                    </p>
                </div>

                <!-- Promo Code Section in Modal -->
                <div style="background: #f0fdf4; padding: 1.5rem; border-radius: 12px; border: 2px solid #9bc26f; margin-top: 1rem;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                        <h4 style="color: #1d3e1f; margin: 0; font-size: 1.125rem;">
                            <i class="fas fa-tags"></i> Mã khuyến mãi
                        </h4>
                    </div>
                    <div style="display: flex; gap: 0.75rem; margin-bottom: 1rem;">
                        <input type="text" id="quickPromoInput" placeholder="Nhập hoặc chọn mã giảm giá" 
                               style="flex: 1; padding: 0.75rem 1rem; border: 2px solid #9bc26f; border-radius: 8px; font-size: 1rem;">
                        <button type="button" onclick="toggleQuickPromoList()" style="background: transparent; border: none; cursor: pointer; color: #059669; font-size: 1.2rem;" title="Xem danh sách mã">
                            <i class="fas fa-chevron-down" id="quickPromoListToggle"></i>
                        </button>
                               <button type="button" onclick="applyQuickPromo()" 
                                style="background: linear-gradient(135deg, #059669, #047857); color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 8px; cursor: pointer; font-weight: 600; white-space: nowrap; transition: all 0.3s;">
                            <i class="fas fa-check"></i> Áp dụng
                        </button>
                    </div>
                    <div id="quickPromoListDropdown" style="display: none; max-height: 200px; overflow-y: auto; background: white; border: 2px solid #9bc26f; border-radius: 8px; padding: 0.5rem; margin-bottom: 1rem;">
                        <div id="quickPromoListContent" style="display: flex; flex-direction: column; gap: 0.5rem;">
                            <p style="text-align: center; color: #64748b; padding: 1rem;">Đang tải...</p>
                        </div>
                    </div>
                    <div id="quickPromoApplied" style="display: none; background: #d1fae5; padding: 0.75rem 1rem; border-radius: 8px; border-left: 4px solid #059669;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span id="quickPromoText" style="color: #065f46; font-weight: 600;"></span>
                            <button type="button" onclick="removeQuickPromo()" 
                                    style="background: #ef4444; color: white; border: none; padding: 0.5rem 1rem; border-radius: 6px; cursor: pointer; font-size: 0.875rem;">
                                <i class="fas fa-times"></i> Xóa
                            </button>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Ghi chú đơn hàng</label>
                    <textarea class="form-textarea" name="note" placeholder="Ghi chú đặc biệt cho đơn hàng (tùy chọn)..."></textarea>
                </div>

                <!-- Order Summary -->
                <div class="checkout-summary-modal">
                    <div class="summary-row">
                        <span>Tạm tính</span>
                        <span id="quickSubtotal">0₫</span>
                    </div>
                    <div class="summary-row">
                        <span>Phí vận chuyển</span>
                        <span id="quickShipping">30.000₫</span>
                    </div>
                    <div class="summary-row total">
                        <span>Tổng cộng</span>
                        <span id="quickTotal">0₫</span>
                    </div>
                </div>

                <button type="submit" class="submit-order-btn">
                    <i class="fas fa-check-circle"></i>
                    Xác nhận đặt hàng
                </button>
            </form>
        </div>
    </div>
</div>

<style>
:root {
    --cream-50: #fffef5;
    --cream-100: #fdfbe8;
    --beige-100: #f5f2d4;
    --beige-200: #e8edc7;
    --taupe-500: #7fa84e;
    --accent-gold: #9bc26f;
    --black: #1d3e1f;
    --charcoal: #3d6b3f;
    --space-sm: 0.5rem;
    --space-md: 0.75rem;
    --space-lg: 1.25rem;
    --space-xl: 1.5rem;
    --radius-md: 8px;
    --radius-lg: 12px;
    --radius-xl: 16px;
    --shadow-lg: 0 10px 25px rgba(0,0,0,0.15);
    --shadow-xl: 0 20px 40px rgba(0,0,0,0.2);
}

.modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.6);
    backdrop-filter: blur(4px);
    z-index: 9999;
    display: none;
    align-items: center;
    justify-content: center;
    animation: fadeIn 0.3s ease;
}

.modal-overlay.active {
    display: flex;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

.checkout-modal {
    background: var(--cream-50);
    border-radius: var(--radius-xl);
    width: 90%;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: var(--shadow-xl);
    animation: slideUp 0.3s ease;
}

@keyframes slideUp {
    from { 
        opacity: 0;
        transform: translateY(2rem);
    }
    to { 
        opacity: 1;
        transform: translateY(0);
    }
}

.modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: var(--space-xl);
    border-bottom: 1px solid var(--beige-200);
    background: var(--beige-100);
}

.modal-title {
    font-size: 1.5rem;
    color: var(--black);
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 10px;
}

.modal-close {
    width: 2.5rem;
    height: 2.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    color: var(--taupe-500);
    transition: all 0.2s;
    background: transparent;
    border: none;
    cursor: pointer;
    font-size: 1.5rem;
}

.modal-close:hover {
    background: var(--beige-100);
    color: var(--black);
    transform: rotate(90deg);
}

.modal-body {
    padding: var(--space-xl);
}

.checkout-form {
    display: flex;
    flex-direction: column;
    gap: var(--space-lg);
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: var(--space-sm);
}

.form-label {
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--black);
}

.form-input,
.form-select,
.form-textarea {
    padding: var(--space-md);
    border: 2px solid var(--beige-200);
    border-radius: var(--radius-md);
    background: var(--cream-100);
    font-size: 1rem;
    color: var(--charcoal);
    transition: all 0.3s ease;
    width: 100%;
}

.form-input:focus,
.form-select:focus,
.form-textarea:focus {
    outline: none;
    border-color: var(--accent-gold);
    background: var(--cream-50);
}

.form-textarea {
    resize: vertical;
    min-height: 80px;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: var(--space-md);
}

.checkout-summary-modal {
    background: var(--cream-100);
    border-radius: var(--radius-lg);
    padding: var(--space-lg);
    margin-top: var(--space-lg);
}

.summary-row {
    display: flex;
    justify-content: space-between;
    padding: 0.5rem 0;
    color: var(--charcoal);
}

.summary-row.total {
    border-top: 2px solid var(--beige-200);
    padding-top: 1rem;
    margin-top: 0.5rem;
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--black);
}

.submit-order-btn {
    width: 100%;
    padding: var(--space-lg);
    background: linear-gradient(135deg, #059669 0%, #10b981 100%) !important;
    color: #ffffff !important;
    border-radius: var(--radius-md);
    font-size: 1rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-top: var(--space-lg);
    transition: all 0.3s ease;
    display: flex !important;
    align-items: center;
    justify-content: center;
    gap: var(--space-sm);
    cursor: pointer;
    border: none;
}

.submit-order-btn:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-lg);
}

@media (max-width: 768px) {
    .form-row {
        grid-template-columns: 1fr;
    }
    .checkout-modal {
        width: 95%;
        max-height: 95vh;
    }
}
</style>

</body>
<script>
    // Rating star click behavior
    (function(){
        const stars = document.querySelectorAll('#ratingStars .star-btn');
        const input = document.getElementById('ratingInput');
        stars.forEach(s => s.addEventListener('click', () => {
            const v = parseInt(s.getAttribute('data-value')) || 5;
            input.value = v;
            stars.forEach(st => st.classList.toggle('active', parseInt(st.getAttribute('data-value')) <= v));
        }));
    })();
</script>
<link rel="stylesheet" href="assets/chatbot.css">
<link rel="stylesheet" href="assets/notifications.css">
<script src="assets/notifications.js" defer></script>
<script src="assets/chatbot.js" defer></script>
</html>


