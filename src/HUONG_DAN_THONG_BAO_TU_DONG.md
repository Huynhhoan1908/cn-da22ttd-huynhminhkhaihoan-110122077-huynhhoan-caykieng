# HƯỚNG DẪN THIẾT LẬP THÔNG BÁO TỰ ĐỘNG

## Các loại thông báo tự động:

### 1. Thông báo sản phẩm mới
- Tự động tạo khi admin thêm sản phẩm mới
- Hết hạn sau 7 ngày
- Không cần cấu hình gì thêm

### 2. Thông báo giảm giá
- Tự động tạo khi giảm giá >= 20%
- Hết hạn sau 3 ngày
- Tự động khi admin cập nhật giá sản phẩm

### 3. Thông báo sắp hết hàng
- Tự động tạo khi số lượng <= 5
- Chỉ thông báo 1 lần trong 24h
- Hết hạn sau 2 ngày

### 4. Flash Sale định kỳ
- Chạy tự động vào 9h sáng hàng ngày
- Chọn ngẫu nhiên 5 sản phẩm
- Hết hạn sau 24h

### 5. Milestone đơn hàng
- Tự động khi đạt 100, 500, 1000... đơn hàng
- Thông báo cảm ơn khách hàng

## Cách thiết lập chạy tự động (Windows):

### Phương án 1: Windows Task Scheduler (Khuyến nghị)

1. Mở Task Scheduler (Tìm "Task Scheduler" trong Windows)

2. Tạo task mới:
   - Click "Create Basic Task"
   - Name: "Thông báo tự động"
   - Description: "Chạy cron job thông báo"

3. Trigger:
   - Daily
   - Start: 9:00 AM
   - Recur every: 1 days

4. Action:
   - Start a program
   - Program: C:\xampp\php\php.exe
   - Arguments: C:\xampp\htdocs\Web\cron_notifications.php

5. Click Finish

### Phương án 2: Chạy thủ công

Mở PowerShell và chạy:
```powershell
php C:\xampp\htdocs\Web\cron_notifications.php
```

### Phương án 3: Tích hợp vào website

Thêm vào file được truy cập thường xuyên (index.php):
```php
// Chạy cron mỗi 24h
if (!file_exists('last_cron.txt') || (time() - filemtime('last_cron.txt')) > 86400) {
    exec('php cron_notifications.php > /dev/null 2>&1 &');
    touch('last_cron.txt');
}
```

## Test thử:

Chạy lệnh:
```bash
php cron_notifications.php
```

Sẽ hiện kết quả:
```
=== CRON JOB: Auto Notifications ===
Started at: 2025-11-28 09:00:00

Checking low stock products...
Low stock notifications: 2

Creating flash sale notification...
  - Flash sale created!

Checking order milestones...
Milestone check completed

Cleaning expired notifications...
Deleted 5 expired notifications

=== SUMMARY ===
Total active notifications: 12
Completed at: 2025-11-28 09:00:15
```

## Xem log:

Kiểm tra file: `C:\xampp\php\logs\php_error_log`

## Tính năng bổ sung:

- Thông báo được lưu trong bảng `notifications`
- User đã đọc được lưu trong `user_notification_reads`
- Tự động xóa thông báo hết hạn
- Badge số lượng chưa đọc
- Real-time update mỗi 30 giây

## Quản lý thông báo:

Truy cập: http://localhost/Web/admin_notifications.php
- Xem tất cả thông báo
- Thêm thông báo thủ công
- Bật/tắt thông báo
- Xóa thông báo
