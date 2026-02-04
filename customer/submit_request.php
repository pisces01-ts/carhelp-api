<?php
require_once '../config/constants.php';
require_once '../config/database.php';
require_once '../config/jwt_helper.php';

$user = requireAuth();

$database = new Database();
$conn = $database->getConnection();

$data = json_decode(file_get_contents("php://input"));

if (empty($data->problem_type) || empty($data->location_lat) || empty($data->location_lng)) {
    echo json_encode(['success' => false, 'message' => 'กรุณากรอกข้อมูลให้ครบ']);
    exit();
}

$customerId = $user['user_id'];
$problemType = trim($data->problem_type);
$problemDetails = trim($data->problem_details ?? '');
$locationLat = floatval($data->location_lat);
$locationLng = floatval($data->location_lng);
$locationAddress = trim($data->location_address ?? '');
$problemImage = $data->problem_image ?? null;

$stmt = $conn->prepare("INSERT INTO service_requests 
    (customer_id, problem_type, problem_details, location_lat, location_lng, location_address, problem_image, status) 
    VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')");
$stmt->execute([$customerId, $problemType, $problemDetails, $locationLat, $locationLng, $locationAddress, $problemImage]);
$requestId = $conn->lastInsertId();

echo json_encode([
    'success' => true,
    'message' => 'ส่งคำขอเรียกช่างเรียบร้อยแล้ว',
    'request_id' => $requestId
]);
