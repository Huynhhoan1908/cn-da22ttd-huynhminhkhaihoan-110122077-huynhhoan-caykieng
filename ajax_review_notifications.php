<?php
require_once __DIR__ . '/connect.php';

header('Content-Type: application/json');

// Lấy đánh giá mới nhất có admin_reply trong 24h gần nhất
try {
    $user_id = $_SESSION['user_id'] ?? null;
    $user_email = $_SESSION['email'] ?? null;
    
    $notifications = [];
    
    if ($user_id || $user_email) {
        $sql = "
            SELECT 
                dg.id,
                dg.san_pham_id,
                dg.admin_reply,
                dg.created_at as review_date,
                sp.ten_san_pham
            FROM danh_gia dg
            INNER JOIN san_pham sp ON dg.san_pham_id = sp.id
            WHERE dg.admin_reply IS NOT NULL 
            AND dg.admin_reply != ''
            AND ";
        
        if ($user_id) {
            $sql .= "dg.user_id = :user_id";
            $params = [':user_id' => $user_id];
        } else {
            $sql .= "dg.user_email = :user_email";
            $params = [':user_email' => $user_email];
        }
        
        $sql .= " ORDER BY dg.created_at DESC LIMIT 10";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $reviews = $stmt->fetchAll();
        
        foreach ($reviews as $review) {
            $notifications[] = [
                'id' => 'review_reply_' . $review['id'],
                'type' => 'review_reply',
                'title' => 'Phản hồi đánh giá: ' . $review['ten_san_pham'],
                'message' => substr($review['admin_reply'], 0, 100) . (strlen($review['admin_reply']) > 100 ? '...' : ''),
                'link' => 'chitiet_san_pham.php?id=' . $review['san_pham_id'],
                'created_at' => $review['review_date'],
                'is_read' => false
            ];
        }
    }
    
    echo json_encode(['success' => true, 'notifications' => $notifications]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
