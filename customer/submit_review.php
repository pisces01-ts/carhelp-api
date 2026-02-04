<?php
require_once '../config/constants.php';
require_once '../config/database.php';
require_once '../config/jwt_helper.php';

$user = requireAuth();

$database = new Database();
$conn = $database->getConnection();

$data = json_decode(file_get_contents("php://input"));

if (empty($data->request_id) || empty($data->rating)) {
    echo json_encode(['success' => false, 'message' => 'กรุณาระบุข้อมูลให้ครบ']);
    exit();
}

$requestId = intval($data->request_id);
$rating = intval($data->rating);
$comment = trim($data->comment ?? '');
$customerId = $user['user_id'];

if ($rating < 1 || $rating > 5) {
    echo json_encode(['success' => false, 'message' => 'คะแนนต้องอยู่ระหว่าง 1-5']);
    exit();
}

// Get request info
$stmt = $conn->prepare("SELECT technician_id, status FROM service_requests WHERE request_id = ? AND customer_id = ?");
$stmt->execute([$requestId, $customerId]);
$request = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$request) {
    echo json_encode(['success' => false, 'message' => 'ไม่พบคำขอนี้']);
    exit();
}

if ($request['status'] !== 'completed') {
    echo json_encode(['success' => false, 'message' => 'งานยังไม่เสร็จสิ้น']);
    exit();
}

$techId = $request['technician_id'];

// Check if already reviewed
$stmt = $conn->prepare("SELECT review_id FROM reviews WHERE request_id = ?");
$stmt->execute([$requestId]);
if ($stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'คุณได้รีวิวงานนี้แล้ว']);
    exit();
}

try {
    $conn->beginTransaction();
    
    // Insert review
    $stmt = $conn->prepare("INSERT INTO reviews (request_id, customer_id, technician_id, rating, comment) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$requestId, $customerId, $techId, $rating, $comment]);
    
    // Update technician avg rating
    $stmt = $conn->prepare("UPDATE technician_profiles SET 
                            avg_rating = (SELECT AVG(rating) FROM reviews WHERE technician_id = ?),
                            total_jobs = (SELECT COUNT(*) FROM reviews WHERE technician_id = ?)
                            WHERE user_id = ?");
    $stmt->execute([$techId, $techId, $techId]);
    
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'ขอบคุณสำหรับรีวิว'
    ]);
    
} catch (Exception $e) {
    $conn->rollBack();
    echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาด']);
}
