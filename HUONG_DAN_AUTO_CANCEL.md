# HƯỚNG DẪN CÀI ĐẶT TỰ ĐỘNG HỦY ĐƠN HÀNG

## Chức năng
- Tự động hủy đơn hàng thanh toán bằng "Chuyển khoản" nếu sau 24 giờ không thanh toán
- Hoàn trả số lượng sản phẩm về kho
- Cập nhật trạng thái đơn hàng thành "Đã hủy"

## Cách 1: Chạy thủ công (Test)
```bash
php auto_cancel_orders.php
```

## Cách 2: Windows Task Scheduler (Khuyên dùng)

### Bước 1: Mở Task Scheduler
- Nhấn `Win + R`
- Gõ `taskschd.msc` và Enter

### Bước 2: Tạo Task mới
1. Click "Create Basic Task..."
2. Name: "Auto Cancel Orders"
3. Description: "Tự động hủy đơn hàng chuyển khoản quá 24h"

### Bước 3: Thiết lập lịch chạy
- Trigger: Daily hoặc Hourly
- Start time: Chọn giờ bắt đầu
- Recur every: 1 hour (chạy mỗi giờ)

### Bước 4: Thiết lập Action
- Action: "Start a program"
- Program/script: `C:\xampp\php\php.exe`
- Add arguments: `C:\xampp\htdocs\Web\auto_cancel_orders.php`

### Bước 5: Lưu và kích hoạt

## Cách 3: Cron Job (Linux/Mac)
```bash
# Chỉnh sửa crontab
crontab -e

# Thêm dòng sau để chạy mỗi giờ
0 * * * * /usr/bin/php /path/to/Web/auto_cancel_orders.php

# Hoặc chạy mỗi 30 phút
*/30 * * * * /usr/bin/php /path/to/Web/auto_cancel_orders.php
```

## Kiểm tra logs
Script sẽ in ra kết quả mỗi lần chạy. Bạn có thể:
1. Chạy thủ công để xem kết quả ngay
2. Xem logs trong Task Scheduler (History tab)
3. Check trong database bảng `don_hang`

## Lưu ý
- Đảm bảo XAMPP đang chạy MySQL
- Phương thức thanh toán phải là **chính xác** "Chuyển khoản" (có dấu)
- Trạng thái đơn hàng ban đầu phải là "Chờ xử lý"
- Sau 24h sẽ chuyển thành "Đã hủy"
