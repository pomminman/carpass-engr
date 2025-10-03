<?php
// app/controllers/user/activity/log_user_process.php

session_start();
header('Content-Type: application/json');
date_default_timezone_set('Asia/Bangkok');

// 1. ตรวจสอบสิทธิ์: ตรวจสอบว่าผู้ใช้ล็อกอินเข้าระบบแล้วหรือยัง
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Authentication required.']);
    exit;
}

// 2. เรียกใช้ไฟล์ที่จำเป็น
require_once '../../../models/db_config.php';
require_once '../../../models/log_helper.php';

// 3. รับข้อมูลที่ส่งมา
$data = json_decode(file_get_contents('php://input'), true);
$request_id = filter_var($data['request_id'] ?? null, FILTER_VALIDATE_INT);

if ($request_id) {
    // 4. เชื่อมต่อฐานข้อมูล
    $conn = new mysqli($servername, $username, $password, $dbname);
    if (!$conn->connect_error) {
        $conn->set_charset("utf8");
        
        // 5. เรียกใช้ฟังก์ชันบันทึก Log
        log_activity($conn, 'user_view_request_details', ['request_id' => $request_id]);
        
        $conn->close();
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request ID.']);
}
?>
