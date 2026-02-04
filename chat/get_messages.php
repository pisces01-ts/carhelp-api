<?php
require_once '../config/constants.php';
require_once '../config/database.php';
require_once '../config/jwt_helper.php';

$user = requireAuth();

$database = new Database();
$conn = $database->getConnection();

$requestId = intval($_GET['request_id'] ?? 0);
$lastId = intval($_GET['last_id'] ?? 0);
$userId = $user['user_id'];

if ($requestId <= 0) {
    echo json_encode(['success' => false, 'message' => 'กรุณาระบุ request_id']);
    exit();
}

// Check if user is part of this request
$stmt = $conn->prepare("SELECT customer_id, technician_id FROM service_requests WHERE request_id = ?");
$stmt->execute([$requestId]);
$request = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$request) {
    echo json_encode(['success' => false, 'message' => 'ไม่พบคำขอนี้']);
    exit();
}

if ($userId != $request['customer_id'] && $userId != $request['technician_id']) {
    echo json_encode(['success' => false, 'message' => 'คุณไม่มีสิทธิ์ดูข้อความในงานนี้']);
    exit();
}

// Get messages
$sql = "SELECT m.message_id, m.sender_id, m.sender_role, m.message, m.created_at, u.fullname as sender_name
        FROM chat_messages m
        JOIN users u ON m.sender_id = u.user_id
        WHERE m.request_id = ?";

if ($lastId > 0) {
    $sql .= " AND m.message_id > ?";
    $stmt = $conn->prepare($sql . " ORDER BY m.created_at ASC");
    $stmt->execute([$requestId, $lastId]);
} else {
    $stmt = $conn->prepare($sql . " ORDER BY m.created_at ASC LIMIT 100");
    $stmt->execute([$requestId]);
}

$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'success' => true,
    'messages' => $messages
]);
