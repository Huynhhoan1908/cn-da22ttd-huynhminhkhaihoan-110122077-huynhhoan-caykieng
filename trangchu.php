<?php
session_start();
// Kết nối CSDL
try {
    $host = 'localhost';
    $dbname = 'web_cay';
    $username = 'root';
    $password = '';
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    // Tự động khoá sản phẩm hết hàng
    $conn->query("UPDATE san_pham SET trang_thai = 0 WHERE so_luong <= 0 AND trang_thai != 0");

    // Lấy thông tin user nếu đã đăng nhập
    $user_info = null;
    if (isset($_SESSION['user_id'])) {
        $stmt = $conn->prepare("SELECT ho_ten, email, so_dien_thoai as dien_thoai, dia_chi FROM nguoi_dung WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user_info = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Xử lý Tìm kiếm
    $limit = 12;
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    
    if ($search !== '') {
        $like = '%' . $search . '%';
        $stmt = $conn->prepare("SELECT * FROM san_pham WHERE ten_san_pham LIKE ? ORDER BY id DESC LIMIT $limit");
        $stmt->execute([$like]);
    } else {
        $stmt = $conn->query("SELECT * FROM san_pham ORDER BY id DESC LIMIT $limit");
    }
    $featured = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    die("Lỗi kết nối: " . $e->getMessage());
}
?>
<!doctype html>
<html lang="vi">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>HuynhHoan — Cây Cảnh & Cây Xanh Chất Lượng</title>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<style>
/* ==========================================
   LIGHT MINIMALIST DESIGN SYSTEM
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
  
  /* Typography */
  --font-base: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
  
  /* Layout */
  --container-max: 1280px;
  
  /* Border Radius */
  --radius-sm: 0.125rem;
  --radius-md: 0.25rem;
  --radius-lg: 0.5rem;
  --radius-full: 9999px;
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
  font-family: var(--font-base);
  font-size: 16px;
  line-height: 1.5;
  color: #1d3e1f;
  background-color: #fffef5;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
}

a {
  color: inherit;
  text-decoration: none;
}

button {
  font-family: inherit;
  cursor: pointer;
  border: none;
  background: none;
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
  padding: 0 1rem;
}

@media (min-width: 640px) {
  .container { padding: 0 1.5rem; }
}

@media (min-width: 1024px) {
  .container { padding: 0 2rem; }
}

/* ==========================================
   ANNOUNCEMENT BAR
========================================== */

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
  gap: 0.5rem;
}

/* User menu dropdown */
.user-menu-wrapper { position: relative; }
.user-menu { 
  position: absolute;
  right: 0;
  top: calc(100% + 8px);
  background: var(--white);
  border: 1px solid var(--stone-200);
  box-shadow: 0 10px 20px rgba(0,0,0,0.08);
  border-radius: 8px;
  min-width: 160px;
  padding: 8px 0;
  display: none;
  z-index: 60;
}
.user-menu .user-greet { padding: 8px 12px; font-size: 0.95rem; color: var(--stone-700); border-bottom: 1px solid var(--stone-100); }
.user-menu-item { display: block; padding: 10px 14px; color: var(--stone-600); font-size: 0.95rem; text-decoration: none; }
.user-menu-item:hover { background: var(--stone-100); color: var(--stone-900); }
.user-menu-wrapper:hover .user-menu { display: block; }

/* On small screens show menu with a class toggle (JS will add .open) */
.user-menu-wrapper.open .user-menu { display: block; }

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
  top: -4px;
  right: -4px;
  background-color: #5a7a4f;
  color: var(--white);
  font-size: 0.625rem;
  width: 1rem;
  height: 1rem;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 600;
}

/* ==========================================
   BANNER SLIDER
   ========================================== */

