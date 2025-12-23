<?php
try {
    // Thông tin kết nối MySQL
    $host = 'localhost';
    $dbname = 'web_cay';
    $username = 'root';
    $password = '';

    // Tạo kết nối PDO với các options bổ sung
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password, $options);
    
} catch(PDOException $e) {
    // Bổ sung thêm log lỗi
    error_log("Database Error: " . $e->getMessage());
    die("Lỗi kết nối: " . $e->getMessage());
}
?>