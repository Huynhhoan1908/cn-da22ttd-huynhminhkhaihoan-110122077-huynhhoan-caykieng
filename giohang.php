<?php 
require_once __DIR__ . '/auth_gate.php';
require_once __DIR__ . '/connect.php';

// Xử lý đặt hàng từ giỏ hàng
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($data['checkout_cart'])) {
    header('Content-Type: application/json');
    
    try {
        $user_id = $_SESSION['user_id'] ?? null;
        $items = $data['items'] ?? [];
        $customer = $data['customer'] ?? [];
        $totals = $data['totals'] ?? [];
        $ma_khuyen_mai = $data['promo_code'] ?? null;
        $giam_gia = $data['promo_discount'] ?? 0;
        
        if (empty($items)) {
            echo json_encode(['success' => false, 'message' => 'Giỏ hàng trống']);
            exit;
        }
        
        $conn->beginTransaction();
        
        // Tạo mã đơn hàng
        $ma_don_hang = 'DH' . date('YmdHis') . rand(100, 999);
        
        // Thêm đơn hàng vào database
        $stmt = $conn->prepare("
            INSERT INTO don_hang (
                ma_don_hang, nguoi_dung_id, ten_khach_hang, so_dien_thoai, 
                email, dia_chi, phuong_thuc_thanh_toan, ghi_chu,
                tong_tien, phi_van_chuyen, ma_khuyen_mai, giam_gia, 
                tong_thanh_toan, trang_thai, ngay_dat
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Chờ xác nhận', NOW())
        ");
        
        $address = ($customer['address'] ?? '') . ', ' . ($customer['city'] ?? '');
        
        $stmt->execute([
            $ma_don_hang,
            $user_id,
            $customer['fullname'] ?? '',
            $customer['phone'] ?? '',
            $customer['email'] ?? '',
            $address,
            $customer['payment'] ?? 'cod',
            $customer['note'] ?? '',
            $totals['subtotal'] ?? 0,
            $totals['shipping'] ?? 0,
            $ma_khuyen_mai,
            $giam_gia,
            $totals['total'] ?? 0
        ]);
        
        $order_id = $conn->lastInsertId();
        
        // Thêm chi tiết đơn hàng và trừ tồn kho
        $stmt_detail = $conn->prepare("
            INSERT INTO chi_tiet_don_hang (don_hang_id, san_pham_id, so_luong, gia, thanh_tien)
            VALUES (?, ?, ?, ?, ?)
        ");
        
        $stmt_update_stock = $conn->prepare("
            UPDATE san_pham SET so_luong = so_luong - ? WHERE id = ?
        ");
        
        foreach ($items as $item) {
            // Thêm chi tiết
            $stmt_detail->execute([
                $order_id,
                $item['id'],
                $item['quantity'],
                $item['price'],
                $item['price'] * $item['quantity']
            ]);
            
            // Trừ tồn kho
            $stmt_update_stock->execute([$item['quantity'], $item['id']]);
        }
        
        // Lưu lịch sử sử dụng mã khuyến mãi (nếu có)
        if ($ma_khuyen_mai && $giam_gia > 0) {
            $stmt_promo = $conn->prepare("
                SELECT id FROM khuyen_mai WHERE ma_khuyen_mai = ? AND trang_thai = 1
            ");
            $stmt_promo->execute([$ma_khuyen_mai]);
            $promo = $stmt_promo->fetch();
            
            if ($promo) {
                $stmt_log = $conn->prepare("
                    INSERT INTO lich_su_khuyen_mai (khuyen_mai_id, don_hang_id, nguoi_dung_id, gia_tri_giam)
                    VALUES (?, ?, ?, ?)
                ");
                $stmt_log->execute([$promo['id'], $order_id, $user_id, $giam_gia]);
                
                // Tăng số lần đã dùng
                $conn->prepare("UPDATE khuyen_mai SET so_lan_da_dung = so_lan_da_dung + 1 WHERE id = ?")->execute([$promo['id']]);
            }
        }
        
        $conn->commit();
        
        echo json_encode([
          'success' => true,
          'message' => 'Đặt hàng thành công'
        ]);
        exit;
        
    } catch (Exception $e) {
        $conn->rollBack();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }
}

// Lấy thông tin user nếu đã đăng nhập
$user_info = null;
if (isset($_SESSION['user_id'])) {
    $stmt = $conn->prepare("SELECT ho_ten, email FROM nguoi_dung WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user_info = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<!doctype html>
<html lang="vi">
<head>

<script>
  var currentUserId = <?php echo isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 'null'; ?>;
</script>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Giỏ Hàng — HuynhHoan</title>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">

<style>
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

/* ==========================================
   PREMIUM MINIMALIST DESIGN SYSTEM
   ========================================== */

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
  
  /* Legacy compatibility */
  --cream-50: var(--white);
  --cream-100: var(--gray-50);
  --cream-200: var(--gray-100);
  --beige-100: var(--gray-100);
  --beige-200: var(--gray-200);
  --beige-300: var(--gray-300);
  --taupe-400: var(--gray-400);
  --taupe-500: var(--gray-500);
  --charcoal: var(--gray-800);
  --black: var(--gray-900);
  --accent-gold: var(--accent);
  --accent-rose: var(--highlight);
  --accent-sage: var(--secondary);
  --accent-terracotta: var(--accent);
  
  /* Typography */
  --font-sans: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
  --font-serif: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
  
  /* Layout */
  --container-max: 1400px;
  
  /* Spacing */
  --space-xs: 0.25rem;
  --space-sm: 0.5rem;
  --space-md: 1rem;
  --space-lg: 1.5rem;
  --space-xl: 2rem;
  --space-2xl: 3rem;
  
  /* Border Radius */
  --radius-sm: 0.25rem;
  --radius-md: 0.5rem;
  --radius-lg: 0.75rem;
  --radius-xl: 1rem;
  --radius-full: 9999px;
  
  /* Shadows */
  --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
  --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
  --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
  --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
}

/* ==========================================
   BASE STYLES
   ========================================== */

* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

body {
  font-family: var(--font-sans);
  font-size: 16px;
  line-height: 1.6;
  color: #1d3e1f;
  background: #fffef5;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
  min-height: 100vh;
}

a {
  color: inherit;
  text-decoration: none;
  transition: all 0.2s ease;
}

button {
  font-family: inherit;
  cursor: pointer;
  border: none;
  background: none;
  transition: all 0.2s ease;
}

img {
  max-width: 100%;
  height: auto;
  display: block;
}

/* ==========================================
   LAYOUT
   ========================================== */

.container {
  max-width: var(--container-max);
  margin: 0 auto;
  padding: 0 var(--space-md);
}

@media (min-width: 640px) {
  .container { padding: 0 var(--space-lg); }
}

@media (min-width: 1024px) {
  .container { padding: 0 var(--space-2xl); }
}

/* ==========================================
   HEADER
   ========================================== */

.header {
  position: sticky;
  top: 0;
  z-index: 40;
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
  border-bottom: 1px solid var(--stone-200);
}

.header .container {
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
}

.nav {
  display: none;
  align-items: center;
  gap: var(--space-2xl);
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
  gap: var(--space-sm);
}

.icon-btn {
  width: 2.5rem;
  height: 2.5rem;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #7fa84e;
  border-radius: var(--radius-lg);
  transition: all 0.2s;
  position: relative;
}

.icon-btn:hover {
  color: #3d6b3f;
  background-color: #fdfbe8;
}

.icon-btn i {
  font-size: 1.25rem;
  color: #316339ff !important;
}

.cart-badge {
  position: absolute;
  top: 0.25rem;
  right: 0.25rem;
  background: var(--black);
  color: var(--cream-50);
  font-size: 0.625rem;
  font-weight: 700;
  width: 1.125rem;
  height: 1.125rem;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
}

/* ==========================================
   PAGE HERO
   ========================================== */

.page-hero {
  background: linear-gradient(135deg, var(--cream-100) 0%, var(--beige-100) 100%);
  padding: var(--space-2xl) 0;
  text-align: center;
  position: relative;
  overflow: hidden;
}

.page-hero::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: radial-gradient(circle at 30% 50%, rgba(201, 169, 97, 0.08) 0%, transparent 50%);
  pointer-events: none;
}

.page-hero .container {
  position: relative;
  z-index: 1;
}

.hero-icon {
  width: 5rem;
  height: 5rem;
  margin: 0 auto var(--space-lg);
  background: var(--black);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--cream-50);
}

.hero-icon i {
  font-size: 2rem;
}

.page-title {
  font-family: var(--font-serif);
  font-size: 3rem;
  font-weight: 700;
  color: var(--black);
  margin-bottom: var(--space-md);
  line-height: 1.1;
}

.page-subtitle {
  font-size: 1.125rem;
  color: var(--taupe-500);
}

/* ==========================================
   CART SECTION
   ========================================== */

.cart-section {
  padding: var(--space-2xl) 0;
}

.cart-layout {
  display: grid;
  grid-template-columns: 1fr;
  gap: var(--space-xl);
}

@media (min-width: 1024px) {
  .cart-layout {
    grid-template-columns: 1fr 400px;
  }
}

/* ==========================================
   CART ITEMS
   ========================================== */

.cart-items {
  background: var(--cream-50);
  border: 1px solid var(--beige-200);
  border-radius: var(--radius-xl);
  padding: var(--space-xl);
}

.cart-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: var(--space-xl);
  padding-bottom: var(--space-lg);
  border-bottom: 2px solid var(--beige-200);
}

.cart-title {
  font-family: var(--font-serif);
  font-size: 1.75rem;
  color: var(--black);
}

.cart-count {
  font-size: 0.875rem;
  color: var(--taupe-500);
  font-weight: 500;
}

.cart-item {
  display: grid;
  grid-template-columns: 120px 1fr auto;
  gap: var(--space-lg);
  padding: var(--space-lg);
  background: var(--cream-100);
  border: 1px solid var(--beige-200);
  border-radius: var(--radius-lg);
  margin-bottom: var(--space-md);
  transition: all 0.3s ease;
}

.cart-item:hover {
  transform: translateX(4px);
  box-shadow: var(--shadow-md);
}

.cart-item-image {
  width: 120px;
  height: 150px;
  border-radius: var(--radius-md);
  overflow: hidden;
  background: var(--beige-100);
}

.cart-item-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.cart-item-info {
  display: flex;
  flex-direction: column;
  justify-content: space-between;
}

.cart-item-name {
  font-size: 1.125rem;
  font-weight: 600;
  color: var(--black);
  margin-bottom: var(--space-xs);
}

.cart-item-details {
  display: flex;
  gap: var(--space-md);
  font-size: 0.875rem;
  color: var(--taupe-500);
  margin-bottom: var(--space-sm);
}

.cart-item-detail {
  display: flex;
  align-items: center;
  gap: var(--space-xs);
}

.cart-item-price {
  font-family: var(--font-serif);
  font-size: 1.375rem;
  font-weight: 700;
  color: var(--black);
}

.cart-item-actions {
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  align-items: flex-end;
}

.remove-btn {
  width: 2.5rem;
  height: 2.5rem;
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--taupe-400);
  border-radius: var(--radius-md);
  transition: all 0.2s ease;
}

.remove-btn:hover {
  background: var(--accent-rose);
  color: var(--cream-50);
  transform: scale(1.1);
}

.qty-control {
  display: flex;
  align-items: center;
  border: 2px solid var(--beige-200);
  border-radius: var(--radius-md);
  overflow: hidden;
  background: var(--cream-50);
}

.qty-btn {
  width: 2.5rem;
  height: 2.5rem;
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--taupe-500);
  background: transparent;
  transition: all 0.2s ease;
}

.qty-btn:hover {
  background: var(--beige-100);
  color: var(--black);
}

.qty-value {
  width: 3rem;
  text-align: center;
  font-weight: 600;
  color: var(--black);
  font-size: 1rem;
}

/* ==========================================
   EMPTY CART
   ========================================== */

.empty-cart {
  text-align: center;
  padding: var(--space-2xl) var(--space-xl);
}

.empty-icon {
  width: 8rem;
  height: 8rem;
  margin: 0 auto var(--space-xl);
  background: var(--beige-100);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--taupe-400);
}

.empty-icon i {
  font-size: 3.5rem;
}

.empty-title {
  font-family: var(--font-serif);
  font-size: 2rem;
  color: var(--black);
  margin-bottom: var(--space-md);
}

.empty-desc {
  font-size: 1.125rem;
  color: var(--taupe-500);
  margin-bottom: var(--space-xl);
}

/* ==========================================
   CART SUMMARY
   ========================================== */

.cart-summary {
  background: var(--cream-50);
  border: 1px solid var(--beige-200);
  border-radius: var(--radius-xl);
  padding: var(--space-xl);
  position: sticky;
  top: 6rem;
  display: block !important;
  visibility: visible !important;
  opacity: 1 !important;
}

.summary-title {
  font-family: var(--font-serif);
  font-size: 1.5rem;
  color: var(--black);
  margin-bottom: var(--space-xl);
  padding-bottom: var(--space-lg);
  border-bottom: 2px solid var(--beige-200);
}

.summary-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: var(--space-md) 0;
  font-size: 1rem;
  color: var(--charcoal);
}

