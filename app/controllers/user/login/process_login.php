<?php
// app/controllers/user/login/process_login.php

session_start();
require_once '../../../models/db_config.php';
require_once '../../../models/log_helper.php'; // เรียกใช้ไฟล์สำหรับบันทึกกิจกรรม (Log)

header('Content-Type: application/json');
$response = ['success' => false, 'message' => 'เกิดข้อผิดพลาดที่ไม่คาดคิด'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        $response['message'] = 'เกิดข้อผิดพลาดในการเชื่อมต่อฐานข้อมูล';
        echo json_encode($response);
        exit;
    }
    $conn->set_charset("utf8");

    $phone = preg_replace('/\\D/', '', $_POST['phone'] ?? '');
    $national_id = preg_replace('/\\D/', '', $_POST['national_id'] ?? '');

    if (empty($phone) || empty($national_id)) {
        $response['message'] = 'กรุณากรอกข้อมูลให้ครบถ้วน';
        echo json_encode($response);
        exit;
    }

    $sql = "SELECT id, title, firstname, lastname FROM users WHERE phone_number = ? AND national_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $phone, $national_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        $_SESSION['loggedin'] = true;
        $_SESSION['user_id'] = $user['id'];

        // [START] ***** EDITED CODE *****
        // ตั้งค่า session สำหรับ flash message ให้สอดคล้องกับระบบแจ้งเตือนหลัก
        $_SESSION['request_status'] = 'success';
        $_SESSION['request_message'] = "เข้าสู่ระบบสำเร็จ ยินดีต้อนรับ!";
        // [END] ***** EDITED CODE *****

        // บันทึก Log การเข้าสู่ระบบสำเร็จ
        log_activity($conn, 'login_success');

        $response['success'] = true;
        $response['redirect_url'] = '../../../views/user/home/dashboard.php';
        $response['user'] = [
            'title' => $user['title'],
            'firstname' => $user['firstname'],
            'lastname' => $user['lastname']
        ];
    } else {
        // บันทึก Log การเข้าสู่ระบบไม่สำเร็จ
        log_activity($conn, 'login_fail', ['phone' => $phone, 'national_id' => $national_id]);
        $response['message'] = 'เบอร์โทรศัพท์หรือเลขบัตรประชาชนไม่ถูกต้อง';
    }

    $stmt->close();
    $conn->close();
}

echo json_encode($response);
?>
