<?php
// --- log_view_action.php ---

session_start();
header('Content-Type: application/json');
date_default_timezone_set('Asia/Bangkok');

// 1. ตรวจสอบสิทธิ์: ตรวจสอบว่าผู้ใช้ล็อกอินเข้าระบบแล้วหรือยัง
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['user_id'])) {
    // ถ้ายังไม่ได้ล็อกอิน ให้ส่งข้อความแจ้งเตือนกลับไป
    echo json_encode(['success' => false, 'message' => 'Authentication required.']);
    exit;
}

// 2. เรียกใช้ไฟล์ที่จำเป็น: ไฟล์ตั้งค่าฐานข้อมูล และไฟล์ฟังก์ชันสำหรับบันทึก Log
require_once '../../../models/db_config.php';
require_once '../../../models/log_helper.php';

// 3. เตรียมค่าเริ่มต้นสำหรับการตอบกลับ
$response = ['success' => false];

// 4. ตรวจสอบว่าเป็น Request แบบ POST เท่านั้น
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 5. รับข้อมูลที่ส่งมาจากหน้าเว็บ (JavaScript)
    $data = json_decode(file_get_contents('php://input'), true);
    $request_id = $data['request_id'] ?? null;

    // 6. ตรวจสอบว่าได้รับ ID ของคำร้องมาหรือไม่
    if ($request_id) {
        // 7. เชื่อมต่อฐานข้อมูล
        $conn = new mysqli($servername, $username, $password, $dbname);
        if (!$conn->connect_error) {
            $conn->set_charset("utf8");
            
            // 8. เรียกใช้ฟังก์ชันบันทึก Log
            // - action: 'user_view_request' เพื่อบอกว่าเป็นการดูข้อมูลคำร้อง
            // - details: ส่ง 'request_id' ไปด้วยเพื่อระบุว่าดูคำร้องใด
            log_activity($conn, 'user_view_request', ['request_id' => $request_id]);
            
            $conn->close();
            $response['success'] = true;
        }
    }
}

// 9. ส่งผลลัพธ์กลับไปให้ JavaScript
echo json_encode($response);
?>
