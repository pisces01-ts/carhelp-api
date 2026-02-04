<?php
require_once '../config/constants.php';
require_once '../config/database.php';
require_once '../config/jwt_helper.php';

$user = requireAuth();

$database = new Database();
$conn = $database->getConnection();

$userId = $user['user_id'];

$stmt = $conn->prepare("SELECT user_id, fullname, phone, email, created_at FROM users WHERE user_id = ? AND role = 'customer'");
$stmt->execute([$userId]);
$profile = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$profile) {
    echo json_encode(['success' => false, 'message' => 'ไม่พบข้อมูลผู้ใช้']);
    exit();
}

// Get stats
$stmt = $conn->prepare("SELECT COUNT(*) as total_requests, 
    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_requests
    FROM service_requests WHERE customer_id = ?");
$stmt->execute([$userId]);
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

echo json_encode([
    'success' => true,
    'profile' => $profile,
    'stats' => [
        'total_requests' => intval($stats['total_requests']),
        'completed_requests' => intval($stats['completed_requests'])
    ]
]);