.summary-row.subtotal {
  font-size: 1.125rem;
}

.summary-row.shipping {
  padding-bottom: var(--space-lg);
  border-bottom: 2px solid var(--beige-200);
}

.summary-row.total {
  padding-top: var(--space-lg);
  font-size: 1.5rem;
  font-weight: 700;
  color: var(--black);
}

.summary-row.total .label {
  font-family: var(--font-serif);
}

.shipping-free {
  color: var(--accent-sage);
  font-weight: 600;
}

.promo-section {
  margin: var(--space-xl) 0;
  padding: var(--space-lg) 0;
  border-top: 1px solid var(--beige-200);
  border-bottom: 1px solid var(--beige-200);
}

.promo-title {
  font-size: 0.875rem;
  font-weight: 600;
  color: var(--black);
  margin-bottom: var(--space-sm);
}

.promo-input-wrapper {
  display: flex;
  gap: var(--space-sm);
}

.promo-input {
  flex: 1;
  padding: var(--space-md);
  border: 2px solid var(--beige-200);
  border-radius: var(--radius-md);
  background: var(--cream-100);
  font-size: 0.875rem;
  transition: all 0.3s ease;
}

.promo-input:focus {
  outline: none;
  border-color: var(--accent-gold);
  background: var(--cream-50);
}

