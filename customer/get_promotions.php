<?php
require_once '../config/constants.php';
require_once '../config/database.php';
require_once '../config/jwt_helper.php';

$user = requireAuth();

$database = new Database();
$conn = $database->getConnection();

$today = date('Y-m-d');

$stmt = $conn->prepare("SELECT promo_id, title, description, code, discount_type, discount_value, min_amount, start_date, end_date 
    FROM promotions 
    WHERE status = 'active' AND start_date <= ? AND end_date >= ? AND (max_uses = 0 OR used_count < max_uses)
    ORDER BY created_at DESC");
$stmt->execute([$today, $today]);
$promotions = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'success' => true,
    'promotions' => $promotions
]);
