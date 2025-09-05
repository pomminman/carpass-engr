<?php
// --- app/controllers/admin/requests/get_request_details.php ---
session_start();
header('Content-Type: application/json');

// 1. ตรวจสอบสิทธิ์: ต้องเป็นแอดมินที่ล็อกอินแล้วเท่านั้น
if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit;
}

// 2. เรียกใช้ไฟล์ที่จำเป็น
require_once '../../../models/db_config.php';

// 3. ตรวจสอบว่ามี request_id ส่งมาหรือไม่
$request_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$request_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid request ID']);
    exit;
}

// 4. เชื่อมต่อฐานข้อมูล
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}
$conn->set_charset("utf8");

// 5. เตรียมคำสั่ง SQL เพื่อดึงข้อมูลทั้งหมดที่จำเป็น
$sql = "SELECT 
            vr.*, 
            u.title AS user_title, u.firstname AS user_firstname, u.lastname AS user_lastname,
            u.phone_number, u.national_id, u.photo_profile, u.user_type,
            u.work_department, u.position, u.official_id,
            a.title AS admin_title, a.firstname AS admin_firstname
        FROM vehicle_requests vr
        JOIN users u ON vr.user_id = u.id
        LEFT JOIN admins a ON vr.approved_by_id = a.id
        WHERE vr.id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $request_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $data = $result->fetch_assoc();
    echo json_encode(['success' => true, 'data' => $data]);
} else {
    echo json_encode(['success' => false, 'message' => 'Request not found']);
}

$stmt->close();
$conn->close();
?>
