<?php
require_once '../config/constants.php';
require_once '../config/database.php';
require_once '../config/jwt_helper.php';

$user = requireAuth();

$database = new Database();
$conn = $database->getConnection();

$data = json_decode(file_get_contents("php://input"));

$lat = floatval($data->lat ?? 0);
$lng = floatval($data->lng ?? 0);
$radius = floatval($data->radius ?? 10); // km
$problemType = trim($data->problem_type ?? '');

// Haversine formula to calculate distance
$sql = "SELECT u.user_id, u.fullname, u.phone, 
        p.vehicle_model, p.vehicle_plate, p.expertise, p.avg_rating, p.total_jobs,
        p.current_lat, p.current_lng,
        (6371 * acos(cos(radians(?)) * cos(radians(p.current_lat)) * cos(radians(p.current_lng) - radians(?)) + sin(radians(?)) * sin(radians(p.current_lat)))) AS distance
        FROM users u
        JOIN technician_profiles p ON u.user_id = p.user_id
        WHERE u.role = 'technician' 
        AND u.status = 'active'
        AND p.is_online = 1 
        AND p.is_available = 1
        AND p.current_lat IS NOT NULL 
        AND p.current_lng IS NOT NULL
        HAVING distance <= ?
        ORDER BY distance ASC
        LIMIT 20";

$stmt = $conn->prepare($sql);
$stmt->execute([$lat, $lng, $lat, $radius]);
$technicians = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Format distance
foreach ($technicians as &$tech) {
    $tech['distance'] = round($tech['distance'], 2);
    $tech['distance_text'] = $tech['distance'] < 1 
        ? round($tech['distance'] * 1000) . ' ม.' 
        : $tech['distance'] . ' กม.';
}

echo json_encode([
    'success' => true,
    'technicians' => $technicians,
    'count' => count($technicians)
]);
