<?php
require_once 'config.php';

// Page configuration
$current_page = 'products';
$page_title = 'Quản Lý Sản Phẩm';

// ============================================================
// AJAX HANDLERS (must be before any HTML output)
// ============================================================

// Get product details
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'get_product') {
    header('Content-Type: application/json');
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID không hợp lệ']);
        exit();
    }
    $stmt = $conn->prepare("SELECT * FROM san_pham WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();
    $product = $res->fetch_assoc();
    
    // Lấy danh sách danh mục của sản phẩm
    $stmt_dm = $conn->prepare("SELECT danh_muc_id FROM san_pham_danh_muc WHERE san_pham_id = ?");
    $stmt_dm->bind_param("i", $id);
    $stmt_dm->execute();
    $res_dm = $stmt_dm->get_result();
    $categories = [];
    while ($row = $res_dm->fetch_assoc()) {
        $categories[] = $row['danh_muc_id'];
    }
    $product['categories'] = $categories;
    
    echo json_encode(['success' => true, 'product' => $product]);
    exit();
}

// Add/Edit product
if(isset($_POST['them_san_pham'])) {
    try {
        header('Content-Type: application/json');
        
        $san_pham_id = isset($_POST['san_pham_id']) ? (int)$_POST['san_pham_id'] : 0;
        $ma_san_pham = trim($_POST['ma_san_pham'] ?? '');
        $ten_san_pham = trim($_POST['ten_san_pham'] ?? '');
        $danh_muc_ids = isset($_POST['danh_muc_ids']) ? $_POST['danh_muc_ids'] : [];
        $gia = (float)($_POST['gia'] ?? 0);
        $gia_nhap = (float)($_POST['gia_nhap'] ?? 0); // <--- THÊM DÒNG NÀY
        $so_luong = (int)($_POST['so_luong'] ?? 0);
        $mo_ta = trim($_POST['mo_ta'] ?? '');
        
        if(empty($ma_san_pham) || empty($ten_san_pham) || empty($danh_muc_ids)) {
            throw new Exception("Vui lòng nhập đầy đủ thông tin bắt buộc và chọn ít nhất 1 danh mục.");
        }
        if($gia <= 0) throw new Exception("Giá phải lớn hơn 0");
        if($so_luong < 0) throw new Exception("Số lượng không thể âm");
        
        // Xử lý upload hình ảnh
        $hinh_anh = '';
        $uploaded_new_image = false;
        if(isset($_FILES['hinh_anh']) && $_FILES['hinh_anh']['error'] == 0) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            if(!in_array($_FILES['hinh_anh']['type'], $allowed_types)) {
                throw new Exception("Chỉ cho phép file ảnh JPG, PNG, GIF");
            }
            if($_FILES['hinh_anh']['size'] > MAX_FILE_SIZE) {
                throw new Exception("Kích thước ảnh tối đa 5MB");
            }
            if (!file_exists(UPLOAD_DIR)) {
                mkdir(UPLOAD_DIR, 0777, true);
            }
            $file_extension = strtolower(pathinfo($_FILES['hinh_anh']['name'], PATHINFO_EXTENSION));
            $new_filename = uniqid('p_') . '.' . $file_extension;
            $target_file = UPLOAD_DIR . $new_filename;
            if (!move_uploaded_file($_FILES['hinh_anh']['tmp_name'], $target_file)) {
                throw new Exception("Không thể lưu ảnh lên server");
            }
            chmod($target_file, 0644);
            $hinh_anh = $new_filename;
            $uploaded_new_image = true;
        }
        
        if ($san_pham_id > 0) {
            // UPDATE
            if ($uploaded_new_image) {
                $stmt = $conn->prepare("SELECT hinh_anh FROM san_pham WHERE id = ?");
                $stmt->bind_param("i", $san_pham_id);
                $stmt->execute();
                $r = $stmt->get_result()->fetch_assoc();
                if ($r && !empty($r['hinh_anh'])) {
                    $old = UPLOAD_DIR . $r['hinh_anh'];
                    if (file_exists($old)) @unlink($old);
                }
            }
            if ($uploaded_new_image) {
                $sql = "UPDATE san_pham SET ma_san_pham=?, ten_san_pham=?, gia=?, gia_nhap=?, so_luong=?, mo_ta=?, hinh_anh=? WHERE id=?";
                $stmt = $conn->prepare($sql);
                // Đúng thứ tự: s = string, d = double/float, i = integer
                $stmt->bind_param("ssddissi", $ma_san_pham, $ten_san_pham, $gia, $gia_nhap, $so_luong, $mo_ta, $hinh_anh, $san_pham_id);
            } else {
                $sql = "UPDATE san_pham SET ma_san_pham=?, ten_san_pham=?, gia=?, gia_nhap=?, so_luong=?, mo_ta=? WHERE id=?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssddisi", $ma_san_pham, $ten_san_pham, $gia, $gia_nhap, $so_luong, $mo_ta, $san_pham_id);
            }
            if(!$stmt->execute()) throw new Exception("Lỗi cập nhật sản phẩm: " . $stmt->error);
            
            // Xóa các liên kết cũ
            $conn->query("DELETE FROM san_pham_danh_muc WHERE san_pham_id = {$san_pham_id}");
            
            // Thêm các liên kết mới cho danh mục
            $stmt_dm = $conn->prepare("INSERT INTO san_pham_danh_muc (san_pham_id, danh_muc_id) VALUES (?, ?)");
            foreach ($danh_muc_ids as $dm_id) {
                $dm_id = (int)$dm_id;
                if ($dm_id > 0) {
                    $stmt_dm->bind_param("ii", $san_pham_id, $dm_id);
                    $stmt_dm->execute();
                }
            }
            
            echo json_encode(['success' => true, 'message' => 'Cập nhật sản phẩm thành công']);
            exit();
        } else {
            // INSERT
            $sql = "INSERT INTO san_pham (ma_san_pham, ten_san_pham, gia, so_luong, mo_ta, hinh_anh) 
                    VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssisss", $ma_san_pham, $ten_san_pham, $gia, $so_luong, $mo_ta, $hinh_anh);
            if(!$stmt->execute()) {
                throw new Exception("Lỗi thêm sản phẩm: " . $stmt->error);
            }
            
            $new_product_id = $conn->insert_id;
            
            // Thêm các liên kết cho danh mục
            $stmt_dm = $conn->prepare("INSERT INTO san_pham_danh_muc (san_pham_id, danh_muc_id) VALUES (?, ?)");
            foreach ($danh_muc_ids as $dm_id) {
                $dm_id = (int)$dm_id;
                if ($dm_id > 0) {
                    $stmt_dm->bind_param("ii", $new_product_id, $dm_id);
                    $stmt_dm->execute();
                }
            }
            
            // Tự động tạo thông báo sản phẩm mới (không throw lỗi nếu notification lỗi)
            $category_result = $conn->query("SELECT ten_danh_muc FROM danh_muc WHERE id = {$danh_muc_ids[0]}");
            $category_name = 'Sản phẩm';
            if ($category_result && $cat = $category_result->fetch_assoc()) {
                $category_name = $cat['ten_danh_muc'];
            }
            echo json_encode(['success' => true, 'message' => 'Thêm sản phẩm thành công']);
            // Gửi thông báo cho tất cả user, nếu lỗi chỉ ghi log
            try {
                if (file_exists(dirname(__DIR__) . '/notification_helpers.php')) {
                    require_once dirname(__DIR__) . '/notification_helpers.php';
                    notify_new_product_all_users($new_product_id, $ten_san_pham, $category_name);
                }
            } catch (Exception $ex) {
                error_log('Lỗi gửi notification: ' . $ex->getMessage());
            }
            exit();
        }
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit();
    }
}

