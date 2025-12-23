<?php
require_once 'config.php';

$current_page = 'posts';
$page_title = 'Quản Lý Bài Viết - HuynhHoan';

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    if ($_POST['action'] === 'approve') {
        $id = (int)($_POST['id'] ?? 0);
        if ($conn->query("UPDATE bai_viet SET trang_thai = 'approved' WHERE id = $id")) {
            echo json_encode(['success' => true, 'message' => 'Đã duyệt bài viết']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Lỗi duyệt bài']);
        }
        exit();
    }
    
    if ($_POST['action'] === 'reject') {
        $id = (int)($_POST['id'] ?? 0);
        if ($conn->query("UPDATE bai_viet SET trang_thai = 'rejected' WHERE id = $id")) {
            echo json_encode(['success' => true, 'message' => 'Đã từ chối bài viết']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Lỗi từ chối bài']);
        }
        exit();
    }
    
    if ($_POST['action'] === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        
        // Xóa bình luận trước
        $conn->query("DELETE FROM binh_luan_bai_viet WHERE bai_viet_id = $id");
        
        // Xóa bài viết
        if ($conn->query("DELETE FROM bai_viet WHERE id = $id")) {
            echo json_encode(['success' => true, 'message' => 'Đã xóa bài viết']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Lỗi xóa bài']);
        }
        exit();
    }
    
    if ($_POST['action'] === 'view') {
        $id = (int)($_POST['id'] ?? 0);
        
        // Lấy thông tin bài viết
        $sql = "SELECT bv.*, nd.ho_ten, nd.email 
                FROM bai_viet bv 
                LEFT JOIN nguoi_dung nd ON bv.nguoi_dung_id = nd.id 
                WHERE bv.id = $id";
        $result = $conn->query($sql);
        
        if ($result && $post = $result->fetch_assoc()) {
            // Format dates
            $post['ngay_tao_formatted'] = date('d/m/Y H:i', strtotime($post['ngay_tao']));
            $post['ngay_cap_nhat_formatted'] = date('d/m/Y H:i', strtotime($post['ngay_cap_nhat']));
            
            // Lấy bình luận
            $comments_sql = "SELECT bl.*, nd.ho_ten 
                           FROM binh_luan_bai_viet bl 
                           LEFT JOIN nguoi_dung nd ON bl.nguoi_dung_id = nd.id 
                           WHERE bl.bai_viet_id = $id 
                           ORDER BY bl.ngay_tao DESC";
            $comments_result = $conn->query($comments_sql);
            $comments = [];
            if ($comments_result) {
                while ($comment = $comments_result->fetch_assoc()) {
                    $comment['ngay_tao_formatted'] = date('d/m/Y H:i', strtotime($comment['ngay_tao']));
                    $comments[] = $comment;
                }
            }
            $post['comments'] = $comments;
            
            echo json_encode(['success' => true, 'post' => $post]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Không tìm thấy bài viết']);
        }
        exit();
    }
}

// Create tables if not exist
$conn->query("CREATE TABLE IF NOT EXISTS bai_viet (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nguoi_dung_id INT NOT NULL,
    tieu_de VARCHAR(255) NOT NULL,
    noi_dung TEXT NOT NULL,
    trang_thai ENUM('pending','approved','rejected') DEFAULT 'pending',
    ngay_tao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ngay_cap_nhat TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_trang_thai (trang_thai),
    INDEX idx_nguoi_dung (nguoi_dung_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$conn->query("CREATE TABLE IF NOT EXISTS binh_luan_bai_viet (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bai_viet_id INT NOT NULL,
    nguoi_dung_id INT NOT NULL,
    noi_dung TEXT NOT NULL,
    ngay_tao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_bai_viet (bai_viet_id),
    INDEX idx_nguoi_dung (nguoi_dung_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Load posts with statistics
$posts = [];
$filter = $_GET['filter'] ?? 'all';
$where = '';
if ($filter === 'pending') $where = "WHERE bv.trang_thai = 'pending'";
elseif ($filter === 'approved') $where = "WHERE bv.trang_thai = 'approved'";
elseif ($filter === 'rejected') $where = "WHERE bv.trang_thai = 'rejected'";

$sql = "SELECT bv.*, nd.ho_ten, nd.email,
        (SELECT COUNT(*) FROM binh_luan_bai_viet WHERE bai_viet_id = bv.id) as so_binh_luan
        FROM bai_viet bv 
        LEFT JOIN nguoi_dung nd ON bv.nguoi_dung_id = nd.id 
        $where
        ORDER BY bv.ngay_tao DESC";
$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $posts[] = $row;
    }
}

// Statistics
$stats = [
    'total' => 0,
    'pending' => 0,
    'approved' => 0,
    'rejected' => 0
];

$total_result = $conn->query("SELECT COUNT(*) as count FROM bai_viet");
if ($total_result) $stats['total'] = $total_result->fetch_assoc()['count'];

$pending_result = $conn->query("SELECT COUNT(*) as count FROM bai_viet WHERE trang_thai = 'pending'");
if ($pending_result) $stats['pending'] = $pending_result->fetch_assoc()['count'];

$approved_result = $conn->query("SELECT COUNT(*) as count FROM bai_viet WHERE trang_thai = 'approved'");
if ($approved_result) $stats['approved'] = $approved_result->fetch_assoc()['count'];

$rejected_result = $conn->query("SELECT COUNT(*) as count FROM bai_viet WHERE trang_thai = 'rejected'");
if ($rejected_result) $stats['rejected'] = $rejected_result->fetch_assoc()['count'];

include 'header.php';
?>

<div class="page-header">
    <h1><i class="fas fa-newspaper"></i> Quản Lý Bài Viết</h1>
    <div class="breadcrumb">
        <i class="fas fa-home"></i>
        <span>Dashboard</span>
        <i class="fas fa-chevron-right"></i>
        <span>Bài Viết</span>
    </div>
</div>

<!-- Statistics Cards -->
<div class="stats-grid" style="margin-bottom: 2rem;">
    <div class="stat-card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
        <div class="stat-header">
            <div>
                <p class="stat-label" style="color: rgba(255,255,255,0.9);">Tổng Bài Viết</p>
                <h3 class="stat-value" style="color: white;"><?php echo $stats['total']; ?></h3>
            </div>
            <div class="stat-icon" style="background: rgba(255,255,255,0.2);">
                <i class="fas fa-newspaper" style="color: white;"></i>
            </div>
        </div>
    </div>
    
    <div class="stat-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white;">
        <div class="stat-header">
            <div>
                <p class="stat-label" style="color: rgba(255,255,255,0.9);">Chờ Duyệt</p>
                <h3 class="stat-value" style="color: white;"><?php echo $stats['pending']; ?></h3>
            </div>
            <div class="stat-icon" style="background: rgba(255,255,255,0.2);">
                <i class="fas fa-clock" style="color: white;"></i>
            </div>
        </div>
    </div>
    
    <div class="stat-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white;">
        <div class="stat-header">
            <div>
                <p class="stat-label" style="color: rgba(255,255,255,0.9);">Đã Duyệt</p>
                <h3 class="stat-value" style="color: white;"><?php echo $stats['approved']; ?></h3>
            </div>
            <div class="stat-icon" style="background: rgba(255,255,255,0.2);">
                <i class="fas fa-check-circle" style="color: white;"></i>
            </div>
        </div>
    </div>
</div>

<div class="table-container">
    <div class="section-header">
        <h2>Danh Sách Bài Viết (<?php echo count($posts); ?>)</h2>
        <div style="display: flex; gap: 0.5rem;">
            <a href="qlbaiviet.php?filter=all" class="btn <?php echo $filter === 'all' ? 'btn-primary' : ''; ?>">
                Tất cả
            </a>
            <a href="qlbaiviet.php?filter=pending" class="btn <?php echo $filter === 'pending' ? 'btn-primary' : ''; ?>">
                Chờ duyệt
            </a>
            <a href="qlbaiviet.php?filter=approved" class="btn <?php echo $filter === 'approved' ? 'btn-primary' : ''; ?>">
                Đã duyệt
            </a>
            <a href="qlbaiviet.php?filter=rejected" class="btn <?php echo $filter === 'rejected' ? 'btn-primary' : ''; ?>">
                Từ chối
            </a>
        </div>
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 60px;">ID</th>
                <th>Tiêu Đề</th>
                <th style="width: 150px;">Tác Giả</th>
                <th style="width: 100px;">Bình Luận</th>
                <th style="width: 120px;">Trạng Thái</th>
                <th style="width: 150px;">Ngày Đăng</th>
                <th style="width: 200px;">Thao Tác</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($posts)): ?>
            <tr>
                <td colspan="7" style="text-align: center; padding: 2rem; color: #999;">
                    <i class="fas fa-inbox" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                    <p>Chưa có bài viết nào</p>
                </td>
            </tr>
            <?php else: ?>
            <?php foreach ($posts as $post): ?>
            <tr>
                <td><strong>#<?php echo $post['id']; ?></strong></td>
                <td>
                    <strong style="color: var(--primary-color);">
                        <?php echo htmlspecialchars($post['tieu_de']); ?>
                    </strong>
                    <div style="font-size: 0.875rem; color: #666; margin-top: 0.25rem;">
                        <?php echo substr(strip_tags($post['noi_dung']), 0, 100); ?>...
                    </div>
                </td>
                <td><?php echo htmlspecialchars($post['ho_ten'] ?? 'N/A'); ?></td>
                <td>
                    <span class="badge" style="background: #3498db; color: white;">
                        <i class="fas fa-comments"></i> <?php echo $post['so_binh_luan']; ?>
                    </span>
                </td>
                <td>
                    <?php if ($post['trang_thai'] === 'pending'): ?>
                        <span class="badge" style="background: #f39c12; color: white;">Chờ duyệt</span>
                    <?php elseif ($post['trang_thai'] === 'approved'): ?>
                        <span class="badge" style="background: #27ae60; color: white;">Đã duyệt</span>
                    <?php else: ?>
                        <span class="badge" style="background: #e74c3c; color: white;">Từ chối</span>
                    <?php endif; ?>
                </td>
                <td><?php echo date('d/m/Y H:i', strtotime($post['ngay_tao'])); ?></td>
                <td>
                    <button class="btn btn-info btn-sm" onclick="viewPost(<?php echo $post['id']; ?>)" title="Xem chi tiết">
                        <i class="fas fa-eye"></i>
                    </button>
                    <?php if ($post['trang_thai'] === 'pending'): ?>
                        <button class="btn btn-success btn-sm" onclick="approvePost(<?php echo $post['id']; ?>)" title="Duyệt bài">
                            <i class="fas fa-check"></i>
                        </button>
                        <button class="btn btn-warning btn-sm" onclick="rejectPost(<?php echo $post['id']; ?>)" title="Từ chối">
                            <i class="fas fa-times"></i>
                        </button>
                    <?php elseif ($post['trang_thai'] === 'rejected'): ?>
                        <button class="btn btn-success btn-sm" onclick="approvePost(<?php echo $post['id']; ?>)" title="Duyệt bài">
                            <i class="fas fa-check"></i>
                        </button>
                    <?php endif; ?>
                    <button class="btn btn-danger btn-sm" onclick="deletePost(<?php echo $post['id']; ?>)" title="Xóa bài">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- View Post Modal -->
<div id="viewModal" class="modal">
    <div class="modal-content" style="max-width:800px;">
        <span class="close" onclick="closeModal()">&times;</span>
        <div id="postDetails"></div>
    </div>
</div>

<script>
function approvePost(id) {
    if (!confirm('Duyệt bài viết này?')) return;
    
    $.ajax({
        url: 'qlbaiviet.php',
        method: 'POST',
        dataType: 'json',
        data: {
            action: 'approve',
            id: id
        },
        success: function(response) {
            if (response.success) {
                toastr.success(response.message);
                setTimeout(() => location.reload(), 1000);
            } else {
                toastr.error(response.message);
            }
        }
    });
}

function rejectPost(id) {
    if (!confirm('Từ chối bài viết này?')) return;
    
    $.ajax({
        url: 'qlbaiviet.php',
        method: 'POST',
        dataType: 'json',
        data: {
            action: 'reject',
            id: id
        },
        success: function(response) {
            if (response.success) {
                toastr.success(response.message);
                setTimeout(() => location.reload(), 1000);
            } else {
                toastr.error(response.message);
            }
        }
    });
}

function deletePost(id) {
    if (!confirm('Xóa bài viết này? Không thể khôi phục!')) return;
    
    $.ajax({
        url: 'qlbaiviet.php',
        method: 'POST',
        dataType: 'json',
        data: {
            action: 'delete',
            id: id
        },
        success: function(response) {
            if (response.success) {
                toastr.success(response.message);
                setTimeout(() => location.reload(), 1000);
            } else {
                toastr.error(response.message);
            }
        }
    });
}

function viewPost(id) {
    $.ajax({
        url: 'qlbaiviet.php',
        method: 'POST',
        dataType: 'json',
        data: {
            action: 'view',
            id: id
        },
        success: function(response) {
            if (response.success) {
                const post = response.post;
                let statusBadge = '';
                let statusColor = '';
                
                if (post.trang_thai === 'pending') {
                    statusBadge = '<span class="badge" style="background: #f39c12; color: white;">Chờ duyệt</span>';
                    statusColor = '#f39c12';
                } else if (post.trang_thai === 'approved') {
                    statusBadge = '<span class="badge" style="background: #27ae60; color: white;">Đã duyệt</span>';
                    statusColor = '#27ae60';
                } else {
                    statusBadge = '<span class="badge" style="background: #e74c3c; color: white;">Từ chối</span>';
                    statusColor = '#e74c3c';
                }
                
                const html = `
                    <h2 style="color: var(--primary-color); margin-bottom: 1rem;">
                        <i class="fas fa-newspaper"></i> Chi Tiết Bài Viết
                    </h2>
                    <div style="background: #f8f9fa; padding: 1.5rem; border-radius: 8px; margin-bottom: 1.5rem;">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                            <div>
                                <strong style="color: #666;">ID:</strong> #${post.id}
                            </div>
                            <div>
                                <strong style="color: #666;">Trạng thái:</strong> ${statusBadge}
                            </div>
                            <div>
                                <strong style="color: #666;">Tác giả:</strong> ${post.ho_ten || 'N/A'}
                            </div>
                            <div>
                                <strong style="color: #666;">Email:</strong> ${post.email || 'N/A'}
                            </div>
                            <div>
                                <strong style="color: #666;">Ngày đăng:</strong> ${post.ngay_tao_formatted}
                            </div>
                            <div>
                                <strong style="color: #666;">Cập nhật:</strong> ${post.ngay_cap_nhat_formatted}
                            </div>
                        </div>
                    </div>
                    
                    <div style="margin-bottom: 1.5rem;">
                        <h3 style="color: #333; margin-bottom: 0.5rem;">Tiêu đề:</h3>
                        <div style="background: white; padding: 1rem; border-left: 4px solid ${statusColor}; border-radius: 4px;">
                            ${post.tieu_de}
                        </div>
                    </div>
                    
                    <div style="margin-bottom: 1.5rem;">
                        <h3 style="color: #333; margin-bottom: 0.5rem;">Nội dung:</h3>
                        <div style="background: white; padding: 1.5rem; border: 1px solid #dee2e6; border-radius: 8px; line-height: 1.8;">
                            ${post.noi_dung.replace(/\n/g, '<br>')}
                        </div>
                    </div>
                    
                    <div style="margin-bottom: 1.5rem;">
                        <h3 style="color: #333; margin-bottom: 0.5rem;">
                            <i class="fas fa-comments"></i> Bình luận (${post.comments.length})
                        </h3>
                        <div style="max-height: 300px; overflow-y: auto;">
                            ${post.comments.length > 0 ? post.comments.map(c => `
                                <div style="background: #f8f9fa; padding: 1rem; border-radius: 8px; margin-bottom: 0.5rem;">
                                    <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                                        <strong style="color: var(--primary-color);">
                                            <i class="fas fa-user-circle"></i> ${c.ho_ten || 'Ẩn danh'}
                                        </strong>
                                        <small style="color: #666;">${c.ngay_tao_formatted}</small>
                                    </div>
                                    <div style="color: #333;">${c.noi_dung.replace(/\n/g, '<br>')}</div>
                                </div>
                            `).join('') : '<p style="text-align: center; color: #999; padding: 2rem;">Chưa có bình luận</p>'}
                        </div>
                    </div>
                    
                    <div style="text-align: right; margin-top: 1.5rem;">
                        <button class="btn btn-secondary" onclick="closeModal()">
                            <i class="fas fa-times"></i> Đóng
                        </button>
                    </div>
                `;
                
                document.getElementById('postDetails').innerHTML = html;
                document.getElementById('viewModal').style.display = 'block';
            } else {
                toastr.error(response.message);
            }
        },
        error: function() {
            toastr.error('Có lỗi xảy ra!');
        }
    });
}

function closeModal() {
    document.getElementById('viewModal').style.display = 'none';
}

// Click outside modal to close
window.onclick = function(event) {
    const modal = document.getElementById('viewModal');
    if (event.target == modal) {
        closeModal();
    }
}
</script>

<style>
.badge {
    display: inline-block;
    padding: 0.4em 0.8em;
    font-size: 0.875em;
    font-weight: 700;
    border-radius: 0.35rem;
}
</style>

<?php include 'footer.php'; ?>
