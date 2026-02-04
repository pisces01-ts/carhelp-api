<?php
require_once '../config/constants.php';
require_once '../config/database.php';
require_once '../config/jwt_helper.php';

$user = requireAuth();

$database = new Database();
$conn = $database->getConnection();

$data = json_decode(file_get_contents("php://input"));

$techId = $user['user_id'];
$isOnline = isset($data->is_online) ? ($data->is_online ? 1 : 0) : 1;

$stmt = $conn->prepare("UPDATE technician_profiles SET is_online = ?, is_available = ? WHERE user_id = ?");
$stmt->execute([$isOnline, $isOnline, $techId]);

echo json_encode([
    'success' => true,
    'message' => $isOnline ? 'ออนไลน์แล้ว' : 'ออฟไลน์แล้ว',
    'is_online' => (bool)$isOnline
]);