// Delete product
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'xoa_san_pham') {
    header('Content-Type: application/json');
    $id = isset($_POST['san_pham_id']) ? (int)$_POST['san_pham_id'] : 0;
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID không hợp lệ']);
        exit();
    }

    try {
        // Get product image before deleting
        $stmt = $conn->prepare("SELECT hinh_anh FROM san_pham WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $res = $stmt->get_result();
        $prod = $res->fetch_assoc();

        // Delete related records first (foreign key constraints)
        $conn->query("DELETE FROM san_pham_danh_muc WHERE san_pham_id = $id");
        $conn->query("DELETE FROM san_pham_chuc_nang WHERE san_pham_id = $id");
        
        // Delete from chi_tiet_don_hang if exists
        $conn->query("DELETE FROM chi_tiet_don_hang WHERE san_pham_id = $id");

        // Now delete the product
        $stmt = $conn->prepare("DELETE FROM san_pham WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            // Delete image file
            if ($prod && !empty($prod['hinh_anh'])) {
                $file = UPLOAD_DIR . $prod['hinh_anh'];
                if (file_exists($file)) @unlink($file);
            }
            echo json_encode(['success' => true, 'message' => 'Xóa sản phẩm thành công']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Lỗi khi xóa sản phẩm: ' . $stmt->error]);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
    }
    exit();
}

// Lock product
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'khoa_san_pham') {
    header('Content-Type: application/json');
    $id = isset($_POST['san_pham_id']) ? (int)$_POST['san_pham_id'] : 0;
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID không hợp lệ']);
        exit();
    }
    
    // Check if trang_thai column exists, if not add it
    $result = $conn->query("SHOW COLUMNS FROM san_pham LIKE 'trang_thai'");
    if ($result->num_rows == 0) {
        $conn->query("ALTER TABLE san_pham ADD COLUMN trang_thai TINYINT(1) NOT NULL DEFAULT 1");
    }
    
    $stmt = $conn->prepare("UPDATE san_pham SET trang_thai = 0 WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Khóa sản phẩm thành công']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Lỗi khi khóa sản phẩm']);
    }
    exit();
}

