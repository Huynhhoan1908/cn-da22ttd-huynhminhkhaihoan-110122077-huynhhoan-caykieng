<?php
// admin/qlchude.php
// Trang quản lý chủ đề
include 'config.php';
include 'header.php';

// Xử lý thêm chủ đề mới
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ten_chude'])) {
    $ten_chude = trim($_POST['ten_chude']);
    $mo_ta = trim($_POST['mo_ta'] ?? '');
    if ($ten_chude !== '') {
        $stmt = $conn->prepare("INSERT INTO chude (ten_chude, mo_ta) VALUES (?, ?)");
        if ($stmt) {
            $stmt->bind_param('ss', $ten_chude, $mo_ta);
            if ($stmt->execute()) {
                echo '<script>location.href="qlchude.php";</script>';
                exit();
            } else {
                echo '<div style="color:red;">Lỗi thêm chủ đề: ' . $stmt->error . '</div>';
            }
            $stmt->close();
        } else {
            echo '<div style="color:red;">Lỗi prepare: ' . $conn->error . '</div>';
        }
    } else {
        echo '<div style="color:red;">Tên chủ đề không được để trống!</div>';
    }
}

// Xử lý xóa chủ đề
if (isset($_GET['delete_id'])) {
    $delete_id = (int)$_GET['delete_id'];
    if ($delete_id > 0) {
        $stmt = $conn->prepare("DELETE FROM chude WHERE id = ?");
        $stmt->bind_param('i', $delete_id);
        if ($stmt->execute()) {
            echo '<script>location.href="qlchude.php";</script>';
            exit();
        } else {
            echo '<div style="color:red;">Lỗi xóa chủ đề: ' . $stmt->error . '</div>';
        }
        $stmt->close();
    }
}

// Xử lý cập nhật chủ đề
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_id'])) {
    $edit_id = (int)$_POST['edit_id'];
    $ten_chude = trim($_POST['ten_chude_edit'] ?? '');
    $mo_ta = trim($_POST['mo_ta_edit'] ?? '');
    if ($edit_id > 0 && $ten_chude !== '') {
        $stmt = $conn->prepare("UPDATE chude SET ten_chude = ?, mo_ta = ? WHERE id = ?");
        $stmt->bind_param('ssi', $ten_chude, $mo_ta, $edit_id);
        if ($stmt->execute()) {
            echo '<script>location.href="qlchude.php";</script>';
            exit();
        } else {
            echo '<div style="color:red;">Lỗi cập nhật chủ đề: ' . $stmt->error . '</div>';
        }
        $stmt->close();
    } else {
        echo '<div style="color:red;">Tên chủ đề không được để trống!</div>';
    }
}
$current_page = 'topics';
$page_title = 'Quản Lý Chủ Đề - HuynhHoan';

// Tạo bảng chủ đề nếu chưa có, xuất lỗi nếu có
$sql_create = "CREATE TABLE IF NOT EXISTS chude (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ten_chude VARCHAR(255) NOT NULL,
    mo_ta TEXT,
    ngay_tao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ngay_cap_nhat TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
if (!$conn->query($sql_create)) {
    echo '<div style="color:red;padding:10px;">Lỗi tạo bảng chủ đề: ' . $conn->error . '</div>';
}

// Lấy danh sách chủ đề
$topics = [];
$result = $conn->query("SELECT * FROM chude ORDER BY ngay_tao DESC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $topics[] = $row;
    }
}
?>

<!-- Section Header -->
<div class="section-header">
    <div style="display: flex; align-items: center; gap: 10px;">
        <h2 style="margin:0;">Quản Lý Chủ Đề</h2>
    </div>
    <div style="display: flex; gap: 1rem; align-items: center;">
        <div class="search-container">
            <i class="fas fa-search search-icon"></i>
            <input type="text" id="searchTopics" class="search-input" placeholder="Tìm kiếm chủ đề...">
        </div>
        <button class="btn btn-add" onclick="moModalChude()">
            <i class="fas fa-plus"></i>
            Thêm Chủ Đề
        </button>
    </div>
</div>

