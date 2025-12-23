<?php
require_once 'config.php';

$current_page = 'customers';
$page_title = 'Quản Lý Khách Hàng';

// --- XỬ LÝ PHP ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    // 1. AJAX: Khóa & Xóa
    if ($_POST['action'] === 'toggle_lock' || $_POST['action'] === 'delete') {
        header('Content-Type: application/json');
        $id = (int)($_POST['id'] ?? 0);
        
        if ($_POST['action'] === 'toggle_lock') {
            $lock = (int)($_POST['lock'] ?? 0);
            if ($conn->query("UPDATE nguoi_dung SET khoa = $lock WHERE id = $id")) {
                echo json_encode(['success' => true]); 
            } else { 
                echo json_encode(['success' => false, 'message' => $conn->error]); 
            }
        } elseif ($_POST['action'] === 'delete') {
            if ($conn->query("DELETE FROM nguoi_dung WHERE id = $id")) {
                echo json_encode(['success' => true]); 
            } else { 
                echo json_encode(['success' => false, 'message' => $conn->error]); 
            }
        }
        exit();
    }

    // 2. FORM: Thêm & Sửa
    if ($_POST['action'] === 'add' || $_POST['action'] === 'edit') {
        $ho_ten = $_POST['ho_ten'];
        $ten_dang_nhap = $_POST['ten_dang_nhap'] ?? '';
        $email = $_POST['email'];
        $sdt = $_POST['so_dien_thoai']; // Lấy dữ liệu từ form
        $dia_chi = $_POST['dia_chi'];   // Lấy dữ liệu từ form
        $role = $_POST['vai_tro'];

        // --- XỬ LÝ THÊM MỚI ---
        if ($_POST['action'] === 'add') {
            // Kiểm tra trùng
            $check = $conn->query("SELECT id FROM nguoi_dung WHERE ten_dang_nhap = '$ten_dang_nhap' OR email = '$email'");
            if ($check && $check->num_rows > 0) {
                echo "<script>alert('Lỗi: Tên đăng nhập hoặc Email đã tồn tại!'); window.history.back();</script>";
                exit;
            }
            
            $pass = password_hash($_POST['mat_khau'], PASSWORD_DEFAULT);
            
            // SQL INSERT ĐÃ CẬP NHẬT: Thêm so_dien_thoai và dia_chi
            $sql = "INSERT INTO nguoi_dung (ho_ten, ten_dang_nhap, email, so_dien_thoai, dia_chi, mat_khau, quyen) VALUES (?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $conn->prepare($sql);
            if (!$stmt) die("Lỗi SQL Add: " . $conn->error);
            
            // Bind 7 tham số (sssssss)
            $stmt->bind_param("sssssss", $ho_ten, $ten_dang_nhap, $email, $sdt, $dia_chi, $pass, $role);
            
            if ($stmt->execute()) echo "<script>alert('Thêm mới thành công!'); window.location.href='qlkhachhang.php';</script>";
            else echo "<script>alert('Lỗi thêm: " . $stmt->error . "');</script>";
        
        // --- XỬ LÝ CẬP NHẬT ---
        } elseif ($_POST['action'] === 'edit') {
            $id = $_POST['user_id'];
            
            // SQL UPDATE ĐÃ CẬP NHẬT
            $sql = "UPDATE nguoi_dung SET ho_ten=?, email=?, so_dien_thoai=?, dia_chi=?, quyen=?";
            $params = [$ho_ten, $email, $sdt, $dia_chi, $role];
            $types = "sssss"; // 5 chuỗi đầu

            // Nếu đổi mật khẩu
            if (!empty($_POST['mat_khau'])) {
                $sql .= ", mat_khau=?";
                $params[] = password_hash($_POST['mat_khau'], PASSWORD_DEFAULT);
                $types .= "s";
            }
            $sql .= " WHERE id=?";
            $params[] = $id;
            $types .= "i"; // id là số

            $stmt = $conn->prepare($sql);
            if (!$stmt) die("Lỗi SQL Edit: " . $conn->error);
            
            $stmt->bind_param($types, ...$params);
            
            if ($stmt->execute()) echo "<script>alert('Cập nhật thành công!'); window.location.href='qlkhachhang.php';</script>";
            else echo "<script>alert('Lỗi sửa: " . $stmt->error . "');</script>";
        }
    }
}

// Lấy danh sách
$customers = [];
$result = $conn->query("SELECT * FROM nguoi_dung ORDER BY id DESC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $row['vaitro'] = $row['quyen'] ?? 'user';
        // Hiển thị dữ liệu thực tế từ DB
        $row['sodienthoai'] = !empty($row['so_dien_thoai']) ? $row['so_dien_thoai'] : '---'; 
        $row['diachi'] = !empty($row['dia_chi']) ? $row['dia_chi'] : '---';
        $customers[] = $row;
    }
}

include 'header.php';
?>

<div class="page-header">
    <h1>Quản Lý Khách Hàng</h1>
</div>

