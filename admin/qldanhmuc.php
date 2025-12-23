<?php
require_once 'config.php';

$current_page = 'categories';
$page_title = 'Quản Lý Danh Mục - HuynhHoan';

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    if ($_POST['action'] === 'add' || $_POST['action'] === 'edit') {
        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        
        if (empty($name)) {
            echo json_encode(['success' => false, 'message' => 'Tên danh mục không được trống']);
            exit();
        }
        
        if ($_POST['action'] === 'add') {
            $stmt = $conn->prepare("INSERT INTO danh_muc (ten_san_pham) VALUES (?)");
            $stmt->bind_param("s", $name);
        } else {
            $stmt = $conn->prepare("UPDATE danh_muc SET ten_san_pham = ? WHERE id = ?");
            $stmt->bind_param("si", $name, $id);
        }
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Lưu thành công']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Lỗi lưu dữ liệu']);
        }
        exit();
    }
    
    if ($_POST['action'] === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        
        if ($conn->query("DELETE FROM danh_muc WHERE id = $id")) {
            echo json_encode(['success' => true, 'message' => 'Xóa thành công']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Lỗi xóa']);
        }
        exit();
    }
}

// Load categories with product count
$categories = [];
// Đếm sản phẩm qua bảng liên kết san_pham_danh_muc
$result = $conn->query("
    SELECT dm.*, COUNT(spdm.san_pham_id) as so_san_pham
    FROM danh_muc dm
    LEFT JOIN san_pham_danh_muc spdm ON dm.id = spdm.danh_muc_id
    GROUP BY dm.id
    ORDER BY dm.ten_san_pham
");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $row['ten_danh_muc'] = $row['ten_san_pham']; // Map tên cột
        $categories[] = $row;
    }
}

include 'header.php';
?>

<div class="page-header">
    <h1><i class="fas fa-tags"></i> Quản Lý Danh Mục</h1>
    <div class="breadcrumb">
        <i class="fas fa-home"></i>
        <span>Dashboard</span>
        <i class="fas fa-chevron-right"></i>
        <span>Danh Mục</span>
    </div>
</div>

<div class="table-container">
    <div class="section-header">
        <h2>Danh Sách Danh Mục (<?php echo count($categories); ?>)</h2>
        <button class="btn btn-primary" onclick="showCategoryModal()">
            <i class="fas fa-plus"></i> Thêm Danh Mục Mới
        </button>
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 80px;">ID</th>
                <th>Tên Danh Mục</th>
                <th style="width: 150px;">Số Sản Phẩm</th>
                <th style="width: 180px;">Thao Tác</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($categories)): ?>
            <tr>
                <td colspan="4" style="text-align: center; padding: 2rem; color: #999;">
                    <i class="fas fa-inbox" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                    <p>Chưa có danh mục nào. Nhấn "Thêm Danh Mục Mới" để bắt đầu!</p>
                </td>
            </tr>
            <?php else: ?>
            <?php foreach ($categories as $cat): ?>
            <tr>
                <td><strong>#<?php echo $cat['id']; ?></strong></td>
                <td>
                    <strong style="color: var(--primary-color); font-size: 1.05em;">
                        <?php echo htmlspecialchars($cat['ten_danh_muc']); ?>
                    </strong>
                </td>
                <td>
                    <span class="badge" style="background: #3498db; color: white; padding: 0.4em 0.8em; border-radius: 20px;">
                        <i class="fas fa-box"></i> <?php echo $cat['so_san_pham']; ?> sản phẩm
                    </span>
                </td>
                <td>
                    <button class="btn btn-info btn-sm" onclick='editCategory(<?php echo json_encode($cat); ?>)' title="Sửa danh mục">
                        <i class="fas fa-edit"></i> Sửa
                    </button>
                    <button class="btn btn-danger btn-sm" onclick="deleteCategory(<?php echo $cat['id']; ?>)" title="Xóa danh mục">
                        <i class="fas fa-trash"></i> Xóa
                    </button>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Category Modal -->
<div id="categoryModal" class="modal">
    <div class="modal-content" style="max-width:500px;">
        <span class="close" onclick="closeModal()">&times;</span>
        <h2 id="modalTitle"><i class="fas fa-plus-circle"></i> Thêm Danh Mục</h2>
        <form id="categoryForm">
            <input type="hidden" id="categoryId" name="id">
            <div class="form-group">
                <label><i class="fas fa-tag"></i> Tên Danh Mục *</label>
                <input type="text" id="categoryName" name="name" placeholder="Nhập tên danh mục (VD: Cây Cảnh, Cây Ăn Trái...)" required style="padding: 0.75rem; font-size: 1rem;">
            </div>
            <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                <button type="submit" class="btn btn-primary" style="flex: 1;">
                    <i class="fas fa-save"></i> Lưu Danh Mục
                </button>
                <button type="button" class="btn" onclick="closeModal()" style="flex: 1; background: #95a5a6;">
                    <i class="fas fa-times"></i> Hủy
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function showCategoryModal() {
    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-plus-circle"></i> Thêm Danh Mục Mới';
    document.getElementById('categoryForm').reset();
    document.getElementById('categoryId').value = '';
    document.getElementById('categoryModal').style.display = 'block';
}

function editCategory(cat) {
    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-edit"></i> Sửa Danh Mục';
    document.getElementById('categoryId').value = cat.id;
    document.getElementById('categoryName').value = cat.ten_danh_muc;
    document.getElementById('categoryModal').style.display = 'block';
}

function closeModal() {
    document.getElementById('categoryModal').style.display = 'none';
}

document.getElementById('categoryForm').onsubmit = function(e) {
    e.preventDefault();
    const id = document.getElementById('categoryId').value;
    const name = document.getElementById('categoryName').value.trim();
    
    if (!name) {
        toastr.error('Vui lòng nhập tên danh mục!');
        return;
    }
    
    $.ajax({
        url: 'qldanhmuc.php',
        method: 'POST',
        dataType: 'json',
        data: {
            action: id ? 'edit' : 'add',
            id: id,
            name: name
        },
        success: function(response) {
            if (response.success) {
                toastr.success(response.message);
                setTimeout(() => location.reload(), 1000);
            } else {
                toastr.error(response.message);
            }
        },
        error: function() {
            toastr.error('Lỗi kết nối! Vui lòng thử lại.');
        }
    });
};

function deleteCategory(id) {
    if (!confirm('Bạn có chắc muốn xóa danh mục này?\n\nLưu ý: Các sản phẩm thuộc danh mục này có thể bị ảnh hưởng!')) return;
    
    $.ajax({
        url: 'qldanhmuc.php',
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
        },
        error: function() {
            toastr.error('Lỗi kết nối! Vui lòng thử lại.');
        }
    });
}
</script>

<?php include 'footer.php'; ?>