<!-- Table -->
<div class="table-container">
    <table class="data-table">
        <thead>
            <tr>
                <th style="width:60px;">ID</th>
                <th>Tên chủ đề</th>
                <th>Mô tả</th>
                <th style="width:150px;">Ngày tạo</th>
                <th style="width:150px;">Cập nhật</th>
                <th style="width:120px;">Thao tác</th>
            </tr>
        </thead>
        <tbody id="topicsTableBody">
            <?php if (empty($topics)): ?>
            <tr>
                <td colspan="6" style="text-align:center; color:#999;">Chưa có chủ đề nào</td>
            </tr>
            <?php else: ?>
            <?php foreach ($topics as $cd): ?>
            <tr>
                <td>#<?php echo $cd['id']; ?></td>
                <td><?php echo htmlspecialchars($cd['ten_chude']); ?></td>
                <td><?php echo htmlspecialchars($cd['mo_ta']); ?></td>
                <td><?php echo date('d/m/Y H:i', strtotime($cd['ngay_tao'])); ?></td>
                <td><?php echo date('d/m/Y H:i', strtotime($cd['ngay_cap_nhat'])); ?></td>
                <td>
                    <button class="btn btn-warning btn-sm sua-chude" data-id="<?php echo $cd['id']; ?>" data-ten="<?php echo htmlspecialchars(addslashes($cd['ten_chude'])); ?>" data-mota="<?php echo htmlspecialchars(addslashes($cd['mo_ta'])); ?>" title="Sửa"><i class="fas fa-edit"></i></button>
                    <a href="qlchude.php?delete_id=<?php echo $cd['id']; ?>" onclick="return confirm('Xác nhận xóa chủ đề này?');" class="btn btn-danger btn-sm" title="Xóa"><i class="fas fa-trash"></i></a>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Modal Thêm/Sửa Chủ Đề -->
<div id="modalChude" class="modal">
    <div class="modal-content" style="max-width:420px;">
        <div class="modal-title-container">
            <h2 id="modalTitle">Thêm Chủ Đề Mới</h2>
        </div>
        <form id="formChude" method="POST">
            <input type="hidden" name="edit_id" id="edit_id">
            <div class="form-group">
                <label for="ten_chude">Tên chủ đề <span class="required">*</span></label>
                <input type="text" name="ten_chude" id="ten_chude" required>
            </div>
            <div class="form-group">
                <label for="mo_ta">Mô tả</label>
                <textarea name="mo_ta" id="mo_ta" rows="2"></textarea>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Lưu</button>
                <button type="button" class="btn btn-secondary" onclick="dongModalChude()">Hủy</button>
            </div>
        </form>
    </div>
</div>

