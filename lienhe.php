<?php
// Tùy chọn: Thêm file kết nối nếu bạn muốn lưu phản hồi vào database
// require_once 'connect.php'; 

// Xử lý gửi biểu mẫu
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_contact'])) {
  $name = trim($_POST['name'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $phone = trim($_POST['phone'] ?? '');
  $subject = trim($_POST['subject'] ?? '');
  $content = trim($_POST['content'] ?? '');
    
  // --- Bắt đầu Logic Xử lý/Lưu DB/Gửi Email ---
    
  if (empty($name) || empty($email) || empty($content)) {
    $message = '<div class="alert error">Vui lòng điền đầy đủ Tên, Email và Nội dung.</div>';
  } else {
    // Tùy chọn: Gửi email thông báo cho Admin (cần cấu hình mail server)
    // mail('HuynhHoan@gmail.com', "Liên hệ mới: $subject", $content, "From: $email");
        
    // Tùy chọn: Lưu vào bảng database (ví dụ: `lien_he`)
    // if (isset($conn)) {
    //     $stmt = $conn->prepare("INSERT INTO lien_he (name, email, phone, subject, content) VALUES (?, ?, ?, ?, ?)");
    //     $stmt->execute([$name, $email, $phone, $subject, $content]);
    // }
        
    $message = '<div class="alert success">✅ Cảm ơn bạn, phản hồi của bạn đã được gửi thành công! Chúng tôi sẽ liên hệ lại sớm nhất.</div>';
        
    // Xóa dữ liệu form sau khi gửi thành công
    unset($_POST);
  }
    
  // --- Kết thúc Logic Xử lý ---
}
?>
<!doctype html>
<html lang="vi">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Liên Hệ — HuynhHoan</title>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400…00;800&family=Playfair+Display:wght@600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/notifications.css">

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
  
  /* Typography */
  --font-sans: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
  --font-serif: 'Playfair Display', Georgia, serif;
  
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
    color: #111 !important;
  color: #111;
  background: #fffef5;
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
  color: #0c0c0cff;
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
  display: none;
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
  max-width: 600px;
  margin: 0 auto;
}

/* ==========================================
   CONTACT SECTION
   ========================================== */

.contact-section {
  padding: var(--space-2xl) 0;
}

.contact-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: var(--space-2xl);
  margin-bottom: var(--space-2xl);
}

@media (min-width: 1024px) {
  .contact-grid {
    grid-template-columns: 1fr 1fr;
  }
}

/* ==========================================
   INFO CARD
   ========================================== */

.info-card {
  background: var(--cream-50);
  border: 1px solid var(--beige-200);
  border-radius: var(--radius-xl);
  padding: var(--space-2xl);
  box-shadow: var(--shadow-md);
}

.info-card h2 {
  font-family: var(--font-serif);
  font-size: 2rem;
  color: var(--black);
  margin-bottom: var(--space-xl);
  padding-bottom: var(--space-lg);
  border-bottom: 2px solid var(--beige-200);
}

.info-item {
  display: flex;
  align-items: flex-start;
  gap: var(--space-lg);
  padding: var(--space-lg);
  margin-bottom: var(--space-md);
  background: var(--cream-100);
  border-radius: var(--radius-lg);
  border-left: 4px solid var(--accent-gold);
  transition: all 0.3s ease;
}

.info-item:hover {
  transform: translateX(8px);
  box-shadow: var(--shadow-md);
}

