<?php
require_once '../config/constants.php';
require_once '../config/database.php';

$database = new Database();
$conn = $database->getConnection();

$stmt = $conn->prepare("SELECT id, name FROM repair_types WHERE is_active = 1 ORDER BY name");
$stmt->execute();
$types = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'success' => true,
    'repair_types' => $types
]);
