<?php
session_start();
// Kết nối database
$conn = mysqli_connect("localhost", "root", "");

// Kiểm tra kết nối
if (!$conn) {
    die("Kết nối MySQL thất bại: " . mysqli_connect_error());
}

// Thử tạo database nếu chưa tồn tại
$sql = "CREATE DATABASE IF NOT EXISTS web_cay";
if (!mysqli_query($conn, $sql)) {
    die("Lỗi tạo database: " . mysqli_error($conn));
}

// Chọn database
if (!mysqli_select_db($conn, "web_cay")) {
    die("Lỗi chọn database: " . mysqli_error($conn));
}

// If Google OAuth client config exists, create the auth URL so the login page
// can link directly to Google (skips intermediate login.php page).
$login_url = null;
if (file_exists(__DIR__ . '/google-login/config.php')) {
  require_once __DIR__ . '/google-login/config.php';
  if (isset($client)) {
    // Force account chooser so user can pick which Google account to use
    if (method_exists($client, 'setPrompt')) {
      $client->setPrompt('select_account');
    }
    $login_url = $client->createAuthUrl();
  }
}

// Tạo bảng nguoi_dung nếu chưa tồn tại
$sql = "CREATE TABLE IF NOT EXISTS nguoi_dung (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ho_ten VARCHAR(100) NOT NULL,
    tendangnhap VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    mat_khau VARCHAR(255) NOT NULL,
    quyen VARCHAR(20) NOT NULL DEFAULT 'user',
    ngay_tao TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
if (!mysqli_query($conn, $sql)) {
    die("Lỗi tạo bảng nguoi_dung: " . mysqli_error($conn));
}

