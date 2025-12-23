-- Tạo bảng khuyến mãi
CREATE TABLE IF NOT EXISTS khuyen_mai (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ma_khuyen_mai VARCHAR(50) UNIQUE NOT NULL,
    ten_khuyen_mai VARCHAR(255) NOT NULL,
    mo_ta TEXT,
    loai_giam ENUM('phan_tram', 'so_tien') DEFAULT 'phan_tram',
    gia_tri_giam DECIMAL(10,2) NOT NULL,
    gia_tri_don_toi_thieu DECIMAL(10,2) DEFAULT 0,
    gia_tri_giam_toi_da DECIMAL(10,2) DEFAULT NULL,
    so_luong_ma INT DEFAULT NULL,
    so_lan_da_dung INT DEFAULT 0,
    loai_ap_dung ENUM('tat_ca', 'danh_muc', 'san_pham') DEFAULT 'tat_ca',
    ngay_bat_dau DATETIME NOT NULL,
    ngay_ket_thuc DATETIME NOT NULL,
    trang_thai TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_ma_khuyen_mai (ma_khuyen_mai),
    INDEX idx_trang_thai (trang_thai),
    INDEX idx_ngay (ngay_bat_dau, ngay_ket_thuc)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng liên kết khuyến mãi với danh mục
CREATE TABLE IF NOT EXISTS khuyen_mai_danh_muc (
    id INT AUTO INCREMENT PRIMARY KEY,
    khuyen_mai_id INT NOT NULL,
    danh_muc_id INT NOT NULL,
    FOREIGN KEY (khuyen_mai_id) REFERENCES khuyen_mai(id) ON DELETE CASCADE,
    FOREIGN KEY (danh_muc_id) REFERENCES danh_muc(id) ON DELETE CASCADE,
    UNIQUE KEY unique_promo_category (khuyen_mai_id, danh_muc_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng liên kết khuyến mãi với sản phẩm
CREATE TABLE IF NOT EXISTS khuyen_mai_san_pham (
    id INT AUTO_INCREMENT PRIMARY KEY,
    khuyen_mai_id INT NOT NULL,
    san_pham_id INT NOT NULL,
    FOREIGN KEY (khuyen_mai_id) REFERENCES khuyen_mai(id) ON DELETE CASCADE,
    FOREIGN KEY (san_pham_id) REFERENCES san_pham(id) ON DELETE CASCADE,
    UNIQUE KEY unique_promo_product (khuyen_mai_id, san_pham_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng lịch sử sử dụng mã khuyến mãi
CREATE TABLE IF NOT EXISTS lich_su_khuyen_mai (
    id INT AUTO_INCREMENT PRIMARY KEY,
    khuyen_mai_id INT NOT NULL,
    don_hang_id INT NOT NULL,
    nguoi_dung_id INT,
    gia_tri_giam DECIMAL(10,2) NOT NULL,
    ngay_su_dung TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (khuyen_mai_id) REFERENCES khuyen_mai(id) ON DELETE CASCADE,
    FOREIGN KEY (don_hang_id) REFERENCES don_hang(id) ON DELETE CASCADE,
    INDEX idx_nguoi_dung (nguoi_dung_id),
    INDEX idx_ngay_su_dung (ngay_su_dung)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Thêm cột mã khuyến mãi vào bảng don_hang
ALTER TABLE don_hang 
ADD COLUMN ma_khuyen_mai VARCHAR(50) DEFAULT NULL AFTER phuong_thuc_thanh_toan,
ADD COLUMN giam_gia DECIMAL(10,2) DEFAULT 0 AFTER ma_khuyen_mai;

-- Insert dữ liệu mẫu
INSERT INTO khuyen_mai (ma_khuyen_mai, ten_khuyen_mai, mo_ta, loai_giam, gia_tri_giam, gia_tri_don_toi_thieu, gia_tri_giam_toi_da, so_luong_ma, loai_ap_dung, ngay_bat_dau, ngay_ket_thuc, trang_thai) VALUES
('NEWYEAR2025', 'Khuyến mãi Tết 2025', 'Giảm 15% cho tất cả đơn hàng từ 500K', 'phan_tram', 15.00, 500000.00, 200000.00, 100, 'tat_ca', '2025-01-01 00:00:00', '2025-02-28 23:59:59', 1),
('CAYCẢNH50K', 'Giảm 50K cho cây cảnh', 'Giảm 50K khi mua cây cảnh', 'so_tien', 50000.00, 200000.00, NULL, NULL, 'danh_muc', '2025-01-01 00:00:00', '2025-12-31 23:59:59', 1),
('FREESHIP', 'Miễn phí vận chuyển', 'Giảm 30K phí ship cho đơn từ 300K', 'so_tien', 30000.00, 300000.00, 30000.00, NULL, 'tat_ca', '2025-01-01 00:00:00', '2025-12-31 23:59:59', 1);
