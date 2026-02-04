<?php
require_once '../config/constants.php';
require_once '../config/database.php';
require_once '../config/jwt_helper.php';

$user = requireAuth();

$database = new Database();
$conn = $database->getConnection();

$data = json_decode(file_get_contents("php://input"));

$requestId = intval($data->request_id ?? 0);
$reason = trim($data->reason ?? '');
$customerId = $user['user_id'];

if (!$requestId) {
    echo json_encode(['success' => false, 'message' => 'กรุณาระบุรหัสคำขอ']);
    exit();
}

// Check if request belongs to customer and is cancellable
$stmt = $conn->prepare("SELECT status FROM service_requests WHERE request_id = ? AND customer_id = ?");
$stmt->execute([$requestId, $customerId]);
$request = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$request) {
    echo json_encode(['success' => false, 'message' => 'ไม่พบคำขอนี้']);
    exit();
}

$cancellableStatuses = ['pending', 'accepted'];
if (!in_array($request['status'], $cancellableStatuses)) {
    echo json_encode(['success' => false, 'message' => 'ไม่สามารถยกเลิกคำขอนี้ได้']);
    exit();
}

$stmt = $conn->prepare("UPDATE service_requests SET status = 'cancelled', cancel_reason = ?, cancelled_at = NOW() WHERE request_id = ?");
$stmt->execute([$reason, $requestId]);

echo json_encode([
    'success' => true,
    'message' => 'ยกเลิกคำขอเรียบร้อยแล้ว'
]);
