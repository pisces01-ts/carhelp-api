<?php
require_once '../config/constants.php';
require_once '../config/database.php';
require_once '../config/jwt_helper.php';

$user = requireAuth();

$database = new Database();
$conn = $database->getConnection();

$data = json_decode(file_get_contents("php://input"));

if (empty($data->request_id)) {
    echo json_encode(['success' => false, 'message' => 'กรุณาระบุ request_id']);
    exit();
}

$requestId = intval($data->request_id);
$techId = $user['user_id'];

// Check if job is available
$stmt = $conn->prepare("SELECT status, technician_id FROM service_requests WHERE request_id = ?");
$stmt->execute([$requestId]);
$job = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$job) {
    echo json_encode(['success' => false, 'message' => 'ไม่พบงานนี้']);
    exit();
}

if ($job['status'] !== 'pending' || ($job['technician_id'] && $job['technician_id'] != $techId)) {
    echo json_encode(['success' => false, 'message' => 'งานนี้ถูกรับไปแล้ว']);
    exit();
}

// Accept job
$stmt = $conn->prepare("UPDATE service_requests SET status = 'accepted', technician_id = ? WHERE request_id = ?");
$stmt->execute([$techId, $requestId]);

echo json_encode([
    'success' => true,
    'message' => 'รับงานสำเร็จ'
]);
