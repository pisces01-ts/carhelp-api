<?php
require_once '../config/constants.php';
require_once '../config/database.php';
require_once '../config/jwt_helper.php';

$user = requireAuth();

$database = new Database();
$conn = $database->getConnection();

$data = json_decode(file_get_contents("php://input"));

if (empty($data->request_id) || empty($data->technician_id)) {
    echo json_encode(['success' => false, 'message' => 'กรุณาระบุข้อมูลให้ครบ']);
    exit();
}

$requestId = intval($data->request_id);
$techId = intval($data->technician_id);
$customerId = $user['user_id'];

// Check if request belongs to customer
$stmt = $conn->prepare("SELECT status FROM service_requests WHERE request_id = ? AND customer_id = ?");
$stmt->execute([$requestId, $customerId]);
$request = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$request) {
    echo json_encode(['success' => false, 'message' => 'ไม่พบคำขอนี้']);
    exit();
}

if ($request['status'] !== 'pending') {
    echo json_encode(['success' => false, 'message' => 'คำขอนี้ถูกดำเนินการแล้ว']);
    exit();
}

// Check if technician is available
$stmt = $conn->prepare("SELECT is_available FROM technician_profiles WHERE user_id = ?");
$stmt->execute([$techId]);
$tech = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$tech || !$tech['is_available']) {
    echo json_encode(['success' => false, 'message' => 'ช่างไม่ว่างในขณะนี้']);
    exit();
}

// Assign technician to request
$stmt = $conn->prepare("UPDATE service_requests SET technician_id = ?, status = 'pending' WHERE request_id = ?");
$stmt->execute([$techId, $requestId]);

echo json_encode([
    'success' => true,
    'message' => 'เลือกช่างสำเร็จ กรุณารอช่างตอบรับ'
]);
