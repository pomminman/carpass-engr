<?php
// app/controllers/admin/login/process_login.php

session_start();
require_once '../../../models/db_config.php';
require_once '../../../models/log_helper.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        $_SESSION['admin_login_error'] = 'เกิดข้อผิดพลาดในการเชื่อมต่อฐานข้อมูล';
        header("location: ../../../views/admin/login/login.php");
        exit;
    }
    $conn->set_charset("utf8");

    $input_username = trim($_POST["username"]);
    $input_password = $_POST["password"];

    if (empty($input_username) || empty($input_password)) {
        $_SESSION['admin_login_error'] = 'กรุณากรอกชื่อผู้ใช้และรหัสผ่าน';
        header("location: ../../../views/admin/login/login.php");
        exit;
    }

    $sql = "SELECT id, password, title, firstname FROM admins WHERE username = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("s", $input_username);
        
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            if ($result->num_rows == 1) {                    
                $admin = $result->fetch_assoc();
                if (password_verify($input_password, $admin['password'])) {
                    // --- Login สำเร็จ ---
                    $_SESSION["admin_loggedin"] = true;
                    $_SESSION["admin_id"] = $admin['id'];
                    
                    // ตั้งค่า Session สำหรับ Flash Message ที่จะไปแสดงผลที่หน้า Dashboard
                    $_SESSION['flash_message'] = "เข้าสู่ระบบสำเร็จ! ยินดีต้อนรับ, " . htmlspecialchars($admin['title'] . $admin['firstname']);
                    $_SESSION['flash_status'] = "success";

                    log_activity($conn, 'admin_login_success');
                    
                    header("location: ../../../views/admin/home/dashboard.php");
                    exit;
                } else {
                    $_SESSION['admin_login_error'] = 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง';
                    log_activity($conn, 'admin_login_fail', ['username' => $input_username, 'reason' => 'Invalid password']);
                }
            } else {
                $_SESSION['admin_login_error'] = 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง';
                log_activity($conn, 'admin_login_fail', ['username' => $input_username, 'reason' => 'Username not found']);
            }
        } else {
            $_SESSION['admin_login_error'] = 'เกิดข้อผิดพลาดบางอย่าง โปรดลองอีกครั้ง';
        }
        $stmt->close();
    }
    $conn->close();
}

header("location: ../../../views/admin/login/login.php");
exit;
?>

