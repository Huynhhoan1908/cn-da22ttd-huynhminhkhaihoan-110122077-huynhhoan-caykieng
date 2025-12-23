# Hướng Dẫn Sử Dụng - Hệ Thống Cập Nhật

## 🎉 Các Tính Năng Mới

### 1. **Chatbot Đã Được Fix**
- ✅ Giao diện mới với màu xanh lá phù hợp theme cây cảnh
- ✅ Nút chat tròn đẹp mắt ở góc dưới phải màn hình
- ✅ Cửa sổ chat rộng hơn, dễ nhìn hơn
- ✅ Hiệu ứng hover và animation mượt mà

**Cách sử dụng:**
- Click vào biểu tượng 💬 ở góc dưới phải
- Nhập tin nhắn và gửi cho admin
- Admin sẽ trả lời trực tiếp qua admin panel

### 2. **Tự Động Trừ Kho Khi Bán Hàng**
- ✅ Khi khách hàng đặt hàng, số lượng tồn kho tự động giảm
- ✅ Áp dụng cho cả `san_pham.so_luong`
- ✅ Có log chi tiết trong error.log để kiểm tra

**Log mẫu:**
```
✅ Đã trừ kho: Product ID=5, Số lượng=2, Order ID=123
```

**Kiểm tra:**
1. Vào **Admin → Quản Lý Sản Phẩm** → xem số lượng
2. Khách mua hàng
3. F5 trang sản phẩm → số lượng đã giảm

### 3. **Hệ Thống Khuyến Mãi Hoàn Chỉnh** ⭐

#### 3.1. Admin Quản Lý Khuyến Mãi

**Truy cập:** `admin/qlkhuyenmai.php`

**Tạo mã khuyến mãi:**
1. Click **"Thêm Mã Khuyến Mãi"**
2. Điền thông tin:
   - **Mã khuyến mãi**: VD: `NEWYEAR2025` (viết hoa, không dấu)
   - **Tên khuyến mãi**: VD: "Khuyến mãi Tết 2025"
   - **Loại giảm giá**: Phần trăm (%) hoặc Số tiền (đ)
   - **Giá trị giảm**: VD: 15 (nếu %), hoặc 50000 (nếu số tiền)
   - **Đơn tối thiểu**: VD: 500000 (đơn từ 500K mới được dùng)
   - **Giảm tối đa**: VD: 200000 (giảm tối đa 200K cho mã %)
   - **Số lượng mã**: Để trống = không giới hạn
   - **Áp dụng cho**:
     - **Tất cả sản phẩm**: Mọi đơn hàng đều dùng được
     - **Theo danh mục**: Chỉ sản phẩm thuộc danh mục chọn
     - **Theo sản phẩm**: Chỉ sản phẩm cụ thể được chọn
   - **Thời gian**: Từ ngày ... đến ngày ...
   - **Kích hoạt ngay**: Tick để mã hoạt động ngay

3. Click **"Lưu"**

**Quản lý mã:**
- ✏️ **Sửa**: Click icon bút chì
- 🗑️ **Xóa**: Click icon thùng rác
- 🔘 **Bật/Tắt**: Toggle switch để bật/tắt mã

**Thống kê:**
- Số lần đã sử dụng
- Số lượng còn lại (nếu có giới hạn)
- Trạng thái: Đang hoạt động / Tạm dừng / Đã hết hạn / Sắp diễn ra

#### 3.2. Khách Hàng Sử Dụng Mã

**Tại trang giỏ hàng** (`giohang.php`):

1. Thêm sản phẩm vào giỏ
2. Kéo xuống phần **"Mã giảm giá"**
3. Nhập mã khuyến mãi (VD: `NEWYEAR2025`)
4. Click biểu tượng 🏷️ (tag)
5. Hệ thống sẽ:
   - ✅ Kiểm tra mã có tồn tại không
   - ✅ Kiểm tra mã còn hạn không
   - ✅ Kiểm tra đơn hàng đủ tối thiểu không
   - ✅ Kiểm tra sản phẩm có áp dụng được không
   - ✅ Tính toán giá trị giảm
   - ✅ Hiển thị badge mã đã áp dụng
   - ✅ Cập nhật tổng tiền

**Ví dụ hiển thị:**
```
✅ NEWYEAR2025
   Khuyến mãi Tết 2025
   [Xóa]

Tạm tính: 1.000.000đ
Vận chuyển: Miễn phí
🏷️ Giảm giá (NEWYEAR2025): -150.000đ
━━━━━━━━━━━━━━━━━━━━━
Tổng cộng: 850.000đ
```

6. Click **"Tiến hành thanh toán"**
7. Điền thông tin → Đặt hàng
8. Mã khuyến mãi sẽ tự động được ghi vào đơn hàng

#### 3.3. Các Loại Mã Khuyến Mãi

