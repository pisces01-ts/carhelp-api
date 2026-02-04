<?php
require_once '../config/constants.php';
require_once '../config/database.php';
require_once '../config/jwt_helper.php';

$user = requireAuth();

$database = new Database();
$conn = $database->getConnection();

$data = json_decode(file_get_contents("php://input"));

if (empty($data->request_id) || empty($data->message)) {
    echo json_encode(['success' => false, 'message' => 'กรุณาระบุข้อมูลให้ครบ']);
    exit();
}

$requestId = intval($data->request_id);
$message = trim($data->message);
$senderId = $user['user_id'];
$senderRole = $user['role'];

// Check if user is part of this request
$stmt = $conn->prepare("SELECT customer_id, technician_id FROM service_requests WHERE request_id = ?");
$stmt->execute([$requestId]);
$request = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$request) {
    echo json_encode(['success' => false, 'message' => 'ไม่พบคำขอนี้']);
    exit();
}

if ($senderId != $request['customer_id'] && $senderId != $request['technician_id']) {
    echo json_encode(['success' => false, 'message' => 'คุณไม่มีสิทธิ์ส่งข้อความในงานนี้']);
    exit();
}

// Insert message
$stmt = $conn->prepare("INSERT INTO chat_messages (request_id, sender_id, sender_role, message) VALUES (?, ?, ?, ?)");
$stmt->execute([$requestId, $senderId, $senderRole, $message]);
$messageId = $conn->lastInsertId();

echo json_encode([
    'success' => true,
    'message_id' => $messageId,
    'created_at' => date('Y-m-d H:i:s')
]);
