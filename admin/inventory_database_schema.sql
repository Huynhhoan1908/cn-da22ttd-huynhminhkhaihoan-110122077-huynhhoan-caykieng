-- =====================================================
-- DATABASE SCHEMA FOR INVENTORY MODULE
-- Ornamental Plant Shop (Shop Cây Cảnh)
-- =====================================================

-- =====================================================
-- TABLE: products
-- Purpose: Store plant/product information
-- =====================================================
CREATE TABLE IF NOT EXISTS `products` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL COMMENT 'Tên cây',
  `slug` VARCHAR(255) NOT NULL COMMENT 'URL-friendly name',
  `description` TEXT DEFAULT NULL COMMENT 'Mô tả chi tiết',
  `import_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Giá vốn',
  `sale_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Giá bán',
  `image` VARCHAR(500) DEFAULT NULL COMMENT 'Đường dẫn ảnh',
  `category_id` INT(11) DEFAULT NULL COMMENT 'ID danh mục',
  `current_stock` INT(11) NOT NULL DEFAULT 0 COMMENT 'Số lượng tồn kho hiện tại',
  `status` ENUM('active', 'inactive', 'out_of_stock') DEFAULT 'active' COMMENT 'Trạng thái sản phẩm',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `idx_category` (`category_id`),
  KEY `idx_stock` (`current_stock`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Bảng sản phẩm (cây cảnh)';

-- =====================================================
-- TABLE: inventory_logs
-- Purpose: Track all stock changes (Import/Export/Adjustments)
-- =====================================================
CREATE TABLE IF NOT EXISTS `inventory_logs` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `product_id` INT(11) NOT NULL COMMENT 'ID sản phẩm',
  `change_amount` INT(11) NOT NULL COMMENT 'Số lượng thay đổi (+ nhập, - xuất)',
  `stock_before` INT(11) NOT NULL DEFAULT 0 COMMENT 'Tồn kho trước khi thay đổi',
  `stock_after` INT(11) NOT NULL DEFAULT 0 COMMENT 'Tồn kho sau khi thay đổi',
  `change_type` ENUM('import', 'export', 'adjustment', 'sale', 'return') NOT NULL COMMENT 'Loại thay đổi',
  `reason` VARCHAR(255) NOT NULL COMMENT 'Lý do thay đổi',
  `note` TEXT DEFAULT NULL COMMENT 'Ghi chú chi tiết',
  `user_id` INT(11) DEFAULT NULL COMMENT 'ID người thực hiện',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_product` (`product_id`),
  KEY `idx_change_type` (`change_type`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `fk_inventory_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Bảng lịch sử nhập xuất kho';

-- =====================================================
-- INDEXES for better performance
-- =====================================================
-- Add composite index for common queries
ALTER TABLE `inventory_logs` ADD INDEX `idx_product_date` (`product_id`, `created_at`);
ALTER TABLE `products` ADD INDEX `idx_stock_status` (`current_stock`, `status`);

-- =====================================================
-- SAMPLE DATA (Optional - for testing)
-- =====================================================
-- Insert sample categories first (if categories table exists)
-- INSERT INTO categories (id, name, slug) VALUES 
-- (1, 'Sen đá', 'sen-da'),
-- (2, 'Cây để bàn', 'cay-de-ban'),
-- (3, 'Cây phong thủy', 'cay-phong-thuy');

-- Insert sample products
INSERT INTO `products` (`name`, `slug`, `description`, `import_price`, `sale_price`, `image`, `category_id`, `current_stock`, `status`) VALUES
('Cây Kim Tiền', 'cay-kim-tien', 'Cây phong thủy mang lại tài lộc', 150000.00, 250000.00, 'kim-tien.jpg', 3, 25, 'active'),
('Sen Đá Hoa Hồng', 'sen-da-hoa-hong', 'Sen đá đẹp dễ chăm sóc', 50000.00, 80000.00, 'sen-da.jpg', 1, 3, 'active'),
('Cây Trúc Nhật', 'cay-truc-nhat', 'Cây để bàn làm việc', 100000.00, 180000.00, 'truc-nhat.jpg', 2, 0, 'out_of_stock'),
('Cây Phát Tài', 'cay-phat-tai', 'Cây phong thủy may mắn', 200000.00, 350000.00, 'phat-tai.jpg', 3, 15, 'active');

-- =====================================================
-- ESSENTIAL SQL QUERIES
-- =====================================================

-- =====================================================
-- QUERY 1: Update stock (Import/Add stock)
-- Purpose: Increase product stock when importing new items
-- Usage: Execute this when receiving new stock from supplier
-- =====================================================
-- Increase stock by specific amount
UPDATE `products` 
SET `current_stock` = `current_stock` + ? 
WHERE `id` = ?;

-- Example: Add 10 units to product ID 5
-- UPDATE products SET current_stock = current_stock + 10 WHERE id = 5;

-- =====================================================
-- QUERY 2: Update stock (Export/Reduce stock)
-- Purpose: Decrease product stock for sales or adjustments
-- Usage: Execute when selling or removing damaged items
-- =====================================================
-- Decrease stock by specific amount (ensure stock doesn't go negative)
UPDATE `products` 
SET `current_stock` = GREATEST(0, `current_stock` - ?) 
WHERE `id` = ?;

-- Example: Remove 5 units from product ID 3
-- UPDATE products SET current_stock = GREATEST(0, current_stock - 5) WHERE id = 3;

-- =====================================================
-- QUERY 3: Log inventory change
-- Purpose: Record all stock movements in inventory_logs
-- Usage: Insert after every stock change for audit trail
-- =====================================================
INSERT INTO `inventory_logs` 
(`product_id`, `change_amount`, `stock_before`, `stock_after`, `change_type`, `reason`, `note`, `user_id`) 
VALUES (?, ?, ?, ?, ?, ?, ?, ?);

-- Example: Log an import
-- INSERT INTO inventory_logs (product_id, change_amount, stock_before, stock_after, change_type, reason, note) 
-- VALUES (5, 10, 15, 25, 'import', 'Nhập hàng mới', 'Nhập từ nhà cung cấp ABC', 1);

-- =====================================================
-- QUERY 4: Complete import transaction (with logging)
-- Purpose: Import stock and create log in one transaction
-- =====================================================
START TRANSACTION;

-- Get current stock
SET @old_stock = (SELECT current_stock FROM products WHERE id = ?);

-- Update stock
UPDATE products SET current_stock = current_stock + ? WHERE id = ?;

-- Get new stock
SET @new_stock = (SELECT current_stock FROM products WHERE id = ?);

-- Create log entry
INSERT INTO inventory_logs 
(product_id, change_amount, stock_before, stock_after, change_type, reason, note) 
VALUES (?, ?, @old_stock, @new_stock, 'import', ?, ?);

COMMIT;

-- =====================================================
-- QUERY 5: Complete adjustment transaction (with logging)
-- Purpose: Adjust stock down and create log (for damaged/dead plants)
-- =====================================================
START TRANSACTION;

-- Get current stock
SET @old_stock = (SELECT current_stock FROM products WHERE id = ?);

-- Update stock (negative adjustment)
UPDATE products SET current_stock = GREATEST(0, current_stock - ?) WHERE id = ?;

-- Get new stock
SET @new_stock = (SELECT current_stock FROM products WHERE id = ?);

-- Create log entry (negative change_amount)
INSERT INTO inventory_logs 
(product_id, change_amount, stock_before, stock_after, change_type, reason, note) 
VALUES (?, ?, @old_stock, @new_stock, 'adjustment', ?, ?);

COMMIT;

-- =====================================================
-- QUERY 6: Get low stock products (< 5 units)
-- Purpose: Alert for products that need restocking
-- =====================================================
SELECT 
    id, 
    name, 
    current_stock, 
    import_price, 
    sale_price,
    category_id
FROM products 
WHERE current_stock < 5 AND current_stock > 0 
ORDER BY current_stock ASC;

-- =====================================================
-- QUERY 7: Get out of stock products
-- Purpose: Show products that are completely out of stock
-- =====================================================
SELECT 
    id, 
    name, 
    category_id,
    sale_price
FROM products 
WHERE current_stock = 0;

-- =====================================================
-- QUERY 8: Get inventory movement history for a product
-- Purpose: View all stock changes for specific product
-- =====================================================
SELECT 
    il.id,
    il.change_amount,
    il.stock_before,
    il.stock_after,
    il.change_type,
    il.reason,
    il.note,
    il.created_at,
    p.name as product_name
FROM inventory_logs il
JOIN products p ON il.product_id = p.id
WHERE il.product_id = ?
ORDER BY il.created_at DESC
LIMIT 50;

-- =====================================================
-- QUERY 9: Get today's inventory movements
-- Purpose: See all stock changes that happened today
-- =====================================================
SELECT 
    il.id,
    p.name as product_name,
    il.change_amount,
    il.change_type,
    il.reason,
    il.created_at
FROM inventory_logs il
JOIN products p ON il.product_id = p.id
WHERE DATE(il.created_at) = CURDATE()
ORDER BY il.created_at DESC;

-- =====================================================
-- QUERY 10: Get inventory statistics
-- Purpose: Dashboard overview of inventory status
-- =====================================================
SELECT 
    COUNT(*) as total_products,
    SUM(current_stock) as total_stock,
    SUM(CASE WHEN current_stock = 0 THEN 1 ELSE 0 END) as out_of_stock_count,
    SUM(CASE WHEN current_stock < 5 AND current_stock > 0 THEN 1 ELSE 0 END) as low_stock_count,
    SUM(current_stock * import_price) as total_import_value,
    SUM(current_stock * sale_price) as total_sale_value
FROM products;

-- =====================================================
-- STORED PROCEDURE: Import Stock with Automatic Logging
-- Purpose: Simplify import process with automatic log creation
-- =====================================================
DELIMITER $$

CREATE PROCEDURE sp_import_stock(
    IN p_product_id INT,
    IN p_quantity INT,
    IN p_reason VARCHAR(255),
    IN p_note TEXT,
    IN p_user_id INT
)
BEGIN
    DECLARE v_old_stock INT;
    DECLARE v_new_stock INT;
    
    -- Start transaction
    START TRANSACTION;
    
    -- Get current stock
    SELECT current_stock INTO v_old_stock 
    FROM products 
    WHERE id = p_product_id;
    
    -- Update stock
    UPDATE products 
    SET current_stock = current_stock + p_quantity,
        status = IF(current_stock + p_quantity > 0, 'active', status)
    WHERE id = p_product_id;
    
    -- Get new stock
    SELECT current_stock INTO v_new_stock 
    FROM products 
    WHERE id = p_product_id;
    
    -- Log the change
    INSERT INTO inventory_logs 
    (product_id, change_amount, stock_before, stock_after, change_type, reason, note, user_id)
    VALUES 
    (p_product_id, p_quantity, v_old_stock, v_new_stock, 'import', p_reason, p_note, p_user_id);
    
    COMMIT;
    
    SELECT v_new_stock as new_stock;
END$$

DELIMITER ;

-- =====================================================
-- STORED PROCEDURE: Adjust Stock (Decrease) with Logging
-- Purpose: Simplify stock reduction with automatic log creation
-- =====================================================
DELIMITER $$

CREATE PROCEDURE sp_adjust_stock(
    IN p_product_id INT,
    IN p_quantity INT,
    IN p_reason VARCHAR(255),
    IN p_note TEXT,
    IN p_user_id INT
)
BEGIN
    DECLARE v_old_stock INT;
    DECLARE v_new_stock INT;
    
    -- Start transaction
    START TRANSACTION;
    
    -- Get current stock
    SELECT current_stock INTO v_old_stock 
    FROM products 
    WHERE id = p_product_id;
    
    -- Validate quantity
    IF p_quantity > v_old_stock THEN
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'Số lượng điều chỉnh vượt quá tồn kho hiện tại';
    END IF;
    
    -- Update stock
    UPDATE products 
    SET current_stock = current_stock - p_quantity,
        status = IF(current_stock - p_quantity = 0, 'out_of_stock', status)
    WHERE id = p_product_id;
    
    -- Get new stock
    SELECT current_stock INTO v_new_stock 
    FROM products 
    WHERE id = p_product_id;
    
    -- Log the change (negative amount)
    INSERT INTO inventory_logs 
    (product_id, change_amount, stock_before, stock_after, change_type, reason, note, user_id)
    VALUES 
    (p_product_id, -p_quantity, v_old_stock, v_new_stock, 'adjustment', p_reason, p_note, p_user_id);
    
    COMMIT;
    
    SELECT v_new_stock as new_stock;
END$$

DELIMITER ;

-- =====================================================
-- USAGE EXAMPLES FOR STORED PROCEDURES
-- =====================================================

-- Example 1: Import 20 units of product ID 5
CALL sp_import_stock(5, 20, 'Nhập hàng mới', 'Nhập từ NCC XYZ ngày 07/12/2025', 1);

-- Example 2: Adjust (reduce) 3 units of product ID 2 (damaged plants)
CALL sp_adjust_stock(2, 3, 'Cây chết', 'Cây bị héo do nhiệt độ cao', 1);

-- =====================================================
-- VIEW: Current Inventory Status
-- Purpose: Easy-to-query view of current inventory
-- =====================================================
CREATE OR REPLACE VIEW v_inventory_status AS
SELECT 
    p.id,
    p.name,
    p.slug,
    p.current_stock,
    p.import_price,
    p.sale_price,
    p.status,
    CASE 
        WHEN p.current_stock = 0 THEN 'Hết hàng'
        WHEN p.current_stock < 5 THEN 'Sắp hết'
        ELSE 'Còn hàng'
    END as stock_status,
    (p.current_stock * p.import_price) as total_import_value,
    (p.current_stock * p.sale_price) as total_sale_value,
    (p.sale_price - p.import_price) as profit_per_unit,
    ((p.sale_price - p.import_price) * p.current_stock) as potential_profit
FROM products p
ORDER BY p.current_stock ASC;

-- Query the view
-- SELECT * FROM v_inventory_status WHERE stock_status = 'Sắp hết';

-- =====================================================
-- TRIGGERS: Auto-update product status based on stock
-- =====================================================
DELIMITER $$

CREATE TRIGGER tr_update_product_status_after_update
AFTER UPDATE ON products
FOR EACH ROW
BEGIN
    IF NEW.current_stock = 0 THEN
        UPDATE products SET status = 'out_of_stock' WHERE id = NEW.id;
    ELSEIF NEW.current_stock > 0 AND OLD.current_stock = 0 THEN
        UPDATE products SET status = 'active' WHERE id = NEW.id;
    END IF;
END$$

DELIMITER ;

-- =====================================================
-- END OF SCHEMA
-- =====================================================