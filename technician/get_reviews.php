<?php
require_once '../config/constants.php';
require_once '../config/database.php';
require_once '../config/jwt_helper.php';

$user = requireAuth();

$database = new Database();
$conn = $database->getConnection();

$techId = $user['user_id'];

// Get stats
$stmt = $conn->prepare("SELECT 
    COUNT(*) as total_reviews,
    AVG(rating) as avg_rating,
    SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as five_star,
    SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) as four_star,
    SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as three_star,
    SUM(CASE WHEN rating <= 2 THEN 1 ELSE 0 END) as low_star
    FROM reviews WHERE technician_id = ?");
$stmt->execute([$techId]);
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Get reviews
$stmt = $conn->prepare("SELECT rv.*, u.fullname as customer_name, r.problem_type
    FROM reviews rv
    LEFT JOIN users u ON rv.customer_id = u.user_id
    LEFT JOIN service_requests r ON rv.request_id = r.request_id
    WHERE rv.technician_id = ?
    ORDER BY rv.created_at DESC
    LIMIT 50");
$stmt->execute([$techId]);
$reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'success' => true,
    'stats' => [
        'total_reviews' => intval($stats['total_reviews']),
        'avg_rating' => floatval($stats['avg_rating'] ?? 0),
        'five_star' => intval($stats['five_star']),
        'four_star' => intval($stats['four_star']),
        'three_star' => intval($stats['three_star']),
        'low_star' => intval($stats['low_star']),
    ],
    'reviews' => $reviews
]);
