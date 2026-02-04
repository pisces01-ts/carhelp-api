<?php
require_once '../config/constants.php';
require_once '../config/database.php';
require_once '../config/jwt_helper.php';

$user = requireAuth();

$database = new Database();
$conn = $database->getConnection();

$userId = $user['user_id'];

// Get recent service request updates as notifications
$stmt = $conn->prepare("SELECT 
    r.request_id,
    r.status,
    r.problem_type,
    r.request_time,
    r.accepted_time,
    r.completed_time,
    t.fullname as technician_name
    FROM service_requests r
    LEFT JOIN users t ON r.technician_id = t.user_id
    WHERE r.customer_id = ?
    ORDER BY COALESCE(r.completed_time, r.accepted_time, r.request_time) DESC
    LIMIT 20");
$stmt->execute([$userId]);
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

$notifications = [];
foreach ($requests as $r) {
    $notif = [
        'id' => $r['request_id'],
        'type' => 'job',
        'read' => false,
    ];
    
    switch ($r['status']) {
        case 'pending':
            $notif['title'] = 'กำลังค้นหาช่าง';
            $notif['message'] = "งาน {$r['problem_type']} กำลังรอช่างรับงาน";
            $notif['time'] = $r['request_time'];
            break;
        case 'accepted':
            $notif['title'] = 'ช่างรับงานแล้ว';
            $notif['message'] = "ช่าง {$r['technician_name']} รับงานของคุณแล้ว";
            $notif['time'] = $r['accepted_time'];
            break;
        case 'traveling':
            $notif['title'] = 'ช่างกำลังเดินทาง';
            $notif['message'] = "ช่าง {$r['technician_name']} กำลังเดินทางมาหาคุณ";
            $notif['time'] = $r['accepted_time'];
            break;
        case 'completed':
            $notif['title'] = 'งานเสร็จสิ้น';
            $notif['message'] = "งาน {$r['problem_type']} เสร็จสิ้นแล้ว";
            $notif['time'] = $r['completed_time'];
            $notif['type'] = 'completed';
            break;
        default:
            continue 2;
    }
    
    $notifications[] = $notif;
}

echo json_encode([
    'success' => true,
    'notifications' => $notifications
]);