// Unlock product
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'mo_khoa_san_pham') {
    header('Content-Type: application/json');
    $id = isset($_POST['san_pham_id']) ? (int)$_POST['san_pham_id'] : 0;
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID không hợp lệ']);
        exit();
    }
    
    $stmt = $conn->prepare("UPDATE san_pham SET trang_thai = 1 WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Mở khóa sản phẩm thành công']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Lỗi khi mở khóa sản phẩm']);
    }
    exit();
}

// ============================================================
// DATA LOADING
// ============================================================

// Detect column names
$sp_danhmuc_col = find_column_like($conn, 'san_pham', ['danh_muc_id','danh_muc','danhmuc_id','category_id']);
$dm_name_col = find_column_like($conn, 'danh_muc', ['ten_danh_muc','ten_san_pham','ten','name','ten_dm','ten_sanpham']);

// Load categories list
$danh_muc_list = [];
if ($dm_name_col) {
    $sql = "SELECT id, `{$dm_name_col}` AS ten_danh_muc FROM danh_muc";
    $result = $conn->query($sql);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $danh_muc_list[] = $row;
        }
        $result->free();
    }
}

// Load products list with category names
$san_pham_list = [];
$sql = "SELECT sp.* FROM san_pham sp ORDER BY sp.id DESC";
$result = $conn->query($sql);
if (!$result) {
    die('Query error: ' . $conn->error);
}
while ($row = $result->fetch_assoc()) {
    // Get all categories for this product
    $product_id = $row['id'];
    $cat_sql = "SELECT dm.ten_san_pham 
                FROM san_pham_danh_muc spdm 
                JOIN danh_muc dm ON spdm.danh_muc_id = dm.id 
                WHERE spdm.san_pham_id = {$product_id}";
    $cat_result = $conn->query($cat_sql);
    $categories = [];
    if ($cat_result) {
        while ($cat_row = $cat_result->fetch_assoc()) {
            $categories[] = $cat_row['ten_san_pham'];
        }
        $cat_result->free();
    }
    $row['ten_danh_muc'] = !empty($categories) ? implode(', ', $categories) : 'Chưa phân loại';
    $san_pham_list[] = $row;
}
$result->free();