.banner-slider {
  position: relative;
  height: 90vh;
  min-height: 600px;
  overflow: hidden;
  background: var(--stone-100);
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
  background: linear-gradient(to right, rgba(0,0,0,0.5), transparent);
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
  font-size: 4rem;
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

/* Slider Navigation */
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
  color: var(--stone-900);
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

/* Slider Dots */
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

/* ==========================================
   RESPONSIVE BANNER
   ========================================== */

@media (max-width: 768px) {
  .banner-slider {
    height: 70vh;
    min-height: 500px;
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

  .slider-nav {
    padding: 0 1rem;
  }
}

/* ==========================================
   FEATURES BAR
   ========================================== */

.features-bar {
  background-color: var(--white);
  border-top: 1px solid var(--stone-200);
  border-bottom: 1px solid var(--stone-200);
  padding: 1.5rem 0;
}

.features-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 1.5rem;
}

@media (min-width: 1024px) {
  .features-grid {
    grid-template-columns: repeat(4, 1fr);
  }
}

.feature-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.feature-icon {
  font-size: 2rem;
  color: var(--stone-900);
  flex-shrink: 0;
}

.feature-text h3 {
  font-size: 0.875rem;
  font-weight: 500;
  color: var(--stone-900);
  margin-bottom: 0.125rem;
}

.feature-text p {
  font-size: 0.75rem;
  color: var(--stone-500);
}

/* ==========================================
   SECTIONS
   ========================================== */

.section {
  padding: 4rem 0;
}

.section-bg-white {
  background-color: var(--white);
}

.section-header {
  text-align: center;
  margin-bottom: 3rem;
}

.section-title {
  font-size: 1.875rem;
  color: var(--stone-900);
  margin-bottom: 0.5rem;
}

.section-subtitle {
  color: var(--stone-600);
}

/* ==========================================
   CATEGORIES (even spacing & equal height cards)
   ========================================== */

.categories-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
  align-items: stretch;
}

/* make the whole card a link and full height so items align */
.category-card,
.category-card > a {
  display: flex;
  flex-direction: column;
  height: 100%;
  background-color: var(--stone-50);
  border: 1px solid var(--stone-200);
  border-radius: var(--radius-md);
  overflow: hidden;
  cursor: pointer;
  transition: transform 0.18s ease, box-shadow 0.18s ease;
  text-decoration: none;
  color: inherit;
}

.category-card:hover,
.category-card > a:hover {
  transform: translateY(-4px);
  box-shadow: 0 10px 24px rgba(0,0,0,0.06);
  border-color: var(--stone-300);
}

/* fixed visual image area so all cards match */
.category-image {
  flex: 0 0 160px; /* same height for all images */
  width: 100%;
  overflow: hidden;
  background: var(--stone-100);
}

@media (min-width: 1024px) {
  .category-image { flex: 0 0 180px; }
}

.category-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
  transition: transform 0.45s ease;
}

.category-card:hover .category-image img {
  transform: scale(1.04);
}

/* info area fills remaining height and is centered */
.category-info {
  padding: 1rem;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  text-align: center;
  gap: 0.25rem;
  flex: 1 1 auto;
}

.category-name {
  font-size: 1rem;
  font-weight: 600;
  color: var(--stone-900);
}

.category-count {
  font-size: 0.85rem;
  color: var(--stone-500);
}

/* ==========================================
   PRODUCTS
   ========================================== */

.gender-filter {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.75rem;
  margin-bottom: 3rem;
}

.filter-btn {
  padding: 0.5rem 1.5rem;
  font-size: 0.875rem;
  font-weight: 500;
  border: 1px solid var(--stone-300);
  border-radius: var(--radius-md);
  color: var(--stone-600);
  transition: all 0.2s;
}

.filter-btn:hover {
  background-color: var(--stone-50);
  color: var(--stone-900);
}

.filter-btn.active {
  background-color: var(--stone-900);
  color: var(--white);
  border-color: var(--stone-900);
}

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
  background-color: var(--white);
  border: 1px solid var(--stone-200);
  border-radius: var(--radius-md);
  overflow: hidden;
  transition: all 0.3s;
}

.product-card:hover {
  border-color: var(--stone-300);
}

