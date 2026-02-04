<?php
require_once '../config/constants.php';
require_once '../config/database.php';
require_once '../config/jwt_helper.php';

$database = new Database();
$conn = $database->getConnection();

$data = json_decode(file_get_contents("php://input"));

// Validate required fields
if (empty($data->fullname) || empty($data->phone) || empty($data->password)) {
    echo json_encode(['success' => false, 'message' => 'กรุณากรอกข้อมูลให้ครบ']);
    exit();
}

$fullname = trim($data->fullname);
$phone = trim($data->phone);
$password = $data->password;
$id_card = trim($data->id_card ?? '');
$vehicle_model = trim($data->vehicle_model ?? '');
$vehicle_plate = trim($data->vehicle_plate ?? '');
$expertise = trim($data->expertise ?? '');

// Check duplicate phone
$stmt = $conn->prepare("SELECT user_id FROM users WHERE phone = ?");
$stmt->execute([$phone]);
if ($stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'เบอร์โทรนี้มีผู้ใช้งานแล้ว']);
    exit();
}

try {
    $conn->beginTransaction();
    
    // Create user with pending status (need admin approval)
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO users (fullname, phone, id_card, password, role, status) VALUES (?, ?, ?, ?, 'technician', 'pending')");
    $stmt->execute([$fullname, $phone, $id_card, $hash]);
    $userId = $conn->lastInsertId();
    
    // Create technician profile
    $stmt = $conn->prepare("INSERT INTO technician_profiles (user_id, vehicle_model, vehicle_plate, expertise, status) VALUES (?, ?, ?, ?, 'pending')");
    $stmt->execute([$userId, $vehicle_model, $vehicle_plate, $expertise]);
    
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'ลงทะเบียนสำเร็จ กรุณารอการอนุมัติจากผู้ดูแลระบบ',
        'user_id' => $userId
    ]);
    
} catch (Exception $e) {
    $conn->rollBack();
    echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()]);
}
