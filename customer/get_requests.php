<?php
require_once '../config/constants.php';
require_once '../config/database.php';
require_once '../config/jwt_helper.php';

$user = requireAuth();

$database = new Database();
$conn = $database->getConnection();

$customerId = $user['user_id'];

$sql = "SELECT r.*, t.fullname as technician_name, t.phone as technician_phone,
        p.vehicle_model, p.vehicle_plate, p.avg_rating
        FROM service_requests r
        LEFT JOIN users t ON r.technician_id = t.user_id
        LEFT JOIN technician_profiles p ON t.user_id = p.user_id
        WHERE r.customer_id = ?
        ORDER BY r.request_time DESC";

$stmt = $conn->prepare($sql);
$stmt->execute([$customerId]);
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'success' => true,
    'requests' => $requests
]);
