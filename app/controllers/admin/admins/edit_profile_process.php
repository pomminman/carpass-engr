<?php
// app/controllers/admin/admins/edit_profile_process.php

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
    header("location: ../../../views/admin/home/edit_profile.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) handle_error('เกิดข้อผิดพลาดในการเชื่อมต่อฐานข้อมูล');
    $conn->set_charset("utf8");

    $admin_id_to_edit = $_SESSION['admin_id'];

    $update_fields = [];
    $params = [];
    $types = "";

    // --- Validate and prepare data ---
    $title = htmlspecialchars(strip_tags(trim($_POST['title'])));
    $firstname = htmlspecialchars(strip_tags(trim($_POST['firstname'])));
    $lastname = htmlspecialchars(strip_tags(trim($_POST['lastname'])));
    $username = trim($_POST['username']);

    if (empty($title) || empty($firstname) || empty($lastname) || empty($username)) {
        handle_error("กรุณากรอกข้อมูลที่จำเป็นให้ครบถ้วน");
    }
    
    // Check if new username already exists for ANOTHER admin
    $stmt_check_user = $conn->prepare("SELECT id FROM admins WHERE username = ? AND id != ?");
    $stmt_check_user->bind_param("si", $username, $admin_id_to_edit);
    $stmt_check_user->execute();
    if ($stmt_check_user->get_result()->num_rows > 0) {
        handle_error('ชื่อผู้ใช้งานนี้มีอยู่ในระบบแล้ว');
    }
    $stmt_check_user->close();

    array_push($update_fields, "title = ?", "firstname = ?", "lastname = ?", "username = ?");
    array_push($params, $title, $firstname, $lastname, $username);
    $types .= "ssss";

    // Handle password update
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if (!empty($new_password)) {
        if (strlen($new_password) < 6) {
            handle_error("รหัสผ่านใหม่ต้องมีอย่างน้อย 6 ตัวอักษร");
        }
        if ($new_password !== $confirm_password) {
            handle_error("รหัสผ่านใหม่และการยืนยันไม่ตรงกัน");
        }
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
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
            log_activity($conn, 'admin_edit_own_profile', ['edited_admin_id' => $admin_id_to_edit]);
            $_SESSION['flash_message'] = 'แก้ไขข้อมูลส่วนตัวสำเร็จ!';
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
    header("location: ../../../views/admin/home/edit_profile.php");
    exit;
}
?>