.promo-btn {
  padding: var(--space-md) var(--space-lg);
  background: var(--black);
  color: var(--cream-50);
  border-radius: var(--radius-md);
  font-size: 0.875rem;
  font-weight: 600;
  transition: all 0.3s ease;
}

.promo-btn:hover {
  background: var(--charcoal);
  transform: translateY(-2px);
}

.checkout-btn {
  width: 100%;
  padding: var(--space-lg);
  background: var(--black);
  color: var(--cream-50);
  border-radius: var(--radius-md);
  font-size: 1rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  display: flex !important;
  visibility: visible !important;
  opacity: 1 !important;
  align-items: center;
  justify-content: center;
  gap: var(--space-sm);
  margin-bottom: var(--space-md);
  transition: all 0.3s ease;
}

.checkout-btn:hover {
  background: var(--charcoal);
  transform: translateY(-2px);
  box-shadow: var(--shadow-lg);
}

.continue-shopping {
  width: 100%;
  padding: var(--space-md);
  border: 2px solid var(--beige-200);
  background: transparent;
  color: var(--charcoal);
  border-radius: var(--radius-md);
  font-size: 0.875rem;
  font-weight: 600;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: var(--space-sm);
  transition: all 0.3s ease;
}

.continue-shopping:hover {
  background: var(--cream-100);
  border-color: var(--beige-300);
}

/* ==========================================
   FEATURES
   ========================================== */

.features-section {
  background: var(--cream-50);
  border: 1px solid var(--beige-200);
  border-radius: var(--radius-xl);
  padding: var(--space-2xl);
  margin-top: var(--space-2xl);
}

.features-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: var(--space-xl);
}

@media (min-width: 768px) {
  .features-grid {
    grid-template-columns: repeat(3, 1fr);
  }
}

.feature-item {
  display: flex;
  align-items: flex-start;
  gap: var(--space-lg);
}

.feature-icon {
  width: 3.5rem;
  height: 3.5rem;
  background: var(--black);
  color: var(--cream-50);
  border-radius: var(--radius-lg);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.feature-icon i {
  font-size: 1.5rem;
}

.feature-content h3 {
  font-size: 1rem;
  font-weight: 700;
  color: var(--black);
  margin-bottom: var(--space-xs);
}

.feature-content p {
  font-size: 0.875rem;
  color: var(--taupe-500);
}

/* ==========================================
   FOOTER
   ========================================== */

.footer {
  background: var(--beige-100);
  padding: var(--space-2xl) 0;
  text-align: center;
  margin-top: var(--space-2xl);
}

.footer-text {
  font-size: 0.875rem;
  color: var(--taupe-500);
}

/* ==========================================
   BUTTONS
   ========================================== */

.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: var(--space-sm);
  padding: var(--space-md) var(--space-xl);
  font-size: 1rem;
  font-weight: 600;
  border-radius: var(--radius-md);
  transition: all 0.3s ease;
}

.btn-primary {
  background: var(--black);
  color: var(--cream-50);
}

.btn-primary:hover {
  background: var(--charcoal);
  transform: translateY(-2px);
  box-shadow: var(--shadow-lg);
}

/* ==========================================
   CHECKOUT MODAL
   ========================================== */

.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.6);
  backdrop-filter: blur(4px);
  z-index: 100;
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
  max-width: 600px;
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
}

.modal-title {
  font-family: var(--font-serif);
  font-size: 1.75rem;
  color: var(--black);
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
}

