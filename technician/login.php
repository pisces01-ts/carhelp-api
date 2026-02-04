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

$stmt = $conn->prepare("SELECT u.user_id, u.fullname, u.phone, u.password, u.status, 
        p.vehicle_model, p.vehicle_plate, p.expertise, p.avg_rating
        FROM users u
        LEFT JOIN technician_profiles p ON u.user_id = p.user_id
        WHERE u.phone = ? AND u.role = 'technician'");
$stmt->execute([$phone]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo json_encode(['success' => false, 'message' => 'ไม่พบเบอร์โทรนี้ในระบบ']);
    exit();
}

if ($user['status'] !== 'active') {
    echo json_encode(['success' => false, 'message' => 'บัญชีถูกระงับหรือรออนุมัติ']);
    exit();
}

if (!password_verify($password, $user['password'])) {
    echo json_encode(['success' => false, 'message' => 'รหัสผ่านไม่ถูกต้อง']);
    exit();
}

$token = generateJWT([
    'user_id' => $user['user_id'],
    'role' => 'technician'
]);

unset($user['password']);

echo json_encode([
    'success' => true,
    'message' => 'เข้าสู่ระบบสำเร็จ',
    'token' => $token,
    'user' => $user
]);
