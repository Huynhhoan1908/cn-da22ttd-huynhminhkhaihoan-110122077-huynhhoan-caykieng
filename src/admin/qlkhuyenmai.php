<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config.php';

$current_page = 'promotions';
$page_title = 'Quản Lý Khuyến Mãi - HuynhHoan';

// Xử lý AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // 🚨 BẬT DEBUG Ở ĐÂY để PHP báo lỗi Fatal Error ngay trong Response
    error_reporting(E_ALL);
    ini_set('display_errors', 1);

    header('Content-Type: application/json; charset=utf-8');
    
    // Xử lý xoá
    if ($_POST['action'] === 'delete') {
        $id = (int)$_POST['id'];
        $stmt = $conn->prepare("DELETE FROM khuyen_mai WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Xóa thành công']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $conn->error]);
        }
        exit;
    }

    // Xử lý thêm/sửa
    if ($_POST['action'] === 'add' || $_POST['action'] === 'edit') {
        // (Khối lấy dữ liệu POST giữ nguyên)
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $ma_khuyen_mai = strtoupper(trim($_POST['ma_khuyen_mai'] ?? ''));
        $ten_khuyen_mai = trim($_POST['ten_khuyen_mai'] ?? '');
        $mo_ta = trim($_POST['mo_ta'] ?? '');
        $loai_giam = $_POST['loai_giam'] ?? 'phan_tram';
        $gia_tri_giam = (float)($_POST['gia_tri_giam'] ?? 0);
        $gia_tri_don_toi_thieu = (float)($_POST['gia_tri_don_toi_thieu'] ?? 0);
        $gia_tri_giam_toi_da = !empty($_POST['gia_tri_giam_toi_da']) ? (float)$_POST['gia_tri_giam_toi_da'] : 0.00;
        $so_luong_ma = !empty($_POST['so_luong_ma']) ? (int)$_POST['so_luong_ma'] : 0;
        $loai_ap_dung = $_POST['loai_ap_dung'] ?? 'tat_ca';
        $ngay_bat_dau = str_replace('T', ' ', $_POST['ngay_bat_dau']);
        $ngay_ket_thuc = str_replace('T', ' ', $_POST['ngay_ket_thuc']);
        $trang_thai = isset($_POST['trang_thai']) ? 1 : 0;
        $danh_muc_ids = isset($_POST['danh_muc_ids']) ? $_POST['danh_muc_ids'] : [];
        $san_pham_ids = isset($_POST['san_pham_ids']) ? $_POST['san_pham_ids'] : [];

        try {
            $conn->begin_transaction();
            if ($_POST['action'] === 'add') {
                // (Kiểm tra mã trùng)
                $check = $conn->prepare("SELECT id FROM khuyen_mai WHERE ma_khuyen_mai = ?");
                $check->bind_param("s", $ma_khuyen_mai);
                $check->execute();
                $result = $check->get_result();
                if ($result->fetch_assoc()) {
                    $conn->rollback();
                    echo json_encode(['success' => false, 'message' => 'Mã khuyến mãi đã tồn tại']);
                    exit;
                }
                $stmt = $conn->prepare("INSERT INTO khuyen_mai (ma_khuyen_mai, ten_khuyen_mai, mo_ta, loai_giam, gia_tri_giam, gia_tri_don_toi_thieu, gia_tri_giam_toi_da, so_luong_ma, loai_ap_dung, ngay_bat_dau, ngay_ket_thuc, trang_thai) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param(
                    "ssssdddisssi",
                    $ma_khuyen_mai,
                    $ten_khuyen_mai,
                    $mo_ta,
                    $loai_giam,
                    $gia_tri_giam,
                    $gia_tri_don_toi_thieu,
                    $gia_tri_giam_toi_da,
                    $so_luong_ma,
                    $loai_ap_dung,
                    $ngay_bat_dau,
                    $ngay_ket_thuc,
                    $trang_thai
                );
                if (!$stmt->execute()) {
                    $conn->rollback();
                    echo json_encode(['success' => false, 'message' => 'Lỗi thực thi SQL: ' . $stmt->error]);
                    exit;
                }
                if (file_exists(dirname(__DIR__) . '/notification_helpers.php')) {
                    require_once dirname(__DIR__) . '/notification_helpers.php';
                    try {
                        notify_new_promo_all_users($ten_khuyen_mai, $ma_khuyen_mai);
                    } catch (Exception $ex) { error_log('Lỗi gửi notification khuyến mãi: ' . $ex->getMessage()); }
                }
            } else { // edit
                $stmt = $conn->prepare("UPDATE khuyen_mai SET ma_khuyen_mai=?, ten_khuyen_mai=?, mo_ta=?, loai_giam=?, gia_tri_giam=?, gia_tri_don_toi_thieu=?, gia_tri_giam_toi_da=?, so_luong_ma=?, loai_ap_dung=?, ngay_bat_dau=?, ngay_ket_thuc=?, trang_thai=? WHERE id=?");
                $stmt->bind_param(
                    "ssssdddisssii",
                    $ma_khuyen_mai,
                    $ten_khuyen_mai,
                    $mo_ta,
                    $loai_giam,
                    $gia_tri_giam,
                    $gia_tri_don_toi_thieu,
                    $gia_tri_giam_toi_da,
                    $so_luong_ma,
                    $loai_ap_dung,
                    $ngay_bat_dau,
                    $ngay_ket_thuc,
                    $trang_thai,
                    $id
                );
                if (!$stmt->execute()) {
                    $conn->rollback();
                    echo json_encode(['success' => false, 'message' => 'Lỗi cập nhật: ' . $stmt->error]);
                    exit;
                }
            }
            $conn->commit();
            echo json_encode(['success' => true, 'message' => $_POST['action'] === 'add' ? 'Thêm mã khuyến mãi thành công' : 'Cập nhật thành công']);
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => 'Lỗi xử lý database: ' . $e->getMessage()]);
        }
        exit;
    }
    // ... (Các khối khác giữ nguyên) ...
}

