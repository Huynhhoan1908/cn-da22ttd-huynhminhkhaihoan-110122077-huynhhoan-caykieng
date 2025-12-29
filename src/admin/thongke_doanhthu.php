<?php
$page_title = "Thống Kê Doanh Thu";
$current_page = "analytics";
include 'header.php';
require_once '../connect.php';

// Lấy khoảng thời gian từ form (mặc định là tháng này)
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// ===== QUERY 1: Tổng doanh thu (Đơn hàng đã giao) =====
$sql_revenue = "SELECT COALESCE(SUM(tong_thanh_toan), 0) as total_revenue
                FROM don_hang 
                WHERE trang_thai = 'Đã giao' 
                AND DATE(ngay_dat) BETWEEN ? AND ?";
$stmt = $conn->prepare($sql_revenue);
$stmt->execute([$start_date, $end_date]);
$revenue_data = $stmt->fetch(PDO::FETCH_ASSOC);
$total_revenue = $revenue_data['total_revenue'];

// ===== QUERY 2: Tổng số đơn hàng đã hoàn thành =====
$sql_orders = "SELECT COUNT(*) as total_orders
               FROM don_hang 
               WHERE trang_thai = 'Đã giao' 
               AND DATE(ngay_dat) BETWEEN ? AND ?";
$stmt = $conn->prepare($sql_orders);
$stmt->execute([$start_date, $end_date]);
$orders_data = $stmt->fetch(PDO::FETCH_ASSOC);
$total_orders = $orders_data['total_orders'];

// ===== QUERY 3: Tỷ lệ thành công (% đơn không bị hủy) =====
$sql_success = "SELECT 
                    COUNT(*) as total_all,
                    SUM(CASE WHEN trang_thai != 'Đã hủy' THEN 1 ELSE 0 END) as success_orders
                FROM don_hang 
                WHERE DATE(ngay_dat) BETWEEN ? AND ?";
$stmt = $conn->prepare($sql_success);
$stmt->execute([$start_date, $end_date]);
$success_data = $stmt->fetch(PDO::FETCH_ASSOC);
$success_rate = $success_data['total_all'] > 0 ? ($success_data['success_orders'] / $success_data['total_all']) * 100 : 0;

// ===== QUERY 4: Lợi nhuận ròng (giả định giá vốn = 60% giá bán) =====
$estimated_cost = $total_revenue * 0.6; // Giả định giá vốn 60%
$net_profit = $total_revenue - $estimated_cost;

// ===== QUERY 5: Doanh thu theo ngày (7 ngày gần nhất hoặc theo range) =====
$sql_daily = "SELECT 
                DATE(ngay_dat) as order_date,
                COALESCE(SUM(tong_thanh_toan), 0) as daily_revenue
              FROM don_hang
              WHERE trang_thai = 'Đã giao'
              AND DATE(ngay_dat) BETWEEN ? AND ?
              GROUP BY DATE(ngay_dat)
              ORDER BY order_date ASC";
$stmt = $conn->prepare($sql_daily);
$stmt->execute([$start_date, $end_date]);
$daily_revenue = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ===== QUERY 6: Doanh thu theo danh mục =====
$sql_category = "SELECT 
                    dm.ten_san_pham as category_name,
                    COALESCE(SUM(ct.so_luong * ct.gia), 0) as category_revenue
                 FROM chi_tiet_don_hang ct
                 INNER JOIN san_pham sp ON ct.san_pham_id = sp.id
                 INNER JOIN danh_muc dm ON sp.danh_muc_id = dm.id
                 INNER JOIN don_hang dh ON ct.don_hang_id = dh.id
                 WHERE dh.trang_thai = 'Đã giao'
                 AND DATE(dh.ngay_dat) BETWEEN ? AND ?
                 GROUP BY dm.id, dm.ten_san_pham
                 ORDER BY category_revenue DESC";
$stmt = $conn->prepare($sql_category);
$stmt->execute([$start_date, $end_date]);
$category_revenue = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ===== QUERY 7: Top 5 sản phẩm bán chạy =====
$sql_top_products = "SELECT 
                        sp.id,
                        sp.ten_san_pham,
                        sp.hinh_anh,
                        SUM(ct.so_luong) as total_quantity,
                        SUM(ct.so_luong * ct.gia) as total_revenue
                     FROM chi_tiet_don_hang ct
                     INNER JOIN san_pham sp ON ct.san_pham_id = sp.id
                     INNER JOIN don_hang dh ON ct.don_hang_id = dh.id
                     WHERE dh.trang_thai = 'Đã giao'
                     AND DATE(dh.ngay_dat) BETWEEN ? AND ?
                     GROUP BY sp.id, sp.ten_san_pham, sp.hinh_anh
                     ORDER BY total_quantity DESC
                     LIMIT 5";
