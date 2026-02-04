<?php
require_once '../config/constants.php';
require_once '../config/database.php';
require_once '../config/jwt_helper.php';

$database = new Database();
$conn = $database->getConnection();

$data = json_decode(file_get_contents("php://input"));

if (empty($data->phone) || empty($data->password)) {
    echo json_encode(['success' => false, 'message' => 'กรุณากรอกเบอร์โทรและรหัสผ่าน']);
    exit();
}

$phone = trim($data->phone);
$password = $data->password;

$stmt = $conn->prepare("SELECT user_id, fullname, phone, email, password, status FROM users WHERE phone = ? AND role = 'customer'");
$stmt->execute([$phone]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo json_encode(['success' => false, 'message' => 'ไม่พบเบอร์โทรนี้ในระบบ']);
    exit();
}

if ($user['status'] !== 'active') {
    echo json_encode(['success' => false, 'message' => 'บัญชีถูกระงับการใช้งาน']);
    exit();
}

if (!password_verify($password, $user['password'])) {
    echo json_encode(['success' => false, 'message' => 'รหัสผ่านไม่ถูกต้อง']);
    exit();
}

$token = generateJWT([
    'user_id' => $user['user_id'],
    'role' => 'customer'
]);

unset($user['password']);

echo json_encode([
    'success' => true,
    'message' => 'เข้าสู่ระบบสำเร็จ',
    'token' => $token,
    'user' => $user
]);
