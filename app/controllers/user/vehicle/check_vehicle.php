<?php
// app/controllers/user/vehicle/check_vehicle.php

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    echo json_encode(['exists' => false, 'message' => 'Authentication required']);
    exit;
}

require_once '../../../models/db_config.php';

$data = json_decode(file_get_contents('php://input'), true);
$license_plate = $data['license_plate'] ?? '';
$province = $data['province'] ?? '';

if (empty($license_plate) || empty($province)) {
    echo json_encode(['exists' => false, 'message' => 'License plate and province are required.']);
    exit;
}

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    echo json_encode(['exists' => false, 'message' => 'Database connection error']);
    exit;
}
$conn->set_charset("utf8");

// This query checks if the vehicle has an active or pending request in the CURRENT active period.
$sql = "
    SELECT vr.id 
    FROM vehicle_requests vr
    JOIN vehicles v ON vr.vehicle_id = v.id
    JOIN application_periods ap ON vr.period_id = ap.id
    WHERE v.license_plate = ? 
    AND v.province = ? 
    AND ap.is_active = 1 
    AND CURDATE() BETWEEN ap.start_date AND ap.end_date
    AND vr.status IN ('pending', 'approved')
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $license_plate, $province);
$stmt->execute();
$result = $stmt->get_result();

echo json_encode(['exists' => $result->num_rows > 0]);

$stmt->close();
$conn->close();
?>