**1. Mã giảm theo phần trăm (%)**
```
Mã: GIAM15
Giảm: 15%
Đơn tối thiểu: 300.000đ
Giảm tối đa: 100.000đ

Ví dụ:
- Đơn 500.000đ → Giảm 75.000đ (15%)
- Đơn 1.000.000đ → Giảm 100.000đ (max)
```

**2. Mã giảm cố định (số tiền)**
```
Mã: GIAM50K
Giảm: 50.000đ
Đơn tối thiểu: 200.000đ

Ví dụ:
- Đơn 250.000đ → Giảm 50.000đ
- Đơn 1.000.000đ → Giảm 50.000đ
```

**3. Mã theo danh mục**
```
Mã: CAYCẢNH20
Giảm: 20%
Áp dụng: Chỉ danh mục "Cây cảnh"

→ Khách mua cây phong thủy → KHÔNG được dùng
→ Khách mua cây cảnh → Được dùng
```

**4. Mã theo sản phẩm**
```
Mã: BONSAI100K
Giảm: 100.000đ
Áp dụng: Chỉ sản phẩm "Cây Bonsai"

→ Giỏ hàng có Bonsai → Được dùng
→ Giỏ hàng không có Bonsai → KHÔNG được dùng
```

**5. Mã có giới hạn số lượng**
```
Mã: FLASH50
Giảm: 50%
Số lượng: 100 mã

→ 100 người đầu tiên dùng
→ Người thứ 101 → Hết lượt
```

## 📊 Cấu Trúc Database

### Bảng `khuyen_mai`
```sql
id, ma_khuyen_mai, ten_khuyen_mai, mo_ta,
loai_giam (phan_tram/so_tien),
gia_tri_giam, gia_tri_don_toi_thieu, gia_tri_giam_toi_da,
so_luong_ma, so_lan_da_dung,
loai_ap_dung (tat_ca/danh_muc/san_pham),
ngay_bat_dau, ngay_ket_thuc, trang_thai
```

### Bảng `khuyen_mai_danh_muc`
```sql
id, khuyen_mai_id, danh_muc_id
```

### Bảng `khuyen_mai_san_pham`
```sql
id, khuyen_mai_id, san_pham_id
```

### Bảng `lich_su_khuyen_mai`
```sql
id, khuyen_mai_id, don_hang_id, nguoi_dung_id,
gia_tri_giam, ngay_su_dung
```

### Cột mới trong `don_hang`
```sql
ALTER TABLE don_hang 
ADD COLUMN ma_khuyen_mai VARCHAR(50) DEFAULT NULL,
ADD COLUMN giam_gia DECIMAL(10,2) DEFAULT 0;
```

## 🚀 Cách Cài Đặt

### Bước 1: Import SQL
```sql
-- Chạy file này trong phpMyAdmin
mysql> source C:/xampp/htdocs/WebCN/database_khuyen_mai.sql
```

### Bước 2: Kiểm tra Files
```
✅ admin/qlkhuyenmai.php
✅ ajax_validate_promo.php
✅ giohang.php (đã cập nhật)
✅ process_order.php (đã cập nhật)
✅ assets/chatbot.css (đã cập nhật)
```

### Bước 3: Test Chatbot
1. Vào bất kỳ trang nào (trangchu.php, san-pham.php...)
2. Kiểm tra góc dưới phải có nút chat 💬
3. Click vào → Cửa sổ chat hiện ra
4. Gửi tin nhắn test

### Bước 4: Test Khuyến Mãi

**Tạo mã test:**
1. Đăng nhập Admin
2. Vào **Quản Lý Khuyến Mãi**
3. Thêm mã: `TEST50K`, giảm 50.000đ, đơn tối thiểu 100.000đ
4. Lưu và kích hoạt

**Dùng mã:**
1. Đăng xuất admin, đăng nhập khách hàng
2. Thêm sản phẩm vào giỏ (tổng > 100K)
3. Vào giỏ hàng
4. Nhập mã `TEST50K` → Click 🏷️
5. Thấy giảm 50.000đ
6. Đặt hàng
7. Vào **Đơn hàng của tôi** → Xem đơn vừa đặt
8. Quay lại Admin → **Quản Lý Khuyến Mãi**
9. Thấy "Đã sử dụng: 1 lần"

### Bước 5: Test Trừ Kho
1. Admin → **Quản Lý Sản Phẩm**
2. Xem sản phẩm có tồn kho = 10
3. Khách mua 3 sản phẩm đó
4. F5 trang Quản Lý Sản Phẩm
5. Tồn kho còn 7 ✅

## 🐛 Troubleshooting

### 1. Chatbot không hiện?
- **Kiểm tra**: Có file `assets/chatbot.css` không?
- **Kiểm tra**: Có dòng `<script src="assets/chatbot.js">` trong footer không?
- **Fix**: F12 → Console → xem có lỗi JS không

