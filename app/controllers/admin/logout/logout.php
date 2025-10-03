<?php
// app/controllers/admin/logout/logout.php

session_start();

// --- บันทึก Log ก่อนออกจากระบบ ---
if (isset($_SESSION['admin_loggedin']) && $_SESSION['admin_loggedin'] === true) {
    require_once '../../../models/db_config.php';
    require_once '../../../models/log_helper.php';

    $conn = new mysqli($servername, $username, $password, $dbname);
    if (!$conn->connect_error) {
        $conn->set_charset("utf8");
        // log_activity function will automatically get admin_id from session
        log_activity($conn, 'admin_logout');
        $conn->close();
    }
}

// [EDIT] ตั้งค่า Flash Message สำหรับแสดงที่หน้า Login
$_SESSION['flash_message'] = "ออกจากระบบสำเร็จ";
$_SESSION['flash_status'] = "success";


// --- ล้างข้อมูล Session ที่เกี่ยวกับการล็อกอินของ Admin ---
unset($_SESSION['admin_loggedin']);
unset($_SESSION['admin_id']);

// --- Redirect กลับไปยังหน้า login ของ admin ---
// หน้า Login จะดึง flash message จาก session ไปแสดงผล
header("Location: ../../../views/admin/login/login.php");
exit;
?>

