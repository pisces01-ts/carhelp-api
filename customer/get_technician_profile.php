<?php
require_once '../config/constants.php';
require_once '../config/database.php';
require_once '../config/jwt_helper.php';

$user = requireAuth();

$database = new Database();
$conn = $database->getConnection();

$techId = intval($_GET['technician_id'] ?? 0);

if ($techId <= 0) {
    echo json_encode(['success' => false, 'message' => 'กรุณาระบุ technician_id']);
    exit();
}

// Get technician info
$stmt = $conn->prepare("SELECT u.user_id, u.fullname, u.phone, 
                        p.vehicle_model, p.vehicle_plate, p.expertise, p.avg_rating, p.total_jobs
                        FROM users u 
                        JOIN technician_profiles p ON u.user_id = p.user_id 
                        WHERE u.user_id = ? AND u.role = 'technician' AND u.status = 'active'");
$stmt->execute([$techId]);
$technician = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$technician) {
    echo json_encode(['success' => false, 'message' => 'ไม่พบข้อมูลช่าง']);
    exit();
}

// Get reviews
$stmt = $conn->prepare("SELECT r.rating, r.comment, r.created_at, u.fullname as customer_name
                        FROM reviews r
                        JOIN users u ON r.customer_id = u.user_id
                        WHERE r.technician_id = ?
                        ORDER BY r.created_at DESC
                        LIMIT 10");
$stmt->execute([$techId]);
$reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

$technician['reviews'] = $reviews;

echo json_encode([
    'success' => true,
    'technician' => $technician
]);
