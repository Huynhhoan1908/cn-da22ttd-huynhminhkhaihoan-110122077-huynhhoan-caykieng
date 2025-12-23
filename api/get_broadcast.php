<?php
// File: api/get_broadcast.php
require_once '../connect.php';
header('Content-Type: application/json');

// Lấy 5 thông báo mới nhất
$sql = "SELECT * FROM thong_bao_chung ORDER BY ngay_tao DESC LIMIT 5";

if ($conn instanceof PDO) {
    $stmt = $conn->query($sql);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $result = $conn->query($sql);
    $data = $result->fetch_all(MYSQLI_ASSOC);
}

echo json_encode($data);
?>