$stmt = $conn->prepare($sql_top_products);
$stmt->execute([$start_date, $end_date]);
$top_products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Chuyển đổi dữ liệu PHP sang JSON cho JavaScript
$daily_labels = json_encode(array_column($daily_revenue, 'order_date'));
$daily_values = json_encode(array_column($daily_revenue, 'daily_revenue'));
$category_labels = json_encode(array_column($category_revenue, 'category_name'));
$category_values = json_encode(array_column($category_revenue, 'category_revenue'));
?>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
    .stats-card {
        background: white;
        border-radius: 12px;
        padding: 1.5rem;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        transition: all 0.3s ease;
        border-left: 4px solid var(--primary-green, #2E8B57);
        height: 100%;
    }

    .stats-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 20px rgba(0,0,0,0.12);
    }

    .stats-card.revenue {
        border-left-color: #28a745;
    }

    .stats-card.profit {
        border-left-color: #17a2b8;
    }

    .stats-card.orders {
        border-left-color: #ffc107;
    }

    .stats-card.success {
        border-left-color: #6f42c1;
    }

    .stats-card .icon {
        width: 50px;
        height: 50px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        margin-bottom: 1rem;
    }

    .stats-card.revenue .icon {
        background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        color: white;
    }

    .stats-card.profit .icon {
        background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
        color: white;
    }

    .stats-card.orders .icon {
        background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%);
        color: white;
    }

    .stats-card.success .icon {
        background: linear-gradient(135deg, #6f42c1 0%, #5a32a3 100%);
        color: white;
    }

    .stats-card h3 {
        font-size: 2rem;
        font-weight: 700;
        margin: 0.5rem 0;
        color: #333;
    }

    .stats-card p {
        color: #6c757d;
        margin: 0;
        font-size: 0.9rem;
        font-weight: 500;
    }

    .chart-container {
        background: white;
        border-radius: 12px;
        padding: 1.5rem;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        margin-bottom: 2rem;
    }

    .chart-container h5 {
        font-weight: 600;
        color: #333;
        margin-bottom: 1.5rem;
        display: flex;
        align-items: center;
    }

    .chart-container h5 i {
        margin-right: 10px;
        color: #2E8B57;
    }

    .filter-section {
        background: white;
        border-radius: 12px;
        padding: 1.5rem;
        margin-bottom: 2rem;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    }

    .table-container {
        background: white;
        border-radius: 12px;
        padding: 1.5rem;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    }

    .product-image {
        width: 50px;
        height: 50px;
        object-fit: cover;
        border-radius: 8px;
    }

    .rank-badge {
        width: 30px;
        height: 30px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 14px;
    }

    .rank-1 { background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%); color: white; }
    .rank-2 { background: linear-gradient(135deg, #C0C0C0 0%, #A9A9A9 100%); color: white; }
    .rank-3 { background: linear-gradient(135deg, #CD7F32 0%, #B8860B 100%); color: white; }
    .rank-4, .rank-5 { background: linear-gradient(135deg, #6c757d 0%, #495057 100%); color: white; }

    .btn-primary {
        background: linear-gradient(135deg, #2E8B57 0%, #1a5634 100%);
        border: none;
        padding: 0.6rem 1.5rem;
        border-radius: 8px;
        font-weight: 500;
    }

    .btn-primary:hover {
        background: linear-gradient(135deg, #1a5634 0%, #0d3d1f 100%);
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }

    .form-control, .form-select {
        border-radius: 8px;
        border: 1px solid #dee2e6;
        padding: 0.6rem 1rem;
    }

    .form-control:focus, .form-select:focus {
        border-color: #2E8B57;
        box-shadow: 0 0 0 0.2rem rgba(46, 139, 87, 0.15);
    }

    .table thead {
        background: linear-gradient(135deg, #2E8B57 0%, #1a5634 100%);
        color: white;
    }

    .table thead th {
        border: none;
        font-weight: 600;
        padding: 1rem;
    }

    .table tbody tr:hover {
        background-color: rgba(46, 139, 87, 0.05);
    }

    @media (max-width: 768px) {
        .stats-card h3 {
            font-size: 1.5rem;
        }
    }
</style>

<div class="container-fluid px-4 py-3">
    
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0"><i class="fas fa-chart-line me-2" style="color: #2E8B57;"></i>Thống Kê Doanh Thu</h1>
            <p class="text-muted mb-0 mt-1">Phân tích doanh thu và hiệu suất bán hàng</p>
        </div>
    </div>

    <!-- Date Filter -->
    <div class="filter-section">
        <form method="GET" action="" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label fw-semibold">
                    <i class="fas fa-calendar-alt me-2"></i>Từ Ngày
                </label>
                <input type="date" name="start_date" class="form-control" value="<?= $start_date ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label fw-semibold">
                    <i class="fas fa-calendar-alt me-2"></i>Đến Ngày
                </label>
                <input type="date" name="end_date" class="form-control" value="<?= $end_date ?>">
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-filter me-2"></i>Lọc Dữ Liệu
                </button>
            </div>
        </form>
    </div>

    <!-- 4 Key Metrics Cards -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="stats-card revenue">
                <div class="icon">
                    <i class="fas fa-money-bill-wave"></i>
                </div>
                <p>Tổng Doanh Thu</p>
                <h3><?= number_format($total_revenue, 0, ',', '.') ?>₫</h3>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="stats-card profit">
                <div class="icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <p>Lợi Nhuận Ròng (Ước tính)</p>
                <h3><?= number_format($net_profit, 0, ',', '.') ?>₫</h3>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="stats-card orders">
                <div class="icon">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <p>Tổng Đơn Hàng</p>
                <h3><?= $total_orders ?></h3>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="stats-card success">
                <div class="icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <p>Tỷ Lệ Thành Công</p>
                <h3><?= number_format($success_rate, 1) ?>%</h3>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row mb-4">
        <!-- Revenue Trend Chart -->
        <div class="col-md-8 mb-3">
            <div class="chart-container">
                <h5><i class="fas fa-chart-area"></i>Xu Hướng Doanh Thu</h5>
                <canvas id="revenueChart" height="80"></canvas>
            </div>
        </div>

        <!-- Category Revenue Chart -->
        <div class="col-md-4 mb-3">
            <div class="chart-container text-center"> <h5><i class="fas fa-chart-pie"></i>Doanh Thu Theo Danh Mục</h5>
                
                <div style="height: 500px; max-width: 500px; margin: 0 auto; position: relative;">
                    <canvas id="categoryChart"></canvas>
                </div>
                
            </div>
        </div>
    </div>

    <!-- Top Selling Products Table -->
    <div class="table-container">
        <h5 class="mb-3"><i class="fas fa-trophy me-2" style="color: #FFD700;"></i>Top 5 Sản Phẩm Bán Chạy</h5>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th width="8%">Hạng</th>
                        <th width="10%">Hình Ảnh</th>
                        <th width="40%">Tên Sản Phẩm</th>
                        <th width="17%">Số Lượng Bán</th>
                        <th width="25%">Tổng Doanh Thu</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($top_products) > 0): ?>
                        <?php foreach ($top_products as $index => $product): ?>
                            <tr>
                                <td>
                                    <div class="rank-badge rank-<?= $index + 1 ?>">
                                        <?= $index + 1 ?>
                                    </div>
                                </td>
                                <td>
                                    <?php 
                                    $img_path = $product['hinh_anh'] ? '../uploads/' . $product['hinh_anh'] : 'https://via.placeholder.com/50';
                                    ?>
                                    <img src="<?= $img_path ?>" alt="<?= htmlspecialchars($product['ten_san_pham']) ?>" class="product-image">
                                </td>
                                <td><strong><?= htmlspecialchars($product['ten_san_pham']) ?></strong></td>
                                <td>
                                    <span class="badge bg-success" style="font-size: 14px; padding: 8px 15px;">
                                        <?= $product['total_quantity'] ?> sản phẩm
                                    </span>
                                </td>
                                <td>
                                    <strong style="color: #28a745; font-size: 16px;">
                                        <?= number_format($product['total_revenue'], 0, ',', '.') ?>₫
                                    </strong>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">
                                <i class="fas fa-inbox fa-3x mb-3 d-block"></i>
                                Chưa có dữ liệu bán hàng trong khoảng thời gian này
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<script>
    // ===== Chart 1: Revenue Trend (Line Chart) =====
    const revenueCtx = document.getElementById('revenueChart').getContext('2d');
    const revenueChart = new Chart(revenueCtx, {
        type: 'line',
        data: {
            labels: <?= $daily_labels ?>,
            datasets: [{
                label: 'Doanh Thu (VNĐ)',
                data: <?= $daily_values ?>,
                backgroundColor: 'rgba(46, 139, 87, 0.1)',
                borderColor: '#2E8B57',
                borderWidth: 3,
                fill: true,
                tension: 0.4,
                pointBackgroundColor: '#2E8B57',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointRadius: 5,
                pointHoverRadius: 7
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
                            weight: '600'
                        }
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    padding: 12,
                    titleFont: {
                        size: 14
                    },
                    bodyFont: {
                        size: 13
                    },
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
                            size: 12
                        }
                    },
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)'
                    }
                },
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        font: {
                            size: 12
                        }
                    }
                }
            }
        }
    });

    // ===== Chart 2: Category Revenue (Doughnut Chart) =====
    const categoryCtx = document.getElementById('categoryChart').getContext('2d');
    const categoryChart = new Chart(categoryCtx, {
        type: 'doughnut',
        data: {
            labels: <?= $category_labels ?>,
            datasets: [{
                label: 'Doanh Thu',
                data: <?= $category_values ?>,
                backgroundColor: [
                    '#2E8B57',
                    '#20c997',
                    '#ffc107',
                    '#17a2b8',
                    '#6f42c1',
                    '#e83e8c'
                ],
                borderWidth: 3,
                borderColor: '#fff',
                hoverOffset: 15
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 15,
                        font: {
                            size: 13,
                            weight: '500'
                        },
                        usePointStyle: true,
                        pointStyle: 'circle'
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    padding: 12,
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = context.parsed || 0;
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = ((value / total) * 100).toFixed(1);
                            
                            return label + ': ' + new Intl.NumberFormat('vi-VN', {
                                style: 'currency',
                                currency: 'VND'
                            }).format(value) + ' (' + percentage + '%)';
                        }
                    }
                }
            }
        }
    });
</script>

<?php include 'footer.php'; ?>
