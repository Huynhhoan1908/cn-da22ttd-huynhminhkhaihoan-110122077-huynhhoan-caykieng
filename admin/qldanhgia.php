<?php
require_once 'config.php';

$current_page = 'reviews';
$page_title = 'Quản Lý Đánh Giá - HuynhHoan';

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    if ($_POST['action'] === 'reply') {
        $id = (int)($_POST['id'] ?? 0);
        $reply = trim($_POST['reply'] ?? '');
        
        if (empty($reply)) {
            echo json_encode(['success' => false, 'message' => 'Nội dung phản hồi không được trống']);
            exit();
        }
        
        $stmt = $conn->prepare("UPDATE danh_gia SET admin_reply = ? WHERE id = ?");
        $stmt->bind_param("si", $reply, $id);
        
        if ($stmt->execute()) {
            // Gửi thông báo cho user đã đánh giá
            $reviewInfo = $conn->query("SELECT user_id, san_pham_id FROM danh_gia WHERE id = $id");
            $review = $reviewInfo ? $reviewInfo->fetch_assoc() : null;
            if ($review && file_exists(dirname(__DIR__) . '/notification_helpers.php')) {
                require_once dirname(__DIR__) . '/notification_helpers.php';
                try {
                    notify_review_reply_user($review['user_id'], $review['san_pham_id']);
                } catch (Exception $ex) { error_log('Lỗi gửi notification đánh giá: ' . $ex->getMessage()); }
            }
            echo json_encode(['success' => true, 'message' => 'Phản hồi thành công']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Lỗi phản hồi']);
        }
        exit();
    }
    
    if ($_POST['action'] === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        
        if ($conn->query("DELETE FROM danh_gia WHERE id = $id")) {
            echo json_encode(['success' => true, 'message' => 'Xóa thành công']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Lỗi xóa']);
        }
        exit();
    }
}

// Load reviews
$reviews = [];
$result = $conn->query("SELECT dg.*, sp.ten_san_pham, dg.user_name as hoten 
                        FROM danh_gia dg
                        LEFT JOIN san_pham sp ON dg.san_pham_id = sp.id
                        ORDER BY dg.created_at DESC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $reviews[] = $row;
    }
}

include 'header.php';
?>

<div class="page-header">
    <h1>Quản Lý Đánh Giá</h1>
    <div class="breadcrumb">
        <i class="fas fa-home"></i>
        <span>Dashboard</span>
        <i class="fas fa-chevron-right"></i>
        <span>Đánh Giá</span>
    </div>
</div>

<div class="table-container">
    <div class="section-header">
        <h2>Danh Sách Đánh Giá (<?php echo count($reviews); ?>)</h2>
        <div class="filter-bar-container">
            <span class="filter-icon"><i class="fas fa-filter"></i></span>
            <select id="filterReply" class="form-control filter-bar-select">
                <option value="">Tất cả phản hồi</option>
                <option value="1">Đã phản hồi</option>
                <option value="0">Chưa phản hồi</option>
            </select>
        </div>
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Sản Phẩm</th>
                <th>Khách Hàng</th>
                <th>Đánh Giá</th>
                <th>Nội Dung</th>
                <th>Ngày</th>
                <th>Phản Hồi</th>
                <th>Thao Tác</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($reviews as $review): ?>
            <tr>
                <td><?php echo $review['id']; ?></td>
                <td><?php echo htmlspecialchars($review['ten_san_pham']); ?></td>
                <td><?php echo htmlspecialchars($review['hoten']); ?></td>
                <td>
                    <?php for ($i = 0; $i < 5; $i++): ?>
                        <i class="fas fa-star" style="color: <?php echo $i < $review['rating'] ? '#f39c12' : '#ddd'; ?>"></i>
                    <?php endfor; ?>
                </td>
                <td><?php echo htmlspecialchars($review['comment']); ?></td>
                <td><?php echo date('d/m/Y', strtotime($review['created_at'])); ?></td>
                <td>
                    <?php if ($review['admin_reply']): ?>
                        <span class="badge badge-success" style="background: linear-gradient(135deg, #7fa84e, #9bc26f); color: white; padding: 0.5rem 1rem; border-radius: 20px; font-size: 0.875rem;">Đã phản hồi</span>
                    <?php else: ?>
                        <span class="badge badge-warning" style="background: #fef3c7; color: #92400e; padding: 0.5rem 1rem; border-radius: 20px; font-size: 0.875rem;">Chưa phản hồi</span>
                    <?php endif; ?>
                </td>
                <td>
                    <button class="btn btn-info btn-sm" onclick='showReplyModal(<?php echo json_encode($review); ?>)'>
                        <i class="fas fa-reply"></i>
                    </button>
                    <button class="btn btn-danger btn-sm" onclick="deleteReview(<?php echo $review['id']; ?>)">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Reply Modal -->
