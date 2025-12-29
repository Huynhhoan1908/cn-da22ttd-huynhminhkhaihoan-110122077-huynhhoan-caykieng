<?php
session_start();

// 1. Kết nối CSDL
$conn = mysqli_connect("localhost", "root", "", "web_cay");

// Kiểm tra kết nối
if (!$conn) {
    die("Lỗi kết nối Database: " . mysqli_connect_error());
}
// Thiết lập font chữ tiếng Việt
mysqli_set_charset($conn, "utf8mb4");

// 2. Cấu hình Google (Giữ nguyên)
define('GOOGLE_CLIENT_ID', 'YOUR_GOOGLE_CLIENT_ID');
$google_enabled = (GOOGLE_CLIENT_ID !== 'YOUR_GOOGLE_CLIENT_ID');
$login_url = null;
if (file_exists(__DIR__ . '/google-login/config.php')) {
    require_once __DIR__ . '/google-login/config.php';
    if (isset($client)) $login_url = $client->createAuthUrl();
}

// 3. Xử lý Form Đăng ký
$error = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Lấy dữ liệu và lọc ký tự đặc biệt để tránh lỗi SQL
    $ho_ten = mysqli_real_escape_string($conn, $_POST['hoten'] ?? '');
    $ten_dang_nhap = mysqli_real_escape_string($conn, $_POST['tendangnhap'] ?? '');
    $email = mysqli_real_escape_string($conn, $_POST['email'] ?? '');
    $sdt = mysqli_real_escape_string($conn, $_POST['so_dien_thoai'] ?? ''); 
    $dia_chi = mysqli_real_escape_string($conn, $_POST['dia_chi'] ?? '');   
    $mat_khau = $_POST['mat_khau'] ?? '';

    // Kiểm tra dữ liệu rỗng
    if ($mat_khau === '' || $ten_dang_nhap === '' || $email === '') {
        $error = "Vui lòng điền đầy đủ các trường bắt buộc (*).";
    } else {
        // --- CÂU LỆNH SQL KIỂM TRA TRÙNG LẶP ---
        // Sử dụng đúng tên cột: ten_dang_nhap, email
        $check_sql = "SELECT id FROM nguoi_dung WHERE ten_dang_nhap = '$ten_dang_nhap' OR email = '$email'";
        $check = mysqli_query($conn, $check_sql);

        // --- ĐOẠN DEBUG QUAN TRỌNG: Bắt lỗi nếu câu lệnh sai ---
        if ($check === false) {
            die("<div style='background:red;color:white;padding:20px;margin:20px;'>
                <h3>LỖI TRUY VẤN SQL (Hãy chụp ảnh này gửi cho tôi):</h3>
                <p>" . mysqli_error($conn) . "</p>
                <p>Câu lệnh chạy: $check_sql</p>
            </div>");
        }

        if (mysqli_num_rows($check) > 0) {
            $error = "Tên đăng nhập hoặc Email đã tồn tại!";
        } else {
            // Mã hóa mật khẩu
            $hashed_pass = password_hash($mat_khau, PASSWORD_DEFAULT);
            
            // --- CÂU LỆNH INSERT CHUẨN ---
            // Khớp với các cột trong ảnh: ho_ten, ten_dang_nhap, email, so_dien_thoai, dia_chi, mat_khau, quyen
            $sql = "INSERT INTO nguoi_dung (ho_ten, ten_dang_nhap, email, so_dien_thoai, dia_chi, mat_khau, quyen) 
                    VALUES (?, ?, ?, ?, ?, ?, 'user')";
            
            $stmt = mysqli_prepare($conn, $sql);
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "ssssss", $ho_ten, $ten_dang_nhap, $email, $sdt, $dia_chi, $hashed_pass);
                if (mysqli_stmt_execute($stmt)) {
                    // Đăng ký thành công -> Xóa localStorage giỏ hàng phía client bằng JS rồi chuyển hướng
                    echo '<script>';
                    echo 'Object.keys(localStorage).forEach(function(k){ if(k.startsWith("myshop_cart_")) localStorage.removeItem(k); });';
                    echo 'window.location.href = "dangnhap.php?registered=1";';
                    echo '</script>';
                    exit();
                } else {
                    $error = "Lỗi lưu dữ liệu: " . mysqli_stmt_error($stmt);
                }
            } else {
                $error = "Lỗi chuẩn bị SQL: " . mysqli_error($conn);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Đăng Ký Tài Khoản</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
body { font-family: 'Segoe UI', sans-serif; background: #e9f5e9; min-height: 100vh; display: flex; align-items: center; justify-content: center; margin: 0; }
.register-card { background: white; padding: 40px; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); width: 100%; max-width: 500px; }
.title { text-align: center; font-size: 24px; font-weight: bold; color: #1d3e1f; margin-bottom: 25px; }
.form-group { margin-bottom: 15px; }
.form-group label { display: block; margin-bottom: 5px; color: #555; font-weight: 500; }
.form-control { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box; transition: 0.2s; }
.form-control:focus { border-color: #27ae60; outline: none; }
.btn-submit { width: 100%; padding: 12px; background: #27ae60; color: white; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; transition: 0.2s; }
.btn-submit:hover { background: #219150; }
.alert { padding: 10px; background: #f8d7da; color: #721c24; border-radius: 6px; margin-bottom: 15px; font-size: 14px; }
.gg-btn { background: #db4437; margin-top: 10px; }
.login-link { text-align: center; margin-top: 15px; font-size: 14px; }
.login-link a { color: #27ae60; text-decoration: none; font-weight: 600; }
</style>
</head>
<body>

<div class="register-card">
  <div class="title">Đăng Ký Tài Khoản</div>

  <?php if (!empty($error)): ?>
    <div class="alert"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
  <?php endif; ?>

  <form method="POST" onsubmit="return validateForm()">
    <div class="form-group">
      <label>Họ và tên *</label>
      <input name="hoten" class="form-control" required placeholder="Nhập họ tên đầy đủ">
    </div>
    
    <div class="form-group">
      <label>Tên đăng nhập *</label>
      <input name="tendangnhap" class="form-control" required placeholder="Viết liền không dấu">
    </div>

    <div class="form-group">
      <label>Email *</label>
      <input name="email" type="email" class="form-control" required placeholder="email@example.com">
    </div>

    <div class="form-group">
      <label>Số điện thoại</label>
      <input name="so_dien_thoai" type="text" class="form-control" placeholder="090...">
    </div>

    <div class="form-group">
      <label>Địa chỉ</label>
      <input name="dia_chi" type="text" class="form-control" placeholder="Số nhà, đường...">
    </div>

    <div class="form-group">
      <label>Mật khẩu *</label>
      <input name="mat_khau" id="mat_khau" type="password" class="form-control" required>
    </div>

    <div class="form-group">
      <label>Xác nhận mật khẩu *</label>
      <input id="xac_nhan_mat_khau" type="password" class="form-control" required>
    </div>

    <button type="submit" class="btn-submit">ĐĂNG KÝ</button>
  </form>

  <?php if ($google_enabled): ?>
    <a href="<?php echo htmlspecialchars($login_url); ?>" style="text-decoration:none;">
        <button class="btn-submit gg-btn"><i class="fab fa-google"></i> Đăng ký bằng Google</button>
    </a>
  <?php endif; ?>

  <div class="login-link">
    Đã có tài khoản? <a href="dangnhap.php">Đăng nhập ngay</a>
  </div>
</div>

<script>
function validateForm() {
  var p = document.getElementById('mat_khau').value;
  var c = document.getElementById('xac_nhan_mat_khau').value;
  if (p !== c) { alert('Mật khẩu xác nhận không khớp!'); return false; }
  return true;
}
</script>

</body>
</html>