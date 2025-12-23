<?php
class NotificationController {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    public function handleRequest($method, $id, $action) {
        switch ($method) {
            case 'GET':
                $this->getNotifications();
                break;
                
            case 'POST':
                $this->sendNotification();
                break;
                
            default:
                Response::error('Method not allowed', 405);
        }
    }
    
    private function getNotifications() {
        $user = Auth::getUser($this->conn);
        if (!$user) {
            Response::error('Chưa đăng nhập', 401);
            return;
        }
        
        $sql = "SELECT * FROM thong_bao WHERE nguoi_dung_id = ? OR nguoi_dung_id IS NULL 
                ORDER BY ngay_tao DESC LIMIT 50";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('i', $user['id']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $notifications = [];
        while ($row = $result->fetch_assoc()) {
            $notifications[] = $row;
        }
        
        Response::success($notifications);
    }
    
    private function sendNotification() {
        if (!Auth::requireAdmin($this->conn)) {
            return;
        }
        
        $input = Auth::getInput();
        $required = ['type', 'title', 'message'];
        
        if (!Auth::validateRequired($input, $required)) {
            Response::error('Thiếu thông tin', 400);
            return;
        }
        
        $link = $input['link'] ?? '';
        
        $sql = "INSERT INTO thong_bao (type, title, message, link) VALUES (?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('ssss', $input['type'], $input['title'], $input['message'], $link);
        
        if ($stmt->execute()) {
            Response::success(['notification_id' => $this->conn->insert_id], 'Gửi thông báo thành công');
        } else {
            Response::error('Gửi thông báo thất bại', 500);
        }
    }
}
?>