// Xử lý đăng nhập
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $usernameInput = trim($_POST['username'] ?? '');
    $passwordInput = $_POST['password'] ?? '';

    // Lấy danh sách cột bảng nguoi_dung để phát hiện tên cột thực tế
    $cols = [];
    $res = mysqli_query($conn, "SHOW COLUMNS FROM `nguoi_dung`");
    if ($res) {
        while ($r = mysqli_fetch_assoc($res)) $cols[] = $r['Field'];
        mysqli_free_result($res);
    }

    // Helper tìm cột
    $find_col = function(array $candidates) use ($cols) {
        foreach ($candidates as $c) {
            if (in_array($c, $cols)) return $c;
        }
        return null;
    };

    // Các ứng viên tên cột cho "username" và "password"
    $user_col = $find_col(['tendangnhap','username','user_name','ten_dang_nhap','email']);
    $pass_col = $find_col(['mat_khau','password','passwd','matkhau']);
    $khoa_col = $find_col(['khoa','is_locked','locked']);

    if (!$user_col) {
        $error = "Không tìm thấy cột tên đăng nhập trong cơ sở dữ liệu. Liên hệ quản trị.";
    } else {
        $sql = "SELECT * FROM `nguoi_dung` WHERE `{$user_col}` = ? LIMIT 1";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            $error = "Lỗi truy vấn: " . $conn->error;
        } else {
            $stmt->bind_param("s", $usernameInput);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result && $result->num_rows === 1) {
                $user_data = $result->fetch_assoc();
                
                // Nếu tìm được cột mật khẩu, so sánh hợp lý
                if ($pass_col && isset($user_data[$pass_col])) {
                    $stored = $user_data[$pass_col];
                    // nếu được hash bằng password_hash, dùng password_verify; ngược lại so sánh trực tiếp
                    if (password_verify($passwordInput, $stored) || $passwordInput === $stored) {
                        // Thiết lập session
                        $_SESSION['username'] = $usernameInput;
                        $_SESSION['user_id'] = $user_data['id'] ?? null;
                        
                        // map cột quyền (ưu tiên quyen trước vai_tro)
                        $role_col = $find_col(['quyen','vai_tro','role']);
                        $user_role = $role_col ? ($user_data[$role_col] ?? null) : null;
                        $_SESSION['role'] = $user_role;
                        
                        // Phân quyền tự động: admin -> trang quản trị, user -> trang chủ
                        if ($user_role === 'admin') {
                            $_SESSION['admin_logged_in'] = true;
                            $_SESSION['admin_username'] = $usernameInput;
                            $_SESSION['admin_id'] = $user_data['id'] ?? null;
                            header("Location: qtvtrangchu.php");
                        } else {
                            $redirect = isset($_SESSION['redirect_after_login']) ? $_SESSION['redirect_after_login'] : 'trangchu.php';
                            unset($_SESSION['redirect_after_login']);
                            header("Location: " . $redirect);
                        }
                        exit();
                    } else {
                        $error = "Tên đăng nhập hoặc mật khẩu không đúng. Vui lòng thử lại.";
                    }
                } else {
                    // Không có cột mật khẩu: từ chối đăng nhập an toàn
                    $error = "Cấu hình mật khẩu của hệ thống chưa chính xác. Liên hệ quản trị.";
                }
            } else {
                $error = "Tên đăng nhập hoặc mật khẩu không đúng. Vui lòng thử lại.";
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Đăng nhập</title>
</head>
<style>
  body {
    font-family: var(--font-base);
    font-size: 16px;
    line-height: 1.5;
    color: #1d3e1f;
    background-color: #91d270ff !important; 
    display: flex;
    justify-content: center; /* căn ngang */
    align-items: center;     /* căn dọc */
    min-height: 100vh;
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
    }

  .login-container {
    background: rgba(255, 255, 255, 0.95);
    padding: 40px 50px;
    border-radius: 15px;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
    width: 100%;
    max-width: 400px;
    transition: transform 0.3s ease;
  }

  .login-container:hover {
    transform: translateY(-5px);
  }

  h1 {
    text-align: center;
    color: #2d3748;
    margin-bottom: 30px;
    font-size: 2.2em;
    font-weight: 600;
  }

  form {
    display: flex;
    flex-direction: column;
    gap: 20px;
  }

  label {
    color: #4a5568;
    font-weight: 500;
    font-size: 0.95em;
  }

  input[type="text"],
  input[type="password"] {
    padding: 15px;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    font-size: 1em;
    transition: all 0.3s ease;
    outline: none;
  }

  input[type="text"]:focus,
  input[type="password"]:focus {
    border-color: #A64674;
    box-shadow: 0 0 0 3px rgba(166, 70, 116, 0.15);
  }

input[type="submit"] {
    background: linear-gradient(to right, #2f6f4e, #3f8f63); /* xanh rêu */
    color: white;
    padding: 15px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-size: 1em;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 1px;
    transition: all 0.3s ease;
    margin-top: 10px;
}

  input[type="submit"]:hover {
    background: linear-gradient(to right, #1f4f37, #2f6f4e); /* xanh rêu đậm hơn */
    transform: scale(0.97); /* hiệu ứng nhấn xuống */
    box-shadow: 0 3px 8px rgba(31, 79, 55, 0.5);
  }
  .signup-link {
    text-align: center;
    margin-top: 20px;
    display: block;
    color: #A64674;
    text-decoration: none;
    font-weight: 500;
    font-size: 1em;
    transition: all 0.3s ease;
  }

  .signup-link:hover {
    color: #F25D63;
    transform: translateY(-2px);
  }
</style>
<body>
  <div class="login-container">
    <h1>Đăng nhập</h1>
    <?php if(isset($error)) { ?>
        <p style="color: red; text-align: center;"><?php echo $error; ?></p>
    <?php } ?>
    <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
      <label for="username">Tên đăng nhập:</label>
      <input type="text" id="username" name="username" required>
      <p></p>
      <label for="password">Mật khẩu:</label>
      <input type="password" id="password" name="password" required>
      <p></p>
      <input type="submit" value="Đăng nhập">
    </form>

    <?php if (true): // show Google button when config exists; fallback link used below ?>
      <div style="text-align:center; margin-top:15px;">
        <a href="<?php echo htmlspecialchars($login_url ?? 'google-login/login.php', ENT_QUOTES); ?>">
          <button type="button" style="padding:12px 16px;background:#db4437;color:#fff;border:none;border-radius:8px;cursor:pointer;width:100%;font-weight:600;">Đăng nhập bằng Google</button>
        </a>
      </div>
    <?php endif; ?>

    <div style="text-align:center; margin-top:12px;">
      <a href="dangky.php" class="signup-link">Đăng ký tài khoản mới</a>
    </div>
  </div>
</body>
</html>
<!-- Duplicate floating Google button removed (we now show a red full-width button under the form) -->