.product-image {
  position: relative;
  aspect-ratio: 3/4;
  background-color: var(--stone-50);
  overflow: hidden;
}

.product-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.7s;
}

.product-card:hover .product-image img {
  transform: scale(1.05);
}

.product-badge {
  position: absolute;
  top: 0.75rem;
  left: 0.75rem;
  background-color: var(--stone-900);
  color: var(--white);
  padding: 0.25rem 0.5rem;
  border-radius: var(--radius-sm);
  font-size: 0.75rem;
  font-weight: 500;
}

.product-actions {
  position: absolute;
  top: 0.75rem;
  right: 0.75rem;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  opacity: 0;
  transition: opacity 0.3s;
}

.product-card:hover .product-actions {
  opacity: 1;
}

.action-btn {
  width: 2.25rem;
  height: 2.25rem;
  display: flex;
  align-items: center;
  justify-content: center;
  background-color: var(--white);
  border-radius: var(--radius-md);
  box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
  transition: all 0.2s;
}

.action-btn:hover {
  background-color: var(--stone-100);
}

.action-btn.favorited {
  background-color: var(--rose-500);
}

.action-btn.favorited i {
  color: var(--white);
}

.action-btn i {
  font-size: 1rem;
  color: var(--stone-900);
}

.product-add-cart {
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  padding: 0.75rem;
  transform: translateY(100%);
  transition: transform 0.3s;
}

.product-card:hover .product-add-cart {
  transform: translateY(0);
}

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