// Tạo bảng nếu chưa có
try {
    $conn->query("
        CREATE TABLE IF NOT EXISTS khuyen_mai (
            id INT AUTO_INCREMENT PRIMARY KEY,
            ma_khuyen_mai VARCHAR(50) UNIQUE NOT NULL,
            ten_khuyen_mai VARCHAR(255) NOT NULL,
            mo_ta TEXT,
            loai_giam ENUM('phan_tram', 'so_tien') DEFAULT 'phan_tram',
            gia_tri_giam DECIMAL(10,2) NOT NULL,
            gia_tri_don_toi_thieu DECIMAL(10,2) DEFAULT 0,
            gia_tri_giam_toi_da DECIMAL(10,2) DEFAULT 0,
            so_luong_ma INT DEFAULT 0,
            so_lan_da_dung INT DEFAULT 0,
            loai_ap_dung ENUM('tat_ca', 'danh_muc', 'san_pham') DEFAULT 'tat_ca',
            ngay_bat_dau DATETIME NOT NULL,
            ngay_ket_thuc DATETIME NOT NULL,
            trang_thai TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    $conn->query("
        CREATE TABLE IF NOT EXISTS khuyen_mai_danh_muc (
            id INT AUTO_INCREMENT PRIMARY KEY,
            khuyen_mai_id INT NOT NULL,
            danh_muc_id INT NOT NULL,
            UNIQUE KEY unique_promo_category (khuyen_mai_id, danh_muc_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    $conn->query("
        CREATE TABLE IF NOT EXISTS khuyen_mai_san_pham (
            id INT AUTO_INCREMENT PRIMARY KEY,
            khuyen_mai_id INT NOT NULL,
            san_pham_id INT NOT NULL,
            UNIQUE KEY unique_promo_product (khuyen_mai_id, san_pham_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    $conn->query("
        CREATE TABLE IF NOT EXISTS lich_su_khuyen_mai (
            id INT AUTO_INCREMENT PRIMARY KEY,
            khuyen_mai_id INT NOT NULL,
            don_hang_id INT NOT NULL,
            nguoi_dung_id INT,
            gia_tri_giam DECIMAL(10,2) NOT NULL,
            ngay_su_dung TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    // Thêm cột vào bảng don_hang nếu chưa có
    // Kiểm tra và thêm từng cột riêng
    $result = $conn->query("SHOW COLUMNS FROM don_hang LIKE 'ma_khuyen_mai'");
    if ($result->num_rows == 0) {
        $conn->query("ALTER TABLE don_hang ADD COLUMN ma_khuyen_mai VARCHAR(50) DEFAULT NULL COMMENT 'Mã khuyến mãi đã sử dụng'");
    }
    
    $result = $conn->query("SHOW COLUMNS FROM don_hang LIKE 'giam_gia'");
    if ($result->num_rows == 0) {
        $conn->query("ALTER TABLE don_hang ADD COLUMN giam_gia DECIMAL(10,2) DEFAULT 0 COMMENT 'Số tiền giảm giá'");
    }
} catch (Exception $e) {
    error_log("Error creating tables: " . $e->getMessage());
}

// Load khuyến mãi
$promotions = [];
try {
    $stmt = $conn->query("
        SELECT km.*, 
               (SELECT COUNT(*) FROM lich_su_khuyen_mai WHERE khuyen_mai_id = km.id) as so_lan_su_dung_thuc_te
        FROM khuyen_mai km 
        ORDER BY km.created_at DESC
    ");
    if ($stmt) {
        $promotions = $stmt->fetch_all(MYSQLI_ASSOC);
    }
    // Lấy danh mục và sản phẩm áp dụng cho mỗi mã
    foreach ($promotions as &$promo) {
        if ($promo['loai_ap_dung'] === 'danh_muc') {
            $stmt2 = $conn->prepare("
                SELECT dm.id, dm.ten_san_pham 
                FROM khuyen_mai_danh_muc kmdm
                JOIN danh_muc dm ON kmdm.danh_muc_id = dm.id
                WHERE kmdm.khuyen_mai_id = ?
            ");
            $stmt2->bind_param("i", $promo['id']);
            $stmt2->execute();
            $result2 = $stmt2->get_result();
            $promo['danh_muc_list'] = $result2 ? $result2->fetch_all(MYSQLI_ASSOC) : [];
        } elseif ($promo['loai_ap_dung'] === 'san_pham') {
            $stmt2 = $conn->prepare("
                SELECT sp.id, sp.ten_san_pham 
                FROM khuyen_mai_san_pham kmsp
                JOIN san_pham sp ON kmsp.san_pham_id = sp.id
                WHERE kmsp.khuyen_mai_id = ?
            ");
            $stmt2->bind_param("i", $promo['id']);
            $stmt2->execute();
            $result2 = $stmt2->get_result();
            $promo['san_pham_list'] = $result2 ? $result2->fetch_all(MYSQLI_ASSOC) : [];
        }
    }
} catch (Exception $e) {
    error_log("Error loading promotions: " . $e->getMessage());
}

// Load danh mục
$categories = [];
try {
    $stmt = $conn->query("SELECT id, ten_san_pham FROM danh_muc ORDER BY ten_san_pham");
    if ($stmt) {
        $categories = $stmt->fetch_all(MYSQLI_ASSOC);
    }
} catch (Exception $e) {
    error_log("Error loading categories: " . $e->getMessage());
}

// Load sản phẩm
$products = [];
try {
    $stmt = $conn->query("SELECT id, ten_san_pham, gia FROM san_pham WHERE trang_thai = 1 ORDER BY ten_san_pham");
    if ($stmt) {
        $products = $stmt->fetch_all(MYSQLI_ASSOC);
    }
} catch (Exception $e) {
    error_log("Error loading products: " . $e->getMessage());
}

include 'header.php';
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>

<style>
.page-header {
    background: linear-gradient(135deg, #fdfbe8 0%, #f5f2d4 100%);
    padding: 2rem;
    border-radius: 16px;
    margin-bottom: 2rem;
    border-left: 5px solid #7fa84e;
}
.page-header h1 {
    color: #1d3e1f;
    font-size: 2rem;
    font-weight: 700;
    margin: 0;
}
.promo-card {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    border: 2px solid #e8edc7;
    transition: all 0.3s;
}
.promo-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(61,107,63,0.15);
    border-color: #9bc26f;
}
.promo-code {
    display: inline-block;
    background: linear-gradient(135deg, #1d3e1f 0%, #3d6b3f 100%);
    color: white;
    padding: 8px 16px;
    border-radius: 8px;
    font-weight: 700;
    font-size: 18px;
    letter-spacing: 1px;
    margin-bottom: 12px;
}
.promo-value {
    color: #059669;
    font-size: 24px;
    font-weight: 700;
}
.btn-add {
    background: linear-gradient(135deg, #059669 0%, #10b981 100%);
    color: white;
    padding: 12px 24px;
    border-radius: 10px;
    border: none;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
}
.btn-add:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(5,150,105,0.4);
}
.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 10000;
    overflow-y: auto;
}
.modal-content {
    background: white;
    max-width: 800px;
    margin: 2rem auto;
    border-radius: 16px;
    padding: 2rem;
}
.form-group {
    margin-bottom: 1.5rem;
}
.form-group label {
    display: block;
    font-weight: 600;
    color: #1d3e1f;
    margin-bottom: 0.5rem;
}
.form-control {
    width: 100%;
    padding: 10px 14px;
    border: 2px solid #e8edc7;
    border-radius: 8px;
    font-size: 14px;
    transition: all 0.3s;
}
.form-control:focus {
    outline: none;
    border-color: #7fa84e;
    box-shadow: 0 0 0 3px rgba(127,168,78,0.1);
}
.badge {
    display: inline-block;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
}
.badge-active {
    background: #ecfdf5;
    color: #059669;
}
.badge-inactive {
    background: #fef3c7;
    color: #92400e;
}
.badge-expired {
    background: #fee;
    color: #c33;
}
</style>

<div class="page-header">
    <div style="display:flex;justify-content:space-between;align-items:center;">
        <h1><i class="fas fa-tags"></i> Quản Lý Khuyến Mãi</h1>
        <button class="btn-add" onclick="showAddModal()">
            <i class="fas fa-plus"></i> Thêm Mã Khuyến Mãi
        </button>
    </div>
</div>

<div class="container">
    <?php if (empty($promotions)): ?>
        <div style="text-align:center;padding:3rem;color:#999;">
            <i class="fas fa-tag" style="font-size:4rem;margin-bottom:1rem;"></i>
            <p>Chưa có mã khuyến mãi nào. Hãy tạo mã mới!</p>
        </div>
    <?php else: ?>
        <?php foreach ($promotions as $promo): 
            $now = new DateTime();
            $start = new DateTime($promo['ngay_bat_dau']);
            $end = new DateTime($promo['ngay_ket_thuc']);
            $is_active = $promo['trang_thai'] == 1;
            $is_expired = $now > $end;
            $is_upcoming = $now < $start;
        ?>
        <div class="promo-card">
            <div style="display:flex;justify-content:space-between;align-items:start;">
                <div style="flex:1;">
                    <div class="promo-code"><?php echo htmlspecialchars($promo['ma_khuyen_mai']); ?></div>
                    
                    <?php if ($is_expired): ?>
                        <span class="badge badge-expired">Đã hết hạn</span>
                    <?php elseif ($is_upcoming): ?>
                        <span class="badge" style="background:#e0f2fe;color:#0369a1;">Sắp diễn ra</span>
                    <?php elseif ($is_active): ?>
                        <span class="badge badge-active">Đang hoạt động</span>
                    <?php else: ?>
                        <span class="badge badge-inactive">Tạm dừng</span>
                    <?php endif; ?>
                    
                    <h3 style="margin:12px 0;color:#1d3e1f;"><?php echo htmlspecialchars($promo['ten_khuyen_mai']); ?></h3>
                    <p style="color:#64748b;margin-bottom:12px;"><?php echo htmlspecialchars($promo['mo_ta']); ?></p>
                    
                    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1rem;margin:1rem 0;">
                        <div>
                            <strong style="color:#1d3e1f;">Giá trị giảm:</strong>
                            <div class="promo-value">
                                <?php 
                                if ($promo['loai_giam'] === 'phan_tram') {
                                    echo $promo['gia_tri_giam'] . '%';
                                } else {
                                    echo number_format($promo['gia_tri_giam'], 0, ',', '.') . 'đ';
                                }
                                ?>
                            </div>
                        </div>
                        <div>
                            <strong style="color:#1d3e1f;">Đơn tối thiểu:</strong>
                            <div style="font-size:18px;font-weight:600;color:#3d6b3f;">
                                <?php echo number_format($promo['gia_tri_don_toi_thieu'], 0, ',', '.'); ?>đ
                            </div>
                        </div>
                        <?php if ($promo['gia_tri_giam_toi_da'] > 0): ?>
                        <div>
                            <strong style="color:#1d3e1f;">Giảm tối đa:</strong>
                            <div style="font-size:18px;font-weight:600;color:#3d6b3f;">
                                <?php echo number_format($promo['gia_tri_giam_toi_da'], 0, ',', '.'); ?>đ
                            </div>
                        </div>
                        <?php else: ?>
                        <div>
                            <strong style="color:#1d3e1f;">Giảm tối đa:</strong>
                            <div style="font-size:18px;font-weight:600;color:#3d6b3f;">Không giới hạn</div>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div style="display:flex;gap:2rem;margin:1rem 0;font-size:14px;color:#64748b;">
                        <div>
                            <i class="far fa-calendar"></i>
                            <strong>Từ:</strong> <?php echo date('d/m/Y', strtotime($promo['ngay_bat_dau'])); ?>
                        </div>
                        <div>
                            <i class="far fa-calendar"></i>
                            <strong>Đến:</strong> <?php echo date('d/m/Y', strtotime($promo['ngay_ket_thuc'])); ?>
                        </div>
                    </div>
                    
                    <?php if ($promo['so_luong_ma'] > 0): ?>
                    <div style="margin:8px 0;font-size:14px;">
                        <i class="fas fa-ticket-alt"></i>
                        Số lượng: <strong><?php echo $promo['so_luong_ma'] - $promo['so_lan_su_dung_thuc_te']; ?></strong> / <?php echo $promo['so_luong_ma']; ?> còn lại
                    </div>
                    <?php else: ?>
                    <div style="margin:8px 0;font-size:14px;">
                        <i class="fas fa-ticket-alt"></i>
                        Số lượng: <strong>Không giới hạn</strong>
                    </div>
                    <?php endif; ?>
                    
                    <div style="margin:8px 0;font-size:14px;">
                        <i class="fas fa-chart-line"></i>
                        Đã sử dụng: <strong><?php echo $promo['so_lan_su_dung_thuc_te']; ?></strong> lần
                    </div>
                    
                    <div style="margin:8px 0;font-size:14px;">
                        <i class="fas fa-layer-group"></i>
                        Áp dụng: <strong>
                        <?php 
                        if ($promo['loai_ap_dung'] === 'tat_ca') {
                            echo 'Tất cả sản phẩm';
                        } elseif ($promo['loai_ap_dung'] === 'danh_muc' && !empty($promo['danh_muc_list'])) {
                            echo 'Danh mục: ' . implode(', ', array_column($promo['danh_muc_list'], 'ten_san_pham'));
                        } elseif ($promo['loai_ap_dung'] === 'san_pham' && !empty($promo['san_pham_list'])) {
                            echo 'Sản phẩm: ' . implode(', ', array_column($promo['san_pham_list'], 'ten_san_pham'));
                        }
                        ?>
                        </strong>
                    </div>
                </div>
                
                <div style="display:flex;flex-direction:column;gap:8px;">
                    <button class="btn btn-info btn-sm" onclick='editPromotion(<?php echo json_encode($promo); ?>)' title="Sửa">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-danger btn-sm" onclick="deletePromotion(<?php echo $promo['id']; ?>)" title="Xóa">
                        <i class="fas fa-trash"></i>
                    </button>
                    <label class="switch" title="Bật/Tắt">
                        <input type="checkbox" <?php echo $is_active ? 'checked' : ''; ?> 
                               onchange="toggleStatus(<?php echo $promo['id']; ?>, this.checked)">
                        <span class="slider"></span>
                    </label>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Modal Add/Edit -->
<div id="promoModal" class="modal">
    <div class="modal-content">
        <h2 id="modalTitle" style="color:#1d3e1f;margin-bottom:1.5rem;">Thêm Mã Khuyến Mãi</h2>
        <form id="promoForm">
            <input type="hidden" name="id" id="promo_id">
            <input type="hidden" name="action" id="form_action" value="add">
            
            <div class="form-group">
                <label>Mã khuyến mãi *</label>
                <input type="text" name="ma_khuyen_mai" id="ma_khuyen_mai" class="form-control" required 
                       placeholder="VD: NEWYEAR2025" style="text-transform:uppercase;">
            </div>
            
            <div class="form-group">
                <label>Tên khuyến mãi *</label>
                <input type="text" name="ten_khuyen_mai" id="ten_khuyen_mai" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label>Mô tả</label>
                <textarea name="mo_ta" id="mo_ta" class="form-control" rows="3"></textarea>
            </div>
            
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                <div class="form-group">
                    <label>Loại giảm giá *</label>
                    <select name="loai_giam" id="loai_giam" class="form-control" required>
                        <option value="phan_tram">Phần trăm (%)</option>
                        <option value="so_tien">Số tiền (đ)</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Giá trị giảm *</label>
                    <input type="number" name="gia_tri_giam" id="gia_tri_giam" class="form-control" required min="0" step="0.01">
                </div>
            </div>
            
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                <div class="form-group">
                    <label>Giá trị đơn tối thiểu (đ)</label>
                    <input type="number" name="gia_tri_don_toi_thieu" id="gia_tri_don_toi_thieu" class="form-control" value="0" min="0">
                </div>
                
                <div class="form-group">
                    <label>Giảm tối đa (đ)</label>
                    <input type="number" name="gia_tri_giam_toi_da" id="gia_tri_giam_toi_da" class="form-control" min="0">
                </div>
            </div>
            
            <div class="form-group">
                <label>Số lượng mã (để trống = không giới hạn)</label>
                <input type="number" name="so_luong_ma" id="so_luong_ma" class="form-control" min="1">
            </div>
            
            <div class="form-group">
                <label>Áp dụng cho *</label>
                <select name="loai_ap_dung" id="loai_ap_dung" class="form-control" required onchange="toggleApplyType()">
                    <option value="tat_ca">Tất cả sản phẩm</option>
                    <option value="danh_muc">Theo danh mục</option>
                    <option value="san_pham">Theo sản phẩm</option>
                </select>
            </div>
            
            <div class="form-group" id="category_select" style="display:none;">
                <label>Chọn danh mục</label>
                <select name="danh_muc_ids[]" id="danh_muc_ids" class="form-control" multiple size="5">
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['ten_san_pham']); ?></option>
                    <?php endforeach; ?>
                </select>
                <small style="color:#64748b;">Giữ Ctrl để chọn nhiều</small>
            </div>
            
            <div class="form-group" id="product_select" style="display:none;">
                <label>Chọn sản phẩm</label>
                <select name="san_pham_ids[]" id="san_pham_ids" class="form-control" multiple size="5">
                    <?php foreach ($products as $prod): ?>
                        <option value="<?php echo $prod['id']; ?>">
                            <?php echo htmlspecialchars($prod['ten_san_pham']); ?> 
                            (<?php echo number_format($prod['gia'], 0, ',', '.'); ?>đ)
                        </option>
                    <?php endforeach; ?>
                </select>
                <small style="color:#64748b;">Giữ Ctrl để chọn nhiều</small>
            </div>
            
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                <div class="form-group">
                    <label>Ngày bắt đầu *</label>
                    <input type="datetime-local" name="ngay_bat_dau" id="ngay_bat_dau" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label>Ngày kết thúc *</label>
                    <input type="datetime-local" name="ngay_ket_thuc" id="ngay_ket_thuc" class="form-control" required>
                </div>
            </div>
            
            <div class="form-group">
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                    <input type="checkbox" name="trang_thai" id="trang_thai" checked>
                    <span>Kích hoạt ngay</span>
                </label>
            </div>
            
            <div style="display:flex;gap:1rem;margin-top:2rem;">
                <button type="submit" class="btn-add" style="flex:1;">
                    <i class="fas fa-save"></i> Lưu
                </button>
                <button type="button" class="btn btn-secondary" onclick="closeModal()" style="flex:1;">
                    Hủy
                </button>
            </div>
        </form>
    </div>
</div>

<style>
.switch {
    position: relative;
    display: inline-block;
    width: 50px;
    height: 24px;
}
.switch input {
    opacity: 0;
    width: 0;
    height: 0;
}
.slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #ccc;
    transition: .4s;
    border-radius: 24px;
}
.slider:before {
    position: absolute;
    content: "";
    height: 18px;
    width: 18px;
    left: 3px;
    bottom: 3px;
    background-color: white;
    transition: .4s;
    border-radius: 50%;
}
input:checked + .slider {
    background-color: #10b981;
}
input:checked + .slider:before {
    transform: translateX(26px);
}
</style>

<script>
function showAddModal() {
    document.getElementById('modalTitle').textContent = 'Thêm Mã Khuyến Mãi';
    document.getElementById('form_action').value = 'add';
    document.getElementById('promoForm').reset();
    document.getElementById('ma_khuyen_mai').removeAttribute('readonly');
    document.getElementById('promoModal').style.display = 'block';
    toggleApplyType();
}

function editPromotion(promo) {
    document.getElementById('modalTitle').textContent = 'Sửa Mã Khuyến Mãi';
    document.getElementById('form_action').value = 'edit';
    document.getElementById('promo_id').value = promo.id;
    document.getElementById('ma_khuyen_mai').value = promo.ma_khuyen_mai;
    document.getElementById('ma_khuyen_mai').setAttribute('readonly', 'readonly');
    document.getElementById('ten_khuyen_mai').value = promo.ten_khuyen_mai;
    document.getElementById('mo_ta').value = promo.mo_ta || '';
    document.getElementById('loai_giam').value = promo.loai_giam;
    document.getElementById('gia_tri_giam').value = promo.gia_tri_giam;
    document.getElementById('gia_tri_don_toi_thieu').value = promo.gia_tri_don_toi_thieu;
    document.getElementById('gia_tri_giam_toi_da').value = promo.gia_tri_giam_toi_da || '';
    document.getElementById('so_luong_ma').value = promo.so_luong_ma || '';
    document.getElementById('loai_ap_dung').value = promo.loai_ap_dung;
    document.getElementById('ngay_bat_dau').value = promo.ngay_bat_dau.replace(' ', 'T');
    document.getElementById('ngay_ket_thuc').value = promo.ngay_ket_thuc.replace(' ', 'T');
    document.getElementById('trang_thai').checked = promo.trang_thai == 1;
    
    toggleApplyType();
    
    // Select categories/products
    if (promo.loai_ap_dung === 'danh_muc' && promo.danh_muc_list) {
        const catIds = promo.danh_muc_list.map(c => c.id);
        $('#danh_muc_ids').val(catIds);
    } else if (promo.loai_ap_dung === 'san_pham' && promo.san_pham_list) {
        const prodIds = promo.san_pham_list.map(p => p.id);
        $('#san_pham_ids').val(prodIds);
    }
    
    document.getElementById('promoModal').style.display = 'block';
}

function closeModal() {
    document.getElementById('promoModal').style.display = 'none';
}

function toggleApplyType() {
    const type = document.getElementById('loai_ap_dung').value;
    document.getElementById('category_select').style.display = type === 'danh_muc' ? 'block' : 'none';
    document.getElementById('product_select').style.display = type === 'san_pham' ? 'block' : 'none';
}

document.getElementById('promoForm').onsubmit = function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    $.ajax({
        url: 'qlkhuyenmai.php',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(response) {
            if (response && response.success) {
                toastr.success(response.message);
                setTimeout(() => location.reload(), 1000);
            } else if (response && response.message) {
                toastr.error(response.message);
            } else {
                toastr.error('Có lỗi xảy ra (response không hợp lệ)');
            }
        },
        error: function(xhr) {
            let msg = 'Có lỗi xảy ra';
            if (xhr && xhr.responseText) {
                try {
                    const res = JSON.parse(xhr.responseText);
                    msg = res.message || msg;
                } catch (e) {}
            }
            toastr.error(msg);
        }
    });
};

function deletePromotion(id) {
    if (!confirm('Bạn có chắc muốn xóa mã khuyến mãi này?')) return;
    
    $.ajax({
        url: 'qlkhuyenmai.php',
        method: 'POST',
        data: { action: 'delete', id: id },
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

function toggleStatus(id, status) {
    $.ajax({
        url: 'qlkhuyenmai.php',
        method: 'POST',
        data: { action: 'toggle_status', id: id, status: status ? 1 : 0 },
        success: function(response) {
            if (response.success) {
                toastr.success(response.message);
            } else {
                toastr.error(response.message);
                location.reload();
            }
        }
    });
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('promoModal');
    if (event.target === modal) {
        closeModal();
    }
}
</script>

<link rel="stylesheet" href="../assets/chatbot.css">
<link rel="stylesheet" href="../assets/notifications.css">

<script src="../assets/notifications.js" defer></script>
<script src="../assets/chatbot.js" defer></script>

<?php include 'footer.php'; ?>
</body>
</html>