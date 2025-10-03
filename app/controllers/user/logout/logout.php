<?php
// app/controllers/user/logout/logout.php

session_start();

// --- บันทึก Log ก่อนออกจากระบบ ---
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    require_once '../../../models/db_config.php';
    require_once '../../../models/log_helper.php';

    $conn = new mysqli($servername, $username, $password, $dbname);
    if (!$conn->connect_error) {
        $conn->set_charset("utf8");
        log_activity($conn, 'logout');
        $conn->close();
    }
}

// --- [เพิ่ม] ตั้งค่า session สำหรับแจ้งเตือนการออกจากระบบ ---
$_SESSION['logout_message'] = "ออกจากระบบสำเร็จ";

// ล้างข้อมูล session ที่เกี่ยวกับการล็อกอิน
unset($_SESSION['loggedin']);
unset($_SESSION['user_id']);

// Redirect กลับไปยังหน้า login
header("Location: ../../../views/user/login/login.php");
exit;
?>
