<?php
// Gửi thông báo khi admin trả lời chat hỗ trợ
function notify_chat_reply_user($user_id) {
    global $conn;
    $title = "💬 Shop đã trả lời hỗ trợ";
    $message = "Shop vừa trả lời tin nhắn hỗ trợ của bạn. Vui lòng kiểm tra hộp chat.";
    $link = "chat_support.php";
    insert_notification_thong_bao($user_id, null, 'chat_reply', $title, $message, $link);
}
// Gửi thông báo khi admin trả lời đánh giá
function notify_review_reply_user($user_id, $product_id) {
    global $conn;
    $title = "💬 Shop đã phản hồi đánh giá của bạn";
    $message = "Shop vừa phản hồi đánh giá sản phẩm của bạn. Xem chi tiết trong đơn hàng.";
    $link = "chitiet_san_pham.php?id=$product_id";
    insert_notification_thong_bao($user_id, null, 'review_reply', $title, $message, $link);
}
// Gửi thông báo trạng thái đơn hàng cho user
function notify_order_status_user($user_id, $order_code, $status) {
    global $conn;
    $title = "📦 Cập nhật đơn hàng: $order_code";
    $message = "Trạng thái đơn hàng của bạn đã chuyển sang: $status.";
    $link = "don_hang_cua_toi.php";
    insert_notification_thong_bao($user_id, null, 'order_status', $title, $message, $link);
}
// File: notification_helpers.php

// Đảm bảo đúng đường dẫn khi require từ admin hoặc gốc
if (!isset($conn) || !$conn) {
    if (file_exists(__DIR__ . '/connect.php')) {
        require_once __DIR__ . '/connect.php';
    } else {
        require_once dirname(__DIR__) . '/connect.php';
    }
}


// Hàm gửi thông báo sản phẩm mới cho tất cả user
function notify_new_product_all_users($product_id, $product_name, $category_name) {
    global $conn;
    $title = "🌱 Sản Phẩm Mới: $product_name";
    $message = "Shop vừa về thêm mẫu $product_name thuộc danh mục $category_name. Xem ngay kẻo hết!";
    $link = "chitiet_san_pham.php?id=$product_id";
    // Lấy tất cả user_id từ bảng nguoi_dung
    $sql = "SELECT id FROM nguoi_dung";
    $result = $conn->query($sql);
    if ($result) {
        if ($conn instanceof PDO) {
            while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                insert_notification_thong_bao($row['id'], null, 'new_product', $title, $message, $link);
            }
        } else {
            while ($row = $result->fetch_assoc()) {
                insert_notification_thong_bao($row['id'], null, 'new_product', $title, $message, $link);
            }
        }
    }
}

// Hàm gửi thông báo khuyến mãi mới cho tất cả user
function notify_new_promo_all_users($promo_name, $promo_code) {
    global $conn;
    $title = "🎁 Khuyến Mãi HOT: $promo_name";
    $message = "Nhập mã [$promo_code] để nhận ưu đãi ngay hôm nay. Số lượng có hạn!";
    $link = "san-pham.php";
    $sql = "SELECT id FROM nguoi_dung";
    $result = $conn->query($sql);
    if ($result) {
        if ($conn instanceof PDO) {
            while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                insert_notification_thong_bao($row['id'], null, 'promo', $title, $message, $link);
            }
        } else {
            while ($row = $result->fetch_assoc()) {
                insert_notification_thong_bao($row['id'], null, 'promo', $title, $message, $link);
            }
        }
    }
}

// Hàm phụ insert vào thong_bao
function insert_notification_thong_bao($user_id, $user_email, $type, $title, $message, $link) {
    global $conn;
    try {
        // Ghi log debug để kiểm tra giá trị truyền vào (file tiếng Việt)
        file_put_contents(__DIR__ . '/debug_thong_bao.txt',
            date('Y-m-d H:i:s') . " | user_id: $user_id | user_email: $user_email | type: $type | title: $title | message: $message | link: $link | conn: ".(is_object($conn)?get_class($conn):gettype($conn))."\n",
            FILE_APPEND
        );
        if (!$conn) {
            file_put_contents(__DIR__ . '/debug_thong_bao.txt', date('Y-m-d H:i:s') . " | ERROR: $conn is null!\n", FILE_APPEND);
            return;
        }
        $sql = "INSERT INTO thong_bao (user_id, user_email, type, title, message, link, is_read, created_at) VALUES (?, ?, ?, ?, ?, ?, 0, NOW())";
        if ($conn instanceof PDO) {
            $stmt = $conn->prepare($sql);
            $stmt->execute([$user_id, $user_email, $type, $title, $message, $link]);
        } else if (method_exists($conn, 'prepare')) {
            $stmt = $conn->prepare($sql);
            if ($stmt === false) {
                file_put_contents(__DIR__ . '/debug_thong_bao.txt', date('Y-m-d H:i:s') . " | ERROR: prepare failed: " . ($conn->error ?? 'unknown') . "\n", FILE_APPEND);
                return;
            }
            $stmt->bind_param("isssss", $user_id, $user_email, $type, $title, $message, $link);
            if (!$stmt->execute()) {
                file_put_contents(__DIR__ . '/debug_thong_bao.txt', date('Y-m-d H:i:s') . " | ERROR: execute failed: " . ($stmt->error ?? 'unknown') . "\n", FILE_APPEND);
            }
        } else {
            file_put_contents(__DIR__ . '/debug_thong_bao.txt', date('Y-m-d H:i:s') . " | ERROR: $conn is not PDO or MySQLi!\n", FILE_APPEND);
        }
    } catch (Exception $e) {
        error_log("Lỗi tạo thông báo thong_bao: " . $e->getMessage());
        file_put_contents(__DIR__ . '/debug_thong_bao.txt', date('Y-m-d H:i:s') . " | EXCEPTION: " . $e->getMessage() . "\n", FILE_APPEND);
    }
}
?>