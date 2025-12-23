# Hệ Thống Đánh Giá Sản Phẩm - Hướng Dẫn

## Cập Nhật Mới

### 1. Giỏ Hàng Sử Dụng LocalStorage
- **Trước**: Sử dụng `$_SESSION['cart']` trên server
- **Sau**: Sử dụng `localStorage` (key: `myshop_cart_items`) trên trình duyệt
- **Lợi ích**: 
  - Không cần session
  - Giỏ hàng vẫn tồn tại khi đóng trình duyệt
  - Tốc độ nhanh hơn
  - Giảm tải cho server

### 2. Đánh Giá Chỉ Sau Khi Mua Hàng
- **Yêu cầu**: Chỉ khách hàng đã mua và nhận hàng (trạng thái: "Đã giao") mới được đánh giá
- **Kiểm tra**: File `chitiet_san_pham.php` sẽ kiểm tra trong bảng `don_hang` và `chi_tiet_don_hang`
- **Hiển thị**:
  - Nếu đã mua: Hiển thị nút "Đánh giá ngay tại Đơn hàng của tôi" (màu xanh)
  - Nếu chưa mua: Hiển thị thông báo "Chỉ khách hàng đã mua và nhận hàng mới có thể đánh giá" (màu vàng)

### 3. Liên Kết Giữa Các Trang
```
┌─────────────────────────────────────────────────────────────┐
│                  LUỒNG ĐÁNH GIÁ SẢN PHẨM                    │
└─────────────────────────────────────────────────────────────┘

1. KHÁCH HÀNG:
   chitiet_san_pham.php (Xem sản phẩm)
   → Kiểm tra đã mua hàng chưa
   → Nếu đã mua: Hiển thị link đến don_hang_cua_toi.php
   → don_hang_cua_toi.php (Xem đơn hàng)
   → Bấm nút "Đánh giá" trên đơn hàng đã giao
   → Popup form đánh giá
   → Gửi đến xu_ly_danh_gia.php
   → INSERT vào bảng danh_gia

2. ADMIN:
   qldanhgia.php (Quản lý đánh giá)
   → Xem danh sách đánh giá từ bảng danh_gia
   → Bấm nút "Phản hồi"
   → Popup form phản hồi
   → UPDATE admin_reply vào bảng danh_gia

3. REAL-TIME UPDATE:
   chitiet_san_pham.php
   → JavaScript tự động gọi ajax_load_reviews.php mỗi 10 giây
   → Cập nhật danh sách đánh giá + admin_reply mới nhất
```

## File Quan Trọng

### 1. `chitiet_san_pham.php`
- Kiểm tra `$has_purchased` (đã mua và giao hàng chưa)
- Hiển thị link đến `don_hang_cua_toi.php` nếu đã mua
- Tự động refresh đánh giá mỗi 10 giây
- Hiển thị admin_reply trong từng đánh giá

### 2. `don_hang_cua_toi.php`
- Hiển thị nút "Đánh giá" cho đơn hàng đã giao
- Form đánh giá sản phẩm (5 sao + bình luận)
- Gửi đánh giá qua AJAX đến `xu_ly_danh_gia.php`

### 3. `xu_ly_danh_gia.php`
- Kiểm tra user đã mua sản phẩm chưa
- Kiểm tra đơn hàng phải có trạng thái "Đã giao"
- Kiểm tra không cho đánh giá trùng
- INSERT vào bảng `danh_gia`

### 4. `admin/qldanhgia.php`
- Hiển thị danh sách đánh giá từ bảng `danh_gia`
- Form phản hồi đánh giá (admin_reply)
- UPDATE admin_reply vào bảng `danh_gia`
- Xóa đánh giá (nếu cần)

### 5. `ajax_load_reviews.php`
- API lấy danh sách đánh giá mới nhất
- Trả về HTML đã render sẵn
- Được gọi tự động mỗi 10 giây

### 6. `ajax_review_notifications.php`
- API lấy thông báo phản hồi đánh giá từ admin
- Hiển thị cho khách hàng khi admin phản hồi

## Cấu Trúc Bảng `danh_gia`

