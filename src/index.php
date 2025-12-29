</body>
<script src="assets/minichat.js" defer></script>
<?php
session_start();
$error_message = '';
$success_message = '';
$is_logged_in = isset($_SESSION['user_id']);
$featured_temples = [];

// Database connection
try {
    $db_config = [
        'host' => 'localhost',
        'dbname' => 'web_cay',
        'username' => 'root',
        'password' => ''
    ];
    
    $conn = new PDO(
        "mysql:host={$db_config['host']};dbname={$db_config['dbname']};charset=utf8mb4",
        $db_config['username'],
        $db_config['password']
    );
    
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    error_log("Database connection error: " . $e->getMessage());
    die("Không thể kết nối đến database. Vui lòng thử lại sau.");
}

// Sửa lại câu truy vấn SQL để lấy bình luận và phản hồi
$stmt = $conn->prepare("
    SELECT 
        bl.id,
        bl.noi_dung,
        bl.ngay_tao,
        bl.ngay_cap_nhat,
        bl.phan_hoi,
        bl.id_nguoi_dung,
        bl.trang_thai,
        nd.ho_ten,
        nd.avatar as user_avatar,
        bl.id_binh_luan_goc
    FROM binh_luan bl
    LEFT JOIN nguoi_dung nd ON bl.id_nguoi_dung = nd.id
    WHERE bl.id_binh_luan_goc IS NULL
    ORDER BY bl.ngay_tao DESC
");
$stmt->execute();
$comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Lấy các phản hồi cho mỗi bình luận
foreach ($comments as &$comment) {
    $stmt = $conn->prepare("
        SELECT 
            bl.id,
            bl.noi_dung,
            bl.ngay_tao,
            bl.ngay_cap_nhat,
            bl.phan_hoi,
            bl.id_nguoi_dung,
            bl.trang_thai,
            nd.ho_ten,
            nd.avatar as user_avatar,
            bl.id_binh_luan_goc
        FROM binh_luan bl
        LEFT JOIN nguoi_dung nd ON bl.id_nguoi_dung = nd.id
        WHERE bl.id_binh_luan_goc = ?
        ORDER BY bl.ngay_tao ASC
    ");
    $stmt->execute([$comment['id']]);
    $comment['replies'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Xử lý chỉnh sửa bình luận
if (isset($_POST['action']) && $_POST['action'] === 'edit_comment') {
    try {
        if (!$is_logged_in) {
            throw new Exception("Vui lòng đăng nhập để chỉnh sửa bình luận");
        }

        $comment_id = (int)$_POST['comment_id'];
        $noi_dung = trim($_POST['noi_dung']);
        $user_id = $_SESSION['user_id'];

        if (empty($noi_dung)) {
            throw new Exception("Nội dung bình luận không được để trống");
        }

        // Kiểm tra quyền chỉnh sửa
        $stmt = $conn->prepare("
            SELECT id_nguoi_dung 
            FROM binh_luan 
            WHERE id = ? AND id_nguoi_dung = ?
        ");
        $stmt->execute([$comment_id, $user_id]);
        
        if (!$stmt->fetch()) {
            throw new Exception("Bạn không có quyền chỉnh sửa bình luận này");
        }

        // Cập nhật bình luận
        $stmt = $conn->prepare("
            UPDATE binh_luan 
            SET noi_dung = ?, 
                ngay_cap_nhat = NOW() 
            WHERE id = ? AND id_nguoi_dung = ?
        ");
        
        if ($stmt->execute([$noi_dung, $comment_id, $user_id])) {
            // Lấy thời gian cập nhật đã được format
            $stmt = $conn->prepare("
                SELECT DATE_FORMAT(ngay_cap_nhat, '%d/%m/%Y %H:%i') as ngay_cap_nhat
                FROM binh_luan WHERE id = ?
            ");
            $stmt->execute([$comment_id]);
            $update_time = $stmt->fetch();
            
            echo json_encode([
                'success' => true,
                'message' => 'Cập nhật bình luận thành công',
                'comment' => [
                    'id' => $comment_id,
                    'noi_dung' => nl2br(htmlspecialchars($noi_dung)),
                    'ngay_cap_nhat' => $update_time['ngay_cap_nhat']
                ]
            ]);
            exit;
        }
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
        exit;
    }
}

// Xử lý xóa bình luận
if (isset($_POST['action']) && $_POST['action'] === 'delete_comment') {
    try {
        if (!$is_logged_in) {
            throw new Exception("Vui lòng đăng nhập để xóa bình luận");
        }

        $comment_id = (int)$_POST['comment_id'];
        $user_id = $_SESSION['user_id'];

        // Kiểm tra quyền xóa
        $stmt = $conn->prepare("
            SELECT id_nguoi_dung 
            FROM binh_luan 
            WHERE id = ? AND id_nguoi_dung = ?
        ");
        $stmt->execute([$comment_id, $user_id]);
        
        if (!$stmt->fetch()) {
            throw new Exception("Bạn không có quyền xóa bình luận này");
        }

        // Xóa bình luận và các phản hồi liên quan
        $stmt = $conn->prepare("
            DELETE FROM binh_luan 
            WHERE id = ? OR id_binh_luan_goc = ?
        ");
        
        if ($stmt->execute([$comment_id, $comment_id])) {
            echo json_encode([
                'success' => true,
                'message' => 'Xóa bình luận thành công'
            ]);
            exit;
        }
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
        exit;
    }
}

// Xử lý gửi bình luận mới
if (isset($_POST['action']) && $_POST['action'] === 'comment') {
    try {
        if (!$is_logged_in) {
            throw new Exception("Vui lòng đăng nhập để bình luận");
        }

        $nguoi_dung_id = $_SESSION['user_id'];
        $noi_dung = trim($_POST['noi_dung']);
        
        if (empty($noi_dung)) {
            throw new Exception("Nội dung bình luận không được để trống");
        }

        $stmt = $conn->prepare("
            INSERT INTO binh_luan (
                id_nguoi_dung, 
                noi_dung, 
                ngay_tao,
                trang_thai
            ) VALUES (?, ?, NOW(), 1)
        ");
        
        if ($stmt->execute([$nguoi_dung_id, $noi_dung])) {
            $comment_id = $conn->lastInsertId();
            
            // Lấy thông tin đầy đủ của bình luận vừa tạo
            $stmt = $conn->prepare("
                SELECT 
                    bl.id,
                    bl.noi_dung,
                    bl.id_nguoi_dung,
                    DATE_FORMAT(bl.ngay_tao, '%d/%m/%Y %H:%i') as ngay_tao,
                    nd.ho_ten,
                    nd.avatar as user_avatar
                FROM binh_luan bl
                JOIN nguoi_dung nd ON bl.id_nguoi_dung = nd.id
                WHERE bl.id = ?
            ");
            $stmt->execute([$comment_id]);
            $comment_data = $stmt->fetch();

            echo json_encode([
                'success' => true,
                'comment' => [
                    'id' => $comment_id,
                    'ho_ten' => htmlspecialchars($comment_data['ho_ten']),
                    'noi_dung' => nl2br(htmlspecialchars($noi_dung)),
                    'ngay_tao' => $comment_data['ngay_tao'],
                    'trang_thai' => 1,
                    'nguoi_dung_id' => $nguoi_dung_id,
                    'user_avatar' => $comment_data['user_avatar'],
                    'can_edit' => true, // Thêm flag này để biết người dùng có thể sửa/xóa
                    'current_user_id' => $nguoi_dung_id // Thêm ID người dùng hiện tại
                ]
            ]);
            exit;
        }
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
        exit;
    }
}

// Xử lý phản hồi comment
if (isset($_POST['action']) && $_POST['action'] === 'reply_comment') {
    try {
        if (!$is_logged_in) {
            throw new Exception("Vui lòng đăng nhập để phản hồi");
        }

        $id_binh_luan_goc = isset($_POST['id_binh_luan_goc']) ? (int)$_POST['id_binh_luan_goc'] : 0;
        $noi_dung = isset($_POST['noi_dung']) ? trim($_POST['noi_dung']) : '';
        $nguoi_dung_id = $_SESSION['user_id'];
        
        if (empty($id_binh_luan_goc)) {
            throw new Exception("Không tìm thấy bình luận gốc");
        }

        if (empty($noi_dung)) {
            throw new Exception("Nội dung phản hồi không được để trống");
        }

        // Lấy thông tin bình luận gốc và người được trả lời
        $stmt = $conn->prepare("
            SELECT bl.noi_dung, nd.ho_ten 
            FROM binh_luan bl
            JOIN nguoi_dung nd ON bl.id_nguoi_dung = nd.id
            WHERE bl.id = ?
        ");
        $stmt->execute([$id_binh_luan_goc]);
        $comment_goc = $stmt->fetch();

        if (!$comment_goc) {
            throw new Exception("Không tìm thấy bình luận gốc");
        }

        // Thêm phản hồi mới
        $stmt = $conn->prepare("
            INSERT INTO binh_luan (
                id_nguoi_dung,
                noi_dung,
                ngay_tao,
                trang_thai,
                id_binh_luan_goc,
                noi_dung_tra_loi,
                ten_nguoi_duoc_tra_loi
            ) VALUES (?, ?, NOW(), 1, ?, ?, ?)
        ");

        $result = $stmt->execute([
            $nguoi_dung_id,
            $noi_dung,
            $id_binh_luan_goc,
            $comment_goc['noi_dung'],
            $comment_goc['ho_ten']
        ]);

        if ($result) {
            $reply_id = $conn->lastInsertId();

            // Lấy thông tin đầy đủ của phản hồi vừa tạo với format thời gian
            $stmt = $conn->prepare("
                SELECT 
                    bl.*,
                    DATE_FORMAT(bl.ngay_tao, '%d/%m/%Y %H:%i') as ngay_tao,
                    nd.ho_ten,
                    nd.avatar as user_avatar
                FROM binh_luan bl
                JOIN nguoi_dung nd ON bl.id_nguoi_dung = nd.id
                WHERE bl.id = ?
            ");
            $stmt->execute([$reply_id]);
            $reply = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($reply) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'reply' => [
                        'id' => $reply['id'],
                        'noi_dung' => $reply['noi_dung'],
                        'ngay_tao' => $reply['ngay_tao'],
                        'ho_ten' => $reply['ho_ten'],
                        'user_avatar' => $reply['user_avatar'],
                        'noi_dung_tra_loi' => $comment_goc['noi_dung'],
                        'ten_nguoi_duoc_tra_loi' => $comment_goc['ho_ten'],
                        'id_binh_luan_goc' => $id_binh_luan_goc,
                        'id_nguoi_dung' => $nguoi_dung_id
                    ]
                ]);
                exit;
            }
        }
        throw new Exception("Không thể tạo phản hồi");
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
        exit;
    }
}

// Xử lý sửa phản hồi
if (isset($_POST['action']) && $_POST['action'] === 'edit_reply') {
    try {
        if (!$is_logged_in) {
            throw new Exception("Vui lòng đăng nhập để chỉnh sửa phản hồi");
        }

        $reply_id = (int)$_POST['reply_id'];
        $noi_dung = trim($_POST['noi_dung']);
        $user_id = $_SESSION['user_id'];

        if (empty($noi_dung)) {
            throw new Exception("Nội dung phản hồi không được để trống");
        }

        // Kiểm tra quyền chỉnh sửa
        $stmt = $conn->prepare("
            SELECT id_nguoi_dung 
            FROM binh_luan 
            WHERE id = ? AND id_nguoi_dung = ?
        ");
        $stmt->execute([$reply_id, $user_id]);
        
        if (!$stmt->fetch()) {
            throw new Exception("Bạn không có quyền chỉnh sửa phản hồi này");
        }

        // Cập nhật phản hồi
        $stmt = $conn->prepare("
            UPDATE binh_luan 
            SET noi_dung = ?, 
                ngay_cap_nhat = NOW() 
            WHERE id = ? AND id_nguoi_dung = ?
        ");
        
        if ($stmt->execute([$noi_dung, $reply_id, $user_id])) {
            echo json_encode([
                'success' => true,
                'message' => 'Cập nhật phản hồi thành công',
                'reply' => [
                    'id' => $reply_id,
                    'noi_dung' => nl2br(htmlspecialchars($noi_dung)),
                    'ngay_cap_nhat' => date('d/m/Y H:i')
                ]
            ]);
            exit;
        }
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
        exit;
    }
}

// Xử lý xóa phản hồi
if (isset($_POST['action']) && $_POST['action'] === 'delete_reply') {
    try {
        if (!$is_logged_in) {
            throw new Exception("Vui lòng đăng nhập để xóa phản hồi");
        }

        $reply_id = (int)$_POST['reply_id'];
        $user_id = $_SESSION['user_id'];

        // Kiểm tra quyền xóa
        $stmt = $conn->prepare("
            SELECT id_nguoi_dung 
            FROM binh_luan 
            WHERE id = ? AND id_nguoi_dung = ?
        ");
        $stmt->execute([$reply_id, $user_id]);
        
        if (!$stmt->fetch()) {
            throw new Exception("Bạn không có quyền xóa phản hồi này");
        }

        // Xóa phản hồi
        $stmt = $conn->prepare("DELETE FROM binh_luan WHERE id = ?");
        if ($stmt->execute([$reply_id])) {
            echo json_encode([
                'success' => true,
                'message' => 'Xóa phản hồi thành công'
            ]);
            exit;
        }
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chùa Khmer Trà Vinh - Trang chủ</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- CSS sẽ được đặt ở đây -->
    <style>
/* === BIẾN VÀ RESET === */
:root {
    /* Màu sắc chính */
    --primary-color: #1a237e;
    --secondary-color: #0d47a1;
    --accent-color: #2962ff;
    
    /* Màu gradient */
    --gradient-primary: linear-gradient(135deg, #1a237e, #0d47a1);
    --gradient-accent: linear-gradient(135deg, #2962ff, #1565c0);
    --gradient-dark: linear-gradient(135deg, #1a237e, #000051);
    
    /* Màu trung tính */
    --text-primary: #2c3e50;
    --text-secondary: #546e7a;
    --text-light: #78909c;
    --background-light: #f5f7fa;
    --white: #ffffff;
    
    /* Bóng đổ */
    --shadow-sm: 0 2px 8px rgba(0,0,0,0.08);
    --shadow-md: 0 4px 12px rgba(0,0,0,0.12);
    --shadow-lg: 0 8px 24px rgba(0,0,0,0.15);
    --shadow-xl: 0 12px 32px rgba(0,0,0,0.2);
    
    /* Hiểu ứng chuyển động */
    --transition-normal: 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    
    /* Bo góc */
    --radius-sm: 8px;
    --radius-md: 12px;
    --radius-lg: 20px;
    --radius-xl: 28px;
    
    /* Typography */
    --font-base: 1rem;
    --font-md: 1.125rem;
    --font-lg: 1.25rem;
    --font-xl: 1.5rem;
    --font-2xl: 1.75rem;
    --font-3xl: 2rem;
    
    /* Font weights */
    --fw-regular: 400;
    --fw-medium: 500;
    --fw-semibold: 600;
    --fw-bold: 700;
    
    /* Khoảng cách */
    --spacing-xs: 0.5rem;
    --spacing-sm: 1rem;
    --spacing-md: 1.5rem;
    --spacing-lg: 2rem;
    --spacing-xl: 3rem;
}

/* Reset CSS */
*, *::before, *::after {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

/* === STYLE CƠ BẢN === */
body {
    font-family: 'Plus Jakarta Sans', sans-serif;
    line-height: 1.6;
    color: var(--text-primary);
    background-color: var(--background-light);
}

.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 var(--spacing-md);
}

/* === HEADER === */
.main-header {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    position: fixed;
    width: 100%;
    top: 0;
    z-index: 1000;
    padding: 1rem 0;
    box-shadow: 0 2px 20px rgba(0, 0, 0, 0.08);
    transition: all 0.3s ease;
}

.main-header.scrolled {
    padding: 0.8rem 0;
    background: rgba(255, 255, 255, 0.98);
}

.header-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 2rem;
}

/* Logo Styles */
.logo-link {
    display: flex;
    align-items: center;
    gap: 1rem;
    text-decoration: none;
    transition: transform 0.3s ease;
}

.logo-link:hover {
    transform: translateY(-2px);
}

.logo-img {
    height: 50px; /* Tăng kích thước logo lên một chút */
    width: auto;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    transition: transform 0.3s ease;
}

.logo h1 {
    font-size: 1.8rem; /* Tăng kích thước chữ */
    font-weight: 700;
    color: black; /* Chuyển màu chữ sang đen */
    margin: 0;
    white-space: nowrap;
}

/* Navigation Styles */
.main-nav {
    margin-left: auto;
}

.main-nav ul {
    display: flex;
    gap: 0.5rem;
    list-style: none;
    margin: 0;
    padding: 0;
}

.main-nav a {
    color: black !important; /* Đảm bảo chữ màu đen */
    text-decoration: none;
    font-size: 0.95rem;
    font-weight: 500;
    padding: 0.8rem 1.2rem;
    border-radius: 12px;
}

.main-nav a:hover {
    color: var(--accent-color);
    background: rgba(41, 98, 255, 0.08);
    transform: translateY(-2px);
}

/* Active State */
.main-nav a.active {
    color: var(--accent-color);
    background: rgba(41, 98, 255, 0.12);
    font-weight: 600;
}

/* Special Buttons */
.main-nav a[href="dangnhap.php"],
.main-nav a[href="dangky.php"] {
    padding: 0.8rem 1.5rem;
    border: 2px solid black;
    color: black !important;
    font-weight: 600;
    background: white !important;
    background-image: none !important;
    box-shadow: none !important;
    border-radius: 12px;
    outline: none !important;
    -webkit-tap-highlight-color: transparent;
    -webkit-touch-callout: none;
    -webkit-user-select: none;
    user-select: none;
}

/* Remove ALL possible outlines and focus effects */
.main-nav a[href="dangnhap.php"]:focus,
.main-nav a[href="dangky.php"]:focus,
.main-nav a[href="dangnhap.php"]:active,
.main-nav a[href="dangky.php"]:active,
.main-nav a[href="dangnhap.php"]:focus-visible,
.main-nav a[href="dangky.php"]:focus-visible,
.main-nav a[href="dangnhap.php"]:focus-within,
.main-nav a[href="dangky.php"]:focus-within {
    outline: none !important;
    box-shadow: none !important;
    border-color: black !important;
    background: white !important;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .main-nav a[href="dangnhap.php"],
    .main-nav a[href="dangky.php"] {
        padding: 0.6rem 1.2rem;
        font-size: 0.85rem;
    }
}

.main-nav a[href="dangky.php"] {
    background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
    color: white;
    box-shadow: 0 4px 12px rgba(41, 98, 255, 0.2);
}

.main-nav a[href="dangky.php"]:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(41, 98, 255, 0.3);
}

/* Responsive Design */
@media (max-width: 1024px) {
    .logo h1 {
        font-size: 1.6rem;
    }
    
    .main-nav a {
        padding: 0.7rem 1rem;
        font-size: 0.9rem;
    }
}

@media (max-width: 768px) {
    .header-content {
        flex-direction: column;
        padding: 1rem;
    }
    
    .main-nav ul {
        flex-wrap: wrap;
        justify-content: center;
        gap: 0.5rem;
    }
    
    .main-nav a {
        padding: 0.6rem 0.8rem;
        font-size: 0.85rem;
    }
    
    .logo-img {
        height: 45px;
    }
    
    .logo h1 {
        font-size: 1.4rem;
    }
}

/* Animation for Scroll */
@keyframes slideDown {
    from {
        transform: translateY(-100%);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

.main-header.scroll-visible {
    animation: slideDown 0.5s ease forwards;
}

/* === CHÙA NỔI BẬT === */
.featured-temples-section {
    padding: 4rem 0;
    background: linear-gradient(180deg, #ffffff, #f8f9fa);
    margin-top: 60px;
}

.section-title {
    text-align: center;
    color: #1a237e;
    font-size: 2.2rem;
    margin-bottom: 3rem;
    font-weight: 700;
    position: relative;
    padding-bottom: 15px;
}

.section-title::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 50%;
    transform: translateX(-50%);
    width: 60px;
    height: 3px;
    background: linear-gradient(90deg, #1a237e, #2962ff);
    border-radius: 1.5px;
}

.temples-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
    gap: 2rem;
    padding: 0 1.5rem;
    max-width: 1400px;
    margin: 0 auto;
}

.temple-card {
    background: #ffffff;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    position: relative;
    border: 1px solid rgba(0, 0, 0, 0.04);
}

.temple-image {
    position: relative;
    height: 240px;
}

.temple-image::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    height: 40%;
    background: linear-gradient(to top, rgba(0,0,0,0.4), transparent);
}

.temple-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.temple-info {
    padding: 1.5rem;
    position: relative;
}

.temple-info h3 {
    color: #1a237e;
    font-size: 1.4rem;
    margin-bottom: 0.8rem;
    font-weight: 600;
}

.temple-address {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: #666;
    font-size: 0.95rem;
    margin-bottom: 0.5rem; /* Giảm từ 1rem xuống 0.5rem */
}

.temple-address i {
    color: #2962ff;
}

.temple-description {
    color: #555;
    line-height: 1.6;
    margin-bottom: 1.5rem;
    font-size: 0.95rem;
}

.read-more-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 24px;
    background: linear-gradient(135deg, #1a237e, #2962ff);
    color: white;
    text-decoration: none;
    border-radius: 12px;
    font-weight: 500;
    font-size: 0.95rem;
    box-shadow: 0 4px 12px rgba(41,98,255,0.15);
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .featured-temples-section {
        padding: 3rem 0;
        margin-top: 40px;
    }

    .section-title {
        font-size: 1.8rem;
        margin-bottom: 2rem;
    }

    .temples-grid {
        grid-template-columns: 1fr;
        gap: 1.5rem;
        padding: 0 1rem;
    }

    .temple-image {
        height: 200px;
    }

    .temple-info {
        padding: 1.2rem;
    }

    .temple-info h3 {
        font-size: 1.2rem;
    }

    .read-more-btn {
        padding: 10px 20px;
        font-size: 0.9rem;
    }
}

@media (min-width: 769px) and (max-width: 1024px) {
    .temples-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 1.5rem;
    }
}

/* Comments section styles */
.comments-section {
    background: var(--white);
    padding: var(--spacing-xl) 0;
    box-shadow: var(--shadow-sm);
}

/* Main comments container */
.comments-container {
    max-width: 800px; /* Giới hạn chiều rộng tối đa */
    margin: 2rem auto; /* Căn giữa và tạo khoảng cách trên dưới */
    padding: 2rem;
    background: var(--white);
    border: 2px solid black; /* Viền đen cho khung chính */
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

/* Comments section title */
.comments-container .section-title {
    text-align: center;
    margin-bottom: 2rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid rgba(0,0,0,0.1);
    font-size: 1.5rem;
    font-weight: 600;
}

/* Comments list */
.comments-list {
    padding: 1rem 0;
}

/* Responsive design */
@media (max-width: 768px) {
    .comments-container {
        max-width: 95%;
        margin: 1rem auto;
        padding: 1rem;
    }
}

/* Base styles for forms */
.comment-form,
.reply-form {
    background: var(--white);
    padding: var(--spacing-lg);
    border-radius: var(--radius-lg);
    margin-bottom: var(--spacing-lg);
    box-shadow: var(--shadow-md);
    border: 1px solid rgba(0,0,0,0.05);
    transition: all 0.3s ease;
}

.comment-form:focus-within,
.reply-form:focus-within {
    box-shadow: var(--shadow-lg);
}

.comment-form textarea,
.reply-form textarea {
    width: 100%;
    padding: var(--spacing-md);
    border: 2px solid rgba(0,0,0,0.08);
    border-radius: var(--radius-md);
    margin-bottom: var(--spacing-md);
    min-height: 120px;
    resize: vertical;
    font-family: inherit;
    font-size: var(--font-base);
    transition: all 0.3s ease;
    background: var(--background-light);
}

.comment-form textarea:focus,
.reply-form textarea:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 4px rgba(26, 35, 126, 0.1);
    background: var(--white);
}

/* Base styles for items */
.comment-item,
.reply-item {
    background: var(--white);
    border-radius: var(--radius-lg);
    padding: var(--spacing-lg);
    margin-bottom: var(--spacing-lg);
    box-shadow: var(--shadow-md);
    border: 1px solid rgba(0,0,0,0.05);
    transition: all 0.3s ease;
    position: relative;
}

.comment-item:hover,
.reply-item:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-lg);
}

/* Header styles */
.comment-header,
.reply-header,
.admin-header {
    display: flex;
    align-items: center;
    gap: var(--spacing-md);
    margin-bottom: var(--spacing-md);
}

/* Avatar styles */
.user-avatar,
.reply-avatar,
.admin-avatar {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: var(--fw-bold);
    font-size: 16px;
    box-shadow: var(--shadow-md);
    border: 3px solid var(--white);
    transition: transform 0.3s ease;
}

.user-avatar:hover,
.reply-avatar:hover,
.admin-avatar:hover {
    transform: scale(1.1);
}

img.user-avatar {
    object-fit: cover;
}

.reply-avatar {
    background: var(--gradient-primary);
    color: var(--white);
}

.admin-avatar {
    background: var(--gradient-accent);
    color: var(--white);
}

/* Content styles */
.comment-content,
.reply-content,
.admin-content {
    color: var(--text-primary);
    line-height: 1.7;
    font-size: var(--font-md);
    margin: var(--spacing-md) 0;
    padding: var(--spacing-md);
    background: var(--background-light);
    border-radius: var(--radius-md);
}

/* Replies container */
.replies-container {
    margin-left: 60px;
    margin-top: var(--spacing-md);
    padding-left: var(--spacing-lg);
    border-left: 3px solid var(--background-light);
}

/* Replied content */
.replied-content {
    background: rgba(0,0,0,0.02);
    padding: var(--spacing-md);
    border-radius: var(--radius-md);
    margin-bottom: var(--spacing-md);
    border-left: 4px solid var(--primary-color);
    font-size: 0.95em;
}

.replied-content small {
    color: var(--text-secondary);
    font-weight: var(--fw-medium);
    display: block;
    margin-bottom: 6px;
}

.replied-content q {
    color: var(--text-secondary);
    font-style: italic;
    display: block;
    margin-top: 6px;
    line-height: 1.6;
}

/* Button styles */
.button-group {
    display: flex;
    gap: var(--spacing-sm);
    margin-top: var(--spacing-md);
    padding-top: var(--spacing-sm);
    border-top: 1px solid rgba(0,0,0,0.05);
}

.button-group button {
    padding: var(--spacing-sm) var(--spacing-md);
    border-radius: var(--radius-md);
    font-weight: var(--fw-medium);
    font-size: var(--font-base);
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
}

.button-group button[type="submit"] {
    background: var(--gradient-primary);
    color: var(--white);
    border: none;
}

.button-group button[type="button"] {
    background: var(--white);
    color: var(--text-primary);
    border: 2px solid rgba(0,0,0,0.1);
}

.button-group button:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
}

/* Admin response */
.admin-response {
    margin-top: 1rem;
    margin-left: 2rem;
    margin-right: 2rem;
    background: var(--white); /* Đổi nền thành màu trắng */
    border-radius: 12px;
    padding: 1rem;
    border-left: 3px solid var(--accent-color);
    border-right: 3px solid var(--accent-color);
    max-width: 80%;
    margin-left: auto;
    margin-right: auto;
    box-shadow: 0 2px 8px rgba(41, 98, 255, 0.05);
}

/* Admin header */
.admin-header {
    display: flex;
    align-items: center;
    gap: 0.8rem; /* Giảm khoảng cách */
    margin-bottom: 0.5rem; /* Giảm margin bottom */
}

/* Admin avatar */
.admin-avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: var(--gradient-accent);
    color: var(--white);
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: var(--fw-bold);
    font-size: 0.8rem;
    border: 2px solid black; /* Thêm viền đen */
}

/* Admin info */
.admin-info {
    flex-grow: 1;
}

.admin-info strong {
    font-size: 0.9rem; /* Giảm kích thước chữ */
    color: var(--text-primary);
    display: block;
    margin-bottom: 2px;
}

/* Admin content */
.admin-content {
    color: var(--text-dark); /* Đổi chữ thành màu đen */
    line-height: 1.5;
    font-size: 1rem; /* Tăng kích thước chữ */
    padding: 0.8rem;
    background: rgba(255, 255, 255, 0.5);
    border-radius: 8px;
    margin-top: 0.5rem;
}

/* Responsive design */
@media (max-width: 768px) {
    .admin-response {
        margin-left: 1rem;
        margin-right: 1rem;
        max-width: 90%;
    }
}

/* Meta styles */
.comment-meta,
.reply-meta,
.admin-info {
    flex-grow: 1;
}

.comment-author,
.reply-author,
.admin-info strong {
    font-weight: var(--fw-semibold);
    font-size: var(--font-md);
    color: var(--text-primary);
    margin-bottom: 4px;
    display: block;
}

.comment-date,
.reply-date,
.admin-date {
    font-size: var(--font-base);
    color: var(--text-light);
}

/* Edit and Delete buttons */
.edit-btn,
.delete-btn {
    padding: var(--spacing-xs) var(--spacing-sm);
    border-radius: var(--radius-sm);
    font-size: 0.9em;
    transition: all 0.3s ease;
}

.edit-btn {
    color: var(--accent-color);
    border: 1px solid var(--accent-color);
    background: transparent;
}

.edit-btn:hover {
    background: var(--accent-color);
    color: var(--white);
}

.delete-btn {
    color: #dc3545;
    border: 1px solid #dc3545;
    background: transparent;
}

.delete-btn:hover {
    background: #dc3545;
    color: var(--white);
}
/* Form bình luận chính */
.comment-form {
    background: var(--white);
    padding: 1.5rem;
    border-radius: 16px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.08);
    margin-bottom: 2rem;
    border: 1px solid rgba(0,0,0,0.05);
    transition: all 0.3s ease;
    display: flex;
    flex-direction: column;
    align-items: center; /* Căn giữa các phần tử con */
}

.comment-form:focus-within {
    box-shadow: 0 4px 20px rgba(0,0,0,0.12);
    transform: translateY(-2px);
}

/* Textarea bình luận */
.comment-form textarea {
    width: 100%;
    min-height: 120px;
    padding: 1rem;
    border: 2px solid rgba(0,0,0,0.08);
    border-radius: 12px;
    font-size: 1rem;
    line-height: 1.6;
    resize: vertical;
    transition: all 0.3s ease;
    background: var(--background-light);
    margin-bottom: 1rem;
}

.comment-form textarea:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 4px rgba(26,35,126,0.1);
    background: var(--white);
}

/* Nút gửi bình luận */
.comment-form .btn-primary {
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
    color: white;
    padding: 0.8rem 1.5rem;
    border: none;
    border-radius: 10px;
    font-weight: 600;
    font-size: 0.95rem;
    cursor: pointer;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    box-shadow: 0 4px 12px rgba(26,35,126,0.2);
    margin: 0 auto; /* Căn giữa nút */
}

.comment-form .btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(26,35,126,0.3);
}

.comment-form .btn-primary:active {
    transform: translateY(0);
}

/* Style cho thông báo đăng nhập */
.login-prompt {
    text-align: center;
    padding: 1.5rem;
    background: var(--white);
    border-radius: 16px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.08);
    border: 1px solid rgba(0,0,0,0.05);
}

.login-prompt a {
    color: var(--primary-color);
    text-decoration: none; /* Bỏ gạch chân */
    font-weight: 600;
    transition: all 0.3s ease;
}

.login-prompt a:hover {
    color: var(--accent-color);
    text-decoration: none; /* Thêm dòng này để đảm bảo không có gạch chân khi hover */
}

/* Khung hiển thị nội dung phản hồi */
.replied-content {
    background: rgba(0,0,0,0.02);
    padding: 1rem 1.2rem;
    border-radius: 12px;
    margin: 1rem 0;
    border-left: 4px solid var(--primary-color);
    position: relative;
}

.replied-content::before {
    content: '"';
    position: absolute;
    left: -2px;
    top: -5px;
    font-size: 2rem;
    color: var(--primary-color);
    opacity: 0.2;
}

/* Nút tương tác (Sửa, Xóa, Phản hồi) */
.comment-actions {
    display: flex;
    gap: 12px;
    margin-top: 1rem;
    justify-content: center; /* Thêm dòng này */
    padding: 0.8rem 0;
    border-top: 1px solid rgba(0,0,0,0.05);
}

.edit-btn,
.delete-btn,
.reply-btn {
    padding: 0.6rem 1rem;
    border-radius: 8px;
    font-size: 0.9rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    min-width: 100px; /* Thêm độ rộng tối thiểu cho nút */
    justify-content: center; /* Căn giữa nội dung trong nút */
}

/* Nút Sửa */
.edit-btn {
    color: var(--accent-color);
    background: rgba(41,98,255,0.1);
}

.edit-btn:hover {
    background: var(--accent-color);
    color: white;
    transform: translateY(-2px);
}

/* Nút Xóa */
.delete-btn {
    color: #dc3545;
    background: rgba(220,53,69,0.1);
}

.delete-btn:hover {
    background: #dc3545;
    color: white;
    transform: translateY(-2px);
}

/* Nút Phản hồi */
.reply-btn {
    color: var(--text-primary);
    background: var(--background-light);
    border: 1px solid rgba(0,0,0,0.1);
}

.reply-btn:hover {
    background: var(--text-primary);
    color: white;
    transform: translateY(-2px);
}

/* Icon trong nút */
.comment-actions i {
    font-size: 0.9rem;
}

/* Form phản hồi */
.reply-form-container {
    margin-top: 1rem;
    padding: 1rem;
    background: var(--background-light);
    border-radius: 12px;
    border-left: 4px solid var(--primary-color);
}

.reply-form textarea {
    width: 100%;
    min-height: 100px;
    padding: 1rem;
    border: 2px solid rgba(0,0,0,0.08);
    border-radius: 10px;
    font-size: 0.95rem;
    resize: vertical;
    transition: all 0.3s ease;
    background: white;
}

.reply-form textarea:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 4px rgba(26,35,126,0.1);
}

/* Button group trong form phản hồi */
.button-group {
    display: flex;
    gap: 10px;
    margin-top: 1rem;
}

.button-group button {
    padding: 0.7rem 1.2rem;
    border-radius: 8px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
}

.button-group button[type="submit"] {
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
    color: white;
    border: none;
    box-shadow: 0 4px 12px rgba(26,35,126,0.2);
}

.button-group button[type="button"] {
    background: white;
    color: var(--text-primary);
    border: 1px solid rgba(0,0,0,0.1);
}

.button-group button:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

/* Hiệu ứng loading cho nút */
.btn-loading {
    position: relative;
    pointer-events: none;
    opacity: 0.8;
}

.btn-loading::after {
    content: '';
    position: absolute;
    width: 16px;
    height: 16px;
    top: 50%;
    left: 50%;
    margin: -8px 0 0 -8px;
    border: 2px solid rgba(255,255,255,0.3);
    border-top-color: white;
    border-radius: 50%;
    animation: button-loading-spinner 0.8s linear infinite;
}

@keyframes button-loading-spinner {
    to {
        transform: rotate(360deg);
    }
}
/* Khung sửa bình lu���n */
.edit-form {
    background: var(--white);
    padding: 1.2rem;
    border-radius: 12px;
    box-shadow: 0 2px 15px rgba(0,0,0,0.08);
    margin: 1rem 0;
    border: 1px solid rgba(0,0,0,0.06);
    transition: all 0.3s ease;
}

.edit-form:focus-within {
    box-shadow: 0 4px 20px rgba(0,0,0,0.12);
    transform: translateY(-2px);
}

/* Textarea sửa bình luận */
.edit-form textarea {
    width: 100%;
    min-height: 100px;
    padding: 1rem;
    border: 2px solid rgba(0,0,0,0.08);
    border-radius: 10px;
    font-size: 0.95rem;
    line-height: 1.6;
    resize: vertical;
    transition: all 0.3s ease;
    background: var(--background-light);
    margin-bottom: 1rem;
}

.edit-form textarea:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 4px rgba(26,35,126,0.1);
    background: var(--white);
}

/* Button group trong form sửa */
.edit-form .button-group {
    display: flex;
    gap: 12px;
    margin-top: 1rem;
    padding-top: 0.8rem;
    border-top: 1px solid rgba(0,0,0,0.05);
}

/* Nút Lưu */
.edit-form button[type="submit"] {
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
    color: white;
    padding: 0.8rem 1.5rem;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    font-size: 0.9rem;
    cursor: pointer;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    box-shadow: 0 4px 12px rgba(26,35,126,0.2);
}

.edit-form button[type="submit"]:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(26,35,126,0.3);
}

.edit-form button[type="submit"]:active {
    transform: translateY(0);
}

/* Nút Hủy */
.edit-form button[type="button"] {
    background: white;
    color: var(--text-primary);
    padding: 0.8rem 1.5rem;
    border: 1px solid rgba(0,0,0,0.1);
    border-radius: 8px;
    font-weight: 500;
    font-size: 0.9rem;
    cursor: pointer;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.edit-form button[type="button"]:hover {
    background: var(--background-light);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

/* Icon trong n��t */
.edit-form button i {
    font-size: 0.9rem;
}

/* Hiệu ứng loading cho nút */
.edit-form button.loading {
    position: relative;
    pointer-events: none;
    opacity: 0.8;
}

.edit-form button.loading::after {
    content: '';
    position: absolute;
    width: 16px;
    height: 16px;
    top: 50%;
    left: 50%;
    margin: -8px 0 0 -8px;
    border: 2px solid rgba(255,255,255,0.3);
    border-top-color: white;
    border-radius: 50%;
    animation: button-loading-spinner 0.8s linear infinite;
}

/* Animation cho loading */
@keyframes button-loading-spinner {
    to {
        transform: rotate(360deg);
    }
}

/* Responsive */
@media (max-width: 576px) {
    .edit-form {
        padding: 1rem;
    }

    .edit-form .button-group {
        flex-direction: column;
    }

    .edit-form button {
        width: 100%;
        justify-content: center;
    }
}

/* Style cho actions của ph���n hồi */
.reply-actions {
    display: flex;
    gap: 8px;
    margin-top: 0.8rem;
    justify-content: center; /* Thêm dòng này */
    padding: 0.6rem 0;
    border-top: 1px solid rgba(0,0,0,0.05);
}

.reply-actions button {
    padding: 0.5rem 0.8rem;
    border-radius: 6px;
    font-size: 0.85rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 4px;
}

/* Form phản hồi cho phản hồi */
#replyToReplyForm {
    margin-top: 1rem;
    padding: 1rem;
    background: var(--background-light);
    border-radius: 10px;
    border-left: 3px solid var(--primary-color);
}

/* Hiệu ứng hover cho các nút trong phản hồi */
.reply-actions .edit-btn:hover,
.reply-actions .delete-btn:hover,
.reply-actions .reply-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.success-notification,
.error-notification {
    position: fixed;
    top: 20px;
    right: 20px;
    padding: 15px 25px;
    border-radius: 8px;
    color: white;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 10px;
    z-index: 1000;
    animation: slideIn 0.3s ease;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.success-notification {
    background-color: #28a745;
}

.error-notification {
    background-color: #dc3545;
}

.success-notification i,
.error-notification i {
    font-size: 1.2em;
}

@keyframes slideIn {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

/* === FOOTER STYLES === */
.main-footer {
    background: var(--background-dark);
    color: var(--white);
    padding: 5rem 0 2rem;
    position: relative;
    overflow: hidden;
}

/* Gradient line at top */
.main-footer::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: var(--gradient-primary);
}

/* Footer Content Grid */
.footer-content {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 3rem;
    margin-bottom: 3rem;
}

/* Footer Sections */
.footer-section {
    padding: 0 1rem;
}

.footer-section h3 {
    color: var(--white);
    font-size: 1.25rem;
    font-weight: 600;
    margin-bottom: 1.5rem;
    position: relative;
    padding-bottom: 0.75rem;
}

.footer-section h3::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    width: 40px;
    height: 3px;
    background: var(--gradient-accent);
    border-radius: 2px;
}

.footer-section p {
    color: var(--text-light);
    line-height: 1.8;
    margin-bottom: 1.5rem;
    font-size: 0.95rem;
}

/* Social Links */
.social-links {
    display: flex;
    gap: 1rem;
    margin-top: 1.5rem;
}

.social-link {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.1);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--white);
    transition: var(--transition-normal);
}

.social-link:hover {
    background: var(--gradient-primary);
    transform: translateY(-3px);
}

/* Footer Links */
.footer-links {
    list-style: none;
    padding: 0;
}

.footer-links li {
    margin-bottom: 0.75rem;
}

.footer-links a {
    color: var(--text-light);
    text-decoration: none;
    transition: var(--transition-normal);
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.95rem;
}

.footer-links a:hover {
    color: var(--white);
    transform: translateX(5px);
}

.footer-links i {
    font-size: 0.8rem;
    color: var(--accent-color);
}

/* Contact Info */
.contact-info {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.contact-item {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
}

.contact-item i {
    color: var(--accent-color);
    font-size: 1.1rem;
    margin-top: 0.2rem;
}

.contact-item p {
    margin: 0;
    color: var(--text-light);
    font-size: 0.95rem;
}

/* Newsletter Form */
.newsletter-form .form-group {
    position: relative;
    margin-top: 1rem;
}

.newsletter-form input {
    width: 100%;
    padding: 0.75rem 1rem;
    padding-right: 3rem;
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: var(--radius-md);
    color: var(--white);
    font-size: 0.95rem;
    transition: var(--transition-normal);
}

.newsletter-form input:focus {
    outline: none;
    background: rgba(255, 255, 255, 0.15);
    border-color: var(--accent-color);
}

.newsletter-form input::placeholder {
    color: var(--text-light);
}

.newsletter-form button {
    position: absolute;
    right: 0.5rem;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: var(--accent-color);
    cursor: pointer;
    padding: 0.5rem;
    transition: var(--transition-normal);
}

.newsletter-form button:hover {
    color: var(--white);
    transform: translateY(-50%) scale(1.1);
}

/* Footer Bottom */
.footer-bottom {
    padding-top: 2rem;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
    margin-top: 2rem;
}

.footer-bottom-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
}

.footer-bottom p {
    color: var(--text-light);
    font-size: 0.9rem;
    margin: 0;
}

.footer-bottom-links {
    display: flex;
    gap: 1.5rem;
}

.footer-bottom-links a {
    color: var(--text-light);
    text-decoration: none;
    font-size: 0.9rem;
    transition: var(--transition-normal);
}

.footer-bottom-links a:hover {
    color: var(--white);
}

/* Responsive Design */
@media (max-width: 768px) {
    .footer-content {
        grid-template-columns: 1fr;
        gap: 2rem;
    }

    .footer-section {
        text-align: center;
        padding: 0;
    }

    .footer-section h3::after {
        left: 50%;
        transform: translateX(-50%);
    }

    .social-links {
        justify-content: center;
    }

    .footer-links a {
        justify-content: center;
    }

    .contact-item {
        justify-content: center;
        text-align: center;
        flex-direction: column;
        align-items: center;
    }

    .footer-bottom-content {
        flex-direction: column;
        text-align: center;
    }

    .footer-bottom-links {
        justify-content: center;
    }
}

.temple-details {
    margin: 0.5rem 0; /* Giảm từ 0.8rem xuống 0.5rem */
    font-size: 0.95rem;
    color: #666;
}

.temple-details p {
    margin: 0.3rem 0; /* Giảm từ 0.4rem xuống 0.3rem */
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.temple-details i {
    color: var(--accent-color);
    font-size: 1rem;
    width: 20px;
    text-align: center;
}

.temple-details p:hover {
    color: var(--text-primary);
}

.temple-details p:hover i {
    transform: scale(1.1);
}

/* Style chung cho tất cả các icon */
i {
    color: var(--accent-color);
    font-size: 1rem;
    width: 20px;
    text-align: center;
    transition: transform 0.3s ease;
}

/* Header icons */
.main-nav i {
    margin-right: 0.5rem;
}

/* Temple card icons */
.temple-info i {
    margin-right: 0.5rem;
}

/* Comment section icons */
.comment-actions i,
.reply-actions i,
.button-group i {
    margin-right: 0.5rem;
}

/* Footer icons */
.footer-section i {
    color: var(--accent-color);
}

.social-link i {
    color: var(--white);
    width: auto;
}

/* Button icons */
.btn i,
button i {
    margin-right: 0.5rem;
    color: inherit;
}

/* Specific overrides */
.btn-primary i,
.button-group button[type="submit"] i,
.social-link i,
.read-more-btn i {
    color: white;
}

/* Loading spinner icon */
.btn-loading i {
    animation: button-loading-spinner 0.8s linear infinite;
}

/* Hover effects */
a:hover i,
button:hover i,
.social-link:hover i {
    transform: scale(1.1);
}

/* Icon alignment in flex containers */
.comment-actions,
.reply-actions,
.button-group,
.footer-links a,
.contact-item,
.social-links {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

/* Newsletter form icon */
.newsletter-form button i {
    margin: 0;
}

/* Admin response icon */
.admin-avatar i {
    color: var(--white);
    width: auto;
}

/* Notification icons */
.success-notification i,
.error-notification i {
    color: white;
    font-size: 1.2em;
    margin-right: 0.5rem;
}

/* Temple details icons */
.temple-details i {
    width: 20px;
    text-align: center;
}

/* Contact info icons in footer */
.contact-info i {
    margin-top: 0.2rem;
}

/* Footer links icons */
.footer-links i {
    font-size: 0.8rem;
}

/* Edit and delete button icons */
.edit-btn i,
.delete-btn i {
    color: inherit;
}

/* Reply button icons */
.reply-btn i {
    color: inherit;
}

/* Loading animation */
@keyframes button-loading-spinner {
    to {
        transform: rotate(360deg);
    }
}
</style>
</head>
<body>
    <header class="main-header">
        <div class="container">
            <div class="header-content">
                <div class="logo">
                    <a href="index.php" class="logo-link">
                        <img src="image/Anh1.jpg" alt="Logo Chùa Khmer" class="logo-img">
                        <h1>Chùa Khmer Trà Vinh</h1>
                    </a>
                </div>
                <nav class="main-nav">
                <nav class="main-nav">
                <ul>
                    <li><a href="dschua.php">Danh sách chùa</a></li>
                    <li><a href="sukien.php">Sự kiện</a></li>
                    <li><a href="lienhe.php">Liên hệ</a></li>
                    <li><a href="taikhoan.php">Tài khoản</a></li>
                    <li><a href="dangnhap.php">Đăng nhập</a></li>
                    <li><a href="dangky.php">Đăng ký</a></li>
                </ul>
            </nav>
            </div>
        </div>
    </header>

            <section class="featured-temples-section">
                <h2 class="section-title">Chùa nổi bật</h2>
                <div class="temples-grid">
                    <!-- Chùa 1 -->
                    <div class="temple-card">
                        <div class="temple-image">
                            <img src="image/chua-ang.jpg" alt="Chùa Âng">
                        </div>
                        <div class="temple-info">
                            <h3>Chùa Âng</h3>
                            <p class="temple-address">
                                <i class="fas fa-map-marker-alt"></i>
                                Địa chỉ: Phường 8, TP. Trà Vinh
                            </p>
                            <div class="temple-details">
                                <p><i class="fas fa-user"></i> Trụ trì: Thượng tọa Thạch Sok Xane</p>
                                <p><i class="fas fa-phone"></i> Điện thoại: 0294 3851 123</p>
                                <p><i class="fas fa-envelope"></i> Email: chuaang@gmail.com</p>
                            </div>
                            <p class="temple-description">
                                Chùa Âng là ngôi chùa Khmer có kiến trúc độc đáo và là một trong những ngôi chùa cổ nhất tại Trà Vinh...
                            </p>
                            <a href="chitietchua.php?id=1" class="read-more-btn">Xem thêm</a>
                        </div>
                    </div>

                    <!-- Chùa 2 -->
                    <div class="temple-card">
                        <div class="temple-image">
                            <img src="image/chua-hang.jpg" alt="Chùa Hang">
                        </div>
                        <div class="temple-info">
                            <h3>Chùa Hang</h3>
                            <p class="temple-address">
                                <i class="fas fa-map-marker-alt"></i>
                               Địa chỉ: Phường 7, TP. Trà Vinh
                            </p>
                            <div class="temple-details">
                                <p><i class="fas fa-user"></i> Trụ trì: Hòa thượng Thạch Huỳnh</p>
                                <p><i class="fas fa-phone"></i> Điện thoại: 0294 3855 456</p>
                                <p><i class="fas fa-envelope"></i> Email: chuahang@gmail.com</p>
                            </div>
                            <p class="temple-description">
                                Chùa Hang là một trong những ngôi chùa Khmer cổ kính và nổi tiếng tại Trà Vinh với kiến trúc độc đáo...
                            </p>
                            <a href="chitietchua.php?id=2" class="read-more-btn">Xem thêm</a>
                        </div>
                    </div>

                    <!-- Chùa 3 -->
                    <div class="temple-card">
                        <div class="temple-image">
                            <img src="image/chua-kompong.jpg" alt="Chùa Kom Pong">
                        </div>
                        <div class="temple-info">
                            <h3>Chùa Kom Pong</h3>
                            <p class="temple-address">
                                <i class="fas fa-map-marker-alt"></i>
                               Địa chỉ: Huyện Châu Thành, Trà Vinh
                            </p>
                            <div class="temple-details">
                                <p><i class="fas fa-user"></i> Trụ trì: Đại đức Thạch Thanh</p>
                                <p><i class="fas fa-phone"></i> Điện thoại: 0294 3853 789</p>
                                <p><i class="fas fa-envelope"></i> Email: chuakompong@gmail.com</p>
                            </div>
                            <p class="temple-description">
                                Chùa Kom Pong là ngôi chùa mang đậm nét văn hóa Khmer với những họa tiết trang trí tinh xảo...
                            </p>
                            <a href="chitietchua.php?id=3" class="read-more-btn">Xem thêm</a>
                        </div>
                    </div>
                </div>
            </section>

    <section class="comments-section">
    <div class="container">
        <div class="comments-container">
            <h2 class="section-title">Bình luận</h2>
            
            <!-- Form bình luận -->
            <?php if ($is_logged_in): ?>
                <form id="commentForm" class="comment-form">
                    <textarea name="noi_dung" placeholder="Nhập bình luận của bạn..." required></textarea>
                    <button type="submit" class="btn btn-primary">Gửi bình luận</button>
                </form>
            <?php else: ?>
                <p class="login-prompt">Vui lòng <a href="dangnhap.php">Đăng nhập</a> để được bình luận.</p>
            <?php endif; ?>

<!-- Danh sách bình luận -->
<div class="comments-list">
    <?php foreach ($comments as &$comment): ?>
        <?php if ($comment['trang_thai'] == 1): ?>  <!-- Chỉ hiển thị khi trang_thai = 1 -->
            <div class="comment-item" data-id="<?php echo $comment['id']; ?>">
                <div class="comment-header">
                    <?php if ($comment['user_avatar']): ?>
                        <img src="<?php echo htmlspecialchars($comment['user_avatar']); ?>" 
                             alt="<?php echo htmlspecialchars($comment['ho_ten']); ?>" 
                             class="user-avatar">
                    <?php else: ?>
                        <div class="comment-avatar">
                            <?php echo strtoupper(substr($comment['ho_ten'], 0, 2)); ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="comment-meta">
                        <div class="comment-author"><?php echo htmlspecialchars($comment['ho_ten']); ?></div>
                        <div class="comment-date">
                            <?php 
                                $date = new DateTime($comment['ngay_tao']);
                                echo $date->format('d/m/Y H:i');
                            ?>
                        </div>
                    </div>
                </div>

                <div class="comment-content" id="comment-content-<?php echo $comment['id']; ?>">
                    <?php echo nl2br(htmlspecialchars($comment['noi_dung'])); ?>
                </div>

                <?php if (!empty($comment['phan_hoi'])): ?>
                    <div class="admin-response">
                        <div class="admin-header">
                            <div class="admin-avatar">QTV</div>
                            <div class="admin-info">
                                <strong>Quản trị viên</strong>
                                <div class="admin-date">
                                    <?php echo isset($comment['ngay_phan_hoi']) ? $comment['ngay_phan_hoi'] : ''; ?>
                                </div>
                            </div>
                        </div>
                        <div class="admin-content">
                            <?php echo nl2br(htmlspecialchars($comment['phan_hoi'])); ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Các nút chức năng cho bình luận -->
                <div class="comment-actions">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <?php if ($_SESSION['user_id'] == $comment['id_nguoi_dung']): ?>
                            <button class="edit-btn" onclick="showEditForm(<?php echo $comment['id']; ?>, '<?php echo htmlspecialchars($comment['noi_dung']); ?>')">
                                <i class="fas fa-edit"></i> Sửa
                            </button>
                            <button class="delete-btn" onclick="deleteComment(<?php echo $comment['id']; ?>)">
                                <i class="fas fa-trash"></i> Xóa
                            </button>
                        <?php endif; ?>
                        <?php if (isset($_SESSION['vai_tro']) && $_SESSION['vai_tro'] == 'admin'): ?>
                            <button class="toggle-status-btn" onclick="toggleCommentStatus(<?php echo $comment['id']; ?>, <?php echo $comment['trang_thai'] == 0 ? 'true' : 'false'; ?>)">
                                <i class="fas fa-<?php echo $comment['trang_thai'] == 1 ? 'eye-slash' : 'eye'; ?>"></i>
                                <?php echo $comment['trang_thai'] == 1 ? 'Ẩn' : 'Hiển thị'; ?>
                            </button>
                        <?php endif; ?>
                        <button class="reply-btn" onclick="showReplyForm(this, <?php echo $comment['id']; ?>)">
                            <i class="fas fa-reply"></i> Phản hồi
                        </button>
                    <?php endif; ?>
                </div>

                <!-- Form phản hồi sẽ được chèn vào đây -->
                <div class="reply-form-wrapper"></div>

                <!-- Danh sách phản hồi -->
                <div class="replies-container" id="replies-<?php echo $comment['id']; ?>">
                    <?php foreach ($comment['replies'] as $reply): ?>
                        <?php if ($reply['trang_thai'] == 1): ?>  <!-- Chỉ hiển thị khi trang_thai = 1 -->
                            <div class="reply-item" data-id="<?php echo $reply['id']; ?>">
                                <div class="reply-header">
                                    <?php if ($reply['user_avatar']): ?>
                                        <img src="<?php echo htmlspecialchars($reply['user_avatar']); ?>" 
                                             alt="<?php echo htmlspecialchars($reply['ho_ten']); ?>" 
                                             class="user-avatar">
                                    <?php else: ?>
                                        <div class="reply-avatar">
                                            <?php echo strtoupper(substr($reply['ho_ten'], 0, 2)); ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="reply-meta">
                                        <div class="reply-author"><?php echo htmlspecialchars($reply['ho_ten']); ?></div>
                                        <div class="reply-date">
                                            <?php 
                                                $date = new DateTime($reply['ngay_tao']);
                                                echo $date->format('d/m/Y H:i');
                                            ?>
                                        </div>
                                    </div>
                                </div>

                                <div class="reply-content" id="reply-content-<?php echo $reply['id']; ?>">
                                    <?php echo nl2br(htmlspecialchars($reply['noi_dung'])); ?>
                                </div>

                                <!-- Hiển thị nội dung phản hồi của quản trị viên nếu có -->
                                <?php if (!empty($reply['phan_hoi'])): ?>
                                    <div class="admin-response">
                                        <div class="admin-header">
                                            <div class="admin-avatar">QTV</div>
                                            <div class="admin-info">
                                                <strong>Quản trị viên</strong>
                                                <!-- Đã xóa dòng hiển thị ngay_phan_hoi -->
                                            </div>
                                        </div>
                                        <div class="admin-content">
                                            <?php echo nl2br(htmlspecialchars($reply['phan_hoi'])); ?>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <!-- Các nút chức năng cho phản hồi -->
                                <div class="reply-actions">
                                    <?php if (isset($_SESSION['user_id'])): ?>
                                        <?php if ($_SESSION['user_id'] == $reply['id_nguoi_dung']): ?>
                                            <button class="edit-btn" onclick="showReplyEditForm(<?php echo $reply['id']; ?>, '<?php echo htmlspecialchars($reply['noi_dung']); ?>')">
                                                <i class="fas fa-edit"></i> Sửa
                                            </button>
                                            <button class="delete-btn" onclick="deleteReply(<?php echo $reply['id']; ?>)">
                                                <i class="fas fa-trash"></i> Xóa
                                            </button>
                                        <?php endif; ?>
                                        <button class="reply-btn" onclick="showReplyToReplyForm(<?php echo $comment['id']; ?>, <?php echo $reply['id']; ?>, '<?php echo htmlspecialchars($reply['ho_ten']); ?>')">
                                            <i class="fas fa-reply"></i> Phản hồi
                                        </button>
                                    <?php endif; ?>
                                </div>
                                <!-- Form phản hồi sẽ được chèn vào đây -->
                                <div class="reply-form-wrapper"></div>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    <?php endforeach; ?>
</div>
        </div>
    </div>
</section>


    <footer class="main-footer">
        <div class="container">
            <div class="footer-content">
                <!-- Giới thiệu -->
                <div class="footer-section">
                    <h3>Về Chùa Khmer</h3>
                    <p>Khám phá vẻ đẹp và giá trị văn hóa độc đáo của các ngôi chùa Khmer tại Trà Vinh. Chúng tôi cam kết bảo tồn và phát huy di sản văn hóa quý báu này.</p>
                    <div class="social-links">
                        <a href="#" class="social-link"><i class="fab fa-facebook"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-youtube"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-instagram"></i></a>
                    </div>
                </div>

                <!-- Liên kết nhanh -->
                <div class="footer-section">
                    <h3>Liên Kết</h3>
                    <ul class="footer-links">
                        <li><a href="dschua.php"><i class="fas fa-chevron-right"></i>Danh sách chùa</a></li>
                        <li><a href="sukien.php"><i class="fas fa-chevron-right"></i>Sự kiện</a></li>
                        <li><a href="gioithieu.php"><i class="fas fa-chevron-right"></i>Giới thiệu</a></li>
                        <li><a href="lienhe.php"><i class="fas fa-chevron-right"></i>Liên hệ</a></li>
                    </ul>
                </div>

                <!-- Thông tin liên hệ -->
                <div class="footer-section">
                    <h3>Liên Hệ</h3>
                    <div class="contact-info">
                        <div class="contact-item">
                            <i class="fas fa-map-marker-alt"></i>
                            <p>126 Nguyễn Thiện Thành, P5, Trà Vinh</p>
                        </div>
                        <div class="contact-item">
                            <i class="fas fa-phone-alt"></i>
                            <p>+84 123 456 789</p>
                        </div>
                        <div class="contact-item">
                            <i class="fas fa-envelope"></i>
                            <p>info@chuakhmer.vn</p>
                        </div>
                    </div>
                </div>

                <!-- Newsletter -->
                <div class="footer-section">
                    <h3>Đăng Ký Nhận Tin</h3>
                    <p>Nhận thông tin mới nhất về các sự kiện và hoạt động văn hóa.</p>
                    <form class="newsletter-form">
                        <div class="form-group">
                            <input type="email" placeholder="Email của bạn" required>
                            <button type="submit"><i class="fas fa-paper-plane"></i></button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Footer Bottom -->
            <div class="footer-bottom">
                <div class="footer-bottom-content">
                    <p>&copy; <?php echo date('Y'); ?> Chùa Khmer Trà Vinh. All rights reserved.</p>
                    <div class="footer-bottom-links">
                        <a href="#">Điều khoản sử dụng</a>
                        <a href="#">Chính sách bảo mật</a>
                    </div>
                </div>
            </div>
        </div>
    </footer>
    <script>
// Thêm biến isLoggedIn ở đầu file
const isLoggedIn = <?php echo $is_logged_in ? 'true' : 'false'; ?>;

// Biến kiểm tra trạng thái loading
let isLoading = false;

// Cập nhật hàm createCommentHTML để thêm các nút tương tác
function createCommentHTML(comment) {
    const currentUserId = <?php echo isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'null'; ?>;
    const isCommentOwner = currentUserId === parseInt(comment.nguoi_dung_id);
    
    const avatarHtml = comment.user_avatar 
        ? `<img src="${escapeHtml(comment.user_avatar)}" alt="${escapeHtml(comment.ho_ten)}" class="user-avatar">` 
        : `<div class="comment-avatar">${escapeHtml(comment.ho_ten.substring(0, 2).toUpperCase())}</div>`;

    const actionButtons = isCommentOwner ? `
        <div class="comment-actions">
            <button class="edit-btn" onclick="showEditForm(${comment.id}, '${escapeHtml(comment.noi_dung)}')">
                <i class="fas fa-edit"></i> Sửa
            </button>
            <button class="delete-btn" onclick="deleteComment(${comment.id})">
                <i class="fas fa-trash"></i> Xóa
            </button>
            <button class="reply-btn" onclick="showReplyForm(${comment.id})">
                <i class="fas fa-reply"></i> Phản hồi
            </button>
        </div>
    ` : `
        <div class="comment-actions">
            <button class="reply-btn" onclick="showReplyForm(${comment.id})">
                <i class="fas fa-reply"></i> Phản hồi
            </button>
        </div>
    `;

    // Phần hiển thị phản hồi của admin
    const adminReplyHtml = comment.phan_hoi ? `
        <div class="admin-reply">
            <div class="admin-reply-header">
                <div class="admin-avatar">QTV</div>
                <div class="admin-meta">
                    <div class="admin-name">Quản trị viên</div>
                </div>
            </div>
            <div class="admin-reply-content">
                ${nl2br(escapeHtml(comment.phan_hoi))}
            </div>
        </div>
    ` : '';

    return `
        <div class="comment-item" data-id="${comment.id}">
            <div class="comment-header">
                ${avatarHtml}
                <div class="comment-meta">
                    <div class="comment-author">${escapeHtml(comment.ho_ten)}</div>
                    <div class="comment-date">
                        ${comment.ngay_cap_nhat ? 
                            ` ${comment.ngay_cap_nhat}` : 
                            comment.ngay_tao}
                    </div>
                </div>
            </div>
            <div class="comment-content" id="comment-content-${comment.id}">
                ${nl2br(escapeHtml(comment.noi_dung))}
            </div>
            ${actionButtons}
            ${adminReplyHtml}
            <div class="reply-form-wrapper"></div>
            <div class="replies-container" id="replies-${comment.id}"></div>
        </div>
    `;
}

// Sửa lại hàm createReplyHTML
function createReplyHTML(reply) {
    const currentUserId = <?php echo isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'null'; ?>;
    const isReplyOwner = currentUserId === parseInt(reply.id_nguoi_dung);
    
    const avatarHtml = reply.user_avatar 
        ? `<img src="${escapeHtml(reply.user_avatar)}" alt="${escapeHtml(reply.ho_ten)}" class="user-avatar">` 
        : `<div class="reply-avatar">${escapeHtml(reply.ho_ten.substring(0, 2).toUpperCase())}</div>`;

    // Tạo các nút tương tác dựa trên quyền sở hữu
    const actionButtons = isReplyOwner ? `
        <div class="reply-actions">
            <button class="edit-btn" onclick="showReplyEditForm(${reply.id}, '${escapeHtml(reply.noi_dung)}')">
                <i class="fas fa-edit"></i> Sửa
            </button>
            <button class="delete-btn" onclick="deleteReply(${reply.id})">
                <i class="fas fa-trash"></i> Xóa
            </button>
            <button class="reply-btn" onclick="showReplyToReplyForm(${reply.id_binh_luan_goc}, ${reply.id}, '${escapeHtml(reply.ho_ten)}')">
                <i class="fas fa-reply"></i> Phản hồi
            </button>
        </div>
    ` : `
        <div class="reply-actions">
            <button class="reply-btn" onclick="showReplyToReplyForm(${reply.id_binh_luan_goc}, ${reply.id}, '${escapeHtml(reply.ho_ten)}')">
                <i class="fas fa-reply"></i> Phản hồi
            </button>
        </div>
    `;

    return `
        <div class="reply-item" data-id="${reply.id}">
            <div class="reply-header">
                ${avatarHtml}
                <div class="reply-meta">
                    <div class="reply-author">${escapeHtml(reply.ho_ten)}</div>
                    <div class="reply-date">
                        ${reply.ngay_cap_nhat ? 
                            `Đã chỉnh sửa ${reply.ngay_cap_nhat}` : 
                            reply.ngay_tao}
                    </div>
                </div>
            </div>
            <div class="reply-content" id="reply-content-${reply.id}">
                ${nl2br(escapeHtml(reply.noi_dung))}
            </div>
            ${actionButtons}
        </div>
    `;
}

// Hàm hiển thị form phn hồi
function showReplyForm(commentId, replyId = null) {
    // Ẩn tất cả các form phản hồi đang mở
    $('.reply-form-container').hide();

    // Xác định phần tử mà sau đó sẽ chèn form phản hồi
    let targetElement;
    if (replyId) {
        // Nếu đang phản hồi cho một reply, chèn form sau reply đó
        targetElement = $(`.reply-item[data-id="${replyId}"]`);
    } else {
        // Nếu đang phản hồi cho comment chính, chèn form sau nút phản hồi
        targetElement = $(`.comment-item[data-id="${commentId}"] > .comment-actions`);
    }

    // Tạo hoặc di chuyển form phản hồi
    let replyForm = $(`#replyForm-${commentId}`);
    if (replyForm.length === 0) {
        // Nếu form chưa tồn tại, tạo mới
        replyForm = $(`
            <div class="reply-form-container" id="replyForm-${commentId}">
                <form class="reply-form" onsubmit="submitReply(event, ${commentId}, ${replyId})">
                    <textarea name="reply_content" placeholder="Nhập phản hồi..." required></textarea>
                    <div class="button-group">
                        <button type="submit">Gửi</button>
                        <button type="button" onclick="hideReplyForm(${commentId})">Hủy</button>
                    </div>
                </form>
            </div>
        `);
    }

    // Chèn form ngay sau phần tử mục tiêu
    targetElement.after(replyForm);

    // Hiển thị form
    replyForm.show();

    // Focus vào textarea
    replyForm.find('textarea').focus();
}

// Hàm ẩn form phản hồi
function hideReplyForm(commentId) {
    $(`#replyForm-${commentId}`).hide();
}

function submitReply(event, commentId) {
    event.preventDefault();
    
    if (!isLoggedIn) {
        window.location.href = 'dangnhap.php';
        return;
    }

    const form = event.target;
    const replyContent = form.querySelector('textarea[name="reply_content"]').value.trim();
    
    if (!replyContent) {
        alert('Vui lòng nhập nội dung phản hồi');
        return;
    }

    const submitButton = form.querySelector('button[type="submit"]');
    submitButton.disabled = true;
    submitButton.textContent = 'Đang gửi...';

    $.ajax({
        url: window.location.href,
        type: 'POST',
        dataType: 'json', // Thêm dòng này
        data: {
            action: 'reply_comment',
            id_binh_luan_goc: commentId,
            noi_dung: replyContent
        },
        success: function(response) {
            if (response.success) {
                // Tìm container chứa replies
                const repliesContainer = $(`#replies-${commentId}`);
                
                // Tạo HTML cho reply mới
                const replyHtml = createReplyHTML(response.reply);
                
                // Thêm reply mới vào container
                repliesContainer.append(replyHtml);
                
                // Reset form và ẩn
                form.reset();
                hideReplyForm(commentId);
            } else {
                alert(response.message || 'Có lỗi xảy ra khi gửi phản hồi');
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', {xhr, status, error});
            let message = 'Có lỗi xảy ra khi gửi phản hồi';
            try {
                const response = JSON.parse(xhr.responseText);
                message = response.message || message;
            } catch (e) {}
            alert(message);
        },
        complete: function() {
            submitButton.disabled = false;
            submitButton.textContent = 'Gửi';
        }
    });
}

function createReplyHTML(reply) {
    const currentUserId = <?php echo isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'null'; ?>;
    const isReplyOwner = currentUserId === parseInt(reply.id_nguoi_dung);
    
    const avatarHtml = reply.user_avatar 
        ? `<img src="${escapeHtml(reply.user_avatar)}" alt="${escapeHtml(reply.ho_ten)}" class="user-avatar">` 
        : `<div class="reply-avatar">${escapeHtml(reply.ho_ten.substring(0, 2).toUpperCase())}</div>`;

    // Tạo các nút tương tác dựa trên quyền sở hữu
    const actionButtons = isReplyOwner ? `
        <div class="reply-actions">
            <button class="edit-btn" onclick="showReplyEditForm(${reply.id}, '${escapeHtml(reply.noi_dung)}')">
                <i class="fas fa-edit"></i> Sửa
            </button>
            <button class="delete-btn" onclick="deleteReply(${reply.id})">
                <i class="fas fa-trash"></i> Xóa
            </button>
            <button class="reply-btn" onclick="showReplyToReplyForm(${reply.id_binh_luan_goc}, ${reply.id}, '${escapeHtml(reply.ho_ten)}')">
                <i class="fas fa-reply"></i> Phản hồi
            </button>
        </div>
    ` : `
        <div class="reply-actions">
            <button class="reply-btn" onclick="showReplyToReplyForm(${reply.id_binh_luan_goc}, ${reply.id}, '${escapeHtml(reply.ho_ten)}')">
                <i class="fas fa-reply"></i> Phản hồi
            </button>
        </div>
    `;

    return `
        <div class="reply-item" data-id="${reply.id}">
            <div class="reply-header">
                ${avatarHtml}
                <div class="reply-meta">
                    <div class="reply-author">${escapeHtml(reply.ho_ten)}</div>
                    <div class="reply-date">
                        ${reply.ngay_cap_nhat ? 
                            `Đã chỉnh sửa ${reply.ngay_cap_nhat}` : 
                            reply.ngay_tao}
                    </div>
                </div>
            </div>
            <div class="reply-content" id="reply-content-${reply.id}">
                ${nl2br(escapeHtml(reply.noi_dung))}
            </div>
            ${actionButtons}
        </div>
    `;
}
// Sửa lại phần xử lý form trong $(document).ready
$(document).ready(function() {
    $('#commentForm').on('submit', function(e) {
        e.preventDefault();
        
        if (!isLoggedIn) {
            window.location.href = 'dangnhap.php';
            return;
        }

        const commentContent = $(this).find('textarea[name="noi_dung"]').val().trim();
        
        if (!commentContent) {
            alert('Vui lòng nh�p nội dung bình luận');
            return;
        }

        const submitButton = $(this).find('button[type="submit"]');
        submitButton.prop('disabled', true);
        submitButton.html('<i class="fas fa-spinner fa-spin"></i> Đang gửi...');

        $.ajax({
            url: window.location.href,
            type: 'POST',
            data: {
                action: 'comment',
                noi_dung: commentContent
            },
            success: function(response) {
                try {
                    const data = typeof response === 'string' ? JSON.parse(response) : response;
                    
                    if (data.success) {
                        // Tạo HTML cho bình luận mới với các nút tương tác
                        const commentHtml = `
                            <div class="comment-item" data-id="${data.comment.id}">
                                <div class="comment-header">
                                    ${data.comment.user_avatar ? 
                                        `<img src="${escapeHtml(data.comment.user_avatar)}" alt="${escapeHtml(data.comment.ho_ten)}" class="user-avatar">` :
                                        `<div class="comment-avatar">${escapeHtml(data.comment.ho_ten.substring(0, 2).toUpperCase())}</div>`
                                    }
                                    <div class="comment-meta">
                                        <div class="comment-author">${escapeHtml(data.comment.ho_ten)}</div>
                                        <div class="comment-date">${data.comment.ngay_tao}</div>
                                    </div>
                                </div>
                                <div class="comment-content" id="comment-content-${data.comment.id}">
                                    ${data.comment.noi_dung}
                                </div>
                                <div class="comment-actions">
                                    <button class="edit-btn" onclick="showEditForm(${data.comment.id}, '${escapeHtml(commentContent)}')">
                                        <i class="fas fa-edit"></i> Sửa
                                    </button>
                                    <button class="delete-btn" onclick="deleteComment(${data.comment.id})">
                                        <i class="fas fa-trash"></i> Xóa
                                    </button>
                                    <button class="reply-btn" onclick="showReplyForm(${data.comment.id})">
                                        <i class="fas fa-reply"></i> Phản hồi
                                    </button>
                                </div>
                                <div class="reply-form-wrapper"></div>
                                <div class="replies-container" id="replies-${data.comment.id}"></div>
                            </div>
                        `;
                        
                        // Thêm bình luận mới vào đầu danh sách
                        $('.comments-list').prepend(commentHtml);
                        // Reset form
                        $('#commentForm')[0].reset();
                        // Thông báo thành công
                        showNotification('success', 'Đã thêm bình luận thành công');
                    } else {
                        showNotification('error', data.message || 'Có lỗi xảy ra khi gửi bình luận');
                    }
                } catch (e) {
                    console.error('Error:', e);
                    showNotification('error', 'Có lỗi xảy ra khi xử lý phản hồi từ server');
                }
            },
            error: function(xhr) {
                let errorMessage = 'Có lỗi xảy ra khi gửi bình luận';
                try {
                    const response = JSON.parse(xhr.responseText);
                    errorMessage = response.message || errorMessage;
                } catch(e) {}
                showNotification('error', errorMessage);
            },
            complete: function() {
                submitButton.prop('disabled', false);
                submitButton.html('<i class="fas fa-paper-plane"></i> Gửi bình luận');
            }
        });
    });

    // Thêm sự kiện cho nút phản hồi
    $(document).on('click', '.reply-btn', function() {
        const commentId = $(this).closest('.comment-item').data('id');
        showReplyForm(commentId);
    });
});

function createCommentHTML(comment) {
    const avatarHtml = comment.user_avatar 
        ? `<img src="${escapeHtml(comment.user_avatar)}" alt="${escapeHtml(comment.ho_ten)}" class="user-avatar">` 
        : `<div class="comment-avatar">${escapeHtml(comment.ho_ten.substring(0, 2).toUpperCase())}</div>`;

    return `
        <div class="comment-item" data-id="${comment.id}">
            <div class="comment-header">
                ${avatarHtml}
                <div class="comment-meta">
                    <div class="comment-author">${escapeHtml(comment.ho_ten)}</div>
                    <div class="comment-date">
                        ${comment.ngay_cap_nhat ? 
                            `Đã ch ${comment.ngay_cap_nhat}` : 
                            comment.ngay_tao}
                    </div>
                </div>
            </div>
            <div class="comment-content">
                ${nl2br(escapeHtml(comment.noi_dung))}
            </div>
            <div class="comment-actions">
                <button type="button" class="reply-btn" onclick="showReplyForm(${comment.id})">
                    <i class="fas fa-reply"></i> Phản hồi
                </button>
            </div>
            <div class="reply-form-wrapper"></div>
            <div class="replies-container" id="replies-${comment.id}"></div>
        </div>
    `;
}

// Hàm hiển thị form chỉnh sửa
function showEditForm(commentId, content) {
    const commentContent = $(`#comment-content-${commentId}`);
    const currentContent = content.replace(/<br\s*\/?>/g, '\n');
    
    commentContent.html(`
        <form onsubmit="submitEdit(event, ${commentId})" class="edit-form">
            <textarea name="edit_content" required>${currentContent}</textarea>
            <div class="button-group">
                <button type="submit">Lưu</button>
                <button type="button" onclick="cancelEdit(${commentId}, '${content}')">Hủy</button>
            </div>
        </form>
    `);
}

// Hàm hủy chỉnh sửa
function cancelEdit(commentId, originalContent) {
    $(`#comment-content-${commentId}`).html(originalContent);
}

// Hm submit chỉnh sửa bình luận
function submitEdit(event, commentId) {
    event.preventDefault();
    const form = event.target;
    const content = form.edit_content.value.trim();

    if (!content) {
        alert('Vui lòng nhập nội dung bình luận');
        return;
    }

    const submitButton = form.querySelector('button[type="submit"]');
    submitButton.disabled = true;
    submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang lưu...';

    $.ajax({
        url: window.location.href,
        type: 'POST',
        data: {
            action: 'edit_comment',
            comment_id: commentId,
            noi_dung: content
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                const commentContent = $(`#comment-content-${commentId}`);
                commentContent.html(response.comment.noi_dung);
                // Xóa phần cập nhật thời gian
                // const dateElement = commentContent.closest('.comment-item').find('.comment-date');
                // dateElement.text(`Đã chỉnh sửa ${response.comment.ngay_cap_nhat}`);
            } else {
                alert(response.message || 'Có lỗi xảy ra khi cập nhật bình luận');
            }
        },
        error: function(xhr) {
            let errorMessage = 'Có lỗi xảy ra khi cập nhật bình luận';
            try {
                const response = JSON.parse(xhr.responseText);
                errorMessage = response.message || errorMessage;
            } catch(e) {}
            alert(errorMessage);
        },
        complete: function() {
            submitButton.disabled = false;
            submitButton.innerHTML = '<i class="fas fa-save"></i> Lưu';
        }
    });
}

// Hàm xóa bình luận
function deleteComment(commentId) {
    if (!confirm('Bạn có chắc chắn muốn xóa bình luận này?')) {
        return;
    }

    $.ajax({
        url: window.location.href,
        type: 'POST',
        data: {
            action: 'delete_comment',
            comment_id: commentId
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                // Xóa phần tử HTML của bình luận
                $(`[data-id="${commentId}"]`).fadeOut(300, function() {
                    $(this).remove();
                });
            } else {
                alert(response.message || 'Có lỗi xảy ra khi xóa bình luận');
            }
        },
        error: function(xhr) {
            let errorMessage = 'Có lỗi xảy ra khi xóa bình luận';
            try {
                const response = JSON.parse(xhr.responseText);
                errorMessage = response.message || errorMessage;
            } catch(e) {}
            alert(errorMessage);
        }
    });
}

// Hiển thị form sửa phản hồi
function showReplyEditForm(replyId, content) {
    const replyContent = $(`#reply-content-${replyId}`);
    const currentContent = content.replace(/<br\s*\/?>/g, '\n');
    
    replyContent.html(`
        <form onsubmit="submitReplyEdit(event, ${replyId})" class="edit-form">
            <textarea name="edit_content" required placeholder="Chỉnh sửa phản hồi của bạn...">${currentContent}</textarea>
            <div class="button-group">
                <button type="submit">
                    <i class="fas fa-save"></i> Lưu thay đổi
                </button>
                <button type="button" onclick="cancelReplyEdit(${replyId}, '${content}')">
                    <i class="fas fa-times"></i> Hủy
                </button>
            </div>
        </form>
    `);

    replyContent.find('textarea').focus();
}

// Hủy sửa phản hồi
function cancelReplyEdit(replyId, originalContent) {
    $(`#reply-content-${replyId}`).html(originalContent);
}

// Submit sửa phản hồi
function submitReplyEdit(event, replyId) {
    event.preventDefault();
    const form = event.target;
    const content = form.edit_content.value.trim();

    if (!content) {
        alert('Vui lòng nhập nội dung phản hồi');
        return;
    }

    const submitButton = form.querySelector('button[type="submit"]');
    submitButton.disabled = true;
    submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang lưu...';

    $.ajax({
        url: window.location.href,
        type: 'POST',
        data: {
            action: 'edit_reply',
            reply_id: replyId,
            noi_dung: content
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                // Tìm đến phần tử cha chứa form và ẩn form đi
                const replyItem = form.closest('.reply-item');
                const editForm = replyItem.querySelector('.edit-form');
                if (editForm) {
                    editForm.style.display = 'none';
                }
                
                // Hiển thị lại khung nội dung và cập nhật nội dung mới
                const contentContainer = replyItem.querySelector(`#reply-content-${replyId}`);
                if (contentContainer) {
                    contentContainer.style.display = 'block';
                    contentContainer.innerHTML = nl2br(escapeHtml(content));
                }
                
                showNotification('success', 'Đã cập nhật phản hồi thành công');
            } else {
                showNotification('error', response.message || 'Có lỗi xảy ra khi cập nhật phản hồi');
            }
        },
        error: function(xhr) {
            let errorMessage = 'Có lỗi xảy ra khi cập nhật phản hồi';
            try {
                const response = JSON.parse(xhr.responseText);
                errorMessage = response.message || errorMessage;
            } catch(e) {}
            showNotification('error', errorMessage);
        },
        complete: function() {
            submitButton.disabled = false;
            submitButton.innerHTML = '<i class="fas fa-save"></i> Lưu thay đổi';
        }
    });
}

// Xóa phản hồi
function deleteReply(replyId) {
    if (!confirm('Bạn có chắc chắn muốn xóa phản hồi này?')) {
        return;
    }

    $.ajax({
        url: window.location.href,
        type: 'POST',
        data: {
            action: 'delete_reply',
            reply_id: replyId
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                $(`[data-id="${replyId}"]`).fadeOut(300, function() {
                    $(this).remove();
                });
            } else {
                alert(response.message || 'Có lỗi xảy ra khi xóa phản hồi');
            }
        },
        error: function(xhr) {
            let errorMessage = 'Có lỗi xảy ra khi xóa phản hồi';
            try {
                const response = JSON.parse(xhr.responseText);
                errorMessage = response.message || errorMessage;
            } catch(e) {}
            alert(errorMessage);
        }
    });
}

// Hiển thị form phản hồi cho phản hồi
function showReplyToReplyForm(commentId, replyId, replyAuthor) {
    // Ẩn tất cả các form phản hồi đang mở
    $('.reply-form-container').hide();
    
    // Tạo form phản hồi mới
    const replyFormHtml = `
        <div class="reply-form-container" id="replyToReplyForm-${replyId}">
            <form class="reply-form" onsubmit="submitReplyToReply(event, ${commentId}, ${replyId}, '${replyAuthor}')">
                <div class="replied-content">
                    <small>Đang trả lời ${replyAuthor}</small>
                </div>
                <textarea name="reply_content" placeholder="Nhập phản hồi của bạn..." required></textarea>
                <div class="button-group">
                    <button type="submit">
                        <i class="fas fa-paper-plane"></i> Gửi
                    </button>
                    <button type="button" onclick="hideReplyToReplyForm(${replyId})">
                        <i class="fas fa-times"></i> Hủy
                    </button>
                </div>
            </form>
        </div>
    `;
    
    $(`[data-id="${replyId}"]`).append(replyFormHtml);
}

// Ẩn form phản hồi cho phản hồi
function hideReplyToReplyForm(replyId) {
    $(`#replyToReplyForm-${replyId}`).remove();
}

// Hàm tiện ích
function escapeHtml(unsafe) {
    return unsafe
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

function nl2br(str) {
    return str.replace(/\n/g, '<br>');
}

// Thêm hàm hiển thị thông báo
function showNotification(type, message) {
    const notificationClass = type === 'success' ? 'success-notification' : 'error-notification';
    const notification = $(`
        <div class="${notificationClass}">
            <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i>
            ${message}
        </div>
    `);
    
    $('body').append(notification);
    
    setTimeout(() => {
        notification.fadeOut(300, function() {
            $(this).remove();
        });
    }, 3000);
}

</script>

<link rel="stylesheet" href="assets/notifications.css">
<script src="assets/notifications.js" defer></script>
</body>
</html>
<?php
session_start();

if (!isset($_SESSION['user'])) {
    echo "<a href='login.php'>Đăng nhập</a>";
    exit;
}

echo "Xin chào, " . $_SESSION['user']['name'] . "<br>";
echo "<img src='" . $_SESSION['user']['picture'] . "' width='80'><br>";
echo "<a href='logout.php'>Đăng xuất</a>";
