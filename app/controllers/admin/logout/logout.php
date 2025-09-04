<?php
// --- app/controllers/admin/login/logout.php ---

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

// --- ล้างข้อมูล Session ทั้งหมดของ Admin ---
unset($_SESSION['admin_loggedin']);
unset($_SESSION['admin_id']);

// ทำลาย Session เพื่อความปลอดภัย
session_destroy();

// --- Redirect กลับไปยังหน้า login ของ admin ---
header("Location: ../../../views/admin/login/login.php");
exit;
?>