// Include header
require_once 'header.php';
?>

<style>
.multi-select-wrapper {
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 15px;
    background: #f9f9f9;
    max-height: 250px;
    overflow-y: auto;
}

.multi-select-wrapper::-webkit-scrollbar {
    width: 8px;
}

.multi-select-wrapper::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 4px;
}

.multi-select-wrapper::-webkit-scrollbar-thumb {
    background: #888;
    border-radius: 4px;
}

.multi-select-wrapper::-webkit-scrollbar-thumb:hover {
    background: #555;
}

.checkbox-item {
    display: flex;
    align-items: center;
    padding: 8px 12px;
    margin: 4px 0;
    background: white;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.2s ease;
    border: 1px solid transparent;
}

.checkbox-item:hover {
    background: #e8f4f8;
    border-color: #4CAF50;
    transform: translateX(3px);
}

.checkbox-item input[type="checkbox"] {
    width: 18px;
    height: 18px;
    margin-right: 10px;
    cursor: pointer;
    accent-color: #4CAF50;
}

.checkbox-item label {
    cursor: pointer;
    flex: 1;
    margin: 0;
    user-select: none;
    font-size: 14px;
}

.checkbox-item input[type="checkbox"]:checked + label {
    color: #4CAF50;
    font-weight: 600;
}

.select-all-wrapper {
    display: flex;
    align-items: center;
    padding: 10px 12px;
    margin-bottom: 10px;
    background: #e3f2fd;
    border-radius: 6px;
    border: 1px solid #2196F3;
}

.select-all-wrapper input[type="checkbox"] {
    width: 18px;
    height: 18px;
    margin-right: 10px;
    cursor: pointer;
    accent-color: #2196F3;
}

.select-all-wrapper label {
    cursor: pointer;
    margin: 0;
    font-weight: 600;
    color: #2196F3;
    user-select: none;
}

.selected-count {
    display: inline-block;
    background: #4CAF50;
    color: white;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 12px;
    margin-left: 8px;
    font-weight: bold;
}
</style>

<!-- Page Header -->
<div class="section-header">
    <div style="display: flex; align-items: center; gap: 10px;">
        <h2><?php echo htmlspecialchars($page_title); ?></h2>
    </div>
    <div style="display: flex; gap: 1rem; align-items: center;">
        <div class="search-container">
            <i class="fas fa-search search-icon"></i>
            <input type="text" id="searchProducts" class="search-input" placeholder="Tìm kiếm sản phẩm...">
        </div>
        <button class="btn btn-add" onclick="moModalSanPham()">
            <i class="fas fa-plus"></i>
            Thêm Sản Phẩm
        </button>
    </div>
</div>