<div id="replyModal" class="modal">
    <div class="modal-content" style="max-width:600px;">
        <span class="close" onclick="closeModal()">&times;</span>
        <h2>Phản Hồi Đánh Giá</h2>
        <div id="reviewInfo" style="padding:1rem;background:#f5f5f5;border-radius:8px;margin-bottom:1rem;"></div>
        <form id="replyForm">
            <input type="hidden" id="reviewId">
            <div class="form-group">
                <label>Nội Dung Phản Hồi *</label>
                <textarea id="replyText" rows="4" required></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Gửi Phản Hồi</button>
        </form>
    </div>
</div>

<script>
// Lọc theo trạng thái phản hồi
document.addEventListener('DOMContentLoaded', function() {
    var filter = document.getElementById('filterReply');
    if (filter) {
        filter.addEventListener('change', function() {
            var val = this.value;
            var rows = document.querySelectorAll('.data-table tbody tr');
            rows.forEach(function(row) {
                var badge = row.querySelector('td:nth-child(7) .badge');
                if (!badge) { row.style.display = ''; return; }
                var isReplied = badge.classList.contains('badge-success');
                if (val === '' || (val === '1' && isReplied) || (val === '0' && !isReplied)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    }
});
function showReplyModal(review) {
    document.getElementById('reviewId').value = review.id;
    document.getElementById('replyText').value = review.admin_reply || '';
    
    const stars = Array(5).fill(0).map((_, i) => 
        `<i class="fas fa-star" style="color: ${i < review.rating ? '#f39c12' : '#ddd'}"></i>`
    ).join('');
    
    document.getElementById('reviewInfo').innerHTML = `
        <strong>Sản phẩm:</strong> ${review.ten_san_pham}<br>
        <strong>Khách hàng:</strong> ${review.hoten}<br>
        <strong>Đánh giá:</strong> ${stars}<br>
        <strong>Nội dung:</strong> ${review.comment || 'undefined'}
    `;
    
    document.getElementById('replyModal').style.display = 'block';
}

function closeModal() {
    document.getElementById('replyModal').style.display = 'none';
}

document.getElementById('replyForm').onsubmit = function(e) {
    e.preventDefault();
    
    $.ajax({
        url: 'qldanhgia.php',
        method: 'POST',
        data: {
            action: 'reply',
            id: document.getElementById('reviewId').value,
            reply: document.getElementById('replyText').value
        },
        success: function(response) {
            if (response.success) {
                toastr.success(response.message);
                closeModal();
                location.reload();
            } else {
                toastr.error(response.message);
            }
        }
    });
};

function deleteReview(id) {
    if (!confirm('Bạn có chắc muốn xóa đánh giá này?')) return;
    
    $.ajax({
        url: 'qldanhgia.php',
        method: 'POST',
        data: {
            action: 'delete',
            id: id
        },
        success: function(response) {
            if (response.success) {
                toastr.success(response.message);
                location.reload();
            } else {
                toastr.error(response.message);
            }
        }
    });
}
</script>

<style>
.badge {
    display: inline-block;
    padding: 0.25em 0.6em;
    font-size: 0.875em;
    font-weight: 600;
    border-radius: 0.25rem;
}
.badge-success { background: var(--success-color); color: white; }
.badge-warning { background: #f39c12; color: white; }

/* Filter bar styling */
.filter-bar-container {
    margin-top: 1rem;
    display: flex;
    align-items: center;
    max-width: 260px;
    background: #fff;
    border: 1.5px solid #d1d5db;
    border-radius: 2rem;
    box-shadow: 0 2px 8px rgba(52,152,219,0.07);
    padding: 0.2rem 1rem 0.2rem 0.8rem;
    position: relative;
    transition: border-color 0.2s;
}
.filter-bar-container:focus-within {
    border-color: #3498db;
    box-shadow: 0 2px 12px rgba(52,152,219,0.15);
}
.filter-bar-select {
    border: none;
    outline: none;
    background: transparent;
    font-size: 1rem;
    padding: 0.5rem 0.5rem 0.5rem 0.2rem;
    width: 100%;
    border-radius: 2rem;
    color: #222;
    appearance: none;
    -webkit-appearance: none;
    -moz-appearance: none;
}
.filter-bar-select:focus {
    background: #f0f8ff;
}
.filter-icon {
    color: #b0b8c1;
    font-size: 1.1rem;
    margin-right: 0.5rem;
    display: flex;
    align-items: center;
}
</style>

<?php include 'footer.php'; ?>