```sql
CREATE TABLE danh_gia (
    id INT AUTO_INCREMENT PRIMARY KEY,
    san_pham_id INT NOT NULL,
    user_id INT NULL,
    user_email VARCHAR(255) NULL,
    user_name VARCHAR(150) NOT NULL,
    rating TINYINT NOT NULL DEFAULT 5,
    comment TEXT NOT NULL,
    admin_reply TEXT NULL,           -- Phản hồi từ admin
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX(san_pham_id),
    INDEX(user_id),
    INDEX(user_email)
);
```

## LocalStorage Cart Structure

```javascript
// Key: myshop_cart_items
// Value: JSON Array
[
    {
        "id": 1,
        "name": "Cây Bonsai",
        "price": 500000,
        "quantity": 2,
        "image": "uploads/bonsai.jpg"
    },
    {
        "id": 5,
        "name": "Cây Phát Tài",
        "price": 350000,
        "quantity": 1,
        "image": "uploads/phattai.jpg"
    }
]
```

## Cách Test

### 1. Test Giỏ Hàng LocalStorage
```javascript
// Mở Console trong Chrome (F12)
// Xem giỏ hàng hiện tại:
localStorage.getItem('myshop_cart_items')

// Xóa giỏ hàng:
localStorage.removeItem('myshop_cart_items')

// Thêm sản phẩm thủ công:
localStorage.setItem('myshop_cart_items', JSON.stringify([
    {id: 1, name: "Test Product", price: 100000, quantity: 1, image: "test.jpg"}
]))
```

### 2. Test Đánh Giá
1. Đăng nhập với tài khoản khách hàng
2. Tạo đơn hàng mua sản phẩm
3. Admin đổi trạng thái đơn hàng thành "Đã giao"
4. Quay lại trang chi tiết sản phẩm → Thấy nút "Đánh giá ngay"
5. Vào "Đơn hàng của tôi" → Bấm "Đánh giá"
6. Điền form và gửi
7. Quay lại trang chi tiết sản phẩm → Thấy đánh giá xuất hiện

### 3. Test Phản Hồi Admin
1. Đăng nhập admin
2. Vào `admin/qldanhgia.php`
3. Bấm nút "Phản hồi" trên đánh giá
4. Nhập nội dung phản hồi và gửi
5. Quay lại trang chi tiết sản phẩm → Thấy phản hồi hiển thị dưới đánh giá
6. Đợi 10 giây → Phản hồi tự động cập nhật (không cần F5)

## Lưu Ý Quan Trọng

1. **Xóa Session Cart Cũ**:
   - Không còn sử dụng `$_SESSION['cart']`
   - Tất cả dữ liệu giỏ hàng nằm trên localStorage

2. **Bảng `binh_luan` vs `danh_gia`**:
   - `binh_luan`: Bảng cũ (không dùng nữa)
   - `danh_gia`: Bảng mới có cột `admin_reply`

3. **Real-time Update**:
   - JavaScript tự động refresh mỗi 10 giây
   - Không làm reload toàn trang
   - Chỉ cập nhật phần đánh giá

4. **Bảo Mật**:
   - Kiểm tra đã mua hàng trước khi cho đánh giá
   - Kiểm tra trạng thái "Đã giao"
   - Không cho đánh giá trùng lặp

## Troubleshooting

### Không thấy nút "Đánh giá ngay"?
- Kiểm tra đã đăng nhập chưa
- Kiểm tra đơn hàng có trạng thái "Đã giao" chưa
- Kiểm tra sản phẩm có trong đơn hàng đã giao chưa

### Đánh giá không xuất hiện?
- Mở Console (F12) xem có lỗi JavaScript không
- Kiểm tra file `ajax_load_reviews.php` có lỗi không
- Kiểm tra bảng `danh_gia` có dữ liệu không

### Admin phản hồi không hiển thị?
- Đợi 10 giây để auto-refresh
- Hoặc F5 trang
- Kiểm tra cột `admin_reply` trong bảng `danh_gia`

## Liên Hệ Hỗ Trợ
Nếu có vấn đề, hãy kiểm tra:
1. Browser Console (F12) → Tab Console
2. PHP Error Log: `C:\xampp\apache\logs\error.log`
3. Database: Kiểm tra bảng `danh_gia` có dữ liệu không
