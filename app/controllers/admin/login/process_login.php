<?php
// --- app/controllers/admin/login/process_login.php ---

// เริ่มต้น session เสมอ
session_start();

// เรียกใช้ไฟล์ที่จำเป็น
require_once '../../../models/db_config.php';
require_once '../../../models/log_helper.php';

// ตรวจสอบว่าฟอร์มถูกส่งมาด้วยเมธอด POST หรือไม่
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // สร้างการเชื่อมต่อฐานข้อมูล
    $conn = new mysqli($servername, $username, $password, $dbname);

    // ตรวจสอบการเชื่อมต่อ
    if ($conn->connect_error) {
        $_SESSION['admin_login_error'] = 'เกิดข้อผิดพลาดในการเชื่อมต่อฐานข้อมูล';
        header("location: ../../../views/admin/login/login.php");
        exit;
    }
    $conn->set_charset("utf8");

    // รับค่าจากฟอร์ม
    $input_username = trim($_POST["username"]);
    $input_password = $_POST["password"];

    // ตรวจสอบค่าว่าง
    if (empty($input_username) || empty($input_password)) {
        $_SESSION['admin_login_error'] = 'กรุณากรอกชื่อผู้ใช้และรหัสผ่าน';
        header("location: ../../../views/admin/login/login.php");
        exit;
    }

    // เตรียมคำสั่ง SQL เพื่อป้องกัน SQL Injection
    $sql = "SELECT id, username, password, title, firstname, role FROM admins WHERE username = ?";
    
    if ($stmt = $conn->prepare($sql)) {
        // ผูกตัวแปรกับ statement
        $stmt->bind_param("s", $param_username);
        $param_username = $input_username;
        
        // ประมวลผล statement
        if ($stmt->execute()) {
            $stmt->store_result();
            
            // ตรวจสอบว่าพบ username หรือไม่
            if ($stmt->num_rows == 1) {                    
                // ผูกผลลัพธ์กับตัวแปร
                $stmt->bind_result($id, $username, $hashed_password, $title, $firstname, $role);
                if ($stmt->fetch()) {
                    // ตรวจสอบรหัสผ่าน
                    if (password_verify($input_password, $hashed_password)) {
                        // รหัสผ่านถูกต้อง, เริ่ม session ใหม่
                        
                        // เก็บข้อมูลใน session
                        $_SESSION["admin_loggedin"] = true;
                        $_SESSION["admin_id"] = $id;
                        // $_SESSION["admin_username"] = $username;
                        // $_SESSION["admin_fullname"] = $title . $firstname;
                        // $_SESSION["admin_role"] = $role;                            
                        
                        // บันทึก Log การเข้าสู่ระบบสำเร็จ
                        log_activity($conn, 'admin_login_success');
                        
                        // ส่งต่อไปยังหน้า dashboard ของแอดมิน
                        header("location: ../../../views/admin/home/home.php");
                        exit;
                    } else {
                        // รหัสผ่านไม่ถูกต้อง
                        $_SESSION['admin_login_error'] = 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง';
                        log_activity($conn, 'admin_login_fail', ['username' => $input_username, 'reason' => 'Invalid password']);
                        header("location: ../../../views/admin/login/login.php");
                        exit;
                    }
                }
            } else {
                // ไม่พบ Username
                $_SESSION['admin_login_error'] = 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง';
                log_activity($conn, 'admin_login_fail', ['username' => $input_username, 'reason' => 'Username not found']);
                header("location: ../../../views/admin/login/login.php");
                exit;
            }
        } else {
            $_SESSION['admin_login_error'] = 'เกิดข้อผิดพลาดบางอย่าง โปรดลองอีกครั้ง';
            header("location: ../../../views/admin/login/login.php");
            exit;
        }

        // ปิด statement
        $stmt->close();
    }
    
    // ปิดการเชื่อมต่อ
    $conn->close();
} else {
    // หากไม่ได้เข้ามาด้วยเมธอด POST ให้กลับไปหน้า login
    header("location: ../../../views/admin/login/login.php");
    exit;
}
?>

