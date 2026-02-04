<?php
require_once '../config/constants.php';
require_once '../config/database.php';
require_once '../config/jwt_helper.php';

$user = requireAuth();

$database = new Database();
$conn = $database->getConnection();

$data = json_decode(file_get_contents("php://input"));

$techId = $user['user_id'];
$fullname = trim($data->fullname ?? '');
$vehicle_model = trim($data->vehicle_model ?? '');
$vehicle_plate = trim($data->vehicle_plate ?? '');
$expertise = trim($data->expertise ?? '');

try {
    $conn->beginTransaction();
    
    // Update user
    if (!empty($fullname)) {
        $stmt = $conn->prepare("UPDATE users SET fullname = ? WHERE user_id = ?");
        $stmt->execute([$fullname, $techId]);
    }
    
    // Update profile
    $stmt = $conn->prepare("UPDATE technician_profiles SET vehicle_model = ?, vehicle_plate = ?, expertise = ? WHERE user_id = ?");
    $stmt->execute([$vehicle_model, $vehicle_plate, $expertise, $techId]);
    
    if ($stmt->rowCount() == 0) {
        // Create profile if not exists
        $stmt = $conn->prepare("INSERT INTO technician_profiles (user_id, vehicle_model, vehicle_plate, expertise) VALUES (?, ?, ?, ?)");
        $stmt->execute([$techId, $vehicle_model, $vehicle_plate, $expertise]);
    }
    
    $conn->commit();
    
    // Get updated data
    $stmt = $conn->prepare("SELECT u.user_id, u.fullname, u.phone, u.email, p.vehicle_model, p.vehicle_plate, p.expertise, p.avg_rating 
                            FROM users u LEFT JOIN technician_profiles p ON u.user_id = p.user_id WHERE u.user_id = ?");
    $stmt->execute([$techId]);
    $userData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'message' => 'อัปเดตโปรไฟล์สำเร็จ',
        'user' => $userData
    ]);
    
} catch (Exception $e) {
    $conn->rollBack();
    echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()]);
}
