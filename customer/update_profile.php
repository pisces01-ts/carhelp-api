<?php
require_once '../config/constants.php';
require_once '../config/database.php';
require_once '../config/jwt_helper.php';

$user = requireAuth();

$database = new Database();
$conn = $database->getConnection();

$data = json_decode(file_get_contents("php://input"));

$userId = $user['user_id'];
$fullname = trim($data->fullname ?? '');
$email = trim($data->email ?? '');
$phone = trim($data->phone ?? '');

if (empty($fullname)) {
    echo json_encode(['success' => false, 'message' => 'กรุณากรอกชื่อ']);
    exit();
}

// Check if phone is taken by another user
if (!empty($phone)) {
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE phone = ? AND user_id != ?");
    $stmt->execute([$phone, $userId]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'เบอร์โทรนี้มีผู้ใช้งานแล้ว']);
        exit();
    }
}

$stmt = $conn->prepare("UPDATE users SET fullname = ?, email = ?, phone = ? WHERE user_id = ?");
$stmt->execute([$fullname, $email, $phone, $userId]);

echo json_encode([
    'success' => true,
    'message' => 'อัปเดตข้อมูลเรียบร้อยแล้ว'
]);
