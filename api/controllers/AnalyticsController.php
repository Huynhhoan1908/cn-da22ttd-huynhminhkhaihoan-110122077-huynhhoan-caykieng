<?php
class AnalyticsController {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    public function handleRequest($method, $id, $action) {
        if (!Auth::requireAdmin($this->conn)) {
            return;
        }
        
        switch ($method) {
            case 'GET':
                if ($id === 'sales') {
                    $this->getSalesAnalytics();
                } elseif ($id === 'products') {
                    $this->getProductAnalytics();
                } else {
                    Response::error('Invalid endpoint', 400);
                }
                break;
                
            default:
                Response::error('Method not allowed', 405);
        }
    }
    
    private function getSalesAnalytics() {
        $from = $_GET['from'] ?? date('Y-m-01');
        $to = $_GET['to'] ?? date('Y-m-d');
        
        $sql = "SELECT 
                    COUNT(*) as total_orders,
                    SUM(tong_tien) as total_revenue,
                    AVG(tong_tien) as avg_order_value,
                    SUM(CASE WHEN trang_thai = 'Đã giao' THEN 1 ELSE 0 END) as completed_orders
                FROM don_hang 
                WHERE DATE(ngay_tao) BETWEEN ? AND ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('ss', $from, $to);
        $stmt->execute();
        $analytics = $stmt->get_result()->fetch_assoc();
        
        $sql = "SELECT DATE(ngay_tao) as date, COUNT(*) as orders, SUM(tong_tien) as revenue
                FROM don_hang 
                WHERE DATE(ngay_tao) BETWEEN ? AND ?
                GROUP BY DATE(ngay_tao)
                ORDER BY date";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('ss', $from, $to);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $daily = [];
        while ($row = $result->fetch_assoc()) {
            $daily[] = $row;
        }
        
        $analytics['daily_stats'] = $daily;
        
        Response::success($analytics);
    }
    
    private function getProductAnalytics() {
        $limit = $_GET['limit'] ?? 10;
        
        $sql = "SELECT 
                    sp.id,
                    sp.ten_san_pham,
                    sp.gia,
                    SUM(ct.so_luong) as total_sold,
                    SUM(ct.so_luong * ct.gia) as total_revenue,
                    COUNT(DISTINCT ct.don_hang_id) as order_count
                FROM san_pham sp
                JOIN chi_tiet_don_hang ct ON sp.id = ct.san_pham_id
                JOIN don_hang dh ON ct.don_hang_id = dh.id
                WHERE dh.trang_thai != 'Đã hủy'
                GROUP BY sp.id
                ORDER BY total_sold DESC
                LIMIT ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('i', $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $products = [];
        while ($row = $result->fetch_assoc()) {
            $products[] = $row;
        }
        
        Response::success([
            'top_products' => $products,
            'period' => 'all_time'
        ]);
    }
}
?>