.modal-close:hover {
  background: var(--beige-100);
  color: var(--black);
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

.submit-order-btn {
  width: 100%;
  padding: var(--space-lg);
  background: #1d3e1f !important;
  color: #ffffff !important;
  border-radius: var(--radius-md);
  font-size: 1rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  margin-top: var(--space-lg);
  margin-bottom: var(--space-lg);
  transition: all 0.3s ease;
  display: flex !important;
  align-items: center;
  justify-content: center;
  gap: var(--space-sm);
  cursor: pointer;
  border: none;
}

.submit-order-btn:hover {
  background: #3d6b3f !important;
  transform: translateY(-2px);
  box-shadow: var(--shadow-lg);
}

/* ==========================================
   RESPONSIVE
   ========================================== */

@media (max-width: 768px) {
  .cart-item {
    grid-template-columns: 100px 1fr;
  }

  .cart-item-actions {
    grid-column: 1 / -1;
    flex-direction: row;
    justify-content: space-between;
    margin-top: var(--space-md);
  }

  .form-row {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 1023px) {
  .hide-mobile { display: none !important; }
}
</style>
</head>
<body>

<!-- Announcement Bar -->

<!-- Header -->
<header class="header">
  <div class="container">
    <a href="/" class="brand-logo" style="display:flex;align-items:center;gap:12px;">
      <img src="images/logo.jpg" alt="HuynhHoan Logo" style="height:45px;width:auto;border-radius:8px;">
      <span style="font-weight:600;font-size:1.4rem;">HuynhHoan</span>
    </a>

    <nav class="nav">
    <nav class="nav">
      <a href="trangchu.php">Trang chủ</a>
      <a href="baiviet.php">Bài viết</a>
      <a href="san-pham.php">Sản Phẩm</a>
      <a href="don_hang_cua_toi.php">Theo Dõi Đơn Hàng</a>
      <a href="lienhe.php">Liên Hệ</a>
    </nav>

    <div class="header-actions">
        <!-- Notification bell/dropdown sẽ được tự động chèn bởi notifications.js nếu chưa có -->
      <a href="dangnhap.php" class="icon-btn hide-mobile" title="Tài khoản">
        <i class="fas fa-sign-out-alt"></i>
      </a>
    </div>
  </div>
</header>

<!-- Page Hero -->
<section class="page-hero">
  <div class="container">
    <div class="hero-icon">
      <i class="fas fa-shopping-bag"></i>
    </div>
    <h1 class="page-title">Giỏ Hàng</h1>
    <p class="page-subtitle">Kiểm tra và hoàn tất đơn hàng của bạn</p>
  </div>
</section>

<!-- Cart Section -->
<div class="container">
  <section class="cart-section">
    <div class="cart-layout">
      <!-- Cart Items -->
      <div class="cart-items">
        <div class="cart-header">
          <h2 class="cart-title">Sản phẩm</h2>
          <span class="cart-count"><span id="itemCount">0</span> sản phẩm</span>
        </div>

        <div id="cartItemsContainer">
          <!-- Cart items will be dynamically inserted here -->
        </div>

        <!-- Empty Cart Message -->
        <div id="emptyCart" class="empty-cart" style="display: none;">
          <div class="empty-icon">
            <i class="fas fa-shopping-bag"></i>
          </div>
          <h3 class="empty-title">Giỏ hàng trống</h3>
          <p class="empty-desc">Hãy thêm sản phẩm yêu thích vào giỏ hàng để tiếp tục mua sắm</p>
          <a href="san-pham.php" class="btn btn-primary">
            <i class="fas fa-arrow-left"></i>
            Tiếp tục mua sắm
          </a>
        </div>
      </div>

      <!-- Cart Summary -->
      <div class="cart-summary">
        <h3 class="summary-title">Tổng đơn hàng</h3>

        <div class="summary-row subtotal">
          <span class="label">Tạm tính:</span>
          <span class="value" id="subtotalAmount">0₫</span>
        </div>

        <div class="summary-row shipping">
          <span class="label">Phí vận chuyển:</span>
          <span class="value" id="shippingAmount">30.000₫</span>
        </div>

        <div class="summary-row total">
          <span class="label">Tổng cộng:</span>
          <span class="value" id="totalAmount">0₫</span>
        </div>

        <!-- Checkout Button -->
        <button class="checkout-btn" id="checkoutBtn" onclick="openCheckout()" style="display: flex !important;">
          <i class="fas fa-lock"></i>
          Tiến hành thanh toán
        </button>

        <a href="san-pham.php" class="continue-shopping">
          <i class="fas fa-arrow-left"></i>
          Tiếp tục mua sắm
        </a>
      </div>
    </div>

    <!-- Features -->
    <div class="features-section">
      <div class="features-grid">
        <div class="feature-item">
          <div class="feature-icon">
            <i class="fas fa-truck"></i>
          </div>
          <div class="feature-content">
            <h3>Giao hàng miễn phí</h3>
            <p>Đơn hàng từ 500.000₫ trở lên</p>
          </div>
        </div>

        <div class="feature-item">
          <div class="feature-icon">
            <i class="fas fa-shield-alt"></i>
          </div>
          <div class="feature-content">
            <h3>Thanh toán an toàn</h3>
            <p>Bảo mật thông tin 100%</p>
          </div>
        </div>

        <div class="feature-item">
          <div class="feature-icon">
            <i class="fas fa-sync-alt"></i>
          </div>
          <div class="feature-content">
            <h3>Đổi trả dễ dàng</h3>
            <p>Trong vòng 30 ngày</p>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>

<!-- Footer -->
<footer class="footer">
  <div class="container">
    <p class="footer-text">© <?php echo date('Y'); ?> HuynhHoan — Cây xanh cho cuộc sống</p>
  </div>
</footer>

<!-- Checkout Modal -->
<div class="modal-overlay" id="checkoutModal">
  <div class="checkout-modal">
    <div class="modal-header">
      <h3 class="modal-title">Thông tin giao hàng</h3>
      <button class="modal-close" onclick="closeCheckout()">
        <i class="fas fa-times"></i>
      </button>
    </div>
    
    <div class="modal-body">
      <!-- Checkout Form -->
      <form class="checkout-form" id="checkoutForm" onsubmit="return handleCheckout(event)">
        <div class="form-group">
          <label class="form-label">Họ và tên *</label>
          <input type="text" class="form-input" name="fullname" required placeholder="Nguyễn Văn A" value="<?php echo isset($user_info['ho_ten']) ? htmlspecialchars($user_info['ho_ten']) : ''; ?>">
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Số điện thoại *</label>
            <input type="tel" class="form-input" name="phone" required placeholder="0123456789" value="<?php echo isset($user_info['dien_thoai']) ? htmlspecialchars($user_info['dien_thoai']) : ''; ?>">
          </div>

          <div class="form-group">
            <label class="form-label">Email</label>
            <input type="email" class="form-input" name="email" placeholder="email@example.com" value="<?php echo isset($user_info['email']) ? htmlspecialchars($user_info['email']) : ''; ?>">
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Địa chỉ giao hàng *</label>
          <input type="text" class="form-input" name="address" required placeholder="Số nhà, tên đường" value="<?php echo isset($user_info['dia_chi']) ? htmlspecialchars($user_info['dia_chi']) : ''; ?>">
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
            <select class="form-select" name="payment" id="cartPaymentSelect" required>
              <option value="">Chọn phương thức thanh toán</option>
              <option value="cod">Thanh toán khi nhận hàng (COD)</option>
              <option value="bank_transfer">Chuyển khoản ngân hàng</option>
            </select>
          </div>
        </div>
        
        <!-- Bank Transfer Info (Hidden by default) -->
        <div id="bankTransferInfo" style="display: none; background: #fdfbe8; padding: 1.5rem; border-radius: var(--radius-lg); border: 2px solid #9bc26f; margin-top: 1rem;">
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
              <input type="checkbox" id="showQRCheckbox" style="width: 18px; height: 18px; cursor: pointer;">
              <span style="font-weight: 600; color: #1d3e1f;">Hiển thị mã QR thanh toán</span>
            </label>
          </div>
        </div>
        
        <!-- QR Code Section (Hidden by default) -->
        <div id="cartQrCodeSection" style="display: none; margin-top: 1.5rem; background: white; padding: 2rem; border-radius: var(--radius-lg); border: 2px solid #9bc26f;">
          <h4 style="color: #1d3e1f; margin-bottom: 1rem; text-align: center;">
            <i class="fas fa-qrcode"></i> Quét mã QR để thanh toán
          </h4>
          <div style="display: flex; justify-content: center; align-items: center; width: 100%;">
            <img src="images/maqr.jpg" alt="QR Code" style="max-width: 300px; width: 100%; height: auto; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); display: block;">
          </div>
          <p style="color: #64748b; margin-top: 1rem; font-size: 0.875rem; text-align: center;">
            Quét mã QR bằng ứng dụng ngân hàng của bạn để thanh toán
          </p>
        </div>

        <!-- Promo Code Section in Modal -->
        <div style="background: #f0fdf4; padding: 1.5rem; border-radius: var(--radius-lg); border: 2px solid #9bc26f; margin-top: 1rem;">
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
            <h4 style="color: #1d3e1f; margin: 0; font-size: 1.125rem;">
              <i class="fas fa-tags"></i> Mã khuyến mãi
            </h4>
          </div>
          <div style="display: flex; gap: 0.75rem; margin-bottom: 1rem;">
            <input type="text" id="modalPromoInput" placeholder="Nhập hoặc chọn mã giảm giá" 
                   style="flex: 1; padding: 0.75rem 1rem; border: 2px solid #9bc26f; border-radius: 8px; font-size: 1rem;">
            <button type="button" onclick="toggleModalPromoList()" style="background: transparent; border: none; cursor: pointer; color: #059669; font-size: 1.2rem;" title="Xem danh sách mã">
              <i class="fas fa-chevron-down" id="modalPromoListToggle"></i>
            </button>
            <button type="button" onclick="applyPromoInModal()" 
                    style="background: linear-gradient(135deg, #059669, #047857); color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 8px; cursor: pointer; font-weight: 600; white-space: nowrap; transition: all 0.3s;">
              <i class="fas fa-check"></i> Áp dụng
            </button>
          </div>
          <div id="modalPromoListDropdown" style="display: none; max-height: 200px; overflow-y: auto; background: white; border: 2px solid #9bc26f; border-radius: 8px; padding: 0.5rem; margin-bottom: 1rem;">
            <div id="modalPromoListContent" style="display: flex; flex-direction: column; gap: 0.5rem;">
              <p style="text-align: center; color: #64748b; padding: 1rem;">Đang tải...</p>
            </div>
          </div>
          <div id="modalPromoApplied" style="display: none; background: #d1fae5; padding: 0.75rem 1rem; border-radius: 8px; border-left: 4px solid #059669;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
              <span id="modalPromoText" style="color: #065f46; font-weight: 600;"></span>
              <button type="button" onclick="removePromoInModal()" 
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

        <!-- Summary (Moved below QR when shown) -->
        <div class="checkout-summary-modal" id="modalOrderSummary">
          <div class="summary-row">
            <span>Tạm tính</span>
            <span id="modalSubtotal">0₫</span>
          </div>
          <div class="summary-row">
            <span>Phí vận chuyển</span>
            <span id="modalShipping">30.000₫</span>
          </div>
          <div class="summary-row total">
            <span>Tổng cộng</span>
            <span id="modalTotal">0₫</span>
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

<script>
(function(){
  'use strict';
  
  // ===== PROMO LIST MANAGEMENT =====
  let promoListLoaded = false;
  
  // Toggle promo list dropdown
  window.togglePromoList = function() {
    const dropdown = document.getElementById('promoListDropdown');
    const toggle = document.getElementById('promoListToggle');
    
    if (dropdown.style.display === 'none') {
      dropdown.style.display = 'block';
      toggle.classList.remove('fa-chevron-down');
      toggle.classList.add('fa-chevron-up');
      
      if (!promoListLoaded) {
        loadPromoList();
      }
    } else {
      dropdown.style.display = 'none';
      toggle.classList.remove('fa-chevron-up');
      toggle.classList.add('fa-chevron-down');
    }
  };
  
  // Toggle modal promo list
  window.toggleModalPromoList = function() {
    const dropdown = document.getElementById('modalPromoListDropdown');
    const toggle = document.getElementById('modalPromoListToggle');
    
    if (dropdown.style.display === 'none') {
      dropdown.style.display = 'block';
      toggle.classList.remove('fa-chevron-down');
      toggle.classList.add('fa-chevron-up');
      
      if (!promoListLoaded) {
        loadPromoList();
      }
    } else {
      dropdown.style.display = 'none';
      toggle.classList.remove('fa-chevron-up');
      toggle.classList.add('fa-chevron-down');
    }
  };
  
  // Load promo list from server
  function loadPromoList() {
    fetch('ajax_get_promo_list.php')
      .then(response => response.json())
      .then(data => {
        if (data.success && data.promos) {
          promoListLoaded = true;
          renderPromoList(data.promos);
        } else {
          showPromoError('Không có mã khuyến mãi nào');
        }
      })
      .catch(error => {
        console.error('Error loading promo list:', error);
        showPromoError('Không thể tải danh sách mã');
      });
  }
  
  // Render promo list
  function renderPromoList(promos) {
    const content = document.getElementById('promoListContent');
    const modalContent = document.getElementById('modalPromoListContent');
    
    if (promos.length === 0) {
      const emptyMsg = '<p style=\"text-align: center; color: #64748b; padding: 1rem;\">Không có mã khuyến mãi nào</p>';
      if (content) content.innerHTML = emptyMsg;
      if (modalContent) modalContent.innerHTML = emptyMsg;
      return;
    }
    
    const html = promos.map(promo => `
      <div onclick="selectPromo('${promo.code}')" style="cursor: pointer; padding: 0.75rem; border: 1px solid #e5e7eb; border-radius: 8px; transition: all 0.2s; background: white;">
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
    
    if (content) content.innerHTML = html;
    if (modalContent) modalContent.innerHTML = html;
  }
  
  // Show error in promo list
  function showPromoError(message) {
    const html = `<p style="text-align: center; color: #ef4444; padding: 1rem;"><i class="fas fa-exclamation-circle"></i> ${message}</p>`;
    const content = document.getElementById('promoListContent');
    const modalContent = document.getElementById('modalPromoListContent');
    if (content) content.innerHTML = html;
    if (modalContent) modalContent.innerHTML = html;
  }
  
  // Select promo from list
  window.selectPromo = function(code) {
    // Fill the input
    const input = document.getElementById('promoInput');
    const modalInput = document.getElementById('modalPromoInput');
    
    if (input && input.offsetParent !== null) {
      input.value = code;
      document.getElementById('promoListDropdown').style.display = 'none';
      document.getElementById('promoListToggle').classList.remove('fa-chevron-up');
      document.getElementById('promoListToggle').classList.add('fa-chevron-down');
    }
    
    if (modalInput && modalInput.offsetParent !== null) {
      modalInput.value = code;
      document.getElementById('modalPromoListDropdown').style.display = 'none';
      document.getElementById('modalPromoListToggle').classList.remove('fa-chevron-up');
      document.getElementById('modalPromoListToggle').classList.add('fa-chevron-down');
    }
  };
  
  // ===== CART MANAGEMENT =====

  // Lắng nghe thay đổi checkbox để cập nhật tổng tiền trong modal
  document.addEventListener('change', function(e) {
    if (e.target && e.target.classList.contains('item-checkbox')) {
      if (typeof appliedPromo === 'object' && appliedPromo) {
        updateSummaryWithPromo();
      } else {
        updateSummary();
      }
    }
  });
  const CART_KEY = currentUserId ? 'myshop_cart_' + currentUserId : 'myshop_cart_guest';
  const CART_COUNT_KEY = 'myshop_cart_count';
  
  // Get cart items from localStorage
  function getCartItems() {
    try {
      return JSON.parse(localStorage.getItem(CART_KEY) || '[]');
    } catch(e) {
      return [];
    }
  }
  
  // Save cart items to localStorage
  function saveCartItems(items) {
    localStorage.setItem(CART_KEY, JSON.stringify(items));
    updateCartCount();
    renderCart();
  }
  
  // Update cart count
  function updateCartCount() {
    const items = getCartItems();
    const count = items.reduce((sum, item) => sum + item.quantity, 0);
    localStorage.setItem(CART_COUNT_KEY, String(count));
    
    const badge = document.getElementById('cartBadge');
    const itemCount = document.getElementById('itemCount');
    
    if (badge) {
      badge.textContent = count;
      badge.style.display = count > 0 ? 'flex' : 'none';
    }
    if (itemCount) itemCount.textContent = items.length;
  }
  
  // Format price
  function formatPrice(price) {
    return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(price);
  }
  
  // Remove item from cart
  window.removeFromCart = function(index) {
    const items = getCartItems();
    items.splice(index, 1);
    saveCartItems(items);
  };
  
  // Update quantity
  window.updateQuantity = function(index, delta) {
    const items = getCartItems();
    if (items[index]) {
      items[index].quantity += delta;
      if (items[index].quantity < 1) items[index].quantity = 1;
      if (items[index].quantity > 99) items[index].quantity = 99;
      saveCartItems(items);
    }
  };
  
  // Calculate totals (only selected items)
  function calculateTotals() {
    const items = getCartItems();
    const checkboxes = document.querySelectorAll('.item-checkbox:checked');
    const selectedIndexes = Array.from(checkboxes).map(cb => parseInt(cb.dataset.index));
    
    const subtotal = items.reduce((sum, item, index) => {
      if (selectedIndexes.includes(index)) {
        return sum + (item.price * item.quantity);
      }
      return sum;
    }, 0);
    
    const shipping = subtotal >= 500000 ? 0 : 30000;
    const total = subtotal + shipping;
    
    return { subtotal, shipping, total, selectedCount: selectedIndexes.length };
  }
  
  // Update checkout button based on selection
  window.updateCheckoutButton = function() {
    const totals = calculateTotals();
    document.getElementById('subtotalAmount').textContent = formatPrice(totals.subtotal);
    document.getElementById('shippingAmount').innerHTML = totals.shipping === 0 ? '<span class="shipping-free">Miễn phí</span>' : formatPrice(totals.shipping);
    document.getElementById('totalAmount').textContent = formatPrice(totals.total);
    
    const checkoutBtn = document.getElementById('checkoutBtn');
    if (totals.selectedCount === 0) {
      checkoutBtn.disabled = true;
      checkoutBtn.style.opacity = '0.5';
    } else {
      checkoutBtn.disabled = false;
      checkoutBtn.style.opacity = '1';
    }
  };
  
  // Render cart
  function renderCart() {
    const items = getCartItems();
    const container = document.getElementById('cartItemsContainer');
    const emptyCart = document.getElementById('emptyCart');
    const checkoutBtn = document.getElementById('checkoutBtn');
    
    console.log('renderCart called, items:', items.length);
    console.log('checkoutBtn element:', checkoutBtn);
    
    if (items.length === 0) {
      container.style.display = 'none';
      emptyCart.style.display = 'block';
      if (checkoutBtn) {
        checkoutBtn.disabled = true;
        checkoutBtn.style.opacity = '0.5';
        console.log('Checkout button disabled (empty cart)');
      }
    } else {
      container.style.display = 'block';
      emptyCart.style.display = 'none';
      if (checkoutBtn) {
        checkoutBtn.disabled = false;
        checkoutBtn.style.display = 'flex';
        checkoutBtn.style.opacity = '1';
        console.log('Checkout button enabled, items:', items.length);
      }
      
      container.innerHTML = items.map((item, index) => {
        let imgSrc = item.image || 'https://images.unsplash.com/photo-1515886657613-9f3515b0c78f?w=600';
        // Nếu là link tuyệt đối (http/https) thì giữ nguyên
        if (imgSrc && !imgSrc.startsWith('http')) {
          // Nếu đã có /uploads/ ở đầu nhưng thiếu /WebCN, thêm vào
          if (imgSrc.startsWith('/uploads/')) {
            if (!imgSrc.startsWith('/WebCN/uploads/')) {
              imgSrc = '/WebCN' + imgSrc;
            }
          } else if (imgSrc.startsWith('uploads/')) {
            // Nếu là uploads/abc.jpg thì thêm /WebCN/ vào đầu
            imgSrc = '/WebCN/' + imgSrc;
          } else if (!imgSrc.startsWith('/WebCN/uploads/')) {
            // Nếu chỉ là tên file hoặc đường dẫn tương đối, thêm /WebCN/uploads/
            imgSrc = '/WebCN/uploads/' + imgSrc.replace(/^\/*/, '');
          }
        }
        return `
        <div class="cart-item">
          <input type="checkbox" class="item-checkbox" data-index="${index}" checked 
                 style="width: 20px; height: 20px; cursor: pointer; margin-right: 15px; align-self: center;"
                 onchange="updateCheckoutButton()">
          <div class="cart-item-image">
            <img src="${imgSrc}" alt="${item.name}" onerror="this.src='https://images.unsplash.com/photo-1515886657613-9f3515b0c78f?w=600'">
          </div>
          <div class="cart-item-info">
            <h4 class="cart-item-name">${item.name}</h4>
            <div class="cart-item-details">
              <span class="cart-item-detail">
                <i class="fas fa-tag"></i>
                ${item.category || 'Cây cảnh'}
              </span>
            </div>
            <div class="cart-item-price">${formatPrice(item.price * item.quantity)}</div>
          </div>
          <div class="cart-item-actions">
            <button class="remove-btn" onclick="removeFromCart(${index})" title="Xóa">
              <i class="fas fa-trash-alt"></i>
            </button>
            <div class="qty-control">
              <button class="qty-btn" onclick="updateQuantity(${index}, -1)">
                <i class="fas fa-minus"></i>
              </button>
              <span class="qty-value">${item.quantity}</span>
              <button class="qty-btn" onclick="updateQuantity(${index}, 1)">
                <i class="fas fa-plus"></i>
              </button>
            </div>
          </div>
        </div>
        `;
      }).join('');
    }
    
    updateSummary();
  }
  
  // Update summary
  function updateSummary() {
    const { subtotal, shipping, total } = calculateTotals();
    
    document.getElementById('subtotalAmount').textContent = formatPrice(subtotal);
    document.getElementById('shippingAmount').innerHTML = shipping === 0 
      ? '<span class="shipping-free">Miễn phí</span>' 
      : formatPrice(shipping);
    document.getElementById('totalAmount').textContent = formatPrice(total);
    
    // Update modal summary
    document.getElementById('modalSubtotal').textContent = formatPrice(subtotal);
    document.getElementById('modalShipping').innerHTML = shipping === 0 
      ? '<span class="shipping-free">Miễn phí</span>' 
      : formatPrice(shipping);
    document.getElementById('modalTotal').textContent = formatPrice(total);
  }
  
  // Apply promo code
  let appliedPromo = null;
  
  window.applyPromo = function() {
    const input = document.getElementById('promoInput');
    const code = input.value.trim().toUpperCase();
    
    if (!code) {
      alert('Vui lòng nhập mã giảm giá!');
      return;
    }
    
    const items = getCartItems();
    if (items.length === 0) {
      alert('Giỏ hàng trống!');
      return;
    }
    
    // Call API to validate promo
    fetch('ajax_validate_promo.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `ma_khuyen_mai=${encodeURIComponent(code)}&gio_hang=${encodeURIComponent(JSON.stringify(items))}`
    })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        appliedPromo = {
          ma: data.promo.ma,
          ten: data.promo.ten,
          giam: data.gia_tri_giam,
          tong_sau_giam: data.tong_sau_giam
        };
        
        alert(`✅ Áp dụng mã "${data.promo.ma}" thành công!\\n${data.promo.ten}\\nGiảm: ${formatPrice(data.gia_tri_giam)}`);
        updateSummaryWithPromo();
      } else {
        alert('❌ ' + data.message);
        appliedPromo = null;
      }
    })
    .catch(err => {
      console.error('Error:', err);
      alert('Có lỗi xảy ra!');
    });
  };
  
  window.removePromo = function() {
    appliedPromo = null;
    updateSummary();
  };
  
  function updateSummaryWithPromo() {
    const { subtotal, shipping, total } = calculateTotals();
    document.getElementById('subtotalAmount').textContent = formatPrice(subtotal);
    document.getElementById('shippingAmount').innerHTML = shipping === 0 ? '<span class=\"shipping-free\">Miễn phí</span>' : formatPrice(shipping);
    if (appliedPromo) {
      let discountRow = document.getElementById('discountRow');
      if (!discountRow) {
        discountRow = document.createElement('div');
        discountRow.id = 'discountRow';
        discountRow.className = 'summary-row';
        discountRow.style.color = '#059669';
        document.querySelector('.summary-row.total').insertAdjacentElement('beforebegin', discountRow);
      }
      discountRow.innerHTML = `<span class=\"label\"><i class=\"fas fa-tag\"></i> Giảm (${appliedPromo.ma}):</span><span class=\"value\">-${formatPrice(appliedPromo.giam)}</span>`;
      discountRow.style.display = 'flex';
      // Tính đúng: Subtotal - Discount + Shipping
      const finalTotal = subtotal - appliedPromo.giam + shipping;
      document.getElementById('totalAmount').textContent = formatPrice(finalTotal);
      document.getElementById('modalTotal').textContent = formatPrice(finalTotal);
    } else {
      const discountRow = document.getElementById('discountRow');
      if (discountRow) discountRow.style.display = 'none';
      document.getElementById('totalAmount').textContent = formatPrice(total);
      document.getElementById('modalTotal').textContent = formatPrice(total);
    }
    document.getElementById('modalSubtotal').textContent = formatPrice(subtotal);
    document.getElementById('modalShipping').innerHTML = shipping === 0 ? '<span class=\"shipping-free\">Miễn phí</span>' : formatPrice(shipping);
  }
  
  // Open checkout modal
  window.openCheckout = function() {
    const items = getCartItems();
    if (items.length === 0) {
      alert('Giỏ hàng trống! Vui lòng thêm sản phẩm.');
      return;
    }
    
    // Reset and hide sections initially
    const qrSection = document.getElementById('cartQrCodeSection');
    const bankInfo = document.getElementById('bankTransferInfo');
    const paymentSelect = document.getElementById('cartPaymentSelect');
    const showQRCheckbox = document.getElementById('showQRCheckbox');
    
    if (qrSection) qrSection.style.display = 'none';
    if (bankInfo) bankInfo.style.display = 'none';
    if (paymentSelect) paymentSelect.value = '';
    if (showQRCheckbox) showQRCheckbox.checked = false;
    
    // Add payment method change listener
    if (paymentSelect) {
      // Remove old listener
      const newSelect = paymentSelect.cloneNode(true);
      paymentSelect.parentNode.replaceChild(newSelect, paymentSelect);
      
      newSelect.addEventListener('change', function() {
        const bankInfo = document.getElementById('bankTransferInfo');
        const qrSection = document.getElementById('cartQrCodeSection');
        const showQRCheckbox = document.getElementById('showQRCheckbox');
        
        if (this.value === 'bank_transfer') {
          bankInfo.style.display = 'block';
          bankInfo.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        } else {
          bankInfo.style.display = 'none';
          qrSection.style.display = 'none';
          if (showQRCheckbox) showQRCheckbox.checked = false;
        }
      });
    }
    
    // Add QR checkbox listener
    if (showQRCheckbox) {
      const newCheckbox = showQRCheckbox.cloneNode(true);
      showQRCheckbox.parentNode.replaceChild(newCheckbox, showQRCheckbox);
      
      newCheckbox.addEventListener('change', function() {
        const qrSection = document.getElementById('cartQrCodeSection');
        const orderSummary = document.getElementById('modalOrderSummary');
        
        if (this.checked) {
          qrSection.style.display = 'block';
          qrSection.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
          
          // Move order summary below QR code
          if (orderSummary && qrSection.parentNode) {
            qrSection.parentNode.insertBefore(orderSummary, qrSection.nextSibling);
          }
        } else {
          qrSection.style.display = 'none';
        }
      });
    }
    
    document.getElementById('checkoutModal').classList.add('active');
  };
  
  // Apply promo in modal
  window.applyPromoInModal = function() {
    const promoCode = document.getElementById('modalPromoInput').value.trim();
    if (!promoCode) {
      alert('Vui lòng nhập mã khuyến mãi');
      return;
    }
    
    // Get only selected items
    const allItems = getCartItems();
    const checkboxes = document.querySelectorAll('.item-checkbox:checked');
    const selectedIndexes = Array.from(checkboxes).map(cb => parseInt(cb.dataset.index));
    const selectedItems = allItems.filter((item, index) => selectedIndexes.includes(index));
    
    if (selectedItems.length === 0) {
      alert('Vui lòng chọn sản phẩm để áp dụng mã');
      return;
    }
    
    const { subtotal } = calculateTotals();
    
    console.log('Validating promo:', {
      code: promoCode,
      selectedItems: selectedItems,
      subtotal: subtotal
    });
    
    fetch('ajax_validate_promo.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `promo_code=${encodeURIComponent(promoCode)}&cart_items=${encodeURIComponent(JSON.stringify(selectedItems))}&total=${subtotal}`
    })
    .then(response => response.json())
    .then(data => {
      console.log('Promo validation response:', data);
      if (data.success) {
        appliedPromo = {
          ma: data.promo.ma,
          ten: data.promo.ten,
          giam: parseFloat(data.gia_tri_giam) || 0,
          tong_sau_giam: parseFloat(data.tong_sau_giam) || subtotal
        };
        
        // Show applied promo in modal
        document.getElementById('modalPromoText').innerHTML = `<i class="fas fa-check-circle"></i> ${appliedPromo.ma} - Giảm ${formatPrice(appliedPromo.giam)}`;
        document.getElementById('modalPromoApplied').style.display = 'block';
        document.getElementById('modalPromoInput').value = '';
        
        // Update summary
        updateSummaryWithPromo();
        
        alert('Áp dụng mã thành công!');
      } else {
        alert(data.message || 'Mã không hợp lệ');
      }
    })
    .catch(error => {
      console.error('Error:', error);
      alert('Có lỗi xảy ra khi áp dụng mã');
    });
  };
  
  // Remove promo in modal
  window.removePromoInModal = function() {
    appliedPromo = null;
    document.getElementById('modalPromoApplied').style.display = 'none';
    updateSummaryWithPromo();
    
    // Also hide in cart page
    const promoBadge = document.querySelector('.promo-applied-badge');
    if (promoBadge) promoBadge.style.display = 'none';
  };
  
  // Close checkout modal
  window.closeCheckout = function() {
    const modal = document.getElementById('checkoutModal');
    modal.classList.remove('active');
    
    // Hide QR section when closing
    const qrSection = document.getElementById('cartQrCodeSection');
    if (qrSection) qrSection.style.display = 'none';
  };
  
  // Handle checkout form submit
  window.handleCheckout = function(event) {
    event.preventDefault();
    
    const formData = new FormData(event.target);
    const allItems = getCartItems();
    const { subtotal, shipping, total, selectedCount } = calculateTotals();
    
    // Get only selected items
    const checkboxes = document.querySelectorAll('.item-checkbox:checked');
    const selectedIndexes = Array.from(checkboxes).map(cb => parseInt(cb.dataset.index));
    const selectedItems = allItems.filter((item, index) => selectedIndexes.includes(index));
    
    if (selectedItems.length === 0) {
      alert('Vui lòng chọn ít nhất một sản phẩm để thanh toán!');
      return;
    }
    
    const orderData = {
      items: selectedItems,
      customer: {
        fullname: formData.get('fullname'),
        phone: formData.get('phone'),
        email: formData.get('email'),
        address: formData.get('address'),
        city: formData.get('city'),
        payment: formData.get('payment'),
        note: formData.get('note')
      },
      totals: { 
        subtotal, 
        shipping, 
        total: appliedPromo ? appliedPromo.tong_sau_giam : total 
      },
      promo_code: appliedPromo ? appliedPromo.ma : null,
      promo_discount: appliedPromo ? appliedPromo.giam : 0
    };
    
    console.log('Order Data:', orderData);
    
    // Gửi đơn hàng lên server
    fetch('', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({ checkout_cart: true, ...orderData })
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        alert(`✅ Đặt hàng thành công!\n\nCảm ơn bạn đã mua hàng tại HuynhHoan!\nBạn có thể xem đơn hàng tại trang "Đơn hàng của tôi".`);
        
        // Remove only selected items from cart
        const remainingItems = allItems.filter((item, index) => !selectedIndexes.includes(index));
        localStorage.setItem(CART_KEY, JSON.stringify(remainingItems));
        updateCartCount();
        closeCheckout();
        event.target.reset();
        renderCart();
        appliedPromo = null; // Reset promo
        
        // Chuyển đến trang đơn hàng của tôi
        setTimeout(() => {
          window.location.href = 'don_hang_cua_toi.php';
        }, 1500);
      } else {
        alert('❌ Lỗi: ' + data.message);
      }
    })
    .catch(error => {
      console.error('Error:', error);
      alert('❌ Có lỗi xảy ra khi đặt hàng');
    });
    
    return false;
  };
  
  
  // Initialize
  console.log('=== Giohang.php initialized ===');
  const initialItems = getCartItems();
  console.log('Cart items from localStorage:', initialItems);
  console.log('Items count:', initialItems.length);
  
  renderCart();
  updateCartCount();
  
  // Log cart elements
  const cartSummary = document.querySelector('.cart-summary');
  const checkoutButton = document.getElementById('checkoutBtn');
  const cartContainer = document.getElementById('cartItemsContainer');
  
  console.log('Cart summary element:', cartSummary);
  console.log('Cart summary display:', cartSummary ? window.getComputedStyle(cartSummary).display : 'not found');
  console.log('Checkout button element:', checkoutButton);
  console.log('Checkout button display:', checkoutButton ? window.getComputedStyle(checkoutButton).display : 'not found');
  console.log('Cart items container:', cartContainer);
  
  // FORCE show checkout button ALWAYS (for debugging)
  if (checkoutButton) {
    checkoutButton.style.cssText = 'display: flex !important; visibility: visible !important; opacity: 1 !important; width: 100%; padding: 1.5rem; background: #1d3e1f; color: white;';
    console.log('✅ FORCED checkout button styles applied');
    console.log('Button computed display:', window.getComputedStyle(checkoutButton).display);
  } else {
    console.error('❌ Checkout button NOT FOUND in DOM!');
  }
  
  // Force show cart summary
  if (cartSummary) {
    cartSummary.style.cssText = 'display: block !important; visibility: visible !important; opacity: 1 !important;';
    console.log('✅ FORCED cart summary styles applied');
  }
  
  // Additional check after delay
  setTimeout(() => {
    const items = getCartItems();
    const btn = document.getElementById('checkoutBtn');
    console.log('=== After 500ms ===');
    console.log('Items in cart:', items.length);
    console.log('Button exists:', !!btn);
    if (btn) {
      console.log('Button display:', window.getComputedStyle(btn).display);
      console.log('Button visibility:', window.getComputedStyle(btn).visibility);
      console.log('Button opacity:', window.getComputedStyle(btn).opacity);
      console.log('Button offsetHeight:', btn.offsetHeight);
      console.log('Button offsetWidth:', btn.offsetWidth);
    }
  }, 500);
  
  // If checkout requested via query param, open checkout modal
  try {
    const params = new URLSearchParams(window.location.search);
    if (params.get('checkout') === '1') setTimeout(openCheckout, 200);
  } catch (e) { console.error('checkout param parse error', e); }
  
  // Close modal on outside click
  document.getElementById('checkoutModal').addEventListener('click', function(e) {
    if (e.target === this) {
      closeCheckout();
    }
  });
  
})();
</script>

