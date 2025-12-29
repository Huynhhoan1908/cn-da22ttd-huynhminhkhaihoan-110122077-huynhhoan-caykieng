<?php
$page_title = "Quản Lý Kho Hàng";
$current_page = "inventory";
include 'header.php';
?>

<!-- Bootstrap CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
        :root {
            --primary-green: #2E8B57;
            --light-green: #90EE90;
            --dark-green: #1a5634;
            --danger-red: #dc3545;
            --warning-orange: #ff9800;
        }

        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .navbar {
            background: linear-gradient(135deg, var(--primary-green) 0%, var(--dark-green) 100%);
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .navbar-brand {
            color: white !important;
            font-weight: bold;
            font-size: 1.5rem;
        }

        .main-header {
            background: white;
            padding: 2rem;
            margin-bottom: 2rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .stats-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: transform 0.3s ease;
            border-left: 4px solid var(--primary-green);
        }

        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }

        .stats-card.warning {
            border-left-color: var(--danger-red);
        }

        .stats-card h3 {
            color: var(--primary-green);
            font-size: 2rem;
            font-weight: bold;
            margin: 0;
        }

        .stats-card p {
            color: #6c757d;
            margin: 0;
            font-size: 0.9rem;
        }

        .stats-card i {
            font-size: 2.5rem;
            color: var(--primary-green);
            opacity: 0.7;
        }

        .filter-section {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            margin-bottom: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .btn-primary {
            background-color: var(--primary-green);
            border-color: var(--primary-green);
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background-color: var(--dark-green);
            border-color: var(--dark-green);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }

        .table-container {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .table {
            margin-bottom: 0;
        }

        .table thead {
            background-color: var(--primary-green);
            color: white;
        }

        .table thead th {
            border: none;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
        }

        .table tbody tr {
            transition: background-color 0.3s ease;
        }

        .table tbody tr:hover {
            background-color: rgba(46, 139, 87, 0.05);
        }

        .plant-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .badge-status {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.75rem;
        }

        .badge-in-stock {
            background-color: #28a745;
            color: white;
        }

        .badge-out-stock {
            background-color: var(--danger-red);
            color: white;
        }

        .stock-low {
            color: var(--danger-red);
            font-weight: bold;
        }

        .stock-normal {
            color: var(--primary-green);
            font-weight: bold;
        }

        .action-btn-group {
            display: flex;
            gap: 6px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .action-btn {
            width: 36px;
            height: 36px;
            padding: 0;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            border: none;
            position: relative;
            cursor: pointer;
        }

        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        .action-btn i {
            font-size: 14px;
        }

        .btn-import {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
        }

        .btn-import:hover {
            background: linear-gradient(135deg, #218838 0%, #1aa179 100%);
        }

        .btn-adjust {
            background: linear-gradient(135deg, #ff9800 0%, #ff6b6b 100%);
            color: white;
        }

        .btn-adjust:hover {
            background: linear-gradient(135deg, #e68900 0%, #e63946 100%);
        }

        .btn-edit {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            color: white;
        }

        .btn-edit:hover {
            background: linear-gradient(135deg, #0056b3 0%, #003d82 100%);
        }

        .btn-delete {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
        }

        .btn-delete:hover {
            background: linear-gradient(135deg, #c82333 0%, #bd2130 100%);
        }

        /* Tooltip */
        .action-btn[title]:hover::after {
            content: attr(title);
            position: absolute;
            bottom: 120%;
            left: 50%;
            transform: translateX(-50%);
            background-color: rgba(0, 0, 0, 0.9);
            color: white;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 12px;
            white-space: nowrap;
            z-index: 1000;
            animation: fadeIn 0.2s ease-in;
        }

        .action-btn[title]:hover::before {
            content: '';
            position: absolute;
            bottom: 110%;
            left: 50%;
            transform: translateX(-50%);
            border: 5px solid transparent;
            border-top-color: rgba(0, 0, 0, 0.9);
            z-index: 1000;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .modal-header {
            background-color: var(--primary-green);
            color: white;
        }

        .modal-header .btn-close {
            filter: brightness(0) invert(1);
        }

        .form-label {
            font-weight: 600;
            color: #495057;
        }

        .search-box {
            position: relative;
        }

        .search-box i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
        }

        .search-box input {
            padding-left: 45px;
        }

        @media (max-width: 768px) {
            .stats-card {
                margin-bottom: 1rem;
            }
            
            .table-container {
                overflow-x: auto;
            }
            
            .action-btn {
                width: 32px;
                height: 32px;
            }

            .action-btn i {
                font-size: 12px;
            }

            .action-btn-group {
                gap: 4px;
            }

            .action-btn[title]:hover::after {
                display: none;
            }

            .action-btn[title]:hover::before {
                display: none;
            }
        }
    </style>

<div class="container-fluid px-4 py-3">
        <!-- Header Section -->
        <div class="main-header d-flex justify-content-between align-items-center">
            <div>
                <h1 class="mb-0"><i class="fas fa-warehouse me-2"></i>Quản Lý Kho Hàng</h1>
                <p class="text-muted mb-0 mt-1">Quản lý tồn kho và nhập xuất cây cảnh</p>
            </div>
        </div>
        <button class="btn btn-info btn-lg text-white ms-2" onclick="openHistoryModal()">
            <i class="fas fa-history me-2"></i>Lịch Sử Nhập
        </button>
        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-4 mb-3">
                <div class="stats-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 id="totalProducts">0</h3>
                            <p>Tổng Sản Phẩm</p>
                        </div>
                        <i class="fas fa-box"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="stats-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 id="totalStock">0</h3>
                            <p>Tổng Số Lượng Tồn</p>
                        </div>
                        <i class="fas fa-cubes"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="stats-card warning">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 id="outOfStock" class="text-danger">0</h3>
                            <p>Sắp Hết Hàng</p>
                        </div>
                        <i class="fas fa-exclamation-triangle text-danger"></i>
                    </div>
                </div>
            </div>
        </div>
        <!-- Data Table -->
        <div class="table-container">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th width="5%">ID</th>
                            <th width="10%">Hình Ảnh</th>
                            <th width="17%">Tên Cây</th>
                            <th width="20%" style="white-space: normal;">Danh Mục</th>
                            <th width="10%">Giá Vốn</th>
                            <th width="10%">Giá Bán</th>
                            <th width="7%">Tồn Kho</th>
                            <th width="6%">Trạng Thái</th>
                            <th width="12%">Thao Tác</th>
                        </tr>
                    </thead>
                    <tbody id="inventoryTableBody">
                        <!-- Data will be populated by JavaScript -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Import Stock Modal -->
    <div class="modal fade" id="importStockModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-plus-circle me-2"></i>Nhập Hàng</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="importProductId">
                    <div class="mb-3">
                        <label class="form-label">Tên Sản Phẩm</label>
                        <input type="text" class="form-control" id="importProductName" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Số Lượng Tồn Hiện Tại</label>
                        <input type="text" class="form-control" id="importCurrentStock" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Số Lượng Nhập Thêm *</label>
                        <input type="number" class="form-control" id="importQuantity" min="1" placeholder="Nhập số lượng...">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Ghi Chú</label>
                        <textarea class="form-control" id="importNote" rows="2" placeholder="Nhập hàng mới từ nhà cung cấp..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="button" class="btn btn-success" onclick="processImport()">
                        <i class="fas fa-check me-2"></i>Xác Nhận Nhập
                    </button>
                </div>
                <div class="mb-3">
                    <label class="form-label">Số Lượng Nhập Thêm *</label>
                    <input type="number" class="form-control" id="importQuantity" min="1" placeholder="Nhập số lượng...">
                </div>

                <div class="mb-3">
                    <label class="form-label">Giá Nhập Của Lô Này (VNĐ)</label>
                    <input type="number" class="form-control" id="importPrice" min="0" placeholder="Nhập giá vốn/cái (nếu có thay đổi)">
                    <small class="text-muted">Để trống nếu muốn giữ nguyên giá vốn cũ.</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Stock Adjustment Modal -->
    <div class="modal fade" id="adjustStockModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-minus-circle me-2"></i>Điều Chỉnh Kho</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="adjustProductId">
                    <div class="mb-3">
                        <label class="form-label">Tên Sản Phẩm</label>
                        <input type="text" class="form-control" id="adjustProductName" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Số Lượng Tồn Hiện Tại</label>
                        <input type="text" class="form-control" id="adjustCurrentStock" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Số Lượng Giảm *</label>
                        <input type="number" class="form-control" id="adjustQuantity" min="1" placeholder="Nhập số lượng...">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Lý Do Điều Chỉnh *</label>
                        <select class="form-select" id="adjustReason">
                            <option value="">-- Chọn Lý Do --</option>
                            <option value="Cây chết">Cây chết</option>
                            <option value="Hư hỏng">Hư hỏng</option>
                            <option value="Sử dụng nội bộ">Sử dụng nội bộ</option>
                            <option value="Trả hàng cho NCC">Trả hàng cho NCC</option>
                            <option value="Khác">Khác</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Ghi Chú Chi Tiết</label>
                        <textarea class="form-control" id="adjustNote" rows="3" placeholder="Mô tả chi tiết lý do..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="button" class="btn btn-warning" onclick="processAdjustment()">
                        <i class="fas fa-check me-2"></i>Xác Nhận Điều Chỉnh
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Product Modal -->
    <div class="modal fade" id="editPlantModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Chỉnh Sửa Sản Phẩm</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="editPlantForm">
                        <input type="hidden" id="editPlantId">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><i class="fas fa-seedling me-1"></i>Tên Cây *</label>
                                <input type="text" class="form-control" id="editPlantName" placeholder="Nhập tên cây..." required>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label"><i class="fas fa-dollar-sign me-1"></i>Giá Nhập (₫) *</label>
                                <input type="number" class="form-control" id="editPlantImportPrice" min="0" placeholder="0" required>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label"><i class="fas fa-box me-1"></i>Số Lượng</label>
                                <input type="number" class="form-control text-center fw-bold" id="editPlantStock" readonly style="background: #e9ecef;">
                            </div>
                            <div class="col-md-12 mb-3">
                                <label class="form-label"><i class="fas fa-tags me-1"></i>Danh Mục * <span id="editCategoryCount" class="badge bg-success ms-2">0 đã chọn</span></label>
                                <div style="border: 2px solid #e0e0e0; border-radius: 10px; padding: 12px; background: linear-gradient(to bottom, #ffffff, #f8f9fa); max-height: 180px; overflow-y: auto; box-shadow: inset 0 2px 4px rgba(0,0,0,0.05);" id="editCategoryWrapper">
                                    <!-- Categories will be loaded here -->
                                </div>
                                <small class="text-muted d-block mt-1"><i class="fas fa-info-circle"></i> Số lượng chỉ thay đổi qua Nhập/Xuất kho</small>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="button" class="btn btn-primary" onclick="updateProduct()">
                        <i class="fas fa-save me-2"></i>Cập Nhật
                    </button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="historyModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title"><i class="fas fa-history me-2"></i>Lịch Sử Nhập Hàng (Gần nhất)</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th>Thời Gian</th>
                                <th>Sản Phẩm</th>
                                <th class="text-center">SL Nhập</th>
                                <th class="text-end">Giá Nhập</th>
                                <th>Ghi Chú</th>
                            </tr>
                        </thead>
                        <tbody id="historyTableBody">
                            <tr><td colspan="5" class="text-center p-3">Đang tải dữ liệu...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // API Base URL
        const API_URL = 'api_kho_hang.php';
        
        // Dữ liệu sản phẩm từ database
        let inventoryData = [];
        let categories = [];

        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            loadCategories();
            loadProducts();
            setupFilters();
        });

        // Load danh mục
        async function loadCategories() {
            try {
                const response = await fetch(`${API_URL}?action=get_categories`);
                const result = await response.json();
                
                if (result.success) {
                    categories = result.data;
                    populateCategoryFilters();
                }
            } catch (error) {
                console.error('Lỗi tải danh mục:', error);
            }
        }

        // Populate category filters
        function populateCategoryFilters() {
            const categoryFilter = document.getElementById('categoryFilter');
            const editCategoryWrapper = document.getElementById('editCategoryWrapper');
            
            categoryFilter.innerHTML = '<option value="">Tất Cả Danh Mục</option>';
            
            categories.forEach(cat => {
                categoryFilter.innerHTML += `<option value="${cat.id}">${cat.ten_danh_muc}</option>`;
            });
            
            // Populate edit modal checkboxes
            editCategoryWrapper.innerHTML = '';
            categories.forEach(cat => {
                editCategoryWrapper.innerHTML += `
                    <div class="category-checkbox-item" style="display: flex; align-items: center; padding: 10px 12px; margin: 5px 0; background: white; border: 1px solid #e0e0e0; border-radius: 8px; cursor: pointer; transition: all 0.2s ease;" onmouseover="this.style.background='#e8f5e9'; this.style.borderColor='#4CAF50'; this.style.transform='translateX(3px)';" onmouseout="this.style.background='white'; this.style.borderColor='#e0e0e0'; this.style.transform='translateX(0)';">
                        <input type="checkbox" class="edit-category-checkbox" value="${cat.id}" id="edit_cat_${cat.id}" style="width: 20px; height: 20px; margin-right: 12px; cursor: pointer; accent-color: #4CAF50;">
                        <label for="edit_cat_${cat.id}" style="cursor: pointer; margin: 0; flex: 1; font-size: 14px; font-weight: 500;">${cat.ten_danh_muc}</label>
                    </div>
                `;
            });
            
            // Add event listeners for checkboxes
            document.querySelectorAll('.edit-category-checkbox').forEach(cb => {
                cb.addEventListener('change', updateEditCategoryCount);
            });
        }
        
        // Update category count badge
        function updateEditCategoryCount() {
            const count = document.querySelectorAll('.edit-category-checkbox:checked').length;
            document.getElementById('editCategoryCount').textContent = count + ' đã chọn';
        }

        // Load sản phẩm từ database
        async function loadProducts() {
            try {
                const response = await fetch(`${API_URL}?action=get_products`);
                const result = await response.json();
                
                if (result.success) {
                    inventoryData = result.data.map(item => ({
                        id: item.id,
                        name: item.ten_san_pham,
                        category: item.ten_danh_muc || 'Chưa phân loại',
                        categoryId: item.danh_muc_id,
                        categoryIds: item.danh_muc_ids || [],
                        importPrice: parseFloat(item.gia_nhap) || 0,
                        salePrice: parseFloat(item.gia_ban),
                        stock: parseInt(item.ton_kho),
                        image: item.hinh_anh_url
                    }));
                    
                    renderTable();
                    updateStatistics();
                } else {
                    alert('Lỗi tải dữ liệu: ' + result.message);
                }
            } catch (error) {
                console.error('Lỗi:', error);
                alert('Không thể kết nối đến server!');
            }
        }

        // Load thống kê
        async function updateStatistics() {
            try {
                const response = await fetch(`${API_URL}?action=get_statistics`);
                const result = await response.json();
                
                if (result.success) {
                    const stats = result.data;
                    document.getElementById('totalProducts').textContent = stats.total_products;
                    document.getElementById('totalStock').textContent = stats.total_stock;
                    document.getElementById('outOfStock').textContent = stats.low_stock_count;
                }
            } catch (error) {
                console.error('Lỗi tải thống kê:', error);
            }
        }

        // Render table
        function renderTable(data = inventoryData) {
            const tbody = document.getElementById('inventoryTableBody');
            tbody.innerHTML = '';

            data.forEach(item => {
                const status = item.stock === 0 ? 'out' : (item.stock < 5 ? 'low' : 'in');
                const statusBadge = status === 'out' 
                    ? '<span class="badge-status badge-out-stock">Hết Hàng</span>'
                    : '<span class="badge-status badge-in-stock">Còn Hàng</span>';
                
                const stockDisplay = item.stock < 5 && item.stock > 0
                    ? `<span class="stock-low">${item.stock} <small>(Sắp hết)</small></span>`
                    : `<span class="${item.stock === 0 ? 'stock-low' : 'stock-normal'}">${item.stock}</span>`;

                const row = `
                    <tr>
                        <td><strong>#${item.id}</strong></td>
                        <td>
                            <img src="${item.image}" alt="${item.name}" class="plant-image">
                        </td>
                        <td><strong>${item.name}</strong></td>
                        <td style="white-space: normal; line-height: 1.3;"><span class="badge bg-secondary" style="white-space: normal; text-align: left; display: inline-block; max-width: 100%;">${item.category}</span></td>
                        <td>${formatCurrency(item.importPrice)}</td>
                        <td>${formatCurrency(item.salePrice)}</td>
                        <td>${stockDisplay}</td>
                        <td>${statusBadge}</td>
                        <td>
                            <div class="action-btn-group">
                                <button class="action-btn btn-import" onclick="openImportModal(${item.id})" title="Nhập hàng">
                                    <i class="fas fa-plus"></i>
                                </button>
                                <button class="action-btn btn-adjust" onclick="openAdjustModal(${item.id})" title="Xuất/Điều chỉnh">
                                    <i class="fas fa-minus"></i>
                                </button>
                                <button class="action-btn btn-edit" onclick="editProduct(${item.id})" title="Chỉnh sửa">
                                    <i class="fas fa-pen"></i>
                                </button>
                                <button class="action-btn btn-delete" onclick="deleteProduct(${item.id})" title="Xóa sản phẩm">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
                tbody.innerHTML += row;
            });
        }

        // Update statistics
        function updateStatistics() {
            const totalProducts = inventoryData.length;
            const totalStock = inventoryData.reduce((sum, item) => sum + item.stock, 0);
            const outOfStock = inventoryData.filter(item => item.stock < 5).length;

            document.getElementById('totalProducts').textContent = totalProducts;
            document.getElementById('totalStock').textContent = totalStock;
            document.getElementById('outOfStock').textContent = outOfStock;
        }

        // Format currency
        function formatCurrency(amount) {
            return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(amount);
        }

        // Setup filters
        function setupFilters() {
            const searchInput = document.getElementById('searchInput');
            const categoryFilter = document.getElementById('categoryFilter');
            const statusFilter = document.getElementById('statusFilter');

            [searchInput, categoryFilter, statusFilter].forEach(element => {
                element.addEventListener('change', applyFilters);
                element.addEventListener('keyup', applyFilters);
            });
        }

        // Apply filters
        function applyFilters() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const categoryId = document.getElementById('categoryFilter').value;
            const status = document.getElementById('statusFilter').value;

            let filtered = inventoryData.filter(item => {
                const matchSearch = item.name.toLowerCase().includes(searchTerm);
                const matchCategory = !categoryId || item.categoryId == categoryId;
                let matchStatus = true;

                if (status === 'in-stock') matchStatus = item.stock >= 5;
                else if (status === 'low-stock') matchStatus = item.stock > 0 && item.stock < 5;
                else if (status === 'out-stock') matchStatus = item.stock === 0;

                return matchSearch && matchCategory && matchStatus;
            });

            renderTable(filtered);
        }

        // Reset filters
        function resetFilters() {
            document.getElementById('searchInput').value = '';
            document.getElementById('categoryFilter').value = '';
            document.getElementById('statusFilter').value = '';
            renderTable();
        }

        // Open import modal
        function openImportModal(productId) {
            const product = inventoryData.find(p => p.id === productId);
            document.getElementById('importProductId').value = product.id;
            document.getElementById('importProductName').value = product.name;
            document.getElementById('importCurrentStock').value = product.stock;
            document.getElementById('importQuantity').value = '';
            document.getElementById('importNote').value = '';
            
            const modal = new bootstrap.Modal(document.getElementById('importStockModal'));
            modal.show();
        }

        // Process import
        async function processImport() {
            const productId = parseInt(document.getElementById('importProductId').value);
            const quantity = parseInt(document.getElementById('importQuantity').value);
            const note = document.getElementById('importNote').value;
            
            if (!quantity || quantity <= 0) {
                alert('Vui lòng nhập số lượng hợp lệ!');
                return;
            }

            try {
                const response = await fetch(`${API_URL}?action=import_stock`, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        product_id: productId,
                        quantity: quantity,
                        note: note
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    // Close modal
                    const modal = bootstrap.Modal.getInstance(document.getElementById('importStockModal'));
                    modal.hide();

                    // Reload data
                    await loadProducts();
                    
                    alert('✓ ' + result.message);
                } else {
                    alert('Lỗi: ' + result.message);
                }
            } catch (error) {
                console.error('Lỗi:', error);
                alert('Không thể kết nối đến server!');
            }
        }

        // Open adjust modal
        function openAdjustModal(productId) {
            const product = inventoryData.find(p => p.id === productId);
            document.getElementById('adjustProductId').value = product.id;
            document.getElementById('adjustProductName').value = product.name;
            document.getElementById('adjustCurrentStock').value = product.stock;
            document.getElementById('adjustQuantity').value = '';
            document.getElementById('adjustReason').value = '';
            document.getElementById('adjustNote').value = '';
            
            const modal = new bootstrap.Modal(document.getElementById('adjustStockModal'));
            modal.show();
        }

        // Process adjustment
        async function processAdjustment() {
            const productId = parseInt(document.getElementById('adjustProductId').value);
            const quantity = parseInt(document.getElementById('adjustQuantity').value);
            const reason = document.getElementById('adjustReason').value;
            const note = document.getElementById('adjustNote').value;
            
            if (!quantity || quantity <= 0) {
                alert('Vui lòng nhập số lượng hợp lệ!');
                return;
            }

            if (!reason) {
                alert('Vui lòng chọn lý do điều chỉnh!');
                return;
            }

            try {
                const response = await fetch(`${API_URL}?action=adjust_stock`, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        product_id: productId,
                        quantity: quantity,
                        reason: reason,
                        note: note
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    // Close modal
                    const modal = bootstrap.Modal.getInstance(document.getElementById('adjustStockModal'));
                    modal.hide();

                    // Reload data
                    await loadProducts();
                    
                    alert('✓ ' + result.message);
                } else {
                    alert('Lỗi: ' + result.message);
                }
            } catch (error) {
                console.error('Lỗi:', error);
                alert('Không thể kết nối đến server!');
            }
        }

        // Edit product
        function editProduct(productId) {
            const product = inventoryData.find(p => p.id === productId);
            if (!product) {
                alert('Không tìm thấy sản phẩm!');
                return;
            }

            document.getElementById('editPlantId').value = product.id;
            document.getElementById('editPlantName').value = product.name;
            document.getElementById('editPlantImportPrice').value = product.importPrice;
            document.getElementById('editPlantStock').value = product.stock;
            
            // Clear all checkboxes first
            document.querySelectorAll('.edit-category-checkbox').forEach(cb => cb.checked = false);
            
            // Check the categories this product belongs to
            if (product.categoryIds && product.categoryIds.length > 0) {
                product.categoryIds.forEach(catId => {
                    const checkbox = document.getElementById('edit_cat_' + catId);
                    if (checkbox) checkbox.checked = true;
                });
            }
            
            updateEditCategoryCount();
            
            const modal = new bootstrap.Modal(document.getElementById('editPlantModal'));
            modal.show();
        }

        // Update product
        async function updateProduct() {
            const id = parseInt(document.getElementById('editPlantId').value);
            const name = document.getElementById('editPlantName').value;
            const importPrice = parseInt(document.getElementById('editPlantImportPrice').value);
            
            // Get selected category IDs
            const categoryIds = [];
            document.querySelectorAll('.edit-category-checkbox:checked').forEach(cb => {
                categoryIds.push(parseInt(cb.value));
            });

            if (!name || categoryIds.length === 0 || !importPrice) {
                alert('Vui lòng điền đầy đủ thông tin và chọn ít nhất 1 danh mục!');
                return;
            }

            try {
                const response = await fetch(`${API_URL}?action=update_product`, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        id: id,
                        ten_san_pham: name,
                        danh_muc_ids: categoryIds,
                        gia_nhap: importPrice
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    // Close modal
                    const modal = bootstrap.Modal.getInstance(document.getElementById('editPlantModal'));
                    modal.hide();

                    // Reload data
                    await loadProducts();
                    
                    alert('✓ ' + result.message);
                } else {
                    alert('Lỗi: ' + result.message);
                }
            } catch (error) {
                console.error('Lỗi:', error);
                alert('Không thể kết nối đến server!');
            }
        }

        // Delete product
        async function deleteProduct(productId) {
            if (!confirm('Bạn có chắc muốn xóa sản phẩm này?')) {
                return;
            }

            try {
                const response = await fetch(`${API_URL}?action=delete_product`, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({ id: productId })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    await loadProducts();
                    alert('✓ ' + result.message);
                } else {
                    alert('Lỗi: ' + result.message);
                }
            } catch (error) {
                console.error('Lỗi:', error);
                alert('Không thể kết nối đến server!');
            }
        }
        // 1. Hàm mở Modal và gọi API lấy dữ liệu
function openHistoryModal() {
    // Mở modal Bootstrap
    const modal = new bootstrap.Modal(document.getElementById('historyModal'));
    modal.show();
    // Gọi hàm tải dữ liệu
    loadHistoryData();
}

// 2. Hàm tải dữ liệu từ API và vẽ lên bảng
async function loadHistoryData() {
    try {
        const response = await fetch(`${API_URL}?action=get_history`);
        const result = await response.json();
        
        const tbody = document.getElementById('historyTableBody');
        tbody.innerHTML = ''; 

        if (result.success && result.data.length > 0) {
            result.data.forEach(item => {
                const date = new Date(item.ngay_tao).toLocaleString('vi-VN');
                const price = new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(item.gia_nhap);
                
                // --- PHẦN XỬ LÝ GIAO DIỆN KHÁC BIỆT ---
                let badgeHtml = '';
                let rowClass = '';
                
                if (item.hanh_dong === 'nhap') {
                    // Nếu là NHẬP: Màu xanh, dấu +
                    badgeHtml = `<span class="badge bg-success"><i class="fas fa-arrow-down"></i> Nhập: +${item.so_luong}</span>`;
                } else {
                    // Nếu là XUẤT/ĐIỀU CHỈNH: Màu vàng cam, dấu -
                    badgeHtml = `<span class="badge bg-warning text-dark"><i class="fas fa-arrow-up"></i> Xuất: -${item.so_luong}</span>`;
                    rowClass = 'table-warning'; // Tô màu nền nhạt cho dòng xuất
                }
                // ---------------------------------------

                tbody.innerHTML += `
                    <tr class="${item.hanh_dong !== 'nhap' ? 'bg-light' : ''}">
                        <td style="font-size:0.9rem; color:#666;">${date}</td>
                        <td class="fw-bold text-success">
                            <img src="${item.hinh_anh}" style="width:30px;height:30px;object-fit:cover;border-radius:4px;margin-right:5px;">
                            ${item.ten_san_pham}
                        </td>
                        <td class="text-center">${badgeHtml}</td>
                        <td class="text-end">${price}</td>
                        <td class="text-muted small">${item.ghi_chu || '-'}</td>
                    </tr>
                `;
            });
        } else {
            tbody.innerHTML = '<tr><td colspan="5" class="text-center p-4 text-muted">Chưa có lịch sử nào.</td></tr>';
        }
    } catch (error) {
        console.error('Lỗi tải lịch sử:', error);
        document.getElementById('historyTableBody').innerHTML = '<tr><td colspan="5" class="text-center text-danger">Lỗi kết nối server!</td></tr>';
    }
}
    </script>

<?php include 'footer.php'; ?>