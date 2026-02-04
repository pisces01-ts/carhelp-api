<?php
require_once '../config/constants.php';
require_once '../config/database.php';
require_once '../config/jwt_helper.php';

$user = requireAuth();

$database = new Database();
$conn = $database->getConnection();

$techId = $user['user_id'];

$notifications = [];

// New jobs nearby (pending jobs)
$stmt = $conn->prepare("SELECT request_id, problem_type, location_address, request_time 
    FROM service_requests 
    WHERE status = 'pending' AND technician_id IS NULL
    ORDER BY request_time DESC LIMIT 5");
$stmt->execute();
$newJobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($newJobs as $job) {
    $notifications[] = [
        'id' => 'job_' . $job['request_id'],
        'type' => 'new_job',
        'title' => 'งานใหม่เข้ามา!',
        'message' => $job['problem_type'] . ' - ' . mb_substr($job['location_address'] ?? 'ไม่ระบุที่อยู่', 0, 30),
        'time' => $job['request_time'],
        'read' => false,
    ];
}

// Recent reviews
$stmt = $conn->prepare("SELECT rv.review_id, rv.rating, rv.comment, rv.created_at, u.fullname as customer_name
    FROM reviews rv
    JOIN users u ON rv.customer_id = u.user_id
    WHERE rv.technician_id = ?
    ORDER BY rv.created_at DESC LIMIT 5");
$stmt->execute([$techId]);
$reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($reviews as $rv) {
    $notifications[] = [
        'id' => 'review_' . $rv['review_id'],
        'type' => 'review',
        'title' => "ได้รับรีวิว {$rv['rating']} ดาว",
        'message' => "จากคุณ {$rv['customer_name']}" . ($rv['comment'] ? ": " . mb_substr($rv['comment'], 0, 30) : ''),
        'time' => $rv['created_at'],
        'read' => false,
    ];
}

// Recent completed jobs (payment)
$stmt = $conn->prepare("SELECT request_id, price, completed_time 
    FROM service_requests 
    WHERE technician_id = ? AND status = 'completed'
    ORDER BY completed_time DESC LIMIT 5");
$stmt->execute([$techId]);
$payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($payments as $p) {
    $notifications[] = [
        'id' => 'payment_' . $p['request_id'],
        'type' => 'payment',
        'title' => 'ยอดเงินเข้า',
        'message' => "คุณได้รับเงิน ฿" . number_format($p['price'], 0) . " จากงาน #" . str_pad($p['request_id'], 5, '0', STR_PAD_LEFT),
        'time' => $p['completed_time'],
        'read' => false,
    ];
}

// Sort by time
usort($notifications, function($a, $b) {
    return strtotime($b['time']) - strtotime($a['time']);
});

echo json_encode([
    'success' => true,
    'notifications' => array_slice($notifications, 0, 15)
]);
