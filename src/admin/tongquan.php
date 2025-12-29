<?php
$page_title = "Tổng Quan Dashboard";
$current_page = "overview";
include 'header.php';
require_once '../connect.php';

// ===== THỐNG KÊ TỔNG QUAN =====
// 1. Tổng doanh thu (Đơn đã giao)
$sql_revenue = "SELECT COALESCE(SUM(tong_thanh_toan), 0) as total_revenue 
                FROM don_hang WHERE trang_thai = 'Đã giao'";
$stmt = $conn->query($sql_revenue);
$total_revenue = $stmt->fetch(PDO::FETCH_ASSOC)['total_revenue'];

// 2. Đơn hàng chờ xử lý
$sql_pending = "SELECT COUNT(*) as pending_orders 
                FROM don_hang WHERE trang_thai = 'Chờ xác nhận'";
$stmt = $conn->query($sql_pending);
$pending_orders = $stmt->fetch(PDO::FETCH_ASSOC)['pending_orders'];

// 3. Sản phẩm sắp hết hàng
$sql_low_stock = "SELECT COUNT(*) as low_stock 
                  FROM san_pham WHERE so_luong < 5 AND so_luong > 0 AND trang_thai = 1";
$stmt = $conn->query($sql_low_stock);
$low_stock = $stmt->fetch(PDO::FETCH_ASSOC)['low_stock'];

// 4. Khách hàng mới (tháng này)
$sql_customers = "SELECT COUNT(*) as new_customers 
                  FROM nguoi_dung 
                  WHERE MONTH(ngay_tao) = MONTH(CURRENT_DATE()) 
                  AND YEAR(ngay_tao) = YEAR(CURRENT_DATE())";
$stmt = $conn->query($sql_customers);
$new_customers = $stmt->fetch(PDO::FETCH_ASSOC)['new_customers'];

// ===== DOANH THU 7 NGÀY GẦN NHẤT =====
$sql_daily = "SELECT DATE(ngay_dat) as order_date, 
              COALESCE(SUM(tong_thanh_toan), 0) as daily_revenue
              FROM don_hang
              WHERE trang_thai = 'Đã giao'
              AND ngay_dat >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
              GROUP BY DATE(ngay_dat)
              ORDER BY order_date ASC";
$stmt = $conn->query($sql_daily);
$daily_revenue = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ===== DANH MỤC BÁN CHẠY =====
$sql_categories = "SELECT dm.ten_san_pham as category_name,
                   COUNT(DISTINCT sp.id) as product_count
                   FROM san_pham sp
                   LEFT JOIN danh_muc dm ON sp.danh_muc_id = dm.id
                   WHERE sp.trang_thai = 1
                   GROUP BY dm.id, dm.ten_san_pham
                   ORDER BY product_count DESC
                   LIMIT 4";
$stmt = $conn->query($sql_categories);
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ===== ĐƠN HÀNG GẦN ĐÂY =====
$sql_recent = "SELECT dh.id, dh.ma_don_hang, dh.ten_khach_hang, 
               dh.tong_thanh_toan, dh.trang_thai, dh.ngay_dat
               FROM don_hang dh
               ORDER BY dh.ngay_dat DESC
               LIMIT 10";