<style>
.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 2px 12px rgba(61,107,63,0.07);
    padding: 1.2rem 2rem;
}
.section-header h2 {
    font-size: 2rem;
    font-weight: 700;
    color: #3d6b3f;
    margin: 0;
}
.btn.btn-add {
    background: #10b981;
    color: #fff;
    font-weight: 700;
    font-size: 1.1rem;
    border: none;
    border-radius: 10px;
    padding: 0.7rem 2.2rem;
    box-shadow: 0 2px 8px rgba(16,185,129,0.08);
    transition: background 0.2s;
    cursor: pointer;
}
.btn.btn-add:hover {
    background: #059669;
}
.search-container {
    position: relative;
    display: flex;
    align-items: center;
}
.search-input {
    padding: 0.6rem 2.2rem 0.6rem 2.2rem;
    border-radius: 8px;
    border: 1.5px solid #e2e8f0;
    font-size: 1rem;
    background: #f9fafb;
    transition: border-color 0.2s;
    width: 220px;
}
.search-input:focus {
    border-color: #10b981;
    outline: none;
}
.search-icon {
    position: absolute;
    left: 10px;
    color: #a0aec0;
    font-size: 1.1rem;
}
.data-table {
    width: 100%;
    border-collapse: collapse;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 2px 12px rgba(61,107,63,0.07);
    overflow: hidden;
}
.data-table th, .data-table td {
    padding: 1rem 1.2rem;
    border-bottom: 1px solid #f1f1f1;
    text-align: left;
}
.data-table th {
    background: #f9fafb;
    color: #3d6b3f;
    font-weight: 700;
    font-size: 1.05rem;
}
.data-table tr:hover {
    background: #f0fdf4;
}
.btn {
    display: inline-flex;
    align-items: center;
    gap: 0.4em;
    border: none;
    border-radius: 8px;
    padding: 0.5em 1.2em;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.2s, box-shadow 0.2s;
}
.btn-sm { font-size: 0.95rem; padding: 0.4em 1em; }
.btn-primary { background: #3d6b3f; color: #fff; }
.btn-primary:hover { background: #2d5a2d; }
.btn-warning { background: #f59e42; color: #fff; }
.btn-warning:hover { background: #e67e22; }
.btn-danger { background: #e74c3c; color: #fff; }
.btn-danger:hover { background: #c0392b; }
.btn-secondary { background: #e2e8f0; color: #3d6b3f; }
.btn-secondary:hover { background: #d1d5db; }
.modal {
    display: none;
    position: fixed;
    top: 0; left: 0; width: 100vw; height: 100vh;
    background: rgba(0,0,0,0.18);
    z-index: 9999;
    align-items: center; justify-content: center;
}
.modal-content {
    background: #fff;
    border-radius: 16px;
    box-shadow: 0 8px 32px rgba(61,107,63,0.13);
    min-width: 320px; max-width: 95vw;
    padding: 2.2rem 2.5rem;
    position: relative;
    border: 1.5px solid #e8e0c5;
}
.modal-title-container h2 {
    color: #3d6b3f;
    font-size: 1.3rem;
    font-weight: 700;
    margin-bottom: 1.2rem;
}
.form-group { margin-bottom: 1.3rem; }
.form-group label { font-weight: 600; color: #2d3748; margin-bottom: 0.5rem; display: block; }
.form-group input, .form-group textarea {
    width: 100%;
    padding: 0.8rem 1rem;
    border: 2px solid #e2e8f0;
    border-radius: 10px;
    font-size: 1rem;
    background: #f9fafb;
    transition: border-color 0.2s, box-shadow 0.2s;
}
.form-group input:focus, .form-group textarea:focus {
    border-color: #3d6b3f;
    background: #fff;
    outline: none;
    box-shadow: 0 2px 8px rgba(61,107,63,0.06);
}
.form-actions {
    display: flex;
    gap: 1rem;
    margin-top: 1.2rem;
}
.required { color: red; }
@media (max-width: 600px) {
    .section-header, .modal-content { padding: 1.2rem 0.7rem; }
}
</style>

<script>
function moModalChude() {
    $('#modalTitle').text('Thêm Chủ Đề Mới');
    $('#formChude')[0].reset();
    $('#edit_id').val('');
    $('#modalChude').show();
}
function dongModalChude() {
    $('#modalChude').hide();
}
// Sửa chủ đề
$(document).on('click', '.sua-chude', function() {
    const id = $(this).data('id');
    const ten = $(this).data('ten');
    const mota = $(this).data('mota');
    $('#modalTitle').text('Sửa Chủ Đề');
    $('#edit_id').val(id);
    $('#ten_chude').val(ten);
    $('#mo_ta').val(mota);
    $('#modalChude').show();
});
// Đóng modal khi bấm ngoài
$(document).mouseup(function(e){
    var modal = $("#modalChude .modal-content");
    if($('#modalChude').is(':visible') && !modal.is(e.target) && modal.has(e.target).length === 0) {
        dongModalChude();
    }
});
// Tìm kiếm chủ đề
$('#searchTopics').on('input', function() {
    const q = $(this).val().toLowerCase();
    $('#topicsTableBody tr').each(function() {
        const text = $(this).text().toLowerCase();
        $(this).toggle(text.indexOf(q) > -1);
    });
});
// Submit form thêm/sửa chủ đề
$('#formChude').on('submit', function(e) {
    // Nếu có edit_id thì là sửa, không thì là thêm
    if($('#edit_id').val()) {
        // Sửa: đổi name input cho đúng
        $('#ten_chude').attr('name', 'ten_chude_edit');
        $('#mo_ta').attr('name', 'mo_ta_edit');
    } else {
        $('#ten_chude').attr('name', 'ten_chude');
        $('#mo_ta').attr('name', 'mo_ta');
    }
});
</script>

<?php include 'footer.php'; ?>
