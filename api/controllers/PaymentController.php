<?php
class PaymentController {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    public function handleRequest($method, $id, $action) {
        switch ($method) {
            case 'GET':
                if ($id) {
                    $this->getPayment($id);
                } else {
                    Response::error('Payment ID required', 400);
                }
                break;
                
            case 'POST':
                if ($action === 'verify') {
                    $this->verifyPayment();
                } else {
                    $this->createPayment();
                }
                break;
                
            default:
                Response::error('Method not allowed', 405);
        }
    }
    
    private function createPayment() {
        $user = Auth::getUser($this->conn);
        if (!$user) {
            Response::error('Chưa đăng nhập', 401);
            return;
        }
        
        $input = Auth::getInput();
        $required = ['don_hang_id', 'phuong_thuc', 'so_tien'];
        
        if (!Auth::validateRequired($input, $required)) {
            Response::error('Thiếu thông tin', 400);
            return;
        }
        
        $sql = "INSERT INTO thanh_toan (don_hang_id, phuong_thuc, so_tien, trang_thai) 
                VALUES (?, ?, ?, 'Chờ xác nhận')";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('isd', $input['don_hang_id'], $input['phuong_thuc'], $input['so_tien']);
        
        if ($stmt->execute()) {
            Response::success(['payment_id' => $this->conn->insert_id], 'Tạo thanh toán thành công');
        } else {
            Response::error('Tạo thanh toán thất bại', 500);
        }
    }
    
    private function getPayment($id) {
        $user = Auth::getUser($this->conn);
        if (!$user) {
            Response::error('Chưa đăng nhập', 401);
            return;
        }
        
        $sql = "SELECT * FROM thanh_toan WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        if ($result) {
            Response::success($result);
        } else {
            Response::error('Không tìm thấy', 404);
        }
    }
    
    private function verifyPayment() {
        if (!Auth::requireAdmin($this->conn)) {
            return;
        }
        
        $input = Auth::getInput();
        if (!isset($input['payment_id'])) {
            Response::error('Thiếu payment_id', 400);
            return;
        }
        
        $sql = "UPDATE thanh_toan SET trang_thai = 'Đã xác nhận' WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('i', $input['payment_id']);
        
        if ($stmt->execute()) {
            Response::success(null, 'Xác nhận thanh toán thành công');
        } else {
            Response::error('Xác nhận thất bại', 500);
        }
    }
}
?>
