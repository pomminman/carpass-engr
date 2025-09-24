<?php
// --- app/controllers/admin/register/process_register.php ---
session_start();
date_default_timezone_set('Asia/Bangkok');

if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true) {
    header("location: ../../../views/admin/login/login.php");
    exit;
}

require_once '../../../models/db_config.php';
require_once '../../../models/log_helper.php';

function handle_error($message) {
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_status'] = 'error';
    header("location: ../../../views/admin/home/manage_admins.php");
    exit;
}


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) handle_error('เกิดข้อผิดพลาดในการเชื่อมต่อฐานข้อมูล');
    $conn->set_charset("utf8");

    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $sql_check = "SELECT id FROM admins WHERE username = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("s", $username);
    $stmt_check->execute();
    if ($stmt_check->get_result()->num_rows > 0) handle_error('ชื่อผู้ใช้งานนี้มีอยู่ในระบบแล้ว');
    $stmt_check->close();
    
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $title = htmlspecialchars(strip_tags(trim($_POST['title'])));
    $firstname = htmlspecialchars(strip_tags(trim($_POST['firstname'])));
    $lastname = htmlspecialchars(strip_tags(trim($_POST['lastname'])));
    $department = htmlspecialchars(strip_tags(trim($_POST['department'])));
    $role = htmlspecialchars(strip_tags(trim($_POST['role'])));
    $view_permission = (int)($_POST['view_permission'] ?? 0);
    $created_by = $_SESSION['admin_id'];

    $sql = "INSERT INTO admins (username, password, title, firstname, lastname, department, role, view_permission, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssssis", $username, $hashed_password, $title, $firstname, $lastname, $department, $role, $view_permission, $created_by);
    
    if ($stmt->execute()) {
        $new_admin_id = $stmt->insert_id;
        log_activity($conn, 'admin_create_account', ['created_admin_id' => $new_admin_id, 'username' => $username]);
        $_SESSION['flash_message'] = 'เพิ่มเจ้าหน้าที่ใหม่สำเร็จ!';
        $_SESSION['flash_status'] = 'success';
    } else {
        log_activity($conn, 'admin_create_fail', ['error' => $stmt->error]);
        handle_error('เกิดข้อผิดพลาดในการสร้างบัญชี: ' . $stmt->error);
    }
    $stmt->close();
    $conn->close();
    
    header("location: ../../../views/admin/home/manage_admins.php");
    exit;
}
?>