$stmt = $conn->query($sql_recent);
$recent_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Chuyển dữ liệu sang JSON cho Chart.js
$daily_labels = json_encode(array_column($daily_revenue, 'order_date'));
$daily_values = json_encode(array_column($daily_revenue, 'daily_revenue'));
$category_labels = json_encode(array_column($categories, 'category_name'));
$category_values = json_encode(array_column($categories, 'product_count'));
?>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
    :root {
        --primary-green: #198754;
        --success-green: #28a745;
        --warning-orange: #ff9800;
        --danger-red: #dc3545;
        --info-blue: #17a2b8;
    }

    .dashboard-card {
        background: white;
        border-radius: 12px;
        padding: 1.2rem;
        box-shadow: 0 3px 10px rgba(0,0,0,0.08);
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
        height: 100%;
    }

    .dashboard-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 5px;
        height: 100%;
        background: var(--card-color, var(--primary-green));
    }

    .dashboard-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 5px 18px rgba(0,0,0,0.12);
    }

    .dashboard-card.revenue {
        --card-color: var(--success-green);
    }

    .dashboard-card.orders {
        --card-color: var(--warning-orange);
    }

    .dashboard-card.alert {
        --card-color: var(--danger-red);
    }

    .dashboard-card.customers {
        --card-color: var(--info-blue);
    }

    .card-icon {
        width: 50px;
        height: 50px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 22px;
        color: white;
        margin-bottom: 0.8rem;
        box-shadow: 0 3px 8px rgba(0,0,0,0.12);
    }

    .dashboard-card.revenue .card-icon {
        background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    }

    .dashboard-card.orders .card-icon {
        background: linear-gradient(135deg, #ff9800 0%, #ff6b6b 100%);
    }

    .dashboard-card.alert .card-icon {
        background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
    }

    .dashboard-card.customers .card-icon {
        background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
    }

    .card-value {
        font-size: 1.6rem;
        font-weight: 700;
        color: #2c3e50;
        margin: 0.5rem 0 0.2rem 0;
        line-height: 1;
    }

    .card-label {
        color: #6c757d;
        font-size: 0.8rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        margin: 0;
    }

    .card-badge {
        position: absolute;
        top: 10px;
        right: 10px;
        padding: 4px 10px;
        border-radius: 15px;
        font-size: 10px;
        font-weight: 600;
        background: rgba(0,0,0,0.05);
        color: var(--card-color, var(--primary-green));
    }

    .chart-container {
        background: white;
        border-radius: 12px;
        padding: 1.3rem;
        box-shadow: 0 3px 10px rgba(0,0,0,0.08);
        margin-bottom: 1.5rem;
        height: 100%;
    }

    .chart-container h5 {
        font-weight: 700;
        color: #2c3e50;
        margin-bottom: 1.2rem;
        display: flex;
        align-items: center;
        font-size: 1rem;
    }

    .chart-container h5 i {
        margin-right: 8px;
        color: var(--primary-green);
        font-size: 1.1rem;
    }

    .table-container {
        background: white;
        border-radius: 12px;
        padding: 1.3rem;
        box-shadow: 0 3px 10px rgba(0,0,0,0.08);
    }

    .table-container h5 {
        font-weight: 700;
        color: #2c3e50;
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        font-size: 1rem;
    }

    .table-container h5 i {
        margin-right: 8px;
        color: var(--primary-green);
    }

    .table {
        margin-bottom: 0;
    }

    .table thead {
        background: linear-gradient(135deg, var(--primary-green) 0%, #146c43 100%);
        color: white;
    }

    .table thead th {
        border: none;
        font-weight: 600;
        padding: 0.7rem;
        font-size: 0.8rem;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }

    .table tbody tr {
        transition: all 0.2s ease;
    }

    .table tbody tr:hover {
        background-color: rgba(25, 135, 84, 0.05);
        transform: scale(1.01);
    }

    .table tbody td {
        padding: 0.7rem;
        vertical-align: middle;
        font-size: 0.85rem;
    }

    .status-badge {
        padding: 4px 12px;
        border-radius: 15px;
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.2px;
        display: inline-block;
    }

    .status-pending {
        background: linear-gradient(135deg, #fff3cd 0%, #ffc107 100%);
        color: #856404;
    }

    .status-shipped {
        background: linear-gradient(135deg, #d1ecf1 0%, #17a2b8 100%);
        color: #0c5460;
    }

    .status-completed {
        background: linear-gradient(135deg, #d4edda 0%, #28a745 100%);
        color: #155724;
    }

    .status-cancelled {
        background: linear-gradient(135deg, #f8d7da 0%, #dc3545 100%);
        color: #721c24;
    }

    .btn-view {
        padding: 6px 14px;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 600;
        background: linear-gradient(135deg, var(--primary-green) 0%, #146c43 100%);
        border: none;
        color: white;
        transition: all 0.3s ease;
    }

    .btn-view:hover {
        background: linear-gradient(135deg, #146c43 0%, #0d4b2e 100%);
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        color: white;
    }

    .page-header {
        margin-bottom: 1.5rem;
    }

    .page-header h1 {
        font-size: 1.5rem;
        font-weight: 700;
        color: #2c3e50;
        margin: 0;
    }

    .page-header p {
        color: #6c757d;
        margin: 0.3rem 0 0 0;
        font-size: 0.9rem;
    }

    @media (max-width: 768px) {
        .card-value {
            font-size: 1.4rem;
        }
        
        .dashboard-card {
            padding: 1rem;
        }

        .card-icon {
            width: 40px;
            height: 40px;
            font-size: 18px;
        }
    }
</style>

<div class="container-fluid px-4 py-3">
    
    <!-- Page Header -->
    <div class="page-header">
        <h1><i class="fas fa-tachometer-alt me-3" style="color: var(--primary-green);"></i>Tổng Quan</h1>
        <p>Tổng quan hoạt động shop cây cảnh</p>
    </div>

    <!-- 4 Key Metric Cards -->
    <div class="row mb-4">
        <!-- Total Revenue -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="dashboard-card revenue">
                <span class="card-badge">Tháng này</span>
                <div class="card-icon">
                    <i class="fas fa-dollar-sign"></i>
                </div>
                <p class="card-label">Tổng Doanh Thu</p>
                <h2 class="card-value"><?= number_format($total_revenue, 0, ',', '.') ?>₫</h2>
            </div>
        </div>

        <!-- Pending Orders -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="dashboard-card orders">
                <span class="card-badge">Cần xử lý</span>
                <div class="card-icon">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <p class="card-label">Đơn Chờ Xử Lý</p>
                <h2 class="card-value"><?= $pending_orders ?></h2>
            </div>
        </div>

        <!-- Low Stock Alert -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="dashboard-card alert">
                <span class="card-badge">Cảnh báo</span>
                <div class="card-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <p class="card-label">Sản Phẩm Sắp Hết</p>
                <h2 class="card-value"><?= $low_stock ?> <small style="font-size: 1rem;">SP</small></h2>
            </div>
        </div>

        <!-- New Customers -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="dashboard-card customers">
                <span class="card-badge">Mới</span>
                <div class="card-icon">
                    <i class="fas fa-users"></i>
                </div>
                <p class="card-label">Khách Hàng Mới</p>
                <h2 class="card-value"><?= $new_customers ?></h2>
            </div>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="row mb-4">
        <!-- Revenue Chart (Left - 8 cols) -->
        <div class="col-lg-8 mb-4">
            <div class="chart-container">
                <h5>
                    <i class="fas fa-chart-line"></i>
                    Xu Hướng Doanh Thu (7 Ngày Gần Nhất)
                </h5>
                <canvas id="revenueChart" height="60"></canvas>
            </div>
        </div>

        <!-- Category Chart (Right - 4 cols) -->
        <div class="col-md-4 mb-3">
            <div class="chart-container text-center"> <h5><i class="fas fa-chart-pie"></i>Doanh Thu Theo Danh Mục</h5>
                
                <div style="height: 500px; max-width: 500px; margin: 0 auto; position: relative;">
                    <canvas id="categoryChart"></canvas>
                </div>
                
            </div>
        </div>
    </div>
    </div>

    <!-- Recent Orders Table -->
    <div class="table-container">
        <h5>
            <i class="fas fa-history"></i>
            Đơn Hàng Gần Đây
        </h5>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th width="12%">Mã Đơn</th>
                        <th width="20%">Khách Hàng</th>
                        <th width="18%">Tổng Tiền</th>
                        <th width="18%">Trạng Thái</th>
                        <th width="17%">Ngày Đặt</th>
                        <th width="15%">Thao Tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($recent_orders) > 0): ?>
                        <?php foreach ($recent_orders as $order): ?>
                            <?php
                            $status_class = 'status-pending';
                            $status_text = $order['trang_thai'];
                            
                            if ($order['trang_thai'] == 'Đã giao') {
                                $status_class = 'status-completed';
                            } elseif ($order['trang_thai'] == 'Đang giao') {
                                $status_class = 'status-shipped';
                            } elseif ($order['trang_thai'] == 'Đã hủy') {
                                $status_class = 'status-cancelled';
                            }
                            ?>
                            <tr>
                                <td><strong>#<?= htmlspecialchars($order['ma_don_hang']) ?></strong></td>
                                <td><?= htmlspecialchars($order['ten_khach_hang']) ?></td>
                                <td><strong style="color: var(--success-green);"><?= number_format($order['tong_thanh_toan'], 0, ',', '.') ?>₫</strong></td>
                                <td>
                                    <span class="status-badge <?= $status_class ?>">
                                        <?= $status_text ?>
                                    </span>
                                </td>
                                <td><?= date('d/m/Y H:i', strtotime($order['ngay_dat'])) ?></td>
                                <td>
                                    <a href="qldonhang.php?view=<?= $order['id'] ?>" class="btn btn-view btn-sm">
                                        <i class="fas fa-eye me-1"></i> Xem
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">
                                <i class="fas fa-inbox fa-3x mb-3 d-block"></i>
                                Chưa có đơn hàng nào
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<script>
    // ===== Revenue Trend Chart (Line Chart) =====
    const revenueCtx = document.getElementById('revenueChart').getContext('2d');
    const revenueChart = new Chart(revenueCtx, {
        type: 'line',
        data: {
            labels: <?= $daily_labels ?>,
            datasets: [{
                label: 'Doanh Thu (VNĐ)',
                data: <?= $daily_values ?>,
                backgroundColor: 'rgba(25, 135, 84, 0.1)',
                borderColor: '#198754',
                borderWidth: 3,
                fill: true,
                tension: 0.4,
                pointBackgroundColor: '#198754',
                pointBorderColor: '#fff',
                pointBorderWidth: 3,
                pointRadius: 6,
                pointHoverRadius: 8,
                pointHoverBackgroundColor: '#198754',
                pointHoverBorderColor: '#fff',
                pointHoverBorderWidth: 3
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    display: true,
                    position: 'top',
                    labels: {
                        font: {
                            size: 14,
                            weight: '700'
                        },
                        color: '#2c3e50',
                        padding: 15
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(44, 62, 80, 0.95)',
                    padding: 15,
                    titleFont: {
                        size: 15,
                        weight: 'bold'
                    },
                    bodyFont: {
                        size: 14
                    },
                    borderColor: '#198754',
                    borderWidth: 2,
                    callbacks: {
                        label: function(context) {
                            return 'Doanh thu: ' + new Intl.NumberFormat('vi-VN', {
                                style: 'currency',
                                currency: 'VND'
                            }).format(context.parsed.y);
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return new Intl.NumberFormat('vi-VN', {
                                notation: 'compact',
                                compactDisplay: 'short'
                            }).format(value) + '₫';
                        },
                        font: {
                            size: 13,
                            weight: '600'
                        },
                        color: '#6c757d'
                    },
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)',
                        drawBorder: false
                    }
                },
                x: {
                    grid: {
                        display: false,
                        drawBorder: false
                    },
                    ticks: {
                        font: {
                            size: 13,
                            weight: '600'
                        },
                        color: '#6c757d'
                    }
                }
            }
        }
    });

    // ===== Category Doughnut Chart =====
    const categoryCtx = document.getElementById('categoryChart').getContext('2d');
    const categoryChart = new Chart(categoryCtx, {
        type: 'doughnut',
        data: {
            labels: <?= $category_labels ?>,
            datasets: [{
                label: 'Số Sản Phẩm',
                data: <?= $category_values ?>,
                backgroundColor: [
                    '#198754',
                    '#20c997',
                    '#ffc107',
                    '#17a2b8'
                ],
                borderWidth: 4,
                borderColor: '#fff',
                hoverOffset: 20,
                hoverBorderWidth: 5
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 20,
                        font: {
                            size: 13,
                            weight: '600'
                        },
                        color: '#2c3e50',
                        usePointStyle: true,
                        pointStyle: 'circle'
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(44, 62, 80, 0.95)',
                    padding: 15,
                    borderColor: '#198754',
                    borderWidth: 2,
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = context.parsed || 0;
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = ((value / total) * 100).toFixed(1);
                            
                            return label + ': ' + value + ' sản phẩm (' + percentage + '%)';
                        }
                    }
                }
            }
        }
    });
</script>

<?php include 'footer.php'; ?>