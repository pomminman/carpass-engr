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

// 4. [แก้ไข] สร้าง Query ที่สมบูรณ์เพียงชุดเดียว
$sql = "SELECT 
            vr.*, 
            v.vehicle_type, v.brand, v.model, v.color, v.license_plate, v.province,
            u.user_key,
            a.title AS admin_title, a.firstname AS admin_firstname,
            COALESCE(aud.title, u.title) AS user_title,
            COALESCE(aud.firstname, u.firstname) AS user_firstname,
            COALESCE(aud.lastname, u.lastname) AS user_lastname,
            COALESCE(aud.phone_number, u.phone_number) AS phone_number,
            COALESCE(aud.national_id, u.national_id) AS national_id,
            u.photo_profile, -- ดึงรูปโปรไฟล์ล่าสุดจากตาราง users เสมอ
            COALESCE(aud.user_type, u.user_type) AS user_type,
            COALESCE(aud.work_department, u.work_department) AS work_department,
            COALESCE(aud.position, u.position) AS position,
            COALESCE(aud.official_id, u.official_id) AS official_id,
            COALESCE(aud.dob, u.dob) AS dob,
            COALESCE(aud.address, u.address) AS address,
            COALESCE(aud.subdistrict, u.subdistrict) AS subdistrict,
            COALESCE(aud.district, u.district) AS district,
            COALESCE(aud.province, u.province) AS user_province,
            COALESCE(aud.zipcode, u.zipcode) AS zipcode
        FROM vehicle_requests vr
        JOIN users u ON vr.user_id = u.id
        JOIN vehicles v ON vr.vehicle_id = v.id
        LEFT JOIN approved_user_data aud ON vr.id = aud.request_id
        LEFT JOIN admins a ON vr.approved_by_id = a.id
        WHERE vr.id = ?";


// 5. Execute Query และส่งผลลัพธ์
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

