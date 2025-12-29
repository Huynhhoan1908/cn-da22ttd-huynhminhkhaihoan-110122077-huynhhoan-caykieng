<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'HuynhHoan - Quản Trị'; ?></title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Toastr -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    
    <link rel="stylesheet" href="../assets/admin.css">
</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
    <div class="sidebar-header">
        <div class="admin-avatar">
            <img src="../images/logo.jpg" alt="Logo" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">
        </div>
        <h2>HuynhHoan</h2>
        <p class="admin-title">Quản Trị Viên</p>
    </div>
    
    <ul class="sidebar-menu">
        <li class="menu-item">
            <a href="tongquan.php" class="<?php echo ($current_page == 'overview') ? 'active' : ''; ?>">
                <i class="fas fa-tachometer-alt"></i>
                <span>Tổng Quan</span>
            </a>
        </li>
        <li class="menu-item">
            <a href="qlsanpham.php" class="<?php echo ($current_page == 'products') ? 'active' : ''; ?>">
                <i class="fas fa-box"></i>
                <span>Sản Phẩm</span>
            </a>
        </li>
        <li class="menu-item">
            <a href="qldanhmuc.php" class="<?php echo ($current_page == 'categories') ? 'active' : ''; ?>">
                <i class="fas fa-list-ul"></i>
                <span>Danh Mục</span>
            </a>
        </li>
        <li class="menu-item">
            <a href="qldonhang.php" class="menu-link">
                <i class="fas fa-shopping-cart"></i> Đơn Hàng 
                <span id="noti-order-badge" class="admin-menu-badge"></span>
            </a>
        </li>
        <li class="menu-item">
            <a href="qlkhachhang.php" class="<?php echo ($current_page == 'customers') ? 'active' : ''; ?>">
                <i class="fas fa-users"></i>
                <span>Khách Hàng</span>
            </a>
        </li>
        <li class="menu-item">
            <a href="chat_support.php" class="nav-link" style="position: relative; display: flex; justify-content: space-between; align-items: center;">
                <div style="display: flex; align-items: center;">
                    <div class="nav-link-icon"><i class="fas fa-comments"></i></div>
                    <span>Hỗ trợ (Chat)</span>
                </div>
                
                <span id="chatBadge" class="badge-counter" style="display: none;">0</span>
            </a>
        </li>

<style>
    .badge-counter {
        background-color: #ff4757; /* Màu đỏ tươi */
        color: white;
        font-size: 11px;
        font-weight: bold;
        padding: 2px 7px;
        border-radius: 10px; /* Bo tròn */
        box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        margin-right: 10px;
        animation: pulse 2s infinite; /* Hiệu ứng nhịp đập nhẹ cho chú ý */
    }
    
    @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.1); }
        100% { transform: scale(1); }
    }

    .admin-menu-badge {
        float: right; 
        background-color: #e74c3c; 
        color: white;
        font-size: 10px;
        font-weight: bold;
        padding: 2px 6px;
        border-radius: 50%;
        margin-left: 5px;
        display: none; 
    }
</style>
        
        <li class="menu-item">
            <a href="qlbaiviet.php" class="menu-link">
                <i class="fas fa-newspaper"></i> Bài Viết 
                <span id="noti-post-badge" class="admin-menu-badge"></span>
            </a>
        <li class="menu-item">
            <a href="qlchude.php" class="menu-link">
                <i class="fas fa-layer-group"></i> Chủ Đề Bài Viết 
                <span id="noti-post-badge" class="admin-menu-badge"></span>
            </a>
        </li>
        <li class="menu-item">
            <a href="qldanhgia.php" class="menu-link">
                <i class="fas fa-star"></i> Đánh Giá 
                <span id="noti-review-badge" class="admin-menu-badge"></span>
            </a>
        </li>
        <li class="menu-item">
            <a href="qlkhuyenmai.php" class="<?php echo ($current_page == 'promotions') ? 'active' : ''; ?>">
                <i class="fas fa-tags"></i>
                <span>Khuyến Mãi</span>
            </a>
        </li>
        <li class="menu-item">
            <a href="qlkhohang.php" class="<?php echo ($current_page == 'inventory') ? 'active' : ''; ?>">
                <i class="fas fa-warehouse"></i>
                <span>Kho Hàng</span>
            </a>
        </li>
        <li class="menu-item">
            <a href="thongke_doanhthu.php" class="<?php echo ($current_page == 'revenue') ? 'active' : ''; ?>">
                <i class="fas fa-chart-line"></i>
                <span>Thống Kê Doanh Thu</span>
            </a>
        </li>
        <li class="menu-item">
            <a href="../logout.php" style="color: #ff6b6b;">
                <i class="fas fa-sign-out-alt"></i>
                <span>Đăng Xuất</span>
            </a>
        </li>
    </ul>
</div>

<!-- Main Content -->
<div class="main-content">
<script>
    function updateChatBadge() {
        fetch('get_unread_count.php')
            .then(response => response.json())
            .then(data => {
                const badge = document.getElementById('chatBadge');
                const count = data.count;

                if (count > 0) {
                    badge.innerText = count > 99 ? '99+' : count;
                    badge.style.display = 'inline-block'; // Hiện lên nếu có tin mới
                } else {
                    badge.style.display = 'none'; // Ẩn đi nếu đã đọc hết
                }
            })
            .catch(err => console.error('Lỗi cập nhật badge:', err));
    }

    // Chạy ngay khi mở trang
    updateChatBadge();

    // Tự động cập nhật mỗi 3 giây
    setInterval(updateChatBadge, 3000);
</script>