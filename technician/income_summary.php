<?php
require_once '../config/constants.php';
require_once '../config/database.php';
require_once '../config/jwt_helper.php';

$user = requireAuth();

$database = new Database();
$conn = $database->getConnection();

$techId = $user['user_id'];

// Get date range
$period = $_GET['period'] ?? 'month'; // day, week, month, year

switch ($period) {
    case 'day':
        $dateCondition = "DATE(completed_time) = CURDATE()";
        break;
    case 'week':
        $dateCondition = "YEARWEEK(completed_time) = YEARWEEK(CURDATE())";
        break;
    case 'year':
        $dateCondition = "YEAR(completed_time) = YEAR(CURDATE())";
        break;
    default: // month
        $dateCondition = "MONTH(completed_time) = MONTH(CURDATE()) AND YEAR(completed_time) = YEAR(CURDATE())";
}

// Total income
$stmt = $conn->prepare("SELECT COALESCE(SUM(price), 0) as total_income, COUNT(*) as total_jobs 
                        FROM service_requests 
                        WHERE technician_id = ? AND status = 'completed' AND $dateCondition");
$stmt->execute([$techId]);
$summary = $stmt->fetch(PDO::FETCH_ASSOC);

// All time stats
$stmt = $conn->prepare("SELECT COALESCE(SUM(price), 0) as all_time_income, COUNT(*) as all_time_jobs 
                        FROM service_requests 
                        WHERE technician_id = ? AND status = 'completed'");
$stmt->execute([$techId]);
$allTime = $stmt->fetch(PDO::FETCH_ASSOC);

// Recent jobs
$stmt = $conn->prepare("SELECT request_id, problem_type, price, completed_time, 
                        (SELECT fullname FROM users WHERE user_id = customer_id) as customer_name
                        FROM service_requests 
                        WHERE technician_id = ? AND status = 'completed'
                        ORDER BY completed_time DESC
                        LIMIT 10");
$stmt->execute([$techId]);
$recentJobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Daily income for chart (last 7 days)
$stmt = $conn->prepare("SELECT DATE(completed_time) as date, SUM(price) as income, COUNT(*) as jobs
                        FROM service_requests 
                        WHERE technician_id = ? AND status = 'completed' 
                        AND completed_time >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                        GROUP BY DATE(completed_time)
                        ORDER BY date");
$stmt->execute([$techId]);
$dailyIncome = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Rating
$stmt = $conn->prepare("SELECT avg_rating, total_jobs FROM technician_profiles WHERE user_id = ?");
$stmt->execute([$techId]);
$profile = $stmt->fetch(PDO::FETCH_ASSOC);

echo json_encode([
    'success' => true,
    'period' => $period,
    'summary' => [
        'total_income' => floatval($summary['total_income']),
        'total_jobs' => intval($summary['total_jobs']),
        'all_time_income' => floatval($allTime['all_time_income']),
        'all_time_jobs' => intval($allTime['all_time_jobs']),
        'avg_rating' => floatval($profile['avg_rating'] ?? 0),
    ],
    'recent_jobs' => $recentJobs,
    'daily_income' => $dailyIncome,
]);
