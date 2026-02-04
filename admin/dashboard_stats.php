<?php
require_once '../config/constants.php';
require_once '../config/database.php';

$database = new Database();
$conn = $database->getConnection();

// Total counts
$stats = [];

// Users count
$stmt = $conn->query("SELECT role, COUNT(*) as count FROM users GROUP BY role");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $stats['users_' . $row['role']] = intval($row['count']);
}

// Pending technicians
$stmt = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'technician' AND status = 'pending'");
$stats['pending_technicians'] = intval($stmt->fetch(PDO::FETCH_ASSOC)['count']);

// Jobs by status
$stmt = $conn->query("SELECT status, COUNT(*) as count FROM service_requests GROUP BY status");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $stats['jobs_' . $row['status']] = intval($row['count']);
}

// Total revenue
$stmt = $conn->query("SELECT COALESCE(SUM(price), 0) as total FROM service_requests WHERE status = 'completed'");
$stats['total_revenue'] = floatval($stmt->fetch(PDO::FETCH_ASSOC)['total']);

// Today's stats
$stmt = $conn->query("SELECT COUNT(*) as count, COALESCE(SUM(price), 0) as revenue 
                      FROM service_requests WHERE DATE(request_time) = CURDATE()");
$today = $stmt->fetch(PDO::FETCH_ASSOC);
$stats['today_jobs'] = intval($today['count']);
$stats['today_revenue'] = floatval($today['revenue']);

// This month
$stmt = $conn->query("SELECT COUNT(*) as count, COALESCE(SUM(price), 0) as revenue 
                      FROM service_requests 
                      WHERE MONTH(request_time) = MONTH(CURDATE()) AND YEAR(request_time) = YEAR(CURDATE()) AND status = 'completed'");
$month = $stmt->fetch(PDO::FETCH_ASSOC);
$stats['month_jobs'] = intval($month['count']);
$stats['month_revenue'] = floatval($month['revenue']);

// Daily jobs for chart (last 7 days)
$stmt = $conn->query("SELECT DATE(request_time) as date, COUNT(*) as jobs, COALESCE(SUM(CASE WHEN status = 'completed' THEN price ELSE 0 END), 0) as revenue
                      FROM service_requests 
                      WHERE request_time >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                      GROUP BY DATE(request_time)
                      ORDER BY date");
$daily = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Top technicians
$stmt = $conn->query("SELECT u.fullname, p.total_jobs, p.avg_rating
                      FROM technician_profiles p
                      JOIN users u ON p.user_id = u.user_id
                      WHERE u.status = 'active'
                      ORDER BY p.total_jobs DESC
                      LIMIT 5");
$topTechnicians = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Complaints count
$stmt = $conn->query("SELECT COUNT(*) as count FROM complaints WHERE status = 'pending'");
$result = $stmt->fetch(PDO::FETCH_ASSOC);
$stats['pending_complaints'] = intval($result['count'] ?? 0);

echo json_encode([
    'success' => true,
    'stats' => $stats,
    'daily_chart' => $daily,
    'top_technicians' => $topTechnicians,
]);
