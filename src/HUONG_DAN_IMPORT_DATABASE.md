# 📋 HƯỚNG DẪN IMPORT DATABASE KHUYẾN MÃI

## ⚠️ QUAN TRỌNG - ĐỌC TRƯỚC KHI SỬ DỤNG

Hiện tại hệ thống **TỰ ĐỘNG TẠO BẢNG** khi bạn truy cập trang `admin/qlkhuyenmai.php` lần đầu tiên.  
**KHÔNG CẦN IMPORT THỦ CÔNG** nữa!

---

## 🎯 Cách Sử Dụng

### Phương Án 1: TỰ ĐỘNG (Khuyến Nghị) ✨

1. Mở trình duyệt và truy cập:
   ```
   http://localhost/WebCN/admin/qlkhuyenmai.php
   ```

2. Hệ thống sẽ **tự động tạo** 4 bảng:
   - `khuyen_mai` - Lưu thông tin khuyến mãi
   - `khuyen_mai_danh_muc` - Liên kết khuyến mãi với danh mục
   - `khuyen_mai_san_pham` - Liên kết khuyến mãi với sản phẩm
   - `lich_su_khuyen_mai` - Lưu lịch sử sử dụng

3. Xong! Có thể bắt đầu thêm khuyến mãi ngay.

---

### Phương Án 2: IMPORT THỦ CÔNG (Nếu cần dữ liệu mẫu)

Nếu muốn có sẵn 3 mã khuyến mãi mẫu:

#### Cách 1: Qua phpMyAdmin
```
1. Mở: http://localhost/phpmyadmin
2. Chọn database: web_cay
3. Click tab "Import"
4. Chọn file: database_khuyen_mai.sql
5. Click "Go"
```

#### Cách 2: Qua Command Line
```bash
cd C:\xampp\htdocs\WebCN
mysql -u root -p web_cay < database_khuyen_mai.sql
```

---

## 🎁 Dữ Liệu Mẫu

File `database_khuyen_mai.sql` chứa 3 mã khuyến mãi demo:

| Mã Code | Loại | Giá Trị | Áp Dụng | Hết Hạn |
|---------|------|---------|---------|---------|
| **NEWYEAR2025** | Giảm 10% | Tối đa 100k | Tất cả | 31/12/2025 |
| **CAYCẢNH50K** | Giảm 50k | Đơn từ 500k | Tất cả | 30/6/2025 |
| **FREESHIP** | Giảm 5% | Đơn từ 200k | Tất cả | 31/3/2025 |

---

## ✅ Kiểm Tra Sau Khi Import

### 1. Kiểm Tra Bảng Đã Tạo
```sql
SHOW TABLES LIKE 'khuyen_mai%';
-- Phải thấy 4 bảng: khuyen_mai, khuyen_mai_danh_muc, khuyen_mai_san_pham, lich_su_khuyen_mai
```

### 2. Kiểm Tra Dữ Liệu Mẫu
```sql
SELECT COUNT(*) FROM khuyen_mai;
-- Kết quả: 3 (nếu import thủ công)
-- Hoặc: 0 (nếu tự động tạo bảng)
```

### 3. Kiểm Tra Cột Mới Trong Bảng don_hang
```sql
DESCRIBE don_hang;
-- Phải có 2 cột: ma_khuyen_mai (VARCHAR 50), giam_gia (DECIMAL 10,2)
```

---

## 🛠️ Khắc Phục Lỗi

### Lỗi: "Table 'khuyen_mai' already exists"
✅ **Không phải lỗi** - Bảng đã tồn tại, có thể sử dụng bình thường.

### Lỗi: "Column 'ma_khuyen_mai' already exists in table 'don_hang'"
✅ **Không phải lỗi** - Cột đã được thêm trước đó.

### Lỗi: "fetchAll() on bool" trong qlkhuyenmai.php
**Nguyên Nhân**: Bảng chưa được tạo  
**Giải Pháp**: Truy cập `admin/qlkhuyenmai.php` một lần để tự động tạo bảng

### Chatbot Không Hiển Thị
**Kiểm Tra**:
1. File `assets/chatbot.js` có tồn tại không?
2. File `assets/chatbot.css` có tồn tại không?
3. Console trình duyệt có lỗi JavaScript không? (F12)

**Giải Pháp**:
- Clear cache trình duyệt: `Ctrl + Shift + Delete`
- Hard reload: `Ctrl + F5`
- Kiểm tra file path đúng: `/WebCN/assets/chatbot.js`

---

## 📊 Cấu Trúc Database

### Bảng `khuyen_mai`
```sql
id INT AUTO_INCREMENT PRIMARY KEY
ma_khuyen_mai VARCHAR(50) UNIQUE NOT NULL
ten_khuyen_mai VARCHAR(255) NOT NULL
mo_ta TEXT
loai_giam ENUM('phan_tram', 'so_tien')
gia_tri_giam DECIMAL(10,2) NOT NULL
gia_tri_don_toi_thieu DECIMAL(10,2) DEFAULT 0
gia_tri_giam_toi_da DECIMAL(10,2) DEFAULT NULL
so_luong_ma INT DEFAULT NULL
so_lan_da_dung INT DEFAULT 0
loai_ap_dung ENUM('tat_ca', 'danh_muc', 'san_pham')
ngay_bat_dau DATETIME NOT NULL
ngay_ket_thuc DATETIME NOT NULL
trang_thai TINYINT(1) DEFAULT 1
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
```

### Bảng `lich_su_khuyen_mai`
```sql
id INT AUTO_INCREMENT PRIMARY KEY
khuyen_mai_id INT NOT NULL
don_hang_id INT NOT NULL
nguoi_dung_id INT
gia_tri_giam DECIMAL(10,2) NOT NULL
ngay_su_dung TIMESTAMP DEFAULT CURRENT_TIMESTAMP
```

### Bảng `don_hang` (Thêm 2 cột)
```sql
ma_khuyen_mai VARCHAR(50) DEFAULT NULL COMMENT 'Mã khuyến mãi đã sử dụng'
giam_gia DECIMAL(10,2) DEFAULT 0 COMMENT 'Số tiền giảm giá'
```

---

## 🚀 Bắt Đầu Sử Dụng

1. **Truy Cập Admin**: `http://localhost/WebCN/admin/qlkhuyenmai.php`
2. **Thêm Mã Khuyến Mãi**: Click "Thêm Khuyến Mãi Mới"
3. **Khách Nhập Mã**: Vào giỏ hàng → Nhập mã → "Áp Dụng"
4. **Thanh Toán**: Giá đã giảm tự động

---

## 📱 Liên Hệ Hỗ Trợ

- **File SQL**: `database_khuyen_mai.sql`
- **Trang Admin**: `admin/qlkhuyenmai.php`
- **API Validate**: `ajax_validate_promo.php`
- **Giỏ Hàng**: `giohang.php`

---

✅ **Hoàn Tất!** Hệ thống khuyến mãi đã sẵn sàng!
