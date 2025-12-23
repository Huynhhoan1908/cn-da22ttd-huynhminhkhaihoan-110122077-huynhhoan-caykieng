<?php
session_start();
require_once __DIR__ . '/connect.php';
// Tự động khoá các sản phẩm hết hàng
try {
  $conn->exec("UPDATE san_pham SET trang_thai = 0 WHERE so_luong <= 0 AND trang_thai != 0");
} catch (Throwable $e) {
  // Nếu lỗi thì bỏ qua
}

// Shop payment info used to generate QR for payments
$shop_bank = 'Vietcombank';
$shop_account = '0123456789';
$shop_owner = 'HUYNHHOAN';

// Lấy thông tin user nếu đã đăng nhập
$user_info = null;
if (isset($_SESSION['user_id'])) {
    $stmt = $conn->prepare("SELECT ho_ten, email FROM nguoi_dung WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user_info = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Lấy filter từ query
$categoryId = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$genderRaw = isset($_GET['gender']) ? trim($_GET['gender']) : 'all';
$gender = strtolower($genderRaw); // 'all', 'nam', 'nu'
$minPrice = isset($_GET['min_price']) && is_numeric($_GET['min_price']) ? (float)$_GET['min_price'] : 0;
$maxPrice = isset($_GET['max_price']) && is_numeric($_GET['max_price']) ? (float)$_GET['max_price'] : 0;

// Lấy danh sách danh mục
$dmStmt = $conn->query("SELECT id, ten_san_pham AS ten_danh_muc FROM danh_muc ORDER BY ten_san_pham ASC");
$danh_muc_list = $dmStmt ? $dmStmt->fetchAll(PDO::FETCH_ASSOC) : [];

// Sắp xếp ưu tiên để hiển thị các danh mục chính trước
$priority = ['Váy Đầm', 'Áo Sơ Mi', 'Quần', 'Phụ Kiện'];
$ordered = [];
$remaining = $danh_muc_list;
foreach ($priority as $p) {
    foreach ($danh_muc_list as $k => $dm) {
        if (mb_stripos($dm['ten_danh_muc'], $p) !== false) {
            $ordered[] = $dm;
            unset($remaining[$k]);
            break;
        }
    }
}
if (!empty($remaining)) {
    foreach ($remaining as $dm) $ordered[] = $dm;
}
$danh_muc_list = array_values($ordered);

// Lấy sản phẩm (SQL đã xử lý filter/search nếu có)
// SỬA: Lọc sản phẩm theo nhiều danh mục (nhiều-nhiều)
$sql = "SELECT sp.*, GROUP_CONCAT(dm.ten_san_pham SEPARATOR ', ') AS ten_danh_muc
    FROM san_pham sp
    LEFT JOIN san_pham_danh_muc spdm ON sp.id = spdm.san_pham_id
    LEFT JOIN danh_muc dm ON spdm.danh_muc_id = dm.id";
$where = [];
$params = [];
if ($categoryId > 0) {
  $where[] = "spdm.danh_muc_id = :category";
  $params[':category'] = $categoryId;
}
if ($search !== '') {
    $where[] = "sp.ten_san_pham LIKE :q";
    $params[':q'] = '%' . $search . '%';
}

// Price range filter
if ($minPrice > 0) {
    $where[] = "sp.gia >= :min_price";
    $params[':min_price'] = $minPrice;
}
if ($maxPrice > 0) {
    $where[] = "sp.gia <= :max_price";
    $params[':max_price'] = $maxPrice;
}

// Map tham chiếu gender sang giá trị trong DB (chỉnh nếu DB lưu khác)
$genderMap = [
    'nam' => 'Nam',
    'nu'  => 'Nữ',
    'all' => null
];

if ($gender !== 'all' && isset($genderMap[$gender]) && $genderMap[$gender] !== null) {
    // giả sử cột trong bảng sản phẩm là `gioi_tinh`
    $where[] = "sp.gioi_tinh = :gender";
    $params[':gender'] = $genderMap[$gender];
}

if ($where) $sql .= " WHERE " . implode(" AND ", $where);
$sql .= " GROUP BY sp.id ORDER BY sp.id DESC";
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$san_pham_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

// No comment summaries on listing page — comments are shown on product detail only
?>
<!doctype html>
<html lang="vi">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>HuynhHoan — Bộ Sưu Tập Cây Xanh</title>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="assets/notifications.css">
<script src="assets/notifications.js" defer></script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">

<style>
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
  background: linear-gradient(to bottom, #fffef5, #fdfbe8);
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
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
   HEADER - LUXURY MINIMAL
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

.brand {
  display: flex;
  align-items: center;
  gap: var(--space-md);
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
  top: -8px;
  right: -10px;
  background: #4a6b47;
  color: #fff;
  font-size: 0.78rem;
  font-weight: 700;
  min-width: 17px;
  height: 17px;
  padding: 0 2px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  border: none;
  box-shadow: none;
  z-index: 2;
  background-clip: padding-box;
  transition: all 0.2s;
}

/* ==========================================
   BANNER SLIDER
   ========================================== */

.banner-slider {
  position: relative;
  height: 70vh;
  min-height: 500px;
  overflow: hidden;
  margin-bottom: var(--space-2xl);
}

.slider-track {
  position: relative;
  width: 100%;
  height: 100%;
}

.slide {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  opacity: 0;
  transition: opacity 1s ease-in-out, transform 1s ease-in-out;
  transform: translateX(100%);
}

.slide.active {
  opacity: 1;
  transform: translateX(0);
  z-index: 2;
}

.slide.prev {
  transform: translateX(-100%);
  opacity: 0;
}

.slide-bg {
  position: absolute;
  inset: 0;
  background-size: cover;
  background-position: center;
}

.slide-bg::after {
  content: '';
  position: absolute;
  inset: 0;
  background: linear-gradient(to right, rgba(0,0,0,0.6), transparent 60%);
}

.slide-content {
  position: relative;
  z-index: 3;
  max-width: var(--container-max);
  margin: 0 auto;
  padding: 0 2rem;
  height: 100%;
  display: flex;
  flex-direction: column;
  justify-content: center;
  color: white;
}

.slide-badge {
  display: inline-block;
  padding: 0.5rem 1.25rem;
  background: rgba(255, 255, 255, 0.2);
  backdrop-filter: blur(10px);
  border-radius: var(--radius-sm);
  font-size: 0.875rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.1em;
  margin-bottom: 1.5rem;
  width: fit-content;
  animation: fadeInUp 0.8s ease-out;
}

.slide h2 {
  font-family: var(--font-serif);
  font-size: 3.5rem;
  line-height: 1.1;
  font-weight: 700;
  margin-bottom: 1.5rem;
  max-width: 600px;
  animation: fadeInUp 1s ease-out;
}

.slide-desc {
  font-size: 1.25rem;
  line-height: 1.75;
  margin-bottom: 2.5rem;
  max-width: 500px;
  opacity: 0.95;
  animation: fadeInUp 1.2s ease-out;
}

.slide-cta {
  display: flex;
  gap: 1rem;
  animation: fadeInUp 1.4s ease-out;
}

@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(30px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.slider-nav {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  z-index: 10;
  width: 100%;
  max-width: var(--container-max);
  left: 50%;
  transform: translate(-50%, -50%);
  display: flex;
  justify-content: space-between;
  padding: 0 2rem;
  pointer-events: none;
}

.slider-btn {
  width: 3.5rem;
  height: 3.5rem;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(255, 255, 255, 0.9);
  backdrop-filter: blur(10px);
  border-radius: 50%;
  color: var(--black);
  cursor: pointer;
  transition: all 0.3s;
  pointer-events: all;
  box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.slider-btn:hover {
  background: white;
  transform: scale(1.1);
  box-shadow: 0 8px 20px rgba(0,0,0,0.2);
}

.slider-btn i {
  font-size: 1.5rem;
}

.slider-dots {
  position: absolute;
  bottom: 2rem;
  left: 50%;
  transform: translateX(-50%);
  z-index: 10;
  display: flex;
  gap: 0.75rem;
}

.dot {
  width: 0.75rem;
  height: 0.75rem;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.5);
  cursor: pointer;
  transition: all 0.3s;
  border: 2px solid transparent;
}

.dot.active {
  width: 2.5rem;
  border-radius: var(--radius-full);
  background: white;
  border-color: rgba(255, 255, 255, 0.3);
}

.dot:hover {
  background: rgba(255, 255, 255, 0.8);
}

@media (max-width: 768px) {
  .banner-slider {
    height: 60vh;
    min-height: 400px;
  }

  .slide h2 {
    font-size: 2.5rem;
  }

  .slide-desc {
    font-size: 1rem;
  }

  .slider-btn {
    width: 2.5rem;
    height: 2.5rem;
  }

  .slider-btn i {
    font-size: 1.25rem;
  }
}

.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  padding: 0.75rem 2rem;
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

.btn-white {
  background: white;
  color: var(--black);
  border: 2px solid white;
}

.btn-white:hover {
  background: transparent;
  color: white;
}

/* ==========================================
   FILTER SECTION
   ========================================== */

.filter-section {
  padding: var(--space-2xl) 0;
}

.shop-layout {
  display: grid;
  grid-template-columns: 280px 1fr;
  gap: 2rem;
  align-items: start;
}

@media (max-width: 1024px) {
  .shop-layout {
    grid-template-columns: 1fr;
  }
  
  .filter-sidebar {
    display: none;
  }
}

/* Filter Sidebar */
.filter-sidebar {
  background: white;
  border: 1px solid #e5e5e5;
  border-radius: 12px;
  padding: 1.5rem;
  position: sticky;
  top: 6rem;
}

.filter-header {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding-bottom: 1rem;
  border-bottom: 1px solid #f0f0f0;
  margin-bottom: 1.5rem;
}

.filter-header i {
  font-size: 1.25rem;
  color: #333;
}

.filter-header h3 {
  font-size: 1.125rem;
  font-weight: 700;
  color: #000;
  margin: 0;
}

.filter-group {
  margin-bottom: 2rem;
}

.filter-title {
  font-size: 0.9375rem;
  font-weight: 600;
  color: #000;
  margin: 0 0 1rem 0;
}

.filter-options {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.filter-option {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  cursor: pointer;
  padding: 0.5rem;
  border-radius: 6px;
  transition: background 0.2s;
}

.filter-option:hover {
  background: #f9f9f9;
}

.filter-option input[type="radio"] {
  width: 18px;
  height: 18px;
  cursor: pointer;
  accent-color: #000;
}

.filter-label {
  font-size: 0.9375rem;
  color: #666;
  flex: 1;
}

.filter-option input[type="radio"]:checked + .filter-label {
  color: #000;
  font-weight: 600;
}

/* Price Range */
.price-inputs {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  margin-top: 0.75rem;
}

.price-input-wrapper {
  flex: 1;
  position: relative;
}

.price-input-wrapper::before {
  content: '₫';
  position: absolute;
  left: 0.75rem;
  top: 50%;
  transform: translateY(-50%);
  color: #999;
  font-size: 0.875rem;
  font-weight: 600;
  pointer-events: none;
}

.price-input {
  width: 100%;
  padding: 0.75rem 0.75rem 0.75rem 2rem;
  border: 2px solid #e5e5e5;
  border-radius: 8px;
  font-size: 0.875rem;
  font-weight: 500;
  background: #fafafa;
  transition: all 0.3s ease;
}

.price-input::placeholder {
  color: #aaa;
  font-weight: 400;
}

.price-input:focus {
  outline: none;
  border-color: #0066ff;
  background: white;
  box-shadow: 0 0 0 3px rgba(0, 102, 255, 0.1);
}

.price-separator {
  color: #999;
  font-weight: 600;
  font-size: 1rem;
}

/* Filter Actions */
.filter-actions {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  margin-top: 1.5rem;
  padding-top: 1.5rem;
  border-top: 1px solid #f0f0f0;
}

.btn-apply,
.btn-clear {
  width: 100%;
  padding: 0.75rem;
  border-radius: 8px;
  font-size: 0.9375rem;
  font-weight: 600;
  border: none;
  cursor: pointer;
  transition: all 0.3s;
}

.btn-apply {
  background: linear-gradient(135deg, #0066ff 0%, #0052cc 100%);
  color: white;
  box-shadow: 0 2px 8px rgba(0, 102, 255, 0.2);
}

.btn-apply:hover {
  background: linear-gradient(135deg, #0052cc 0%, #0041a3 100%);
  transform: translateY(-2px);
  box-shadow: 0 6px 16px rgba(0, 102, 255, 0.35);
}

.btn-apply:active {
  transform: translateY(0);
}

.btn-clear {
  background: white;
  color: #666;
  border: 2px solid #e5e5e5;
}

.btn-clear:hover {
  background: #f9f9f9;
  border-color: #ff4444;
  color: #ff4444;
  transform: translateY(-1px);
}

/* Products Main */
.products-main {
  min-width: 0;
}

.products-header-bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 2rem;
  margin-bottom: 2rem;
  padding-bottom: 1.5rem;
  border-bottom: 1px solid #f0f0f0;
}

.products-title {
  font-size: 1.75rem;
  font-weight: 700;
  color: #000;
  margin: 0;
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.products-count {
  font-size: 0.875rem;
  color: #999;
  font-weight: 400;
}

.search-wrapper {
  position: relative;
  max-width: 400px;
  flex: 1;
}

.search-icon {
  position: absolute;
  left: 1rem;
  top: 50%;
  transform: translateY(-50%);
  color: #999;
  font-size: 1rem;
}

.search-input {
  width: 100%;
  padding: 0.75rem 1rem 0.75rem 3rem;
  border: 1px solid #e5e5e5;
  border-radius: 8px;
  font-size: 0.9375rem;
  transition: all 0.3s;
}

.search-input:focus {
  outline: none;
  border-color: #333;
  box-shadow: 0 0 0 3px rgba(0,0,0,0.05);
}

.filter-bar {
  background: var(--cream-50);
  border: 1px solid var(--beige-200);
  border-radius: var(--radius-xl);
  padding: var(--space-xl);
  margin-bottom: var(--space-xl);
  box-shadow: var(--shadow-sm);
}

.search-wrapper {
  position: relative;
  margin-bottom: var(--space-lg);
}

.category-filter {
  display: flex;
  flex-wrap: wrap;
  gap: var(--space-sm);
  align-items: center;
}

.category-btn {
  padding: 0.625rem 1.5rem;
  border: 2px solid #e5e5e5;
  border-radius: 25px;
  background: white;
  color: #666;
  font-size: 0.9375rem;
  font-weight: 500;
  transition: all 0.3s ease;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  white-space: nowrap;
}

.category-btn i {
  font-size: 0.875rem;
}

.category-btn:hover {
  border-color: #333;
  color: #000;
  background: #f9f9f9;
  transform: translateY(-1px);
  box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.category-btn.active {
  background: #000;
  color: white;
  border-color: #000;
  font-weight: 600;
  box-shadow: 0 2px 12px rgba(0,0,0,0.15);
}

/* ==========================================
   STATS BAR
   ========================================== */

.stats-bar {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: var(--space-md);
  margin-bottom: var(--space-xl);
}

.stat-item {
  background: var(--cream-50);
  border: 1px solid var(--beige-200);
  border-radius: var(--radius-lg);
  padding: var(--space-lg);
  text-align: center;
  transition: all 0.3s ease;
}

.stat-item:hover {
  transform: translateY(-4px);
  box-shadow: var(--shadow-lg);
  border-color: var(--accent-gold);
}

.stat-value {
  font-family: var(--font-serif);
  font-size: 2rem;
  font-weight: 700;
  color: var(--black);
  margin-bottom: var(--space-xs);
}

.stat-label {
  font-size: 0.875rem;
  color: var(--taupe-500);
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

/* ==========================================
   PRODUCTS SECTION
   ========================================== */

.products-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: var(--space-xl);
}

.products-title {
  font-family: var(--font-serif);
  font-size: 2rem;
  color: var(--black);
}

.products-count {
  font-size: 0.875rem;
  color: var(--taupe-500);
  font-weight: 500;
}

.products-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: var(--space-xl);
  margin-bottom: var(--space-2xl);
}

@media (min-width: 640px) {
  .products-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (min-width: 1024px) {
  .products-grid {
    grid-template-columns: repeat(3, 1fr);
  }
}

@media (min-width: 1280px) {
  .products-grid {
    grid-template-columns: repeat(4, 1fr);
  }
}

/* ==========================================
   PRODUCT CARD - PREMIUM DESIGN
   ========================================== */

.product-card {
  background: var(--cream-50);
  border: 1px solid var(--beige-200);
  border-radius: var(--radius-xl);
  overflow: hidden;
  transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
  position: relative;
}

.product-card:hover {
  transform: translateY(-8px);
  box-shadow: var(--shadow-xl);
  border-color: var(--beige-300);
}

.product-image-wrapper {
  position: relative;
  aspect-ratio: 3/4;
  background: var(--beige-100);
  overflow: hidden;
}

.product-image-wrapper img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.7s cubic-bezier(0.4, 0, 0.2, 1);
}

.product-card:hover .product-image-wrapper img {
  transform: scale(1.08);
}

.product-badge {
  position: absolute;
  top: var(--space-md);
  left: var(--space-md);
  background: var(--accent-terracotta);
  color: var(--cream-50);
  padding: var(--space-xs) var(--space-md);
  border-radius: var(--radius-sm);
  font-size: 0.75rem;
  font-weight: 700;
  letter-spacing: var(--space-xxxs);
  text-transform: uppercase;
  z-index: var(--z-badge);
}

.product-locked .product-badge {
  background: #dc3545;
  color: white;
}

.product-locked .product-image-wrapper::after {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.4);
  z-index: 1;
}

.product-locked .product-image-wrapper img {
  filter: grayscale(50%) opacity(0.7);
}

.product-locked:hover {
  transform: none;
  cursor: not-allowed;
}

.product-locked .add-to-cart-btn {
  background: #6c757d;
  cursor: not-allowed;
  pointer-events: none;
}

.product-overlay {
  position: absolute;
  inset: 0;
  background: linear-gradient(to top, rgba(0,0,0,0.5) 0%, transparent 50%);
  opacity: 0;
  transition: opacity 0.4s ease;
}

.product-card:hover .product-overlay {
  opacity: 1;
}

.quick-actions {
  position: absolute;
  top: var(--space-md);
  right: var(--space-md);
  display: flex;
  flex-direction: column;
  gap: var(--space-sm);
  z-index: 2;
  opacity: 0;
  transform: translateX(1rem);
  transition: all 0.4s ease;
}

.product-card:hover .quick-actions {
  opacity: 1;
  transform: translateX(0);
}

.quick-btn {
  width: 2.5rem;
  height: 2.5rem;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(10px);
  border-radius: var(--radius-md);
  color: var(--charcoal);
  box-shadow: var(--shadow-md);
  transition: all 0.2s ease;
}

.quick-btn:hover {
  background: var(--cream-50);
  transform: scale(1.1);
}

.quick-btn.favorited {
  background: var(--accent-rose);
  color: var(--cream-50);
}

.quick-btn i {
  font-size: 1rem;
}

/* ==========================================
   PRODUCT INFO
   ========================================== */

.product-info {
  padding: var(--space-lg);
}

.product-category {
  font-size: 0.75rem;
  color: var(--taupe-400);
  text-transform: uppercase;
  letter-spacing: 0.1em;
  margin-bottom: var(--space-xs);
}

.product-name {
  font-size: 1rem;
  font-weight: 600;
  color: var(--black);
  line-height: 1.4;
  min-height: 2.8rem;
  margin-bottom: var(--space-md);
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.product-desc {
  font-size: 0.9375rem;
  color: var(--taupe-400);
  margin-bottom: var(--space-sm);
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.product-rating {
  display: flex;
  align-items: center;
  gap: var(--space-sm);
  margin-bottom: var(--space-md);
}

.rating-stars {
  display: flex;
  gap: 0.125rem;
}

.rating-stars i {
  font-size: 0.875rem;
  color: var(--accent-gold);
}

.rating-stars i.empty {
  color: var(--beige-300);
}

.rating-count {
  font-size: 0.75rem;
  color: var(--taupe-400);
}

.product-price {
  font-family: var(--font-serif);
  font-size: 1.375rem;
  font-weight: 700;
  color: var(--black);
  margin-bottom: var(--space-lg);
}


/* ==========================================
   QUANTITY CONTROLS
   ========================================== */
.add-cart-btn,
.buy-now-btn {
  border: none;
  outline: none;
  text-decoration: none;
}

.add-cart-btn {
  width: 100%;
  background: #e8dcc8;
  color: #222;
  padding: 0.9rem 0.5rem;
  border-radius: 0.5rem;
  font-size: 1.05rem;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.7rem;
  transition: background 0.2s, color 0.2s;
  cursor: pointer;
  border: none;
  box-shadow: none;
  text-transform: uppercase;
  letter-spacing: 0.01em;
  font-family: inherit;
  outline: none;
  margin-top: 0.5rem;
}
.add-cart-btn i {
  font-size: 1.1rem;
  margin-right: 0.5rem;
  color: #222;
}
.add-cart-btn:hover {
  background: #d4c5b0;
  color: #111;
}

.add-cart-btn:hover {
  background-color: var(--stone-600);
}

* Đảm bảo các thẻ sản phẩm đều nhau, nút luôn ở đáy */
.products-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 1.5rem;
    margin-bottom: 3rem;
}
@media (min-width: 640px) {
    .products-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}
@media (min-width: 1024px) {
    .products-grid {
        grid-template-columns: repeat(4, 1fr);
    }
}
.product-card {
    display: flex;
    flex-direction: column;
    height: 100%;
}
.product-info {
    flex: 1 1 auto;
    display: flex;
    flex-direction: column;
    justify-content: flex-start;
}
.add-to-cart-section {
    display: flex;
    flex-direction: column;
    margin-top: auto;
}
/* ==========================================
   ADD TO CART SECTION
   ========================================== */

.add-to-cart-section {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  margin-bottom: 0;
  width: 100%;
}

.qty-selector {
  display: flex;
  align-items: center;
  border: 2px solid var(--beige-200);
  border-radius: var(--radius-md);
  overflow: hidden;
  background: var(--cream-100);
  flex-shrink: 0;
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
  border: none;
  cursor: pointer;
}

.qty-btn:hover {
  background: var(--beige-100);
  color: var(--black);
}

.qty-input {
  width: 3.5rem;
  text-align: center;
  border: none;
  background: transparent;
  font-weight: 600;
  color: var(--black);
  font-size: 1rem;
}

.qty-input:focus {
  outline: none;
}

.add-to-cart-btn {
  flex: 1;
  padding: var(--space-md) var(--space-lg);
  background: linear-gradient(135deg, #D4C5B0, #E8DCC8);
  color: #2d2a26;
  border-radius: var(--radius-md);
  font-size: 0.9rem;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: var(--space-sm);
  transition: all 0.3s ease;
  border: none;
  cursor: pointer;
  white-space: nowrap;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  box-shadow: 0 2px 8px rgba(212, 197, 176, 0.4);
}

.add-to-cart-btn:hover {
  background: linear-gradient(135deg, #C5B5A0, #D4C5B0);
  transform: translateY(-2px);
  box-shadow: 0 4px 16px rgba(212, 197, 176, 0.5);
}

.add-to-cart-btn:active {
  transform: translateY(0);
}

.add-to-cart-btn:disabled {
  background: #e0e0e0;
  color: #999;
  cursor: not-allowed;
  transform: none;
  box-shadow: none;
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

.checkout-product {
  display: flex;
  gap: var(--space-lg);
  margin-bottom: var(--space-xl);
  padding: var(--space-lg);
  background: var(--cream-100);
  border-radius: var(--radius-lg);
}

.checkout-product-img {
  width: 100px;
  height: 130px;
  object-fit: cover;
  border-radius: var(--radius-md);
}

.checkout-product-info {
  flex: 1;
}

.checkout-product-name {
  font-weight: 600;
  color: var(--black);
  margin-bottom: var(--space-xs);
}

.checkout-product-details {
  font-size: 0.875rem;
  color: var(--taupe-500);
  margin-bottom: var(--space-sm);
}

.checkout-product-price {
  font-family: var(--font-serif);
  font-size: 1.25rem;
  font-weight: 700;
  color: var(--black);
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

.checkout-summary {
  background: var(--cream-100);
  border-radius: var(--radius-lg);
  padding: var(--space-lg);
  margin-top: var(--space-lg);
}

.summary-row {
  display: flex;
  justify-content: space-between;
  padding: var(--space-sm) 0;
  font-size: 0.875rem;
}

.summary-row.total {
  border-top: 2px solid var(--beige-200);
  margin-top: var(--space-sm);
  padding-top: var(--space-md);
  font-size: 1.125rem;
  font-weight: 700;
}

.submit-order-btn {
  width: 100%;
  padding: var(--space-lg);
  background: var(--black);
  color: var(--cream-50);
  border-radius: var(--radius-md);
  font-size: 1rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  margin-top: var(--space-lg);
  transition: all 0.3s ease;
}

.submit-order-btn:hover {
  background: var(--charcoal);
  transform: translateY(-2px);
  box-shadow: var(--shadow-lg);
}

/* ==========================================
   FEATURES
   ========================================== */

.features-section {
  background: var(--cream-50);
  border: 1px solid var(--beige-200);
  border-radius: var(--radius-xl);
  padding: var(--space-2xl);
  margin: var(--space-2xl) 0;
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
   UTILITIES
   ========================================== */

@media (max-width: 1023px) {
  .hide-mobile { display: none !important; }
}

@media (max-width: 640px) {
  .form-row {
    grid-template-columns: 1fr;
  }
}
/* CSS cho cái chuông */
.noti-badge {
    position: absolute; top: -5px; right: -5px;
    background-color: #e74c3c; color: white;
    font-size: 10px; font-weight: bold;
    height: 18px; width: 18px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    border: 2px solid white;
}
.noti-dropdown {
    display: none; position: absolute;
    top: 50px; right: -10px; /* Điều chỉnh vị trí thả xuống */
    width: 320px; background: white;
    border-radius: 12px; box-shadow: 0 5px 20px rgba(0,0,0,0.15);
    border: 1px solid #eee; z-index: 9999; overflow: hidden;
}
.noti-dropdown.active { display: block; }
.noti-header { background: #f9fafb; padding: 12px 15px; font-weight: bold; border-bottom: 1px solid #eee; color: #333; }
.noti-item { display: block; padding: 12px 15px; border-bottom: 1px solid #f1f1f1; text-decoration: none !important; transition: 0.2s; }
.noti-item:hover { background-color: #f0fdf4; }
.noti-item h4 { margin: 0 0 5px; font-size: 14px; font-weight: 700; color: #3d6b3f; }
.noti-item p { margin: 0; font-size: 13px; color: #555; line-height: 1.4; }
.noti-item small { display: block; margin-top: 5px; font-size: 11px; color: #999; }

.close-line {
  position: absolute;
  left: 10%;
  right: 10%;
  top: 50%;
  height: 1px;
  background: var(--taupe-400);
  transform: rotate(-45deg);
}
</style>
</head>
<body>

<!-- Announcement Bar -->
<div class="announcement-bar" style="background-color:#1d3e1f;color:#fdfbe8;padding:0.625rem 0;text-align:center;font-size:0.875rem;">
  <p style="display:flex;align-items:center;justify-content:center;gap:0.5rem;margin:0;">
    <i class="fas fa-leaf"></i>
    <span>Miễn phí vận chuyển cho đơn hàng từ 500.000₫ | Khuyến mãi đến 40% - Thời gian có hạn</span>
    <i class="fas fa-seedling"></i>
  </p>
</div>

<!-- Header -->
<header class="header">
  <div class="container">
    <a href="/" class="brand-logo" style="display:flex;align-items:center;gap:12px;">
      <img src="images/logo.jpg" alt="HuynhHoan Logo" style="height:45px;width:auto;border-radius:8px;">
      <span style="font-weight:600;font-size:1.4rem;">HuynhHoan</span>
    </a>

    <nav class="nav">
      <a href="trangchu.php">Trang chủ</a>
      <a href="baiviet.php">Bài viết</a>
      <a href="don_hang_cua_toi.php">Theo Dõi Đơn Hàng</a>
      <a href="lienhe.php">Liên Hệ</a>
    </nav>

    <div class="header-actions">
        <a href="giohang.php" class="icon-btn" title="Giỏ hàng">
        <i class="fas fa-shopping-bag"></i>
        <span class="cart-badge">0</span>
        </a>
      <a href="dangnhap.php" class="icon-btn hide-mobile" title="Tài khoản">
        <i class="fas fa-sign-out-alt"></i>
      </a>
    </div>
  </div>
</header>

<!-- Banner Slider -->
<section class="banner-slider">
  <div class="slider-track">
    <!-- Slide 1 -->
    <div class="slide active">
      <div class="slide-bg" style="background-image: url('images/banner.jpg');"></div>
      <div class="slide-content">
        <div class="slide-badge">Bộ Sưu Tập Mới 2025</div>
        <h2>Cây Xanh<br>Không Gian Sống</h2>
        <p class="slide-desc">
          Khám phá bộ sưu tập cây cảnh cao cấp, mang thiên nhiên vào ngôi nhà của bạn.
          Tươi mát, dễ chăm sóc và đầy sức sống.
        </p>
        <div class="slide-cta">
        </div>
      </div>
</div>
    <!-- Slide 2 -->
    <div class="slide">
      <div class="slide-bg" style="background-image: url('images/banner2.jpeg');"></div>
      <div class="slide-content">
        <div class="slide-badge">Chạm vào thiên nhiên</div>
        <h2>Tinh tế sắc xanh</h2>
        <p class="slide-desc">
          Ưu đãi đặc biệt cho các loài cây cảnh chọn lọc. 
          Nhanh tay sở hữu những chậu cây đẹp với giá tốt nhất!
        </p>
        <div class="slide-cta">
        </div>
      </div>
</div>
    <!-- Slide 3 -->
    <div class="slide">
      <div class="slide-bg" style="background-image: url('images/banner3.png');"></div>
      <div class="slide-content">
        <div class="slide-badge">Bộ Sưu Tập Cao Cấp</div>
        <h2>Cây Cảnh<br>Phong Thủy 2025</h2>
        <p class="slide-desc">
          Cây phong thủy cao cấp với ý nghĩa tốt lành, 
          mang may mắn và thịnh vượng cho gia đình bạn.
        </p>
        <div class="slide-cta">
        </div>
      </div>
    </div>

    <!-- Slide 4 -->
    <div class="slide">
      <div class="slide-bg" style="background-image: url('images/banner4.jpg');"></div>
      <div class="slide-content">
        <div class="slide-badge">Xu Hướng Mới</div>
        <h2>Không Gian Xanh<br>Hiện Đại</h2>
        <p class="slide-desc">
          Tạo nên không gian sống xanh, hiện đại và gần gũi với thiên nhiên.
          Phong cách tối giản nhưng đầy ấn tượng.
        </p>
        <div class="slide-cta">
        </div>
      </div>
    </div>
  </div>

  <!-- Navigation -->
  <div class="slider-nav">
    <button class="slider-btn prev-btn" onclick="prevSlide()">
      <i class="fas fa-chevron-left"></i>
    </button>
    <button class="slider-btn next-btn" onclick="nextSlide()">
      <i class="fas fa-chevron-right"></i>
    </button>
  </div>

  <!-- Dots -->
  <div class="slider-dots">
    <span class="dot active" onclick="goToSlide(0)"></span>
    <span class="dot" onclick="goToSlide(1)"></span>
    <span class="dot" onclick="goToSlide(2)"></span>
  </div>
</section>

<!-- Main Content -->
<div class="container">
  <section class="filter-section" id="products">
    <div class="shop-layout">
      <!-- Left Sidebar Filter -->
      <aside class="filter-sidebar">
        <div class="filter-header">
          <i class="fas fa-filter"></i>
          <h3>Bộ Lọc</h3>
        </div>
        
        <!-- Category Filter -->
        <div class="filter-group">
          <h4 class="filter-title">Danh Mục</h4>
          <div class="filter-options">
            <label class="filter-option">
              <input type="radio" name="category" value="0" <?php echo $categoryId === 0 ? 'checked' : ''; ?> onchange="filterByCategory(0)">
              <span class="filter-label">Tất cả</span>
            </label>
            <?php foreach($danh_muc_list as $dm): ?>
            <label class="filter-option">
              <input type="radio" name="category" value="<?php echo (int)$dm['id']; ?>" <?php echo $categoryId === (int)$dm['id'] ? 'checked' : ''; ?> onchange="filterByCategory(<?php echo (int)$dm['id']; ?>)">
              <span class="filter-label"><?php echo htmlspecialchars($dm['ten_danh_muc']); ?></span>
            </label>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- Price Range Filter -->
        <div class="filter-group">
          <h4 class="filter-title">Khoảng Giá</h4>
          <div class="price-inputs">
            <div class="price-input-wrapper">
              <input type="number" class="price-input" placeholder="Từ" id="minPrice" min="0" step="1000">
            </div>
            <span class="price-separator">—</span>
            <div class="price-input-wrapper">
              <input type="number" class="price-input" placeholder="Đến" id="maxPrice" min="0" step="1000">
            </div>
          </div>
        </div>

        <!-- Action Buttons -->
        <div class="filter-actions">
          <button class="btn-apply" onclick="applyFilters()">
            <i class="fas fa-check-circle"></i> Áp Dụng
          </button>
          <button class="btn-clear" onclick="clearFilters()">
            <i class="fas fa-times-circle"></i> Xóa Bộ Lọc
          </button>
        </div>
      </aside>
      
      <!-- Main Content -->
      <div class="products-main">
        <!-- Search Bar -->
        <div class="products-header-bar">
          <h2 class="products-title">Sản Phẩm <span class="products-count"><?php echo count($san_pham_list); ?> sản phẩm</span></h2>

        </div>

        <!-- Products Grid -->
    <div class="products-grid">
      <?php 
      $sizes = [
        ['size' => 'S', 'weight' => '40-43kg'],
        ['size' => 'M', 'weight' => '44-50kg'],
        ['size' => 'L', 'weight' => '51-58kg'],
        ['size' => 'XL', 'weight' => '59-65kg']
      ];
      foreach($san_pham_list as $index => $p):
        // Check if product is locked (trang_thai = 0 means locked)
        $isLocked = isset($p['trang_thai']) && $p['trang_thai'] == 0;
        
        $img = '';
        if (!empty($p['hinh_anh'])) {
          if (strpos($p['hinh_anh'], 'http') === 0) $img = $p['hinh_anh'];
          elseif (file_exists(__DIR__ . '/uploads/' . $p['hinh_anh'])) $img = 'uploads/' . $p['hinh_anh'];
        }
        if (!$img) $img = 'https://images.unsplash.com/photo-1515886657613-9f3515b0c78f?w=600';
        
        $name = htmlspecialchars($p['ten_san_pham'] ?? 'Sản phẩm');
        $price = isset($p['gia']) ? (float)$p['gia'] : 0;
        $priceFormatted = number_format($price,0,',','.') . '₫';
        $badge = ($index < 3) ? 'New' : '';
        if ($isLocked) { $badge = 'Hết hàng'; }
      ?>
        <article class="product-card <?php echo $isLocked ? 'product-locked' : ''; ?>" id="prod-<?php echo (int)$p['id']; ?>"
           data-id="<?php echo (int)$p['id']; ?>"
           data-name="<?php echo $name; ?>"
           data-price="<?php echo $price; ?>"
           data-img="<?php echo $img; ?>">
          <div class="product-image-wrapper">
            <a href="<?php echo $isLocked ? '#' : 'chitiet_san_pham.php?id=' . (int)$p['id']; ?>" class="<?php echo $isLocked ? '' : 'open-quick'; ?>" data-id="<?php echo (int)$p['id']; ?>" <?php echo $isLocked ? 'onclick=\"return false;\"' : ''; ?> >
              <img 
                src="<?php echo $img; ?>" 
                alt="<?php echo $name; ?>" 
                loading="lazy" 
                onerror="this.onerror=null;this.src='https://images.unsplash.com/photo-1515886657613-9f3515b0c78f?w=600'"
              >
            </a>
            <div class="product-overlay"></div>
            <?php if ($badge): ?>
              <div class="product-badge"><?php echo $badge; ?></div>
            <?php endif; ?>
            <div class="quick-actions">
              <button class="quick-btn quick-view" data-id="<?php echo (int)$p['id']; ?>" title="Xem nhanh">
                <i class="far fa-eye"></i>
              </button>
            </div>
          </div>
          <div class="product-info">
            <h3 class="product-name">
              <a href="chitiet_san_pham.php?id=<?php echo (int)$p['id']; ?>"><?php echo $name; ?></a>
            </h3>
            <div class="product-price"><?php echo $priceFormatted; ?></div>
            <div class="add-to-cart-section">
              <div class="qty-selector" style="margin-bottom: 0; width: 100%;">
                <button type="button" class="qty-btn qty-minus" data-product="<?php echo (int)$p['id']; ?>">
                  <i class="fas fa-minus"></i>
                </button>
                <input 
                  type="number" 
                  class="qty-input" 
                  value="1" 
                  min="1" 
                  max="99"
                  data-product="<?php echo (int)$p['id']; ?>"
                  readonly
                >
                <button type="button" class="qty-btn qty-plus" data-product="<?php echo (int)$p['id']; ?>">
                  <i class="fas fa-plus"></i>
                </button>
              </div>
              <button class="add-cart-btn" data-id="<?php echo (int)$p['id']; ?>" <?php echo $isLocked ? 'disabled' : ''; ?> >
                <i class="fas fa-shopping-cart"></i>
                <span><?php echo $isLocked ? 'HẾT HÀNG' : 'THÊM VÀO GIỎ HÀNG'; ?></span>
              </button>
            </div>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
      </div><!-- .products-main -->
    </div><!-- .shop-layout -->
  </section>

  <!-- Features -->
  <section class="features-section">
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
  </section>
</div>

<!-- Footer -->
<footer class="footer">
  <div class="container">
    <p class="footer-text">© <?php echo date('Y'); ?> HuynhHoan — Thiết kế bởi đam mê</p>
  </div>
</footer>


<!-- Checkout Modal -->
<div class="modal-overlay" id="checkoutModal">
  <div class="checkout-modal">
    <div class="modal-header">
      <h3 class="modal-title">Thanh Toán</h3>
      <button class="modal-close" onclick="closeCheckoutModal()">
        <i class="fas fa-times"></i>
      </button>
    </div>
    
    <div class="modal-body">
      <!-- Product Preview -->
      <div class="checkout-product" id="checkoutProduct">
        <img src="" alt="" class="checkout-product-img" id="checkoutImg">
        <div class="checkout-product-info">
          <h4 class="checkout-product-name" id="checkoutName">Tên sản phẩm</h4>
          <div class="checkout-product-details">
            <span id="checkoutSize">Size: M</span> • 
            <span id="checkoutQty">Số lượng: 1</span>
          </div>
          <div class="checkout-product-price" id="checkoutPrice">0₫</div>
        </div>
      </div>

      <!-- Checkout Form -->
      <form class="checkout-form" id="checkoutForm" onsubmit="return handleCheckoutSubmit(event)">
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
            </select>
          </div>

          <div class="form-group">
            <label class="form-label">Phương thức thanh toán</label>
            <select class="form-select" name="payment" id="paymentSelect">
              <option value="cod">Thanh toán khi nhận hàng</option>
              <option value="bank">Chuyển khoản ngân hàng</option>
              <option value="momo">Ví MoMo</option>
              <option value="qr">Quét mã QR</option>
            </select>
            <input type="hidden" name="order_id" id="orderIdInput" value="">
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Ghi chú</label>
          <textarea class="form-textarea" name="note" placeholder="Ghi chú đơn hàng (tùy chọn)"></textarea>
        </div>

        <!-- Summary -->
        <div class="checkout-summary">
          <div class="summary-row">
            <span>Tạm tính</span>
            <span id="summarySubtotal">0₫</span>
          </div>
          <div class="summary-row">
            <span>Phí vận chuyển</span>
            <span id="summaryShipping">30.000₫</span>
          </div>
          <div class="summary-row total">
            <span>Tổng cộng</span>
            <span id="summaryTotal">0₫</span>
          </div>
        </div>

        <!-- Bank Transfer Info Section (hidden by default) -->
        <div id="bankInfoSection" style="display: none; margin-top: 1.5rem;">
          <div style="background: #f0f9ff; border: 2px solid #3b82f6; border-radius: 12px; padding: 1.5rem;">
            <h4 style="font-weight: 700; font-size: 1rem; margin-bottom: 1rem; color: #1e40af; text-align: center;">
              <i class="fas fa-university"></i> Thông tin chuyển khoản
            </h4>
            <div style="background: white; padding: 1rem; border-radius: 8px; margin-bottom: 0.5rem;">
              <div style="display: flex; justify-content: space-between; margin-bottom: 0.75rem; padding-bottom: 0.75rem; border-bottom: 1px solid #e5e7eb;">
                <span style="color: #6b7280; font-size: 0.875rem;">Chủ tài khoản:</span>
                <span style="font-weight: 600; color: #1f2937;">Trương Thị Mỹ Phương</span>
              </div>
              <div style="display: flex; justify-content: space-between; margin-bottom: 0.75rem; padding-bottom: 0.75rem; border-bottom: 1px solid #e5e7eb;">
                <span style="color: #6b7280; font-size: 0.875rem;">Số tài khoản:</span>
                <span style="font-weight: 700; color: #1f2937; font-size: 1.125rem;">0325048679</span>
              </div>
              <div style="display: flex; justify-content: space-between;">
                <span style="color: #6b7280; font-size: 0.875rem;">Ngân hàng:</span>
                <span style="font-weight: 600; color: #1f2937;">MB Bank</span>
              </div>
            </div>
            <p style="margin-top: 1rem; font-size: 0.8rem; color: #6b7280; text-align: center; font-style: italic;">
              <i class="fas fa-info-circle"></i> Vui lòng chuyển khoản với nội dung: Tên + Số điện thoại
            </p>
          </div>
        </div>

        <!-- QR Code Section (hidden by default) -->
        <div id="qrCodeSection" style="display: none; margin-top: 1.5rem; text-align: center;">
          <div style="background: var(--cream-100); border-radius: var(--radius-lg); padding: var(--space-xl);">
            <img src="images/qr.jpg" alt="QR Code Thanh Toán" style="max-width: 100%; height: auto; border-radius: var(--radius-md);">
            <p style="margin-top: 1rem; font-size: 0.875rem; color: var(--taupe-500);">Quét mã QR để thanh toán</p>
          </div>
        </div>

        <button type="submit" class="submit-order-btn">
          <i class="fas fa-check-circle"></i>
          Đặt Hàng Ngay
        </button>
      </form>
    </div>
  </div>
</div>

    <!-- Quick View Modal -->
    <div class="modal-overlay" id="quickViewModal">
      <div class="checkout-modal">
        <div class="modal-header">
          <h3 class="modal-title" id="qvTitle">Xem nhanh</h3>
          <button class="modal-close" onclick="closeQuickView()">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="modal-body" id="qvBody">
          <div style="display:flex;gap:1rem;align-items:flex-start;flex-wrap:wrap;">
            <img id="qvImg" src="" alt="" style="width:220px;height:auto;border-radius:8px;object-fit:cover;" />
            <div style="flex:1;min-width:240px;">
              <div style="font-weight:700;font-size:1.125rem;margin-bottom:0.25rem;" id="qvName"></div>
              <div style="color:var(--taupe-500);margin-bottom:0.75rem;" id="qvCategory"></div>
              <div style="font-family:var(--font-serif);font-size:1.25rem;font-weight:700;margin-bottom:0.75rem;" id="qvPrice"></div>
              <div id="qvDesc" style="color:var(--taupe-500);margin-bottom:1rem;"></div>
            </div>
          </div>
        </div>

            <!-- QR Scanner Modal -->
            <div class="modal-overlay" id="qrScannerModal">
              <div class="checkout-modal">
                <div class="modal-header">
                  <h3 class="modal-title">Quét mã QR</h3>
                  <button class="modal-close" onclick="closeQrScanner()">
                    <i class="fas fa-times"></i>
                  </button>
                </div>
                <div class="modal-body">
                  <div id="qr-reader" style="width:100%;max-width:520px;margin:0 auto;"></div>
                  <div style="text-align:center;margin-top:0.5rem;">
                    <label for="qrCameraSelect" style="font-size:0. ninerem;color:var(--taupe-500);">Chọn camera:</label>
                    <select id="qrCameraSelect" style="margin-left:0.5rem;padding:0.25rem 0.5rem;"></select>
                  </div>
                  <div style="margin-top:1rem;display:flex;gap:0.5rem;justify-content:center;">
                    <button type="button" class="btn btn-white" id="btnStopQr">Dừng quét</button>
                    <button type="button" class="btn btn-primary" id="btnUseQr" style="display:none;">Sử dụng mã đã quét</button>
                  </div>
                  <div id="qrStatus" style="margin-top:0.75rem;color:var(--taupe-500);text-align:center;">Hãy cho phép truy cập camera và hướng camera vào mã QR.</div>
                </div>
              </div>
            </div>
            <!-- QR Create Modal -->
            <div class="modal-overlay" id="qrCreateModal">
              <div class="checkout-modal" style="max-width:720px;">
                <div class="modal-header">
                  <h3 class="modal-title">Thanh Toán QR Code</h3>
                  <button class="modal-close" onclick="closeQrCreator()">
                    <i class="fas fa-times"></i>
                  </button>
                </div>
                <div class="modal-body">
                  <div style="display:flex;gap:1.5rem;align-items:center;justify-content:center;flex-direction:column;padding:1rem;">
                    <div id="qrFormFields" style="flex:1;min-width:280px;display:none;">
                      <h4 style="margin-bottom:0.5rem;font-weight:700;">Thông Tin Thanh Toán</h4>
                      <div class="form-group">
                        <label class="form-label">Số tiền (VND)</label>
                        <input id="qrCreateAmount" class="form-input" type="number" step="1000" min="0" value="" />
                      </div>
                      <div class="form-group">
                        <label class="form-label">Tên ngân hàng</label>
                        <input id="qrCreateBank" class="form-input" type="text" placeholder="Vietcombank, Techcombank, MBBank..." />
                      </div>
                      <div class="form-group">
                        <label class="form-label">Số tài khoản</label>
                        <input id="qrCreateAccount" class="form-input" type="text" placeholder="0123456789" />
                      </div>
                      <div class="form-group">
                        <label class="form-label">Tên chủ tài khoản</label>
                        <input id="qrCreateName" class="form-input" type="text" placeholder="NGUYEN VAN A" />
                      </div>
                      <div class="form-group">
                        <label class="form-label">Nội dung chuyển khoản</label>
                        <input id="qrCreateNote" class="form-input" type="text" placeholder="Thanh toán đơn hàng #123" />
                      </div>

                      <div style="display:flex;gap:0.5rem;margin-top:0.75rem;">
                        <button type="button" class="btn btn-primary" id="btnGenerateQr" style="display:none;">Tạo Mã QR</button>
                        <button type="button" class="btn btn-white" id="btnClearQr" style="display:none;">Xóa</button>
                      </div>
                    </div>

                    <div style="text-align:center;">
                      <div id="qrCanvas" style="background:white;padding:1.5rem;border-radius:12px;display:inline-block;box-shadow:var(--shadow-lg);"></div>
                      <div id="qrCreateInfo" style="margin-top:1rem;color:var(--taupe-500);font-size:1rem;"></div>
                      <div style="margin-top:0.75rem;display:flex;gap:0.5rem;justify-content:center;">
                        <a id="qrDownload" class="btn btn-white" style="display:none;">Tải ảnh</a>
                        <button id="qrUseForPayment" class="btn btn-primary" style="display:none;">Sử dụng mã này</button>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
      </div>
    </div>

<script src="https://unpkg.com/html5-qrcode@2.4.9/minified/html5-qrcode.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<?php
// Kiểm tra trạng thái đăng nhập ngay tại đây để gán vào biến JS
$isUserLoggedIn = isset($_SESSION['user_id']) ? 'true' : 'false';
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
    /* CSS CHAT - Dán trực tiếp để đảm bảo nhận style */
    #live-chat-widget { position: fixed; bottom: 20px; right: 20px; z-index: 2147483647; font-family: sans-serif; }
    
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
    // --- FILTER LOGIC ---
    // ...existing code...

    // Hàm lọc theo danh mục
    function filterByCategory(id) {
      const url = new URL(window.location.href);
      if (id == 0) url.searchParams.delete('category');
      else url.searchParams.set('category', id);
      window.location.href = url.toString();
    }

    // Hàm áp dụng lọc giá
    function applyFilters() {
      const min = document.getElementById('minPrice').value;
      const max = document.getElementById('maxPrice').value;
      const url = new URL(window.location.href);
      if (min > 0) url.searchParams.set('min_price', min); else url.searchParams.delete('min_price');
      if (max > 0) url.searchParams.set('max_price', max); else url.searchParams.delete('max_price');
      window.location.href = url.toString();
    }

    // Hàm xóa bộ lọc
    function clearFilters() {
      const url = new URL(window.location.href);
      url.searchParams.delete('category');
      url.searchParams.delete('min_price');
      url.searchParams.delete('max_price');
      url.searchParams.delete('search');
      window.location.href = url.pathname;
    }

    // ...existing code...
function loadBroadcastNoti() {
    // Gọi API lấy thông báo
    fetch('api/get_broadcast.php')
        .then(response => response.json())
        .then(data => {
            const list = document.getElementById('public-noti-list');
            const badge = document.getElementById('public-noti-badge');
            
            // Logic kiểm tra tin mới dựa vào LocalStorage
            const lastSeenId = localStorage.getItem('last_seen_broadcast_id') || 0;
            let unreadCount = 0;
            let maxId = 0;

            if (data.length > 0) {
                list.innerHTML = '';
                data.forEach(item => {
                    if (item.id > maxId) maxId = item.id;
                    if (item.id > lastSeenId) unreadCount++;
                    
                    let icon = item.loai == 'san_pham' ? '🌱' : '🎁'; // Icon tùy loại
                    
                    list.innerHTML += `
                        <a href="${item.duong_dan || '#'}" class="noti-item">
                            <h4>${icon} ${item.tieu_de}</h4>
                            <p>${item.noi_dung}</p>
                            <small>${item.ngay_tao}</small>
                        </a>`;
                });

                if (unreadCount > 0) {
                    badge.innerText = unreadCount;
                    badge.style.display = 'flex';
                } else {
                    badge.style.display = 'none';
                }
                document.querySelector('.notification-wrapper').dataset.latestId = maxId;
            } else {
                list.innerHTML = '<p style="padding:20px;text-align:center;color:#888">Chưa có thông báo nào</p>';
            }
        })
        .catch(err => console.error(err));
}

function toggleNotiDropdown() {
    const dropdown = document.getElementById('public-noti-dropdown');
    dropdown.classList.toggle('active');
    
    // Nếu mở ra -> Coi như đã xem hết -> Xóa số đỏ
    if (dropdown.classList.contains('active')) {
        document.getElementById('public-noti-badge').style.display = 'none';
        const wrapper = document.querySelector('.notification-wrapper');
        const latestId = wrapper.dataset.latestId || 0;
        if (latestId > 0) localStorage.setItem('last_seen_broadcast_id', latestId);
    }
}

'use strict';

document.addEventListener('DOMContentLoaded', function() {
  
  // 1. CẤU HÌNH KEY GIỎ HÀNG (Đồng bộ với giohang.php)
  const currentUserId = <?php echo json_encode($_SESSION['user_id'] ?? null); ?>;
  const CART_KEY = currentUserId ? 'myshop_cart_' + currentUserId : 'myshop_cart_guest';

  // 2. HÀM XỬ LÝ GIỎ HÀNG
  function getCartItems() {
    try { return JSON.parse(localStorage.getItem(CART_KEY) || '[]'); } catch (e) { return []; }
  }

  function saveCartItems(items) {
    localStorage.setItem(CART_KEY, JSON.stringify(items));
    updateCartUI(); 
  }

  function updateCartUI() {
    const badge = document.querySelector('.cart-badge');
    if (!badge) return;
    const items = getCartItems();
    const count = items.reduce((sum, item) => sum + (item.quantity || 0), 0);
    badge.textContent = count > 99 ? '99+' : count;
    badge.style.display = count > 0 ? 'flex' : 'none';
  }

  window.addToCart = function(product) {
    let items = getCartItems();
    const idx = items.findIndex(i => i.id === product.id);
    if (idx !== -1) {
      items[idx].quantity += product.quantity;
    } else {
      items.push(product);
    }
    saveCartItems(items);
    
    // Toast thông báo
    const toast = document.createElement('div');
    toast.style.cssText = 'position:fixed; top:80px; right:20px; background:#27ae60; color:white; padding:15px 25px; border-radius:8px; z-index:9999; box-shadow:0 5px 15px rgba(0,0,0,0.2); animation: slideIn 0.3s ease; font-weight:600; display:flex; align-items:center; gap:10px;';
    toast.innerHTML = `<i class="fas fa-check-circle"></i> Đã thêm <b>${product.name}</b> vào giỏ!`;
    document.body.appendChild(toast);
    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(-20px)';
        setTimeout(() => toast.remove(), 300);
    }, 2000);
  };

  // 3. SỰ KIỆN CLICK NÚT MUA
  document.addEventListener('click', function(e) {
    const btn = e.target.closest('.add-cart-btn');
    if (!btn || btn.disabled) return;
    e.preventDefault();

    const card = btn.closest('.product-card');
    if (!card) return;

    const id = card.getAttribute('data-id');
    const name = card.getAttribute('data-name');
    const price = parseFloat(card.getAttribute('data-price'));
    // Lấy đúng đường dẫn ảnh từ data-img (ưu tiên), nếu không có thì lấy từ img.src
    let image = card.getAttribute('data-img') || '';
    if (!image) {
      const imgEl = card.querySelector('.product-image-wrapper img');
      if (imgEl && imgEl.src) image = imgEl.src;
    }

    // Lấy số lượng từ input
    let quantity = 1;
    const qtyInput = card.querySelector('.qty-input');
    if (qtyInput) quantity = parseInt(qtyInput.value) || 1;

    if (id) {
      addToCart({
        id: Number(id),
        name: name,
        price: price,
        image: image,
        quantity: quantity
      });
    }
  });

  // 4. SLIDER LOGIC (Banner)
  let currentSlide = 0;
  const slides = document.querySelectorAll('.slide');
  const dots = document.querySelectorAll('.dot');
  
  function showSlide(index) {
      if(slides.length === 0) return;
      if (index >= slides.length) currentSlide = 0;
      else if (index < 0) currentSlide = slides.length - 1;
      else currentSlide = index;
      
      slides.forEach(s => s.classList.remove('active'));
      dots.forEach(d => d.classList.remove('active'));
      
      slides[currentSlide].classList.add('active');
      if(dots[currentSlide]) dots[currentSlide].classList.add('active');
  }

  window.nextSlide = () => showSlide(currentSlide + 1);
  window.prevSlide = () => showSlide(currentSlide - 1);
  window.goToSlide = (n) => showSlide(n);
  
  if(slides.length > 0) setInterval(window.nextSlide, 5000);

  // Khởi chạy
  updateCartUI();
});

// Các hàm tiện ích toàn cục
window.updateQty = function(btn, delta) {
    const input = btn.parentElement.querySelector('.qty-input');
    let val = parseInt(input.value) + delta;
    if (val < 1) val = 1;
    input.value = val;
};

window.filterCat = function(id) {
    const url = new URL(window.location.href);
    if(id == 0) url.searchParams.delete('category');
    else url.searchParams.set('category', id);
    window.location.href = url.toString();
};

window.applyPrice = function() {
    const min = document.getElementById('minPrice').value;
    const max = document.getElementById('maxPrice').value;
    const url = new URL(window.location.href);
    if(min > 0) url.searchParams.set('min_price', min); else url.searchParams.delete('min_price');
    if(max > 0) url.searchParams.set('max_price', max); else url.searchParams.delete('max_price');
    window.location.href = url.toString();
};

window.doSearch = function() {
    const q = document.getElementById('searchInput').value.trim();
    const url = new URL(window.location.href);
    if(q) url.searchParams.set('search', q); else url.searchParams.delete('search');
    window.location.href = url.toString();
};

document.getElementById('searchInput').addEventListener('keypress', function(e) {
    if(e.key === 'Enter') doSearch();
});

// Chat Logic
function toggleChat() {
    const win = document.getElementById('chatWindow');
    win.style.display = win.style.display === 'none' ? 'flex' : 'none';
}
function sendMessage() {
    const input = document.getElementById('chatInput');
    const text = input.value.trim();
    if(!text) return;
    const body = document.getElementById('chatMessages');
    body.innerHTML += `<div style="background:#1d3e1f; color:white; padding:8px 12px; border-radius:10px; align-self:flex-end; margin-top:5px;">${text}</div>`;
    input.value = '';
    body.scrollTop = body.scrollHeight;
}
</script>
</body>
</html>