.info-icon {
  width: 3rem;
  height: 3rem;
  background: var(--black);
  color: var(--cream-50);
  border-radius: var(--radius-md);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.info-icon i {
  font-size: 1.25rem;
}

.info-content h3 {
  font-size: 1rem;
  font-weight: 700;
  color: var(--black);
  margin-bottom: var(--space-xs);
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.info-content p {
  font-size: 1.125rem;
  color: var(--charcoal);
  margin: 0;
}

.info-content a {
  color: var(--accent-gold);
  font-weight: 600;
  transition: all 0.2s ease;
}

.info-content a:hover {
  color: var(--black);
  text-decoration: underline;
}

/* ==========================================
   IMAGE CARD
   ========================================== */

.image-card {
  background: var(--cream-50);
  border: 1px solid var(--beige-200);
  border-radius: var(--radius-xl);
  overflow: hidden;
  box-shadow: var(--shadow-md);
  position: relative;
}

.image-card::before {
  content: '';
  position: absolute;
  inset: 0;
  background: linear-gradient(to bottom, transparent 0%, rgba(0,0,0,0.3) 100%);
  z-index: 1;
  opacity: 0;
  transition: opacity 0.3s ease;
}

.image-card:hover::before {
  opacity: 1;
}

.image-card img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.5s ease;
}

.image-card:hover img {
  transform: scale(1.05);
}

.image-overlay {
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  padding: var(--space-xl);
background: linear-gradient(to top, rgba(0,0,0,0.8) 0%, transparent 100%);
  color: white;
  z-index: 2;
  transform: translateY(100%);
  transition: transform 0.3s ease;
}

.image-card:hover .image-overlay {
  transform: translateY(0);
}

.image-overlay h3 {
  font-family: var(--font-serif);
  font-size: 1.5rem;
  margin-bottom: var(--space-sm);
}

.image-overlay p {
  font-size: 0.875rem;
  opacity: 0.9;
}

/* ==========================================
   DESCRIPTION SECTION
   ========================================== */

.description-section {
  background: var(--cream-50);
  border: 1px solid var(--beige-200);
  border-radius: var(--radius-xl);
  padding: var(--space-2xl);
  margin-bottom: var(--space-2xl);
  border-left: 6px solid var(--accent-gold);
}

.description-section h3 {
  font-family: var(--font-serif);
  font-size: 1.75rem;
  color: var(--black);
  margin-bottom: var(--space-lg);
}

.description-section p {
  font-size: 1.125rem;
  color: var(--charcoal);
  margin-bottom: var(--space-md);
  line-height: 1.8;
}

.description-section .highlight {
  background: var(--beige-100);
  padding: var(--space-lg);
  border-radius: var(--radius-lg);
  margin: var(--space-lg) 0;
}

.description-section .highlight strong {
  color: var(--black);
  font-size: 1.125rem;
  display: block;
  margin-bottom: var(--space-md);
}

.description-section ul {
  list-style: none;
  padding: 0;
  margin: var(--space-md) 0;
}

.description-section ul li {
  padding: var(--space-md);
  margin-bottom: var(--space-sm);
  background: var(--cream-100);
  border-radius: var(--radius-md);
  border-left: 3px solid var(--accent-sage);
  position: relative;
  padding-left: 3rem;
  transition: all 0.2s ease;
}

.description-section ul li:hover {
  transform: translateX(8px);
  background: var(--cream-50);
  box-shadow: var(--shadow-sm);
}

.description-section ul li::before {
  content: '✓';
  position: absolute;
  left: var(--space-md);
  color: var(--accent-sage);
  font-weight: 700;
  font-size: 1.25rem;
}

/* ==========================================
   FEATURES GRID
   ========================================== */

.features-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: var(--space-lg);
  margin-bottom: var(--space-2xl);
}

@media (min-width: 768px) {
  .features-grid {
    grid-template-columns: repeat(3, 1fr);
  }
}

.feature-card {
  background: var(--cream-50);
  border: 1px solid var(--beige-200);
  border-radius: var(--radius-xl);
  padding: var(--space-xl);
  text-align: center;
  transition: all 0.3s ease;
}

.feature-card:hover {
  transform: translateY(-8px);
  box-shadow: var(--shadow-xl);
  border-color: var(--accent-gold);
}

.feature-icon {
  width: 4rem;
  height: 4rem;
  margin: 0 auto var(--space-lg);
  background: linear-gradient(135deg, var(--black), var(--charcoal));
  border-radius: 50%;
  display: flex;
  align-items: center;
justify-content: center;
  color: white;
}

.feature-icon i {
  font-size: 1.75rem;
}

.feature-card h3 {
  font-size: 1.125rem;
  font-weight: 700;
  color: var(--black);
  margin-bottom: var(--space-sm);
}