<!-- Products Table -->
<div class="table-container">
    <table class="data-table">
        <thead>
            <tr>
                <th>Hình Ảnh</th>
                <th>Mã SP</th>
                <th>Tên Sản Phẩm</th>
                <th>Danh Mục</th>
                <th>Giá Vốn</th>
                <th>Giá Bán</th>
                <th>Số Lượng</th>
                <th>Trạng Thái</th>
                <th>Thao Tác</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($san_pham_list as $sp): ?>
            <tr>
                <td>
                    <?php if(!empty($sp['hinh_anh'])): ?>
                        <img src="../uploads/<?php echo htmlspecialchars($sp['hinh_anh'] ?? ''); ?>" 
                             alt="<?php echo htmlspecialchars($sp['ten_san_pham'] ?? ''); ?>" 
                             class="product-image"
                             onclick="showFullImage(this.src)">
                    <?php else: ?>
                        <span class="badge badge-danger">Chưa có ảnh</span>
                    <?php endif; ?>
                </td>
                <td><?php echo htmlspecialchars($sp['ma_san_pham'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars($sp['ten_san_pham'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars($sp['ten_danh_muc'] ?? ''); ?></td>
                <td>
                    <span style="color: #6c757d; font-weight: 500;">
                        <?php echo number_format($sp['gia_nhap'] ?? 0, 0, ',', '.'); ?>₫
                    </span>
                </td>

                <td>
                    <strong style="color: #28a745;">
                        <?php echo number_format($sp['gia'] ?? 0, 0, ',', '.'); ?>₫
                    </strong>
                </td>
                <td>
                    <span class="badge <?php echo ($sp['so_luong'] ?? 0) > 10 ? 'badge-success' : (($sp['so_luong'] ?? 0) > 0 ? 'badge-warning' : 'badge-danger'); ?>">
                        <?php echo $sp['so_luong'] ?? 0; ?>
                    </span>
                </td>
                <td>
                    <?php 
                    $isLocked = isset($sp['trang_thai']) && $sp['trang_thai'] == 0;
                    ?>
                    <span class="badge <?php echo $isLocked ? 'badge-danger' : 'badge-success'; ?>">
                        <?php echo $isLocked ? 'Khóa' : 'Hoạt động'; ?>
                    </span>
                </td>
                <td>
                    <button class="btn btn-warning btn-sm sua-san-pham" data-id="<?php echo $sp['id'] ?? 0; ?>" title="Sửa">
                        <i class="fas fa-edit"></i>
                    </button>
                    <?php if($isLocked): ?>
                        <button class="btn btn-success btn-sm mo-khoa-san-pham" data-id="<?php echo $sp['id'] ?? 0; ?>" title="Mở khóa">
                            <i class="fas fa-unlock"></i>
                        </button>
                    <?php else: ?>
                        <button class="btn btn-warning btn-sm khoa-san-pham" data-id="<?php echo $sp['id'] ?? 0; ?>" title="Khóa sản phẩm">
                            <i class="fas fa-lock"></i>
                        </button>
                    <?php endif; ?>
                    <button class="btn btn-danger btn-sm xoa-san-pham" data-id="<?php echo $sp['id'] ?? 0; ?>" title="Xóa">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Product Modal Form -->
<div id="modalSanPham" class="modal">
    <div class="modal-content">
        <div class="modal-title-container">
            <h2 id="modalTitle">Thêm Sản Phẩm Mới</h2>
        </div>
        
        <form id="formSanPham" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="them_san_pham" value="1">
            <input type="hidden" name="san_pham_id" id="san_pham_id">
            
            <div class="form-group">
                <label>Mã Sản Phẩm <span class="required">*</span></label>
                <input type="text" name="ma_san_pham" id="ma_san_pham" required>
            </div>
            
            <div class="form-group">
                <label>Tên Sản Phẩm <span class="required">*</span></label>
                <input type="text" name="ten_san_pham" id="ten_san_pham" required>
            </div>
            
            <div class="form-group">
                <label>Danh Mục <span class="required">*</span> <span class="selected-count" id="categoryCount">0 đã chọn</span></label>
                <div class="select-all-wrapper">
                    <input type="checkbox" id="selectAllCategories">
                    <label for="selectAllCategories">Chọn tất cả danh mục</label>
                </div>
                <div class="multi-select-wrapper" id="categoryWrapper">
                    <?php foreach($danh_muc_list as $dm): ?>
                    <div class="checkbox-item">
                        <input type="checkbox" name="danh_muc_ids[]" value="<?php echo (int)($dm['id'] ?? 0); ?>" id="cat_<?php echo (int)($dm['id'] ?? 0); ?>" class="category-checkbox">
                        <label for="cat_<?php echo (int)($dm['id'] ?? 0); ?>"><?php echo htmlspecialchars(((isset($dm['ten_danh_muc']) && trim($dm['ten_danh_muc']) !== '') ? $dm['ten_danh_muc'] : '(Không tên)'), ENT_QUOTES, 'UTF-8'); ?></label>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div class="form-group">
                    <label>Giá Vốn (Nhập) <span class="required">*</span></label>
                    <input type="number" name="gia_nhap" id="gia_nhap" required class="form-control" placeholder="0">
                </div>
                <div class="form-group">
                    <label>Giá Bán (Ra) <span class="required">*</span></label>
                    <input type="number" name="gia" id="gia" required class="form-control" placeholder="0">
                </div>
            </div>
                        
            <div class="form-group">
                <label>Số Lượng <span class="required">*</span></label>
                <input type="number" name="so_luong" id="so_luong" required>
            </div>
            
            <div class="form-group">
                <label>Mô Tả</label>
                <textarea name="mo_ta" id="mo_ta" rows="4"></textarea>
            </div>
            
            <div class="form-group">
                <label>Hình Ảnh</label>
                <input type="file" name="hinh_anh" id="hinh_anh" accept="image/*">
                <div class="image-preview">
                    <img id="previewImg" style="display:none;">
                </div>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i>
                    Lưu
                </button>
                <button type="button" class="btn btn-secondary" onclick="dongModal()">
                    <i class="fas fa-times"></i>
                    Hủy
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Modal functions
function moModalSanPham() {
    $('#modalTitle').text('Thêm Sản Phẩm Mới');
    $('#formSanPham')[0].reset();
    $('#san_pham_id').val('');
    $('#previewImg').hide();
    $('.category-checkbox').prop('checked', false);
    updateCounts();
    $('#modalSanPham').show();
}

function dongModal() {
    $('#modalSanPham').hide();
}

function showFullImage(src) {
    window.open(src, '_blank');
}

function fillForm(product) {
    $('#san_pham_id').val(product.id);
    $('#ma_san_pham').val(product.ma_san_pham);
    $('#ten_san_pham').val(product.ten_san_pham);
    
    // Clear all checkboxes first
    $('.category-checkbox').prop('checked', false);
    
    // Set selected categories
    if (product.categories && product.categories.length > 0) {
        product.categories.forEach(function(catId) {
            $('#cat_' + catId).prop('checked', true);
        });
    }
    
    // Set selected functions
    if (product.functions && product.functions.length > 0) {
        product.functions.forEach(function(funcId) {
            $('#func_' + funcId).prop('checked', true);
        });
    }
    $('#gia').val(product.gia);           // Giá bán
    $('#gia_nhap').val(product.gia_nhap);
    // Update counts
    updateCounts();
    
    $('#gia').val(product.gia);
    $('#so_luong').val(product.so_luong);
    $('#mo_ta').val(product.mo_ta);
    if (product.hinh_anh) {
        $('#previewImg').attr('src', '../uploads/' + product.hinh_anh).show();
    }
}

function updateCounts() {
    const categoryCount = $('.category-checkbox:checked').length;
    $('#categoryCount').text(categoryCount + ' đã chọn');
}

$(document).ready(function() {
    // Update counts on checkbox change
    $('.category-checkbox').on('change', updateCounts);
    
    // Select all categories
    $('#selectAllCategories').on('change', function() {
        $('.category-checkbox').prop('checked', $(this).prop('checked'));
        updateCounts();
    });
    
    // Update select-all checkbox state when individual checkboxes change
    $('.category-checkbox').on('change', function() {
        const total = $('.category-checkbox').length;
        const checked = $('.category-checkbox:checked').length;
        $('#selectAllCategories').prop('checked', total === checked);
    });
    
    // Initialize counts
    updateCounts();
    
    // Search products
    $('#searchProducts').on('input', function() {
        const q = $(this).val().toLowerCase();
        $('.data-table tbody tr').each(function() {
            const text = $(this).text().toLowerCase();
            $(this).toggle(text.indexOf(q) > -1);
        });
    });
    
    // Image preview
    $('#hinh_anh').on('change', function() {
        const file = this.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = e => $('#previewImg').attr('src', e.target.result).show();
            reader.readAsDataURL(file);
        }
    });
    
    // Form submit with validation
    $('#formSanPham').on('submit', function(e) {
        e.preventDefault();
        
        // Validate at least one category is selected
        const categoryCount = $('.category-checkbox:checked').length;
        if (categoryCount === 0) {
            toastr.error('Vui lòng chọn ít nhất 1 danh mục');
            return false;
        }
        
        const formData = new FormData(this);
        
        $.ajax({
            url: 'qlsanpham.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(res) {
                const data = typeof res === 'string' ? JSON.parse(res) : res;
                if (data.success) {
                    toastr.success(data.message);
                    dongModal();
                    setTimeout(() => location.reload(), 1000);
                } else {
                    toastr.error(data.message);
                }
            },
            error: () => toastr.error('Lỗi kết nối server')
        });
    });
    
    // Edit product
    $(document).on('click', '.sua-san-pham', function() {
        const id = $(this).data('id');
        $.post('qlsanpham.php', { action: 'get_product', id: id })
            .done(function(res) {
                const data = typeof res === 'string' ? JSON.parse(res) : res;
                if (data.success) {
                    fillForm(data.product);
                    $('#modalTitle').text('Sửa Sản Phẩm');
                    $('#modalSanPham').show();
                }
            });
    });
    
    // Delete product
    $(document).on('click', '.xoa-san-pham', function() {
        if (!confirm('Xác nhận xóa sản phẩm?')) return;
        const id = $(this).data('id');
        $.post('qlsanpham.php', { action: 'xoa_san_pham', san_pham_id: id })
            .done(function(res) {
                const data = typeof res === 'string' ? JSON.parse(res) : res;
                if (data.success) {
                    toastr.success(data.message);
                    setTimeout(() => location.reload(), 1000);
                } else {
                    toastr.error(data.message);
                }
            });
    });

    // Lock product
    $(document).on('click', '.khoa-san-pham', function() {
        if (!confirm('Xác nhận khóa sản phẩm này?')) return;
        const $btn = $(this);
        const id = $btn.data('id');
        $.post('qlsanpham.php', { action: 'khoa_san_pham', san_pham_id: id })
            .done(function(res) {
                const data = typeof res === 'string' ? JSON.parse(res) : res;
                if (data.success) {
                    toastr.success('Khóa sản phẩm thành công');
                    const $tr = $btn.closest('tr');
                    $tr.find('td').eq(6).html('<span class="badge badge-danger">Khóa</span>');
                    const $actions = $tr.find('td').eq(7);
                    $actions.find('.khoa-san-pham').remove();
                    $actions.find('.sua-san-pham').after('<button class="btn btn-success btn-sm mo-khoa-san-pham" data-id="'+id+'" title="Mở khóa"><i class="fas fa-unlock"></i></button> ');
                } else {
                    toastr.error(data.message || 'Có lỗi xảy ra');
                }
            }).fail(function(){ toastr.error('Lỗi kết nối server'); });
    });

    // Unlock product
    $(document).on('click', '.mo-khoa-san-pham', function() {
        if (!confirm('Xác nhận mở khóa sản phẩm này?')) return;
        const $btn = $(this);
        const id = $btn.data('id');
        $.post('qlsanpham.php', { action: 'mo_khoa_san_pham', san_pham_id: id })
            .done(function(res) {
                const data = typeof res === 'string' ? JSON.parse(res) : res;
                if (data.success) {
                    toastr.success('Mở khóa thành công');
                    const $tr = $btn.closest('tr');
                    $tr.find('td').eq(6).html('<span class="badge badge-success">Hoạt động</span>');
                    const $actions = $tr.find('td').eq(7);
                    $actions.find('.mo-khoa-san-pham').remove();
                    $actions.find('.sua-san-pham').after('<button class="btn btn-warning btn-sm khoa-san-pham" data-id="'+id+'" title="Khóa sản phẩm"><i class="fas fa-lock"></i></button> ');
                } else {
                    toastr.error(data.message || 'Có lỗi xảy ra');
                }
            }).fail(function(){ toastr.error('Lỗi kết nối server'); });
    });
});
</script>

<?php require_once 'footer.php'; ?>
