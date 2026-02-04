<?php
require_once '../config/constants.php';
require_once '../config/database.php';
require_once '../config/jwt_helper.php';

$user = requireAuth();

$database = new Database();
$conn = $database->getConnection();

$techId = $user['user_id'];

$stmt = $conn->prepare("SELECT u.user_id, u.fullname, u.phone, u.email, u.id_card, u.status,
                        p.vehicle_model, p.vehicle_plate, p.expertise, p.avg_rating, p.total_jobs, p.is_online
                        FROM users u 
                        LEFT JOIN technician_profiles p ON u.user_id = p.user_id 
                        WHERE u.user_id = ?");
$stmt->execute([$techId]);
$userData = $stmt->fetch(PDO::FETCH_ASSOC);

if ($userData) {
    echo json_encode([
        'success' => true,
        'user' => $userData
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'ไม่พบข้อมูลผู้ใช้']);
}
