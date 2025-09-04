<?php
// controllers/user/vehicle/check_vehicle.php

session_start();
header('Content-Type: application/json');

// ตรวจสอบว่าผู้ใช้ล็อกอินแล้วหรือยัง
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    echo json_encode(['error' => 'Authentication required.']);
    http_response_code(401);
    exit;
}

require_once '../../../models/db_config.php';

$response = ['exists' => false];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $license_plate = $data['license_plate'] ?? null;
    $province = $data['province'] ?? null;
    $request_id = $data['request_id'] ?? 0; // รับ request_id สำหรับการแก้ไข (ถ้ามี)

    if ($license_plate && $province) {
        $conn = new mysqli($servername, $username, $password, $dbname);
        if ($conn->connect_error) {
            echo json_encode(['error' => 'Database connection failed.']);
            http_response_code(500);
            exit;
        }
        $conn->set_charset("utf8");

        // คิวรีเพื่อตรวจสอบว่ามีป้ายทะเบียนและจังหวัดนี้ในระบบแล้วหรือไม่ (โดยไม่นับรวม request_id ปัจจุบัน)
        $sql = "SELECT id FROM vehicle_requests WHERE license_plate = ? AND province = ? AND id != ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssi", $license_plate, $province, $request_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $response['exists'] = true;
        }

        $stmt->close();
        $conn->close();
    } else {
        $response['error'] = 'Invalid input.';
        http_response_code(400);
    }
} else {
    $response['error'] = 'Invalid request method.';
    http_response_code(405);
}

echo json_encode($response);
?>
