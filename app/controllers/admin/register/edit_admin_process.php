<?php
// app/controllers/admin/register/edit_admin_process.php

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

    // --- Permission Check ---
    $current_admin_id = $_SESSION['admin_id'];
    $stmt_role = $conn->prepare("SELECT role FROM admins WHERE id = ?");
    $stmt_role->bind_param("i", $current_admin_id);
    $stmt_role->execute();
    $result_role = $stmt_role->get_result();
    if ($result_role->num_rows === 0) handle_error("ไม่พบข้อมูลผู้ใช้ปัจจุบัน");
    $current_admin_role = $result_role->fetch_assoc()['role'];
    $stmt_role->close();

    if (!in_array($current_admin_role, ['admin', 'superadmin'])) {
        handle_error("คุณไม่มีสิทธิ์แก้ไขข้อมูล");
    }

    $admin_id_to_edit = filter_input(INPUT_POST, 'admin_id', FILTER_VALIDATE_INT);
    if (!$admin_id_to_edit) handle_error("รหัสเจ้าหน้าที่ไม่ถูกต้อง");

    $update_fields = [];
    $params = [];
    $types = "";

    $title = htmlspecialchars(strip_tags(trim($_POST['title'])));
    $firstname = htmlspecialchars(strip_tags(trim($_POST['firstname'])));
    $lastname = htmlspecialchars(strip_tags(trim($_POST['lastname'])));
    $department = htmlspecialchars(strip_tags(trim($_POST['department'])));
    $role = htmlspecialchars(strip_tags(trim($_POST['role'])));
    $view_permission = (int)($_POST['view_permission'] ?? 0);
    
    array_push($update_fields, "title = ?", "firstname = ?", "lastname = ?", "department = ?", "role = ?", "view_permission = ?");
    array_push($params, $title, $firstname, $lastname, $department, $role, $view_permission);
    $types .= "sssssi";

    // Handle password update
    $password = $_POST['password'];
    if (!empty($password)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $update_fields[] = "password = ?";
        $params[] = $hashed_password;
        $types .= "s";
    }

    if (!empty($update_fields)) {
        $sql = "UPDATE admins SET " . implode(", ", $update_fields) . " WHERE id = ?";
        $params[] = $admin_id_to_edit;
        $types .= "i";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);

        if ($stmt->execute()) {
            log_activity($conn, 'admin_edit_account', ['edited_admin_id' => $admin_id_to_edit]);
            $_SESSION['flash_message'] = 'แก้ไขข้อมูลเจ้าหน้าที่สำเร็จ!';
            $_SESSION['flash_status'] = 'success';
        } else {
            handle_error('เกิดข้อผิดพลาดในการอัปเดตข้อมูล: ' . $stmt->error);
        }
        $stmt->close();
    } else {
        $_SESSION['flash_message'] = 'ไม่มีข้อมูลที่ถูกเปลี่ยนแปลง';
        $_SESSION['flash_status'] = 'info';
    }

    $conn->close();
    header("location: ../../../views/admin/home/manage_admins.php");
    exit;
}
?>

