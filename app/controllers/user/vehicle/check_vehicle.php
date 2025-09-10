<?php
// --- app/controllers/user/vehicle/check_vehicle.php ---
session_start();
header('Content-Type: application/json');

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    echo json_encode(['exists' => false, 'message' => 'Authentication required']);
    exit;
}

require_once '../../../models/db_config.php';

// รับข้อมูลที่ส่งมาเป็น JSON
$data = json_decode(file_get_contents('php://input'), true);
$license_plate = $data['license_plate'] ?? '';
$province = $data['province'] ?? '';

// ตรวจสอบว่ามีข้อมูลที่จำเป็นครบถ้วนหรือไม่
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

// สร้าง SQL Query เพื่อตรวจสอบว่ารถทะเบียนนี้
// ได้ยื่นคำร้องใน "รอบปัจจุบัน" ที่มีสถานะ "รออนุมัติ" หรือ "อนุมัติแล้ว" หรือไม่
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
if ($stmt) {
    $stmt->bind_param("ss", $license_plate, $province);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // พบข้อมูลซ้ำในรอบปัจจุบัน
        echo json_encode(['exists' => true]);
    } else {
        // ไม่พบข้อมูลซ้ำ
        echo json_encode(['exists' => false]);
    }
    $stmt->close();
} else {
    echo json_encode(['exists' => false, 'message' => 'Failed to prepare statement.']);
}

$conn->close();
?>
