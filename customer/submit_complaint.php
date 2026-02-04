<?php
require_once '../config/constants.php';
require_once '../config/database.php';
require_once '../config/jwt_helper.php';

$user = requireAuth();

$database = new Database();
$conn = $database->getConnection();

$data = json_decode(file_get_contents("php://input"));

if (empty($data->request_id) || empty($data->subject) || empty($data->message)) {
    echo json_encode(['success' => false, 'message' => 'กรุณากรอกข้อมูลให้ครบ']);
    exit();
}

$requestId = intval($data->request_id);
$subject = trim($data->subject);
$message = trim($data->message);
$customerId = $user['user_id'];

// Check if request belongs to customer
$stmt = $conn->prepare("SELECT technician_id FROM service_requests WHERE request_id = ? AND customer_id = ?");
$stmt->execute([$requestId, $customerId]);
$request = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$request) {
    echo json_encode(['success' => false, 'message' => 'ไม่พบคำขอนี้']);
    exit();
}

$techId = $request['technician_id'];

// Insert complaint
$stmt = $conn->prepare("INSERT INTO complaints (request_id, customer_id, technician_id, subject, message, status) VALUES (?, ?, ?, ?, ?, 'pending')");
$stmt->execute([$requestId, $customerId, $techId, $subject, $message]);

echo json_encode([
    'success' => true,
    'message' => 'ส่งเรื่องร้องเรียนเรียบร้อยแล้ว'
]);