<div class="table-container">
    <div class="section-header" style="display:flex; justify-content:space-between; margin-bottom:20px;">
        <div style="display:flex; gap:10px; align-items:center;">
            <h2 style="margin:0;">Danh Sách (<?php echo count($customers); ?>)</h2>
            <button type="button" onclick="openModal('addModal')" class="btn btn-primary" style="background:#27ae60; color:white; border:none; padding:8px 15px; border-radius:5px; cursor:pointer;">
                <i class="fas fa-plus"></i> Thêm Mới
            </button>
        </div>
        <input type="text" id="searchName" placeholder="Tìm kiếm..." style="padding:8px; border-radius:20px; border:1px solid #ddd; width:200px;">
    </div>

    <table class="data-table" style="width:100%; border-collapse:collapse;">
        <thead>
            <tr style="background:#f8f9fa; border-bottom:2px solid #ddd;">
                <th style="padding:12px;">ID</th>
                <th>Thông Tin</th>
                <th>Liên Hệ</th>
                <th>Quyền</th>
                <th>Trạng Thái</th>
                <th>Thao Tác</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($customers as $u): ?>
            <tr style="border-bottom:1px solid #eee;">
                <td style="padding:10px;"><?php echo $u['id']; ?></td>
                <td>
                    <b><?php echo htmlspecialchars($u['ho_ten']); ?></b><br>
                    <small style="color:#888">User: <?php echo htmlspecialchars($u['ten_dang_nhap'] ?? ''); ?></small>
                </td>
                <td>
                    <div><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($u['email']); ?></div>
                    <div><i class="fas fa-phone"></i> <?php echo htmlspecialchars($u['sodienthoai']); ?></div>
                    <div style="font-size:12px; color:#666;"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($u['diachi']); ?></div>
                </td>
                <td>
                    <?php $isAdmin = ($u['vaitro'] == 1 || $u['vaitro'] == 'admin'); ?>
                    <span style="background:<?php echo $isAdmin?'#dc3545':'#17a2b8'; ?>; color:white; padding:3px 8px; border-radius:4px; font-size:12px;">
                        <?php echo $isAdmin ? 'Admin' : 'Khách'; ?>
                    </span>
                </td>
                <td>
                    <?php if($u['khoa']): ?>
                        <span style="color:red;">Đã khóa</span>
                    <?php else: ?>
                        <span style="color:green;">Hoạt động</span>
                    <?php endif; ?>
                </td>
                <td>
                    <button type="button" class="btn-action" style="background:#ffc107; border:none; padding:5px 10px; border-radius:4px; cursor:pointer;"
                        onclick="openEditModal(
                            '<?php echo $u['id']; ?>',
                            '<?php echo htmlspecialchars($u['ho_ten']); ?>',
                            '<?php echo htmlspecialchars($u['email']); ?>',
                            '<?php echo htmlspecialchars($u['sodienthoai']); ?>',
                            '<?php echo htmlspecialchars($u['diachi']); ?>',
                            '<?php echo $isAdmin ? 'admin' : 'user'; ?>'
                        )">
                        <i class="fas fa-edit"></i>
                    </button>
                    
                    <?php if($u['khoa']): ?>
                        <button onclick="toggleLock(<?php echo $u['id']; ?>, 0)" style="background:#28a745; color:white; border:none; padding:5px 10px; border-radius:4px; cursor:pointer;" title="Mở khóa"><i class="fas fa-unlock"></i></button>
                    <?php else: ?>
                        <button onclick="toggleLock(<?php echo $u['id']; ?>, 1)" style="background:#6c757d; color:white; border:none; padding:5px 10px; border-radius:4px; cursor:pointer;" title="Khóa"><i class="fas fa-lock"></i></button>
                    <?php endif; ?>

                    <button onclick="deleteUser(<?php echo $u['id']; ?>)" style="background:#dc3545; color:white; border:none; padding:5px 10px; border-radius:4px; cursor:pointer;"><i class="fas fa-trash"></i></button>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div id="addModal" class="custom-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:9999;">
    <div style="background:white; width:500px; margin:50px auto; padding:20px; border-radius:8px; position:relative;">
        <span onclick="closeModal('addModal')" style="position:absolute; right:15px; top:10px; font-size:24px; cursor:pointer;">&times;</span>
        <h3 style="margin-top:0;">Thêm Khách Hàng</h3>
        
        <form method="POST">
            <input type="hidden" name="action" value="add">
            <div style="margin-bottom:15px;">
                <label>Họ tên *</label>
                <input type="text" name="ho_ten" required class="form-control" style="width:100%; padding:8px; border:1px solid #ddd;">
            </div>
            <div style="margin-bottom:15px;">
                <label>Tên đăng nhập *</label>
                <input type="text" name="ten_dang_nhap" required class="form-control" style="width:100%; padding:8px; border:1px solid #ddd;">
            </div>
            <div style="margin-bottom:15px;">
                <label>Mật khẩu *</label>
                <input type="password" name="mat_khau" required class="form-control" style="width:100%; padding:8px; border:1px solid #ddd;">
            </div>
            <div style="margin-bottom:15px;">
                <label>Email *</label>
                <input type="email" name="email" required class="form-control" style="width:100%; padding:8px; border:1px solid #ddd;">
            </div>
            <div style="margin-bottom:15px;">
                <label>Số điện thoại</label>
                <input type="text" name="so_dien_thoai" class="form-control" style="width:100%; padding:8px; border:1px solid #ddd;">
            </div>
            <div style="margin-bottom:15px;">
                <label>Địa chỉ</label>
                <input type="text" name="dia_chi" class="form-control" style="width:100%; padding:8px; border:1px solid #ddd;">
            </div>
            <div style="margin-bottom:20px;">
                <label>Quyền hạn</label>
                <select name="vai_tro" style="width:100%; padding:8px;">
                    <option value="user">Khách hàng</option>
                    <option value="admin">Quản trị viên (Admin)</option>
                </select>
            </div>
            <div style="text-align:right;">
                <button type="button" onclick="closeModal('addModal')" style="padding:8px 20px; background:#6c757d; color:white; border:none; border-radius:4px; cursor:pointer;">Hủy</button>
                <button type="submit" style="padding:8px 20px; background:#27ae60; color:white; border:none; border-radius:4px; cursor:pointer;">Lưu lại</button>
            </div>
        </form>
    </div>