.buy-now-btn {
  width: 100%;
  background: linear-gradient(135deg, #D4C5B0, #E8DCC8);
  color: #2d2a26;
  padding: 0.625rem;
  border-radius: var(--radius-md);
  font-size: 0.875rem;
  font-weight: 600;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  transition: all 0.3s ease;
  cursor: pointer;
  box-shadow: 0 2px 8px rgba(212, 197, 176, 0.4);
}

.buy-now-btn:hover {
  background: linear-gradient(135deg, #C5B5A0, #D4C5B0);
  transform: translateY(-2px);
  box-shadow: 0 4px 16px rgba(212, 197, 176, 0.5);
}

.size-btn-small {
  padding: 6px 8px;
  border: 2px solid #e0e0e0;
  border-radius: 6px;
  background: #fff;
  color: #666;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 2px;
  min-width: 50px;
  font-size: 0.75rem;
  cursor: pointer;
  transition: all 0.2s;
}

.size-btn-small:hover {
  border-color: #2d2a26 !important;
  background: #f5f5f5 !important;
}

.size-btn-small:active,
.size-btn-small.selected {
  border-color: #2d2a26 !important;
  background: #2d2a26 !important;
  color: #fff !important;
}

.product-info {
  padding: 1rem;
}

.product-name {
  font-size: 0.875rem;
  font-weight: 500;
  color: var(--stone-900);
  line-height: 1.4;
  min-height: 2.5rem;
  margin-bottom: 0.5rem;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.product-rating {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 0.5rem;
}

.stars {
  display: flex;
  gap: 0.125rem;
}

.stars i {
  font-size: 0.75rem;
  color: var(--stone-900);
}

.stars i.empty {
  color: var(--stone-300);
}

.review-count {
  font-size: 0.75rem;
  color: var(--stone-500);
}

.product-price-row {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 0.5rem;
}

.product-price {
  font-size: 1.125rem;
  font-weight: 600;
  color: var(--stone-900);
}

.product-old-price {
  font-size: 0.875rem;
  color: var(--stone-400);
  text-decoration: line-through;
}

.product-meta {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.size-badge {
  display: inline-block;
  background: linear-gradient(90deg, #ef476f, #ffd166);
  color: var(--white);
  padding: 0.25rem 0.5rem;
  border-radius: var(--radius-full);
  font-size: 0.75rem;
  font-weight: 600;
}

.stock-badge {
  display: inline-block;
  background-color: var(--stone-100);
  color: var(--stone-900);
  padding: 0.25rem 0.5rem;
  border-radius: var(--radius-full);
  font-size: 0.75rem;
  font-weight: 600;
}

/* ==========================================
   BUTTONS
   ========================================== */

.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  padding: 0.75rem 2rem;
  font-size: 1rem;
  font-weight: 500;
  border-radius: var(--radius-md);
  transition: all 0.2s;
}

.btn-primary {
  background-color: var(--stone-900);
  color: var(--white);
}

.btn-primary:hover {
  background-color: var(--stone-600);
}

.btn-outline {
  border: 1px solid var(--stone-300);
  color: var(--stone-900);
}

.btn-outline:hover {
  background-color: var(--stone-100);
}

.btn-white {
  background-color: white;
  color: var(--stone-900);
  border: 2px solid white;
}

.btn-white:hover {
  background-color: transparent;
  color: white;
}

/* ==========================================
   FOOTER
   ========================================== */

.footer {
  background-color: var(--stone-50);
  border-top: 1px solid var(--stone-200);
  padding: 3rem 0 1.5rem;
}

.footer-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 2rem;
  margin-bottom: 2rem;
}

@media (min-width: 640px) {
  .footer-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (min-width: 1024px) {
  .footer-grid {
    grid-template-columns: repeat(4, 1fr);
  }
}

.footer-col h3 {
  font-size: 1.125rem;
  color: var(--stone-900);
  margin-bottom: 1rem;
}

.footer-col p {
  font-size: 0.875rem;
  color: var(--stone-600);
  line-height: 1.75;
  margin-bottom: 1rem;
}

.footer-col ul {
  list-style: none;
}

.footer-col ul li {
  margin-bottom: 0.5rem;
}

.footer-col ul li a {
  font-size: 0.875rem;
  color: var(--stone-600);
  transition: color 0.2s;
}

.footer-col ul li a:hover {
  color: var(--stone-900);
}

.social-links {
  display: flex;
  gap: 0.75rem;
}

.social-btn {
  width: 2.25rem;
  height: 2.25rem;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 1px solid var(--stone-300);
  border-radius: var(--radius-full);
  color: var(--stone-600);
  transition: all 0.2s;
}

.social-btn:hover {
  color: var(--stone-900);
  border-color: var(--stone-900);
}

.footer-bottom {
  padding-top: 2rem;
  border-top: 1px solid var(--stone-200);
  text-align: center;
}

.footer-bottom p {
  font-size: 0.875rem;
  color: var(--stone-500);
}

/* ==========================================
   UTILITIES
   ========================================== */

.text-center {
  text-align: center;
}

.mt-3 {
  margin-top: 3rem;
}

@media (max-width: 1023px) {
  .hide-mobile { display: none; }
}

@media (min-width: 1024px) {
  .show-mobile { display: none; }
}
</style>
<!-- Checkout modal styles (homepage) -->
<style>
.hc-modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,0.6);backdrop-filter:blur(4px);z-index:10000;display:none;align-items:center;justify-content:center}
.hc-modal-overlay.active{display:flex}
.hc-checkout-modal{background:#fff;border-radius:12px;max-width:680px;width:94%;max-height:90vh;overflow:auto;box-shadow:0 20px 40px rgba(0,0,0,0.2);}
.hc-modal-header{display:flex;align-items:center;justify-content:space-between;padding:18px;border-bottom:1px solid #f1f1f1}
.hc-modal-title{font-size:1.25rem;font-weight:700}
.hc-modal-close{width:40px;height:40px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;background:transparent;border:none}
.hc-modal-body{padding:18px}
.hc-checkout-form{display:flex;flex-direction:column;gap:12px}
.hc-form-group{display:flex;flex-direction:column;gap:6px}
.hc-form-input,.hc-form-select,.hc-form-textarea{padding:10px;border:1px solid #e6e6e6;border-radius:8px}
.hc-summary{background:#fafafa;padding:12px;border-radius:8px;margin-top:8px}
.hc-submit-btn{background:#111;color:#fff;padding:12px;border-radius:8px;border:none;font-weight:700}
</style>
</head>
<body>

<header class="header">
  <div class="container">
    <a href="/" class="brand-logo" style="display:flex;align-items:center;gap:12px;">
      <img src="images/logo.jpg" alt="Logo" style="height:45px;width:auto;border-radius:8px;display:block;">
      <span style="font-weight:600;font-size:1.4rem;line-height:1;">HuynhHoan</span>
    </a>

    <nav class="nav">
      <a href="trangchu.php">Trang chủ</a>
      <a href="san-pham.php">Sản phẩm</a>
      <a href="baiviet.php">Bài viết</a>
      <a href="don_hang_cua_toi.php">Đơn Hàng</a>
      <a href="lienhe.php">Liên Hệ</a>
    </nav>

    <div class="header-actions">
        <a href="giohang.php" class="icon-btn">
            <i class="fas fa-shopping-bag"></i>
            <span class="cart-badge" style="display:none;">0</span>
        </a>
        
        <?php if (isset($_SESSION['user_id'])): ?>
            <a href="logout.php" class="icon-btn" title="Đăng xuất"><i class="fas fa-sign-out-alt"></i></a>
        <?php else: ?>
            <a href="dangnhap.php" class="icon-btn" title="Đăng nhập"><i class="fas fa-sign-in-alt"></i></a>
        <?php endif; ?>
    </div>
  </div>
</header>
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

        <div class="slide-cta"></div>
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
      <span class="dot" onclick="goToSlide(3)"></span>
    </div>
</section>

<section class="section">
  <div class="container">
    <h2 style="text-align:center; font-size:2rem; margin:3rem 0; color:#1d3e1f; font-weight:700;">Sản Phẩm Nổi Bật</h2>

    <div class="products-grid">
      <?php if (empty($featured)): ?>
        <p style="text-align:center;color:#888;width:100%;">Hiện chưa có sản phẩm nổi bật để hiển thị.</p>
      <?php endif; ?>
      <?php foreach ($featured as $p):
          $img = !empty($p['hinh_anh']) ? 'uploads/' . $p['hinh_anh'] : 'images/no-image.jpg';
          $isLocked = (isset($p['trang_thai']) && $p['trang_thai'] == 0) || ((int)($p['so_luong'] ?? 0) <= 0);
      ?>
        <article class="product-card" 
                 data-id="<?php echo (int)$p['id']; ?>"
                 data-name="<?php echo htmlspecialchars($p['ten_san_pham']); ?>"
                 data-price="<?php echo (float)$p['gia']; ?>">
                 
          <div class="product-image">
            <a href="chitiet_san_pham.php?id=<?php echo $p['id']; ?>">
                <img src="<?php echo htmlspecialchars($img); ?>" alt="<?php echo htmlspecialchars($p['ten_san_pham']); ?>"
                     style="<?php echo $isLocked ? 'filter: grayscale(1);' : ''; ?>">
            </a>
            <?php if ($isLocked): ?>
              <span style="position:absolute;top:10px;left:10px;background:#ef4444;color:white;padding:5px 10px;font-size:12px;border-radius:4px;font-weight:bold;">HẾT HÀNG</span>
            <?php endif; ?>
          </div>
          
          <div class="product-info">
            <div>
                <a href="chitiet_san_pham.php?id=<?php echo $p['id']; ?>" class="product-name">
                    <?php echo htmlspecialchars($p['ten_san_pham']); ?>
                </a>
                <div class="product-price">
                    <?php echo number_format((float)$p['gia'], 0, ',', '.'); ?>₫
                </div>
            </div>
            
            <?php if (!$isLocked): ?>
              <button type="button" class="add-cart-btn">
                  <i class="fas fa-cart-plus"></i> THÊM VÀO GIỎ
              </button>
            <?php else: ?>
                <button type="button" class="add-cart-btn" style="background:#eee;color:#999;cursor:not-allowed;" disabled>
                  HẾT HÀNG
                </button>
            <?php endif; ?>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<footer style="background:#f9f9f9; padding:40px 0; text-align:center; margin-top: 50px; border-top:1px solid #eee;">
  <div class="container">
    <p style="color:#666;">© <?php echo date('Y'); ?> HuynhHoan. Mang thiên nhiên vào nhà bạn.</p>
  </div>
</footer>

<!-- Homepage Checkout Modal -->
<div class="hc-modal-overlay" id="homeCheckoutModal">
  <div class="hc-checkout-modal" role="dialog" aria-modal="true" aria-label="Thanh toán">
    <div class="hc-modal-header">
      <div class="hc-modal-title">Thanh toán — HuynhHoan</div>
      <button class="hc-modal-close" id="homeModalClose" aria-label="Đóng">
        <i class="fas fa-times"></i>
      </button>
    </div>
    <div class="hc-modal-body">
      <form id="homeCheckoutForm" class="hc-checkout-form">
        <div class="hc-form-group">
          <label>Họ và tên *</label>
          <input name="fullname" class="hc-form-input" required placeholder="Nguyễn Văn A" value="<?php echo isset($user_info['ho_ten']) ? htmlspecialchars($user_info['ho_ten']) : ''; ?>">
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px">
          <div class="hc-form-group"><label>Số điện thoại *</label><input name="phone" class="hc-form-input" required placeholder="0123456789" value="<?php echo isset($user_info['dien_thoai']) ? htmlspecialchars($user_info['dien_thoai']) : ''; ?>"></div>
          <div class="hc-form-group"><label>Email</label><input name="email" class="hc-form-input" placeholder="email@example.com" value="<?php echo isset($user_info['email']) ? htmlspecialchars($user_info['email']) : ''; ?>"></div>
        </div>

        <div class="hc-form-group"><label>Địa chỉ *</label><input name="address" class="hc-form-input" required placeholder="Số nhà, tên đường" value="<?php echo isset($user_info['dia_chi']) ? htmlspecialchars($user_info['dia_chi']) : ''; ?>"></div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px">
          <div class="hc-form-group"><label>Thành phố *</label>
            <select name="city" class="hc-form-select" required>
              <option value="">Chọn thành phố</option>
              <option>Hồ Chí Minh</option>
              <option>Hà Nội</option>
              <option>Đà Nẵng</option>
              <option>Cần Thơ</option>
              <option>Vĩnh Long</option>
            </select>
          </div>
          <div class="hc-form-group"><label>Phương thức thanh toán</label>
            <select name="payment" class="hc-form-select" id="homePaymentSelect">
              <option value="cod">Thanh toán khi nhận hàng</option>
              <option value="bank">Chuyển khoản</option>
              <option value="momo">Ví MoMo</option>
              <option value="qr">Quét mã QR</option>
            </select>
          </div>
        </div>

        <div class="hc-form-group"><label>Ghi chú</label><textarea name="note" class="hc-form-textarea" placeholder="Ghi chú đơn hàng (tùy chọn)"></textarea></div>

        <!-- Bank Transfer Info Section (hidden by default) -->
        <div id="homeBankInfoSection" style="display: none; margin-top: 1.5rem;">
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
        <div id="homeQrCodeSection" style="display: none; margin-top: 1.5rem; text-align: center;">
          <div style="background: #f5f2ed; border-radius: 12px; padding: 1.5rem;">
            <img src="images/qr.jpg" alt="QR Code Thanh Toán" style="max-width: 100%; height: auto; border-radius: 8px;">
            <p style="margin-top: 1rem; font-size: 0.875rem; color: #8a8179;">Quét mã QR để thanh toán</p>
          </div>
        </div>

        <div class="hc-summary">
          <div style="display:flex;justify-content:space-between"><span>Tạm tính</span><span id="homeModalSubtotal">0₫</span></div>
          <div style="display:flex;justify-content:space-between"><span>Phí vận chuyển</span><span id="homeModalShipping">30.000₫</span></div>
          <div style="display:flex;justify-content:space-between;font-weight:700;margin-top:8px"><span>Tổng cộng</span><span id="homeModalTotal">0₫</span></div>
        </div>

        <button type="submit" class="hc-submit-btn">Đặt hàng ngay</button>
      </form>
    </div>
  </div>
</div>

<script>
'use strict';

document.addEventListener('DOMContentLoaded', function() {
  
  // 1. CẤU HÌNH KEY (Đảm bảo đồng bộ với giohang.php)
  // Sử dụng json_encode để tránh lỗi cú pháp JS khi session null
  const currentUserId = <?php echo json_encode($_SESSION['user_id'] ?? null); ?>;
  const CART_KEY = currentUserId ? 'myshop_cart_' + currentUserId : 'myshop_cart_guest';

  // 2. CÁC HÀM XỬ LÝ
  function getCartItems() {
    try { return JSON.parse(localStorage.getItem(CART_KEY) || '[]'); } catch (e) { return []; }
  }

  function saveCartItems(items) {
    localStorage.setItem(CART_KEY, JSON.stringify(items));
    updateCartUI(); 
  }

  function updateCartUI() {
    const cartLink = document.querySelector('a.icon-btn[href="giohang.php"]');
    if (!cartLink) return;

    let badge = cartLink.querySelector('.cart-badge');
    if (!badge) {
      badge = document.createElement('span');
      badge.className = 'cart-badge';
      cartLink.appendChild(badge);
    }

    const items = getCartItems();
    const count = items.reduce((sum, item) => sum + (item.quantity || 0), 0);

    badge.textContent = count > 99 ? '99+' : count;
    badge.style.display = count > 0 ? 'flex' : 'none';
  }

  // 3. HÀM THÊM VÀO GIỎ
  window.addToCart = function(product) {
    // Tự động kiểm tra: Nếu muốn ép đăng nhập thì bỏ comment đoạn dưới
    /*
    if (!currentUserId) {
        if (confirm('Bạn cần đăng nhập để mua hàng. Đăng nhập ngay?')) window.location.href = 'dangnhap.php';
        return;
    }
    */

    let items = getCartItems();
    const idx = items.findIndex(i => i.id === product.id);
    
    if (idx !== -1) {
      items[idx].quantity += product.quantity;
    } else {
      items.push(product);
    }

    saveCartItems(items);
    
    // Hiệu ứng thông báo (Toast)
    const toast = document.createElement('div');
    toast.style.cssText = 'position:fixed; top:80px; right:20px; background:#27ae60; color:white; padding:15px 25px; border-radius:8px; z-index:9999; box-shadow:0 5px 15px rgba(0,0,0,0.2); animation: slideIn 0.3s ease; font-weight:600; display:flex; align-items:center; gap:10px;';
    toast.innerHTML = `<i class="fas fa-check-circle"></i> Đã thêm <b>${product.name}</b> vào giỏ!`;
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(-20px)';
        toast.style.transition = 'all 0.3s';
        setTimeout(() => toast.remove(), 300);
    }, 2000);
  };

  // 4. BẮT SỰ KIỆN CLICK NÚT MUA
  document.addEventListener('click', function(e) {
    const btn = e.target.closest('.add-cart-btn');
    if (!btn) return;
    e.preventDefault();

    const card = btn.closest('.product-card');
    if (!card) return;

    const id = card.getAttribute('data-id');
    const name = card.getAttribute('data-name');
    const price = parseFloat(card.getAttribute('data-price'));
    const imgEl = card.querySelector('.product-image img');
    let image = '';
    if (imgEl && imgEl.src) {
        image = imgEl.src.split('/').pop();
        if(image.includes('no-image')) image = '';
    }

    if (id) {
        addToCart({
            id: Number(id),
            name: name,
            price: price,
            image: image,
            quantity: 1
        });
    }
  });

  // Khởi chạy
  updateCartUI();
});

// Chat function
function toggleChat() {
    const win = document.getElementById('chatWindow');
    win.classList.toggle('chat-visible');
    if(win.classList.contains('chat-visible')) document.getElementById('chatInput').focus();
}
function sendMessage() {
    const input = document.getElementById('chatInput');
    const text = input.value.trim();
    if(!text) return;
    
    const body = document.getElementById('chatMessages');
    body.innerHTML += `<div class="message user-msg">${text}</div>`;
    input.value = '';
    body.scrollTop = body.scrollHeight;
    
    // Fake reply
    setTimeout(() => {
        body.innerHTML += `<div class="message bot-msg">Cảm ơn bạn đã nhắn tin. Nhân viên sẽ phản hồi sớm nhất ạ!</div>`;
        body.scrollTop = body.scrollHeight;
    }, 1000);
}
</script>
<!-- Quick View Modal for Homepage -->
<div class="hc-modal-overlay" id="homeQuickViewModal">
  <div class="hc-checkout-modal" style="max-width: 800px;">
    <div class="hc-modal-header">
      <div class="hc-modal-title" id="hqvTitle">Xem nhanh</div>
      <button class="hc-modal-close" onclick="closeHomeQuickView()" aria-label="Đóng">
        <i class="fas fa-times"></i>
      </button>
    </div>
    <div class="hc-modal-body">
      <div style="display:flex;gap:1.5rem;align-items:flex-start;flex-wrap:wrap;">
        <img id="hqvImg" src="" alt="" style="width:280px;height:auto;border-radius:8px;object-fit:cover;" />
        <div style="flex:1;min-width:300px;">
          <div style="font-weight:700;font-size:1.25rem;margin-bottom:0.5rem;" id="hqvName"></div>
          <div style="font-family:Georgia,serif;font-size:1.5rem;font-weight:700;margin-bottom:1rem;color:#2d2a26;" id="hqvPrice"></div>
          <div id="hqvDesc" style="color:#666;line-height:1.8;font-size:0.95rem;"></div>
        </div>
      </div>
    </div>
  </div>
</div>
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
    #live-chat-widget { position: fixed; bottom: 20px; right: 20px; z-index: 9999999; font-family: sans-serif; }
    #chatLauncher { width: 60px; height: 60px; background: #1d3e1f; color: white; border-radius: 50%; border: none; cursor: pointer; box-shadow: 0 4px 15px rgba(0,0,0,0.3); font-size: 26px; display: flex; align-items: center; justify-content: center; position: relative; transition: transform 0.2s; }
    #chatLauncher:hover { transform: scale(1.1); }
    .online-status { position: absolute; top: 0; right: 0; width: 14px; height: 14px; background: #2ecc71; border: 2px solid #fff; border-radius: 50%; }
    
    #chatWindow { position: absolute; bottom: 80px; right: 0; width: 320px; height: 400px; background: #fff; border-radius: 12px; box-shadow: 0 5px 30px rgba(0,0,0,0.2); display: none; flex-direction: column; overflow: hidden; border: 1px solid #ddd; }
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
    #live-chat-widget { position: fixed; bottom: 20px; right: 20px; z-index: 999999; font-family: sans-serif; }
    
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
    #live-chat-widget { position: fixed; bottom: 20px; right: 20px; z-index: 999999; font-family: sans-serif; }
    
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
                        html += '<div class="message ' + type + '">' + msg.message + '</div>';
                    });
                }
                body.innerHTML = html;
                body.scrollTop = body.scrollHeight; // Tự cuộn xuống dưới
            })
            .catch(err => console.log('Lỗi chat:', err));
    }

    function appendMessage(text, cls) {
        const div = document.createElement('div');
        div.className = 'message ' + cls;
        div.textContent = text;
        const body = document.getElementById('chatMessages');
        body.appendChild(div);
        body.scrollTop = body.scrollHeight;
    }
</script>
<link rel="stylesheet" href="assets/notifications.css">
<script src="assets/notifications.js" defer></script>
</body>
</html>