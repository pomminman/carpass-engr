<?php
// app/models/log_helper.php

// --- models/log_helper.php ---
date_default_timezone_set('Asia/Bangkok');

/**
 * ฟังก์ชันสำหรับบันทึกกิจกรรมลงในตาราง activity_logs
 *
 * @param mysqli $conn Connection object to the database.
 * @param string $action คำอธิบายกิจกรรมที่ทำ เช่น 'login_success', 'create_request'.
 * @param array $details ข้อมูลเพิ่มเติมที่จะเก็บเป็น JSON.
 * @return void
 */
function log_activity($conn, $action, $details = []) {
    // ดึง ID ของผู้ใช้หรือแอดมินจาก Session (ถ้ามี)
    $user_id = $_SESSION['user_id'] ?? null;
    $admin_id = $_SESSION['admin_id'] ?? null;

    // ดึง IP Address และ User Agent ของผู้ใช้
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'UNKNOWN';

    // แปลง array ของ details เป็น JSON string
    $details_json = json_encode($details, JSON_UNESCAPED_UNICODE);

    $sql = "INSERT INTO activity_logs (user_id, admin_id, action, details, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?)";
    
    if ($stmt = $conn->prepare($sql)) {
        // ประเภทของ parameter คือ 'iissss'
        $stmt->bind_param("iissss", $user_id, $admin_id, $action, $details_json, $ip_address, $user_agent);
        $stmt->execute();
        $stmt->close();
    }
}
?>
