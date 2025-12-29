<?php
header('Content-Type: text/html; charset=utf-8'); // Đảm bảo hiển thị tiếng Việt
// baiviet.php

// BẮT ĐẦU PHẦN DEBUG: Bật hiển thị lỗi PHP
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'connect.php'; // Đảm bảo $conn là kết nối PDO

// KHỞI TẠO BIẾN $USER_ID ĐỂ TRÁNH LỖI UNDEFINED VARIABLE
$user_id = $_SESSION['user_id'] ?? 0;
// Lấy danh sách chủ đề để lọc
$chu_de_list = [];
$res = $conn->query('SELECT id, ten_chude FROM chude ORDER BY ten_chude ASC');
while ($row = $res->fetch(PDO::FETCH_ASSOC)) $chu_de_list[] = $row;

// KIỂM TRA XEM PHẢI LÀ REQUEST ĐĂNG BÀI KHÔNG
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_article'])) {
    // 1. Lấy dữ liệu từ form

    $nguoi_dung_id = $_SESSION['user_id'] ?? null;
    $chu_de_id = intval($_POST['chu_de_id'] ?? 0);
    $tieu_de = trim($_POST['tieu_de'] ?? '');
    $noi_dung = trim($_POST['noi_dung'] ?? '');
    $trang_thai = 'pending'; // Mặc định là chờ duyệt

    // DEBUG 1: Kiểm tra các biến đầu vào
    if (!$nguoi_dung_id || empty($tieu_de) || empty($noi_dung) || $chu_de_id == 0) {
        die("LỖI DEBUG 1: Thiếu dữ liệu (ID User: $nguoi_dung_id, Tiêu đề: $tieu_de, Chủ đề: $chu_de_id)");
    }

    // 2. Xử lý INSERT bài viết vào bảng bai_viet
    try {
        $stmt_post = $conn->prepare("INSERT INTO bai_viet (nguoi_dung_id, chu_de_id, tieu_de, noi_dung, trang_thai) VALUES (?, ?, ?, ?, ?)");
        // DEBUG 2: Kiểm tra lỗi SQL Prepare
        if (!$stmt_post) {
            die("LỖI DEBUG 2: Lỗi Prepare Bài Viết: " . json_encode($conn->errorInfo()));
        }
        $stmt_post->execute([$nguoi_dung_id, $chu_de_id, $tieu_de, $noi_dung, $trang_thai]);
        // 3. Lấy ID của bài viết vừa tạo
        $post_id = $conn->lastInsertId(); 

        // DEBUG A: Kiểm tra xem đã lấy được ID bài viết chưa
        if ($post_id == 0) {
            // Nếu post_id = 0, tức là lệnh INSERT bài viết thất bại (Cần kiểm tra lại code INSERT bai_viet)
            // Tạm thời, chúng ta sẽ bỏ qua và xem lỗi thông báo
        }

        // -----------------------------------------------------
        // BẮT ĐẦU CODE GHI THÔNG BÁO ADMIN (CẦN CHÈN LỆNH DEBUG)
        // -----------------------------------------------------

        $tieu_de = trim($_POST['tieu_de'] ?? 'Tiêu đề trống'); // Lấy lại tiêu đề
        $message = "Bài viết mới '{$tieu_de}' đang chờ duyệt.";
        $link = "admin/qlbaiviet.php?filter=pending"; 
        $type = 'new_post';

        // DEBUG B: Kiểm tra kết nối
        if (!$conn) {
            die("LỖI DEBUG B: Biến $conn (kết nối PDO) bị mất hoặc chưa được khởi tạo.");
        }

        // Thao tác INSERT vào bảng admin_notifications (PDO)
        $stmt_noti = $conn->prepare("INSERT INTO admin_notifications (type, message, link) VALUES (?, ?, ?)");

        // DEBUG C: Kiểm tra lỗi Prepare Statement
        if (!$stmt_noti) {
            // Nếu lỗi Prepare Statement, in ra chi tiết lỗi SQL
            var_dump($conn->errorInfo()); 
            die("LỖI DEBUG C: Lỗi Prepare Statement cho admin_notifications.");
        }

        // Thực thi lệnh INSERT
        $success = $stmt_noti->execute([$type, $message, $link]);

        // DEBUG D: Kiểm tra kết quả thực thi lệnh INSERT
        if (!$success) {
             // Lỗi SQL Execute, in ra chi tiết lỗi
             var_dump($stmt_noti->errorInfo());
             die("LỖI DEBUG D: Lỗi Execute INSERT vào admin_notifications.");
        }

        // DEBUG E: Nếu đến đây, thông báo đã ghi thành công!
        // die("THÀNH CÔNG DEBUG E: Đã ghi thông báo vào DB."); 
        // -----------------------------------------------------
        // KẾT THÚC CODE GHI THÔNG BÁO ADMIN
        // -----------------------------------------------------
        // Chuyển hướng hoặc thông báo thành công
        header("Location: baiviet.php?success=post_sent");
        exit();
    } catch (PDOException $e) {
        // DEBUG 6: Lỗi Database tổng quát
        die("LỖI DEBUG 6: Lỗi PDO: " . $e->getMessage());
    }
}
// --- [MỚI] Xử lý Yêu thích (Like/Unlike) bài viết ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_like'])) {
    header('Content-Type: application/json'); // Đảm bảo trả về JSON chuẩn
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập để yêu thích!']);
        exit();
    }

    $post_id = (int)($_POST['post_id'] ?? 0);
    $user_id = $_SESSION['user_id'];

    // Kiểm tra xem đã like chưa (PDO)
    $check = $conn->prepare("SELECT id FROM bai_viet_yeuthich WHERE nguoi_dung_id = ? AND bai_viet_id = ?");
    $check->execute([$user_id, $post_id]);
    $liked = $check->fetch(PDO::FETCH_ASSOC);

    if ($liked) {
        // Đã like -> Xóa (Unlike)
        $stmt = $conn->prepare("DELETE FROM bai_viet_yeuthich WHERE nguoi_dung_id = ? AND bai_viet_id = ?");
        $stmt->execute([$user_id, $post_id]);
        $action = 'unliked';
    } else {
        // Chưa like -> Thêm (Like)
        $stmt = $conn->prepare("INSERT INTO bai_viet_yeuthich (nguoi_dung_id, bai_viet_id) VALUES (?, ?)");
        $stmt->execute([$user_id, $post_id]);
        $action = 'liked';
    }

    // Đếm lại tổng số like của bài viết này
    $countStmt = $conn->prepare("SELECT COUNT(*) as total FROM bai_viet_yeuthich WHERE bai_viet_id = ?");
    $countStmt->execute([$post_id]);
    $count = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

    echo json_encode(['success' => true, 'action' => $action, 'count' => $count]);
    exit();
}
// --- [HẾT PHẦN XỬ LÝ LIKE] ---
// Xử lý bình luận
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['post_comment'])) {
    // --- [XỬ LÝ UPDATE, DELETE, RESUBMIT BÀI VIẾT] ---
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_post'])) {
        header('Content-Type: application/json');
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập!']);
            exit();
        }
        $post_id = (int)($_POST['post_id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $user_id = $_SESSION['user_id'];
        if ($post_id && $title && $content) {
            // Chỉ cho phép sửa bài của mình và chưa duyệt hoặc bị từ chối
            $stmt = $conn->prepare("UPDATE bai_viet SET tieu_de = ?, noi_dung = ?, trang_thai = 'pending' WHERE id = ? AND nguoi_dung_id = ? AND trang_thai IN ('pending','rejected')");
            $stmt->execute([$title, $content, $post_id, $user_id]);
            if ($stmt->rowCount() > 0) {
                echo json_encode(['success' => true, 'message' => 'Đã lưu bài viết!']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Không thể sửa bài viết!']);
            }
            exit();
        }
        echo json_encode(['success' => false, 'message' => 'Thiếu dữ liệu!']);
        exit();
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_post'])) {
        header('Content-Type: application/json');
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập!']);
            exit();
        }
        $post_id = (int)($_POST['post_id'] ?? 0);
        $user_id = $_SESSION['user_id'];
        if ($post_id) {
            // Xóa bình luận trước
            $conn->prepare("DELETE FROM binh_luan_bai_viet WHERE bai_viet_id = ?")->execute([$post_id]);
            // Xóa bài viết của mình (chỉ khi chưa duyệt hoặc bị từ chối hoặc là chủ bài viết đã duyệt)
            $stmt = $conn->prepare("DELETE FROM bai_viet WHERE id = ? AND nguoi_dung_id = ?");
            $stmt->execute([$post_id, $user_id]);
            if ($stmt->rowCount() > 0) {
                echo json_encode(['success' => true, 'message' => 'Đã xóa bài viết!']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Không thể xóa bài viết!']);
            }
            exit();
        }
        echo json_encode(['success' => false, 'message' => 'Thiếu dữ liệu!']);
        exit();
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['resubmit_post'])) {
        header('Content-Type: application/json');
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập!']);
            exit();
        }
        $post_id = (int)($_POST['post_id'] ?? 0);
        $user_id = $_SESSION['user_id'];
        if ($post_id) {
            // Chỉ cho phép gửi lại bài bị từ chối của mình
            $stmt = $conn->prepare("UPDATE bai_viet SET trang_thai = 'pending' WHERE id = ? AND nguoi_dung_id = ? AND trang_thai = 'rejected'");
            $stmt->execute([$post_id, $user_id]);
            if ($stmt->rowCount() > 0) {
                echo json_encode(['success' => true, 'message' => 'Đã gửi lại bài viết!']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Không thể gửi lại bài viết!']);
            }
            exit();
        }
        echo json_encode(['success' => false, 'message' => 'Thiếu dữ liệu!']);
        exit();
    }
    if (!isset($_SESSION['user_id'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập để bình luận!']);
        exit();
    }

    $post_id = (int)($_POST['post_id'] ?? 0);
    $comment = trim($_POST['comment'] ?? '');
    $user_id = $_SESSION['user_id'];

    if ($post_id && !empty($comment)) {
        $stmt = $conn->prepare("INSERT INTO binh_luan_bai_viet (bai_viet_id, nguoi_dung_id, noi_dung) VALUES (?, ?, ?)");
        if ($stmt->execute([$post_id, $user_id, $comment])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Bình luận thành công!']);
            exit();
        }
    }

    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Lỗi bình luận!']);
    exit();
}

// --- [LẤY DANH SÁCH BÀI VIẾT, BÀI CHỜ DUYỆT, BỊ TỪ CHỐI] ---
$posts = [];
$my_pending_posts = [];
$my_rejected_posts = [];

// Lấy danh sách chủ đề để lọc
$chu_de_list = [];
$res = $conn->query('SELECT id, ten_chude FROM chude ORDER BY ten_chude ASC');
while ($row = $res->fetch(PDO::FETCH_ASSOC)) $chu_de_list[] = $row;

// Xử lý lọc chủ đề
$filter_chu_de_id = intval($_GET['chu_de_id'] ?? 0);

// Lấy bài viết đã duyệt (công khai) kèm tên chủ đề
$sql = "SELECT bv.*, nd.ho_ten, cd.ten_chude,
        (SELECT COUNT(*) FROM bai_viet_yeuthich WHERE bai_viet_id = bv.id) as total_likes,
        (SELECT COUNT(*) FROM bai_viet_yeuthich WHERE bai_viet_id = bv.id AND nguoi_dung_id = ?) as is_liked
        FROM bai_viet bv
        LEFT JOIN nguoi_dung nd ON bv.nguoi_dung_id = nd.id
        LEFT JOIN chude cd ON bv.chu_de_id = cd.id
        WHERE bv.trang_thai = 'approved'";
$params = [$user_id];
if ($filter_chu_de_id > 0) {
    $sql .= " AND bv.chu_de_id = ?";
    $params[] = $filter_chu_de_id;
}
$sql .= " ORDER BY bv.ngay_tao DESC";
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Lấy bài viết chờ duyệt của tôi
if ($user_id) {
    $stmt = $conn->prepare("SELECT * FROM bai_viet WHERE nguoi_dung_id = ? AND trang_thai = 'pending' ORDER BY ngay_tao DESC");
    $stmt->execute([$user_id]);
    $my_pending_posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Lấy bài viết bị từ chối của tôi
    $stmt = $conn->prepare("SELECT * FROM bai_viet WHERE nguoi_dung_id = ? AND trang_thai = 'rejected' ORDER BY ngay_tao DESC");
    $stmt->execute([$user_id]);
    $my_rejected_posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bài Viết - HuynhHoan</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Quicksand:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/theme.css">
    <link rel="stylesheet" href="assets/notifications.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="assets/notifications.js" defer></script>
    <style>
                .icon-btn {
                    width: 2rem;
                    height: 2rem;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    color: #8ab74a;
                    background: none;
                    border: none;
                    border-radius: 0;
                    box-shadow: none;
                    transition: color 0.2s;
                    position: relative;
                    padding: 0;
                }
                .icon-btn:hover {
                    color: #6ea13a;
                    background: none;
                    border: none;
                    box-shadow: none;
                }
                .icon-btn i {
                    color: #3d6b3f !important;
                    font-size: 1.2rem;
                    filter: none;
                }
                .icon-btn i.fa-bell {
                    color: #3d6b3f !important;
                }
        /* Ép toàn bộ trang dùng font Quicksand */
        body, h1, h2, h3, h4, h5, h6, 
        p, span, a, button, input, textarea, select, label {
            font-family: 'Quicksand', sans-serif !important;
        }
        .posts-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
            color: #e0f7e1ff;
        }
        
        .post-form {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .post-form h2 {
            color: #3d6b3f;
            margin-bottom: 1.5rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #2d3748;
        }
        
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #3d6b3f;
        }
        
        .form-group textarea {
            min-height: 150px;
            resize: vertical;
        }
        
        .btn-submit {
            background: #3d6b3f;
            color: white;
            padding: 0.75rem 2rem;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        .btn-submit:hover {
            background: #2d5a2d;
        }
        
        .btn-edit, .btn-delete {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 6px;
            font-size: 0.875rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-edit {
            background: #3498db;
            color: white;
        }
        
        .btn-edit:hover {
            background: #2980b9;
        }
        
        .btn-delete {
            background: #e74c3c;
            color: white;
        }
        
        .btn-delete:hover {
            background: #c0392b;
        }

        body {
            font-family: var(--font-base);
            font-size: 16px;
            line-height: 1.5;
            color: #1d3e1f;
            background-color: #f7f9f6 !important; 
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
                }
        
        .status-badge {
            display: inline-block;
            padding: 0.4em 0.8em;
            border-radius: 6px;
            font-size: 0.875rem;
            font-weight: 600;
        }
        
        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }
        
        .status-rejected {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .btn-resubmit {
            background: #10b981;
            color: white;
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 6px;
            font-size: 0.875rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-resubmit:hover {
            background: #059669;
        }

        .btn-like {
        background: #fff0f0;
        color: #e74c3c;
        padding: 0.5rem 1rem;
        border: 1px solid #e74c3c;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 600;
        transition: all 0.3s;
        margin-right: 10px;
        }
            .btn-like:hover {
        background: #ffe6e6;
        }
        .btn-like.liked {
            background: #e74c3c;
            color: white;
        }
        .btn-like i {
            margin-right: 5px;
        }
        .pending-posts-section {
            background: #fffbeb;
            border: 2px solid #fbbf24;
            padding: 2rem;
            border-radius: 12px;
            margin-bottom: 2rem;
        }
        
        .rejected-posts-section {
            background: #fef2f2;
            border: 2px solid #f87171;
            padding: 2rem;
            border-radius: 12px;
            margin-bottom: 2rem;
        }
        
        .post-item {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .post-item.pending {
            background: #fefce8;
            border: 2px solid #fbbf24;
        }
        
        .post-item.rejected {
            background: #fef2f2;
            border: 2px solid #f87171;
        }
        
        .post-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #e2e8f0;
        }
        
        .post-author {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .author-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #3d6b3f;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
        
        .author-info h4 {
            margin: 0;
            font-size: 1rem;
            color: #2d3748;
        }
        
        .post-date {
            font-size: 0.875rem;
            color: #718096;
        }
        
        .post-title {
            font-size: 1.5rem;
            color: #3d6b3f;
            margin-bottom: 1rem;
        }
        
        .post-content {
            color: #4a5568;
            line-height: 1.8;
            margin-bottom: 1.5rem;
        }
        
        .post-actions {
            display: flex;
            gap: 1rem;
            padding-top: 1rem;
            border-top: 2px solid #e2e8f0;
        }
        
        .btn-comment {
            background: #f0f4f0;
            color: #3d6b3f;
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-comment:hover {
            background: #3d6b3f;
            color: white;
        }
        
        .comments-section {
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 2px solid #e2e8f0;
            display: none;
        }
        
        .comment-form {
            margin-bottom: 1.5rem;
        }
        
        .comment-form textarea {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            min-height: 80px;
            resize: vertical;
        }
        
        .comment-item {
            background: #f7fafc;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
        
        .comment-author {
            font-weight: 600;
            color: #3d6b3f;
            margin-bottom: 0.5rem;
        }
        
        .comment-content {
            color: #4a5568;
            line-height: 1.6;
        }
        
        .comment-date {
            font-size: 0.75rem;
            color: #a0aec0;
            margin-top: 0.5rem;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
        
        .alert-success {
            background: #c6f6d5;
            color: #204333ff;
        }
        
        .alert-error {
            background: #fed7d7;
            color: #742a2a;
        }
        /* --- CSS THANH MENU VIÊN THUỐC --- */
        .nav {
            display: flex;
            align-items: center;
            justify-content: center; /* Căn giữa menu */
            gap: 15px; /* Khoảng cách giữa các nút */
        }

        /* Ẩn menu trên điện thoại (nếu cần) */
        @media (max-width: 1024px) {
            .nav { display: none; }
        }

        /* Giao diện nút bấm */
        .nav a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            height: 42px; /* Chiều cao nút */
            padding: 0 28px; /* Độ rộng nút */
            
            /* Hình dáng viên thuốc giống hình mẫu */
            background-color: #ffffff;
            border: 1px solid #e8e0c5; /* Viền màu vàng kem nhạt */
            border-radius: 50px; /* Bo tròn hoàn toàn */
            
            /* Chữ */
            color: #3d6b3f; /* Màu xanh rêu */
            font-size: 15px;
            font-weight: 600;
            text-decoration: none !important; /* Bỏ gạch chân tuyệt đối */
            transition: all 0.3s ease; /* Hiệu ứng mượt mà */
            white-space: nowrap;
        }

        /* Hiệu ứng khi đưa chuột vào (Hover) & Đang chọn (Active) */
        .nav a:hover, 
        .nav a.active {
            border-color: #3d6b3f; /* Viền đổi màu xanh đậm */
            color: #1d3e1f;        /* Chữ đậm hơn */
            background-color: #fff;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1); /* Nổi lên nhẹ */
            transform: translateY(-2px); /* Bay lên 1 xíu */
        }

        /* Ẩn class menu-link cũ nếu còn sót */
        .menu-link {
            text-decoration: none !important;
            border-bottom: none !important;
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
        .topic-tabs { margin-bottom:2rem; }
        .topic-tab {
    display:inline-block;
    padding:10px 24px;
    background:#fff;
    border:1.5px solid #e8e0c5;
    border-radius:30px;
    color:#3d6b3f;
    font-weight:600;
    font-size:1rem;
    text-decoration:none;
    transition:all 0.2s;
    box-shadow:0 2px 8px rgba(0,0,0,0.04);
    margin-bottom:6px;
}
.topic-tab:hover, .topic-tab.active {
    background:#3d6b3f;
    color:#fff;
    border-color:#3d6b3f;
    box-shadow:0 4px 16px rgba(61,107,63,0.08);
}
</style>
</head>
<body>
    <!-- Header Navigation -->
    <header class="header" style="background: white; box-shadow: 0 2px 10px rgba(0,0,0,0.1); position: sticky; top: 0; z-index: 1000; padding: 1rem 0;">
        <div class="container" style="max-width: 1200px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center; padding: 0 1rem;">
            <a href="trangchu.php" class="brand-logo" style="display:flex;align-items:center;gap:12px;text-decoration:none;">
                <img src="images/logo.jpg" alt="HuynhHoan Logo" style="height:45px;width:auto;border-radius:8px;">
                <span style="font-weight:600;font-size:1.4rem;color:#3d6b3f;">HuynhHoan</span>
            </a>

            <nav class="nav">
                <a href="trangchu.php">Trang chủ</a>
                <a href="san-pham.php">Sản Phẩm</a>
                <a href="baiviet_yeuthich.php" >Đã thích</a>
                <a href="don_hang_cua_toi.php">Theo Dõi Đơn Hàng</a>
                <a href="lienhe.php">Liên Hệ</a>
            </nav>
            <div class="header-actions" style="display:flex;gap:1rem;align-items:center;">
                <a href="giohang.php" style="text-decoration:none;color:#2d3748;font-size:1.2rem;">
                    <i class="fas fa-shopping-bag"></i>
                </a>
                <!-- Notification bell/dropdown sẽ được tự động chèn bởi notifications.js nếu chưa có -->
                <!-- Đăng nhập / Đăng xuất -->
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="logout.php" style="text-decoration:none;color:#1d3e1f;font-weight:600;">
                        <i class="fas fa-sign-out-alt" style="color:#1d3e1f;"></i> Đăng Xuất
                    </a>
                <?php else: ?>
                    <a href="dangnhap.php" style="text-decoration:none;color:#3d6b3f;font-weight:500;">
                        <i class="fas fa-sign-in-alt"></i> Đăng Nhập
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </header>
    
    <div class="posts-container">
        <h1 style="text-align: center; color: #316339ff; margin-bottom: 2rem;">
            <i class="fas fa-newspaper"></i> Cộng Đồng Chia Sẻ
        </h1>
        <!-- Bộ lọc chủ đề dạng tab -->
        <div class="topic-tabs" style="display:flex;flex-wrap:wrap;gap:10px;justify-content:center;margin-bottom:2rem;">
    <a href="baiviet.php" class="topic-tab<?= ($filter_chu_de_id == 0) ? ' active' : '' ?>">
        <i class="fas fa-layer-group" style="margin-right:6px;"></i> Tất cả
    </a>
    <?php foreach ($chu_de_list as $cd): ?>
        <a href="baiviet.php?chu_de_id=<?= $cd['id'] ?>" class="topic-tab<?= ($filter_chu_de_id == $cd['id']) ? ' active' : '' ?>">
            <i class="fas fa-tag" style="margin-right:6px;color:#8ab74a;"></i> <?= htmlspecialchars($cd['ten_chude']) ?>
        </a>
    <?php endforeach; ?>
</div>
<style>
.topic-tabs {
    margin-bottom: 2rem;
    display: flex;
    flex-wrap: nowrap;
    gap: 10px;
    overflow-x: auto;
    justify-content: center;
    scrollbar-width: thin;
    padding-bottom: 4px;
}
.topic-tab {
    display: inline-flex;
    align-items: center;
    padding: 8px 20px;
    background: linear-gradient(90deg, #e0ffe7 0%, #f7f9f6 100%);
    border: none;
    border-radius: 24px;
    color: #316339;
    font-weight: 600;
    font-size: 1rem;
    text-decoration: none;
    transition: all 0.22s cubic-bezier(.4,0,.2,1);
    box-shadow: 0 2px 12px rgba(61,107,63,0.07);
    margin-bottom: 0;
    white-space: nowrap;
    position: relative;
    cursor: pointer;
}
.topic-tab i {
    font-size: 1.1em;
    margin-right: 6px;
    color: #8ab74a;
    transition: color 0.2s;
}
.topic-tab:hover, .topic-tab.active {
    background: linear-gradient(90deg, #8ab74a 0%, #3d6b3f 100%);
    color: #fff;
    box-shadow: 0 4px 18px rgba(61,107,63,0.13);
}
.topic-tab.active i, .topic-tab:hover i {
    color: #fff;
}
</style>
        
        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <!-- Form đăng bài -->
        <?php if (isset($_SESSION['user_id'])): ?>
        <?php
        // Lấy danh sách chủ đề từ DB
        $chu_de_list = [];
        // Lấy chủ đề từ bảng chude (admin/qlchude.php)
        $res = $conn->query('SELECT id, ten_chude FROM chude ORDER BY ten_chude ASC');
        while ($row = $res->fetch(PDO::FETCH_ASSOC)) $chu_de_list[] = $row;
        ?>
        <div class="post-form">
            <h2><i class="fas fa-pen"></i> Chia Sẻ Bài Viết Mới</h2>
            <form method="POST">
                <div class="form-group">
                    <label>Chủ đề *</label>
                    <select name="chu_de_id" required>
                        <option value="">-- Chọn chủ đề --</option>
                        <?php foreach (
                            $chu_de_list as $cd): ?>
                            <option value="<?= $cd['id'] ?>"><?= htmlspecialchars($cd['ten_chude']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Tiêu Đề *</label>
                    <input type="text" name="tieu_de" placeholder="Nhập tiêu đề bài viết..." required>
                </div>
                <div class="form-group">
                    <label>Nội Dung *</label>
                    <textarea name="noi_dung" placeholder="Chia sẻ suy nghĩ của bạn..." required></textarea>
                </div>
                <button type="submit" name="submit_article" class="btn-submit">
                    <i class="fas fa-paper-plane"></i> Đăng Bài
                </button>
                <p style="margin-top: 1rem; color: #718096; font-size: 0.875rem;">
                    <i class="fas fa-info-circle"></i> Bài viết sẽ được kiểm duyệt trước khi hiển thị công khai
                </p>
            </form>
        </div>
        <?php else: ?>
        <div class="post-form">
            <p style="text-align: center; color: #718096;">
                <i class="fas fa-sign-in-alt"></i> 
                <a href="dangnhap.php" style="color: #3d6b3f; font-weight: 600;">Đăng nhập</a> 
                để chia sẻ bài viết của bạn!
            </p>
        </div>
        <?php endif; ?>
        
        <!-- Bài viết chưa duyệt của tôi -->
        <?php if (!empty($my_pending_posts)): ?>
        <div class="pending-posts-section">
            <h2 style="color: #92400e; margin-bottom: 1.5rem;">
                <i class="fas fa-clock"></i> Bài Viết Chờ Duyệt Của Bạn (<?php echo count($my_pending_posts); ?>)
            </h2>
            <?php foreach ($my_pending_posts as $pending): ?>
            <div class="post-item pending" id="pending-post-<?php echo $pending['id']; ?>">
                <div class="post-header">
                    <div>
                        <span class="status-badge status-pending">
                            <i class="fas fa-hourglass-half"></i> Chờ duyệt
                        </span>
                        <span class="post-date" style="margin-left: 1rem;">
                            <i class="fas fa-clock"></i> 
                            <?php echo date('d/m/Y H:i', strtotime($pending['ngay_tao'])); ?>
                        </span>
                    </div>
                    <div style="display: flex; gap: 0.5rem;">
                        <button class="btn-edit" onclick="editPost(<?php echo $pending['id']; ?>)">
                            <i class="fas fa-edit"></i> Sửa
                        </button>
                        <button class="btn-delete" onclick="deletePost(<?php echo $pending['id']; ?>)">
                            <i class="fas fa-trash"></i> Xóa
                        </button>
                    </div>
                </div>
                
                <h3 class="post-title" id="title-<?php echo $pending['id']; ?>">
                    <?php echo htmlspecialchars($pending['tieu_de']); ?>
                </h3>
                <div class="post-content" id="content-<?php echo $pending['id']; ?>">
                    <?php echo nl2br(htmlspecialchars($pending['noi_dung'])); ?>
                </div>
                
                <!-- Form chỉnh sửa (ẩn) -->
                <div id="edit-form-<?php echo $pending['id']; ?>" style="display: none; margin-top: 1rem;">
                    <div class="form-group">
                        <label>Tiêu Đề</label>
                        <input type="text" id="edit-title-<?php echo $pending['id']; ?>" 
                               value="<?php echo htmlspecialchars($pending['tieu_de']); ?>" 
                               class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Nội Dung</label>
                        <textarea id="edit-content-<?php echo $pending['id']; ?>" 
                                  style="width: 100%; padding: 0.75rem; border: 2px solid #e2e8f0; border-radius: 8px; min-height: 150px;"
                        ><?php echo htmlspecialchars($pending['noi_dung']); ?></textarea>
                    </div>
                    <div style="display: flex; gap: 0.5rem;">
                        <button class="btn-submit" onclick="savePost(<?php echo $pending['id']; ?>)">
                            <i class="fas fa-save"></i> Lưu
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        <!-- Danh sách bài viết -->
        <h2 style="color: #3a633dff; margin-bottom: 1.5rem;">Bài Viết Mới Nhất</h2>
        
        <?php if (empty($posts)): ?>
            <div class="post-item" style="text-align: center; color: #718096;">
                <i class="fas fa-inbox" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                <p>Chưa có bài viết nào. Hãy là người đầu tiên chia sẻ!</p>
            </div>
        <?php else: ?>
            <?php foreach ($posts as $post): ?>
                <?php
                // Lấy bình luận (PDO)
                $comments = [];
                $comment_sql = "SELECT bl.*, nd.ho_ten 
                               FROM binh_luan_bai_viet bl 
                               LEFT JOIN nguoi_dung nd ON bl.nguoi_dung_id = nd.id 
                               WHERE bl.bai_viet_id = ? 
                               ORDER BY bl.ngay_tao DESC";
                $comment_stmt = $conn->prepare($comment_sql);
                $comment_stmt->execute([$post['id']]);
                $comments = $comment_stmt->fetchAll(PDO::FETCH_ASSOC);
                ?>
                <div class="post-item">
                    <div class="post-header">
                        <div class="post-author">
                            <div class="author-avatar">
                                <?php echo strtoupper(substr($post['ho_ten'] ?? 'U', 0, 1)); ?>
                            </div>
                            <div class="author-info">
                                <h4><?php echo htmlspecialchars($post['ho_ten'] ?? 'Ẩn danh'); ?></h4>
                                <span class="post-date">
                                    <i class="fas fa-clock"></i> 
                                    <?php echo date('d/m/Y H:i', strtotime($post['ngay_tao'])); ?>
                                </span>
                            </div>
                        </div>
                        <?php if (
                            isset($_SESSION['user_id']) &&
                            $_SESSION['user_id'] == $post['nguoi_dung_id'] &&
                            $post['trang_thai'] === 'approved'
                        ): ?>
                        <button class="btn-delete" onclick="deletePost(<?php echo $post['id']; ?>)" 
                                style="padding: 0.4rem 0.8rem; font-size: 0.8rem;">
                            <i class="fas fa-trash"></i> Xóa
                        </button>
                        <?php endif; ?>
                    </div>
                    
                    <h3 class="post-title"><?php echo htmlspecialchars($post['tieu_de']); ?></h3>
                    <div style="margin-bottom: 0.5rem; color: #6ea13a; font-size: 1rem; font-weight: 600;">
                        <i class="fas fa-tag"></i> Chủ đề: <?php echo htmlspecialchars($post['ten_chude'] ?? 'Không xác định'); ?>
                    </div>
                    <div class="post-content post-content-toggle">
                        <?php echo nl2br(htmlspecialchars($post['noi_dung'])); ?>
                    </div>
                    <button class="toggle-content-btn" style="display:none;margin-top:8px;background:none;border:none;color:#3498db;cursor:pointer;font-weight:600;" onclick="togglePostContent(this)">Xem thêm</button>
                        <script>
                        // Ẩn/bật nội dung dài bài viết
                        function togglePostContent(btn) {
                            const contentDiv = btn.previousElementSibling;
                            if (contentDiv.classList.contains('expanded')) {
                                contentDiv.classList.remove('expanded');
                                btn.textContent = 'Xem thêm';
                            } else {
                                contentDiv.classList.add('expanded');
                                btn.textContent = 'Thu gọn';
                            }
                        }
                        // Tự động kiểm tra và hiển thị nút thu gọn nếu nội dung quá dài
                        document.addEventListener('DOMContentLoaded', function() {
                            document.querySelectorAll('.post-content-toggle').forEach(function(div) {
                                if (div.scrollHeight > 220) {
                                    div.style.maxHeight = '220px';
                                    div.style.overflow = 'hidden';
                                    div.nextElementSibling.style.display = 'inline-block';
                                }
                            });
                        });
                        // CSS cho hiệu ứng thu gọn/mở rộng
                        const style = document.createElement('style');
                        style.innerHTML = `
                            .post-content-toggle { max-height:220px; overflow:hidden; transition:max-height 0.3s; }
                            .post-content-toggle.expanded { max-height:2000px !important; overflow:auto; }
                        `;
                        document.head.appendChild(style);
                        </script>
                    
                    <div class="post-actions">
                        <button class="btn-like <?php echo ($post['is_liked'] > 0) ? 'liked' : ''; ?>" onclick="toggleLike(this, <?php echo $post['id']; ?>)">
                            <i class="<?php echo ($post['is_liked'] > 0) ? 'fas' : 'far'; ?> fa-heart"></i> 
                            <span><?php echo $post['total_likes']; ?></span> Yêu thích
                        </button>
                        <button class="btn-comment" onclick="toggleComments(<?php echo $post['id']; ?>)">
                            <i class="fas fa-comments"></i> 
                            Bình luận (<?php echo count($comments); ?>)
                        </button>
                    </div>
                    
                    <div class="comments-section" id="comments-<?php echo $post['id']; ?>">
                        <?php if (isset($_SESSION['user_id'])): ?>
                        <div class="comment-form">
                            <textarea id="comment-text-<?php echo $post['id']; ?>" placeholder="Viết bình luận..."></textarea>
                            <button class="btn-submit" style="margin-top: 0.5rem;" onclick="postComment(<?php echo $post['id']; ?>)">
                                <i class="fas fa-paper-plane"></i> Gửi
                            </button>
                        </div>
                        <?php endif; ?>
                        
                        <div id="comments-list-<?php echo $post['id']; ?>">
                            <?php foreach ($comments as $comment): ?>
                            <div class="comment-item">
                                <div class="comment-author">
                                    <i class="fas fa-user-circle"></i> 
                                    <?php echo htmlspecialchars($comment['ho_ten'] ?? 'Ẩn danh'); ?>
                                </div>
                                <div class="comment-content">
                                    <?php echo nl2br(htmlspecialchars($comment['noi_dung'])); ?>
                                </div>
                                <div class="comment-date">
                                    <?php echo date('d/m/Y H:i', strtotime($comment['ngay_tao'])); ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <script>
    function toggleComments(postId) {
        const section = document.getElementById('comments-' + postId);
        section.style.display = section.style.display === 'none' ? 'block' : 'none';
    }
    
    function postComment(postId) {
        const comment = document.getElementById('comment-text-' + postId).value.trim();
        if (!comment) {
            alert('Vui lòng nhập nội dung bình luận!');
            return;
        }
        
        $.ajax({
            url: 'baiviet.php',
            method: 'POST',
            data: {
                post_comment: true,
                post_id: postId,
                comment: comment
            },
            success: function(response) {
                if (response.success) {
                    alert(response.message);
                    location.reload();
                } else {
                    alert(response.message);
                }
            }
        });
    }
    
    function editPost(postId) {
        // Ẩn nội dung hiện tại
        document.getElementById('title-' + postId).style.display = 'none';
        document.getElementById('content-' + postId).style.display = 'none';
        // Hiện form chỉnh sửa
        document.getElementById('edit-form-' + postId).style.display = 'block';
    }
    
    function cancelEdit(postId) {
        // Hiện lại nội dung
        document.getElementById('title-' + postId).style.display = 'block';
        document.getElementById('content-' + postId).style.display = 'block';
        // Ẩn form chỉnh sửa
        document.getElementById('edit-form-' + postId).style.display = 'none';
    }
    
    function savePost(postId) {
        const title = document.getElementById('edit-title-' + postId).value.trim();
        const content = document.getElementById('edit-content-' + postId).value.trim();
        
        if (!title || !content) {
            alert('Vui lòng điền đầy đủ thông tin!');
            return;
        }
        
        $.ajax({
            url: 'baiviet.php',
            method: 'POST',
            dataType: 'json',
            data: {
                update_post: true,
                post_id: postId,
                title: title,
                content: content
            },
            success: function(response) {
                if (response.success) {
                    alert(response.message);
                    location.reload();
                } else {
                    alert(response.message);
                }
            },
            error: function() {
                alert('Có lỗi xảy ra. Vui lòng thử lại!');
            }
        });
    }
    
    function deletePost(postId) {
        if (!confirm('Bạn có chắc muốn xóa bài viết này?')) {
            return;
        }
        
        $.ajax({
            url: 'baiviet.php',
            method: 'POST',
            dataType: 'json',
            data: {
                delete_post: true,
                post_id: postId
            },
            success: function(response) {
                if (response.success) {
                    alert(response.message);
                    location.reload();
                } else {
                    alert(response.message);
                }
            },
            error: function() {
                alert('Có lỗi xảy ra. Vui lòng thử lại!');
            }
        });
    }
    
    function resubmitPost(postId) {
        if (!confirm('Gửi lại bài viết này để admin duyệt?')) {
            return;
        }
        
        $.ajax({
            url: 'baiviet.php',
            method: 'POST',
            dataType: 'json',
            data: {
                resubmit_post: true,
                post_id: postId
            },
            success: function(response) {
                if (response.success) {
                    alert(response.message);
                    location.reload();
                } else {
                    alert(response.message);
                }
            },
            error: function() {
                alert('Có lỗi xảy ra. Vui lòng thử lại!');
            }
        });
    }
    function toggleLike(btn, postId) {
    $.ajax({
        url: 'baiviet.php',
        method: 'POST',
        data: {
            toggle_like: true,
            post_id: postId
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                const icon = btn.querySelector('i');
                const countSpan = btn.querySelector('span');
                
                // Cập nhật số lượng
                countSpan.innerText = response.count;
                
                // Cập nhật giao diện nút
                if (response.action === 'liked') {
                    btn.classList.add('liked');
                    icon.classList.remove('far');
                    icon.classList.add('fas');
                } else {
                    btn.classList.remove('liked');
                    icon.classList.remove('fas');
                    icon.classList.add('far');
                }
            } else {
                alert(response.message);
                if(response.message.includes('đăng nhập')) {
                    window.location.href = 'dangnhap.php';
                }
            }
        },
        error: function() {
            alert('Có lỗi xảy ra!');
        }
    });
}
    </script>
    
    <!-- Footer -->
    <footer style="background: #35482dff; color: white; padding: 2rem 0; margin-top: 4rem; text-align: center;">
        <div style="max-width: 1200px; margin: 0 auto; padding: 0 1rem;">
            <p style="margin: 0; font-size: 0.875rem;">
                <i class="fas fa-leaf" style="color: #3d6b3f;"></i> 
                &copy; 2025 HuynhHoan. Hệ thống chia sẻ cộng đồng.
            </p>
        </div>
    </footer>
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
