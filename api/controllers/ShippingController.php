<?php
class ShippingController {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    public function handleRequest($method, $id, $action) {
        switch ($method) {
            case 'GET':
                if ($action === 'options') {
                    $this->getShippingOptions();
                } else {
                    Response::error('Invalid endpoint', 400);
                }
                break;
                
            case 'POST':
                if ($action === 'address') {
                    $this->addShippingAddress();
                } elseif ($action === 'tracking') {
                    $this->trackOrder();
                } else {
                    Response::error('Invalid action', 400);
                }
                break;
                
            default:
                Response::error('Method not allowed', 405);
        }
    }
    
    private function getShippingOptions() {
        $options = [
            [
                'id' => 1,
                'name' => 'Giao hàng tiêu chuẩn',
                'price' => 30000,
                'estimated_days' => '3-5 ngày'
            ],
            [
                'id' => 2,
                'name' => 'Giao hàng nhanh',
                'price' => 50000,
                'estimated_days' => '1-2 ngày'
            ],
            [
                'id' => 3,
                'name' => 'Giao hàng hỏa tốc',
                'price' => 80000,
                'estimated_days' => 'Trong ngày'
            ]
        ];
        
        Response::success($options);
    }
    
    private function addShippingAddress() {
        $user = Auth::getUser($this->conn);
        if (!$user) {
            Response::error('Chưa đăng nhập', 401);
            return;
        }
        
        $input = Auth::getInput();
        $required = ['dia_chi', 'thanh_pho', 'quan_huyen', 'sdt'];
        
        if (!Auth::validateRequired($input, $required)) {
            Response::error('Thiếu thông tin', 400);
            return;
        }
        
        Response::success([
            'message' => 'Thêm địa chỉ thành công',
            'address' => $input
        ]);
    }
    
    private function trackOrder() {
        $input = Auth::getInput();
        
        if (!isset($input['don_hang_id'])) {
            Response::error('Thiếu don_hang_id', 400);
            return;
        }
        
        $sql = "SELECT trang_thai, ngay_tao, ngay_cap_nhat FROM don_hang WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('i', $input['don_hang_id']);
        $stmt->execute();
        $order = $stmt->get_result()->fetch_assoc();
        
        if (!$order) {
            Response::error('Không tìm thấy đơn hàng', 404);
            return;
        }
        
        $tracking = [
            'don_hang_id' => $input['don_hang_id'],
            'trang_thai' => $order['trang_thai'],
            'ngay_dat' => $order['ngay_tao'],
            'cap_nhat_cuoi' => $order['ngay_cap_nhat']
        ];
        
        Response::success($tracking);
    }
}
?>
