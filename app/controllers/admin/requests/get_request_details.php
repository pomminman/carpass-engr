<?php
// --- app/controllers/admin/requests/get_request_details.php ---
session_start();
header('Content-Type: application/json');

// 1. ตรวจสอบสิทธิ์
if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit;
}

// 2. เรียกใช้ไฟล์และตรวจสอบ ID
require_once '../../../models/db_config.php';
$request_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$request_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid request ID']);
    exit;
}

// 3. เชื่อมต่อฐานข้อมูล
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}
$conn->set_charset("utf8");

// 4. ตรวจสอบสถานะของคำร้องก่อน
$status = null;
$sql_status = "SELECT status FROM vehicle_requests WHERE id = ?";
$stmt_status = $conn->prepare($sql_status);
$stmt_status->bind_param("i", $request_id);
$stmt_status->execute();
$result_status = $stmt_status->get_result();
if ($row_status = $result_status->fetch_assoc()) {
    $status = $row_status['status'];
}
$stmt_status->close();

if (!$status) {
    echo json_encode(['success' => false, 'message' => 'Request not found']);
    $conn->close();
    exit;
}

// 5. [แก้ไข] เลือก Query ตามสถานะ และดึง u.photo_profile เสมอ
if ($status === 'approved') {
    // ถ้าอนุมัติแล้ว, ดึงข้อมูลจากตาราง snapshot (approved_user_data) แต่ดึงรูปโปรไฟล์ล่าสุดจาก users
    $sql = "SELECT 
                vr.*, 
                aud.title AS user_title, aud.firstname AS user_firstname, aud.lastname AS user_lastname,
                aud.phone_number, aud.national_id, u.photo_profile AS photo_profile, aud.user_type,
                aud.work_department, aud.position, aud.official_id,
                aud.dob, aud.address, aud.subdistrict, aud.district, aud.province as user_province, aud.zipcode,
                a.title AS admin_title, a.firstname AS admin_firstname,
                u.user_key
            FROM vehicle_requests vr
            JOIN approved_user_data aud ON vr.id = aud.request_id
            JOIN users u ON aud.original_user_id = u.id
            LEFT JOIN admins a ON vr.approved_by_id = a.id
            WHERE vr.id = ?";
} else {
    // ถ้ายังไม่อนุมัติ (pending, rejected), ดึงข้อมูลล่าสุดจากตาราง users
    $sql = "SELECT 
                vr.*, 
                u.title AS user_title, u.firstname AS user_firstname, u.lastname AS user_lastname,
                u.phone_number, u.national_id, u.photo_profile AS photo_profile, u.user_type,
                u.work_department, u.position, u.official_id,
                u.dob, u.address, u.subdistrict, u.district, u.province as user_province, u.zipcode,
                a.title AS admin_title, a.firstname AS admin_firstname,
                u.user_key
            FROM vehicle_requests vr
            JOIN users u ON vr.user_id = u.id
            LEFT JOIN admins a ON vr.approved_by_id = a.id
            WHERE vr.id = ?";
}

// 6. Execute Query และส่งผลลัพธ์
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $request_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $data = $result->fetch_assoc();
    echo json_encode(['success' => true, 'data' => $data]);
} else {
    echo json_encode(['success' => false, 'message' => 'Could not retrieve request details.']);
}

$stmt->close();
$conn->close();
?>

