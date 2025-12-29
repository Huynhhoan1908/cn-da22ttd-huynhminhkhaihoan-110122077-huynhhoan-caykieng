<?php
class DiscountController {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    public function handleRequest($method, $id, $action) {
        switch ($method) {
            case 'GET':
                $this->getDiscounts();
                break;
                
            case 'POST':
                if ($action === 'apply') {
                    $this->applyDiscount();
                } else {
                    Response::error('Invalid action', 400);
                }
                break;
                
            default:
                Response::error('Method not allowed', 405);
        }
    }
    
    private function getDiscounts() {
        $sql = "SELECT * FROM giam_gia WHERE trang_thai = 'active' AND ngay_bat_dau <= NOW() AND ngay_ket_thuc >= NOW()";
        $result = $this->conn->query($sql);
        
        $discounts = [];
        while ($row = $result->fetch_assoc()) {
            $discounts[] = $row;
        }
        
        Response::success($discounts);
    }
    
    private function applyDiscount() {
        $input = Auth::getInput();
        
        if (!isset($input['ma_giam_gia']) || !isset($input['tong_tien'])) {
            Response::error('Thiếu thông tin', 400);
            return;
        }
        
        $sql = "SELECT * FROM giam_gia WHERE ma_code = ? AND trang_thai = 'active' 
                AND ngay_bat_dau <= NOW() AND ngay_ket_thuc >= NOW()";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('s', $input['ma_giam_gia']);
        $stmt->execute();
        $discount = $stmt->get_result()->fetch_assoc();
        
        if (!$discount) {
            Response::error('Mã giảm giá không hợp lệ', 404);
            return;
        }
        
        $giam = 0;
        if ($discount['loai'] === 'percent') {
            $giam = $input['tong_tien'] * ($discount['gia_tri'] / 100);
            if (isset($discount['giam_toi_da']) && $giam > $discount['giam_toi_da']) {
                $giam = $discount['giam_toi_da'];
            }
        } else {
            $giam = $discount['gia_tri'];
        }
        
        $tong_sau_giam = $input['tong_tien'] - $giam;
        
        Response::success([
            'tong_tien_goc' => $input['tong_tien'],
            'giam_gia' => $giam,
            'tong_sau_giam' => $tong_sau_giam,
            'ma_code' => $discount['ma_code']
        ]);
    }
}
?>