<link rel="stylesheet" href="assets/notifications.css">
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
    document.addEventListener('DOMContentLoaded', function() {
      const bell = document.getElementById('notificationToggle');
      const dropdown = document.getElementById('notificationDropdown');
      const list = document.getElementById('notificationList');
      const badge = document.getElementById('notificationBadge');
      const markAllReadBtn = document.getElementById('markAllReadBtn');

      // Toggle dropdown
      bell.addEventListener('click', function(e) {
        e.stopPropagation();
        dropdown.classList.toggle('active');
        if (dropdown.classList.contains('active')) {
          loadNotifications();
        }
      });

      // Đóng dropdown khi click ngoài
      document.addEventListener('click', function(e) {
        if (!dropdown.contains(e.target) && !bell.contains(e.target)) {
          dropdown.classList.remove('active');
        }
      });

      // Đánh dấu tất cả đã đọc
      markAllReadBtn.addEventListener('click', function() {
        fetch('notifications_api.php?action=mark_all_read', { method: 'POST' })
          .then(() => loadNotifications());
      });

      async function loadNotifications() {
        try {
          const resp = await fetch('notifications_api.php?action=get_notifications');
          const data = await resp.json();
          if (data.success) {
            renderNotifications(data.notifications);
            updateBadge(data.unread_count);
          } else {
            list.innerHTML = '<div class="notification-empty"><i class="fas fa-bell-slash"></i><div>Lỗi tải thông báo</div></div>';
          }
        } catch (err) {
          list.innerHTML = '<div class="notification-empty"><i class="fas fa-bell-slash"></i><div>Lỗi kết nối server</div></div>';
        }
      }

      function renderNotifications(notifications) {
        if (!notifications || notifications.length === 0) {
          list.innerHTML = '<div class="notification-empty"><i class="fas fa-bell-slash"></i><div>Chưa có thông báo nào</div></div>';
          return;
        }
        list.innerHTML = notifications.map(n => `
          <div class="notification-item${n.is_read ? '' : ' unread'}">
            <div class="notification-type type-${n.type}">${n.type || ''}</div>
            <div class="notification-title">${escapeHtml(n.title)}</div>
            <div class="notification-message">${escapeHtml(n.message)}</div>
            <div class="notification-time">${formatTime(n.created_at)}</div>
          </div>
        `).join('');
      }

      function updateBadge(count) {
        if (typeof count !== 'number') count = 0;
        badge.textContent = count > 99 ? '99+' : count;
        badge.style.display = count > 0 ? 'block' : 'none';
        bell.classList.toggle('has-unread', count > 0);
      }

      function formatTime(dateStr) {
        const date = new Date(dateStr);
        const now = new Date();
        const diff = Math.floor((now - date) / 1000);
        if (diff < 60) return 'Vừa xong';
        if (diff < 3600) return Math.floor(diff / 60) + ' phút trước';
        if (diff < 86400) return Math.floor(diff / 3600) + ' giờ trước';
        if (diff < 2592000) return Math.floor(diff / 86400) + ' ngày trước';
        return date.toLocaleDateString('vi-VN');
      }

      function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
      }
    });
</script>
<script src="assets/notifications.js" defer></script>
</body>
</html>