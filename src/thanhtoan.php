<?php
session_start();

// Bắt buộc đăng nhập trước khi thanh toán
if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_after_login'] = 'thanhtoan.php';
    header('Location: dangnhap.php');
    exit();
}

// Kiểm tra nếu không có sản phẩm trong giỏ hàng
if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    header('Location: giohang.php');
    exit();
}

// Xử lý khi form được submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Lưu thông tin đơn hàng vào session
    $_SESSION['order'] = [
        'ten_khach_hang' => $_POST['ten_khach_hang'],
        'dia_chi' => $_POST['dia_chi'],
        'so_dien_thoai' => $_POST['so_dien_thoai'],
        'phuong_thuc_thanh_toan' => $_POST['phuong_thuc_thanh_toan']
    ];
    
    // Xóa giỏ hàng
    unset($_SESSION['cart']);
    
    // Chuyển hướng đến trang thành công
    header('Location: thanhtoan_thanhcong.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thanh Toán - Shop - Thời trang</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .checkout-container {
            max-width: 1000px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .checkout-form, .order-summary {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }

        h2 {
            color: #667eea;
            margin-bottom: 25px;
            font-size: 24px;
            border-bottom: 2px solid #667eea;
            padding-bottom: 10px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }

        input[type="text"],
        input[type="tel"],
        select {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 15px;
            transition: border-color 0.3s;
        }

        input:focus, select:focus {
            outline: none;
            border-color: #667eea;
        }

        .promo-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .promo-input-group {
            display: flex;
            gap: 10px;
        }

        .promo-input-group input {
            flex: 1;
        }

        .promo-apply-btn {
            background: #059669;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: background 0.3s;
        }

        .promo-apply-btn:hover {
            background: #047857;
        }

        .promo-applied {
            margin-top: 15px;
            padding: 12px;
            background: #d1fae5;
            border-left: 4px solid #059669;
            border-radius: 5px;
            display: none;
        }

        .promo-applied.show {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .promo-remove {
            background: #ef4444;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 13px;
        }

        .order-items {
            max-height: 300px;
            overflow-y: auto;
            margin-bottom: 20px;
        }

        .order-item {
            display: flex;
            justify-content: space-between;
            padding: 12px;
            border-bottom: 1px solid #e0e0e0;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            font-size: 16px;
        }

        .summary-row.discount {
            color: #059669;
            font-weight: 600;
        }

        .summary-row.total {
            font-size: 20px;
            font-weight: bold;
            color: #667eea;
            border-top: 2px solid #e0e0e0;
            padding-top: 15px;
            margin-top: 10px;
        }

        .submit-btn {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            width: 100%;
            font-size: 18px;
            font-weight: 600;
            transition: transform 0.3s ease, box-shadow 0.3s;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }

        @media (max-width: 768px) {
            .checkout-container {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="checkout-container">
        <div class="checkout-form">
            <h2><i class="fas fa-shopping-bag"></i> Thông tin thanh toán</h2>
            <form method="POST" action="" id="checkoutForm">
            <div class="form-group">
                <label for="ten_khach_hang"><i class="fas fa-user"></i> Họ và tên:</label>
                <input type="text" id="ten_khach_hang" name="ten_khach_hang" required>
            </div>

            <div class="form-group">
                <label for="dia_chi"><i class="fas fa-map-marker-alt"></i> Địa chỉ:</label>
                <input type="text" id="dia_chi" name="dia_chi" required>
            </div>

            <div class="form-group">
                <label for="so_dien_thoai"><i class="fas fa-phone"></i> Số điện thoại:</label>
                <input type="tel" id="so_dien_thoai" name="so_dien_thoai" required>
            </div>

            <div class="form-group">
                <label for="phuong_thuc_thanh_toan"><i class="fas fa-credit-card"></i> Phương thức thanh toán:</label>
                <select id="phuong_thuc_thanh_toan" name="phuong_thuc_thanh_toan" required>
                    <option value="cod">Thanh toán khi nhận hàng (COD)</option>
                    <option value="banking">Chuyển khoản ngân hàng</option>
                    <option value="momo">Ví MoMo</option>
                </select>
            </div>

            <!-- Hidden fields for promo -->
            <input type="hidden" id="promo_code" name="promo_code" value="">
            <input type="hidden" id="promo_discount" name="promo_discount" value="0">

            <button type="submit" class="submit-btn">
                <i class="fas fa-check-circle"></i> Xác nhận thanh toán
            </button>
        </form>
        </div>

        <div class="order-summary">
            <h2><i class="fas fa-receipt"></i> Đơn hàng của bạn</h2>
            
            <div class="order-items" id="orderItems">
                <!-- Items will be loaded by JavaScript -->
            </div>

            <div class="promo-section">
                <label><i class="fas fa-tags"></i> Mã khuyến mãi:</label>
                <div class="promo-input-group">
                    <input type="text" id="promoInput" placeholder="Nhập mã khuyến mãi">
                    <button type="button" class="promo-apply-btn" onclick="applyPromo()">
                        <i class="fas fa-check"></i> Áp dụng
                    </button>
                </div>
                <div class="promo-applied" id="promoApplied">
                    <span id="promoText"></span>
                    <button type="button" class="promo-remove" onclick="removePromo()">
                        <i class="fas fa-times"></i> Xóa
                    </button>
                </div>
            </div>

            <div class="summary-totals">
                <div class="summary-row">
                    <span>Tạm tính:</span>
                    <span id="subtotal">0đ</span>
                </div>
                <div class="summary-row discount" id="discountRow" style="display: none;">
                    <span>Giảm giá:</span>
                    <span id="discountAmount">-0đ</span>
                </div>
                <div class="summary-row total">
                    <span>Tổng cộng:</span>
                    <span id="totalAmount">0đ</span>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        let appliedPromo = null;
        let cartItems = [];
        let subtotalAmount = 0;

        // Load cart items
        function loadCart() {
            const cart = localStorage.getItem('myshop_cart_items');
            if (cart) {
                cartItems = JSON.parse(cart);
                displayCartItems();
                calculateTotal();
            }
        }

        function displayCartItems() {
            const container = document.getElementById('orderItems');
            container.innerHTML = '';
            
            cartItems.forEach(item => {
                const itemDiv = document.createElement('div');
                itemDiv.className = 'order-item';
                itemDiv.innerHTML = `
                    <div>
                        <strong>${item.ten_san_pham}</strong><br>
                        <small>Số lượng: ${item.so_luong}</small>
                    </div>
                    <div><strong>${formatCurrency(item.gia * item.so_luong)}</strong></div>
                `;
                container.appendChild(itemDiv);
            });
        }

        function calculateTotal() {
            subtotalAmount = cartItems.reduce((sum, item) => sum + (item.gia * item.so_luong), 0);
            document.getElementById('subtotal').textContent = formatCurrency(subtotalAmount);
            updateFinalTotal();
        }

        function updateFinalTotal() {
            const discount = appliedPromo ? appliedPromo.discount : 0;
            const total = subtotalAmount - discount;
            
            if (discount > 0) {
                document.getElementById('discountRow').style.display = 'flex';
                document.getElementById('discountAmount').textContent = '-' + formatCurrency(discount);
            } else {
                document.getElementById('discountRow').style.display = 'none';
            }
            
            document.getElementById('totalAmount').textContent = formatCurrency(total);
        }

        function applyPromo() {
            const code = document.getElementById('promoInput').value.trim();
            if (!code) {
                alert('Vui lòng nhập mã khuyến mãi');
                return;
            }

            $.ajax({
                url: 'ajax_validate_promo.php',
                method: 'POST',
                data: {
                    promo_code: code,
                    cart_items: JSON.stringify(cartItems),
                    total: subtotalAmount
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        appliedPromo = {
                            code: code,
                            discount: response.discount,
                            final_total: response.final_total
                        };
                        
                        document.getElementById('promo_code').value = code;
                        document.getElementById('promo_discount').value = response.discount;
                        
                        document.getElementById('promoText').innerHTML = 
                            `<i class="fas fa-check-circle"></i> Mã <strong>${code}</strong> - Giảm ${formatCurrency(response.discount)}`;
                        document.getElementById('promoApplied').classList.add('show');
                        document.getElementById('promoInput').value = '';
                        
                        updateFinalTotal();
                        alert('Áp dụng mã thành công!');
                    } else {
                        alert(response.message || 'Mã không hợp lệ');
                    }
                },
                error: function() {
                    alert('Có lỗi xảy ra khi áp dụng mã');
                }
            });
        }

        function removePromo() {
            appliedPromo = null;
            document.getElementById('promo_code').value = '';
            document.getElementById('promo_discount').value = '0';
            document.getElementById('promoApplied').classList.remove('show');
            updateFinalTotal();
        }

        function formatCurrency(amount) {
            return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(amount);
        }

        // Load cart on page load
        document.addEventListener('DOMContentLoaded', loadCart);
    </script>

<link rel="stylesheet" href="assets/chatbot.css">
<link rel="stylesheet" href="assets/notifications.css">
<script src="assets/notifications.js" defer></script>
<script src="assets/chatbot.js" defer></script>
</body>
</html> 
