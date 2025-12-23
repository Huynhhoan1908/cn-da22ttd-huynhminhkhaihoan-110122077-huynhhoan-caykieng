<?php
// Admin Configuration & Database Connection
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Ngăn cache
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Kiểm tra đăng nhập
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: ../dangnhap.php");
    exit();
}

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'web_cay');
define('UPLOAD_DIR', dirname(__DIR__) . '/uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB

// Tạo thư mục uploads nếu chưa có
$upload_dir = dirname(__DIR__) . '/uploads/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}
chmod($upload_dir, 0777);

// Kết nối database
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

// Helper function: tìm cột trong bảng
function find_column_like($conn, $table, $patterns = []) {
    $cols = [];
    $res = $conn->query("SHOW COLUMNS FROM `{$table}`");
    if (!$res) return null;
    while ($r = $res->fetch_assoc()) $cols[] = $r['Field'];
    
    // Check explicit candidates first
    foreach ($patterns as $p) {
        if (in_array($p, $cols)) return $p;
    }
    
    // Fallback: tìm cột chứa từ khóa
    foreach ($cols as $c) {
        foreach ($patterns as $p) {
            if (stripos($c, $p) !== false) return $c;
        }
    }
    return null;
}

// Tạo bảng kho movements nếu chưa có
$conn->query("CREATE TABLE IF NOT EXISTS `kho_movements` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `loai` ENUM('nhap','xuat','kiemke') NOT NULL,
    `san_pham_id` INT NOT NULL,
    `qty` INT NOT NULL,
    `nguoi_id` INT DEFAULT NULL,
    `ghi_chu` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

// Tạo bảng đánh giá nếu chưa có
$conn->query("CREATE TABLE IF NOT EXISTS danh_gia (
    id INT AUTO_INCREMENT PRIMARY KEY,
    san_pham_id INT NOT NULL,
    user_id INT NULL,
    user_email VARCHAR(255) NULL,
    user_name VARCHAR(150) NOT NULL,
    rating TINYINT NOT NULL DEFAULT 5,
    comment TEXT NOT NULL,
    admin_reply TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX(san_pham_id),
    INDEX(user_id),
    INDEX(user_email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// Tạo bảng admin_notifications nếu chưa có
if ($conn instanceof PDO) {
    $conn->exec("CREATE TABLE IF NOT EXISTS admin_notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        type VARCHAR(50) NOT NULL,
        message TEXT NOT NULL,
        link VARCHAR(255) DEFAULT NULL,
        is_read TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
}
?>
