<?php
// File: admin/gui_thong_bao.php

function taoThongBaoHeThong($conn, $tieu_de, $noi_dung, $loai, $link) {
    try {
        if ($conn instanceof PDO) {
            $stmt = $conn->prepare("INSERT INTO thong_bao_chung (tieu_de, noi_dung, loai, duong_dan) VALUES (?, ?, ?, ?)");
            $stmt->execute([$tieu_de, $noi_dung, $loai, $link]);
        } else {
            // Dành cho mysqli
            $stmt = $conn->prepare("INSERT INTO thong_bao_chung (tieu_de, noi_dung, loai, duong_dan) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $tieu_de, $noi_dung, $loai, $link);
            $stmt->execute();
        }
    } catch (Exception $e) {
        // Ghi log lỗi nếu cần, không làm gián đoạn quy trình chính
        error_log("Lỗi tạo thông báo: " . $e->getMessage());
    }
}
?>