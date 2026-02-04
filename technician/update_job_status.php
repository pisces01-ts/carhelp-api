<?php
require_once '../config/constants.php';
require_once '../config/database.php';
require_once '../config/jwt_helper.php';

$user = requireAuth();

$database = new Database();
$conn = $database->getConnection();

$data = json_decode(file_get_contents("php://input"));

if (empty($data->request_id) || empty($data->status)) {
    echo json_encode(['success' => false, 'message' => 'กรุณาระบุ request_id และ status']);
    exit();
}

$requestId = intval($data->request_id);
$status = trim($data->status);
$techId = $user['user_id'];

$validStatuses = ['traveling', 'arrived', 'working', 'completed', 'cancelled'];
if (!in_array($status, $validStatuses)) {
    echo json_encode(['success' => false, 'message' => 'สถานะไม่ถูกต้อง']);
    exit();
}

// Check if job belongs to technician
$stmt = $conn->prepare("SELECT technician_id, status FROM service_requests WHERE request_id = ?");
$stmt->execute([$requestId]);
$job = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$job || $job['technician_id'] != $techId) {
    echo json_encode(['success' => false, 'message' => 'คุณไม่มีสิทธิ์จัดการงานนี้']);
    exit();
}

// Update status
$sql = "UPDATE service_requests SET status = ?";
$params = [$status];

if ($status === 'completed' && isset($data->price)) {
    $sql .= ", price = ?";
    $params[] = floatval($data->price);
}

$sql .= " WHERE request_id = ?";
$params[] = $requestId;

$stmt = $conn->prepare($sql);
$stmt->execute($params);

echo json_encode([
    'success' => true,
    'message' => 'อัปเดตสถานะสำเร็จ'
]);