</div>

<div id="editModal" class="custom-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:9999;">
    <div style="background:white; width:500px; margin:50px auto; padding:20px; border-radius:8px; position:relative;">
        <span onclick="closeModal('editModal')" style="position:absolute; right:15px; top:10px; font-size:24px; cursor:pointer;">&times;</span>
        <h3 style="margin-top:0;">Cập Nhật Thông Tin</h3>
        
        <form method="POST">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="user_id" id="edit_id">
            
            <div style="margin-bottom:15px;">
                <label>Họ tên</label>
                <input type="text" name="ho_ten" id="edit_name" required style="width:100%; padding:8px; border:1px solid #ddd;">
            </div>
            <div style="margin-bottom:15px;">
                <label>Email</label>
                <input type="email" name="email" id="edit_email" required style="width:100%; padding:8px; border:1px solid #ddd;">
            </div>
            <div style="margin-bottom:15px;">
                <label>Số điện thoại</label>
                <input type="text" name="so_dien_thoai" id="edit_phone" style="width:100%; padding:8px; border:1px solid #ddd;">
            </div>
            <div style="margin-bottom:15px;">
                <label>Địa chỉ</label>
                <input type="text" name="dia_chi" id="edit_address" style="width:100%; padding:8px; border:1px solid #ddd;">
            </div>
            <div style="margin-bottom:15px;">
                <label>Mật khẩu mới (Để trống nếu không đổi)</label>
                <input type="password" name="mat_khau" placeholder="******" style="width:100%; padding:8px; border:1px solid #ddd;">
            </div>
            <div style="margin-bottom:20px;">
                <label>Quyền hạn</label>
                <select name="vai_tro" id="edit_role" style="width:100%; padding:8px;">
                    <option value="user">Khách hàng</option>
                    <option value="admin">Quản trị viên</option>
                </select>
            </div>
            <div style="text-align:right;">
                <button type="button" onclick="closeModal('editModal')" style="padding:8px 20px; background:#6c757d; color:white; border:none; border-radius:4px; cursor:pointer;">Hủy</button>
                <button type="submit" style="padding:8px 20px; background:#ffc107; color:black; border:none; border-radius:4px; cursor:pointer;">Cập nhật</button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal(id) { document.getElementById(id).style.display = 'block'; }
function closeModal(id) { document.getElementById(id).style.display = 'none'; }

function openEditModal(id, name, email, phone, address, role) {
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_name').value = name;
    document.getElementById('edit_email').value = email;
    // Điền SĐT và Địa chỉ vào ô input
    document.getElementById('edit_phone').value = (phone == '---' ? '' : phone);
    document.getElementById('edit_address').value = (address == '---' ? '' : address);
    
    document.getElementById('edit_role').value = (role == 'admin' || role == '1') ? 'admin' : 'user';
    openModal('editModal');
}

function toggleLock(id, lock) {
    var fd = new FormData();
    fd.append('action', 'toggle_lock');
    fd.append('id', id);
    fd.append('lock', lock);
    fetch('', { method:'POST', body:fd }).then(r=>r.json()).then(res=>{
        if(res.success) location.reload();
        else alert(res.message);
    });
}

function deleteUser(id) {
    if(!confirm('Bạn chắc chắn muốn xóa?')) return;
    var fd = new FormData();
    fd.append('action', 'delete');
    fd.append('id', id);
    fetch('', { method:'POST', body:fd }).then(r=>r.json()).then(res=>{
        if(res.success) location.reload();
        else alert(res.message);
    });
}

document.getElementById('searchName').addEventListener('input', function(e){
    var txt = e.target.value.toLowerCase();
    var rows = document.querySelectorAll('.data-table tbody tr');
    rows.forEach(r => {
        var name = r.cells[1].textContent.toLowerCase();
        r.style.display = name.includes(txt) ? '' : 'none';
    });
});
</script>

<?php include 'footer.php'; ?>