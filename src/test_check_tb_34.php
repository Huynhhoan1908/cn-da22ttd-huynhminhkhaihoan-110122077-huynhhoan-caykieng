<?php
require 'connect.php';
$user_id = 34;
$stmt = $conn->prepare('SELECT * FROM thong_bao WHERE user_id = ? ORDER BY created_at DESC LIMIT 10');
$stmt->execute([$user_id]);
$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
