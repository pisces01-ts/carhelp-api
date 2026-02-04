<?php
require_once '../config/constants.php';
require_once '../config/database.php';
require_once '../config/jwt_helper.php';

$user = requireAuth();

$database = new Database();
$conn = $database->getConnection();

$techId = $user['user_id'];

$sql = "SELECT r.*, u.fullname AS customer_name, u.phone AS customer_phone, u.address AS customer_address
        FROM service_requests r
        LEFT JOIN users u ON r.customer_id = u.user_id
        WHERE (r.status = 'pending' AND (r.technician_id IS NULL OR r.technician_id = 0))
           OR (r.technician_id = ? AND r.status NOT IN ('completed', 'cancelled'))
        ORDER BY r.request_time DESC";

$stmt = $conn->prepare($sql);
$stmt->execute([$techId]);
$jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'success' => true,
    'jobs' => $jobs
]);
