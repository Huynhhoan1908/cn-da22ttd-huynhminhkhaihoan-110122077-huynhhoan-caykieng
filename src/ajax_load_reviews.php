<?php
require_once __DIR__ . '/connect.php';

header('Content-Type: application/json');

$product_id = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;

if ($product_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
    exit;
}

try {
    $stmt = $conn->prepare("SELECT id, user_name, rating, comment, created_at, admin_reply FROM danh_gia WHERE san_pham_id = ? ORDER BY created_at DESC");
    $stmt->execute([$product_id]);
    $comments = $stmt->fetchAll();
    
    $html = '';
    if (empty($comments)) {
        $html = '<p style="color:#999;font-style:italic;margin-top:20px;">Chưa có đánh giá nào.</p>';
    } else {
        foreach ($comments as $c) {
            $stars = '';
            for ($x = 1; $x <= 5; $x++) {
                $stars .= $x <= (int)$c['rating'] ? '<i class="fas fa-star"></i>' : '<i class="far fa-star"></i>';
            }
            
            $html .= '<div class="comment" data-review-id="' . $c['id'] . '">';
            $html .= '<div class="meta">';
            $html .= '<strong>' . htmlspecialchars($c['user_name']) . '</strong> ';
            $html .= '- ' . date('d/m/Y H:i', strtotime($c['created_at'])) . ' ';
            $html .= '<span class="stars">' . $stars . '</span>';
            $html .= '</div>';
            $html .= '<p>' . nl2br(htmlspecialchars($c['comment'])) . '</p>';
            
            if (!empty($c['admin_reply'])) {
                $html .= '<div class="admin-reply" style="margin-top:12px;padding:12px;background:#fdfbe8;border-left:3px solid #7fa84e;border-radius:8px;">';
                $html .= '<strong style="color:#1d3e1f;"><i class="fas fa-reply"></i> Phản hồi từ Shop:</strong>';
                $html .= '<p style="margin-top:8px;color:#3d6b3f;">' . nl2br(htmlspecialchars($c['admin_reply'])) . '</p>';
                $html .= '</div>';
            }
            
            $html .= '</div>';
        }
    }
    
    echo json_encode(['success' => true, 'html' => $html, 'count' => count($comments)]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
