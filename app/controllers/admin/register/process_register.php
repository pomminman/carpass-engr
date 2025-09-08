<?php
// --- app/controllers/admin/register/process_register.php ---
session_start();
date_default_timezone_set('Asia/Bangkok');

// --- [แก้ไข] เพิ่มการตรวจสอบสิทธิ์: ต้องเป็นแอดมินที่ล็อกอินอยู่แล้ว ---
if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true) {
    // [แก้ไข] Redirect ไปยังหน้า login หากยังไม่ได้ login
    header("location: ../../../views/admin/login/login.php");
    exit;
}

require_once '../../../models/db_config.php';
require_once '../../../models/log_helper.php';

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    $_SESSION['register_status'] = 'error';
    $_SESSION['register_message'] = 'เกิดข้อผิดพลาดในการเชื่อมต่อฐานข้อมูล';
    header("location: ../../../views/admin/admins/manage_admins.php");
    exit;
}
$conn->set_charset("utf8");

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // รับและตรวจสอบข้อมูล
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // ตรวจสอบรหัสผ่าน
    if ($password !== $confirm_password) {
        $_SESSION['register_status'] = 'error';
        $_SESSION['register_message'] = 'รหัสผ่านและการยืนยันรหัสผ่านไม่ตรงกัน';
        header("location: ../../../views/admin/admins/manage_admins.php");
        exit;
    }
    
    // ตรวจสอบ Username ซ้ำ
    $sql_check = "SELECT id FROM admins WHERE username = ?";
    if ($stmt_check = $conn->prepare($sql_check)) {
        $stmt_check->bind_param("s", $username);
        $stmt_check->execute();
        $stmt_check->store_result();
        if ($stmt_check->num_rows > 0) {
            $_SESSION['register_status'] = 'error';
            $_SESSION['register_message'] = 'ชื่อผู้ใช้งานนี้มีอยู่ในระบบแล้ว';
            header("location: ../../../views/admin/admins/manage_admins.php");
            exit;
        }
        $stmt_check->close();
    }
    
    // เข้ารหัสรหัสผ่าน
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // จัดการกับ Title
    $title_choice = htmlspecialchars(strip_tags(trim($_POST['title'])));
    $final_title = ($title_choice === 'other') ? htmlspecialchars(strip_tags(trim($_POST['title_other']))) : $title_choice;

    // จัดการกับ Department
    $department_choice = htmlspecialchars(strip_tags(trim($_POST['department'])));
    $final_department = ($department_choice === 'other') ? htmlspecialchars(strip_tags(trim($_POST['department_other']))) : $department_choice;

    // เตรียมข้อมูลอื่นๆ
    $firstname = htmlspecialchars(strip_tags(trim($_POST['firstname'])));
    $lastname = htmlspecialchars(strip_tags(trim($_POST['lastname'])));
    $phone_number = htmlspecialchars(strip_tags(trim($_POST['phone_number'])));
    $position = htmlspecialchars(strip_tags(trim($_POST['position'])));
    $role = htmlspecialchars(strip_tags(trim($_POST['role'])));
    $view_permission = (int)($_POST['view_permission'] ?? 0);
    $created_by = $_SESSION['admin_id'];

    // บันทึกข้อมูล
    $sql = "INSERT INTO admins (username, password, title, firstname, lastname, phone_number, position, department, role, view_permission, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("sssssssssii", $username, $hashed_password, $final_title, $firstname, $lastname, $phone_number, $position, $final_department, $role, $view_permission, $created_by);
        
        if ($stmt->execute()) {
            $new_admin_id = $stmt->insert_id;
            log_activity($conn, 'admin_create_account', ['created_admin_id' => $new_admin_id, 'username' => $username]);
            $_SESSION['register_status'] = 'success';
            $_SESSION['register_message'] = 'สร้างบัญชีผู้ดูแลระบบสำเร็จ!';
        } else {
            log_activity($conn, 'admin_create_fail', ['error' => $stmt->error]);
            $_SESSION['register_status'] = 'error';
            $_SESSION['register_message'] = 'เกิดข้อผิดพลาดในการสร้างบัญชี: ' . $stmt->error;
        }
        $stmt->close();
    } else {
        $_SESSION['register_status'] = 'error';
        $_SESSION['register_message'] = 'เกิดข้อผิดพลาด: ' . $conn->error;
    }
    $conn->close();
    
    // [แก้ไข] Redirect กลับไปหน้า manage_admins.php
    header("location: ../../../views/admin/admins/manage_admins.php");
    exit;
}
?>
