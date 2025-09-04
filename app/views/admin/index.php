<?php
// เริ่มต้น session เพื่อตรวจสอบสถานะการล็อกอิน
session_start();

// ตรวจสอบว่ามี session 'loggedin' และมีค่าเป็น true หรือไม่
if (isset($_SESSION['admin_loggedin']) && $_SESSION['admin_loggedin'] === true) {
    // หากผู้ใช้ล็อกอินอยู่แล้ว ให้ redirect ไปยังหน้า home
    header("Location: home/home.php");
    exit;
} else {
    // หากผู้ใช้ยังไม่ได้ล็อกอิน ให้ redirect ไปยังหน้า login
    header("Location: login/login.php");
    exit;
}
?>
