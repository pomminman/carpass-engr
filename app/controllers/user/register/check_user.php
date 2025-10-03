<?php
// app/c1ontrollers/user/register/check_user.php

// --- check_user.php (Secure Version - Updated) ---
// ไฟล์นี้ทำหน้าที่เป็น API สำหรับตรวจสอบว่าเบอร์โทรศัพท์หรือเลขบัตรประชาชน
// มีอยู่แล้วในฐานข้อมูลหรือไม่ และจะส่งผลลัพธ์กลับไปในรูปแบบ JSON

// --- 0. ตั้งค่า Header ---
header('Content-Type: application/json');

// --- เรียกใช้ไฟล์ตั้งค่าฐานข้อมูล ---
require_once '../../../models/db_config.php';

// --- ฟังก์ชันจัดการข้อผิดพลาด ---
function handle_error($user_message, $log_message = '') {
    http_response_code(500);
    echo json_encode(['error' => $user_message]);
    exit();
}

// --- สร้างการเชื่อมต่อ ---
$conn = new mysqli($servername, $username, $password, $dbname);

// --- ตรวจสอบการเชื่อมต่อ ---
if ($conn->connect_error) {
    handle_error('เกิดข้อผิดพลาดในการเชื่อมต่อเซิร์ฟเวอร์', 'Database Connection failed: ' . $conn->connect_error);
}
$conn->set_charset("utf8");

// --- เตรียมโครงสร้างการตอบกลับ (Response) เริ่มต้น ---
$response = [
    'phoneExists' => false,
    'nidExists'   => false,
    'error'       => null
];

// --- ตรวจสอบว่าเป็น Request แบบ POST เท่านั้น ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    $phone = $data['phone'] ?? null;
    $nid   = $data['nid'] ?? null;
    $user_id = $data['user_id'] ?? 0; // รับ user_id สำหรับการยกเว้น (ถ้ามี)

    if ($phone && is_numeric($phone) && strlen($phone) === 10) {
        $sql = "SELECT phone_number FROM users WHERE phone_number = ? AND id != ?";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("si", $phone, $user_id);
            if (!$stmt->execute()) {
                handle_error('เกิดข้อผิดพลาดในการค้นหาข้อมูล', 'SQL Execute Error: ' . $stmt->error);
            }
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $response['phoneExists'] = true;
            }
            $stmt->close();
        } else {
            handle_error('เกิดข้อผิดพลาดภายในระบบ', 'SQL Prepare Error: ' . $conn->error);
        }
    } elseif ($nid && is_numeric($nid) && strlen($nid) === 13) {
        $sql = "SELECT national_id FROM users WHERE national_id = ? AND id != ?";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("si", $nid, $user_id);
            if (!$stmt->execute()) {
                handle_error('เกิดข้อผิดพลาดในการค้นหาข้อมูล', 'SQL Execute Error: ' . $stmt->error);
            }
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $response['nidExists'] = true;
            }
            $stmt->close();
        } else {
            handle_error('เกิดข้อผิดพลาดภายในระบบ', 'SQL Prepare Error: ' . $conn->error);
        }
    } else {
        $response['error'] = 'ข้อมูลที่ส่งมาไม่ถูกต้อง';
    }
} else {
    // ถ้าไม่ใช่ POST request
    http_response_code(405); // 405 Method Not Allowed
    $response['error'] = 'Method Not Allowed';
}

// --- ปิดการเชื่อมต่อฐานข้อมูล ---
$conn->close();

// --- ส่งผลลัพธ์กลับไปเป็น JSON ---
echo json_encode($response);
?>