.feature-card p {
  font-size: 0.9375rem;
  color: var(--taupe-500);
  line-height: 1.6;
}

/* ==========================================
   STATS SECTION
   ========================================== */

.stats-section {
  background: linear-gradient(135deg, var(--black) 0%, var(--charcoal) 100%);
  color: white;
  padding: var(--space-2xl);
  border-radius: var(--radius-xl);
  margin-bottom: var(--space-2xl);
}

.stats-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: var(--space-xl);
}

@media (min-width: 768px) {
  .stats-grid {
    grid-template-columns: repeat(4, 1fr);
  }
}

.stat-item {
  text-align: center;
  padding: var(--space-lg);
  border-radius: var(--radius-lg);
  background: rgba(255, 255, 255, 0.1);
  backdrop-filter: blur(10px);
  border: 1px solid rgba(255, 255, 255, 0.2);
  transition: all 0.3s ease;
}

.stat-item:hover {
  background: rgba(255, 255, 255, 0.15);
  transform: translateY(-4px);
}

.stat-value {
  font-family: var(--font-serif);
  font-size: 3rem;
  font-weight: 700;
  margin-bottom: var(--space-xs);
  background: linear-gradient(135deg, #fff, var(--accent-gold));
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.stat-label {
  font-size: 0.875rem;
  opacity: 0.9;
  text-transform: uppercase;
  letter-spacing: 0.1em;
}

/* ==========================================
   CTA SECTION
   ========================================== */

.cta-section {
  background: linear-gradient(135deg, var(--accent-gold) 0%, #b8965a 100%);
  color: white;
  padding: var(--space-2xl);
  border-radius: var(--radius-xl);
  text-align: center;
  margin-bottom: var(--space-2xl);
}

.cta-section h3 {
  font-family: var(--font-serif);
  font-size: 2rem;
  margin-bottom: var(--space-md);
}

.cta-section p {
  font-size: 1.125rem;
  margin-bottom: var(--space-xl);
  opacity: 0.95;
}

.cta-buttons {
  display: flex;
  gap: var(--space-md);
  justify-content: center;
  flex-wrap: wrap;
}

.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: var(--space-sm);
  padding: var(--space-lg) var(--space-2xl);
  font-size: 1rem;
  font-weight: 700;
  border-radius: var(--radius-full);
  transition: all 0.3s ease;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.btn-primary {
  background: white;
  color: var(--black);
}

.btn-primary:hover {
  transform: translateY(-4px);
  box-shadow: 0 10px 25px rgba(0,0,0,0.3);
}

.btn-outline {
  background: transparent;
  color: white;
  border: 2px solid white;
}

.btn-outline:hover {
  background: white;
  color: var(--accent-gold);
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
   RESPONSIVE
   ========================================== */

@media (max-width: 1023px) {
  .hide-mobile { display: none !important; }
}

@media (max-width: 768px) {
  .page-title {
    font-size: 2rem;
  }

  .info-card h2 {
    font-size: 1.5rem;
  }

  .stat-value {
    font-size: 2rem;
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
.icon-btn i {
  color: #111 !important;
}
</style>
</head>
<body>

<!-- Header -->
<header class="header">
  <div class="container">
    <a class="brand-logo" style="display:flex;align-items:center;gap:12px;">
      <img src="images/logo.jpg" alt="HuynhHoan Logo" style="height:45px;width:auto;border-radius:8px;">
      <span style="color:#000;font-size:1.4rem;font-weight:600;">
        HuynhHoan
      </span>
    </a>

    <nav class="nav">
      <a href="trangchu.php">Trang chủ</a>
      <a href="san-pham.php">Sản Phẩm</a>
      <a href="don_hang_cua_toi.php">Theo Dõi Đơn Hàng</a>
    </nav>

    <div class="header-actions">
      <a href="dangnhap.php" class="icon-btn hide-mobile" title="Tài khoản">
        <i class="fas fa-sign-out-alt"></i>
      </a>
    </div>
  </div>
</header>

<!-- Main Content -->
<div class="container">
  <section class="contact-section">
    <!-- Contact Grid -->
    <div class="contact-grid">
      <!-- Info Card -->
      <div class="info-card">
<h2>Thông Tin Liên Hệ</h2>

        <div class="info-item">
          <div class="info-icon">
            <i class="fas fa-store"></i>
          </div>
          <div class="info-content">
            <h3>Tên Cửa Hàng</h3>
            <p>HuynhHoan</p>
          </div>
        </div>

        <div class="info-item">
          <div class="info-icon">
            <i class="fas fa-phone-alt"></i>
          </div>
          <div class="info-content">
            <h3>Hotline / Zalo</h3>
            <p><a href="tel:0795474219">0795 474 219</a></p>
          </div>
        </div>

        <div class="info-item">
          <div class="info-icon">
            <i class="fas fa-map-marker-alt"></i>
          </div>
          <div class="info-content">
            <h3>Địa Chỉ</h3>
            <p>Số 126, đường Nguyễn Thiện Thành, phường Hòa Thuận, tỉnh Vĩnh Long.</p>
          </div>
        </div>

        <div class="info-item">
          <div class="info-icon">
            <i class="fas fa-envelope"></i>
          </div>
          <div class="info-content">
            <h3>Email</h3>
            <p><a href="mailto:HuynhHoanStore@gmail.com">HuynhHoanStore@gmail.com</a></p>
          </div>
        </div>

        <div class="info-item">
          <div class="info-icon">
            <i class="fas fa-clock"></i>
          </div>
          <div class="info-content">
            <h3>Giờ Làm Việc</h3>
            <p>T2 - CN: 7:00 - 22:00</p>
          </div>
        </div>
      </div>

      <!-- Image Card -->
      <div class="image-card">
        <img src="images/logo.jpg" alt="HuynhHoan Store" style="min-height: 600px;">
        <div class="image-overlay">
          <h3>Không Gian Xanh Sang Trọng</h3>
          <p>Trải nghiệm mua sắm đẳng cấp tại HuynhHoan với không gian xanh mát và đội ngũ tư vấn chuyên nghiệp</p>
        </div>
      </div>
    </div>

    <!-- Description Section -->
    <div class="description-section">
      <h3>Về HuynhHoan</h3>
      <p>
        <strong>Cảm ơn các bạn đã ghé thăm HuynhHoan — Cây Xanh Chất Lượng!</strong>
      </p>
      <p>
        Chúng tôi chuyên cung cấp các loại cây cảnh, cây nội thất, cây phong thủy và cây để bàn, phù hợp cho không gian nhà ở, văn phòng, quán cà phê và cửa hàng. Mỗi sản phẩm đều được tuyển chọn kỹ lưỡng từ giống cây, độ khỏe, dáng cây đến chậu trồng, đảm bảo tính thẩm mỹ và sức sống bền lâu.
        <br><br>
        HuynhHoan cam kết mang đến những sản phẩm cây xanh chất lượng cao, dễ chăm sóc, thân thiện với môi trường cùng sự tư vấn tận tâm, giúp không gian sống của bạn luôn xanh mát, hài hòa và tràn đầy năng lượng tích cực.
      </p>
    </div>


      <div class="highlight">
        <strong>⚠️ Quý khách lưu ý khi mua hàng:</strong>
        <ul>
          <li>Shop không nhận đặt hàng qua tin nhắn và ghi chú, quý khách vui lòng đặt hàng trên website để đảm bảo quyền lợi</li>
          <li>Khi nhận hàng, vui lòng quay lại video mở hàng để bảo vệ quyền lợi của cả hai bên</li>
<li>Nếu có bất kỳ thắc mắc hoặc khiếu nại gì, hãy nhắn tin cho shop ngay, chúng tôi sẽ hỗ trợ tận tình và nhanh chóng nhất</li>
          <li>Đổi trả miễn phí trong vòng 30 ngày nếu sản phẩm có lỗi từ nhà sản xuất</li>
          <li>Miễn phí vận chuyển cho đơn hàng từ 500.000₫ trở lên</li>
        </ul>
      </div>
    </div>
  </section>
</div>
<!-- Google Maps lớn ghim địa chỉ shop -->
<div style="max-width: 100vw; margin: 0 auto; padding: 0 0 2rem 0;">
  <h2 style="text-align:center; margin:0 0 1rem 0; font-size:2rem; color:#1d3e1f;">Bản đồ vị trí cửa hàng</h2>
  <div style="width:100%; max-width:1200px; margin:0 auto; border-radius:16px; overflow:hidden; box-shadow:0 4px 24px rgba(0,0,0,0.10);">
    <iframe
      src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3918.803964049425!2d106.0028886749911!3d10.380424567084657!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3175402c91a72099%3A0xc665134701297d02!2zMTI2IE5ndXnhu4VuIFRoxINuIFRow6FuaCwgSG_DoGEsIFRow6B1w6JuLCBWw6BuaCBMb25n!5e0!3m2!1svi!2s!4v1702434000000!5m2!1svi!2s"
      width="100%" height="500" style="border:0; display:block;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade">
    </iframe>
  </div>
</div>
<!-- Footer -->
<footer class="footer">
  <div class="container">
    <p class="footer-text">© <?php echo date('Y'); ?> HuynhHoan — Đam mê cây cảnh</p>
    <p class="footer-text" style="margin-top: 0.5rem;">
      Liên hệ: <a href="tel:0795474219" style="color: var(--accent-gold); font-weight: 600;">0795 474 219</a> | 
      Email: <a href="mailto:HuynhHoan@gmail.com" style="color: var(--accent-gold); font-weight: 600;">HuynhHoan@gmail.com</a>
    </p>
  </div>
</footer>
<script src="assets/notifications.js" defer></script>
<script>
(function(){
  'use strict';

  // ===== HIGHLIGHT ACTIVE NAV LINK =====
  (function() {
    const currentPath = window.location.pathname;
    const navLinks = document.querySelectorAll('.nav a');
    
    navLinks.forEach(link => {
      const linkPath = new URL(link.href).pathname;
      
      if (currentPath === linkPath || 
          (currentPath === '/' && linkPath === '/') ||
          (currentPath.includes('trangchu.php') && linkPath === '/') ||
          (currentPath.includes('sale.php') && link.href.includes('sale.php')) ||
          (currentPath.includes('san-pham.php') && link.href.includes('san-pham.php')) ||
          (currentPath.includes('lienhe.php') && link.href.includes('lienhe.php'))) {
        link.classList.add('active');
      }
    });
  })();

  // ===== CART BADGE =====
  function updateCartBadge() {
    try {
      const items = JSON.parse(localStorage.getItem('HuynhHoan_cart_items') || '[]');
      const count = items.reduce((s, i) => s + (i.quantity || 0), 0);
      const badge = document.getElementById('cartBadge');
      if (badge) {
        badge.textContent = count;
        badge.style.display = count > 0 ? 'flex' : 'none';
      }
    } catch(e) {
      console.error('Error updating cart badge:', e);
    }
  }

  // Initialize
  updateCartBadge();

  // ===== SMOOTH SCROLL =====
  document.querySelectorAll('a[href^="#"]').forEach(anchor => {
anchor.addEventListener('click', function (e) {
      e.preventDefault();
      const target = document.querySelector(this.getAttribute('href'));
      if (target) {
        target.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }
    });
  });

  // ===== ANIMATE ON SCROLL =====
  const observerOptions = {
    threshold: 0.1,
    rootMargin: '0px 0px -50px 0px'
  };

  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.style.opacity = '1';
        entry.target.style.transform = 'translateY(0)';
      }
    });
  }, observerOptions);

  document.querySelectorAll('.info-item, .feature-card, .stat-item').forEach(el => {
    el.style.opacity = '0';
    el.style.transform = 'translateY(20px)';
    el.style.transition = 'all 0.6s ease';
    observer.observe(el);
  });

})();
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

// Tự động chạy khi tải trang
document.addEventListener('DOMContentLoaded', loadBroadcastNoti);
</script>
</body>
</html>
