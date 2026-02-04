<?php
require_once '../config/constants.php';
require_once '../config/database.php';
require_once '../config/jwt_helper.php';

$user = requireAuth();

$database = new Database();
$conn = $database->getConnection();

$data = json_decode(file_get_contents("php://input"));

if (!isset($data->lat) || !isset($data->lng)) {
    echo json_encode(['success' => false, 'message' => 'กรุณาระบุพิกัด']);
    exit();
}

$techId = $user['user_id'];
$lat = floatval($data->lat);
$lng = floatval($data->lng);

$stmt = $conn->prepare("UPDATE technician_profiles SET current_lat = ?, current_lng = ?, last_location_update = NOW() WHERE user_id = ?");
$stmt->execute([$lat, $lng, $techId]);

echo json_encode([
    'success' => true,
    'message' => 'อัปเดตตำแหน่งสำเร็จ'
]);