### 2. Mã khuyến mãi không áp dụng được?
- ✅ Kiểm tra mã còn hiệu lực (ngày bắt đầu - kết thúc)
- ✅ Kiểm tra trạng thái = "Đang hoạt động"
- ✅ Kiểm tra đơn hàng đủ tối thiểu chưa
- ✅ Kiểm tra sản phẩm có thuộc loại áp dụng không
- ✅ F12 → Network → Xem response từ `ajax_validate_promo.php`

### 3. Không trừ kho khi bán?
- **Kiểm tra**: File `C:\xampp\apache\logs\error.log`
- **Tìm**: Dòng có "✅ Đã trừ kho" hoặc "❌ KHO UPDATE FAILED"
- **Nguyên nhân**: Có thể sản phẩm không đủ tồn kho

### 4. Lỗi SQL khi import?
```sql
-- Nếu lỗi "Table already exists"
DROP TABLE IF EXISTS lich_su_khuyen_mai;
DROP TABLE IF EXISTS khuyen_mai_san_pham;
DROP TABLE IF EXISTS khuyen_mai_danh_muc;
DROP TABLE IF EXISTS khuyen_mai;

-- Sau đó chạy lại file SQL
```

### 5. Admin không thấy menu "Khuyến Mãi"?
- **Kiểm tra**: File `admin/header.php` có dòng:
```php
<a href="qlkhuyenmai.php" class="<?php echo ($current_page == 'promotions') ? 'active' : ''; ?>">
    <i class="fas fa-tags"></i>
    <span>Khuyến Mãi</span>
</a>
```

## 📈 Thống Kê & Báo Cáo

### Xem mã đã dùng bao nhiêu lần
```sql
SELECT 
    km.ma_khuyen_mai,
    km.ten_khuyen_mai,
    COUNT(lskm.id) as so_lan_su_dung,
    SUM(lskm.gia_tri_giam) as tong_da_giam
FROM khuyen_mai km
LEFT JOIN lich_su_khuyen_mai lskm ON km.id = lskm.khuyen_mai_id
GROUP BY km.id;
```

### Top khách hàng dùng mã nhiều nhất
```sql
SELECT 
    nd.ho_ten,
    COUNT(lskm.id) as so_lan_dung_ma,
    SUM(lskm.gia_tri_giam) as tong_tiet_kiem
FROM lich_su_khuyen_mai lskm
INNER JOIN nguoi_dung nd ON lskm.nguoi_dung_id = nd.id
GROUP BY lskm.nguoi_dung_id
ORDER BY so_lan_dung_ma DESC
LIMIT 10;
```

### Doanh thu thực tế (sau giảm giá)
```sql
SELECT 
    SUM(tong_thanh_toan) as doanh_thu_thuc_te,
    SUM(giam_gia) as tong_da_giam,
    SUM(tong_tien) as doanh_thu_truoc_giam
FROM don_hang
WHERE trang_thai != 'Đã hủy';
```

## 💡 Tips & Best Practices

### 1. Tạo mã khuyến mãi hiệu quả
- ✅ Mã ngắn gọn, dễ nhớ (VD: `TET2025`, `FLASH50`)
- ✅ Đặt tên mô tả rõ ràng
- ✅ Giới hạn thời gian để tạo cảm giác khan hiếm
- ✅ Đặt đơn tối thiểu hợp lý (500K - 1.000K)
- ✅ Giảm tối đa để tránh lỗ (100K - 200K)

### 2. Chiến lược khuyến mãi
- 📅 **Theo mùa**: Tết, 8/3, 20/10, Noel
- 🎯 **Theo danh mục**: Giảm cho cây phong thủy vào đầu năm
- 💎 **VIP**: Mã đặc biệt cho khách hàng thân thiết
- ⚡ **Flash sale**: Giới hạn số lượng (50 mã đầu tiên)
- 🎁 **Freeship**: Giảm 30K phí ship cho đơn > 300K

### 3. Bảo mật
- ❌ Không tạo mã dễ đoán (VD: `GIAM10`, `GIAM20`...)
- ✅ Dùng số ngẫu nhiên (VD: `KM47B9`, `SALE93X2`)
- ✅ Giới hạn số lần dùng / 1 khách
- ✅ Theo dõi log sử dụng

## 📞 Hỗ Trợ

Nếu gặp vấn đề:
1. Kiểm tra `C:\xampp\apache\logs\error.log`
2. F12 → Console → Xem lỗi JavaScript
3. F12 → Network → Xem AJAX request/response
4. Kiểm tra database có đầy đủ bảng chưa

---

**Tất cả đã sẵn sàng! Chúc bạn kinh doanh thành công! 🎉**
