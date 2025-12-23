-- Thêm cột gia_nhap vào bảng san_pham
ALTER TABLE `san_pham` 
ADD COLUMN `gia_nhap` DECIMAL(10,2) DEFAULT 0 AFTER `gia`;

-- Cập nhật giá nhập bằng 70% giá bán hiện tại cho các sản phẩm đã có
UPDATE `san_pham` 
SET `gia_nhap` = `gia` * 0.7 
WHERE `gia_nhap` = 0;
