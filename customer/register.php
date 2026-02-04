<?php
require_once '../config/constants.php';
require_once '../config/database.php';
require_once '../config/jwt_helper.php';

$database = new Database();
$conn = $database->getConnection();

$data = json_decode(file_get_contents("php://input"));

if (empty($data->fullname) || empty($data->phone) || empty($data->password)) {
    echo json_encode(['success' => false, 'message' => 'กรุณากรอกข้อมูลให้ครบ']);
    exit();
}

$fullname = trim($data->fullname);
$phone = trim($data->phone);
$password = $data->password;
$email = trim($data->email ?? '');

// Check duplicate phone
$stmt = $conn->prepare("SELECT user_id FROM users WHERE phone = ?");
$stmt->execute([$phone]);
if ($stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'เบอร์โทรนี้มีผู้ใช้งานแล้ว']);
    exit();
}

// Create user
$hash = password_hash($password, PASSWORD_DEFAULT);
$stmt = $conn->prepare("INSERT INTO users (fullname, phone, email, password, role, status) VALUES (?, ?, ?, ?, 'customer', 'active')");
$stmt->execute([$fullname, $phone, $email, $hash]);
$userId = $conn->lastInsertId();

$token = generateJWT([
    'user_id' => $userId,
    'role' => 'customer'
]);

echo json_encode([
    'success' => true,
    'message' => 'สมัครสมาชิกสำเร็จ',
    'token' => $token,
    'user' => [
        'user_id' => $userId,
        'fullname' => $fullname,
        'phone' => $phone,
        'email' => $email
    ]
]);
